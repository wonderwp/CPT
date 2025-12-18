<?php

namespace WonderWp\Component\CPT\Traits;

trait HasCustomPostTypeAutoloader
{
    /**
     * Customize discovery paths for custom post types.
     *
     * @param array $discoveryPaths
     * @return array
     */
    protected function resolveDiscoveryPaths(array $discoveryPaths): array
    {
        $discoveryPathsRoots = $this->manager->getConfig('discoveryPathsRoots', [
            'post-types' => rtrim($this->manager->getConfig('path.root') ?? '', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR,
        ]);

        $discoverFolderSuffix = $this->manager->getConfig('customPostTypeService.discoverFolderSuffix', 'PostTypes');
        $defaultPaths         = $this->deductDefaultDiscoveryPaths($discoveryPathsRoots, $discoverFolderSuffix);

        return array_merge($defaultPaths, $discoveryPaths);
    }

    /**
     * After autoloading, register discovered custom post types.
     *
     * @param array    $result
     * @param array    $classNameFromFiles
     * @param array    $discoveryPaths
     * @param callable $successCallback
     * @param array    $excludedClasses
     *
     * @return array
     */
    protected function afterAutoload(
        array $result,
        array $classNameFromFiles,
        array $discoveryPaths,
        callable $successCallback,
        array $excludedClasses
    ): array {
        if (!empty($this->customPostTypes)) {
            $this->registerCustomPostTypes();
        }

        return $result;
    }
}


