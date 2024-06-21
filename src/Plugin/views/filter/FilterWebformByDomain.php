<?php

namespace Drupal\hcl_domain_webform\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\Entity\Domain;
use Drupal\views\Plugin\views\filter\StringFilter;

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
   * @var
   */
  protected $currentUser;

  // public function create

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
  function valueForm(&$form, FormStateInterface $form_state) {
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
  private function generateDomainList()  {
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
    $id = \Drupal::currentUser()->id();

    /** @var \Drupal\user\Entity\User */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($id);
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
        ->condition('webform_submission.domain_id', $value , '=');
  
      if ($show_unmapped_webforms) {
        $condition->isNull("webform_submission.domain_id");
      }
  
      $this->query->addWhere($this->options['group'], $condition);
    }
  }

}
