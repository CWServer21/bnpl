<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\Installment;
use Illuminate\Support\Str;
use App\Service\LateFeeService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class BnplOrdersExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $data;

    public function __construct($data)
    {

        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            "SL No",
            "CTP",
            "Zone",
            "BNPL Order No",
            "Loan Start Date",
            "Loan End Date",
            "Brand",
            "Model",
            "Size",
            "Down Payment %",
            "Total Hire Price",
            "First Installment",
            "Monthly Installment",
            "Payment Received",
            "Late Payment Fee",
            "Late Payment Received",
            "Late Payment Outstanding",
            "Late Fee Waived Amount",
            "Late Fee After Waiver",
            "Late Fee Waived By",
            "Total Payment Received",
            "Total Hire Price Including Late Payment Fee",
            "Outstanding Balance",
            "Outstanding Balance Including Late Payment Fee",
            "Total Installment",
            "Paid Installment",
            "Due Installment",
            "Last Transaction Amount",
            "Last Transaction Date",
            "Next Due Date",
            "Sales Return Date",
            "Sales Return Amount",
            "Collection Refund Amount",
            "Other Income From Defaulter",
            "Sales Return Reason",
            "Customer Name",
            "Phone Number",
            "Sales Representative",
            "Created By",
            "Status",
            "Current Status",
            "Guarantor 1 Name",
            "Guarantor 1 Relation",
            "Guarantor 1 Phone",
            "Guarantor 1 NID",
            "Guarantor 2 Name",
            "Guarantor 2 Relation",
            "Guarantor 2 Phone",
            "Guarantor 2 NID"
        ];
    }

    public function map($purchase): array
    {
        try {
            // Get loan dates
            $firstLoanStartDate = null;
            $lastLoanEndDate = null;
            $next_due_date = null;

            if ($purchase->installment && count($purchase->installment) > 0) {
                $firstLoanStartDate = $purchase->installment[0]->loan_start_date;
                $sortedInstallments = $purchase->installment->sortBy('loan_start_date');
                $lastLoanEndDate = optional($sortedInstallments->last())->loan_start_date;

                // Find next due date
                $next_installment = Installment::where('hire_purchase_id', $purchase->id)
                    ->where('status', 0)
                    ->orderby('id', "ASC")
                    ->first();
                if ($next_installment) {
                    $next_due_date = $next_installment->loan_start_date;
                }
            }

            // Get last transaction details
            $last_paid_amount = 0;
            $last_payment_date = null;
            if ($purchase->transaction && count($purchase->transaction) > 0) {
                $last_paid_amount = $purchase->transaction[count($purchase->transaction) - 1]->amount;
                $last_payment_date = $purchase->transaction[count($purchase->transaction) - 1]->updated_at;
            }

            // Check if status is 2 (Rejected) or 4 (Sale Cancel)
            $isRejectedOrCancelled = in_array($purchase->status, [2, 4]);

            $installment_paid = $purchase->installment->where('status', 1)->sum('amount');
            $hire_price = $purchase->hire_price ?? 0;

            // Trait use করা class instance আনতে হবে
            $lateFeeService = app(LateFeeService::class);
            $late_fee = $lateFeeService->calculateLateFine($purchase->id);
            $paid_fine_amount = $purchase->installment->sum('fine_amount');



            // মোট বাকি = (hire_price - paid) + late_fee
            $outstanding_balance = ($hire_price - $installment_paid) + $late_fee;


            // Get status text
            $status_text = '';
            switch ($purchase->status) {
                case 0:
                    $status_text = 'Pending';
                    break;
                case 1:
                    $status_text = 'Approved';
                    break;
                case 2:
                    $status_text = 'Rejected';
                    break;
                case 3:
                    $status_text = 'Sale Confirm';
                    break;
                case 4:
                    $status_text = 'Sale Cancel';
                    break;
                case 5:
                    $status_text = 'Sale Return';
                    break;
                default:
                    $status_text = 'Unknown';
            }

            static $slNo = 0;
            $slNo++;

            // Get guarantor information
            $guarantor1_name = 'N/A';
            $guarantor1_relation = 'N/A';
            $guarantor1_phone = 'N/A';
            $guarantor1_nid = 'N/A';
            $guarantor2_name = 'N/A';
            $guarantor2_relation = 'N/A';
            $guarantor2_phone = 'N/A';
            $guarantor2_nid = 'N/A';

            if ($purchase->guaranter_info && count($purchase->guaranter_info) > 0) {
                // First guarantor (index 0)
                if (isset($purchase->guaranter_info[0])) {
                    $guarantor1_name = $purchase->guaranter_info[0]->guarater_name ?? 'N/A';
                    $guarantor1_relation = $purchase->guaranter_info[0]->guarater_relation ?? 'N/A';
                    $guarantor1_phone = $purchase->guaranter_info[0]->guarater_phone ?? 'N/A';
                    $guarantor1_nid = $purchase->guaranter_info[0]->guarater_nid ?? 'N/A';
                }

                // Second guarantor (index 1)
                if (isset($purchase->guaranter_info[1])) {
                    $guarantor2_name = $purchase->guaranter_info[1]->guarater_name ?? 'N/A';
                    $guarantor2_relation = $purchase->guaranter_info[1]->guarater_relation ?? 'N/A';
                    $guarantor2_phone = $purchase->guaranter_info[1]->guarater_phone ?? 'N/A';
                    $guarantor2_nid = $purchase->guaranter_info[1]->guarater_nid ?? 'N/A';
                }
            }



            // Set conditional values based on status
            $firstInstallment = $isRejectedOrCancelled ? '0.00' : (@$purchase->down_payment ? ($purchase->down_payment) : '0.00');

            // $totalPaymentReceived = $isRejectedOrCancelled ? '0.00' : (@$purchase->purchase_product ? number_format($purchase->purchase_product->total_paid, 2) : '0.00');
            // $totalPaymentReceived = $isRejectedOrCancelled
            //     ? '0.00'
            //     : (
            //         $purchase->installment
            //         ->where('status', 1)
            //         ->sum(function ($installment) {
            //             return $installment->amount + $installment->fine_amount;
            //         })
            //     );

            // $totalPaymentReceivedWithoutFine = $isRejectedOrCancelled
            //     ? '0.00'
            //     : (
            //         $purchase->installment
            //         ->where('status', 1)
            //         ->sum(function ($installment) {
            //             return $installment->amount;
            //         })
            //     );
            // $outstandingBalanceFormatted = $isRejectedOrCancelled ? '0.00' :
            //     number_format($outstanding_balance, 2);

            $outstandingBalanceFormatted = $isRejectedOrCancelled
                ? '0.00'
                : (max(0, $outstanding_balance));

            $paidInstallment = $isRejectedOrCancelled ? '0' : (@$purchase->installment ? $purchase->installment->where('status', 1)->count() : '0');

            $dueInstallment = $isRejectedOrCancelled ? '0' : (@$purchase->installment ? $purchase->installment->where('status', 0)->count() : '0');

            // Sales Return data
            $salesReturn = $purchase->salesReturn ?? null;  // relation থেকে আসবে (eager load করতে হবে)

            $returnDate       = $salesReturn ? \Carbon\Carbon::parse($salesReturn->returned_at)->format('d F Y') : 'N/A';
            $returnAmount     = $salesReturn ? number_format($salesReturn->return_amount ?? 0, 2) : '0.00';
            $refundAmount     = $salesReturn ? number_format($salesReturn->refund_amount ?? 0, 2) : '0.00';
            $otherIncome      = $salesReturn ? number_format($salesReturn->other_income ?? 0, 2) : '0.00';
            $returnReason     = $salesReturn ? ($salesReturn->reason_text ?? ucfirst($salesReturn->reason)) : 'N/A';

            $status = 'Regular';

            if ($purchase->status == 2) {
                $status = 'Rejected';
            } elseif ($purchase->status == 4) {
                $status = 'Sale Cancel';
            } elseif ($purchase->salesReturn) {
                $status = 'Sale Return';
            } elseif ($purchase->installment && $purchase->installment->isNotEmpty()) {
                $hasUnpaid = $purchase->installment->contains('status', 0);

                if (!$hasUnpaid) {
                    $status = 'Paid';
                } else {
                    $latestUnpaidDate = $purchase->installment
                        ->where('status', 0)
                        ->max('loan_start_date');
                    $hasOverdueUnpaid = $purchase->installment
                        ->where('status', 0)
                        ->filter(function ($inst) {
                            return Carbon::parse($inst->loan_start_date)->lt(now());
                        })
                        ->isNotEmpty();

                    if ($latestUnpaidDate) {
                        $latestDue = Carbon::parse($latestUnpaidDate);
                        if ($latestDue->lt(now()->subDays(30))) {
                            $status = 'Defaulter';
                        } elseif ($hasOverdueUnpaid) {
                            $status = 'Overdue';
                        }
                    } elseif ($hasOverdueUnpaid) {
                        $status = 'Overdue';
                    }
                }
            }


            // Down Payment % calculation
            $downPaymentPercent = '0.00%';
            if (!$isRejectedOrCancelled && $hire_price > 0) {
                $percent = round(($purchase->down_payment / $hire_price) * 100, 0);  // nearest whole number
                $downPaymentPercent = $percent . ' %';
            }

            // Installment paid (যদি relation load করা থাকে)
            $installment_paid = $purchase->installment
                ? $purchase->installment->where('status', 1)->sum('amount')
                : 0;

            // Total Payment Received Without Fine (DB query দিয়ে সেফ করা)
            $totalPaymentReceivedWithoutFine = Installment::where('hire_purchase_id', $purchase->id)
                ->where('status', 1)
                ->sum('amount') ?? 0;

            // Total Payment Received (fine সহ)
            $totalPaymentReceived = Installment::query()
                ->where('hire_purchase_id', $purchase->id)
                ->where('status', 1)
                ->sum(DB::raw('COALESCE(amount, 0) + COALESCE(fine_amount, 0)'));

            // এখানে আগের মতো normalizeZero ব্যবহার করো
            $outstanding_balanceWithoutLateFee = Helper::normalizeZero(
                max(0, $hire_price - $totalPaymentReceivedWithoutFine ?? 0.00)
            );

            $outstanding_balance = Helper::normalizeZero(
                max(0, $outstanding_balanceWithoutLateFee + ($late_fee ?? 0.00))
            );

            // Rejected/Cancelled case হ্যান্ডেল
            if ($isRejectedOrCancelled) {
                $outstanding_balanceWithoutLateFee = 0.00;
                $outstanding_balance = 0.00;
                $totalPaymentReceived = 0.00;
                $totalPaymentReceivedWithoutFine = 0.00;
            }


            $waiver = $purchase->fineWaiverSummary();

            return [
                $slNo,
                data_get($purchase, 'show_room.name', 'N/A'),
                data_get($purchase, 'show_room.zone.name', 'N/A'),
                $purchase->order_no,
                $firstLoanStartDate ? \Carbon\Carbon::parse($firstLoanStartDate)->format('d F Y') : 'N/A',
                $lastLoanEndDate ? \Carbon\Carbon::parse($lastLoanEndDate)->format('d F Y')  : 'N/A',
                @$purchase->purchase_products->pluck('brand.name')->implode(', ') ?? 'N/A',
                @$purchase->purchase_products->pluck('product.product_model')->implode(', ') ?? 'N/A',
                @$purchase->purchase_products->pluck('product_size_id')->implode(', ') ?? 'N/A',
                $downPaymentPercent,
                @$purchase->hire_price ? (float)($purchase->hire_price) : '0.00',
                $firstInstallment, // Conditional: 0.00 if rejected/cancelled
                @$purchase->monthly_installment ? (float)($purchase->monthly_installment) : '0.00',
                $totalPaymentReceivedWithoutFine, // Conditional: 0.00 if rejected/cancelled
                // @$purchase->late_fee ?? 0.00,
                $isRejectedOrCancelled ? '0.00' : Helper::formatNumber((float)($late_fee) + $paid_fine_amount),
                $isRejectedOrCancelled ? '0.00' : Helper::formatNumber($paid_fine_amount),
                $isRejectedOrCancelled ? '0.00' : Helper::formatNumber($late_fee),
                Helper::formatNumber($waiver['waived']),
                Helper::formatNumber($waiver['fine_after_waiver']),
                $waiver['waived_by'],
                Helper::formatNumber($totalPaymentReceived), // Conditional: 0.00 if rejected/cancelled
                Helper::formatNumber(@$purchase->hire_price ? (float)($purchase->hire_price) + (float)($late_fee) + $paid_fine_amount : '0.00'),
                // $outstandingBalanceFormatted, // Conditional: 0.00 if rejected/cancelled
                // Helper::formatNumber(@$purchase->hire_price ? (float)($purchase->hire_price) - $totalPaymentReceivedWithoutFine : '0.00'),
                // Helper::formatNumber($late_fee ? (float)($late_fee) + ((float)($purchase->hire_price) - $totalPaymentReceivedWithoutFine) : '0.00'),
                Helper::formatNumber($outstanding_balanceWithoutLateFee),

                // "Outstanding Balance Including Late Payment Fee"
                Helper::formatNumber($outstanding_balance),
                @$purchase->installment ? $purchase->installment->count() : '0',
                $paidInstallment, // Conditional: 0 if rejected/cancelled
                $dueInstallment, // Conditional: 0 if rejected/cancelled
                (float)($last_paid_amount),
                $last_payment_date ? \Carbon\Carbon::parse($last_payment_date)->format('d F Y') : 'N/A',
                $next_due_date ? \Carbon\Carbon::parse($next_due_date)->format('d F Y') : 'N/A',

                $returnDate,
                $returnAmount,
                $refundAmount,
                $otherIncome,
                $returnReason,

                Str::title($purchase->name ?? 'N/A'),
                $purchase->pr_phone ?? 'N/A',
                Str::title(data_get($purchase, 'show_room_user.name', 'N/A')),
                Str::title(data_get($purchase, 'users.name', 'N/A')),
                $status_text,
                $status,
                Str::title($guarantor1_name ?? 'N/A'),
                Str::title($guarantor1_relation),
                $guarantor1_phone,
                $guarantor1_nid,
                Str::title($guarantor2_name),
                Str::title($guarantor2_relation),
                $guarantor2_phone,
                $guarantor2_nid
            ];
        } catch (\Exception $e) {
            logger()->error('BnplOrdersExport error (hire_purchase_id=' . ($purchase->id ?? 'N/A') . '): ' . $e->getMessage());
            return [];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Set column widths
                $columns = [
                    'A' => 8,  // SL No
                    'B' => 25, // CTP
                    'C' => 20, // Zone
                    'D' => 25, // Order No
                    'E' => 15, // Loan Start Date
                    'F' => 15, // Loan End Date
                    'G' => 15, // Brand
                    'H' => 20, // Model
                    'I' => 15, // Size
                    'J' => 20, // Total Hire Price
                    'K' => 20, // First Installment
                    'L' => 20, // Monthly Installment
                    'M' => 20, // Total Payment
                    'N' => 20, // Outstanding Balance
                    'O' => 15, // Total Installment
                    'P' => 15, // Paid Installment
                    'Q' => 15, // Due Installment
                    'R' => 20, // Last Transaction Amount
                    'S' => 20, // Last Transaction Date
                    'T' => 15, // Next Due Date
                    'U' => 25, // Customer Name
                    'V' => 15, // Phone Number
                    'W' => 25, // Sales Representative
                    'X' => 25, // Created By
                    'Y' => 15, // Status
                    'Z' => 25, // Guarantor 1 Name
                    'AA' => 20, // Guarantor 1 Relation
                    'AB' => 15, // Guarantor 1 Phone
                    'AC' => 20, // Guarantor 1 NID
                    'AD' => 25, // Guarantor 2 Name
                    'AE' => 20, // Guarantor 2 Relation
                    'AF' => 15, // Guarantor 2 Phone
                    'AG' => 20, // Guarantor 2 NID
                ];

                foreach ($columns as $column => $width) {
                    $event->sheet->getColumnDimension($column)->setWidth($width);
                }

                // Style header row
                $event->sheet->getStyle('A1:AJ1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E2EFD9',
                        ]
                    ]
                ]);
            }
        ];
    }
}
