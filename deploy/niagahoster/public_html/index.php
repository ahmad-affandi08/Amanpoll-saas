<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Struktur production yang direkomendasikan:
// domains/domain-anda.tld/
// ├── amanpoll/       <- seluruh source Laravel
// └── public_html/    <- HANYA isi folder deploy/niagahoster/public_html
$amanpollRoot = dirname(__DIR__) . '/amanpoll';

if (file_exists($maintenance = $amanpollRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $amanpollRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $amanpollRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
