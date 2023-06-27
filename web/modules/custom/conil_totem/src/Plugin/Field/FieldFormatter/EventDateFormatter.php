<?php

namespace Drupal\conil_totem\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'events_date_formatter' formatter.
 *
 * @FieldFormatter(
 * id = "events_date_formatter",
 * module = "conil_totem",
 * label = @Translation("Events date formatter"),
 * field_types = {
 *  "datetime"
 * }
 * )
 */
class EventDateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $date = explode("-", $item->value);
      $elements[$delta] = [
        '#markup' => '<span class="' . $this->getSetting('day_class') . '">' . $date[2] . '</span><span class="' . $this->getSetting('date_class') . '">' . $this->t(date('M', mktime(0, 0, 0, $date[1], 1))) . ' ' . $date[0] . '</span><span class="' . $this->getSetting('text_class') . '">' . $this->t($this->getSetting('text')) . '</span>',
      ];
      return $elements;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $output['day_class'] = [
      '#title' => $this->t('Day class'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('day_class'),
    ];
    $output['date_class'] = [
      '#title' => $this->t('Date class'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('date_class'),
    ];
    $output['text_class'] = [
      '#title' => $this->t('Text class'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('text_class'),
    ];
    $output['text'] = [
      '#title' => $this->t('Text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('text'),
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'day_class' => "",
      'date_class' => "",
      'text_class' => "",
      'text' => "",
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Day Class: @dayClass', ['@dayClass' => $this->getSetting('day_class')]);
    $summary[] = $this->t('Date Class: @dateClass', ['@dateClass' => $this->getSetting('date_class')]);
    $summary[] = $this->t('Text Class: @textClass', ['@textClass' => $this->getSetting('text_class')]);
    $summary[] = $this->t('Text: @text', ['@text' => $this->getSetting('text')]);
    return $summary;
  }

}
