### Hexlet tests and linter status:

[![Actions Status](https://github.com/Olia-tsk/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Olia-tsk/php-project-9/actions)
[![Maintainability](https://qlty.sh/badges/628a7b41-0810-4e22-9d7c-9f4e6e58695e/maintainability.svg)](https://qlty.sh/gh/Olia-tsk/projects/php-project-9)
[![make-lint](https://github.com/Olia-tsk/php-project-9/actions/workflows/make-lint.yml/badge.svg)](https://github.com/Olia-tsk/php-project-9/actions/workflows/make-lint.yml)

## Проект модуля #3 Анализатор страниц

### О проекте:

Анализатор страниц – полноценное приложение на базе фреймворка Slim, которое анализирует указанные страницы на SEO пригодность по аналогии с PageSpeed Insights.

В этом проекте отрабатываются базовые принципы построения современных сайтов на MVC-архитектуре: работа с роутингом, обработчиками запросов и шаблонизатором, взаимодействие с базой данных.

### Демо:

Проект [Анализатор страниц](https://panalyzer.olala-dev.ru/)

### Минимальные требования

- Composer 2.8.1
- PHP 8.3
- Slim 4.14
- PostgreSQL 16.8 или MySQL 8.0

### Запуск

1. Склонируйте репозиторий:

```
git@github.com:Olia-tsk/php-project-9.git
```

2. Перейдите в директорию проекта:

```
cd php-project-9
```

3. Установите зависимости:

```
make install
```

4. Создайте файл `.env` в корне проекта и укажите параметры подключения к вашей базе данных.

Пример для PostgreSQL:

```
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

Пример для MySQL:

```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

5. Создайте таблицы в БД выполнив команду (выберите подходящую для вашей СУБД):

Для PostgreSQL:

```
make create-db-postgres DB_USER=your_db_user DB_HOST=localhost DB_NAME=your_db_name
```

Для MySQL:

```
make create-db-mysql DB_USER=your_db_user DB_PASSWORD=your_db_password DB_HOST=localhost DB_NAME=your_db_name
```

6. Запустите проект:

```
make start
```

7. Откройте в браузере:

```
http://localhost:8000
```

### Информация о рутах и методах

| Метод | Путь              | Инфо                                     |
| ----- | ----------------- | ---------------------------------------- |
| GET   | /                 | главная страница                         |
| GET   | /urls             | получение списка всех добавленных сайтов |
| GET   | /urls/{id}        | просмотр информации о сайте              |
| POST  | /urls             | запуск валидации сайта перед добавлением |
| POST  | /urls/{id}/checks | запуск проверки сайта                    |
