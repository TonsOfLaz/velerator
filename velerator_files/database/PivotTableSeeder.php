<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class [PIVOTNAME]TableSeeder extends Seeder 
{

    public function run()  
    {  
        DB::table('[PIVOTTABLE]')->truncate();

        // [FAKE]

        // [REAL]
    }
} 
// Faker reference: https://github.com/fzaninotto/Faker