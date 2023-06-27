<?php

namespace Drupal\conil_platform_apis\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use PhpParser\Node\NullableType;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\NotNull;
use GuzzleHttp\Client;

/**
 * Provides REST API for Sensors information.
 *
 * @RestResource(
 *   id = "conil_platform_apis",
 *   label = @Translation("Sensors information API"),
 *   uri_paths = {
 *     "canonical" = "/api/sensors_information"
 *   }
 * )
 */

class SensorsInfoService extends ResourceBase
{
  /**
   * The configuration of the module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A curent user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * A instance of entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database variable.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    Request $request,
    ConfigFactoryInterface $config_factory,
    Connection $database
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->request = $request;
    $this->configFactory = $config_factory;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory'),
      $container->get('database')
    );
  }


  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing a list of bundle names.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get()
  {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    //Get config
    $this->config = $this->configFactory->get('conil_platform_apis.sensors_settings');
    $base_url = $this->config->get('base_url');
    $key_1 = $this->config->get('key_1');
    $value_1 = $this->config->get('value_1');
    $key_2 = $this->config->get('key_2');
    $value_2_conteo = $this->config->get('value_2_conteo');
    $value_2_meteo = $this->config->get('value_2_meteo');
    $value_2_airquality = $this->config->get('value_2_airquality');

    $conteo = new \GuzzleHttp\Client(['headers' => [$key_1 => $value_1, $key_2 => $value_2_conteo]]);
    $response_conteo = $conteo->request('GET', $base_url, [
      'verify' => false,
    ]);
    $content_conteo = json_decode($response_conteo->getBody()->getContents());
    $meteo = new \GuzzleHttp\Client(['headers' => [$key_1 => $value_1, $key_2 => $value_2_meteo]]);
    $response_meteo = $meteo->request('GET', $base_url, [
      'verify' => false,
    ]);
    $content_meteo = json_decode($response_meteo->getBody()->getContents());
    $airquality = new \GuzzleHttp\Client(['headers' => [$key_1 => $value_1, $key_2 => $value_2_airquality]]);
    $response_airquality = $airquality->request('GET', $base_url, [
      'verify' => false,
    ]);
    $content_airquality = json_decode($response_airquality->getBody()->getContents());

    //Recoger la información de conteo
    $conteo = [];
    foreach ($content_conteo as $clave => $valor) {
      $personas = [];
      foreach ($valor as $c => $v) {
        if ($c == "peopleCounter") {
          $personas['value'] = $v->value;
        } else if ($c == "location") {
          $personas['location'] = $v->value;
        }
      }
      if ($personas != null) {
        array_push($conteo, $personas);
      }
    }

    //Recoger la información de conteo
    $radiacion_solar = [];
    $humedad_relativa = [];
    $velocidad_viento = [];
    $temperatura = 0;
    foreach ($content_meteo as $clave => $valor) {
      array_push($radiacion_solar, ["location" => $valor->location->value, "value" => $valor->solarRadiation->value]);
      array_push($humedad_relativa, ["location" => $valor->location->value, "value" => $valor->relativeHumidity->value]);
      array_push($velocidad_viento, ["location" => $valor->location->value, "value" => $valor->windSpeed->value]);
      $temperatura += $valor->temperature->value;
    }
    $temperatura_media = $temperatura / count($content_airquality);

    //Recoger la información de airquality
    $monoxido_media = 0;
    $dioxido_media = 0;
    foreach ($content_airquality as $clave => $valor) {
      foreach ($valor as $c => $v) {
        if ($c == "co") {
          $monoxido_media += $v->value;
        } else if ($c == "co2") {
          $dioxido_media += $v->value;
        }
      }
    }
    if ($monoxido_media != 0) {
      $monoxido_media /= count($content_airquality);
    } else {
      $monoxido_media = 0;
    }
    if ($dioxido_media != 0) {
      $dioxido_media /= count($content_airquality);
    } else {
      $dioxido_media = 0;
    }

    // Make response structure with content
    $response = [
      "Número de personas" => $conteo,
      "Radiación solar" => $radiacion_solar,
      "Humedad relativa del aire" => $humedad_relativa,
      "Velocidad del viento" => $velocidad_viento,
      "Temperatura media" => $temperatura_media,
      "Monóxido de carbono media" => $monoxido_media,
      "Dióxido de carbono (CO2) media" => $dioxido_media,
    ];

    return (new ResourceResponse($response))->addCacheableDependency($build);
  }
}
