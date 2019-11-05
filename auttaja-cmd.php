<?php

$firstArg = (count($argv) >= 2) ? $argv[1] : null;
$secondArg = (count($argv) >= 3) ? $argv[2] : null;

$path = realpath(__DIR__) . DIRECTORY_SEPARATOR;

switch ($firstArg) {
    case null:
    case 'env':
        (new \AuttajaCmd\Runner())->runEnvFileProcessor(); // TODO: Get file path from arguments list
        break;
}