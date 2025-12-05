Commands:

Валидация схемы БД:
php bin/console doctrine:schema:validate

Создание миграции:
php bin/console make:migration

Накатка миграции:
php bin/console doctrine:migrations:migrate

Накатка изменений миграции:
php bin/console doctrine:migrations:diff