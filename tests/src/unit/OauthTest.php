<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\media_webdam\Oauth;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use cweagans\webdam\Client;
use Drupal\media_webdam\OauthInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Oauth test.
 *
 * @group media_webdam
 */
class OauthTest extends UnitTestCase {

  /**
   * The base URL to use for the Webdam API.
   *
   * @var string
   */
  protected $webdamApiBase = "https://apiv2.webdamdb.comh";

  /**
   * The media_webdam configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $config;

  /**
   * A CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfTokenGenerator;

  /**
   * A URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * An HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $httpClient;

  /**
   * Destination URI after authentication is completed.
   *
   * @var string
   */
  protected $authFinishRedirect;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->config = $this->getConfigFactoryStub()->get('media_webdam.settings');

    $this->csrfTokenGenerator = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $token = 'testToken112233';
    $this->csrfTokenGenerator->expects($this->any())
      ->method('get')
      ->willReturn($token);

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturn('some/url/test');

//    $this->guzzle_client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
    $this->guzzle_client = new GClient();

    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->oAuth = new Oauth($this->getConfigFactoryStub(), $this->csrfTokenGenerator, $this->urlGenerator, $this->guzzle_client);

  }

  /**
   * {@inheritdoc}
   */
  public function getConfigFactoryStub(array $configs = []) {
    return parent::getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetAuthLink() {
    $authUrl = $this->oAuth->getAuthLink();

    $this->assertContains('some/url/test', $authUrl);
    $this->assertContains('testToken112233', $authUrl);
    $this->assertContains('WDclient-id', $authUrl);
    $this->assertContains('/oauth2/authorize', $authUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function testAuthRequestStateIsValid() {
    $token = 'testToken112233';
    $this->csrfTokenGenerator->expects($this->any())
      ->method('validate')
      ->with($token)
      ->willReturn(TRUE);

    $this->oAuth->authRequestStateIsValid($token);
    $this->assertTrue($this->csrfTokenGenerator->validate($token));
  }

  /**
   * {@inheritdoc}
   */
  public function testGetAccessToken($auth_code = '') {
    // @todo: This test is not testing the oauth class? To review and clarify.
    /*$mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
      new Response(200, [], '{"response_type":"code", "state":"z09GqyCtJFZa-BT2Lz1K_E5ngfCvObZqpGHJyjtlSzc"}'),
    ]);
    $handler = HandlerStack::create($mock);
    $httpClient = new GClient(['handler' => $handler]);
    $client = new Client($httpClient, '', '', '', '');

    // Debugging.
    $webdamConfig = $this->getConfigFactoryStub()->get('media_webdam.settings');
    $tokenGen = $this->csrfTokenGenerator->get('media_webdam.oauth');
    $authfinishRedirect = 'http://any.local.path/to-go';
    $urlGen = $this->urlGenerator->generateFromRoute('mocked.auth_finish', ['auth_finish_redirect' => $authfinishRedirect], ['absolute' => TRUE]);

    $auth_code = 'somedummycode123';
    $url = $this->webdamApiBase . '/oauth2/token';
    $data = [
      'grant_type' => 'authorization_code',
      'code' => $auth_code,
      'redirect_uri' => $urlGen,
      'client_id' => $this->config->get('client_id'),
      'client_secret' => $this->config->get('secret'),
    ];*/

  }

  /**
   * {@inheritdoc}
   */
  public function testSetAuthFinishRedirect() {

  }

}
