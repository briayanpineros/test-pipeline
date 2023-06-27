<?php

namespace Drupal\conil_polls;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\Render\PlainTextOutput;

/**
 * Defines a form that configures forms module settings.
 */
class ConilPollsMailHelper implements ConilPollsMailHelperInterface {

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   THe module handler.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    LoggerInterface $logger,
    Token $token
  ) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->logger = $logger;
    $this->token = $token;
  }

  /**
   * Send mail.
   */
  public function send($key, $to, $langcode = NULL, $params = [], $custom_replacements = []) {

    // Token options.
    $variables = !empty($params['token_variables']) ? $params['token_variables'] : [];
    $token_options = [
      'langcode' => $langcode,
      'callback' => NULL,
      'clear' => TRUE,
    ];

    // We must override the current language in the configuration to send the
    // mail in the correct language.
    $language = $this->languageManager->getLanguage($langcode);
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($language);

    $config = $this->configFactory->get('conil_polls.draft_mail_settings');
    $subject = $config->get($key . '.subject');
    $body = $config->get($key . '.body');
    // Token replacement.
    $params['title'] = PlainTextOutput::renderFromHtml($this->token->replace($subject, $variables, $token_options));
    $params['message'] = $this->token->replace($body, $variables, $token_options);
    if (!empty($custom_replacements)) {
      $params['message'] = str_replace(array_keys($custom_replacements), array_values($custom_replacements), $params['message']);
    }
    $langcode = ($langcode) ?: $this->languageManager->getCurrentLanguage();
    $result = $this->mailManager->mail('conil_polls', $key, $to, $langcode, $params, NULL, TRUE);
    if (!$result) {
      $this->logger->error('Error sending mail with key %key', [
        '%key' => $key,
      ]);
    }

    // Set the current language after the mail has been sent.
    $this->languageManager->setConfigOverrideLanguage($original_language);
  }

}
