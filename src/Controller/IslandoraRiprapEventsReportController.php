<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller.
 */
class IslandoraRiprapEventsReportController extends ControllerBase {

  /**
   * Output the markup that Chart.js needs.
   *
   * The chart itself is rendered via Javascript.
   *
   * @return string
   *   Themed markup used by the events report.
   */
  public function main() {
    return [
      '#theme' => 'islandora_riprap_events_report',
    ];

  }

}
