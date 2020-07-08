<?php

  // grab latest version from live site for each platform and save

  $platforms = ['android', 'developer', 'ios', 'linux', 'mac', 'web', 'windows'];
  $tiers = ['alpha', 'beta', 'stable'];

  // grab sample keyboards + models as well:

  $keyboards = ['khmer_angkor'];
  $models = ['nrc.en.mtnt'];

  $version_data = json_decode(file_get_contents('https://downloads.keyman.com/api/version/2.0'));
  foreach($platforms as $platform) {
    foreach($tiers as $tier) {
      downloadPlatformTierFiles($platform, $tier, $version_data);
    }
  }

  foreach($keyboards as $keyboard) {
    downloadKeyboard($keyboard);
  }

  foreach($models as $model) {
    downloadModel($model);
  }

  function downloadPlatformTierFiles($platform, $tier, $version_data) {
    echo "# Downloading files for $platform / $tier\n";
    $files = $version_data->$platform->$tier->files;
    $version = $version_data->$platform->$tier->version;
    $path =  __DIR__ . "/../$platform/$tier/$version/";
    $url = "https://downloads.keyman.com/$platform/$tier/$version/";
    force_directories($path);
    foreach($files as $file => $detail) {
      download($path . $detail->file, $url . $detail->file);
      download($path . $detail->file . '.download_info', $url . $detail->file . '.download_info');
    }
  }

  function force_directories($path) {
    if(!file_exists($path))
      mkdir(str_replace('/', '\\', $path), 0777, true);
  }

  function download($file, $url) {
    if(!file_exists($file)) {
      echo "## Downloading ".basename($file)."\n";
      $ch = curl_init($url);
      $fp = fopen($file, "wb");
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
    } else {
      echo "## - skipping ".basename($file)." - already downloaded\n";
    }
  }

  function downloadKeyboard($id) {
    echo "# Downloading files for keyboard $id\n";
    $version_data = json_decode(file_get_contents('https://downloads.keyman.com/api/keyboard/'.$id));
    if($version_data->kmp && preg_match("/\\/keyboards\\/$id\\/([0-9.]+)\\//", $version_data->kmp, $matches)) {
      $version = $matches[1];
      $path = __DIR__ . "/../keyboards/$id/$version/";
      force_directories($path);
      // This gives us the file version
      foreach($version_data as $type => $url) {
        download($path . basename($url), $url);
      }
      download($path . $id . ".keyboard_info", "https://downloads.keyman.com/keyboards/$id/$version/$id.keyboard_info");
    }
  }

  function downloadModel($id) {
    echo "# Downloading files for model $id\n";
    $version_data = json_decode(file_get_contents('https://downloads.keyman.com/api/model/'.$id));
    if($version_data->kmp && preg_match("/\\/models\\/$id\\/([0-9.]+)\\//", $version_data->kmp, $matches)) {
      $version = $matches[1];
      $path = __DIR__ . "/../models/$id/$version/";
      force_directories($path);
      // This gives us the file version
      foreach($version_data as $type => $url) {
        download($path . basename($url), $url);
      }
      download($path . $id . ".model_info", "https://downloads.keyman.com/models/$id/$version/$id.model_info");
    }
  }
