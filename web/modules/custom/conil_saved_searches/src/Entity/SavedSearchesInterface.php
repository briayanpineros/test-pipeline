<?php

namespace Drupal\conil_saved_searches\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Saved Searches entities.
 *
 * @ingroup conil_saved_searches
 */
interface SavedSearchesInterface extends ContentEntityInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Saved Searches creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Saved Searches.
   */
  public function getCreatedTime();

  /**
   * Sets the Saved Searches creation timestamp.
   *
   * @param int $timestamp
   *   The Saved Searches creation timestamp.
   *
   * @return \Drupal\conil_saved_searches\Entity\SavedSearchesInterface
   *   The called Saved Searches entity.
   */
  public function setCreatedTime($timestamp);

}
