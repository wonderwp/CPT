<?php

namespace WonderWp\Component\CPT\Definition;

abstract class AbstractCustomPostType implements CustomPostTypeInterface
{
    protected string $key;
    protected array $args = [];

    /**
     * @param string $key
     * @param array $args
     */
    public function __construct(string $key, array $args)
    {
        $this->key = $key;
        $this->args = $args;
    }

    /** @inheritDoc */
    public function getKey(): string
    {
        return $this->key;
    }

    /** @inheritDoc */
    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /** @inheritDoc */
    public function getArgs(): array
    {
        return $this->args;
    }

    /** @inheritDoc */
    public function setArgs(array $args): static
    {
        $this->args = $args;

        return $this;
    }

    /** @inheritDoc */
    public function getArg(string $key): mixed
    {
        return $this->args[$key] ?? null;
    }

    /** @inheritDoc */
    public function setArg(string $key, mixed $value): static
    {
        $this->args[$key] = $value;

        return $this;
    }

}
