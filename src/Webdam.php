<?php

namespace Drupal\media_webdam;

/**
 * Class Webdam.
 *
 * Abstracts away details of the REST API.
 *
 * @package Drupal\media_webdam
 */
class Webdam {

  /**
   * A webdam HTTP client.
   *
   * @var \cweagans\webdam\Client
   */
  protected $client;

  /**
   * Webdam constructor.
   *
   * @param \Drupal\media_webdam\ClientFactory $client_factory
   *   An instance of ClientFactory that we can get a webdam client from.
   */
  public function __construct(ClientFactory $client_factory) {
    $this->client = $client_factory->get();
  }

  /**
   * Get details about the current Webdam account's subscription.
   *
   * @return \stdClass
   *   Subscription details.
   */
  public function getSubscriptionDetails() {
    return $this->client->getAccountSubscriptionDetails();
  }

}
