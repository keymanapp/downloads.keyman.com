<?php
  function version_filter($a) {
    if($a == '.' || $a == '..') return false;
    // Match only files/folders that have names in an a.b[.c[.d...]] version numbering pattern
    return preg_match('/^\d+\.\d+(\.\d+)*$/', $a);
  }

  function special_folders_filter($a) {
    if($a == '.' || $a == '..') return false;
    return true; 
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