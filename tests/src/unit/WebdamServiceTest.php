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

  public function testGetSubscriptionDetails() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "expires_in":3600, "token_type":"bearer", "refresh_token":"REFRESH_TOKEN"}'),
      new Response(200, [], '{"maxAdmins": "5","numAdmins": "4","maxContributors": "10","numContributors": 0,"maxEndUsers": "15","numEndUsers": 0,"maxUsers": 0,"url": "accounturl.webdamdb.com","username": "username","planDiskSpace": "10000 MB","currentDiskSpace": "45 MB","activeUsers": "4","inactiveUsers": 0}')
    ]);
    $handler = HandlerStack::create($mock);
    $guzzleClient = new GClient(['handler' => $handler]);
    $client_factory = new ClientFactory($this->getConfigFactoryStub(), $guzzleClient);
    $webdam = new Webdam($client_factory);

    $subscription_details = $webdam->getSubscriptionDetails();
    $this->assertEquals("5", $subscription_details->maxAdmins);

    // Since this is directly passed through to the HTTP client lib, we don't
    // need to test it in great detail. We're only concerned with testing the
    // code written specifically for this module.
  }

}
