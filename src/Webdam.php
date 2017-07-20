<?php

namespace Drupal\media_webdam;

/**
 * Class Webdam.
 *
 * Abstracts away details of the REST API.
 *
 * @package Drupal\media_webdam
 */
class Webdam implements WebdamInterface {

  /**
   * A webdam HTTP client.
   *
   * @var \cweagans\webdam\Client
   */
  protected $client;

  /**
   * Webdam constructor.
   *
   * @param \Drupal\media_webdam\ClientFactory $client_factory
   *   An instance of ClientFactory that we can get a webdam client from.
   */
  public function __construct(ClientFactory $client_factory) {
    $this->client = $client_factory->get();
  }

  /**
   * Get details about the current Webdam account's subscription.
   *
   * @return \stdClass
   *   Subscription details.
   */
  public function getSubscriptionDetails() {
    return $this->client->getAccountSubscriptionDetails();
  }

  /**
   * Get a list of folders keyed by ID.
   *
   * @param int $folder_id
   *   The folder ID to recurse into. This is mostly for internal use.
   *
   * @return array
   *   A list of folder names keyed by folder IDs.
   */
  public function getFlattenedFolderList($folder_id = NULL) {
    $folder_data = [];

    if (is_null($folder_id)) {
      $folders = $this->client->getTopLevelFolders();
    }
    else {
      $folders = $this->client->getFolder($folder_id)->folders;
    }

    foreach ($folders as $folder) {
      $folder_data[$folder->id] = $folder->name;

      $folder_list = $this->getFlattenedFolderList($folder->id);

      foreach ($folder_list as $folder_id => $folder_name) {
        $folder_data[$folder_id] = $folder_name;
      }
    }

    return $folder_data;
  }

}
