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
        try {
            $validated = $request->validate([
                'mode' => ['required', 'string', 'in:profile,posts,hashtags'],
                'target' => ['required', 'string', 'max:255'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 10;

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
}
