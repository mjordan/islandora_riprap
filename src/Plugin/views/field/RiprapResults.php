<?php

namespace Drupal\islandora_riprap\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field plugin that renders data for Media from Riprap.
 *
 * @ViewsField("riprap_results")
 */
class RiprapResults extends FieldPluginBase {
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->gemini_endpoint = $config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $value) {
    $media = $value->_entity;
    $mid = $media->id();
    $uuid = $media->uuid();
    dd($mid . ':' . $this->gemini_endpoint . '/' . $uuid);
    $url = $this->getFedoraUrl($uuid);
    return $url;
  }

   /**
    * Get a Fedora URL for the entity from Gemini.
    *
    * @param string $uuid
    *   The entity's UUID.
    *
    * @return string
    *    The Fedora URL, or a message.
    */
  private function getFedoraUrl($uuid) {
    try {
      $container = \Drupal::getContainer();
      $jwt = $container->get('jwt.authentication.jwt');
      $auth = 'Bearer ' . $jwt->generateToken();
      $client = \Drupal::httpClient();
      $options = [
        'http_errors' => false,
        'headers' => ['Authorization' => $auth],
      ];
      $url = $this->gemini_endpoint . '/' . $uuid;
      $response = $client->request('GET', $url, $options);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $body = $response->getBody()->getContents();
        $body_array = json_decode($body, true);
        return $body_array['fedora'];
      }
      elseif ($code == 404) {
        $body = $response->getBody()->getContents();
        return 'Not in Fedora';
      }
      else {
        \Drupal::logger('islandora_riprap')->error('HTTP response code: @code', array('@code' => $code));
      }
    }
    catch (RequestException $e) {
       \Drupal::logger('islandora_riprap')->error($e->getMessage());
       return "Sorry, there has been an error, please refer to the system log";
    }
  }

}
