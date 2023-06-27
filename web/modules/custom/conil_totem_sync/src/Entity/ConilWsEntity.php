<?php

namespace Drupal\conil_totem_sync\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Conil WS entity entity.
 *
 * @ConfigEntityType(
 *   id = "conil_ws_entity",
 *   label = @Translation("Conil WS entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\conil_totem_sync\ConilWsEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\conil_totem_sync\Form\ConilWsEntityForm",
 *       "edit" = "Drupal\conil_totem_sync\Form\ConilWsEntityForm",
 *       "delete" = "Drupal\conil_totem_sync\Form\ConilWsEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\conil_totem_sync\ConilWsEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "conil_ws_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/conil-totem-sync/conections/{conil_ws_entity}",
 *     "add-form" = "/admin/config/services/conil-totem-sync/conections/add",
 *     "edit-form" = "/admin/config/services/conil-totem-sync/conections/{conil_ws_entity}/edit",
 *     "delete-form" = "/admin/config/services/conil-totem-sync/conections/{conil_ws_entity}/delete",
 *     "collection" = "/admin/config/services/conil-totem-sync/conections"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "url",
 *     "api",
 *     "user",
 *     "pass",
 *     "status",
 *   }
 * )
 */
class ConilWsEntity extends ConfigEntityBase implements ConilWsEntityInterface {

  /**
   * The Conil WS entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Conil WS entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Conil WS url.
   *
   * @var string
   */
  protected $url;

  /**
   * The Conil WS api.
   *
   * @var string
   */
  protected $api;

  /**
   * The Conil WS user.
   *
   * @var string
   */
  protected $user;

  /**
   * The Conil WS pass.
   *
   * @var string
   */
  protected $pass;

  /**
   * The status, whether to be used by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url');
  }

  /**
   * {@inheritdoc}
   */
  public function getApi() {
    return $this->get('api');
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user');
  }

  /**
   * {@inheritdoc}
   */
  public function getPass() {
    return $this->get('pass');
  }

}
