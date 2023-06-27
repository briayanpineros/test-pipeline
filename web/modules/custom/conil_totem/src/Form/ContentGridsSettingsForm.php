<?php

namespace Drupal\conil_totem\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ContentGridsSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'content_grids_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      'content_grids.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_grids.settings');

    // Construcción del formulario.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    $form['url_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL API'),
      '#default_value' => $config->get('url_api'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validación del formulario.
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit del formulario.
    $config = $this->config('content_grids.settings');
    $form_state->cleanValues();
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->set('url_api', $form_state->getValue('url_api'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
