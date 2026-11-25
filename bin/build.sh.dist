#!/bin/sh
set -e

echo "Removing old cache if any"
rm -rf var/cache/*
rm -rf var/log/*

echo "Dumping js-routing"
bin/console fos:js-routing:dump


echo "Dumping assets"
# --no-cleanup: without it, the committed files in public/bundles are deleted
bin/console assets:install --symlink --relative --no-cleanup

php bin/console cache:clear

mkdir -p var/log var/cache
chmod -R 0777 var/log/ var/cache/

#'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'

