<?php

namespace Drupal\conil_tweaks\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Url;

/**
 * Removes composite revisions that are no longer used.
 *
 * @QueueWorker(
 *   id = "conil_tweaks_user_email_notification",
 *   title = @Translation("Conil: User Email Notification"),
 *   cron = {"time" = 60}
 * )
 */
class SendEmailUserAction extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;


  /**
   * Constructs a new OrphanPurger instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Mail\MailManager $mail_manager
   *   The mail manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    MailManager $mail_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $to = $data['mail'];
    $uid = $data['uid'];

    $category = NULL;
    if($data['agenda_category']){
      $category = $data['agenda_category'];
    }

    if($data['tid']){
      $tid = $data['tid'];
    }else{
      $entity_id = $data['entity_id'];
    }

    if (!empty($to)) {
      $key = 'conil_new_agenda_content_notify';
      $mailManager = $this->mailManager;
      $module = 'conil_tweaks';
      $params['subject'] = t("Conil has send you a notification");
      $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      if($category){
        if($tid){
          $params['message'] = t("Hi there!\nNew @category content available for you.\nIn case you want to unsubscribe from these alerts please follow this link @url/cancel-agenda-subscription?tid=@tid&email=@to&uid=@uid",
            ['@category' => $category, '@tid' => $tid, '@to' => $to, '@uid' => $uid, '@url' => $url]);
        }else{
          $params['message'] = t("Hi there!\n New @category content available for you.\nIn case you want to unsubscribe from these alerts please follow this link @url/cancel-agenda-subscription?entity_id=@entity_id&email=@to&uid=@uid",
            ['@category' => $category, '@entity_id' => $entity_id, '@to' => $to, '@uid' => $uid, '@url' => $url]);
        }
      }
      else{
        if ($tid) {
          $params['message'] = t("Hi there!\n New content available for you.\nIn case you want to unsubscribe from these alerts please follow this link @url/cancel-news-subscription?entity_id=@tid&email=@to&uid=@uid",
            ['@tid' => $tid, '@to' => $to, '@uid' => $uid, '@url' => $url]);
        }
        else {
          $params['message'] = t("Hi there!\n New content available for you.\nIn case you want to unsubscribe from these alerts please follow this link @url/cancel-news-subscription?entity_id=@entity_id&email=@to&uid=@uid",
            ['@entity_id' => $entity_id, '@to' => $to, '@uid' => $uid, '@url' => $url]);
        }
      }
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($uid);
      $langcode = $user->getPreferredLangcode();
      $send = TRUE;

      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
      $message = t('An email notification has been sent to @email', ['@email' => $to]);
      if ($result['result'] != TRUE) {
        $message =t('There was a problem sending your email notification to @email', ['@email' => $to]);
        return;
      }
    }
  }

}
