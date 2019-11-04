<?php
  /**
    API documentation: https://github.com/keymanapp/keyman/wiki/downloads.keyman.com-bundles-API

    Finds details about bundled versions of Keyman Desktop + keyboards and returns a JSON blob with the data.

    Schema: schemas/bundles/1.0/bundles.json
  */

  require_once('bundles.util.php');
  require_once('bundles.class.php');

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');

  $filter = get_filter_param();

  if(isset($_REQUEST['debug'])) {
    // This pathway can be helpful for building new test fixtures
    // keep for that reason :)
    $path_prefix = "https://downloads.keyman.com";
    $versionInfo = file_get_contents('./test/bundles/fixtures/bundles.test.version.json');
    if($versionInfo === FALSE) {
      fail("Unable to retrieve test version info bundles.test.version.json");
    }
    $keyboards = file_get_contents("./test/bundles/fixtures/bundles.test.data.$filter.json");
    if($keyboards === FALSE) {
      fail("Unable to retrieve test data bundles.test.data.json");
    }
    $keyboards = json_decode($keyboards, JSON_OBJECT_AS_ARRAY);
  } else {
    // Get current Keyman Desktop stable version
    $path_prefix = "..";
    $versionInfo = @file_get_contents(get_current_uri_base() . "/api/version/windows");
    if($versionInfo === FALSE) {
      fail("Unable to retrieve version info for Windows");
    }
    $keyboards = scanKeyboardDirs();
  }

  $get_keyboard_info = function($keyboard, $version) {
    global $path_prefix;
    return json_decode(file_get_contents("$path_prefix/keyboards/$keyboard/$version/$keyboard.keyboard_info"));
  };

  $bundles = new Bundles();
  $result = $bundles->get_bundles($filter, $keyboards, $versionInfo, $get_keyboard_info);
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>