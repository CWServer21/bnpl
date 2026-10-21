<style type="text/css" media="print">
    @page {
        size: auto;
        margin-top: 40px;
        margin-bottom: 40px;
    }
</style>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap');

    .formBody {
        /* padding: 20px; */
    }

    body {
        font-family: 'Jost', sans-serif !important;
        font-size: 12px !important;
        padding: 2px;
        margin: 0px;
    }

    table th,
    table td {
        font-family: 'Jost', sans-serif !important;
        font-size: 14px !important;
        border-collapse: collapse;
        padding: 2px !important;
    }
    .list_table{
        border-collapse: collapse;
    }
    .list_table td,.list_table th{
        border: 1px solid #f1f2f6;
        padding: 0.5rem 0.5rem !important;
    }
.table>:not(:last-child)>:last-child>* {
    border: 1px solid #e6e6e6 !important;
}
    .userDatatable-header th {
        border-bottom: none;
        padding: 5px;
        vertical-align: middle;
        background: #f1f2f6;
        text-align: center !important;
    }

    p {
        line-height: 20px;
    }

    @media print {
        @import url('https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap');

        .formBody {
            /* padding: 20px 40px; */
        }

        body {
            font-family: 'Jost', sans-serif !important;
            font-size: 12px !important;
            padding: 0.5px;
            margin: 0px;
        }
    table th,
    table td {
        font-family: 'Jost', sans-serif !important;
        font-size: 14px !important;
        border-collapse: collapse;
        padding: 1px !important;
    }
    .list_table{
        border-collapse: collapse;
    }
    .list_table td,.list_table th{
        border: 1px solid #f1f2f6;
        padding: 0.5rem 0.5rem !important;
    }
.table>:not(:last-child)>:last-child>* {
    border: 1px solid #e6e6e6 !important;
}
    .userDatatable-header th {
        border-bottom: none;
        padding: 5px;
        vertical-align: middle;
        background: #f1f2f6;
        text-align: center !important;
    }


        p {
            line-height: 18px;
            margin-top: 2px;
            margin-bottom: 2px;
        }

        body {
            -webkit-print-color-adjust: exact;
            /*chrome & webkit browsers*/
            color-adjust: exact;
            /*firefox & IE */
            font-family: 'Jost', sans-serif;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        img {
            -webkit-print-color-adjust: exact;
        }

    }
</style>

<style type="text/css" media="print">
    @page {
        size: auto;
        margin-top: 40px;
        margin-bottom: 50px;

        /* Footer with page counter */
        @bottom-right {
            content: "পৃষ্ঠা - " counter(page) "/" counter(pages);
            font-family: 'SutonnyMJ', sans-serif;
            font-size: 12px !important;
            font-weight: 500 !important;
        }
    }
</style>

<br>
<div class="formBody" style="width: 850px;margin:0 auto;">
    <table style="width:100%;" cellspacing="0">
        <tbody>
            <tr>
                <table style="width:100%;margin-bottom:3px" cellspacing="0">
                    <tr>
                        <td>
                            <h2 style="font-weight: 700; font-size: 22px; color: #000;margin-top:0; margin-bottom: 3px;">
                                র‍্যাংগ্স ইলেকট্রনিক্স লিমিটেড
                            </h2>
                            <p style="font-size: 12.5px; line-height: 14px; margin: 0;margin-bottom: 2px;font-weight:500;">
                                সোনারতরী টাওয়ার, ১২ সোনারগাঁও রোড, ঢাকা-১০০০, বাংলাদেশ।
                            </p>
                            <p style="font-size: 12.5px; line-height: 14px; margin: 0;font-weight:500;">
                                হটলাইন : +৮৮ ০৯৬৭৭ ২৪৪ ২৪৪, ই-মেইল : marketing@rangs.com.bd
                            </p>
                        </td>
                        <td style="text-align: right;">
                            <img src="{{ asset('assets/img/sony_rangs.png') }}" alt="Logo" width="200">
                        </td>
                    </tr>
                </table>
            </tr>
            <tr>
                <table style="width:100%;margin-bottom:3px;" cellspacing="0">
                    <tr>
                        <td style="width: 40%;">
                            <table style="width:100%;font-size:14px; text-align:left;">
                            <tr>
                                <td colspan="2" style="background:#e30613; color:#fff; font-weight:600; text-align:center; padding:3px !important; font-size:14px !important;">
                                OFFICE USE ONLY
                                </td>
                            </tr>

                            <tr>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9; font-weight:500;font-family: 'Jost', sans-serif;font-size:12px;padding:1px !important;">BNPL Order No.</td>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">
                                    {{ $hirepurchase->order_no }}
                                </td>
                            </tr>

                            <tr>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9; font-weight:500;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">Product Model</td>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">
                                    {{ @$hirepurchase->purchase_products->pluck('product.product_model')->implode(', ') }}
                                </td>
                            </tr>

                            <tr>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9; font-weight:500;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">Category</td>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">
                                    {{ @$hirepurchase->purchase_products->pluck('product_category.name')->implode(', ') }}
                                </td>
                            </tr>

                            <tr>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9; font-weight:500;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">Showroom Name:</td>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">
                                    {{ $hirepurchase->show_room->name }}
                                </td>
                            </tr>

                            <tr>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9; font-weight:500;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">Approval date:</td>
                                <td style="border:1px solid #000; padding:6px; background: #e6e7e9;font-family: 'Jost', sans-serif;font-size:12px;padding: 1px !important;">
                                    {{ date('d F Y', strtotime($hirepurchase->created_at)) }}
                                </td>
                            </tr>
                            </table>
                        </td>
                        <td style="text-align:center;vertical-align:middle;">
                            <!-- Center Badge -->
                            <div style="background-color: #ec2026; color: white; padding: 3px 22px; border-radius: 16px; display: inline-block; font-weight: 500; font-size: 23px; text-align: center;">
                                Installment List
                            </div>
                        </td>
                        <td style="width:31%;vertical-align:top;">
                              <table style="width:100%;text-align:right;" cellspacing="0">
                                    <tr>
                                        <td style="font-weight: 600;">Customer Name :
                                            {{ $hirepurchase->name }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600;">Customer Phone :
                                            {{ $hirepurchase->pr_phone }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600;">Peresent Address :
                                            {{ $hirepurchase->pr_house_no }},
                                            {{ $hirepurchase->pr_road_no }},
                                            {{ $hirepurchase->districtpr->en_name }}
                                        </td>
                                    </tr>
                                </table>
                        </td>
                    </tr>
                </table>
            </tr>
            <tr>
                <td>
                    <table style="margin:0 auto;width:100%;" class="table mb-0 table-bordered list_table">
                        <thead>
                            <tr class="userDatatable-header">
                                <th>
                                    <span class="userDatatable-title">SL</span>
                                </th>
                                <th>
                                    <span class="userDatatable-title">Name of Installment</span>
                                </th>
                                <th>
                                    <span class="userDatatable-title">Transaction Type</span>
                                </th>
                                 <th>
                                    <span class="userDatatable-title">Status</span>
                                </th>
                                 <th>
                                    <span class="userDatatable-title">Payment Date</span>
                                </th>
                                <th>
                                    <span class="userDatatable-title">Amount</span>
                                </th>
                                <th>
                                    <span class="userDatatable-title">Late Fee</span>
                                </th>
                                <th>
                                    <span class="userDatatable-title">Due Date</span>
                                </th>
                                <th><span class="userDatatable-title">Remarks</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installments as $key=>$item)
                            <tr>
                                <td>
                                    <div class="userDatatable-content">{{ $key+1 }}</div>
                                </td>
                                <td>
                                    <div class="userDatatable-content">{{ \App\Helpers\Helper::formatOrdinal(++$key) }} Installment</div>
                                </td>
                                <td>
                                    <div class="userDatatable-content">
                                        @if($key==1) Down Payment @else Installment @endif
                                    </div>
                                </td>
                                 <td>
                                    <div class="userDatatable-content">
                                        {{ $item->status == 1 ? "Paid" : "Unpaid" }}
                                    </div>
                                </td>
                                 <td>
                                    <div class="userDatatable-content">
                                       {{ $item->status == 1 ? $item->updated_at->format('d F Y') : "" }}
                                    </div>
                                </td>
                                <td>
                                    <div class="userDatatable-content">
                                        {{ $item->amount }}
                                    </div>
                                </td>
                                {{-- <td>
                                    <div class="userDatatable-content">
                                        @if($item->calculated_late_fee > 0)
                                            <span style="color: #e30613; font-weight: 600;">{{ number_format($item->calculated_late_fee, 2) }}</span>
                                        @else
                                            0.00
                                        @endif
                                    </div>
                                 </td> --}}

                                 <td>
                                        <div class="userDatatable-content">
                                            @if($item->calculated_late_fee > 0)
                                                @if($item->status == 1)
                                                    <!-- Paid late fee - show in normal color -->
                                                    <span style="font-weight: 600;">{{ number_format($item->calculated_late_fee, 2) }}</span>
                                                @else
                                                    <!-- Unpaid late fee - show in red -->
                                                    <span style="color: #e30613; font-weight: 600;">{{ number_format($item->calculated_late_fee, 2) }}</span>
                                                @endif
                                            @else
                                                0.00
                                            @endif
                                        </div>
                                    </td>
                                <td>
                                    <div class="userDatatable-content">
                                        {{ date('d F Y', strtotime($item->loan_start_date)) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="userDatatable-content">
                                        {{ $item->fine_remarks ?? '' }}
                                        @if(($item->fine_waived_amount ?? 0) > 0)
                                            <div style="color: #e30613;">
                                                Fine waived: {{ number_format($item->fine_waived_amount, 2) }}
                                                (fine after waiver: {{ number_format($item->fine_amount, 2) }})
                                                by {{ optional($item->waivedBy)->name ?? 'N/A' }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                                <tr>
                                  <td colspan="6">
                                    <div class="userDatatable-content">
                                     Hire Price :
                                    </div>
                                </td>
                                <td colspan="6">
                                    <div class="userDatatable-content">
                                  <b>  {{ $hirepurchase->hire_price }}</b>
                                    </div>
                                </td>
                            </tr>
                                <tr>
                                  <td colspan="6">
                                    <div class="userDatatable-content">
                                     Total Installment Amount :
                                    </div>
                                </td>
                                <td colspan="6">
                                    <div class="userDatatable-content">
                                   <b> {{ $total_installment_amount }}</b>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                  <td colspan="6">
                                    <div class="userDatatable-content">
                                       Total Late Payment Fee :
                                    </div>
                                </td>
                                <td colspan="6">
                                    <div class="userDatatable-content">
                                    <b>{{ $late_fee }}</b>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                  <td colspan="6">
                                    <div class="userDatatable-content">
                                        Total Paid Late Payment Fee :
                                    </div>
                                </td>
                                <td colspan="6">
                                    <div class="userDatatable-content">
                                    <b>{{ number_format($installments->sum('fine_amount'), 2) }}</b>
                                    </div>
                                </td>
                            </tr>
                              <tr>
                                  <td colspan="6">
                                    <div class="userDatatable-content">
                                       Due
                                    </div>
                                </td>
                                <td colspan="6">
                                    <div class="userDatatable-content">
                                        <b>{{number_format($due, 2) }}</b>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p style="font-size: 16px;margin-top:20px;">1. Payment must be made withing the Due date mentioned in the Installment List.</p>
                    <p style="font-size: 16px">2. Late payment fee will be applicable if customer due is over 30 days.</p>
                    <p style="font-size: 16px">3. In case of default or stop communication for more than 60 days, Rangs Electronics Limited will contact with the Guarantors to settle the overdue amount.</p>
                    <p style="font-size: 16px">
                        4. In case Guarantors don't respond, Rangs Electronics Limited will have rights to take legal step for recovery.
                    </p>
                    <table style="width:20%;margin-top:32px;" cellspacing="0">
                        <tr>
                            <td style="text-align: center;padding-top:5px !important;border-top:2px dashed #000;vertical-align:bottom;padding-bottom: 45px !important;text-align: left;font-weight:600;">
                                Signature of Customer
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    window.print();
</script>
