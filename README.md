# WonderWp Custom Post Type Component

A WonderWp package allowing you to manage Custom Post Types.

## Documentation

- Documentation about the CPT component can be found [here](http://wonderwp.net/Creating_a_plugin/Custom_Posts/index.html).
- Testing strategy: [wonderwp/docs/testing.md](https://github.com/wonderwp/wonderwp/blob/chore/deps-max/docs/testing.md)

## Translatable slugs

A post type can have one slug per language, without the result depending on the locale of the
process that flushes the rewrite rules (admin, WP-CLI, front visitor) nor on the locale of the request.

```php
'rewrite' => [
    'slug'                    => 'actualite',                              // overridden by the default locale slug
    'slugs'                   => ['actualites', 'wwp-actu'],               // legacy aliases, still resolvable
    'locale_slugs'            => ['fr_FR' => 'actualite', 'en_EN' => 'news'],
    'default_locale'          => 'fr_FR',                                  // canonical slug + fallback for posts without language
    'locale_meta'             => 'news_locale',                            // post meta holding the language of a post
    'redirect_to_locale_slug' => true,                                     // 301 to the canonical URL
],
```

- **Resolution:** a rewrite rule is registered for **every** slug of `locale_slugs` plus the `slugs` aliases (including `/page/N/`), so the rule set is identical whoever flushes.
- **`rewrite.slug`:** forced to the slug of the default locale, so WordPress' permastruct is deterministic.
- **Permalinks:** a `post_type_link` filter swaps the canonical slug for the slug of the language of the post (`locale_meta`, fallback: default locale).
- **Redirection (opt-in):** a single requested with a slug that is not the one of its language (legacy alias, other language) gets a `301` to its canonical URL, on `template_redirect` with priority `20` (after the usual priority `10` redirections, which exit first). Skipped for previews and non GET/HEAD requests. The target only depends on the post, so it is page cache friendly.
- **Default locale**, in order: `rewrite.default_locale`, the `wonderwp.customposttype.rewrite.default-locale` filter, then `get_locale()`. Only the last one depends on the request: declare one of the first two.

A `wp rewrite flush` is still needed once to publish the new rules.

## Testing

### Unit (CI)

```bash
composer install
composer test
```

### Integration (WordPress host)

```bash
wp eval-file vendor/wonderwp/customposttype/tests/Integration/smoke-cpt-register.php
ddev wp eval-file vendor/wonderwp/customposttype/tests/Integration/smoke-cpt-register.php
```
