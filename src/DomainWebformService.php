<?php

namespace Drupal\hcl_domain_webform;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Clones nodes from entityQueues.
 */
class DomainWebformService {

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
  public function __construct(EntityTypeManager $entityTypeManager,
  LoggerChannelFactory $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Assigns Domain id to the webform.
   *
   * @param string $webform_id
   *   Webform id.
   * @param array $domain_ids
   *   Array containing the domain ids.
   */
  public function mapDomain(string $webform_id, array $domain_ids) {
    try {
      /** @var \Drupal\webform\WebformInterface */
      $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
      if ($webform) {
        $webform->setThirdPartySetting('hcl_domain_webform', 'domain_id', $domain_ids);
        $webform->save();
      }
      return $webform;
    }
    catch (\Exception $e) {
      $this->logger->get('hcl_domain_webform')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Assigns Domain id to the webform submissions.
   *
   * @param string $webform_id
   *   Webform id.
   */
  public function mapSubmissionsDomain(string $webform_id) {
    try {
      if ($webform_domain_ids = $this->getWebformDomainIds($webform_id)) {
        // Fetching all the submissions.
        $submissions = $this->entityTypeManager->getStorage('webform_submission')
          ->loadByProperties(['webform_id' => $webform_id]);
        // Result array.
        $result = [
          'success' => 0,
          'failed' => 0,
        ];
        foreach ($submissions as $submission) {
          if ($this->updateWebformSubmission($submission, $webform_domain_ids)) {
            $result['success']++;
          }
          else {
            $result['failed']++;
          }
        }
        return $result;
      }
    }
    catch (\Exception $e) {
      $this->logger->get('hcl_domain_webform')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Returns webform's domain_ids.
   *
   * @param string $webform_id
   *   Webform id.
   */
  protected function getWebformDomainIds(string $webform_id) {
    /** @var \Drupal\webform\WebformInterface */
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
    if ($webform) {
      return $webform->getThirdPartySetting('hcl_domain_webform', 'domain_id');
    }
    return NULL;
  }

  /**
   * Updates Webform submission's domain.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   Provides an interface defining a webform submission entity.
   * @param array $domain_ids
   *   Array containing the domain ids.
   */
  protected function updateWebformSubmission(WebformSubmissionInterface $submission, array $domain_ids) {
    try {
      $domain_id = NULL;
      if (count($domain_ids) == 1) {
        $domain_id = reset($domain_ids);
      }
      elseif (count($domain_ids) > 1) {
        $domain_paths = $this->fetchDomainPath($domain_ids);
        $uri = $submission->get('uri')->value;
        $domain_path = explode('/', $uri);
        $domain_path = $domain_path[1];
        $domain_id = $domain_paths[$domain_path] ?? NULL;
      }
      $submission->set('domain_id', $domain_id);
      $submission->save();
      return $submission;
    }
    catch (\Exception $e) {
      $this->logger->get('hcl_domain_webform')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Fetches domain path.
   *
   * @param array $domain_ids
   *   Array containing the domain ids.
   */
  protected function fetchDomainPath(array $domain_ids) {
    $domain_paths = [];
    /** @var array /Drupal/domain/DomainInterface */
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple($domain_ids);
    foreach ($domains as $domain) {
      if ($domain_uri = $domain->getThirdPartySetting('country_path', 'domain_path')) {
        $domain_paths[$domain_uri] = $domain->id();
      }
    }
    return $domain_paths;
  }

}
