<?php

namespace WonderWp\Component\CPT\Service;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Exception\CustomPostTypeRegistrationException;
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
            if (!empty($cptOpts['rewrite']) && !empty($cptOpts['rewrite']['slugs']) && is_array($cptOpts['rewrite']['slugs'])) {
                unset($cptOpts['rewrite']['slugs']);
                $slugRewriteRules = $this->computeAdditionalSlugsRewriteRules($customPostType);
                if (!empty($slugRewriteRules)) {
                    $this->registerAdditionalRewriteRules($slugRewriteRules, 'top');
                }
            }

            $wpRes = register_post_type($customPostType->getKey(), $cptOpts);

            if ($wpRes instanceof \WP_Error) {
                throw new CustomPostTypeRegistrationException($wpRes->get_error_message(), $wpRes->get_error_code());
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

    protected function computeAdditionalSlugsRewriteRules(CustomPostTypeInterface $customPostType): array
    {
        $cptOpts = $customPostType->getArgs();
        $rewriteSlugs = !empty($cptOpts['rewrite']['slugs']) && is_array($cptOpts['rewrite']['slugs'])
            ? $cptOpts['rewrite']['slugs']
            : [];

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
                if (!str_starts_with($regex, $archiveSlug . '/')) {
                    continue;
                }

                $newRegex = preg_replace(
                    '#^' . preg_quote($archiveSlug, '#') . '/\(\[\^/\]\+\)(\(\?:/\(\(\[0-9\]\+\)\)\)\?/?\$)$#',
                    $archiveSlug . '/(?!' . $reservedSlugPattern . ')([^/]+)$1',
                    $regex
                );

                if (!is_string($newRegex) || $newRegex === $regex) {
                    continue;
                }

                unset($rules[$regex]);
                $rules[$newRegex] = $query;
                break;
            }
        }

        return $rules;
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
