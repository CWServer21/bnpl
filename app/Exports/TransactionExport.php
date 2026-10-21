<?php

namespace App\Exports;

use App\Helpers\Helper;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class TransactionExport implements FromCollection, WithHeadings, WithMapping, WithEvents
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
            'Transaction Type',
            'Order No',
            'Customer Name',
            'Phone Number',
            'Product Model',
            'Showroom',
            'Transaction Date & Time',
            'Transaction Amount',
            'Received By',
            'Late Fee Waived Amount',
            'Late Fee Waived By'
        ];
    }

    public function map($transaction): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $transaction->transaction_type ?? 'N/A',
            $transaction->hire_purchase->order_no ?? 'N/A',
            Str::title($transaction->hire_purchase->name ?? 'N/A'),
            $transaction->hire_purchase->pr_phone ?? 'N/A',
            $transaction->hire_purchase->purchase_products->pluck('product.product_model')->implode(', ') ?? 'N/A',
            $transaction->hire_purchase->show_room->name ?? 'N/A',
            // Helper::formatDateTimeStandard($transaction->created_at),
            \Carbon\Carbon::parse($transaction->created_at)->format('d F Y'),
            ($transaction->amount),
            Str::title($transaction->users->name ?? 'N/A'),
            (float) ($transaction->fine_waived_amount ?? 0),
            ($transaction->fine_waived_amount ?? 0) > 0 ? Str::title($transaction->waivedBy->name ?? 'N/A') : 'N/A'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Style header row
                $event->sheet->getStyle('A1:L1')->applyFromArray([
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

                // Set column widths
                $columns = [
                    'A' => 8,  // SL No
                    'B' => 20, // Transaction Type
                    'C' => 25, // Order No
                    'D' => 25, // Customer Name
                    'E' => 15, // Phone Number
                    'F' => 25, // Product Model
                    'G' => 25, // Showroom
                    'H' => 20, // Transaction Date & Time
                    'I' => 18, // Transaction Amount
                    'J' => 25, // Received By
                    'K' => 20, // Late Fee Waived Amount
                    'L' => 25, // Late Fee Waived By
                ];

                foreach ($columns as $column => $width) {
                    $event->sheet->getColumnDimension($column)->setWidth($width);
                }
            }
        ];
    }
}
