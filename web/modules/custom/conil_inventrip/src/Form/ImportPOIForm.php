<?php

namespace Drupal\conil_inventrip\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Implements the Simple form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ImportPOIForm extends FormBase {

  /**
   * The configuration of the module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  const TAXRELATIONS = [
    "Qué hacer" => [
      "Playas y Calas" => ["Beach"],
      "Lugares religiosos" => ["PlaceOfWorship"],
      "Edificios de interés" => ["CivilBuilding", "TouristAttraction", "CultureCenter", "LandmarksOrHistoricalBuildings", "MilitaryBuilding", "FishingPort"],
      "Museos y Galerías de arte" => ["Museum"],
      "Espectáculos" => ["ExhibitionEvent", "Event"],
      "Información Turística" => ["TouristInformationCenter"],
      "Barrios y calles" => ["District", "Street"],
      "Mirador" => ["ViewPoint"],
      "Artesanía y compras" => ["ShoppingCenter", "Store", "Winery", "TouristDestination"],
      "Senderismo y bicicleta" => ["Trail"],
      "Información General" => ["Pharmacy", "MedicalClinic", "PrimaryCare", "CivilProtection", "PoliceStation"],
      "Turismo Activo y Aventura" => ["WaterActivityCenter", "NatureActivityCenter", "TouristTrain", "Park"],
      "Salud y bienestar" => ["HealthAndBeautyBusiness"],
      "Deportes" => ["SportsActivityLocation"],
    ],
    "Dónde comer" => [
      "Restaurantes y bares" => ["Restaurant", "Brewery"],
    ],
    "Cómo Llegar" => [
      "Otros" => ["City", "ParkingFacility"],
      "Autobuses" => ["Autobuses", "BusStation"],
    ],
    "Dónde dormir" => [
      "Otros" => ["LodgingBusiness"],
      "Hoteles" => ["Hotel"],
      "Hostales" => ["GuestHouse", "Hostel"],
    ],
  ];

  /**
   * Function in which we define classes for the form.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    $this->config = $config_factory->get('conil_inventrip.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Function in which we create the form and assign the required information to it.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['question'] = [
      '#type' => 'item',
      '#title' => $this->t('¿Do you want to import POI from Inventrip?'),
    ];

    // Code for confirm button.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Function to get form id.
   */
  public function getFormId() {
    return 'conil_inventrip_form_importpoi';
  }

  /**
   * Function vaidate of the form, in which we validate the extensions of the files upload to kwnow if they are allowed .
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Function for submit, in which we call the batch files funstions to upload the files.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $url = "https://api.inventrip.com/v100/pois?api_key=conil%25%26%2A20220422&tourist_destination=conil";
    $base_url = $this->config->get('base_url');
    $url = rtrim($base_url, "/");
    $url .= "?api_key=" . $this->config->get('api_key');
    $url .= "&tourist_destination=" . $this->config->get('tourist_destination');
    $content = file_get_contents($url);
    if (!$content) {
      return;
    }
    $decode = json_decode($content, TRUE);

    $batch = [
      'title'            => 'Importing points of interest...',
      'operations'       => [],
      'init_message'     => 'Starting...',
      'progress_message' => 'Processed @current out of @total.',
      'error_message'    => 'An error occurred during processing',
      'finished'         => '\Drupal\conil_inventrip\Form\ImportPOIForm::importFinished',
    ];

    // $array1 = [];
    for ($i = 0; $i < count($decode); $i++) {
      $batch['operations'][] = [
        '\Drupal\conil_inventrip\Form\ImportPOIForm::process',
        [
          $decode[$i],
        ],
      ];
    }
    batch_set($batch);

  }

  // METODS CREATED FOR THE BATCH.

  /**
   * Handle batch completion.
   */
  public static function importFinished($results) {
    \Drupal::messenger()->addMessage('Imported ' . $results['rows_imported'] . ' rows.');
    return 'The import has been completed.';
  }

  /**
   * Method to create media for batch.
   */
  public static function process($object) {

    // Obtener la imagen de portada, insertarla en la BBDD para luego poder referenciarla.
    $imagenes = [];
    // Creamos la conexión a la base de datos.
    $connection = \Drupal::database();
    $config = \Drupal::config('conil_inventrip.settings');
    $base_url = $config->get('image_base_url');
    $mediaStorage = \Drupal::entityTypeManager()->getStorage('media');
    $fileSystem = \Drupal::service('file_system');
    $fileRepository = \Drupal::service('file.repository');
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    for ($i = 0; $i < count($object['image']); $i++) {
      $filename = explode('/', $object['image'][$i]);
      // Insertar media file y luego cargarlo y referenciarlo.
      // $url = "https://api.inventrip.com/v100/image/" . $filename[1] . "?api_key=conil%25%26%2A20220422&image_quality=medium";
      $url = rtrim($base_url, "/");
      $url .= "/" . $filename[1];
      $url .= "?api_key=" . $config->get('api_key');
      $url .= "&image_quality=" . $config->get('image_quality');

      $fichero = file_get_contents($url);
      if (!$fichero) {
        continue;
      }
      $directory = 'public://poi_media';

      $fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
      $uri = $directory . '/' . $filename[1] . '.jpeg';
      $file = $fileRepository->writeData($fichero, $uri, FileSystemInterface::EXISTS_REPLACE);

      // Create media and if it is not inserted in DB, we insert it.
      $medias = $mediaStorage->loadByProperties([
        'bundle' => 'poi_media',
        'field_media_image' => $file->id(),
      ]);
      if (!empty($medias)) {
        $media = reset($medias);
      }
      else {
        $media = Media::create([
          'name'             => $file->getFilename(),
          'bundle'           => 'poi_media',
          'status'           => 1,
          'languaje'         => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          "thumbnail"        => [
            "target_id"      => $file->id(),
            "alt"            => $file->getFilename(),
          ],
          "field_media_image" => [
            "target_id"       => $file->id(),
            "alt"             => $file->getFilename(),
          ],
        ]);
        $media->save();
      }
      array_push($imagenes, $media->id());
    }

    // Obtener todos los títulos en los distintos idiomas y poner el default.
    $titles = [];
    $title_es = "";
    foreach ($object['name'] as $c => $v) {
      array_push($titles, $v);
      // Añadimos un "idioma" extra que será el default, equivaldrá al español.
      if ($v['language'] == "es") {
        array_push($titles, ["value" => $v['value'], 'language' => 'x-default']);
        $title_es = $v['value'];
      }
    }

    // Obtener todas las descripciones en los distintos idiomas y poner el default.
    $descriptions = [];
    $description_es = "";
    foreach ($object['description'] as $c => $v) {
      array_push($descriptions, $v);
      // Añadimos un "idioma" extra que será el default, equivaldrá al español.
      if ($v['language'] == "es") {
        array_push($descriptions, ["value" => $v['value'], 'language' => 'x-default']);
        $description_es = $v['value'];
      }
    }

    $direccion = !empty($object['streetAddress']) ? $object['streetAddress'] : NULL;
    $direccion_extra = !empty($object['extra']['complementAddress']) ? $object['extra']['complementAddress'] : NULL;
    $direccion_cp = !empty($object['postalCode']) ? $object['postalCode'] : NULL;
    $direccion_province = !empty($object['addressProvince']) ? $object['addressProvince'] : NULL;
    $direccion_region = !empty($object['addressRegion']) ? $object['addressRegion'] : NULL;
    $direccion_locality = !empty($object['addressLocality']) ? $object['addressLocality'] : NULL;
    $direccion_country = !empty($object['addressCountry']) ? $object['addressCountry'] : NULL;

    // Categoría.
    $categories = [];
    $subcategories = [];
    if (!empty($object['type'])) {
      foreach(self::TAXRELATIONS as $termCat => $subCats) {
        foreach($subCats as $termSubCat => $invCats) {
          foreach ($object['type'] as $objType) {
            if (in_array($objType, $invCats)) {
              // $terms = $termStorage->loadByProperties(['name' => $termCat, 'vid' => 'categoria_interes']);
              // if (!empty($terms)) {
              //   $term = reset($terms);
              //   $categories[$term->id()] = $term->id();
              // }
              $query = $termStorage->getQuery();
              $query->condition('name', $termSubCat);
              $query->condition('vid', 'categoria_interes');
              $termIds = $query->execute();
              $terms = $termStorage->loadMultiple($termIds);
              // $terms = $termStorage->loadByProperties(['name' => $termSubCat, 'vid' => 'categoria_interes']);
              if (!empty($terms)) {

                $term = reset($terms);
                $subcategories[$term->id()] = $term->id();

                $query = $termStorage->getQuery();
                $query->condition('name', $termCat);
                $query->condition('vid', 'categoria_interes');
                $CatTermIds = $query->execute();
                $CatTerms = $termStorage->loadMultiple($CatTermIds);
                $CatTerm = reset($CatTerms);

                $categories[$CatTerm->id()] = $CatTerm->id();

              }
            }
          }
        }
      }
    }

    // We get tid to reference
    // Para el tipo de turista, añadimos los que no se encuentren guardados, y los importamos y asignamos directamente.
    $touristTypes = [];
    foreach ($object['touristType'] as $valor) {
      $terms = $termStorage->loadByProperties(['name' => $valor, 'vid' => 'tipo_viaje']);
      if (count($terms) == 0) {
        $term = Term::create([
          'vid' => 'tipo_viaje',
          'langcode' => 'en',
          'status' => 1,
          'name' => $valor,
        ]);

        $term->enforceIsNew();
        $term->save();
        $touristTypes[] = $term->id();
      }
      else {
        $term = reset($terms);
        $touristTypes[] = $term->id();
      }
    }

    $nodes = $nodeStorage->loadByProperties(['field_poi_inventrip_identifier' => $object['identifier']]);

    $config2 = \Drupal::config('field.field.node.poi.field_poi_media');
    $uuid = $config2->get('default_value')[0]['target_uuid'];
    if ($imagenes[0] == null) {
      $principal = array_keys($mediaStorage->loadByProperties(['uuid' => $uuid]))[0];
    } else if (count($imagenes) >= 1) {
      $principal = reset($imagenes);
      $imagenes = [];
    } else {
      $imagenes = [];
    }

    // Creamos un array para guardar los parámetros del poi.
    $parametros = [
      'langcode' => 'es',
      'status' => 1,
      'type' => 'poi',
      'title' => $title_es,
      'field_poi_inventrip_identifier' => $object['identifier'],
      'field_poi_metatags' => $object['name'],
      'field_poi_email' => $object['email'],
      'field_poi_webpage' => $object['url'],
      'field_poi_media' => $principal,
      'field_poi_gallery' => $imagenes,
      'field_poi_telephone' => $object['telephone'],
      'body' => $description_es,
      'field_poi_address' => $direccion,
      'field_poi_address_extra' => $direccion_extra,
      'field_poi_address_cp' => $direccion_cp,
      'field_poi_address_region' => $direccion_region,
      'field_poi_address_locality' => $direccion_locality,
      'field_poi_address_province' => $direccion_province,
      'field_poi_address_country' => $direccion_country,
      'field_tipo_viaje' => $touristTypes,
      'field_poi_extratags' => $object['extras'],
      'field_poi_geofield' => "POINT (" . $object['longitude'] . " " . $object['latitude'] . ")",
      'field_poi_category' => $categories,
      'field_poi_subcategoy' => $subcategories,
    ];

    if (empty($nodes)) {
      $node = $nodeStorage->create($parametros);
      $node->save();
      // Añadimos las traducciones para los títulos y los cuerpos.
      for ($i = 0; $i < count($titles); $i++) {
        // Deben coincidir siempre los idiomas, pero por si acaso pongo ésta condición.
        if ($titles[$i]['language'] == $descriptions[$i]['language']) {
          // Añadimos el lenguaje si todo está bien y si no está insertado anteriormente.
          if (1
            && $titles[$i]['value'] != NULL
            && $descriptions[$i]['value'] != NULL
            && $titles[$i]['language'] != 'es'
            && $titles[$i]['language'] != 'pt'
            && $titles[$i]['language'] != 'x-default'
            && !$node->hasTranslation($titles[$i]['language'])
          ) {
            $node->addTranslation(
              $titles[$i]['language'],
              [
                'title' => $titles[$i]['value'],
                'body' => $descriptions[$i]['value'],
                'status' => 1,
              ]
            );
          }
        }
      }
      $node->save();
    }
    else {
      // Update POIS.
      $node = reset($nodes);
      foreach ($parametros as $clave => $valor) {
        $node->{$clave} = $valor;
      }

      $node->save();

      // Añadimos las traducciones para los títulos y los cuerpos.
      for ($i = 0; $i < count($titles); $i++) {
        // Deben coincidir siempre los idiomas, pero por si acaso pongo ésta condición.
        if ($titles[$i]['language'] == $descriptions[$i]['language']) {
          // Añadimos el lenguaje si todo está bien y si no está insertado anteriormente.
          if (1
            && $titles[$i]['value'] != NULL
            && $descriptions[$i]['value'] != NULL
            && $titles[$i]['language'] != 'es'
            && $titles[$i]['language'] != 'pt'
            && $titles[$i]['language'] != 'x-default'
            && !$node->hasTranslation($titles[$i]['language'])
          ) {
            $node->addTranslation($titles[$i]['language'], ['title' => $titles[$i]['value'], 'body' => $descriptions[$i]['value']]);
          }
          elseif ($node->hasTranslation($titles[$i]['language'])) {
            // Aquí actualizamos las traducciones si ya se encuentran traducidas.
            $translation = $node->getTranslation($titles[$i]['language']);
            $translation->set('title', $titles[$i]['value']);
            $translation->set('body', $descriptions[$i]['value']);
            $translation->set('status', 1);
            $translation->save();
          }
        }
      }

      // Guardamos una vez actualizados y añadidas las traducciones.
      $node->save();
    }
  }

}
