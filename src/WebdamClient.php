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
    $data = [
      'grant_type'    => 'password',
      'username'      => $this->username,
      'password'      => $this->password,
      'client_id'     => $this->client,
      'client_secret' => $this->secret,
    ];
    $query = UrlHelper::buildQuery($data);
    $options = [
      'data' => $query,
    ];

    $client = $this->httpClient;
    try {
      $res = $client->post($url, [
        'form_params' => $data,
      ]);
      try {
        // Response contains keys for access_token & refresh_token.
        // Also token_type: 'bearer'.
        $authentication = json_decode($res->getBody());
        $this->token = $authentication->access_token;
        return $authentication;
      }
      catch (RequestException $e) {
        return $e;
      }
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
        $res = $client->get($url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $auth->access_token,
          ],
        ]);
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
        $res = $client->get($url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $auth->access_token,
          ],
        ]);
        $folders_shallow = json_decode($res->getBody());
        $folders_deep = $this->extractFolders($folders_shallow, $token);
        return [
          'status' => $res->getStatusCode(),
          'folders' => $folders_shallow,
          'folder_list' => $folders_deep,
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
        $res = $client->get($url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $auth->access_token,
          ],
        ]);
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
        $res = $client->get($url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $auth->access_token,
          ],
        ]);
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

  /**
   * Using API result for 1st, 2nd level folders.
   *
   * Generates full list of Webdam folders.
   *
   * Must use additional queries for 3rd level, etc.
   */
  public function extractFolders($folder_list, $token = NULL) {
    $folders = [];
    foreach ($folder_list as $item) {
      // Property 'numchildren' is present on all items.
      if ($item->numchildren == 0) {
        $folders[$item->id] = [$item->name, NULL];
      }
      // Property 'folders' array is only present on top-level items.
      elseif (!empty($item->folders)) {
        $children = $this->extractFolders($item->folders);
        $folders[$item->id] = [$item->name, $children];
      }
      // Must query for second-level children or more.
      else {
        $folderInfo = $this->getFolder($item->id, $token)['folder'];
        $children = isset($folderInfo->folders) ?
          $this->extractFolders($folderInfo->folders) :
          NULL;
        $folders[$item->id] = [$item->name, $children];
      }
    }
    return $folders;
  }

}
