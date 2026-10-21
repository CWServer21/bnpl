<div class="col-md-5">
    @if ($installment_due_check > 0)
        @if ($hirepurchase->status == 3)

            @php
                $isManager = Auth::user()->role_id == \App\Models\User::MANAGER;

                $alreadyPaidThisMonth = false;

                if ($isManager) {
                    $alreadyPaidThisMonth = \App\Models\Transaction::where('hire_purchase_id', $hirepurchase->id)
                        ->where('created_by', Auth::id())
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->exists();
                }
            @endphp

            @if ($isManager && $alreadyPaidThisMonth)
                <div class="alert alert-warning">
                    <strong>You have already collected payment for this month</strong><br>
                    Please wait until next month to collect the next installment.
                </div>
            @else
                <form action="{{ url('payment-collection') }}" method="post" class="parent-assign">
                    @csrf
                    <fieldset class="">
                        <legend>Payment Info:</legend>
                        <div class="row">
                            <div class="col-md-12 mb-25">
                                {{-- <h6 class="fw-normal">Details of money received on <strong>May 7,2024</strong>
                                regarding <strong>installments</strong> from <strong></strong></h6> --}}
                            </div>
                            <div class="col-md-12 mb-25">
                                <input type="hidden" value="{{ $hirepurchase->id }}" name="hire_purchase_id"
                                    id="hire_purchase_id">
                                <input type="hidden" id="monthly_installment"
                                    value="{{ @$hirepurchase->monthly_installment }}" name="monthly_installment">
                                <select onchange="CalculateAmount()" name="number_of_instllment"
                                    id="number_of_instllment" class="form-control">
                                    <option value="">How many months are you accepting
                                        installments?</option>
                                    @php
                                        $remainingInstallments = App\Models\Installment::where(
                                            'hire_purchase_id',
                                            $hirepurchase->id,
                                        )
                                            ->where('status', 0)
                                            ->count();
                                        $maxInstallments =
                                            Auth::user()->role_id == 3 ? 1 : min(9, $remainingInstallments);
                                    @endphp
                                    @for ($i = 1; $i <= $maxInstallments; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                    {{--                                    <option value="0">Others</option> --}}
                                </select>
                            </div>
                            <div class="col-md-12 mb-25">
                                <div class="holder">
                                    <div class="input-holder">
                                        <input readonly required="" name="amount" id="amount" class="input"
                                            type="text" placeholder=" " />
                                        <div class="placeholder">
                                            <p class="m-0">Receiving (BDT)<span class="text-danger">*</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mb-25" id="remarks_container" style="display:none;">
                                <div class="holder">
                                    <div class="input-holder">
                                        <textarea name="fine_remarks" id="fine_remarks" class="form-control" placeholder="Enter remarks for late fine (and reason, if waived)..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="fine_amount" id="fine_amount">
                            <div class="col-md-12 mb-25">
                                <h3 id="fine_amount_display" style="color: red; display: none;"></h3>
                            </div>
                            @if (!empty($canWaiveFine))
                                <div class="col-md-12 mb-25" id="waive_container" style="display:none;">
                                    <label for="fine_waived_amount" class="mb-1">Waive late fine (BDT)</label>
                                    <input type="number" min="0" step="0.01" value="0"
                                        name="fine_waived_amount" id="fine_waived_amount" class="form-control"
                                        oninput="CalculateAmount()">
                                    <small class="text-muted">Max: <span id="max_waive">0</span> BDT. The waived
                                        amount is deducted from the fine collected and sent to ERP.</small>
                                </div>
                            @endif
                            <div class="col-md-12 mb-25">
                                <div class="select-style2">
                                    <div class="form-control d-flex gap-3 align-items-center">
                                        <div class="d-flex align-items-center flex-nowrap gap-2"><input checked
                                                name="payment_type" id="cus_showroom" value="1" type="radio" />
                                            <label class="mt-0" for="cus_showroom">The customer comes
                                                to
                                                the showroom and makes the payment</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mb-25">
                                <div class="select-style2">
                                    <div class="form-control d-flex gap-3 align-items-center">
                                        <div class="d-flex align-items-center flex-nowrap gap-2"><input id="cus_house"
                                                name="payment_type" value="cus_house" type="radio" /> <label
                                                class="mt-0" for="2">Collection is being done by going to
                                                the customer's house</label></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7 mb-25 pe-md-0">
                                <h4 id="msg_forwarn"></h4>
                                <button type="submit" class="btn btn-lg btn-primary customr-btn btn-submit">Recive
                                    Payment</button>
                            </div>
                            {{-- <div class="col-md-5 mb-25 ps-md-0">
                            <button type="button" class="btn btn-danger">
                                Cancel
                            </button>
                        </div> --}}
                        </div>
                    </fieldset>
                </form>
            @endif
        @else
            <h4>

                This {{ @$hirepurchase->order_no }} Sales are pending

            </h4>
        @endif
    @else
        <br>
        <h4>

            Full payment for installment of product
            {{ @$hirepurchase->purchase_products->pluck('brand.name')->implode(', ') }}
            {{ @$hirepurchase->purchase_products->pluck('product.product_model')->implode(', ') }} from
            {{ @$hirepurchase->name }}

        </h4>
    @endif
</div>

<div class="col-md-7">
    <fieldset>
        <legend>{{ @$hirepurchase->purchase_products->pluck('brand.name')->implode(', ') }}
            {{ @$hirepurchase->purchase_products->pluck('product.product_model')->implode(', ') }}</legend>
        <div class="row">
            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Product's Price:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span>{{ $hirepurchase->hire_price }} TK</span>
            </div>
            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Down payment paid:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span>{{ $hirepurchase->down_payment }} TK</span>
            </div>
            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Total payment ({{ $hirepurchase->installment_month - 1 }}
                    installments) to be made:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span>{{ $hirepurchase->hire_price - $hirepurchase->down_payment }}
                    TK</span>
            </div>
            {{-- <div class="col-md-7 mb-15 text-end">
                            <span class="fw-medium">Total payment (6 installments) to be made:</span>
                        </div>
                        <div class="col-md-5 mb-15">
                            <span>51,400.00 TK</span>
                        </div> --}}
            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Total paid in installments:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span> {{ $hirepurchase->total_paid }} TK</span>
            </div>
            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Remaining Installments:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span>
                    {{ App\Models\Installment::where('hire_purchase_id', $hirepurchase->id)->where('status', 0)->count() }}</span>
            </div>

            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Remaining Installments Amount:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span>
                    {{ App\Models\Installment::where('hire_purchase_id', $hirepurchase->id)->where('status', 0)->sum('amount') }}
                    TK </span>
            </div>

            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Monthly installment amount:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span>{{ $hirepurchase->monthly_installment }} TK</span>
            </div>
            <div class="col-md-7 mb-15 mt-15 text-end">
                <span class="fw-medium">Purchased the product:</span>
            </div>
            <div class="col-md-5 mb-15 mt-15">
                <span>{{ $hirepurchase->created_at->format('M j, Y') }}</span>
            </div>
            <div class="col-md-7 mb-15 text-end">
                <span class="fw-medium">Today's date:</span>
            </div>
            <div class="col-md-5 mb-15">
                <span> {{ $hirepurchase->created_at->format('M j, Y') }}
                </span>
            </div>
        </div>
    </fieldset>

</div>

<script>
    function CalculateAmount() {
        var monthly_installment = $("#monthly_installment").val();
        var number_of_instllment = $("#number_of_instllment").val();

        var hire_purchase_id = $("#hire_purchase_id").val();
        if (number_of_instllment == 0) {
            $("#amount").removeAttr("readonly");
            $("#amount").val(monthly_installment);
        } else {
            // Send data to the server via AJAX
            $.ajax({
                url: '/calculate-amount', // Server endpoint
                type: 'POST',
                data: {
                    monthly_installment: monthly_installment,
                    number_of_instllment: number_of_instllment,
                    hire_purchase_id: hire_purchase_id,
                    waive_amount: $("#fine_waived_amount").val() || 0,
                    _token: $('meta[name="csrf-token"]').attr('content') // Laravel CSRF token
                },
                success: function(response) {
                    // Update the amount field with the calculated value
                    $("#amount").attr("readonly", "readonly");
                    $("#amount").val(response.total_amount);

                    // // Display fine amount if exists
                    // if (response.fine && response.fine > 0) {
                    //     $("#fine_amount_display").html("Fine Amount: " + response.fine + " BDT").show();
                    // } else {
                    //     $("#fine_amount_display").hide();
                    // }

                    // Waive box is only rendered for roles allowed to waive.
                    if ($("#waive_container").length) {
                        if (response.gross_fine > 0) {
                            $("#waive_container").show();
                            $("#max_waive").text(response.gross_fine);
                            $("#fine_waived_amount").attr("max", response.gross_fine);
                            // Server clamps the waiver; reflect it back in the input.
                            if (parseFloat($("#fine_waived_amount").val() || 0) > response.waived_fine) {
                                $("#fine_waived_amount").val(response.waived_fine);
                            }
                        } else {
                            $("#waive_container").hide();
                            $("#fine_waived_amount").val(0);
                        }
                    }

                    if (response.gross_fine > 0) {
                        var fineText = "Fine Amount: " + response.gross_fine + " BDT";
                        if (response.waived_fine > 0) {
                            fineText += " &minus; Waived " + response.waived_fine + " = " + response.fine + " BDT";
                        }
                        $("#fine_amount_display").html(fineText).show();
                        $("#fine_amount").val(response.fine);
                    }

                    // One remarks box covers the late fine and any waiver of it, so it is
                    // needed whenever there is a fine, even if it is waived in full.
                    if (response.gross_fine && response.gross_fine > 0) {
                        $("#remarks_container").show();
                        $("#fine_remarks").attr("required", true);
                    } else {
                        $("#remarks_container").hide();
                        $("#fine_remarks").removeAttr("required");
                    }

                    if (!(response.gross_fine > 0)) {
                        $("#fine_amount_display").hide();
                        $("#fine_amount").val('');
                        $("#remarks_container").hide();
                        $("#fine_remarks").removeAttr("required");
                    }
                },
                error: function(xhr) {
                    console.error("Error calculating amount:", xhr.responseText);
                }
            });
        }
    }

    function calculateMinimuninstallment() {
        var monthly_installment = $("#monthly_installment").val();
        var amount = $("#amount").val();
        var msg = "You Must be need to Payment " + monthly_installment + "BDT";
        if (monthly_installment > amount) {
            $("#msg_forwarn").html(msg);
        } else {
            $("#msg_forwarn").html('');
        }
    }
    $("#amount").on("input", calculateMinimuninstallment);

    // Prevent double/rapid re-submission of the payment collection form (the
    // ERP call it triggers can take a few seconds, and clicking again before
    // the page redirects was creating duplicate transactions and duplicate
    // Fine/Penalty postings to ERP).
    $(document).on("submit", "form.parent-assign", function() {
        var $form = $(this);
        if ($form.data("submitted")) {
            return false;
        }
        $form.data("submitted", true);
        $form.find(".btn-submit").prop("disabled", true).text("Processing...");
    });
</script>
