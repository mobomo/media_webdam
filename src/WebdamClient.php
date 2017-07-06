<?php

namespace Drupal\media_webdam;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Custom class to handle Webdam api functions.
 */
class WebdamClient {

  public $username;
  public $password;
  public $client;
  public $secret;
  public $token;
  public $error;
  public $httpClient;

  /**
   * Construct function.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $config           = $config_factory->get('media_webdam.settings');
    $this->username   = $config->get('username');
    $this->password   = $config->get('password');
    $this->client     = $config->get('client_id');
    $this->secret     = $config->get('secret');
    $this->httpClient = $http_client;
  }

  /**
   * Gather credentials from settings config and generate token.
   */
  public function authenticate() {
    $url = 'https://apiv2.webdamdb.com/oauth2/token';
    $data = array(
      'grant_type'    => 'password',
      'username'      => $this->username,
      'password'      => $this->password,
      'client_id'     => $this->client,
      'client_secret' => $this->secret,
    );
    $query = UrlHelper::buildQuery($data);
    $options = array(
      'data' => $query,
    );

    $client = $this->httpClient;
    try {
      $res = $client->post($url, array(
        'form_params' => $data,
      ));
      $authentication = json_decode($res->getBody());
      $this->token = json_decode($res->getBody())->access_token;
      return $authentication;
    }
    catch (RequestException $e) {
      $this->error = $e;
      return FALSE;
    }
  }

  /**
   * Return full info on Webdam asset.
   */
  public function getAssetInfo($assetId, $token = NULL) {
    $url = 'https://apiv2.webdamdb.com/assets/' . $assetId;
    $auth = $token != NULL ? $token : $this->authenticate();

    if ($auth->access_token) {
      $client = $this->httpClient;
      try {
        $res = $client->get($url, array(
          'headers' => array(
            'Authorization' => 'Bearer ' . $auth->access_token,
          ),
        ));
        return [
          'status' => $res->getStatusCode(),
          'asset' => json_decode($res->getBody()),
        ];
      }
      catch (RequestException $e) {
        return [
          'error' => json_decode($e),
        ];
      }
    }
    else {
      return [
        'error' => 'Failed to generate access token.',
      ];
    }
  }

  /**
   * Fetch data for Webdam folders.
   */
  public function getFolders($token = NULL) {
    $url = 'https://apiv2.webdamdb.com/folders';
    $auth = $token != NULL ? $token : $this->authenticate();

    if ($auth->access_token) {
      $client = $this->httpClient;
      try {
        $res = $client->get($url, array(
          'headers' => array(
            'Authorization' => 'Bearer ' . $auth->access_token,
          ),
        ));
        return [
          'status' => $res->getStatusCode(),
          'folders' => json_decode($res->getBody()),
        ];
      }
      catch (RequestException $e) {
        return [
          'error' => json_decode($e),
        ];
      }
    }
    else {
      return [
        'error' => 'Failed to generate access token.',
      ];
    }
  }

  /**
   * Fetch data for one Webdam folder, by ID.
   */
  public function getFolder($folderId, $token = NULL) {
    $url = 'https://apiv2.webdamdb.com/folders/' . $folderId;
    $auth = $token != NULL ? $token : $this->authenticate();

    if ($auth->access_token) {
      $client = $this->httpClient;
      try {
        $res = $client->get($url, array(
          'headers' => array(
            'Authorization' => 'Bearer ' . $auth->access_token,
          ),
        ));
        return [
          'status' => $res->getStatusCode(),
          'folder' => json_decode($res->getBody()),
        ];
      }
      catch (RequestException $e) {
        return [
          'error' => json_decode($e),
        ];
      }
    }
    else {
      return [
        'error' => 'Failed to generate access token.',
      ];
    }
  }

  /**
   * Fetch folder items.
   */
  public function getFolderItems($folderId, $token = NULL) {
    $url = 'https://apiv2.webdamdb.com/folders/' . $folderId . "/assets";
    $auth = $token != NULL ? $token : $this->authenticate();

    if ($auth->access_token) {
      $header = 'Authorization: Bearer ' . $auth->access_token;
      $client = $this->httpClient;
      try {
        $res = $client->get($url, array(
          'headers' => array(
            'Authorization' => 'Bearer ' . $auth->access_token,
          ),
        ));
        return [
          'status' => $res->getStatusCode(),
          'items' => json_decode($res->getBody()),
        ];
      }
      catch (RequestException $e) {
        $message['error'] = "Failure: " . $e;
      }
    }
    else {
      return [
        'error' => 'Failed to generate access token.',
      ];
    }
  }

}
