<?php

namespace WonderWp\Component\CPT\Traits;

interface HasLabelsInterface
{
    /**
     * Returns the labels for the post type.
     * @see https://developer.wordpress.org/reference/functions/get_post_type_labels/
     *
     * @return array
     */
    public static function getLabels(): array;

    /**
     * Returns the label for the given key.
     *
     * @param string $key
     *
     * @return string
     */
    public static function getLabel(string $key): string;

    /**
     * Use this method to define the labels for the post type (by filling static::$labels).
     * @return void
     */
    public static function provideLabels(): array;
}
