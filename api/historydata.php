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
  if (!preg_match('/^(' . implode('|', $allowed_platforms) . ')$/', $platform)) {
    fail('Invalid platform: Only ' . implode('/', $allowed_platforms) . ' allowed');
  }
} else if ($version == '2.0') {
  $platform = 'all';
} else {
  // platform required for API version 1.0
  fail("Must specify a platform for history.md retrieval!");
}

// Doing the actual work.

function get_history_contents($platform, $version) {
  // Reads straight from a file.  Likely to be useful for the history-exposing API.
  if ($version == '2.0') {
    $inputURL = "https://raw.githubusercontent.com/keymanapp/keyman/master/HISTORY.md";
  } else {
    $inputURL = "../$platform/history.md";
  }

  $contents = @file_get_contents($inputURL);

  if($contents === FALSE) {
    fail("Cannot locate history information!");
  }

  if ($version == '2.0') {
    if ($platform != 'all') {
      $contents = filter_for_platform($contents, $platform);
    }

    // Remove the general history title for client to insert
    $contents = preg_replace('/^# Keyman Version History/', '', $contents);

    // Leave it to clients to also append reference to platform's older history
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
  global $commit_types_regex;
  global $scopes_regex;

  // Allowed commit types defined in keymanapp/keyman/resources/scopes/commit-types.json
  $allowed_commit_types = array('fix', 'feat', 'chore', 'change', 'docs', 'style', 'refactor', 'test', 'auto');
  $commit_types_regex = join('|', $allowed_commit_types);

  // Validate platform matches a scope defined in keymanapp/keyman/resources/scopes/scopes.json
  // Also include appropriate 'common' scopes per keymanapp/downloads.keyman.com#30
  $scope_core_desktop = array('common\/core\/desktop', 'core');
  $scope_core_web = array('common\/core\/web','common\/web');
  $scope_models = array('common\/models[a-z\/]*');
  $scope_common_resources = array('common\/resources');

  $allowed_scopes = array($platform.'[a-z\/]*');
  switch($platform) {
    case 'linux':
    case 'mac':
    case 'windows':
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_desktop);
      break;

    case 'developer':
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_web);
      break;

    case 'web':
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_web, $scope_models);
      break;

    // Filter out OEM history for android/ios
    case 'android':
    case 'ios':
      $allowed_scopes = array_merge($allowed_scopes, $scope_core_web, $scope_models);
      break;
    // Invalid platforms already filtered by web.config
  }

  // All platforms include common/resources
  $allowed_scopes = array_merge($allowed_scopes, $scope_common_resources);

  $scopes_regex = join('|', $allowed_scopes);
  $lines = preg_split("/(\r\n|\n|\r)/", $contents);

  // Grep lines that start with '#' | '*'
  // There might be multiple scopes within the '()'

  function filter_line($line) {
    global $commit_types_regex;
    global $scopes_regex;
    // Include headings
    if(preg_match("/^#/", $line)) {
      return TRUE;
    }
    // Find lines which are history entries, '* type(scope): message'
    if(preg_match("/^\*( )+($commit_types_regex)\((.+?)\):/i", $line, $matches)) {
      $scopes = $matches[3];
      // Only include those which have a good match for the scopes
      if(preg_match("/(^|,)($scopes_regex)/i", $scopes)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  $filtered_contents = array_filter($lines, 'filter_line');
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