<?php

/** @var string $content */
/** @var string $currentRoute */
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализатор страниц</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>

<body class="min-vh-100 d-flex flex-column">
    <header class="flex-shrink-0">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark px-3">
            <a class="navbar-brand" href="/">Анализатор страниц</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentRoute == '/' ? 'active' : '' ?>" href="/">Главная</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentRoute == 'urls.index' ? 'active' : '' ?>" href="/urls">Сайты</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <?php if (!empty($flash['success'])) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash['success'][0]) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['info'])) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash['info'][0]) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error'])) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flash['error'][0]) ?></div>
    <?php endif; ?>

    <?= $content ?>

    <footer class="border-top py-3 mt-5 flex-shrink-0">
        <div class="container-lg">
            <div class="text-center">
                <a href="https://hexlet.io/pages/about" target="_blank">Hexlet</a>
            </div>
        </div>
    </footer>

</body>

</html>