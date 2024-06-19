<?php

namespace Drupal\hcl_domain_webform\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Bulk User Manager class.
 */
class UpdateWebformDomain {

  use StringTranslationTrait;

  /**
   * Updates webform's domain field.
   *
   * @param string $webform_id
   *   Webform ids.
   * @param array $domain_ids
   *   Domain target ids.
   * @param array $context
   *   The batch context.
   */
  public static function updateDomain(string $webform_id, array $domain_ids, array &$context) {
    $domain_webform = \Drupal::service('hcl_domain_webform.domain_webform');
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);
    if ($webform) {
      if ($domain_webform->mapDomain($webform_id, $domain_ids)) {
        $context['results']['success'][] = $webform->label();
      }
      else {
        $context['results']['failed'][] = $webform->label();
      }
    }
  }

  /**
   * Handle batch completion.
   *
   * @param bool $success
   *   TRUE if all batch tasks were completed successfully.
   * @param array $results
   *   An array of emails.
   * @param array $operations
   *   A list of the operations that had not been completed.
   */
  public static function batchFinishedCallback(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if (!empty($results['success'])) {
      $messenger->addMessage(t(
        '@count Webforms domain has been updated.', [
          '@count' => count($results['success']),
        ]
      ));
    }
    elseif (!empty($results['failed'])) {
      $messenger->addError(t(
        '@count Webforms domain has been failed.', [
          '@count' => count($results['failed']),
        ]
      ));
    }
    else {
      $messenger->addError(t('Batch proccess ended with an error'));
    }
  }

}
