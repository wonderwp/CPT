<?php

namespace WonderWp\Component\CPT;

use WonderWp\Component\PluginSkeleton\Controller\AbstractPluginFrontendController;
use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Repository\PostRepository;
use WonderWp\Component\Service\ServiceInterface;

class CustomPostTypePublicController extends AbstractPluginFrontendController
{
    protected $customPostType;

    /**
     * @return mixed
     */
    public function getCustomPostType()
    {
        return $this->customPostType;
    }

    /**
     * @param mixed $customPostType
     *
     * @return static
     */
    public function setCustomPostType($customPostType)
    {
        $this->customPostType = $customPostType;

        return $this;
    }

    /** @inheritdoc */
    public function defaultAction(array $attributes = [])
    {
        return $this->listAction($attributes);
    }

    /**
     * Default List action
     *
     * @param array $attributes
     *
     * @return string
     * @throws \WonderWp\Component\PluginSkeleton\Exception\ViewNotFoundException
     */
    public function listAction(array $attributes = [])
    {
        $request = $this->request;
        $page    = (int)$request->get('pageno', 1);
        $perPage = $this->manager->getConfig('per_page', 10);

        try {
            $filterService = $this->manager->getService('filters');
            $criterias     = !empty($filterService) ? $filterService->prepareCriterias($request->request->all(), $attributes) : [];
        } catch (ServiceNotFoundException $e) {
            $criterias = [];
        }

        /** @var PostRepository $repository */
        $repository = $this->manager->getService(ServiceInterface::REPOSITORY_SERVICE_NAME);

        $posts = !empty($repository) ? $repository->findBy($criterias, null, ($page * $perPage) - $perPage, $perPage) : [];

        $view = !empty($attributes['vue']) ? $attributes['vue'] : 'list';

        $viewParams = [
            'posts'      => $posts,
            'attributes' => $attributes,
        ];

        return $this->renderView($view, $this->filterViewParams($viewParams));
    }

    protected function filterViewParams($viewParams)
    {
        return $viewParams;
    }

    /**
     * Default Detail View
     *
     * @param array $attributes
     *
     * @return string
     * @throws \WonderWp\Component\PluginSkeleton\Exception\ViewNotFoundException
     */
    public function detailAction(array $attributes)
    {
        if (!empty($attributes['post'])) {
            $post = get_post($attributes['post']);
        } else {
            global $post;
        }

        $view = !empty($attributes['vue']) ? $attributes['vue'] : 'detail';

        $viewParams = [
            'post' => $post,
        ];

        return $this->renderView($view, $viewParams);
    }
}
