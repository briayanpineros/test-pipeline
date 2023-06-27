<?php

namespace Drupal\conil_totem;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Totem entity.
 *
 * @see \Drupal\conil_totem\Entity\Totem.
 */
class TotemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\conil_totem\Entity\TotemInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished totem entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published totem entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit totem entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete totem entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add totem entities');
  }


}
