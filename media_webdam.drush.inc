<?php

/**
 * @file
 * Contains command definition for WebDAM folders import.
 */

/**
 * Implements hook_drush_command().
 */
function media_webdam_drush_command() {

  $commands['folders-import'] = [
    'description' => 'Imports folders from WebDAM',
    'aliases' => ['wdfi'],
    'examples' => [
      'drush wdfi' => 'Imports WebDAM folders into Drupal',
    ],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  ];

  return $commands;
}

/**
 * Drush command logic.
 *
 * Instances the WebdamClient class as a service.
 * Calls getFolders() method and stores results in State API.
 */
function drush_media_webdam_folders_import() {

  $wdSession = \Drupal::service('media_webdam.session');
  $getFolders = $wdSession->getFolders();

  $status = $getFolders['status'];
  $folders_deep = $getFolders['folder_list'];

  if ($status == '200') {
    if (!empty($folders_deep)) {
      $folders = [];
      $folders['wd.folders'] = $folders_deep;
      Drupal::state()->setMultiple($folders);
      // drush_log(var_dump($folders));
      drush_log(dt('Folders stored. You can get them using \'drush state-get wd.folders\''), 'ok');

    }
    else {
      drush_log(dt('No folders to retrieve.'), 'warning');
    }

  }
  else {
    drush_log(dt('Request failed. Status code: ' . $status), 'error');
  }

}
