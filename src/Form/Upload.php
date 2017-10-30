<?php

namespace Drupal\media_acquiadam\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_acquiadam\AcquiadamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Upload extends FormBase {

  /**
   * @var \Drupal\media_acquiadam\AcquiadamInterface
   */
  protected $acquiadam;

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
   * AcquiadamUpload constructor.
   *
   * @param \Drupal\media_acquiadam\AcquiadamInterface $acquiadam
   *   The Webdam elements.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The EntityManagerInterface.
   */
  public function __construct(AcquiadamInterface $acquiadam, EntityTypeManagerInterface $entityManager) {
    $this->acquiadam = $acquiadam;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_acquiadam.acquiadam'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquiadam_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['upload_media'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Upload media'),
    ];
    $form['upload_media']['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#description' => $this->t('Select a file to upload.'),
      '#upload_location' => 'temporary://',
      '#multiple' => FALSE,
      '#required' => TRUE,
    ];
    $form['upload_media']['folder'] = [
      '#type' => 'radios',
      '#title' => $this->t('Folder'),
      '#description' => $this->t('Please select a folder to store your file'),
      //@todo: Change method name for something nicer.
      '#options' => $this->acquiadam->getFlattenedFolderList(),
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
    $folderID = $form_state->getValue('folder');
    $fid = $form_state->getValue('file');
    if (!empty($fid)) {
      $file = $this->entityManager->getStorage('file')->load($fid[0]);
      $file->save();

      // File data we need for upload assets to Webdam.
      $file_uri = $file->getFileUri();
      $file_name = $file->getFilename();

      // Uploading asset to AWS.
      $this->acquiadam->uploadAsset($file_uri, $file_name, $folderID);
    }
  }
}
