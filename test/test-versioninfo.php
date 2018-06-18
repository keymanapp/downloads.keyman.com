<?php
  //
  // Generate random version folders and .download_info for the various projects,
  // so we can verify that versioninfo.php returns the right values.
  //
  // This won't touch an existing folder, for safety's sake
  //
  header('Content-Type: text/plain');

  $allowed_platforms = array('android', 'ios', 'linux', 'mac', 'web', 'windows');
  $stabilities = array('alpha', 'alpha', 'alpha', 'alpha', 'alpha', 'beta', 'beta', 'stable');
  foreach($allowed_platforms as $p) {
    if(is_dir("../$p")) {
      echo "Skipping $p, it already exists.\n";
      continue;
    }
    @mkdir("../$p");
    $n1 = rand(2, 4);
    $n2 = $n1 + rand(0, 4);
    for($i = $n1; $i < $n2; $i++) {
      $n3 = rand(0, 12);
      for($j = 0; $j < $n3; $j++) {
        @mkdir("../$p/$i.0.$j");
        $stability = $stabilities[rand(0,count($stabilities)-1)];
        echo "$p/$i.0.$j = $stability\n";
        file_put_contents("../$p/$i.0.$j/.download_info", <<<END
{
  "name": "$p-test",
  "version": "$i.0.$j",
  "date": "2017-06-21",
  "platform": "$p",
  "stability": "$stability",
  "file": "$p-$i.0.$j.zip",
  "type": "zip",
  "build": "$j"
}
END
        );
      }
    }
  }
?>