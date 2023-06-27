<?php

namespace Drupal\conil_totem;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Totem entities.
 *
 * @ingroup conil_totem
 */
class TotemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Totem ID');
    $header['name'] = $this->t('Name');
    $header['indetifier'] = $this->t('Indentifier');
    $header['input_url'] = $this->t('Input Url');
    $header['interactive'] = $this->t('Interactive');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\conil_totem\Entity\Totem $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.totem.edit_form',
      ['totem' => $entity->id()]
    );
    $row['identifier'] = $entity->field_identifier->value;
    $row['input_url'] = $entity->field_input_url->value;
    if ($entity->field_interactive->value == 1) {
      $row['interactive'] = "Yes";
    }
    else {
      $row['interactive'] = "No";
    }
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];

    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl('edit-form'),
      ];
    }

    if ($entity->access('update')) {
      $operations['copy'] = [
        'title' => $this->t('Copy'),
        'weight' => 10,
        'url' => Url::fromRoute('conil_totem.copy_totem', ['clave' => $entity->id()]),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    return $operations;
  }

}
