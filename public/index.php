<?php

require __DIR__ . '/../vendor/autoload.php';

use Analyzer\UrlCheck;
use Analyzer\Url as Url;
use Analyzer\UrlCheckRepository;
use Analyzer\UrlValidator;
use Analyzer\UrlRepository;
use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

use function DI\string;

session_start();

$renderer = new PhpRenderer(__DIR__ . '/../templates');
$renderer->setLayout('layouts/layout.php');

$container = new Container();

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

$router = $app->getRouteCollector()->getRouteParser();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function ($request, $exception, $displayErrorDetails) use ($renderer, $router) {
        $response = new \Slim\Psr7\Response();
        return $renderer->render($response->withStatus(404), "404.phtml", ['router' => $router]);
    }
);

$urlRepo = $container->get(UrlRepository::class);
$checkRepo = $container->get(UrlCheckRepository::class);

$app->get('/', function ($request, $response) use ($renderer, $router) {
    $params = [
        'router' => $router,
        'url' => ['name' => ''],
    ];

    return $renderer->render($response, 'index.phtml', $params);
})->setName('/');

$app->get('/urls', function ($request, $response) use ($urlRepo, $checkRepo, $router, $renderer) {

    $urls = $urlRepo->getEntities();
    $lastChecks = $checkRepo->getAllLastChecks();

    $urlsCheckData = array_reduce($urls, function ($result, $url) use ($lastChecks) {
        $lastCheck = array_filter($lastChecks, function ($check) use ($url) {
            return $check->getUrlId() === $url->getId();
        });

        $lastCheck = array_values($lastCheck);

        $result[] = [
            "id" => $url->getId(),
            'name' => $url->getName(),
            'status_code' => $lastCheck ? $lastCheck[0]->getStatusCode() : '',
            'created_at' => $lastCheck ? $lastCheck[0]->getCheckDate() : '',
        ];

        return $result;
    });

    $params = [
        'urlsCheckData' => $urlsCheckData,
        'router' => $router,
        'urlRepo' => $urlRepo,
    ];
    return $renderer->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) use ($urlRepo, $checkRepo, $router, $renderer) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];

    $url = $urlRepo->find($id);

    if (is_null($url)) {
        return $renderer->render($response->withStatus(404), "404.phtml", ['router' => $router]);
    }

    $params = [
        'flash' => $messages,
        'url' => $url,
        'checkData' => $checkRepo->getChecks($args['id']),
        'router' => $router
    ];
    return $renderer->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) use ($router, $urlRepo, $renderer) {
    $urlData = $request->getParsedBodyParam('url');

    $validator = new UrlValidator();
    $errors = $validator->validate($urlData);

    if (count($errors) != 0) {
        $params = [
            'errors' => $errors,
            'router' => $router,
            'url' => $urlData
        ];

        return $renderer->render($response->withStatus(422), 'index.phtml', $params);
    }

    $parsedUrl = parse_url($urlData['name']);
    $urlData['name'] = mb_strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");

    $url = $urlRepo->findByName($urlData['name']);

    if (!is_null($url)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        $id = $url->getId();
        return $response->withRedirect($router->urlFor('urls.show', ['id' => (string) $id]));
    }

    $url = new Url($urlData['name']);
    $urlRepo->save($url);
    $id = $url->getId();
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($router->urlFor('urls.show', ['id' => (string) $id]));
})->setName('urls.store');

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, $args) use ($router, $urlRepo) {
    $urlId = $args['url_id'];
    $url = $urlRepo->find($urlId);
    $client = new Client();

    try {
        $responseResult = $client->get($url->getName());
        $statusCode = $responseResult->getStatusCode();
        $body = $responseResult->getBody()->getContents();
        $document = new Document($body);
        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();
        $descriptionTag = $document->first('meta[name=description]') ?? null;
        $description = $descriptionTag ? $descriptionTag->getAttribute('content') : null;
        $this->get(UrlCheckRepository::class)->addCheck($urlId, $statusCode, $h1, $title, $description);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (RequestException | ConnectException $e) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    return $response->withRedirect($router->urlFor('urls.show', ['id' => (string) $urlId]));
})->setName('urls.check');

$app->run();
