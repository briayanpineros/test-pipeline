<?php

namespace Drupal\content_api\Plugin\rest\resource;

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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Provides REST API for Content Based on URL.
 *
 * @RestResource(
 *   id = "news_content_data_rest_api",
 *   label = @Translation("News Export API"),
 *   uri_paths = {
 *     "canonical" = "/api/news/{id}"
 *   }
 * )
 */

class NewsContentDataRestApi extends ResourceBase
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
  public function get($id){
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $invalidParameters = $this->checkInvalidParameters($id);
    if (!$invalidParameters) {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $node = $nodeStorage->load($id);
      if ($node->hasTranslation($lang)) {
        $translation = $node->getTranslation($lang);
      }

      if (!empty($translation)) {
        $response = [];
        $response['id'] = $id;
        $response['title'] = $translation->title->value;
        $response['subtitle'] = $translation->field_news_subtitle->value;
        $response['body'] = $translation->body->value;
        $response['paragraphs'] = $translation->field_news_paragraphs->value;
        $response['created'] = date('Y-m-d\\TH:i:s',$translation->created->value);
        $response['status'] = $translation->status->value;
      }

      return (new ResourceResponse($response))->addCacheableDependency($build);
    } else {
      throw new NotFoundHttpException("Nodo desconocido");
    }
  }



  protected function checkInvalidParameters($id){
    if (isset($id) && (!is_numeric($id) || $id < 1)) {
      throw new UnprocessableEntityHttpException('El campo "id" debe ser un valor entero positivo.');
    }
    return FALSE;
  }
}

