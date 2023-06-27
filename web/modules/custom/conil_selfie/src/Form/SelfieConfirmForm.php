<?php

namespace Drupal\conil_selfie\Form;

use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Imagick;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class SelfieConfirmForm extends ConfirmFormBase implements ContainerInjectionInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The TeacherContactController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder,
    RendererInterface $renderer,
    FileSystemInterface $file_system,
    EntityTypeManager $entity_type_manager,
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list) {
    $this->formBuilder = $formBuilder;
    $this->renderer = $renderer;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('renderer'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_selfie_selfie_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you like the resulting selfie?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('conil_selfie.selfie');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '<p class="first-description">' . $this->t('2. Preview of your postcard.') . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $selfie = NULL, $background = NULL) {
    $form = parent::buildForm($form, $form_state);

    // Call to template.
    $this->printPdfSelfie($selfie, $background);
    $filepath = \Drupal::service('file_system')->realpath('temporary://selfies/');

    $form['container'] = [
      '#type' => 'container',
      '#title' => $this->t('Result'),
    ];

    if (!empty($selfie)) {
      $src = 'data:image/png;base64,' . base64_encode(file_get_contents($filepath . '/' . urldecode($selfie) . ".png"));
    }
    else {
      $src = 'data:image/png;base64,' . base64_encode(file_get_contents($filepath . '/' . $background . ".png"));
    }
    $form['selfie_result'] = [
      '#type' => 'html_tag',
      '#attributes' => [
        'id' => 'selfie_result',
        'src' => $src,
      ],
      '#tag' => 'img',
    ];

    $form['actions']['send'] = [
      '#type' => 'link',
      '#title' => $this->t('Send'),
      '#url' => Url::fromRoute('conil_selfie.selfie_send_email', ["selfie" => $selfie, "background" => $background]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'btn',
          'btn-primary',
        ],
      ],
    ];

    $form['actions']['submit'] = NULL;

    // Attach the library for pop-up dialogs/modals.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @DCG Place your code here.
    // $this->messenger()->addStatus($this->t('Done!'));
  }

  /**
   * Prints the page as PDF.
   */
  public function printPdfSelfie($selfie, $background) {
    if ($background == 'api') {
      return FALSE;
    }
    $filepathTemp = \Drupal::service('file_system')->realpath('temporary://selfies/');
    $this->fileSystem->prepareDirectory($filepathTemp, $this->fileSystem::CREATE_DIRECTORY);

    if (!empty($selfie)) {
      $selfieData = 'data:image/png;base64,' . base64_encode(file_get_contents($filepathTemp . '/' . $selfie));
    }
    else {
      $selfie = $background;
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

          $selfieData = 'data:image/png;base64,' . base64_encode(file_get_contents($this->entityTypeManager
            ->getStorage('image_style')
            ->load('wide')
            ->buildUri($backgroundEntity->field_media_image->entity->getFileUri())));

        }
      }
    }

    // Image cover.
    $mediaEntity = $this->entityTypeManager->getStorage('media')->load($background);
    $text = $mediaEntity->field_description->value;

    $directory = $this->config('system.file')->get('default_scheme') . '://pdfs-print';
    $this->fileSystem->prepareDirectory($directory, $this->fileSystem::CREATE_DIRECTORY);

    $filepath = $this->fileSystem->realpath($this->config('system.file')->get('default_scheme') . '://pdfs-print');
    $css = file_get_contents($this->moduleExtensionList->getPath('conil_selfie') . '/css/template-selfie.css');
    $marco = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_selfie') . '/images/selfie_marco.png'));

    // Get all file names.
    $files = glob($filepath . '/*.pdf');
    foreach ($files as $file) {
      // Delete file.
      if ($file->created < (time() - 3600)) {
        unlink($file);
      }
    }
    // Get all file names.
    $files = glob($filepath . '/*.html');
    foreach ($files as $file) {
      // Delete file.
      if ($file->created < (time() - 3600)) {
        unlink($file);
      }
    }

    $html_render = [
      '#theme' => 'template_selfie',
      '#css' => $css,
      '#text' => $text,
      '#marco' => $marco,
      '#selfie' => $selfieData,
    ];

    $html = $this->renderer->render($html_render);

    $filename = 'template-' . $selfie . '.pdf';
    file_put_contents($filepath . '/template-' . $selfie . '.html', $html);

    $options = new Options();
    $options->set('enable_css_float', TRUE);
    $options->set('enable_html5_parser', TRUE);
    $options->set('enable_remote', TRUE);
    $options->set('defaultFont', 'sans-serif');
    $options->set('defaultMediaType', 'all');
    $options->set('isFontSubsettingEnabled', TRUE);
    $options->set('dpi', 72);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper([0, 0, 834, 1076]);
    $dompdf->render();
    $uri = $filepath . '/' . $filename;

    // Save PDF.
    file_put_contents($uri, $dompdf->output());

    // Create PNG from PDF.
    $imagick = new Imagick();
    $imagick->readImage($uri);
    $imagick->setImageFormat('png');
    $imagick->writeImage($filepathTemp . '/' . $selfie . ".png");
    file_put_contents('test2.png', $imagick);

  }

}
