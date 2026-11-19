<?php

use Illuminate\Support\Facades\Http;

test('it renders the scraper index view', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertViewIs('scraper.index')
        ->assertSee('InstaScrape — Instagram Data, Made Simple')
        ->assertSee('csrf-token', false);
});

test('it validates scraper request inputs', function () {
    $response = $this->postJson('/scrape', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['mode', 'target']);

    $invalidModeResponse = $this->postJson('/scrape', [
        'mode' => 'invalid_mode',
        'target' => 'laravelphp',
    ]);

    $invalidModeResponse->assertStatus(422)
        ->assertJsonValidationErrors(['mode']);
});

test('it scrapes profile mode successfully', function () {
    Http::fake([
        'https://api.apify.com/v2/acts/*/runs' => Http::response([
            'data' => [
                'id' => 'run_profile_123',
                'status' => 'RUNNING',
                'defaultDatasetId' => 'dataset_profile_123',
            ],
        ], 201),
        'https://api.apify.com/v2/actor-runs/run_profile_123' => Http::response([
            'data' => [
                'id' => 'run_profile_123',
                'status' => 'SUCCEEDED',
                'defaultDatasetId' => 'dataset_profile_123',
            ],
        ], 200),
        'https://api.apify.com/v2/datasets/dataset_profile_123/items*' => Http::response([
            [
                'id' => '839210491',
                'username' => 'laravelphp',
                'fullName' => 'Laravel Framework',
                'biography' => 'The PHP Framework for Web Artisans.',
                'profilePicUrlHD' => 'https://example.com/avatar.jpg',
                'externalUrl' => 'https://laravel.com',
                'followersCount' => 125000,
                'followsCount' => 20,
                'postsCount' => 350,
                'verified' => true,
                'businessCategoryName' => 'Software Company',
            ],
        ], 200),
    ]);

    $response = $this->postJson('/scrape', [
        'mode' => 'profile',
        'target' => '@laravelphp',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'completed',
            'mode' => 'profile',
            'target' => '@laravelphp',
            'data' => [
                'id' => '839210491',
                'username' => 'laravelphp',
                'fullName' => 'Laravel Framework',
                'biography' => 'The PHP Framework for Web Artisans.',
                'followersCount' => 125000,
                'followingCount' => 20,
                'postsCount' => 350,
                'isVerified' => true,
                'category' => 'Software Company',
            ],
        ]);
});

test('it scrapes posts mode successfully', function () {
    Http::fake([
        'https://api.apify.com/v2/acts/*/runs' => Http::response([
            'data' => [
                'id' => 'run_posts_123',
                'status' => 'RUNNING',
                'defaultDatasetId' => 'dataset_posts_123',
            ],
        ], 201),
        'https://api.apify.com/v2/actor-runs/run_posts_123' => Http::response([
            'data' => [
                'id' => 'run_posts_123',
                'status' => 'SUCCEEDED',
                'defaultDatasetId' => 'dataset_posts_123',
            ],
        ], 200),
        'https://api.apify.com/v2/datasets/dataset_posts_123/items*' => Http::response([
            [
                'id' => 'post_101',
                'shortCode' => 'CX123ABC',
                'caption' => 'Exciting news from Laravel ecosystem!',
                'likesCount' => 1500,
                'commentsCount' => 85,
                'timestamp' => '2026-03-01T12:00:00Z',
                'displayUrl' => 'https://example.com/post1.jpg',
                'url' => 'https://instagram.com/p/CX123ABC',
            ],
        ], 200),
    ]);

    $response = $this->postJson('/scrape', [
        'mode' => 'posts',
        'target' => 'https://instagram.com/laravelphp',
        'limit' => 5,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'completed',
            'mode' => 'posts',
            'count' => 1,
            'data' => [
                [
                    'id' => 'post_101',
                    'shortcode' => 'CX123ABC',
                    'caption' => 'Exciting news from Laravel ecosystem!',
                    'likesCount' => 1500,
                    'commentsCount' => 85,
                    'displayUrl' => 'https://example.com/post1.jpg',
                    'url' => 'https://instagram.com/p/CX123ABC',
                ],
            ],
        ]);
});

test('it scrapes hashtags mode successfully', function () {
    Http::fake([
        'https://api.apify.com/v2/acts/*/runs' => Http::response([
            'data' => [
                'id' => 'run_hash_123',
                'status' => 'RUNNING',
                'defaultDatasetId' => 'dataset_hash_123',
            ],
        ], 201),
        'https://api.apify.com/v2/actor-runs/run_hash_123' => Http::response([
            'data' => [
                'id' => 'run_hash_123',
                'status' => 'SUCCEEDED',
                'defaultDatasetId' => 'dataset_hash_123',
            ],
        ], 200),
        'https://api.apify.com/v2/datasets/dataset_hash_123/items*' => Http::response([
            [
                'id' => 'post_202',
                'shortCode' => 'HT789XYZ',
                'caption' => 'Building cool apps with #laravel',
                'likesCount' => 890,
                'commentsCount' => 42,
                'timestamp' => '2026-03-02T15:30:00Z',
                'displayUrl' => 'https://example.com/hashtag.jpg',
                'url' => 'https://instagram.com/p/HT789XYZ',
            ],
        ], 200),
    ]);

    $response = $this->postJson('/scrape', [
        'mode' => 'hashtags',
        'target' => '#laravel',
        'limit' => 10,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'completed',
            'mode' => 'hashtags',
            'data' => [
                [
                    'id' => 'post_202',
                    'caption' => 'Building cool apps with #laravel',
                    'likesCount' => 890,
                    'commentsCount' => 42,
                ],
            ],
        ]);
});

test('it returns empty status when dataset returns no items', function () {
    Http::fake([
        'https://api.apify.com/v2/acts/*/runs' => Http::response([
            'data' => [
                'id' => 'run_empty_123',
                'status' => 'RUNNING',
                'defaultDatasetId' => 'dataset_empty_123',
            ],
        ], 201),
        'https://api.apify.com/v2/actor-runs/run_empty_123' => Http::response([
            'data' => [
                'id' => 'run_empty_123',
                'status' => 'SUCCEEDED',
                'defaultDatasetId' => 'dataset_empty_123',
            ],
        ], 200),
        'https://api.apify.com/v2/datasets/dataset_empty_123/items*' => Http::response([], 200),
    ]);

    $response = $this->postJson('/scrape', [
        'mode' => 'profile',
        'target' => 'nonexistent_user_9999999',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'empty',
            'mode' => 'profile',
            'count' => 0,
            'data' => [],
        ]);
});

test('it handles apify run failure gracefully', function () {
    Http::fake([
        'https://api.apify.com/v2/acts/*/runs' => Http::response([
            'data' => [
                'id' => 'run_fail_123',
                'status' => 'RUNNING',
                'defaultDatasetId' => 'dataset_fail_123',
            ],
        ], 201),
        'https://api.apify.com/v2/actor-runs/run_fail_123' => Http::response([
            'data' => [
                'id' => 'run_fail_123',
                'status' => 'FAILED',
                'statusMessage' => 'Instagram rate limited the request',
            ],
        ], 200),
    ]);

    $response = $this->postJson('/scrape', [
        'mode' => 'profile',
        'target' => 'private_account',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'failed',
        ])
        ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'FAILED'));
});

test('it starts an async scrape run and polls status', function () {
    Http::fake([
        'https://api.apify.com/v2/acts/*/runs' => Http::response([
            'data' => [
                'id' => 'run_async_123',
                'status' => 'READY',
                'defaultDatasetId' => 'dataset_async_123',
            ],
        ], 201),
        'https://api.apify.com/v2/actor-runs/run_async_123' => Http::sequence()
            ->push([
                'data' => [
                    'id' => 'run_async_123',
                    'status' => 'RUNNING',
                    'defaultDatasetId' => 'dataset_async_123',
                ],
            ], 200)
            ->push([
                'data' => [
                    'id' => 'run_async_123',
                    'status' => 'SUCCEEDED',
                    'defaultDatasetId' => 'dataset_async_123',
                ],
            ], 200),
        'https://api.apify.com/v2/datasets/dataset_async_123/items*' => Http::response([
            [
                'id' => '999',
                'username' => 'taylorotwell',
                'fullName' => 'Taylor Otwell',
                'biography' => 'Creator of Laravel.',
                'followersCount' => 300000,
                'followingCount' => 150,
                'postsCount' => 200,
                'verified' => true,
            ],
        ], 200),
    ]);

    // 1. Start async run
    $startResponse = $this->postJson('/scrape', [
        'mode' => 'profile',
        'target' => 'taylorotwell',
        'async' => true,
    ]);

    $startResponse->assertOk()
        ->assertJson([
            'status' => 'queued',
            'run_id' => 'run_async_123',
            'mode' => 'profile',
            'target' => 'taylorotwell',
        ]);

    // 2. Poll: first check is running
    $pollRunning = $this->getJson('/scrape/status/run_async_123?mode=profile&target=taylorotwell');
    $pollRunning->assertOk()
        ->assertJson([
            'status' => 'running',
            'run_id' => 'run_async_123',
        ]);

    // 3. Poll: second check is succeeded and returns completed dataset
    $pollCompleted = $this->getJson('/scrape/status/run_async_123?mode=profile&target=taylorotwell');
    $pollCompleted->assertOk()
        ->assertJson([
            'status' => 'completed',
            'mode' => 'profile',
            'target' => 'taylorotwell',
            'data' => [
                'username' => 'taylorotwell',
                'fullName' => 'Taylor Otwell',
                'followersCount' => 300000,
            ],
        ]);
});
