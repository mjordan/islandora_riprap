<?php

namespace Drupal\islandora_riprap\Plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class IslandoraRiprapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_riprap_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'islandora_riprap.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('islandora_riprap.settings');
    $vid = 'islandora_media_use';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term_data[$term->tid] = $term->name;
    }

    $utils = \Drupal::service('islandora_riprap.utils');

    $form['failed_fixity_events_report']['#markup'] = $utils->getLinkToFailedFixityEventsReport();
    $form['riprap_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Riprap location'),
      '#options' => [
        'remote' => $this->t('Remote'),
        'local' => $this->t('Local'),
      ],
      '#default_value' => $config->get('riprap_mode'),
      '#attributes' => [
        'id' => 'riprap_mode',
      ],
    ];
    $form['riprap_rest_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Riprap microservice REST endpoint'),
      '#description' => $this->t('Do not include the trailing /.'),
      '#default_value' => $config->get('riprap_rest_endpoint'),
      '#states' => [
        'visible' => [
          ':input[id=riprap_mode]' => ['value' => 'remote'],
        ],
      ],
    ];
    $form['riprap_local_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Absolute path to your local Riprap installation directory'),
      '#description' => $this->t('For example, "/var/local/riprap". Used only when running in "local" mode. Ignore if you are using Riprap as a microservice.'),
      '#default_value' => $config->get('riprap_local_directory'),
      '#states' => [
        'visible' => [
          ':input[id=riprap_mode]' => ['value' => 'local'],
        ],
      ],
    ];
    $form['riprap_local_settings_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Absolute path to the YAML settings file used by the local Riprap installation'),
      '#description' => $this->t('For example, "/var/local/riprap/settings.yml". Used only when running in "local" mode. Ignore if you are using Riprap as a microservice.'),
      '#default_value' => $config->get('riprap_local_settings_file'),
      '#states' => [
        'visible' => [
          ':input[id=riprap_mode]' => ['value' => 'local'],
        ],
      ],
    ];
    $form['execute_riprap_in_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Execute Riprap during Drupal cron runs. Only applies to "local" mode.'),
      '#default_value' => $config->get('execute_riprap_in_cron'),
      '#states' => [
        'visible' => [
          ':input[id=riprap_mode]' => ['value' => 'local'],
        ],
      ],
    ];
    $form['number_of_events'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of events to show in each Media\'s "Details" report. Leave empty to show all.'),
      '#default_value' => $config->get('number_of_events'),
    ];
    $form['gemini_rest_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gemini microservice REST endpoint'),
      '#description' => $this->t('Do not include the trailing /.'),
      '#default_value' => $config->get('gemini_rest_endpoint'),
    ];
    $form['use_drupal_urls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Drupal URLs for media instead of Fedora URLs. Check this box only if you are not using Fedora.'),
      '#default_value' => $config->get('use_drupal_urls'),
    ];
    $form['log_riprap_warnings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log warnings about missing resources and fixity events.'),
      '#default_value' => $config->get('log_riprap_warnings'),
    ];
    $form['use_sample_failed_fixity_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use sample fixity event data.'),
      '#description' => $this->t('In the "Failed Fixity Check Events" report, use auto-generated sample data instead of data from Riprap. Useful for demos, etc.'),
      '#default_value' => $config->get('use_sample_failed_fixity_events'),
    ];

    $form['riprap_config'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => t('Generate Riprap configuration settings (optional)'),
      '#description' => t('This section of the form allows you to generate a configuration file for Riprap. After you save this form, the content in the "Configuration file contents" section below can be copied into a YAML file to use as your Riprap microservice configuration.'),
    ];
    $form['riprap_config']['fixity_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type to be examined'),
      '#options' => node_type_get_names(),
      '#description' => $this->t('Media of this content type will be checked'),
      '#default_value' => $config->get('fixity_content_type'),
    ];
    $form['riprap_config']['fixity_terms'] = [
      '#type' => 'select',
      '#title' => $this->t('Media Use terms'),
      '#description' => $this->t('Media tagged with these terms will be checked.'),
      '#default_value' => $config->get('fixity_terms'),
      '#options' => $term_data,
    ];
    $form['riprap_config']['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal user'),
      '#description' => $this->t('User name with media access permsissions'),
      '#default_value' => $config->get('user_name'),
    ];
    $form['riprap_config']['user_pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Drupal password'),
      '#description' => $this->t('Password for user with media access permsissions'),
      '#default_value' => $config->get('user_pass'),
    ];

    $current_config = $this->generateRiprapConfig($form_state->getValues());
    if (!$current_config) {
      $current_config = t("No saved configuration found.");
    }
    // @todo: Test this replacement.
    $replacement_string = "drupal_media_auth: ['xxxxx', 'xxxxx']";
    $replacement_string = "drupal_media_auth: ['" . $form_state->getValue('user_name') . "', '" . $form_state->getValue('user_pass') . "']";
    $current_config = preg_replace('/drupal_media_auth.*\]/', $replacement_string, $current_config);
    $form['riprap_config']['current_setup'] = [
      '#type' => 'textarea',
      '#rows' => 50,
      '#title' => $this->t('Configuration file contents'),
      '#default_value' => $current_config,
      '#description' => $this->t('Copy and paste this into a Riprap configuration file.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('riprap_mode') == 'local') {
      if (!file_exists(trim($form_state->getValue('riprap_local_directory')))) {
        $form_state->setErrorByName(
          'riprap_local_directory',
          $this->t('Cannot find the Riprap installation directory at the path specified.')
        );
      }
      if (!file_exists(trim($form_state->getValue('riprap_local_settings_file')))) {
        $form_state->setErrorByName(
          'riprap_local_settings_file',
          $this->t('Cannot find the Riprap settings file at the path specified.')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configFactory->getEditable('islandora_riprap.settings')
      ->set('riprap_mode', $form_state->getValue('riprap_mode'))
      ->set('riprap_rest_endpoint', rtrim($form_state->getValue('riprap_rest_endpoint'), '/'))
      ->set('riprap_local_directory', rtrim($form_state->getValue('riprap_local_directory'), '/'))
      ->set('riprap_local_settings_file', rtrim($form_state->getValue('riprap_local_settings_file'), '/'))
      ->set('execute_riprap_in_cron', $form_state->getValue('execute_riprap_in_cron'))
      ->set('number_of_events', $form_state->getValue('number_of_events'))
      ->set('gemini_rest_endpoint', rtrim($form_state->getValue('gemini_rest_endpoint'), '/'))
      ->set('use_drupal_urls', $form_state->getValue('use_drupal_urls'))
      ->set('log_riprap_warnings', $form_state->getValue('log_riprap_warnings'))
      ->set('use_sample_failed_fixity_events', $form_state->getValue('use_sample_failed_fixity_events'))
      ->set('fixity_terms', $form_state->getValue('fixity_terms'))
      ->set('fixity_content_type', $form_state->getValue('fixity_content_type'))
      ->set('user_name', $form_state->getValue('user_name'))
      ->set('user_pass', $form_state->getValue('user_pass'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Saves a Riprap configuration YAML file.
   *
   * @param array $values
   *   The form values.
   */
  public function generateRiprapConfig(array $values) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $riprap_config = <<<EOF
####################
# General settings #
####################

fixity_algorithm: sha256

###########
# Plugins #
###########

plugins.fetchresourcelist: ['PluginFetchResourceListFromDrupal']
drupal_baseurl: '$base_url'
jsonapi_authorization_headers: ['Authorization: Basic YWRtaW46aXNsYW5kb3Jh']
drupal_media_auth: ['{$values['user_name']}', '{$values['user_pass']}']
drupal_content_types: ['{$values['fixity_content_type']}']
drupal_media_tags: ['/taxonomy/term/{$values['fixity_terms']}']
use_fedora_urls: true
gemini_endpoint: '{$values['gemini_rest_endpoint']}'
gemini_auth_header: 'Bearer islandora'
# Can be a maximum of 50.
jsonapi_page_size: 50
# The number of resources to check in one Riprap run; if absent, will use
# value defined in jsonapi_page_size. Must be a multiple of number specified
# in jsonapi_page_size.
max_resources: 1000
jsonapi_pager_data_file_path: '/var/www/html/riprap/var/fetchresourcelist.from.drupal.pager.txt'

plugins.fetchdigest: PluginFetchDigestFromFedoraAPI
fedoraapi_method: HEAD
fedoraapi_digest_header_leader_pattern: "^.+="

plugins.persist: PluginPersistToDatabase

plugins.postcheck: ['PluginPostCheckCopyFailures']
# Absolute or relative to the Riprap application directory.
failures_log_path: '/tmp/riprap_failed_events.log'
EOF;
    return $riprap_config;
  }

}
