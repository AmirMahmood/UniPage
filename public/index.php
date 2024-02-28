<?php

use DI\ContainerBuilder;
use Slim\App;

require_once __DIR__ . '/../vendor/autoload.php';

// Start Session
session_start();

// Build DI container instance
$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__ . '/../config/container.php')
    ->build();

// Session validity check
$inactividad = $container->get('settings')['session_lifetime_minute'] * 60; // Session lifetime in seconds
if (isset($_SESSION["timeout"])) {
    $sessionTTL = time() - $_SESSION["timeout"];
    if ($sessionTTL > $inactividad) {
        session_destroy();
        header("Location: /");
    }
}
$_SESSION["timeout"] = time(); // Session lifetime in seconds

// Run App
$container->get(App::class)->run();
