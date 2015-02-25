<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class [PIVOTNAME]TableSeeder extends Seeder 
{

    public function run()  
    {  
        $faker = Faker\Factory::create();

        DB::table('[PIVOTTABLE]')->truncate();
        $date = new \DateTime;
        foreach(range(1,[COUNT]) as $index)  
        {  
            DB::table('[PIVOTTABLE]')->insert([ 
                [ARRAY]
                'created_at' => $date,
                'updated_at' => $date,
            ]);  
        }
    }
} 
// Faker reference: https://github.com/fzaninotto/Faker