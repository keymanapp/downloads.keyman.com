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

    // Update the title
    switch ($platform) {
      case 'linux':
      case 'windows':
      case 'android': $platform_title = 'Keyman for ' . ucfirst($platform); break;

      case 'web': $platform_title = 'KeymanWeb'; break;
      case 'developer': $platform_title = 'Keyman Developer'; break;
      case 'mac': $platform_title = 'Keyman for macOS'; break;
      case 'ios': $platform_title = 'Keyman for iOS'; break;
    }
    $contents = preg_replace('/^# Keyman Version History/', '# ' . $platform_title . ' Version History', $contents);
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
  switch($platform) {
    case 'linux':
    case 'mac':
    case 'web':
    case 'windows':
    case 'developer':
      $allowed_scopes = array($platform);
      break;
    // Filter out OEM history for android/ios
    case 'android':
      $allowed_scopes = array('android', 'android\/engine', 'android\/app', 'android\/samples');
      break;
    case 'ios':
      $allowed_scopes = array('ios', 'ios\/engine', 'ios\/app', 'ios\/samples');
      break;
    // Invalid platforms already filtered by web.config
  }

  $scopes_regex = join('|', $allowed_scopes);
  $lines = preg_split("/(\r\n|\n|\r)/", $contents);

  // Grep lines that start with '#' | '*' | ' '
  $filtered_contents = preg_grep("/^(#| |\*( )+($commit_types_regex)\(($scopes_regex)\)).*$/", $lines);

  // TODO: filter out intermittent releases where $platform wasn't updated

  // Add whitespacing back
  return implode("\n\n", $filtered_contents);
}

get_history_contents($platform, $version);

?>