<?php

namespace WonderWp\Component\CPT;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Exception\CustomPostTypeRegistrationException;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponse;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponseInterface;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\BooleanField;
use WonderWp\Component\Form\Field\FieldInterface;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\Repository\PostRepository;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Component\Service\ServiceInterface;

/**
 * @deprecated Use \WonderWp\Component\CPT\Service\CustomPostTypeService instead
 */
abstract class AbstractCustomPostTypeService extends \WonderWp\Component\CPT\Service\CustomPostTypeService
{
    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function addToSitemap(string $sitemap): string
    {
        trigger_error('Method addToSitemap has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated.
     * If you used it, you can get its previous content from the git history and add it to your project.
     */
    public function fixCptDashboardUrl(string $computedUrl): string
    {
        trigger_error('Method fixCptDashboardUrl has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function createMetasForm(): static
    {
       trigger_error('Method createMetasForm has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function displayCptsMetaBox(\WP_Post $post)
    {
       trigger_error('Method displayCptsMetaBox has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function computeCptsMetaBox(\WP_Post $post)
    {
        trigger_error('Method computeCptsMetaBox has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function createFieldFromMetaDefinition($metaKey, $metaDef, $savedMetaValue)
    {
        trigger_error('Method createFieldFromMetaDefinition has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function saveMetasForm($post_id, \WP_Post $post)
    {
        trigger_error('Method saveMetasForm has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }

    /**
     * @deprecated This method has been removed because it was a mix of concerns and too opinionated
     */
    public function makeMetasAvailableInRestApi()
    {
        trigger_error('Method makeMetasAvailableInRestApi has been removed because it was a mix of concerns and too opinionated.
        If you used it, you can get its previous content from the git history and add it to your project.', E_USER_DEPRECATED);
    }
}
