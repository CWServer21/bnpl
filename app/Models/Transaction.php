<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = "transactions";
    protected $fillable = [
        'hire_purchase_id', 'payment_type','amount','transaction_type','number_of_instllment','created_by','status', 'fine_amount','fine_remarks',
        'fine_waived_amount','fine_waived_by'
    ];

    public function hire_purchase(){
        return $this->belongsTo(HirePurchase::class, 'hire_purchase_id');
    }

    public function waivedBy(){
        return $this->belongsTo(User::class, 'fine_waived_by');
    }

    public function users(){
        return $this->belongsTo(User::class, 'created_by')->selectRaw('id, name, email, phone, address');
    }

}
