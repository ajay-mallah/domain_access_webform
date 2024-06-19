<?php

namespace Drupal\hcl_domain_webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorage;

/**
 * Overrides the default WebformSubmissionStorage.
 */
class DomainWebformSubmissionStorage extends WebformSubmissionStorage {

  /**
   * {@inheritdoc}
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $columns = parent::getColumns($webform, $source_entity, $account, $include_elements);

    $columns['domain_id'] = [
      'title' => $this->t('Domain'),
      'name' => 'domain_id',
      'format' => 'value',
    ];

    return $columns;
  }

}
