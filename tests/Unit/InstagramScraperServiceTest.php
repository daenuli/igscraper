<?php

use App\Services\InstagramScraperService;

beforeEach(function () {
    $this->service = new InstagramScraperService(token: 'mock-token', userId: 'mock-user');
});

test('it cleans usernames and hashtags correctly', function () {
    expect($this->service->cleanUsername('@taylorotwell'))->toBe('taylorotwell')
        ->and($this->service->cleanUsername('laravelphp'))->toBe('laravelphp')
        ->and($this->service->cleanUsername('https://www.instagram.com/laravelphp/'))->toBe('laravelphp')
        ->and($this->service->cleanHashtag('#php'))->toBe('php')
        ->and($this->service->cleanHashtag('laravel'))->toBe('laravel')
        ->and($this->service->cleanHashtag('https://www.instagram.com/explore/tags/webdev/'))->toBe('webdev');
});

test('it builds actor input for profile mode', function () {
    $input = $this->service->buildActorInput('profile', '@laravelphp', 5);

    expect($input)->toBe([
        'directUrls' => ['https://www.instagram.com/laravelphp/'],
        'resultsType' => 'details',
        'resultsLimit' => 1,
    ]);
});

test('it builds actor input for posts mode', function () {
    $input = $this->service->buildActorInput('posts', 'laravelphp', 20);

    expect($input)->toBe([
        'directUrls' => ['https://www.instagram.com/laravelphp/'],
        'resultsType' => 'posts',
        'resultsLimit' => 20,
    ]);
});

test('it builds actor input for hashtags mode', function () {
    $input = $this->service->buildActorInput('hashtags', '#laravel', 10);

    expect($input)->toBe([
        'directUrls' => ['https://www.instagram.com/explore/tags/laravel/'],
        'resultsType' => 'posts',
        'resultsLimit' => 10,
    ]);
});

test('it transforms profile items with fallback defaults', function () {
    $transformed = $this->service->transformProfile([
        'id' => '12345',
        'username' => 'testuser',
        'name' => 'Test User',
        'bio' => 'Sample bio',
        'profile_pic_url' => 'https://example.com/pic.jpg',
        'followers' => 500,
        'following' => 100,
        'postsCount' => 10,
        'isVerified' => true,
    ]);

    expect($transformed)->toBe([
        'id' => '12345',
        'username' => 'testuser',
        'fullName' => 'Test User',
        'biography' => 'Sample bio',
        'profilePicUrl' => 'https://example.com/pic.jpg',
        'externalUrl' => '',
        'followersCount' => 500,
        'followingCount' => 100,
        'postsCount' => 10,
        'isVerified' => true,
        'category' => 'Public Profile',
        'scrapedAt' => $transformed['scrapedAt'],
    ]);
});

test('it transforms post items with fallback defaults', function () {
    $transformed = $this->service->transformPost([
        'shortCode' => 'DEF456',
        'caption' => 'Hello World',
        'likesCount' => 100,
        'commentsCount' => 5,
        'timestamp' => '2026-01-01T00:00:00Z',
        'displayUrl' => 'https://example.com/img.jpg',
    ]);

    expect($transformed)->toMatchArray([
        'id' => 'DEF456',
        'shortcode' => 'DEF456',
        'caption' => 'Hello World',
        'likesCount' => 100,
        'commentsCount' => 5,
        'timestamp' => '2026-01-01T00:00:00Z',
        'displayUrl' => 'https://example.com/img.jpg',
        'url' => 'https://www.instagram.com/p/DEF456/',
        'contentType' => 'Photo',
    ]);
});

test('it accurately determines post content types', function () {
    // Reel by productType clips
    expect($this->service->determineContentType(['productType' => 'clips']))->toBe('Reel');

    // Reel by reel url
    expect($this->service->determineContentType(['url' => 'https://instagram.com/reel/xyz123/']))->toBe('Reel');

    // Carousel by sidecar type
    expect($this->service->determineContentType(['type' => 'Sidecar']))->toBe('Carousel');

    // Carousel by childPosts
    expect($this->service->determineContentType(['childPosts' => [['id' => '1'], ['id' => '2']]]))->toBe('Carousel');

    // IGTV
    expect($this->service->determineContentType(['productType' => 'igtv']))->toBe('IGTV');

    // Video
    expect($this->service->determineContentType(['type' => 'Video']))->toBe('Video');
    expect($this->service->determineContentType(['videoUrl' => 'https://example.com/video.mp4']))->toBe('Video');

    // Standard Photo fallback
    expect($this->service->determineContentType(['type' => 'Image']))->toBe('Photo');
    expect($this->service->determineContentType([]))->toBe('Photo');
});
