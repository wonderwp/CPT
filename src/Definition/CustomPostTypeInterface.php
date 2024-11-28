<?php

namespace WonderWp\Component\CPT\Definition;

interface CustomPostTypeInterface
{
    /**
     * Get the post type key.
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     * @return string
     */
    public function getKey(): string;

    /**
     * Set the post type key.
     * Must not exceed 20 characters and may only contain lowercase alphanumeric characters, dashes, and underscores.
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     * @see https://developer.wordpress.org/reference/functions/sanitize_key/
     * @param $key
     * @return $this
     */
    public function setKey(string $key): static;

    /**
     * Return the post type args, that will be passed to register_post_type
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     * @return array
     */
    public function getArgs(): array;

    /**
     * Set the post type args that will be passed to register_post_type
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     * @param array $args
     * @return $this
     */
    public function setArgs(array $args): static;

    /**
     * Return a specific arg
     * @param string $key
     * @return mixed
     */
    public function getArg(string $key): mixed;

    /**
     * Set a specific arg
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setArg(string $key, mixed $value): static;
}
