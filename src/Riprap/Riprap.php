<?php

namespace Drupal\islandora_riprap\Riprap;

/**
 * Utilities for interacting with a Riprap fixity microservice.
 */
class Riprap {

  /**
   * Queries the Riprap microservice's REST interface for events about $resource_id.
   *
   * @param string $resource_id
   *   The URI of the resource.
   *
   * @return string
   *   The raw JSON response body.
   */
  public function getEvents($resource_id) {
    try {
      $client = \Drupal::httpClient();
      // Assumes Riprap requires no authentication (e.g., it's behind the Symfony or other firewall).
      $options = [
        'headers' => ['Resource-ID' => $url],
        'query' => ['limit' => $this->number_of_events, 'sort' => 'desc'],
      ];
      $response = $client->request('GET', $this->riprap_endpoint, $options);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $body = $response->getBody()->getContents();
      }
      else {
        \Drupal::logger('islandora_riprap')->error('HTTP response code returned by Riprap: @code', array('@code' => $code));
      }
    }
    catch (RequestException $e) {
       \Drupal::logger('islandora_riprap')->error($e->getMessage());
       return "Sorry, there has been an error connecting to Riprap, please refer to the system log";
    }

    return $body;
  }

}
