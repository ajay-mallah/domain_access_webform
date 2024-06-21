<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure hcl_domain_webform settings for this site.
 */
final class RestrictWebformDomainAccess extends ConfigFormBase {

  /**
   * Contains the entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the required dependency of the form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Contains the entity type manager instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'hcl_domain_webform_restrict_webform_domain_access';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['hcl_domain_webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $available_roles = $this->entityManager->getStorage('user_role')->loadMultiple();
    // Unseting the annonymous and administrator user roles, as these roles do
    // not require permission explicitely to view webforms.
    unset($available_roles['anonymous']);
    unset($available_roles['authenticated']);

    $available_roles = array_keys($available_roles);
    $available_roles = array_combine($available_roles, $available_roles);
    $form['roles_to_restrict'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles to restrict'),
      '#options' => $available_roles,
      '#default_value' => $this->config('hcl_domain_webform.settings')->get('roles_to_restrict'),
      'administrator' => [
        '#disabled' => TRUE,
        '#default_value' => 0,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $selected_roles = array_filter($form_state->getValue('roles_to_restrict'),
    function ($value) {
      return $value;
    });

    $this->config('hcl_domain_webform.settings')
      ->set('roles_to_restrict', $selected_roles)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
