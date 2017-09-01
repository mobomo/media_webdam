<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Form\FormState;
use Drupal\media_webdam\Form\WebdamUpload;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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
    $entityTypeManager = new EntityTypeManagerTestStub();

    $container->set('config.factory', $configFactory);
    // $container->set('entity_type.manager', $entityTypeManager);
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

    $form_obj = new WebdamUpload($WebdamStubTest, $entityTypeManager);
    $form = $form_obj->buildForm([], new FormState());

    $this->assertArrayHasKey('managed_file', $form['upload_media']);
    $this->assertArrayHasKey('webdam_folder', $form['upload_media']);
    $this->assertEquals(FALSE, $form['upload_media']['managed_file']['#multiple']);

  }
  /**
  * Mocks drupal_set_message Drupal global function.
  * @return mixed
  *    String or null.
  */
  public function drupal_set_message() {

      return 'Filename has been successfully uploaded to Webdam!';

  }

  public function testSubmitForm() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "expires_in":3600, "token_type":"bearer", "refresh_token":"REFRESH_TOKEN"}'),
      new Response(200, [], file_get_contents(__DIR__ . '/json/presign.json')),
      new Response(200, [], '{"id":"1234567"}'),
      new Response(200, [], file_get_contents(__DIR__ . '/json/asset_uploaded.json')),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new GClient(['handler' => $handler]);
    $client = new Client($guzzleClient, '', '', '', '');

    // mocking form_state and webdam client.
    $form_state = new FormState();
    $WebdamStubTest = new WebdamStubTest();

    // Test entityManager getstorage(), load().
    // @todo: test save().
    $fileStorage = $this->getMock(EntityStorageInterface::class);
    $entityTypeManager = $this->getMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('file')
      ->willReturn($fileStorage);

    $form_obj = new WebdamUpload($WebdamStubTest, $entityTypeManager);

    // @todo: Test drupal_set_message.
    $message = $this->drupal_set_message();
    $this->assertEquals('Filename has been successfully uploaded to Webdam!', $message);

    // Test Webdam Folder and Managed File ID
    $form_state->setValue('webdam_folder', 123456);
    $form_state->setValue('managed_file', 2);

    $this->assertEquals(123456, $form_state->getValue('webdam_folder'));
    $this->assertNotEmpty(2, $form_state->getValue('managed_file'));

    // Test uploadAsset() method.
    $file_uri = __DIR__ . '/not_for_real.png';
    $file_name = basename($file_uri);
    $folderID = 112233;
    $client->uploadAsset($file_uri, $file_name, $folderID);

    // @todo: Test submitForm().
    // $form = $form_obj->buildForm([], $form_state);
    // $form_obj->submitForm($form, $form_state);

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

  public function getStorage($entity_type = 'file') {}

}

// @todo: Replace with messenger service after https://www.drupal.org/node/2278383.
// namespace Drupal\media_webdam\WebdamUpload;
//
// if (!function_exists('drupal_set_message')) {
//   function drupal_set_message() {}
// }
