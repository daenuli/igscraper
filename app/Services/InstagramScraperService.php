<?php

namespace App\Services;

use App\Exceptions\ApifyException;
use App\Exceptions\ApifyTimeoutException;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Http;

class InstagramScraperService
{
    /**
     * Create a new InstagramScraperService instance.
     */
    public function __construct(
        public ?string $token = null,
        public ?string $userId = null,
        public string $actorId = 'apify/instagram-scraper',
        public int $timeout = 120
    ) {
        if (Container::getInstance()?->bound('config')) {
            $this->token = $this->token ?? config('services.apify.token');
            $this->userId = $this->userId ?? config('services.apify.user_id');
            $this->actorId = config('services.apify.actor_id') ?? $this->actorId;
            $this->timeout = (int) (config('services.apify.timeout') ?? $this->timeout);
        }
    }

    /**
     * Execute a full scraping flow for the given mode and target.
     *
     * @return array<string, mixed>
     *
     * @throws ApifyException
     * @throws ApifyTimeoutException
     */
    public function scrape(string $mode, string $target, int $limit = 10): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(max(180, $this->timeout + 30));
        }
        @ini_set('max_execution_time', (string) max(180, $this->timeout + 30));

        if (empty($this->token)) {
            throw new ApifyException('Apify API token is not configured. Please set APIFY_TOKEN in your environment.');
        }

        $actorInput = $this->buildActorInput($mode, $target, $limit);
        $runData = $this->startRun($actorInput);
        $runId = $runData['id'] ?? null;

        if (! $runId) {
            throw new ApifyException('Apify failed to return a valid Actor run ID.');
        }

        $finalRun = $this->waitForRun($runId);
        $datasetId = $finalRun['defaultDatasetId'] ?? null;

        $items = $datasetId ? $this->getDatasetItems($datasetId) : [];
        $transformed = $this->transformResults($mode, $items, $target);

        $isEmpty = empty($transformed) || (is_array($transformed) && count($transformed) === 0);

        return [
            'status' => $isEmpty ? 'empty' : 'completed',
            'mode' => $mode,
            'target' => $target,
            'data' => $transformed,
            'raw' => $items,
            'count' => is_array($transformed) ? count($transformed) : ($isEmpty ? 0 : 1),
        ];
    }

    /**
     * Start an Actor run asynchronously and return run metadata.
     *
     * @return array<string, mixed>
     *
     * @throws ApifyException
     */
    public function startScrape(string $mode, string $target, int $limit = 10): array
    {
        if (empty($this->token)) {
            throw new ApifyException('Apify API token is not configured. Please set APIFY_TOKEN in your environment.');
        }

        $actorInput = $this->buildActorInput($mode, $target, $limit);

        return $this->startRun($actorInput);
    }

    /**
     * Check the current status of an Actor run and retrieve results if completed.
     *
     * @return array<string, mixed>
     *
     * @throws ApifyException
     */
    public function checkRunStatus(string $runId, string $mode, string $target): array
    {
        if (empty($this->token)) {
            throw new ApifyException('Apify API token is not configured. Please set APIFY_TOKEN in your environment.');
        }

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(15)
            ->get("https://api.apify.com/v2/actor-runs/{$runId}");

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new ApifyException("Failed to check Actor run status: {$errorMsg}");
        }

        $runData = $response->json('data') ?? [];
        $status = $runData['status'] ?? 'UNKNOWN';

        if ($status === 'READY') {
            return [
                'status' => 'queued',
                'run_id' => $runId,
            ];
        }

        if ($status === 'RUNNING') {
            return [
                'status' => 'running',
                'run_id' => $runId,
            ];
        }

        if ($status === 'SUCCEEDED') {
            $datasetId = $runData['defaultDatasetId'] ?? null;
            $items = $datasetId ? $this->getDatasetItems($datasetId) : [];
            $transformed = $this->transformResults($mode, $items, $target);

            $isEmpty = empty($transformed) || (is_array($transformed) && count($transformed) === 0);

            return [
                'status' => $isEmpty ? 'empty' : 'completed',
                'mode' => $mode,
                'target' => $target,
                'data' => $transformed,
                'raw' => $items,
                'count' => is_array($transformed) ? count($transformed) : ($isEmpty ? 0 : 1),
            ];
        }

        $statusMessage = $runData['statusMessage'] ?? "Actor run ended with status {$status}.";

        return [
            'status' => 'failed',
            'run_id' => $runId,
            'message' => "Scraper execution {$status}: {$statusMessage}",
        ];
    }

    /**
     * Build the input payload required by the Apify instagram-scraper Actor.
     *
     * @return array<string, mixed>
     */
    public function buildActorInput(string $mode, string $target, int $limit = 10): array
    {
        $sanitizedLimit = max(1, min($limit, 50));

        return match ($mode) {
            'profile' => [
                'directUrls' => [$this->formatProfileUrl($target)],
                'resultsType' => 'details',
                'resultsLimit' => 1,
            ],
            'posts' => [
                'directUrls' => [$this->formatPostsUrl($target)],
                'resultsType' => 'posts',
                'resultsLimit' => $sanitizedLimit,
            ],
            'hashtags' => [
                'directUrls' => [$this->formatHashtagUrl($target)],
                'resultsType' => 'posts',
                'resultsLimit' => $sanitizedLimit,
            ],
            default => throw new ApifyException("Unsupported scraper mode: {$mode}"),
        };
    }

    /**
     * Start an Actor run through the Apify REST API.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ApifyException
     */
    public function startRun(array $input): array
    {
        $formattedActor = str_replace('/', '~', $this->actorId);
        $url = "https://api.apify.com/v2/acts/{$formattedActor}/runs";

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($url, $input);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new ApifyException("Failed to start Apify actor run: {$message}");
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new ApifyException('Invalid response structure received from Apify API when starting run.');
        }

        return $data;
    }

    /**
     * Poll the Actor run status until completion, failure, or timeout.
     *
     * @return array<string, mixed>
     *
     * @throws ApifyException
     * @throws ApifyTimeoutException
     */
    public function waitForRun(string $runId, int $pollInterval = 2): array
    {
        $startTime = time();
        $terminalStatuses = ['SUCCEEDED', 'FAILED', 'TIMED-OUT', 'ABORTED'];

        while (true) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(max(180, $this->timeout + 30));
            }

            if ((time() - $startTime) >= $this->timeout) {
                throw new ApifyTimeoutException("Apify scraper run timed out after {$this->timeout} seconds.");
            }

            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(15)
                ->get("https://api.apify.com/v2/actor-runs/{$runId}");

            if (! $response->successful()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                throw new ApifyException("Failed to check Actor run status: {$errorMsg}");
            }

            $runData = $response->json('data') ?? [];
            $status = $runData['status'] ?? 'UNKNOWN';

            if (in_array($status, $terminalStatuses, true)) {
                if ($status !== 'SUCCEEDED') {
                    $statusMessage = $runData['statusMessage'] ?? "Actor run ended with status {$status}.";
                    throw new ApifyException("Scraper execution {$status}: {$statusMessage}");
                }

                return $runData;
            }

            sleep($pollInterval);
        }
    }

    /**
     * Retrieve items from the default dataset produced by the run.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws ApifyException
     */
    public function getDatasetItems(string $datasetId, int $limit = 100): array
    {
        $url = "https://api.apify.com/v2/datasets/{$datasetId}/items";

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(20)
            ->get($url, [
                'clean' => 'true',
                'limit' => $limit,
            ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new ApifyException("Failed to retrieve dataset items: {$errorMsg}");
        }

        $items = $response->json();

        return is_array($items) ? $items : [];
    }

    /**
     * Transform raw dataset items into structured frontend data.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function transformResults(string $mode, array $items, string $target): array
    {
        if (empty($items)) {
            return [];
        }

        if ($mode === 'profile') {
            return $this->transformProfile($items[0], $this->cleanUsername($target));
        }

        return array_map(fn (array $item): array => $this->transformPost($item), $items);
    }

    /**
     * Transform a raw profile item.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function transformProfile(array $item, string $fallbackUsername = ''): array
    {
        $username = $item['username'] ?? $item['ownerUsername'] ?? $fallbackUsername;
        $followers = $item['followersCount'] ?? $item['followers'] ?? 0;
        $following = $item['followsCount'] ?? $item['followingCount'] ?? $item['following'] ?? 0;
        $postsCount = $item['postsCount'] ?? $item['mediaCount'] ?? 0;

        return [
            'id' => (string) ($item['id'] ?? $item['pk'] ?? ''),
            'username' => (string) $username,
            'fullName' => (string) ($item['fullName'] ?? $item['name'] ?? $username),
            'biography' => (string) ($item['biography'] ?? $item['bio'] ?? ''),
            'profilePicUrl' => (string) ($item['profilePicUrlHD'] ?? $item['profilePicUrl'] ?? $item['profile_pic_url'] ?? ''),
            'externalUrl' => (string) ($item['externalUrl'] ?? $item['external_url'] ?? $item['url'] ?? ''),
            'followersCount' => (int) $followers,
            'followingCount' => (int) $following,
            'postsCount' => (int) $postsCount,
            'isVerified' => (bool) ($item['verified'] ?? $item['isVerified'] ?? $item['is_verified'] ?? false),
            'category' => (string) ($item['businessCategoryName'] ?? $item['category'] ?? $item['categoryName'] ?? 'Public Profile'),
            'scrapedAt' => Container::getInstance()?->bound('config') ? now()->toIso8601String() : date('c'),
        ];
    }

    /**
     * Transform a raw post or hashtag media item.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function transformPost(array $item): array
    {
        $shortcode = $item['shortCode'] ?? $item['shortcode'] ?? $item['code'] ?? '';
        $url = $item['url'] ?? ($shortcode ? "https://www.instagram.com/p/{$shortcode}/" : '#');

        $displayUrl = $item['displayUrl'] ?? $item['imageUrl'] ?? $item['thumbnailUrl'] ?? null;
        if (! $displayUrl && ! empty($item['images']) && is_array($item['images'])) {
            $firstImage = $item['images'][0];
            $displayUrl = is_string($firstImage) ? $firstImage : ($firstImage['url'] ?? null);
        }

        $timestamp = $item['timestamp'] ?? $item['takenAt'] ?? null;
        if ($timestamp && is_numeric($timestamp)) {
            $timestamp = date('c', (int) $timestamp);
        } elseif (! $timestamp) {
            $timestamp = Container::getInstance()?->bound('config') ? now()->toIso8601String() : date('c');
        }

        $contentType = $this->determineContentType($item);
        $rawProductType = $item['productType'] ?? null;
        $productTypeStr = is_array($rawProductType)
            ? ($rawProductType['name'] ?? json_encode($rawProductType))
            : (string) ($rawProductType ?? '');

        $childCount = 0;
        if (! empty($item['childPosts']) && is_array($item['childPosts'])) {
            $childCount = count($item['childPosts']);
        } elseif (! empty($item['images']) && is_array($item['images'])) {
            $childCount = count($item['images']);
        }

        return [
            'id' => (string) ($item['id'] ?? $shortcode ?? uniqid('post_')),
            'shortcode' => (string) $shortcode,
            'caption' => (string) ($item['caption'] ?? $item['text'] ?? ''),
            'likesCount' => (int) ($item['likesCount'] ?? $item['likes'] ?? 0),
            'commentsCount' => (int) ($item['commentsCount'] ?? $item['comments'] ?? 0),
            'timestamp' => (string) $timestamp,
            'displayUrl' => (string) ($displayUrl ?? ''),
            'url' => (string) $url,
            'type' => (string) ($item['type'] ?? ''),
            'productType' => $productTypeStr,
            'contentType' => $contentType,
            'childCount' => $childCount,
            'videoUrl' => (string) ($item['videoUrl'] ?? ''),
            'videoViewCount' => isset($item['videoViewCount']) ? (int) $item['videoViewCount'] : null,
            'videoPlayCount' => isset($item['videoPlayCount']) ? (int) $item['videoPlayCount'] : null,
        ];
    }

    /**
     * Determine the specific content type (Reel, Carousel, Video, Photo)
     * using the productType metadata and type fields.
     *
     * @param  array<string, mixed>  $item
     */
    public function determineContentType(array $item): string
    {
        $productType = $item['productType'] ?? null;
        if (is_array($productType)) {
            $productType = $productType['name'] ?? $productType['type'] ?? '';
        }
        $productType = strtolower(trim((string) $productType));

        $type = strtolower(trim((string) ($item['type'] ?? '')));
        $url = strtolower(trim((string) ($item['url'] ?? '')));

        // 1. Reels: productType "clips", type "reel", or reel URL format
        if ($productType === 'clips' || $type === 'reel' || str_contains($url, '/reel/')) {
            return 'Reel';
        }

        // 2. Carousels: productType "carousel_container", type "sidecar"/"carousel", or multiple slides/child posts
        if (
            $productType === 'carousel_container'
            || $type === 'sidecar'
            || $type === 'carousel'
            || (! empty($item['childPosts']) && is_array($item['childPosts']))
            || (! empty($item['images']) && is_array($item['images']) && count($item['images']) > 1)
        ) {
            return 'Carousel';
        }

        // 3. IGTV long-form videos
        if ($productType === 'igtv') {
            return 'IGTV';
        }

        // 4. Standalone Video feed post
        if ($type === 'video' || ! empty($item['videoUrl'])) {
            return 'Video';
        }

        // 5. Default to standard Photo post
        return 'Photo';
    }

    /**
     * Clean and format target username.
     */
    public function cleanUsername(string $input): string
    {
        $trimmed = trim($input);

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($trimmed, PHP_URL_PATH), '/');
            $segments = explode('/', $path);

            return preg_replace('/[^A-Za-z0-9._]/', '', $segments[0] ?? '') ?: $trimmed;
        }

        return ltrim(preg_replace('/[^A-Za-z0-9._]/', '', $trimmed), '@');
    }

    /**
     * Clean and format target hashtag.
     */
    public function cleanHashtag(string $input): string
    {
        $trimmed = trim($input);

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($trimmed, PHP_URL_PATH), '/');
            $segments = explode('/', $path);
            $tagIndex = array_search('tags', $segments, true);

            if ($tagIndex !== false && isset($segments[$tagIndex + 1])) {
                return ltrim($segments[$tagIndex + 1], '#');
            }
        }

        return ltrim($trimmed, '#');
    }

    /**
     * Format full Instagram profile URL.
     */
    public function formatProfileUrl(string $target): string
    {
        if (filter_var($target, FILTER_VALIDATE_URL) && str_contains($target, 'instagram.com')) {
            return $target;
        }

        $username = $this->cleanUsername($target);

        return "https://www.instagram.com/{$username}/";
    }

    /**
     * Format full Instagram posts target URL.
     */
    public function formatPostsUrl(string $target): string
    {
        if (filter_var($target, FILTER_VALIDATE_URL) && str_contains($target, 'instagram.com')) {
            return $target;
        }

        $username = $this->cleanUsername($target);

        return "https://www.instagram.com/{$username}/";
    }

    /**
     * Format full Instagram hashtag target URL.
     */
    public function formatHashtagUrl(string $target): string
    {
        if (filter_var($target, FILTER_VALIDATE_URL) && str_contains($target, 'instagram.com/explore/tags')) {
            return $target;
        }

        $tag = $this->cleanHashtag($target);

        return "https://www.instagram.com/explore/tags/{$tag}/";
    }
}
