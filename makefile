migration :
	docker compose exec phpation :
	docker compose exec php php bin/console make:migration
	docker compose exec php php bin/console doctrine:migrations:migrate


migration-tests :
	docker compose exec php php bin/console make:migration ENV=test
	docker compose exec php php bin/console doctrine:migrations:migrate

tests :
	docker compose exec php ./vendor/bin/phpunit --testdox

cache: 
	docker compose exec php bin/console cache:clear

cache-clear-dk :
	docker exec -it followup-php php bin/console cache:clear
	docker exec -it followup-php rm -rf var/cache/*
	docker restart followup-php
	docker-compose restart

tests :
	docker exec -it followup-php ./vendor/bin/phpunit --testdox