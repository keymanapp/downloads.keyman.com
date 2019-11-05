<?php
  use PHPUnit\Framework\TestCase;

  require_once('../bundles.class.php');

  define('FIXTURES_PATH', 'bundles/fixtures');

  class BundlesTest extends TestCase {
    public function testAll() {
      $this->helpTest('all');
    }

    public function testMissing() {
      $this->helpTest('missing');
    }

    public function testOutdated() {
      $this->helpTest('outdated');
    }

    public function testCurrent() {
      $this->helpTest('current');
    }

    public function testExpired() {
      $this->helpTest('expired');
    }

    // This is not a truly mockable implementation but it is good enough for now.
    // Still runs the tests!
    private function helpTest($filter) {
      $versionInfo = file_get_contents(FIXTURES_PATH . '/bundles.test.version.json');
      if($versionInfo === FALSE) {
        fail("Unable to retrieve test version info bundles.test.version.json");
      }
      $keyboards = file_get_contents(FIXTURES_PATH . "/bundles.test.data.$filter.json");
      if($keyboards === FALSE) {
        fail("Unable to retrieve test data bundles.test.data.json");
      }
      $keyboards = json_decode($keyboards, JSON_OBJECT_AS_ARRAY);

      $get_keyboard_info = function($keyboard, $version) {
        return json_decode(file_get_contents(FIXTURES_PATH . "/$keyboard-$version.keyboard_info"));
      };

      $bundles = new Bundles();

      $result = json_encode($bundles->get_bundles($filter, $keyboards, $versionInfo, $get_keyboard_info), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

      $expected = file_get_contents(FIXTURES_PATH . "/bundles.test.data.$filter.expected.json");

      $this->assertSame($result, $expected);
    }
  }