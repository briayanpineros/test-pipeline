<?php

namespace Drupal\conil_saved_searches\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\conil_saved_searches\Entity\SavedSearchesInterface;

/**
 * Defines the Saved Searches entity.
 *
 * @ingroup conil_saved_searches
 *
 * @ContentEntityType(
 *   id = "saved_searches",
 *   label = @Translation("Saved Searches"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\conil_saved_searches\SavedSearchesListBuilder",
 *     "views_data" = "Drupal\conil_saved_searches\Entity\SavedSearchesViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\conil_saved_searches\Form\SavedSearchesForm",
 *       "add" = "Drupal\conil_saved_searches\Form\SavedSearchesForm",
 *       "edit" = "Drupal\conil_saved_searches\Form\SavedSearchesForm",
 *       "delete" = "Drupal\conil_saved_searches\Form\SavedSearchesDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\conil_saved_searches\SavedSearchesHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\conil_saved_searches\SavedSearchesAccessControlHandler",
 *   },
 *   base_table = "saved_searches",
 *   translatable = FALSE,
 *   admin_permission = "administer saved searches entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/saved_searches/{saved_searches}",
 *     "add-form" = "/admin/structure/saved_searches/add",
 *     "edit-form" = "/admin/structure/saved_searches/{saved_searches}/edit",
 *     "delete-form" = "/admin/structure/saved_searches/{saved_searches}/delete",
 *     "collection" = "/admin/structure/saved_searches",
 *   },
 *   field_ui_base_route = "saved_searches.settings"
 * )
 */
class SavedSearches extends ContentEntityBase implements SavedSearchesInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Saved Searches entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['search'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Search'))
      ->setDescription(t('The search.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Search date'))
      ->setDescription(t('The date the search was made.'))
      ->setRevisionable(FALSE)
      ->setSettings([
        'datetime_type' => 'date'
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => 14,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    return $fields;
  }

}
