<?php

namespace Drupal\hcl_domain_webform\Batch;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Handles batch process callback for webform submission deletion.
 */
class DeleteWebofomSubmission {

  /**
   * Operation callback function to delete webform submissions.
   *
   * @param array $submission_ids
   *   Array containing the submission id.
   * @param mixed $context
   *   Determine the context of batch process.
   */
  public static function deleteSubmissions(array $submission_ids, &$context) {
    $count = 0;
    foreach ($submission_ids as $submission_id) {
      $submission = WebformSubmission::load($submission_id);
      $submission->delete();
      $count++;
    }
    $context['message'] = 'Deleting Webform Submissions...';
    $context['results'] = $context['results'] ? $context['results'] + $count : $count;
  }

  /**
   * Operation callback function to delete webform submissions.
   *
   * @param mixed $success
   *   Array status of the batch process.
   * @param mixed $results
   *   Determine the results of batch process.
   * @param mixed $operations
   *   Determine the operations of batch process.
   */
  public static function finishedCallback($success, $results, $operations) {
    if ($success) {
      $message = t('@count Webform submissions deleted',
      ['@count' => $results]
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}
