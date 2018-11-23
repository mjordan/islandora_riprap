<?php
namespace Drupal\islandora_riprap\Form;

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

    $form['riprap_rest_endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Riprap microservice REST endpoint'),
      '#description' => $this->t('Do not include the trailing /.'),
      '#default_value' => $config->get('riprap_rest_endpoint') ? $config->get('riprap_rest_endpoint') : 'http://localhost:8000/api/fixity',
    );
    $form['number_of_events'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of events to show in report. Leave empty to show all.'),
      '#default_value' => $config->get('number_of_events') ? $config->get('number_of_events') : '10',
    );
    $form['gemini_rest_endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Gemini microservice REST endpoint'),
      '#description' => $this->t('Do not include the trailing /.'),
      '#default_value' => $config->get('gemini_rest_endpoint') ? $config->get('gemini_rest_endpoint') : 'http://localhost:8000/gemini',
    );
    $form['use_drupal_urls'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use Drupal URLs for media instead of Fedora URLs.'),
      '#default_value' => $config->get('use_drupal_urls') ? $config->get('use_drupal_urls') : FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
       $this->configFactory->getEditable('islandora_riprap.settings')
      ->set('riprap_rest_endpoint', $form_state->getValue('riprap_rest_endpoint'))
      ->set('number_of_events', $form_state->getValue('number_of_events'))
      ->set('gemini_rest_endpoint', $form_state->getValue('gemini_rest_endpoint'))
      ->set('use_drupal_urls', $form_state->getValue('use_drupal_urls'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

