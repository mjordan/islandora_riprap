<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller.
 */
class IslandoraRiprapGetHashController extends ControllerBase {

  /**
   * Gets the checksum of the file identified by the file_uri value.
   *
   * This endpoint uses two query parameters, 'file_uri' and
   * 'algorithm', to get a file entity's checksum. 'algorithm'
   * is one of 'md5', 'sha1', or 'sha256'.
   *
   * @return JsonResponse
   *   A JSON response with the keys below. 
   */
  public function main() {
    $file_uri = \Drupal::request()->query->get('file_uri');
    $algorithm = \Drupal::request()->query->get('algorithm');

    if (is_null($file_uri) || is_null($algorithm)) {
      return new JsonResponse(['error' => 'Request is missing either the "file_uri" or "algorithm" parameter.']);
    }
    if (!in_array($algorithm, ['md5', 'sha1', 'sha256'])) {
      return new JsonResponse(['error' => '"algorithm" parameter must be one of "md5", "sha1", or "sha256".']);
    }

    $file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_uri]);
    $file = reset($file);
    $checksum = hash_file($algorithm, $file->getFileUri());
    $response[] = [
      'checksum' => $checksum,
      'file_uuid' => $file->uuid(),
      'algorithm' => $algorithm,
      'uri' => $file->getFileUri(),
      'url' => file_create_url($file->getFileUri()),
    ];

    return new JsonResponse($response);
  }

}
