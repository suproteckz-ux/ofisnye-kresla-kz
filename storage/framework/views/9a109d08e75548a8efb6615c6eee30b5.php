<?php
    $phone         = \App\Services\CacheService::setting('phone', '');
    $whatsapp      = \App\Services\CacheService::setting('whatsapp', '');
    $siteName      = config('app.name', 'Офисные кресла');
    $navCategories = \Illuminate\Support\Facades\Cache::remember('nav.categories', 3600,
        fn() => \App\Models\Category::active()->root()->ordered()->with('children')->limit(6)->get()
    );
?>

<header class="bg-white border-b border-stone-100 sticky top-0 z-50 shadow-sm">
    <div class="container mx-auto px-4">

        
        <div style="display:flex;align-items:center;justify-content:space-between;height:52px;gap:10px;min-width:0">

            
            <a href="<?php echo e(route('home')); ?>"
               style="display:flex;align-items:center;gap:8px;flex-shrink:0;text-decoration:none;min-width:0">
                <div style="width:32px;height:32px;flex-shrink:0;background:#1c1917;border-radius:8px;
                            display:flex;align-items:center;justify-content:center">
                    <svg style="width:18px;height:18px;color:#fff" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3C8 3 5 6 5 10v2c0 1.5.5 2.9 1.3 4H4v2h16v-2h-2.3A7 7 0 0019 12v-2c0-4-3-7-7-7zm0 2a5 5 0 015 5v2a5 5 0 01-10 0v-2a5 5 0 015-5zm-1 13h2v3h-2v-3z"/>
                    </svg>
                </div>
                
                <div class="hidden xs:block" style="min-width:0;overflow:hidden">
                    <div style="font-weight:700;color:#1c1917;font-size:14px;line-height:1.2;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo e($siteName); ?></div>
                    <div style="font-size:11px;color:#a8a29e;line-height:1">Алматы</div>
                </div>
            </a>

            
            <form action="<?php echo e(route('search')); ?>" method="GET"
                  style="flex:1;max-width:480px;min-width:0;display:none" id="desktop-search">
                <input type="search" name="q" value="<?php echo e(request('q')); ?>"
                       placeholder="Поиск кресел..."
                       style="flex:1;min-width:0;padding:8px 14px;background:#f5f5f4;
                              border:1px solid #e7e5e4;border-radius:10px 0 0 10px;
                              font-size:13px;color:#1c1917;outline:none">
                <button type="submit"
                        style="padding:8px 14px;background:#1c1917;border:none;
                               border-radius:0 10px 10px 0;cursor:pointer;flex-shrink:0">
                    <svg style="width:16px;height:16px;color:#fff" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>

            
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phone): ?>
                <a href="tel:<?php echo e(preg_replace('/\D/', '', $phone)); ?>"
                   style="display:none;align-items:center;gap:6px;font-size:13px;font-weight:500;color:#57534e;text-decoration:none;white-space:nowrap" id="header-phone">
                    <?php echo e($phone); ?>

                </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($whatsapp): ?>
                <a href="https://wa.me/<?php echo e($whatsapp); ?>" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;gap:6px;padding:7px 12px;
                          background:#22c55e;color:#fff;font-size:13px;font-weight:600;
                          border-radius:8px;text-decoration:none;white-space:nowrap;flex-shrink:0">
                    <svg style="width:16px;height:16px;flex-shrink:0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                    </svg>
                    <span id="wa-text" style="display:none">WhatsApp</span>
                </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        
        <nav style="display:flex;align-items:center;gap:4px;padding-bottom:8px;
                    overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none"
             x-data="{ megaOpen: false }">

            
            <div style="position:relative;flex-shrink:0"
                 @mouseenter="megaOpen=true" @mouseleave="megaOpen=false">
                <a href="<?php echo e(route('catalog')); ?>"
                   style="display:flex;align-items:center;gap:4px;padding:6px 12px;
                          font-size:13px;font-weight:500;color:#44403c;
                          white-space:nowrap;text-decoration:none;border-radius:8px"
                   onmouseover="this.style.background='#f5f5f4'"
                   onmouseout="this.style.background='transparent'">
                    Каталог
                    <svg style="width:12px;height:12px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
                <div x-show="megaOpen" x-cloak
                     style="position:absolute;top:calc(100% + 4px);left:0;
                            background:#fff;border:1px solid #e7e5e4;border-radius:12px;
                            box-shadow:0 8px 24px rgba(0,0,0,0.10);z-index:100;
                            padding:8px;min-width:200px">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e($cat->url); ?>"
                       style="display:block;padding:8px 12px;font-size:13px;color:#44403c;
                              text-decoration:none;border-radius:8px;white-space:nowrap"
                       onmouseover="this.style.background='#fffbeb';this.style.color='#d97706'"
                       onmouseout="this.style.background='transparent';this.style.color='#44403c'">
                        <?php echo e($cat->name); ?>

                    </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $navCategories->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($cat->url); ?>"
               style="display:none;padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0" class="nav-cat-desktop"
               onmouseover="this.style.background='#f5f5f4';this.style.color='#1c1917'"
               onmouseout="this.style.background='transparent';this.style.color='#78716c'">
                <?php echo e($cat->name); ?>

            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <a href="<?php echo e(route('brands')); ?>"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Бренды
            </a>
            <a href="<?php echo e(route('blog')); ?>"
               style="padding:6px 10px;font-size:13px;font-weight:500;color:#78716c;
                      white-space:nowrap;text-decoration:none;border-radius:8px;flex-shrink:0"
               onmouseover="this.style.background='#f5f5f4'" onmouseout="this.style.background='transparent'">
                Блог
            </a>

            
            <form action="<?php echo e(route('search')); ?>" method="GET"
                  style="flex:1;min-width:80px;display:flex;margin-left:4px" id="mobile-search">
                <input type="search" name="q" placeholder="Поиск..."
                       style="flex:1;min-width:0;padding:6px 10px;background:#f5f5f4;
                              border:1px solid #e7e5e4;border-radius:8px 0 0 8px;
                              font-size:12px;color:#1c1917;outline:none">
                <button type="submit"
                        style="padding:6px 10px;background:#1c1917;border:none;
                               border-radius:0 8px 8px 0;cursor:pointer;flex-shrink:0">
                    <svg style="width:13px;height:13px;color:#fff" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>
        </nav>
    </div>
</header>

<style>
/* Header responsive — заменяет Tailwind-классы */
@media(min-width:768px){
  #desktop-search{display:flex!important}
  #mobile-search{display:none!important}
}
@media(min-width:1024px){
  #header-phone{display:flex!important}
  .nav-cat-desktop{display:block!important}
}
@media(min-width:480px){
  #wa-text{display:inline!important}
}
</style>
<?php /**PATH /var/www/vhosts/autohimiki.kz/xn----8sbnbmoilhzgg4a5h.kz/resources/views/components/ui/header.blade.php ENDPATH**/ ?>