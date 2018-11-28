<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
* Controller.
*/
class IslandoraRiprapController extends ControllerBase {
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->gemini_endpoint = $config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';
  }

  /**
   * Get the Riprap data for the current Media entity and render it.
   *
   * @return string
   */
   public function main() {
     $current_path = \Drupal::service('path.current')->getPath();
     $path_args = explode('/', $current_path);
     $mid = $path_args[2];
     $output = $this->queryRiprap($mid);

     $header = [t('Event UUID'), t('Resource URI'), t('Event type'), t('Timestamp'),
       t('Digest algorithm'), t('Digest value'), t('Event detail'), t('Event outcome'), t('Note')];
     $rows = array();
     foreach ($output as &$event) {
       $rows[] = array_values($event);
     }

     $output = [
       '#theme' => 'table',
       '#header' => $header,
       '#rows' => $rows,
     ];

     drupal_set_message(t('This is sample data!'), 'warning');
     return [
       '#theme' => 'islandora_riprap_report',
       '#report' => $output,
       '#mid' => $mid,
     ];
   }

  /**
   * Query Riprap for fixity events on the given resource.
   *
   * @param int $mid
   *   The Media resource's ID.
   *
   * @return string
   *   The JSON response from Riprap.
   */
  private function queryRiprap($mid) {
    $sample_riprap_output = '[{"event_uuid":"cdecb9ac-5938-4992-b5b7-8ef9e4e94c62","resource_id":"http:\/\/localhost:8000\/mockrepository\/rest\/11","event_type":"fix","timestamp":"2018-10-20T07:35:04-0800","digest_algorithm":"SHA-1","digest_value":"339e2ebc99d2a81e7786a466b5cbb9f8b3b81377","event_detail":"","event_outcome":"suc","event_outcome_detail_note":"Fedora says hi."},{"event_uuid":"f26ed6cc-e6e2-4ebe-9607-5ad7a2e4b857","resource_id":"http:\/\/localhost:8000\/mockrepository\/rest\/11","event_type":"fix","timestamp":"2018-10-29T07:35:04-0800","digest_algorithm":"SHA-1","digest_value":"339e2ebc99d2a81e7786a466b5cbb9f8b3b81377","event_detail":"","event_outcome":"suc","event_outcome_detail_note":"Fedora says hi."},{"event_uuid":"af60c8d5-7504-4be0-a355-06177855b8b8","resource_id":"http:\/\/localhost:8000\/mockrepository\/rest\/11","event_type":"fix","timestamp":"2018-11-20T07:35:04-0800","digest_algorithm":"SHA-1","digest_value":"339e2ebc99d2a81e7786a466b5cbb9f8b3b81377","event_detail":"","event_outcome":"suc","event_outcome_detail_note":""}]';
    $sample_riprap_output_array = json_decode($sample_riprap_output, true);
    return $sample_riprap_output_array;
  }

}

