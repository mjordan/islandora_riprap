<?php

namespace Drupal\islandora_riprap\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
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

    $media = $value->_entity;
    $mid = $media->id();
    $binary_resource_uuid = $utils->getFileUuid($mid);
    
    if ($this->use_drupal_urls) {
      $url = $utils->getLocalUrl($mid);
    }
    else {
      $url = $utils->getFedoraUrl($binary_resource_uuid);
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

}
