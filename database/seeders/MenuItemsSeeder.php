<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menuItems = [
            // Main dishes
            [
                'name' => 'Pizza Margherita',
                'description' => 'Classic tomato and mozzarella pizza',
                'price' => 10.99,
                'category' => 'Main',
                'available' => true,
                'image_path' => 'images/menu/pizza-margherita.jpg',
            ],
            [
                'name' => 'Burger',
                'description' => 'Beef patty with cheese and fries',
                'price' => 12.99,
                'category' => 'Main',
                'available' => true,
                'image_path' => 'images/menu/burger.jpg',
            ],
            [
                'name' => 'Pasta Carbonara',
                'description' => 'Spaghetti with creamy sauce, bacon and parmesan',
                'price' => 11.99,
                'category' => 'Main',
                'available' => true,
                'image_path' => 'images/menu/pasta-carbonara.jpg',
            ],

            // Starters
            [
                'name' => 'Caesar Salad',
                'description' => 'Fresh romaine lettuce with parmesan',
                'price' => 8.99,
                'category' => 'Starter',
                'available' => true,
                'image_path' => 'images/menu/caesar-salad.jpg',
            ],
            [
                'name' => 'Garlic Bread',
                'description' => 'Toasted bread with garlic butter',
                'price' => 4.99,
                'category' => 'Starter',
                'available' => true,
                'image_path' => 'images/menu/garlic-bread.jpg',
            ],
            [
                'name' => 'Soup of the Day',
                'description' => 'Ask your server for today\'s special',
                'price' => 5.99,
                'category' => 'Starter',
                'available' => true,
                'image_path' => 'images/menu/soup.jpg',
            ],

            // Desserts
            [
                'name' => 'Tiramisu',
                'description' => 'Classic Italian dessert',
                'price' => 6.99,
                'category' => 'Dessert',
                'available' => true,
                'image_path' => 'images/menu/tiramisu.jpg',
            ],
            [
                'name' => 'Cheesecake',
                'description' => 'New York style cheesecake',
                'price' => 7.99,
                'category' => 'Dessert',
                'available' => true,
                'image_path' => 'images/menu/cheesecake.jpg',
            ],

            // Drinks
            [
                'name' => 'Soft Drink',
                'description' => 'Cola, Sprite, Fanta',
                'price' => 2.99,
                'category' => 'Drinks',
                'available' => true,
                'image_path' => 'images/menu/soft-drink.jpg',
            ],
            [
                'name' => 'Coffee',
                'description' => 'Espresso, Cappuccino, Latte',
                'price' => 3.99,
                'category' => 'Drinks',
                'available' => true,
                'image_path' => 'images/menu/coffee.jpg',
            ],
        ];

        foreach ($menuItems as $item) {
            DB::table('menu_items')->insert(array_merge($item, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
