<?php

namespace Drupal\media_webdam\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Implements form to save media files and upload them to Webdam.
 *
 * @package Drupal\media_webdam\Form
 */
class WebdamUpload extends FormBase {

  /**
   * Drupal\media_webdam\WebdamInterface definition.
   *
   * @var \Drupal\media_webdam\WebdamInterface
   */
  protected $webdam;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The storage handler class for files.
   *
   * @var \Drupal\file\FileStorage
   */
  protected $fileStorage;

  /**
   * The EntityManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * WebdamUpload constructor.
   *
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   *   The Webdam elements.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The EntityManagerInterface.
   */
  public function __construct(WebdamInterface $webdam, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityManager) {
    $this->webdam = $webdam;
    $this->configFactory = $config_factory;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_webdam.webdam'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a list with available Webdam folders keyed by ID.
   *
   * @return array
   *   Array of available folders.
   */
  protected function availFoldersData() {
    $folders = $this->config('media_webdam.settings')->get('folders_filter');

    $availableFolders = array_filter($folders);

    $avail_folders_data = [];
    foreach ($availableFolders as $key => $folder) {
      $avail_folders_data[$key] = $this->webdam->getFolder($folder)->name;
    };

    return $avail_folders_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webdam_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['upload_media'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Upload media'),
    ];
    $form['upload_media']['managed_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Media Upload'),
      '#description' => $this->t('Select a file to Upload. Max upload size: 1MB'),
      '#upload_location' => 'public://media_webdam/',
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg mp3 mp4 mkv'],
        'file_validate_size' => [1048576],
      ],
      '#multiple' => FALSE,
      '#required' => TRUE,
    ];
    $form['upload_media']['webdam_folder'] = [
      '#type' => 'radios',
      '#title' => $this->t('Webdam folder'),
      '#description' => $this->t('Please select a Webdam folder to store your file'),
      '#options' => $this->availFoldersData(),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add media'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $folderID = $form_state->getValue('webdam_folder');
    $fid = $form_state->getValue('managed_file');
    if (!empty($fid)) {
      $file = $this->entityManager->getStorage('file')->load($fid[0]);
      $file->save();

      // File data we need for upload assets to Webdam.
      $file_uri = $file->getFileUri();
      $file_name = $file->getFilename();

      // Uploading asset to AWS.
      $this->webdam->uploadAsset($file_uri, $file_name, $folderID);

    }

  }

}
