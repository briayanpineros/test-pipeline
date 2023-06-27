<?php

namespace Drupal\web_speech_synthesis_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure web_speech_synthesis_api settings for this site.
 */
class WebSpeechSynthesisSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_speech_synthesis_api_web_speech_synthesis_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['web_speech_synthesis_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['welcome'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message Welcome'),
      '#default_value' => $this->config('web_speech_synthesis_api.settings')->get('welcome'),
    ];

    $form['rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate'),
      '#min' => 0.5,
      '#max' => 2,
      '#step' => 0.5,
      '#default_value' => !empty($this->config('web_speech_synthesis_api.settings')->get('rate')) ? $this->config('web_speech_synthesis_api.settings')->get('rate') : 1,
    ];

    $form['pitch'] = [
      '#type' => 'number',
      '#title' => $this->t('Pitch'),
      '#min' => 0,
      '#max' => 2,
      '#default_value' => !empty($this->config('web_speech_synthesis_api.settings')->get('pitch')) ? $this->config('web_speech_synthesis_api.settings')->get('pitch') : 1,
    ];

    $form['selectorLang'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector of Language.'),
      '#description' => $this->t('It must be a selector of an html select element in order to read the language value. Ex. #selectid .selectclass'),
      '#required' => TRUE,
      '#default_value' => $this->config('web_speech_synthesis_api.settings')->get('selectorLang'),
    ];

    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name class to read.'),
      '#description' => $this->t('Class from where you have to read the locutions.'),
      '#required' => TRUE,
      '#default_value' => $this->config('web_speech_synthesis_api.settings')->get('class'),
    ];

    $form['seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Seconds'),
      '#description' => $this->t('Seconds from the end of the locution and the counter is reset to start from the first locution.'),
      '#min' => 5,
      '#max' => 60,
      '#step' => 1,
      '#default_value' => !empty($this->config('web_speech_synthesis_api.settings')->get('seconds')) ? $this->config('web_speech_synthesis_api.settings')->get('seconds') : 5,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*if ($form_state->getValue('example') != 'example') {
      $form_state->setErrorByName('example', $this->t('The value is not correct.'));
    }*/
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('web_speech_synthesis_api.settings');
    $form_state->cleanValues();
    $config->setData($form_state->getValues());
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
