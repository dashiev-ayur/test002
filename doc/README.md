# Запуск окружения для разработки

Документ описывает, как поднять инфраструктуру и приложение на локальной машине (macOS). Описания API и сущностей здесь нет.

## Требования

- PHP и Composer (как при создании проекта через Symfony CLI).
- [Symfony CLI](https://symfony.com/download): `symfony version`.
- Docker Desktop (или иной движок с поддержкой Compose v2).

Файлы Compose в корне проекта:

| Файл | Назначение |
|------|------------|
| `compose.yaml` | База: PostgreSQL (и закомментированный Redis). |
| `compose.override.yaml` | Локальные дополнения: проброс **5432**, Mailpit. Подмешивается **только** при команде `docker compose …` **без** явного списка `-f`. |
| `compose.prod.yaml` | Контейнеры **backend** и **frontend**. Подключается вместе с базой командой для продакшена (см. ниже). |

## Разработка: только инфраструктура в Docker

Поднять PostgreSQL, Mailpit и проброс порта БД на хост:

```bash
docker compose up -d
```

Остановить:

```bash
docker compose down
```

Остановить и удалить том с данными БД (осторожно: данные пропадут):

```bash
docker compose down -v
```

По умолчанию PostgreSQL доступна на хосте на порту **5432** (см. `compose.override.yaml`). Параметры БД задаются в `.env` через переменные `POSTGRES_*` и `DATABASE_URL`. Для секретов и переопределений под себя используйте `.env.local` (файл не коммитится).

## Продакшен: полный стек в Docker

Одна команда собирает образы и поднимает БД, backend и frontend (`compose.override.yaml` **не** используется — нет Mailpit и наружного порта PostgreSQL, если вы не добавите его сами):

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

Остановка:

```bash
docker compose -f compose.yaml -f compose.prod.yaml down
```

Порты UI и API на хосте — **8080** и **8000** (см. корневой [README.md](../README.md)).

### Redis

Сервис Redis в `compose.yaml` **закомментирован**. Когда он понадобится: раскомментируйте блоки `redis` и том `redis_data` в `compose.yaml`, при необходимости добавьте проброс порта в `compose.override.yaml` для разработки (например `6379:6379`) и пропишите DSN в `.env.local`. Для продакшена при необходимости расширьте `compose.prod.yaml` или общую сеть сервисов.

## Веб-приложение Symfony (локальный PHP)

Из корня репозитория:

```bash
composer install   # при первом клонировании
symfony server:start
```

Остановить встроенный сервер: `Ctrl+C` в том же терминале.

Проверка требований окружения:

```bash
symfony check:requirements
```

Полезные команды консоли:

```bash
php bin/console about
php bin/console dbal:run-sql "SELECT 1"
```

## Порядок типичного рабочего дня

1. `docker compose up -d` — только PostgreSQL и прочая инфра из override.
2. `symfony server:start` (второй терминал)
3. При работе с UI: в каталоге `frontend/` выполнить `npm install` (один раз после клонирования), затем `npm run dev` (ещё один терминал). Если фронт и API на разных origin, задайте `VITE_API_BASE_URL` в `frontend/.env` (см. `frontend/.env.example`). Подробности — в [Frontend.md](./Frontend.md).
4. После работы: остановить серверы, при желании `docker compose stop`.

Полный стек в контейнерах для деплоя — в разделе «Продакшен» выше и в корневом README.

## Переменные окружения

- Общие значения по умолчанию — в `.env`.
- Личные пароли, URL и отличия от команды — в `.env.local`.

Не храните прод-секреты в файлах, которые попадают в git.
