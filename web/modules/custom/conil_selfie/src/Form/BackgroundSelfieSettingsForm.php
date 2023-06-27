<?php

namespace Drupal\conil_selfie\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Settings form.
 */
class BackgroundSelfieSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_selfie.background',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_import_selfies_background_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_selfie.background');

    $form['url_api'] = [
      '#type' => 'url',
      '#title' => $this->t('URL API'),
      '#default_value' => $config->get('url_api'),
      '#description' => $this->t('The URL API'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#default_value' => $config->get('timeout'),
    ];

    $form['backgrounds'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['background_selfie'],
      '#default_value' => $config->get('backgrounds'),
      '#description' => $this->t('Upload or select your backgrounds images.'),
      '#cardinality' => 9,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_selfie.background');
    $config->set('url_api', $form_state->getValue('url_api'));
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->set('backgrounds', $form_state->getValue('backgrounds'));
    $config->set('timeout', $form_state->getValue('timeout'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
