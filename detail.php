<?php
// ===============================================
// detail.php — Page détail produit Exoleton (SEO /{lang}/...)
// ===============================================
require __DIR__ . '/auth.php';

/**
 * IMPORTANT
 * - Ce fichier est appelé par router.php (ex: /fr/produit/slug -> router.php -> detail.php)
 * - Donc $lang doit venir du routeur. Fallback fr si accès direct.
 */
$lang = $lang ?? 'fr';
$base = '/' . $lang;

// slug depuis router.php ?path=produit/{slug} OU depuis querystring ?slug=
$slug = '';
if (!empty($_GET['path'])) {
  // ex: "produit/mon-slug"
  $path = trim((string)$_GET['path'], '/');
  $parts = explode('/', $path);
  if (count($parts) >= 2) $slug = $parts[1];
}
if ($slug === '' && isset($_GET['slug'])) $slug = trim((string)$_GET['slug']);

if ($slug === '') {
  http_response_code(404);
  echo '<h1>Produit introuvable</h1>';
  exit;
}

function price_html($p, $cur = 'EUR'){
  if ($p === null) return 'Sur demande';
  $p = (float)$p;
  if ($p <= 0) return 'Sur demande';
  return number_format($p, 0, ',', ' ') . ' ' . ($cur === 'EUR' ? '€' : $cur);
}

/**
 * Fix critique: normaliser les URLs médias (sinon /fr/produit/... casse les chemins relatifs)
 */
function media_url(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') return '';
  if (preg_match('#^(https?://|data:)#i', $url)) return $url;
  if (str_starts_with($url, '/')) return $url;
  return '/' . ltrim($url, '/');
}

$currentUser = current_user($pdo);

$navCategoryOptions = [
  '' => 'All categories',
  'industriels_professionnels' => 'Industrial',
  'medical' => 'Medical',
  'personnel_sport' => 'Personal / Sport',
  'collectivites' => 'Communities',
  'guides' => 'Guides & resources',
];

/** PRODUIT + PRIX + IMAGE + ATTRIBUTS */
$sqlProduct = "
SELECT
  p.id,
  pi.slug,
  pi.title AS name,
  pi.description,
  ci.name AS category,
  pv.price,
  'EUR' AS currency,
  pm.url AS hero_image,

  t.value_select   AS assistance_type,
  w.value_decimal  AS weight_kg,
  a.value_decimal  AS autonomy_h,
  ch.value_decimal AS max_user_weight_kg

FROM products p
JOIN products_i18n pi ON pi.product_id = p.id AND pi.lang = :lang
JOIN categories_i18n ci ON ci.category_id = p.category_id AND ci.lang = :lang

LEFT JOIN (
  SELECT product_id, MIN(price) AS price
  FROM product_variants
  WHERE is_active = 1
  GROUP BY product_id
) pv ON pv.product_id = p.id

LEFT JOIN (
  SELECT m1.product_id, m1.url
  FROM media m1
  JOIN (
    SELECT product_id, MIN(sort_order) AS min_sort
    FROM media
    WHERE type='image'
    GROUP BY product_id
  ) mm ON mm.product_id = m1.product_id AND mm.min_sort = m1.sort_order
  WHERE m1.type='image'
) pm ON pm.product_id = p.id

LEFT JOIN attributes at ON at.code='assistance_type'
LEFT JOIN product_attribute_values t ON t.product_id=p.id AND t.attribute_id=at.id

LEFT JOIN attributes aw ON aw.code='weight_kg'
LEFT JOIN product_attribute_values w ON w.product_id=p.id AND w.attribute_id=aw.id

LEFT JOIN attributes aa ON aa.code='autonomy_h'
LEFT JOIN product_attribute_values a ON a.product_id=p.id AND a.attribute_id=aa.id

LEFT JOIN attributes ach ON ach.code='max_user_weight_kg'
LEFT JOIN product_attribute_values ch ON ch.product_id=p.id AND ch.attribute_id=ach.id

WHERE p.is_active = 1
  AND pi.slug = :slug
LIMIT 1
";
$productStmt = $pdo->prepare($sqlProduct);
$productStmt->execute([':lang' => $lang, ':slug' => $slug]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
  http_response_code(404);
  echo '<h1>Produit introuvable</h1>';
  exit;
}

/** IMAGES (media) */
$imagesStmt = $pdo->prepare("
  SELECT url
  FROM media
  WHERE product_id = :pid AND type='image'
  ORDER BY sort_order ASC, id ASC
");
$imagesStmt->execute([':pid' => $product['id']]);
$galleryImages = $imagesStmt->fetchAll(PDO::FETCH_COLUMN);
$galleryImages = array_values(array_filter(array_map('media_url', $galleryImages)));

if (empty($galleryImages)) {
  $fallback = media_url($product['hero_image'] ?: '/assets/img/hero-exosquelette.png');
  $galleryImages = [$fallback];
}

/** SPECS (table techniques) */
$specs = [];
if (!empty($product['assistance_type']))    $specs[] = ['label' => 'Type', 'value' => $product['assistance_type']];
if (!empty($product['weight_kg']))          $specs[] = ['label' => 'Poids (kg)', 'value' => (string)$product['weight_kg']];
if (!empty($product['autonomy_h']))         $specs[] = ['label' => 'Autonomie (h)', 'value' => (string)$product['autonomy_h']];
if (!empty($product['max_user_weight_kg'])) $specs[] = ['label' => 'Charge (kg)', 'value' => (string)$product['max_user_weight_kg']];

/** Ces tables n’existent pas forcément : on laisse vide */
$downloads = [];
$use_cases = [];

/** ALTERNATIVES */
$alternativesStmt = $pdo->prepare("
SELECT
  pi2.title AS alt_name,
  pi2.slug  AS alt_slug,
  ci2.name  AS tag,
  SUBSTRING(REPLACE(REPLACE(pi2.description, '\\r',' '), '\\n',' '), 1, 120) AS summary,
  pv2.price AS price,
  pm2.url   AS image
FROM products p2
JOIN products_i18n pi2 ON pi2.product_id = p2.id AND pi2.lang = :lang
JOIN categories_i18n ci2 ON ci2.category_id = p2.category_id AND ci2.lang = :lang
LEFT JOIN (
  SELECT product_id, MIN(price) AS price
  FROM product_variants
  WHERE is_active = 1
  GROUP BY product_id
) pv2 ON pv2.product_id = p2.id
LEFT JOIN (
  SELECT m1.product_id, m1.url
  FROM media m1
  JOIN (
    SELECT product_id, MIN(sort_order) AS min_sort
    FROM media
    WHERE type='image'
    GROUP BY product_id
  ) mm ON mm.product_id = m1.product_id AND mm.min_sort = m1.sort_order
  WHERE m1.type='image'
) pm2 ON pm2.product_id = p2.id
WHERE p2.is_active = 1
  AND p2.category_id = (SELECT category_id FROM products WHERE id = :pid)
  AND p2.id <> :pid
ORDER BY p2.id DESC
LIMIT 4
");
$alternativesStmt->execute([':lang' => $lang, ':pid' => $product['id']]);
$alternatives = $alternativesStmt->fetchAll(PDO::FETCH_ASSOC);

/** Résumé affiché */
$summaryText = trim(strip_tags((string)($product['description'] ?? '')));
$summaryText = mb_substr(preg_replace("/\s+/", " ", $summaryText), 0, 260);

// Canonical SEO
$canonical = 'https://exoleton.com' . $base . '/produit/' . urlencode($product['slug']);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?> – Exoleton</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars(mb_substr($summaryText, 0, 160)) ?>">

  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

  <link rel="icon" type="image/x-icon" href="/favicon.ico?v=1">
  <link rel="shortcut icon" href="/favicon.ico?v=1">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/ico.png">
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/ico.png">
  <link rel="apple-touch-icon" href="/assets/img/ico.png">

  <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?> – <?= htmlspecialchars($product['category']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars(mb_substr($summaryText, 0, 160)) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(media_url($galleryImages[0])) ?>">
  <meta property="og:type" content="product">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">

  <style>
    .thumbs img{width:100%;border-radius:.5rem;cursor:pointer;border:2px solid transparent}
    .thumbs img.active{border-color:#0d6efd}
    .product-sticky{position:fixed;left:0;right:0;bottom:0;z-index:1030;background:#111;color:#fff;padding:.75rem 1rem;border-top:1px solid rgba(255,255,255,.1);display:none}
    .product-sticky .price{font-weight:700}
    @media (max-width:991.98px){.product-sticky{display:block}}
    .specs th{width:42%}
  </style>
</head>
<body>

  <!-- HEADER / NAV -->
  <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>/">
        <img src="/assets/img/logo.png" alt="Exoleton" width="272" height="1000" class="me-2">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <nav id="mainNav" class="collapse navbar-collapse">
        <div class="d-lg-flex align-items-lg-center w-100 gap-3">
          <ul class="navbar-nav align-items-lg-center mb-2 mb-lg-0 me-lg-3">
            <li class="nav-item"><a class="nav-link fw-semibold" href="<?= $base ?>/" data-i18n="nav.home">Accueil</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= $base ?>/#guides" data-i18n="nav.guides">Guides</a></li>
          </ul>

          <form class="nav-search flex-grow-1 my-3 my-lg-0" method="get" action="<?= $base ?>/recherche" role="search">
            <div class="nav-search-bar" role="group" aria-label="Search">
              <div class="nav-search-select-wrap">
                <label class="visually-hidden" for="navSearchCategory">Category</label>
                <select id="navSearchCategory" name="cat" class="form-select nav-search-select">
                  <?php foreach ($navCategoryOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
                <span class="nav-search-caret" aria-hidden="true">▾</span>
              </div>
              <div class="nav-search-input">
                <label class="visually-hidden" for="navSearchQuery">Search</label>
                <input id="navSearchQuery" name="q" type="search" class="form-control" placeholder="Industrial exoskeleton, walking aid…" data-i18n-placeholder="search.placeholder">
              </div>
              <button class="nav-search-btn" type="submit" aria-label="Search">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
                  <path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.71.71l.27.28v.79l4.25 4.25a1 1 0 0 0 1.42-1.42L15.5 14Zm-6 0a5 5 0 1 1 0-10a5 5 0 0 1 0 10Z"/>
                </svg>
                <span class="visually-hidden" data-i18n="search.cta">Search</span>
              </button>
            </div>
          </form>

          <ul class="navbar-nav ms-lg-auto mb-2 mb-lg-0 align-items-lg-center">
            <li class="nav-item ms-lg-3">
              <a class="btn btn-primary" href="#demo" data-i18n="nav.demo">Demander une démo</a>
            </li>
            <li class="nav-item ms-lg-3">
              <label class="visually-hidden" for="languageSwitcherDetail" data-i18n="lang.label">Langue</label>
              <select id="languageSwitcherDetail" class="form-select form-select-sm" data-language-switcher></select>
            </li>
            <?php if ($currentUser): ?>
              <li class="nav-item dropdown ms-lg-3">
                <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Bonjour <?= htmlspecialchars($currentUser['name']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                  <li><a class="dropdown-item" href="<?= $base ?>/account">Mon compte</a></li>
                  <?php if (($currentUser['role'] ?? 'customer') === 'admin'): ?>
                    <li><a class="dropdown-item" href="<?= $base ?>/admin">Administration</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="<?= $base ?>/logout">Se déconnecter</a></li>
                </ul>
              </li>
            <?php else: ?>
              <li class="nav-item ms-lg-3">
                <a class="btn btn-outline-primary" href="<?= $base ?>/login">Connexion</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </nav>
    </div>
  </header>

  <main class="mt-5 pt-4">

    <nav class="container" aria-label="breadcrumb">
      <ol class="breadcrumb small mb-2">
        <li class="breadcrumb-item"><a href="<?= $base ?>/" data-i18n="nav.home">Accueil</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
      </ol>
    </nav>

    <section class="container mb-5">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="ratio ratio-4x3 mb-3">
            <img id="mainImage" src="<?= htmlspecialchars(media_url($galleryImages[0])) ?>" alt="Vue principale" class="w-100 h-100 rounded-3" style="object-fit:cover">
          </div>
          <div class="row g-2 thumbs" role="listbox" aria-label="Galerie produit">
            <?php foreach($galleryImages as $idx => $g): $gAbs = media_url($g); ?>
              <div class="col-4">
                <img src="<?= htmlspecialchars($gAbs) ?>"
                     alt="Miniature <?= (int)($idx+1) ?>"
                     class="thumb rounded-3 <?= $idx===0?'active':'' ?>"
                     data-full="<?= htmlspecialchars($gAbs) ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <span class="badge bg-secondary mb-2"><?= htmlspecialchars($product['category'] ?: '—') ?></span>
              <h1 class="h3 mb-2"><?= htmlspecialchars($product['name']) ?></h1>

              <h2 class="h5 mb-2" data-i18n="product.about">À propos</h2>
              <p class="text-muted"><?= htmlspecialchars($summaryText ?: '—') ?></p>

              <div class="d-flex align-items-center justify-content-between my-3">
                <div>
                  <div class="small text-muted" data-i18n="product.priceLabel">Tarif indicatif</div>
                  <div class="h4 mb-0"><?= price_html($product['price'], $product['currency']) ?></div>
                </div>
                <div class="d-flex gap-2">
                  <a href="#demo" class="btn btn-primary" data-i18n="product.demoButton">Demande de démo</a>
                  <a href="#devis" class="btn btn-outline-primary" data-i18n="product.quoteButton">Devis</a>
                </div>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <div class="small text-muted">Type</div>
                    <div class="fw-semibold"><?= htmlspecialchars($product['assistance_type'] ?: '—') ?></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <div class="small text-muted">Poids</div>
                    <div class="fw-semibold"><?= htmlspecialchars($product['weight_kg'] ?: '—') ?></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <div class="small text-muted">Autonomie</div>
                    <div class="fw-semibold"><?= htmlspecialchars($product['autonomy_h'] ?: '—') ?></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <div class="small text-muted">Charge</div>
                    <div class="fw-semibold"><?= htmlspecialchars($product['max_user_weight_kg'] ?: '—') ?></div>
                  </div>
                </div>
              </div>

              <hr>
              <ul class="list-unstyled mb-0">
                <li class="mb-2" data-i18n="product.bullet1">• Essai sur site possible (selon zone)</li>
                <li class="mb-2" data-i18n="product.bullet2">• Formation opérateur incluse</li>
                <li class="mb-2" data-i18n="product.bullet3">• Financements et aides possibles</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php if (!empty($use_cases)): ?>
    <section class="py-5 bg-light border-top">
      <div class="container">
        <h2 class="h4 mb-4" data-i18n="product.useCases">Cas d’usage</h2>
        <div class="row g-4">
          <?php foreach($use_cases as $uc): ?>
            <div class="col-md-4">
              <div class="card h-100 shadow-sm">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <h3 class="h6 mb-1"><?= htmlspecialchars($uc['title']) ?></h3>
                    <span class="badge bg-primary"><?= htmlspecialchars($uc['kpi']) ?></span>
                  </div>
                  <p class="text-muted mb-0"><?= htmlspecialchars($uc['description']) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section id="specs" class="py-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-lg-7">
            <h2 class="h4 mb-3" data-i18n="product.specs">Caractéristiques techniques</h2>
            <div class="table-responsive rounded-3 shadow-sm bg-white">
              <table class="table align-middle mb-0 specs">
                <tbody>
                  <?php if (!empty($specs)): ?>
                    <?php foreach($specs as $spec): ?>
                      <tr>
                        <th class="bg-light"><?= htmlspecialchars($spec['label']) ?></th>
                        <td><?= htmlspecialchars($spec['value']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <th class="bg-light">—</th>
                      <td class="text-muted">Données à compléter</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <small class="text-muted d-block mt-2" data-i18n="product.specsNote">*Données indicatives, variables selon configuration.</small>
          </div>

          <div class="col-lg-5">
            <h2 class="h4 mb-3" data-i18n="product.downloads">Téléchargements</h2>
            <?php if (!empty($downloads)): ?>
              <ul class="list-group">
                <?php foreach($downloads as $d): ?>
                  <li class="list-group-item d-flex align-items-center justify-content-between">
                    <span><?= htmlspecialchars($d['label']) ?></span>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($d['href']) ?>" target="_blank" rel="noopener" data-i18n="product.downloadCta">PDF</a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="alert alert-secondary small mb-0">Aucun document disponible pour l’instant.</div>
            <?php endif; ?>

            <div class="alert alert-info mt-3 mb-0 small" data-i18n-html="true" data-i18n="product.downloadHelp">
              Besoin d’un document spécifique (fiche technique, essais, certificats) ? <a href="#contact" class="alert-link">Contacte-nous</a>.
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="demo" class="py-5 bg-primary text-white">
      <div class="container">
        <div class="row g-4">
          <div class="col-lg-6">
            <h2 class="h4 mb-3" data-i18n="product.demoTitle">Demander une démo</h2>
            <form class="row g-3" action="#" method="post" onsubmit="return false;">
              <div class="col-md-6">
                <label class="form-label" data-i18n="product.form.name">Nom</label>
                <input type="text" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="product.form.email">Email</label>
                <input type="email" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="product.form.sector">Secteur</label>
                <select class="form-select" required>
                  <option value="" data-i18n="product.form.select">Sélectionner</option>
                  <option data-i18n="product.form.logistics">Logistique</option>
                  <option data-i18n="product.form.industry">Industrie</option>
                  <option data-i18n="product.form.construction">BTP</option>
                  <option data-i18n="product.form.health">Santé</option>
                  <option data-i18n="product.form.other">Autre</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="product.form.zip">Code postal</label>
                <input type="text" class="form-control" pattern="[0-9]{5}" placeholder="75001" required>
              </div>
              <div class="col-12">
                <label class="form-label" data-i18n="product.form.need">Votre besoin</label>
                <textarea class="form-control" rows="3" placeholder="Contexte, postes concernés, objectifs…" data-i18n-placeholder="product.form.needPlaceholder"></textarea>
              </div>
              <div class="col-12">
                <button class="btn btn-light btn-lg" data-i18n="product.form.send">Envoyer la demande</button>
              </div>
            </form>
          </div>

          <div id="devis" class="col-lg-6">
            <h2 class="h4 mb-3" data-i18n="product.quoteTitle">Devis rapide</h2>
            <form class="row g-3" action="#" method="post" onsubmit="return false;">
              <div class="col-md-6">
                <label class="form-label" data-i18n="product.quote.quantity">Quantité</label>
                <input type="number" class="form-control" id="qte" value="1" min="1">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="product.quote.options">Options</label>
                <select class="form-select" id="opt">
                  <option value="0" data-i18n="product.quote.optionNone">Aucune</option>
                  <option value="450" data-i18n="product.quote.optionBattery">Batterie supplémentaire (+450€)</option>
                  <option value="190" data-i18n="product.quote.optionHarness">Harnais XL (+190€)</option>
                </select>
              </div>
              <div class="col-12">
                <div class="p-3 bg-white text-dark rounded-3 d-flex align-items-center justify-content-between">
                  <div>
                    <div class="small text-muted" data-i18n="product.quote.estimate">Estimation</div>
                    <div class="h5 mb-0" id="estimation"><?= price_html($product['price'], $product['currency']) ?></div>
                  </div>
                  <button class="btn btn-primary" data-i18n="product.quote.receive">Recevoir le devis</button>
                </div>
              </div>
            </form>

            <div class="mt-4 p-3 bg-white text-dark rounded-3">
              <h3 class="h6" data-i18n="product.roi.title">ROI rapide (indicatif)</h3>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small" data-i18n="product.roi.operators">Opérateurs</label>
                  <input type="number" class="form-control form-control-sm" id="op" value="5" min="1">
                </div>
                <div class="col-6">
                  <label class="form-label small" data-i18n="product.roi.at">AT/an évités</label>
                  <input type="number" class="form-control form-control-sm" id="at" value="1" min="0">
                </div>
                <div class="col-12">
                  <label class="form-label small" data-i18n="product.roi.lifts">Levages/jour</label>
                  <input type="number" class="form-control form-control-sm" id="lev" value="200" min="0">
                </div>
              </div>
              <div class="d-flex align-items-center justify-content-between mt-3">
                <small class="text-muted" data-i18n="product.roi.hint">Hypothèses internes simples</small>
                <div class="fw-bold" id="roiTxt" data-i18n="product.roi.result">Payback estimé : —</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <?php if (!empty($alternatives)): ?>
    <section class="py-5 bg-light border-top">
      <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <h2 class="h4 mb-0" data-i18n="product.alternatives">Alternatives proches</h2>
          <a href="<?= $base ?>/#comparateur" class="link-primary" data-i18n="product.compare">Comparer →</a>
        </div>
        <div class="row g-4">
          <?php foreach($alternatives as $alt): ?>
            <div class="col-md-6">
              <article class="card h-100">
                <div class="row g-0 h-100">
                  <div class="col-4">
                    <img src="<?= htmlspecialchars(media_url($alt['image'] ?: '/assets/img/hero-exosquelette.png')) ?>"
                         alt="<?= htmlspecialchars($alt['alt_name']) ?>"
                         class="w-100 h-100" style="object-fit:cover">
                  </div>
                  <div class="col-8">
                    <div class="card-body">
                      <span class="badge bg-secondary mb-2"><?= htmlspecialchars($alt['tag'] ?: '—') ?></span>
                      <h3 class="h6 mb-1"><?= htmlspecialchars($alt['alt_name']) ?></h3>
                      <p class="small text-muted mb-2"><?= htmlspecialchars($alt['summary']) ?></p>
                      <div class="d-flex align-items-center justify-content-between">
                        <strong class="price"><?= price_html($alt['price'], 'EUR') ?></strong>
                        <a href="<?= $base ?>/produit/<?= urlencode($alt['alt_slug']) ?>" class="btn btn-outline-primary btn-sm" data-i18n="product.view">Voir</a>
                      </div>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

  </main>

  <div class="product-sticky">
    <div class="container d-flex align-items-center justify-content-between">
      <div class="me-2">
        <div class="small text-white-50"><?= htmlspecialchars($product['name']) ?></div>
        <div class="price"><?= price_html($product['price'], $product['currency']) ?></div>
      </div>
      <div class="d-flex gap-2">
        <a href="#demo" class="btn btn-light btn-sm" data-i18n="product.stickyDemo">Démo</a>
        <a href="#devis" class="btn btn-outline-light btn-sm" data-i18n="product.stickyQuote">Devis</a>
      </div>
    </div>
  </div>

  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"Product",
    "name":<?= json_encode($product['name'], JSON_UNESCAPED_UNICODE) ?>,
    "image":<?= json_encode($galleryImages, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>,
    "description":<?= json_encode(mb_substr($summaryText,0,300), JSON_UNESCAPED_UNICODE) ?>,
    "category":<?= json_encode($product['category'], JSON_UNESCAPED_UNICODE) ?>,
    "brand":{"@type":"Brand","name":"Exoleton"},
    "offers":{
      "@type":"Offer",
      "priceCurrency":"EUR",
      "price":"<?= ($product['price'] && (float)$product['price'] > 0) ? (float)$product['price'] : 0 ?>",
      "availability":"https://schema.org/InStock",
      "url":<?= json_encode($canonical, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
    }
  }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/i18n.js"></script>
  <script>
    // Galerie
    document.querySelectorAll('.thumbs .thumb').forEach(function(el){
      el.addEventListener('click', function(){
        document.getElementById('mainImage').src = this.dataset.full;
        document.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Estimation devis
    const basePrice = <?= (int)($product['price'] ?? 0) ?>;
    function updateEstimate(){
      const q = parseInt(document.getElementById('qte').value||'1',10);
      const opt = parseInt(document.getElementById('opt').value||'0',10);
      const total = Math.max(0, basePrice)*q + opt*q;
      const fmt = new Intl.NumberFormat('fr-FR').format(total);
      document.getElementById('estimation').textContent = (basePrice>0) ? (fmt + ' €') : 'Sur demande';
    }
    ['qte','opt'].forEach(id=>document.getElementById(id)?.addEventListener('input', updateEstimate));
    updateEstimate();

    // Mini ROI
    function updateROI(){
      const op = parseInt(document.getElementById('op').value||'0',10);
      const at = parseInt(document.getElementById('at').value||'0',10);
      const lev = parseInt(document.getElementById('lev').value||'0',10);
      const gainAT = at * 3000;
      const gainProd = op * (lev/100) * 0.005 * (<?= (int)($product['price'] ?? 4000) ?>);
      const cout = Math.max(0, basePrice)*Math.max(1, parseInt(document.getElementById('qte').value||'1',10));
      const payback = (gainAT + gainProd) > 0 ? (cout / (gainAT + gainProd)) : Infinity;
      document.getElementById('roiTxt').textContent = isFinite(payback) ? ('Payback estimé : ' + payback.toFixed(1) + ' an(s)') : 'Payback estimé : —';
    }
    ['op','at','lev','qte','opt'].forEach(id=>document.getElementById(id)?.addEventListener('input', updateROI));
    updateROI();
  </script>
</body>
</html>
