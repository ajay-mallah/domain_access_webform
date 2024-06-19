# Domain Webform Access

The Domain Webform Access module lets you restrict webform's forms and submissions
access based on assigned domains.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/domain_webform_access).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/domain_webform_access).


## Requirements

This module requires following modules:

[webform](https://www.drupal.org/project/webform).
[domain](https://www.drupal.org/project/domain).

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


### Domain Webform Access permissions

- User having permission `Grant all webform access` will be able to access all
the webforms wether they are assigned to that domain or not. 

### Code linting

We use (and recommend) [PHPCBF](https://phpqa.io/projects/phpcbf.html), [PHP Codesniffer](https://github.com/squizlabs/PHP_CodeSniffer), and [phpstan](https://phpstan.org/) for code quality review.

The following commands are run before commit:

- `vendor/bin/phpcbf web/modules/contrib/domain --standard="Drupal,DrupalPractice" -n --extensions="php,module,inc,install,test,profile,theme"`
- `vendor/bin/phpcs web/modules/contrib/domain --standard="Drupal,DrupalPractice" -n --extensions="php,module,inc,install,test,profile,theme"`
- `vendor/bin/phpstan analyse web/modules/contrib/domain`


## Maintainers

- Ajay Mallah - [ajay-mallah](https://www.drupal.org/u/ajay-mallah)
- Vishal Prasad - [vishal-prasad](https://www.drupal.org/u/vishal-prasad)
