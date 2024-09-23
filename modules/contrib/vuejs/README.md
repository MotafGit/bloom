Vue.js Drupal module
====================

DESCRIPTION
-----------

The module provides a bridge between Drupal and Vue.js 3 framework.  
*This module is for use with the Vue 3.x library and above. For Vue 2, please use the 8.x-1.x versions of this module.*

REQUIREMENTS
------------

- Drupal 9+
- PHP 7.4 or higher

CONFIGURATION
-------------

Navigate to the `admin/config/development/vuejs` page and set up desired
versions of the libraries.

LIBRARY INSTALLATION (CDN)
-------------
By default the configuration uses the official VueJS unpkg CDN to load the library. On the configuration form you can set your desired CDN and version for your Vue runtime. Optionall set to "development" mode for an unminified copy of the library.

LIBRARY INSTALLATION (LOCAL)
-------------

Note if you prefer installing libraries locally you can do so with the following methods.

### Local via Composer (Asset Packagist: NPM):
Download libraries by
```
composer require npm-asset/vue:<VERSION>

```
to libraries/vue/dist/*.js.
See https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#third-party-libraries
for details.

### Local via Composer (Asset Packagist: Bower):
Same as the NPM asset instructions above but with `bower-asset/vue` as the package name.

### Manually with an npm or CI script
If you chose not to use composer to manage your libraries you'll be responsible for creating the `/libraries/vue/dist/*.js` files in your Drupal codebase with an npm/yarn script or some other CI script when building your Drupal application.

Using the library
----------

1. You can use inside Twig templates as usual, for example:
```twig
{{ attach_library('vuejs/vue') }}
```
2. You can attach it programmatically:
```php
function MYMODULE_page_attachments(array &$attachments) {
  $attachments['#attached']['library'][] = 'vuejs/vue';
}
```
3. You can add it as a dependency inside your `*.libraries.yml`:
```yaml
  dependencies:
    - vuejs/vue
```

PROJECT PAGE
------------

https://www.drupal.org/project/vuejs
