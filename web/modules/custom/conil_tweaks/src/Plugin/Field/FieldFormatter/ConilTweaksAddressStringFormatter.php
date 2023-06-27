<?php

namespace Drupal\conil_tweaks\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "conil_tweaks_address_string",
 *   label = @Translation("Conil Tweaks: Address String"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class ConilTweaksAddressStringFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $url = NULL;
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityType();

    if ($this->getSetting('link_to_entity') && !$entity->isNew() && $entity_type->hasLinkTemplate('canonical')) {
      $url = $this->getEntityUrl($entity);
    }
    $address = $entity->field_poi_address->value;
    $address .= $entity->field_poi_address_extra->value ? ", " . $entity->field_poi_address_extra->value : NULL;
    $address .= $entity->field_poi_address_cp->value ? ", " . $entity->field_poi_address_cp->value : NULL;
    $address .= $entity->field_poi_address_locality->value ? ", " . $entity->field_poi_address_locality->value : NULL;
    $address .= $entity->field_poi_address_region->value ? ", " . $entity->field_poi_address_region->value : NULL;
    $address .= $entity->field_poi_address_province->value ? ", " . $entity->field_poi_address_province->value : NULL;
    $address .= $entity->field_poi_address_country->value ? ", " . $entity->field_poi_address_country->value : NULL;
    $view_value = $this->viewCustomValue($address);
    if (!empty($address)) {
      if ($url) {
        $elements[0] = [
          '#type' => 'link',
          '#title' => $view_value,
          '#url' => $url,
        ];
      }
      else {
        $elements[0] = $view_value;
      }
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param string $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewCustomValue(string $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => $item],
    ];
  }


}
