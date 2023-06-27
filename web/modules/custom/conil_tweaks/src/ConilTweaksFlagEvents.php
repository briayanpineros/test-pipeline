<?php

namespace Drupal\conil_tweaks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\mailrelay_newsletter\MailrelayServiceInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Class FlagCountManager.
 */
class ConilTweaksFlagEvents implements EventSubscriberInterface {

  /**
   * The currently logged-in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The mailRelay service.
   *
   * @var Drupal\mailrelay_newsletter\MailrelayServiceInterface
   */
  protected $mailRelay;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a new SearchApiConverter.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\mailrelay_newsletter\MailrelayServiceInterface $mail_relay
   *   The MailRelay.
   * @param \Drupal\Core\Language\LanguageManagere $language_manager
   *   The language manager.
   */
  public function __construct(AccountInterface $user, MailrelayServiceInterface $mail_relay, LanguageManager $language_manager) {
    $this->currentUser = $user;
    $this->mailRelay = $mail_relay;
    $this->languageManager   = $language_manager;

  }

  /**
   * Increments count of flagged entities.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function subscribeUserMaiRelay(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    if ($flagging->getFlagId() == "news_channel_subscriptions") {

      $deleted = $this->mailRelay->subscribers('GET', [], ['deleted'], ['email_eq' => $this->currentUser->getEmail()]);
      if (!empty($deleted)) {
        $subscriber = reset($deleted);
        $this->mailRelay->subscribers('PATCH', [], [$subscriber['id'], 'restore']);
      }
      else {
        $categories = $this->mailRelay->getGroups();
        $groups = [];
        foreach ($categories as $category) {
          $groups[] = $category['id'];
        }
        $language = $this->languageManager->getCurrentLanguage()->getId();

        $arguments = [
          'status' => 'active',
          'email' => $this->currentUser->getEmail(),
          'group_ids' => $groups,
          'locale' => $language,
        ];
        $this->mailRelay->subscribers('POST', $arguments, []);
      }

    }
  }

    /**
   * Decrements count of flagged entities.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function unsubscribeUserMaiRelay(UnflaggingEvent $event) {
    $flaggings = $event->getFlaggings();
    foreach ($flaggings as $flagging) {
      if ($flagging->getFlagId() == "news_channel_subscriptions") {
        $subscribers =$this->mailRelay->subscribers('GET', [], [], ['q[email_eq]' => $this->currentUser->getEmail()]);
        if (!empty($subscribers)) {
          $subscriber = reset($subscribers);
          $subscribers =$this->mailRelay->subscribers('DELETE', [], [$subscriber['id']]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['subscribeUserMaiRelay', 0];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = ['unsubscribeUserMaiRelay',0];
    return $events;
  }

}
