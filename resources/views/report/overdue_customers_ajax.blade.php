<div class="table-responsive d-block custom-data-table-wrapper2">
    <table class="table mb-0 table-bordered custom-data-table">
        <thead>
            <tr class="userDatatable-header">
                <th>
                    <span class="userDatatable-title">SL No</span>
                </th>
                <th>
                    <span class="userDatatable-title">Order No</span>
                </th>
                <th>
                    <span class="userDatatable-title">Customer Name</span>
                </th>
                <th>
                    <span class="userDatatable-title">Product Name</span>
                </th>
                <th>
                    <span class="userDatatable-title">Phone</span>
                </th>
                <th>
                    <span class="userDatatable-title">Total Amount</span>
                </th>
                <th>
                    <span class="userDatatable-title">Late Payment Fee</span>
                </th>
                <th>
                    <span class="userDatatable-title">Late Fee Waived</span>
                </th>
                <th>
                    <span class="userDatatable-title">Late Fee After Waiver</span>
                </th>
                <th>
                    <span class="userDatatable-title">Waived By</span>
                </th>
                <th>
                    <span class="userDatatable-title">Outstanding Balance</span>
                </th>
                <th>
                    <span class="userDatatable-title">Last Payment Date</span>
                </th>
                <th>
                    <span class="userDatatable-title">Days Overdue</span>
                </th>
                <th>
                    <span class="userDatatable-title">Next Due Date</span>
                </th>
                <th>
                    <span class="userDatatable-title">Showroom</span>
                </th>
                <th>
                    <span class="userDatatable-title">Zone</span>
                </th>
                <th>
                    <span class="userDatatable-title">Action</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $key => $customer)
                @php
                    $next_due_date = null;
                    $outstanding_balance = 0;

                    // Find the earliest overdue installment
                    if ($customer->installment && count($customer->installment) > 0) {
                        $overdue_installments = $customer->installment
                            ->filter(function ($installment) {
                                return $installment->status == 0 &&
                                    $installment->loan_start_date < now()->toDateString();
                            })
                            ->sortBy('loan_start_date');

                        if ($overdue_installments->count() > 0) {
                            $next_due_date = $overdue_installments->first()->loan_start_date;
                        }
                    }

                    // Installment paid from DB (not from eager-loaded collection)
                    $installment_paid = \App\Models\Installment::where('hire_purchase_id', $customer->id)
                        ->where('status', 1)
                        ->sum('amount');

                    // Hire price
                    $hire_price = $customer->hire_price ?? 0;

                    // Late fee from Trait
                    $lateFeeService = app(App\Service\LateFeeService::class);
                    $late_fee = $lateFeeService->calculateLateFine($customer->id);

                    // Final outstanding balance
                    $outstanding_balance = $hire_price - $installment_paid + $late_fee;

                @endphp
                <tr>
                    <td>
                        <div class="userDatatable-content">
                            {{ ($customers->currentpage() - 1) * $customers->perpage() + $key + 1 }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ $customer->order_no }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ $customer->name }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">
                            {{ @$customer->purchase_products->pluck('product.product_model')->implode(', ') }}
                        </div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ $customer->pr_phone }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">
                            {{ @$customer->hire_price ? (float) $customer->hire_price : '0.00' }}
                        </div>
                    </td>
                    @php $waiver = $customer->fineWaiverSummary(); @endphp
                    <td>
                        <div class="userDatatable-content">{{ (float) $customer->late_fee }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ number_format($waiver['waived'], 2) }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ number_format($waiver['fine_after_waiver'], 2) }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ $waiver['waived_by'] }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ number_format($outstanding_balance, 2)}}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">
                            @php
                                $last_payment = null;
                                if ($customer->transaction && count($customer->transaction) > 0) {
                                    $last_transaction = $customer->transaction->sortByDesc('created_at')->first();
                                    $last_payment = $last_transaction->created_at;
                                }
                            @endphp
                            {{ $last_payment ? \Carbon\Carbon::parse($last_payment)->format('d F Y') : 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ $customer->days_overdue ?? 0 }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">
                            {{ $next_due_date ? \Carbon\Carbon::parse($next_due_date)->format('d F Y') : 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ @$customer->show_room->name }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">{{ @$customer->show_room->zone->name ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="userDatatable-content">
                            <a class="action-icon action-icon--info" href="{{ url('product_details', $customer->id) }}"
                                target="_blank" title="Details" aria-label="Details">
                                <i class="uil uil-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if (empty($customers))
        <p class="text-center">Data Not Found</p>
    @endif
</div>
<div class="pt-2">
    {{ $customers->links() }}
</div>
<style>
    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #f1f3f5;
        color: #495057;
        text-decoration: none;
        transition: all 0.15s ease-in-out;
    }
    .action-icon:hover {
        background: #e9ecef;
        color: #212529;
        text-decoration: none;
    }
    .action-icon--info { color: #0d6efd; }
    .action-icon i { font-size: 18px; }
</style>

<script>
    $(document).ready(function() {
        // Create the outer div for the table top scrollbar
        let $parentDivForTableTopScrollBar = $('<div></div>').addClass('custom-data-table-wrapper1 sticky-top');

        // Create the inner div for the table top scrollbar
        let $innerDivForTableTopScrollBar = $('<div></div>').addClass('custom-data-table-top-scrollbar');

        // Append the inner div to the outer div
        $parentDivForTableTopScrollBar.append($innerDivForTableTopScrollBar);

        // Insert the outer div before the .custom-data-table-wrapper2 element
        let $customDataTableWrapper2 = $('.custom-data-table-wrapper2');
        $parentDivForTableTopScrollBar.insertBefore($customDataTableWrapper2);

        let $customDataTableWrapper1 = $('.custom-data-table-wrapper1');

        // Add a scroll event listener to customDataTableWrapper1
        $customDataTableWrapper1.on('scroll', function() {
            $customDataTableWrapper2.scrollLeft($customDataTableWrapper1.scrollLeft());
        });

        // Add a scroll event listener to customDataTableWrapper2
        $customDataTableWrapper2.on('scroll', function() {
            $customDataTableWrapper1.scrollLeft($customDataTableWrapper2.scrollLeft());
        });

        let $customDataTable = $('.custom-data-table');
        let customDataTableWidth = $customDataTable.outerWidth();
        $('.custom-data-table-top-scrollbar').css('width', customDataTableWidth + 'px');
    });
</script>
