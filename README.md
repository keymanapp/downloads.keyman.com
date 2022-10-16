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
* robots.txt

## Files not for upload

* web.config
* api/web.config
* test/*
* **/.gitignore
* _tools/build_local.php
* README.md
* LICENSE

## Release embargo

When we are preparing a build for a new major stable release version, it can be
helpful to get all the builds in place and complete before we make them publicly
visible.

The file `embargo.txt` can be placed in the root of the website to accomplish
this. It should contain a single line, being the major version number to
'embargo' from the version API. When this file is present, the version API will
not return a stable version equal to or newer than the version in the file.
