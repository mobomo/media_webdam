<?php

namespace Drupal\Tests\media_webdam\unit;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\media_webdam\WebdamClient;

/**
 * Webdam test.
 *
 * @group media_webdam_broken
 */
class WebdamClientTest extends UnitTestCase {

  protected $webdamSession;

  /**
   * Test Webdam credentials.
   */
  public function testCredentials() {
    $mock = new MockHandler([
      new Response(200, [], '{"testResponse": "success"}'),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handler]);

    $config = $this->stubConfig();

    $this->assertEquals(
      $config->get('media_webdam.settings')->get('username'),
      'testName',
      'The username is testName'
      );
  }

  /**
   * Test api call for authorization token.
   */
  public function testAuthenticate() {
    $mock = new MockHandler([
      new Response(200, [],
        '{
          "access_token":"ACCESS_TOKEN",
          "expires_in":3600,
          "token_type":"bearer",
          "refresh_token":"REFRESH_TOKEN"
        }'
      ),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handler]);

    $config = $this->stubConfig();
    $webdamSession = new WebdamClient($config, $guzzleClient);
    $auth = $webdamSession->authenticate();

    $this->assertEquals($auth->access_token, "ACCESS_TOKEN", 'Authentication API calls returns token.');
  }

  /**
   * Test api call for top-level folders.
   */
  public function testFolders() {
    $mock = new MockHandler([
      new Response(200, [],
        '[
          {"name": "folderOne", "id": "221221", "numchildren": "0", "folders": []},
          {"name": "folderTwo", "id": "112112", "numchildren": "0", "folders": []}
        ]'
      ),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handler]);

    $config = $this->stubConfig();
    $webdamSession = new WebdamClient($config, $guzzleClient);

    $token = $this->stubToken();
    $folders = $webdamSession->getFolders($token);
    $this->assertEquals($folders['status'], 200, 'Folders API call returns status of 200.');
    $this->assertEquals(count($folders['folders']), 2, 'Folder API call returns multiple folders.');
  }

  /**
   * Test api call for info on one folder.
   */
  public function testFolderInfo() {
    $folderID = '221221';
    $mock = new MockHandler([
      new Response(200, [],
        '{"name": "folderOne", "id": "' . $folderID . '", "numchildren": "0", "folders": []}'
      ),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handler]);

    $config = $this->stubConfig();
    $webdamSession = new WebdamClient($config, $guzzleClient);

    $token = $this->stubToken();
    $folder = $webdamSession->getFolder($folderID, $token);
    $this->assertEquals($folder['status'], 200, 'Folder API call returns status of 200.');
    $this->assertEquals($folder['folder']->id, $folderID, 'Folder API call returns correct folder info.');
  }

  /**
   * Test api call for info on items in one folder.
   */
  public function testFolderAssets() {
    $folderID = '12345';
    $mock = new MockHandler([
      new Response(200, [],
        '{
          "total_count": 2,
          "items": [
            {"filename": "itemOne", "id": "221221", "folder": {"id": "' . $folderID . '"}},
            {"filename": "itemTwo", "id": "112112", "folder": {"id": "' . $folderID . '"}}
            ]
          }'
      ),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handler]);

    $config = $this->stubConfig();
    $webdamSession = new WebdamClient($config, $guzzleClient);

    $token = $this->stubToken();
    $folder = $webdamSession->getFolderItems($folderID, $token);
    $this->assertEquals($folder['status'], 200, 'Folder items API call returns status of 200.');
    $this->assertEquals(count($folder['items']->items), 2, 'Folder items API call returns info on multiple assets.');
  }

  /**
   * Test api call for info on one asset.
   */
  public function testAssetInfo() {
    $mock = new MockHandler([
      new Response(200, [],
        '{"id": "221221", "type": "asset", "filename": "itemOne.jpg"}'
      ),
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handler]);

    $config = $this->stubConfig();
    $webdamSession = new WebdamClient($config, $guzzleClient);

    $token = $this->stubToken();
    $asset = $webdamSession->getAssetInfo('12345', $token);
    $this->assertEquals($asset['status'], 200, 'Asset API call returns status of 200.');
    $this->assertTrue(property_exists($asset['asset'], 'id'), 'Asset API response has an id property.');
  }

  /**
   * Helper function to mock webdam credentials as config.
   */
  public function stubConfig() {
    return $this->getConfigFactoryStub(
      [
        'media_webdam.settings' =>
        [
          'username' => 'testName',
          'password' => 'testPass',
          'client_id' => '12345',
          'secret' => '67890',
        ],
      ]
    );
  }

  /**
   * Helper function to mock token.
   */
  public function stubToken() {
    $token = new \stdClass();
    $token->access_token = 'abc123';
    return $token;
  }

}
