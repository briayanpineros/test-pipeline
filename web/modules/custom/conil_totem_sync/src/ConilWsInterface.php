<?php

namespace Drupal\conil_totem_sync;

/**
 * Class CurrentScityEntity.
 */
interface ConilWsInterface {

  /**
   * Method to create Json.
   *
   * @return mixed
   *   The result of the call.
   */
  public function createJson();

  /**
   * Method to verified Json last update.
   *
   * @return bool
   *   The result of the call.
   */
  public function verifiedJson(String $json);

  /**
   * Method to call the ws.
   *
   * @return mixed
   *   The result of the call.
   */
  public function post(String $json);

  /**
   * Check if the WS is callable.
   */
  public function check();

  /**
   * Check if the WS is activated througth config.
   */
  public function isActive();

}
