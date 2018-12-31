<?php

namespace Drupal\islandora_riprap\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\islandora_riprap\Riprap\Riprap;
use Drupal\islandora_riprap\Riprap\IslandoraRiprapUtils;

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
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;

    $utils = new IslandoraRiprapUtils();
    $riprap = new Riprap();

    $media = $value->_entity;
    $mid = $media->id();
    $binary_resource_uuid = $utils->getFileUuid($mid);
    
    if ($this->use_drupal_urls) {
      $binary_resource_url = $utils->getLocalUrl($mid);
    }
    else {
      $binary_resource_url = $utils->getFedoraUrl($binary_resource_uuid);
    }

    $riprap_output = $riprap->getEvents($binary_resource_url);
    $events = (json_decode($riprap_output, true));

    // Look for events with an 'event_outcome' of 'fail'.
    $failed_events = 0;
    foreach ($events as $event) {
      if ($event['event_outcome'] == 'fail') {
        $failed_events++;
      }
    }

    // Set flag in markup so that our Javascript can set the color.
    if ($binary_resource_url == 'Not in Fedora') {
      $outcome = 'notinfedora';
      $mid = null;
    }
    else {
      if ($failed_events == 0) {
        $outcome = 'success';
      }
      else {
        $outcome = 'fail';
      }
    }

    return [
      '#theme' => 'islandora_riprap_summary',
      '#content' => $binary_resource_url,
      '#outcome' => $outcome,
      '#mid' => $mid,
    ];
  }

}
