<?php

namespace Drupal\media_webdam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media_webdam\Webdam;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_webdam\WebdamInterface;
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
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebdamInterface $webdam) {
    $this->configFactory = $config_factory;
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

    $form['cron'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cron Settings'),
    ];

    $form['cron']['sync_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Asset refresh interval'),
      '#options' => [
        '-1' => 'Every cron run',
        '3600' => 'Every hour',
        '7200' => 'Every 2 hours',
        '10800' => 'Every 3 hours',
        '14400' => 'Every 4 hours',
        '21600' => 'Every 6 hours',
        '28800' => 'Every 8 hours',
        '43200' => 'Every 12 hours',
        '86400' => 'Every 24 hours',
      ],
      '#default_value' => empty($config->get('sync_interval')) ? 3600 : $config->get('sync_interval'),
      '#description' => $this->t('Asset values and metadata are synchronized from Webdam during cron.  How often should asset values be synchronized from Webdam'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Try to call Webdam checkCredentials() with details from form_state.
    try {
      // We set the client data array with the values from form_state.
      $cli_data = [
        'grant_type' => 'password',
        'username' => $form_state->getValue('username'),
        'password' => $form_state->getValue('password'),
        'client_id' => $form_state->getValue('client_id'),
        'client_secret' => $form_state->getValue('client_secret'),
      ];
      $this->webdam->checkCredentials($cli_data);
    }
    // If checkCredentials() throws an exception,
    // we catch it here and display the error message to the user.
    catch (InvalidCredentialsException $e) {
      $form_state->setErrorByName('authentication', $e->getMessage());
    }

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
      ->set('sync_interval', $form_state->getValue('sync_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
