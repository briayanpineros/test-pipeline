<?php

namespace Drupal\conil_selfie\Form;

use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Imagick;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SelfieSendEmailForm class.
 */
class SelfieSendEmailModalForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RendererInterface $renderer,
    FileSystemInterface $file_system,
    EntityTypeManager $entity_type_manager,
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list) {
    $this->renderer = $renderer;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'selfie_send_email_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $selfie = NULL, $background = NULL) {

    $form['#prefix'] = '<div id="selfie_modal_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['selfie'] = [
      '#type' => 'hidden',
      '#value' => urldecode($selfie),
    ];

    $form['background'] = [
      '#type' => 'hidden',
      '#value' => $background,
    ];

    $form['title'] = [
      '#markup' => '<div class="dialog-title">' . $this->t('3- Where do you want to send your Conil postcard?') . '</div>',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => 'Email',
      '#required' => TRUE,
      '#placeholder' => $this->t('Write here the email of the recipient of the postcard.'),
      // '#description' => $this->t('Enter your email address where the resulting selfie will be sent to you.'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#title' => $this
        ->t('Back'),
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'btn',
          'button',
          'btn-secondary',
        ],
      ],
      '#url' => Url::fromRoute('conil_selfie.selfie'),
    ];

    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-primary',
        ],
      ],
      '#ajax' => [
        'callback' => [
          $this,
          'submitModalFormAjax',
        ],
        'event' => 'click',
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'conil_selfie/conil_selfie_webcam';

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      /*$form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];*/
      $form['#sorted'] = FALSE;
      $response->addCommand(new ReplaceCommand('#selfie_modal_form', $form));
    }
    else {
      //$response->addCommand(new RedirectCommand(Url::fromRoute('conil_selfie.selfie')->toString()));
      $form['div'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'text-align: center',
        ],
      ];
      $form['div']['selfie_result'] = [
        '#type' => 'html_tag',
        '#attributes' => [
          'src' => 'data:image/png;base64,' . base64_encode(file_get_contents(DRUPAL_ROOT . '/' . $this->moduleExtensionList->getPath('conil_selfie') . '/images/send.png')),
        ],
        '#tag' => 'img',
      ];
      $form['message'] = [
        '#markup' => "<h2>Gracias, su postal ha sido enviada.</h2>",
      ];
      unset($form['title']);
      unset($form['email']);
      unset($form['actions']['send']);
      $form['actions']['back']['#attributes']['class'][] = 'btn-primary';
      $response->addCommand(new ReplaceCommand('#selfie_modal_form', $form));

    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Check if it looks like we are going to exceed the flood limit.
    // Not ported to Drupal 8.0.3 yet.
    if (empty($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', 'You must fill in the email');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mailManager = \Drupal::service('plugin.manager.mail');

    $module = 'conil_selfie';
    $key = 'selfie_mail';
    $to = $form_state->getValue('email');
    $params['subject'] = $this->t('Conil Selfie/Postal');
    $params['message'] = $this->t('Hi, in this e-mail we attach the selfie resulting from the photo taken in Conil. Thank you very much.');
    // Here we add our files URIs for an attachment.

    $filepath = \Drupal::service('file_system')->realpath('temporary://selfies/');
    if (!empty($form_state->getValue('selfie'))) {
      $img = $form_state->getValue('selfie');
    }
    else {
      $img = $form_state->getValue('background');
    }
    $filecontent = file_get_contents($filepath . '/' . $img . ".png");

    if (!empty($filecontent)) {
      $params['attachments'][] = [
        'filecontent' => $filecontent,
        'filename' => 'selfie.png',
        'filemime' => 'image/png',
      ];
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = TRUE;
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    }
    else {
      $message = $this->t('There was an error sending the selfie');
      \Drupal::logger('mail-log')->error('The content of file is empty');
    }

    if ($result['result'] != TRUE) {
      $message = $this->t('There was a problem sending your email to @email.', ['@email' => $to]);
      $this->messenger()->addError($message);
      \Drupal::logger('mail-log')->error($message);
    }
    else {
      // $message = $this->t('An email has been sent to @email with selfie', ['@email' => $to]);
      // $this->messenger()->addMessage($message);
      \Drupal::logger('mail-log')->notice($message);
    }

  }

}
