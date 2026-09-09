=== Skycode Maintenance Mode ===
Contributors: skycodeagency
Tags: maintenance mode, coming soon, under construction
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Modo "en construcción" / mantenimiento enterprise, genérico y reutilizable entre proyectos.

== Description ==

Bloquea el front del sitio con una plantilla propia mientras se prepara o mantiene, sin romper pagos, webhooks ni la API REST.

* Modo "Próximamente" (200 OK) o "Mantenimiento" (503 + Retry-After).
* Control de acceso por rol, capacidad, IP/CIDR y enlace secreto con token.
* Excluye siempre pagos, webhooks de pasarelas (Mercado Pago incluido), REST API, cron y login.
* 5 plantillas visuales configurables sin tocar código (logo, colores, contador, redes, CSS/HTML propio).
* Captura de suscriptores con honeypot, rate limit y webhook de integración.
* Programación automática por fecha, evaluada en cada visita (no depende del cron de WordPress).
* WP-CLI: `wp skycode-maintenance enable|disable|status|token|export|import`.
* Exportación/importación de configuración en JSON para clonar entre proyectos.

Prefijo de código `SKC_MM_` y slug `skycode-maintenance`: no requiere renombrar nada al reutilizarlo en otro sitio.

== Changelog ==

= 1.0.0 =
* Versión inicial.
