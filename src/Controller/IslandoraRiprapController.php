<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\islandora_riprap\Riprap\Riprap;
use Drupal\islandora_riprap\Riprap\IslandoraRiprapUtils;

/**
* Controller.
*/
class IslandoraRiprapController extends ControllerBase {
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->show_warnings = !$config->get('show_riprap_warnings') ? $config->get('show_riprap_warnings') : TRUE;
    $this->gemini_endpoint = $config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';
  }

  /**
   * Get the Riprap data for the current Media entity and render it.
   *
   * @return array
   */
   public function main() {
     $riprap = new Riprap();
     $current_path = \Drupal::service('path.current')->getPath();
     $path_args = explode('/', $current_path);
     $mid = $path_args[2];

     $utils = new IslandoraRiprapUtils();
     $binary_resource_uuid = $utils->getFileUuid($mid);
     if ($this->use_drupal_urls) {
       $binary_resource_url = $utils->getLocalUrl($mid);
     }
     else {
       $binary_resource_url = $utils->getFedoraUrl($binary_resource_uuid);
     } 

     $riprap_output = $riprap->getEvents($binary_resource_url);

     if (!$riprap_output) {
       \Drupal::messenger()->addMessage($this->t('Cannot retrieve fixity events from Riprap for this media. This has been logged, but please contact the system administrator.'), 'error');
       \Drupal::logger('islandora_riprap')->error($this->t('Riprap expected to get fixity event information for @url (Media @mid) but cannot.', ['@url' => $binary_resource_url, '@mid' => $mid]));
       return array();
     }

     $riprap_output = json_decode($riprap_output, true);
     if ($this->show_warnings) {
       if (count($riprap_output) == 0 && $binary_resource_url != 'Not in Fedora') {
         \Drupal::messenger()->addMessage($this->t('No fixity event information for @binary_resource_url (Media @mid).', ['@binary_resource_url' => $binary_resource_url, '@mid' => $mid]), 'warning');
         return array();
       }
     }

     $header = [t('Event UUID'), t('Resource URI'), t('Event type'), t('Timestamp'),
       t('Digest algorithm'), t('Digest value'), t('Event detail'), t('Event outcome'), t('Note')];
     $rows = array();
     foreach ($riprap_output as &$event) {
       $rows[] = array_values($event);
     }

     $output = [
       '#theme' => 'table',
       '#header' => $header,
       '#rows' => $rows,
     ];

     return [
       '#theme' => 'islandora_riprap_report',
       '#report' => $output,
       '#mid' => $mid,
       '#binary_resource_url' => $binary_resource_url,
     ];
   }
}

