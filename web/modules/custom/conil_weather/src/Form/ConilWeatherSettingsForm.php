<?php

namespace Drupal\conil_weather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class ConilWeatherSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'conil_weather_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'conil_weather.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_weather.settings');

    $form['api_city_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City ID'),
      '#default_value' => $config->get('api_city_id'),
      '#maxlength' => 10,
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    $form['api_lang'] = [
      '#type' => 'select',
      '#title' => $this->t('API Langcode'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'es' => $this->t('Spanish'),
        'en' => $this->t('English'),
        'it' => $this->t('Italian'),
        'de' => $this->t('German'),
        'fr' => $this->t('French'),
      ],
      '#default_value' => $config->get('api_lang'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validación del formulario.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit del formulario.
    $config = $this->config('conil_weather.settings');
    $form_state->cleanValues();
    $config->set('api_city_id', $form_state->getValue('api_city_id'));
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->set('api_lang', $form_state->getValue('api_lang'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
