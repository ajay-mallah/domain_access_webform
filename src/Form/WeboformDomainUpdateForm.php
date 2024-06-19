<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\FileInterface;

/**
 * The form builder class for providing access to users.
 */
class WeboformDomainUpdateForm extends FormBase {

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

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['detail'] = [
      '#markup' => $this->t("Update webform's domain by uploading file(csv, xls, xlsx)."),
    ];

    $form['upload_excel_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add users from file'),
      '#size' => 20,
      '#description' => $this->t('The file should contain the webform id in the first column and domain(microsites) in the second column and first row is for the headers. (Only .csv, .xlsx, .xls files are allowed).'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv xls xlsx'],
      ],
      '#upload_location' => 'public://bulk-import/excel_files/',
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];
    $fileUploaded = $form_state->getValue(['upload_excel_file', 0]) ?? NULL;
    if ($fileUploaded) {
      // Load the object of the file by its fid.
      $file = $this->entityTypeManager->getStorage('file')->load($fileUploaded);
      $datas = $this->fetchFileData($file);
      foreach ($datas as $webform_id => $domain_ids) {
        $operations[] = [
          '\Drupal\hcl_domain_webform\Batch\UpdateWebformDomain::updateDomain',
          [$webform_id, $domain_ids],
        ];
      }
      $batch = [
        'title' => $this->t("Updating webform's domain"),
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
      $row_data = [];
      // Set the status flag permanent of the file object.
      $file->setPermanent();
      // Save the file in the database.
      $file->save();
      $inputFileName = $this->fileSystem->realpath($file->getFileUri());
      $spreadsheet = IOFactory::load($inputFileName);
      $sheetData = $spreadsheet->getActiveSheet();
      foreach ($sheetData->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);
        $cells = [];
        foreach ($cellIterator as $cell) {
          $cells[] = $cell->getValue();
        }
        $row_data[] = $cells;
      }
      // Remove 1st line that is header.
      array_shift($row_data);
      // Returning the processed row data.
      return $this->processData($row_data);
    }
  }

  /**
   * Processes webform and related domains.
   *
   * @param array $row_data
   *   Defines getter and setter methods for file entity base fields.
   */
  protected function processData(array $row_data) {
    $data = [];
    foreach ($row_data as $row) {
      $webform_id = $row[0];
      if (!empty($webform_id)) {
        // Removing whitespaces.
        $domain_string = str_replace(' ', '', $row[1]);
        // Converting domain_ids string into array.
        $domains = explode(',', $domain_string);
        $data[$webform_id] = isset($data[$webform_id]) ? array_merge($data[$webform_id], $domains) : $domains;
        $data[$webform_id] = array_unique($data[$webform_id]);
      }
    }
    return $data;
  }

}
