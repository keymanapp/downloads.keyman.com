<?php
  require_once('util.php');

  /*
    API documentation: https://github.com/keymanapp/keyman/wiki/downloads.keyman.com-keyboard-API

    Gets the available download files for a given keyboard id
  */

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');

  $allowed_platforms = array('android', 'ios', 'linux', 'mac', 'web', 'windows');
  $allowed_versions = array('1.0'); // api versions, not product versions
  $release_tiers = array('alpha', 'beta', 'stable');

  //
  // Parameter checks for version, id
  //

  if(isset($_REQUEST['version'])) {
    $version = $_REQUEST['version'];
  } else {
    $version = '1.0';
  }

  if(array_search($version, $allowed_versions) === FALSE) {
    fail('Invalid version: Only '.implode('/', $allowed_versions).' allowed');
  }

  if(isset($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
  } else {
    fail('id parameter is required');
  }

  if(preg_match('/[\\/\:\\\\]/', $id)) {
    fail('id parameter must be a valid id');
  }

  /*$tier = 'stable';
  if(isset($_REQUEST['tier'])) {
    $t = $_REQUEST['tier'];
    if(in_array($t, array('alpha', 'beta', 'stable'))) $tier = $t;
  }*/

  $result = array();

  // Look in the keyboards/ folder for the id

  if(!is_dir("../keyboards/$id")) {
    fail('Keyboard '.$id.' does not exist');
  }

  $dirs = scandir("../keyboards/$id");
  $dirs = array_filter($dirs, 'special_folders_filter');
  if(count($dirs) == 0) {
    fail('No downloads exist for '.$id);
  }
  usort($dirs, 'version_compare_backward');
  $files = scandir("../keyboards/$id/{$dirs[0]}");

  // Go through and report on windows, js, kmp downloads.
  // Find the latest Windows download, expecting a format of keymandesktop-<ver>-<id>-<kbdver>.exe

  $base = "https://{$_SERVER['SERVER_NAME']}/keyboards/$id/{$dirs[0]}/";
  $winver = '0';
  foreach($files as $file) {
    if(preg_match('/^keymandesktop-([0-9.]+)-(.+)\.exe$/', $file, $matches)) {
      if(version_compare($matches[1], $winver) > 0) {
        $winver = $matches[1];
        $result['windows'] = $base . $file;
      }
    } else if(preg_match('/\.kmp$/', $file)) {
      $result['kmp'] = $base . $file;
    } else if(preg_match('/\.js$/', $file)) {
      $result['js'] = $base . $file;
    }
  }

  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>