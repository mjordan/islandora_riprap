<?php

/**
* Implements hook_views_data_alter().
*/
function islandora_riprap_views_data_alter(&$data) {
  $data['media']['riprap_results'] = [
    'title' => t('Riprap Results'),
    'group' => t('Media'),
    'help' => t('Shows results from the Riprap fixity microservice.'),
    'field' => [
      'id' => 'riprap_results',
    ],
  ];
}

