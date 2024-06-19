<?php

namespace Drupal\hcl_domain_webform\Batch;

/**
 * Bulk User Manager class.
 */
class UpdateWebformSubmissionDomain extends UpdateWebformDomain {

  /**
   * {@inheritdoc}
   */
  public static function updateSubmissionDomain(string $webform_id, array &$context) {
    $domain_webform = \Drupal::service('hcl_domain_webform.domain_webform');
    $result = $domain_webform->mapSubmissionsDomain($webform_id);
    // Adding success updations count.
    $context['results']['success'] = $context['results']['success'] ?? 0;
    $context['results']['success'] += $result['success'] ?? 0;
    // Adding failed updations count.
    $context['results']['failed'] = $context['results']['failed'] ?? 0;
    $context['results']['failed'] += $result['failed'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function batchFinishedCallback(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if (isset($results['success'])) {
      $messenger->addMessage(t(
        '@count Webforms domain has been updated.', [
          '@count' => $results['success'],
        ]
      ));
    }
    elseif (isset($results['failed'])) {
      $messenger->addError(t(
        '@count Webforms domain has been failed.', [
          '@count' => $results['failed'],
        ]
      ));
    }
    else {
      $messenger->addError(t('Batch proccess ended with an error'));
    }
  }

}
