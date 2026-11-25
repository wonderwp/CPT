<?php

namespace WonderWp\Component\CPT\Locale;

/**
 * Decides whether a single post URL must be redirected to its canonical slug.
 * Pure: strings in, string out, no WordPress call.
 */
final class LocaleSlugRedirectDecider
{
    /**
     * @param string   $relativePath  Request path relative to the home url ("news/my-news/page/2/")
     * @param string   $canonicalSlug Slug of the language of the post
     * @param string[] $knownSlugs    Slugs (locale slugs + legacy aliases) that resolve this post type
     * @param bool     $isPreview     Previews are never redirected
     *
     * @return string|null Path to redirect to (same shape as $relativePath), null when nothing to do
     */
    public function decide(string $relativePath, string $canonicalSlug, array $knownSlugs, bool $isPreview): ?string
    {
        if ($isPreview) {
            return null;
        }

        $path = ltrim($relativePath, '/');
        if ($path === '') {
            return null;
        }

        $parts   = explode('/', $path, 2);
        $first   = strtolower($parts[0]);
        $known   = array_map('strtolower', $knownSlugs);

        // Already canonical (so the redirect target is stable: no loop) or not one of our prefixes.
        if ($first === strtolower($canonicalSlug) || !in_array($first, $known, true)) {
            return null;
        }

        if (!isset($parts[1]) || $parts[1] === '') {
            return null;
        }

        return $canonicalSlug . '/' . $parts[1];
    }
}
