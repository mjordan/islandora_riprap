<?php

/**
  * Implements hook_views_data_alter().
  */
function islandora_riprap_views_data_alter(array &$data) {
  $data['media']['riprap_results'] = array(
    'title' => t('Fixity auditing'),
    'help' => t('Shows results from the Riprap fixity auditing microservice.'),
    'field' => array(
      'id' => 'riprap_results',
    ),
  );
}

