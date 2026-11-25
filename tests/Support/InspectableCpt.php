<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Support;

use WonderWp\Component\CPT\Definition\CustomPostTypeInterface;

final class InspectableCpt implements CustomPostTypeInterface
{
    public function __construct(private string $key, private array $args)
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): static
    {
        $this->args = $args;

        return $this;
    }

    public function getArg(string $key): mixed
    {
        return $this->args[$key] ?? null;
    }

    public function setArg(string $key, mixed $value): static
    {
        $this->args[$key] = $value;

        return $this;
    }

    /** News-like CPT: FR "actualite", EN "news", legacy aliases, archive enabled. */
    public static function news(array $rewriteOverrides = []): self
    {
        return new self('wwp-actu', [
            'public'      => true,
            'has_archive' => true,
            'rewrite'     => array_merge([
                'slug'                       => 'actualite',
                'slugs'                      => ['actualites', 'wwp-actu'],
                'locale_slugs'               => ['fr_FR' => 'actualite', 'en_EN' => 'news'],
                'locale_meta'                => 'news_locale',
                'default_locale'             => 'fr_FR',
                'redirect_to_locale_slug'    => true,
            ], $rewriteOverrides),
        ]);
    }
}
