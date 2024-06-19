<?php

namespace Drupal\hcl_domain_webform\Form;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form to exclude Domains from domain expiration feature.
 *
 * @internal
 */
class AutoDeleteSubmissionsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Country manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Passes cache tag events to classes that wish to respond to them.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs the service objects.
   *
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityManager, CacheTagsInvalidator $cacheTagsInvalidator) {
    $this->entityTypeManager = $entityManager;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_delete_submissions';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hcl_domain_webform.auto_delete_submissions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hcl_domain_webform.auto_delete_submissions.settings');

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Configuration form to manage webform submissions cron job.'),
    ];

    $form['auto_delete_container']['auto_deletion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto-deletion'),
      '#default_value' => $config->get('auto_deletion') ?? FALSE,
      '#description' => $this->t('Enables auto deletion process webform submissions.'),
    ];

    $form['auto_delete_container']['expiry_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Submissions exipiry duration in days'),
      '#min' => 0,
      '#default_value' => $config->get('expiry_duration') ?? 30,
      '#description' => $this->t('Submissions older than expiry duration will get automatically deleted.'),
      '#states' => [
        'visible' => [
          ':input[name="auto_deletion"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="auto_deletion"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $auto_deletion = $form_state->getValue('auto_deletion') ?? FALSE;
    $expiry_duration = $form_state->getValue('expiry_duration') ?? 30;

    $config_setting = $this->config('hcl_domain_webform.auto_delete_submissions.settings');
    $config_setting->set('auto_deletion', $auto_deletion);
    $config_setting->set('expiry_duration', $expiry_duration);
    $config_setting->save();

    $this->cacheTagsInvalidator->invalidateTags(['config:hcl_domain_webform.auto_delete_submissions.settings']);
    parent::submitForm($form, $form_state);
  }

}
