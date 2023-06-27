<?php

namespace Drupal\conil_bulk_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the Simple form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class MultimediaForm extends FormBase {

  /**
   * Function in which we define classes for the form.
   */
  public function __construct($entityBundleStorage, $currentUser, $entityFieldManager, $messenger) {
    $this->entityBundleStorage = $entityBundleStorage;
    $this->currentUser = $currentUser;
    $this->entityFieldManager = $entityFieldManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('messenger')
    );
  }


  /**
   * Function in which we create the form and assign the required information to it.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // We take all multimedia types to make the radio list.
    $bundleInfoMedia = $this->entityBundleStorage->getBundleInfo('media');

    $options = [];
    foreach ($bundleInfoMedia as $key => $value) {
      $options[$key] = $value['label'];
    }

    $form['#prefix'] = '<div id="reemplazar-todo">';
    $form['#suffix'] = '</div>';

    $form['multimedia_types'] = [
      '#type' => 'radios',
      '#title' => $this->t('Multimedia types'),
      '#options' => $options,
      '#description' => $this->t('Choose a multimedia type.'),
      '#required' => TRUE,
      '#ajax' => [
    // don't forget :: when calling a class method.
        'callback' => '::myAjaxCallback',
        // 'callback' => [$this, 'myAjaxCallback'], //alternative notation
    // Or TRUE to prevent re-focusing on the triggering element.
        // 'disable-refocus' => FALSE,
        // 'event' => 'change',
    // This element is updated with this AJAX callback.
        'wrapper' => 'reemplazar-todo',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
    ];

    // dpm($file_directory);
    // Upload the form to add validate allow extensions.
    if (!empty($form_state->getValue('multimedia_types'))) {
      // Get for the multimedia type selected the upload location and allow file extensions.
      $multimedia_type = $form_state->getValue('multimedia_types');

      $media = $this->entityFieldManager->getFieldDefinitions('media', $multimedia_type);

      // This allow us to reach the clases of every multimedia type.
      $keys = array_keys($media);
      $notallow = [
        "mid", "uuid", "vid", "langcode", "bundle", "revision_created", "revision_user", "revision_log_message", "status", "uid", "name", "thumbnail",
        "created", "changed", "default_langcode", "revision_default", "revision_translation_affected", "path",
      ];

      // Loop to get the needed value.
      foreach ($keys as $key => $value) {
        if (!in_array($value, $notallow) && !empty($media[$value]->getSettings()['file_extensions'])) {
          $field = $value;
          // Save new upload location information.
          $form_state->set('field', $field);
        }
      }

      // Access to the directory to save the files
      // for the multimedia type selected.
      $file_directory = $media[$form_state->get('field')]->getSettings()['file_directory'];
      $uri_schema = $media[$form_state->get('field')]->get("fieldStorage")->get("settings")["uri_scheme"];
      $upload_location = $uri_schema . "://" . \Drupal::token()->replace($file_directory);

      // We save the media_field to access the field type
      // for the files that are images, that have another attributes.
      $form_state->set('media_field', $media[$form_state->get('field')]);

      // Access to the allow file extensions.
      $extensions = $media[$form_state->get('field')]->getSettings()['file_extensions'];

      // If for the file extensions and control if
      // is not selected any multimedia type.
      $allow_extensions = !empty($extensions) ? $extensions : [];
      $form_state->set('extensions', $allow_extensions);

      // If for the upload location and control if
      // is not selected any multimedia type.
      $file_directory = !empty($upload_location) ? $upload_location : 'public://';
      $form_state->set('file_directory', $file_directory);
    }

    // If the type of multimedia selected is image, load this.
    // If it is not, load the other.
    if (!empty($media[$form_state->get('field')]) && $media[$form_state->get('field')]->get('field_type') == "image") {
      $form['file_upload'] = [
        '#type' => 'managed_file',
        '#prefix' => '<div id="file-upload-wrapper">',
        '#sufix' => '</div>',
        '#title' => $this->t('Upload a file'),
        '#required' => TRUE,
        '#multiple' => TRUE,
        '#upload_validators' => [
          'file_validate_extensions' => [$allow_extensions],
        ],
        '#upload_location' => "private://media",
      ];
    }
    else {
      $form['file_upload'] = [
        '#type' => 'managed_file',
        '#prefix' => '<div id="file-upload-wrapper">',
        '#sufix' => '</div>',
        '#title' => $this->t('Upload a file'),
        '#required' => TRUE,
        '#multiple' => TRUE,
        '#upload_validators' => [
          'file_validate_extensions' => [$allow_extensions],
        ],
        '#upload_location' => $form_state->get('file_directory'),
      ];
    }

    //Ésta es una función de prueba, que actualiza el campo managed files cuando cambias de selección de tipo
    //$form['file_upload'] = $this->form($form, $form_state);

    // Code for submit button.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Function to get form id.
   */
  public function getFormId() {
    return 'conil_bulk_upload_Form_MultimediaForm';
  }

  /*
   * Method created for the possibles selection's changes in the form .
   */
  // Get the value from example select field and fill.

  /**
   * The textbox with the selected text.
   */
  public function myAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  // METODS CREATED FOR THE BATCH.

  /**
   * Handle batch completion.
   */
  public function importFinished($results) {
    $this->messenger->addMessage('Imported ' . $results['rows_imported'] . ' rows.');

    /*
    Añadimos aquí cualquier ejecución final que necesitemos.
     */

    return 'The import has been completed.';
  }

  /**
   * Function for batch method.
   */
  public function batchFiles($fids, $media_type, $field, $field_type) {
    // Creamos el batch.
    $batch = [
      'title'            => 'Importing files...',
      'operations'       => [],
      'init_message'     => 'Starting...',
      'progress_message' => 'Processed @current out of @total.',
      'error_message'    => 'An error occurred during processing',
      'finished'         => '\Drupal\conil_bulk_upload\Form\MultimediaForm::importFinished',
    ];

    // Realizamos la accion de guardar para cada fileid. (Para cada documento)
    foreach ($fids as $fid) {

      $batch['operations'][] = [
        '\Drupal\conil_bulk_upload\Form\MultimediaForm::createMediaBatch',
        [
          $fid,
          $media_type,
          $this->currentUser->id(),
          $field,
          $field_type,
        ],
      ];
    }

    // Save the batch.
    batch_set($batch);

    // Return the number of files to show in the final message.
    return count($fids);
  }

  /**
   * Method to create media for batch.
   */
  public static function createMediaBatch($fid, $media_type, $uid, $field, $field_type) {
    // With the file id, we get de file and then the name of the file.
    $file = File::load($fid);
    $file->setPermanent();

    // Create media and save it.
    if ($field_type == "image") {
      $media = Media::create([
        'name'             => $file->getFilename(),
        'bundle'           => $media_type,
        'uid'              => $uid,
        'status'           => TRUE,
        $field => [
          'target_id'      => $fid,
          'alt'            => $file->getFilename(),
        ],
      ]);
    }
    else {
      $media = Media::create([
        'name'             => $file->getFilename(),
        'bundle'           => $media_type,
        'uid'              => $uid,
        'status'           => TRUE,
        $field => [
          'target_id'      => $fid,
        ],
      ]);
    }

    $media->save();
  }

  /**
   * Function vaidate of the form, in which we validate the extensions of the files upload to kwnow if they are allowed .
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()["#name"] != "file_upload_remove_button") {

      // Get fileid from the file upload.
      $fid = $form_state->getValue('file_upload');
      if (!empty($fid)) {
        // For to check if the selected files have the correct extensions.
        foreach ($fid as $key => $value) {
          // We take the file selected.
          $file = File::load($value);

          // We get the extension of the file.
          $extension = substr($file->getFileUri(), strrpos($file->getFileUri(), ".") + 1);

          // To control possible errors.
          $extensions = explode(" ", $form_state->get('extensions'));
          if (!in_array($extension, $extensions)) {
            $form_state->setErrorByName('file_upload', $this->t("The extension does not match with the extensions allow ({$form_state->get('extensions')})"));
          }else{
          }
        }
      }
    }
  }

  /**
   * Function for submit, in which we call the batch files funstions to upload the files.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the multimedia type to upload.
    $media_type = $form_state->getValue('multimedia_types');

    // Get the id of the file upload.
    $fids = $form_state->getValue('file_upload');

    // If when file id is not empty.
    if (!empty($fids)) {
      $rows = $this->batchFiles($fids, $media_type, $form_state->get('field'), $form_state->get('media_field')->get('field_type'));

      // Message when files are imported correctly.
      $this->messenger->addMessage('Imported ' . $rows . ' files.');
    }

    // Message when all is okay.
    $this->messenger->addStatus($this->t('The form has been submitted correctly'));
  }

}
