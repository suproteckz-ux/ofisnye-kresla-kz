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
            ?? null;

        if ($workingHours) {
            $workingHours = str_replace(['\\n', "\r\n", "\r"], "\n", $workingHours);
            $workingHours = preg_replace('/(?<=\d)n(?=\p{L})/u', "\n", $workingHours);
        }

        return view('pages.contacts', compact(
            'address',
            'phone',
            'whatsapp',
            'workingHours'
        ));
    }
}
