<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantCategory;
use App\Models\MerchantProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoMerchantSeeder extends Seeder
{
    public function run()
    {
        $merchant = Merchant::updateOrCreate(
            ['slug' => 'kebab-blancmesnil'],
            [
                'name' => 'Kebab du Blanc-Mesnil',
                'email' => 'contact@kebab-blancmesnil.test',
                'phone' => '+33 1 00 00 00 00',
                'address' => '1 Avenue de la Démo, 93150 Le Blanc-Mesnil',
                'description' => 'Sandwichs, assiettes et menus préparés à la commande. Démo restaurant Véloxi.',
                'opening_hours' => [
                    'monday' => '11:00-23:00',
                    'tuesday' => '11:00-23:00',
                    'wednesday' => '11:00-23:00',
                    'thursday' => '11:00-23:00',
                    'friday' => '11:00-23:30',
                    'saturday' => '11:00-23:30',
                    'sunday' => '12:00-22:30',
                ],
                'is_open' => true,
                'accepts_pickup' => true,
                'accepts_delivery' => true,
                'max_delivery_distance_km' => 5,
                'latitude' => 48.9386,
                'longitude' => 2.4614,
                'minimum_order_amount' => 0,
                'status' => 1,
            ]
        );

        $categories = collect([
            ['name' => 'Menus kebab', 'position' => 1],
            ['name' => 'Assiettes', 'position' => 2],
            ['name' => 'Boissons', 'position' => 3],
        ])->mapWithKeys(function ($category) use ($merchant) {
            $model = MerchantCategory::updateOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'slug' => Str::slug($category['name']),
                ],
                [
                    'name' => $category['name'],
                    'position' => $category['position'],
                    'status' => 1,
                ]
            );

            return [$category['name'] => $model];
        });

        $products = [
            ['category' => 'Menus kebab', 'name' => 'Menu kebab classique', 'description' => 'Pain, viande kebab, crudités, sauce au choix, frites et boisson.', 'price' => 9.90, 'sort_order' => 1],
            ['category' => 'Menus kebab', 'name' => 'Menu galette kebab', 'description' => 'Galette roulée, viande kebab, crudités, frites et boisson.', 'price' => 10.50, 'sort_order' => 2],
            ['category' => 'Menus kebab', 'name' => 'Menu chicken', 'description' => 'Poulet mariné, crudités, frites et boisson.', 'price' => 9.50, 'sort_order' => 3],
            ['category' => 'Assiettes', 'name' => 'Assiette kebab', 'description' => 'Viande kebab, frites, salade composée et sauce.', 'price' => 13.90, 'sort_order' => 1],
            ['category' => 'Assiettes', 'name' => 'Assiette mixte', 'description' => 'Kebab, chicken, frites, salade et sauce maison.', 'price' => 15.50, 'sort_order' => 2],
            ['category' => 'Boissons', 'name' => 'Coca-Cola 33cl', 'description' => 'Canette 33cl.', 'price' => 2.20, 'sort_order' => 1],
            ['category' => 'Boissons', 'name' => 'Eau minérale 50cl', 'description' => 'Bouteille 50cl.', 'price' => 1.80, 'sort_order' => 2],
        ];

        foreach ($products as $product) {
            MerchantProduct::updateOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'name' => $product['name'],
                ],
                [
                    'category_id' => $categories[$product['category']]->id,
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'status' => 1,
                    'sort_order' => $product['sort_order'],
                ]
            );
        }
    }
}
