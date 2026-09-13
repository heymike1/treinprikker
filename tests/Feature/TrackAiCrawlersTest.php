<?php

namespace Tests\Feature;

use App\Analytics\AiCrawlerDetector;
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
        config()->set('services.datafast.domain', 'treinprikker.nl');
        Http::fake();
    }

    #[DataProvider('userAgents')]
    public function test_classifies_known_crawlers(string $userAgent, ?string $agent, ?string $category): void
    {
        $result = AiCrawlerDetector::classify($userAgent);

        $this->assertSame($agent, $result['agent'] ?? null);
        $this->assertSame($category, $result['category'] ?? null);
    }

    public static function userAgents(): array
    {
        return [
            'gptbot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)', 'GPTBot', 'training'],
            'chatgpt user' => ['Mozilla/5.0 ChatGPT-User/1.0; +https://openai.com/bot', 'ChatGPT-User', 'answer_fetch'],
            'claudebot' => ['Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', 'ClaudeBot', 'training'],
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Googlebot', 'search_index'],
            'perplexity' => ['Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)', 'PerplexityBot', 'search_index'],
            'alias only' => ['some-new-anthropic-fetcher/2.0', 'Anthropic', 'ai_crawler'],
            'safari' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1', null, null],
            'empty' => ['', null, null],
        ];
    }

    public function test_reports_crawler_page_views_to_datafast(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; GPTBot/1.2)', 'Referer' => 'https://chat.openai.com/'])
            ->get('/hoe-werkt-het')
            ->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://datafa.st/api/ai-crawls'
            && $request['websiteId'] === 'dfid_test'
            && $request['domain'] === 'treinprikker.nl'
            && str_ends_with($request['href'], '/hoe-werkt-het')
            && $request['referrer'] === 'https://chat.openai.com/'
            && $request['ai']['agent'] === 'GPTBot'
            && $request['ai']['provider'] === 'OpenAI'
            && $request['ai']['category'] === 'training'
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

    public function test_ignores_ordinary_visitors_and_admin_pages(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh) Safari/605.1.15'])->get('/');
        $this->withHeaders(['User-Agent' => 'GPTBot/1.2'])->get('/admin');
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

    #[DataProvider('paths')]
    public function test_trackable_paths(string $path, bool $expected): void
    {
        $this->assertSame($expected, TrackAiCrawlers::isTrackablePath($path));
    }

    public static function paths(): array
    {
        return [
            ['/', true],
            ['/statistieken', true],
            ['/station/kropswolde', true],
            ['/robots.txt', true],
            ['/sitemap.xml', true],
            ['/sitemap-stations.xml', true],
            ['/llms.txt', true],
            ['/admin', false],
            ['/admin/marketing', false],
            ['/livewire/update', false],
            ['/build/assets/app.js', false],
            ['/data/spoornet.json', false],
            ['/images/og.jpg', false],
            ['/logo.svg', false],
        ];
    }
}
