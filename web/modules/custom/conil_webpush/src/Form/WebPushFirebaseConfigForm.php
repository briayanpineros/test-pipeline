<?php

namespace Drupal\conil_webpush\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clase WebPushFirebaseConfigForm
 */
class WebPushFirebaseConfigForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conil_webpush_firebase_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_webpush.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_webpush.settings');
    $options = [];

    $form['apiKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('apiKey'),
      '#required' => TRUE,
    ];

    $form['authDomain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth Domain'),
      '#default_value' => $config->get('authDomain'),
      '#required' => TRUE,
    ];

    $form['projectId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#default_value' => $config->get('projectId'),
      '#required' => TRUE,
    ];

    $form['storageBucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Storage Bucket'),
      '#default_value' => $config->get('storageBucket'),
      '#required' => TRUE,
    ];

    $form['messagingSenderId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Messaging Sender ID'),
      '#default_value' => $config->get('messagingSenderId'),
      '#required' => TRUE,
    ];

    $form['appId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#default_value' => $config->get('appId'),
      '#required' => TRUE,
    ];

    $form['measurementId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Measurement ID'),
      '#default_value' => $config->get('measurementId'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_webpush.settings');
    $form_state->cleanValues();
    $config->setData($form_state->getValues());
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
