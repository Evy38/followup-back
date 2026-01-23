migration :
	docker compose exec php php bin/console make:migration
	docker compose exec php php bin/console doctrine:migrations:migrate

test :
	docker compose exec php ./vendor/bin/phpunit --testdox