<?php

namespace Drupal\conil_totem\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Totem entities.
 *
 * @ingroup conil_totem
 */
interface TotemInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Totem name.
   *
   * @return string
   *   Name of the Totem.
   */
  public function getName();

  /**
   * Sets the Totem name.
   *
   * @param string $name
   *   The Totem name.
   *
   * @return \Drupal\conil_totem\Entity\TotemInterface
   *   The called Totem entity.
   */
  public function setName($name);

  /**
   * Gets the Totem creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Totem.
   */
  public function getCreatedTime();

  /**
   * Sets the Totem creation timestamp.
   *
   * @param int $timestamp
   *   The Totem creation timestamp.
   *
   * @return \Drupal\conil_totem\Entity\TotemInterface
   *   The called Totem entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Totem revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Totem revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\conil_totem\Entity\TotemInterface
   *   The called Totem entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Totem revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Totem revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\conil_totem\Entity\TotemInterface
   *   The called Totem entity.
   */
  public function setRevisionUserId($uid);

}
