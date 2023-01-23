"use strict";

var billing_prod_list=[], total_order_amount = 0;

function addProductToBilling(){
    var ids = '',inv_barcodes_arr = [];
    var prod_barcode = $("#productBarcode").val();
    var staff_id = $("#staff_id").val();
    $("#prodError").html('').hide();
    
    if(prod_barcode == ''){
        setTimeout(function(){ $("#prodError").html('Please enter Product Barcode').show();  }, 200);
        return false;
    }
    
    for(var i=0;i<billing_prod_list.length;i++){
        ids+=billing_prod_list[i]['id']+",";
        inv_barcodes_arr.push(billing_prod_list[i].peice_barcode);
    }
    
    // If user have added inventory barcode, not product barcode
    if(inv_barcodes_arr.indexOf(prod_barcode) >= 0){
        setTimeout(function(){ $("#prodError").html('Product already added').show();  }, 200);
        return false;
    }
    
    if(staff_id == '' || staff_id == 0 || staff_id == '0'){
        setTimeout(function(){ $("#prodError").html('Staff Name is Required Field').show();  }, 200);
        return false;
    }
    
    ids = ids.substring(0,ids.length-1);
    var discount = $("#discount").val();
    var foc = $("#foc").val();
    
    var store_user_id = $("#store_user_id").val();
    var form_data = "barcode="+prod_barcode+"&ids="+ids+"&discount_percent="+discount+"&foc="+foc+"&staff_id="+staff_id+"&store_user_id="+store_user_id;
    
    $("#productBarcode").attr('disabled',true).attr('readonly',true);
            
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/pos/product/detail",
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
                    setTimeout(function(){ $("#productBarcode").attr('disabled',false).attr('readonly',false).focus();  }, 800);
                }else{ 
                    setTimeout(function(){ $("#productBarcode").attr('disabled',false).attr('readonly',false).focus();  }, 800);
                    $("#prodError,.invalid-feedback").html('').hide();
                    var product_data = msg.product_data;
                    if(product_data == null || product_data == ''){
                        $("#prodError").html('Product does not exists').show();
                    }else{
                        if(inv_barcodes_arr.indexOf(product_data.peice_barcode) < 0){
                            $("#productBarcode").val('');
                            //product_data.staff_id = $("#staff_id").val();
                            product_data.discount_percent_orig = product_data.discount_percent;
                            product_data.gst_inclusive_orig = product_data.gst_inclusive;
                            billing_prod_list.push(product_data);
                            updateBillingTotalPrice();
                            $("#productBarcode,#discount").val('');
                            $("#productBarcode").focus();
                        }else{
                            $("#prodError").html('Product already added').show();
                        }
                    }
                }
            }else{
                displayResponseError(msg,"prodError");
                $("#productBarcode").attr('disabled',false).attr('readonly',false).focus(); 
            }
        },error:function(obj,status,error){
            $("#prodError").html('Error in processing request').show();
            $("#pos_prod_billing_spinner").hide();
            $("#productBarcode").attr('disabled',false).attr('readonly',false).focus(); 
        }
    });
}

function deleteBillingProduct(id){
    $('#confirm_delete_billing_product').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_billing_product_btn', function(e) {
        e.preventDefault();
        
        billing_prod_list.splice(id,1);
        updateBillingTotalPrice();
       
        $('#confirm_delete_billing_product').modal('hide');
    });
}

function updateBillingTotalPrice(){
    var str = '',count = 0,tr_css_class = '',total_sale_price = 0,total_discount = 0,total_gst = 0,total_net_price = 0,total_net_price_sale = 0;
    
    for(var i=0;i<billing_prod_list.length;i++){
        var product_data = billing_prod_list[i];
        if(product_data.return_product == 0){
            var net_price = product_data.net_price;
            total_net_price_sale+=parseFloat(net_price);
        }else{
            var net_price = parseFloat(-product_data.net_price_return).toFixed(2);
        }
        
        total_net_price+=parseFloat(net_price);
    }
    
    var coupon_item_id  = $("#coupon_item_id").val();
    var coupon_discount = $("#coupon_discount").val();
    if(coupon_item_id != '' && coupon_discount != ''){
        coupon_discount = parseFloat(coupon_discount);
        var coupon_discount_amount = total_net_price*(coupon_discount/100);
        var coupon_discount_percent = (coupon_discount_amount/total_net_price_sale).toFixed(5);//alert(coupon_discount_percent);
        coupon_discount = coupon_discount_percent;
        
        for(var i=0;i<billing_prod_list.length;i++){
            if(billing_prod_list[i].return_product == 0){
                var discount_amt = billing_prod_list[i].net_price*(coupon_discount);
                billing_prod_list[i].net_price_required = (billing_prod_list[i].net_price-discount_amt).toFixed(2);
                
                if(billing_prod_list[i].net_price >= 1120 && billing_prod_list[i].net_price_required < 1120){
                    var gst_percent_coupon = 5;
                    billing_prod_list[i].gst_percent_coupon = gst_percent_coupon;
                    var discount_req = getCouponDiscount(billing_prod_list[i],true);
                    billing_prod_list[i].gst_percent = 5;
                }else{
                    var gst_percent_coupon = billing_prod_list[i].gst_percent;
                    billing_prod_list[i].gst_percent_coupon = gst_percent_coupon;
                    var discount_req = getCouponDiscount(billing_prod_list[i],false);
                }
                
                billing_prod_list[i].discount_percent = discount_req;
                if(billing_prod_list[i].gst_inclusive == 1){
                    billing_prod_list[i].gst_inclusive = 0;
                }
            }
        }
    }
    
    var str = '',count = 0,tr_css_class = '',total_sale_price = 0,total_discount = 0,total_gst = 0,total_net_price = 0;
                    
    for(var i=0;i<billing_prod_list.length;i++){
        var product_data = billing_prod_list[i];
        if(product_data.return_product == 0){
            var sale_price = product_data.sale_price;
            var discount_amount = product_data.discount_amount;
            var discounted_amount = product_data.discounted_price.toFixed(2);
            var gst_percent = product_data.gst_percent;
            var gst_amount = product_data.gst_amount.toFixed(2);
            var net_price_1 = product_data.net_price;
            var net_price = product_data.net_price.toFixed(2);
            var discount_percent = product_data.discount_percent;
            
            tr_css_class = '';
            var style = '',other_store_name = '';
        }else{
            var sale_price = -product_data.sale_price_return;
            var discount_amount = -product_data.discount_amount_return;
            var discounted_amount = -(product_data.sale_price_return-discount_amount).toFixed(2);
            var gst_amount = (-product_data.gst_amount_return).toFixed(2);
            var net_price = (-product_data.net_price_return).toFixed(2);
            var net_price_1 = (-product_data.net_price_return);
            var discount_percent = (parseFloat(product_data.discount_percent_return)).toFixed(2);
            var gst_percent = product_data.gst_percent_return;
            tr_css_class = 'pos-return-prod-tr';
            var style = 'style = "background-color:#EAC0A6 !important"';
            if(product_data.other_store_prod == 1){
                var other_store_name = " (Sold at "+product_data.other_store_name+")";
            }else{
                other_store_name = '';
            }
        }
        
        str+='<div class="inner-content-val '+tr_css_class+'" '+style+'><div class="val">'+product_data.product_name+" "+product_data.size_name+" "+product_data.color_name+other_store_name+'</div><div class="val-mrp">'+product_data.sale_price+'</div>\
        <div class="val-dis"><span>'+discount_percent+'</span></div><div class="val-dis-price">'+discount_amount+'</div><div class="val-gst-price">'+gst_amount+" ("+gst_percent+'%)</div>\
        <div class="val-qty"><span>1</span></div><div class="val-net">'+net_price+'</div>\
        <div class="val-remove"><i onclick="deleteBillingProduct('+i+');" class="fas fa-trash" aria-hidden="true" style="cursor:pointer;"></i></div>\
        </div>';
        
        total_sale_price+=parseFloat(sale_price);
        total_discount+=parseFloat(discount_amount);
        total_gst+=parseFloat(gst_amount);
        total_net_price+=parseFloat(net_price_1);
        count++;
    }    
    
    $(".billing-prod-list").html(str);
    $("#subtotal_total").html(currency+" "+total_sale_price.toFixed(2));
    $("#discount_total").html(currency+" "+total_discount.toFixed(2));
    $("#cgst_total").html(currency+" "+(total_gst/2).toFixed(2));
    $("#sgst_total").html(currency+" "+(total_gst/2).toFixed(2));
    
    $("#order_total").html(currency+" "+total_net_price.toFixed(2));
    $("#cartitems_total").html('Cart Details ('+count+' Items)');
    total_order_amount = total_net_price.toFixed(2);
    
    if($("#voucherAmount").val()!=''){
        applyVoucher();
    }
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
    var ids = '',discounts = '',discount_ids = '',staff_ids = '',gst_percent = '',gst_inclusive = '',price_data = '';
    for(var i=0;i<billing_prod_list.length;i++){
        ids+=billing_prod_list[i].id+",";
        discounts+=billing_prod_list[i].discount_percent+",";
        discount_ids+=billing_prod_list[i].discount_id+",";
        staff_ids+=billing_prod_list[i].staff_id+",";
        gst_percent+=billing_prod_list[i].gst_percent+",";
        gst_inclusive+=billing_prod_list[i].gst_inclusive+",";
        
        if(billing_prod_list[i].return_product == 0){
            price_data+=billing_prod_list[i].id+":"+billing_prod_list[i].net_price+",";
        }else{
            price_data+=billing_prod_list[i].id+":-"+billing_prod_list[i].net_price_return+",";
        }
    }
    
    ids = ids.substring(0,ids.length-1);
    discounts = discounts.substring(0,discounts.length-1);
    discount_ids = discount_ids.substring(0,discount_ids.length-1);
    staff_ids = staff_ids.substring(0,staff_ids.length-1);
    gst_percent = gst_percent.substring(0,gst_percent.length-1);
    gst_inclusive = gst_inclusive.substring(0,gst_inclusive.length-1);
    price_data = price_data.substring(0,price_data.length-1);
    
    var ref_no = $("#eWalletRefNo").val();
    var payment_method = '';
    var WalletAmtValue = $("#E-WalletAmt").val() == '' ? 0 : $("#E-WalletAmt").val();     
    var cardAmtValue = $("#CardAmt").val() == '' ? 0 : $("#CardAmt").val();     
    var cashAmtValue = $("#CashAmt").val() == '' ? 0 : $("#CashAmt").val();  
    
    var voucherApprover = 0;        
    var voucherComment = $("#voucherComment").val();  
    var voucherAmount = $("#voucherAmount").val();  
    var customer_gst_no = $("#customerGSTNo").val();  
    var coupon_item_id = $("#coupon_item_id").val();
    var draft_id = $("#draft_id").val();
    var order_id = $("#order_id").val();
    var foc = $("#foc").val();
    var bags_count = $("#bags_count").val();
    var store_user_id = $("#store_user_id").val();
    
    var form_data = $("#createPosOrderForm").serialize()+"&ids="+ids+"&discounts="+discounts+"&payment_method="+payment_method+"&ref_no="+ref_no+"&WalletAmtValue="+WalletAmtValue+"&cardAmtValue="+cardAmtValue+"&cashAmtValue="+cashAmtValue +"&voucherApprover="+voucherApprover+"&voucherComment="+voucherComment+"&voucherAmount="+voucherAmount+"&discount_ids="+discount_ids+"&customer_gst_no="+customer_gst_no+"&staff_ids="+staff_ids+"&coupon_item_id="+coupon_item_id+"&gst_percent="+gst_percent+"&gst_inclusive"+gst_inclusive+"&draft_id="+draft_id+"&order_id="+order_id+"&foc="+foc+"&bags_count="+bags_count+"&store_user_id="+store_user_id+"&price_data="+price_data+"&total_net_price="+total_order_amount;//alert(form_data);return;
    $("#pos_add_order_spinner").show();    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/create",
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
                    setTimeout(function(){  
                        $("#pos_add_order_dialog").modal('hide');
                        if(foc == 0){
                            window.location.href = ROOT_PATH+'/pos/order/invoice/'+msg.order_data.id; 
                        }else{
                            window.location.href = ROOT_PATH+'/pos/order/detail/'+msg.order_data.id; 
                        }
                    }, 1000);
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
        $("#voucherAmount,#voucherComment").val('');
        $("#voucher_payment,#order_total_final").html('');
        $(".voucher-sec").hide();
    }
}

function editPosOrderDate(){
    $("#edit_order_date_dialog").modal('show');
}

function applyPosBillCoupon(){
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
                    updateBillingTotalPrice();
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
    $("#coupon_discount,#coupon_item_id,#couponNo").val('');
    $("#couponNo").attr("readonly",false);
    $("#applyCouponBtn").removeClass('elem-hidden');
    $("#removeCouponBtn").addClass('elem-hidden');
    $("#couponAddError,#couponAddSuccess").html('').hide();
    
    $("#coupon_discount,#sub_total").html('');
    $(".coupon-sec").css("display","none");
    updateBillingTotalPrice();
}

function createFakePosOrders(){
    $("#fakePosCreateOrderErrorMessage,#fakePosCreateOrderSuccessMessage,.invalid-feedback").html('').hide();
    $("#fake_pos_add_order_dialog").find('.form-control').val('');
    $(".billing-new-cust-div").hide();
    $(".billing-exist-cust-div").show();
    $("#billing_customer_type_existing").prop('checked',true);
    $("#fake_pos_add_order_dialog").modal('show');
}

function submitCreateFakePosOrders(){
    $("#fake_pos_order_submit,#fake_pos_order_cancel").attr('disabled',true);
    
    var fake_order_store_id = $("#fake_order_store_id").val();
    var fake_order_count = $("#fake_order_count").val();
    var fake_order_date = $("#fake_order_date").val();
    var fake_order_discount = $("#fake_order_discount").val();
    var fake_order_gst_type = $("#fake_order_gst_type").val();
   
    $("#fake_pos_add_order_spinner").show();    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/orders/fake/create",
        method:"POST",
        data:{fake_order_store_id:fake_order_store_id,fake_order_count:fake_order_count,fake_order_date:fake_order_date,fake_order_discount:fake_order_discount,fake_order_gst_type:fake_order_gst_type},
        success:function(msg){
            $("#fake_pos_order_submit,#fake_pos_order_cancel").attr('disabled',false);
            $("#fake_pos_add_order_spinner").hide();            
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_billing_');
                    if(errors != ''){
                        $("#fakePosCreateOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#fakePosCreateOrderSuccessMessage").html(msg.message).show();
                    $("#fakePosCreateOrderErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#fake_pos_add_order_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"fakePosCreateOrderErrorMessage");
            }
        },error:function(obj,status,error){
            $("#fakePosCreateOrderErrorMessage").html('Error in processing request').show();
            $("#fake_pos_add_order_spinner").hide();
            $("#fake_pos_order_submit,#fake_pos_order_cancel").attr('disabled',false);
        }
    });
}

function savePosOrderDraft(){
    $("#save_pos_order_draft_dialog").modal('show');
    if(billing_prod_list.length == 0){
        $("#savePosOrderDraftErrorMessage").html('Products list is empty').show();
        return false;
    }
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/orders/draft/save",
        method:"POST",
        //data:{inv_barcodes_arr:inv_barcodes_arr},
        data:{billing_prod_list},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#savePosOrderDraftErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#savePosOrderDraftSuccessMessage").html(msg.message).show();
                    $("#savePosOrderDraftErrorMessage,.invalid-feedback").html('').hide();
                    var draft_data = msg.draft_data;
                    
                    setTimeout(function(){  
                        $("#save_pos_order_draft_dialog").modal('hide');
                        if(draft_data != null && draft_data != ''){
                            window.location.href = ROOT_PATH+'/store/posbilling?draft_id='+draft_data.id;  
                        }
                    }, 1500);
                }
            }else{
                displayResponseError(msg,"savePosOrderDraftErrorMessage");
            }
        },error:function(obj,status,error){
            $("#savePosOrderDraftErrorMessage").html('Error in processing request').show();
        }
    });
}

function loadPosDraft(id){
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/orders/draft/items/"+id,
        method:"GET",
        data:{},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#create_pos_order_error_dialog").modal('show');
                        $("#createPosOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var products_barcodes = msg.products_barcodes;
                    var staff_ids = msg.staff_ids;
                    for(var i=0;i<products_barcodes.length;i++){
                        $("#productBarcode").val(products_barcodes[i]);
                        $("#staff_id").val(staff_ids[i]);
                        addProductToBilling();
                    }
                    $("#productBarcode").val('');
                }
            }else{
                displayResponseError(msg,"createPosOrderErrorMessage");
            }
        },error:function(obj,status,error){
            $("#create_pos_order_error_dialog").modal('show');
            $("#createPosOrderErrorMessage").html('Error in processing request').show();
        }
    });
}

function deletePosOrderDrafts(){
    var chk_class = 'pos_product-list-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#deletePosOrderDraftErrorMessage").html('Please select drafts').show();
        $("#delete_pos_order_draft_dialog").modal('show');
        return false;
    }
   
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/orders/draft/delete",
        method:"POST",
        data:{deleteChkArray:deleteChkArray},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#deletePosOrderDraftErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#delete_pos_order_draft_dialog").modal('show');
                    $("#deletePosOrderDraftSuccessMessage").html(msg.message).show();
                    $("#deletePosOrderDraftErrorMessage,.invalid-feedback").html('').hide();
                    
                    setTimeout(function(){  
                        $("#delete_pos_order_draft_dialog").modal('hide');
                        window.location.reload();
                    }, 2000);
                }
            }else{
                displayResponseError(msg,"deletePosOrderDraftErrorMessage");
            }
        },error:function(obj,status,error){
            $("#deletePosOrderDraftErrorMessage").html('Error in processing request').show();
        }
    });
}

function createFakePosOrdersFromCsv(){
    $("#fakePosOrderCsvFile").val('');
    $("#uploadFakePosOrderCSVSuccessMessage,#uploadFakePosOrderCSVErrorMessage").html('').hide();
    $("#uploadFakePosOrderCsvDialog").modal('show');
}

function submitCreateFakePosOrdersFromCsv(){
    $("#fakePosOrderCsvForm").submit();
}

$("#fakePosOrderCsvForm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    formData.append('store_id',$("#fake_order_csv_store_id").val());
    $("#upload_fake_pos_order_csv_spinner").show();
    $("#updateFakePosOrderCsvBtn,#updateFakePosOrderCsvCancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/pos/orders/fake/csv/create",
        success: function(msg){		
            $("#upload_fake_pos_order_csv_spinner").hide();
            $("#updateFakePosOrderCsvBtn,#updateFakePosOrderCsvCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#uploadFakePosOrderCSVErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#uploadFakePosOrderCSVSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#uploadFakePosOrderCsvDialog").modal('hide');window.location.reload(); }, 1500);
                }
            }else{
                displayResponseError(msg,"uploadFakePosOrderCSVErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#uploadFakePosOrderCSVErrorMessage").html('Error in processing request').show();
            $("#upload_fake_pos_order_csv_spinner").hide();
            $("#updateFakePosOrderCsvBtn,#updateFakePosOrderCsvCancel").attr('disabled',false);
        }
    });
});

function displayCalculatePosOrderAmount(){
    $("#pos_order_calculate_amount_dialog").modal('show');
    $("#cash_amount_calculate,#card_amount_calculate,#ewallet_amount_calculate,#return_amount_calculate").val('');
    $("#bill_total_calculate").val(total_order_amount);
}

function calculatePosOrderAmount(){
    var cash_amount = ($("#cash_amount_calculate").val()!='' && !isNaN($("#cash_amount_calculate").val()))?$("#cash_amount_calculate").val():0;
    var card_amount = ($("#card_amount_calculate").val()!='' && !isNaN($("#card_amount_calculate").val()))?$("#card_amount_calculate").val():0;
    var ewallet_amount = ($("#ewallet_amount_calculate").val()!='' && !isNaN($("#ewallet_amount_calculate").val()))?$("#ewallet_amount_calculate").val():0;
    var total_amount = parseFloat(cash_amount)+parseFloat(card_amount)+parseFloat(ewallet_amount);
    var return_amount = total_amount-total_order_amount;
    $("#return_amount_calculate").val(return_amount.toFixed(2));
}

function applyVoucher(){
    var error_msg = '';
    var voucher_amount = ($("#voucherAmount").val()!='' && !isNaN($("#voucherAmount").val()))?parseFloat($("#voucherAmount").val()):0;
    
    if(total_order_amount <= 0){
        error_msg = 'Order Total Amount should be greater than 0';
    }else if(voucher_amount <= 0){
        error_msg = 'Voucher Amount should be greater than 0';
    }else if(voucher_amount > total_order_amount){
        error_msg = 'Voucher Amount should be less than Order Total Amount';
    }else if($("#voucherComment").val() == ''){
        error_msg = 'Voucher Comment is Required Field';
    }else{
        
    }
    
    if(error_msg != ''){
        $("#create_pos_order_error").html('<div class="alert alert-danger">'+error_msg+'</div>').show();
        $("#create_pos_order_error_dialog").modal('show');
        $("#voucher_payment,#order_total_final").html('');
        $(".voucher-sec").hide();
        return false;
    }
    
    if(voucher_amount > 0){
        $("#voucher_payment").html("- "+currency+" "+voucher_amount.toFixed(2));
        $("#order_total_final").html(currency+" "+(parseFloat(total_order_amount)-parseFloat(voucher_amount)).toFixed(2));
        $(".voucher-sec").show();
    }
}

function holdBills(){
    $("#holdBillsDialog").modal('show');
    $("#holdBillsErrorMessage,#holdBillsSuccessMessage,.invalid-feedback").html('').hide();
    $("#hold_bills_count").val('').focus();
}

function submitHoldBills(){
    var hold_bills_count = $("#hold_bills_count").val();
    $("#hold_bills_spinner").show();
    $("#holdBillsErrorMessage,#holdBillsSuccessMessage,.invalid-feedback").html('').hide();
    $("#holdBillsCancel,#holdBillsBtn").attr('disabled',true);
    
    ajaxSetup();
    
    $.ajax({
        url:ROOT_PATH+"/pos/order/hold",
        method:"POST",
        data:{hold_bills_count:hold_bills_count},
        success:function(msg){
            $("#holdBillsCancel,#holdBillsBtn").attr('disabled',false);
            $("#hold_bills_spinner").hide();
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#holdBillsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#holdBillsSuccessMessage").html(msg.message).show();
                    $("#holdBillsErrorMessage,.invalid-feedback").html('').hide();
                    
                    setTimeout(function(){  
                        $("#holdBillsDialog").modal('hide');
                        window.location.reload();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"holdBillsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#holdBillsCancel,#holdBillsBtn").attr('disabled',false);
            $("#hold_bills_spinner").hide();
            $("#holdBillsErrorMessage").html('Error in processing request').show();
        }
    });
}

function cancelPosOrder(){
    $("#cancelPosOrderDialog").modal('show');
    $("#cancelPosOrderErrorMessage,#cancelPosOrderSuccessMessage").html('').hide();
    $("#comments_cancel_order").val('').focus();
}

function submitCancelPosOrder(){
    var order_id = $("#order_id_hdn").val();
    var form_data = $("#cancelPosOrderForm").serialize();
    form_data = form_data+"&order_id="+order_id;
    $("#cancelPosOrderErrorMessage,#cancelPosOrderSuccessMessage").html('').hide();
    $("#cancelPosOrderSubmit,#cancelPosOrderCancel").attr('disabled',true);
    
    ajaxSetup();
    
    $.ajax({
        url:ROOT_PATH+"/pos/order/cancel",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#cancelPosOrderSubmit,#cancelPosOrderCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#cancelPosOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPosOrderErrorMessage").html('').hide();
                    $("#cancelPosOrderSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#cancelPosOrderDialog").modal('hide'); window.location.href = ROOT_PATH+'/pos/order/detail/'+order_id; }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPosOrderErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPosOrderSubmit,#cancelPosOrderCancel").attr('disabled',false);
            $("#cancelPosOrderErrorMessage").html('Error in processing request').show();
        }
    });
}

function downloadPosOrders(){
    $("#downloadPosOrdersDialog .form-control").val('');
    $("#error_validation_pos_orders_count").html('').hide();
    $("#downloadPosOrdersDialog").modal('show');
}

function submitDownloadPosOrders(){
    var pos_orders_count = $("#pos_orders_count").val(), str = '';
    $("#error_validation_pos_orders_count").html('').hide();
    if(pos_orders_count == ''){
        $("#error_validation_pos_orders_count").html('Pos Orders Records is Required Field').show();
        return false;
    }
    
    const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);

    var url = ROOT_PATH+"/pos/order/list?action=download_csv&pos_orders_count="+pos_orders_count+"&"+str;
    window.location.href = url;
}

function updateSearchValue(){
    if($("#search_by").val() != ''){
        var text = $("#search_by option:selected").text();
        $("#search_value").attr('placeholder',text);
    }
}

function createFocOrderType(){
    $("#foc_order_store_id").val('-1');
    $("#error_validation_create_foc_order_type").html('').hide();
    $("#createFocOrderTypeDialog").modal('show');
}

function submitCreateFocOrderType(){
    var store_id = $("#foc_order_store_id").val();
    if(store_id == -1){
        $("#error_validation_create_foc_order_type").html('Warehouse / Store is Required Field').show();
        return;
    }
    var url = ROOT_PATH+"/store/posbilling?foc=1&store_id="+store_id;
    window.location.href = url;
}