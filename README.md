# NutriFit

Application web de suivi nutrition/fitness (repas, activités, objectifs) basée sur Symfony.

## Prérequis

- PHP >= 8.4
- Composer
- MySQL 8.x (ou MariaDB/PostgreSQL si tu adaptes `DATABASE_URL`)
- Node.js (utile pour recompiler le SCSS via `npx sass`)

## Installation

1) Installer les dépendances PHP

```bash
composer install
```

2) Configurer l’environnement

Crée/édite `.env.local` avec au minimum :

```
APP_ENV=dev
APP_SECRET=une_valeur_aleatoire
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/nutrifit?serverVersion=8.0.43&charset=utf8mb4"

# API externes
SPOONACULAR_API_KEY=ton_cle
```

3) Créer la base et lancer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4) (Optionnel) Recompiler le SCSS

```bash
npx --yes sass --style=expanded --no-source-map assets/styles/scss/main.scss assets/styles/main.css
npx --yes sass --style=compressed --no-source-map assets/styles/scss/main.scss assets/styles/main.min.css

npx --yes sass --style=expanded --no-source-map assets/styles/scss/admin.scss assets/styles/admin.css
npx --yes sass --style=compressed --no-source-map assets/styles/scss/admin.scss assets/styles/admin.min.css
```

## Lancer l’application

```bash
symfony serve
```

ou

```bash
php -S 127.0.0.1:8000 -t public
```

Puis ouvrir : http://127.0.0.1:8000

## Comptes / Accès

- Admin : accès via `/admin` (EasyAdmin)
- Utilisateur : inscription via `/register`

## Notes

- La recherche de recettes utilise l’API Spoonacular.
- Le front public est en CSS/SCSS (mobile-first).
