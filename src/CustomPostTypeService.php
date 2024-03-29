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

class CustomPostTypeService extends AbstractService
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

    /**
     * Call this method in the cpt hook service to add the cpt entries to the sitemap
     *
     * @param string $sitemap
     *
     * @return string
     * @example
     * $cptService = $this->manager->getService(ServiceInterface::CUSTOM_POST_TYPE_SERVICE_NAME);
     * add_filter('wwp.htmlsitemap.content', [$cptService, 'addToSitemap']);
     *
     */
    public function addToSitemap($sitemap)
    {
        /** @var PostRepository $repo */
        $repo = $this->manager->getService(ServiceInterface::REPOSITORY_SERVICE_NAME);
        if (!empty($repo)) {
            $cptSlug    = sanitize_title($this->customPostType->getName());
            $methodName = apply_filters($cptSlug . '.sitemap.repoMethodName', 'findAll');
            $posts      = $repo->$methodName();
            if (!empty($posts)) {
                $mainWrapCssClasses     = [
                    'sitemap-' . $cptSlug . '-wrap',
                ];
                $childrenWrapCssClasses = [
                    'children',
                ];
                $sitemap                .= '<li class="' . implode(' ', apply_filters($cptSlug . '.sitemap.mainwrap.cssclasses', $mainWrapCssClasses, $this->customPostType->getName())) . '">
                    <a href="' . apply_filters($cptSlug . '.sitemap.cpt.parentpage.href', '#') . '">' . trad($this->customPostType->getName(), $this->manager->getConfig('textDomain')) . '</a>
                    <ul class="' . apply_filters($cptSlug . '.sitemap.childrenwrap.cssclasses', implode(' ', $childrenWrapCssClasses), $this->customPostType->getName()) . '">';
                foreach ($posts as $post) {
                    $post->filter = 'sample';
                    $sitemap      .= ' <li><a href = "' . get_permalink($post) . '" > ' . $post->post_title . '</a></li> ';
                }
                $sitemap .= '</ul>
                </li> ';
            }
        }

        return $sitemap;
    }

    /**
     * Call this method in the cpt hok service to fix the cpt dashboard url
     *
     * @param string $computedUrl , the original url
     *
     * @return string , the new url
     */
    public function fixCptDashboardUrl($computedUrl)
    {
        $computedUrl = admin_url('edit.php?post_type=' . $this->customPostType->getName());

        return $computedUrl;
    }

    /**
     * Create a meta box with a form to administer post metas based on meta definitions
     */
    public function createMetasForm()
    {
        $metasDefinition = $this->customPostType->getMetaDefinitions();
        if (!empty($metasDefinition)) {
            add_meta_box('cpts_meta_box',
                __('cpts.metabox.title'),
                [$this, 'displayCptsMetaBox'],
                $this->customPostType->getName(), 'normal', 'high'
            );
        }
    }

    /**
     * The automatic meta form
     *
     * @param \WP_Post $post
     */
    public function displayCptsMetaBox(\WP_Post $post)
    {
        $metaBoxForm = $this->computeCptsMetaBox($post);
        if (!empty($metaBoxForm->getFields())) {
            echo $metaBoxForm->renderView([
                'formStart' => ['showFormTag' => false],
                'formEnd'   => ['showFormTag' => false, 'showSubmit' => false],
            ]);
        }
    }

    /**
     * @param \WP_Post $post
     *
     * @return FormInterface
     */
    public function computeCptsMetaBox(\WP_Post $post)
    {
        $container = Container::getInstance();
        /** @var FormInterface $form */
        $form            = $container['wwp.form.form'];
        $metasDefinition = $this->customPostType->getMetaDefinitions();

        if (!empty($metasDefinition)) {
            $metasValues = !empty($post->ID) ? get_post_meta($post->ID) : [];

            foreach ($metasDefinition as $metaKey => $metaDef) {
                $savedMetaValue = !empty($metasValues[$metaKey]) ? reset($metasValues[$metaKey]) : (!empty($metaDef[1]) ? $metaDef[1] : null);
                if (is_serialized($savedMetaValue)) {
                    $savedMetaValue = unserialize($savedMetaValue);
                }
                $field = $this->createFieldFromMetaDefinition($metaKey, $metaDef, $savedMetaValue);
                if ($field instanceof FieldInterface) {
                    $form->addField($field);
                }
            }
        }

        return $form;
    }

    /**
     * @param string $metaKey
     * @param array|callable $metaDef
     * @param mixed $savedMetaValue
     *
     * @return FieldInterface|null
     */
    public function createFieldFromMetaDefinition($metaKey, $metaDef, $savedMetaValue)
    {
        if (is_callable($metaDef)) {
            return call_user_func($metaDef, $metaKey, $savedMetaValue);
        }

        $inputType       = $metaDef[0];
        $displayRules    = !empty($metaDef[2]) ? $metaDef[2] : [];
        $validationRules = !empty($metaDef[3]) ? $metaDef[3] : [];

        return new $inputType($metaKey, $savedMetaValue, $displayRules, $validationRules);
    }

    /**
     * Save meta box form submitted data into post metas
     *
     * @param          $post_id
     * @param \WP_Post $post
     */
    public function saveMetasForm($post_id, \WP_Post $post)
    {
        $metasDefinition = $this->customPostType->getMetaDefinitions();

        // Check post type
        if ($post->post_type == $this->customPostType->getName()) {
            // Store data in post meta table if present in post data

            $metasDefinition = $this->customPostType->getMetaDefinitions();
            if (!empty($metasDefinition)) {
                foreach ($metasDefinition as $metaKey => $metaDef) {
                    if (isset($_POST[$metaKey])) {
                        update_post_meta($post_id, $metaKey, $_POST[$metaKey]);
                    } else {
                        if ($metaDef[0] === BooleanField::class) {
                            update_post_meta($post_id, $metaKey, 0);
                        }
                    }
                }
            }
        }
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
