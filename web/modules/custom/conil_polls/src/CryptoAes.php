<?php

namespace Drupal\conil_polls;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class RechPackWs.
 */
class CryptoAes implements CryptoAesInterface {

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
   * The current request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new RechPackWs object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Log system.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    LoggerChannelFactory $logger,
    ConfigFactoryInterface $config_factory
  ) {
    $this->logger = $logger->get('conil_polls');
    $this->config = $config_factory->get('conil_polls.draft_mail_settings');
  }

  /**
   * The encrypt method.
   *
   * @param string $params
   *   The params to be encrypted.
   *
   * @return string
   *   The encrypted params.
   */
  public function encrypt($params) {
    $cipher = "AES-256-CBC";
    $prepared_key = openssl_pbkdf2($this->config->get('aes_key'), $this->config->get('aes_salt'), 256, 65536, "sha256");
    $encrypted_data = openssl_encrypt($params, $cipher, $prepared_key, OPENSSL_RAW_DATA, $this->config->get('aes_vector'));
    return $encrypted_data ? $this->base64urlEncode($encrypted_data) : "";
  }

  /**
   * The decrypt method.
   *
   * @param string $params
   *   The params to be decrypted.
   *
   * @return string|null
   *   The decrypted params.
   */
  public function decrypt($params) {
    $message = $this->base64urlDecode($params);
    $cipher = "AES-256-CBC";
    $prepared_key = openssl_pbkdf2($this->config->get('aes_key'), $this->config->get('aes_salt'), 256, 65536, "sha256");
    $decrypted_data = openssl_decrypt($message, $cipher, $prepared_key, OPENSSL_RAW_DATA, $this->config->get('aes_vector'));
    return $decrypted_data ?: NULL;
  }

  /**
   * Base64 encode with url safe.
   *
   * @see https://github.com/firebase/php-jwt/blob/feb0e820b8436873675fd3aca04f3728eb2185cb/src/JWT.php#L350
   */
  protected function base64urlEncode($data) {
    return \str_replace('=', '', \strtr(\base64_encode($data), '+/', '-_'));
  }

  /**
   * Base64 decode with url safe.
   *
   * @see https://github.com/firebase/php-jwt/blob/feb0e820b8436873675fd3aca04f3728eb2185cb/src/JWT.php#L333
   */
  protected function base64urlDecode($data) {
    $remainder = \strlen($data) % 4;
    if ($remainder) {
      $padlen = 4 - $remainder;
      $data .= \str_repeat('=', $padlen);
    }
    return \base64_decode(\strtr($data, '-_', '+/'));
  }

  /**
   * Codify params.
   */
  public function encodeParams($params) {
    $simplified = [];
    foreach ($params as $key => $value) {
      $simplified[] = $key . "::" . $value;
    }
    $text = implode("|", $simplified);
    return $this->encrypt($text);
  }

  /**
   * Codify params.
   */
  public function decodeParams($params) {
    $text = $this->decrypt($params);
    $simplified = explode("|", $text);
    $elements = [];
    foreach ($simplified as $value) {
      $exploded = explode("::", $value);
      $elements[$exploded[0]] = $exploded[1];
    }
    return $elements;
  }

}
