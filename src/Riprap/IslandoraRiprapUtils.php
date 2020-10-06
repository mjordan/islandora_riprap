<?php

namespace Drupal\islandora_riprap\Riprap;

use Drupal\media\Entity\Media;
use Drupal\Core\Link;

/**
 * Islandora-specific utilities for interacting with Riprap.
 */
class IslandoraRiprapUtils {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->config = \Drupal::config('islandora_riprap.settings');
    $this->gemini_endpoint = $this->config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';
    $this->riprap = \Drupal::service('islandora_riprap.riprap');
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
    if ($this->config->get('media_fields')) {
      $media_fields_config = $this->config->get('media_fields');
      $media_fields = preg_split('/\n/', $media_fields_config);
    }
    else {
      $media_fields = [
        'field_media_file',
	'field_media_document',
	'field_media_image',
	'field_media_audio_file',
	'field_media_video_file',
      ];
    }
    $media = Media::load($mid);
    // Loop through each of the media fields and get the UUID of the File
    // in the first one encountered. Assumes each Media entity has only
    // one of the media file fields.
    foreach ($media_fields as $media_field) {
      $media_field = trim($media_field);

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
   *   The Fedora URL corresponding to the UUID, or a message.
   */
  public function getFedoraUrl($uuid) {
    try {
      $container = \Drupal::getContainer();
      $jwt = $container->get('jwt.authentication.jwt');
      $auth = 'Bearer ' . $jwt->generateToken();
      $client = \Drupal::httpClient();
      $options = [
        'http_errors' => FALSE,
        'headers' => ['Authorization' => $auth],
      ];
      $url = $this->gemini_endpoint . '/' . $uuid;
      $response = $client->request('GET', $url, $options);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $body = $response->getBody()->getContents();
        $body_array = json_decode($body, TRUE);
        return $body_array['fedora'];
      }
      elseif ($code == 404) {
        $body = $response->getBody()->getContents();
        return 'Not in Fedora';
      }
      else {
        \Drupal::logger('islandora_riprap')->error('HTTP response code: @code', ['@code' => $code]);
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
   *   The local Drupal URL of the file associated with the
   *   incoming Media entity.
   */
  public function getLocalUrl($mid) {
    $media_fields = [
      'field_media_file',
      'field_media_image',
      'field_media_audio_file',
      'field_media_video_file',
    ];
    $media = Media::load($mid);
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
   * Generates link to the failed fixity events report page.
   *
   * @return string
   *   String version of \Drupal\Core\Url Link object.
   */
  public function getLinkToFailedFixityEventsReport() {
    $chart_link = Link::createFromRoute('Failed fixity check events report',
      'islandora_riprap.events_report',
      [],
      ['attributes' => ['target' => '_blank', 'title' => t('This report will open in a new tab')]]
    );
    return $chart_link->toString();
  }

  /**
   * Formats Riprap events for use in Chart.js.
   *
   * @return array
   *   Array of yyyy-mm month keys with number of failed events as values.
   */
  public function getFailedFixityEventsReportData() {
    $event_data = $this->riprap->getEvents(['output_format' => 'json', 'outcome' => 'fail']);
    $event_data_array = json_decode($event_data, TRUE);
    $months = [];
    foreach ($event_data_array as $event) {
      $month = preg_replace('/\-\d\dT.+$/', '', $event['timestamp']);
      if (in_array($month, array_keys($months))) {
        $months[$month]++;
      }
      else {
        $months[$month] = 0;
      }
    }
    return $months;
  }

  /**
   * Generates sample failed fixity events data.
   *
   * @return array
   *   Array of yyyy-mm month keys with number of failed events as values.
   */
  public function getSampleFailedFixityEventsReportData() {
    $current_year = date('Y');
    // 3 year's worth of data.
    $years = range($current_year, $current_year - 2);
    $months = [];
    foreach ($years as $year) {
      for ($m = 1; $m <= 12; $m++) {
        $date = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $months[$date] = 0;
      }
    }

    $months_with_failures = array_rand($months, 6);
    foreach ($months_with_failures as $month) {
      $months[$month] = rand(1, 10);
    }

    return $months;
  }

}
