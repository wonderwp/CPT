<?php

namespace WonderWp\Component\CPT;

use WonderWp\Component\CPT\Definition\AbstractCustomPostType;
use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;
use WonderWp\Component\CPT\Exception\CustomPostTypeRegistrationException;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponse;
use WonderWp\Component\CPT\Response\CustomPostTypeRegistrationResponseInterface;
use WonderWp\Component\HttpFoundation\Result;
use function WonderWp\Functions\array_merge_recursive_distinct;

/**
 * @deprecated Use \WonderWp\Component\CPT\Definition\CustomPostType instead
 */
class CustomPostType extends AbstractCustomPostType
{
    /** @deprecated This is a mix of concerns and will be moved to a more appropriate place in the futur */
    protected string $taxonomy_name;
    /** @deprecated This is a mix of concerns and will be moved to a more appropriate place in the futur */
    protected array $taxonomy_opts;
    /** @deprecated This is a mix of concerns and will be moved to a more appropriate place in the futur */
    protected array $metaDefinitions;

    public function __construct($name = '', array $passedArgs = [], $taxonomyName = '', array $passedTaxonomyArgs = [])
    {
        $defaultArgs = static::getDefaultArgs();
        $name = !empty($name) ? $name : static::getDefaultKey();
        $args = array_merge_recursive_distinct($defaultArgs, $passedArgs);
        parent::__construct($name, $args);

        $defaultTaxonomyOpts = static::getDefaultTaxonomyOpts();
        $this->taxonomy_name = !empty($taxonomyName) ? $taxonomyName : static::getDefaultTaxonomyName();
        $this->taxonomy_opts = array_merge_recursive_distinct($defaultTaxonomyOpts, $passedTaxonomyArgs);
    }

    public static function getDefaultKey()
    {
        return '';
    }

    public static function getDefaultArgs(): array
    {
        return [
            'public' => true,
            'hierarchical' => false,
            'show_in_admin_bar' => false,
            'exclude_from_search' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];
    }

    //===========================================================//
    // Deprecated methods //
    //===========================================================//

    /**
     * @return string
     * @deprecated Use getKey() instead
     */
    public function getName()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use getKey() instead.', E_USER_DEPRECATED);
        return $this->getKey();
    }

    /**
     * @param string $name
     * @return static
     * @deprecated Use setKey() instead
     */
    public function setName($name)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use setKey() instead.', E_USER_DEPRECATED);
        return $this->setKey($name);
    }

    /**
     * @return array
     * @deprecated Use getArgs() instead
     */
    public function getOpts()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use getArgs() instead.', E_USER_DEPRECATED);
        return $this->getArgs();
    }

    /**
     * @param array $opts
     * @return static
     * @deprecated Use setArgs() instead
     */
    public function setOpts($opts)
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use setArgs() instead.', E_USER_DEPRECATED);
        return $this->setArgs($opts);
    }


    /**
     * @deprecated Use getDefaultKey() instead
     */
    public static function getDefaultName()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use getDefaultKey() instead.', E_USER_DEPRECATED);
        return static::getDefaultKey();
    }

    /**
     * @deprecated Use getDefaultArgs() instead
     */
    public static function getDefaultOpts(): array
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. Use getDefaultArgs() instead.', E_USER_DEPRECATED);
        return static::getDefaultArgs();
    }

    /**
     * @deprecated CustomPostTypes should not register themselves. Use a CustomPostTypeService instead.
     */
    public function register()
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated. CustomPostTypes should not register themselves. Use a CustomPostTypeService instead.', E_USER_DEPRECATED);
    }


    //===========================================================//
    // Methods that should move at some point //
    //===========================================================//

    /**
     * @return string
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     */
    public function getTaxonomyName()
    {
        return $this->taxonomy_name;
    }

    /**
     * @param string $taxonomy_name
     *
     * @return static
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     */
    public function setTaxonomyName($taxonomy_name)
    {
        $this->taxonomy_name = $taxonomy_name;

        return $this;
    }

    /**
     * @return array
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     */
    public function getTaxonomyOpts()
    {
        return $this->taxonomy_opts;
    }

    /**
     * @param array $taxonomy_opts
     * @return static
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     *
     */
    public function setTaxonomyOpts($taxonomy_opts)
    {
        $this->taxonomy_opts = $taxonomy_opts;

        return $this;
    }

    /**
     * @return string
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     */
    public static function getDefaultTaxonomyName()
    {
        return '';
    }

    /**
     * @return array
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     */
    public static function getDefaultTaxonomyOpts()
    {
        return [];
    }

    /**
     * @return array
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     */
    public function getMetaDefinitions()
    {
        return $this->metaDefinitions;
    }

    /**
     * @param array $metaDefinitions
     * @return static
     * @deprecated This is a mix of concerns and will be moved to a more appropriate place in the future
     *
     */
    public function setMetaDefinitions(array $metaDefinitions)
    {
        $this->metaDefinitions = $metaDefinitions;

        return $this;
    }

}
