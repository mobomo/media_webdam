<?php
/**
 * Created by PhpStorm.
 * User: cweagans
 * Date: 7/18/17
 * Time: 17:01
 */

namespace Drupal\media_webdam;


/**
 * Class Webdam.
 *
 * Abstracts away details of the REST API.
 *
 * @package Drupal\media_webdam
 */
interface WebdamInterface {

  /**
   * Get a list of folders keyed by ID.
   *
   * @param int $folder_id
   *   The folder ID to recurse into. This is mostly for internal use.
   *
   * @return array
   *   A list of folder names keyed by folder IDs.
   */
  public function getFlattenedFolderList($folder_id = NULL);

  /**
   * Passes method calls through to the webdam client object.
   *
   * @param $name
   *   The name of the method to call.
   * @param $arguments
   *   An array of arguments.
   *
   * @return mixed
   *   Returns whatever the webdam client returns.
   */
  public function __call($name, $arguments);
}
