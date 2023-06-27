<?php

namespace Drupal\conil_totem\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\conil_totem\Entity\TotemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
/**
 * Class TotemController.
 *
 *  Returns responses for Totem routes.
 */
class TotemController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Totem revision.
   *
   * @param int $totem_revision
   *   The Totem revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($totem_revision) {
    $totem = $this->entityTypeManager()->getStorage('totem')
      ->loadRevision($totem_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('totem');

    return $view_builder->view($totem);
  }

  /**
   * Page title callback for a Totem revision.
   *
   * @param int $totem_revision
   *   The Totem revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($totem_revision) {
    $totem = $this->entityTypeManager()->getStorage('totem')
      ->loadRevision($totem_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $totem->label(),
      '%date' => $this->dateFormatter->format($totem->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Totem.
   *
   * @param \Drupal\conil_totem\Entity\TotemInterface $totem
   *   A Totem object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(TotemInterface $totem) {
    $account = $this->currentUser();
    $totem_storage = $this->entityTypeManager()->getStorage('totem');

    $langcode = $totem->language()->getId();
    $langname = $totem->language()->getName();
    $languages = $totem->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $totem->label()]) : $this->t('Revisions for %title', ['%title' => $totem->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all totem revisions") || $account->hasPermission('administer totem entities')));
    $delete_permission = (($account->hasPermission("delete all totem revisions") || $account->hasPermission('administer totem entities')));

    $rows = [];

    $vids = $totem_storage->revisionIds($totem);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\conil_totem\TotemInterface $revision */
      $revision = $totem_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $totem->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.totem.revision', [
            'totem' => $totem->id(),
            'totem_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $totem->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.totem.translation_revert', [
                'totem' => $totem->id(),
                'totem_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.totem.revision_revert', [
                'totem' => $totem->id(),
                'totem_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.totem.revision_delete', [
                'totem' => $totem->id(),
                'totem_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['totem_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
