<?php

namespace Drupal\media_webdam;

use cweagans\webdam\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class ClientFactory.
 *
 * @package Drupal\media_webdam
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
   * ClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config object to retrieve Webdam auth information from.
   * @param \GuzzleHttp\ClientInterface $gclient
   *   A fully configured Guzzle client to pass to the webdam client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $gclient) {
    $this->config = $config_factory->get('media_webdam.settings');
    $this->client = $gclient;
  }

  /**
   * Creates a new Webdam client object.
   *
   * @return \cweagans\webdam\Client
   *   A configured Webdam HTTP client object.
   */
  public function get() {
    $client = new Client(
      $this->client,
      $this->config->get('username'),
      $this->config->get('password'),
      $this->config->get('client_id'),
      $this->config->get('secret')
    );

    return $client;
  }

}
