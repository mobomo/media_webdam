<?php

namespace Drupal\media_acquia_dam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\media_acquia_dam\OauthInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for webdam routes.
 */
class OauthController extends ControllerBase {

  protected $webdamApiBase = "https://apiv2.webdamdb.com";

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_acquia_dam.oauth'),
      $container->get('request_stack'),
      $container->get('user.data'),
      $container->get('current_user')
    );
  }

  /**
   * The media_acquia_dam oauth service.
   *
   * @var OauthInterface $oauth
   */
  protected $oauth;

  /**
   * The current request object.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * UserData interface to handle storage of tokens.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * WebdamController constructor.
   *
   * @param \Drupal\media_acquia_dam\OauthInterface $oauth
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\user\UserDataInterface $user_data
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   */
  public function __construct(OauthInterface $oauth, RequestStack $request_stack, UserDataInterface $user_data, AccountProxyInterface $currentUser) {
    $this->oauth = $oauth;
    $this->request = $request_stack->getCurrentRequest();
    $this->userData = $user_data;
    $this->currentUser = $currentUser;
  }

  /**
   * Builds the Webdam auth page for a given user.
   *
   * Route: /user/{$user}/webdam
   */
  public function authPage(UserInterface $user) {
    // Users cannot access other users' auth forms.
    if ($user->id() !== $this->currentUser()->id()) {
      throw new NotFoundHttpException();
    }

    $access_token = $this->userData->get('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token');
    $access_token_expiration = $this->userData->get('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token_expiration');

    if ($access_token !== NULL && $access_token_expiration > time()) {
      return [
        [
          '#markup' => '<p>' . $this->t('You are authenticated with Webdam.') . '</p>',
        ],
        [
          '#markup' => $this->getLinkGenerator()->generate('Reauthenticate', Url::fromRoute('media_acquia_dam.auth_start')),
        ]
      ];
    }
    else {
      $this->userData->delete('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token');
      $this->userData->delete('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token_expiration');

      return [
        [
          '#markup' => '<p>' . $this->t('You are not authenticated with Webdam.') . '</p>',
        ],
        [
          '#markup' => $this->getLinkGenerator()->generate('Authenticate', Url::fromRoute('media_acquia_dam.auth_start')),
        ]
      ];
    }
  }

  /**
   * Redirects the user to the webdam auth url.
   *
   * Route: /webdam/authStart
   */
  public function authStart() {
    return new TrustedRedirectResponse($this->oauth->getAuthLink());
  }

  /**
   * Finish the authentication process.
   *
   * Route: /webdam/authFinish
   */
  public function authFinish() {
    if (!$this->oauth->authRequestStateIsValid($this->request->get('state'))) {
      throw new AccessDeniedHttpException();
    }

    $access_token = $this->oauth->getAccessToken($this->request->get('code'));

    $this->userData->set('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token', $access_token['access_token']);
    $this->userData->set('media_acquia_dam', $this->currentUser->id(), 'webdam_access_token_expiration', $access_token['expire_time']);

    return new RedirectResponse("/user/{$this->currentUser->id()}/webdam");
  }

}