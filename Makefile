.PHONY: help up down build restart ps logs logs-queue logs-scheduler sh root psql redis composer art migrate fresh seed tinker test clear

# По умолчанию при вызове просто `make` выводится справка
.DEFAULT_GOAL := help

DC := docker compose
APP := app
DB_USER := dental_user
DB_NAME := dental_crm

help: ## Показать список всех команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

# --- Управление Docker ---

up: ## Поднять все контейнеры в фоне
	$(DC) up -d

down: ## Остановить и удалить контейнеры
	$(DC) down

build: ## Пересобрать образы и поднять контейнеры
	$(DC) up -d --build

restart: ## Перезапустить контейнеры
	$(DC) restart

ps: ## Показать статус контейнеров
	$(DC) ps

logs: ## Смотреть логи всех сервисов
	$(DC) logs -f --tail=100

logs-queue: ## Логи воркера очередей Redis
	$(DC) logs -f --tail=100 queue

logs-scheduler: ## Логи планировщика (Scheduler)
	$(DC) logs -f --tail=100 scheduler

# --- Вход в контейнеры ---

sh: ## Войти в bash контейнера PHP (app)
	$(DC) exec $(APP) bash

psql: ## Войти в консоль PostgreSQL (psql)
	$(DC) exec postgres psql -U $(DB_USER) -d $(DB_NAME)

redis: ## Войти в консоль Redis (redis-cli)
	$(DC) exec redis redis-cli

# --- Laravel и Composer ---

art: ## Запустить artisan (пример: make art c="route:list")
	$(DC) exec $(APP) php artisan $(c)

composer: ## Запустить composer (пример: make composer c="dump-autoload")
	$(DC) exec $(APP) composer $(c)

migrate: ## Применить миграции
	$(DC) exec $(APP) php artisan migrate

fresh: ## Пересоздать БД с нуля и наполнить сидами
	$(DC) exec $(APP) php artisan migrate:fresh --seed

seed: ## Запустить сиды
	$(DC) exec $(APP) php artisan db:seed

tinker: ## Открыть Laravel Tinker
	$(DC) exec $(APP) php artisan tinker

test: ## Запустить автотесты (Feature / Unit)
	$(DC) exec $(APP) php artisan test

clear: ## Очистить все кэши Laravel
	$(DC) exec $(APP) php artisan optimize:clear
