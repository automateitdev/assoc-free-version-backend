<?php

namespace Database\Seeders;

use App\Models\CoreCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CoreCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['core_category_name' => 'Academic Year'],
            ['core_category_name' => 'Academic Session'],
            ['core_category_name' => 'Shift'],
            ['core_category_name' => 'Class'],
            ['core_category_name' => 'Group'],
            ['core_category_name' => 'Section'],
            ['core_category_name' => 'Student Category'],
            // Add more categories if needed
        ];

        // Insert data into the core_categories table
        CoreCategory::insert($categories);
    }
}
