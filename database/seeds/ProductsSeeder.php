<?php

use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $productes = factory(\App\Models\Product::class,30)->create();
        foreach ($productes as $producte){
            $skus = factory(\App\Models\ProductSku::class,3)->create(['product_id'=>$producte->id]);
            $producte->update(['price'=>$skus->min('price')]);
        }
    }
}
