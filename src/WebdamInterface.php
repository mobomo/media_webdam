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
   * Get details about the current Webdam account's subscription.
   *
   * @return \stdClass
   *   Subscription details.
   */
  public function getSubscriptionDetails();

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
   * Gets a webdam folder by its ID.
   *
   * @param int $folder_id
   *   The folder ID to recurse into. This is mostly for internal use.
   *
   * @return object
   *   Webdam folder.
   */
  public function getFolder($folder_id = NULL);

  /**
   * Uploads Assets to Webdam using the previously defined methods.
   *
   * @param string $file_uri
   *   The file URI.
   *  @param string $file_name
   *   The File filename.
   * @param int $folderID
   *   The Webdam folder ID.
   *
   * @return array
   *   Webdam response.
   */
  public function uploadAsset($file_uri, $file_name, $folderID = NULL);

}
