<?php
class [NAME]TableSeeder extends Seeder 
{

    public function run()  
    {  
        $faker = Faker\Factory::create();

        [NAME]::truncate();

        foreach(range(1,[COUNT]) as $index)  
        {  
            [NAME]::create([  
                [ARRAY]
            ]);  
        }
    }
} 
// Faker reference: https://github.com/fzaninotto/Faker