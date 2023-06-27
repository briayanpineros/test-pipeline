<?php

namespace Drupal\conil_selfie\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a conil_selfie form.
 */
class SelfieForm extends FormBase {

  /**
   * The entity type manager.
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
   * The file system manager.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new DeleteMultiple object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   Http client to make http calls.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Log system.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ClientFactory $http_client_factory,
    FileSystemInterface $file_system,
    LoggerChannelFactory $logger
    ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClientFactory = $http_client_factory;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('http_client_factory'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_selfie_selfie';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_selfie.background');

    $arrBackgrounds = preg_split('@,@', $config->get('backgrounds'), -1, PREG_SPLIT_NO_EMPTY);
    $backgrounds = $this->entityTypeManager->getStorage('media')->loadMultiple($arrBackgrounds);
    $img = [];
    foreach ($backgrounds as $entityImage) {
      $img[$entityImage->id()] = "<img src='" .
      $this->entityTypeManager
        ->getStorage('image_style')
        ->load('media_library')
        ->buildUrl($entityImage->field_media_image->entity->getFileUri())
      . "'>";
    }

    $form['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => ['class' => ['first-description']],
      '#value' => $this->t('1. Select a background and take a picture in front of the camera.'),
    ];

    $form['first_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['first-group']],
    ];

    $markup = "";
    if ($form_state->getValue('backgrounds')) {
      $markup = $img[$form_state->getValue('backgrounds')];
      $output = '<div id="imgSelected"><div class="img-selected">' . $markup . '</div></div>';
      $form['first_group']['imgSelected'] = [
        '#markup' => $output,
      ];
    }
    else {
      $form['first_group']['imgSelected'] = [
        '#markup' => '<div id="imgSelected"><div class="img-selected">' . $img[array_key_first($img)] . "</div></div>",
      ];
    }
    $form['first_group']['camera'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['camera-group']],
    ];
    $form['first_group']['camera']['video'] = [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'video',
      ],
      '#tag' => 'video',
    ];

    $form['first_group']['camera']['photo_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['photo-wrapper']],
    ];
    $form['first_group']['camera']['photo_wrapper']['photo'] = [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'photo',
        'src' => '/themes/custom/totem_theme/imagenes/camera_background.png',
      ],
      '#tag' => 'img',
    ];

    $form['first_group']['camera']['canvas'] = [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'canvas',
      ],
      '#tag' => 'canvas',
    ];

    $form['first_group']['camera']['output'] = [
      '#type' => 'container',
      '#title' => $this->t('Output'),
    ];

    $form['first_group']['camera']['buttonsCamera'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'buttonsCamera',
        'class' => ['shot'],
      ],
    ];

    $form['first_group']['camera']['buttonsCamera']['startbutton'] = [
      '#type' => 'button',
      '#attributes' => [
        'id' => 'startbutton',
        'class' => ['btn-info'],
      ],
      '#value' => $this->t('Shot'),
    ];

    $form['first_group']['camera']['selfie'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'selfie',
      ],
    ];

    $form['backgrounds'] = [
      '#type' => 'radios',
      '#options' => $img,
      '#title' => NULL,
      '#default_value' => !empty($img) ? array_key_first($img) : NULL,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::loadImage',
        'wrapper' => 'imgSelected',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#attributes' => [
        'class' => [
          'btn-success',
          'submit-selfie',
        ],
      ],
    ];

    $form['#attached']['library'][] = 'conil_selfie/conil_selfie_webcam';
    return $form;
  }

  /**
   * Load Image method.
   */
  public function loadImage(array &$form, FormStateInterface $form_state) {
    // Return the prepared image.
    return $form['first_group']['imgSelected'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('backgrounds'))) {
      $form_state->setErrorByName('backgrounds', $this->t('You must choose a background for the selfie'));
    }

    /*if (empty($form_state->getValue('selfie'))) {
    $form_state->setErrorByName('selfie', $this->t('Has not taken any photos'));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fondo.
    $background = $form_state->getValue('backgrounds');
    $backgroundEntity = $this->entityTypeManager->getStorage('media')->load($background);
    if (!empty($backgroundEntity)) {
      if (!$backgroundEntity->field_media_image->isEmpty()) {
        $style = $this->entityTypeManager
          ->getStorage('image_style')
          ->load('wide');

        $destination = $style
          ->buildUri($backgroundEntity->field_media_image->entity->getFileUri());

        if (!file_exists($destination)) {
          $style->createDerivative($backgroundEntity->field_media_image->entity->getFileUri(), $destination);
        }

        $fopenback = fopen($this->entityTypeManager
          ->getStorage('image_style')
          ->load('wide')
          ->buildUri($backgroundEntity->field_media_image->entity->getFileUri()), 'r');

      }
    }

    // Se guarda temporalmente el selfie.
    if (!empty($form_state->getValue('selfie'))) {
      $selfie = preg_replace('#^data:image/\w+;base64,#i', '', $form_state->getValue('selfie'));
      $directory = 'temporary://selfies/';
      $filepath = $this->fileSystem->realpath($directory);
      $this->fileSystem->prepareDirectory($filepath, $this->fileSystem::CREATE_DIRECTORY);
      $path_temp = tempnam($filepath, 'selfies_') . ".png";
      $filename = basename($path_temp);
      file_put_contents($path_temp, base64_decode($selfie));

      $fopenself = fopen($path_temp, 'r');

      /*$fopenback = fopen('101-2621x1747.jpg', 'r');
      $fopenself = fopen('avatar-1.jpg', 'r');*/
      // Llamada al servicio.
      $result = $this->postFormData([
        'imagen_fondo' => $fopenback,
        'imagen_foto' => $fopenself,
      ]);

      // Llamada al servicio.
      /*$result = $this->post([
      'imagen_fondo' => $background64,
      'imagen_foto' => $selfie,
      ]);*/

      // Comprobamos el resultado.
      if (!empty($result)) {
        $selfie = call_user_func_array('pack', array_merge(['C*'], $result['imageConvert']['data']));
        $directory = 'temporary://selfies/';
        $filepath = $this->fileSystem->realpath($directory);
        $this->fileSystem->prepareDirectory($filepath, $this->fileSystem::CREATE_DIRECTORY);

        $path_temp = tempnam($filepath, 'selfies_');
        $filename = basename($path_temp);
        file_put_contents($path_temp, $selfie);

        $form_state->setRedirect('conil_selfie.selfie_confirm', [
          'selfie' => urlencode($filename),
          'background' => $background,
        ]);
      }
      else {
        $this->messenger()->addError("SERVICIO NO DISPONIBLE");
      }
    }
    else {
      $form_state->setRedirect('conil_selfie.selfie_confirm', [
        'selfie' => 0,
        'background' => $background,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function post($params) {
    $config = $this->config('conil_selfie.background');
    try {
      $url = $config->get('url_api');
      $client = $this->httpClientFactory->fromOptions([
        'http_errors' => FALSE,
        'connect_timeout' => 5,
      ]);
      $response = $client->post($url, [
        'auth' => [
          'api_key' => $config->get('api_key'),
        ],
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'body' => Json::encode($params),
      ]);
      if ($response->getStatusCode() == 200) {
        $this->logger->get('conil_selfie')->notice(
          $this->t(
            "[200] The selfie conil."
          )
        );
        return Json::decode($response->getBody());
      }
      else {
        $this->logger->get('conil_selfie')->error(
          $this->t(
            "[%status] The selfie conil post has returned status code %status",
            [
              '%status' => $response->getStatusCode(),
            ]
          )
        );
        return FALSE;
      }
    }
    catch (ConnectException $e) {
      watchdog_exception('conil_selfie', $e);
    }
    catch (Exception $e) {
      watchdog_exception('conil_selfie', $e);
    }
  }

  /**
   * Multipart post.
   *
   * @param array $params
   *   Files.
   */
  public function postFormData(array $params) {
    $config = $this->config('conil_selfie.background');
    try {
      $url = $config->get('url_api');
      $client = $this->httpClientFactory->fromOptions([
        'http_errors' => FALSE,
        'connect_timeout' => 5,
        'timeout' => $config->get('timeout'),
      ]);
      $response = $client->post(
        $url, [
          'multipart' => [
            [
              'name' => 'imagen',
              'contents' => $params['imagen_foto'],
            ],
            [
              'name' => 'imagen',
              'contents' => $params['imagen_fondo'],
            ],
          ],
        ],
      );
      if ($response->getStatusCode() == 200) {
        $this->logger->get('conil_selfie')->notice(
          $this->t(
            "[200] The selfie conil."
          )
        );
        return Json::decode($response->getBody());
      }
      else {
        $this->logger->get('conil_selfie')->error(
          $this->t(
            "[%status] The selfie conil post has returned status code %status",
            [
              '%status' => $response->getStatusCode(),
            ]
          )
        );
        return FALSE;
      }
    }
    catch (ConnectException $e) {
      watchdog_exception('conil_selfie', $e);
    }
    catch (Exception $e) {
      watchdog_exception('conil_selfie', $e);
    }
  }

}
