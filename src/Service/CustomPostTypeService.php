<?php

namespace WonderWp\Component\CPT\Service;

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

    public function autoload(array $classNameFromFiles = [], array $discoveryPaths = [], callable $successCallback = null, array $excludedClasses=[]): array
    {
        $discoveryPathsRoots = $this->manager->getConfig('discoveryPathsRoots', [
            'post-types' => rtrim($this->manager->getConfig('path.root') ?? '', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
        ]);
        $discoverFolderSuffix = $this->manager->getConfig('cptservice.discoverFolderSuffix', 'PostTypes');
        $defaultPaths = $this->deductDefaultDiscoveryPaths($discoveryPathsRoots, $discoverFolderSuffix);
        $discoveryPaths = array_merge($defaultPaths, $discoveryPaths);
        $autoLoaded = parent::autoload($classNameFromFiles, $discoveryPaths, $successCallback);

        if (!empty($this->customPostTypes)) {
            $this->registerCustomPostTypes();
        }

        return $autoLoaded;
    }

    protected function autoloadFile(string $className, string $filePath): object
    {
        $instance = parent::autoloadFile($className, $filePath);

        $this->addCustomPostType($instance);

        return $instance;
    }


}
