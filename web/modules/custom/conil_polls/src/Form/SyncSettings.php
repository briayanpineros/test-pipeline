<?php

namespace Drupal\conil_polls\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Settings form.
 */
class SyncSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conil_polls.sync_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conil_polls_sync_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conil_polls.sync_settings');

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Synchronize endpoints'),
    ];

    // AES config.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#group' => 'tabs',
    ];
    $form['general']['sync_hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Hour to synchronizate'),
      '#default_value' => $config->get('sync_hour'),
      '#min' => 0,
      '#max' => 23,
      '#step' => 1,
      '#description' => $this->t('Use a 24 hours format, the min value is 0 and the max value is 23 hours.'),
    ];

    // Synchronize polls config.
    $form['export_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Sync poll'),
      '#group' => 'tabs',
    ];
    $form['export_config']['endpoint_config_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Call service active'),
      '#default_value' => $config->get('endpoint_config_active'),
    ];
    $form['export_config']['endpoint_config_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#default_value' => $config->get('endpoint_config_url'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_config_active"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="endpoint_config_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['export_config']['endpoint_config_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $config->get('endpoint_config_user'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_config_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['export_config']['endpoint_config_pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('endpoint_config_pass'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_config_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['export_config']['endpoint_config_ssl'] = [
      '#type' => 'checkbox',
      '#title' => t('Use SSL.'),
      '#default_value' => $config->get('endpoint_config_ssl'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_config_active"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Synchronize polls results.
    $form['export_results'] = [
      '#type' => 'details',
      '#title' => $this->t('Sync poll results'),
      '#group' => 'tabs',
    ];
    $form['export_results']['endpoint_results_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Call service active'),
      '#default_value' => $config->get('endpoint_results_active'),
    ];
    $form['export_results']['endpoint_results_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#default_value' => $config->get('endpoint_results_url'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_results_active"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="endpoint_results_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['export_results']['endpoint_results_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $config->get('endpoint_results_user'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_results_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['export_results']['endpoint_results_pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('endpoint_results_pass'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_results_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['export_results']['endpoint_results_ssl'] = [
      '#type' => 'checkbox',
      '#title' => t('Use SSL.'),
      '#default_value' => $config->get('endpoint_results_ssl'),
      '#states' => [
        'visible' => [
          'input[name="endpoint_results_active"]' => ['checked' => TRUE],
        ],
      ],
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('conil_polls.sync_settings');
    $data = $form_state->cleanValues()->getValues();
    $config->setData($data);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
