<?php $__env->startSection('title',$product->seoTitle()); ?>
<?php $__env->startSection('description',$product->seoDescription()); ?>
<?php $__env->startSection('canonical'); ?><link rel="canonical" href="<?php echo e($product->url); ?>"><?php $__env->stopSection(); ?>

<?php $__env->startSection('schema'); ?>
<?php
$schemaProd=[
    '@context'=>'https://schema.org','@type'=>'Product',
    'name'=>$product->name,
    'description'=>$product->short_description?:Str::limit(strip_tags($product->description??''),200),
    'sku'=>$product->sku,
    'offers'=>[
        '@type'=>'Offer','priceCurrency'=>'KZT',
        'price'=>(float)$product->price,
        'availability'=>$product->in_stock?'https://schema.org/InStock':'https://schema.org/OutOfStock',
        'seller'=>['@type'=>'Organization','name'=>config('app.name')],
    ],
];
if($product->brand)$schemaProd['brand']=['@type'=>'Brand','name'=>$product->brand->name];
if($product->main_image)$schemaProd['image']=[asset('storage/'.$product->main_image)];
$bcItems=[['@type'=>'ListItem','position'=>1,'name'=>'Главная','item'=>url('/')]];
foreach($breadcrumbs as $i=>$bc){if(!empty($bc['url'])){$bcItems[]=['@type'=>'ListItem','position'=>$i+2,'name'=>$bc['name'],'item'=>$bc['url']];}}
$schemaBC=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$bcItems];
?>
<script type="application/ld+json"><?php echo json_encode($schemaProd,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($schemaBC,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumbs'); ?>
<a href="<?php echo e(route('home')); ?>">Главная</a>
<span class="bc-sep">/</span>
<a href="<?php echo e(route('catalog')); ?>">Каталог</a>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->category): ?>
<span class="bc-sep">/</span>
<a href="<?php echo e($product->category->url); ?>"><?php echo e($product->category->name); ?></a>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<span class="bc-sep">/</span><span><?php echo e(Str::limit($product->name,40)); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
$productHasDiscount = !empty($product->old_price) && (float)$product->old_price > (float)$product->price;
try { if (method_exists($product,'hasDiscount')) { $productHasDiscount = (bool)$product->hasDiscount(); } } catch(\Throwable $e) {}
?>
<style>
.prod-img-box{
    background:#f8f8f8;border-radius:16px;overflow:hidden;
    border:1px solid #eee;position:relative;margin-bottom:12px;
    max-height:340px;aspect-ratio:1;cursor:zoom-in;
}
/* Кнопка-обёртка галереи — поверх всего, без pointer-events конфликтов */
.prod-img-btn{
    display:block;width:100%;height:100%;
    background:none;border:none;padding:0;cursor:zoom-in;
    position:absolute;inset:0;z-index:2;
}
@media(min-width:768px){.prod-img-box{max-height:none}}

/* HTML-описание товара */
.product-content{max-width:900px;color:#444;line-height:1.8;font-size:15px}
.product-content p{margin:0 0 16px}
.product-content h2{font-size:22px;font-weight:700;color:#111;margin:28px 0 12px}
.product-content h3{font-size:18px;font-weight:700;color:#111;margin:22px 0 10px}
.product-content ul,.product-content ol{margin:14px 0 18px;padding-left:24px}
.product-content li{margin:0 0 8px}
.product-content strong{font-weight:700;color:#111}
.product-content a{color:#ff8a00;text-decoration:underline}

/* Характеристики — красивый список вместо простой таблицы */
.attrs-list{display:flex;flex-direction:column;gap:0}
.attrs-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    padding:11px 16px;
    border-bottom:1px solid #f0f0f0;
    align-items:start;
    transition:background .15s;
}
.attrs-row:nth-child(even){background:#fafafa}
.attrs-row:last-child{border-bottom:none}
.attrs-row:hover{background:#fff8f0}
.attrs-key{font-size:13px;color:#666;line-height:1.4}
.attrs-val{font-size:13px;color:#111;font-weight:500;line-height:1.4;text-align:right}
@media(max-width:480px){
    .attrs-row{grid-template-columns:1fr;gap:3px;padding:10px 14px}
    .attrs-val{text-align:left;color:#333}
}

/* ── Product gallery clickable fix ── */
.prod-main-photo-btn{position:absolute;inset:0;z-index:2;width:100%;height:100%;border:0;background:transparent;padding:0;cursor:zoom-in;display:block}
.prod-main-photo-btn img{pointer-events:none}
.prod-photo-count{position:absolute;top:10px;right:10px;background:rgba(0,0,0,.5);color:#fff;font-size:12px;padding:3px 8px;border-radius:99px;pointer-events:none;z-index:3;line-height:1.35;width:auto;max-width:max-content;white-space:nowrap}
.prod-thumbs{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.prod-thumb{width:64px;height:64px;border-radius:10px;overflow:hidden;background:#f8f8f8;border:2px solid transparent;cursor:pointer;padding:4px;transition:border-color .2s,box-shadow .2s;flex-shrink:0}
.prod-thumb.is-active{border-color:#ff8a00;box-shadow:0 0 0 2px #ff8a0033}
.prod-thumb img{width:100%;height:100%;object-fit:contain;pointer-events:none}
.prod-lightbox[hidden]{display:none!important}
.prod-lightbox{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);display:flex;align-items:center;justify-content:center;padding:16px}
.prod-lightbox img{max-width:min(90vw,1200px);max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 24px 64px rgba(0,0,0,.5)}
.prod-lightbox-close{position:absolute;top:16px;right:16px;width:42px;height:42px;background:rgba(255,255,255,.15);border:0;border-radius:50%;color:#fff;font-size:30px;line-height:1;cursor:pointer;z-index:10000}
.prod-lightbox-close:hover{background:rgba(255,255,255,.3)}

</style>
<?php $wa=\App\Services\CacheService::setting('whatsapp','');
$waMsg=urlencode('Хочу заказать: '.$product->name.' — '.$product->url); ?>

<div class="container" style="padding-top:24px;padding-bottom:48px">
  <div class="product-page-grid">

    
    <?php
    $allImages = [];
    if ($product->main_image) {
        $allImages[] = ['src' => asset('storage/'.$product->main_image), 'alt' => $product->main_image_alt ?: $product->name];
    }
    if (method_exists($product, 'images')) {
        foreach ($product->images as $img) {
            if (!empty($img->path)) {
                $allImages[] = ['src' => asset('storage/'.$img->path), 'alt' => $img->alt ?: $product->name];
            }
        }
    }
    $firstSrc = $allImages[0]['src'] ?? '';
    ?>

    <div class="product-gallery" data-product-gallery>
      
      <div class="prod-img-box">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($firstSrc): ?>
        <button type="button" class="prod-main-photo-btn" data-open-lightbox aria-label="Открыть фото">
          <img data-main-image src="<?php echo e($firstSrc); ?>"
               alt="<?php echo e($product->name); ?>"
               style="width:100%;height:100%;object-fit:contain;padding:24px;display:block"
               loading="eager" fetchpriority="high">
        </button>
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
          <svg width="80" height="80" fill="#ddd" viewBox="0 0 100 100"><path d="M50 15C35 15 25 25 25 40v20c0 5 3 9 7 10l-2 10h10l2-10h16l2 10h10l-2-10c4-1 7-5 7-10V40c0-15-10-25-25-25z" opacity=".3"/></svg>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($allImages) > 1): ?>
        <div class="prod-photo-count"><?php echo e(count($allImages)); ?> фото</div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div style="position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:6px;z-index:3;pointer-events:none">
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_new): ?><span class="badge badge-new">Новинка</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_hit): ?><span class="badge badge-hit">Хит продаж</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
      </div>

      
      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($allImages) > 1): ?>
      <div class="prod-thumbs">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $allImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button"
                class="prod-thumb <?php echo e($i === 0 ? 'is-active' : ''); ?>"
                data-thumb
                data-src="<?php echo e($img['src']); ?>"
                data-alt="<?php echo e($img['alt']); ?>"
                aria-label="Показать фото <?php echo e($i + 1); ?>">
          <img src="<?php echo e($img['src']); ?>" alt="<?php echo e($img['alt']); ?>" loading="lazy">
        </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>
      <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

      
      <div class="prod-lightbox" data-lightbox hidden>
        <button type="button" class="prod-lightbox-close" data-close-lightbox aria-label="Закрыть">×</button>
        <img data-lightbox-image src="<?php echo e($firstSrc); ?>" alt="<?php echo e($product->name); ?>">
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-product-gallery]').forEach(function (gallery) {
            var mainImage = gallery.querySelector('[data-main-image]');
            var mainButton = gallery.querySelector('[data-open-lightbox]');
            var lightbox = gallery.querySelector('[data-lightbox]');
            var lightboxImage = gallery.querySelector('[data-lightbox-image]');
            var closeButton = gallery.querySelector('[data-close-lightbox]');
            var activeSrc = mainImage ? mainImage.getAttribute('src') : '';

            function setActive(src, alt) {
                if (!src || !mainImage) return;
                activeSrc = src;
                mainImage.setAttribute('src', src);
                if (alt) mainImage.setAttribute('alt', alt);

                gallery.querySelectorAll('[data-thumb]').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.getAttribute('data-src') === src);
                });

                if (lightbox && !lightbox.hasAttribute('hidden') && lightboxImage) {
                    lightboxImage.setAttribute('src', src);
                    if (alt) lightboxImage.setAttribute('alt', alt);
                }
            }

            function openLightbox() {
                if (!activeSrc || !lightbox || !lightboxImage) return;
                lightboxImage.setAttribute('src', activeSrc);
                lightbox.removeAttribute('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                if (!lightbox) return;
                lightbox.setAttribute('hidden', 'hidden');
                document.body.style.overflow = '';
            }

            if (mainButton) {
                mainButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    openLightbox();
                });
            }

            gallery.querySelectorAll('[data-thumb]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    setActive(btn.getAttribute('data-src'), btn.getAttribute('data-alt'));
                });
            });

            if (closeButton) {
                closeButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeLightbox();
                });
            }

            if (lightbox) {
                lightbox.addEventListener('click', function (e) {
                    if (e.target === lightbox) closeLightbox();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeLightbox();
            });
        });
    });
    </script>

    
    <div>
      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->brand): ?>
      <a href="<?php echo e(route('brand.show',$product->brand->slug)); ?>"
         style="font-size:13px;font-weight:600;color:#ff8a00;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px;display:inline-block">
          <?php echo e($product->brand->name); ?>

      </a>
      <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

      <h1 style="font-size:clamp(1.3rem,3vw,1.7rem);font-weight:700;color:#111;line-height:1.3;margin-bottom:16px">
          <?php echo e($product->name); ?>

      </h1>

      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_hit): ?>
      <div style="margin-bottom:12px"><span class="badge badge-hit" style="font-size:12px;padding:3px 10px">Хит продаж</span></div>
      <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

      <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:12px">
        <span style="font-size:2rem;font-weight:800;color:#111"><?php echo e(number_format($product->price,0,'.',' ')); ?> ₸</span>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($productHasDiscount): ?>
        <span style="font-size:16px;color:#aaa;text-decoration:line-through"><?php echo e(number_format($product->old_price,0,'.',' ')); ?> ₸</span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>

      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->in_stock): ?>
      <div style="display:flex;align-items:center;gap:6px;font-size:14px;color:#16a34a;margin-bottom:8px">
        <div style="width:8px;height:8px;background:#22c55e;border-radius:50%"></div> В наличии
      </div>
      <?php else: ?>
      <div style="font-size:14px;color:#aaa;margin-bottom:8px">Нет в наличии</div>
      <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->sku): ?>
      <div style="font-size:13px;color:#999;margin-bottom:20px">Артикул: <?php echo e($product->sku); ?></div>
      <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($wa): ?>
        <a href="https://wa.me/<?php echo e($wa); ?>?text=<?php echo e($waMsg); ?>" target="_blank" rel="noopener"
           class="btn-wa" style="justify-content:center;font-size:16px;padding:15px">
          <svg class="wa-svg" style="width:20px;height:20px" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
          Купить в WhatsApp
        </a>
        <a href="https://wa.me/<?php echo e($wa); ?>" target="_blank" rel="noopener"
           style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;
                  border:1.5px solid #eee;border-radius:10px;font-size:14px;font-weight:500;
                  color:#333;transition:border-color .2s"
           onmouseover="this.style.borderColor='#ff8a00'" onmouseout="this.style.borderColor='#eee'">
          Быстрая консультация
          <svg width="15" height="15" fill="#22c55e" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
        </a>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [['🚚','Доставка по Алматы','1–2 дня'],['🏅','Гарантия','от 12 месяцев'],['↩️','Возврат','14 дней'],['💳','Оплата','Kaspi, карта, нал']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon,$t,$d]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;align-items:center;gap:8px;padding:10px;background:#f8f8f8;border-radius:10px">
          <span style="font-size:18px"><?php echo e($icon); ?></span>
          <div>
            <div style="font-size:11px;font-weight:600;color:#111"><?php echo e($t); ?></div>
            <div style="font-size:11px;color:#999"><?php echo e($d); ?></div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>
    </div>
  </div>

  
  <?php $attrs = $product->attributes_array ?? []; ?>

  <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->description): ?>
  <div style="margin-bottom:36px">
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px;
               padding-bottom:10px;border-bottom:2px solid #ff8a00;display:inline-block">
        Описание
    </h2>
    <div class="prose product-content">
      <?php echo $product->description; ?>

    </div>
  </div>
  <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

  <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($attrs)): ?>
  <div style="margin-bottom:36px">
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px;
               padding-bottom:10px;border-bottom:2px solid #ff8a00;display:inline-block">
        Характеристики
    </h2>
    <div style="border:1px solid #eee;border-radius:14px;overflow:hidden">
      <div class="attrs-list">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $attrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="attrs-row">
          <span class="attrs-key"><?php echo e($key); ?></span>
          <span class="attrs-val"><?php echo e($val); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

  
  <?php $productFaq = $product->faq_array ?? []; ?>
  <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($productFaq)): ?>
  <div style="margin-bottom:36px" x-data="{open:null}">
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:16px">Вопросы и ответы</h2>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $productFaq; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="faq-item">
      <button class="faq-q" @click="open===`p<?php echo e($i); ?>`?open=null:open=`p<?php echo e($i); ?>`">
        <?php echo e($faq['question'] ?? ''); ?>

        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
             :style="open===`p<?php echo e($i); ?>`?'transform:rotate(180deg)':''"
             style="flex-shrink:0;transition:transform .2s">
          <path d="M6 9l6 6 6-6"/>
        </svg>
      </button>
      <div x-show="open===`p<?php echo e($i); ?>`" class="faq-a"><?php echo e($faq['answer'] ?? ''); ?></div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>
  <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

  
  <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($similar ?? collect())->count()): ?>
  <div>
    <h2 style="font-size:20px;font-weight:700;color:#111;margin-bottom:20px">Похожие товары</h2>
    <div class="pgrid">
      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $similar->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $__env->make('components.product.card', ['product' => $p], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
  </div>
  <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<?php $wa = \App\Services\CacheService::setting('whatsapp', ''); ?>
<div style="background:#111;padding:40px 16px;text-align:center">
  <p style="color:#fff;font-weight:700;font-size:18px;margin-bottom:8px">Нужна помощь с выбором?</p>
  <p style="color:#aaa;font-size:14px;margin-bottom:20px">Напишите нам в WhatsApp — подберём кресло под ваши задачи</p>
  <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($wa): ?>
  <a href="https://wa.me/<?php echo e($wa); ?>" target="_blank" class="btn-wa">
    <svg class="wa-svg" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
    Написать в WhatsApp
  </a>
  <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/vhosts/autohimiki.kz/xn----8sbnbmoilhzgg4a5h.kz/resources/views/pages/product.blade.php ENDPATH**/ ?>