<?php

namespace Drupal\conil_polls\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Settings form.
 */
class DraftMailSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_polls.draft_mail_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_polls_draft_mail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_polls.draft_mail_settings');

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
    ];

    // AES config.
    $form['aes'] = [
      '#type' => 'details',
      '#title' => $this->t('AES Encriptación/Desencriptación'),
      '#group' => 'tabs',
    ];
    $form['aes']['aes_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key (Crypto AES)'),
      '#description' => $this->t('Check if the header must be hide on recharges paths.'),
      '#default_value' => $config->get('aes_key'),
    ];
    $form['aes']['aes_salt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Salt (Crypto AES)'),
      '#description' => $this->t('Check if the header must be hide on recharges paths.'),
      '#default_value' => $config->get('aes_salt'),
    ];
    $form['aes']['aes_vector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vector (Crypto AES)'),
      '#description' => $this->t('Check if the header must be hide on recharges paths.'),
      '#default_value' => $config->get('aes_vector'),
    ];

    $email_token_help = $this->t('You can use tokens in the subject and the body of each email. The custom replacements are only available on the body and them will be specified on each mail body.');
    $form['email'] = [
      '#type' => 'details',
      '#title' => $this->t('Mail'),
      '#description' => $email_token_help,
      '#group' => 'tabs',
    ];

    $form['email']['send_draft_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('send_draft_mail.subject'),
    ];

    $description = $this->t('You must write the token {{ link }} to print in the body the link of the mail.');
    $form['email']['send_draft_mail_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('send_draft_mail.body'),
      '#description' => $description,
    ];

    // Token support.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['email']['tokens'] = [
        '#title' => $this->t('Tokens'),
        '#type' => 'container',
      ];
      $form['email']['tokens']['help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_polls.draft_mail_settings');
    $config->set('aes_key', $form_state->getValue('aes_key'));
    $config->set('aes_salt', $form_state->getValue('aes_salt'));
    $config->set('aes_vector', $form_state->getValue('aes_vector'));
    $config->set('send_draft_mail.subject', $form_state->getValue('send_draft_mail_subject'));
    $config->set('send_draft_mail.body', $form_state->getValue('send_draft_mail_body'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
