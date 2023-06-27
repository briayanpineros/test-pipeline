<?php

namespace Drupal\conil_totem_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class Conil Totem Sync Settings form.
 */
class ConilTotemSyncSettings extends ConfigFormBase {

  /**
   * A instance of entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_totem_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_totem_sync_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_totem_sync.settings');
    $options = [];

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Service activated'),
      '#default_value' => $config->get('active'),
    ];

    $environments = $this->entityTypeManager->getStorage('conil_ws_entity')->loadMultiple();
    foreach ($environments as $environment) {
      $options[$environment->id()] = $environment->label();
    }
    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the environment'),
      '#default_value' => $config->get('environment'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_totem_sync.settings');
    $form_state->cleanValues();
    $config->setData($form_state->getValues());
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
