<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Form\FormState;
use Drupal\media_webdam\Form\WebdamUpload;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use cweagans\webdam\Client;
use Drupal\media_webdam\ClientFactory;
use Drupal\media_webdam\Webdam;

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
    $configFactory = $this->getConfigFactoryStub();
    $container->set('config.factory', $configFactory);
    $drupal_root = $this->root;

    require_once ($drupal_root . '/core/includes/file.inc');

    \Drupal::setContainer($container);
  }

  // Saves some typing.
  public function getConfigFactoryStub(array $configs = []) {
    return parent::getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
      'system.file' => [
        'path.temporary' => '/path/to/temp/dir'
      ]
    ]);
  }

  public function testConstructor() {
    $client_factory = new ClientFactory($this->getConfigFactoryStub(), new GClient());
    $webdam = new Webdam($client_factory);
    $this->assertInstanceOf('Drupal\media_webdam\Webdam', $webdam);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetFormId() {
    $WebdamStubTest = new WebdamStubTest();
    $entityTypeManager = new EntityTypeManagerTestStub();
    $form = new WebdamUpload($WebdamStubTest, $entityTypeManager);
    $this->assertEquals('webdam_upload', $form->getFormId());
  }

  /**
   * {@inheritdoc}
   */
  public function testBuildForm() {
    $WebdamStubTest = new WebdamStubTest();
    $entityTypeManager = new EntityTypeManagerTestStub();

    $form_array = new WebdamUpload($WebdamStubTest, $entityTypeManager);
    $form = $form_array->buildForm([], new FormState());

    $this->assertArrayHasKey('managed_file', $form['upload_media']);
    $this->assertArrayHasKey('webdam_folder', $form['upload_media']);

  }

  public function testSubmitForm() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "expires_in":3600, "token_type":"bearer", "refresh_token":"REFRESH_TOKEN"}'),
      new Response(200, [], '{"processId":"123456789","presignUrl":"123456789","confirm":200}'),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new GClient(['handler' => $handler]);
    $client = new Client($guzzleClient, '', '', '', '');

    $form_state = new FormState();
    $WebdamStubTest = new WebdamStubTest();

    $entityTypeManager = new EntityTypeManagerTestStub();

    $form_obj = new WebdamUpload($WebdamStubTest, $entityTypeManager);

    $form_state->set('webdam_folder', 123456);
    $form_state->set('managed_file', 2);

    $form = [];
    $form_obj->submitForm($form, $form_state);

    $this->assertEquals(123456, $form_state->get('webdam_folder'));
    $this->assertNotEmpty(2, $form_state->get('managed_file'));
    // @TODO: Test uploadAsset() method.
    // $upload = $client->uploadAsset($file_uri, $file_name, $folderID = NULL);
    // dump($upload);
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

/**
 * Webdam class stub.
 */
class WebdamStubTest implements WebdamInterface {

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

  /**
   * Implements uploadAsset method dor testing.
   *
   * @return string
   *   Webdam response.
   */
  public function uploadAsset($file_uri, $file_name, $folderID = NULL) {
    return '55697118';
  }

}

/**
 * Extends EntityTypeManager for tests.
 */
class EntityTypeManagerTestStub extends EntityTypeManager {

  public function __construct() {}

  public function getStorage($entity_type){}

}
