<?php

namespace Drupal\Tests\media_acquia_dam\unit;

use Drupal\media_acquia_dam\ClientFactory;
use Drupal\media_acquia_dam\Webdam;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;

/**
 * Webdam service test
 *
 * @group media_acquia_dam
 */
class WebdamServiceTest extends UnitTestCase {

  // Saves some typing.
  public function getConfigFactoryStub(array $configs = []) {
    return parent::getConfigFactoryStub([
      'media_acquia_dam.settings' => [
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
    $this->assertInstanceOf('Drupal\media_acquia_dam\Webdam', $webdam);
  }

}
