<?php

namespace Drupal\media_webdam;

use Drupal\Core\State\StateInterface;

class Metadata {

  /**
   * @var WebdamInterface $webdam
   */
  protected $webdam;

  /**
   * @var StateInterface $state
   */
  protected $state;

  /**
   * Metadata constructor.
   *
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(WebdamInterface $webdam, StateInterface $state) {
    $this->webdam = $webdam;
    $this->state = $state;
  }

  /**
   * Get a list of folders from Webdam and save them to Drupal's state.
   */
  public function saveFolderList() {
    $folder_list = $this->webdam->getFlattenedFolderList();
    $this->state->set('webdam_folders', $folder_list);
  }

}
