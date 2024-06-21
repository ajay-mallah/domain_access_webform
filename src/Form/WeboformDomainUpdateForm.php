<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form builder class for updating mapping domains with the webforms.
 */
class WeboformDomainUpdateForm extends FormBase {

  /**
   * Proccessed CSV data.
   *
   * @var array
   */
  protected $csvData;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs the service objects.
   *
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FileSystem $fileSystem) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
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
    return 'webform_domain_mapping';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['detail'] = [
      '#markup' => $this->t("Upload CSV file to map webforms with the domain."),
    ];

    $form['upload_excel_file'] = [
      '#type' => 'managed_file',
      '#element_validate' => ['::validateCsv'],
      '#title' => $this->t('Upload user data in CSV'),
      '#description' => "
        <p>First row of the csv file will be header [webform_id, domain_id]</p>
        <p>Only signle webform_id is allowed in a row</p>
        <p>Multiple domain_id is allowed followed seperated by | . E.g. site_1|site_2</p>
      ",
      '#upload_location' => 'public://bulk-import/excel_files/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Validates for CSV file.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Provides an interface for an object containing the current form's state.
   */
  public function validateCsv(array &$form, FormStateInterface $form_state) {
    $fileUploaded = $form_state->getValue(['upload_excel_file', 'fids', 0]) ?? NULL;
    if ($fileUploaded) {
      $file = $this->entityTypeManager->getStorage('file')->load($fileUploaded);
      $this->csvData = $this->fetchFileData($file);
      if ($this->csvData === NULL) {
        $form_state->setErrorByName('upload_excel_file', $this->t('CSV file contains name email mapping in invalid formate.'));
      }
      $file->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];
    if ($this->csvData) {
      foreach ($this->csvData as $webform_id => $domain_ids) {
        $operations[] = [
          '\Drupal\hcl_domain_webform\Batch\UpdateWebformDomain::updateDomain',
          [$webform_id, $domain_ids],
        ];
      }
      $batch = [
        'title' => $this->t("Processing webforms..."),
        'operations' => $operations,
        'progress_message' => $this->t('Processed @current out of @total.'),
        'finished' => '\Drupal\hcl_domain_webform\Batch\UpdateWebformDomain::batchFinishedCallback',
      ];
      batch_set($batch);
    }
  }

  /**
   * Processes webform and related domains.
   *
   * @param \Drupal\file\FileInterface $file
   *   Defines getter and setter methods for file entity base fields.
   */
  protected function fetchFileData(FileInterface $file) {
    if ($file) {
      $spreadsheet = fopen($file->getFileUri(), 'r');
      $rows = [];
      while (!feof($spreadsheet)) {
        $row = fgetcsv($spreadsheet, NULL, ",");
        if ($row) {
          array_push($rows, $row);
        }
      }
      fclose($spreadsheet);
      if ($rows) {
        // Remove 1st line that is header.
        array_shift($rows);
        return $this->processData($rows);
      }
      return NULL;
    }
  }

  /**
   * Processes CSV data into a string format.
   *
   * @param array $row_data
   *   Row data of the csv file.
   */
  protected function processData(array $row_data) {
    $data = [];
    foreach ($row_data as $row) {
      $webform_id = $row[0];
      if (!empty($webform_id)) {
        $domain_string = str_replace('|', ';', $row[1]);
        $domain_string = ';' . $domain_string . ';';
        $data[$webform_id] = isset($data[$webform_id]) ? $data[$webform_id] . ';' . $domain_string : $domain_string;
      }
    }
    return $data;
  }

}
