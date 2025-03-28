<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

$container = new Container();

$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/urls', function ($request, $response) {
    return $this->get('renderer')->render($response, 'urls.phtml');
})->setName('urls');

$app->get('/urls/1', function ($request, $response) {
    return $this->get('renderer')->render($response, 'url.phtml');
})->setName('url');

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('urls');
    $router->urlFor('url');

    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->run();
