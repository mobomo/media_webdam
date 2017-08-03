<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\State\StateInterface;
use Drupal\media_webdam\Metadata;
use Drupal\media_webdam\WebdamInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Webdam service test
 *
 * @group media_webdam
 */
class MetadataTest extends UnitTestCase {

  public function testSaveFolderList() {
    $state = new MetadataTestStateStub();

    $metadata = new Metadata(new MetadataTestWebdamStub(), $state);
    $metadata->saveFolderList();

    $this->assertCount(3, $state->get('webdam_folders'));
  }

}

/**
 * Testing stubs. Do not use in production.
 */
class MetadataTestWebdamStub implements WebdamInterface {
  public function getSubscriptionDetails() {}
  public function getFlattenedFolderList($folder_id = NULL) {
    return [
      123 => "Test folder 1",
      456 => "Test folder 2",
      789 => "Test folder 3",
    ];
  }

  public function getFolder($folder_id = NULL) {}

}

class MetadataTestStateStub implements StateInterface {
  protected $values;

  public function set($key, $value) {
    $this->values[$key] = $value;
  }

  public function get($key, $default = NULL) {
    return $this->values[$key];
  }

  public function getMultiple(array $keys) {}
  public function setMultiple(array $data) {}
  public function delete($key) {}
  public function deleteMultiple(array $keys) {}
  public function resetCache() {}
}
