<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HirePurchase extends Model
{
    use HasFactory;

    protected $table = "hire_purchases";
    protected $fillable = [
        'showroom_user_id',
        'delivery_showroom_id',
        'customer_id',
        'credit_id',
        'showroom_id',
        'name',
        'fathers_name',
        'mothers_name',
        'spouse_name',
        'nid',
        'nid_image',
        'age',
        'marital_status',
        'pr_house_no',
        'pr_road_no',
        'pr_district_id',
        'pr_upazila_id',
        'pr_phone',
        'pr_residence_status',
        'pr_duration_staying',
        'pa_house_no',
        'pa_road_no',
        'pa_district_id',
        'pa_upazila_id',
        'pa_phone',
        'profession_id',
        'designation',
        'duration_current_profe',
        'organization_name',
        'organization_short_desc',
        'org_house_no',
        'org_road_no',
        'org_district_id',
        'org_upazila_id',
        'org_phone',
        'month_income',
        'number_of_children',
        'other_family_member',
        'name_age_family_member',
        'product_name',
        'sell_price',
        'previously_purchased',
        'pre_b_product_id',
        'pre_purchase_date',
        'pp_showroom_id',
        'shipping_address',
        'distance_from_showroom',
        'facebook_url',
        'whatsapp_number',
        'created_by',
        'email',
        'invoice_no',
        'order_no',
        'type_product',
        'status',
        'is_paid',
        'approved_by',
        'sale_by',
        'bank_id',
        'bank_account_number',
        'branch_name',
        'checkque_number',
        'approval_date',
        'rejection_note',
        'rejected_by',
        'rejected_at',
        'sale_type',
        'package_id',
        'cash_price',
        'hire_price',
        'down_payment',
        'monthly_installment',
        'installment_month',
        'total_paid',
    ];


    /* these code for due on next month search. but currently is not using.

        public function scopeDueOnNextMonthGetData($query, $request)
        {
            return $query->initialQuerySetup()
                ->applyProductGroupFilter($request)
                ->applyDateFilter($request);
        }

        public function scopeInitialQuerySetup($query)
        {
            return $query->with([
                'installment',
                'transaction',
                'purchase_product' => function ($query) {
                    $query->latest('created_at')->limit(1);
                }
            ])->where('status', 3);
        }

        public function scopeApplyProductGroupFilter($query, $request)
        {
            if ($request->order_no) {
                return $query->where('order_no', $request->order_no);
            }
            if ($request->product_group) {
                $product_group_ids = explode(',', $request->product_group);
                return $query->whereHas('purchase_product', function ($q) use ($product_group_ids) {
                    $q->whereIn('product_group_id', $product_group_ids);
                });
            }

            return $query;
        }

        public function scopeApplyDateFilter($query, $request)
        {
            if ($request->from_date && $request->to_date) {
                $from_date = date('Y-m-d 00:00:00', strtotime($request->from_date));
                $to_date = date('Y-m-d 23:59:59', strtotime($request->to_date));

                return $query->whereHas('installment', function ($q) use ($from_date, $to_date) {
                    $q->whereBetween('loan_start_date', [$from_date, $to_date])->where('status', 0);
                });
            }

            return $query;
        }

        */
    public static function selectEntities($is_paid = null, $status = null)
    {

        $query = self::with([
            'purchase_products',
            'show_room.zone',
            'transaction',
            'purchase_products.brand',
            'purchase_products.product',
            'installment',
            'purchase_products.product_category',
            'purchase_products.product_group',
            'guaranter_info',
            'salesReturn',
        ]);

        if (!is_null($is_paid)) {
            $query->where('is_paid', $is_paid);
        }
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }


    public function erplog()
    {
        return $this->hasOne(ErpLog::class, 'tracking_number');
    }
    public function transaction()
    {
        return $this->hasMany(Transaction::class, 'hire_purchase_id');
    }

    public function customer_profession()
    {
        return $this->belongsTo(CustomerProfession::class, 'profession_id')->selectRaw('id, name');
    }
    public function bank(){
        return $this->belongsTo(Bank::class,'bank_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function installment()
    {
        return $this->hasMany(Installment::class, 'hire_purchase_id');
    }
    /**
     * Late fee waiver totals for this order, used by reports and exports.
     * waived = total waived, fine_after_waiver = fine actually charged on the
     * waived installments, waived_by = distinct names of who waived.
     */
    public function fineWaiverSummary(): array
    {
        $waived = $this->installment->where('fine_waived_amount', '>', 0);

        return [
            'waived' => (float) $waived->sum('fine_waived_amount'),
            'fine_after_waiver' => (float) $waived->sum('fine_amount'),
            'waived_by' => $waived->map(fn ($i) => optional($i->waivedBy)->name)
                ->filter()->unique()->implode(', ') ?: 'N/A',
        ];
    }

    public function guaranter_info()
    {
        return $this->hasMany(GuaranterInfo::class);
    }
    public function show_room()
    {
        return $this->belongsTo(ShowRoom::class, 'showroom_id')->selectRaw('id,name,zone_id');
    }

    public function show_room_delivery()
    {
        return $this->belongsTo(ShowRoom::class, 'delivery_showroom_id')->selectRaw('id,name,zone_id');
    }

    public function ppshow_room()
    {
        return $this->belongsTo(ShowRoom::class, 'pp_showroom_id')->selectRaw('id,name');
    }

    public function show_room_user()
    {
        return $this->belongsTo(ShowRoomUser::class, 'showroom_user_id')->selectRaw('id,name');
    }



    // public function purchase_product()
    // {
    //     return $this->hasOne(HirePurchaseProduct::class, 'hire_purchase_id');
    // }

    public function purchase_products()
    {
        return $this->hasMany(HirePurchaseProduct::class, 'hire_purchase_id');
    }

    public function product_type()
    {
        return $this->hasOne(HirePurchaseProduct::class, 'hire_purchase_id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pre_purchase_product()
    {
        return $this->belongsTo(Product::class, 'pre_b_product_id');
    }

    public function districtpr()
    {
        return $this->belongsTo(District::class, 'pr_district_id');
    }

    public function upazilapr()
    {
        return $this->belongsTo(Upazila::class, 'pr_upazila_id');
    }

    public function districtpa()
    {
        return $this->belongsTo(District::class, 'pa_district_id');
    }

    public function upazilapa()
    {
        return $this->belongsTo(Upazila::class, 'pa_upazila_id');
    }


    public function districtorg()
    {
        return $this->belongsTo(District::class, 'org_district_id');
    }

    public function upazilaorg()
    {
        return $this->belongsTo(Upazila::class, 'org_upazila_id');
    }

    public function salesReturn()
    {
        return $this->hasOne(SalesReturn::class, 'hire_purchase_id');
    }


}
