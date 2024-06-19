<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class DeleteSubmissionsConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hcl_domain_webform_batch_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to do this?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_config');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $timestamp = $form_state->getUserInput()['timestamp'];
    $this->setBatchProcess($timestamp);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Adds webform submission delete process to the batch.
   *
   * @param string $timestamp
   *   Timestamp for fetching webform submissions.
   */
  protected function setBatchProcess(string $timestamp) {
    $webform_submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $query = $webform_submission_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('created', $timestamp, '<');
    $submissions = $query->execute();
    if (!empty($submissions)) {
      $chunks = array_chunk($submissions, 50);
      $operations = [];
      foreach ($chunks as $chunk) {
        $operations[] = ['\Drupal\hcl_domain_webform\Batch\DeleteWebofomSubmission::deleteSubmissions',
          [$chunk],
        ];
      }
      $batch = [
        'title' => $this->t('Deleting Node...'),
        'operations' => $operations,
        'finished' => '\Drupal\hcl_domain_webform\Batch\DeleteWebofomSubmission::finishedCallback',
      ];

      batch_set($batch);
    }
  }

}
