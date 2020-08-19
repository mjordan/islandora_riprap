<?php

namespace Drupal\islandora_riprap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller.
 */
class IslandoraRiprapGetHashController extends ControllerBase {

  /**
   * Gets the hash of the file identified by the UUID.
   *
   * @param string $file_uuid
   *   The UUID of the file.
   * @param string $algorith
   *   One of 'md5', 'sha1', or 'sha256'.
   *
   * @return JsonResponse
   *   A JSON response. 
   */
  public function main($file_uuid, $algorithm) {
    $file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uuid' => $file_uuid]);
    $file = reset($file);    
    $checksum = hash_file($algorithm, $file->getFileUri());
    $response[] = [
      'checksum' => $checksum,
      'file_uuid' => $file_uuid,
      'algorithm' => $algorithm,
    ];

    return new JsonResponse($response);
  }

}
