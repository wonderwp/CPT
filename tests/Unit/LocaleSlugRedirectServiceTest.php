<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WonderWp\Component\CPT\Service\AbstractCustomPostTypeService;
use WonderWp\Component\CPT\Tests\Support\InspectableCpt;
use WonderWp\Component\CPT\Tests\Support\InspectableCptService;

final class LocaleSlugRedirectServiceTest extends TestCase
{
    private InspectableCptService $service;

    protected function setUp(): void
    {
        $GLOBALS['wwp_test_actions']    = [];
        $GLOBALS['wwp_test_filters']    = [];
        $GLOBALS['wwp_test_redirects']  = [];
        $GLOBALS['wwp_test_is_singular'] = true;
        $GLOBALS['wwp_test_is_preview']  = false;
        $GLOBALS['wwp_test_post_meta']   = [20 => ['news_locale' => 'en_EN'], 10 => ['news_locale' => 'fr_FR']];
        $GLOBALS['wwp_test_queried_object'] = (object) ['ID' => 20, 'post_type' => 'wwp-actu'];
        $GLOBALS['wp'] = (object) ['request' => 'actualite/my-news'];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['QUERY_STRING']);

        $property = (new ReflectionClass(AbstractCustomPostTypeService::class))->getProperty('rewriteRulesPatchFiltersRegistered');
        $property->setValue(null, []);

        $this->service = new InspectableCptService();
        $this->service->addCustomPostType(InspectableCpt::news());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp'], $GLOBALS['wwp_test_is_singular'], $GLOBALS['wwp_test_queried_object']);
    }

    public function testSlugOfAnotherLanguageRedirectsWith301ToTheCanonicalUrl(): void
    {
        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame([['location' => 'https://example.test/news/my-news/', 'status' => 301]], $GLOBALS['wwp_test_redirects']);
        $this->assertSame(1, $this->service->terminated);
    }

    public function testCanonicalUrlIsNotRedirected(): void
    {
        $GLOBALS['wp']->request = 'news/my-news';

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame([], $GLOBALS['wwp_test_redirects']);
        $this->assertSame(0, $this->service->terminated);
    }

    public function testLegacyAliasRedirectsToTheCanonicalUrl(): void
    {
        $GLOBALS['wp']->request = 'wwp-actu/my-news';

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame('https://example.test/news/my-news/', $GLOBALS['wwp_test_redirects'][0]['location']);
    }

    public function testQueryStringIsKept(): void
    {
        $_SERVER['QUERY_STRING'] = 'utm_source=newsletter';

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame('https://example.test/news/my-news/?utm_source=newsletter', $GLOBALS['wwp_test_redirects'][0]['location']);
    }

    public function testPreviewIsNotRedirected(): void
    {
        $GLOBALS['wwp_test_is_preview'] = true;

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame([], $GLOBALS['wwp_test_redirects']);
    }

    public function testNonSingularRequestIsNotRedirected(): void
    {
        $GLOBALS['wwp_test_is_singular'] = false;

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame([], $GLOBALS['wwp_test_redirects']);
    }

    public function testPostWithoutLocaleMetaUsesTheDefaultLocaleSlug(): void
    {
        $GLOBALS['wwp_test_queried_object'] = (object) ['ID' => 99, 'post_type' => 'wwp-actu'];
        $GLOBALS['wp']->request = 'news/sans-meta';

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame('https://example.test/actualite/sans-meta/', $GLOBALS['wwp_test_redirects'][0]['location']);
    }

    public function testNonGetRequestsAreNotRedirected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame([], $GLOBALS['wwp_test_redirects']);
    }

    public function testOtherPostTypeIsNotRedirected(): void
    {
        $GLOBALS['wwp_test_queried_object'] = (object) ['ID' => 20, 'post_type' => 'page'];

        $this->service->maybeRedirectToLocaleSlug();

        $this->assertSame([], $GLOBALS['wwp_test_redirects']);
    }
}
