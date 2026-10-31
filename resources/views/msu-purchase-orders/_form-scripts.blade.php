<script>
(function() {

    function boot() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(boot, 50);
            return;
        }
        initPoForm(window.jQuery);
    }

    function initPoForm($) {
        var rowIndex = $('#po-items-body .po-item-row').length;
        var chargeRowIndex = $('#po-charges-body .po-charge-row').length;
        var previewPoNumberUrl = @json(route('msu-purchase-orders.preview-po-number'));
        var formCompanyId = @json($formCompanyId ?? session('global_company_id'));
        var defaultCompanyCode = @json($companyCode ?? 'MSULLP');
        var isEditPo = @json(isset($msuPurchaseOrder));
        var paymentTermsListUrl = @json(route('payment-terms.list-manage'));
        var paymentTermsBulkUrl = @json(route('payment-terms.bulk-save'));
        var miscChargesListUrl = @json(route('miscellaneous-charges.list-manage'));
        var miscChargesBulkUrl = @json(route('miscellaneous-charges.bulk-save'));
        var csrfToken = @json(csrf_token());
        var initialPoNumber = @json(isset($msuPurchaseOrder) ? $msuPurchaseOrder->po_number : '');

        var paymentTermsDeletedIds = [];
        var miscChargesDeletedIds = [];
        var paymentLoadSeq = 0;
        var miscLoadSeq = 0;

        function initSearchableSelect($el) {
            if (!$el || !$el.length || typeof $.fn.select2 !== 'function') return;
            $el.each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                var options = {
                    placeholder: 'Select',
                    allowClear: true,
                    width: '100%',
                    dropdownAutoWidth: false
                };
                if ($select.closest('.input-group').length) {
                    options.dropdownParent = $select.closest('.col, .card-body, form').first();
                }
                $select.select2(options);
            });
        }

        function showModal(el, retries) {
            if (!el) return;
            retries = retries == null ? 40 : retries;
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
                return;
            }
            if (retries > 0) {
                setTimeout(function() { showModal(el, retries - 1); }, 50);
                return;
            }
            $(el).addClass('show').css({ display: 'block' }).attr('aria-hidden', 'false');
            if (!$('.modal-backdrop').length) {
                $('body').append('<div class="modal-backdrop fade show"></div>');
            }
            $('body').addClass('modal-open');
        }

        function hideModal(el) {
            if (!el) return;
            if (window.bootstrap && bootstrap.Modal) {
                var instance = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                instance.hide();
                return;
            }
            $(el).removeClass('show').css({ display: 'none' }).attr('aria-hidden', 'true');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
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
            $('#po-charges-body .po-charge-row').each(function() {
                var amount = parseFloat($(this).find('.charge-amount').val()) || 0;
                var taxPct = parseFloat($(this).find('.charge-tax').val()) || 0;
                var taxAmount = amount * (taxPct / 100);
                totalAmount += amount;
                totalTaxAmount += taxAmount;
            });
            var miscTotal = totalAmount + totalTaxAmount;

            $('#misc_total_display').val(totalAmount.toFixed(2));
            $('#misc_tax_total_display').val(totalTaxAmount.toFixed(2));

            return { amount: totalAmount, taxAmount: totalTaxAmount, miscTotal: miscTotal };
        }

        function recalcTotals() {
            var itemGrossTotal = 0;
            var itemTaxTotal = 0;
            $('#po-items-body .po-item-row').each(function() {
                var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                var rate = parseFloat($(this).find('.price-input').val()) || 0;
                var taxPct = parseFloat($(this).find('.tax-input').val()) || 0;
                var gross = qty * rate;
                var tax = gross * (taxPct / 100);
                itemGrossTotal += gross;
                itemTaxTotal += tax;
            });
            var misc = calcMiscCharges();
            
            var totalTax = itemTaxTotal + misc.taxAmount; // Items tax + Misc tax goes into SGST/CGST/IGST
            
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
            $('#item_tax_total_display').val(itemTaxTotal.toFixed(2));
            $('#misc_total_display').val(misc.amount.toFixed(2));
            $('#misc_tax_total_display').val(misc.taxAmount.toFixed(2));
            $('#item_misc_total_display').val((itemGrossTotal + misc.amount).toFixed(2));
            $('#sgst').val(sgst.toFixed(2));
            $('#cgst').val(cgst.toFixed(2));
            $('#igst').val(igst.toFixed(2));

            var grandTotal = itemGrossTotal + misc.amount + totalTax;
            $('#grand_total_display').val(grandTotal.toFixed(2));
        }

        function findDatalistOption($list, val) {
            var $match = $();
            $list.find('option').each(function() {
                if (this.value === val) {
                    $match = $(this);
                    return false;
                }
            });
            return $match;
        }

        function findItemDatalistOption(val) {
            var $list = $('#po-item-options');
            var $match = findDatalistOption($list, val);
            if ($match.length || !val) {
                return $match;
            }

            $list.find('option').each(function() {
                var $opt = $(this);
                var code = String($opt.attr('data-code') || '');
                var fullLabel = String($opt.attr('data-label') || '');
                if (val === code || val === fullLabel) {
                    $match = $opt;
                    return false;
                }
            });
            if ($match.length) {
                return $match;
            }

            var prefixHits = [];
            $list.find('option').each(function() {
                var $opt = $(this);
                var fullLabel = String($opt.attr('data-label') || this.value);
                if (fullLabel.indexOf(val) === 0 || (this.value && val.indexOf(this.value) === 0)) {
                    prefixHits.push($opt);
                }
            });
            if (prefixHits.length === 1 && val.length >= 8) {
                return prefixHits[0];
            }

            return $();
        }

        $(document).on('input change', '.charge-autocomplete', function() {
            var $this = $(this);
            var val = $this.val();
            var $row = $this.closest('.po-charge-row');
            var $hidden = $row.find('.charge-select-hidden');
            var $errorMsg = $row.find('.charge-error');

            $this.attr('title', val || '');

            if (!val) {
                $hidden.val('');
                $errorMsg.addClass('d-none');
                $this.removeClass('is-invalid');
                calcChargeRow($row);
                return;
            }

            var $match = findDatalistOption($('#po-charge-options'), val);
            if ($match.length) {
                var chargeName = $match.attr('data-name') || val;
                var isDuplicate = false;
                $('.charge-select-hidden').not($hidden).each(function() {
                    if ($(this).val() === chargeName) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    $this.addClass('is-invalid');
                    $errorMsg.removeClass('d-none').text('This charge is already added.');
                    $hidden.val('');
                    return;
                }

                $this.removeClass('is-invalid');
                $errorMsg.addClass('d-none');
                $hidden.val(chargeName);

                var amountStr = $match.attr('data-amount');
                if (amountStr !== undefined && amountStr !== '') {
                    var amount = parseFloat(amountStr);
                    if (!isNaN(amount) && amount > 0) {
                        $row.find('.charge-amount').val(amount.toFixed(2));
                    }
                }
                calcChargeRow($row);
            } else {
                $hidden.val('');
            }
        });

        $(document).on('input', '.charge-amount, .charge-tax', function() {
            var $row = $(this).closest('.po-charge-row');
            calcChargeRow($row);
        });

        $('#add-charge-row').on('click', function() {
            var html = $('#po-charge-row-template').html().replace(/__INDEX__/g, chargeRowIndex);
            $('#po-charges-body').append(html);
            chargeRowIndex++;
            recalcTotals();
        });

        $(document).on('click', '.remove-charge-row', function() {
            $(this).closest('.po-charge-row').remove();
            reindexChargeRows();
            recalcTotals();
        });

        function reindexRows() {
            $('#po-items-body .po-item-row').each(function(i) {
                $(this).find('select, input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + i + ']'));
                    }
                });
            });
            rowIndex = $('#po-items-body .po-item-row').length;
        }

        function reindexChargeRows() {
            $('#po-charges-body .po-charge-row').each(function(i) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/charges\[\d+\]/, 'charges[' + i + ']'));
                    }
                });
            });
            chargeRowIndex = $('#po-charges-body .po-charge-row').length;
        }

        function escapeHtml(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function makeCode(description) {
            var code = String(description || '')
                .trim()
                .toUpperCase()
                .replace(/[^A-Z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
            if (!code) code = 'CODE';
            return code.substring(0, 50);
        }

        function ensureUniqueCodes(items) {
            var seen = {};
            items.forEach(function(item) {
                var base = item.code || makeCode(item.description);
                var code = base;
                var i = 1;
                while (seen[code]) {
                    var suffix = '_' + i;
                    code = (base.substring(0, Math.max(1, 50 - suffix.length)) + suffix);
                    i++;
                }
                seen[code] = true;
                item.code = code;
            });
            return items;
        }

        function paymentRowHtml(item) {
            item = item || {};
            var id = item.id ? String(item.id) : '';
            var paymentMode = item.payment_mode || '';
            var description = item.description || '';
            var code = item.code || '';
            var active = item.active === false ? false : true;
            return '' +
                '<tr class="manage-row" data-id="' + escapeHtml(id) + '">' +
                '<td><input type="text" class="form-control form-control-sm manage-payment-mode" value="' + escapeHtml(paymentMode) + '" placeholder="Cash / NEFT / UPI"></td>' +
                '<td><input type="text" class="form-control form-control-sm manage-description" value="' + escapeHtml(description) + '" placeholder="Payment description"></td>' +
                '<td><input type="text" class="form-control form-control-sm manage-code" value="' + escapeHtml(code) + '" placeholder="Auto if empty"></td>' +
                '<td class="text-center"><input type="checkbox" class="form-check-input manage-active"' + (active ? ' checked' : '') + '></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger manage-delete-row" title="Delete"><i class="bx bx-trash"></i></button></td>' +
                '</tr>';
        }

        function miscRowHtml(item) {
            item = item || {};
            var id = item.id ? String(item.id) : '';
            var description = item.description || '';
            var code = item.code || '';
            var amount = item.amount != null && item.amount !== '' ? parseFloat(item.amount).toFixed(2) : '0.00';
            var active = item.active === false ? false : true;
            return '' +
                '<tr class="manage-row" data-id="' + escapeHtml(id) + '">' +
                '<td><input type="text" class="form-control form-control-sm manage-description" value="' + escapeHtml(description) + '" placeholder="Description"></td>' +
                '<td><input type="text" class="form-control form-control-sm manage-code" value="' + escapeHtml(code) + '" placeholder="Auto if empty"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm manage-amount" value="' + escapeHtml(amount) + '"></td>' +
                '<td class="text-center"><input type="checkbox" class="form-check-input manage-active"' + (active ? ' checked' : '') + '></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger manage-delete-row" title="Delete"><i class="bx bx-trash"></i></button></td>' +
                '</tr>';
        }

        function collectPaymentRows($tbody) {
            var items = [];
            $tbody.find('tr.manage-row').each(function() {
                var $row = $(this);
                var paymentMode = $.trim($row.find('.manage-payment-mode').val() || '');
                var description = $.trim($row.find('.manage-description').val() || '');
                var code = $.trim($row.find('.manage-code').val() || '');
                if (!paymentMode || !description) {
                    return;
                }
                if (!code) {
                    code = makeCode(paymentMode + '_' + description);
                    $row.find('.manage-code').val(code);
                }
                var payload = {
                    payment_mode: paymentMode,
                    description: description,
                    code: code,
                    active: $row.find('.manage-active').is(':checked')
                };
                var rawId = $.trim($row.attr('data-id') || '');
                if (rawId) payload.id = parseInt(rawId, 10);
                items.push(payload);
            });
            return ensureUniqueCodes(items);
        }

        function collectMiscRows($tbody) {
            var items = [];
            $tbody.find('tr.manage-row').each(function() {
                var $row = $(this);
                var description = $.trim($row.find('.manage-description').val() || '');
                var code = $.trim($row.find('.manage-code').val() || '');
                var amountRaw = $.trim($row.find('.manage-amount').val() || '');
                if (!description) {
                    return;
                }
                if (!code) {
                    code = makeCode(description);
                    $row.find('.manage-code').val(code);
                }
                var amount = amountRaw === '' ? 0 : parseFloat(amountRaw);
                if (isNaN(amount) || amount < 0) amount = 0;
                $row.find('.manage-amount').val(amount.toFixed(2));
                var payload = {
                    description: description,
                    code: code,
                    amount: amount,
                    active: $row.find('.manage-active').is(':checked')
                };
                var rawId = $.trim($row.attr('data-id') || '');
                if (rawId) payload.id = parseInt(rawId, 10);
                items.push(payload);
            });
            return ensureUniqueCodes(items);
        }

        function fillSelect($select, options, selectedId, placeholder) {
            var current = selectedId != null ? String(selectedId) : String($select.val() || '');
            $select.empty().append('<option value="" data-amount="0">' + placeholder + '</option>');
            (options || []).forEach(function(opt) {
                var amount = parseFloat(opt.amount || 0);
                var label = opt.label || ((opt.code ? opt.code + ' - ' : '') + (opt.description || ''));
                if (amount > 0) label += ' (' + amount.toFixed(2) + ')';
                var $opt = $('<option></option>')
                    .attr('value', opt.id)
                    .attr('data-amount', amount.toFixed(2))
                    .text(label);
                if (current && String(opt.id) === current) $opt.prop('selected', true);
                $select.append($opt);
            });
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change');
            }
            if ($select.is('#miscellaneous_charge_id')) syncMiscAmountFromSelect();
        }

        function syncMiscAmountFromSelect() {
            var $select = $('#miscellaneous_charge_id');
            if (!$select.length) return;
        }

        function loadManageRows(type) {
            var isPayment = type === 'payment';
            var $tbody = $(isPayment ? '#payment-terms-manage-table tbody' : '#misc-charges-manage-table tbody');
            var listUrl = isPayment ? paymentTermsListUrl : miscChargesListUrl;
            var seq = isPayment ? ++paymentLoadSeq : ++miscLoadSeq;
            var colspan = isPayment ? 5 : 5;

            if (isPayment) paymentTermsDeletedIds = [];
            else miscChargesDeletedIds = [];

            $tbody.html('<tr><td colspan="' + colspan + '" class="text-center text-muted">Loading...</td></tr>');

            $.ajax({
                url: listUrl,
                type: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' }
            }).done(function(res) {
                if ((isPayment ? paymentLoadSeq : miscLoadSeq) !== seq) return;
                $tbody.empty();
                var rows = (res && res.data) ? res.data : [];
                if (!rows.length) {
                    $tbody.append(isPayment ? paymentRowHtml({ active: true }) : miscRowHtml({ active: true, amount: 0 }));
                    return;
                }
                rows.forEach(function(row) {
                    $tbody.append(isPayment ? paymentRowHtml(row) : miscRowHtml(row));
                });
                $tbody.find('.manage-code').each(function() {
                    if ($.trim($(this).val() || '')) {
                        $(this).data('manual', true);
                    }
                });
            }).fail(function(xhr) {
                if ((isPayment ? paymentLoadSeq : miscLoadSeq) !== seq) return;
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load records.';
                $tbody.html('<tr><td colspan="' + colspan + '" class="text-center text-danger">' + escapeHtml(msg) + '</td></tr>');
            });
        }

        function saveManageModal(type) {
            var isPayment = type === 'payment';
            var $tbody = $(isPayment ? '#payment-terms-manage-table tbody' : '#misc-charges-manage-table tbody');
            var $saveBtn = $(isPayment ? '#payment-terms-save-btn' : '#misc-charges-save-btn');

            if (!$tbody.find('tr.manage-row').length) {
                Swal.fire({ icon: 'info', title: 'Please wait for the list to finish loading.' });
                return;
            }

            var items = isPayment ? collectPaymentRows($tbody) : collectMiscRows($tbody);
            if (!items.length) {
                Swal.fire({ icon: 'warning', title: 'Enter at least one Description, then click Save.' });
                return;
            }

            $saveBtn.prop('disabled', true);

            $.ajax({
                url: isPayment ? paymentTermsBulkUrl : miscChargesBulkUrl,
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                data: JSON.stringify({
                    items: items,
                    deleted_ids: isPayment ? paymentTermsDeletedIds : miscChargesDeletedIds
                })
            }).done(function(res) {
                if (!res || !res.success) {
                    Swal.fire({ icon: 'error', title: (res && res.message) ? res.message : 'Save failed' });
                    return;
                }
                if (isPayment) {
                    fillSelect($('#payment_term_id'), res.data, $('#payment_term_id').val(), 'Select Payment Term');
                    hideModal(document.getElementById('paymentTermsManageModal'));
                } else {
                    fillSelect($('#miscellaneous_charge_id'), res.data, $('#miscellaneous_charge_id').val(), 'Select Misc Charge');
                    hideModal(document.getElementById('miscChargesManageModal'));
                }
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
            }).fail(function(xhr) {
                var msg = 'Failed to save';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    if (xhr.responseJSON.errors) {
                        var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstKey && xhr.responseJSON.errors[firstKey] && xhr.responseJSON.errors[firstKey][0]) {
                            msg = xhr.responseJSON.errors[firstKey][0];
                        }
                    }
                }
                Swal.fire({ icon: 'error', title: msg });
            }).always(function() {
                $saveBtn.prop('disabled', false);
            });
        }

        $(document).on('input', '.qty-input, .price-input, .tax-input', function() {
            var $row = $(this).closest('.po-item-row');
            if ($row.length) calcRow($row); else recalcTotals();
        });

        $('#vendor_id, #branch_id').on('change', function() {
            recalcTotals();
        });

        $(document).on('input', '.item-autocomplete', function() {
            var $this = $(this);
            var val = $this.val();
            var $row = $this.closest('.po-item-row');
            var $hidden = $row.find('.item-select-hidden');
            var $errorMsg = $this.siblings('.item-error');

            if (!val) {
                $hidden.val('');
                $errorMsg.addClass('d-none');
                $this.removeClass('is-invalid');
                $this.attr('title', '');
                $row.find('.hsn-input, input[name*="[hsn]"]').prop('readonly', false).removeAttr('tabindex').val('');
                $row.find('.price-input').prop('readonly', false).removeAttr('tabindex').val('');
                $row.find('.tax-input').prop('readonly', false).removeAttr('tabindex').val('');
                calcRow($row);
                return;
            }

            var match = findItemDatalistOption(val);
            
            if (match.length) {
                var id = match.data('id');
                var isDuplicate = false;
                
                $('.item-select-hidden').not($hidden).each(function() {
                    if ($(this).val() == id) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    $this.addClass('is-invalid');
                    $errorMsg.removeClass('d-none').text('This item is already added.');
                    $hidden.val('');
                } else {
                    $this.removeClass('is-invalid');
                    $errorMsg.addClass('d-none');
                    $hidden.val(id);
                    var shortVal = match.get(0).value;
                    if ($this.val() !== shortVal) {
                        $this.val(shortVal);
                    }
                    $this.attr('title', match.attr('data-label') || shortVal);
                    
                    var hsnData = match.data('hsn');
                    var rateData = match.data('rate');
                    var taxData = match.data('tax');
                    
                    var $hsnInput = $row.find('.hsn-input, input[name*="[hsn]"]');
                    var $priceInput = $row.find('.price-input');
                    var $taxInput = $row.find('.tax-input');

                    $hsnInput.val(hsnData || '');
                    if (hsnData) {
                        $hsnInput.prop('readonly', true).attr('tabindex', '-1');
                    } else {
                        $hsnInput.prop('readonly', false).removeAttr('tabindex');
                    }
                    
                    if (rateData !== '' && rateData != null) {
                        $priceInput.val(parseFloat(rateData).toFixed(2)).prop('readonly', true).attr('tabindex', '-1');
                    } else {
                        $priceInput.val('').prop('readonly', false).removeAttr('tabindex');
                    }

                    if (taxData !== '' && taxData != null) {
                        $taxInput.val(parseFloat(taxData).toFixed(2)).prop('readonly', true).attr('tabindex', '-1');
                    } else {
                        $taxInput.val('').prop('readonly', false).removeAttr('tabindex');
                    }

                    calcRow($row);
                }
            } else {
                $hidden.val('');
                $row.find('.hsn-input, input[name*="[hsn]"]').prop('readonly', false).removeAttr('tabindex');
                $row.find('.price-input').prop('readonly', false).removeAttr('tabindex');
                $row.find('.tax-input').prop('readonly', false).removeAttr('tabindex');
            }
        });

        $(document).on('input', '.manage-description', function() {
            var $row = $(this).closest('tr');
            var $code = $row.find('.manage-code');
            if ($code.data('manual')) return;
            var description = $.trim($(this).val() || '');
            $code.val(description ? makeCode(description) : '');
        });

        $(document).on('input', '.manage-code', function() {
            $(this).data('manual', true);
        });

        $('#add-item-row').on('click', function() {
            var html = $('#po-item-row-template').html().replace(/__INDEX__/g, rowIndex);
            var $newRow = $(html);
            $('#po-items-body').append($newRow);
            rowIndex++;
            recalcTotals();
        });

        $(document).on('click', '.remove-item-row', function() {
            if ($('#po-items-body .po-item-row').length <= 1) {
                Swal.fire({ icon: 'info', title: 'At least one item is required' });
                return;
            }
            $(this).closest('.po-item-row').remove();
            reindexRows();
            recalcTotals();
        });

        var poPreviewRequest = null;
        var poPreviewToken = 0;

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

        function applyLocalPoNumberPreview() {
            var $opt = $('#branch_id option:selected');
            var branchId = String($('#branch_id').val() || '');
            var branchCode = branchId
                ? normalizeBranchCode($opt.attr('data-code') || '')
                : 'BRN';
            var companyCode = branchId
                ? ($opt.attr('data-company-code') || defaultCompanyCode || 'MSULLP')
                : (defaultCompanyCode || 'MSULLP');
            var poDate = $('#po_date').val() || '';
            var current = String($('#po_number_preview').val() || '');
            var parts = current.split('/');
            var seq = parts.length ? parts[parts.length - 1] : '';
            if (!/^\d+$/.test(seq)) {
                seq = '0001';
            }
            var fy = financialYearCode(poDate);
            var month = (poDate && /^\d{4}-\d{2}-\d{2}$/.test(poDate)) ? poDate.substring(5, 7) : '--';
            $('#po_number_preview').val([companyCode, branchCode, 'PO', fy, month, seq].join('/'));
        }

        function refreshPoNumberPreview() {
            var branchId = String($('#branch_id').val() || '');
            var poDate = $('#po_date').val();

            if (isEditPo) {
                if (poPreviewRequest && poPreviewRequest.abort) {
                    poPreviewRequest.abort();
                }
                $('#po_number_preview').val(initialPoNumber);
                return;
            }

            applyLocalPoNumberPreview();

            var token = ++poPreviewToken;
            if (poPreviewRequest && poPreviewRequest.abort) {
                poPreviewRequest.abort();
            }

            poPreviewRequest = $.ajax({
                url: previewPoNumberUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    branch_id: branchId || '',
                    po_date: poDate || '',
                    company_id: formCompanyId || ''
                }
            }).done(function(res) {
                if (token !== poPreviewToken) return;
                if (res.success && res.po_number) {
                    $('#po_number_preview').val(res.po_number);
                }
            });
        }

        $('#branch_id').on('change', function() {
            refreshPoNumberPreview();
        });

        $('#po_date').on('change', function() {
            if (!$('#date_of_commencement').val()) {
                $('#date_of_commencement').val($(this).val());
            }
            refreshPoNumberPreview();
        });

        // Recalculate all rows on load
        $('#po-items-body .po-item-row').each(function() {
            calcRow($(this));
        });
        $('#po-charges-body .po-charge-row').each(function() {
            calcChargeRow($(this));
        });

        // Fill HSN / rate / tax defaults from item when label is already set
        $('#po-items-body .item-autocomplete').each(function() {
            if ($(this).val() && !$(this).closest('.po-item-row').find('.item-select-hidden').val()) {
                $(this).trigger('input');
            }
        });

        refreshPoNumberPreview();

        $('#manage-payment-terms-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showModal(document.getElementById('paymentTermsManageModal'));
            loadManageRows('payment');
        });

        $('#manage-misc-charges-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showModal(document.getElementById('miscChargesManageModal'));
            loadManageRows('misc');
        });

        $('#payment-terms-add-row').on('click', function() {
            $('#payment-terms-manage-table tbody').append(paymentRowHtml({ active: true }));
        });

        $('#misc-charges-add-row').on('click', function() {
            $('#misc-charges-manage-table tbody').append(miscRowHtml({ active: true, amount: 0 }));
        });

        $(document).on('click', '#payment-terms-manage-table .manage-delete-row', function() {
            var $row = $(this).closest('tr');
            var id = $.trim($row.attr('data-id') || '');
            if (id) paymentTermsDeletedIds.push(parseInt(id, 10));
            $row.remove();
            if (!$('#payment-terms-manage-table tbody tr.manage-row').length) {
                $('#payment-terms-manage-table tbody').append(paymentRowHtml({ active: true }));
            }
        });

        $(document).on('click', '#misc-charges-manage-table .manage-delete-row', function() {
            var $row = $(this).closest('tr');
            var id = $.trim($row.attr('data-id') || '');
            if (id) miscChargesDeletedIds.push(parseInt(id, 10));
            $row.remove();
            if (!$('#misc-charges-manage-table tbody tr.manage-row').length) {
                $('#misc-charges-manage-table tbody').append(miscRowHtml({ active: true, amount: 0 }));
            }
        });

        $('#payment-terms-save-btn').on('click', function(e) {
            e.preventDefault();
            saveManageModal('payment');
        });

        $('#misc-charges-save-btn').on('click', function(e) {
            e.preventDefault();
            saveManageModal('misc');
        });

        initSearchableSelect($('#vendor_id, #branch_id, #employee_master_id, #department_id, #payment_term_id, #shipping_detail_id'));

        $('#po-items-body .po-item-row').each(function() { calcRow($(this)); });
        syncMiscAmountFromSelect();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
