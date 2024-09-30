<?php

namespace App\Models\Product;

use App\Models\BaseModel;

class Product extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'main_id',
        'barcode',
        'title',
        'description',
        'sale_price',
        'stock_unit',
        'quantity',
        'attrs',
        'approved'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sale_price' => 'decimal:2',
        'quantity' => 'int',
        'options' => 'array',
        'approved' => 'boolean'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'is_variant'
    ];

    /**
     * @return bool
     */
    protected function getIsVariantAttribute(): bool
    {
        return $this->attrs !== null;
    }
}
