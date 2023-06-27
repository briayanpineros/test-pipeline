<?php

namespace Drupal\conil_inventrip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class Simia SISS Settings form.
 */
class ImportPOISettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_inventrip.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_inventrip_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_inventrip.settings');
    $options = [];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => $config->get('base_url'),
      '#description' => $this->t('The inventrip base url'),
      '#required' => TRUe,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('The inventrip API key'),
      '#required' => TRUe,
    ];

    $form['tourist_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tourist destination'),
      '#default_value' => $config->get('tourist_destination'),
      '#description' => $this->t('The inventrip Tourist destination'),
      '#required' => TRUe,
    ];

    $form['image_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image base url'),
      '#default_value' => $config->get('image_base_url'),
      '#description' => $this->t('The inventrip Image base url'),
      '#required' => TRUe,
    ];

    $form['image_quality'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image quality'),
      '#default_value' => $config->get('image_quality'),
      '#description' => $this->t('The inventrip Image quality'),
      '#required' => TRUe,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_inventrip.settings');
    $form_state->cleanValues();
    $config->setData($form_state->getValues());
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
