<?php

namespace Drupal\conil_totem;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class TotemStorage extends SqlContentEntityStorage implements TotemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(TotemInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {totem_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {totem_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(TotemInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {totem_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('totem_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
