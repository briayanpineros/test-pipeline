<?php

namespace Drupal\conil_weather\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @Block(
 *   id = "conil_weather_block",
 *   admin_label = @Translation("Conil Weather Block"),
 *   category = @Translation("Block")
 * )
 */
class ConilWeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a ConilWeatherBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->getClima(),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  private function getClima() {
    $config = $this->configuration;

    $api_city_id = $config['api_city_id'];
    $api_key = $config['api_key'];
    $api_unit = $config['api_unit'];

    if ($config['api_lang'] == 'auto'){
      $api_lang = $this->currentUser->getPreferredLangcode(TRUE);
    }else{
      $api_lang = $config['api_lang'];
    }

    $client = new Client([
      'base_uri' => 'https://api.openweathermap.org/data/2.5/'
    ]);

    try {
      $response = $client->request('GET', 'weather', [
        'query' => [
          'id' => $api_city_id,
          'appid' => $api_key,
          'units' => $api_unit,
          'lang' => $api_lang
        ]
      ]);
    }catch (GuzzleException $e) {
      \Drupal::logger('Conil Weather')->error($e);
      return $this->t("Can't obtain weather information.");
    }

    $unit = '';

    if ($api_unit === "metric"){
      $unit = "ºC";
    }elseif ($api_unit === "standard"){
      $unit = "ºK";
    }elseif ($api_unit === "imperial"){
      $unit = "ºF";
    }

    $decode = Json::decode($response->getBody()->getContents());

    $temp = round($decode['main']['temp']);
    $currentWeather = ucwords($decode['weather'][0]['description']);

    $icon = "https://openweathermap.org/img/wn/" . $decode['weather'][0]['icon'] . "@4x.png";

//    return $this->t("Temperature") . ": $temp $unit <br>" . $this->t("Weather") . ": $currentWeather";
    return "<div class='conil-weather-block'><span id='conil-weather-temperature'>$temp$unit</span><img id='conil-weather-icon' src='$icon' alt='$currentWeather'></div>";
  }

  /**
   * {@inheritDoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form['api_city_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City ID'),
      '#default_value' => '2519289',
      '#disabled' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config['api_key'],
    ];

    $form['api_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Units of measurement'),
      '#options' => [
        'standard' => $this->t('Kelvin'),
        'metric' => $this->t('Celsius'),
        'imperial' => $this->t('Fahrenheit')
      ],
      '#default_value' => $config['api_unit'],
    ];

    $form['api_lang'] = [
      '#type' => 'select',
      '#title' => $this->t('API Langcode'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'es' => $this->t('Spanish'),
        'en' => $this->t('English'),
        'it' => $this->t('Italian'),
        'de' => $this->t('German'),
        'fr' => $this->t('French'),
      ],
      '#default_value' => $config['api_lang'],
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value){
      $this->configuration[$key] = $value;
    }
  }

}
