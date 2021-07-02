<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller.
 */
class IslandoraRiprapMediaEventsController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->show_warnings = !$config->get('show_riprap_warnings') ? $config->get('show_riprap_warnings') : TRUE;
  }

  /**
   * Get the Riprap data for the current Media entity and render it.
   *
   * @return array
   *   Themed markup.
   */
  public function main() {
    $riprap = \Drupal::service('islandora_riprap.riprap');
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $mid = $path_args[2];

    $binary_resource_uuid = $riprap->getFileUuid($mid);
    if ($this->use_drupal_urls) {
      $binary_resource_url = $riprap->getLocalUrl($mid);
    }
    else {
      $binary_resource_url = $riprap->getFedoraUrl($mid);
    }

    $riprap_output = $riprap->getEvents(['output_format' => 'json', 'resource_id' => $binary_resource_url]);

    if (!$riprap_output) {
      \Drupal::messenger()->addMessage($this->t('Cannot retrieve fixity events from Riprap for this media. This has been logged, but please contact the system administrator.'), 'error');
      \Drupal::logger('islandora_riprap')->error($this->t('Riprap expected to get fixity event information for @url (Media @mid) but cannot.', ['@url' => $binary_resource_url, '@mid' => $mid]));
      return [];
    }

    $riprap_output = json_decode($riprap_output, TRUE);
    if ($this->show_warnings) {
      if (count($riprap_output) == 0 && $binary_resource_url != 'Not in Fedora') {
        \Drupal::messenger()->addMessage($this->t('No fixity event information for @binary_resource_url (Media @mid).', ['@binary_resource_url' => $binary_resource_url, '@mid' => $mid]), 'warning');
        return [];
      }
    }

    $header = [t('Event UUID'), t('Resource URI'), t('Event type'), t('Timestamp'),
      t('Digest algorithm'), t('Digest value'), t('Event detail'), t('Event outcome'), t('Note'),
    ];
    $rows = [];
    foreach ($riprap_output as &$event) {
      $rows[] = array_values($event);
    }

    $output = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return [
      '#theme' => 'islandora_riprap_media_events',
      '#report' => $output,
      '#mid' => $mid,
      '#binary_resource_url' => $binary_resource_url,
    ];
  }

}
