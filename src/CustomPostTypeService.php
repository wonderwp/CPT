<?php

namespace WonderWp\Component\CPT;

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
     * @param CustomPostType|null  $customPostType
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
        $this->customPostType->register();

        return $this;
    }

    /**
     * Call this method in the cpt hook service to add the cpt entries to the sitemap
     * @example
     * $cptService = $this->manager->getService(ServiceInterface::CUSTOM_POST_TYPE_SERVICE_NAME);
     * add_filter('wwp.htmlsitemap.content', [$cptService, 'addToSitemap']);
     *
     * @param string $sitemap
     *
     * @return string
     */
    public function addToSitemap($sitemap)
    {
        /** @var PostRepository $repo */
        $repo = $this->manager->getService(ServiceInterface::REPOSITORY_SERVICE_NAME);
        if (!empty($repo)) {
            $posts = $repo->findAll();
            if (!empty($posts)) {
                $cptSlug                = sanitize_title($this->customPostType->getName());
                $mainWrapCssClasses     = [
                    'sitemap-' . $cptSlug . '-wrap',
                ];
                $childrenWrapCssClasses = [
                    'children',
                ];
                $sitemap                .= '<li class="' . implode(' ', apply_filters($cptSlug . '.sitemap.mainwrap.cssclasses', $mainWrapCssClasses, $this->customPostType->getName())) . '">
                    <a href="#">' . trad($this->customPostType->getName(), $this->manager->getConfig('textDomain')) . '</a>
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
}
