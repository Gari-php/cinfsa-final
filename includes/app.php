<?php

require 'funciones.php';
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require 'database.php';
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Conectarnos a la base de datos
use Models\ActiveRecord;
ActiveRecord::setDB($db);
