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

  function version_compare_forward($a, $b) {
    return version_compare($a, $b, '>');
  }

  function remove_utf8_bom($text) {
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
  }

  function fail($s) {
    header("HTTP/1.0 400 $s");
    exit;
  }

  function get_host_url() {
    // We will set protocol on the basis of the hostname
    $hostname = $_SERVER['SERVER_NAME'];
    if($hostname == 'downloads.keyman.com')
      // production, always https (because downloads server is behind a front end, actual protocol reported to PHP may be http)
      return 'https://downloads.keyman.com';
    else if($hostname == 'staging-downloads-keyman-com.azurewebsites.net')
      // staging, always https
      return 'https://staging-downloads-keyman-com.azurewebsites.net';
    else if($hostname == 'downloads.keyman.com.local')
      // test and dev, always http
      return 'http://downloads.keyman.com.local';

    // unknown so return what we were given
    $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://';
    return $protocol . $hostname;
  }

?>