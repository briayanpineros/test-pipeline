<?php

namespace Drupal\conil_weather;

/**
 * Interface ConilWeather.
 */
interface ConilWeatherInterface {

  /**
   * Method to call the ws.
   *
   * @return mixed
   *   The result of the call.
   */
  public function getWeather();

  /**
   * Method to create weather pdf.
   *
   * @return mixed
   *   The result of the call.
   */
  public function printPdfWeather();

}
