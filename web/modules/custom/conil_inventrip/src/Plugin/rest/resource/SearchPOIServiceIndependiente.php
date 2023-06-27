<?php

namespace Drupal\conil_inventrip\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\file\Entity\File;

/**
 * Provides REST API for Search POIS.
 *
 * @RestResource(
 *   id = "search_pois_service",
 *   label = @Translation("Search POIS API"),
 *   uri_paths = {
 *     "canonical" = "/api/search_pois/{date}"
 *   }
 * )
 */
class SearchPOIServiceIndependiente extends ResourceBase {

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function get($date) {

    // Creamos la conexión a la base de datos.
    $connection = \Drupal::database();

    $invalidParameters = $this->checkInvalidParameters($date);
    if (!$invalidParameters) {
      // Convertimos el string de la fecha recibida a time.
      $time = strtotime($date);

      $query = \Drupal::entityQuery('node')
        ->sort('created', 'DESC')
        ->condition('changed', $time, '>=')
        ->condition('type', 'poi');

      $result_nids = $query->execute();
      $resultados = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($result_nids);

      // Vamos a rellenar el array response con los datos para cada uno de los pois.
      $response = [];
      foreach ($resultados as $clave => $valor) {

        // Obtener todos los títulos en los distintos idiomas.
        $titles = [];
        // Hacemos una consulta para obtener el nodo (lo usaremos también para las traducciones del cuerpo de los POIs)
        // Una vez obtenido el nid, obtenemos las traducciones.
        $query = $connection->select('node_field_data', 'n');
        $query->addfield('n', 'langcode');
        $query->addfield('n', 'title');
        $query->condition('n.nid', $valor->id());
        $result = $query->execute()->fetchAllKeyed(0, 1);
        foreach ($result as $c => $v) {
          array_push($titles, ['value' => $v, 'language' => $c]);
        }

        // Obtener todas las descripciones en los distintos idiomas.
        $descriptions = [];
        // Una vez obtenido el nid, obtenemos las traducciones.
        $query = $connection->select('node__body', 'n');
        $query->addfield('n', 'langcode');
        $query->addfield('n', 'body_value');
        $query->condition('n.entity_id', $valor->id());
        $result = $query->execute()->fetchAllKeyed(0, 1);
        foreach ($result as $c => $v) {
          array_push($descriptions, ['value' => $v, 'language' => $c]);
        }

        // Obtener la imagen de portada, insertarla en la BBDD para luego poder referenciarla.
        $imagenes = [];
        foreach ($valor->field_poi_gallery->referencedEntities() as $val) {
          if (in_array($val->bundle(), ['poi_media', 'image', 'carrousel_images'])) {
            $fid = $val->getSource()->getSourceFieldValue($val);
            $file = File::load($fid);
            $url = $file->createFileUrl(FALSE);
            array_push($imagenes, $url);
          }
        }

        // Obtener los nombres para la categoría y para el tipo de viaje teniendo las ids
        // Hacemos una consulta a la base de datos.
        $tipos = [];
        foreach ($valor->field_poi_category->referencedEntities() as $val) {
          $tipos[] = $val->label();
        }

        $tipos_viaje = [];
        foreach ($valor->field_tipo_viaje->referencedEntities() as $val) {
          $tipos_viaje[] = $val->label();
        }

        // Get latitude and longitude.
        $point = explode(" ", $valor->field_poi_geofield->value);
        $longitude = explode("(", $point[1]);
        $latitude = explode(")", $point[2]);

        $response['node/' . $valor->id()] = [
          'url' => !$valor->field_poi_webpage->isEmpty() ? $valor->field_poi_webpage->getValue('url')[0]['uri'] : NULL,
          'name'  => $titles,
          'type' => $tipos,
          'email' => !$valor->field_poi_email->isEmpty() ? $valor->field_poi_email->getValue('email')[0]['value'] : NULL,
          'image' => $imagenes,
          'extras' => ["complementAddress" => $valor->field_poi_address_extra->value],
          'latitude' => $latitude[0],
          'longitude' => $longitude[1],
          'telephone' => !$valor->field_poi_telephone->isEmpty() ? $valor->field_poi_telephone->value : NULL,
          'identifier' => !$valor->field_poi_inventrip_identifier->isEmpty() ? $valor->field_poi_inventrip_identifier->value : NULL,
          'identifierDrupal' => $valor->id(),
          'postalCode' => !$valor->field_poi_address_cp->isEmpty() ? $valor->field_poi_address_cp->value : NULL,
          'description' => $descriptions,
          'touristType' => $tipos_viaje,
          'addressRegion' => !$valor->field_poi_address_region->isEmpty() ? $valor->field_poi_address_region->value : NULL,
          'streetAddress' => !$valor->field_poi_address->isEmpty() ? $valor->field_poi_address->value : NULL,
          'addressCountry' => !$valor->field_poi_address_country->isEmpty() ? $valor->field_poi_address_country->value : NULL,
          'addressLocality' => !$valor->field_poi_address_locality->isEmpty() ? $valor->field_poi_address_locality->value : NULL,
          'addressProvince' => !$valor->field_poi_address_province->isEmpty() ? $valor->field_poi_address_province->value : NULL,
        ];
      }

      return new ModifiedResourceResponse($response, 200);
    }
    else {
      throw new NotFoundHttpException("Nodo desconocido");
    }
  }

  /**
   *
   */
  protected function checkInvalidParameters($date) {
    if ($date == NULL || !is_string($date) || strlen($date) != 10) {
      throw new UnprocessableEntityHttpException('El campo "date" debe ser: una cadena con formato dd-mm-YYYY.');
    }
    return FALSE;
  }

}
