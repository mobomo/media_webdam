<?php

namespace Drupal\media_webdam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_webdam\Webdam;

/**
 * Class WebdamConfig.
 *
 * @package Drupal\media_webdam\Form
 */
class WebdamConfig extends ConfigFormBase {

  /**
   * Drupal\media_webdam\Webdam definition.
   *
   * @var \Drupal\media_webdam\Webdam $webdam
   */
  protected $webdam;

  /**
   * WebdamConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\media_webdam\Webdam $webdam
   *   The Webdam elements.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Webdam $webdam) {
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('media_webdam.settings');
    $webdamFolders = [];
    $webdamFolders = $this->webdam->getFlattenedFolderList();

    $form['authentication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication details'),
    ];

    $form['authentication']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('username'),
      '#description' => $this->t('The username of the Webdam account to use for API access.'),
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
    ];

    $form['authentication']['client_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('secret'),
      '#description' => $this->t('API Client Secret to use for API access. Contact the Webdam support team to get one assigned. Note that this field will appear blank even if you have previously saved a value.'),
      '#required' => TRUE,
    ];
    //@TODO: replace checking on fields with a new isAuthenticated() fn
    if (!empty($config->get('username')) && !empty($config->get('password'))) {
      $form['configuration'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configuration'),
      ];

      $form['configuration']['folders_filter'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Available Folders'),
        '#options' => $webdamFolders,
        '#description' => $this->t('Select which folders from your Webdam account will be available.'),
        '#default_value' => $config->get('folders_filter'),
      ];
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
