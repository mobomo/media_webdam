<?php

namespace Drupal\Tests\media_acquia_dam\unit;

use Drupal\Core\Session\AccountProxy;
use Drupal\media_webdam\ClientFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
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
    $client_factory = new ClientFactory($config_factory, $guzzle_client, $this->getMock(UserDataInterface::class), $this->getMock(AccountProxy::class));

    $client = $client_factory->get('background');

    $this->assertInstanceOf('cweagans\webdam\Client', $client);
  }

}
