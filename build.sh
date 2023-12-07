#!/usr/bin/env bash

rm -f uddns-extension.zip

(cd src && zip -r "../uddns-extension.zip"  \
    --exclude="*/\.DS_Store" \
    --exclude=build.sh \
    --exclude=docs/* \
    --exclude=uddns-extension.zip \
    --exclude="*/\.*" ./*)