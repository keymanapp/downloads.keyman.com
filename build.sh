#!/usr/bin/env bash
## START STANDARD SITE BUILD SCRIPT INCLUDE
readonly THIS_SCRIPT="$(readlink -f "${BASH_SOURCE[0]}")"
readonly BOOTSTRAP="$(dirname "$THIS_SCRIPT")/resources/bootstrap.inc.sh"
readonly BOOTSTRAP_VERSION=v1.0.12
[ -f "$BOOTSTRAP" ] && source "$BOOTSTRAP" || source <(curl -H "Cache-Control: no-cache" -fs https://raw.githubusercontent.com/keymanapp/shared-sites/$BOOTSTRAP_VERSION/bootstrap.inc.sh)
## END STANDARD SITE BUILD SCRIPT INCLUDE

readonly KEYMAN_CONTAINER_NAME=downloads-keyman-website
readonly KEYMAN_CONTAINER_DESC=downloads-keyman-com-app
readonly KEYMAN_IMAGE_NAME=downloads-keyman-website
readonly HOST_DOWNLOADS_KEYMAN_COM=downloads.keyman.com.localhost
# TODO: move to shared-sites keyman-local-ports.inc.sh:
readonly PORT_DOWNLOADS_KEYMAN_COM=8062

source _common/keyman-local-ports.inc.sh
source _common/docker.inc.sh

################################ Main script ################################

builder_describe \
  "Setup downloads.keyman.com site to run via Docker." \
  configure \
  clean \
  build \
  start \
  stop \
  test \

builder_parse "$@"

function test_docker_container() {
  # Note: ci.yml replicates these

  builder_echo TODO
}

builder_run_action configure  bootstrap_configure
builder_run_action clean      clean_docker_container $KEYMAN_IMAGE_NAME $KEYMAN_CONTAINER_NAME
builder_run_action stop       stop_docker_container  $KEYMAN_IMAGE_NAME $KEYMAN_CONTAINER_NAME
builder_run_action build      build_docker_container $KEYMAN_IMAGE_NAME $KEYMAN_CONTAINER_NAME $BUILDER_CONFIGURATION
builder_run_action start      start_docker_container $KEYMAN_IMAGE_NAME $KEYMAN_CONTAINER_NAME $KEYMAN_CONTAINER_DESC $HOST_DOWNLOADS_KEYMAN_COM $PORT_DOWNLOADS_KEYMAN_COM $BUILDER_CONFIGURATION

# builder_run_action test       test_docker_container
