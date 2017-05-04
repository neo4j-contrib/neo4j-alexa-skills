<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Set up Twig Templating Engine
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

// Set up Monolog
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));

// Should come from environment variable
$app['debug'] = true;


// Application routes
$app->get('/dashboard/feed', '\\Neo4j\\Controller\\DashboardController::feed');

$app->get('/', '\\Neo4j\\Controller\\RawTextSkillController::rawTextIntent');

// Run the application
$app->run();