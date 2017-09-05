<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\media_webdam\ClientFactory;
use Drupal\media_webdam\Webdam;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;

/**
 * Webdam service test
 *
 * @group media_webdam
 */
class WebdamServiceTest extends UnitTestCase {

  // Saves some typing.
  public function getConfigFactoryStub(array $configs = []) {
    return parent::getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ]
    ]);
  }

  public function testConstructor() {
    $client_factory = new ClientFactory($this->getConfigFactoryStub(), new GClient());
    $webdam = new Webdam($client_factory);
    $this->assertInstanceOf('Drupal\media_webdam\Webdam', $webdam);
  }

}
