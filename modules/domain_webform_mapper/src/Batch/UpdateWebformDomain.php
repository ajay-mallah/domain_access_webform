<?php

namespace Drupal\domain_webform_mapper\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Batch process to map webforms with the domains.
 */
class UpdateWebformDomain {

  use StringTranslationTrait;

  /**
   * Updates webform's domain field.
   *
   * @param string $webform_id
   *   Webform ids.
   * @param string $domain_ids
   *   Domain target ids.
   * @param array $context
   *   The batch context.
   */
  public static function updateDomain(string $webform_id, string $domain_ids, array &$context) {
    $domain_webform = \Drupal::service('domain_webform_mapper.mapper');
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
    $translator = \Drupal::translation();
    $messenger = \Drupal::messenger();
    if (!empty($results['success'])) {
      $messenger->addMessage($translator->translate(
        '@count Webforms has been updated.', [
          '@count' => count($results['success']),
        ]
      ));
    }
    elseif (!empty($results['failed'])) {
      $messenger->addError($translator->translate(
        '@count Webforms has been failed.', [
          '@count' => count($results['failed']),
        ]
      ));
    }
    else {
      $messenger->addError($translator->translate('Batch proccess ended with an error'));
    }
  }

}
