# downloads.keyman.com

## Resources for downloads.keyman.com.

Warning: do not rsync this site directly to downloads.keyman.com
as additional files will be in place. .rsyncignore is a pseudo
.ignore file for rsync that lists files in this repo that should
not be put onto downloads.keyman.com.

The _tools folder contains a script that determines which files should
be synchronised using rsync

* Update this document to note new files that should be uploaded

## Files to upload

* api/versioninfo.php
* api/historydata.php
* api/keyboard.php
* api/util.php
* tools/*
* **/.htaccess

## Files not for upload

* web.config
* api/web.config
* test/*
* **/.gitignore
* _tools/build_local.php
* README.md
* LICENSE