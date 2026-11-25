<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WonderWp\Component\CPT\Service\AbstractCustomPostTypeService;
use WonderWp\Component\CPT\Tests\Support\InspectableCpt;
use WonderWp\Component\CPT\Tests\Support\InspectableCptService;

final class LocaleSlugRulesTest extends TestCase
{
    private const SINGLE_KEY  = '%s/(?!page(?:/|$)|feed(?:/|$))([^/]+)(?:/([0-9]+))?/?$';
    private const PAGED_KEY   = '%s/([^/]+)/page/?([0-9]{1,})/?$';

    protected function setUp(): void
    {
        $GLOBALS['wwp_test_actions']                = [];
        $GLOBALS['wwp_test_filters']                = [];
        $GLOBALS['wwp_test_rewrite_rules']          = [];
        $GLOBALS['wwp_test_register_post_type_calls'] = [];
        $GLOBALS['wwp_test_locale']                 = 'fr_FR';
        unset($GLOBALS['wwp_test_register_post_type_return']);

        $property = (new ReflectionClass(AbstractCustomPostTypeService::class))->getProperty('rewriteRulesPatchFiltersRegistered');
        $property->setValue(null, []);
    }

    public function testRulesAreGeneratedForEveryLocaleSlugAndLegacyAlias(): void
    {
        $rules = (new InspectableCptService())->exposeComputeAdditionalSlugsRewriteRules(InspectableCpt::news());

        foreach (['actualite', 'news', 'actualites', 'wwp-actu'] as $slug) {
            $this->assertArrayHasKey(sprintf(self::PAGED_KEY, $slug), $rules, "paged rule for $slug");
        }

        // Archive slugs (every locale slug) keep the reserved-slug lookahead.
        $this->assertArrayHasKey(sprintf(self::SINGLE_KEY, 'actualite'), $rules);
        $this->assertArrayHasKey(sprintf(self::SINGLE_KEY, 'news'), $rules);
        $this->assertSame('index.php?wwp-actu=$matches[1]&page=$matches[2]', $rules[sprintf(self::SINGLE_KEY, 'news')]);
        $this->assertSame('index.php?wwp-actu=$matches[1]&paged=$matches[2]', $rules[sprintf(self::PAGED_KEY, 'news')]);
    }

    public function testRuleSetDoesNotDependOnTheCurrentLocale(): void
    {
        $GLOBALS['wwp_test_locale'] = 'fr_FR';
        $fromFr = (new InspectableCptService())->exposeComputeAdditionalSlugsRewriteRules(InspectableCpt::news());

        $GLOBALS['wwp_test_locale'] = 'en_EN';
        $fromEn = (new InspectableCptService())->exposeComputeAdditionalSlugsRewriteRules(InspectableCpt::news());

        $this->assertSame($fromFr, $fromEn);
    }

    public function testRegisteredCanonicalSlugIsTheDefaultLocaleSlugWhateverTheRequestLocale(): void
    {
        foreach (['fr_FR', 'en_EN'] as $requestLocale) {
            $GLOBALS['wwp_test_locale']                   = $requestLocale;
            $GLOBALS['wwp_test_register_post_type_calls'] = [];

            (new InspectableCptService())->registerCustomPostType(InspectableCpt::news());

            $rewrite = $GLOBALS['wwp_test_register_post_type_calls'][0]['args']['rewrite'];
            $this->assertSame('actualite', $rewrite['slug'], "canonical slug in request locale $requestLocale");
        }
    }

    public function testDefaultLocaleCanBeOverriddenThroughTheFilter(): void
    {
        add_filter('wonderwp.customposttype.rewrite.default-locale', static fn() => 'en_EN');

        (new InspectableCptService())->registerCustomPostType(InspectableCpt::news(['default_locale' => '']));

        $this->assertSame('news', $GLOBALS['wwp_test_register_post_type_calls'][0]['args']['rewrite']['slug']);
    }

    public function testWordPressDoesNotReceiveTheLocaleSlugArgs(): void
    {
        (new InspectableCptService())->registerCustomPostType(InspectableCpt::news());

        $rewrite = $GLOBALS['wwp_test_register_post_type_calls'][0]['args']['rewrite'];
        foreach (['slugs', 'locale_slugs', 'locale_meta', 'default_locale', 'redirect_to_locale_slug'] as $custom) {
            $this->assertArrayNotHasKey($custom, $rewrite);
        }
    }

    public function testRulesAreRegisteredOnTopForEveryLocaleSlug(): void
    {
        (new InspectableCptService())->registerCustomPostType(InspectableCpt::news());

        $regexes = array_column($GLOBALS['wwp_test_rewrite_rules'], 'regex');
        $this->assertContains(sprintf(self::SINGLE_KEY, 'news'), $regexes);
        $this->assertContains(sprintf(self::SINGLE_KEY, 'actualite'), $regexes);
        $this->assertSame(['top'], array_values(array_unique(array_column($GLOBALS['wwp_test_rewrite_rules'], 'after'))));
    }

    public function testPostTypeLinkFilterAndRedirectActionAreRegisteredOncePerPostType(): void
    {
        $service = new InspectableCptService();
        $service->registerCustomPostType(InspectableCpt::news());

        $this->assertCount(1, $GLOBALS['wwp_test_filters']['post_type_link'] ?? []);
        $this->assertCount(1, $GLOBALS['wwp_test_actions']['template_redirect'] ?? []);
        $this->assertSame(20, $GLOBALS['wwp_test_actions']['template_redirect'][0]['priority']);
    }

    public function testNoLocaleHookIsRegisteredWithoutLocaleSlugs(): void
    {
        (new InspectableCptService())->registerCustomPostType(new InspectableCpt('book', ['rewrite' => ['slugs' => ['livres']]]));

        $this->assertArrayNotHasKey('post_type_link', $GLOBALS['wwp_test_filters']);
        $this->assertArrayNotHasKey('template_redirect', $GLOBALS['wwp_test_actions']);
    }

    public function testRedirectActionIsSkippedWhenNotRequested(): void
    {
        (new InspectableCptService())->registerCustomPostType(InspectableCpt::news(['redirect_to_locale_slug' => false]));

        $this->assertCount(1, $GLOBALS['wwp_test_filters']['post_type_link'] ?? []);
        $this->assertArrayNotHasKey('template_redirect', $GLOBALS['wwp_test_actions']);
    }
}
