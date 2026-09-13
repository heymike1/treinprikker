<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports page requests from AI assistants and crawlers to DataFast, which
 * classifies them itself. Those bots never run the frontend script, so this
 * is the only way to see them. Mirrors DataFast's PHP example
 * (https://datafa.st/docs/bot-traffic-tracking#php-example); the report goes
 * out in terminate(), after the response has been sent.
 */
class TrackAiCrawlers
{
    private const ENDPOINT = 'https://datafa.st/api/ai-crawls';

    private const CRAWLER_HINTS = [
        'bot', 'crawler', 'spider', 'chatgpt', 'gptbot', 'claude',
        'perplexity', 'bing', 'google', 'applebot', 'bytespider', 'ccbot',
    ];

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

        $userAgent = (string) $request->userAgent();
        if (! self::looksLikeCrawler($userAgent)) {
            return;
        }

        $token = config('services.datafast.bot_token');

        rescue(fn () => Http::connectTimeout(0.3)
            ->timeout(1)
            ->withHeaders($token ? ['Authorization' => 'Bearer '.$token] : [])
            ->post(self::ENDPOINT, [
                'websiteId' => config('services.datafast.website_id'),
                'domain' => $request->getHost(),
                'href' => $request->url(),
                'ai' => [
                    'userAgent' => $userAgent,
                    'ip' => $request->ip(),
                    'statusCode' => $response->getStatusCode(),
                    'source' => 'server_middleware',
                ],
            ]), report: false);
    }

    public static function looksLikeCrawler(string $userAgent): bool
    {
        $needle = strtolower($userAgent);

        foreach (self::CRAWLER_HINTS as $hint) {
            if (str_contains($needle, $hint)) {
                return true;
            }
        }

        return false;
    }
}
