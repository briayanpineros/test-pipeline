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
 *   id = "surveys_config_export",
 *   label = @Translation("Surveys config from our site API"),
 *   uri_paths = {
 *     "canonical" = "/api/surveys/config"
 *   }
 * )
 */

class SurveysConfigService extends ResourceBase
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

    ////Creamos la conexión a la base de datos
    $connection = \Drupal::database();

    //Create response variable
    $response = ["Configuracion_Encuesta" => []];

    //Get webforms for surveys created only
    $query = $connection->query("SELECT webform_id FROM webform WHERE webform_id LIKE 'survey_%'");
    $result_query = $query->fetchAll();
    $nids = [];
    foreach ($result_query as $valor) {
      array_push($nids, $valor->webform_id);
    }
    $load_webforms_surveys = \Drupal::entityTypeManager()->getStorage('webform')->loadMultiple($nids);


    //Foreach to go throw webform submissions
    foreach ($load_webforms_surveys as $clave => $value) {
      //Obtenemos la configuración del webform que estamos recorriendo
      $config = \Drupal::config('webform.webform.' . $value->id());
      $webform = Webform::load($value->id()); //replace webform_id with the webform id
      if ($webform) {
        $elementsDecoded = $webform->getElementsDecoded();
      }

      //ARRAY PARA AÑADIR A RESPONSE
      $add_to_response = [];

      //////////////////////////////////////////////////
      //PARTE DEL CÓDIGO PARA LA ESTRUCTURA DE LAS ENCUESTAS

      //Get conil_description on diferent languages
      $titles = [
        [
          'value' => $config->get('title'),
          'langcode' => 'en'
        ]
      ];

      //Get conil_description on diferent languages
      $descriptions = [
        [
          'value' => $config->get('description'),
          'langcode' => 'en'
        ]
      ];

      //Array to add encuesta structure
      $estructura_encuesta = [];
      $estructura_encuesta['model'] = "encuesta";
      $estructura_encuesta['id'] = $value->id();
      //Aquí se decidió que no habría fecha de inicio (open) y de fin (cierre) de la encuesta,
      //ya que no se almacenaba dicha información en la base de datos

      //Obtenemos todas las traducciones del título y la descripción
      $query = $connection->select('config', 'c');
      $query->addfield('c', 'collection');
      $query->addfield('c', 'data');
      $query->condition('c.name', 'webform.webform.' . $value->id());
      $result = $query->execute()->fetchAllKeyed(0, 1);

      foreach ($result as $c => $v) {
        if ($c != "") {
          $language = explode('.', $c);
          array_push($titles, ['value' => unserialize($v)['title'], 'language' => $language[1]]);
          if (unserialize($v)['description'] != null && unserialize($v)['description'] != "") {
            array_push($descriptions, ['value' => unserialize($v)['description'], 'language' => $language[1]]);
          } else {
            array_push($descriptions, ['value' => "", 'language' => $language[1]]);
          }
        }
      }

      //Añadimos los arrays titulos y descripciones a la estructura json
      $estructura_encuesta['title'] = $titles;
      $estructura_encuesta['description'] = $descriptions;

      //Add idQuestions
      $estructura_encuesta['idQuestions'] = [];
      foreach ($elementsDecoded as $c => $v) {
        if ($v['#title'] != "" || $v['#title'] != null) {
          array_push($estructura_encuesta["idQuestions"], $c);
        }
      }

      array_push($add_to_response, $estructura_encuesta);
      //////////////////////////////////////////////////

      //////////////////////////////////////////////////
      //PARTE DEL CÓDIGO PARA LA ESTRUCTURA DE LAS PREGUNTAS
      $estructura_preguntas = [];
      $estructura_preguntas['model'] = "preguntas";
      $estructura_preguntas['questions'] = [];

      $position = 0;
      foreach ($elementsDecoded as $cla => $val) {
        $titles = [
          [
            'value' => $val['#title'],
            'language' => 'en'
          ]
        ];

        $elements = [];
        //Get questions title on diferent languages
        if ($val['#title'] != "" || $val['#title'] != null) {
          //Add title yo array
          $elements['id'] = $cla;

          foreach ($result as $c => $v) {
            $to_array = explode("\n", unserialize($v)['elements']);

            $claves = [];
            //To obtain titles from keys of idQuestion and add it to new array with translations
            foreach ($estructura_encuesta['idQuestions'] as $val) {
              if (substr($val, 0, 1) != " ") {
                $search = array_search($val . ':', $to_array);
                $val_final = substr($to_array[$search + 1], 13);

                //str_replace("'", " ", $val_final);
                array_push($claves, iconv(mb_detect_encoding($val_final, mb_detect_order(), true), "UTF-8", $val_final));
              }
            }

            if ($c != "") {
              $language = explode('.', $c);
              if (substr($claves[$position], -1) == "'") {
                $substr = substr($claves[$position], 0, (strlen($claves[$position])-1));
                $string_final = iconv(mb_detect_encoding($substr, mb_detect_order(), true), "UTF-8", $substr);
                $claves[$position] = $string_final;
              }

              //Add titles to elements array
              array_push($titles, ['value' => $claves[$position], 'language' => $language[1]]);
            }
          }
          $elements['title'] = $titles;
          $position += 1;
        }

        //Add elements array to another array for questions
        array_push($estructura_preguntas['questions'], $elements);
      }

      //Add array for questions to the global array
      array_push($add_to_response, $estructura_preguntas);
      //////////////////////////////////////////////////

      //////////////////////////////////////////////////
      //PARTE DEL CÓDIGO PARA LA ESTRUCTURA DE LAS POSIBLES RESPUESTAS
      $estructura_opciones = [];
      $estructura_opciones['model'] = "opciones_respuestas";
      $estructura_opciones['questions'] = [];

      foreach ($elementsDecoded as $cla => $val) {
        $elements = [];

        //Get questions title on diferent languages
        if ($val['#title'] != "" || $val['#title'] != null) {
          //Add title yo array
          $elements['id'] = $cla;
          $elements['idPregunta'] = $cla;
          $elements['answers'] = [];

          //Tratamiento de los distintos tipos de contenido de las encuestas
          if ($val['#type'] == 'textfield') {
            $elements['answers']['option'] = [
              ['value' => 'Text filled in by the user', 'language' => 'en'],
              ['value' => "Texte rempli par l'utilisateur", 'language' => 'fr'],
              ['value' => 'Texto introducido por el usuario', 'language' => 'es'],
              ['value' => 'Vom Benutzer eingegebener Text', 'language' => 'de'],
              ['value' => "Testo compilato dall'utente", 'language' => 'it'],
            ];
          } elseif ($val['#type'] == 'radios') {
            $elements['answers']['option'] = [];
            foreach ($val['#options'] as $option) {
              array_push($elements['answers']['option'], ['value' => $option, 'language' => 'en']);
            }
          } elseif ($val['#type'] == 'number') {
            $elements['answers']['option'] = [
              ['value' => 'Number entered by user', 'language' => 'en'],
              ['value' => "Numéro saisi par l'utilisateur", 'language' => 'fr'],
              ['value' => 'Número introducido por el usuario', 'language' => 'es'],
              ['value' => 'Vom Benutzer eingegebene Nummer', 'language' => 'de'],
              ['value' => "Numero inserito dall'utente", 'language' => 'it']
            ];
          } elseif ($val['#type'] == 'captcha') {
            $elements['answers']['option'] = [
              ['value' => 'Captcha', 'language' => 'en'],
              ['value' => 'Captcha', 'language' => 'fr'],
              ['value' => 'Captcha', 'language' => 'es'],
              ['value' => 'Captcha', 'language' => 'de'],
              ['value' => 'Captcha', 'language' => 'it']
            ];
          } elseif ($val['#type'] == 'webform_table') {
            $elements['answers']['option'] = [];

            //Para las claves que no contengan "#"
            foreach (array_keys($val) as $cl => $va) {
              if (str_contains($va, '#') == false) {
                foreach (array_keys($val[$va]) as $c => $v) {
                  if (str_contains($v, '#') == false) {
                    if ($val[$va][$v]['#type'] == 'webform_rating') {
                      array_push($elements['answers']['option'], ['value' => $val[$va][$v]['#title'] . ' (Rating 1-10)', 'language' => 'en']);
                    }
                  }
                }
              }
            }
          } elseif ($val['#type'] == 'fieldset') {
            $elements['answers']['option'] = [];
            //Bucle para obtener las respuestas de las distintas opciones de las preguntas
            foreach (array_keys($val) as $c => $v) {
              if (str_contains($v, '#') == false) {
                foreach ($val[$v]['#options'] as $option) {
                  array_push($elements['answers']['option'], ['value' => $option, 'language' => 'en']);
                }
              }
            }
          } else {
            $elements['answers']['option'] = [
              ['value' => 'No predefined options', 'language' => 'en'],
              ['value' => "Pas d'options prédéfinies", 'language' => 'fr'],
              ['value' => 'Sin opciones predefinidas', 'language' => 'es'],
              ['value' => 'Keine vordefinierten Optionen', 'language' => 'de'],
              ['value' => 'Nessuna opzione predefinita', 'language' => 'it']
            ];
          }
        }

        //Add elements array to another array for questions
        array_push($estructura_opciones['questions'], $elements);
      }

      //Add array for questions to the global array
      array_push($add_to_response, $estructura_opciones);


      //Add global array yo main array
      array_push($response["Configuracion_Encuesta"], $add_to_response);
    }


    return new ModifiedResourceResponse($response, 200);
  }
}
