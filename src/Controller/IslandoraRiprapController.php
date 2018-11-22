<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Access\AccessResult;

/**
* Controller.
*/
class IslandoraRiprapController extends ControllerBase {
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
  }

  /**
   * Get the Riprap data for the current node and render it.
   *
   * @return string
   */
   public function main(NodeInterface $node = NULL) {
     $node = \Drupal::routeMatch()->getParameter('node');
     $nid = $node->id();
     $output = $this->getRiprapEvents($nid);
     return [
       '#theme' => 'islandora_riprap_report',
       '#hello_world' => $output,
     ];
   }


   /**
    * Get various representations of the object.
    */
   private function getRiprapEvents($nid) {
     try {
       $container = \Drupal::getContainer();
       $jwt = $container->get('jwt.authentication.jwt');
       $auth = 'Bearer ' . $jwt->generateToken();
       $client = \Drupal::httpClient();
       $options = [
         'auth' => [],
         'headers' => ['Authorization' => $auth],
         'form_params' => []
       ];
       $response = $client->request('GET', 'http://localhost:8000/node/' . $nid . '/media?_format=json', $options);
       $code = $response->getStatusCode();
       if ($code == 200) {
         $body = $response->getBody()->getContents();
       }
       else {
         \Drupal::logger('islandora_riprap')->error('HTTP response code: @code', array('@code' => $code));
       }
     }
     catch (RequestException $e) {
        \Drupal::logger('islandora_riprap')->error($e->getMessage());
     }

     /*
     -Query curl -v -u admin:islandora http://localhost:8000/node/266/media?_format=json to get list of media.
      @todo: How do we authenticate?
     -For each media entry, perform the code at https://github.com/mjordan/islandora_riprap/issues/2.
      This will get URLs for each media file.
     -If necessary, convert each media file URL into its Fedora equivalent using Gemini. This will only be
      necessary if Islandora is using Fedora (perhaps an admin option, or an autodetect if one is available).
      The Riprap output will look like the same data below.
     -For each media use (Preservation master, etc.), provide a summary of the total number of fixity checks,
      plus number of success and failures.
     */

     $sample_riprap_output = '[{"event_uuid":"cdecb9ac-5938-4992-b5b7-8ef9e4e94c62","resource_id":"http:\/\/localhost:8000\/mockrepository\/rest\/11","event_type":"fix","timestamp":"2018-10-20T07:35:04-0800","digest_algorithm":"SHA-1","digest_value":"339e2ebc99d2a81e7786a466b5cbb9f8b3b81377","event_detail":"","event_outcome":"suc","event_outcome_detail_note":"Fedora says hi."},{"event_uuid":"f26ed6cc-e6e2-4ebe-9607-5ad7a2e4b857","resource_id":"http:\/\/localhost:8000\/mockrepository\/rest\/11","event_type":"fix","timestamp":"2018-10-29T07:35:04-0800","digest_algorithm":"SHA-1","digest_value":"339e2ebc99d2a81e7786a466b5cbb9f8b3b81377","event_detail":"","event_outcome":"suc","event_outcome_detail_note":"Fedora says hi."},{"event_uuid":"af60c8d5-7504-4be0-a355-06177855b8b8","resource_id":"http:\/\/localhost:8000\/mockrepository\/rest\/11","event_type":"ing","timestamp":"2018-11-20T07:35:04-0800","digest_algorithm":"SHA-1","digest_value":"339e2ebc99d2a81e7786a466b5cbb9f8b3b81377","event_detail":"","event_outcome":"fail","event_outcome_detail_note":""}]';
     $riprap_output_as_array = json_decode($sample_riprap_output, true);
     $successful_events = 0;
     $failed_events = 0;
     foreach ($riprap_output_as_array as $event) {
       if ($event['event_outcome'] == 'suc') {
         $successful_events++;
       }
       if ($event['event_outcome'] == 'fail') {
         $failed_events++;
       }
     }

     $output = "Report from sample Riprap data (3 events): $successful_events successful events, $failed_events failed events.";
     return $output;
   }

   /**
    * Only show tab on nodes with the 'islandora_object' content type.
    */
   public function islandoraContentTypeOnly(NodeInterface $node = NULL) {
     return ($node->getType() == 'islandora_object') ? AccessResult::allowed() : AccessResult::forbidden();
   }

   /**
    * Convert from term ID to term name.
    *
    * @param int $tid
    *   The term ID.
    *
    * @return string
    *   The term name.
    */
   public function tidToName($tid) {
     $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
     return $term->getName();
   }

}
