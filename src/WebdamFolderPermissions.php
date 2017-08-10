<?php

namespace Drupal\media_webdam;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media_webdam\WebdamInterface;

class WebdamFolderPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * A config object to retrieve Webdam folder info.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * Drupal\media_webdam\WebdamInterface definition.
   *
   * @var \Drupal\media_webdam\WebdamInterface
   */
  protected $webdam;

  /**
   * WebdamFolderPermissions contructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config object to retrieve local settings for Webdam folder.
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   *   Webdam interface to query for folders.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebdamInterface $webdam) {
    $this->config = $config_factory->get('media_webdam.settings');
    $this->webdam = $webdam;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('media_webdam.webdam')
    );
  }

  /**
   * Return array of local permissions for individual Webdam folders enabled via WebdamConfig.
   */
  public function getPermissions() {
    $permissions = [];

    // Eliminate folders NOT enabled via WebdamConfig.
    $enabledFolders = $this->getEnabledFolders();

    if (!empty($enabledFolders)) {
      // Get list of folders on Webdam.
      $webdamFolders = $this->webdam->getFlattenedFolderList();

      // Build permissions for each enabled folder.
      foreach ($enabledFolders as $k => $v) {
        $permissions['view ' . $k] = [
          'title' => $this->t('<em>@name</em>: View', ['@name' => $webdamFolders[$k]]),
          'description' => $this->t('View assets in folder @name.', ['@name' => $webdamFolders[$k]]),
        ];
        $permissions['create ' . $k] = [
          'title' => $this->t('<em>@name</em>: Create', ['@name' => $webdamFolders[$k]]),
          'description' => $this->t('Create assets in folder @name.', ['@name' => $webdamFolders[$k]]),
        ];
        $permissions['update ' . $k] = [
          'title' => $this->t('<em>@name</em>: Update', ['@name' => $webdamFolders[$k]]),
          'description' => $this->t('Update assets to folder @name.', ['@name' => $webdamFolders[$k]]),
        ];
        $permissions['delete ' . $k] = [
          'title' => $this->t('<em>@name</em>: Delete', ['@name' => $webdamFolders[$k]]),
          'description' => $this->t('Delete assets in folder @name.', ['@name' => $webdamFolders[$k]]),
        ];
      }
    }

    return $permissions;
  }

  /**
   * Return array of Webdam folders enabled via WebdamConfig. 
   */
  public function getEnabledFolders() {
    // Get local configuration for Webdam folders.
    $allFolders = $this->config->get('folders_filter');
    // Eliminate folders NOT enabled via WebdamConfig.
    $enabledFolders = array_filter($allFolders);
    return $enabledFolders;
  }
}