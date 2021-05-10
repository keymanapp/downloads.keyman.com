<?php
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../_common/KeymanSentry.php';

  const SENTRY_DSN = 'https://c997ea5ff5f04931b139ae1ec43e1d06@sentry.keyman.com/16';
  \Keyman\Site\Common\KeymanSentry::init(SENTRY_DSN);
