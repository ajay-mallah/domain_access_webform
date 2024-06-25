<?php

namespace Drupal\domain_access_webform\Batch;

/**
 * Batch process to submissions with the selected domain in bulk.
 */
class UpdateWebformSubmissionDomain extends UpdateWebformDomain {

  /**
   * Maps submissions.
   *
   * @param array $chunks
   *   List of submissions.
   * @param string $domain_id
   *   Domain target ids.
   * @param array $context
   *   The batch context.
   */
  public static function updateSubmissionDomain(array $chunks, string $domain_id, array &$context) {
    $domain_webform = \Drupal::service('domain_access_webform.domain_webform');
    $domain_webform->mapSubmissionsDomain($chunks, $domain_id);
    $context['results']['success'] = isset($context['results']['success']) ?
    $context['results']['success'] + count($chunks) : count($chunks);
  }

  /**
   * {@inheritdoc}
   */
  public static function batchFinishedCallback(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    $translator = \Drupal::translation();
    if (isset($results['success'])) {
      $messenger->addMessage($translator->translate(
        '@count Submissions has been updated.', [
          '@count' => $results['success'],
        ]
      ));
    }
    else {
      $messenger->addError($translator->translate('Batch proccess ended with an error'));
    }
  }

}
