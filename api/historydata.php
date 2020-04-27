<?php
/*
    API documentation: https://github.com/keymanapp/keyman/wiki/downloads.keyman.com-history-API

    Retrieves HISTORY.md
  */

/**
 * Created by PhpStorm.
 * User: joshua
 * Date: 9/27/2017
 * Time: 1:04 PM
 */

header('Content-Type: text/plain; charset=utf-8');

$allowed_platforms = array('android', 'ios', 'linux', 'mac', 'web', 'windows', 'developer');
$allowed_versions = array('1.0'); // api versions, not product versions
//$release_tiers = array('alpha', 'beta', 'stable');

function fail($s) {
  header("HTTP/1.0 400 $s");
  exit;
}

// Mirroring versioninfo.php parameters.

if(isset($_REQUEST['version'])) {
  $version = $_REQUEST['version'];
} else {
  $version = '1.0';
}

if(array_search($version, $allowed_versions) === FALSE) {
  fail('Invalid version: Only '.implode('/', $allowed_versions).' allowed');
}

if(isset($_REQUEST['platform'])) {
  $platform = $_REQUEST['platform'];
  if(!preg_match('/^('.implode('|', $allowed_platforms).')$/', $platform)) {
    fail('Invalid platform: Only '.implode('/', $allowed_platforms).' allowed');
  }
} else {
  fail("Must specify a platform for history.md retrieval!");
}

// Doing the actual work.

function get_history_contents($platform) {
// Reads straight from a file.  Likely to be useful for the history-exposing API.
  $inputURL = "../$platform/history.md";

  $contents = @file_get_contents($inputURL);

  if($contents === FALSE) {
    fail("Cannot locate history information!");
  }
  echo $contents;
}

get_history_contents($platform);

?>