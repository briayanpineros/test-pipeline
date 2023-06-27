<?php

namespace Drupal\conil_totem;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\conil_totem\Entity\TotemInterface;

/**
 * Defines the storage handler class for Totem entities.
 *
 * This extends the base storage class, adding required special handling for
 * Totem entities.
 *
 * @ingroup conil_totem
 */
interface TotemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Totem revision IDs for a specific Totem.
   *
   * @param \Drupal\conil_totem\Entity\TotemInterface $entity
   *   The Totem entity.
   *
   * @return int[]
   *   Totem revision IDs (in ascending order).
   */
  public function revisionIds(TotemInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Totem author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Totem revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\conil_totem\Entity\TotemInterface $entity
   *   The Totem entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(TotemInterface $entity);

  /**
   * Unsets the language for all Totem with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
