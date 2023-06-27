<?php

namespace Drupal\conil_saved_searches;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Saved Searches entity.
 *
 * @see \Drupal\conil_saved_searches\Entity\SavedSearches.
 */
class SavedSearchesAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\conil_saved_searches\Entity\SavedSearchesInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished saved searches entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published saved searches entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit saved searches entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete saved searches entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add saved searches entities');
  }


}
