<?php

namespace Drupal\media_webdam\Plugin\MediaEntity\Type;

use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;

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
   * {@inheritdoc}
   */
  public function providedFields() {
    // @TODO: Populate the appropriate webdam properties here.
    $fields = [
      'type' => $this->t('Asset type'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    switch ($name) {
      // @TODO: Populate the appropriate webdam properties here.
      case 'type':
        return 'image';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    // @TODO: Implement the right thumbnail logic for each asset type.
    return $this->getDefaultThumbnail();
  }

}
