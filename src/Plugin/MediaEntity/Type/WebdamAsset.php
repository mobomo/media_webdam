<?php

namespace Drupal\media_webdam\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\media_webdam\WebdamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for Webdam Images.
 *
 * @MediaType(
 *   id = "webdam_asset",
 *   label = @Translation("Webdam asset"),
 *   description = @Translation("Provides business logic and metadata for assets stored on Webdam.")
 * )
 */
class WebdamAsset extends MediaTypeBase {

  /**
   * @var \Drupal\media_webdam\Webdam $webdam
   */
  protected $webdam;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, WebdamInterface $webdam) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory->get('media_entity.settings'));
    $this->webdam = $webdam;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('media_webdam.webdam')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    // @TODO: Determine if other properties need to be added here.
    // @TODO: Determine how to support custom metadata.
    $fields = [
      'type_id' => $this->t('Type ID'),
      'status' => $this->t('Asset status'),
      'filename' => $this->t('Filename'),
      'name' => $this->t('Name'),
      'filesize' => $this->t('Filesize'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'description' => $this->t('Description'),
      'filetype' => $this->t('Filetype'),
      'colorspace' => $this->t('Color space'),
      'version' => $this->t('Version'),
      'datecreated' => $this->t('Date created'),
      'datemodified' => $this->t('Date modified'),
      'datecaptured' => $this->t('Date captured'),
      'folderID' => $this->t('Folder ID')
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['integer'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores the Webdam asset ID. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $assetID = NULL;

    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];

      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        $assetID = $media->{$source_field}->{$property_name};
      }
    }

    // If we don't have an asset ID, there's not much we can do.
    if (is_null($assetID)) {
      return FALSE;
    }

    // Load the asset.
    $asset = $this->webdam->getAsset($assetID);

    switch ($name) {
      case 'type_id':
        return $asset->type_id;
      case 'status':
        return $asset->status;
      case 'filename':
        return $asset->filename;
      case 'name':
        return $asset->name;
      case 'filesize':
        return $asset->filesize;
      case 'width':
        return $asset->width;
      case 'height':
        return $asset->height;
      case 'description':
        return $asset->description;
      case 'filetype':
        return $asset->filetype;
      case 'colorspace':
        return $asset->colorspace;
      case 'version':
        return $asset->version;
      case 'datecreated':
        return $asset->date_created_unix;
      case 'datemodified':
        return $asset->date_modified_unix;
      case 'datecaptured':
        return $asset->datecapturedUnix;
      case 'folderID':
        return $asset->folder->id;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    // @TODO: Should this be a webdam thumbnail image?
    return drupal_get_path('module', 'media_webdam') . '/img/webdam.png';
  }

}
