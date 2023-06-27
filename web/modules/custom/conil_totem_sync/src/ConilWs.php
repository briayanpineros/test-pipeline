<?php

namespace Drupal\conil_totem_sync;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class CurrentScityEntity.
 */
class ConilWs implements ConilWsInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The configuration of the module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * The configuration of the module.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity = NULL;

  /**
   * The current logged user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The file system manager.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new CurrentScityEntity object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   Http client to make http calls.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Log system.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current User.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ClientFactory $http_client_factory,
    LoggerChannelFactory $logger,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    FileSystemInterface $file_system
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClientFactory = $http_client_factory;
    $this->logger = $logger->get('conil_totem_sync');
    $this->config = $config_factory->get('conil_totem_sync.settings');
    $this->currentUser = $current_user;
    $this->fileSystem = $file_system;
  }

  /**
   * Get if the service is active or not.
   */
  public function isActive() {
    $active = $this->config->get('active');
    return $active ? TRUE : FALSE;
  }

  /**
   * Get the current environment.
   */
  protected function getEnvironment() {
    $environment = $this->config->get('environment');
    if (empty($environment)) {
      $this->logger->error($this->t('There no are any environment configured to the Conil WS.'));
      return NULL;
    }
    return $this->entityTypeManager->getStorage('conil_ws_entity')
      ->load($environment);
  }

  /**
   * {@inheritdoc}
   */
  public function createJson() {
    $config = \Drupal::service('config.factory')->get('totems.settings');

    if ($config->get('nextMedia') == NULL) {
      return;
    }

    $arrayMedias = explode(',', $config->get('media_content'));
    $medias = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($arrayMedias);

    if ($medias == NULL) {
      return;
    }
    $totems = [];

    foreach ($medias as $media) {
      if ($media->bundle() == 'content_grids_videos') {
        $arrayUrl[] = [
          'url' => 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_video_file->entity->createFileUrl(),
          'time' => $config->get('nextMedia'),
        ];
      }
      else {
        $arrayUrl[] = [
          'url' => 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_image->entity->createFileUrl(),
          'time' => $config->get('nextMedia'),
        ];
      }
    }
    $arrayUrl[] = [
      'url' => 'https://' . $_SERVER['SERVER_NAME'] . '/sites/default/files/pdfs-print/weather.png',
      'time' => $config->get('nextMedia'),
    ];
    $current_date = date('Y-m-d');
    $current_hour = date('H:i');

    $arrayTotems = \Drupal::entityTypeManager()->getStorage('totem')->loadMultiple();
    if ($arrayTotems == NULL) {
      return;
    }
    foreach ($arrayTotems as $totem) {
      $identifier = $totem->field_identifier->value;
      $interactive = $totem->field_interactive->value;
      $totems[]['id'] = $identifier;
      if ($interactive) {
        $type = 'ssaver';
      }
      else {
        $type = 'cmsplay';
      }

      $lastkey = array_key_last($totems);
      $totems[$lastkey]['type'] = $type;

      $parents = $totem->field_media_library->referencedEntities();
      foreach ($parents as $father) {
        $children = $father->field_hours->referencedEntities();
        if ($current_date >= $father->field_start_time->value  && $current_date <= $father->field_end_time->value) {
          foreach ($children as $son) {
            $medias = $son->field_media_content_grids->referencedEntities();
            $start_minutes = (floor(($son->field_interval_hours->from / 60) % 60)) == 0 ? '00' : floor(($son->field_interval_hours->from / 60) % 60);
            $start_hour = floor($son->field_interval_hours->from / 3600) . ":" . $start_minutes;
            $end_minutes = (floor(($son->field_interval_hours->to / 60) % 60)) == 0 ? '00' : floor(($son->field_interval_hours->to / 60) % 60);
            $end_hour = floor($son->field_interval_hours->to / 3600) . ":" . $end_minutes;
            if (strtotime($current_hour) >= strtotime($start_hour) && strtotime($current_hour) <= strtotime($end_hour)) {
              foreach ($medias as $media) {
                if ($media->bundle() == 'content_grids_videos') {
                  $arrayUrl2[] = [
                    'url' => 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_video_file->entity->createFileUrl(),
                    'time' => $config->get('nextMedia'),
                  ];
                }
                else {
                  $arrayUrl2[] = [
                    'url' => 'https://' . $_SERVER['SERVER_NAME'] . $media->field_media_image->entity->createFileUrl(),
                    'time' => $config->get('nextMedia'),
                  ];
                }
              }
              $arrayUrl2[] = [
                'url' => 'https://' . $_SERVER['SERVER_NAME'] . '/sites/default/files/pdfs-print/weather.png',
                'time' => $config->get('nextMedia'),
              ];
              $totems[$lastkey]['list'][] = $arrayUrl2;
            }
            $arrayUrl2 = [];
          }
        }
      }
      if (empty($totems[$lastkey]['list'])) {
        $totems[$lastkey]['list'][] = $arrayUrl;
      }
    }

    return $totems;
  }

  /**
   * {@inheritdoc}
   */
  public function verifiedJson($json) {
    $last_update = $this->config->get('last_update');
    $md5 = md5(json_encode($json, JSON_UNESCAPED_SLASHES));
    if ($md5 !== $last_update) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function post($json) {
    if (!$this->isActive()) {
      return NULL;
    }
    $environment = $this->getEnvironment();
    if (empty($environment)) {
      $this->logger->error($this->t('There no are any environment configured to the Conil WS.'));
      return NULL;
    }
    try {
      $url = $environment->getUrl();
      $api = $environment->getApi();
      $client = $this->httpClientFactory->fromOptions([
        'http_errors' => FALSE,
        'connect_timeout' => 5,
      ]);
      $response = $client->post($url, [
        'auth' => [
          'api_key' => $api,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'body' => JSON::encode($json),
      ]);
      if ($response->getStatusCode() == 200) {
        \Drupal::logger('conil_totem_sync')->notice(
          $this->t(
            "[200] Updated totem info."
          )
        );
        return TRUE;
      }
      else {
        \Drupal::logger('conil_totem_sync')->error(
          $this->t(
            "[%status] Conil totem synchronization returned a status code %status",
            [
              '%status' => $response->getStatusCode(),
            ]
          )
        );
        return FALSE;
      }
    }
    catch (ConnectException $e) {
      watchdog_exception('conil_totem_sync', $e);
    }
    catch (Exception $e) {
      watchdog_exception('conil_totem_sync', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    $environment = $this->getEnvironment();
    if ($environment) {
      return TRUE;
    }
    return FALSE;
  }

}
