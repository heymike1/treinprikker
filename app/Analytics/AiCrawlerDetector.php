<?php

namespace App\Analytics;

/**
 * Recognises AI assistants, search engines and model-training crawlers by
 * user agent. The table mirrors DataFast's @datafast/ai-crawl package
 * (v1.0.9); DataFast uses the provider/agent/category we send as-is.
 */
class AiCrawlerDetector
{
    public const ANSWER_FETCH = 'answer_fetch';

    public const SEARCH_INDEX = 'search_index';

    public const TRAINING = 'training';

    public const AI_CRAWLER = 'ai_crawler';

    /** @var list<array{0: string, 1: string, 2: string}> agent, provider, category — order matters (most specific first). */
    private const AGENTS = [
        ['ChatGPT-User', 'OpenAI', 'answer_fetch'],
        ['OAI-SearchBot', 'OpenAI', 'search_index'],
        ['OAI-AdsBot', 'OpenAI', 'ai_crawler'],
        ['GPTBot', 'OpenAI', 'training'],
        ['Claude-User', 'Anthropic', 'answer_fetch'],
        ['Claude-SearchBot', 'Anthropic', 'search_index'],
        ['ClaudeBot', 'Anthropic', 'training'],
        ['Perplexity-User', 'Perplexity', 'answer_fetch'],
        ['PerplexityBot', 'Perplexity', 'search_index'],
        ['Google-InspectionTool', 'Google', 'search_index'],
        ['GoogleOther', 'Google', 'training'],
        ['Google-Extended', 'Google', 'training'],
        ['Google-CloudVertexBot', 'Google', 'training'],
        ['Google-Agent', 'Google', 'answer_fetch'],
        ['Google-NotebookLM', 'Google', 'answer_fetch'],
        ['Google-Read-Aloud', 'Google', 'answer_fetch'],
        ['Googlebot', 'Google', 'search_index'],
        ['GoogleAgent', 'Google', 'answer_fetch'],
        ['MistralAI-User', 'Mistral', 'answer_fetch'],
        ['MistralAI-Index', 'Mistral', 'search_index'],
        ['Bingbot', 'Microsoft', 'search_index'],
        ['msnbot', 'Microsoft', 'search_index'],
        ['Copilot', 'Microsoft', 'answer_fetch'],
        ['Applebot-Extended', 'Apple', 'training'],
        ['Applebot', 'Apple', 'training'],
        ['Amazonbot', 'Amazon', 'training'],
        ['Amzn-SearchBot', 'Amazon', 'search_index'],
        ['Amzn-User', 'Amazon', 'answer_fetch'],
        ['DuckAssistBot', 'DuckDuckGo', 'answer_fetch'],
        ['xAI-SearchBot', 'xAI', 'answer_fetch'],
        ['Grok-DeepSearch', 'xAI', 'answer_fetch'],
        ['GrokBot', 'xAI', 'ai_crawler'],
        ['xAI-Bot', 'xAI', 'ai_crawler'],
        ['xAI-Grok', 'xAI', 'ai_crawler'],
        ['xAI-Web-Crawler', 'xAI', 'ai_crawler'],
        ['Grok', 'xAI', 'ai_crawler'],
        ['meta-externalagent', 'Meta', 'training'],
        ['meta-externalfetcher', 'Meta', 'answer_fetch'],
        ['FacebookBot', 'Meta', 'ai_crawler'],
        ['Kimi-User', 'Moonshot AI', 'answer_fetch'],
        ['Kimi-SearchBot', 'Moonshot AI', 'search_index'],
        ['KimiBot', 'Moonshot AI', 'training'],
        ['Doubaobot', 'ByteDance', 'ai_crawler'],
        ['Bytespider', 'ByteDance', 'training'],
        ['TikTokSpider', 'ByteDance', 'search_index'],
        ['ERNIEBot', 'Baidu', 'training'],
        ['YiyanBot', 'Baidu', 'ai_crawler'],
        ['Baiduspider', 'Baidu', 'search_index'],
        ['Qwen-User', 'Alibaba', 'answer_fetch'],
        ['QwenBot', 'Alibaba', 'training'],
        ['TongyiBot', 'Alibaba', 'ai_crawler'],
        ['AliyunBot', 'Alibaba', 'ai_crawler'],
        ['ChatGLM-Spider', 'Zhipu AI', 'training'],
        ['DeepSeekBot', 'DeepSeek', 'training'],
        ['cohere-ai', 'Cohere', 'training'],
        ['cohere-training-data-crawler', 'Cohere', 'training'],
        ['AI2Bot', 'Allen AI', 'training'],
        ['YouBot', 'You.com', 'search_index'],
        ['CCBot', 'Common Crawl', 'training'],
    ];

    /** @var list<array{0: list<string>, 1: string, 2: string, 3: string}> aliases, agent, provider, category — looser fallback. */
    private const ALIASES = [
        [['openai', 'chatgpt', 'gptbot', 'oai-', 'oai_', 'openai-search'], 'OpenAI', 'OpenAI', 'ai_crawler'],
        [['anthropic', 'claude'], 'Anthropic', 'Anthropic', 'ai_crawler'],
        [['perplexity'], 'Perplexity', 'Perplexity', 'ai_crawler'],
        [['googlebot', 'googleother', 'google-extended', 'google-inspection', 'google-read-aloud', 'google-notebooklm', 'google-cloudvertex', 'googleagent', 'gemini'], 'Google', 'Google', 'search_index'],
        [['bingbot', 'msnbot', 'copilot'], 'Microsoft', 'Microsoft', 'search_index'],
        [['applebot'], 'Apple', 'Apple', 'training'],
        [['amazonbot', 'amzn-searchbot', 'amzn-user'], 'Amazon', 'Amazon', 'ai_crawler'],
        [['duckassist', 'duckassistbot'], 'DuckDuckGo', 'DuckDuckGo', 'ai_crawler'],
        [['xai', 'x-ai', 'grok'], 'xAI', 'xAI', 'ai_crawler'],
        [['meta-external', 'facebookbot'], 'Meta', 'Meta', 'ai_crawler'],
        [['mistralai', 'mistral-ai', 'mistral'], 'Mistral', 'Mistral', 'ai_crawler'],
        [['kimi', 'moonshot'], 'Moonshot AI', 'Moonshot AI', 'ai_crawler'],
        [['bytespider', 'doubaobot', 'tiktokspider'], 'ByteDance', 'ByteDance', 'training'],
        [['baiduspider', 'erniebot', 'yiyanbot'], 'Baidu', 'Baidu', 'search_index'],
        [['qwen', 'tongyi', 'aliyunbot'], 'Alibaba', 'Alibaba', 'ai_crawler'],
        [['chatglm', 'zhipu'], 'Zhipu AI', 'Zhipu AI', 'training'],
        [['deepseek'], 'DeepSeek', 'DeepSeek', 'training'],
        [['cohere'], 'Cohere', 'Cohere', 'training'],
        [['ai2bot', 'allenai', 'allen-ai'], 'Allen AI', 'Allen AI', 'training'],
        [['youbot', 'you.com'], 'You.com', 'You.com', 'search_index'],
        [['ccbot', 'commoncrawl', 'common-crawl'], 'Common Crawl', 'Common Crawl', 'training'],
    ];

    /**
     * @return array{provider: string, agent: string, category: string}|null
     */
    public static function classify(?string $userAgent): ?array
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $needle = mb_strtolower($userAgent);

        foreach (self::AGENTS as [$agent, $provider, $category]) {
            if (str_contains($needle, mb_strtolower($agent))) {
                return ['provider' => $provider, 'agent' => $agent, 'category' => $category];
            }
        }

        foreach (self::ALIASES as [$aliases, $agent, $provider, $category]) {
            foreach ($aliases as $alias) {
                if (str_contains($needle, $alias)) {
                    return ['provider' => $provider, 'agent' => $agent, 'category' => $category];
                }
            }
        }

        return null;
    }
}
