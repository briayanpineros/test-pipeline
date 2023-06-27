<?php

namespace Drupal\conil_totem\Controller;

use chillerlan\QRCode\Data\QRMatrix;
use Drupal\Core\Controller\ControllerBase;
use Dompdf\Dompdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Options;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Imagick;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Node routes.
 */
class TemplateGeneratorController extends ControllerBase {

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
   * Constante para el color del QR.
   */
  const QRVALUES = [
    // Finder.
    // Dark (true).
    (QRMatrix::M_FINDER << 8)     => [255, 254, 255],
    // Finder dot, dark (true).
    (QRMatrix::M_FINDER_DOT << 8) => [255, 254, 255],
    QRMatrix::M_FINDER            => [255, 255, 255],
    // Light (false), white is the transparency color and is enabled by default
    // Alignment.
    (QRMatrix::M_ALIGNMENT << 8)  => [255, 254, 255],
    QRMatrix::M_ALIGNMENT         => [255, 255, 255],
    // Darkmodule.
    (QRMatrix::M_DARKMODULE << 8) => [255, 254, 255],
    QRMatrix::M_DARKMODULE => [255, 255, 255],
    // Timing.
    (QRMatrix::M_TIMING << 8)     => [255, 254, 255],
    QRMatrix::M_TIMING            => [255, 255, 255],
    // Format.
    (QRMatrix::M_FORMAT << 8)     => [255, 254, 255],
    QRMatrix::M_FORMAT            => [255, 255, 255],
    // Version.
    (QRMatrix::M_VERSION << 8)    => [255, 254, 255],
    QRMatrix::M_VERSION           => [255, 255, 255],
    // Data.
    (QRMatrix::M_DATA << 8)       => [255, 254, 255],
    QRMatrix::M_DATA              => [255, 255, 255],
    // Darkmodule.
    QRMatrix::M_LOGO              => [255, 255, 255],
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RendererInterface $renderer,
    FileSystemInterface $file_system,
    EntityTypeManager $entity_type_manager,
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list) {
    $this->renderer = $renderer;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );
  }

  /**
   * The load from WS.
   *
   * @param string $id
   *   The code of the entity.
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   The preloaded entity or FALSE.
   */
  protected function loadLocal($id) {
    $entity = $this->entityTypeManager->getStorage('node')->load($id);
    if (!empty($entity)) {
      return $entity;
    }
    return FALSE;
  }

  /**
   * Prints the page as PDF.
   */
  public function printPdfEvent($id, $mode = 'download') {
    if (!empty($id)) {

      $entity = $this->loadLocal($id);

      // Fivestars.
      $stars_data = [];
      for ($i = 1; $i <= 5; $i++) {
        $star_value = ceil((100 / 5) * $i);
        $prev_star_value = ceil((100 / 5) * ($i - 1));

        $stars_data[$i] = [
          'star_value' => $star_value,
          'percent' => null,
        ];

        if ($entity->field_fivestart->rating < $star_value && $entity->field_fivestart->rating > $prev_star_value) {
          $stars_data[$i]['percent'] = (($entity->field_fivestart->rating - $prev_star_value) / ($star_value - $prev_star_value)) * 100;
        }
      }

      $stars = [
        'rating' => $entity->field_fivestart->rating,
        'stars' => 5,
        'vote_type' => 'stars',
        'widget' => 'default',
        'numeric_rating' => $entity->field_fivestart->rating / (100 / 5),
        'stars_data' => $stars_data,
      ];

      $voteResult = \Drupal::service('fivestar.vote_result_manager')->getResults($entity);

      // Image cover.
      $mediaEntity = $this->entityTypeManager->getStorage('media')->load($entity->field_agenda_cover->entity->id());

      /** @var \Drupal\File\FileInterface $imageEntity */
      $imageEntity = $this->entityTypeManager->getStorage('file')->load($mediaEntity->field_media_image->entity->id());

      $data = [
        'title' => $entity->title->value,
        'body' => strip_tags($entity->body->value),
        'fecha_inicio' => $entity->field_agenda_inicio_fecha->value,
        'fecha_fin' => $entity->field_agenda_fin_fecha->value,
        'imagen' => 'data:image/png;base64,' . base64_encode(file_get_contents(\Drupal::service('file_url_generator')->generateAbsoluteString($imageEntity->getFileUri()))),
      ];

      $directory = $this->config('system.file')->get('default_scheme') . '://pdfs-print';
      $this->fileSystem->prepareDirectory($directory, $this->fileSystem::CREATE_DIRECTORY);

      $filepath = $this->fileSystem->realpath($this->config('system.file')->get('default_scheme') . '://pdfs-print');
      $css = file_get_contents($this->moduleExtensionList->getPath('conil_totem') . '/css/pdf_events.css');
      $conil_logo = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->themeExtensionList->getPath('conil_theme') . '/imagenes/CONIL_LOGO.png'));

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

      // QR Image.
      $options = new QROptions([
        'imageTransparent' => TRUE,
        'moduleValues' => $this::QRVALUES,
      ]);
      $qr = (new QRCode($options))->render($entity->toUrl()->setAbsolute()->toString());

      $html_render = [
        '#theme' => 'template_totem_pdf_events',
        '#code' => $id,
        '#css' => $css,
        '#conil_logo' => $conil_logo,
        '#data' => $data,
        '#qr' => $qr,
        '#stars' => $stars,
        '#votes' => $voteResult,
      ];
      $html = $this->renderer->render($html_render);

      $filename = 'template-' . $id . '.pdf';
      file_put_contents($filepath . '/template-' . $id . '.html', $html);

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
      $dompdf->setPaper([0, 0, 1080, 1920]);
      $dompdf->render();
      $uri = $filepath . '/' . $filename;
      $return = NULL;
      switch ($mode) {
        case 'download':
          // Save PDF.
          file_put_contents($uri, $dompdf->output());
          /*$headers = [
            'Content-Type'     => 'application/pdf',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
          ];
          $return = new BinaryFileResponse($uri, 200, $headers, TRUE);*/

          // Create PNG from PDF.
          $imagick = new Imagick();
          $imagick->readImage($uri);
          $imagick->setImageFormat('png');

          $return = new Response();
          $return->headers->set('Content-Type', 'application/png');
          $return->headers->set('Content-Disposition', 'attachment;filename="' . $id . '.png"');
          $return->sendHeaders();
          $return->setContent($imagick->getImageBlob());

          break;

        case 'content':
          $return = $dompdf->output();
          break;

        case 'file':
          file_put_contents($uri, $dompdf->output());
          $return = $uri;
      }
      return $return;
    }
  }

  /**
   * Prints the page as PDF.
   */
  public function printPdfPoi($id, $mode = 'download') {
    if (!empty($id)) {

      $entity = $this->loadLocal($id);

      $data = [
        'title' => $entity->title->value,
        'body' => strip_tags($entity->body->value),
      ];

      // Image cover.
      if (!$entity->field_poi_media->isEmpty) {
        $mediaEntity = $this->entityTypeManager->getStorage('media')->load($entity->field_poi_media->entity->id());
        /** @var \Drupal\File\FileInterface $imageEntity */
        $imageEntity = $this->entityTypeManager->getStorage('file')->load($mediaEntity->field_media_image->entity->id());
        $data += [
          'cover' => 'data:image/png;base64,' . base64_encode(file_get_contents(\Drupal::service('file_url_generator')->generateAbsoluteString($imageEntity->getFileUri()))),
        ];
      }

      $image_gradient = file_get_contents($this->moduleExtensionList->getPath('conil_totem') . "/css/gradientBase64.dat");
      $empty_image = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
      // Gallery.
      $data['images'] = [
        1 => $image_gradient,
        2 => $empty_image,
        3 => $empty_image,
        4 => $empty_image,
      ];

      if (!$entity->field_poi_gallery->isEmpty()) {
        $i = 1;
        foreach ($entity->field_poi_gallery->getValue() as $images) {
          if ($i == 5) {
            break;
          }
          $css_cover = file_get_contents($this->moduleExtensionList->getPath('conil_totem') . "/css/cover_pdf_poi_" . $i . ".css");
          $mediaGallery = $this->entityTypeManager->getStorage('media')->load($images['target_id']);
          /** @var \Drupal\File\FileInterface $galleryEntity */
          $galleryEntity = $this->entityTypeManager->getStorage('file')->load($mediaGallery->field_media_image->entity->id());
          $data['images'][$i] = 'data:image/png;base64,' . base64_encode(file_get_contents(\Drupal::service('file_url_generator')->generateAbsoluteString($galleryEntity->getFileUri())));
          $i++;
        }
      }

      $directory = $this->config('system.file')->get('default_scheme') . '://pdfs-print';
      $this->fileSystem->prepareDirectory($directory, $this->fileSystem::CREATE_DIRECTORY);

      $filepath = $this->fileSystem->realpath($this->config('system.file')->get('default_scheme') . '://pdfs-print');
      $css = file_get_contents($this->moduleExtensionList->getPath('conil_totem') . '/css/pdf_poi.css');
      $conil_logo = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->themeExtensionList->getPath('conil_theme') . '/imagenes/CONIL_LOGO.png'));

      // Get all file names.
      $files = glob($filepath . '/*.pdf');
      foreach ($files as $file) {
        // Delete file.
        if (filectime($file) < (time() - 3600)) {
          unlink($file);
        }
      }
      // Get all file names.
      $files = glob($filepath . '/*.html');
      foreach ($files as $file) {
        // Delete file.
        if (filectime($file) < (time() - 3600)) {
          unlink($file);
        }
      }

      // QR Image.
      $options = new QROptions([
        'imageTransparent' => TRUE,
        'moduleValues' => $this::QRVALUES,
      ]);
      $qr = (new QRCode($options))->render($entity->toUrl()->setAbsolute()->toString());

      $html_render = [
        '#theme' => 'template_totem_pdf_poi',
        '#code' => $id,
        '#css_cover' => $css_cover,
        '#css' => $css,
        '#conil_logo' => $conil_logo,
        '#data' => $data,
        '#qr' => $qr,
      ];
      $html = $this->renderer->render($html_render);

      $filename = 'template-' . $id . '.pdf';
      file_put_contents($filepath . '/template-' . $id . '.html', $html);

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
      $dompdf->setPaper([0, 0, 1080, 1920]);
      $dompdf->render();
      $uri = $filepath . '/' . $filename;
      $return = NULL;
      switch ($mode) {
        case 'download':
          // Save PDF.
          file_put_contents($uri, $dompdf->output());
          /*$headers = [
            'Content-Type'     => 'application/pdf',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
          ];
          $return = new BinaryFileResponse($uri, 200, $headers, TRUE);*/

          // Create PNG from PDF.
          $imagick = new Imagick();
          $imagick->readImage($uri);
          $imagick->setImageFormat('png');

          $return = new Response();
          $return->headers->set('Content-Type', 'application/png');
          $return->headers->set('Content-Disposition', 'attachment;filename="' . $id . '.png"');
          $return->sendHeaders();
          $return->setContent($imagick->getImageBlob());

          break;

        case 'content':
          $return = $dompdf->output();
          break;

        case 'file':
          file_put_contents($uri, $dompdf->output());
          $return = $uri;
      }
      return $return;
    }
  }

}
