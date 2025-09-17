<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuperUserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $user = [
      'name' => 'Super User',
      'email' => 'admin@automate.com',
      'password' => bcrypt('12345678'),
      'mobile' =>  '01673735270',
      'user_type' => 'admin',
      'status' => 'active',
    ];
    User::insert($user);

    // DB::table('merchants')->insert([
    //   'name' => 'Example Merchant',
    //   'email' => 'admin@automate.com',
    //   'password' => Hash::make('password'),
    //   'mobile' => '01737010101',
    //   'status' => 'active',
    //   'institute_details_id' => 1,
    //   'user_type' => 'regular',
    //   'created_at' => now(),
    //   'updated_at' => now(),
    // ]);
    // DB::table('institute_details')->insert([
    //   'institute_id' => 1,
    //   'institute_name' => 'Automate Institute',
    //   'institute_contact' => '01737010101',
    //   'institute_email' => 'automate@automate.com.bd',
    //   'institute_address' => '123 example Street, Dhaka',
    //   'institute_division' => 'Dhaka',
    //   'institute_district' => 'Dhaka',
    //   'institute_upozilla' => 'Dhaka',
    //   'created_at' => now(),
    //   'updated_at' => now(),
    // ]);
  }
}
