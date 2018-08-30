<?php

namespace WonderWp\Component\CPT;

use WonderWp\Component\Service\AbstractService;

class CustomPostTypeService extends AbstractService
{
    /** @var CustomPostType */
    protected $customPostType;

    /**
     * CustomPostTypeService constructor.
     *
     * @param CustomPostType $customPostType
     */
    public function __construct(CustomPostType $customPostType = null)
    {
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

}
