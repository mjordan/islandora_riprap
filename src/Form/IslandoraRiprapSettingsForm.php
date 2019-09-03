<?php

namespace Drupal\islandora_riprap\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class IslandoraRiprapSettingsForm extends ConfigFormBase {
  /**
   * The path to stored config file.
   *
   * @var string
   */
  protected $config_filepath;

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
public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config_filepath = "private://riprap_config";
  parent::__construct($config_factory);
}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $actual_path = \Drupal::service('file_system')->realpath('private://');
    if(!$actual_path) {
      $this->messenger()->addWarning("No Private File Folder found, please contact system administrator");
    }

    $config = $this->config('islandora_riprap.settings');
    $vid = 'islandora_media_use';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term_data[$term->tid] = $term->name;
    }

    $current_config = nl2br(file_get_contents("{$this->config_filepath}/islandora_riprap_config.yml"));
    $replacement_string = "drupal_media_auth: ['xxxxx', 'xxxxx']";
    $current_config = preg_replace('/drupal_media_auth.*\]/', $replacement_string, $current_config);

    $form['riprap_rest_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Riprap microservice REST endpoint'),
      '#description' => $this->t('Do not include the trailing /.'),
      '#default_value' => $config->get('riprap_rest_endpoint') ? $config->get('riprap_rest_endpoint') : 'http://localhost:8000/api/fixity',
    ];
    $form['number_of_events'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of events to show in report. Leave empty to show all.'),
      '#default_value' => $config->get('number_of_events') ? $config->get('number_of_events') : '10',
    ];
    $form['gemini_rest_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gemini microservice REST endpoint'),
      '#description' => $this->t('Do not include the trailing /.'),
      '#default_value' => $config->get('gemini_rest_endpoint') ? $config->get('gemini_rest_endpoint') : 'http://localhost:8000/gemini',
    ];
    $form['use_drupal_urls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Drupal URLs for media instead of Fedora URLs.'),
      '#default_value' => $config->get('use_drupal_urls') ? $config->get('use_drupal_urls') : FALSE,
    ];
    $form['show_riprap_warnings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show and log warnings about missing resources and fixity events.'),
      '#default_value' => !$config->get('show_riprap_warnings') ? $config->get('show_riprap_warnings') : TRUE,
    ];
    $form['fixity_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type to be examined'),
      '#options' => node_type_get_names(),
      '#description' => $this->t('Media of this content type will be checked'),
      '#default_value' => $config->get('fixity_content_type') ? $config->get('fixity_content_type') : 'Repository Item',
    ];
    $form['fixity_terms'] = [
      '#type' => 'select',
      '#title' => $this->t('Media Use terms'),
      '#description' => $this->t('Media tagged with these terms will be checked. Seperate terms with commas.'),
      '#default_value' => $config->get('fixity_terms') ? $config->get('fixity_terms') : array_search('Original File', $term_data),
      '#options' => $term_data,
    ];
    $form['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal user'),
      '#description' => $this->t('User name with media access permsissions'),
      '#default_value' => $config->get('user_name') ? $config->get('user_name') : '',
    ];
    $form['user_pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Drupal password'),
      '#description' => $this->t('Password for user with media access permsissions'),
      '#default_value' => $config->get('user_pass') ? $config->get('user_pass') : '',
    ];

    $form['config'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => t('Current configuration'),
      ];
    $form['config']['current setup'] = [
      '#description' => t('Username and password ha\ve been obfuscated'),
      '#markup' => $current_config,
      '#title' => t('Current configuration'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->persistConfig($values);
    $this->configFactory->getEditable('islandora_riprap.settings')
      ->set('riprap_rest_endpoint', rtrim($form_state->getValue('riprap_rest_endpoint'), '/'))
      ->set('number_of_events', $form_state->getValue('number_of_events'))
      ->set('gemini_rest_endpoint', rtrim($form_state->getValue('gemini_rest_endpoint'), '/'))
      ->set('use_drupal_urls', $form_state->getValue('use_drupal_urls'))
      ->set('show_riprap_warnings', $form_state->getValue('show_riprap_warnings'))
      ->set('fixity_terms', $form_state->getValue('fixity_terms'))
      ->set('fixity_content_type', $form_state->getValue('fixity_content_type'))
      ->set('user_name', $form_state->getValue('user_name'))
      ->set('user_pass', $form_state->getValue('user_pass'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public function persistConfig($values) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    if (!file_exists($this->config_filepath)) {
      mkdir($this->config_filepath, 0777, true);
    }
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
  $success = file_save_data($riprap_config, "$this->config_filepath/islandora_riprap_config.yml", FILE_EXISTS_REPLACE);
  }

}

