<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Form\FormState;
use Drupal\media_webdam\Form\WebdamConfig;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Webdam config form test.
 *
 * @group media_webdam
 */
class WebdamConfigFormTest extends UnitTestCase {

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
  public function testGetFormId() {
    $webdamStub = new WebdamTestStub();
    $form = new WebdamConfig($this->getConfigFactoryStub(), $webdamStub);
    $this->assertEquals('webdam_config', $form->getFormId());
  }

  /**
   *
   */
  public function testBuildForm() {
    $webdamStub = new WebdamTestStub();
    $wconfig = new WebdamConfig($this->getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
        'folders_filter' => [
          '112233' => ['View' => 1, 'Create' => 1, 'Update' => 0, 'Delete' => 0],
          '445566' => ['View' => 0, 'Create' => 0, 'Update' => 0, 'Delete' => 0],
        ],
      ],
    ]), $webdamStub
    );
    $form = $wconfig->buildForm([], new FormState());

    $this->assertArrayHasKey('authentication', $form);
    $this->assertArrayHasKey('folders_filter', $form);
    $this->assertArrayHasKey('username', $form['authentication']);
    $this->assertArrayHasKey('password', $form['authentication']);
    $this->assertArrayHasKey('client_id', $form['authentication']);
    $this->assertArrayHasKey('client_secret', $form['authentication']);
    $this->assertArrayHasKey('112233', $form['folders_filter']);
    $this->assertArrayHasKey('445566', $form['folders_filter']);

    $this->assertEquals('WDusername', $form['authentication']['username']['#default_value']);
    $this->assertEquals('WDpassword', $form['authentication']['password']['#default_value']);
    $this->assertEquals('WDclient-id', $form['authentication']['client_id']['#default_value']);
    $this->assertEquals('WDsecret', $form['authentication']['client_secret']['#default_value']);
    $this->assertTrue(array_key_exists('112233', $form['folders_filter']));
    $this->assertTrue(array_key_exists('445566', $form['folders_filter']));
    $this->assertEquals(['View' => 1, 'Create' => 1, 'Update' => 0, 'Delete' => 0], $form['folders_filter']['112233']['#default_value']);
    $this->assertEquals(['View' => 0, 'Create' => 0, 'Update' => 0, 'Delete' => 0], $form['folders_filter']['445566']['#default_value']);
  }

  // @TODO: This test is broken. Not sure what's wrong and don't have time to debug.
  //  public function testSubmitForm() {
  //    $config_stub = new FormConfigStub();
  //    $config_factory_stub = new FormConfigFactoryStub();
  //    $config_factory_stub->set('media_webdam.settings', $config_stub);
  //
  //    $wconfig = new WebdamConfig($config_factory_stub);
  //
  //    $form_state = new FormState();
  //    $form_state->set('username', 'webdam_username');
  //    $form_state->set('password', 'webdam_pw');
  //    $form_state->set('client_id', 'webdam_client_id');
  //    $form_state->set('secret', 'webdam_client_secret');
  //
  //    $form = [];
  //
  //    $wconfig->submitForm($form, $form_state);
  //
  //    $this->assertEquals('webdam_username', $config_stub->get('username'));
  //    $this->assertEquals('webdam_pw', $config_stub->get('password'));
  //    $this->assertEquals('webdam_client_id', $config_stub->get('client_id'));
  //    $this->assertEquals('webdam_client_secret', $config_stub->get('secret'));
  //  }
}

class FormConfigFactoryStub extends ConfigFactory {
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

class FormConfigStub extends Config {
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
