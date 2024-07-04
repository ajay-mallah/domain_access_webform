<?php

namespace Drupal\domain_access_webform;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   * Defines an account interface which represents the current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Sets class variables.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Manages entity type plugin definitions.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Describes a logger instance.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Defines an account interface which represents the current user.
   */
  public function __construct(
    EntityTypeManager $entityTypeManager,
    LoggerChannelFactory $logger,
    AccountInterface $currentUser,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->currentUser = $currentUser;
  }

  /**
   * Provides the allowed domain of the user.
   *
   * @return array
   *   Returns the array of user allowed domains.
   */
  public function getUserAllowedDomains() {
    /** @var /Drupal\user\UserInterface */
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
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

  /**
   * Returns allowed domains options.
   *
   * @return array
   *   Returns list of domain options.
   */
  public function getDomainOptions() {
    if ($this->currentUser->hasPermission('bypass domain access webform restrictions')) {
      $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
      return $this->generateDomainOptions($domains);
    }
    else {
      $allowed_domains = $this->getUserAllowedDomains();
      $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple($allowed_domains);
      return $this->generateDomainOptions($domains);
    }
  }

}
