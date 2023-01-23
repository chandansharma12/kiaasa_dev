"use strict";

var billing_prod_list=[], total_order_amount = 0;

function addProductToBilling(){
    var ids = '',inv_barcodes_arr = [];
    var prod_barcode = $("#productBarcode").val();
    if(prod_barcode == ''){
        $("#prodError").html('').hide();
        setTimeout(function(){ $("#prodError").html('Please enter Product Barcode').show();  }, 200);
        
        return false;
    }
    
    for(var i=0;i<billing_prod_list.length;i++){
        ids+=billing_prod_list[i]['id']+",";
        inv_barcodes_arr.push(billing_prod_list[i].peice_barcode);
    }
    
    // If user have added inventory barcode, not product barcode
    if(inv_barcodes_arr.indexOf(prod_barcode) >= 0){
        $("#prodError").html('').hide();
        setTimeout(function(){ $("#prodError").html('Product already added').show();  }, 200);
        return false;
    }
    
    ids = ids.substring(0,ids.length-1);
    var discount = $("#discount").val();
    var form_data = "barcode="+prod_barcode+"&ids="+ids+"&discount_percent="+discount;
            
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/pos/product/detail-updated",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#pos_prod_billing_spinner").hide();            
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#prodError").html(errors).show();
                    } 
                }else{ 
                    $("#prodError,.invalid-feedback").html('').hide();
                    var product_data = msg.product_data;
                    if(product_data == null || product_data == ''){
                        $("#prodError").html('Product does not exists').show();
                    }else{
                        if(inv_barcodes_arr.indexOf(product_data.peice_barcode) < 0){
                            $("#productBarcode").val('');
                            product_data.staff_id = $("#staff_id").val();
                            
                            billing_prod_list.push(product_data);//alert(billing_prod_list.length);
                            updateBillingTotalPrice(product_data.id);
                            $("#productBarcode,#discount").val('');
                            $("#productBarcode").focus();
                        }else{
                            $("#prodError").html('Product already added').show();
                        }
                    }
                }
            }else{
                displayResponseError(msg,"prodError");
            }
        },error:function(obj,status,error){
            $("#prodError").html('Error in processing request').show();
            $("#pos_prod_billing_spinner").hide();
        }
    });
}

function deleteBillingProduct(id){
    $('#confirm_delete_billing_product').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_billing_product_btn', function(e) {
        e.preventDefault();
        var billing_prod_list_1 = [];
        for(var i=0;i<billing_prod_list.length;i++){
            if(billing_prod_list[i].id != id){
                billing_prod_list_1.push(billing_prod_list[i]);
            }
        }

        billing_prod_list = billing_prod_list_1;
        
        updateBillingTotalPrice('');
       
        $('#confirm_delete_billing_product').modal('hide');
    });
}

function updateBillingTotalPrice(product_id){
    var str = '',count = 0,tr_css_class = '',ids = '',total_sale_price = 0,total_discount = 0,total_gst = 0,total_net_price = 0,total_net_price_sale = 0;
    
    for(var i=0;i<billing_prod_list.length;i++){
        ids+=billing_prod_list[i]['id']+",";
    }    
    
    ids = ids.substring(0,ids.length-1);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/pos/billing/products/detail",
        method:"POST",
        data:{ids:ids},
        success:function(msg){
            $("#pos_prod_billing_spinner").hide();            
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#prodError").html(errors).show();
                    } 
                    
                    if(product_id != ''){
                        var billing_prod_list_1 = [];
                        for(var i=0;i<billing_prod_list.length;i++){
                            if(billing_prod_list[i].id != product_id){
                                billing_prod_list_1.push(billing_prod_list[i]);
                            }
                        }
                        
                        billing_prod_list = billing_prod_list_1;
                    }
                }else{ 
                    $("#prodError,.invalid-feedback").html('').hide();
                    var group_products = msg.group_products;
                    var individual_products = msg.individual_products;
                    var return_products = msg.return_products;
                    var discount_group = msg.discount_group;
                    
                    if(group_products.length > 0){
                        str+='<b style="padding:16px 12px">Buy '+discount_group.buy_items+" Get "+discount_group.get_items+': </b>';
                        for(var i=0;i<group_products.length;i++){
                            var product_data = group_products[i];
                            str+='<div class="inner-content-val"><div class="val">'+product_data.product_name+" "+product_data.size_name+" "+product_data.color_name+'</div><div class="val-mrp">'+product_data.sale_price+'</div>\
                            <div class="val-dis"><span>'+parseFloat(product_data.discount_percent).toFixed(2)+'</span></div><div class="val-dis-price">'+parseFloat(product_data.discount_amount).toFixed(2)+'</div><div class="val-gst-price">'+parseFloat(product_data.gst_amount).toFixed(2)+" ("+product_data.gst_percent+'%)</div>\
                            <div class="val-qty"><span>1</span></div><div class="val-net">'+parseFloat(product_data.net_price).toFixed(2)+'</div>\
                            <div class="val-remove"><i onclick="deleteBillingProduct('+product_data.id+');" class="fas fa-trash" aria-hidden="true" style="cursor:pointer;"></i></div>\
                            </div>';
                        }
                    }
                    
                    if(individual_products.length > 0){
                        str+='<b style="padding:16px 12px">Individual Products: </b>';
                        for(var i=0;i<individual_products.length;i++){
                            var product_data = individual_products[i];
                            str+='<div class="inner-content-val"><div class="val">'+product_data.product_name+" "+product_data.size_name+" "+product_data.color_name+'</div><div class="val-mrp">'+product_data.sale_price+'</div>\
                            <div class="val-dis"><span>'+parseFloat(product_data.discount_percent).toFixed(2)+'</span></div><div class="val-dis-price">'+parseFloat(product_data.discount_amount).toFixed(2)+'</div><div class="val-gst-price">'+parseFloat(product_data.gst_amount).toFixed(2)+" ("+product_data.gst_percent+'%)</div>\
                            <div class="val-qty"><span>1</span></div><div class="val-net">'+parseFloat(product_data.net_price).toFixed(2)+'</div>\
                            <div class="val-remove"><i onclick="deleteBillingProduct('+product_data.id+');" class="fas fa-trash" aria-hidden="true" style="cursor:pointer;"></i></div>\
                            </div>';
                        }
                    }
                    
                    if(return_products.length > 0){
                        str+='<b style="padding:16px 12px">Return Products: </b>';
                        for(var i=0;i<return_products.length;i++){
                            var product_data = return_products[i];
                            var other_store_name = (product_data.other_store_prod == 1)?" (Sold at "+product_data.other_store_name+")":'';
                            
                            str+='<div class="inner-content-val pos-return-prod-tr" style="background-color:#EAC0A6 !important;"><div class="val">'+product_data.product_name+" "+product_data.size_name+" "+product_data.color_name+other_store_name+'</div><div class="val-mrp">'+product_data.order_data.sale_price+'</div>\
                            <div class="val-dis"><span>'+parseFloat(product_data.order_data.discount_percent).toFixed(2)+'</span></div><div class="val-dis-price">-'+parseFloat(product_data.order_data.discount_amount).toFixed(2)+'</div><div class="val-gst-price">-'+parseFloat(product_data.order_data.gst_amount).toFixed(2)+" ("+product_data.order_data.gst_percent+'%)</div>\
                            <div class="val-qty"><span>1</span></div><div class="val-net">-'+parseFloat(product_data.order_data.net_price).toFixed(2)+'</div>\
                            <div class="val-remove"><i onclick="deleteBillingProduct('+product_data.id+');" class="fas fa-trash" aria-hidden="true" style="cursor:pointer;"></i></div>\
                            </div>';
                            
                            /*if(typeof return_products[i]['product_exchanged'] != 'undefined'){
                                var product_data = return_products[i]['product_exchanged'];
                                str+='<div class="inner-content-val" ><div class="val">'+product_data.product_name+" "+product_data.size_name+" "+product_data.color_name+'</div><div class="val-mrp">'+product_data.sale_price+'</div>\
                                <div class="val-dis"><span>'+parseFloat(product_data.discount_percent).toFixed(2)+'</span></div><div class="val-dis-price">'+product_data.discount_amount+'</div><div class="val-gst-price">'+product_data.gst_amount+" ("+product_data.gst_percent+'%)</div>\
                                <div class="val-qty"><span>1</span></div><div class="val-net">'+product_data.net_price+'</div>\
                                <div class="val-remove"><i onclick="deleteBillingProduct('+product_data.id+');" class="fas fa-trash" aria-hidden="true" style="cursor:pointer;"></i></div>\
                                </div>';
                            }*/
                        }
                    }
                    
                    $(".billing-prod-list").html(str);
                    $("#subtotal_total").html(currency+" "+msg.sale_price_total.toFixed(2));
                    $("#discount_total").html(currency+" "+msg.discount_total.toFixed(2));
                    $("#cgst_total").html(currency+" "+(msg.gst_total/2).toFixed(2));
                    $("#sgst_total").html(currency+" "+(msg.gst_total/2).toFixed(2));
                    $("#order_total").html(currency+" "+msg.net_price_total.toFixed(2));
                    var total_cart_items = group_products.length+individual_products.length+return_products.length;
                    $("#cartitems_total").html('Cart Details ('+total_cart_items+' Items)');
                    total_order_amount = msg.net_price_total.toFixed(2);
                }
            }else{
                displayResponseError(msg,"prodError");
            }
        },error:function(obj,status,error){
            $("#prodError").html('Error in processing request').show();
            $("#pos_prod_billing_spinner").hide();
        }
    });
}

function getCouponDiscount(product_data,decrease_discount){
    var discount_updated = 0, discounted_price = 0, gst_amount = 0,total_amount = 0,discount_amount = 0;
    
    var sale_price = product_data.sale_price;
    var discount = product_data.discount_percent;
    var discount_percent = (discount/100).toFixed(3);
    var gst = product_data.gst_percent_coupon;
    var gst_percent = (gst/100).toFixed(2);
    var net_price = product_data.net_price; 
    
    var net_price_req = product_data.net_price_required;
    net_price_req = parseFloat(net_price_req).toFixed(3);
    
    if(decrease_discount == false){
        for(var i=0;i<100000;i = i+0.00001){
            discount_updated = parseFloat(discount_percent)+i;
            discount_amount = sale_price*discount_updated;
            discounted_price = sale_price-discount_amount;
            gst_amount = (discounted_price*gst_percent);
            total_amount = parseFloat(discounted_price+gst_amount).toFixed(3);//alert(parseFloat(total_amount));alert(parseFloat(net_price_req));
            //console.log(total_amount);
            if(parseFloat(total_amount) <= parseFloat(net_price_req)){
                break;
            }
        }
    }else{
        for(var i=0;i<100000;i = i+0.00001){
            discount_updated = parseFloat(discount_percent)-i;
            discount_amount = sale_price*discount_updated;
            discounted_price = sale_price-discount_amount;
            gst_amount = (discounted_price*gst_percent);
            total_amount = parseFloat(discounted_price+gst_amount).toFixed(3);//alert(parseFloat(total_amount));
            //console.log(total_amount);
            if(parseFloat(total_amount) >= parseFloat(net_price_req)){
                break;
            }
        }
    }
    
    return (discount_updated*100).toFixed(5);
}

function createPosOrder(){
    var error_msg = '',total_net_price = 0;
    if(billing_prod_list.length == 0){
        error_msg = '<div class="alert alert-danger">Please add Products</div>';
    }
    
    // Code by sandeep for adding multiple payment methods. 
    var voucherAmountValue = $("#voucherAmount").val() == '' ? 0 : $("#voucherAmount").val(); 
    var WalletAmtValue = $("#E-WalletAmt").val() == '' ? 0 : $("#E-WalletAmt").val();     
    var cardAmtValue = $("#CardAmt").val() == '' ? 0 : $("#CardAmt").val();     
    var cashAmtValue = $("#CashAmt").val() == '' ? 0 : $("#CashAmt").val();   
         
    var paymentSum = parseFloat(voucherAmountValue)+parseFloat(WalletAmtValue)+parseFloat(cardAmtValue)+parseFloat(cashAmtValue);
    //alert(total_order_amount);alert(paymentSum);
    if(!(paymentSum == total_order_amount || Math.ceil(paymentSum) == Math.ceil(total_order_amount) || Math.floor(paymentSum) == Math.floor(total_order_amount))){
        error_msg+= '<div class="alert alert-danger">Please correct the payment amount</div>'; 
    }
    
    if($("#cashvoucher").is(':checked')){
        if($("#voucherAmount").val() =='' || $("#voucherComment").val() =='' ){
             error_msg+= '<div class="alert alert-danger">Please enter Voucher  details.</div>'; 
        }
    }     
    if(WalletAmtValue > 0){
        var eWalletRefNo= $("#eWalletRefNo").val() 
        if(eWalletRefNo==''){
            error_msg+= '<div class="alert alert-danger">Please enter WalletRef number.</div>';
        }
    }
    
    if(billing_prod_list.length > 0){        
        for(var i=0;i<billing_prod_list.length;i++){
            if(billing_prod_list[i].return_product == 0){
                total_net_price+=parseFloat(billing_prod_list[i].net_price);
            }else{
                total_net_price-=parseFloat(billing_prod_list[i].net_price_return);
            }
        }
        
        
        if(total_net_price.toFixed(0) < 0){
            error_msg+= '<div class="alert alert-danger">Total bill amount should be greater or equal to 0</div>';
        }
    }
    
    if(error_msg != ''){
        $("#create_pos_order_error").html(error_msg).show();
        $("#create_pos_order_error_dialog").modal('show');
        return false;
    }
  
    $("#posCreateOrderErrorMessage,#posCreateOrderSuccessMessage,.invalid-feedback").html('').hide();
    $("#pos_add_order_dialog").find('.form-control').val('');
    $(".billing-new-cust-div").hide();
    $(".billing-exist-cust-div").show();
    $("#billing_customer_type_existing").prop('checked',true);
    $("#pos_add_order_dialog").modal('show');
}

function toggleBillingCustomerForm(type){
    $(".billing-cust-div").hide();
    $(".billing-"+type+"-cust-div").show();
    $("#customer_phone_new,#customer_name,#customer_salutation,#customer_email,#customer_dob").val('');
    $("#customer_wedding_date,#customer_postal_code,#customer_id").val('');
    if(type == 'new'){
        $('#customer_dob,#customer_wedding_date').datepicker('destroy');
        $('#customer_dob').datepicker({format: 'dd-mm-yyyy',endDate: '+0d',defaultViewDate:new Date()});
        $('#customer_wedding_date').datepicker({format: 'dd-mm-yyyy',endDate: '+0d',defaultViewDate:new Date()});
        if($("#customer_phone_existing").val() != ''){
           $("#customer_phone_new").val($("#customer_phone_existing").val()); 
        }
    }
}

function submitCreatePosOrder(){
    $("#pos_order_submit,#pos_order_cancel").attr('disabled',true);
    var ids = '',staff_ids = '';
    for(var i=0;i<billing_prod_list.length;i++){
        ids+=billing_prod_list[i].id+",";
        staff_ids+=billing_prod_list[i].staff_id+",";
    }
    
    ids = ids.substring(0,ids.length-1);
    staff_ids = staff_ids.substring(0,staff_ids.length-1);
    
    var ref_no = $("#eWalletRefNo").val();
    var payment_method = '';
    var WalletAmtValue = $("#E-WalletAmt").val() == '' ? 0 : $("#E-WalletAmt").val();     
    var cardAmtValue = $("#CardAmt").val() == '' ? 0 : $("#CardAmt").val();     
    var cashAmtValue = $("#CashAmt").val() == '' ? 0 : $("#CashAmt").val();  
    
    var voucherApprover = 0; //$("#voucherApprover").val();        
    var voucherComment = $("#voucherComment").val();  
    var voucherAmount = $("#voucherAmount").val();  
    var customer_gst_no = $("#customerGSTNo").val();  
    //var coupon_item_id = $("#coupon_item_id").val();
    
    var form_data = $("#createPosOrderForm").serialize()+"&ids="+ids+"&payment_method="+payment_method+"&ref_no="+ref_no+"&WalletAmtValue="+WalletAmtValue+"&cardAmtValue="+cardAmtValue+"&cashAmtValue="+cashAmtValue +"&voucherApprover="+voucherApprover+"&voucherComment="+voucherComment+"&voucherAmount="+voucherAmount+"&customer_gst_no="+customer_gst_no+"&staff_ids="+staff_ids;//alert(form_data);return;
    $("#pos_add_order_spinner").show();    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/create-updated",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#pos_order_submit,#pos_order_cancel").attr('disabled',false);
            $("#pos_add_order_spinner").hide();            
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_billing_');
                    if(errors != ''){
                        $("#posCreateOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    billing_prod_list = [];
                    $("#posCreateOrderSuccessMessage").html(msg.message).show();
                    $("#posCreateOrderErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#pos_add_order_dialog").modal('hide');window.location.href = ROOT_PATH+'/pos/order/invoice/'+msg.order_data.id; }, 1000);
                }
            }else{
                displayResponseError(msg,"posCreateOrderErrorMessage");
            }
        },error:function(obj,status,error){
            $("#posCreateOrderErrorMessage").html('Error in processing request').show();
            $("#pos_add_order_spinner").hide();
            $("#pos_order_submit,#pos_order_cancel").attr('disabled',false);
        }
    });
}

function selectPOSPaymentMethod(payment_method,elem){
    if(payment_method.toLowerCase() == 'e-wallet'){
        $("#eWalletRefNo").show();
    }else{
        $("#eWalletRefNo").hide();
    }
    
    $(".custom-card").removeClass('payment-btn-sel');
    $(elem).addClass('payment-btn-sel');
    $("#payment_method_hdn").val(payment_method);
}

function validatePosDiscount(discount){
    if(isNaN(discount)){
        $("#discount").val('');
        return false;
    }
    
    if(discount != '' && (discount < 0 || discount > 100)){
        $("#discount").val('');
        return false;
    }
}

function checkExistingPosCustomer(){
    $(".invalid-feedback,#posCreateOrderErrorMessage,#posCreateOrderSuccessMessage").html('').hide();
    $("#exist_cust_spinner").show();    
    $("#exist_cust_btn,#exist_cust_cancel").attr('disabled',true);
    var phone_no = $("#customer_phone_existing").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/create?action=check_exist_customer",
        method:"POST",
        data:{phone_no:phone_no},
        success:function(msg){
            $("#exist_cust_spinner").hide();            
            $("#exist_cust_btn,#exist_cust_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_billing_');
                    if(errors != ''){
                        $("#posCreateOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    //$("#posCreateOrderSuccessMessage").html(msg.message).show();
                    var customer_data = msg.customer_data;
                    $("#posCreateOrderErrorMessage,.invalid-feedback").html('').hide();
                    $(".billing-exist-cust-div").hide();
                    $(".billing-new-cust-div").show();
                    $("#customer_phone_new").val(customer_data.phone);
                    $("#customer_name").val(customer_data.customer_name);
                    $("#customer_salutation").val(customer_data.salutation);
                    $("#customer_email").val(customer_data.email);
                    $("#customer_dob").val(customer_data.dob);
                    $("#customer_wedding_date").val(customer_data.wedding_date);
                    $("#customer_postal_code").val(customer_data.postal_code);
                    $("#customer_id").val(customer_data.id);
                    
                    $('#customer_dob,#customer_wedding_date').datepicker('destroy');
                    
                    if(customer_data.dob != '' && customer_data.dob != null){
                        $('#customer_dob').datepicker({format: 'dd-mm-yyyy',endDate: '+0d',defaultViewDate:customer_data.dob});
                    }else{
                        $('#customer_dob').datepicker({format: 'dd-mm-yyyy',endDate: '+0d'});
                    }
                    if(customer_data.wedding_date != '' && customer_data.wedding_date != null){
                        $('#customer_wedding_date').datepicker({format: 'dd-mm-yyyy',endDate: '+0d',defaultViewDate:customer_data.wedding_date});
                    }else{
                        $('#customer_wedding_date').datepicker({format: 'dd-mm-yyyy',endDate: '+0d'});
                    }
                }
            }else{
                displayResponseError(msg,"posCreateOrderErrorMessage");
            }
        },error:function(obj,status,error){
            $("#posCreateOrderErrorMessage").html('Error in processing request').show();
            $("#exist_cust_spinner").hide();
            $("#exist_cust_btn,#exist_cust_cancel").attr('disabled',false);
        }
    });
}

function editPosOrderPaymentMethod(order_id,id){
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=get_payment_method_data",
        method:"GET",
        data:{order_id:order_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editOrderPaymentMethodErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var payment_data = msg.payment_data;
                    $("#editOrderPaymentMethodErrorMessage").html('').hide();
                    for(var i=0;i<payment_data.length;i++){
                        if(payment_data[i].payment_method.toLowerCase() == 'cash'){
                            $("#payment_amount_cash_edit").val(payment_data[i].payment_amount);
                            $("#payment_received_cash_edit").val(payment_data[i].payment_received);
                        }
                        if(payment_data[i].payment_method.toLowerCase() == 'card'){
                            $("#payment_amount_card_edit").val(payment_data[i].payment_amount);
                            $("#payment_received_card_edit").val(payment_data[i].payment_received);
                        }
                        if(payment_data[i].payment_method.toLowerCase() == 'e-wallet'){
                            $("#payment_amount_ewallet_edit").val(payment_data[i].payment_amount);
                            $("#payment_received_ewallet_edit").val(payment_data[i].payment_received);
                            $("#reference_no_ewallet_edit").val(payment_data[i].reference_number);
                        }
                   }
                   
                   $("#edit_payment_method_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"editOrderPaymentMethodErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editOrderPaymentMethodErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditPosOrderPaymentMethod(order_id){
    ajaxSetup();
    var form_data = $("#editOrderPaymentMethodFrm").serialize();
    form_data = form_data+"&order_id="+order_id;
    
    $("#edit_payment_method_submit,#edit_payment_method_cancel").attr('disabled',true);
    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=update_payment_method_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#edit_payment_method_submit,#edit_payment_method_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editOrderPaymentMethodErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var payment_data = msg.payment_data;
                    $("#editOrderPaymentMethodErrorMessage").html('').hide();
                    $("#editOrderPaymentMethodSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editOrderPaymentMethodErrorMessage");
            }
        },error:function(obj,status,error){
            $("#edit_payment_method_submit,#edit_payment_method_cancel").attr('disabled',false);
            $("#editOrderPaymentMethodErrorMessage").html('Error in processing request').show();
        }
    });
}

function toggleCustomerGSTNoDiv(){
    if($("#customer_gst_no_chk").is(":checked")){
        $("#customerGstNoDiv").show();
    }else{
        $("#customerGstNoDiv").hide();
    }
}

function toggleCashVoucherDiv(){
    if($("#cashvoucher").is(":checked")){
        $("#cashVoucherDiv").show();
    }else{
        $("#cashVoucherDiv").hide();
    }
}

function editPosOrderDate(){
    $("#edit_order_date_dialog").modal('show');
}

function applyPosBillCoupon(){
    return false;
    ajaxSetup();
    var coupon_no = $("#couponNo").val();
    
    $("#applyCouponBtn").attr('disabled',true);
    $.ajax({
        url:ROOT_PATH+"/store/posbilling?action=get_coupon_data&coupon_no="+coupon_no,
        method:"GET",
        success:function(msg){
            $("#applyCouponBtn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#couponAddError").html(errors).show();
                    } 
                }else{ 
                    var coupon_data = msg.coupon_data;
                    $("#couponAddError").html('').hide();
                    $("#couponAddSuccess").html(msg.message).show();
                    $("#applyCouponBtn").addClass('elem-hidden');
                    $("#removeCouponBtn").removeClass('elem-hidden');
                    $("#couponNo").attr("readonly",true);
                    $("#coupon_discount").val(coupon_data.discount);
                    $("#coupon_item_id").val(coupon_data.coupon_item_id);
                    updateBillingTotalPrice('');
                }
            }else{
                displayResponseError(msg,"editOrderPaymentMethodErrorMessage");
            }
        },error:function(obj,status,error){
            $("#applyCouponBtn").attr('disabled',false);
            $("#couponAddError").html('Error in processing request').show();
        }
    });
}

function removePosBillCoupon(){
    return false;
    $("#coupon_discount,#coupon_item_id,#couponNo").val('');
    $("#couponNo").attr("readonly",false);
    $("#applyCouponBtn").removeClass('elem-hidden');
    $("#removeCouponBtn").addClass('elem-hidden');
    $("#couponAddError,#couponAddSuccess").html('').hide();
    
    $("#coupon_discount,#sub_total").html('');
    $(".coupon-sec").css("display","none");
    updateBillingTotalPrice('');
}