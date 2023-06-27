<?php

namespace Drupal\conil_visitors_api\Plugin\rest\resource;

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
use Exception;
use PhpParser\Node\NullableType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Provides REST API for Count Visitors for categories.
 *
 * @RestResource(
 *   id = "conil_visitors_api",
 *   label = @Translation("Visitors categories API"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/api/visitors/categories"
 *   }
 * )
 */

class VisitorsCategoriesService extends ResourceBase
{

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

    //Comprobar si el usuario tiene permisos
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $connection = \Drupal::database();
    $query = $connection->query("SELECT DISTINCT nc.nid, nc.totalcount, nfd.type, nfd.title, ttfd.name
                                  FROM node_counter as nc, node_field_data as nfd, taxonomy_index as ti, taxonomy_term_field_data as ttfd
                                    WHERE nc.nid = nfd.nid
                                      AND nfd.nid = ti.nid
                                      AND ti.tid = ttfd.tid
                                      AND (ttfd.name = 'Qué hacer'
                                        OR ttfd.name = 'Dónde dormir'
                                        OR ttfd.name = 'Dónde comer'
                                        OR ttfd.name = 'Cómo llegar')");
    $results = $query->fetchAll();

    $response = [];
    $nids = ['Cómo llegar' => [], 'Dónde dormir' => [], 'Dónde comer' => [], 'Qué hacer' => [],];
    foreach ($results as $node) {

      if ($node->type == 'poi') {
        //Si no está creado, lo creamos
        if (!isset($response['Visitas POI'])) {
          $response['Visitas POI'] = [
            'Cómo llegar' => 0,
            'Dónde dormir' => 0,
            'Dónde comer' => 0,
            'Qué hacer' => 0,
          ];
        }

        //Sumamos las visitas POI a cada tipo
        if ($node->name == "Cómo llegar") {
          if (!in_array($node->nid, $nids['Cómo llegar'])) {
            array_push($nids['Cómo llegar'], $node->nid);
            $response['Visitas POI']['Cómo llegar'] += $node->totalcount;
          }
        } else if ($node->name == "Dónde dormir") {
          if (!in_array($node->nid, $nids['Dónde dormir'])) {
            array_push($nids['Dónde dormir'], $node->nid);
            $response['Visitas POI']['Dónde dormir'] += $node->totalcount;
          }
        } else if ($node->name == "Dónde comer") {
          if (!in_array($node->nid, $nids['Dónde comer'])) {
            array_push($nids['Dónde comer'], $node->nid);
            $response['Visitas POI']['Dónde comer'] += $node->totalcount;
          }
        } else if ($node->name == "Qué hacer") {
          if (!in_array($node->nid, $nids['Qué hacer'])) {
            array_push($nids['Qué hacer'], $node->nid);
            $response['Visitas POI']['Qué hacer'] += $node->totalcount;
          }
        }
      }
    }
    return (new ResourceResponse($response))->addCacheableDependency($build);
  }

}
