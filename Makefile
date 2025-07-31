PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

install:
	composer install

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src

create-db-postgres:
	psql -U $(DB_USER) -h $(DB_HOST) -d $(DB_NAME) -f database.sql

create-db-mysql:
	mysql -u$(DB_USER) -p$(DB_PASSWORD) -h $(DB_HOST) $(DB_NAME) < database.sql