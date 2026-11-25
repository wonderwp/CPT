<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Support;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Service\AbstractCustomPostTypeService;

/**
 * Concrete service exposing the protected internals the locale-slug tests need.
 */
final class InspectableCptService extends AbstractCustomPostTypeService
{
    public int $terminated = 0;

    public function register(): void
    {
    }

    public function exposeComputeAdditionalSlugsRewriteRules(CustomPostTypeInterface $customPostType): array
    {
        return $this->computeAdditionalSlugsRewriteRules($customPostType);
    }

    public function exposeComputeLocaleSlugRedirect(CustomPostTypeInterface $customPostType): ?string
    {
        return $this->computeLocaleSlugRedirect($customPostType);
    }

    protected function terminateRequest(): void
    {
        $this->terminated++;
    }
}
