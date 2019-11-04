<?php
  require_once('util.php'); // for version_compare_*

  class Bundles {
    function get_bundles($filter, $keyboards, $versionInfo, $get_keyboard_info) {
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

          // At present, for 'expired', we don't delete the most recent installer for old versions
          // of keyboards. This is something we could consider for the future.

          if($has_kmp || !in_array($filter, ['missing', 'outdated'])) {
            usort($appVersions, 'version_compare_forward');
            foreach($appVersions as $appVersion) {
              $windowsBundles[$appVersion] = ["url" => "/keyboards/$keyboard/$version/keymandesktop-$appVersion-$keyboard-$version.exe"];
            }

            $windowsBundles = $this->filter_by_params($windowsBundles, $filter, $appver);
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
            $keyboard_info = $get_keyboard_info($keyboard, $current_version);

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
          if(count($bundles) == 1)
            // If only one bundle, never delete it!
            return [];
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
  }
?>