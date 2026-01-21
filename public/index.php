<?php

use Core\Init;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

Init::handle();
