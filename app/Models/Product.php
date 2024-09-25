<?php

// app/Models/Product.php

// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_product_id',
        'status',
        'json_en',
        'json_ar'
    ];

    // Define the relationship with variants
}
