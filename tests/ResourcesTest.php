<?php

declare(strict_types=1);

namespace Cryptohopper\Sdk\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Spot-checks path + body wiring for at least one method on every resource.
 * The exhaustive per-method test lives in other language SDKs; here we
 * verify the PHP surface routes to the same paths as Ruby/Node/etc.
 */
final class ResourcesTest extends TestCase
{
    private MockBackend $backend;

    protected function setUp(): void
    {
        // Queue enough 200s for the whole suite; each test only triggers one.
        $responses = [];
        for ($i = 0; $i < 40; $i++) {
            $responses[] = new Response(200, [], '{"data":[]}');
        }
        $this->backend = new MockBackend($responses);
    }

    public function testHoppersListHitsHopperListPath(): void
    {
        $this->backend->client->hoppers->list();
        self::assertStringEndsWith('/hopper/list', $this->backend->last()->getUri()->getPath());
    }

    public function testHoppersConfigUpdateMergesHopperId(): void
    {
        $this->backend->client->hoppers->configUpdate(42, ['dca' => ['enabled' => true]]);
        $req  = $this->backend->last();
        $body = json_decode((string) $req->getBody(), true);
        self::assertSame(42, $body['hopper_id']);
        self::assertSame(['dca' => ['enabled' => true]], ['dca' => $body['dca']]);
    }

    public function testExchangeForexRatesHyphenatedPath(): void
    {
        $this->backend->client->exchange->forexRates();
        self::assertStringEndsWith('/exchange/forex-rates', $this->backend->last()->getUri()->getPath());
    }

    public function testStrategyListHitsStrategiesPluralPath(): void
    {
        $this->backend->client->strategy->list();
        self::assertStringEndsWith('/strategy/strategies', $this->backend->last()->getUri()->getPath());
    }

    public function testBacktestCreateHitsNew(): void
    {
        $this->backend->client->backtest->create(['foo' => 'bar']);
        self::assertStringEndsWith('/backtest/new', $this->backend->last()->getUri()->getPath());
    }

    public function testMarketItemsHitsMarketitemsPath(): void
    {
        $this->backend->client->market->items();
        self::assertStringEndsWith('/market/marketitems', $this->backend->last()->getUri()->getPath());
    }

    public function testSignalsChartDataSingleWordPath(): void
    {
        $this->backend->client->signals->chartData();
        self::assertStringEndsWith('/signals/chartdata', $this->backend->last()->getUri()->getPath());
    }

    public function testArbitrageMarketCancelHyphenated(): void
    {
        $this->backend->client->arbitrage->marketCancel();
        self::assertStringEndsWith('/arbitrage/market-cancel', $this->backend->last()->getUri()->getPath());
    }

    public function testArbitrageDeleteBacklogPostsId(): void
    {
        $this->backend->client->arbitrage->deleteBacklog('bl-1');
        $body = json_decode((string) $this->backend->last()->getBody(), true);
        self::assertSame('bl-1', $body['backlog_id']);
    }

    public function testMarketMakerSetMarketTrendHyphenated(): void
    {
        $this->backend->client->marketmaker->setMarketTrend(['trend' => 'up']);
        self::assertStringEndsWith('/marketmaker/set-market-trend', $this->backend->last()->getUri()->getPath());
    }

    public function testTemplateLoadSendsBothIds(): void
    {
        $this->backend->client->template->load(7, 99);
        $body = json_decode((string) $this->backend->last()->getBody(), true);
        self::assertSame(7, $body['template_id']);
        self::assertSame(99, $body['hopper_id']);
    }

    public function testAiLlmAnalyzePath(): void
    {
        $this->backend->client->ai->llmAnalyze(['model' => 'gpt-5']);
        self::assertStringEndsWith('/ai/doaillmanalyze', $this->backend->last()->getUri()->getPath());
    }

    public function testAiGetCreditsKeepsServerPrefix(): void
    {
        $this->backend->client->ai->getCredits();
        self::assertStringEndsWith('/ai/getaicredits', $this->backend->last()->getUri()->getPath());
    }

    public function testPlatformSearchDocumentationWithQ(): void
    {
        $this->backend->client->platform->searchDocumentation('dca');
        self::assertStringContainsString('q=dca', $this->backend->last()->getUri()->getQuery());
    }

    public function testChartShareSaveHyphenated(): void
    {
        $this->backend->client->chart->shareSave(['foo' => 1]);
        self::assertStringEndsWith('/chart/share-save', $this->backend->last()->getUri()->getPath());
    }

    public function testSubscriptionStopSubscriptionPostsEmptyBody(): void
    {
        $this->backend->client->subscription->stopSubscription();
        $req = $this->backend->last();
        self::assertSame('POST', $req->getMethod());
        self::assertSame('[]', (string) $req->getBody());
    }

    public function testSocialGetConversationMapsToLoadconversation(): void
    {
        $this->backend->client->social->getConversation('c-42');
        self::assertStringEndsWith('/social/loadconversation', $this->backend->last()->getUri()->getPath());
    }

    public function testSocialCreatePostMapsToBarePost(): void
    {
        $this->backend->client->social->createPost(['text' => 'hi']);
        self::assertStringEndsWith('/social/post', $this->backend->last()->getUri()->getPath());
    }

    public function testTournamentsListGettournaments(): void
    {
        $this->backend->client->tournaments->list();
        self::assertStringEndsWith('/tournaments/gettournaments', $this->backend->last()->getUri()->getPath());
    }

    public function testTournamentsTournamentLeaderboardUnderscored(): void
    {
        $this->backend->client->tournaments->tournamentLeaderboard(9);
        self::assertStringEndsWith('/tournaments/leaderboard_tournament', $this->backend->last()->getUri()->getPath());
    }

    public function testUserGetHitsUserGet(): void
    {
        $this->backend->client->user->get();
        self::assertStringEndsWith('/user/get', $this->backend->last()->getUri()->getPath());
    }

    public function testWebhooksCreatePostsToApiWebhookCreate(): void
    {
        $this->backend->client->webhooks->create(['url' => 'https://example.com/hook']);
        self::assertStringEndsWith('/api/webhook_create', $this->backend->last()->getUri()->getPath());
    }

    public function testAppInAppPurchaseUnderscored(): void
    {
        $this->backend->client->app->inAppPurchase(['receipt' => 'abc']);
        self::assertStringEndsWith('/app/in_app_purchase', $this->backend->last()->getUri()->getPath());
    }

    public function testTemplateSaveHyphenatedPath(): void
    {
        $this->backend->client->template->save(['foo' => 1]);
        self::assertStringEndsWith('/template/save-template', $this->backend->last()->getUri()->getPath());
    }

    public function testStrategyUpdateHitsEdit(): void
    {
        $this->backend->client->strategy->update(3, ['name' => 'foo']);
        self::assertStringEndsWith('/strategy/edit', $this->backend->last()->getUri()->getPath());
    }
}
