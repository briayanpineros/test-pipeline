<?php

namespace Drupal\conil_totem\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Totem edit forms.
 *
 * @ingroup conil_totem
 */
class TotemForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\conil_totem\Entity\Totem $entity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
      $form['actions']['copy'] = [
        '#type' => 'submit',
        '#limit_validation_errors' => [],
        '#value' => $this->t('Copy configuration'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Totem.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Totem.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.totem.canonical', ['totem' => $entity->id()]);
  }

  /**
   * Implements validateForm.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Creación de array con el contenido de los paragraphs.
    foreach ($form_state->getValue('field_media_library') as $fechas) {
      foreach ($fechas['subform']['field_hours'] as $horas) {
        if (!empty($fechas['subform']['field_start_time'][0]['value'])) {
          $intervalo['fecha_inicio'] = $fechas['subform']['field_start_time'][0]['value']->format('d-m-Y');
        }
        if (!empty($fechas['subform']['field_start_time'][0]['value'])) {
          $intervalo['fecha_fin'] = $fechas['subform']['field_end_time'][0]['value']->format('d-m-Y');
        }

        $intervalo['hora_inicio'] = $horas['subform']['field_interval_hours'][0]['from'];
        $intervalo['hora_fin'] = $horas['subform']['field_interval_hours'][0]['to'];

        if (strtotime($intervalo['fecha_fin']) < strtotime($intervalo['fecha_inicio'])) {
          $form_state->setErrorByName('field_media_library', $this->t('The end date cannot be less than the initial date.'));
        }
        if ($intervalo['hora_fin'] < $intervalo['hora_inicio']) {
          $form_state->setErrorByName('field_media_library', $this->t('The end time cannot be less than the start time.'));
        }
        if (($intervalo['hora_inicio'] == $intervalo['hora_fin']) && ($intervalo['hora_inicio'] != NULL && $intervalo['hora_fin'])) {
          $form_state->setErrorByName('field_media_library', $this->t('The start time cannot be the same as the end time.'));
        }
        if ((!empty($intervalo['hora_inicio']) || $intervalo['hora_inicio'] === 0) && (!empty($intervalo['hora_fin']) || $intervalo['hora_fin'] === 0)) {
          $tabla[] = $intervalo;
        }
      }
    }

    // Creación de array con fechas y todas las horas del día.
    // Inicializamos valores de comprobación a cero.
    foreach ($tabla as $fila) {
      for ($dia = $fila['fecha_inicio']; strtotime($dia) <= strtotime($fila['fecha_fin']); $dia = date("d-m-Y", strtotime($dia . "+ 1 days"))) {
        for ($i = 0; $i <= 86340; $i += 60) {
          $tabla_horas[$dia][$i] = 0;
        }
      }
    }

    // Recorrido del array anterior y comprobación de
    // intervalos horarios repetidos.
    foreach ($tabla as $fila) {
      for ($dia = $fila['fecha_inicio']; strtotime($dia) <= strtotime($fila['fecha_fin']); $dia = date("d-m-Y", strtotime($dia . "+ 1 days"))) {
        for ($i = 0; $i <= $fila['hora_fin']; $i += 60) {
          if ((strtotime($dia) >= strtotime($fila['fecha_inicio']) && strtotime($dia) <= strtotime($fila['fecha_fin'])) && ($i >= $fila['hora_inicio'] && $i <= $fila['hora_fin'])) {
            $tabla_horas[$dia][$i]++;
          }
          if ($tabla_horas[$dia][$i] == 2) {
            $form_state->setErrorByName('field_media_library', $this->t('Please check the time slots.'));
            break 3;
          }
        }
      }
    }
  }

  /**
   * Implements submitForm.
   * @param array $form
   * @param FormStateInterface $form_state
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($form_state->getTriggeringElement()['#id'] == 'edit-copy') {
      $form_state->setRedirect('conil_totem.copy_totem', ['clave' => $this->entity->id()]);
    }
  }

}
