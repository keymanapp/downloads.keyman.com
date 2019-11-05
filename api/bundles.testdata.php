<?php
  require_once('bundles.util.php');

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: max-age=0');
  $result = scanKeyboardDirs();
  echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
