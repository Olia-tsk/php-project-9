<?php

require __DIR__ . '/../vendor/autoload.php';

use Analyzer\Url as Url;
use Analyzer\UrlCheckRepository;
use Analyzer\UrlValidator;
use Analyzer\UrlRepository;
use DI\Container;
use DiDom\Document;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;

use function DI\string;

session_start();

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$container = new Container();

$container->set(PDO::class, function () {
    $dbDriver = $_ENV['DB_CONNECTION'];
    $dbHost = $_ENV['DB_HOST'];
    $dbName = $_ENV['DB_DATABASE'];
    $dbUser = $_ENV['DB_USERNAME'];
    $dbPass = $_ENV['DB_PASSWORD'];
    $dbPort = $_ENV['DB_PORT'] ?? null;

    if ($dbDriver === 'mysql') {
        $dsn = "mysql:host=$dbHost;dbname=$dbName" . ($dbPort ? ";port=$dbPort" : "") . ";charset=utf8mb4";
    } elseif ($dbDriver === 'pgsql') {
        $dsn = "pgsql:host=$dbHost;dbname=$dbName" . ($dbPort ? ";port=$dbPort" : "");
    } else {
        throw new Exception("Unsupported DB driver: $dbDriver");
    }

    $connection = new PDO($dsn, $dbUser, $dbPass);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $connection;
});

$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::createFromContainer($container);

$container->set('router', fn() => $app->getRouteCollector()->getRouteParser());

$container->set('renderer', function ($container) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates', ['router' => $container->get('router')]);
    $renderer->setLayout('layouts/layout.php');

    $renderer->addAttribute('getCurrentRoute', function ($request) {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route ? $route->getName() : '';
    });
    return $renderer;
});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function ($request, $exception, $displayErrorDetails) {
        $response = new \Slim\Psr7\Response();
        return $this->get('renderer')->render($response->withStatus(404), "404.phtml");
    }
);

$app->get('/', function ($request, $response) {
    $params = [
        'currentRoute' => $this->get('renderer')->getAttribute('getCurrentRoute')($request),
        'url' => ['name' => ''],
    ];

    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('/');

$app->get('/urls', function ($request, $response) {
    $urls = $this->get(UrlRepository::class)->getEntities();
    $lastChecks = $this->get(UrlCheckRepository::class)->getAllLastChecks();

    $urlsCheckData = array_reduce($urls, function ($result, $url) use ($lastChecks) {
        $lastCheck = array_filter($lastChecks, function ($check) use ($url) {
            return $check->getUrlId() === $url->getId();
        });

        $lastCheck = array_values($lastCheck);

        $result[] = [
            "id" => $url->getId(),
            'name' => $url->getName(),
            'statusCode' => $lastCheck ? $lastCheck[0]->getStatusCode() : '',
            'createdAt' => $lastCheck ? $lastCheck[0]->getCheckDate() : '',
        ];

        return $result;
    });

    $params = [
        'urlsCheckData' => $urlsCheckData,
        'currentRoute' => $this->get('renderer')->getAttribute('getCurrentRoute')($request),
        'urlRepo' => $this->get(UrlRepository::class),
    ];
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];

    $url = $this->get(UrlRepository::class)->find($id);

    if (is_null($url)) {
        return $this->get('renderer')->render($response->withStatus(404), "404.phtml");
    }

    $params = [
        'flash' => $messages,
        'url' => $url,
        'checkData' => $this->get(UrlCheckRepository::class)->getChecks($args['id']),
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) {
    $urlData = $request->getParsedBodyParam('url');

    $validator = new UrlValidator();
    $errors = $validator->validate($urlData);

    if (count($errors) != 0) {
        $params = [
            'errors' => $errors,
            'url' => $urlData
        ];

        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
    }

    $parsedUrl = parse_url($urlData['name']);
    $urlData['name'] = mb_strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");

    $url = $this->get(UrlRepository::class)->findByName($urlData['name']);

    if (!is_null($url)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        $id = $url->getId();
        return $response->withRedirect($this->get('router')->urlFor('urls.show', ['id' => (string) $id]));
    }

    $url = new Url($urlData['name']);
    $this->get(UrlRepository::class)->save($url);
    $id = $url->getId();
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withRedirect($this->get('router')->urlFor('urls.show', ['id' => (string) $id]));
})->setName('urls.store');

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, $args) {
    $urlId = $args['url_id'];
    $url = $this->get(UrlRepository::class)->find($urlId);
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
    } catch (RequestException $e) {
        $statusCode = $e->getResponse()?->getStatusCode();

        if ($statusCode !== null) {
            $h1 = null;
            $title = null;
            $description = null;
            $this->get(UrlCheckRepository::class)->addCheck($urlId, $statusCode, $h1, $title, $description);
        } else {
            $this->get('flash')->addMessage('error', 'Ошибка запроса. Код ответа отсутствует.');
        }

        $this->get('flash')->addMessage('error', "Ошибка ответа. Код: $statusCode");
    } catch (ConnectException $e) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    return $response->withRedirect($this->get('router')->urlFor('urls.show', ['id' => (string) $urlId]));
})->setName('urls.check');

$app->run();
