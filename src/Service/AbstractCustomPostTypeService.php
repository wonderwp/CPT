<?php

namespace WonderWp\Component\CPT\Service;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Exception\CustomPostTypeRegistrationException;
use WonderWp\Component\CPT\Locale\LocaleSlugMap;
use WonderWp\Component\CPT\Locale\LocaleSlugRedirectDecider;
use WonderWp\Component\CPT\Locale\PostTypeLinkFilter;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponse;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponseInterface;
use WonderWp\Component\Service\Traits\HasAutoloadingCapabilities;
use WonderWp\Component\CPT\Traits\HasCustomPostTypeAutoloader;
use WonderWp\Component\Service\AbstractService;

abstract class AbstractCustomPostTypeService extends AbstractService implements CustomPostTypeServiceInterface
{
    use HasAutoloadingCapabilities, HasCustomPostTypeAutoloader {
        HasCustomPostTypeAutoloader::resolveDiscoveryPaths insteadof HasAutoloadingCapabilities;
        HasCustomPostTypeAutoloader::afterAutoload insteadof HasAutoloadingCapabilities;
    }
    /** @var CustomPostTypeInterface[] */
    protected $customPostTypes = [];

    /** @var array<class-string, bool> */
    private static array $rewriteRulesPatchFiltersRegistered = [];

    //========================================================================================================//
    // Getters and Setters
    //========================================================================================================//

    /** @inheritDoc */
    public function getCustomPostTypes(): array
    {
        return $this->customPostTypes;
    }

    /** @inheritDoc */
    public function getCustomPostType(string $key): ?CustomPostTypeInterface
    {
        return $this->customPostTypes[$key] ?? null;
    }

    /** @inheritDoc */
    public function addCustomPostType(CustomPostTypeInterface $customPostType): static
    {
        $this->customPostTypes[$customPostType->getKey()] = $customPostType;
        return $this;
    }

    /** @inheritDoc */
    public function removeCustomPostType(string $key): static
    {
        if (isset($this->customPostTypes[$key])) {
            unset($this->customPostTypes[$key]);
        }
        return $this;
    }

    /** @inheritDoc */
    public function setCustomPostTypes(array $customPostTypes): static
    {
        $this->customPostTypes = $customPostTypes;
        return $this;
    }

    //========================================================================================================//
    // Registration methods
    //========================================================================================================//

    /** @inheritDoc */
    public function registerCustomPostTypes(): array
    {
        $this->registerRewriteRulesPatchFilter();

        $responses = [];

        foreach ($this->customPostTypes as $customPostType) {
            $responses[$customPostType->getKey()] = $this->registerCustomPostType($customPostType);
        }

        return $responses;
    }

    /** @inheritDoc */
    public function registerCustomPostType(CustomPostTypeInterface $customPostType): CustomPostTypeRegistrationResponseInterface
    {
        try {
            $cptOpts = $customPostType->getArgs();
            if (!empty($cptOpts['rewrite']) && is_array($cptOpts['rewrite'])) {
                $localeSlugMap = $this->getLocaleSlugMap($customPostType);
                $hasAliases    = !empty($cptOpts['rewrite']['slugs']) && is_array($cptOpts['rewrite']['slugs']);

                if ($localeSlugMap !== null || $hasAliases) {
                    $slugRewriteRules = $this->computeAdditionalSlugsRewriteRules($customPostType);
                    if (!empty($slugRewriteRules)) {
                        $this->registerAdditionalRewriteRules($slugRewriteRules, 'top');
                    }
                }

                // Options handled here, unknown to WordPress.
                unset(
                    $cptOpts['rewrite']['slugs'],
                    $cptOpts['rewrite']['locale_slugs'],
                    $cptOpts['rewrite']['locale_meta'],
                    $cptOpts['rewrite']['default_locale'],
                    $cptOpts['rewrite']['redirect_to_locale_slug']
                );

                if ($localeSlugMap !== null) {
                    // Stable permastruct: the canonical slug never depends on the request locale.
                    $cptOpts['rewrite']['slug'] = $localeSlugMap->getDefaultSlug();
                    $this->registerLocaleSlugHooks($customPostType, $localeSlugMap);
                }
            }

            $wpRes = register_post_type($customPostType->getKey(), $cptOpts);

            if ($wpRes instanceof \WP_Error) {
                $wpCode = $wpRes->get_error_code();
                $exceptionCode = is_numeric($wpCode) ? (int) $wpCode : 500;
                throw new CustomPostTypeRegistrationException($wpRes->get_error_message(), $exceptionCode);
            } else {
                $response = new CustomPostTypeRegistrationResponse(200, CustomPostTypeRegistrationResponseInterface::SUCCESS);
                $response->setWpRegistrationResult($wpRes);
            }
        } catch (\Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            $response = new CustomPostTypeRegistrationResponse($errorCode, CustomPostTypeRegistrationResponseInterface::ERROR);
            $response->setError($e);
        }

        return $response;
    }

    protected function registerRewriteRulesPatchFilter(): void
    {
        $serviceClass = static::class;

        if (!empty(self::$rewriteRulesPatchFiltersRegistered[$serviceClass])) {
            return;
        }

        add_filter('rewrite_rules_array', [$this, 'patchRewriteRulesArray']);
        self::$rewriteRulesPatchFiltersRegistered[$serviceClass] = true;
    }

    public function patchRewriteRulesArray(array $rules): array
    {
        foreach ($this->customPostTypes as $customPostType) {
            $cptOpts = $customPostType->getArgs();

            if (empty($cptOpts['has_archive'])) {
                continue;
            }

            $rules = $this->patchSinglePostRewriteRulesForPostType($rules, $customPostType);
        }

        return $rules;
    }

    //========================================================================================================//
    // Locale slugs (translatable, flush independent)
    //========================================================================================================//

    /**
     * Slug map declared through `rewrite.locale_slugs`, null when the post type does not use it.
     *
     * The default locale (canonical slug, fallback for posts without language) is, in this order:
     * `rewrite.default_locale`, the `wonderwp.customposttype.rewrite.default-locale` filter, `get_locale()`.
     * Only the last one depends on the request: declare one of the first two for a stable permastruct.
     */
    protected function getLocaleSlugMap(CustomPostTypeInterface $customPostType): ?LocaleSlugMap
    {
        $cptOpts = $customPostType->getArgs();
        $rewrite = $cptOpts['rewrite'] ?? null;
        if (!is_array($rewrite) || empty($rewrite['locale_slugs'])) {
            return null;
        }

        $defaultLocale = (string) apply_filters(
            'wonderwp.customposttype.rewrite.default-locale',
            (string) ($rewrite['default_locale'] ?? ''),
            $customPostType
        );
        if ($defaultLocale === '') {
            $defaultLocale = (string) get_locale();
        }

        return LocaleSlugMap::fromArgs($rewrite, $defaultLocale);
    }

    protected function registerLocaleSlugHooks(CustomPostTypeInterface $customPostType, LocaleSlugMap $map): void
    {
        $rewrite = $customPostType->getArgs()['rewrite'];

        $linkKey = static::class . '::post_type_link::' . $customPostType->getKey();
        if (empty(self::$rewriteRulesPatchFiltersRegistered[$linkKey])) {
            $linkFilter = new PostTypeLinkFilter($customPostType->getKey(), $map, (string) ($rewrite['locale_meta'] ?? ''));
            add_filter('post_type_link', [$linkFilter, 'filter'], 10, 2);
            self::$rewriteRulesPatchFiltersRegistered[$linkKey] = true;
        }

        $redirectKey = static::class . '::template_redirect';
        if (!empty($rewrite['redirect_to_locale_slug']) && empty(self::$rewriteRulesPatchFiltersRegistered[$redirectKey])) {
            // After the "module unavailable" redirections (priority 10), which exit before us.
            add_action('template_redirect', [$this, 'maybeRedirectToLocaleSlug'], 20);
            self::$rewriteRulesPatchFiltersRegistered[$redirectKey] = true;
        }
    }

    /**
     * 301 to the canonical URL of a single post requested with another slug (legacy alias,
     * slug of another language). The target only depends on the post, so it stays cache friendly.
     */
    public function maybeRedirectToLocaleSlug(): void
    {
        foreach ($this->customPostTypes as $customPostType) {
            $target = $this->computeLocaleSlugRedirect($customPostType);
            if ($target !== null) {
                wp_safe_redirect($target, 301);
                $this->terminateRequest();

                return;
            }
        }
    }

    protected function computeLocaleSlugRedirect(CustomPostTypeInterface $customPostType): ?string
    {
        $rewrite = $customPostType->getArgs()['rewrite'] ?? null;
        if (!is_array($rewrite) || empty($rewrite['redirect_to_locale_slug'])) {
            return null;
        }

        $map = $this->getLocaleSlugMap($customPostType);
        if ($map === null) {
            return null;
        }

        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            return null;
        }

        $key = $customPostType->getKey();
        if (!is_singular($key)) {
            return null;
        }

        $post = get_queried_object();
        if (!is_object($post) || ($post->post_type ?? '') !== $key) {
            return null;
        }

        $localeMeta = (string) ($rewrite['locale_meta'] ?? '');
        $postLocale = $localeMeta !== '' ? (string) get_post_meta($post->ID, $localeMeta, true) : '';

        $knownSlugs = $map->getAllSlugs();
        foreach ((array) ($rewrite['slugs'] ?? []) as $alias) {
            if (is_string($alias) && sanitize_title($alias) !== '') {
                $knownSlugs[] = sanitize_title($alias);
            }
        }

        $requestPath = (string) ($GLOBALS['wp']->request ?? '');
        $newPath     = (new LocaleSlugRedirectDecider())->decide(
            $requestPath,
            $map->slugFor($postLocale),
            array_values(array_unique($knownSlugs)),
            is_preview()
        );
        if ($newPath === null) {
            return null;
        }

        $url   = user_trailingslashit(home_url('/' . $newPath));
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

        return $query !== '' ? $url . '?' . $query : $url;
    }

    protected function terminateRequest(): void
    {
        exit;
    }

    protected function computeAdditionalSlugsRewriteRules(CustomPostTypeInterface $customPostType): array
    {
        $cptOpts = $customPostType->getArgs();
        $rewriteSlugs = !empty($cptOpts['rewrite']['slugs']) && is_array($cptOpts['rewrite']['slugs'])
            ? $cptOpts['rewrite']['slugs']
            : [];

        // One rule set for every language: what is resolvable never depends on the flushing locale.
        $localeSlugMap = $this->getLocaleSlugMap($customPostType);
        if ($localeSlugMap !== null) {
            $rewriteSlugs = array_values(array_unique(array_merge($localeSlugMap->getAllSlugs(), $rewriteSlugs)));
        }

        if (empty($rewriteSlugs)) {
            return [];
        }

        $rewriteSlugs = apply_filters('wonderwp.customposttype.rewrite.slugs', $rewriteSlugs, $customPostType);

        if (empty($rewriteSlugs)) {
            return [];
        }

        $archiveSlugs = $this->getArchiveRewriteSlugs($cptOpts, $customPostType);
        $reservedSlugPattern = $this->getReservedSlugNegativeLookaheadPattern($customPostType);
        $rules = [];

        foreach ($rewriteSlugs as $slug) {
            $rules[$slug . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?' . $customPostType->getKey() . '=$matches[1]&paged=$matches[2]';

            $singlePostPattern = $slug . '/([^/]+)(?:/([0-9]+))?/?$';
            if ($this->isArchiveRewriteSlug($slug, $archiveSlugs)) {
                $singlePostPattern = $slug . '/(?!' . $reservedSlugPattern . ')([^/]+)(?:/([0-9]+))?/?$';
            }

            $rules[$singlePostPattern] = 'index.php?' . $customPostType->getKey() . '=$matches[1]&page=$matches[2]';
        }

        return $rules;
    }

    protected function patchSinglePostRewriteRulesForPostType(array $rules, CustomPostTypeInterface $customPostType): array
    {
        $cptOpts = $customPostType->getArgs();
        $archiveSlugs = $this->getArchiveRewriteSlugs($cptOpts, $customPostType);
        $reservedSlugPattern = $this->getReservedSlugNegativeLookaheadPattern($customPostType);
        $singlePostQuery = 'index.php?' . $customPostType->getKey() . '=$matches[1]&page=$matches[2]';

        foreach ($rules as $regex => $query) {
            if ($query !== $singlePostQuery || str_contains($regex, '(?!')) {
                continue;
            }

            foreach ($archiveSlugs as $archiveSlug) {
                $newRegex = $this->injectReservedSlugLookahead($regex, $archiveSlug, $reservedSlugPattern);
                if ($newRegex === null) {
                    continue;
                }

                // Preserve position: unset()+append would push the rule after WordPress'
                // page catch-all and break /{archive}/{slug}/ singles (matched as pagename → 404).
                $reordered = [];
                foreach ($rules as $existingRegex => $existingQuery) {
                    if ($existingRegex === $regex) {
                        $reordered[$newRegex] = $query;
                        continue;
                    }
                    $reordered[$existingRegex] = $existingQuery;
                }
                $rules = $reordered;
                break;
            }
        }

        return $rules;
    }

    /**
     * Inject a negative lookahead for reserved archive sub-slugs into a WP single-post rewrite rule.
     *
     * @return string|null Patched regex, or null when the rule does not match the expected WP shape.
     */
    protected function injectReservedSlugLookahead(
        string $regex,
        string $archiveSlug,
        string $reservedSlugPattern
    ): ?string {
        if (!str_starts_with($regex, $archiveSlug . '/')) {
            return null;
        }

        $newRegex = preg_replace(
            '#^' . preg_quote($archiveSlug, '#') . '/\(\[\^/\]\+\)(\(\?:/\(\[0-9\]\+\)\)\?/\?\$)$#',
            $archiveSlug . '/(?!' . $reservedSlugPattern . ')([^/]+)$1',
            $regex
        );

        if (!is_string($newRegex) || $newRegex === $regex) {
            return null;
        }

        return $newRegex;
    }

    protected function getReservedSlugNegativeLookaheadPattern(CustomPostTypeInterface $customPostType): string
    {
        $reservedSlugs = apply_filters(
            'wonderwp.customposttype.rewrite.reserved-slugs',
            ['page', 'feed'],
            $customPostType
        );

        $parts = array_map(static function ($slug) {
            return preg_quote(sanitize_title($slug), '#') . '(?:/|$)';
        }, $reservedSlugs);

        return implode('|', $parts);
    }

    protected function isArchiveRewriteSlug(string $slug, array $archiveSlugs): bool
    {
        return in_array(sanitize_title($slug), $archiveSlugs, true);
    }

    protected function getArchiveRewriteSlugs(array $cptOpts, CustomPostTypeInterface $customPostType): array
    {
        $slugs = [];

        if (!empty($cptOpts['has_archive'])) {
            if (is_string($cptOpts['has_archive'])) {
                $slugs[] = sanitize_title($cptOpts['has_archive']);
            }

            if (!empty($cptOpts['rewrite']['slug'])) {
                $slugs[] = sanitize_title($cptOpts['rewrite']['slug']);
            }

            $localeSlugMap = $this->getLocaleSlugMap($customPostType);
            if ($localeSlugMap !== null) {
                foreach ($localeSlugMap->getAllSlugs() as $localeSlug) {
                    $slugs[] = $localeSlug;
                }
            }
        }

        $archiveSlug = apply_filters('wonderwp.customposttype.archive.slug', '', $customPostType, $cptOpts);
        if (!empty($archiveSlug)) {
            $slugs[] = sanitize_title($archiveSlug);
        }

        return array_values(array_unique(array_filter($slugs)));
    }

    protected function registerAdditionalRewriteRules(array $rewriteRules, $after = 'bottom')
    {
        if (!empty($rewriteRules)) {
            foreach ($rewriteRules as $ruleCondition => $ruleDestination) {
                add_rewrite_rule($ruleCondition, $ruleDestination, $after);
            }
        }
    }
}
