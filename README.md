# RACING BIKE 1998 — Tienda

Theme WordPress construido sobre [Sage 11](https://roots.io/sage/) (Blade + Acorn) con Tailwind CSS v4 y WooCommerce.

## Requisitos

- Docker
- Node 20.19+ / 22.12+
- PHP 8.4.1+ y Composer (sólo para instalar dependencias del theme en el host)

## Levantar el entorno local

```bash
cp .env.example .env
docker compose up -d
```

La tienda queda en **http://localhost:8080** y el admin en **/wp-admin** (`admin` / `admin`).

> El theme se monta desde el host: editar Blade, CSS o JS se refleja sin reconstruir la imagen.

### Aprovisionar desde cero

Si el volumen está vacío, tras `docker compose up -d`:

```bash
set -a && . ./.env && set +a

# 1. Instalar WordPress
docker compose run --rm wpcli wp core install \
  --url="$WP_URL" --title="$WP_TITLE" \
  --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email="$WP_ADMIN_EMAIL" --skip-email

# 2. Theme y WooCommerce
docker compose run --rm wpcli wp theme activate racing-bike-theme
docker compose run --rm wpcli wp plugin install woocommerce --activate

# 3. Registrar el service provider de sage-woocommerce y publicar sus vistas
docker compose run --rm wpcli wp acorn package:discover
docker compose run --rm wpcli wp acorn vendor:publish --tag="woocommerce-template-views"

# 4. Catálogo y menú de ejemplo
docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-catalog.php
docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-menu.php
docker compose run --rm -v "$PWD/scripts:/scripts" wpcli wp eval-file /scripts/seed-home.php
```

## Assets

```bash
cd racing-bike-theme
npm install
npm run build     # producción
npm run dev       # servidor de desarrollo Vite
```

## Notas de configuración

Estas son trampas conocidas, no preferencias — el sitio se rompe sin ellas:

- **`wp acorn package:discover` es obligatorio** tras instalar `generoi/sage-woocommerce`. Sin él, `vendor:publish` responde *"No publishable resources"* porque el service provider no está registrado.
- **Carrito y Checkout usan shortcodes**, no bloques: `[woocommerce_cart]` y `[woocommerce_checkout]`. WooCommerce 9+ crea esas páginas con bloques, que ignoran las plantillas del theme.
- ***Coming soon mode* debe estar desactivado** (`woocommerce_coming_soon` = `no`), o los visitantes no logueados no ven el theme.
- **`config.platform.php` está fijado a 8.4.1** en `composer.json`. El árbol real (Acorn 6 + Symfony 8) exige ese mínimo; sin fijarlo, un `composer install` en PHP 8.5 genera un `vendor/` que revienta en contenedores con PHP menor.
- Moneda configurada en **COP** con separador de miles `.` y 0 decimales.
- **Los View Composers exponen datos vía `with()`, no como métodos públicos.** Acorn envuelve los métodos públicos en `InvokableComponentVariable`; al pasar una de esas variables a un atributo de componente (`:slides="$slides"`), Blade la escapa y `htmlspecialchars()` falla con un array. `with()` entrega valores planos.

## Estructura

```
racing-bike-theme/
├── app/                    # Setup, filtros, View Composers
├── resources/
│   ├── css/app.css         # Tokens de diseño + utilidades
│   ├── js/app.js           # Drawers, carrusel, interacciones
│   └── views/
│       ├── components/     # Componentes Blade reutilizables
│       ├── sections/       # Header, footer
│       └── woocommerce/    # Plantillas de tienda (publicadas)
└── public/build/           # Assets compilados (ignorado en git)

scripts/                    # Semillas de datos para desarrollo
```
