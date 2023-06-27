<?php

namespace Drupal\conil_totem\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;

/**
 * Plugin implementation of the 'image_media_poi_formatter' formatter.
 *
 * @FieldFormatter(
 * id = "image_media_poi_formatter",
 * module = "conil_totem",
 * label = @Translation("Image preview video formatter"),
 * field_types = {
 *  "file"
 * }
 * )
 */
class ImageMediaPOIFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $entity = $items->getEntity();
      if (isset($entity)) {
        $video_uri = $entity->field_media_video_file->entity->createFileUrl();
        if (isset($entity->field_media_video_preview->entity)) {
          $image_uri = $entity->field_media_video_preview->entity->createFileUrl();
          $html = "<div class='url_video' style='display:none'>" . $video_uri . "</div><img class='popupVideo' src='" . $image_uri . "' loading='lazy' typeof='foaf:Image' class='image-style-poi-thumbnail' alt='" . $entity->field_media_video_preview->alt . "'>";
          $elements[$delta] = [
            '#markup' => Markup::create($html),
          ];
        }
      }
    }
    return $elements;
  }

}
