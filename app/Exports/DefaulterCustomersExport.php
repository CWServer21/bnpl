<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\Installment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DefaulterCustomersExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $data;
    private $rowNumber = 0;

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
            'SL No',
            'Showroom',
            'Zone',
            'Order No',
            'Customer Name',
            'Phone',
            "Down Payment %",
            'Total Hire Price',
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
            'Last Payment Date',
            'Days Overdue',
            'Next Due Date',
        ];
    }

    public function map($customer): array
    {
        $this->rowNumber++;
        $next_due_date = null;
        $outstanding_balance = 0;

        // Check for overdue installments (installment is the relationship name, not installments)
        if ($customer->installment && count($customer->installment) > 0) {
            // Find the earliest overdue installment
            $overdue_installments = $customer->installment->filter(function($installment) {
                return $installment->status == 0 && $installment->loan_start_date < now()->toDateString();
            })->sortBy('loan_start_date');

            if ($overdue_installments->count() > 0) {
                $next_due_date = $overdue_installments->first()->loan_start_date;
            }
        }

        // Installment paid from DB
        $installment_paid = \App\Models\Installment::where('hire_purchase_id', $customer->id)
            ->where('status', 1)
            ->sum('amount');

        // Hire price
        $hire_price = $customer->hire_price ?? 0;

        // Late fee from Trait
        $lateFeeService = app(\App\Service\LateFeeService::class);
        $late_fee = $lateFeeService->calculateLateFine($customer->id);

        // Final outstanding balance
        $outstanding_balanceWithoutLateFee = Helper::normalizeZero(
            max(0, $hire_price - $installment_paid ?? 0.00)
        );

        $outstanding_balance = Helper::normalizeZero(
            max(0, $outstanding_balanceWithoutLateFee + ($late_fee ?? 0.00))
        );

        $paid_fine_amount = $customer->installment
            ? $customer->installment->sum('fine_amount')
            : 0.00;

        $totalPaymentReceivedWithoutFine = Installment::where('hire_purchase_id', $customer->id)
            ->where('status', 1)
            ->sum('amount');

        $totalPaymentReceived = Installment::query()
            ->where('hire_purchase_id', $customer->id)
            ->where('status', 1)
            ->sum(DB::raw('COALESCE(amount, 0) + COALESCE(fine_amount, 0)'));

        $last_payment = null;
        if ($customer->transaction && count($customer->transaction) > 0) {
            $last_transaction = $customer->transaction->sortByDesc('created_at')->first();
            $last_payment = $last_transaction->created_at;
        }

        $downPaymentPercent = '0.00%';
        if ($hire_price > 0) {
            $percent = round(($customer->down_payment / $hire_price) * 100, 0);  // nearest whole number
            $downPaymentPercent = $percent . ' %';
        }

        $waiver = $customer->fineWaiverSummary();

        return [
            $this->rowNumber,
            $customer->show_room ? $customer->show_room->name : 'N/A',
            $customer->show_room && $customer->show_room->zone ? $customer->show_room->zone->name : 'N/A',
            $customer->order_no,
            Str::title($customer->name),
            $customer->pr_phone,
            $downPaymentPercent,
            $customer->hire_price ? (float)($customer->hire_price) : '0.00',
            Helper::formatNumber($totalPaymentReceivedWithoutFine),
            // (float)($customer->late_fee ?? 0.00),
            // (float)($outstanding_balance),
            Helper::formatNumber((float)($late_fee ?? 0) + $paid_fine_amount),
            Helper::formatNumber($paid_fine_amount),
            Helper::formatNumber($late_fee ?? 0),
            Helper::formatNumber($waiver['waived']),
            Helper::formatNumber($waiver['fine_after_waiver']),
            $waiver['waived_by'],
            Helper::formatNumber($totalPaymentReceived),
            Helper::formatNumber(@$customer->hire_price ? (float)($customer->hire_price) + (float)($late_fee ?? 0) + $paid_fine_amount : '0.00'),
            // $outstandingBalanceFormatted, // Conditional: 0.00 if rejected/cancelled
            Helper::formatNumber($outstanding_balanceWithoutLateFee),
            Helper::formatNumber($outstanding_balance),
            $last_payment ? \Carbon\Carbon::parse($last_payment)->format('d F Y') : 'N/A',
            $customer->days_overdue ?? 0,
            $next_due_date ? \Carbon\Carbon::parse($next_due_date)->format('d F Y') : 'N/A',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:N1')->applyFromArray([
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

                // Auto-size columns
                foreach(range('A','N') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }
}
