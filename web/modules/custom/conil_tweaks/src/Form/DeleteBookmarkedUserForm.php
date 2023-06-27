<?php

namespace Drupal\conil_tweaks\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Deletes all bookmarked content from the current user.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class DeleteBookmarkedUserForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form_state->setRedirect('/bookmarked');

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'enviar',
      '#value' => $this->t('Delete'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#name' => 'cancel',
      '#value' => $this->t('Cancel'),
    ];

    return $form;
  }

  public function getFormId() {
    return "DeleteBookmarkedUserForm";
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    if ($button['#name'] == 'cancel') {
      $form_state->setRedirect('view.bookmarked_user_content.page_1');
      $form_state->setSubmitted(TRUE);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    if ($button['#name'] != 'cancel') {
      $uid = Drupal::currentUser()->id();
      $database = Drupal::database();
      $query = $database->query("DELETE FROM flagging WHERE uid=$uid");
    }

    $form_state->setRedirect('view.bookmarked_user_content.page_1');
  }

}