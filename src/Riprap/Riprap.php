<?php

namespace Drupal\islandora_riprap\Riprap;

/**
 * Utilities for interacting with a Riprap fixity microservice.
 */
class Riprap {

  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
  }

  /**
   * Queries the Riprap microservice's REST interface for events about $resource_id.
   *
   * @param string $resource_id
   *   The URI of the resource.
   *
   * @return string|bool
   *   The raw JSON response body, or false.
   */
  public function getEvents($resource_id) {
    try {
      $client = \Drupal::httpClient();
      // Assumes Riprap requires no authentication (e.g., it's behind the Symfony or other firewall).
      $options = [
        'http_errors' => false,
        'headers' => ['Resource-ID' => $resource_id],
        'query' => ['limit' => $this->number_of_events, 'sort' => 'desc'],
      ];
      $response = $client->request('GET', $this->riprap_endpoint, $options);
      $code = $response->getStatusCode();
      // Note: Riprap returns a 200 even if there are no events for $resource_id. However, in
      // this case, the response body will be an empty array.
      if ($code == 200) {
        $body = $response->getBody()->getContents();
        return $body;
      }
      else {
        \Drupal::logger('islandora_riprap')->error('HTTP response code returned by Riprap: @code', array('@code' => $code));
        drupal_set_message(t("Sorry, Riprap appears to not have any events for @resource_id", array('@resource_id' => $resource_id)), 'error');
      }
    }
    catch (RequestException $e) {
       \Drupal::logger('islandora_riprap')->error($e->getMessage());
       drupal_set_message(t("Sorry, there has been an error connecting to Riprap, please refer to the system log"), 'error');
    }
  }

}
