<?php

/**
 * @file
 * Contains totem.page.inc.
 *
 * Page callback for Totem entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Totem templates.
 *
 * Default template: totem.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_totem(array &$variables) {
  // Fetch Totem Entity Object.
  $totem = $variables['elements']['#totem'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
