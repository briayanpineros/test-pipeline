<?php

namespace Drupal\conil_weather;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Dompdf\Dompdf;
use Dompdf\Options;
use Imagick;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class ConilWeather.
 */
class ConilWeather implements ConilWeatherInterface {

  use StringTranslationTrait;

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
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list,
    ClientFactory $http_client_factory,
    LoggerChannelFactory $logger,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
  ) {
    $this->renderer = $renderer;
    $this->fileSystem = $file_system;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
    $this->httpClientFactory = $http_client_factory;
    $this->logger = $logger->get('conil_weather');
    $this->config = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Get the current weather.
   */
  public function getWeather() {

    $configWeather = $this->config->get('conil_weather.settings');
    if (empty($configWeather)) {
      if ($configWeather->get('api_lang') == 'auto') {
        $api_lang = $this->currentUser->getPreferredLangcode(TRUE);
      }
      else {
        $api_lang = $configWeather->get('api_lang');
      }
    }

    $url = 'https://api.tutiempo.net/json/?lan=' . $api_lang . '&apid=' . $configWeather->get('api_key') . '&lid=' . $configWeather->get('api_city_id');
    $response = json_decode(file_get_contents($url), TRUE);

    if (isset($response['error']) || $response == NULL) {
      \Drupal::logger('Conil Weather')->error($this->t("Can't obtain weather information."));
      return FALSE;
    }
    else {
      return $response;
    }

  }

  /**
   * Prints the page as PDF.
   */
  public function printPdfWeather() {
    $datos = $this->getWeather();
    if ($datos != FALSE) {

      $directory = $this->config->get('system.file')->get('default_scheme') . '://pdfs-print';
      $this->fileSystem->prepareDirectory($directory, $this->fileSystem::CREATE_DIRECTORY);

      $filepath = $this->fileSystem->realpath($this->config->get('system.file')->get('default_scheme') . '://pdfs-print');
      $css = file_get_contents($this->moduleExtensionList->getPath('conil_weather') . '/css/pdf_weather.css');
      $conil_logo = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->themeExtensionList->getPath('conil_theme') . '/imagenes/CONIL_LOGO.png'));
      $drop_img = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_weather') . '/img/drop.png'));
      $wind_img = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_weather') . '/img/wind.png'));

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
      $qr = (new QRCode($options))->render('https://turismoconil.es');
      $ruta = DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_weather') . '/img/wi/';
      $ruta2 = DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_weather') . '/img/wd/';
      $background = 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_weather') . '/img/FONDO.jpg'));
      for ($i = 1; $i <= 7; $i++) {
        $datos['day' . $i]['icon'] = 'data:image/png;base64,' . base64_encode(file_get_contents($ruta . $datos['day' . $i]['icon'] . '.png'));
        $datos['day' . $i]['icon_wind'] = 'data:image/png;base64,' . base64_encode(file_get_contents($ruta2 . $datos['day' . $i]['icon_wind'] . '.png'));
      }

      $html_render = [
        '#theme' => 'template_totem_pdf_weather',
        '#css' => $css,
        '#background' => $background,
        '#qr' => $qr,
        '#conil_logo' => $conil_logo,
        '#wind_img' => $wind_img,
        '#drop_img' => $drop_img,
        '#data' => $datos,
      ];
      $html = $this->renderer->render($html_render);

      $filename = 'template-weather.pdf';
      file_put_contents($filepath . '/template-weather.html', $html);

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

      // Save PDF.
      file_put_contents($uri, $dompdf->output());

      // Create PNG from PDF.
      $imagick = new Imagick();
      $imagick->readImage($uri);
      $imagick->setImageFormat('png');
      $imagick->writeImage($filepath . "/weather.png");

      return $return;
    }
  }

}
