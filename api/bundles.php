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

  //require_once('util.php');
  require_once('bundles.util.php');
  require_once('bundles.class.php');

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');

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

  $get_keyboard_info = function($keyboard, $version) {
    global $path_prefix;
    return json_decode(file_get_contents("$path_prefix/keyboards/$keyboard/$version/$keyboard.keyboard_info"));
  };

  $bundles = new Bundles();
  $result = $bundles->get_bundles($filter, $keyboards, $versionInfo);
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

?>