<?php

/**
 * @file
 * Contains Drush command definitions for media_webdam.
 */

/**
 * Implements hook_drush_command().
 */
function media_webdam_drush_command() {
  $commands['webdam-folder-import'] = [
    'description' => 'Imports list of folders from Webdam',
    'aliases' => 'wdfi',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  ];

  return $commands;
}

/**
 * Drush command callback for webdam-folder-import.
 */
function drush_media_webdam_folders_import() {
  /** @var \Drupal\media_webdam\Metadata $metadata */
  $metadata = \Drupal::service('media_webdam.metadata');
  $metadata->saveFolderList();
}
