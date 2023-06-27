<?php

namespace Drupal\conil_saved_searches\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Saved Searches entities.
 */
class SavedSearchesViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
