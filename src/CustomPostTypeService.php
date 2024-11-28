<?php

namespace WonderWp\Component\CPT;

use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Form\Field\BooleanField;
use WonderWp\Component\Form\Field\FieldInterface;
use WonderWp\Component\Form\FormInterface;
use WonderWp\Component\PluginSkeleton\AbstractManager;
use WonderWp\Component\Repository\PostRepository;
use WonderWp\Component\Service\AbstractService;
use WonderWp\Component\Service\ServiceInterface;

/**
 * @deprecated
 */
class CustomPostTypeService extends AbstractCustomPostTypeService
{
    /** @var CustomPostType */
    protected $customPostType;

    /**
     * CustomPostTypeService constructor.
     *
     * @param CustomPostType|null $customPostType
     * @param AbstractManager|null $manager
     */
    public function __construct(CustomPostType $customPostType = null, AbstractManager $manager = null)
    {
        parent::__construct($manager);
        $this->customPostType = $customPostType;
    }

    /**
     * @return CustomPostType
     */
    public function getCustomPostType()
    {
        return $this->customPostType;
    }

    /**
     * @param CustomPostType $customPostType
     *
     * @return static
     */
    public function setCustomPostType($customPostType)
    {
        $this->customPostType = $customPostType;

        return $this;
    }

    /**
     * Register shorthand method
     * @return $this
     */
    public function register()
    {
        return $this->customPostType->register();
    }

    public function makeMetasAvailableInRestApi()
    {
        //retrieve cpt metas definitions
        $metasDefinitions = $this->getCustomPostType()->getMetaDefinitions();
        if (!empty($metasDefinitions)) {
            //foreach meta definition, register the meta towards the rest api
            $cptName        = $this->getCustomPostType()->getName();
            $opts           = $this->getCustomPostType()->getOpts();
            $authCapability = !empty($opts['capabilities']['edit_post']) ? $opts['capabilities']['edit_post'] : 'edit_posts';
            foreach ($metasDefinitions as $metaName => $metaDefinition) {
                $metaArgs = [
                    'show_in_rest'      => true,
                    'single'            => true,
                    'type'              => 'string',
                    'auth_callback'     => function () use ($authCapability) {
                        return current_user_can($authCapability);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ];
                $registered = register_post_meta(
                    $cptName,
                    $metaName,
                    apply_filters('cpt.' . $cptName . '.register_post_meta.' . $metaName, $metaArgs)
                );
            }
        }
    }
}
