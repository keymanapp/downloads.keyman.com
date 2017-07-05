<?php
  /**
    API documentation: https://github.com/sillsdev/keyman/wiki/downloads.keyman.com-API
    
    Finds the latest versions of downloads and returns a JSON blob with the data. The first *.download_info file in each folder will be checked.
  */
  
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');
  
  $allowed_platforms = array('android', 'ios', 'mac', 'web', 'windows');
  $tiers = array('alpha', 'beta', 'stable');
  
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
    
    foreach($tiers as $tier) {
      $dirs = scandir("../$platform/$tier");
      $dirs = array_filter($dirs, 'version_filter');
      if(count($dirs) > 0) {
        usort($dirs, 'version_compare_backward');
        $p[$tier] = $dirs[0];
      }
    }

    if(count($p) == 0) {
      $p = null;
    }
    $result[$platform] = $p;
  }
  
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>