<?php

/**
 * @file
 * Contains saved_searches.page.inc.
 *
 * Page callback for Saved Searches entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Saved Searches templates.
 *
 * Default template: saved_searches.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_saved_searches(array &$variables) {
  // Fetch SavedSearches Entity Object.
  $saved_searches = $variables['elements']['#saved_searches'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
