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
use Drupal\rest\ModifiedResourceResponse;
use Drupal\webform\Entity\Webform;

/**
 * Provides REST API for Surveys from our site.
 *
 * @RestResource(
 *   id = "surveys_responses_export",
 *   label = @Translation("Surveys responses from our site API"),
 *   uri_paths = {
 *     "canonical" = "/api/surveys/responses"
 *   }
 * )
 */

class SurveysResponsesService extends ResourceBase
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

    //Creamos la conexión a la base de datos
    $connection = \Drupal::database();

    //Create response variable
    $response = ["Respuestas_Encuesta" => []];

    //Webform submission data
    $query_webform_submission = $connection->query("SELECT * FROM webform_submission WHERE webform_id LIKE 'survey%'");
    $result_webform_submission = $query_webform_submission->fetchAll();

    //Foreach to go throw webform submissions
    foreach ($result_webform_submission as $clave => $value) {

      //Webform submission data to fill the answer list
      $query_webform_submission_data_especific = $connection->query("SELECT * FROM webform_submission_data WHERE sid='" . $value->sid . "' AND webform_id LIKE 'survey%'");
      $results_webform_submission_data_especific = $query_webform_submission_data_especific->fetchAll();

      //Array to add all submissions
      $add_to_response = [];
      $add_to_response['model'] = "respuestas";
      $add_to_response['idEncuesta'] = $results_webform_submission_data_especific[0]->webform_id;
      $add_to_response['dateCreated'] = date("d/m/Y", $results_webform_submission_data_especific[0]->created);
      $add_to_response['answersList'] = [];

      $numero_respuesta = 0;
      foreach ($results_webform_submission_data_especific as $c => $v) {
        if ($v->value != null && $v->value != "") {
          $numero_respuesta += 1;
          $answer_list = [
            "id" => 'Respuesta ' . $numero_respuesta,
            //HAY QUE CAMBIAR IDPREGUNTA e IDOPCIONESRESPUESTA para que sean la misma que la otra
            "idPregunta" => $v->name,
            "idOpcionesRespuesta" => $v->name,
            "language" => $value->langcode,
            "optionSelection" => [
              "value" => $v->value,
            ]
          ];
          array_push($add_to_response['answersList'], $answer_list);
        }
      }

      array_push($response["Respuestas_Encuesta"], $add_to_response);
    }


    return new ModifiedResourceResponse($response, 200);
  }
}
