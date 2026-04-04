# Posiflora — MVP интеграции Telegram

Backend на Symfony 8 и фронтенд в **`frontend/`** (Vite + React + TypeScript). Подробные требования и план работ — в каталоге [doc/](doc/).

## Быстрый старт

1. Установить зависимости: `composer install`
2. Поднять инфраструктуру: `docker compose up -d` (PostgreSQL на порту **5432**, см. [compose.override.yaml](compose.override.yaml))
3. Убедиться, что строка `DATABASE_URL` в `.env` или `.env.local` совпадает с учётными данными контейнера. Значения по умолчанию в Compose: пользователь и база `postgre`, пароль `postgre`, хост с хост-машины `127.0.0.1`, порт `5432` — как в шаблоне `.env`.
4. Запуск веб-сервера для разработки: `symfony server:start` (или настройте свой vhost на `public/`).

Расширенные сценарии (Symfony CLI, работа с БД из консоли) описаны в [doc/README.md](doc/README.md).

## Фронтенд

1. Установить [Node.js](https://nodejs.org/) (LTS) и использовать npm из состава Node.
2. В каталоге проекта: `cd frontend && npm install`
3. Если API открывается с другого origin (типично: Symfony на порту **8000**, Vite на **5173**), скопируйте `frontend/.env.example` в `frontend/.env` и задайте `VITE_API_BASE_URL` на базовый URL API (без завершающего слэша), например `http://127.0.0.1:8000`.
4. Запуск дев-сервера: `npm run dev`. Маршрут экрана настроек Telegram совпадает с [ТЗ](doc/ТЗ.md); детали стека и структуры кода — в [doc/Frontend.md](doc/Frontend.md). Краткий отчёт по фазе 5 — [doc/Что сделано - фаза 5.md](doc/Что%20сделано%20-%20фаза%205.md).

Сборка и проверка статики: `npm run build`; локальный просмотр сборки: `npm run preview`.

## Переменные окружения

- **`DATABASE_URL`** — подключение Doctrine к PostgreSQL; переопределения без коммита — в `.env.local`.
- **`TELEGRAM_USE_REAL_API`** — переключение режима отправки в Telegram:
  - `false` (по умолчанию) — мок без обращения к сети (удобно для локальной разработки и тестов);
  - `true` — реальные вызовы Bot API через `App\Telegram\HttpTelegramClient`.

Параметр контейнера: `telegram.use_real_api` (см. [config/services.yaml](config/services.yaml)).

## Качество кода

В репозитории не зафиксированы отдельные конфигурации PHPStan или PHP CS Fixer; полезная проверка на текущем этапе — `./vendor/bin/phpunit`. При необходимости инструменты статического анализа и стиля можно добавить отдельно.
