<?php
/*
 * Router script for `php -S`, for any of the three applications in this port.
 *
 * Without one, `php -S -t public public/index.php` sets SCRIPT_FILENAME to the
 * *requested file* whenever that file exists, and Symfony's autoload_runtime.php
 * does `$app = require $_SERVER['SCRIPT_FILENAME']` - so a request for a
 * stylesheet requires the stylesheet as PHP and dies with "Invalid return value:
 * callable object expected, int returned". It answers 500 with the CSS as the
 * body, which curl reports as served content and a browser aborts, so the page
 * renders unstyled with nothing in the request log to explain it. That is the
 * §5.36 failure mode again: the assets are published, they just never arrive.
 *
 * So: serve a real file as a real file, and hand everything else to the front
 * controller with SCRIPT_FILENAME pointing at the front controller.
 *
 *   php8.5 -S 127.0.0.1:8802 -t next/public migration/bin/serve-router.php
 */

$root = realpath($_SERVER['DOCUMENT_ROOT']);
$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = realpath($root . $path);

// A real file inside the docroot, and not the front controller itself, is static.
// strpos rather than str_starts_with: this router also serves the SYMFONY 2 tree
// under PHP 7.4, where str_starts_with does not exist. It fatalled on every
// static request there - so legacy served no stylesheet, no script and no image,
// and every screenshot and measurement taken of it was of an unstyled page.
if ($file !== false && is_file($file) && strpos($file, $root . DIRECTORY_SEPARATOR) === 0
    && basename($file) !== 'index.php') {
    return false;
}

$front = $root . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $front;
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $front;
