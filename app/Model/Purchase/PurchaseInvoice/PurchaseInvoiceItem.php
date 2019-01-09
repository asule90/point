<?php

namespace App\Model\Purchase\PurchaseInvoice;

use App\Model\Master\Allocation;
use App\Model\Master\Item;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $connection = 'tenant';

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_item_id',
        'item_id',
        'quantity',
        'unit',
        'converter',
    ];

    protected $casts = [
        'quantity'  => 'double',
        'converter' => 'double',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }
}
