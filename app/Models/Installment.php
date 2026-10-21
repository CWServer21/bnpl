<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $table = "installments";
    protected $fillable = [
        'hire_purchase_id','amount','loan_start_date','loan_end_date','status','fine',
        'fine_amount','fine_remarks','fine_waived_amount','fine_waived_by'
    ];

    public function waivedBy(){
        return $this->belongsTo(User::class, 'fine_waived_by');
    }

    public function hire_purchase(){
        return $this->belongsTo(HirePurchase::class, 'hire_purchase_id');
    }

    public function penalty(){
        return $this->hasMany(Penalty::class, 'installment_id');
    }
}
