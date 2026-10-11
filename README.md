# Base PHP Project Structure

A lightweight, fast starting point for small-to-medium websites built with **vanilla TypeScript** on the front end and **vanilla PHP** on the back end, wired together with **Composer** for PSR-4 autoloading.

It ships with a few aggregated, ready-to-use building blocks (database layer, entities, utilities, dev tooling) so you can start a new project quickly instead of scaffolding everything from scratch.

## Stack

- **Backend** — vanilla PHP 8.3, no framework, autoloaded via Composer (PSR-4).
- **Frontend** — vanilla TypeScript, bundled with [esbuild](https://esbuild.github.io/) (no framework).
- **Database** — MySQL (via `mysqli`), with a thin query wrapper and entity classes.
- **Infra** — Docker Compose (Apache + PHP, MySQL, phpMyAdmin).
- **DX** — hot-reload watcher, TypeScript type-checking, Xdebug debugging support.

## Project layout

```
.
├── docker-compose.yml          # PHP (Apache) + MySQL + phpMyAdmin
├── Dockerfile                  # Alpine Apache + PHP 8.3 image
└── website/
    ├── composer.json           # PSR-4 autoloading + file autoloads
    ├── src/
    │   ├── data/
    │   │   ├── config/         # misc config helpers
    │   │   ├── db/             # Database wrapper, Entities, Classes
    │   │   └── utils.php       # shared utility functions
    │   └── ts/                 # TypeScript source (main, editor, hot-reload, pages/)
    └── www/
        ├── package.json        # esbuild + TypeScript scripts
        ├── tsconfig.json
        ├── run-watcher.sh      # hot-reload dev server
        └── public/             # web root (served by Apache)
```

## Getting started

### 1. Backend dependencies

```sh
cd website
composer install
```

### 2. Frontend dependencies

```sh
cd website/www
pnpm install   # or npm install
```

### 3. Run

```sh
# from the repo root
docker compose up -d
```

For local TypeScript development with hot reload:

```sh
cd website/www
pnpm dev
```

## Notes

- PHP namespaces are autoloaded with Composer: the `database\` namespace maps to `src/data/db/`, `src/data/db/Entities/`, and `src/data/db/Classes/`.
- `src/data/utils.php` and `src/data/config/misc.php` are autoloaded on every request via Composer's `files` entry.
- The front end is plain TypeScript with no runtime framework; `main.ts` is the entry point and `pages/*/main.ts` are built per-page.
