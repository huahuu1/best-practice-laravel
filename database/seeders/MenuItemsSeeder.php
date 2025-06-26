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
            ],
            [
                'name' => 'Burger',
                'description' => 'Beef patty with cheese and fries',
                'price' => 12.99,
                'category' => 'Main',
                'available' => true,
            ],
            [
                'name' => 'Pasta Carbonara',
                'description' => 'Spaghetti with creamy sauce, bacon and parmesan',
                'price' => 11.99,
                'category' => 'Main',
                'available' => true,
            ],

            // Starters
            [
                'name' => 'Caesar Salad',
                'description' => 'Fresh romaine lettuce with parmesan',
                'price' => 8.99,
                'category' => 'Starter',
                'available' => true,
            ],
            [
                'name' => 'Garlic Bread',
                'description' => 'Toasted bread with garlic butter',
                'price' => 4.99,
                'category' => 'Starter',
                'available' => true,
            ],
            [
                'name' => 'Soup of the Day',
                'description' => 'Ask your server for today\'s special',
                'price' => 5.99,
                'category' => 'Starter',
                'available' => true,
            ],

            // Desserts
            [
                'name' => 'Tiramisu',
                'description' => 'Classic Italian dessert',
                'price' => 6.99,
                'category' => 'Dessert',
                'available' => true,
            ],
            [
                'name' => 'Cheesecake',
                'description' => 'New York style cheesecake',
                'price' => 7.99,
                'category' => 'Dessert',
                'available' => true,
            ],

            // Drinks
            [
                'name' => 'Soft Drink',
                'description' => 'Cola, Sprite, Fanta',
                'price' => 2.99,
                'category' => 'Drinks',
                'available' => true,
            ],
            [
                'name' => 'Coffee',
                'description' => 'Espresso, Cappuccino, Latte',
                'price' => 3.99,
                'category' => 'Drinks',
                'available' => true,
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
