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
      '#default_value' => $config->get('riprap_rest_endpoint') ? $config->get('riprap_rest_endpoint') : 'http://localhost:8000/api/fixity',
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
       $this->configFactory->getEditable('islandora_riprap.settings')
      ->set('riprap_rest_endpoint', $form_state->getValue('riprap_rest_endpoint'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

