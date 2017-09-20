<?php

namespace Drupal\media_acquia_dam;

use cweagans\webdam\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class ClientFactory.
 *
 * @package Drupal\media_acquia_dam
 */
class ClientFactory {

  /**
   * A config object to retrieve Webdam auth information from.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A fully-configured Guzzle client to pass to the webdam client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $gclient;

  /**
   * A user data object to retrieve API keys from.
   *
   * @var UserDataInterface
   */
  protected $userData;

  /**
   * The current user.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config object to retrieve Webdam auth information from.
   * @param \GuzzleHttp\ClientInterface $gclient
   *   A fully configured Guzzle client to pass to the webdam client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $gclient, UserDataInterface $user_data, AccountProxyInterface $currentUser)  {
    $this->config = $config_factory->get('media_acquia_dam.settings');
    $this->client = $gclient;
    $this->userData = $user_data;
    $this->currentUser = $currentUser;
  }

  /**
   * Creates a new Webdam client object.
   *
   * @param string $credentials
   *   The switch for which credentials the client object should be configured with.
   *
   * @return \cweagans\webdam\Client
   *   A configured Webdam HTTP client object.
   */
  public function get($credentials = 'background') {
    $client = new Client(
      $this->client,
      $this->config->get('username'),
      $this->config->get('password'),
      $this->config->get('client_id'),
      $this->config->get('secret')
    );

    // Set the user's credentials in the client if necessary.
    if ($credentials == 'current') {
      $access_token = $this->userData->get('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token');
      $access_token_expiration = $this->userData->get('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token_expiration');
      $client->setToken($access_token, $access_token_expiration);
    }

    return $client;
  }

}
