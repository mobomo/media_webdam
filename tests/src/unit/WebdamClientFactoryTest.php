<?php

namespace Drupal\Tests\media_acquia_dam\unit;

use Drupal\media_acquia_dam\ClientFactory;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client as GClient;

/**
 * Webdam client factory test.
 *
 * @group media_acquia_dam
 */
class WebdamClientFactoryTest extends UnitTestCase {

  public function testFactory() {
    $config_factory = $this->getConfigFactoryStub([
      'media_acquia_dam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ]
    ]);
    $guzzle_client = new GClient();
    $client_factory = new ClientFactory($config_factory, $guzzle_client);

    $client = $client_factory->get();

    $this->assertInstanceOf('cweagans\webdam\Client', $client);
  }

}
