<?php

namespace Drupal\domain_access_webform\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\Entity\Domain;
use Drupal\views\Plugin\views\filter\StringFilter;
use Psr\Container\ContainerInterface;

/**
 * Filters Webform submission by domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_webform_filter")
 */
class FilterWebformByDomain extends StringFilter {

  use StringTranslationTrait;

  /**
   * The current User account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EntityTypeManager Instance to handle the entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new StringFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Contains the current user account object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Contains the entity type manager instance to handlle entities.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['domain_id'] = ['default' => NULL];
    $options['show_unmapped_webforms'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $options = $this->generateDomainList();
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Domain Ids'),
      '#options' => $options,
      '#description' => $this->t('Timestamp or string containing a relative date.'),
      '#default_value' => $this->options['domain_id'],
    ];

    $form['show_unmapped_webforms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('List the Unmapped domain webform submission?'),
      '#default_value' => $this->options['show_unmapped_webforms'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $options = $this->getAllowedDomains();

    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#title' => $this->t('Domain Ids'),
      '#options' => $options,
      '#default_value' => $this->options['domain_id'],
    ];
  }

  /**
   * Function to generate the options of available domains.
   *
   * @return array
   *   Returns the array of available domains.
   */
  private function generateDomainList() {
    $domains = Domain::loadMultiple();
    $options = [];
    $options['any'] = $this->t('- Any domain -');
    foreach ($domains as $m_name => $domain) {
      $options[$m_name] = $domain->label();
    }

    return $options;
  }

  /**
   * Function to get the user allowed domains.
   *
   * @return array
   *   Returns the domain list as an array.
   */
  private function getAllowedDomains() {
    /** @var \Drupal\user\Entity\UserInterface */
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $allowed_domains = $user->get('field_domain_access')->referencedEntities();

    $options['any'] = $this->t('- Any domain -');
    foreach ($allowed_domains as $domain) {
      $options[$domain->id()] = $domain->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $value = $this->value;

    if ($value == 'any') {
      $domains = $this->getAllowedDomains();
      array_shift($domains);

      $allowed_domains = array_keys($domains);
      $this->query->addWhere($this->options['group'], 'webform_submission.domain_id', $allowed_domains, 'IN');
    }
    else {
      $show_unmapped_webforms = $this->options['show_unmapped_webforms'];
      $condition = $this->query->getConnection()->condition('OR')
        ->condition('webform_submission.domain_id', $value, '=');

      if ($show_unmapped_webforms) {
        $condition->isNull("webform_submission.domain_id");
      }

      $this->query->addWhere($this->options['group'], $condition);
    }
  }

}
