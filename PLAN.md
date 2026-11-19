Implement the Apify `apify/instagram-scraper` API integration in `ScraperController` so the existing `resources/views/scraper/index.blade.php` works end-to-end.

Requirements:
- Read `APIFY_USER_ID` and `APIFY_TOKEN` from Laravel configuration/environment variables.
- Support the three existing scraper modes: Profile, Posts, and Hashtags.
- Map each mode to the appropriate Apify Actor input parameters.
- Start the Actor run through the Apify REST API.
- Handle asynchronous execution by polling the run status until it succeeds or fails.
- Retrieve the resulting dataset items when the run completes.
- Return structured JSON responses for the frontend.
- Handle validation errors, API failures, timeouts, and empty results gracefully.
- Keep API credentials strictly server-side.
- Follow Laravel best practices and use Laravel's HTTP client.

Preserve the existing UI and JavaScript interactions. Do not redesign the page or introduce unnecessary dependencies. Ensure the frontend can display loading, success, and error states correctly.