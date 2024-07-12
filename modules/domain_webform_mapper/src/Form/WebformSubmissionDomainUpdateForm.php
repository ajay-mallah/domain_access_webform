<?php

namespace Drupal\domain_webform_mapper\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormStateInterface;
use Psr\Container\ContainerInterface;

/**
 * The form builder class for providing access to users.
 */
class WebformSubmissionDomainUpdateForm extends WebformDomainUpdateForm {

  /**
   * Stores webform and associated domains.
   *
   * @var array
   */
  private $webformList = [];

  /**
   * Constructs the service objects.
   *
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FileSystem $fileSystem) {
    parent::__construct($entityTypeManager, $fileSystem);
    $this->webformList = $this->getWebformList();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
    );
  }

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
    $form['#attached']['library'][] = 'domain_webform_mapper/domain_webform_mapper.inline_radios';

    $form['detail'] = [
      '#markup' => $this->t("Maps webform's all the submissions with the selected domain"),
    ];

    $header = [
      'enabled' => $this->t('Enable'),
      'webforms' => $this->t('Webforms'),
      'domains' => $this->t('Domains'),
    ];

    // Initialize the table element.
    $form['domain_matrix'] = [
      '#type' => 'table',
      '#header' => $header,
    ];
    // Adding the table form to select webform and it's assigned domain.
    foreach ($this->webformList as $key => $webform) {
      $form['domain_matrix'][$key]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      ];
      $form['domain_matrix'][$key]['label'] = [
        '#markup' => $this->t('@label', [
          '@label' => $webform['label'],
        ]),
      ];
      $form['domain_matrix'][$key]['domain'] = [
        '#type' => 'radios',
        '#options' => $webform['domains'],
        '#default_value' => array_key_first($webform['domains']),
      ];
    }

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
    $domain_matrix = $form_state->getValue('domain_matrix');
    $mapped_webform = [];
    foreach ($domain_matrix as $key => $row) {
      if ($row['enabled']) {
        $mapped_webform[$key] = $row['domain'];
      }
    }

    // Adding webforms and their associated domains for the batch process.
    if (!empty($mapped_webform)) {
      $batch_data = [];
      foreach ($mapped_webform as $key => $domain_id) {
        $query = $this->entityTypeManager->getStorage('webform_submission')
          ->getQuery()
          ->accessCheck(TRUE);
        $query->condition('webform_id', $key);
        $sids = $query->execute();
        if (!empty($sids)) {
          $chunks = array_chunk($sids, 100);
          $batch_data[$key]['chunks'] = $chunks;
          $batch_data[$key]['domain_id'] = $domain_id;
        }
      }
      if (!empty($batch_data)) {
        $this->setBatchProcess($batch_data);
      }
    }
  }

  /**
   * Returns list of webforms having domains.
   */
  protected function getWebformList() {
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
    $domain_list = [];
    foreach ($domains as $domain) {
      $domain_list[$domain->id()] = $domain->label();
    }
    $webform_list = [];
    /** @var \Drupal\webform\WebformInterface */
    $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    foreach ($webforms as $webform) {
      if ($domain_ids = $webform->get('domain_ids')) {
        $webform_list[$webform->id()] = [
          'label' => $webform->label(),
          'domains' => $this->getWebformDomains($domain_ids, $domain_list),
        ];
      }
    }
    return $webform_list;
  }

  /**
   * Returns the list of assigned domains id.
   *
   * @param string $domain_ids
   *   Webform's id.
   * @param array $domain_list
   *   List of domains.
   *
   * @return array
   *   Returns the list of the assigned domains.
   */
  protected function getWebformDomains(string $domain_ids, array $domain_list) {
    $domains = explode(';', $domain_ids);
    $mapped_webforms = [];
    foreach ($domains as $domain) {
      if ($domain !== '') {
        $mapped_webforms[$domain] = $domain_list[$domain];
      }
    }
    return $mapped_webforms;
  }

  /**
   * Sets Batch process to map webform submissions.
   *
   * @param array $batch_data
   *   Contains submissions chunks and it's domain.
   */
  protected function setBatchProcess(array $batch_data) {
    $operations = [];
    foreach ($batch_data as $data) {
      foreach ($data['chunks'] as $chunk) {
        $operations[] = [
          '\Drupal\domain_webform_mapper\Batch\UpdateWebformSubmissionDomain::updateSubmissionDomain',
          [$chunk, $data['domain_id']],
        ];
      }
    }
    $batch = [
      'title' => $this->t("Processsing webform submissions..."),
      'operations' => $operations,
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => '\Drupal\domain_webform_mapper\Batch\UpdateWebformSubmissionDomain::batchFinishedCallback',
    ];

    batch_set($batch);
  }

}
