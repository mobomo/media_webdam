<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\media_entity\Entity\Media;
use Drupal\media_webdam\Plugin\MediaEntity\Type\WebdamAsset;
use Drupal\Tests\UnitTestCase;

/**
 * Webdam asset plugin test.
 *
 * @group media_webdam
 */
class WebdamAssetPluginTest extends UnitTestCase {

  /**
   * Tests the providedFields method.
   */
  public function testProvidedFields() {
    $plugin = new WebdamAsset(
      [],
      'test_plugin',
      [],
      new EntityTypeManagerStub(),
      new EntityFieldManagerStub(),
      new ConfigStub()
    );

    $plugin->setStringTranslation($this->getStringTranslationStub());
    $this->assertArrayHasKey('type', $plugin->providedFields());
  }

  /**
   * Tests the getField method.
   */
  public function testGetField() {
    $plugin = new WebdamAsset(
      [],
      'test_plugin',
      [],
      new EntityTypeManagerStub(),
      new EntityFieldManagerStub(),
      new ConfigStub()
    );

    $media = new MediaStub();
    $this->assertEquals('image', $plugin->getField($media, 'type'));
  }

}

/**
 * Class EntityTypeManagerStub.
 */
class EntityTypeManagerStub extends EntityTypeManager {

  /**
   * EntityTypeManagerStub constructor.
   */
  public function __construct() {}

}

/**
 * Class EntityFieldManagerStub.
 */
class EntityFieldManagerStub extends EntityFieldManager {

  /**
   * EntityFieldManagerStub constructor.
   */
  public function __construct() {}

}

/**
 * Class ConfigStub.
 */
class ConfigStub extends Config {

  /**
   * ConfigStub constructor.
   */
  public function __construct() {}

}

/**
 * Class MediaStub.
 */
class MediaStub extends Media {

  /**
   * MediaStub constructor.
   */
  public function __construct() {}

}
