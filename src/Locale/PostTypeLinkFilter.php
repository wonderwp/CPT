<?php

namespace WonderWp\Component\CPT\Locale;

/**
 * `post_type_link` callback: puts in a permalink the slug of the language of the post
 * (not the language of the request), so a post always has the same URL.
 */
final class PostTypeLinkFilter
{
    public function __construct(
        private string $postType,
        private LocaleSlugMap $map,
        private string $localeMeta = ''
    ) {
    }

    /**
     * @param string $link Permalink built by WordPress with the canonical (default locale) slug
     * @param mixed  $post The post (WP_Post)
     */
    public function filter($link, $post = null)
    {
        if (!is_string($link) || !is_object($post) || ($post->post_type ?? '') !== $this->postType) {
            return $link;
        }

        $postLocale = $this->localeMeta !== '' ? (string) get_post_meta($post->ID, $this->localeMeta, true) : '';
        $slug       = $this->map->slugFor($postLocale);
        $canonical  = $this->map->getDefaultSlug();

        if ($slug === $canonical) {
            return $link;
        }

        $query = '';
        $queryPos = strpos($link, '?');
        if ($queryPos !== false) {
            $query = substr($link, $queryPos);
            $link  = substr($link, 0, $queryPos);
        }

        // Skip "scheme://host" so a host containing the slug is never rewritten.
        $offset = preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//[^/]+#i', $link, $m) ? strlen($m[0]) : 0;
        $needle = '/' . $canonical . '/';
        $pos    = strpos($link, $needle, $offset);

        if ($pos === false) {
            return $link . $query;
        }

        return substr_replace($link, '/' . $slug . '/', $pos, strlen($needle)) . $query;
    }
}
