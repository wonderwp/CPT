<?php

namespace WonderWp\Component\CPT\Locale;

/**
 * Slug of a custom post type for each locale, e.g. ['fr_FR' => 'actualite', 'en_EN' => 'news'].
 *
 * Pure value object: it never reads the current locale, so what it returns does not depend on
 * the locale of the process (admin, WP-CLI, front visitor) that happens to use it.
 */
final class LocaleSlugMap
{
    /** @var array<string, string> locale => sanitized slug */
    private array $slugs = [];

    private string $defaultLocale;

    /**
     * @param array<string, mixed> $localeSlugs   locale => slug (empty or non string slugs are dropped)
     * @param string               $defaultLocale locale used as canonical and as fallback
     */
    public function __construct(array $localeSlugs, string $defaultLocale)
    {
        foreach ($localeSlugs as $locale => $slug) {
            if (!is_string($locale) || $locale === '' || !is_string($slug)) {
                continue;
            }

            $slug = sanitize_title($slug);
            if ($slug === '') {
                continue;
            }

            $this->slugs[$locale] = $slug;
        }

        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Builds the map from the `rewrite` args of a post type.
     *
     * @return self|null null when the post type does not declare any usable `locale_slugs`
     */
    public static function fromArgs(array $rewriteArgs, string $defaultLocale): ?self
    {
        if (empty($rewriteArgs['locale_slugs']) || !is_array($rewriteArgs['locale_slugs'])) {
            return null;
        }

        $map = new self($rewriteArgs['locale_slugs'], $defaultLocale);

        return $map->slugs === [] ? null : $map;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /** Canonical slug: the one of the default locale, or the first known slug when it has none. */
    public function getDefaultSlug(): string
    {
        return $this->slugs[$this->defaultLocale] ?? (string) reset($this->slugs);
    }

    /** Slug of a locale, falling back to the default slug (unknown, empty or null locale). */
    public function slugFor(?string $locale): string
    {
        if ($locale !== null && $locale !== '' && isset($this->slugs[$locale])) {
            return $this->slugs[$locale];
        }

        return $this->getDefaultSlug();
    }

    /** @return string[] */
    public function getLocales(): array
    {
        return array_keys($this->slugs);
    }

    /** @return string[] every distinct slug, in declaration order */
    public function getAllSlugs(): array
    {
        return array_values(array_unique($this->slugs));
    }
}
