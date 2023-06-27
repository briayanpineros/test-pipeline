<?php

namespace Drupal\conil_totem_sync\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConilWsEntityForm.
 */
class ConilWsEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $conil_ws_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $conil_ws_entity->label(),
      '#description' => $this->t("Label for the Conil WS entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $conil_ws_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\conil_totem_sync\Entity\ConilWsEntity::load',
      ],
      '#disabled' => !$conil_ws_entity->isNew(),
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $conil_ws_entity->getUrl(),
      '#description' => $this->t("The url of the endpoint."),
      '#required' => TRUE,
    ];

    $form['api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#default_value' => $conil_ws_entity->getApi(),
      '#description' => $this->t("The api key of the endpoint."),
      '#required' => TRUE,
    ];

    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#maxlength' => 255,
      '#default_value' => $conil_ws_entity->getUser(),
      '#description' => $this->t("The user of the connection."),
      '#required' => TRUE,
    ];

    $form['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#default_value' => $conil_ws_entity->getPass(),
      '#description' => $this->t("The pass of the connection."),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('url', trim($form_state->getValue('url'), '/'));
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $conil_ws_entity = $this->entity;
    $status = $conil_ws_entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Conil WS entity.', [
          '%label' => $conil_ws_entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Conil WS entity.', [
          '%label' => $conil_ws_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($conil_ws_entity->toUrl('collection'));
  }

}
