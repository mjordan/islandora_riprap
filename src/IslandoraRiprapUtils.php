<?php

namespace Drupal\islandora_riprap;

/**
 * Utilities for interacting with a Riprap fixity microservice.
 */
class IslandoraRiprapUtils {

  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->gemini_endpoint = $config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';
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
  public function getFedoraUrl($uuid) {
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
   *   The local Drupal URL of the file associated with the incoming Media entity.
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

}
