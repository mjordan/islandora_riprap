<?php

namespace Drupal\islandora_riprap\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field plugin that renders data for Media from Riprap.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("riprap_results")
 */
class RiprapResults extends FieldPluginBase {

  /**
   * Leave empty to avoid a query on this field.
   *
   * @{inheritdoc}
   */
  public function query() {
    // I am empty.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $value) {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;

    $riprap = \Drupal::service('islandora_riprap.riprap');

    $media = $value->_entity;
    $mid = $media->id();

    if ($this->use_drupal_urls) {
      $binary_resource_url = $riprap->getLocalUrl($mid, FALSE);
    }
    else {
      $binary_resource_url = $riprap->getFedoraUrl($mid);
      if (!$binary_resource_url) {
        return [
          '#theme' => 'islandora_riprap_summary',
          '#content' => 'Not in Fedora',
          '#outcome' => NULL,
          '#mid' => NULL,
        ];
      }
    }

    $num_events = $config->get('number_of_events') ?: 10;
    $riprap_output = $riprap->getEvents([
      'limit' => $num_events,
      'sort' => 'desc',
      'output_format' => 'json',
      'resource_id' => $binary_resource_url,
    ]);
    $events = (json_decode($riprap_output, TRUE));

    // Look for events with an 'event_outcome' of 'fail'.
    $failed_events = 0;
    if (count($events) > 0) {
      foreach ($events as $event) {
        if ($event['event_outcome'] == 'fail') {
          $failed_events++;
        }
      }
    }

    // Set flag in markup so that our Javascript can set the color.
    if ($binary_resource_url == 'Not in Fedora') {
      $outcome = 'notinfedora';
      $mid = NULL;
    }
    else {
      if ($failed_events == 0) {
        $outcome = 'success';
      }
      else {
        $outcome = 'fail';
      }
    }

    if (count($events) == 0) {
      $outcome = 'noevents';
      // Show mid and indicate that file is not in
      // Riprap (e.g., 'No Riprap events for $mid').
      $binary_resource_url = 'No Riprap events for ' . $binary_resource_url;
      return [
        '#theme' => 'islandora_riprap_summary',
        '#content' => $binary_resource_url,
        '#outcome' => NULL,
        '#mid' => NULL,
      ];
    }

    // Not a Riprap event, but output that indicates Riprap
    // is not available at its configured endpoint URL.
    if (array_key_exists('riprap_status', $events) && $events['riprap_status'] == 404) {
      $binary_resource_url = $events['message'];
      $mid = NULL;
      $outcome = 'riprapnotfound';
    }

    return [
      '#theme' => 'islandora_riprap_summary',
      '#content' => $binary_resource_url,
      '#outcome' => $outcome,
      '#mid' => $mid,
    ];
  }

}
