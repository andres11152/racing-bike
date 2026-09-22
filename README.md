# RACING BIKE 1998 — Store

WordPress theme built on [Sage 11](https://roots.io/sage/) (Blade + Acorn) with Tailwind CSS v4 and WooCommerce.

## Requirements

- Docker
- Node 20.19+ / 22.12+
- PHP 8.4.1+ and Composer (only needed to install the theme's dependencies on the host)

## Running the local environment

```
cp .env.example .env
docker compose up -d
```

The store is available at http://localhost:8080 and the admin at `/wp-admin` (`admin` / `admin`).

The theme is mounted from the host: editing Blade, CSS or JS is reflected immediately, with no need to rebuild the image.

## Provisioning from scratch

If the volume is empty, after `docker compose up -d`:

```
set -a && . ./.env && set +a

# 1. Install WordPress
docker compose run --rm wpcli wp core install \
  --url="$WP_URL" --title="$WP_TITLE" \
  --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email="$WP_ADMIN_EMAIL" --skip-email

# 2. Theme and WooCommerce
docker compose run --rm wpcli wp theme activate racing-bike-theme
docker compose run --rm wpcli wp plugin install woocommerce --activate

# 3. Register the sage-woocommerce service provider and publish its views
docker compose run --rm wpcli wp acorn package:discover
docker compose run --rm wpcli wp acorn vendor:publish --tag="woocommerce-template-views"

# 4. Sample catalog and menu
docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-catalog.php
docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-menu.php
docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-home.php
```

## Assets

```
cd racing-bike-theme
npm install
npm run build # production
npm run dev   # Vite dev server
```

## Configuration notes

These are known gotchas, not preferences — the site breaks without them:

- `wp acorn package:discover` is required after installing `generoi/sage-woocommerce`. Without it, `vendor:publish` responds "No publishable resources" because the service provider isn't registered.
- Cart and Checkout use shortcodes, not blocks: `[woocommerce_cart]` and `[woocommerce_checkout]`. WooCommerce 9+ creates those pages with blocks, which ignore the theme's templates.
- Coming soon mode must be disabled (`woocommerce_coming_soon = no`), or logged-out visitors won't see the theme.
- `config.platform.php` is pinned to `8.4.1` in `composer.json`. The actual dependency tree (Acorn 6 + Symfony 8) requires that minimum; without pinning it, running `composer install` on PHP 8.5 produces a `vendor/` that breaks on containers with an older PHP.
- Currency is configured as COP with a `.` thousands separator and 0 decimals.
- View Composers expose data via `with()`, not as public methods. Acorn wraps public methods in `InvokableComponentVariable`; when one of those variables is passed to a component attribute (`:slides="$slides"`), Blade escapes it and `htmlspecialchars()` fails on an array. `with()` delivers plain values.

## Structure

```
racing-bike-theme/
├── app/                      # Setup, filters, View Composers
├── resources/
│   ├── css/app.css           # Design tokens + utilities
│   ├── js/app.js             # Drawers, carousel, interactions
│   └── views/
│       ├── components/       # Reusable Blade components
│       ├── sections/         # Header, footer
│       └── woocommerce/      # Store templates (published)
└── public/build/             # Compiled assets (gitignored)

scripts/                      # Development seed data
```
