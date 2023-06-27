<?php

namespace Drupal\conil_tweaks\TwigExtension;

use Drupal\Component\Transliteration\PhpTransliteration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TransliterateFilter extends AbstractExtension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new TwigFilter('transliterate', array($this, 'transliterateString')),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'transliterate.twig_extension';
  }

  /**
   * Transliterates a string
   */
  public static function transliterateString($string) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $trans = new PHPTransliteration();
    return $trans->transliterate($string, $langcode);
  }

}
