<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;

/**
* Controller.
*/
class IslandoraRiprapChartController extends ControllerBase {
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
  }

  /**
   * Output the markup that Chart.js needs. The chart itself is rendered via Javascript.
   *
   * @return string
   */
   public function main() {
     $utils = \Drupal::service('islandora_riprap.utils');
     $output = $utils->getFixityEventsReportMarkup();
     return [
      '#type' => 'markup',
      '#markup' => $output,
      '#allowed_tags' => array_merge(Xss::getHtmlTagList(), ['canvas', 'p', 'div'])
     ];
   }
}

