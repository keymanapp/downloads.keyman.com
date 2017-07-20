#
# Use rsync to copy over specific files listed in syncfiles.txt
#

$RSYNC_HOME=$env:RSYNC_HOME
$REMOTE_RSYNC_PATH=$env:REMOTE_RSYNC_PATH
$dstroot=$env:DSTROOT

#
# Translate current folder for cygwin
#

$srcroot=(get-item $pwd).parent.fullName
$srcroot=$srcroot -replace "^([a-z]):",'/cygdrive/$1'

#
# Upload with rsync to downloads.keyman.com
# (rsync requires that we are in the upload folder to get folders in 
# sync correctly; it is possible to resolve this but easier to just cd.)
#

# function uploadFile(

$rsync_args = @(
  '-vrzltp',                                # verbose, recurse, zip, copy symlinks, preserve times, permissions
  '--chmod=Dug=rwx,Do=rx,Fug=rw,Fo=r',      # map Windows security to host security
  '--stats',                                # show statistics for log
  "--rsync-path=$REMOTE_RSYNC_PATH",        # path on remote server
  "--rsh=$RSYNC_HOME\ssh",                  # use ssh
  "--files-from=./syncfiles.txt",           # list of files to upload
  "../",                                    # base source path of files in syncfiles.txt
  "$dstroot"                                # target server + base dest path
)

& $RSYNC_HOME\rsync.exe $rsync_args

# EOF