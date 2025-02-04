<?php

use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\PluginSkeleton\ManagerAwareInterface;
use WonderWp\Component\Service\ServiceInterface;
use WonderWp\Component\PluginSkeleton\ManagerInterface;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\CPT\Service\CustomPostTypeService;
use WonderWp\Component\CPT\Service\CustomPostTypeServiceInterface;

add_action('wonderwp.loader.load', 'wwp_register_cpt_definitions_towards_container', 10, 2);
add_action('wwp.abstract_manager.run', 'wwp_register_cpt_service_towards_manager', 10, 2);

function wwp_register_cpt_definitions_towards_container(Container $container)
{
    $container['wwp.cpt.defaultService'] = $container->factory(function () {
        return new CustomPostTypeService();
    });
}

function wwp_register_cpt_service_towards_manager(ManagerInterface $manager, Container $container)
{
    // Custom Post Types
    try {
        $cptService = $manager->getService(ServiceInterface::CUSTOM_POST_TYPE_SERVICE_NAME);
        if ($cptService instanceof CustomPostTypeServiceInterface) {
            $cptService->register();
        }
    } catch (ServiceNotFoundException $e) {
        if ($e->getServiceType() === ServiceInterface::CUSTOM_POST_TYPE_SERVICE_NAME) {
            //No custom post type service found, use the default one instead
            $cptService = $container['wwp.cpt.defaultService'];
            if ($cptService instanceof CustomPostTypeServiceInterface) {
                if($cptService instanceof ManagerAwareInterface){
                    $cptService->setManager($manager);
                }
                $cptService->register();
            }
        } else {
            throw $e;
        }
    }
}
