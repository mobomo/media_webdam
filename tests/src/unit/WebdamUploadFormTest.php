<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Form\FormState;
use Drupal\media_webdam\Form\WebdamUpload;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Webdam config form test.
 *
 * @group media_webdam
 */
class WebdamUploadFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $container = new Container();
    $container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($container);

  }

  /**
   * {@inheritdoc}
   */
  public function testGetFormId() {
    $webdamStub = new WebdamTestStub();
    $configFactoryStub = new ConfigFactoryStub();
    $form = new WebdamUpload($webdamStub, $configFactoryStub);
    self::assertEquals('webdam_upload', $form->getFormId());
  }

  /**
   * {@inheritdoc}
   */
  public function testBuildForm() {
    $webdamStub = new WebdamTestStub();
    $configStub = new ConfigStub();
    $configFactoryStub = new ConfigFactoryStub();

    $configStub->set('folders_filter', [1234 => '1234', 5678 => '0']);
    $configFactoryStub->set('media_webdam.settings', $configStub);

    self::assertEquals($configStub->get('folders_filter'), [1234 => '1234', 5678 => '0']);
    self::assertEquals($configFactoryStub->get('media_webdam.settings'), $configStub);

    $form_array = new WebdamUpload($webdamStub, $configFactoryStub);
    $form = $form_array->buildForm([], new FormState());

    self::assertArrayHasKey('managed_file', $form['upload_media']);
    self::assertArrayHasKey('webdam_folder', $form['upload_media']);

  }

}

/**
 * ConfigFactory class stub for tests.
 */
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

/**
 * Config class stub for tests.
 */
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

class WebdamStub implements WebdamInterface {

  public function getSubscriptionDetails() {
    return (object) [
      'url'      => 'testurl.webdamdb.com',
      'username' => 'username',
    ];
  }

  public function getFlattenedFolderList($folder_id = NULL) {
    return [
      112233 => "Wd Folder 1",
      445566 => "Wd Folder 2",
    ];
  }

  public function getFolder($folder_id = NULL) {
    return (object) [
      '112233id' => 112233,
      'name' => "Wd Folder 1",
    ];
  }

}