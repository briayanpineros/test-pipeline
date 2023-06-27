<?php

namespace Drupal\web_speech_synthesis_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a matter brand block block.
 *
 * @Block(
 *   id = "web_speech_synthesis_block",
 *   admin_label = @Translation("Web Speech Synthesis Block"),
 *   category = @Translation("Web Speech API")
 * )
 */
class WebSpeechSynthesisBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new WebSpeechSynthesisBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $return = parent::defaultConfiguration();
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = $this->config->get('web_speech_synthesis_api.settings');

    if (!empty($settings->get('welcome'))) {

      $build['welcome'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#name' => 'welcome_speech',
        '#value' => $settings->get('welcome'),
        '#attributes' => [
          'class' => [
            $settings->get('class'),
            'welcome-speech',
          ],
        ],
      ];

    }

    $build['container'] =  [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'container-play-speech',
        ],
      ],
    ];

    $build['container']['play'] = [
      '#type' => 'button',
      '#name' => 'btn-play-speech',
      '#value' => $this->t('Play'),
      '#attributes' => [
        'class' => [
          'button',
          'btn-primary',
          'btn-play-speech',
        ],
      ],
      '#weight' => -11,
    ];

    $build[] = [
      '#markup' => '',
      '#attached' => [
        'drupalSettings' => [
          'class' => $settings->get('class'),
          'rate' => $settings->get('rate'),
          'pitch' => $settings->get('pitch'),
          'seconds' => $settings->get('seconds'),
          'selector' => $settings->get('selectorLang'),
        ],
        'library' => [
          'web_speech_synthesis_api/web-speech-synthesis',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
