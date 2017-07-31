<?php

namespace Drupal\media_webdam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use cweagans\webdam\Exception\InvalidCredentialsException;

/**
 * Class WebdamConfig.
 *
 * @package Drupal\media_webdam\Form
 */
class WebdamConfig extends ConfigFormBase {

  /**
   * Drupal\media_webdam\WebdamInterface definition.
   *
   * @var \Drupal\media_webdam\WebdamInterface
   */
  protected $webdam;

  /**
   * WebdamConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   *   The Webdam elements.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebdamInterface $webdam) {
    parent::__construct($config_factory);
    $this->webdam = $webdam;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('media_webdam.webdam')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webdam_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'media_webdam.settings',
    ];
  }

  /**
   * Checks if we can get subscription details.
   *
   * @return bool
   *   Whether client is authenticated or not.
   */
  protected function isAuthenticated() {

    try {
      $subsDetails = $this->webdam->getSubscriptionDetails();
      $subsDetailsUrl = $subsDetails->url;
      $subsDetailsUser = $subsDetails->username;

      return isset($subsDetailsUrl);
    }

    catch (InvalidCredentialsException $e) {
      $this->logger('media_webdam')->error($e->getMessage());

      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('media_webdam.settings');

    $form['authentication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication details'),
    ];

    $form['authentication']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('username'),
      '#description' => $this->t('The username of the Webdam account to use for API access.'),
      '#required' => TRUE,
    ];

    $form['authentication']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('password'),
      '#description' => $this->t('The passwords of the Webdam account to use for API access. Note that this field will appear blank even if you have previously saved a value.'),
      '#required' => TRUE,
    ];

    $form['authentication']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('API Client ID to use for API access. Contact the Webdam support team to get one assigned.'),
      '#required' => TRUE,
    ];

    $form['authentication']['client_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('secret'),
      '#description' => $this->t('API Client Secret to use for API access. Contact the Webdam support team to get one assigned. Note that this field will appear blank even if you have previously saved a value.'),
      '#required' => TRUE,
    ];

    if ($this->isAuthenticated()) {
      $webdamFolders = $this->webdam->getFlattenedFolderList();

      $form['folders_filter'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Folder access control'),
        '#tree' => TRUE,
        '#description' => $this->t('Select which folders from your Webdam account will be available.'),
      ];

      foreach($webdamFolders as $folderID => $folderName) {
        $form['folders_filter'][$folderID] = [
        '#type' => 'checkboxes',
        '#title' => $this->t($folderName),
        '#options' => [
          'View' => 'View',
          'Create' => 'Create',
          'Update' => 'Update',
          'Delete' => 'Delete',
          ],
        '#default_value' => $config->get('folders_filter')[$folderID],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('media_webdam.settings')
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('secret', $form_state->getValue('client_secret'))
      ->set('folders_filter', $form_state->getValue('folders_filter'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
