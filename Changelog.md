# Changelog

## Unreleased

- Translatable slugs: `rewrite.locale_slugs`, `rewrite.default_locale`, `rewrite.locale_meta` and `rewrite.redirect_to_locale_slug`. Rewrite rules cover every language slug, the permastruct slug is the default locale one, permalinks follow the language of the post (`post_type_link`) and other slugs can `301` to the canonical URL.

## 1.0.9

- Better meta box form overridability by splitting the `displayCptsMetaBox` into several smaller ones.
