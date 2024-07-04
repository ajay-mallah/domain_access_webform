<?php

namespace Drupal\domain_webform_mapper;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Service class to map webforms with the domain.
 */
class DomainWebformMapper {

  use StringTranslationTrait;

  /**
   * Manages entity type plugin definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Sets class variables.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Manages entity type plugin definitions.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Describes a logger instance.
   */
  public function __construct(
    EntityTypeManager $entityTypeManager,
    LoggerChannelFactory $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Assigns Domain id to the webform.
   *
   * @param string $webform_id
   *   Webform id.
   * @param string $domain_ids
   *   Array containing the domain ids.
   *
   * @return \Drupal\webform\WebformInterface
   *   Returns webform object if domain is mapped successfully.
   */
  public function mapDomain(string $webform_id, string $domain_ids) {
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
    try {
      /** @var \Drupal\webform\WebformInterface */
      $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
      if ($webform) {
        $webform->set('domain_ids', $domain_ids);
        $webform->save();
      }
      return $webform;
    }
    catch (\Exception $e) {
      $this->logger->get('domain_webform_mapper')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Assigns Domain id to the webform submissions.
   *
   * @param array $chunks
   *   Contains chunks of webform submissions.
   * @param string $domain_id
   *   Target domain id.
   *
   * @return void
   *   Returns nothing.
   */
  public function mapSubmissionsDomain(array $chunks, $domain_id) {
    try {
      foreach ($chunks as $submission_id) {
        $submission = $this->entityTypeManager->getStorage('webform_submission')
          ->load($submission_id);
        if ($submission instanceof WebformSubmission) {
          $submission->set('domain_id', $domain_id);
          $submission->save();
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->get('domain_webform_mapper')->error($e->getMessage());
    }
  }

}
