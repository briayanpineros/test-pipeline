<?php

namespace Drupal\conil_tweaks\Plugin\views\field;

use Drupal\Core\Template\Attribute;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\fivestar\Element\Fivestar;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("average_stars_counter")
 */
class AverageStarsCounter extends FieldPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // First check whether the field should be hidden if the value(hide_alter_empty = TRUE) /the rewrite is empty (hide_alter_empty = FALSE).
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($values->_entity->bundle()== 'agenda') {
      $query = \Drupal::database()->select('votingapi_vote', 'vapi');
      $query->addField('vapi', 'value');
      $query->condition('vapi.entity_type', 'node');
      $query->condition('vapi.entity_id', $values->_entity->id());
      $results = $query->execute()->fetchCol();
    }
    else {
      $query = \Drupal::database()->select('votingapi_vote', 'vapi');
      $query->addField('vapi', 'value');
      $query->join('comment_field_data', 'cfd', 'vapi.entity_id=cfd.cid');
      $query->condition('vapi.entity_type', 'comment');
      $query->condition('cfd.entity_type', $values->_entity->getEntityType()
        ->id());
      $query->condition('cfd.entity_id', $values->_entity->id());
      $results = $query->execute()->fetchCol();
    }

    if (!empty($results)) {
      $total = 0;
      foreach ($results as $key => $val) {
        $total += $val;
      }

      $rating = intval(round(($total / 10) / count($results)) * 10);
    }
    else {
      $rating = 0;
    }

    $class[] = 'clearfix';

    $renderer = \Drupal::service('renderer');
    $static_stars = [
      '#theme' => 'fivestar_static',
      '#rating' => $rating,
      '#stars' => 5,
      '#vote_type' => 'stars',
      '#widget' => [
        'name' => "basic",
        'text_format' => "average",
        'display_format' => "average",
        'fivestar_widget' => "basic",
      ],
    ];

    $element_static = [
      '#theme' => 'fivestar_static_element',
      '#star_display' => $renderer->render($static_stars),
      '#title' => '',
      '#description' => '',
    ];

    $element['vote_statistic'] = [
      '#type' => 'markup',
      '#markup' => $renderer->render($element_static),
    ];


    $class[] = "fivestar-average-text";
    $class[] = 'fivestar-average-stars';
    $class[] = 'fivestar-form-item';
    $class[] = 'fivestar-basic';

    $element['#attached']['library'][] = \Drupal::service('fivestar.widget_manager')
      ->getWidgetLibrary('basic');

    $element['#prefix'] = '<div ' . new Attribute(['class' => $class]) . '>';
    $element['#suffix'] = '<div class="average-stars-field-counter">(<strong>' . count($results) . '</strong>) ' . $this->t('ratings') . '</div></div>';

    $element['#attached']['library'][] = 'fivestar/fivestar.base';

    return $element;
  }

}