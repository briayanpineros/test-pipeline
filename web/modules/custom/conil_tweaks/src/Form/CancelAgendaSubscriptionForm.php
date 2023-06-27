<?php

namespace Drupal\conil_tweaks\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes all agenda category subscriptions from the an user.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class CancelAgendaSubscriptionForm extends FormBase {

  /**
   * The flag object.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flag;

  /**
   *
   */
  public function __construct(FlagServiceInterface $flag_interface) {
    $this->flag = $flag_interface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag')
    );
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
      * @param \Drupal\flag\FlagInterface $flag
      *   The flag object.
     */

    $form_state->setRedirect('/cancel-agenda-subscription?tid={tid}&email={email}&uid={uid}');
    $form['usermail'] = [
      '#type' => 'textfield',
      '#name' => 'email',
      '#title' => $this->t('Please, enter your email adress'),
      '#value' => \Drupal::request()->query->get('email'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'enviar',
      '#value' => $this->t('Send'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#name' => 'cancel',
      '#value' => $this->t('Cancel'),
    ];

    $form['tid'] = [
      '#type' => 'value',
      '#name' => 'tid',
      '#value' => \Drupal::request()->query->get('tid'),
    ];

    return $form;
  }

  /**
   *
   */
  public function getFormId() {
    return "CancelAgendaSubscriptionForm";
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    if (empty($form_state->getValue('usermail'))) {
      $emailErr = "Email is required";
    }
    else {
      // Check if e-mail address is well-formed.
      if (!filter_var($form_state->getValue('usermail'), 274)) {
        $emailErr = "Invalid email format";
      }
    }
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $values = $form_state->cleanValues()->getValues();

    if ($values['name'] != 'cancel') {
      // \Drupal::request()->query->get('tid');
      $tid = $values['tid'];
      $uid = \Drupal::request()->query->get('uid');
      $taxonomy_term_entity = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['tid' => $tid]);
      $user = User::load($uid);
      $flag = $this->flag->getFlagById('agenda_channel_subscriptions');
      $this->flag->unflag($flag, $taxonomy_term_entity[$tid], $user);
    }

    $form_state->setRedirect('view.goodbye_view_for_unsubscription_form.page_1');
  }

}
