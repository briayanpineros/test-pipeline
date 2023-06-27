<?php

namespace Drupal\conil_totem\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clase TotemCopyForm.
 */
class TotemCopyForm extends FormBase {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'totem_copy_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * Defines form for copy totems.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $clave
   *   The "clave" of the entity.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $clave = NULL) {

    $form['clave'] = [
      '#type' => 'value',
      '#value' => $clave,
    ];
    $header = [
      'id' => $this->t('Id'),
      'name' => $this->t('Name'),
      'identifier' => $this->t('Identifier'),
    ];
    $node = \Drupal::entityTypeManager()->getStorage('totem')->loadMultiple();

    foreach ($node as $totem) {
      if ($totem->id() != $clave) {
        $options[$totem->id()] = [
          'id' => $totem->id(),
          'name' => $totem->getName(),
          'identifier' => $totem->field_identifier->value,
        ];
      }
    }

    $form['totems'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No totems found.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Copy'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
    ];

    return $form;
  }

  /**
   * Implements submitForm.
   * @param array $form
   * @param FormStateInterface $form_state
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#id'] == 'edit-cancel') {
      $form_state->setRedirect('entity.totem.collection');
    }
    else {
      // Recorrido de la tabla para obtener los totems seleccionados.
      foreach ($form_state->getValue('totems') as $totem => $value) {
        if ($value != 0) {
          $totems[] = $totem;
        }
      }

      $totems = implode('+', $totems);
      $form_state->setRedirect('conil_totem.confirm_copy', ['clave' => $form_state->getValue('clave'), 'totems' => $totems]);
    }
  }

}
