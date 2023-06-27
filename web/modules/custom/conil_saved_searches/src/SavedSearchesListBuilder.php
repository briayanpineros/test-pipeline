<?php

namespace Drupal\conil_saved_searches;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Saved Searches entities.
 *
 * @ingroup conil_saved_searches
 */
class SavedSearchesListBuilder extends EntityListBuilder {

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->condition('search', NULL, 'IS NOT NULL')
      ->condition('search', '', '<>')
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Keywords');
    $header['owner'] = $this->t('Owner');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\conil_saved_searches\Entity\SavedSearches $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->get('search')->value;
    $row['owner'] = !$entity->get('user_id')->isEmpty() ? $entity->get('user_id')->entity->label() : '';
    return $row + parent::buildRow($entity);
  }

}
