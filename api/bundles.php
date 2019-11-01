<?php
  // bundles/all shows a list of all bundles
  // bundles/current shows a list of the latest version of each bundles
  // bundles/missing shows bundles that have never been built: note that this also shows non-Windows .kmp files when debug mode
  //                 because we don't have access to the .keyboard_info files in debug mode
  // bundles/outdated shows bundles that are on old versions of Keyman or keyboard
  // bundles/expired shows bundles that could be deleted
  //
  // {
  //   "khmer_angkor": {
  //     "versions": {
  //       "1.1": {
  //         "windows": {
  //            "12.0.52.0": {
  //              url: "/keyboards/khmer_angkor/1.1/keymandesktop-12.0.52.0-khmer_angkor-1.1.exe", // for all, current, expired
  //              missing: true    // for missing, outdated
  //            }, ...
  //         ]
  //       }, ...
  //     }
  //   }, ...
  // }
  //

  require_once('util.php');
  require_once('bundles.util.php');

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');

  function fail($s) {
    header("HTTP/1.0 400 $s");
    exit;
  }

  $filter = get_filter_param();

  function get_filter_param() {
    if(isset($_REQUEST['q'])) $filter = $_REQUEST['q'];
    else $filter = 'all';

    if(!in_array($filter, ['all','outdated','missing','current','expired'])) {
      fail("Invalid q parameter, expecting: all, outdated, missing, current, expired");
    }
    return $filter;
  }

  if(isset($_REQUEST['debug'])) {
    $path_prefix = "https://downloads.keyman.com";
    $versionInfo = file_get_contents('./bundles.test.version.json');
    if($versionInfo === FALSE) {
      fail("Unable to retrieve test version info bundles.test.version.json");
    }
    $keyboards = file_get_contents("./bundles.test.data.$filter.json");
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

  $result = get_bundles($filter, $keyboards, $versionInfo, $path_prefix);
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

  function get_current_uri_base() {
    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
      $link = "https";
    else
      $link = "http";
    // Here append the common URL characters.
    $link .= "://";

    // Append the host(domain name, ip) to the URL.
    $link .= $_SERVER['HTTP_HOST'];
    return $link;
  }

  function get_bundles($filter, $keyboards, $versionInfo, $path_prefix) {
    $result = [];
    $versionInfo = json_decode($versionInfo);
    $appver = $versionInfo->windows->stable;

    //
    // Iterate through all keyboard folders now
    // Use version info from folder name
    //

    foreach($keyboards as $keyboard => $versions) {

      $result_keyboard = [];

      //
      // Scan versions
      //

      if($filter == "outdated" && count($versions) > 0) {
        // For 'outdated' we only care about the most recent version of the keyboard so
        // we can filter before enumerating versions
        $latest = array_keys($versions)[count($versions)-1];
        $versions = [$latest => $versions[$latest]];
      }

      foreach($versions as $version => $files) {
        $windowsBundles = [];
        $appVersions = [];

        //
        // Scan bundles
        //

        $has_kmp = false;

        foreach($files as $file) {
          if(preg_match('/^keymandesktop-(\d+\.\d+\.\d+\.\d+)-(.+)-(.+)\.exe$/', $file, $matches)) {
            // This is a bundle to include
            $appVersion = $matches[1];
            array_push($appVersions, $appVersion);
          } else if(in_array($filter, ['missing', 'outdated']) && preg_match('/\.kmp/', $file)) {
            $has_kmp = true;
          }
        }

        if($has_kmp || !in_array($filter, ['missing', 'outdated'])) {
          usort($appVersions, 'version_compare_forward');
          foreach($appVersions as $appVersion) {
            $windowsBundles[$appVersion] = ["url" => "/keyboards/$keyboard/$version/keymandesktop-$appVersion-$keyboard-$version.exe"];
          }

          $windowsBundles = filter_by_params($windowsBundles, $filter, $appver);
          if(count($windowsBundles) > 0) {
            $result_keyboard[$version] = ['windows' => $windowsBundles];
          }
        }
      }

      if($filter == "current" && count($result_keyboard) > 0) {
        // For 'current' we only care about the most recent version of the keyboard
        // that also includes a bundle -- so we filter after enumerating all bundles
        $current_version = array_keys($result_keyboard)[count($result_keyboard)-1];
        $result_keyboard = [$current_version => $result_keyboard[$current_version]];
      }

      if(($filter == "missing" || $filter == 'outdated') && count($result_keyboard) > 0) {
        // For 'missing' we only care about the most recent version of the keyboard
        // that also includes a bundle -- so we filter after enumerating all bundles
        $current_version = array_keys($result_keyboard)[count($result_keyboard)-1];
        if($result_keyboard[$current_version]['windows'][$appver]['missing']) {
          // Retrieve keyboard targets for this keyboard - we only care about Windows-targeted keyboards
          $keyboard_info = json_decode(file_get_contents("$path_prefix/keyboards/$keyboard/$current_version/$keyboard.keyboard_info"));
          if(isset($keyboard_info->platformSupport->windows) && $keyboard_info->platformSupport->windows != 'none')
            $result_keyboard = [$current_version => $result_keyboard[$current_version]];
          else
            $result_keyboard = [];
        }
        else
          $result_keyboard = [];
      }

      if(count($result_keyboard) > 0)
        $result[$keyboard] = $result_keyboard;
    }

    return $result;
  }

  function filter_by_params($bundles, $filter, $appver) {
    switch($filter) {
      case "all":
        // bundles/all shows a list of all bundles
        return $bundles;

      case "current":
        // bundles/current shows a list of the latest version of each bundles
        if(count($bundles) == 0)
          return [];
        $keys = array_keys($bundles);
        return [$bundles[$keys[count($bundles)-1]]];

      case "missing":
        // bundles/missing shows bundles that have never been built
        if(count($bundles) == 0) {
          return [ $appver => [ "missing" => true ] ];
        }
        return [ $appver => [ "missing" => false ]];
      case "outdated":
        // bundles/outdated shows bundles that are on old versions of Keyman or keyboard
        if(!isset($bundles[$appver])) {
          return [ $appver => [ "missing" => true ] ];
        }
        return [];
      case "expired":
        // bundles/expired shows bundles that could be deleted
        if(count($bundles) == 1) return $bundles;
        array_pop($bundles); // Never delete most recent version
        $result = [];
        foreach($bundles as $version => $bundle) {
          if($version != $appver) $result[$version] = $bundle;
        }
        return $result;
      default:
        fail('Unexpected filter');
    }
  }
?>