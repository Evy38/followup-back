migration :
	docker compose exec php php bin/console make:migration
	docker compose exec php php bin/console doctrine:migrations:migrate

tests :
	docker compose exec php ./vendor/bin/phpunit --testdox

cache : 
	docker compose exec php bin/console cache:clear