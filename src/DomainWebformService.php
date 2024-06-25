<?php

namespace Drupal\hcl_domain_webform;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Entity\WebformSubmission;

/**
 * DomainWebformService class to provides services.
 */
class DomainWebformService {

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
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactory $logger) {
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
      $this->logger->get('domain_access_webform')->error($e->getMessage());
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
      $this->logger->get('domain_access_webform')->error($e->getMessage());
    }
  }

  /**
   * Provides the alowed domain of the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   *
   * @return array
   *   Returns the array of user allowed domains.
   */
  public function getUserAllowedDomains(AccountInterface $account) {
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $domain_access = $user->get('field_domain_access')->getValue();
    $domain_access = array_column($domain_access, 'target_id');
    $domain_admin = $user->get('field_domain_admin')->getValue();
    $domain_admin = array_column($domain_admin, 'target_id');

    $allowed_domains = array_unique(array_merge($domain_access, $domain_admin));

    return $allowed_domains;
  }

  /**
   * Generates the domain dropdown options.
   *
   * @param array $domains
   *   Takes the array of domain objects.
   *
   * @return array
   *   Returns the array of generated options.
   */
  public function generateDomainOptions(array $domains) {
    $options = ['' => $this->t('All domains')];
    foreach ($domains as $domain) {
      $options[$domain->id()] = $domain->label();
    }

    return $options;
  }

}
