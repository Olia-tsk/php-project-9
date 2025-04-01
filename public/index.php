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

$container->set(PDO::class, function () {
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    $connection = new PDO("pgsql:host=" . $databaseUrl['host'] . ";dbname=" . ltrim($databaseUrl['path'], '/'), $databaseUrl['user'], $databaseUrl['pass']);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $connection;
});

$container->set('flash', function () {
    return new Messages();
});

$initFilePath = implode('/', [dirname(__DIR__), 'database.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(PDO::class)->exec($initSql);

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$repo = $container->get(UrlRepository::class);

$app->get('/urls', function ($request, $response) use ($repo) {
    $urls = $repo->getEntities();
    $params = [
        'urls' => $urls
    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) use ($repo) {
    $messages = $this->get('flash')->getMessages();
    $url = $repo->find($args['id']);
    $params = [
        'flash' => $messages,
        'url' => $url
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('url');

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('urls');
    $router->urlFor('url');

    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->run();
