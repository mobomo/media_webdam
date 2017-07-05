<?php

namespace Drupal\media_webdam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\media_webdam\WebdamSession;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MediaWebdamController.
 *
 * @package Drupal\media_webdam\Controller
 */
class MediaWebdamController extends ControllerBase {

  protected $webdamSession;
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebdamSession $session, RequestStack $request) {
    $this->webdamSession = $session;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_webdam.session'),
      $container->get('request_stack')
    );
  }

  /**
   * Responds to router path /webdam/info/{assetId}.
   */
  public function getAsset($assetId) {
    $webdamSession = $this->webdamSession;
    $request = $webdamSession->getAssetInfo($assetId);

    if ($request['status'] == 200) {
      return new JsonResponse([
        'status' => $request['status'],
        'asset' => isset($request['asset']) ? $request['asset'] : NULL,
      ]);
    }
    else {
      return new JsonResponse([
        'status' => $request['status'],
        'error' => 'Failed to get asset info.',
      ]);
    }
  }

  /**
   * Endpoint to get all top-level folders.
   */
  public function getFolders() {
    $webdam_session = $this->webdamSession;
    $request = $webdam_session->getFolders();

    return new JsonResponse([
      'status' => $request['status'],
      'folders' => isset($request['folders']) ? $request['folders'] : NULL,
    ]);
  }

  /**
   * Endpoint to get folder by ID.
   */
  public function getFolder($folderId) {
    $webdam_session = $this->webdamSession;
    $request = $webdam_session->getFolder($folderId);

    return new JsonResponse([
      'status' => $request['status'],
      'folder' => isset($request['folder']) ? $request['folder'] : NULL,
    ]);
  }

  /**
   * Get folder items.
   */
  public function getFolderItems($folderId) {
    if ($folderId) {
      $webdam_session = $this->webdamSession;
      $request = $webdam_session->getFolderItems($folderId);

      return new JsonResponse([
        'status' => $request['status'],
        'items' => !empty($request['items']) ? $request['items'] : NULL,
      ]);
    }
    else {
      return new JsonResponse([
        'error' => 'Must pass a valid ID.',
      ]);
    }
  }

}
