<?php

namespace Drupal\conil_totem_sync\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Conil WS entity entities.
 */
interface ConilWsEntityInterface extends ConfigEntityInterface {

  /**
   * Method to get the url value.
   */
  public function getUrl();

  /**
   * Method to get the api value.
   */
  public function getApi();

  /**
   * Method to get the user value.
   */
  public function getUser();

  /**
   * Method to get the pass value.
   */
  public function getPass();

}
