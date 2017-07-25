<?php

namespace Drupal\media_webdam;

use cweagans\webdam\Exception\InvalidCredentialsException;

/**
 * Class AdminSettings.
 *
 * Gets Webdam folders into config form as options.
 *
 * @package Drupal\media_webdam
 */
class AdminSettings {

  /**
   * @var WebdamInterface
   */
  protected $webdam;

  /**
   * AdminSettings constructor.
   *
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   */
  public function __construct(WebdamInterface $webdam) {
    $this->webdam = $webdam;
  }

  /**
   * Get a list of folders from Webdam and send to Settings Form.
   *
   * @return array
   *   List of folders as id => name
   */
  public function folderList() {

//     $folder_list = $this->webdam->getFlattenedFolderList();
    try {
      $folder_list = $this->webdam->getFlattenedFolderList();
    }
    catch (InvalidCredentialsException $e) {
      \Drupal::logger('folder_list')->error($e->getMessage());
    }

    return $folder_list;
  }

}
