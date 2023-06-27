<?php

namespace Drupal\conil_totem\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TotemsSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'totems_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      'totems.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('totems.settings');

    // Construcción del formulario.
    $form['nextMedia'] = [
      '#type' => 'number',
      '#title' => $this->t('Time between content'),
      '#default_value' => $config->get('nextMedia'),
      '#description' => $this->t("Time in seconds"),
    ];
    $form['waitTime'] = [
      '#type' => 'number',
      '#title' => $this->t('Waiting time in totem'),
      '#default_value' => empty($config->get('waitTime')) ? 0 : $config->get('waitTime'),
      '#description' => $this->t("Time in seconds"),
    ];
    $form['media_content'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['content_grids_images', 'content_grids_videos'],
      '#title' => $this->t('Content to display on grids'),
      '#description' => $this->t('Upload or select images and video content.'),
      '#default_value' => $config->get('media_content'),
      '#cardinality' => -1,
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validación del formulario.
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit del formulario.
    $config = $this->config('totems.settings');
    $form_state->cleanValues();
    $config->set('nextMedia', $form_state->getValue('nextMedia'));
    $config->set('waitTime', $form_state->getValue('waitTime'));
    $config->set('media_content', $form_state->getValue('media_content'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
