<?php

namespace Drupal\islandora_riprap\Riprap;

use Symfony\Component\Process\Process;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Utilities for interacting with a Riprap fixity microservice.
 */
class Riprap {

  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_mode = $config->get('riprap_mode') ?: 'remote';
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->riprap_local_directory = $config->get('riprap_local_directory') ?: '';
    $this->riprap_local_settings_file = $config->get('riprap_local_settings_file') ?: '';
    $this->show_warnings = !$config->get('show_riprap_warnings') ? $config->get('show_riprap_warnings') : TRUE;
  }

  /**
   * Queries a remote copy of Riprap using its REST interface for fixity events.
   *
   * @param array $options
   *   Associative array with keys that are Ripraps's REST and CLI parameters.
   *
   * @return string|bool
   *   The raw JSON response body, or false.
   */
  public function getEvents(array $options) {
    if ($this->riprap_mode == 'local') {
      return $this->getEventsFromLocalInstance($options);
    }

    try {
      $resource_id = $options['resource_id'];
      // Assumes Riprap requires no authentication
      // (e.g., it's behind the Symfony or other firewall).
      $client = \Drupal::httpClient();
      $options = [
        'http_errors' => FALSE,
        'headers' => ['Resource-ID' => $resource_id],
        'query' => $options,
      ];
      $response = $client->request('GET', $this->riprap_endpoint, $options);
      $code = $response->getStatusCode();
      // Note: Riprap returns a 200 even if there are no events for
      // $resource_id. However, in this case, the response body will
      // be an empty array.
      if ($code == 200) {
        $body = $response->getBody()->getContents();
        return $body;
      }
      elseif ($code == 404) {
        \Drupal::logger('islandora_riprap')->error('Riprap service not running or found at @endpoint', ['@endpoint' => $this->riprap_endpoint]);
        // This is a special 'response' indicating that Riprap
        // was not found (or is not running) at its configured URL.
        $status_message = t("Riprap not found or is not running at @endpoint", ['@endpoint' => $this->riprap_endpoint]);
        return json_encode(['riprap_status' => 404, 'message' => $status_message]);
      }
      else {
        \Drupal::messenger()->addMessage($this->t('Riprap does not have any fixity events for @resource_id.', ['@resource_id' => $resource_id]), 'warning');
        if ($this->show_warnings) {
          if ($resource_id !== 'Not in Fedora') {
            \Drupal::logger('islandora_riprap')->error('HTTP response code returned by Riprap for request to @resource_id: @code', ['@code' => $code, '@resource_id' => $resource_id]);
          }
        }
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('islandora_riprap')->error($e->getMessage());
      \Drupal::messenger()->addMessage($this->t('Sorry, there has been an error connecting to Riprap, please refer to the system log.'), 'error');
    }
  }

  /**
   * Queries a local copy of Riprap using its command-line interface.
   *
   * @param array $options
   *   Associative array with keys that are Ripraps's REST and CLI parameters.
   *
   * @return string|bool
   *   The raw JSON output, or false.
   */
  public function getEventsFromLocalInstance(array $options) {
    $riprap_cmd = ['./bin/console', 'app:riprap:get_events'];
    foreach ($options as $option_name => $option_value) {
      $riprap_cmd[] = '--' . $option_name . '=' . $option_value;
    }
    // @todo: limit is not being applied.
    $process = new Process($riprap_cmd);
    $process->setWorkingDirectory($this->riprap_local_directory);
    $process->run();

    if ($process->isSuccessful()) {
      return $process->getOutput();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Executes Riprap's check_fixty command, if running in 'local' mode.
   *
   * Used in hook_cron().
   *
   * @return bool
   *   TRUE if the command executed, FALSE if not.
   */
  public function executeRiprapCheckFixity() {
    $riprap_cmd = [
      './bin/console',
      'app:riprap:check_fixity',
      '--settings=' . $this->riprap_local_settings_file,
    ];

    $process = new Process($riprap_cmd);
    $process->setWorkingDirectory($this->riprap_local_directory);
    $process->run();

    if ($process->isSuccessful()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
