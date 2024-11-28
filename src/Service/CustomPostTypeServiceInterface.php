<?php

namespace WonderWp\Component\CPT\Service;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponseInterface;
use WonderWp\Component\PluginSkeleton\Service\RegistrableInterface;

interface CustomPostTypeServiceInterface extends RegistrableInterface
{
    /**
     * @return CustomPostTypeInterface[]
     */
    public function getCustomPostTypes(): array;

    /**
     * @param string $key
     * @return CustomPostTypeInterface|null
     */
    public function getCustomPostType(string $key): ?CustomPostTypeInterface;

    /**
     * @param CustomPostTypeInterface $customPostType
     * @return $this
     */
    public function addCustomPostType(CustomPostTypeInterface $customPostType): static;

    /**
     * @param string $key
     * @return $this
     */
    public function removeCustomPostType(string $key): static;

    /**
     * @param CustomPostTypeInterface[] $customPostTypes
     * @return $this
     */
    public function setCustomPostTypes(array $customPostTypes): static;

    /**
     * Register all custom post types
     *
     * @return CustomPostTypeRegistrationResponseInterface[]
     * @throws CustomPostTypeRegistrationException
     */
    public function registerCustomPostTypes(): array;

    /**
     * Register a custom post type
     *
     * @param CustomPostTypeInterface $customPostType
     * @return CustomPostTypeRegistrationResponseInterface
     * @throws CustomPostTypeRegistrationException
     */
    public function registerCustomPostType(CustomPostTypeInterface $customPostType): CustomPostTypeRegistrationResponseInterface;
}
