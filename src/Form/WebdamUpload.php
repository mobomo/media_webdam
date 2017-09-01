<?php

namespace Drupal\media_webdam\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_webdam\WebdamInterface;
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
   * The storage handler class for files.
   *
   * @var \Drupal\file\FileStorage
   */
  protected $fileStorage;

  /**
   * The EntityManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * WebdamUpload constructor.
   *
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   *   The Webdam elements.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The EntityManagerInterface.
   */
  public function __construct(WebdamInterface $webdam, EntityTypeManagerInterface $entityManager) {
    $this->webdam = $webdam;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_webdam.webdam'),
      $container->get('entity_type.manager')
    );
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
      '#upload_location' => 'temporary://',
      '#multiple' => FALSE,
      '#required' => TRUE,
    ];
    $form['upload_media']['webdam_folder'] = [
      '#type' => 'radios',
      '#title' => $this->t('Webdam folder'),
      '#description' => $this->t('Please select a Webdam folder to store your file'),
      //@todo: Change method name for something nicer. 
      '#options' => $this->webdam->getFlattenedFolderList(),
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

      // Print success message
      drupal_set_message(
       $this->t(
         '@filename has been successfully uploaded to Webdam!',
         ['@filename' => $file_name]
       )
      );

    }

  }

}
