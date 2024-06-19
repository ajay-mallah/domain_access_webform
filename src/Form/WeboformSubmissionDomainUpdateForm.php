<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * The form builder class for providing access to users.
 */
class WeboformSubmissionDomainUpdateForm extends WeboformDomainUpdateForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submission_domain_mapping';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['detail'] = [
      '#markup' => $this->t("Update webform submission's domain by selecting webforms."),
    ];

    $form['apply_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply to all'),
      '#default_value' => $form_state->getValue('apply_all') ?? FALSE,
      '#description' => $this->t('Update operation will be applied to all listed webforms.'),
    ];

    $form['webforms'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Webforms'),
      '#options' => $this->getWebformDomainOptions(),
      '#default_value' => $form_state->getValue('webforms') ? array_filter($form_state->getValue('webforms')) : [],
      '#description' => $this->t('Update operation will be performed on selected webforms.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $apply_all = $form_state->getValue('apply_all');
    $webform_ids = $form_state->getValue('webforms') ? array_filter($form_state->getValue('webforms')) : [];

    if ($apply_all) {
      $domain_webforms = $this->getWebformDomainOptions();
      $webform_ids = !empty($domain_webforms) ? array_keys($domain_webforms) : [];
    }
    $operations = [];
    if (!empty($webform_ids)) {
      // Load the object of the file by its fid.
      foreach ($webform_ids as $webform_id) {
        $operations[] = [
          '\Drupal\hcl_domain_webform\Batch\UpdateWebformSubmissionDomain::updateSubmissionDomain',
          [$webform_id],
        ];
      }
      $batch = [
        'title' => $this->t("Updating webform Submission's domain"),
        'operations' => $operations,
        'progress_message' => $this->t('Processed @current out of @total.'),
        'finished' => '\Drupal\hcl_domain_webform\Batch\UpdateWebformSubmissionDomain::batchFinishedCallback',
      ];
      batch_set($batch);
    }
  }

  /**
   * Returns list of webforms having domains.
   */
  protected function getWebformDomainOptions() {
    $options = [];
    /** @var \Drupal\webform\WebformInterface */
    $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    foreach ($webforms as $webform) {
      if ($webform->getThirdPartySetting('hcl_domain_webform', 'domain_id')) {
        $options[$webform->id()] = $webform->label();
      }
    }
    return $options;
  }

}
