<?php

namespace WonderWp\Component\CPT\Exception;

use WonderWp\Component\Response\Traits\HasWpError;

class CustomPostTypeRegistrationException extends \Exception
{
    use HasWpError;
}
