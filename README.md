# WonderWp Custom Post Type Component

A WonderWp package allowing you to manage Custom Post Types.

## Documentation

- Documentation about the CPT component can be found [here](http://wonderwp.net/Creating_a_plugin/Custom_Posts/index.html).
- Testing strategy: [wonderwp/docs/testing.md](https://github.com/wonderwp/wonderwp/blob/chore/deps-max/docs/testing.md)

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
