<?php

namespace WonderWp\Component\CPT\Response;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Response\AbstractResponse;
use \WP_Post_Type;

class CustomPostTypeRegistrationResponse extends AbstractResponse implements CustomPostTypeRegistrationResponseInterface
{
    protected ?WP_Post_Type $wpRegistrationResult = null;

    public function getWpRegistrationResult(): ?WP_Post_Type
    {
        return $this->wpRegistrationResult;
    }

    public function setWpRegistrationResult(?WP_Post_Type $wpRegistrationResult): static
    {
        $this->wpRegistrationResult = $wpRegistrationResult;

        return $this;
    }
}
