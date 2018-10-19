//jQuery.noConflict();
function validateFields(){
    var returnValue = false;
    (function ($) {
        var rule_order = $('#rule_order');
        if(rule_order.val() != ''){
            rule_order.removeClass('invalid-field');
            rule_order.next('.error').remove();
            returnValue = true;
        } else {
            $('a.general_tab').trigger('click');
            rule_order.addClass('invalid-field');
            rule_order.next('.error').remove();
            rule_order.after('<span class="error">'+woo_discount_localization.please_fill_this_field+'</span>');
            returnValue = false;
        }

    })(jQuery);
    return returnValue;
}

function trigger_woocommerce_tooltip(){
    jQuery( '.tips, .help_tip, .woocommerce-help-tip' ).tipTip( {
        'attribute': 'data-tip',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 200
    } );
}
(function ($) {
    jQuery(document).ready(function () {
        // Tooltips
        trigger_woocommerce_tooltip();

        var ajax_url = $('#ajax_path').val();
        var admin_url = $('#admin_path').val();
        var pro_suffix = $('#pro_suffix').val();
        var is_pro = $('#is_pro').val();
        // $(".datepicker").datepicker();
        $(".datepicker").datetimepicker({
            //format: "dd MM yyyy - hh:ii",
            format: "mm/dd/yyyy hh:ii",
            autoclose: true,
            todayBtn: true,
            pickerPosition: "top-right"
        });

        //--------------------------------------------------------------------------------------------------------------
        //--------------------------------------------PRICING RULES-----------------------------------------------------
        //--------------------------------------------------------------------------------------------------------------

        // Manage Customer Selection ON-LOAD
        var user_selection = $('#apply_customer').val();
        if (user_selection == 'only_given') {
            $('#user_list').css('display', 'block');
        } else {
            $('#user_list').css('display', 'none');
        }

        // Saving Rule.
        $('#savePriceRule').on('click', function (event) {
            var validate = validateFields();

            if(validate == false){
                return false;
            }
            var form = $('#form_price_rule').serialize();
            var current = $(this);
            var rule_id = $('#rule_id').val();
            var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
            event.preventDefault();
            if ($('#rule_name').val() == '') {
                alert(woo_discount_localization.please_enter_the_rule_name);
            } else {
                current.val(woo_discount_localization.saving);
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: {action: 'savePriceRule', data: form},
                    beforeSend: function() {
                        loader.show();
                    },
                    complete: function() {
                        loader.hide();
                    },
                    success: function () {
                        // After Status Changed.
                        resizeChart = setTimeout(function () {
                            current.val(woo_discount_localization.save_rule);
                        }, 300);
                        // Reset, if its New Form.
                        if (rule_id == 0) {
                            $('#form_price_rule')[0].reset();
                            window.location.replace(admin_url);
                        }
                        adminNotice();
                    }

                });
            }
        });

        // License key check
        $('#woo-disc-license-check').on('click', function (event) {
            var license_key = $('#woo-disc-license-key');
            var resp_msg = $('#woo-disc-license-check-msg');
            if(license_key.val() == ''){
                license_key.addClass('invalid-field');
                resp_msg.html('<div class="notice-message error inline notice-error notice-alt">'+woo_discount_localization.please_enter_a_key+'</div>');
                return false;
            }else{
                license_key.removeClass('invalid-field');
                resp_msg.html('');
            }
            
            var form = $('#discount_config').serialize();
            var current = $(this);
            
            event.preventDefault();

            current.removeClass('button-primary');
            current.addClass('button-secondary');
            current.val(woo_discount_localization.saving);
            $('.license-success, .license-failed').hide();
            var license_chk_req = $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {action: 'forceValidateLicenseKey', data: form},
                success: function () {
                    resizeChart = setTimeout(function () {
                        current.addClass('button-primary');
                        current.removeClass('button-secondary');
                        current.val('Validate');
                    }, 300);
                    
                    //adminNotice();
                    // display a success message
                }
            });
            license_chk_req.done(function( resp ) {
                    
                   response = JSON.parse(resp);
                    if (response['error']) {
                        resp_msg.html('<div class="notice-message error inline notice-error notice-alt">'+response['error']+'</div>');
                    } else if( response['success']){
                        resp_msg.html('<div class="notice-message success inline notice-success notice-alt">'+response['success']+'</div>');
                    }
                    
                });
        });


        // Adding New Discount Range.
        $('#addNewDiscountRange').on('click', function () {
            var count = $('.discount_rule_list').length + 1;
            if (is_pro) {
                var form = '<div class="discount_rule_list"> <div class="form-group"><label>'+woo_discount_localization.min_quantity+' <input type="text" name="discount_range[' + count + '][min_qty]" class="form-control" value="" placeholder="'+woo_discount_localization.place_holder_ex_1+'"></label>' +
                    '<label>'+woo_discount_localization.max_quantity+' <input type="text" name="discount_range[' + count + '][max_qty]" class="form-control" value="" placeholder="'+woo_discount_localization.place_holder_ex_50+'"> </label> <label>'+woo_discount_localization.adjustment_type+'<select class="form-control price_discount_type" name="discount_range[' + count + '][discount_type]"> ' +
                    '<option value="percentage_discount"> '+woo_discount_localization.percentage_discount+' </option> <option value="price_discount">'+woo_discount_localization.price_discount+' </option> <option value="product_discount">'+woo_discount_localization.product_discount+' </option> </select></label> <label><span class="hide-for-product-discount">'+woo_discount_localization.value_text+'</span>' +
                    '<input type="text" name="discount_range[' + count + '][to_discount]" class="form-control price_discount_amount" value="" placeholder="'+woo_discount_localization.place_holder_ex_50+'"> ';
                form += '<div class="price_discount_product_list_con hide">' +
                    ' '+woo_discount_localization.apply_for+' <select class="selectpicker discount_product_option" name="discount_range['+count+'][discount_product_option]"><option value="all">'+woo_discount_localization.all_selected+'</option><option value="same_product">'+woo_discount_localization.same_product+'</option><option value="any_cheapest">'+woo_discount_localization.any_one_cheapest_from_selected+'</option><option value="any_cheapest_from_all">'+woo_discount_localization.any_one_cheapest_from_all_products+'</option>' +
                    '<option value="more_than_one_cheapest_from_cat">'+woo_discount_localization.more_than_one_cheapest_from_selected_category+'</option><option value="more_than_one_cheapest">'+woo_discount_localization.more_than_one_cheapest_from_selected+'</option><option value="more_than_one_cheapest_from_all">'+woo_discount_localization.more_than_one_cheapest_from_all+'</option>' +
                    '</select>';
                form += '<div class="discount_product_option_bogo_con">';
                form += ' <label> '+woo_discount_localization.free_quantity+' <span class="woocommerce-help-tip" data-tip="'+woo_discount_localization.number_of_quantities_in_each_products+'"></span> <input type="text" name="discount_range['+count+'][discount_bogo_qty]" class="form-control" value="" placeholder="'+woo_discount_localization.place_holder_ex_1+'" /></label>';
                form += '</div>';
                form += '<div class="discount_product_option_more_cheapest_con hide">';
                form += '<select class="selectpicker discount_product_item_count_type" name="discount_range['+count+'][discount_product_item_type]">';
                form += '<option value="dynamic">'+woo_discount_localization.dynamic_item_count+'</option>';
                form += '<option value="static">'+woo_discount_localization.fixed_item_count+'</option>';
                form += '</select>';
                form += '<span class="woocommerce-help-tip" data-tip="'+woo_discount_localization.fixed_item_count_tooltip+'"></span>';
                form += ' <label class="discount_product_items_count_field hide"> '+woo_discount_localization.item_count+' <span class="woocommerce-help-tip" data-tip="'+woo_discount_localization.discount_number_of_item_tooltip+'"></span><input type="text" name="discount_range['+count+'][discount_product_items]" class="form-control discount_product_items_count_field hide" value="" placeholder="'+woo_discount_localization.place_holder_ex_1+'" /></label>';
                form += ' <label> '+woo_discount_localization.item_quantity+' <span class="woocommerce-help-tip" data-tip="'+woo_discount_localization.discount_number_of_each_item_tooltip+'"></span><input type="text" name="discount_range['+count+'][discount_product_qty]" class="form-control" value="" placeholder="'+woo_discount_localization.place_holder_ex_1+'" /></label>';
                form += '</div>';
                form += '<div class="discount_product_option_list_con">';
                if($('#flycart_wdr_woocommerce_version').val() == 2){
                    form += ' <input type="hidden" class="wc-product-search" style="min-width: 250px" data-multiple="true" name="discount_range[' + count + '][discount_product][]" data-placeholder="'+woo_discount_localization.place_holder_search_for_products+'" data-action="woocommerce_json_search_products_and_variations" data-selected=""/>';
                } else {
                    form += ' <select class="wc-product-search" multiple="multiple" style="min-width: 250px" name="discount_range[' + count + '][discount_product][]" data-placeholder="'+woo_discount_localization.place_holder_search_for_products+'" data-action="woocommerce_json_search_products_and_variations"></select>'
                }
                form += '</div>';
                form += '<div class="discount_category_option_list_con hide">';
                form += ' <select class="category_list selectpicker" multiple title="'+woo_discount_localization.none_selected+'" name="discount_range[' + count + '][discount_category][]">';
                $("#category_list select.category_list option").each(function()
                {
                    form += '<option value="'+$(this).val()+'">'+$(this).html()+'</option>';
                });
                form += '</select>';
                form += '</div>';
                form += '<div class="discount_product_percent_con">';
                form += ' '+woo_discount_localization.and_text+' <select class="selectpicker discount_product_discount_type" name="discount_range['+ count +'][discount_product_discount_type]"><option value="">'+woo_discount_localization.percent_100+'</option><option value="limited_percent">'+woo_discount_localization.limited_percent+'</option></select>';
                form += '<span class="discount_product_percent_field"> <input type="text" name="discount_range['+count+'][discount_product_percent]" class="discount_product_percent_field" value="" placeholder="'+woo_discount_localization.place_holder_ex_10+'" /><span class="woocommerce-help-tip" data-tip="'+woo_discount_localization.percentage_tooltip+'"></span></span> '+woo_discount_localization.as_discount;
                form += '</div>';
                form += '</div>';
                form += '</label> <label><a href=javascript:void(0) class="btn btn-danger form-control remove_discount_range">'+woo_discount_localization.remove_text+'</a></label> </div> </div>';
            } else {
                var form = '<div class="discount_rule_list"> <div class="form-group"><label>'+woo_discount_localization.min_quantity+' <input type="text" name="discount_range[' + count + '][min_qty]" class="form-control" value="" placeholder="'+woo_discount_localization.place_holder_ex_1+'"></label>' +
                    '<label>'+woo_discount_localization.max_quantity+' <input type="text" name="discount_range[' + count + '][max_qty]" class="form-control" value="" placeholder="'+woo_discount_localization.place_holder_ex_50+'"> </label> <label>'+woo_discount_localization.adjustment_type+'<select class="form-control price_discount_type" name="discount_range[' + count + '][discount_type]"> ' +
                    '<option value="percentage_discount"> '+woo_discount_localization.percentage_discount+' </option> <option disabled>'+woo_discount_localization.price_discount+' <b>' + pro_suffix + '</b> </option> <option disabled>'+woo_discount_localization.product_discount+' <b>' + pro_suffix + '</b> </option> </select></label> <label>'+woo_discount_localization.value_text+' ' +
                    '<input type="text" name="discount_range[' + count + '][to_discount]" class="form-control price_discount_amount" value="" placeholder="'+woo_discount_localization.place_holder_ex_50+'"> ';
                form += '<div class="price_discount_product_list_con hide"><select class="product_list selectpicker price_discount_product_list" multiple title="'+woo_discount_localization.none_selected+'" name="discount_range[' + count + '][discount_product][]">';
                form += '<option>'+woo_discount_localization.none_text+'</option>';
                form += '</select></div>';
                form += '</label> <label><a href=javascript:void(0) class="btn btn-danger form-control remove_discount_range">'+woo_discount_localization.remove_text+'</a> </label></div> </div>';
            }
            $('#discount_rule_list').append(form);
            $('.product_list,.selectpicker').selectpicker('refresh');
            $('.wc-product-search').trigger( 'wc-enhanced-select-init' );
            $('select.discount_product_discount_type').trigger('change');
            // Tooltips
            trigger_woocommerce_tooltip();
        });

        // Removing Discount Rule.
        $(document).on('click', '.remove_discount_range', function () {
            var confirm_delete = confirm(woo_discount_localization.are_you_sure_to_remove_this);
            if (confirm_delete) {
                $(this).closest('.discount_rule_list').remove();
            }
        });

        // Enabling and Disabling the Status of the Rule.
        $('.manage_status').on('click', function (event) {
            event.preventDefault();
            var current = $(this);
            var id = $(this).attr('id');
            id = id.replace('state_', '');
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {action: 'UpdateStatus', id: id, from: 'pricing-rules'},
                success: function (status) {
                    // After Status Changed.
                    if (status == 'Disable') {
                        current.removeClass('btn-success');
                        current.addClass('btn-warning');
                        current.html(woo_discount_localization.enable_text);
                    } else if (status == 'Publish') {
                        current.addClass('btn-success');
                        current.removeClass('btn-warning');
                        current.html(woo_discount_localization.disable_text);
                    }
                }

            });
        });

        // Remove Rule.
        $('.delete_rule').on('click', function (event) {
            event.preventDefault();
            var current = $(this);
            var id = $(this).attr('id');
            id = id.replace('delete_', '');
            var confirm_delete = confirm(woo_discount_localization.are_you_sure_to_remove);
            if (confirm_delete) {
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: {action: 'RemoveRule', id: id, from: 'pricing-rules'},
                    success: function () {
                        // After Removed.
                        current.closest('tr').remove();
                        location.reload(true);
                    }
                });
            }
        });

        $('#restriction_block').hide();
        $('#discount_block').hide();

        $('.general_tab').on('click', function () {
            $('#general_block').show();
            $('#restriction_block').hide();
            $('#discount_block').hide();
            makeActiveForSelectedTab($("a.general_tab"));
        });
        $('.restriction_tab').on('click', function () {
            if(validateFields() == true){
                $('#general_block').hide();
                $('#restriction_block').show();
                $('#discount_block').hide();
                makeActiveForSelectedTab($(".restriction_tab"));
            }
        });
        $('.discount_tab').on('click', function () {
            $('#general_block').hide();
            $('#restriction_block').hide();
            $('#discount_block').show();
            makeActiveForSelectedTab($(".discount_tab"));
        });



        // Manage the Type of Apply.
        $('#apply_to').on('change', function () {
            var option = $(this).val();
            $('#cumulative_for_products_cont').hide();
            if (option == 'specific_products') {
                $('#product_list').css('display', 'block');
                $('#category_list').css('display', 'none');
                $('#product_attributes_list').css('display', 'none');
                $('#product_exclude_list').hide();
                $('#cumulative_for_products_cont').show();
            } else if (option == 'specific_category') {
                $('#product_list').css('display', 'none');
                $('#product_attributes_list').css('display', 'none');
                $('#category_list').css('display', 'block');
                $('#product_exclude_list').show();
            } else if (option == 'specific_attribute') {
                $('#product_list').css('display', 'none');
                $('#category_list').css('display', 'none');
                $('#product_attributes_list').css('display', 'block');
                $('#product_exclude_list').show();
            } else {
                $('#product_list').css('display', 'none');
                $('#category_list').css('display', 'none');
                $('#product_attributes_list').css('display', 'none');
                $('#product_exclude_list').show();
                $('#cumulative_for_products_cont').show();
            }
        });
        $('#apply_to').trigger('change');

        // Manage the Customer.
        $('#apply_customer').on('change', function () {
            var option = $(this).val();
            if (option == 'only_given') {
                $('#user_list').show();
            } else {
                $('#user_list').hide();
            }
        });
        $('#coupon_option_price_rule').on('change', function () {
            var option = $(this).val();
            if (option == 'none') {
                $('.coupons_to_apply_price_rule_con').hide();
            } else {
                $('.coupons_to_apply_price_rule_con').show();
            }
        });
        $('#coupon_option_price_rule').trigger('change');

        $('#subtotal_option_price_rule').on('change', function () {
            var option = $(this).val();
            if (option == 'none') {
                $('.subtotal_to_apply_price_rule_con').hide();
            } else {
                $('.subtotal_to_apply_price_rule_con').show();
            }
        });
        $('#subtotal_option_price_rule').trigger('change');

        $('#show_discount_table').on('change', function () {
            var option = $(this).val();
            if (option == 'show') {
                $('.discount_table_options').show();
            } else {
                $('.discount_table_options').hide();
            }
        });
        $('#show_discount_table').trigger('change');

        $('#message_on_apply_cart_discount').on('change', function () {
            var option = $(this).val();
            if (option == 'yes') {
                $('.message_on_apply_cart_discount_options').show();
            } else {
                $('.message_on_apply_cart_discount_options').hide();
            }
        });
        $('#message_on_apply_cart_discount').trigger('change');

        $('#message_on_apply_price_discount').on('change', function () {
            var option = $(this).val();
            if (option == 'yes') {
                $('.message_on_apply_price_discount_options').show();
            } else {
                $('.message_on_apply_price_discount_options').hide();
            }
        });
        $('#message_on_apply_price_discount').trigger('change');

        $(document).on('keyup', '.rule_descr', function () {
            var value = $(this).val();
            value = '| ' + value;
            var id = $(this).attr('id');
            id = id.replace('rule_descr_', '');
            $('#rule_label_' + id).html(value);
        });

        //--------------------------------------------------------------------------------------------------------------
        //-----------------------------------------------CART RULES-----------------------------------------------------
        //--------------------------------------------------------------------------------------------------------------

        $(document).on('click', '#add_cart_rule', function () {
            var count = $('.cart_rules_list').length;
            var product_list = '';
            var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {action: 'loadProductSelectBox', name: 'discount_rule['+count+'][purchase_history_products]'},
                beforeSend: function() {
                    loader.show();
                },
                complete: function() {
                    loader.hide();
                },
                success: function (response) {
                    product_list = response;
                    $('#purchase_history_products_list_'+count).html(product_list);
                    $('.wc-product-search').trigger( 'wc-enhanced-select-init' );
                }
            });

            // Cloning the List.
            var user_list = $('#cart_user_list_0 > option').clone();
            var category_list = $('#cart_category_list_0 > option').clone();
            var roles_list = $('#cart_roles_list_0 > option').clone();
            var country_list = $('#cart_countries_list_0 > option').clone();
            var order_status_list = $('#order_status_list_0 > option').clone();
            if (is_pro) {
                var form = '<div class="cart_rules_list row"> <div class="col-md-3 form-group"> <label>'+woo_discount_localization.type_text+' <select class="form-control cart_rule_type" id="cart_condition_type_' + count + '" name="discount_rule[' + count + '][type]"> <optgroup label="'+woo_discount_localization.cart_subtotal+'"><option value="subtotal_least" selected="selected">'+woo_discount_localization.subtotal_at_least+'</option><option value="subtotal_less">'+woo_discount_localization.subtotal_less_than+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.cart_item_count+'"><option value="item_count_least">'+woo_discount_localization.number_of_line_items_in_cart_at_least+'</option><option value="item_count_less">'+woo_discount_localization.number_of_line_items_in_cart_less_than+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.quantity_sum+'"><option value="quantity_least">'+woo_discount_localization.total_number_of_quantities_in_cart_at_least+'</option><option value="quantity_less">'+woo_discount_localization.total_number_of_quantities_in_cart_less_than+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.categories_in_cart+'">' +
                    '<option value="categories_in">'+woo_discount_localization.categories_in_cart+'</option>' +
                    '<option value="atleast_one_including_sub_categories">'+woo_discount_localization.atleast_one_including_sub_categories+'</option>' +
                    '<option value="in_each_category">'+woo_discount_localization.in_each_category_cart+'</option>' +
                    '</optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_details_must_be_logged_in+'"><option value="users_in">'+woo_discount_localization.user_in_list+'</option><option value="roles_in">'+woo_discount_localization.user_role_in_list+'</option><option value="shipping_countries_in">'+woo_discount_localization.shipping_country_list+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_email+'"><option value="customer_email_tld">'+woo_discount_localization.customer_email_tld+'</option><option value="customer_email_domain">'+woo_discount_localization.customer_email_domain+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_billing_details+'"><option value="customer_billing_city">'+woo_discount_localization.customer_billing_city+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_shipping_details+'"><option value="customer_shipping_state">'+woo_discount_localization.customer_shipping_state+'</option>' +
                    '<option value="customer_shipping_city">'+woo_discount_localization.customer_shipping_city+'</option>' +
                    '<option value="customer_shipping_zip_code">'+woo_discount_localization.customer_shipping_zip_code+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.purchase_history+'">' +
                    '<option value="customer_based_on_purchase_history">'+woo_discount_localization.purchased_amount+'</option>'+
                    '<option value="customer_based_on_purchase_history_order_count">'+woo_discount_localization.number_of_order_purchased+'</option>'+
                    '<option value="customer_based_on_purchase_history_product_order_count">'+woo_discount_localization.number_of_order_purchased_in_product+'</option>'+
                    '</optgroup>' +
                    '<optgroup label="'+woo_discount_localization.coupon_applied+'"><option value="coupon_applied_any_one">'+woo_discount_localization.atleast_any_one+'</option><option value="coupon_applied_all_selected">'+woo_discount_localization.all_selected+'</option></optgroup>' +
                    '</select></label></div>' +
                    '<div class="col-md-3 form-group"><label> '+woo_discount_localization.value_text+'<div id="general_' + count + '"><input type="text" name="discount_rule[' + count + '][option_value]"></div>' +
                    '<div id="user_div_' + count + '">';
                if($('#flycart_wdr_woocommerce_version').val() == 2){
                    form += '<input class="wc-customer-search" style="width: 250px" name="discount_rule[' + count + '][users_to_apply][]" data-placeholder="'+woo_discount_localization.place_holder_search_for_a_user+'"/>';
                } else {
                    form += '<select class="wc-customer-search" style="width: 250px" multiple="multiple" name="discount_rule[' + count + '][users_to_apply][]" data-placeholder="'+woo_discount_localization.place_holder_search_for_a_user+'"></select>';
                }
                form += '</div>' +
                    '<div id="product_div_' + count + '"><select id="cart_product_list_' + count + '" class="product_list selectpicker"  title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][product_to_apply][]"></select></div>' +
                    '<div id="category_div_' + count + '"><select id="cart_category_list_' + count + '" class="category_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][category_to_apply][]"></select></div>' +
                    '<div id="roles_div_' + count + '"><select id="cart_roles_list_' + count + '" class="roles_list selectpicker"  title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][user_roles_to_apply][]"></select></div>' +
                    '<div id="countries_div_' + count + '"><select id="cart_countries_list_' + count + '" class="country_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][countries_to_apply][]"></select></div>' +
                    '<div id="purchase_history_div_' + count + '">' +
                    '<div class="form-group wdr_hide" id="purchase_history_products_list_'+ count +'">'+
                    '</div>'+
                    '<select class="selectpicker purchased_history_type" data-live-search="true" name="discount_rule['+count+'][purchased_history_type]">' +
                    '<option value="atleast">'+woo_discount_localization.greater_than_or_equal_to+'</option>' +
                    '<option value="less_than_or_equal">'+woo_discount_localization.less_than_or_equal_to+'</option>' +
                    '</select>' +
                    ' <input name="discount_rule[' + count + '][purchased_history_amount]" value="" type="text"/> '+woo_discount_localization.in_order_status+' <select id="order_status_list_' + count + '" class="order_status_list selectpicker"  data-live-search="true" multiple name="discount_rule[' + count + '][purchase_history_order_status][]"></select></div>' +
                    '</div><div class="col-md-1"> <label> '+woo_discount_localization.action_text+'</label> <br> <a href=javascript:void(0) class="btn btn-danger remove_cart_rule">'+woo_discount_localization.remove_text+'</a>  </div>' +
                    '</label></div>';
            } else {
                var form = '<div class="cart_rules_list row"> <div class="col-md-3 form-group"> <label>'+woo_discount_localization.type_text+' <select class="form-control cart_rule_type" id="cart_condition_type_' + count + '" name="discount_rule[' + count + '][type]"> <optgroup label="'+woo_discount_localization.cart_subtotal+'"><option value="subtotal_least" selected="selected">'+woo_discount_localization.subtotal_at_least+'</option><option value="subtotal_less">'+woo_discount_localization.subtotal_less_than+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.cart_item_count+'"><option value="item_count_least">'+woo_discount_localization.number_of_line_items_in_cart_at_least+'</option><option value="item_count_less">'+woo_discount_localization.number_of_line_items_in_cart_less_than+'</option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.quantity_sum+'"><option disabled>'+woo_discount_localization.total_number_of_quantities_in_cart_at_least+' <b>' + pro_suffix + '</b></option><option disabled>'+woo_discount_localization.total_number_of_quantities_in_cart_less_than+' <b>' + pro_suffix + '</b></option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.categories_in_cart+'">' +
                        '<option disabled>'+woo_discount_localization.categories_in_cart+' <b>' + pro_suffix + '</b></option>' +
                        '<option disabled>'+woo_discount_localization.atleast_one_including_sub_categories+' <b>' + pro_suffix + '</b></option>' +
                        '<option disabled>'+woo_discount_localization.in_each_category_cart+' <b>' + pro_suffix + '</b></option>' +
                    '</optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_details_must_be_logged_in+'"><option disabled>'+woo_discount_localization.user_in_list+' <b>' + pro_suffix + '</b></option><option disabled>'+woo_discount_localization.user_role_in_list+' <b>' + pro_suffix + '</b></option><option disabled>'+woo_discount_localization.shipping_country_list+' <b>' + pro_suffix + '</b></option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_email+'"><option disabled>'+woo_discount_localization.customer_email_tld+' <b>' + pro_suffix + '</b></option><option disabled>'+woo_discount_localization.customer_email_domain+'<b>' + pro_suffix + '</b></option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_billing_details+'"><option disabled>'+woo_discount_localization.customer_billing_city+' <b>' + pro_suffix + '</b></option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.customer_shipping_details+'"><option disabled>'+woo_discount_localization.customer_shipping_state+' <b>' + pro_suffix + '</b></option>' +
                    '<option disabled>'+woo_discount_localization.customer_shipping_city+' <b>' + pro_suffix + '</b></option>' +
                    '<option disabled>'+woo_discount_localization.customer_shipping_zip_code+' <b>' + pro_suffix + '</b></option></optgroup>' +
                    '<optgroup label="'+woo_discount_localization.purchase_history+'"><option disabled>'+woo_discount_localization.purchased_amount+' <b>' + pro_suffix + '</b></option>' +
                    '<option disabled>'+woo_discount_localization.number_of_order_purchased+' <b>' + pro_suffix + '</b></option>' +
                    '<option disabled>'+woo_discount_localization.number_of_order_purchased_in_product+' <b>' + pro_suffix + '</b></option>' +
                    '</optgroup>' +
                    '<optgroup label="'+woo_discount_localization.coupon_applied+'"><option disabled>'+woo_discount_localization.atleast_any_one+' <b>' + pro_suffix + '</b></option><option disabled>'+woo_discount_localization.all_selected+' <b>' + pro_suffix + '</b></option></optgroup>' +
                    '</select></label></div>' +
                    '<div class="col-md-3 form-group"><label> '+woo_discount_localization.value_text+'<div id="general_' + count + '"><input type="text" name="discount_rule[' + count + '][option_value]"></div>' +
                    '<div id="user_div_' + count + '"><select id="cart_user_list_' + count + '" class="user_list selectpicker"  title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][users_to_apply][]"></select></div>' +
                    '<div id="product_div_' + count + '"><select id="cart_product_list_' + count + '" class="product_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][product_to_apply][]"></select></div>' +
                    '<div id="category_div_' + count + '"><select id="cart_category_list_' + count + '" class="category_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][category_to_apply][]"></select></div>' +
                    '<div id="roles_div_' + count + '"><select id="cart_roles_list_' + count + '" class="roles_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][user_roles_to_apply][]"></select></div>' +
                    '<div id="countries_div_' + count + '"><select id="cart_countries_list_' + count + '" class="country_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][countries_to_apply][]"></select></div>' +
                    '<div id="purchase_history_div_' + count + '"><select id="order_status_list_' + count + '" class="order_status_list selectpicker" title="'+woo_discount_localization.none_selected+'" data-live-search="true" multiple name="discount_rule[' + count + '][purchase_history_order_status][]"></select></div>' +
                    '</div><div class="col-md-1"> <label> '+woo_discount_localization.action_text+' </label><br><a href=javascript:void(0) class="btn btn-danger remove_cart_rule">'+woo_discount_localization.remove_text+'</a>  </div>' +
                    '</label></div>';
            }

            // Append to Cart rules list.
            $('#cart_rules_list').append(form);
            if(product_list != ''){
                $('#purchase_history_products_list_'+count).html(product_list);
                $('.wc-product-search').trigger( 'wc-enhanced-select-init' );
            }

            $('.wc-customer-search').trigger( 'wc-enhanced-select-init' );

            // Append the List of Values.
            $('#cart_user_list_' + count).append(user_list);
            $('#cart_product_list_' + count).append(product_list);
            $('#cart_category_list_' + count).append(category_list);
            $('#cart_roles_list_' + count).append(roles_list);
            $('#cart_countries_list_' + count).append(country_list);
            $('#order_status_list_' + count).append(order_status_list);

            // Refresh the SelectPicker.
            $('.product_list').selectpicker('refresh');
            $('.category_list').selectpicker('refresh');
            $('.roles_list').selectpicker('refresh');
            $('.country_list').selectpicker('refresh');
            $('.order_status_list').selectpicker('refresh');
            $('.purchased_history_type').selectpicker('refresh');

            // Default Hide List.
            $('#user_div_' + count).css('display', 'none');
            $('#product_div_' + count).css('display', 'none');
            $('#category_div_' + count).css('display', 'none');
            $('#roles_div_' + count).css('display', 'none');
            $('#countries_div_' + count).css('display', 'none');
            $('#purchase_history_div_' + count).css('display', 'none');
        });

        $(document).on('change', '.cart_rule_type', function () {
            var id = $(this).attr('id');
            id = id.replace('cart_condition_type_', '');
            var active = $(this).val();
            showOnly(active, id);

        });

        $('#cart_rule_discount_type').on('change', function () {
            var option = $(this).val();
            if (option == 'shipping_price') {
                $('#cart_rule_discount_value_con').addClass('wdr_hide_important');
            } else {
                $('#cart_rule_discount_value_con').removeClass('wdr_hide_important');
            }
        });
        $('#cart_rule_discount_type').trigger('change');

        //on change discount type in price discount
        $(document).on('change', '.price_discount_type', function () {
            var discount_amount = $(this).closest('.discount_rule_list').find('.price_discount_amount');
            var price_discount_amount = $(this).closest('.discount_rule_list').find('.price_discount_product_list_con');
            var discount_product_percent_con = $(this).closest('.discount_rule_list').find('.discount_product_percent_con');
            if($(this).val() == 'product_discount'){
                discount_amount.hide();
                price_discount_amount.removeClass('hide').show();
                discount_product_percent_con.removeClass('hide').show();
                $(this).closest('.discount_rule_list').find('.hide-for-product-discount').hide();
            } else {
                discount_amount.show();
                price_discount_amount.hide();
                discount_product_percent_con.hide();
                $(this).closest('.discount_rule_list').find('.hide-for-product-discount').show();
            }
        });
        $('.price_discount_type').trigger('change');

        //on change discount_product_option in product discount
        $(document).on('change', 'select.discount_product_option', function () {
            var discount_product = $(this).closest('.price_discount_product_list_con').find('.discount_product_option_list_con');
            var discount_category = $(this).closest('.price_discount_product_list_con').find('.discount_category_option_list_con');
            var discount_product_more_cheapest = $(this).closest('.price_discount_product_list_con').find('.discount_product_option_more_cheapest_con');
            var discount_product_option_bogo_con = $(this).closest('.price_discount_product_list_con').find('.discount_product_option_bogo_con');
            discount_category.addClass('hide');
            discount_product_option_bogo_con.addClass('hide');
            if($(this).val() == 'all' || $(this).val() == 'same_product'){
                discount_product_option_bogo_con.removeClass('hide');
            }
            if($(this).val() == 'any_cheapest_from_all' || $(this).val() == 'more_than_one_cheapest_from_all'){
                discount_product.addClass('hide');
            } else {
                discount_product.removeClass('hide');
            }
            if($(this).val() == 'more_than_one_cheapest' || $(this).val() == 'more_than_one_cheapest_from_all' || $(this).val() == 'more_than_one_cheapest_from_cat'){
                discount_product_more_cheapest.removeClass('hide');
            } else {
                discount_product_more_cheapest.addClass('hide');
            }
            if($(this).val() == 'more_than_one_cheapest_from_cat'){
                discount_product.addClass('hide');
                discount_category.removeClass('hide');
            }
            if($(this).val() == 'same_product'){
                discount_product.addClass('hide');
            }

        });
        $('select.discount_product_option').trigger('change');

        $(document).on('change', 'select.discount_product_item_count_type', function () {
            var optionVal = $(this).val();
            var target = $(this).closest('.discount_product_option_more_cheapest_con').find('.discount_product_items_count_field');
            if (optionVal == 'static') {
                target.removeClass('hide');
            } else {
                target.addClass('hide');
            }
        });
        $('select.discount_product_item_count_type').trigger('change');

        //on change discount_product_discount_type in product discount
        $(document).on('change', 'select.discount_product_discount_type', function () {
            var discount_product_percent_field = $(this).closest('.discount_product_percent_con').find('.discount_product_percent_field');
            if($(this).val() == 'limited_percent'){
                discount_product_percent_field.removeClass('hide');
            } else {
                discount_product_percent_field.addClass('hide');
            }
        });
        $('select.discount_product_discount_type').trigger('change');


        // Saving Cart Rule.
        $('#saveCartRule').on('click', function (event) {
            var form = $('#form_cart_rule').serialize();
            var current = $(this);
            var rule_id = $('#rule_id').val();
            var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
            event.preventDefault();
            if ($('#rule_name').val() == '') {
                alert(woo_discount_localization.please_enter_the_rule_name);
            } else {
                current.val(woo_discount_localization.saving);
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: {action: 'saveCartRule', data: form},
                    beforeSend: function() {
                        loader.show();
                    },
                    complete: function() {
                        loader.hide();
                    },
                    success: function () {
                        // After Status Changed.
                        resizeChart = setTimeout(function () {
                            current.val(woo_discount_localization.save_rule);
                        }, 300);

                        // Reset, if its New Form.
                        if (rule_id == 0) {
                            window.location.replace(admin_url + '&tab=cart-rules');
                        }
                        adminNotice();
                    }
                });
            }
        });

        // Change the List to Show, on change of Rule Type.
        $('.cart_rule_type').on('change', function () {
            var id = $(this).attr('id');
            id = id.replace('cart_condition_type_', '');

            $('#cart_user_list_' + id).selectpicker('val', []);
            $('#cart_product_list_' + id).selectpicker('val', []);
            $('#cart_category_list_' + id).selectpicker('val', []);
            $('#cart_roles_list_' + id).selectpicker('val', []);
            $('#cart_countries_list_' + id).selectpicker('val', []);
            $('#order_status_list_' + id).selectpicker('val', []);

        });

        // Enabling and Disabling the Status of the Rule.
        $('.cart_manage_status').on('click', function (event) {
            event.preventDefault();
            var current = $(this);
            var id = $(this).attr('id');
            id = id.replace('state_', '');
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {action: 'UpdateStatus', id: id, from: 'cart-rules'},
                success: function (status) {
                    // After Status Changed.
                    if (status == 'Disable') {
                        current.addClass('btn-warning');
                        current.removeClass('btn-success');
                        current.html(woo_discount_localization.enable_text);
                    } else if (status == 'Publish') {
                        current.removeClass('btn-warning');
                        current.addClass('btn-success');
                        current.html(woo_discount_localization.disable_text);
                    }
                }

            });
        });

        // Removing Cart Rule.
        $('.cart_delete_rule').on('click', function (event) {
            event.preventDefault();
            var current = $(this);
            var id = $(this).attr('id');
            id = id.replace('delete_', '');
            var confirm_delete = confirm(woo_discount_localization.are_you_sure_to_remove);
            if (confirm_delete) {
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: {action: 'RemoveRule', id: id, from: 'cart-rules'},
                    success: function () {
                        // After Removed.
                        current.closest('tr').remove();
                        location.reload(true);
                    }
                });
            }
        });

        // Removing Cart Condition.
        $(document).on('click', '.remove_cart_rule', function () {
            var confirm_remove = confirm(woo_discount_localization.are_you_sure_to_remove);
            if (confirm_remove) {
                $(this).closest('.cart_rules_list').remove();
            }
        });

        $('#based_on_purchase_history').on('change', function () {
            var checked = $( this ).val();
            if(checked == "0" || checked == ""){
                $('#based_on_purchase_history_fields').hide();
            } else {
                $('#based_on_purchase_history_fields').show();
            }
            if(checked == "3"){
                $("#purchase_history_products").show();
            } else {
                $("#purchase_history_products").hide();
            }
        });
        $('#based_on_purchase_history').trigger('change');

        $('#price_rule_method').on('change', function () {
            var rule_method = $(this).val();
            $('.price_discounts_con, .price_discount_condition_con').hide();
            $('.'+rule_method+'_discount_cont, .'+rule_method+'_condition_cont').show();
        });
        $('#price_rule_method').trigger('change');

        $('#product_based_condition_quantity_rule').on('change', function () {
            var quantity_values = $(this).val();
            if(quantity_values == 'from'){
                $('.product_based_condition_to').css({"display": "inline-block"})
            } else {
                $('.product_based_condition_to').css({"display": "none"})
            }
        });
        $('#product_based_condition_quantity_rule').trigger('change');

        $('#product_based_condition_get_discount_type').on('change', function () {
            var discount_type_value = $(this).val();
            $('.get_discount_type_product_tag, .get_discount_type_category_tag').hide();
            if(discount_type_value == 'product'){
                $('.get_discount_type_product_tag').show();
            } else {
                $('.get_discount_type_category_tag').show();
            }
        });
        $('#product_based_condition_get_discount_type').trigger('change');

        // product_based_condition_product_to_apply_count_option
        $('#product_based_condition_product_to_apply_count_option').on('change', function () {
            var value = $(this).val();
            if(value == 'all'){
                $('#product_based_condition_product_to_apply_count').css({"display": "none"})
            } else {
                $('#product_based_condition_product_to_apply_count').css({"display": "inline-block"})
            }
        });
        $('#product_based_condition_product_to_apply_count_option').trigger('change');

        $('#wdr_do_bulk_action').on('click', function (event) {
            event.preventDefault();
            var formData = $('#woo_discount_list_form').serializeArray();
            var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
            if($('#bulk-action-selector-top').val() != ''){
                if($('#bulk-action-selector-top').val() == 'delete'){
                    if(!confirm(woo_discount_localization.are_you_sure_to_delete)){
                        return false;
                    }
                }

                if ($("#woo_discount_list_form input:checkbox:checked").length > 0) {
                    formData.push({'name': 'action', 'value': 'doBulkAction'});
                    $.ajax({
                        url: ajax_url,
                        type: 'POST',
                        data: formData,
                        beforeSend: function() {
                            loader.show();
                        },
                        complete: function() {
                            loader.hide();
                        },
                        success: function (response) {
                            jQuery('#woo-admin-message').html(' <div class="notice notice-success is-dismissable"><p>'+response+'</p></div>');
                            location.reload();
                        }
                    });
                } else {
                    alert(woo_discount_localization.please_select_at_least_one_checkbox);
                    return false;
                }
            } else {
                alert(woo_discount_localization.please_select_bulk_action);
                return false;
            }
        });

        function createDuplicateRule(id, type) {
            if(id != undefined && type != undefined){
                var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    data: {'action': 'createDuplicateRule', 'id': id, 'type': type},
                    beforeSend: function() {
                        loader.show();
                    },
                    complete: function() {
                        loader.hide();
                    },
                    success: function (response) {
                        jQuery('#woo-admin-message').html(' <div class="notice notice-success is-dismissable"><p>'+response+'</p></div>');
                        location.reload();
                    }
                });
            }
        }

        $('.duplicate_price_rule_btn').on('click', function (event) {
            event.preventDefault();
;            createDuplicateRule($(this).attr('data-id'), 'price_rule');
        });
        $('.duplicate_cart_rule_btn').on('click', function (event) {
            event.preventDefault();
            createDuplicateRule($(this).attr('data-id'), 'cart_rule');
        });
        //--------------------------------------------------------------------------------------------------------------
        //-----------------------------------------------SETTINGS-------------------------------------------------------
        //--------------------------------------------------------------------------------------------------------------

        $('#saveConfig').on('click', function (event) {
            event.preventDefault();
            var form = $('#discount_config').serialize();
            var current = $(this);
            var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
            current.val(woo_discount_localization.saving);
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {action: 'saveConfig', from: 'settings', data: form},
                beforeSend: function() {
                    loader.show();
                },
                complete: function() {
                    loader.hide();
                },
                success: function () {
                    // After Removed.
                    resizeChart = setTimeout(function () {
                        current.val(woo_discount_localization.save_text);
                    }, 300);
                    adminNotice();
                }
            });
        });

        $('#refresh_wdr_cache').on('click', function (event) {
            event.preventDefault();
            var loader = $('.woo_discount_loader_outer > .woo_discount_loader');
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {action: 'resetWDRCache'},
                beforeSend: function() {
                    loader.show();
                },
                complete: function() {
                    loader.hide();
                },
                success: function (response) {
                    //adminNotice();
                    jQuery('#woo-admin-message').html(' <div class="notice notice-success is-dismissable"><p>'+response+'</p></div>');
                }
            });
        });

        $('input[type=radio][name=enable_variable_product_cache]').change(function() {
            if (this.value == '1') {
                $('.enable_variable_product_cache_con').show();
            } else {
                $('.enable_variable_product_cache_con').hide();
            }
        });
        $('input[type=radio][name=enable_variable_product_cache]:checked').trigger('change');

        $('select#enable_free_shipping').on('change', function () {
            var option = $(this).val();
            if (option == 'woodiscountfree') {
                $('#woodiscount_settings_free_shipping_con').show();
            } else {
                $('#woodiscount_settings_free_shipping_con').hide();
            }
        });
        $('select#enable_free_shipping').trigger('change');

        //--------------------------------------------------------------------------------------------------------------
        //-----------------------------------------------SIDE PANEL-----------------------------------------------------
        //--------------------------------------------------------------------------------------------------------------

        $('.woo-side-button').on('click', function () {
            //$('#woo-side-panel').toggle();
            if ($('#sidebar_text').html() == woo_discount_localization.show_text) {
                $('#sidebar_text').html(woo_discount_localization.hide_text);
                $('.woo-side-panel').show();
                $('#sidebar_icon').addClass('dashicons-arrow-left');
                $('#sidebar_icon').removeClass('dashicons-arrow-down');
            } else {
                $('#sidebar_text').html(woo_discount_localization.show_text);
                $('.woo-side-panel').hide();
                $('#sidebar_icon').removeClass('dashicons-arrow-left');
                $('#sidebar_icon').addClass('dashicons-arrow-down');
            }
        });

    });

    //------------------------------------------------------------------------------------------------------------------
    function processShowOnlyTags(id_prefix, id){
        var availableTags = ["user_div_", "product_div_", "category_div_", "general_", "roles_div_", "countries_div_", "purchase_history_div_"];
        $.each(availableTags, function( index, value ) {
            if(value == id_prefix)
                $('#'+value+id).css('display', 'block');
            else
                $('#'+value+id).css('display', 'none');
        });
    }
    function showOnly(option, id) {
        if (option == 'products_atleast_one' || option == 'products_not_in') {
            processShowOnlyTags('product_div_', id);
        } else if (option == 'categories_atleast_one' || option == 'categories_not_in' || option == 'categories_in' || option == 'in_each_category' || option == 'atleast_one_including_sub_categories') {
            processShowOnlyTags('category_div_', id);
        } else if (option == 'users_in') {
            processShowOnlyTags('user_div_', id);
        } else if (option == 'roles_in') {
            processShowOnlyTags('roles_div_', id);
        } else if (option == 'shipping_countries_in') {
            processShowOnlyTags('countries_div_', id);
        } else if (option == 'customer_based_on_purchase_history' || option == 'customer_based_on_purchase_history_product_order_count' || option == 'customer_based_on_purchase_history_order_count') {
            processShowOnlyTags('purchase_history_div_', id);
            if(option == 'customer_based_on_purchase_history_product_order_count'){
                $('#purchase_history_div_'+id+' #purchase_history_products_list_'+id).show();
            } else {
                $('#purchase_history_div_'+id+' #purchase_history_products_list_'+id).hide();
            }
        } else {
            processShowOnlyTags('general_', id);
        }

    }

    function adminNotice() {
        jQuery('#woo-admin-message').html(' <div class="notice notice-success is-dismissable"><p>'+woo_discount_localization.saved_successfully+'</p></div>');

        setTimeout(function () {
            jQuery('#woo-admin-message').html('');
        }, 2000);
    }

    function makeActiveForSelectedTab(selected){
        var container = selected.closest('.nav-tab-wrapper');
        container.find('.nav-tab').removeClass('nav-tab-active');
        selected.addClass('nav-tab-active');
    }

})(jQuery);