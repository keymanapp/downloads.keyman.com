<?php
  /*
    API documentation: https://github.com/keymanapp/keyman/wiki/downloads.keyman.com-version-API#all-versions-api

    Returns a list of all versions of Keyman, starting with version 10.0, grouped by major version:
      { "10.0": { "alpha": ["10.0.1", ...], "beta": ["10.0.111", ...], "stable": ["10.0.112", ...] }, "11.0": ..., ... }

    Uses data from web folder as canonical across all platforms
  */

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');

  $platform = 'web';
  $allowed_versions = array('1.0'); // api versions, not product versions
  $release_tiers = array('alpha', 'beta', 'stable');

  function fail($s) {
    header("HTTP/1.0 400 $s");
    exit;
  }

  //
  // Parameter checks for platforms
  //

  if(isset($_REQUEST['version'])) {
    $version = $_REQUEST['version'];
  } else {
    $version = '1.0';
  }

  if(array_search($version, $allowed_versions) === FALSE) {
    fail('Invalid version: Only '.implode('/', $allowed_versions).' allowed');
  }

  $result = array();

  function version_filter($a) {
    if($a == '.' || $a == '..') return false;
    return preg_match('/^\d+\.\d+(\.\d+)*$/', $a);
  }

  function version_compare_backward($a, $b) {
    return version_compare($a, $b, '<');
  }

  // Collect all the relevant directories for the platform, ordered by newest release first

  foreach($release_tiers as $tier) {
    $dirs = scandir("../$platform/$tier");
    $dirs = array_filter($dirs, 'version_filter');
    if(count($dirs) > 0) {
      usort($dirs, 'version_compare_backward');
      foreach($dirs as $dir) {
        if(preg_match('/^(\d+\.\d+)\./', $dir, $matches)) {
          $major = $matches[1];
          if((double) $major < 10) {
            // we did not have convergent version numbers before 10.0, don't list
            continue;
          }
          if(!array_key_exists($major, $result)) {
            $result[$major] = array('alpha' => array(), 'beta' => array(), 'stable' => array());
          }
          array_push($result[$major][$tier], $dir);
        }
      }
    }
  }

  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>