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
    $this->show_warnings = !$config->get('show_riprap_warnings') ? $config->get('show_riprap_warnings') : TRUE;
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
      elseif ($code == 404) {
        \Drupal::logger('islandora_riprap')->error('Riprap service not running or found at @endpoint', array('@endpoint' => $this->riprap_endpoint));
        // This is a special 'response' indicating that Riprap was not found (or is not running) at its configured URL.
        $status_message = t("Riprap not found or is not running at @endpoint", array('@endpoint' => $this->riprap_endpoint));
        return json_encode(array('riprap_status' => 404, 'message' => $status_message));
      }
      else {
        if ($this->show_warnings) {
          if ($resource_id !== 'Not in Fedora') {
            \Drupal::logger('islandora_riprap')->error('HTTP response code returned by Riprap for request to @resource_id: @code', array('@code' => $code, '@resource_id' => $resource_id));
            drupal_set_message(t("Riprap appears to not have any events for @resource_id", array('@resource_id' => $resource_id)), 'warning', TRUE);
          }
        }
      }
    }
    catch (RequestException $e) {
       \Drupal::logger('islandora_riprap')->error($e->getMessage());
       drupal_set_message(t("Sorry, there has been an error connecting to Riprap, please refer to the system log"), 'error');
    }
  }

}


