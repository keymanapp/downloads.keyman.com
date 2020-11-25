<?php
/*
    API documentation: https://github.com/keymanapp/keyman/wiki/downloads.keyman.com-history-API

    Retrieves HISTORY.md
  */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: max-age=0');

$allowed_platforms = array('android', 'ios', 'linux', 'mac', 'web', 'windows', 'developer');
$allowed_versions = array('1.0', '2.0'); // api versions, not product versions
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

function get_history_contents($platform, $version) {
// Reads straight from a file.  Likely to be useful for the history-exposing API.
  if ($version == '2.0') {
    $inputURL = "./HISTORY.md";
  } else {
    $inputURL = "../$platform/history.md";
  }

  $contents = @file_get_contents($inputURL);

  if($contents === FALSE) {
    fail("Cannot locate history information!");
  }

  // Keyman 14.0+ needs to filter history.md by platform
  if ($version == '2.0') {
    $contents = filter_for_platform($contents, $platform);

    // Update the title for the platform
    $platform_title = [
      'android' => 'Keyman for Android',
      'ios' => 'Keyman for iOS',
      'linux' => 'Keyman for Linux',
      'mac' => 'Keyman for macOS',
      'web' => 'KeymanWeb',
      'windows' => 'Keyman for Windows',
      'developer' => 'Keyman Developer'
    ];
    $contents = preg_replace('/^# Keyman Version History/', '# ' . $platform_title[$platform] . ' Version History', $contents);

    // Append reference to older history
    $contents = $contents . "\n----\n\n" .
      "Older $platform_title[$platform] History available at http://downloads.keyman.com/api/history/$platform\n";
  }

  echo $contents;
}

/**
 * Filters the history.md file for a particular platform. Used for Keyman 14.0+
 * @param $contents
 * @param $platform
 * @return string
 */
function filter_for_platform($contents, $platform) {
  // Allowed commit types defined in keymanapp/keyman/resources/scopes/commit-types.json
  $allowed_commit_types = array('fix', 'feat', 'chore', 'change', 'docs', 'style', 'refactor', 'test', 'auto');
  $commit_types_regex = join('|', $allowed_commit_types);

  // Validate platform matches a scope defined in keymanapp/keyman/resources/scopes/scopes.json
  // Also include appropriate 'common' scopes per keymanapp/downloads.keyman.com#30
  $scope_core_desktop = array('common\/core\/desktop');
  $scope_core_web = array('common\/core\/web');
  $scope_models = array('common\/models', 'common\/models\/types', 'common\/models\/templates', 'common\/models\/wordbreakers');
  $scope_common_resources = array('common\/resources');

  switch($platform) {
    case 'linux':
    case 'mac':
    case 'windows':
      $allowed_scopes = array($platform, $platform.'\/config', $platform.'\/engine',
        $platform.'\/resources', $platform.'\/samples');
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_desktop);
      break;

    case 'developer':
      $allowed_scopes = array($platform, $platform.'\/compilers', $platform.'\/ide',
        $platform.'\/resources', $platform.'\/tools');
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_web);
      break;

    case 'web':
      $allowed_scopes = array($platform, $platform.'\/bookmarklet', $platform.'\/engine',
        $platform.'\/resources', $platform.'\/ui', $platform.'\/tools');
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_web, $scope_models);
      break;

    // Filter out OEM history for android/ios
    case 'android':
    case 'ios':
      $allowed_scopes = array($platform, $platform.'\/app', $platform.'\/browser', $platform.'\/engine',
        $platform.'\/resources', $platform.'\/samples');
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_web, $scope_models);
      break;
    // Invalid platforms already filtered by web.config
  }

  // All platforms include common/resources
  $allowed_scopes = array_merge($allowed_scopes, $scope_common_resources);

  $scopes_regex = join('|', $allowed_scopes);
  $lines = preg_split("/(\r\n|\n|\r)/", $contents);

  // Grep lines that start with '#' | '*'
  // scope might be separated by ','
  $filtered_contents = preg_grep("/^(#|\*( )+($commit_types_regex)\(((.)*,)?($scopes_regex)\)).*$/", $lines);
  $filtered_contents = array_values(array_filter($filtered_contents));

  // Filter out intermittent releases where $platform wasn't updated
  foreach($filtered_contents as $index=>$line) {
    if (stripos($line, '## ') !== false) {
      // Consecutive lines that start with '## '
      if ($index > 1 && stripos($filtered_contents[$index-1], '## ') !== false) {
        $filtered_contents[$index-1] = '';
      }
    }
  }
  $filtered_contents = array_values(array_filter($filtered_contents));

  // Add whitespacing back
  return implode("\n\n", $filtered_contents);
}

get_history_contents($platform, $version);

?>