<?php

require __DIR__ . '/../vendor/autoload.php';

use Analyzer\Check;
use Analyzer\Url as Url;
use Analyzer\CheckRepository;
use Analyzer\Validator;
use Analyzer\UrlRepository;
use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

session_start();

$container = new Container();

$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set(PDO::class, function () {
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    $dbHost = $databaseUrl['host'];
    $dbName = ltrim($databaseUrl['path'], '/');
    $dbUser = $databaseUrl['user'];
    $dbPass = $databaseUrl['pass'];
    $connection = new PDO("pgsql:host=" . $dbHost . ";dbname=" . $dbName, $dbUser, $dbPass);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $connection;
});

$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::createFromContainer($container);
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$repo = $container->get(UrlRepository::class);
$checkRepo = $container->get(CheckRepository::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function ($request, $exception, $displayErrorDetails) {
    $response = new \Slim\Psr7\Response();
    return $this->get('renderer')->render($response->withStatus(404), "404.phtml");
});

$app->get('/urls', function ($request, $response) use ($repo, $checkRepo, $router) {
    $urls = $repo->getEntities();
    $urlsCheckData = array_map(function ($url) use ($checkRepo) {
        $lastCheck = $checkRepo->getLastCheck($url->getId());
        if ($lastCheck) {
            $check = new Check();
            $check->setUrlId($lastCheck['url_id']);
            $check->setName($url->getName());
            $check->setLastCheck($lastCheck['created_at']);
            $check->setStatusCode($lastCheck['status_code']);
        }
        return $check;
    }, $urls);

    $params = [
        'urlsCheckData' => $urlsCheckData,
        'router' => $router
    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) use ($repo, $checkRepo, $router) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];

    $url = $repo->find($id);

    if (!$url) {
        return $this->get('renderer')->render($response->withStatus(404), "404.phtml",);
    }

    $params = [
        'flash' => $messages,
        'url' => $url,
        'checkData' => $checkRepo->getCheck($args['id']),
        'router' => $router
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) use ($router, $repo) {
    $urlData = $request->getParsedBodyParam('url');

    $validator = new Validator();
    $errors = $validator->validate($urlData);

    if (count($errors) != 0) {
        $params = [
            'url' => new Url(),
            'errors' => $errors
        ];

        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
    }

    $parsedUrl = parse_url($urlData['name']);
    $urlData['name'] = mb_strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");
    $url = Url::fromArray($urlData);
    $result = $repo->save($url);
    $id = $url->getId();
    $this->get('flash')->addMessage('success', $result);

    $params = [
        'id' => (string) $id
    ];

    return $response->withRedirect($router->urlFor('urls.show', $params));
})->setName('urls.store');

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, $args) use ($router, $repo) {
    $urlId = $args['url_id'];
    $url = $repo->find($urlId);
    $client = new Client();

    try {
        $responseResult = $client->get($url->getName());
        $statusCode = $responseResult->getStatusCode();
        $body = $responseResult->getBody()->getContents();
        $document = new Document($body);
        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();
        $description = optional($document->first('meta[name=description]'))->getAttribute('content');

        $this->get(CheckRepository::class)->addCheck($urlId, $statusCode, $h1, $title, $description);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (Exception $e) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    $params = [
        'id' => $urlId,
    ];

    return $response->withRedirect($router->urlFor('urls.show', $params));
})->setName('urls.check');

$app->run();
