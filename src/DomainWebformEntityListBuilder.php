<?php

namespace Drupal\domain_access_webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\domain\Entity\Domain;
use Drupal\webform\WebformEntityListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class DomainWebformEntityListBuilder extends WebformEntityListBuilder {

  /**
   * Domain Negotiator.
   *
   * @var \Drupal\domain\DomainNegotiator
   *   Domain negotiator interface.
   */
  protected $domainNegotiator;

  /**
   * Helper Class instance for domain webforms.
   *
   * @var \Drupal\domain_access_webform\DomainWebformService
   */
  protected $domainHelper;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->domainNegotiator = $container->get('domain.negotiator');
    $instance->domainHelper = $container->get('domain_access_webform.domain_webform');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $entity_ids = [];
    $header = $this->buildHeader();
    if ($this->request->query->get('order') === (string) $header['results']['data']) {
      $entity_ids = $this->getQuery($this->keys, $this->category, $this->state)
        ->execute();
      // Make sure all entity ids have totals.
      $this->totalNumberOfResults += array_fill_keys($entity_ids, 0);

      // Calculate totals.
      // @see \Drupal\webform\WebformEntityStorage::getTotalNumberOfResults
      if ($entity_ids) {
        $query = $this->database->select('webform_submission', 'ws');
        $query->fields('ws', ['webform_id']);
        $query->condition('webform_id', $entity_ids, 'IN');
        $query->addExpression('COUNT(sid)', 'results');
        $query->groupBy('webform_id');
        $totals = array_map('intval', $query->execute()->fetchAllKeyed());
        foreach ($totals as $entity_id => $total) {
          $this->totalNumberOfResults[$entity_id] = $total;
        }
      }

      // Sort totals.
      asort($this->totalNumberOfResults, SORT_NUMERIC);
      if ($this->request->query->get('sort') === 'desc') {
        $this->totalNumberOfResults = array_reverse($this->totalNumberOfResults, TRUE);
      }

      // Build an associative array of entity ids from totals.
      $entity_ids = array_keys($this->totalNumberOfResults);
      $entity_ids = array_combine($entity_ids, $entity_ids);
    }
    else {
      $query = $this->getQuery($this->keys, $this->category, $this->state);
      $query->tableSort($header);
      $query->pager(FALSE);
      $entity_ids = $query->execute();

      // Calculate totals.
      // @see \Drupal\webform\WebformEntityStorage::getTotalNumberOfResults
      if ($entity_ids) {
        $query = $this->database->select('webform_submission', 'ws');
        $query->fields('ws', ['webform_id']);
        $query->condition('webform_id', $entity_ids, 'IN');
        $query->addExpression('COUNT(sid)', 'results');
        $query->groupBy('webform_id');
        $this->totalNumberOfResults = array_map('intval', $query->execute()->fetchAllKeyed());
      }

      // Make sure all entity ids have totals.
      $this->totalNumberOfResults += array_fill_keys($entity_ids, 0);
    }
    // Manually initialize and apply paging to the entity ids.
    $page = $this->request->query->get('page') ?: 0;
    $total = count($entity_ids);
    $limit = $this->getLimit();
    $start = ($page * $limit);
    \Drupal::service('pager.manager')->createPager($total, $limit);
    return array_slice($entity_ids, $start, $limit, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery($keys = '', $category = '', $state = ''): QueryInterface {
    $query = parent::getQuery($keys, $category, $state);
    $or_group = $query->orConditionGroup();
    $domain = $this->request->query->get('domain');
    $domain = trim($domain ?? '');

    if (!$this->currentUser->hasPermission('bypass domain access webform restrictions')) {
      $allowed_domains = $this->domainHelper->getUserAllowedDomains($this->currentUser);
      if ($domain) {
        if (in_array($domain, $allowed_domains)) {
          $query->condition('domain_ids', ";$domain;", 'CONTAINS');
        }
        else {
          $query->condition('id', '');
        }
      }
      foreach ($allowed_domains as $domain) {
        $or_group->condition('domain_ids', ";$domain;", 'CONTAINS');
      }
      $query->condition($or_group);
    }
    else {
      if ($domain) {
        if (Domain::load($domain)) {
          $or_group->condition('domain_ids', ";$domain;", 'CONTAINS');
          $query->condition($or_group);
        }
        else {
          $query->condition('id', '');
        }
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $header['domain'] = [
      'data' => $this->t('Domain'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'domain_ids',
      'field' => 'domain_ids',
    ];
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    $domain_ids = $entity->get('domain_ids');

    $domains = [];
    if ($domain_ids) {
      $domain_ids = explode(';', $domain_ids);
      array_shift($domain_ids);
      array_pop($domain_ids);
      $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple($domain_ids);
      foreach ($domains as $key => $domain) {
        $domains[$key] = $domain->label();
      }
    }
    $row['domain']['data']['#markup'] = implode(', ', $domains ?? []);
    $row['domain']['#weight'] = -1;
    return $row;
  }

}
