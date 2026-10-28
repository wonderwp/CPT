<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponseInterface;
use WonderWp\Component\CPT\Service\AbstractCustomPostTypeService;
use WP_Error;
use WP_Post_Type;

final class AbstractCustomPostTypeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wwp_test_actions'] = [];
        $GLOBALS['wwp_test_filters'] = [];
        $GLOBALS['wwp_test_rewrite_rules'] = [];
        $GLOBALS['wwp_test_register_post_type_calls'] = [];
        unset($GLOBALS['wwp_test_register_post_type_return']);

        $reflection = new ReflectionClass(AbstractCustomPostTypeService::class);
        $property = $reflection->getProperty('rewriteRulesPatchFiltersRegistered');
        $property->setValue(null, []);
    }

    public function testComputeAdditionalSlugsRewriteRulesReturnsEmptyWhenNoSlugs(): void
    {
        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('book', ['public' => true]);

        $this->assertSame([], $service->exposeComputeAdditionalSlugsRewriteRules($cpt));
    }

    public function testComputeAdditionalSlugsRewriteRulesBuildsRulesForExtraSlugs(): void
    {
        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('book', [
            'rewrite' => ['slugs' => ['livres']],
        ]);

        $rules = $service->exposeComputeAdditionalSlugsRewriteRules($cpt);

        $this->assertArrayHasKey('livres/([^/]+)/page/?([0-9]{1,})/?$', $rules);
        $this->assertSame(
            'index.php?book=$matches[1]&paged=$matches[2]',
            $rules['livres/([^/]+)/page/?([0-9]{1,})/?$']
        );
        $this->assertArrayHasKey('livres/([^/]+)(?:/([0-9]+))?/?$', $rules);
        $this->assertSame(
            'index.php?book=$matches[1]&page=$matches[2]',
            $rules['livres/([^/]+)(?:/([0-9]+))?/?$']
        );
    }

    public function testComputeAdditionalSlugsRewriteRulesUsesNegativeLookaheadForArchiveSlug(): void
    {
        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('event', [
            'has_archive' => true,
            'rewrite' => [
                'slug' => 'events',
                'slugs' => ['events'],
            ],
        ]);

        $rules = $service->exposeComputeAdditionalSlugsRewriteRules($cpt);
        $pattern = 'events/(?!page(?:/|$)|feed(?:/|$))([^/]+)(?:/([0-9]+))?/?$';

        $this->assertArrayHasKey($pattern, $rules);
    }

    public function testComputeAdditionalSlugsRewriteRulesAppliesSlugsFilter(): void
    {
        add_filter('wonderwp.customposttype.rewrite.slugs', static function (array $slugs): array {
            return ['filtered-slug'];
        });

        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('book', [
            'rewrite' => ['slugs' => ['ignored']],
        ]);

        $rules = $service->exposeComputeAdditionalSlugsRewriteRules($cpt);

        $this->assertArrayHasKey('filtered-slug/([^/]+)(?:/([0-9]+))?/?$', $rules);
    }

    public function testPatchSinglePostRewriteRulesForPostTypeAddsLookaheadForArchiveSlug(): void
    {
        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('event', [
            'has_archive' => 'events',
            'rewrite' => ['slug' => 'events'],
        ]);

        $originalRegex = 'events/([^/]+)(?:/([0-9]+))?/?$';
        $query = 'index.php?event=$matches[1]&page=$matches[2]';
        $rules = [$originalRegex => $query];

        $patched = $service->exposePatchSinglePostRewriteRulesForPostType($rules, $cpt);
        $expectedRegex = 'events/(?!page(?:/|$)|feed(?:/|$))([^/]+)(?:/([0-9]+))?/?$';

        $this->assertArrayNotHasKey($originalRegex, $patched);
        $this->assertArrayHasKey($expectedRegex, $patched);
        $this->assertSame($query, $patched[$expectedRegex]);
    }

    public function testPatchRewriteRulesArraySkipsPostTypesWithoutArchive(): void
    {
        $service = new TestableCustomPostTypeService();
        $rules = ['sample/([^/]+)/?$' => 'index.php?post_type=sample'];

        $service->addCustomPostType(new StubCustomPostType('sample', ['public' => true]));

        $this->assertSame($rules, $service->patchRewriteRulesArray($rules));
    }

    public function testPatchRewriteRulesArrayPatchesWhenHasArchive(): void
    {
        $service = new TestableCustomPostTypeService();
        $service->addCustomPostType(new StubCustomPostType('event', [
            'has_archive' => true,
            'rewrite' => ['slug' => 'events'],
        ]));

        $originalRegex = 'events/([^/]+)(?:/([0-9]+))?/?$';
        $query = 'index.php?event=$matches[1]&page=$matches[2]';
        $input = [$originalRegex => $query];

        $patched = $service->patchRewriteRulesArray($input);
        $expectedRegex = 'events/(?!page(?:/|$)|feed(?:/|$))([^/]+)(?:/([0-9]+))?/?$';

        $this->assertArrayHasKey($expectedRegex, $patched);
    }

    public function testRegisterCustomPostTypeSuccess(): void
    {
        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('book', ['public' => true]);

        $response = $service->registerCustomPostType($cpt);

        $this->assertSame(200, $response->getCode());
        $this->assertSame(CustomPostTypeRegistrationResponseInterface::SUCCESS, $response->getMsgKey());
        $this->assertInstanceOf(WP_Post_Type::class, $response->getWpRegistrationResult());
        $this->assertCount(1, $GLOBALS['wwp_test_register_post_type_calls']);
        $this->assertSame('book', $GLOBALS['wwp_test_register_post_type_calls'][0]['post_type']);
    }

    public function testRegisterCustomPostTypeMapsWpErrorToErrorResponse(): void
    {
        $GLOBALS['wwp_test_register_post_type_return'] = new WP_Error('invalid', 'Invalid post type');

        $service = new TestableCustomPostTypeService();
        $response = $service->registerCustomPostType(new StubCustomPostType('bad', []));

        $this->assertSame(CustomPostTypeRegistrationResponseInterface::ERROR, $response->getMsgKey());
        $this->assertSame(500, $response->getCode());
        $this->assertSame('Invalid post type', $response->getError()?->getMessage());
    }

    public function testRegisterCustomPostTypeRegistersAdditionalRewriteRules(): void
    {
        $service = new TestableCustomPostTypeService();
        $cpt = new StubCustomPostType('book', [
            'rewrite' => ['slugs' => ['livres']],
        ]);

        $service->registerCustomPostType($cpt);

        $this->assertNotEmpty($GLOBALS['wwp_test_rewrite_rules']);
        $this->assertSame('top', $GLOBALS['wwp_test_rewrite_rules'][0]['after']);
        $this->assertArrayNotHasKey('slugs', $GLOBALS['wwp_test_register_post_type_calls'][0]['args']['rewrite'] ?? []);
    }

    public function testRegisterCustomPostTypesRegistersRewriteRulesPatchFilterOnce(): void
    {
        $service = new TestableCustomPostTypeService();
        $service->addCustomPostType(new StubCustomPostType('a', ['public' => true]));
        $service->addCustomPostType(new StubCustomPostType('b', ['public' => true]));

        $service->registerCustomPostTypes();

        $this->assertCount(1, $GLOBALS['wwp_test_filters']['rewrite_rules_array'] ?? []);
        $this->assertCount(2, $GLOBALS['wwp_test_register_post_type_calls']);
    }
}

final class TestableCustomPostTypeService extends AbstractCustomPostTypeService
{
    public function register(): void
    {
    }

    public function exposeComputeAdditionalSlugsRewriteRules(CustomPostTypeInterface $customPostType): array
    {
        return $this->computeAdditionalSlugsRewriteRules($customPostType);
    }

    public function exposePatchSinglePostRewriteRulesForPostType(array $rules, CustomPostTypeInterface $customPostType): array
    {
        return $this->patchSinglePostRewriteRulesForPostType($rules, $customPostType);
    }
}

final class StubCustomPostType implements CustomPostTypeInterface
{
    public function __construct(private string $key, private array $args)
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): static
    {
        $this->args = $args;

        return $this;
    }

    public function getArg(string $key): mixed
    {
        return $this->args[$key] ?? null;
    }

    public function setArg(string $key, mixed $value): static
    {
        $this->args[$key] = $value;

        return $this;
    }
}
