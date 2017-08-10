<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\media_webdam\WebdamInterface;
use Drupal\media_webdam\WebdamFolderPermissions;
use Drupal\media_webdam\WebdamConfig;
use Drupal\Tests\UnitTestCase;

/**
 * Webdam folder permissions test.
 *
 * @group media_webdam
 */
class WebdamFolderPermissionsTest extends UnitTestCase {

  /**
   *
   */
  public function setUp() {
    parent::setUp();
    $container = new Container();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   *
   */
  public function testGetEnabledFolders() {
    $configStub = new ConfigStub();
    // Test for all folders enabled.
    $configStub->set('folders_filter', ['112233' => '112233', '445566' => '445566']);

    $this->assertEquals($configStub->get('folders_filter'), ['112233' => '112233', '445566' => '445566']);

    $configFactoryStub = new ConfigFactoryStub();
    $configFactoryStub->set('media_webdam.settings', $configStub);

    $this->assertEquals($configFactoryStub->get('media_webdam.settings'), $configStub);

    $webdamStub = new WebdamTestStub();
    $permissions = new WebdamFolderPermissions($configFactoryStub, $webdamStub);

    $this->assertEquals($permissions->getEnabledFolders(), ['112233' => '112233', '445566' => '445566']);
    $this->assertEquals(array_keys($permissions->getPermissions()), [
      'view 112233',
      'create 112233',
      'update 112233',
      'delete 112233',
      'view 445566',
      'create 445566',
      'update 445566',
      'delete 445566',
    ]);

    // Test for no folders enabled.
    $configStub->set('folders_filter', ['112233' => 0, '445566' => 0]);
    $permissions = new WebdamFolderPermissions($configFactoryStub, $webdamStub);
    
    $this->assertEquals($permissions->getEnabledFolders(), []);
    $this->assertEquals($permissions->getPermissions(), []);
  }

}

class ConfigFactoryStub extends ConfigFactory {
  protected $configs = [];
  public function __construct() {}
  public function get($name) {
    return $this->configs[$name];
  }
  public function getEditable($name) {
    return $this->configs[$name];
  }
  public function set($name, $config) {
    $this->configs[$name] = $config;
  }

}

class ConfigStub extends Config {
  protected $data = [];
  public function __construct() {}
  public function save($has_trusted_data = FALSE) {}
  public function set($key, $value) {
    $this->data[$key] = $value;
  }
  public function get($key = '') {
    return $this->data[$key];
  }

}

class WebdamTestStub implements WebdamInterface {

  public function getSubscriptionDetails() {
    return (object) array(
      'url'      => 'testurl.webdamdb.com',
      'username' => 'username',
    );
  }
  public function getFlattenedFolderList($folder_id = NULL) {
    return [
      112233 => "Wd Folder 1",
      445566 => "Wd Folder 2",
    ];
  }
  
}
