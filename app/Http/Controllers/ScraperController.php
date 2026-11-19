<?php

namespace App\Http\Controllers;

use App\Exceptions\ApifyException;
use App\Exceptions\ApifyTimeoutException;
use App\Services\InstagramScraperService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ScraperController extends Controller
{
    /**
     * Create a new ScraperController instance.
     */
    public function __construct(
        protected InstagramScraperService $scraperService
    ) {}

    /**
     * Display the scraper index view.
     */
    public function index(): View
    {
        return view('scraper.index');
    }

    /**
     * Scrape Instagram data using Apify.
     */
    public function scrape(Request $request): JsonResponse
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        @ini_set('max_execution_time', '180');

        try {
            $validated = $request->validate([
                'mode' => ['required', 'string', 'in:profile,posts,hashtags'],
                'target' => ['required', 'string', 'max:255'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
                'async' => ['nullable', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 10;

        if ($request->boolean('async')) {
            try {
                $runData = $this->scraperService->startScrape(
                    mode: $validated['mode'],
                    target: $validated['target'],
                    limit: $limit
                );

                return response()->json([
                    'status' => 'queued',
                    'run_id' => $runData['id'] ?? null,
                    'mode' => $validated['mode'],
                    'target' => $validated['target'],
                    'limit' => $limit,
                ]);
            } catch (ApifyException $e) {
                return response()->json([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ], 422);
            } catch (Throwable $e) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'An unexpected error occurred while starting the scraping run: '.$e->getMessage(),
                ], 500);
            }
        }

        try {
            $result = $this->scraperService->scrape(
                mode: $validated['mode'],
                target: $validated['target'],
                limit: $limit
            );

            return response()->json($result);
        } catch (ApifyTimeoutException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 504);
        } catch (ApifyException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'An unexpected error occurred while processing the scraping request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check the status of an ongoing Apify Actor run.
     */
    public function status(Request $request, string $runId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mode' => ['required', 'string', 'in:profile,posts,hashtags'],
                'target' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $result = $this->scraperService->checkRunStatus(
                runId: $runId,
                mode: $validated['mode'],
                target: $validated['target']
            );

            $statusCode = ($result['status'] ?? '') === 'failed' ? 422 : 200;

            return response()->json($result, $statusCode);
        } catch (ApifyException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to check scraping status: '.$e->getMessage(),
            ], 500);
        }
    }
}
