<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormStateInterface;
use Psr\Container\ContainerInterface;

/**
 * The form builder class for providing access to users.
 */
class WeboformSubmissionDomainUpdateForm extends WeboformDomainUpdateForm {

  /**
   * Stored weboform and their domains.
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
    $form['#attached']['library'][] = 'hcl_domain_webform/hcl_domain_webform.inline_radios';

    $form['detail'] = [
      '#markup' => $this->t("Maps webform's all the submissions with the selected domain"),
    ];

    $header = [
      'webforms' => $this->t('Webforms'),
      'domains' => $this->t('Domains'),
    ];

    // Initialize the table element.
    $form['domain_matrix'] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    foreach ($this->webformList as $key => $webform) {
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
    // dump($form_state->getValue(['domain_matrix', 'contact_us_hcl', 'domain']));
    // dd($form_state->getValue('domain_matrix'));

    // $operations = [];
    // if (!empty($webform_ids)) {
    //   // Load the object of the file by its fid.
    //   foreach ($webform_ids as $webform_id) {
    //     $operations[] = [
    //       '\Drupal\hcl_domain_webform\Batch\UpdateWebformSubmissionDomain::updateSubmissionDomain',
    //       [$webform_id],
    //     ];
    //   }
    //   $batch = [
    //     'title' => $this->t("Updating webform Submission's domain"),
    //     'operations' => $operations,
    //     'progress_message' => $this->t('Processed @current out of @total.'),
    //     'finished' => '\Drupal\hcl_domain_webform\Batch\UpdateWebformSubmissionDomain::batchFinishedCallback',
    //   ];
    //   batch_set($batch);
    // }
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
    $associated_array = [];
    foreach ($domains as $domain) {
      if ($domain !== '') {
        $associated_array[$domain] = $domain_list[$domain];
      }
    }
    return $associated_array;
  }

}
