<?php

namespace WonderWp\Component\CPT\Service;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\PluginSkeleton\ManagerAwareInterface;
use WonderWp\Component\PluginSkeleton\ManagerAwareTrait;
use WonderWp\Component\PluginSkeleton\Service\RegistrableInterface;

class CustomPostTypeService extends AbstractCustomPostTypeService implements RegistrableInterface, ManagerAwareInterface
{
    use ManagerAwareTrait;

    public function register()
    {
        add_action('init', function () {
            $autoLoaded = $this->autoload();
        }, 9);
    }

    protected function autoloadFile(string $className, string $filePath): object
    {
        $instance = parent::autoloadFile($className, $filePath);
        
        if($instance instanceof CustomPostTypeInterface) {
            $this->addCustomPostType($instance);
        }

        return $instance;
    }


}
