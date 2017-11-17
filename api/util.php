<?php
  function version_filter($a) {
    global $platform;
    if($a == '.' || $a == '..') return false;
    // Match only files/folders that have names in an a.b[.c[.d...]] version numbering pattern
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
?>