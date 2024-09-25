<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    //chnages in it
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'shopify_product_id')->unique(); // Add this line
            $table->string('status')->nullable();
            $table->json('json_en')->nullable();  // Field to store the entire JSON response for English
            $table->json('json_ar')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
