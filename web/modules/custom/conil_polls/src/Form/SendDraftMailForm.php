<?php

namespace Drupal\conil_polls\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\conil_polls\ConilPollsMailHelperInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SendDraftFormMail.
 */
class SendDraftMailForm extends FormBase {

  /**
   * The mail handler.
   *
   * @var \Drupal\conil_polls\ConilPollsMailHelperInterface
   */
  protected $mailHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\conil_polls\ConilPollsMailHelperInterface $mail_helper
   *   The mail handler.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   */
  public function __construct(ConilPollsMailHelperInterface $mail_helper, EntityTypeManagerInterface $entity_type_manager) {
    $this->mailHelper = $mail_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('conil_polls.mail_helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Nombre del formulario.
    return 'send_draft_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $webform = NULL, $data = NULL) {
    if (empty($data)) {
      $form['no_data'] = [
        '#markup' => $this->t('The data is empty'),
      ];
      return $form;
    }
    $form['#attributes']['autocomplete'] = 'off';
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('The email address to receibe the poll data to continue it later.'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelSubmit'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webformStorage = $this->entityTypeManager->getStorage('webform');
    $buildInfo = $form_state->getBuildInfo();
    $webform = $webformStorage->load($buildInfo['args'][0]);
    $data = $buildInfo['args'][1];
    $url = $webform->toUrl();
    $urlRedirect = clone($url);
    $options = $urlRedirect->getOptions();
    $options['query']['data'] = $data;
    $options['absolute'] = TRUE;
    $urlRedirect->setOptions($options);
    $replacements = [
      '{{ link }}' => $urlRedirect->toString(),
    ];
    $this->mailHelper->send('send_draft_mail', $form_state->getValue('mail'), NULL, [], $replacements);
    $this->messenger()->addMessage($this->t('The poll has been sent to the provided mail.'));
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state) {
    $arguments = $form_state->getBuildInfo();
    $webform = $arguments['webform'];
    $url = $webform->toUrl();
    $form_state->setRedirectUrl($url);
  }

}

