<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackAiCrawlers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrackAiCrawlersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.datafast.bot_tracking', true);
        config()->set('services.datafast.website_id', 'dfid_test');
        Http::fake();
    }

    #[DataProvider('userAgents')]
    public function test_recognises_crawlers(string $userAgent, bool $expected): void
    {
        $this->assertSame($expected, TrackAiCrawlers::looksLikeCrawler($userAgent));
    }

    public static function userAgents(): array
    {
        return [
            'gptbot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)', true],
            'chatgpt user' => ['Mozilla/5.0 ChatGPT-User/1.0; +https://openai.com/bot', true],
            'claudebot' => ['Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', true],
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', true],
            'perplexity' => ['Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)', true],
            'unknown crawler' => ['SomeNewCrawler/0.1', true],
            'safari' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1', false],
            'empty' => ['', false],
        ];
    }

    public function test_reports_crawler_page_views_to_datafast(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; GPTBot/1.2)'])
            ->get('/hoe-werkt-het?utm_source=x')
            ->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://datafa.st/api/ai-crawls'
            && $request['websiteId'] === 'dfid_test'
            && $request['domain'] === parse_url(config('app.url'), PHP_URL_HOST)
            && $request['href'] === rtrim(config('app.url'), '/').'/hoe-werkt-het'
            && $request['ai']['userAgent'] === 'Mozilla/5.0 (compatible; GPTBot/1.2)'
            && $request['ai']['statusCode'] === 200
            && $request['ai']['source'] === 'server_middleware'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_sends_bearer_token_when_configured(): void
    {
        config()->set('services.datafast.bot_token', 'dfbot_secret');

        $this->withHeaders(['User-Agent' => 'ClaudeBot/1.0'])->get('/');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer dfbot_secret'));
    }

    public function test_ignores_ordinary_visitors_and_non_get_requests(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh) Safari/605.1.15'])->get('/');
        $this->withHeaders(['User-Agent' => 'GPTBot/1.2'])->post('/feedback', []);

        Http::assertNothingSent();
    }

    public function test_stays_silent_when_disabled(): void
    {
        config()->set('services.datafast.bot_tracking', false);

        $this->withHeaders(['User-Agent' => 'GPTBot/1.2'])->get('/');

        Http::assertNothingSent();
    }

    public function test_survives_a_failing_datafast_api(): void
    {
        Http::fake(fn () => throw new \RuntimeException('down'));

        $this->withHeaders(['User-Agent' => 'GPTBot/1.2'])->get('/')->assertOk();
    }
}
