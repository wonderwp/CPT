<?php

namespace WonderWp\Component\CPT\Traits;

interface HasCustomTypeDefinitionsInterface
{
    /**
     * Provide the key of the custom post type
     * @see CustomPostType::setKey()
     * @return string
     */
    public static function provideKey(): string;

    /**
     * Provide the args of the custom post type
     * @see CustomPostType::setArgs()
     * @return array
     */
    public static function provideArgs(): array;
}
