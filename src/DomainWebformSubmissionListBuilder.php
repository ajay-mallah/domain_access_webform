<?php

namespace Drupal\domain_access_webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\webform\WebformSubmissionListBuilder;

/**
 * Overrides the defaul WebformSubmissionListBuilder.
 */
class DomainWebformSubmissionListBuilder extends WebformSubmissionListBuilder {

  /**
   * Building row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Defines a common interface for all entity objects.
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    \Drupal::moduleHandler()->alter('webform_build_row', $row, $entity);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRowColumn(array $column, EntityInterface $entity) {
    $output = parent::buildRowColumn($column, $entity);

    $name = $column['name'];

    switch ($name) {
      case 'domain_id':
        return !empty($entity->domain_id->target_id)
          ? $entity->domain_id->entity->label()
          : '';

      default:
        return $output;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery($keys = '', $state = '', $source_entity = '') :QueryInterface {
    $query = parent::getQuery($keys, $state, $source_entity);

    // Add a custom tag so we can limit the results.
    $query->addTag('domain_webform_filter');

    return $query;
  }

}
