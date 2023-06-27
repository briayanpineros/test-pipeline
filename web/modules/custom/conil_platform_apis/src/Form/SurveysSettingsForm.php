<?php

namespace Drupal\conil_platform_apis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class Simia SISS Settings form.
 */
class SurveysSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_platform_apis.surveys_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_surveys_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_platform_apis.surveys_settings');
    $options = [];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => $config->get('base_url'),
      '#description' => $this->t('Sensors base url'),
      '#required' => true,
    ];

    $form['key_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First key'),
      '#default_value' => $config->get('key_1'),
      '#description' => $this->t('First key'),
      '#required' => true,
    ];

    $form['value_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First value'),
      '#default_value' => $config->get('value_1'),
      '#description' => $this->t('First value'),
      '#required' => true,
    ];

    $form['key_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second key'),
      '#default_value' => $config->get('key_2'),
      '#description' => $this->t('Second key'),
      '#required' => true,
    ];

    $form['value_2_conteo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second value conteo'),
      '#default_value' => $config->get('value_2_conteo'),
      '#description' => $this->t('Second value conteo'),
      '#required' => true,
    ];

    $form['value_2_meteo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second value meteo'),
      '#default_value' => $config->get('value_2_meteo'),
      '#description' => $this->t('Second value meteo'),
      '#required' => true,
    ];

    $form['value_2_airquality'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second value air quality'),
      '#default_value' => $config->get('value_2_airquality'),
      '#description' => $this->t('Second value air quality'),
      '#required' => true,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_platform_apis.surveys_settings');
    $form_state->cleanValues();
    $config->setData($form_state->getValues());
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
