<?php

/**
 * El SEO Analyzer de Rank Math marca "Some Opengraph meta tags are
 * missing": no hay imagen og:image de respaldo configurada
 * (open_graph_image / open_graph_image_id en rank-math-options-titles),
 * así que cualquier página o producto sin imagen destacada propia sale
 * sin og:image al compartirse.
 *
 * Usa el logo blanco de la marca (adjunto 136, 1280x1280,
 * wp-content/uploads/2026/08/logo-blanco.png) como imagen de respaldo —
 * ya es el logo principal del sitio, no se sube nada nuevo.
 *
 * Uso: wp --skip-themes eval-file scripts/seo-fix-og-default-image.php apply
 * Sin "apply" solo imprime qué haría (dry run).
 */

if (! defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once __DIR__ . '/../wp-load.php';
}

$apply = in_array('apply', $args ?? [], true);

echo $apply ? "MODO: aplicando cambios\n\n" : "MODO: dry-run (nada se escribe; agrega el argumento \"apply\" para ejecutar)\n\n";

const RB_LOGO_ATTACHMENT_ID = 136;

$logoUrl = wp_get_attachment_image_url(RB_LOGO_ATTACHMENT_ID, 'full');

if (! $logoUrl) {
    echo "No se encontró el adjunto {$logoUrl} (ID " . RB_LOGO_ATTACHMENT_ID . "), se aborta.\n";
    exit(1);
}

$titlesOptions = get_option('rank-math-options-titles');

if (! is_array($titlesOptions)) {
    echo "No se pudo leer la opción rank-math-options-titles, se aborta.\n";
    exit(1);
}

$actual = [
    'open_graph_image' => $titlesOptions['open_graph_image'] ?? '(sin definir)',
    'open_graph_image_id' => $titlesOptions['open_graph_image_id'] ?? '(sin definir)',
];

printf("Actual: open_graph_image=%s | open_graph_image_id=%s\n", $actual['open_graph_image'], $actual['open_graph_image_id']);
printf("Nuevo:  open_graph_image=%s | open_graph_image_id=%d\n", $logoUrl, RB_LOGO_ATTACHMENT_ID);

if ($apply) {
    $titlesOptions['open_graph_image'] = $logoUrl;
    $titlesOptions['open_graph_image_id'] = RB_LOGO_ATTACHMENT_ID;
    update_option('rank-math-options-titles', $titlesOptions);
    echo "\nGuardado.\n";
}

echo ($apply ? 'Aplicado.' : 'Se aplicaría.') . "\n";
