<?php

namespace WonderWp\Component\CPT;

use WonderWp\Component\HttpFoundation\Result;
use function WonderWp\Functions\array_merge_recursive_distinct;

class CustomPostType
{
    /** @var string */
    protected $name;
    /** @var array */
    protected $opts;
    /** @var string */
    protected $taxonomy_name;
    /** @var array */
    protected $taxonomy_opts;
    /** @var array */
    protected $metaDefinitions;

    public function __construct($name = '', array $passed_opts = [], $taxonomy_name = '', array $passed_taxonomy_opts = [])
    {
        $defaultOpts         = static::getDefaultOpts();
        $defaultTaxonomyOpts = static::getDefaultTaxonomyOpts();
        $this->name          = !empty($name) ? $name : static::getDefaultName();
        $this->opts          = array_merge_recursive_distinct($defaultOpts, $passed_opts);
        $this->taxonomy_name = !empty($taxonomy_name) ? $taxonomy_name : static::getDefaultTaxonomyName();
        $this->taxonomy_opts = array_merge_recursive_distinct($defaultTaxonomyOpts, $passed_taxonomy_opts);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getOpts()
    {
        return $this->opts;
    }

    /**
     * @param array $opts
     *
     * @return static
     */
    public function setOpts($opts)
    {
        $this->opts = $opts;

        return $this;
    }

    /**
     * @return string
     */
    public function getTaxonomyName()
    {
        return $this->taxonomy_name;
    }

    /**
     * @param string $taxonomy_name
     *
     * @return static
     */
    public function setTaxonomyName($taxonomy_name)
    {
        $this->taxonomy_name = $taxonomy_name;

        return $this;
    }

    /**
     * @return array
     */
    public function getTaxonomyOpts()
    {
        return $this->taxonomy_opts;
    }

    /**
     * @param array $taxonomy_opts
     *
     * @return static
     */
    public function setTaxonomyOpts($taxonomy_opts)
    {
        $this->taxonomy_opts = $taxonomy_opts;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetaDefinitions()
    {
        return $this->metaDefinitions;
    }

    /**
     * @param array $metaDefinitions
     *
     * @return static
     */
    public function setMetaDefinitions(array $metaDefinitions)
    {
        $this->metaDefinitions = $metaDefinitions;

        return $this;
    }

    public static function getDefaultName()
    {
        return '';
    }

    public static function getDefaultOpts()
    {
        return [
            'public'              => true,
            'hierarchical'        => false,
            'show_in_admin_bar'   => false,
            'exclude_from_search' => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];
    }

    public static function getDefaultTaxonomyName()
    {
        return '';
    }

    public static function getDefaultTaxonomyOpts()
    {
        return [];
    }

    public function register()
    {
        $resCode = 200;
        $resData = [];
        if (!empty($this->getName())) {
            $cptRegistrationRes = $this->registerCustomPostType();
            if ($cptRegistrationRes->getCode() !== 200) {
                $resCode = $cptRegistrationRes->getCode();
            }
            $resData = array_merge($resData, $cptRegistrationRes->getData());
        }
        if (!empty($this->getTaxonomyName())) {
            $taxonomyRegistrationRes = $this->registerCustomPostTypeTaxonomy();
            if ($taxonomyRegistrationRes->getCode() !== 200) {
                $resCode = $taxonomyRegistrationRes->getCode();
            }
            $resData = array_merge($resData, $taxonomyRegistrationRes->getData());
        }

        return new Result($resCode, $resData);
    }

    /**
     * @return Result
     */
    protected function registerCustomPostType()
    {
        $cptOpts = $this->getOpts();
        if (!empty($cptOpts['rewrite']) && !empty($cptOpts['rewrite']['slugs']) && is_array($cptOpts['rewrite']['slugs'])) {
            $slugs = $cptOpts['rewrite']['slugs'];
            unset($cptOpts['rewrite']['slugs']);
            $slugRewriteRules = $this->computeAdditionalSlugsRewriteRules($slugs);
            $this->registerAdditionalRewriteRules($slugRewriteRules, 'top');
        }

        $wpRes = register_post_type($this->getName(), $cptOpts);

        return new Result($wpRes instanceof \WP_Error ? 500 : 200, ['wp_res' => $wpRes]);
    }

    protected function computeAdditionalSlugsRewriteRules($slugs)
    {
        $rules = [];
        if (empty($slugs)) {
            return $rules;
        }
        foreach ($slugs as $slug) {
            $rules[$slug . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?' . $this->name . '=$matches[1]&paged=$matches[2]';
            $rules[$slug . '/([^/]+)(?:/([0-9]+))?/?$']     = 'index.php?' . $this->name . '=$matches[1]&page=$matches[2]';
        }
        return $rules;
    }

    protected function registerAdditionalRewriteRules(array $rewriteRules, $after = 'bottom')
    {
        if (!empty($rewriteRules)) {
            foreach ($rewriteRules as $ruleCondition => $ruleDestination) {
                add_rewrite_rule($ruleCondition, $ruleDestination, $after);
            }
        }
    }

    /**
     * @return Result
     */
    protected function registerCustomPostTypeTaxonomy()
    {
        $wpRes = false;

        if (taxonomy_exists($this->getTaxonomyName())) {
            $result = register_taxonomy_for_object_type($this->getTaxonomyName(), $this->getName());

            if ($result) {
                $wpRes = get_taxonomy($this->getTaxonomyName());
            }
        } else {
            $wpRes = register_taxonomy($this->getTaxonomyName(), [$this->getName()], $this->getTaxonomyOpts());
        }

        return new Result($wpRes instanceof \WP_Error || $wpRes === false ? 500 : 200, ['wp_res' => $wpRes]);
    }

}
