<?php

namespace App\Traits;

use App\Models\Installment;
use Carbon\Carbon;

trait LateFeeCalculationTrait
{
    // public function calculateLateFine($hireId)
    // {
    //     $lateFinePerMonth = 500;
    //     $totalFine = 0;

    //     $overdueInstallments = Installment::where('hire_purchase_id', $hireId)
    //         ->where('status', 0)
    //         ->orderBy('loan_start_date')
    //         ->get();

    //     foreach ($overdueInstallments as $installment) {
    //         $loanStartDate = Carbon::parse($installment->loan_start_date);
    //         $currentDate = Carbon::now();

    //         if ($currentDate->greaterThan($loanStartDate)) {
    //             $monthsOverdue = $loanStartDate->diffInMonths($currentDate);

    //             if ($monthsOverdue > 1) {
    //                 $totalFine += ($monthsOverdue - 1) * $lateFinePerMonth;
    //             }
    //         }
    //     }

    //     return $totalFine;
    // }

    public function calculateLateFine($hireId)
    {
        $lateFinePerMonth = 500;
        $totalFine = 0;

        $overdueInstallments = Installment::where('hire_purchase_id', $hireId)
            ->where('status', 0) // unpaid
            ->orderBy('loan_start_date')
            ->get();

        $currentDate = Carbon::now();

        foreach ($overdueInstallments as $installment) {
            $dueDate = Carbon::parse($installment->loan_start_date);
            $penaltyStartAnchor = $dueDate->copy()->addMonth()->addDay();

            if ($currentDate->greaterThanOrEqualTo($penaltyStartAnchor)) {
                // Inclusive month count
                $monthsWithPenalty = $penaltyStartAnchor->diffInMonths($currentDate) + 1;
                $totalFine += $monthsWithPenalty * $lateFinePerMonth;
            }
        }

        return $totalFine;
    }





    /**
     * Late fine for the next $count unpaid installments (oldest due first),
     * i.e. the installments a collection of $count installments would settle.
     */
    public function calculateLateFineForNextInstallments($hireId, $count)
    {
        $installments = Installment::where('hire_purchase_id', $hireId)
            ->where('status', 0)
            ->orderBy('loan_start_date')
            ->take((int) $count)
            ->get();

        return $installments->sum(fn ($installment) => $this->calculateInstallmentLateFine($installment));
    }

    // private function calculateInstallmentLateFine($installment)
    // {
    //     $lateFinePerMonth = 500;

    //     // If already paid, no late fee
    //     if ($installment->status == 1) {
    //         return 0;
    //     }

    //     $loanStartDate = Carbon::parse($installment->loan_start_date);
    //     $currentDate = Carbon::now();

    //     if ($currentDate->greaterThan($loanStartDate)) {
    //         $monthsOverdue = $loanStartDate->diffInMonths($currentDate);

    //         if ($monthsOverdue > 1) {
    //             return ($monthsOverdue - 1) * $lateFinePerMonth;
    //         }
    //     }

    //     return 0;
    // }

    private function calculateInstallmentLateFine($installment)
    {
        $lateFinePerMonth = 500;

        // If already paid, no late fee
        if ($installment->status == 1) {
            return 0;
        }

        $dueDate = Carbon::parse($installment->loan_start_date);
        $currentDate = Carbon::now();

        // Penalty starts from the next month + 1 day
        $penaltyStartAnchor = $dueDate->copy()->addMonth()->addDay();

        if ($currentDate->greaterThanOrEqualTo($penaltyStartAnchor)) {
            // Inclusive month count
            $monthsWithPenalty = $penaltyStartAnchor->diffInMonths($currentDate) + 1;
            return $monthsWithPenalty * $lateFinePerMonth;
        }

        return 0;
    }
}
