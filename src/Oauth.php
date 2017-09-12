<?php

/**
 * @file
 * Contains Drupal\media_acquia_dam\Oauth.
 */

namespace Drupal\media_acquia_dam;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

class Oauth implements OauthInterface {

  /**
   * The base URL to use for the Webdam API.
   *
   * @var string
   */
  protected $webdamApiBase = "https://apiv2.webdamdb.com";

  /**
   * The media_acquia_dam configuration.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * A URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * An HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  protected $httpClient;

  /**
   * Oauth constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   * @param \GuzzleHttp\ClientInterface $httpClient
   */
  public function __construct(ConfigFactory $config_factory, CsrfTokenGenerator $csrfTokenGenerator, UrlGeneratorInterface $urlGenerator, ClientInterface $httpClient) {
    $this->config = $config_factory->get('media_acquia_dam.settings');
    $this->csrfTokenGenerator = $csrfTokenGenerator;
    $this->urlGenerator = $urlGenerator;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthLink() {
    $client_id = $this->config->get('client_id');
    $token = $this->csrfTokenGenerator->get('media_acquia_dam.oauth');
    $redirect_uri = $this->urlGenerator->generateFromRoute('media_acquia_dam.auth_finish', [], ['absolute' => TRUE]);

    return "{$this->webdamApiBase}/oauth2/authorize?response_type=code&state={$token}&redirect_uri={$redirect_uri}&client_id={$client_id}";
  }

  /**
   * {@inheritdoc}
   */
  public function authRequestStateIsValid($token) {
    return $this->csrfTokenGenerator->validate($token, 'media_acquia_dam.oauth');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($auth_code) {
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->httpClient->post("{$this->webdamApiBase}/oauth2/token", [
      'form_params' => [
        'grant_type' => 'authorization_code',
        'code' => $auth_code,
        'redirect_uri' => 'http://webdam.dev/webdam/authFinish',
        'client_id' => $this->config->get('client_id'),
        'client_secret' => $this->config->get('secret'),
      ],
    ]);

    $body = (string) $response->getBody();
    $body = json_decode($body);

    return [
      'access_token' => $body->access_token,
      'expire_time' => time() + $body->expires_in,
    ];
  }

}