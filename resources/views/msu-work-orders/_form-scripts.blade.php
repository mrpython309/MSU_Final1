<script>
(function() {

    function boot() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(boot, 50);
            return;
        }
        initWoForm(window.jQuery);
    }

    function initWoForm($) {
        var rowIndex = $('#wo-items-body .wo-item-row').length;
        var chargeRowIndex = $('#wo-charges-body .wo-charge-row').length;
        var previewWoNumberUrl = @json(route('msu-work-orders.preview-wo-number'));
        var formCompanyId = @json($formCompanyId ?? session('global_company_id'));
        var defaultCompanyCode = @json($companyCode ?? 'MSULLP');
        var isEditWO = @json(isset($msuWorkOrder));
        var csrfToken = @json(csrf_token());
        var initialWoNumber = @json(isset($msuWorkOrder) ? $msuWorkOrder->wo_number : '');
        var woPreviewRequest = null;
        var woPreviewToken = 0;

        function initSearchableSelect($el, extraOptions) {
            if (!$el || !$el.length || typeof $.fn.select2 !== 'function') return;
            $el.each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                var options = $.extend({
                    placeholder: 'Select',
                    allowClear: true,
                    width: '100%',
                    dropdownAutoWidth: false
                }, extraOptions || {});
                if ($select.closest('.input-group').length) {
                    options.dropdownParent = $select.closest('.col, .col-md-6, .card-body, form').first();
                } else if ($select.closest('.table-responsive').length) {
                    options.dropdownParent = $(document.body);
                }
                $select.select2(options);
            });
        }

        function normalizeBranchCode(code) {
            var raw = String(code || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            return raw ? raw.substring(0, 3) : 'BRN';
        }

        function financialYearCode(dateStr) {
            if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                return '----';
            }
            var year = parseInt(dateStr.substring(0, 4), 10);
            var month = parseInt(dateStr.substring(5, 7), 10);
            var startYear = month >= 4 ? year : year - 1;
            var endYear = startYear + 1;
            return String(startYear).slice(-2) + String(endYear).slice(-2);
        }

        function applyLocalWoNumberPreview() {
            var $opt = $('#branch_id option:selected');
            var branchId = String($('#branch_id').val() || '');
            var branchCode = branchId
                ? normalizeBranchCode($opt.attr('data-code') || '')
                : 'BRN';
            var companyCode = branchId
                ? ($opt.attr('data-company-code') || defaultCompanyCode || 'MSULLP')
                : (defaultCompanyCode || 'MSULLP');
            var woDate = $('#wo_date').val() || '';
            var current = String($('#wo_number_preview').val() || '');
            var parts = current.split('/');
            var seq = parts.length ? parts[parts.length - 1] : '';
            if (!/^\d+$/.test(seq)) {
                seq = '0001';
            }
            var fy = financialYearCode(woDate);
            var month = (woDate && /^\d{4}-\d{2}-\d{2}$/.test(woDate)) ? woDate.substring(5, 7) : '--';
            $('#wo_number_preview').val([companyCode, branchCode, 'WO', fy, month, seq].join('/'));
        }

        function calcRow($row) {
            var qty = parseFloat($row.find('.qty-input').val()) || 0;
            var rate = parseFloat($row.find('.price-input').val()) || 0;
            var taxPct = parseFloat($row.find('.tax-input').val()) || 0;
            var gross = qty * rate;
            var taxValue = gross * (taxPct / 100);
            var net = gross + taxValue;
            $row.find('.gross-value').val(gross.toFixed(2));
            $row.find('.tax-value').val(taxValue.toFixed(2));
            $row.find('.net-value').val(net.toFixed(2));
            recalcTotals();
        }

        function calcChargeRow($row) {
            var amount = parseFloat($row.find('.charge-amount').val()) || 0;
            var taxPct = parseFloat($row.find('.charge-tax').val()) || 0;
            if (amount === 0) {
                taxPct = 0;
                $row.find('.charge-tax').val('0.00');
            }
            var taxAmount = amount * (taxPct / 100);
            var total = amount + taxAmount;
            $row.find('.charge-tax-val').val(taxAmount.toFixed(2));
            $row.find('.charge-total').val(total.toFixed(2));
            recalcTotals();
        }

        function calcMiscCharges() {
            var totalAmount = 0;
            var totalTaxAmount = 0;
            $('#wo-charges-body .wo-charge-row').each(function() {
                var amount = parseFloat($(this).find('.charge-amount').val()) || 0;
                var taxPct = parseFloat($(this).find('.charge-tax').val()) || 0;
                var taxAmount = amount * (taxPct / 100);
                totalAmount += amount;
                totalTaxAmount += taxAmount;
            });
            var miscTotal = totalAmount + totalTaxAmount;
            
            $('#misc_total_display').val(totalAmount.toFixed(2));
            $('#misc_charges_label').text('Misc. Charges');
            
            return { amount: totalAmount, taxAmount: totalTaxAmount, miscTotal: miscTotal };
        }

        function recalcTotals() {
            var itemGrossTotal = 0;
            var itemTaxTotal = 0;
            $('#wo-items-body .wo-item-row').each(function() {
                var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                var rate = parseFloat($(this).find('.price-input').val()) || 0;
                var taxPct = parseFloat($(this).find('.tax-input').val()) || 0;
                var gross = qty * rate;
                var tax = gross * (taxPct / 100);
                itemGrossTotal += gross;
                itemTaxTotal += tax;
            });
            var misc = calcMiscCharges();
            
            var totalTax = itemTaxTotal + misc.taxAmount; // Items tax + misc tax goes into SGST/CGST/IGST
            
            var vendorState = $('#vendor_id option:selected').attr('data-state') || '';
            var branchState = $('#branch_id option:selected').attr('data-state') || 'karnataka';
            var isIntraState = vendorState.trim().toLowerCase() === branchState.trim().toLowerCase();

            var sgst = 0;
            var cgst = 0;
            var igst = 0;

            if (vendorState && !isIntraState) {
                igst = totalTax;
                $('#col-sgst, #col-cgst').addClass('d-none');
                $('#col-igst').removeClass('d-none');
            } else {
                sgst = totalTax / 2;
                cgst = totalTax / 2;
                $('#col-sgst, #col-cgst').removeClass('d-none');
                $('#col-igst').addClass('d-none');
            }

            $('#total_value_display').val(itemGrossTotal.toFixed(2));
            $('#misc_total_display').val(misc.amount.toFixed(2));
            $('#sgst').val(sgst.toFixed(2));
            $('#cgst').val(cgst.toFixed(2));
            $('#igst').val(igst.toFixed(2));

            var grandTotal = itemGrossTotal + misc.amount + totalTax;
            $('#grand_total_display').val(grandTotal.toFixed(2));
        }

        $(document).on('change', '.charge-select', function() {
            var $this = $(this);
            var selectedValue = $this.val();
            
            var $opt = $this.find('option:selected');
            var $row = $this.closest('.wo-charge-row');
            var amountStr = $opt.attr('data-amount');
            if (amountStr !== undefined && amountStr !== '') {
                var amount = parseFloat(amountStr);
                if (!isNaN(amount) && amount > 0) {
                    $row.find('.charge-amount').val(amount.toFixed(2));
                }
            }
            calcChargeRow($row);
        });

        $(document).on('input', '.charge-amount, .charge-tax', function() {
            var $row = $(this).closest('.wo-charge-row');
            calcChargeRow($row);
        });

        $('#add-charge-row').on('click', function() {
            var $newRow = $($('#wo-charge-row-template').html().replace(/__INDEX__/g, chargeRowIndex));
            $('#wo-charges-body').append($newRow);
            initSearchableSelect($newRow.find('.charge-select'));
            chargeRowIndex++;
            recalcTotals();
        });

        $(document).on('click', '.remove-charge-row', function() {
            $(this).closest('.wo-charge-row').remove();
            reindexChargeRows();
            recalcTotals();
        });

        function reindexRows() {
            $('#wo-items-body .wo-item-row').each(function(i) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + i + ']'));
                    }
                });
            });
            rowIndex = $('#wo-items-body .wo-item-row').length;
        }

        function reindexChargeRows() {
            $('#wo-charges-body .wo-charge-row').each(function(i) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/charges\[\d+\]/, 'charges[' + i + ']'));
                    }
                });
            });
            chargeRowIndex = $('#wo-charges-body .wo-charge-row').length;
        }

        $(document).on('input', '.qty-input, .price-input, .tax-input', function() {
            var $row = $(this).closest('.wo-item-row');
            if ($row.length) calcRow($row); else recalcTotals();
        });

        $('#vendor_id, #branch_id').on('change', function() {
            recalcTotals();
        });

        $('#add-item-row').on('click', function() {
            var html = $('#wo-item-row-template').html().replace(/__INDEX__/g, rowIndex);
            var $newRow = $(html);
            $('#wo-items-body').append($newRow);
            initSearchableSelect($newRow.find('.unit-select'));
            rowIndex++;
            recalcTotals();
        });

        $(document).on('click', '.remove-item-row', function() {
            if ($('#wo-items-body .wo-item-row').length <= 1) {
                Swal.fire({ icon: 'info', title: 'At least one item is required' });
                return;
            }
            $(this).closest('.wo-item-row').remove();
            reindexRows();
            recalcTotals();
        });

        function refreshWoNumberPreview() {
            var branchId = String($('#branch_id').val() || '');
            var woDate = $('#wo_date').val();

            if (isEditWO) {
                if (woPreviewRequest && woPreviewRequest.abort) {
                    woPreviewRequest.abort();
                }
                $('#wo_number_preview').val(initialWoNumber);
                return;
            }

            applyLocalWoNumberPreview();

            var token = ++woPreviewToken;
            if (woPreviewRequest && woPreviewRequest.abort) {
                woPreviewRequest.abort();
            }

            woPreviewRequest = $.ajax({
                url: previewWoNumberUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    branch_id: branchId || '',
                    wo_date: woDate || '',
                    company_id: formCompanyId || ''
                }
            }).done(function(res) {
                if (token !== woPreviewToken) return;
                if (res.success && res.wo_number) {
                    $('#wo_number_preview').val(res.wo_number);
                }
            });
        }

        $('#branch_id').on('change', function() {
            refreshWoNumberPreview();
        });

        $('#wo_date').on('change', function() {
            if (!$('#date_of_commencement').val()) {
                $('#date_of_commencement').val($(this).val());
            }
            if (!$('#contract_period_from').val()) {
                $('#contract_period_from').val($(this).val());
            }
            refreshWoNumberPreview();
        });

        initSearchableSelect($('#vendor_id, #branch_id, #employee_master_id, #department_id, #payment_term_id, #shipping_detail_id'));
        initSearchableSelect($('#frequency'), {
            placeholder: 'None',
            allowClear: false
        });
        initSearchableSelect($('#wo-items-body .unit-select, #wo-charges-body .charge-select'));

        // Recalculate all rows on load
        $('#wo-items-body .wo-item-row').each(function() {
            calcRow($(this));
        });

        refreshWoNumberPreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>

