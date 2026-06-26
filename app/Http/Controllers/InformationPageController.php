<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class InformationPageController extends Controller
{
    public function promotions()
    {
        $products = Cache::remember('page.promotions.hits', 1800, fn () =>
            Product::hits()
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->orderByDesc('views')
                ->limit(8)
                ->get()
        );

        if ($products->isEmpty()) {
            $products = Product::active()
                ->inStock()
                ->with(['brand:id,name,slug', 'category:id,name,slug,parent_id', 'category.parent:id,slug'])
                ->whereNotNull('main_image')
                ->orderByDesc('views')
                ->limit(8)
                ->get();
        }

        return view('pages.promotions', compact('products'));
    }

    public function deliveryPayment()
    {
        return view('pages.delivery-payment');
    }

    public function contacts()
    {
        $settings = CacheService::settings();

        $address = ($settings['address'] ?? '') ?: 'г. Алматы, ул. Муратбаева 138';
        $phone = ($settings['phone'] ?? '') ?: '+7 778 492 11 13';
        $whatsapp = ($settings['whatsapp'] ?? '') ?: '77784921113';
        $workingHours = $settings['working_hours']
            ?? $settings['work_hours']
            ?? $settings['schedule']
            ?? 'ежедневно с 09:00 до 21:00';

        if ($workingHours) {
            $workingHours = str_replace(['\\n', "\r\n", "\r"], "\n", $workingHours);
            $workingHours = preg_replace('/(?<=\d)n(?=\p{L})/u', "\n", $workingHours);
        }

        $routeUrl = 'https://go.2gis.com/MMVo7';
        $showroomPhotos = [
            [
                'webp' => asset('images/showroom/showroom-entrance.webp'),
                'jpg' => asset('images/showroom/showroom-entrance.jpg'),
                'alt' => 'Вход в шоурум офисных кресел Алматы',
            ],
            [
                'webp' => asset('images/showroom/showroom-netbazar.webp'),
                'jpg' => asset('images/showroom/showroom-netbazar.jpg'),
                'alt' => 'Шоурум офисных кресел NetBazar в Алматы',
            ],
            [
                'webp' => asset('images/showroom/showroom-headrest-chairs.webp'),
                'jpg' => asset('images/showroom/showroom-headrest-chairs.jpg'),
                'alt' => 'Офисные кресла с подголовником в шоуруме',
            ],
            [
                'webp' => asset('images/showroom/showroom-executive-chairs.webp'),
                'jpg' => asset('images/showroom/showroom-executive-chairs.jpg'),
                'alt' => 'Кресла для руководителей в шоуруме Алматы',
            ],
            [
                'webp' => asset('images/showroom/showroom-ergonomic-chairs.webp'),
                'jpg' => asset('images/showroom/showroom-ergonomic-chairs.jpg'),
                'alt' => 'Эргономичные офисные кресла в наличии',
            ],
        ];

        return view('pages.contacts', compact(
            'address',
            'phone',
            'whatsapp',
            'workingHours',
            'routeUrl',
            'showroomPhotos'
        ));
    }
}
