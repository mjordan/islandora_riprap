<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\islandora_riprap\Riprap\Riprap;
use Drupal\islandora_riprap\Riprap\IslandoraRiprapUtils;

/**
* Controller.
*/
class IslandoraRiprapPremisController extends ControllerBase {
  public function __construct() {
    $config = \Drupal::config('islandora_riprap.settings');
    $this->riprap_endpoint = $config->get('riprap_rest_endpoint') ?: 'http://localhost:8000/api/fixity';
    $this->number_of_events = $config->get('number_of_events') ?: 10;
    $this->use_drupal_urls = $config->get('use_drupal_urls') ?: FALSE;
    $this->gemini_endpoint = $config->get('gemini_rest_endpoint') ?: 'http://localhost:8000/gemini';
  }

  /**
   * Get the Riprap data for the current Media entity and render it as Turtle.
   *
   * @return string
   */
   public function main() {
     $current_path = \Drupal::service('path.current')->getPath();
     $path_args = explode('/', $current_path);
     $mid = $path_args[2];

     $output = '@prefix premis: <http://www.loc.gov/premis/rdf/v3/> .' . "\n";
     $output .= "\n";
     $output .= '<http://example.com/a/resource/uri>';

     $response = new Response($output, 200);
     $response->headers->set("Content-Type", 'text/turtle');
     return $response;
   }

}

