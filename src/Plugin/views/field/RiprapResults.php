<?php

namespace Drupal\islandora_riprap\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Field plugin that renders data for Media from Riprap.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("riprap_results")
 */
class RiprapResults extends FieldPluginBase {
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
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->gemini_endpoint = $config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';

    $media = $value->_entity;
    $mid = $media->id();
    $binary_resource_uuid = $this->getFileUuid($mid);
    
    if ($this->use_drupal_urls) {
      $url = $this->getLocalUrl($mid);
    }
    else {
      $url = $this->getFedoraUrl($binary_resource_uuid);
    }

    // @todo: clean up logic around assigning sucess and fail outcomes.
    // The outcome is passed to the theme and determines if the table
    // cell in the Manage Media View output is green or orange.
    if ($url == 'Not in Fedora') {
      $outcome = 'notinfedora';
      $mid = null;
    }
    else {
      $outcome = 'success';
    }

    return [
      '#theme' => 'islandora_riprap_summary',
      '#content' => $url,
      '#outcome' => $outcome,
      '#mid' => $mid,
    ];
  }

  /**
   * Given a Media id, get the corresponding File's UUID.
   *
   * @param int $mid
   *   A Media ID.
   *
   * @return string
   *   The UUID of the file associated with the incoming Media entity.
   */
  public function getFileUuid($mid) {
    $media_fields = array(
      'field_media_file',
      'field_media_image',
      'field_media_audio_file',
      'field_media_video_file',
    );
    $media = \Drupal\Media\Entity\Media::load($mid);
    // Loop through each of the media fields and get the UUID of the File
    // in the first one encountered. Assumes each Media entity has only
    // one of the media file fields.
    foreach ($media_fields as $media_field) {
      if (isset($media->$media_field)) {
        $files = $media->get($media_field);
        $file = $files->first();
        $target_file = $file->get('entity')->getTarget();
        $target_file_uuid_array = $target_file->get('uuid')->getValue();
        return $target_file_uuid_array[0]['value'];
      }
    }
  }

  /**
   * Get a Fedora URL for a File entity from Gemini.
   *
   * @param string $uuid
   *   The File entity's UUID.
   *
   * @return string
   *    The Fedora URL corresponding to the UUID, or a message.
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

  /**
   * Given a Media id, get the corresponding File's local Drupal URL.
   *
   * Used for files that are not stored in Fedora.
   *
   * @param int $mid
   *   A Media ID.
   *
   * @return string
   *   The local URL of the file associated with the incoming Media entity.
   */
  public function getLocalUrl($mid) {
    $media_fields = array(
      'field_media_file',
      'field_media_image',
      'field_media_audio_file',
      'field_media_video_file',
    );
    $media = \Drupal\Media\Entity\Media::load($mid);
    // Loop through each of the media fields and get the UUID of the File
    // in the first one encountered. Assumes each Media entity has only
    // one of the media file fields.
    foreach ($media_fields as $media_field) {
      if (isset($media->$media_field)) {
        $url = file_create_url($media->$media_field->entity->getFileUri());
        return $url;
      }
    }
  }

  /**
   * Query Riprap for fixity events on the given resource.
   *
   * @param int $url
   *   The Media resource's URL.
   *
   * @return string
   *   The JSON response from Riprap.
   */
  private function queryRiprap($url) {
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

/*
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
*/

