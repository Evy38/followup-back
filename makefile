migration : 
	docker compose exec php bin/console doctrine:database:drop --force
	docker compose exec php bin/console doctrine:database:drop --force --env=test
	docker compose exec php bin/console doctrine:database:create
	docker compose exec php bin/console doctrine:database:create --env=test
	docker compose exec php php bin/console make:migration 
	docker compose exec php php bin/console doctrine:migrations:migrate 
	docker compose exec php php bin/console doctrine:migrations:migrate --env=test
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction 
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction --env=test

create-db :
	docker compose exec php bin/console doctrine:database:create
	docker compose exec php bin/console doctrine:database:create --env=test

migration-db :
	docker compose exec php php bin/console make:migration 
	docker compose exec php php bin/console doctrine:database:create 
	docker compose exec php php bin/console doctrine:migrations:migrate 
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction 


migration-tests :
	docker compose exec php php bin/console make:migration --env=test
	docker compose exec php php bin/console doctrine:database:create --env=test
	docker compose exec php php bin/console doctrine:migrations:migrate --env=test
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction --env=test
	
migration-verif : 
	docker compose exec php bin/console doctrine:migrations:status

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