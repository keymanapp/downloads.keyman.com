<?php
  require_once('util.php');

  /**
    API documentation: https://github.com/sillsdev/keyman/wiki/downloads.keyman.com-API

    Gets the available download files for a given model id
  */

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');

  $allowed_platforms = array('android', 'ios' /*, 'linux', 'mac', 'web', 'windows'*/); // models only currently supported on iOS and Android
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

  $result = array();

  // Look in the models/ folder for the id

  if(!is_dir("../models/$id")) {
    fail('Model '.$id.' does not exist');
  }

  $dirs = scandir("../models/$id");
  $dirs = array_filter($dirs, 'special_folders_filter');
  if(count($dirs) == 0) {
    fail('No downloads exist for '.$id);
  }
  usort($dirs, 'version_compare_backward');
  $files = scandir("../models/$id/{$dirs[0]}");

  // Go through and report on js, kmp downloads.

  $base = "https://{$_SERVER['SERVER_NAME']}/models/$id/{$dirs[0]}/";
  foreach($files as $file) {
    if(preg_match('/\.kmp$/', $file)) {
      $result['kmp'] = $base . $file;
    } else if(preg_match('/\.js$/', $file)) {
      $result['js'] = $base . $file;
    }
  }

  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>