<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Set up Twig Templating Engine
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

// Set up Monolog
$app->register(new Silex\Provider\MonologServiceProvider(), array(
#    'monolog.logfile' => __DIR__.'/development.log',
    'monolog.logfile' => 'php://stderr',
    'monolog.level' => \Monolog\Logger::WARNING,
));

// Set up Neo4j Client
$app['neo4j'] = function() {
    $host = getenv('NEO4J_URL') !== false ? getenv('NEO4J_URL') : 'http://localhost:7474';

    $client = \GraphAware\Neo4j\Client\ClientBuilder::create()
        ->addConnection('default', $host)
        ->build();

    return $client;
};

// Should come from environment variable
$app['debug'] = true;


// Application routes
$app->get('/dashboard/feed', '\\Neo4j\\Alexa\\Controller\\DashboardController::feed');

$app->get('/', '\\Neo4j\\Alexa\\Controller\\DashboardController::index');

$app->post('/intent', '\\Neo4j\\Alexa\\Controller\\IntentController::handleIntent');

// Run the application
$app->run();