<?php

require __DIR__ . '/../vendor/autoload.php';

use Analyzer\Url as AnalyzerUrl;
use Analyzer\CheckRepository;
use Analyzer\Validator;
use Analyzer\UrlRepository;
use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
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
$checkRepo = $container->get(CheckRepository::class);

$app->get('/urls', function ($request, $response) use ($repo, $checkRepo) {
    $urls = $repo->getEntities();
    $urlsCheckData = array_map(function ($url) use ($checkRepo) {
        $lastCheck = $checkRepo->getLastCheck($url->getId());
        if ($lastCheck) {
            $url->setLastCheck($lastCheck['created_at']);
            $url->setStatusCode($lastCheck['status_code']);
        }
        return $url;
    }, $urls);

    $params = [
        'urlsCheckData' => $urlsCheckData

    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id}', function ($request, $response, $args) use ($repo, $checkRepo) {
    $messages = $this->get('flash')->getMessages();
    $url = $repo->find($args['id']);
    $params = [
        'flash' => $messages,
        'url' => $url,
        'checkData' => $checkRepo->getCheck($args['id'])
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) use ($router, $repo) {
    $urlData = $request->getParsedBodyParam('url');

    $validator = new Validator();
    $errors = $validator->validate($urlData);

    if (count($errors) === 0) {
        $parsedUrl = parse_url($urlData['name']);
        $urlData['name'] = strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");
        $url = AnalyzerUrl::fromArray($urlData);
        $result = $repo->save($url);
        $id = $url->getId();
        $this->get('flash')->addMessage('success', $result);
        $params = [
            'id' => (string) $id
        ];
        return $response->withRedirect($router->urlFor('urls.show', $params));
    }

    $params = [
        'url' => new AnalyzerUrl(),
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
})->setName('urls.store');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router, $repo, $checkRepo) {
    $url_id = $args['url_id'];
    $url = $repo->find($url_id);
    $client = new Client();

    try {
        $requestResult = $client->get($url->getName());
        $status_code = $requestResult->getStatusCode();
        $body = $requestResult->getBody()->getContents();
        $document = new Document($body);
        $h1 = optional($document->first('h1'))->text() ?? null;
        $title = optional($document->first('title'))->text() ?? null;
        $descTag = $document->first('meta[name=description]') ?? null;
        $description = $descTag ? $descTag->getAttribute('content') : null;

        $checkRepo->addCheck($url_id, $status_code, $h1, $title, $description);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (Exception $e) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
    }


    $params = [
        'id' => $url_id,
    ];

    return $response->withRedirect($router->urlFor('urls.show', $params));
})->setName('urls.check');

$app->run();
