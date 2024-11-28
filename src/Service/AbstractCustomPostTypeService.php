<?php

namespace WonderWp\Component\CPT\Service;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Exception\CustomPostTypeRegistrationException;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponse;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponseInterface;
use WonderWp\Component\Service\AbstractService;

abstract class AbstractCustomPostTypeService extends AbstractService implements CustomPostTypeServiceInterface
{
    /** @var CustomPostTypeInterface[] */
    protected $customPostTypes = [];

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
                $slugs = $cptOpts['rewrite']['slugs'];
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

    protected function computeAdditionalSlugsRewriteRules(CustomPostTypeInterface $customPostType): array
    {
        $rules = [];
        if (empty($cptOpts['rewrite']) || empty($cptOpts['rewrite']['slugs']) || !is_array($cptOpts['rewrite']['slugs'])) {
            return $rules;
        }
        $slugs = $cptOpts['rewrite']['slugs'];
        foreach ($slugs as $slug) {
            $rules[$slug . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?' . $this->customPostType->getKey() . '=$matches[1]&paged=$matches[2]';
            $rules[$slug . '/([^/]+)(?:/([0-9]+))?/?$'] = 'index.php?' . $this->customPostType->getKey() . '=$matches[1]&page=$matches[2]';
        }
        return $rules;
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
