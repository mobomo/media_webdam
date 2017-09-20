<?php

namespace Drupal\Tests\media_acquia_dam\unit;

use Drupal\Core\Session\AccountProxy;
use Drupal\media_acquia_dam\ClientFactory;
use Drupal\media_acquia_dam\Webdam;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use GuzzleHttp\Client as GClient;

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
    $client_factory = new ClientFactory($this->getConfigFactoryStub(), new GClient(), $this->getMock(UserDataInterface::class), $this->getMock(AccountProxy::class));
    $webdam = new Webdam($client_factory, 'background');
    $this->assertInstanceOf('Drupal\media_acquia_dam\Webdam', $webdam);
  }

}
