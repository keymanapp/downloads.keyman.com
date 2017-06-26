<?php
  /**
    API documentation: https://github.com/sillsdev/keyman/wiki/downloads.keyman.com-API
    
    Finds the latest versions of downloads and returns a JSON blob with the data. The first *.download_info file in each folder will be checked.
  */
  
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');
  
  $allowed_platforms = array('android', 'ios', 'mac', 'web', 'windows');
  
  //
  // Parameter checks for platforms
  //
  
  function fail($s) {
    header("HTTP/1.0 400 $s");
    exit;
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
    return is_dir("../$platform/$a");
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
    $dirs = scandir("../$platform");
    $dirs = array_filter($dirs, 'version_filter');
    usort($dirs, 'version_compare_backward');
    
    $p = array();
    
    foreach($dirs as $dir) {
      // For each directory
      $files = glob("../$platform/$dir/{.download_info,*.download_info}", GLOB_NOSORT|GLOB_BRACE);
      if($files === FALSE || count($files) == 0) continue;
      for($i = 0; $i < count($files); $i++) {
        // Read the JSON
        $data = remove_utf8_bom(file_get_contents("{$files[$i]}"));
        $json = json_decode($data);
        if($json === NULL) continue;
        
        // Check for stability property
        if(!property_exists($json, 'stability')) continue;
        $stability = $json->stability;
        
        // Add the property to the platform data
        if(!array_key_exists($stability, $p)) {
          $p[$stability] = array("version" => $dir, "path" => "/$platform/$dir");
        
          //
          // If we find a beta before we find an alpha, then the alpha is same as beta, and same as stable
          // We are only testing 3 labels for now, so let's keep the test simple
          //
          
          if($stability == 'stable') {
            if(!array_key_exists('beta', $p)) $p['beta'] = $p['stable'];
          }
          if($stability == 'stable' || $stability == 'beta') {
            if(!array_key_exists('alpha', $p)) $p['alpha'] = $p['beta'];
          }
        }
        
        break;
      }
      // Once we have all three stability records, we can stop searching
      if(count($p) >= 3) break;
    }
    
    if(count($p) == 0) {
      $p = null;
    }
    $result[$platform] = $p;
  }
  
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>