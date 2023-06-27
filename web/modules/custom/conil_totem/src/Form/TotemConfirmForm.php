<?php

namespace Drupal\conil_totem\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clase TotemConfirmForm.
 */
class TotemConfirmForm extends ConfirmFormBase {

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
    return 'totem_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to overwrite the totems?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.totem.collection');
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
  public function buildForm(array $form, FormStateInterface $form_state, $clave = NULL, $totems = NULL) {
    $form = parent::buildForm($form, $form_state);

    $node = \Drupal::entityTypeManager()->getStorage('totem')->load($clave);
    $totems = explode('+', $totems);

    // Obtenermos el nombre de los totems para construir la pregunta.
    foreach ($totems as $totem => $value) {
      $n = \Drupal::entityTypeManager()->getStorage('totem')->load($value);
      $selected[] = $n->getName();
    }
    $form['message'] = [
      '#type' => 'item',
      '#markup' => $this->t('Are you sure to overwrite the information of: ' . implode(', ', $selected) . ' with the information of ' . $node->getName() . '?'),
    ];

    $form['clave'] = [
      '#type' => 'value',
      '#value' => $clave,
    ];
    $form['totems'] = [
      '#type' => 'value',
      '#value' => $totems,
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
    // Carga de Totem a copiar.
    $node = \Drupal::entityTypeManager()->getStorage('totem')->load($form_state->getvalue('clave'));
    // Recorrido de totems a los que vamos a copiar información.
    foreach ($form_state->getvalue('totems') as $value) {
      // Carga de totem a sobrescribir.
      $totem = \Drupal::entityTypeManager()->getStorage('totem')->load($value);
      // Obtención de paragraph padre (fechas)
      $padres = $node->field_media_library->referencedEntities();
      foreach ($padres as $padre) {
        // Duplicado de paragraph padre.
        $padreDuplicate = $padre->createDuplicate();
        // Obtención de paragraph hijo (horas).
        $hijos = $padre->field_hours->referencedEntities();
        foreach ($hijos as $hijo) {
          // Duplicado de paragraph hijo.
          $hijoDuplicate = $hijo->createDuplicate();
          $hijoDuplicate->save();
          // Array de paragraph hijos.
          $entity_hijo[] = $hijoDuplicate;
        }
        // Al paragraph padre le insertamos los paragraphs hijos.
        $padreDuplicate->set('field_hours', $entity_hijo);
        $padreDuplicate->save();
        // Array de paragraph hijos.
        $entity_padre[] = $padreDuplicate;
        $entity_hijo = [];
      }
      // Asignamos paragraph padre al totem.
      $totem->set('field_media_library', $entity_padre);
      $totem->save();
      $entity_padre = [];
    }
    \Drupal::messenger()->addMessage($node->getName() . " has been copied to the selected totems.");
    $form_state->setRedirectUrl($this->getCancelUrl());

  }

}
