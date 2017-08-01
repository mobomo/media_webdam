<?php

namespace Drupal\media_webdam;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media_webdam\WebdamInterface;

class WebdamFolderPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  protected $config_factory;
  protected $webdam;

  public function __construct(ConfigFactoryInterface $config_factory, WebdamInterface $webdam) {
    $this->config = $config_factory->get('media_webdam.settings');
    $this->webdam = $webdam;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('media_webdam.webdam')
    );
  }

  public function getPermissions() {
    // Get list of folders on Webdam.
    $webdamFolders = $this->webdam->getFlattenedFolderList();

    // Get local configuration for Webdam folders.
    $allFolders = $this->config->get('folders_filter');

    // Eliminate folders NOT enabled via WebdamConfig.
    $enabledFolders = array_filter($allFolders, function($v) { return $v != 0;});

    // Build permissions for each enabled folder.
    $permissions = [];
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

    return $permissions;
  }
}