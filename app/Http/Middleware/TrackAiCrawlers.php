<?php

namespace App\Http\Middleware;

use App\Analytics\AiCrawlerDetector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports page requests from AI assistants and crawlers to DataFast. Those
 * bots never run the frontend script, so this is the only way to see them.
 * The report goes out in terminate(), after the response has been sent.
 */
class TrackAiCrawlers
{
    private const ENDPOINT = 'https://datafa.st/api/ai-crawls';

    /** Paths that are never interesting to report. */
    private const IGNORED_PREFIXES = ['/admin', '/livewire', '/build', '/data', '/images', '/fonts', '/favicon', '/up', '/.well-known'];

    private const IGNORED_EXTENSIONS = ['avif', 'bmp', 'br', 'css', 'csv', 'gif', 'gz', 'ico', 'jpeg', 'jpg', 'js', 'json', 'map', 'mjs', 'mp4', 'pdf', 'png', 'svg', 'ttf', 'txt', 'wasm', 'webmanifest', 'webp', 'woff', 'woff2', 'xml', 'zip'];

    /** Anything that smells like a bot is reported too; DataFast classifies what we don't recognise. */
    private const GENERIC_HINTS = ['bot', 'crawler', 'spider', 'fetch', 'scrape'];

    /** Files bots fetch on purpose, tracked even though their extension is ignored above. */
    private const CRAWLER_FILES = ['/robots.txt', '/llms.txt', '/llms-full.txt', '/sitemap.xml'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('services.datafast.bot_tracking') || ! config('services.datafast.website_id')) {
            return;
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return;
        }

        if (! self::isTrackablePath('/'.ltrim($request->path(), '/'))) {
            return;
        }

        $userAgent = (string) $request->userAgent();
        $crawler = AiCrawlerDetector::classify($userAgent);
        if ($crawler === null && ! self::looksLikeBot($userAgent)) {
            return;
        }

        rescue(fn () => Http::timeout(2)
            ->withHeaders(array_filter(['Authorization' => config('services.datafast.bot_token') ? 'Bearer '.config('services.datafast.bot_token') : null]))
            ->post(self::ENDPOINT, [
                'websiteId' => config('services.datafast.website_id'),
                'domain' => config('services.datafast.domain'),
                'href' => $request->url(),
                'referrer' => $request->headers->get('referer'),
                'ai' => [
                    ...($crawler ?? []),
                    'userAgent' => $userAgent,
                    'ip' => $request->ip(),
                    'statusCode' => $response->getStatusCode(),
                    'source' => 'server_middleware',
                ],
            ]), report: false);
    }

    public static function looksLikeBot(string $userAgent): bool
    {
        $needle = strtolower($userAgent);

        foreach (self::GENERIC_HINTS as $hint) {
            if (str_contains($needle, $hint)) {
                return true;
            }
        }

        return false;
    }

    public static function isTrackablePath(string $path): bool
    {
        $path = strtolower($path);

        if (in_array($path, self::CRAWLER_FILES, true) || (str_contains(basename($path), 'sitemap') && str_ends_with($path, '.xml'))) {
            return true;
        }

        foreach (self::IGNORED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return false;
            }
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === '' || ! in_array($extension, self::IGNORED_EXTENSIONS, true);
    }
}
