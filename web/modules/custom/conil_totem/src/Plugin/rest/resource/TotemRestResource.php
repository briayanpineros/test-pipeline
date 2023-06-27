<?php

namespace Drupal\conil_totem\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Provides REST API for Totems.
 *
 * @RestResource(
 *   id = "totem_rest_resource",
 *   label = @Translation("Totem Configuration Data"),
 *   uri_paths = {
 *     "canonical" = "/services/totem-configuration/{identifier}"
 *   }
 * )
 */
class TotemRestResource extends ResourceBase {

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
  public function get($identifier) {

    $config = $this->configFactory->get('totems.settings');

    $arrayMedias = explode(',', $config->get('media_content'));
    $medias = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($arrayMedias);

    foreach ($medias as $media) {
      if ($media->bundle() == 'content_grids_videos') {
        $arrayUrl[] = 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_video_file->entity->createFileUrl();
      }
      else {
        $arrayUrl[] = 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_image->entity->createFileUrl();
      }
    }

    $arrayTotems = \Drupal::entityTypeManager()->getStorage('totem')->loadByProperties(['field_identifier' => $identifier]);
    foreach ($arrayTotems as $totem) {
      $id = $totem->id();
      $name = $totem->getName();
      $identifier = $totem->field_identifier->value;
      $interactive = $totem->field_interactive->value;
      $url = $totem->field_input_url->value;
      $parents = $totem->field_media_library->referencedEntities();
      $dates = [];
      foreach ($parents as $father) {
        $children = $father->field_hours->referencedEntities();
        $dates[$name]['dates'][] = [
          "start_time" => $father->field_start_time->value,
          "end_time" => $father->field_end_time->value,
        ];
        foreach ($children as $son) {
          $medias = $son->field_media_content_grids->referencedEntities();

          foreach ($medias as $media) {
            if ($media->bundle() == 'content_grids_videos') {
              $arrayUrl2[] = 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_video_file->entity->createFileUrl();
            }
            else {
              $arrayUrl2[] = 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_image->entity->createFileUrl();
            }
          }
          $dates[$name]['dates'][array_key_last($dates[$name]['dates'])]['content'][] = [
            "start" => $son->field_interval_hours->from,
            "end" => $son->field_interval_hours->to,
            "media" => $arrayUrl2,
          ];
          $arrayUrl2 = [];
        }
      }
      $totems[$name] = [
        'id' => $id,
        'identifier' => $identifier,
        'interactive' => $interactive,
        'url' => $url,
      ];
      $totems[$name]['dates'] = $dates[$name]['dates'];
    }

    $respuesta = [
      "default" => [
        "nextMedia" => $config->get('nextMedia'),
        "waitTime" => $config->get('waitTime'),
        "media_content" => $arrayUrl,
      ],
      "totems_list" => $totems,
    ];

    return new ModifiedResourceResponse(($respuesta), 200);
  }

}
