<?php
  require_once('util.php');

  function scanKeyboardDirs() {
    $result = [];
    $keyboards = scandir("../keyboards");
    $keyboards = array_filter($keyboards, 'special_folders_filter');
    foreach($keyboards as $keyboard) {
      if(!is_dir("../keyboards/$keyboard")) continue;
      $result_keyboard = [];
      $versions = scandir("../keyboards/$keyboard");
      $versions = array_filter($versions, 'special_folders_filter');
      usort($versions, 'version_compare');
      foreach($versions as $version) {
        if(!is_dir("../keyboards/$keyboard/$version")) continue;
        $result_version = [];
        $files = scandir("../keyboards/$keyboard/$version");
        $files = array_filter($files, 'special_folders_filter');
        foreach($files as $file) {
          array_push($result_version, $file);
        }
        $result_keyboard[$version] = $result_version;
      }
      $result[$keyboard] = $result_keyboard;
    }
    return $result;
  }
?>