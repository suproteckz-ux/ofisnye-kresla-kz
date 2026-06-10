<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<?php
  $pageTitle = trim(View::yieldContent('title')) ?: config('app.name');
  $pageDesc  = trim(View::yieldContent('description')) ?: '';
  $ogImg     = $ogImage ?? asset('img/og-default.jpg');
?>
<title><?php echo e($pageTitle); ?></title>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pageDesc): ?><meta name="description" content="<?php echo e($pageDesc); ?>"><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php if (! empty(trim($__env->yieldContent('canonical')))): ?><?php echo $__env->yieldContent('canonical'); ?><?php else: ?><link rel="canonical" href="<?php echo e(url()->current()); ?>"><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php if (! empty(trim($__env->yieldContent('noindex')))): ?>
<meta name="robots" content="noindex,follow">
<?php else: ?>
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<meta property="og:site_name" content="<?php echo e(config('app.name')); ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo e($pageTitle); ?>">
<meta property="og:description" content="<?php echo e($pageDesc); ?>">
<meta property="og:image" content="<?php echo e($ogImg); ?>">
<meta property="og:url" content="<?php echo e(url()->current()); ?>">
<meta property="og:locale" content="ru_RU">
<link rel="icon" href="<?php echo e(asset('favicon.ico')); ?>" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{max-width:100%;overflow-x:hidden}
img,picture{max-width:100%;height:auto;display:block}
body{font-family:'Inter',system-ui,sans-serif;color:#111;background:#fff;line-height:1.5}
a{color:inherit;text-decoration:none}
button{font-family:inherit;cursor:pointer}
[x-cloak]{display:none!important}

/* ── Container ── */
.container{max-width:1280px;margin:0 auto;width:100%;padding:0 16px}
@media(min-width:768px){.container{padding:0 24px}}
@media(min-width:1024px){.container{padding:0 32px}}

/* ── Typography ── */
.text-secondary{color:#666}
.text-sm{font-size:13px}
.text-xs{font-size:12px}

/* ── Buttons ── */
.btn-orange{display:inline-flex;align-items:center;gap:8px;padding:13px 24px;background:#ff8a00;color:#fff;font-weight:600;font-size:15px;border-radius:10px;border:none;cursor:pointer;transition:background .2s;text-decoration:none;white-space:nowrap}
.btn-orange:hover{background:#e67a00}
.btn-orange-sm{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:#ff8a00;color:#fff;font-weight:600;font-size:14px;border-radius:8px;border:none;cursor:pointer;transition:background .2s;text-decoration:none}
.btn-orange-sm:hover{background:#e67a00}
.btn-wa{display:inline-flex;align-items:center;gap:8px;padding:13px 24px;background:#22c55e;color:#fff;font-weight:600;font-size:15px;border-radius:10px;border:none;cursor:pointer;transition:background .2s;text-decoration:none;white-space:nowrap}
.btn-wa:hover{background:#16a34a}
.btn-wa-sm{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:10px;background:#22c55e;color:#fff;font-weight:600;font-size:13px;border-radius:8px;border:none;cursor:pointer;transition:background .2s;text-decoration:none}
.btn-wa-sm:hover{background:#16a34a}
.btn-outline{display:inline-flex;align-items:center;gap:8px;padding:13px 24px;background:transparent;color:#111;font-weight:600;font-size:15px;border-radius:10px;border:1.5px solid #ddd;cursor:pointer;transition:border-color .2s;text-decoration:none}
.btn-outline:hover{border-color:#ff8a00;color:#ff8a00}

/* ── Cards ── */
.card{background:#fff;border-radius:16px;overflow:hidden;border:1px solid #eee;transition:box-shadow .2s}
.card:hover{box-shadow:0 8px 32px rgba(0,0,0,.10)}

/* ── Section ── */
.section{padding:48px 0}
@media(min-width:768px){.section{padding:64px 0}}
.section-title{font-size:22px;font-weight:700;color:#111}
@media(min-width:768px){.section-title{font-size:26px}}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;gap:12px}
.see-all{font-size:14px;color:#666;white-space:nowrap;transition:color .2s}
.see-all:hover{color:#ff8a00}

/* ── Product grid ── */
.pgrid{display:grid;gap:12px;grid-template-columns:repeat(2,1fr)}
@media(min-width:1024px){.pgrid{grid-template-columns:repeat(4,1fr);gap:20px}}
.pgrid-5{display:grid;gap:12px;grid-template-columns:repeat(2,1fr)}
@media(min-width:768px){.pgrid-5{grid-template-columns:repeat(3,1fr);gap:16px}}
@media(min-width:1200px){.pgrid-5{grid-template-columns:repeat(5,1fr);gap:20px}}

/* ── Category grid ── */
.cat-grid{display:grid;gap:12px;grid-template-columns:repeat(2,1fr)}
@media(min-width:640px){.cat-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:1024px){.cat-grid{grid-template-columns:repeat(5,1fr);gap:16px}}

/* ── Divider ── */
.divider{border:none;border-top:1px solid #eee;margin:0}

/* ── WhatsApp icon SVG ── */
.wa-svg{width:18px;height:18px;fill:#fff;flex-shrink:0}

/* ── Star ── */
.stars{color:#fbbf24;font-size:14px;letter-spacing:1px}

/* ── Badge ── */
.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700}
.badge-hit{background:#ff8a00;color:#fff}
.badge-new{background:#3b82f6;color:#fff}
.badge-green{background:#dcfce7;color:#16a34a}

/* ── Product page grid ── */
.product-page-grid{display:grid;grid-template-columns:1fr;gap:24px;margin-bottom:48px}
@media(min-width:768px){.product-page-grid{grid-template-columns:1fr 1fr;gap:40px}}

/* ── Advantages strip ── */
.adv-strip{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
@media(min-width:768px){.adv-strip{grid-template-columns:repeat(4,1fr);gap:20px}}
.adv-item{display:flex;align-items:flex-start;gap:10px;padding:16px;background:#f8f8f8;border-radius:12px}
.adv-icon{width:36px;height:36px;background:#fff3e0;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.adv-title{font-weight:600;font-size:13px;color:#111;margin-bottom:2px}
.adv-desc{font-size:12px;color:#666;line-height:1.4}

/* ── Why us ── */
.why-grid{display:grid;grid-template-columns:1fr;gap:32px}
@media(min-width:768px){.why-grid{grid-template-columns:1fr 1fr;gap:48px;align-items:center}}

/* ── Reviews ── */
.reviews-grid{display:grid;gap:16px;grid-template-columns:1fr}
@media(min-width:640px){.reviews-grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1024px){.reviews-grid{grid-template-columns:repeat(3,1fr)}}

/* ── Hero ── */
.hero-wrap{display:grid;grid-template-columns:1fr;gap:24px;align-items:center}
@media(min-width:768px){.hero-wrap{grid-template-columns:1fr 1fr;gap:0}}
.hero-img-col{display:none}
@media(min-width:768px){.hero-img-col{display:flex;align-items:stretch}}

/* ── FAQ ── */
.faq-item{border-bottom:1px solid #eee}
.faq-q{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 0;cursor:pointer;font-weight:500;font-size:15px;color:#111;background:none;border:none;width:100%;text-align:left}
.faq-a{font-size:14px;color:#444;line-height:1.7;padding-bottom:18px}

/* ── Prose ── */
.prose{line-height:1.8;color:#444}
.prose p{margin-bottom:1em}
.prose h2,.prose h3{font-weight:700;color:#111;margin:1.5em 0 .5em;font-size:18px}

/* ── Pill nav ── */
.pill{display:inline-flex;align-items:center;padding:7px 14px;background:#fff;border:1px solid #eee;border-radius:99px;font-size:13px;font-weight:500;color:#444;white-space:nowrap;transition:border-color .2s,color .2s}
.pill:hover,.pill.active{border-color:#ff8a00;color:#ff8a00}

/* ── Toolbar ── */
.catalog-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.sort-select{padding:8px 14px;border:1px solid #eee;border-radius:8px;font-size:13px;background:#fff;cursor:pointer;outline:none;color:#111}

/* ── Breadcrumb ── */
.bc{display:flex;align-items:center;gap:6px;font-size:13px;color:#888;flex-wrap:wrap;padding:10px 0}
.bc a{color:#888;transition:color .2s}.bc a:hover{color:#ff8a00}
.bc-sep{color:#ccc}

/* ── Tabs ── */
.tab-btn{padding:10px 20px;font-size:14px;font-weight:500;border:none;background:none;border-bottom:2px solid transparent;cursor:pointer;color:#666;transition:color .2s,border-color .2s}
.tab-btn.active{color:#ff8a00;border-bottom-color:#ff8a00}

/* ── Char table ── */
.char-table{width:100%;border-collapse:collapse}
.char-table tr:nth-child(even){background:#f8f8f8}
.char-table td{padding:10px 14px;font-size:14px;border-bottom:1px solid #eee}
.char-table td:first-child{color:#666;width:50%}
.char-table td:last-child{font-weight:500;color:#111}

/* ── Footer ── */
.footer{background:#111;color:#aaa;padding:48px 0 24px}
.footer-grid{display:grid;gap:32px;grid-template-columns:1fr}
@media(min-width:640px){.footer-grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1024px){.footer-grid{grid-template-columns:2fr 1fr 1fr 1.5fr}}
.footer-title{color:#fff;font-weight:600;font-size:15px;margin-bottom:16px}
.footer-link{display:block;font-size:14px;color:#aaa;margin-bottom:10px;transition:color .2s}
.footer-link:hover{color:#fff}
.footer-bottom{margin-top:40px;padding-top:20px;border-top:1px solid #222;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;color:#555}

/* ── Карточки товаров: фото не обрезается ── */
.card-img-wrap { height: 200px !important; }
@media(max-width: 640px) { .card-img-wrap { height: 150px !important; } }
.card-img-wrap img { object-fit: contain !important; }

/* ── Пагинация ── */
.pagination { display: flex; align-items: center; justify-content: center; gap: 4px; flex-wrap: wrap; }

</style>
</head>
<body>
<?php echo $__env->make('components.ui.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>



<?php if (! empty(trim($__env->yieldContent('breadcrumbs')))): ?>
<div class="container"><div class="bc"><?php echo $__env->yieldContent('breadcrumbs'); ?></div></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<main><?php echo $__env->yieldContent('content'); ?></main>

<?php echo $__env->make('components.ui.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('components.ui.whatsapp-button', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('components.ui.lead-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('components.schema.local-business', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->yieldContent('schema'); ?>
<?php echo $__env->make('components.ui.analytics', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /var/www/vhosts/autohimiki.kz/xn----8sbnbmoilhzgg4a5h.kz/resources/views/layouts/app.blade.php ENDPATH**/ ?>