<?php
  /**
    API documentation: https://github.com/sillsdev/keyman/wiki/downloads.keyman.com-API
    
    Finds the latest versions of downloads and returns a JSON blob with the data. The first *.download_info file in each folder will be checked.
  */
  
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');
  
  $allowed_platforms = array('android', 'ios', 'mac', 'web', 'windows');
  $allowed_versions = array('1.0', '2.0');
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
  
  if(isset($_REQUEST['platform'])) {
    $platform = $_REQUEST['platform'];
    if(!preg_match('/^('.implode('|', $allowed_platforms).')$/', $platform)) {
      fail('Invalid platform: Only '.implode('/', $allowed_platforms).' allowed');
    }
    $platforms = array($platform);
  } else {
    $platforms = $allowed_platforms;
  }
  
  $result = array();
  
  function version_filter($a) {
    global $platform;
    if($a == '.' || $a == '..') return false;
    // Match only files/folders that have names in an a.b[.c[.d...]] version numbering pattern
    return preg_match('/^\d+\.\d+(\.\d+)*$/', $a);
  }
  
  function version_compare_backward($a, $b) {
    return version_compare($a, $b, '<');
  }
  
  function remove_utf8_bom($text) {
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
  }
  
  foreach($platforms as $platform) {
    // Collect all the relevant directories for the platform, ordered by newest release first
   
    $p = array();
    
    foreach($release_tiers as $tier) {
      $dirs = scandir("../$platform/$tier");
      $dirs = array_filter($dirs, 'version_filter');
      if(count($dirs) > 0) {
        usort($dirs, 'version_compare_backward');
        if($version == '1.0') {
          $p[$tier] = $dirs[0];
        } else {
          // version info now returns version + download_info for each file in the folder
          $path = "../$platform/$tier/{$dirs[0]}";
          $files = scandir($path);
          $filedata = array();
          foreach($files as $file) {
            $filepath = "$path/$file";
            if(preg_match('/^(.+)\.download_info$/', $file, $matches)) {
              $filejson = @json_decode(file_get_contents($filepath));
              if($filejson === NULL || $filejson === FALSE) {
                continue;
              }
              $filejson->size = filesize("$path/{$matches[1]}");
              $filedata[$matches[1]] = $filejson;
            }
          }
          $p[$tier] = array('version' => $dirs[0], 'files' => $filedata);
        }
      }
    }

    if(count($p) == 0) {
      $p = null;
    }
    $result[$platform] = $p;
  }
  
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>