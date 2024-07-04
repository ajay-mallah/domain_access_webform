# Domain Webform Access

The Domain Webform Access module allows you to restrict access to webforms and
their submissions based on assigned domains. It provides an interfact to map
forms and submissions with the provided domain.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/domain_webform_access).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/domain_webform_access).


## Requirements

This module requires following modules:

- [webform](https://www.drupal.org/project/webform)
- [domain](https://www.drupal.org/project/domain)

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

### Assigning domain to the webform form
- Go to the `/admin/structure/webform/manage/[webform]/settings/form` to assign
domains by selecting `domain access` field.
- Go to the `/admin/domain-access-webform/form` and upload CSV file to map
domains in a bulk.

### Assigning domain to the webform submissions
- Go to the `/admin/domain-access-webform/form` and select webforms to map their
submissions with the selected domain.
- Only one domain can be assigned to submissions.

### Configure webform access permission
- User having permission `bypass domain access webform restrictions` will be
able to access all the webforms wether they are assigned to that domain or not. 


## Maintainers

- Ajay Mallah - [ajay-mallah](https://www.drupal.org/u/ajay-mallah)
- Vishal Prasad - [vishal-prasad](https://www.drupal.org/u/vishal-prasad)
