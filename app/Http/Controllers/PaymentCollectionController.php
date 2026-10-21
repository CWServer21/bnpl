<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Zone;
use App\Models\ShowRoom;
use App\Models\Incentive;
use App\Models\Installment;
use App\Models\Transaction;
use App\Service\ApiService;
use App\Models\HirePurchase;
use Illuminate\Http\Request;
use App\Models\ZonePermission;
use App\Models\PaymentErpHistory;
use Illuminate\Support\Facades\DB;
use App\Models\HirePurchaseProduct;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\GeneralIncentiveConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;



class PaymentCollectionController extends Controller
{
    public function calculateAmountBiaAjax(Request $request)
    {
        $monthlyInstallment = (float) $request->input('monthly_installment');
        $numberOfInstallments = (int) $request->input('number_of_instllment');
        $hire_purchase_id     = $request->input('hire_purchase_id');

        $grossFine = (float) app(\App\Service\LateFeeService::class)
            ->calculateLateFineForNextInstallments($hire_purchase_id, $numberOfInstallments);

        // Waiver can never exceed the calculated fine, and only privileged roles may waive.
        $waivedFine = $this->canWaiveFine()
            ? min(max(round((float) $request->input('waive_amount', 0), 2), 0), $grossFine)
            : 0.0;
        $netFine = $grossFine - $waivedFine;

        $totalAmount = ($monthlyInstallment * $numberOfInstallments) + $netFine;

        return response()->json([
            'total_amount' => round($totalAmount, 2),
            'gross_fine' => $grossFine,
            'waived_fine' => $waivedFine,
            'fine' => $netFine,
            'can_waive' => $this->canWaiveFine(),
        ]);
    }

    /**
     * Only admins can waive late fees.
     */
    private function canWaiveFine(): bool
    {
        return Auth::user()->role_id == User::ADMIN;
    }
    public function calculateAmount(Request $request)
    {
        $monthlyInstallment = (float) $request->input('monthly_installment');
        $numberOfInstallments = (int) $request->input('number_of_instllment');
        $hire_purchase_id = $request->input('hire_purchase_id');

        // Use the same LateFeeService/trait logic as reports/other calculations.
        $lateFeeService = app(\App\Service\LateFeeService::class);
        $totalFine = $hire_purchase_id ? (float) $lateFeeService->calculateLateFine($hire_purchase_id) : 0.0;

        $totalAmount = ($monthlyInstallment * $numberOfInstallments) + $totalFine;

        return response()->json([
            'monthly_installment' => $monthlyInstallment,
            'number_of_installments' => $numberOfInstallments,
            'fine_per_month' => 500,
            'total_fine' => $totalFine,
            'fine' => $totalFine, // backward compatible with JS expecting response.fine
            'total_amount' => $totalAmount,
        ]);
    }

    public function LoanDetails($id)
    {
        $hirepurchase = HirePurchase::with(['purchase_products', 'purchase_products.brand', 'purchase_products.product', 'show_room'])->where('order_no', $id)->first();

        if ($hirepurchase) {
            // Check if manager is trying to access BNPL from different CTP/showroom
            if (Auth::user()->role_id == User::MANAGER) { // Manager role
                if ($hirepurchase->showroom_id != Auth::user()->showroom_id) {
                    return response()->json([
                        'error' => true,
                        'message' => 'This BNPL order is not from your CTP. You can only access orders from your own CTP.'
                    ]);
                }
            }

            $installment_due_check = Installment::where('hire_purchase_id', $hirepurchase->id)->where('status', 0)->count();
            $canWaiveFine = $this->canWaiveFine();
            return view('installment.payment.details', compact("hirepurchase", "installment_due_check", "canWaiveFine"));
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Order not found. Please check the order number and try again.'
            ]);
        }
    }

    public function PaymentCollection(Request $request, ApiService $ApiService)
    {
        // dd($request->all());
        date_default_timezone_set('Asia/Dhaka');

        $isManager = Auth::user()->role_id == User::MANAGER;

        if ($isManager) {
            $alreadyPaidThisMonth = Transaction::where('hire_purchase_id', $request->hire_purchase_id)
                ->where('created_by', Auth::id())
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->exists();

            if ($alreadyPaidThisMonth) {
                return redirect()->back()->with('error', 'You have already collected an installment for this order this month. As a Showroom Manager, you can only collect one installment per month for each order.');
            }

            $number_of_installment = (int) $request->number_of_instllment;
            if ($number_of_installment > 1 || $number_of_installment === 0) {
                return redirect()->back()->with('error', 'As a Showroom Manager, you can only collect one installment at a time. Please select exactly 1 installment to collect.');
            }
        }

        // Guard against duplicate submissions for the same order (double-click, page
        // resubmission, or two near-simultaneous requests) — without this, each
        // duplicate request re-runs the whole collection, including a fresh ERP post.
        $lock = Cache::lock("payment-collection-hp-{$request->hire_purchase_id}", 30);

        if (!$lock->get()) {
            return redirect()->back()->with('error', 'This payment is already being processed. Please wait a moment, then check the transaction list before submitting again.');
        }

        // Late fee waiver: recompute the fine on the server so the client can't
        // waive more than what is actually owed, and so a waiver is only honoured
        // for roles that are allowed to give one.
        $grossFine = (float) app(\App\Service\LateFeeService::class)
            ->calculateLateFineForNextInstallments($request->hire_purchase_id, (int) $request->number_of_instllment);
        $requestedWaiver = round((float) ($request->fine_waived_amount ?? 0), 2);

        if ($requestedWaiver > 0 && !$this->canWaiveFine()) {
            $lock->release();
            return redirect()->back()->with('error', 'You are not allowed to waive late fees.');
        }
        if ($requestedWaiver > $grossFine) {
            $lock->release();
            return redirect()->back()->with('error', 'Waived amount cannot be greater than the late fine (' . $grossFine . ' BDT).');
        }

        $data = $request->all();
        $data['fine_amount'] = $grossFine - $requestedWaiver; // net fine actually charged
        $data['fine_waived_amount'] = $requestedWaiver;
        $data['fine_waived_by'] = $requestedWaiver > 0 ? Auth::id() : null;
        $data['fine_remarks'] = $request->fine_remarks;
        $data['transaction_type'] = "Installment ";
        $data['created_by'] = Auth::user()->id;
        $advance_payment = 0;

        $number_of_installment = (int) $request->number_of_instllment;
        DB::beginTransaction();
        try {
            //            if ($request->number_of_instllment == 0) {
            $amount = (float) $request->amount;
            $monthly_installment = (float) $request->monthly_installment;
            $number_of_installment = floor($amount / $monthly_installment);
            $total_pay_amount = $monthly_installment * $number_of_installment;
            // This leftover is the (net, post-waiver) fine that is posted to ERP via FineApi.
            $advance_payment = round((float) $request->amount - $total_pay_amount, 2);
            $data['number_of_instllment'] = $number_of_installment;

            Log::info('Payment calculations', [
                'amount' => $amount,
                'monthly_installment' => $monthly_installment,
                'number_of_installment' => $number_of_installment,
                'advance_payment' => $advance_payment
            ]);
            //            }
            // If no Installment records have status 0, update HirePurchase
            $hirepurchase = HirePurchase::find($request->hire_purchase_id);

            if ($hirepurchase->is_paid == 1) {
                return redirect()->back()->with('error', 'Installment already paid');
            }

            $transaction = new Transaction;
            $transaction->fill($data)->save();
            $transaction_id = $transaction->id;

            $hirepurchase->total_paid += (float) $request->amount;
            $hirepurchase->save();

            $HirePurchaseProduct = HirePurchaseProduct::where('hire_purchase_id', $request->hire_purchase_id)->first();
            // $HirePurchaseProduct->total_paid += (float) $request->amount;
            $HirePurchaseProduct->advance_pay += $advance_payment;
            $HirePurchaseProduct->save();
            $Installment = Installment::where('hire_purchase_id', $request->hire_purchase_id)->where('status', 0)->orderby('id', "ASC")->take($number_of_installment)->get();

            // The fine/penalty applies once to this whole collection (it's the
            // leftover amount after paying off full installments), so we only
            // need the installment reference from the first installment in the
            // batch to report it against — captured inside the loop below.
            $fineInstallmentNumber = null;
            $finePaymentRef = null;
            $fineInstallmentId = null;

            foreach ($Installment as $key => $install) {
                $installment_number = Installment::where('hire_purchase_id', $request->hire_purchase_id)->where('status', 1)->count();

                $paymentRef = "Cash-BNPL-Ins-{$installment_number}";


                $response =    $ApiService->CollectionApi($hirepurchase->order_no, $installment_number, $paymentRef);

                logger([
                    'response' => $response,
                ]);


                if ($response['error'] == 1) {
                    $sent = 0;
                } else {
                    $sent = 1;
                }

                if ($key == 0) {
                    $fineInstallmentNumber = $installment_number;
                    $finePaymentRef = $paymentRef;
                    $fineInstallmentId = $install->id;
                }

                PaymentErpHistory::create([
                    'tracking_id' => $hirepurchase->id,
                    'transaction_id' => $transaction_id,
                    'installment_id' => $install->id,
                    'erp_data' => [
                        'type' => 'collection',
                        'eorder_no' => $hirepurchase->order_no,
                        'ins_no' => $installment_number,
                        'payment_ref' => $paymentRef,
                    ],
                    'erp_status' => $sent,
                    'response' => json_encode($response),
                ]);

                $ins = Installment::findOrFail($install->id);
                $ins->status = 1;

                if (($data['fine_amount'] > 0 || $data['fine_waived_amount'] > 0) && $key == 0) {
                    $ins->fine_amount = $data['fine_amount'];
                    $ins->fine_waived_amount = $data['fine_waived_amount'];
                    $ins->fine_waived_by = $data['fine_waived_by'];
                    $ins->fine_remarks = $request->fine_remarks;
                }

                $ins->save();
            }

            // Send the fine/penalty exactly once per payment collection, not once
            // per installment covered by it — previously this sat inside the loop
            // above and posted the same penalty_amt to ERP once per installment,
            // producing exact duplicate Fine/Delay Charge rows in ERP.
            if ($advance_payment > 0 && $fineInstallmentNumber !== null) {
                $fineResponse = $ApiService->FineApi($hirepurchase->order_no, $advance_payment, $fineInstallmentNumber, $finePaymentRef);

                PaymentErpHistory::create([
                    'tracking_id' => $hirepurchase->id,
                    'transaction_id' => $transaction_id,
                    'installment_id' => $fineInstallmentId,
                    'erp_data' => [
                        'type' => 'fine',
                        'eorder_no' => $hirepurchase->order_no,
                        'penalty_amt' => $advance_payment,
                        'ins_no' => $fineInstallmentNumber,
                        'payment_ref' => $finePaymentRef,
                    ],
                    'erp_status' => (isset($fineResponse['error']) && $fineResponse['error'] == 1) ? 0 : 1,
                    'response' => json_encode($fineResponse),
                ]);
            }

            //if fine  then execute this code
            $installmentCount = Installment::where('hire_purchase_id', $request->hire_purchase_id)->where('status', 0)->count();

            $ShowRoom = ShowRoom::where('id', $hirepurchase->showroom_id)->first();
            $ShowRoom->remaining_credit = $ShowRoom->remaining_credit + (float) $request->amount;
            $ShowRoom->save();

            if ($installmentCount == 0) {
                if ($hirepurchase) {
                    $hirepurchase->is_paid = 1;
                    $hirepurchase->save();
                }
            }

            $allPaid = Installment::where('hire_purchase_id', $request->hire_purchase_id)
                ->where('status', 0)
                ->count() === 0;

            $latestDueDate = Installment::where('hire_purchase_id', $request->hire_purchase_id)
                ->max('loan_start_date');

            $now = Carbon::now();
            $withinDeadline = $now->lte(Carbon::parse($latestDueDate));

            // if ($allPaid && $withinDeadline) {
            //     $incentiveRate = 2.5;
            //     $totalCollected = Transaction::where('hire_purchase_id', $request->hire_purchase_id)->sum('amount');
            //     $incentiveAmount = ($totalCollected * $incentiveRate) / 100;

            //     Incentive::create([
            //         'hire_purchase_id' => $request->hire_purchase_id,
            //         'showroom_user_id' => $hirepurchase->showroom_user_id, // à¦…à¦¥à¦¬à¦¾ showroom_user_id from hirepurchase
            //         'type' => 'collection',
            //         'amount' => $totalCollected,
            //         'incentive_rate' => $incentiveRate,
            //         'incentive_amount' => $incentiveAmount,
            //         'status' => 'pending',
            //         'payment_date' => null,
            //     ]);
            // }

            if ($allPaid && $withinDeadline) {

                $incentiveStartDate = Carbon::create(2025, 12, 1);

                Incentive::where('hire_purchase_id', $request->hire_purchase_id)
                    ->whereIn('type', ['down_payment', 'sure_shot'])
                    ->update(['status' => 'approved']);
                if ($hirepurchase->created_at->gte($incentiveStartDate)) {

                    $incentiveRate = GeneralIncentiveConfig::getCollectionIncentiveRate();
                    $totalCollected = Transaction::where('hire_purchase_id', $request->hire_purchase_id)->sum('amount');
                    $incentiveAmount = ($totalCollected * $incentiveRate) / 100;

                    Incentive::create([
                        'hire_purchase_id' => $request->hire_purchase_id,
                        'showroom_user_id' => $hirepurchase->showroom_user_id,
                        'type' => 'collection',
                        'amount' => $totalCollected,
                        'incentive_rate' => $incentiveRate,
                        'incentive_amount' => $incentiveAmount,
                        'status' => 'approved',
                        'payment_date' => null,
                    ]);
                }
            }

            if ($response['error'] == 1) {
                // $erp_log->sent = 0;
                // echo $response['error'];
            } else {
                //    $erp_log->sent = 1;
            }
            DB::commit();
            return redirect()->back()->with('success', 'Success! Installment');
        } catch (Exception $e) {
            DB::rollback();
            return redirect('')->with('error', $e->getMessage());
        } finally {
            $lock->release();
        }
    }
    public function TransactionList()
    {
        $title = "Product Details";
        $description = "Some description for the page";
        if (Auth::user()->role_id == 6) {
            $permission = ZonePermission::where('user_id', Auth::user()->id)->pluck('zone_id')->toArray();
            $zones = Zone::selectRaw('id, name')->whereIn('id', $permission)->orderBy('id', 'ASC')->where('status', 1)->get();
            $showrooms = ShowRoom::selectRaw('id, name')->whereIn('zone_id', $permission)->where('status', 1)->get();
        } elseif (Auth::user()->role_id == 2) {
            $showrooms = ShowRoom::selectRaw('id, name')->where('zone_id', Auth::user()->zone_id)->where('status', 1)->get();
            $zones = Zone::selectRaw('id, name')->orderBy('id', 'ASC')->where('status', 1)->get();
        } else {
            $zones = Zone::selectRaw('id, name')->orderBy('id', 'ASC')->where('status', 1)->get();
            $showrooms = ShowRoom::selectRaw('id, name')->where('status', 1)->get();
        }

        return view('installment.payment.history', compact("title", "description", "zones", "showrooms"));
    }

    public function TransactionListShow(Request $request)
    {

        $query = Transaction::with(['hire_purchase:id,order_no,name,pr_phone,showroom_id,status', 'users', 'hire_purchase.purchase_products.product', 'hire_purchase.show_room'])
            ->where('status', 1)->whereHas('hire_purchase', function ($q) {
                $q->where('status', 3);
            });

        if ($request->from_date && $request->to_date) {
            // Date query
            $from_date = date('Y-m-d 00:00:00', strtotime($request->from_date));
            $to_date = date('Y-m-d 23:59:59', strtotime($request->to_date));
            // Date
            $query->whereBetween('created_at', [$from_date, $to_date]);
        }

        if ($request->order_no) {
            $query->whereHas('hire_purchase', function ($q) use ($request) {
                $q->where('order_no', 'LIKE', '%' . $request->order_no . '%');
            });
        }

        if ($request->zone_id) {
            $zone_id = $request->zone_id;
            // Zone
            $query->whereHas('hire_purchase.show_room', function ($q) use ($zone_id) {
                $q->where('zone_id', $zone_id);
            });
        }
        if ($request->showroom_id) {
            $showroom_id = $request->showroom_id;
            // Zone
            $query->whereHas('hire_purchase', function ($q) use ($showroom_id) {
                $q->where('showroom_id', $showroom_id);
            });
        }

        if ($request->store_type) {
            $stor_type = $request->store_type;
            $query->whereHas('hire_purchase.show_room', function ($q) use ($stor_type) {
                $q->where('dealar', $stor_type);
            });
        }


        if (Auth::user()->role_id == 2) {
            $zone_id = Auth::user()->zone_id;
            $query->whereHas('hire_purchase.show_room', function ($q) use ($zone_id) {
                $q->where('zone_id', $zone_id);
            });
        } elseif (Auth::user()->role_id == 3) {
            $showroom_id = Auth::user()->showroom_id;
            $query->whereHas('hire_purchase', function ($q) use ($showroom_id) {
                $q->where('showroom_id', $showroom_id);
            });
        } elseif (Auth::user()->role_id == 6) {
            $permission = ZonePermission::where('user_id', Auth::user()->id)->pluck('zone_id')->toArray();
            $query->whereHas('hire_purchase.show_room', function ($q) use ($permission) {
                $q->whereIn('zone_id', $permission);
            });
        }

        // Get per_page value from request, default to 30
        $perPage = $request->per_page ? (int) $request->per_page : 30;
        $installments = $query->latest()->paginate($perPage);


        return view('installment.payment.payment-history-ajax', compact("installments"));
    }
    public function TransactionListExport(Request $request)
    {
        $query = Transaction::with(['hire_purchase:id,order_no,name,pr_phone,showroom_id,status', 'users', 'hire_purchase.purchase_products.product', 'hire_purchase.show_room'])
            ->where('status', 1)->whereHas('hire_purchase', function ($q) {
                $q->where('status', 3);
            });

        if ($request->from_date && $request->to_date) {
            // Date query
            $from_date = date('Y-m-d 00:00:00', strtotime($request->from_date));
            $to_date = date('Y-m-d 23:59:59', strtotime($request->to_date));
            // Date
            $query->whereBetween('created_at', [$from_date, $to_date]);
        }

        if ($request->order_no) {
            $query->whereHas('hire_purchase', function ($q) use ($request) {
                $q->where('order_no', 'LIKE', '%' . $request->order_no . '%');
            });
        }

        if ($request->zone_id) {
            $zone_id = $request->zone_id;
            // Zone
            $query->whereHas('hire_purchase.show_room', function ($q) use ($zone_id) {
                $q->where('zone_id', $zone_id);
            });
        }
        if ($request->showroom_id) {
            $showroom_id = $request->showroom_id;
            // Zone
            $query->whereHas('hire_purchase', function ($q) use ($showroom_id) {
                $q->where('showroom_id', $showroom_id);
            });
        }

        if ($request->store_type) {
            $stor_type = $request->store_type;
            $query->whereHas('hire_purchase.show_room', function ($q) use ($stor_type) {
                $q->where('dealar', $stor_type);
            });
        }

        if (Auth::user()->role_id == 2) {
            $zone_id = Auth::user()->zone_id;
            $query->whereHas('hire_purchase.show_room', function ($q) use ($zone_id) {
                $q->where('zone_id', $zone_id);
            });
        } elseif (Auth::user()->role_id == 3) {
            $showroom_id = Auth::user()->showroom_id;
            $query->whereHas('hire_purchase', function ($q) use ($showroom_id) {
                $q->where('showroom_id', $showroom_id);
            });
        } elseif (Auth::user()->role_id == 6) {
            $permission = ZonePermission::where('user_id', Auth::user()->id)->pluck('zone_id')->toArray();
            $query->whereHas('hire_purchase.show_room', function ($q) use ($permission) {
                $q->whereIn('zone_id', $permission);
            });
        }

        $transactions = $query->latest()->get();
        $filename = 'transaction-list-report-' . \App\Helpers\Helper::formatDateTimeFilename() . '.xlsx';
        return Excel::download(new \App\Exports\TransactionExport($transactions), $filename);
    }
}
