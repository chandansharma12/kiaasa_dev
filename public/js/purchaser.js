"use strict";

$(document).ready(function(){
    loadGSTRules();
});

var purchase_order_rows = [];
function displayQuotationForm(design_id,sku,version,reviewer,prod_count){
    if(prod_count != 0){
        $("#quotation_design_id").val(design_id);
        $("#quotation_emails,#quotation_message").val("");
        $(".invalid-feedback").html("");
        $("#requestQuotationErrorMessage,#requestQuotationSuccessMessage").html('').hide();
        if(sku == null || sku == 'null') sku = '&mdash;';
        $("#request_quotation_sku").html(sku+" (Version: "+version+")");
        $("#request_quotation_reviewer").html(reviewer);
        $("#request_quotation_dialog").modal('show');
    }else{
        $("#error_request_quotation_dialog").modal('show');
    }
}

function requestQuotation(){
    var form_data = $("#requestQuotationFrm").serialize();
    var quotation_design_id = $("#quotation_design_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/quotation/requestquotation/"+quotation_design_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_request_quotation_');
                    if(errors != ''){
                        $("#requestQuotationErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#requestQuotationErrorMessage").html('').hide();
                    $("#requestQuotationSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $('#request_quotation_dialog').modal('hide'); }, 2000);
                }
            }else{
                displayResponseError(msg,"requestQuotationErrorMessage");
            }
        },error:function(obj,status,error){
            $("#requestQuotationSuccessMessage").html('').hide();
            $("#requestQuotationErrorMessage").html(error).show();
        }
    });
}

function requestQuotationBySKU(){
    var id_str = '';
    $(".design_id-chk").each(function(){
        if($(this).is(":checked")){
            id_str+=$(this).val()+",";
        }
    });
    
    if(id_str == ''){
        $("#error_request_quotation_sku").modal('show');
        return false;
    }
    
    id_str = id_str.substring(0,id_str.length-1);
    $("#design_ids").val(id_str);
    $("#sku_quotation_form").submit();
}

function requestSKUQuotation(){
    var str = '';
    if($('.fabric_id-chk:checked').length == 0 && $('.acc_id-chk:checked').length == 0 && $('.process_id-chk:checked').length == 0){
        $("#error_request_quotation_sku").modal('show');
        return;
    }
    
    str='<div class="table-responsive table-filter"><table class="table table-striped sku-quotation-tbl">';
    
    str+='<tr><th colspan="10">Fabric</th></tr><tr><th>Name</th><th>Color</th><th>Width</th><th></th><th>Purchase Quantity</th></tr>';
    $(".fabric_id-chk").each(function(){
        if($(this).is(":checked")){
            var fabric_arr = $(this).val().split('__');
            var qty = $("#fabric_qty_"+fabric_arr[4]).val();
            str+='<tr><td>'+fabric_arr[0]+'</td><td>'+fabric_arr[1]+'</td><td>'+fabric_arr[2]+" "+fabric_arr[3]+'</td><td></td><td>'+qty+'</td></tr>';
        }
    })
    
    str+='<tr><th colspan="10">Accessories</th></tr><tr><th>Category</th><th>SubCategory</th><th>Color</th><th>Size</th><th>Purchase Quantity</th></tr>';
    $(".acc_id-chk").each(function(){
        if($(this).is(":checked")){
            var acc_arr = $(this).val().split('__');
            var qty = $("#acc_qty_"+acc_arr[4]).val();
            str+='<tr><td>'+acc_arr[0]+'</td><td>'+acc_arr[1]+'</td><td>'+acc_arr[2]+'</td><td>'+acc_arr[3]+'</td><td>'+qty+'</td></tr>';
        }
    })
    
    str+='<tr><th colspan="10">Process</th></tr><tr><th>Category</th><th>Type</th><th></th><th>Purchase Quantity</th></tr>';
    $(".process_id-chk").each(function(){
        if($(this).is(":checked")){
            var process_arr = $(this).val().split('__');
            var qty = $("#process_qty_"+process_arr[2]).val();
            str+='<tr><td>'+process_arr[0]+'</td><td>'+process_arr[1]+'</td><td></td><td>'+qty+'</td></tr>';
        }
    })
    
    str+='</table></div>';
    
    $("#quotation_sku_data").html(str);
    $("#skuQuotationErrorMessage,#skuQuotationSuccessMessage").html('').hide();
    $("#request_quotation_sku_dialog").modal('show');
}

function confirmSKUQuotation(){
    var master_id_str = '',qty_str = '';
    
    $(".fabric_id-chk").each(function(){
        if($(this).is(":checked")){
            var instance_id = $(this).attr('id').replace("chk_fabric_id_",'');
            var qty = $("#fabric_qty_"+instance_id).val();
            master_id_str+=instance_id+",";
            qty_str+=qty+",";
        }
    });
    
    $(".acc_id-chk").each(function(){
        if($(this).is(":checked")){
            var instance_id = $(this).attr('id').replace("chk_acc_id_",'');
            var qty = $("#acc_qty_"+instance_id).val();
            master_id_str+=instance_id+",";
            qty_str+=qty+",";
        }
    });
    
    $(".process_id-chk").each(function(){
        if($(this).is(":checked")){
            var instance_id = $(this).attr('id').replace("chk_process_id_",'');
            var qty = $("#process_qty_"+instance_id).val();
            master_id_str+=instance_id+",";
            qty_str+=qty+",";
        }
    });
    
    master_id_str = master_id_str.substring(0,master_id_str.length-1);
    qty_str = qty_str.substring(0,qty_str.length-1);
    
    var vendor_ids = $("#vendor_ids").val();
    if(vendor_ids == '' || vendor_ids == null){
        $("#skuQuotationErrorMessage").html('Please select vendors').show();;
        return false;
    }
    
    var form_data = "master_ids="+master_id_str+"&qty_list="+qty_str+"&vendor_ids="+vendor_ids;
    $("#sku_quotation_spinner").show();
    
    $("#request_quotation_sku_btn,#request_quotation_sku_cancel").attr("disabled",true);
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchaser/addquotation",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#sku_quotation_spinner").hide();
            $("#request_quotation_sku_btn,#request_quotation_sku_cancel").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#skuQuotationSuccessMessage").html(data.message).show();
                    $("#skuQuotationErrorMessage").html('').hide();
                    setTimeout(function(){  $("#request_quotation_sku_dialog").modal('hide'); }, 2000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#skuQuotationErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"skuQuotationErrorMessage");
            }
        },error:function(obj,status,error){
            $("#skuQuotationErrorMessage").html(error).show();
            $("#sku_quotation_spinner").hide();
            $("#request_quotation_sku_btn,#request_quotation_sku_cancel").attr("disabled",false);
        }
    });
}

function addPurchaseOrderRow(){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    
    var row_data = {}, error_msg = '';
    row_data.style = $("#style_name").val();
    row_data.color = $("#color_id").val();
    row_data.hsn_code = $("#hsn_code").val();
    row_data.color_name = $("#color_name").val(); //$("#color_id option:selected").text();
    $(".size-item").each(function(){
        if($(this).val() != ''){
            row_data[$(this).attr('id')] = $(this).val();
        }
    });
    
    row_data.size_total = $("#size_total").val();
    row_data.style_rate = $("#style_rate").val();
    
    for(var i=0;i<purchase_order_rows.length;i++){
        if(purchase_order_rows[i].style == row_data.style){
            error_msg = 'Style: '+row_data.style+' is already added </br>';
            break;
        }
    }
    
    if(row_data.color == '' || row_data.color == null){
        error_msg+='Style Color is required field </br>';
    }
    
    if(row_data.style_rate == '' || row_data.style_rate == null){
        error_msg+='Style Rate is required field </br>';
    }
    
    if(row_data.size_total == ''){
        error_msg+='Size data is required field </br>';
    }
    
    if(error_msg != ''){
        $("#OrderRowStatusErrorMessage").html(error_msg).show();
        return false;
    }
    
    purchase_order_rows.push(row_data);
    displayPurchaseOrderRows();
    
    $("#style_name,#color_name,#style_rate,#size_total,#amount_total,.size-item").val('');
    $("#style_name").focus();
}

function displayPurchaseOrderRows(){
    var total_data = new Object();
    var str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Style</th><th>Color</th>';
    for(var i=0;i<size_list.length;i++){
        var size_name = size_list[i][1];
        str+='<th>'+size_name+'</th>';
    }

    str+='<th>Rate</th><th>Total Qty</th><th>Amount</th><th>Delete</th></tr></thead>'

    for(var i=0;i<purchase_order_rows.length;i++){
        str+='<tr><td>'+(i+1)+'</td><td>'+purchase_order_rows[i].style+'</td><td>'+purchase_order_rows[i].color_name+'</td>';
        for(var q=0;q<size_list.length;q++){
            var size_id = size_list[q][0];
            var size_val = (typeof purchase_order_rows[i]['size_'+size_id] != 'undefined')?purchase_order_rows[i]['size_'+size_id]:'';
            str+='<td>'+size_val+'</td>';
            
            if(typeof total_data[size_id] != 'undefined'){ 
                if(size_val != '') total_data[size_id]+=parseInt(size_val);
            }else{
                if(size_val != '') total_data[size_id]=parseInt(size_val);else total_data[size_id] = 0;
            }
        }
        
        var total_amount = (purchase_order_rows[i].size_total*purchase_order_rows[i].style_rate).toFixed(2);
        //var gst_data = getGSTData(purchase_order_rows[i].hsn_code,total_amount);
        var gst_data = getGSTData(purchase_order_rows[i].hsn_code,purchase_order_rows[i].style_rate);
        purchase_order_rows[i].gst_percent = gst_data.rate_percent;//alert(gst_data.rate_percent);
        purchase_order_rows[i].gst_amount = ((gst_data.rate_percent/100)*total_amount);
        str+='<td>'+purchase_order_rows[i].style_rate+'</td><td>'+purchase_order_rows[i].size_total+'</td><td>'+total_amount+'</td>';
        str+='<td><a href="javascript:;" title="Delete" onclick="deletePurchaseOrderRow('+i+')"><i class="fas fa-trash"></i></a></td></tr>';
        
        if(typeof total_data.qty != 'undefined')total_data.qty+= parseInt(purchase_order_rows[i].size_total);else total_data.qty = parseInt(purchase_order_rows[i].size_total);
        if(typeof total_data.amount != 'undefined')total_data.amount+= parseFloat(total_amount);else total_data.amount = parseFloat(total_amount);
        if(typeof total_data.gst_amount != 'undefined')total_data.gst_amount+= parseFloat(purchase_order_rows[i].gst_amount);else total_data.gst_amount = parseFloat(purchase_order_rows[i].gst_amount);
    }
    
    if(purchase_order_rows.length > 0){
        str+='<tr><th colspan="3">Total</th>';
        for(var q=0;q<size_list.length;q++){
            var size_id = size_list[q][0];
            if(typeof total_data[size_id] != 'undefined') var size_val = total_data[size_id];else total_data[size_id] = '';
            str+='<th>'+total_data[size_id]+'</th>';
        }
        str+='<th></th><th>'+total_data.qty+'</th><th>'+currency+" "+(total_data.amount).toFixed(2)+'</th><th></th></tr>';
        
        var colspan = parseInt(size_list.length)+5;
        str+='<tr><td colspan="'+colspan+'">GST</td><td>'+currency+" "+(total_data.gst_amount).toFixed(2)+'</td><td></td></tr>';
        var other_cost = ($("#other_cost").val()!='')?$("#other_cost").val():0;
        var total_cost = (parseFloat(total_data.amount)+parseFloat(other_cost)+parseFloat(total_data.gst_amount)).toFixed(2);
        str+='<tr><td colspan="'+colspan+'">Other Cost</td><td >'+currency+" "+other_cost+'</td><td></td></tr>';
        str+='<tr><th colspan="'+colspan+'">Total Cost</th><th>'+currency+" "+total_cost+'</th><th></th></tr>';
        
        $(".submit-data-row").removeClass('elem-hidden');
    }else{
        $(".submit-data-row").addClass('elem-hidden');
    }
    
    str+='</table></div>';
    $("#purchase_order_rows_list").html(str);
}

function deletePurchaseOrderRow(index){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage").html('').hide();
    $('#confirm_delete_row_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_style_row_btn', function(e) {
        e.preventDefault();
        purchase_order_rows.splice(index,1);
        displayPurchaseOrderRows();
        $('#confirm_delete_row_item').modal('hide');
    });
}

function createPurchaseOrder(){
    var error_msg = '';
   
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    var vendor_id = $('#vendor_id').val();
    var delivery_date = $("#delivery_date").val();
    var category_id = $("#category_id").val();
    
    if(purchase_order_rows.length == 0){
        error_msg+="Style is Required Field <br>";
    }
    if(vendor_id == ''){
        error_msg+="Vendor is Required Field <br>";
    }
    if(delivery_date == ''){
        error_msg+="Delivery date is Required Field <br>";
    }
    if(category_id == ''){
        error_msg+="Category is Required Field <br>";
    }
    
    if(error_msg != ''){
        $("#purchaseOrderCreateErrorMessage").html(error_msg).show();
        document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
        return false;
    }
    
    $("#create_purchase_order_spinner").show();
    $("#create_purchase_order_submit").attr("disabled",true);
    
    var other_cost = $("#other_cost").val();
    var other_comments = $("#other_comments").val();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/create?create_order=1",
        method:"POST",
        data:{rows:purchase_order_rows,vendor_id:vendor_id,other_cost:other_cost,other_comments:other_comments,delivery_date:delivery_date,category_id:category_id},
        success:function(data){
            $("#create_purchase_order_spinner").hide();
            $("#create_purchase_order_submit").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#purchaseOrderCreateSuccessMessage").html(data.message).show();
                    $("#purchaseOrderCreateErrorMessage").html('').hide();
                    document.getElementById("purchaseOrderCreateSuccessMessage").scrollIntoView();
                    setTimeout(function(){  window.location.reload(); }, 2000);
                }else{    
                    $("#purchaseOrderCreateErrorMessage").html(data.message).show();
                    document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
                }
            }else{
                displayResponseError(data,"purchaseOrderCreateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrderCreateErrorMessage").html(error).show();
            $("#create_purchase_order_spinner").hide();
            $("#create_purchase_order_submit").attr("disabled",false);
        }
    });
}

function updatePOTotalQtyCost(){
    var total_qty = 0;
    $(".size-item").each(function(){
        if($(this).val() != '' && isNaN($(this).val())){
           $(this).val('');
        } 
        
        if($(this).val() != ''){
            total_qty+=parseInt($(this).val());
        }
    });
    
    $("#size_total").val(total_qty);
    
    if($("#style_rate").val() != ''){
        if(isNaN($("#style_rate").val())){
            $("#style_rate").val('');
        }
        var amount_total = ($("#style_rate").val()*total_qty).toFixed(2);
        $("#amount_total").val(amount_total);
    }
}

function getStyleData(val){
    if(val == ''){
        return false;
    }
    $(".size-item").attr('disabled',false).val('');
    $("#color_name,#style_rate,#size_total,#amount_total").val('');
    $("#style_row_add_spinner").show();
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/create?style_data=1",
        method:"POST",
        data:{style:val},
        success:function(data){
            $("#style_row_add_spinner").hide();
            
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    //$("#OrderRowStatusSuccessMessage").html(data.message).show();
                    var error_msg='';
                    $("#OrderRowStatusErrorMessage").html('').hide();
                    var style_data = data.style_data;
                    var parameter_arr = ['color_name','base_price','hsn_code'];
                    for(var i=0;i<parameter_arr.length;i++){
                        if(style_data[parameter_arr[i]] == null || style_data[parameter_arr[i]] == ''){
                            error_msg+= parameter_arr[i].replace('_',' ').toUpperCase()+' is not added for product';
                        }
                    }
                    
                    if(style_data['base_price'] == 0 || style_data['base_price'] == '0'){
                        error_msg+='Base Price is not added for product';
                    }
                    
                    if(error_msg != ''){
                        $("#OrderRowStatusErrorMessage").html(error_msg).show();
                        return false;
                    }
    
                    $("#color_name").val(style_data.color_name);
                    $("#color_id").val(style_data.color_id);
                    //var base_price = (parseFloat(style_data.base_price)+parseFloat(style_data.base_price*.10)).toFixed(2);
                    $("#style_rate").val(style_data.base_price);
                    $("#hsn_code").val(style_data.hsn_code);
                    
                    $(".size-item").each(function(){
                        var size_id = parseInt($(this).attr('id').replace('size_',''));
                        if(style_data.size_id_list.indexOf(size_id) < 0){
                            $(this).attr('disabled',true);
                        }
                    });
                }else{    
                    $("#OrderRowStatusErrorMessage").html(data.message).show();
                }
            }else{
                displayResponseError(data,"OrderRowStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#OrderRowStatusErrorMessage").html(error).show();
            $("#style_row_add_spinner").hide();
        }
    });
}

function editPurchaseOrder(po_id,id){
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/edit/"+po_id+"?action=get_po_item_data",
        method:"GET",
        data:{po_id:po_id,id:id},
        success:function(data){
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#editPoErrorMessage").html('').hide();
                    var po_items = data.po_items;
                    
                    var str = '<div class="table-responsive table-filter">\
                    <table class="table table-striped admin-table" cellspacing="0"><thead><tr class="header-tr">';
                    for(var i=0;i<po_items.length;i++){
                        str+='<th colspan="2">'+po_items[i].size+'</th>';
                    }
                    
                    str+='</tr><tr>';
                    
                    for(var i=0;i<po_items.length;i++){
                        str+='<th style="text-align:left;">Ord</th><th style="text-align:left;">Rec</th>';
                    }
                    str+='</tr></thead><tbody><tr>';
                    
                    for(var i=0;i<po_items.length;i++){
                        str+='<td><input type="text" value="'+po_items[i].size_count+'" name="size_'+po_items[i].size_id+'" id="size_'+po_items[i].size_id+'" class="form-control"></td>\
                        <td><input type="text" value="'+po_items[i].size_count_rec+'" name="size_rec_'+po_items[i].size_id+'" id="size_rec_'+po_items[i].size_id+'" class="form-control" readonly="true"></td>';
                    }
                    
                    str+='</tr></tbody></table>';
                    
                    $("#edit_po_data").html(str);
                    $("#po_item_id").val(id);
                    $("#edit_po_dialog").modal('show');
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#editPoErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"editPoErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPoErrorMessage").html(error).show();
        }
    });
}

function submitEditPurchaseOrder(po_id){
    $("#edit_po_btn,#edit_po_cancel").attr("disabled",true);
    $("#edit-po-spinner").show();
    var form_data = $("#editPoForm").serialize()+"&po_id="+po_id;
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/edit/"+po_id+"?action=update_po_item_data",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#edit-po-spinner").hide();
            $("#edit_po_btn,#edit_po_cancel").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#editPoSuccessMessage").html(data.message).show();
                    $("#editPoErrorMessage").html('').hide();
                    setTimeout(function(){  $("#edit_po_dialog").modal('hide');window.location.reload(); }, 1000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#editPoErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"editPoErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPoErrorMessage").html(error).show();
            $("#edit-po-spinner").hide();
            $("#edit_po_btn,#edit_po_cancel").attr("disabled",false);
        }
    });
}

function editPurchaseOrderItemRate(po_id,id){
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/edit/"+po_id+"?action=get_po_item_data",
        method:"GET",
        data:{po_id:po_id,id:id},
        success:function(data){
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#editPoErrorMessage").html('').hide();
                    var po_item_data = data.po_item_data;
                    $("#item_sku_edit").val(po_item_data.vendor_sku);
                    $("#item_rate_edit").val(po_item_data.rate);
                    $("#po_item_id_1").val(id);
                    $("#edit_po_rate_dialog").modal('show');
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#editPoItemRateErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"editPoItemRateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPoItemRateErrorMessage").html(error).show();
        }
    });
}

function submitEditPurchaseOrderItemRate(po_id){
    $("#edit_po_item_rate_btn,#edit_po_item_rate_cancel").attr("disabled",true);
    var form_data = $("#editPoItemRateForm").serialize()+"&po_id="+po_id;
    $("#editPoItemRateErrorMessage,#editPoItemRateoSuccessMessage").html('').hide();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/edit/"+po_id+"?action=update_po_item_rate_data",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#edit_po_item_rate_btn,#edit_po_item_rate_cancel").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#editPoItemRateoSuccessMessage").html(data.message).show();
                    $("#editPoItemRateErrorMessage").html('').hide();
                    setTimeout(function(){  $("#edit_po_rate_dialog").modal('hide');window.location.reload(); }, 1000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editPoItemRateErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"editPoItemRateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPoItemRateErrorMessage").html(error).show();
            $("#edit_po_item_rate_btn,#edit_po_item_rate_cancel").attr("disabled",false);
        }
    });
}

function updateFabricNameData(elem_value){
    var fabric_name_id = elem_value;
    if(fabric_name_id == ''){
        $("#width_id,#content_id,#gsm_id").html('<option value="">Select One</option>');
        return false;
    }
    var form_data = "pid="+fabric_name_id;
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/design/getlookupitemsdata",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#purchaseOrderCreateErrorMessage").html('').hide();
            if(objectPropertyExists(data,'status')){
                var options_arr = [];
                if(data.status == 'success'){
                    var design_lookup_items = data.design_lookup_items;
                    for(var i=0;i<design_lookup_items.length;i++){
                        if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_quality'){
                            //options_arr['quality']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_width'){
                            options_arr['width']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_content'){
                            options_arr['content']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_count'){
                            //options_arr['count']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_gsm'){
                            options_arr['gsm']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }
                    }

                    $("#width_id").html('<option value="">Select One</option>'+options_arr['width']);
                    $("#content_id").html('<option value="">Select One</option>'+options_arr['content']);
                    $("#gsm_id").html('<option value="">Select One</option>'+options_arr['gsm']);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#purchaseOrderCreateErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"purchaseOrderCreateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrderCreateErrorMessage").html("Error in processing request").show();
        }
    });
}

var purchase_order_bulk_rows = [];

function getBulkPOStyleData(val){
    if(val == ''){
        return false;
    }
    
    $("#hsn_code").val('');
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/create?style_data=1",
        method:"POST",
        data:{id:val},
        success:function(data){
           if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    var error_msg='';
                    $("#OrderRowStatusErrorMessage").html('').hide();
                    var style_data = data.style_data;
                    
                    if(error_msg != ''){
                        $("#OrderRowStatusErrorMessage").html(error_msg).show();
                        return false;
                    }
    
                    $("#hsn_code").val(style_data.hsn_code);
                    
                }else{    
                    $("#OrderRowStatusErrorMessage").html(data.message).show();
                }
            }else{
                displayResponseError(data,"OrderRowStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#OrderRowStatusErrorMessage").html(error).show();
        }
    });
}

function addBulkPurchaseOrderRow(){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    
    var row_data = {}, error_msg = '';
    row_data.design_id = $("#design_id").val();
    row_data.fabric_id = $("#fabric_id").val();
    row_data.width_id = $("#width_id").val();
    row_data.content_id = $("#content_id").val(); 
    row_data.gsm_id = $("#gsm_id").val(); 
    row_data.color_id = $("#color_id").val(); 
    row_data.unit_id = $("#unit_id").val();
    row_data.hsn_code = $("#hsn_code").val();
    
    row_data.design_name = $("#design_id option:selected").text();
    row_data.fabric_name = $("#fabric_id option:selected").text();
    row_data.width_name = $("#width_id option:selected").text();
    row_data.content_name = $("#content_id option:selected").text();
    row_data.gsm_name = $("#gsm_id option:selected").text();
    row_data.unit_name = $("#unit_id option:selected").text();
    row_data.color_name =  $("#color_name").val(); 
    
    row_data.style_rate = $("#style_rate").val();
    row_data.style_qty = $("#style_qty").val();
    
    for(var i=0;i<purchase_order_bulk_rows.length;i++){
        if(purchase_order_bulk_rows[i].design_id == row_data.design_id){
            //error_msg = 'Style: '+row_data.design_name+' is already added </br>';
            //break;
        }
    }
    
    if(row_data.design_id == '' || row_data.design_id == null){
        error_msg+='Style is required field </br>';
    }
    
    if(row_data.fabric_id == '' || row_data.fabric_id == null){
        error_msg+='Fabric is required field </br>';
    }
    
    if(row_data.width_id == '' || row_data.width_id == null){
        error_msg+='Width is required field </br>';
    }
    
    if(row_data.content_id == '' || row_data.content_id == null){
        error_msg+='Content is required field </br>';
    }
    
    if(row_data.gsm_id == '' || row_data.gsm_id == null){
        error_msg+='GSM is required field </br>';
    }
    
    if(row_data.style_rate == '' || row_data.style_rate == null){
        error_msg+='Style Rate is required field </br>';
    }
    
    if(row_data.style_qty == '' || row_data.style_qty == null){
        error_msg+='Style Quantity is required field </br>';
    }
    
    if(row_data.unit_id == '' || row_data.unit_id == null){
        error_msg+='Unit is required field </br>';
    }
    
    if(row_data.hsn_code == '' || row_data.hsn_code == null){
        error_msg+='HSN Code of Design is required field </br>';
    }
    
    if(error_msg != ''){
        $("#OrderRowStatusErrorMessage").html(error_msg).show();
        return false;
    }
    
    purchase_order_bulk_rows.push(row_data);
    displayBulkPurchaseOrderRows();
    
    $("#design_id,#fabric_id,#width_id,#content_id,#gsm_id,#style_rate,#style_qty,#amount_total,#color_id,#color_name").val('');
    $("#add_fabric_color_span").html('Select One');
    $("#design_id").focus();
}

function displayBulkPurchaseOrderRows(){
    var total_data = new Object();
    var str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Style</th><th>Fabric</th>';
    str+='<th>Width</th><th>Content</th><th>GSM</th><th>Color</th><th>Rate</th><th>Qty</th><th>Amount</th><th>Delete</th></tr></thead>'

    for(var i=0;i<purchase_order_bulk_rows.length;i++){
        str+='<tr><td>'+(i+1)+'</td><td>'+purchase_order_bulk_rows[i].design_name+'</td><td>'+purchase_order_bulk_rows[i].fabric_name+'</td><td>'+purchase_order_bulk_rows[i].width_name+'</td><td>'+purchase_order_bulk_rows[i].content_name+'</td><td>'+purchase_order_bulk_rows[i].gsm_name+'</td><td>'+purchase_order_bulk_rows[i].color_name+'</td>';
        
        
        var total_amount = (purchase_order_bulk_rows[i].style_qty*purchase_order_bulk_rows[i].style_rate).toFixed(2);
        
        var gst_data = getGSTData(purchase_order_bulk_rows[i].hsn_code,purchase_order_bulk_rows[i].style_rate);
        //var gst_data = []; 
        //gst_data.rate_percent = 5;
        purchase_order_bulk_rows[i].gst_percent = gst_data.rate_percent;
        purchase_order_bulk_rows[i].gst_amount = ((gst_data.rate_percent/100)*total_amount);
        str+='<td>'+purchase_order_bulk_rows[i].style_rate+'</td><td>'+purchase_order_bulk_rows[i].style_qty+'</td><td>'+total_amount+'</td>';
        str+='<td><a href="javascript:;" title="Delete" onclick="deleteBulkPurchaseOrderRow('+i+')"><i class="fas fa-trash"></i></a></td></tr>';
        
        if(typeof total_data.qty != 'undefined')total_data.qty+= parseInt(purchase_order_bulk_rows[i].style_qty);else total_data.qty = parseInt(purchase_order_bulk_rows[i].style_qty);
        if(typeof total_data.amount != 'undefined')total_data.amount+= parseFloat(total_amount);else total_data.amount = parseFloat(total_amount);
        if(typeof total_data.gst_amount != 'undefined')total_data.gst_amount+= parseFloat(purchase_order_bulk_rows[i].gst_amount);else total_data.gst_amount = parseFloat(purchase_order_bulk_rows[i].gst_amount);
    }
    
    if(purchase_order_bulk_rows.length > 0){
        str+='<tr><th colspan="8">Total</th>';
        
        str+='<th>'+total_data.qty+'</th><th>'+currency+" "+(total_data.amount).toFixed(2)+'</th><th></th></tr>';
        
        var colspan = 9;
        str+='<tr><td colspan="'+colspan+'">GST</td><td>'+currency+" "+(total_data.gst_amount).toFixed(2)+'</td><td></td></tr>';
        var other_cost = ($("#other_cost").val()!='')?$("#other_cost").val():0;
        var total_cost = (parseFloat(total_data.amount)+parseFloat(other_cost)+parseFloat(total_data.gst_amount)).toFixed(2);
        str+='<tr><td colspan="'+colspan+'">Other Cost</td><td >'+currency+" "+other_cost+'</td><td></td></tr>';
        str+='<tr><th colspan="'+colspan+'">Total Cost</th><th>'+currency+" "+total_cost+'</th><th></th></tr>';
        
        $(".submit-data-row").removeClass('elem-hidden');
    }else{
        $(".submit-data-row").addClass('elem-hidden');
    }
    
    str+='</table></div>';
    $("#purchase_order_bulk_rows_list").html(str);
}

function deleteBulkPurchaseOrderRow(index){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage").html('').hide();
    $('#confirm_delete_row_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_style_row_btn', function(e) {
        e.preventDefault();
        purchase_order_bulk_rows.splice(index,1);
        displayBulkPurchaseOrderRows();
        $('#confirm_delete_row_item').modal('hide');
    });
}

function createBulkPurchaseOrder(){
    var error_msg = '';
   
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    var vendor_id = $('#vendor_id').val();
    var delivery_date = $("#delivery_date").val();
    var category_id = $("#category_id").val();
    
    if(purchase_order_bulk_rows.length == 0){
        error_msg+="Style is Required Field <br>";
    }
    if(vendor_id == ''){
        error_msg+="Vendor is Required Field <br>";
    }
    if(delivery_date == ''){
        error_msg+="Delivery date is Required Field <br>";
    }
    if(category_id == ''){
        error_msg+="Category is Required Field <br>";
    }
    
    var category_name = $("#category_id option:selected").text().toLowerCase();
    if(category_name == 'dyeing' || category_name == 'printing'){
        for(var i=0;i<purchase_order_bulk_rows.length;i++){
            if(purchase_order_bulk_rows[i].color_id == '' || purchase_order_bulk_rows[i].color_id == null){
                error_msg+="Color is Required Field for Row "+(i+1)+" <br>";
                break;
            }
        }
    }
    
    if(error_msg != ''){
        $("#purchaseOrderCreateErrorMessage").html(error_msg).show();
        document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
        return false;
    }
    
    $("#create_purchase_order_spinner").show();
    $("#create_purchase_order_submit").attr("disabled",true);
    
    var other_cost = $("#other_cost").val();
    var other_comments = $("#other_comments").val();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/create?create_order=1",
        method:"POST",
        data:{rows:purchase_order_bulk_rows,vendor_id:vendor_id,other_cost:other_cost,other_comments:other_comments,delivery_date:delivery_date,category_id:category_id},
        success:function(data){
            $("#create_purchase_order_spinner").hide();
            $("#create_purchase_order_submit").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#purchaseOrderCreateSuccessMessage").html(data.message).show();
                    $("#purchaseOrderCreateErrorMessage").html('').hide();
                    document.getElementById("purchaseOrderCreateSuccessMessage").scrollIntoView();
                    var po_details = data.po_details;
                    var url = ROOT_PATH+"/purchase-order/bulk/detail/"+po_details.id;
                    setTimeout(function(){  window.location.href = url; }, 1500);
                }else{    
                    $("#purchaseOrderCreateErrorMessage").html(data.message).show();
                    document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
                }
            }else{
                displayResponseError(data,"purchaseOrderCreateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrderCreateErrorMessage").html(error).show();
            $("#create_purchase_order_spinner").hide();
            $("#create_purchase_order_submit").attr("disabled",false);
        }
    });
}

function updateBulkPOCost(){
    var style_rate = ($("#style_rate").val() == '' || isNaN($("#style_rate").val()))?0:$("#style_rate").val();
    var style_qty = ($("#style_qty").val() == '' || isNaN($("#style_qty").val()))?0:$("#style_qty").val();
    
    var total_amt = (style_rate*style_qty).toFixed(2);
    $("#amount_total").val(total_amt);
    
}

function submitAddBulkPOInvoice(){
    $("#addVehicleDetailsForm").submit();
}

$("#addVehicleDetailsForm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    formData.append('vehicle_no', $("#vehicle_no").val());
    formData.append('containers_count', $("#containers_count").val());
    formData.append('comments', $("#comments").val());
    formData.append('invoice_no', $("#invoice_no").val());
    formData.append('invoice_date', $("#invoice_date").val());
    formData.append('products_count', $("#products_count").val());
    formData.append('po_id', $("#po_id").val());
    
    var po_detail_id = 0;
    
    $("#vehicle_details_add_spinner").show();
    $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',true);
    $(".invalid-feedback,#addVehicleDetailsSuccessMessage,#addVehicleDetailsErrorMessage").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/purchase-order/bulk/items/import/"+po_detail_id+"?action=add_vehicle_details",
        success: function(msg){		
            $("#vehicle_details_add_spinner").hide();
            $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addVehicleDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addVehicleDetailsSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_vehicle_details_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addVehicleDetailsErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addVehicleDetailsErrorMessage").html('Error in processing request').show();
            $("#vehicle_details_add_spinner").hide();
            $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',false);
        }
    });
});

function displayAddBulkPOInvoice(){
    $("#vehicle_no,#containers_count,#comments,.form-control").val('');
    
    $("#add_po_invoice_dialog").modal('show');
}

function displayVehicleDetailsContainerImages(containers_count){
    if(containers_count == ''){
        $("#vehicle_details_container_images").html('');
        return false;
    }
    var str = '';
    for(var i=1;i<=containers_count;i++){
        str+='<div class="form-group col-md-4"><input type="file" name="container_image_'+i+'" id="container_image_'+i+'" class="form-control"><div class="invalid-feedback" id="error_validation_container_image_'+i+'"></div></div>';
    }
    
    $("#vehicle_details_container_images").html(str);
}

function displayBulkPOInvoiceDetails(id){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/items/import/"+id+"?action=get_po_invoice_details",
        method:"get",
        data:{id:id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#inventoryVehicleDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#po_invoice_detail_dialog").modal('show');
                    $("#vehicle_no_detail").val(msg.vehicle_details.vehicle_no);
                    $("#containers_count_detail").val(msg.vehicle_details.containers_count);
                    $("#comments_detail").val(msg.vehicle_details.comments);
                   
                    $("#invoice_no_detail").val(msg.vehicle_details.invoice_no);
                    $("#invoice_date_detail").val(displayDate(msg.vehicle_details.invoice_date));
                    $("#products_count_detail").val(msg.vehicle_details.products_count);
                    $("#po_detail_id").val(id);
                    
                    var images_list = msg.vehicle_details.images_list;
                    var str = '';
                    str+='<div class="row">';
                    for(var i=0;i<images_list.length;i++){
                        var img_url = ROOT_PATH+'/images/po_images/'+msg.vehicle_details.po_id+"/thumbs/"+images_list[i];
                        str+='<div class="col-md-3"><img src="'+img_url+'" class="inventory-container-image"></div>';
                        if(i > 0 && (i+1)%4 == 0 ){
                            str+='</div><div class="separator-10">&nbsp;</div><div class="row">';
                        } 
                    }
                    str+='</div>';
                    
                    $("#container_images_detail").html(str);
                }
            }else{
                displayResponseError(msg,"inventoryVehicleDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryVehicleDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitUpdateBulkPOInvoiceDetails(){
    $("#updateInvoiceDetailsErrorMessage,#updateInvoiceDetailsSuccessMessage").html('').hide();
    $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var form_data = $("#vehicleDetailForm").serialize();
    form_data+="&po_id="+po_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/items/import/"+po_detail_id+"?action=update_po_invoice_details",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateInvoiceDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateInvoiceDetailsSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#po_invoice_detail_dialog").modal('hide');window.location.reload();  }, 1000);
                }
            }else{
                displayResponseError(msg,"updateInvoiceDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',false);
            $("#updateInvoiceDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayAddBulkPOGRN(){
    $("#addInventoryGRNSuccessMessage,#addInventoryGRNErrorMessage").html('').hide();
    $("#add_inventory_grn_comments").val('');
    $("#add_inventory_grn_dialog").modal('show');
}

function submitAddBulkPOGRN(){
    $("#addInventoryGRNSuccessMessage,#addInventoryGRNErrorMessage").html('').hide();
    $("#add_inventory_grn_spinner").show();
    $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var add_inventory_grn_comments = $("#add_inventory_grn_comments").val();
    
    var form_data = $("#importBulkPOItemsFrm").serialize();
    form_data+="&po_id="+po_id+"&add_inventory_grn_comments="+add_inventory_grn_comments+"&po_detail_id="+po_detail_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/items/import/"+po_detail_id+"?action=add_bulk_po_grn",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#add_inventory_grn_spinner").hide();
            $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryGRNErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryGRNSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_inventory_grn_dialog").modal('hide');window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,"addInventoryGRNErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_inventory_grn_spinner").hide();
            $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',false);
            $("#addInventoryGRNErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayAddBulkPOQC(){
    $("#addInventoryQCSuccessMessage,#addInventoryQCErrorMessage").html('').hide();
    $("#add_inventory_qc_comments").val('');
    $("#add_inventory_qc_dialog").modal('show');
}

function submitAddBulkPOQC(){
    $("#addInventoryQCSuccessMessage,#addInventoryQCErrorMessage").html('').hide();
    $("#add_inventory_qc_spinner").show();
    $("#add_inventory_qc_cancel,#add_inventory_qc_submit").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var add_inventory_qc_comments = $("#add_inventory_qc_comments").val();
    
    var form_data = $("#qcBulkPOItemsFrm").serialize();
    form_data+="&po_id="+po_id+"&add_inventory_qc_comments="+add_inventory_qc_comments+"&po_detail_id="+po_detail_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/items/qc/"+po_detail_id+"?action=add_bulk_po_qc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#add_inventory_qc_spinner").hide();
            $("#add_inventory_qc_cancel,#add_inventory_qc_submit").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryQCErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryQCSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_inventory_qc_dialog").modal('hide');window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,"addInventoryQCErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_inventory_qc_spinner").hide();
            $("#add_inventory_qc_cancel,#add_inventory_qc_submit").attr('disabled',false);
            $("#addInventoryQCErrorMessage").html('Error in processing request').show();
        }
    });
}


function returnBulkInventoryToVendor(id){
    $("#qc_return_id").val(id);
    $("#returnInventoryToVendorDialog").modal('show');
}

function submitReturnBulkInventoryToVendor(){
    $("#returnInventoryToVendorSpinner").show();
    $("#returnInventoryToVendorCancelSubmit,#returnInventoryToVendorCancel").attr('disabled',true);
    $("#returnInventoryToVendorErrorMessage,#returnInventoryToVendorSuccessMessage").html('').hide();
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var boxes_count = $("#boxes_count").val();
    var transporter_name = $("#transporter_name").val();
    var transporter_gst = $("#transporter_gst").val();
    var docket_no = $("#docket_no").val();
    var eway_bill_no = $("#eway_bill_no").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/items/qc/"+po_detail_id+"?action=confirm_return_bulk_po_items",
        method:"POST",
        data:{po_id:po_id,po_detail_id:po_detail_id,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no},
        success:function(msg){
            $("#returnInventoryToVendorSpinner").hide();
            $("#returnInventoryToVendorCancelSubmit,#returnInventoryToVendorCancel").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#returnInventoryToVendorErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#returnInventoryToVendorSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#return_inventory_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"inventoryReturnErrorMessage");
            }
        },error:function(obj,status,error){
            $("#returnInventoryToVendorSpinner").hide();
            $("#returnInventoryToVendorCancelSubmit,#returnInventoryToVendorCancel").attr('disabled',false);
            $("#returnInventoryToVendorErrorMessage").html('Error in processing request').show();
        }
    });
}

function editReturnInvGatePassData(){
    $("#editGatePassDialog").modal('show');
}

function submitEditReturnInvGatePassData(){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var form_data = $("#editGatePassForm").serialize()+"&po_id="+po_id+"&po_detail_id="+po_detail_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=update_return_inv_gate_pass_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editGatePassErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editGatePassSuccessMessage").html(msg.message).show();
                    
                    setTimeout(function(){  $("#editGatePassDialog").modal('hide');window.location.reload();  }, 1000);
                }
            }else{
                displayResponseError(msg,"editGatePassErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editGatePassErrorMessage").html('Error in processing request').show();
        }
    });
}

function getBulkFinishedStyleData(val){
    if(val == ''){
        return false;
    }
    $(".size-item").attr('disabled',false).val('');
    $("#color_name,#style_rate,#size_total,#amount_total").val('');
    $("#style_row_add_spinner").show();
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/finished/create?style_data=1",
        method:"POST",
        data:{style:val},
        success:function(data){
            $("#style_row_add_spinner").hide();
            
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    var error_msg='';
                    $("#OrderRowStatusErrorMessage").html('').hide();
                    var style_data = data.style_data;
                    var parameter_arr = ['color_name','base_price'];
                    for(var i=0;i<parameter_arr.length;i++){
                        if(style_data[parameter_arr[i]] == null || style_data[parameter_arr[i]] == ''){
                            error_msg+= parameter_arr[i].replace('_',' ').toUpperCase()+' is not added for product';
                        }
                    }
                    
                    if(style_data['base_price'] == 0 || style_data['base_price'] == '0'){
                        error_msg+='Cost Price is not added for product';
                    }
                    
                    if(error_msg != ''){
                        $("#OrderRowStatusErrorMessage").html(error_msg).show();
                        return false;
                    }
    
                    $("#color_name").val(style_data.color_name);
                    $("#color_id").val(style_data.color_id);
                    $("#style_rate").val(style_data.base_price);
                    $("#hsn_code").val(style_data.hsn_code);
                    
                    $(".size-item").each(function(){
                        var size_id = parseInt($(this).attr('id').replace('size_',''));
                        if(style_data.size_id_list.indexOf(size_id) < 0){
                            $(this).attr('disabled',true);
                        }
                    });
                }else{    
                    $("#OrderRowStatusErrorMessage").html(data.message).show();
                }
            }else{
                displayResponseError(data,"OrderRowStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#OrderRowStatusErrorMessage").html(error).show();
            $("#style_row_add_spinner").hide();
        }
    });
}

function addBulkFinishedPurchaseOrderRow(){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    
    var row_data = {}, error_msg = '';
    row_data.style = $("#style_name").val();
    row_data.color = $("#color_id").val();
    row_data.color_name = $("#color_name").val(); 
    row_data.hsn_code = $("#hsn_code").val();
    
    $(".size-item").each(function(){
        if($(this).val() != ''){
            row_data[$(this).attr('id')] = $(this).val();
        }
    });
    
    row_data.size_total = $("#size_total").val();
    row_data.style_rate = $("#style_rate").val();
    
    for(var i=0;i<purchase_order_rows.length;i++){
        if(purchase_order_rows[i].style == row_data.style){
            error_msg = 'Style: '+row_data.style+' is already added </br>';
            break;
        }
    }
    
    if(row_data.color == '' || row_data.color == null){
        error_msg+='Style Color is required field </br>';
    }
    
    if(row_data.style_rate == '' || row_data.style_rate == null){
        error_msg+='Style Rate is required field </br>';
    }
    
    if(row_data.size_total == ''){
        error_msg+='Size data is required field </br>';
    }
    
    if(error_msg != ''){
        $("#OrderRowStatusErrorMessage").html(error_msg).show();
        return false;
    }
    
    purchase_order_rows.push(row_data);
    displayBulkFinishedPurchaseOrderRows();
    
    $("#style_name,#color_name,#style_rate,#size_total,#amount_total,.size-item").val('');
    $("#style_name").focus();
}

function displayBulkFinishedPurchaseOrderRows(){
    var total_data = new Object();
    var str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Style</th><th>Color</th>';
    for(var i=0;i<size_list.length;i++){
        var size_name = size_list[i][1];
        str+='<th>'+size_name+'</th>';
    }

    str+='<th>Rate</th><th>Total Qty</th><th>Amount</th><th>Delete</th></tr></thead>'

    for(var i=0;i<purchase_order_rows.length;i++){
        str+='<tr><td>'+(i+1)+'</td><td>'+purchase_order_rows[i].style+'</td><td>'+purchase_order_rows[i].color_name+'</td>';
        for(var q=0;q<size_list.length;q++){
            var size_id = size_list[q][0];
            var size_val = (typeof purchase_order_rows[i]['size_'+size_id] != 'undefined')?purchase_order_rows[i]['size_'+size_id]:'';
            str+='<td>'+size_val+'</td>';
            
            if(typeof total_data[size_id] != 'undefined'){ 
                if(size_val != '') total_data[size_id]+=parseInt(size_val);
            }else{
                if(size_val != '') total_data[size_id]=parseInt(size_val);else total_data[size_id] = 0;
            }
        }
        
        var total_amount = (purchase_order_rows[i].size_total*purchase_order_rows[i].style_rate).toFixed(2);
        
        var gst_data = getGSTData(purchase_order_rows[i].hsn_code,purchase_order_rows[i].style_rate);
        //var gst_data = new Object();
        //gst_data.rate_percent = (purchase_order_rows[i].style_rate <= 1000)?5:12;
        purchase_order_rows[i].gst_percent = gst_data.rate_percent;
        purchase_order_rows[i].gst_amount = ((gst_data.rate_percent/100)*total_amount);
        str+='<td>'+purchase_order_rows[i].style_rate+'</td><td>'+purchase_order_rows[i].size_total+'</td><td>'+total_amount+'</td>';
        str+='<td><a href="javascript:;" title="Delete" onclick="deletePurchaseOrderRow('+i+')"><i class="fas fa-trash"></i></a></td></tr>';
        
        if(typeof total_data.qty != 'undefined')total_data.qty+= parseInt(purchase_order_rows[i].size_total);else total_data.qty = parseInt(purchase_order_rows[i].size_total);
        if(typeof total_data.amount != 'undefined')total_data.amount+= parseFloat(total_amount);else total_data.amount = parseFloat(total_amount);
        if(typeof total_data.gst_amount != 'undefined')total_data.gst_amount+= parseFloat(purchase_order_rows[i].gst_amount);else total_data.gst_amount = parseFloat(purchase_order_rows[i].gst_amount);
    }
    
    if(purchase_order_rows.length > 0){
        str+='<tr><th colspan="3">Total</th>';
        for(var q=0;q<size_list.length;q++){
            var size_id = size_list[q][0];
            if(typeof total_data[size_id] != 'undefined') var size_val = total_data[size_id];else total_data[size_id] = '';
            str+='<th>'+total_data[size_id]+'</th>';
        }
        str+='<th></th><th>'+total_data.qty+'</th><th>'+currency+" "+(total_data.amount).toFixed(2)+'</th><th></th></tr>';
        
        var colspan = parseInt(size_list.length)+5;
        str+='<tr><td colspan="'+colspan+'">GST</td><td>'+currency+" "+(total_data.gst_amount).toFixed(2)+'</td><td></td></tr>';
        var other_cost = ($("#other_cost").val()!='')?$("#other_cost").val():0;
        var total_cost = (parseFloat(total_data.amount)+parseFloat(other_cost)+parseFloat(total_data.gst_amount)).toFixed(2);
        str+='<tr><td colspan="'+colspan+'">Other Cost</td><td >'+currency+" "+other_cost+'</td><td></td></tr>';
        str+='<tr><th colspan="'+colspan+'">Total Cost</th><th>'+currency+" "+total_cost+'</th><th></th></tr>';
        
        $(".submit-data-row").removeClass('elem-hidden');
    }else{
        $(".submit-data-row").addClass('elem-hidden');
    }
    
    str+='</table></div>';
    $("#purchase_order_rows_list").html(str);
}

function deleteBulkFinishedPurchaseOrderRow(index){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage").html('').hide();
    $('#confirm_delete_row_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_style_row_btn', function(e) {
        e.preventDefault();
        purchase_order_rows.splice(index,1);
        displayPurchaseOrderRows();
        $('#confirm_delete_row_item').modal('hide');
    });
}

function createBulkFinishedPurchaseOrder(){
    var error_msg = '';
   
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    
    var vendor_id = $('#vendor_id').val();
    var vendor_id = $('#vendor_id').val();
    var delivery_date = $("#delivery_date").val();
    var category_id = $("#category_id").val();
    
    if(purchase_order_rows.length == 0){
        error_msg+="Style is Required Field <br>";
    }
    
    if(vendor_id == ''){
        error_msg+="Vendor is Required Field <br>";
    }
    if(delivery_date == ''){
        error_msg+="Delivery date is Required Field <br>";
    }
    if(category_id == ''){
        error_msg+="Category is Required Field <br>";
    }
    
    if(error_msg != ''){
        $("#purchaseOrderCreateErrorMessage").html(error_msg).show();
        document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
        return false;
    }
    
    $("#create_purchase_order_spinner").show();
    $("#create_purchase_order_submit").attr("disabled",true);
    
    var other_cost = $("#other_cost").val();
    var other_comments = $("#other_comments").val();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/finished/create?create_order=1",
        method:"POST",
        data:{rows:purchase_order_rows,vendor_id:vendor_id,other_cost:other_cost,other_comments:other_comments,delivery_date:delivery_date,category_id:category_id},
        success:function(data){
            $("#create_purchase_order_spinner").hide();
            $("#create_purchase_order_submit").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#purchaseOrderCreateSuccessMessage").html(data.message).show();
                    $("#purchaseOrderCreateErrorMessage").html('').hide();
                    document.getElementById("purchaseOrderCreateSuccessMessage").scrollIntoView();
                    
                    var url = ROOT_PATH+"/purchase-order/product/detail/"+data.po_data.id;
                    setTimeout(function(){  window.location.href = url; }, 2000);
                }else{    
                    $("#purchaseOrderCreateErrorMessage").html(data.message).show();
                    document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
                }
            }else{
                displayResponseError(data,"purchaseOrderCreateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrderCreateErrorMessage").html(error).show();
            $("#create_purchase_order_spinner").hide();
            $("#create_purchase_order_submit").attr("disabled",false);
        }
    });
}

function editReturnBulkPOItemsGatePassData(){
    $("#editGatePassDialog").modal('show');
}

function submitEditReturnBulkPOItemsGatePassData(){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var form_data = $("#editGatePassForm").serialize()+"&po_id="+po_id+"&po_detail_id="+po_detail_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/items/qc/"+po_detail_id+"?action=update_return_inv_gate_pass_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editGatePassErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editGatePassSuccessMessage").html(msg.message).show();
                    
                    setTimeout(function(){  $("#editGatePassDialog").modal('hide');window.location.reload();  }, 1000);
                }
            }else{
                displayResponseError(msg,"editGatePassErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editGatePassErrorMessage").html('Error in processing request').show();
        }
    });
}

/*
function submitAddBulkFinishedPOInvoice(){
    $("#addBulkFinishedPOVehicleDetailsForm").submit();
}

$("#addBulkFinishedPOVehicleDetailsForm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    formData.append('vehicle_no', $("#vehicle_no").val());
    formData.append('containers_count', $("#containers_count").val());
    formData.append('comments', $("#comments").val());
    formData.append('invoice_no', $("#invoice_no").val());
    formData.append('invoice_date', $("#invoice_date").val());
    formData.append('products_count', $("#products_count").val());
    formData.append('po_id', $("#po_id").val());
    
    var po_id = $("#po_id").val();
    
    $("#vehicle_details_add_spinner").show();
    $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',true);
    $(".invalid-feedback,#addVehicleDetailsSuccessMessage,#addVehicleDetailsErrorMessage").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/purchase-order/bulk/finished/invoice/list/"+po_id+"?action=add_vehicle_details",
        success: function(msg){		
            $("#vehicle_details_add_spinner").hide();
            $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addVehicleDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addVehicleDetailsSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_vehicle_details_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addVehicleDetailsErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addVehicleDetailsErrorMessage").html('Error in processing request').show();
            $("#vehicle_details_add_spinner").hide();
            $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',false);
        }
    });
});

function displayAddBulkFinishedPOInvoice(){
    $("#vehicle_no,#containers_count,#comments,.form-control").val('');
    var po_id = $("#po_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/finished/invoice/list/"+po_id+"?action=get_pending_import_inv_count",
        method:"GET",
        data:{po_id:po_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#purchaseOrdersErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var options_str = '<option value="">Select</option>';
                    var inventory_count = parseInt(msg.inventory_count);
                    for(var i=0;i<inventory_count;i++){
                        var z = i+1;
                        options_str+='<option value="'+z+'">'+z+'</option>';
                    }
                    $("#products_count").html(options_str);
                    $("#add_po_invoice_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"purchaseOrdersErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrdersErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayBulkFinishedVehicleDetailsContainerImages(containers_count){
    if(containers_count == ''){
        $("#vehicle_details_container_images").html('');
        return false;
    }
    var str = '';
    for(var i=1;i<=containers_count;i++){
        str+='<div class="form-group col-md-4"><input type="file" name="container_image_'+i+'" id="container_image_'+i+'" class="form-control"><div class="invalid-feedback" id="error_validation_container_image_'+i+'"></div></div>';
    }
    
    $("#vehicle_details_container_images").html(str);
}

function displayBulkFinishedPOInvoiceDetails(id){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/finished/invoice/list/"+id+"?action=get_inventory_detail",
        method:"get",
        data:{id:id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#inventoryVehicleDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#po_invoice_detail_dialog").modal('show');
                    $("#vehicle_no_detail").val(msg.vehicle_details.vehicle_no);
                    $("#containers_count_detail").val(msg.vehicle_details.containers_count);
                    $("#comments_detail").val(msg.vehicle_details.comments);
                    //$("#grn_comments_detail").val(msg.grn_data.comments);
                    $("#invoice_no_detail").val(msg.vehicle_details.invoice_no);
                    $("#invoice_date_detail").val(displayDate(msg.vehicle_details.invoice_date));
                    $("#products_count_detail").val(msg.vehicle_details.products_count);
                    $("#po_detail_id").val(id);
                    
                    var images_list = msg.vehicle_details.images_list;
                    var str = '';
                    str+='<div class="row">';
                    for(var i=0;i<images_list.length;i++){
                        var img_url = ROOT_PATH+'/images/po_images/'+msg.vehicle_details.po_id+"/thumbs/"+images_list[i];
                        str+='<div class="col-md-3"><img src="'+img_url+'" class="inventory-container-image"></div>';
                        if(i > 0 && (i+1)%4 == 0 ){
                            str+='</div><div class="separator-10">&nbsp;</div><div class="row">';
                        } 
                    }
                    str+='</div>';
                    
                    $("#container_images_detail").html(str);
                }
            }else{
                displayResponseError(msg,"inventoryVehicleDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryVehicleDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitUpdateBulkFinishedPOInvoiceDetails(){
    $("#updateInvoiceDetailsErrorMessage,#updateInvoiceDetailsSuccessMessage").html('').hide();
    $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var form_data = $("#vehicleDetailForm").serialize();
    form_data+="&po_id="+po_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/bulk/finished/invoice/list/"+po_detail_id+"?action=update_po_invoice_details",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateInvoiceDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateInvoiceDetailsSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#po_invoice_detail_dialog").modal('hide');window.location.reload();  }, 1000);
                }
            }else{
                displayResponseError(msg,"updateInvoiceDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',false);
            $("#updateInvoiceDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}
*/


function updateBulkQCDefectiveItem(qty,id){
    var acc = $("#qc_acc_"+id).val();
    
    if(isNaN(acc) || acc < 0 || acc > qty){
        $("#qc_acc_"+id).val('');
        $("#qc_def_"+id).val('');
        return false;
    }
    
    var defective = qty-acc;
    $("#qc_def_"+id).val(defective);
}


var purchase_order_accessories_rows = [];

function getAccessoriesPOItemData(val){
    if(val == ''){
        return false;
    }
    
    $("#acc_rate").val('');
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/accessories/create?item_data=1",
        method:"POST",
        data:{id:val},
        success:function(data){
           if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    var error_msg='';
                    $("#OrderRowStatusErrorMessage").html('').hide();
                    var item_data = data.item_data;
                    
                    if(error_msg != ''){
                        $("#OrderRowStatusErrorMessage").html(error_msg).show();
                        return false;
                    }
                    
                    $("#item_rate").val(item_data.rate);
                    $("#gst_percent").val(item_data.gst_percent);
    
                }else{    
                    $("#OrderRowStatusErrorMessage").html(data.message).show();
                }
            }else{
                displayResponseError(data,"OrderRowStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#OrderRowStatusErrorMessage").html(error).show();
        }
    });
}


function addAccessoriesPurchaseOrderRow(){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    
    var row_data = {}, error_msg = '';
    row_data.item_id = $("#item_id").val();
    row_data.item_name = $("#item_id option:selected").text();
    row_data.item_id = $("#item_id").val();
    row_data.gst_percent = $("#gst_percent").val();
    
    $(".size-item").each(function(){
        if($(this).val() != ''){
            row_data[$(this).attr('id')] = $(this).val();
        }
    });
    
    row_data.size_total = $("#size_total").val();
    row_data.item_rate = $("#item_rate").val();
    
    for(var i=0;i<purchase_order_accessories_rows.length;i++){
        if(purchase_order_accessories_rows[i].item_id == row_data.item_id){
            error_msg = 'Item: '+row_data.item_name+' is already added </br>';
            break;
        }
    }
    
    if(row_data.item_rate == '' || row_data.item_rate == null){
        error_msg+='Item Rate is required field </br>';
    }
    
    if(row_data.size_total == ''){
        error_msg+='Size data is required field </br>';
    }
    
    if(error_msg != ''){
        $("#OrderRowStatusErrorMessage").html(error_msg).show();
        return false;
    }
    
    purchase_order_accessories_rows.push(row_data);
    displayAccessoriesPurchaseOrderRows();
    
    $("#item_id,#item_rate,#size_total,#amount_total,.size-item").val('');
    $("#item_id").focus();
}

function displayAccessoriesPurchaseOrderRows(){
    var total_data = new Object();
    var str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Item</th>';
    for(var i=0;i<size_list.length;i++){
        var size_name = size_list[i][1];
        str+='<th>'+size_name+'</th>';
    }

    str+='<th>Rate</th><th>Total Qty</th><th>Amount</th><th>Delete</th></tr></thead>'

    for(var i=0;i<purchase_order_accessories_rows.length;i++){
        str+='<tr><td>'+(i+1)+'</td><td>'+purchase_order_accessories_rows[i].item_name+'</td>';
        for(var q=0;q<size_list.length;q++){
            var size_id = size_list[q][0];
            var size_val = (typeof purchase_order_accessories_rows[i]['size_'+size_id] != 'undefined')?purchase_order_accessories_rows[i]['size_'+size_id]:'';
            str+='<td>'+size_val+'</td>';
            
            if(typeof total_data[size_id] != 'undefined'){ 
                if(size_val != '') total_data[size_id]+=parseInt(size_val);
            }else{
                if(size_val != '') total_data[size_id]=parseInt(size_val);else total_data[size_id] = 0;
            }
        }
        
        var total_amount = (purchase_order_accessories_rows[i].size_total*purchase_order_accessories_rows[i].item_rate).toFixed(2);
        
        
        purchase_order_accessories_rows[i].gst_amount = ((purchase_order_accessories_rows[i].gst_percent/100)*total_amount);
        str+='<td>'+purchase_order_accessories_rows[i].item_rate+'</td><td>'+purchase_order_accessories_rows[i].size_total+'</td><td>'+total_amount+'</td>';
        str+='<td><a href="javascript:;" title="Delete" onclick="deleteAccessoriesPurchaseOrderRow('+i+')"><i class="fas fa-trash"></i></a></td></tr>';
        
        if(typeof total_data.qty != 'undefined')total_data.qty+= parseInt(purchase_order_accessories_rows[i].size_total);else total_data.qty = parseInt(purchase_order_accessories_rows[i].size_total);
        if(typeof total_data.amount != 'undefined')total_data.amount+= parseFloat(total_amount);else total_data.amount = parseFloat(total_amount);
        if(typeof total_data.gst_amount != 'undefined')total_data.gst_amount+= parseFloat(purchase_order_accessories_rows[i].gst_amount);else total_data.gst_amount = parseFloat(purchase_order_accessories_rows[i].gst_amount);
    }
    
    if(purchase_order_accessories_rows.length > 0){
        str+='<tr><th colspan="2">Total</th>';
        for(var q=0;q<size_list.length;q++){
            var size_id = size_list[q][0];
            if(typeof total_data[size_id] != 'undefined') var size_val = total_data[size_id];else total_data[size_id] = '';
            str+='<th>'+total_data[size_id]+'</th>';
        }
        str+='<th></th><th>'+total_data.qty+'</th><th>'+currency+" "+(total_data.amount).toFixed(2)+'</th><th></th></tr>';
        
        var colspan = parseInt(size_list.length)+4;
        str+='<tr><td colspan="'+colspan+'">GST</td><td>'+currency+" "+(total_data.gst_amount).toFixed(2)+'</td><td></td></tr>';
        var other_cost = ($("#other_cost").val()!='')?$("#other_cost").val():0;
        var total_cost = (parseFloat(total_data.amount)+parseFloat(other_cost)+parseFloat(total_data.gst_amount)).toFixed(2);
        str+='<tr><td colspan="'+colspan+'">Other Cost</td><td >'+currency+" "+other_cost+'</td><td></td></tr>';
        str+='<tr><th colspan="'+colspan+'">Total Cost</th><th>'+currency+" "+total_cost+'</th><th></th></tr>';
        
        $(".submit-data-row").removeClass('elem-hidden');
    }else{
        $(".submit-data-row").addClass('elem-hidden');
    }
    
    str+='</table></div>';
    $("#purchase_order_accessories_rows_list").html(str);
}

function deleteAccessoriesPurchaseOrderRow(index){
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage").html('').hide();
    $('#confirm_delete_row_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_style_row_btn', function(e) {
        e.preventDefault();
        purchase_order_accessories_rows.splice(index,1);
        displayAccessoriesPurchaseOrderRows();
        $('#confirm_delete_row_item').modal('hide');
    });
}

function updateAccessoriesPOTotalQtyCost(){
    var total_qty = 0;
    $(".size-item").each(function(){
        if($(this).val() != '' && isNaN($(this).val())){
           $(this).val('');
        } 
        
        if($(this).val() != ''){
            total_qty+=parseInt($(this).val());
        }
    });
    
    $("#size_total").val(total_qty);
    
    if($("#item_rate").val() != ''){
        if(isNaN($("#item_rate").val())){
            $("#item_rate").val('');
        }
        var amount_total = ($("#item_rate").val()*total_qty).toFixed(2);
        $("#amount_total").val(amount_total);
    }
}

function createAccessoriesPurchaseOrder(){
    var error_msg = '';
   
    $("#OrderRowStatusErrorMessage,#OrderRowStatusSuccessMessage,#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage").html('').hide();
    
    var vendor_id = $('#vendor_id').val();
    var delivery_date = $("#delivery_date").val();
    
    if(purchase_order_accessories_rows.length == 0){
        error_msg+="Item is Required Field <br>";
    }
    
    if(vendor_id == ''){
        error_msg+="Vendor is Required Field <br>";
    }
    if(delivery_date == ''){
        error_msg+="Delivery date is Required Field <br>";
    }
   
    if(error_msg != ''){
        $("#purchaseOrderCreateErrorMessage").html(error_msg).show();
        document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
        return false;
    }
    
    $("#create_purchase_order_submit").attr("disabled",true);
    
    var other_cost = $("#other_cost").val();
    var other_comments = $("#other_comments").val();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/accessories/create?create_order=1",
        method:"POST",
        data:{rows:purchase_order_accessories_rows,vendor_id:vendor_id,other_cost:other_cost,other_comments:other_comments,delivery_date:delivery_date},
        success:function(data){
            
            $("#create_purchase_order_submit").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#purchaseOrderCreateSuccessMessage").html(data.message).show();
                    $("#purchaseOrderCreateErrorMessage").html('').hide();
                    document.getElementById("purchaseOrderCreateSuccessMessage").scrollIntoView();
                    
                    var url = ROOT_PATH+"/purchase-order/accessories/detail/"+data.po_data.id;
                    setTimeout(function(){  window.location.href = url; }, 2000);
                }else{    
                    $("#purchaseOrderCreateErrorMessage").html(data.message).show();
                    document.getElementById("purchaseOrderCreateErrorMessage").scrollIntoView();
                }
            }else{
                displayResponseError(data,"purchaseOrderCreateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrderCreateErrorMessage").html(error).show();
            $("#create_purchase_order_submit").attr("disabled",false);
        }
    });
}

function submitAddAccessoriesPOInvoice(){
    $("#addAccessoriesVehicleDetailsForm").submit();
}

$("#addAccessoriesVehicleDetailsForm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    formData.append('vehicle_no', $("#vehicle_no").val());
    formData.append('containers_count', $("#containers_count").val());
    formData.append('comments', $("#comments").val());
    formData.append('invoice_no', $("#invoice_no").val());
    formData.append('invoice_date', $("#invoice_date").val());
    //formData.append('products_count', $("#products_count").val());
    formData.append('po_id', $("#po_id").val());
    
    var po_detail_id = 0;
    
    $("#vehicle_details_add_spinner").show();
    $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',true);
    $(".invalid-feedback,#addVehicleDetailsSuccessMessage,#addVehicleDetailsErrorMessage").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/purchase-order/accessories/items/import/"+po_detail_id+"?action=add_vehicle_details",
        success: function(msg){		
            $("#vehicle_details_add_spinner").hide();
            $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addVehicleDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addVehicleDetailsSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_vehicle_details_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addVehicleDetailsErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addVehicleDetailsErrorMessage").html('Error in processing request').show();
            $("#vehicle_details_add_spinner").hide();
            $("#add_vehicle_details_confirm_cancel,#add_vehicle_details_confirm_submit").attr('disabled',false);
        }
    });
});

function displayAddAccessoriesPOInvoice(){
    $("#vehicle_no,#containers_count,#comments,.form-control").val('');
    $("#add_po_invoice_dialog").modal('show');
}

function displayVehicleDetailsContainerImages(containers_count){
    if(containers_count == ''){
        $("#vehicle_details_container_images").html('');
        return false;
    }
    var str = '';
    for(var i=1;i<=containers_count;i++){
        str+='<div class="form-group col-md-4"><input type="file" name="container_image_'+i+'" id="container_image_'+i+'" class="form-control"><div class="invalid-feedback" id="error_validation_container_image_'+i+'"></div></div>';
    }
    
    $("#vehicle_details_container_images").html(str);
}

function displayAccessoriesPOInvoiceDetails(id){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/accessories/items/import/"+id+"?action=get_po_invoice_details",
        method:"get",
        data:{id:id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#inventoryVehicleDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#po_invoice_detail_dialog").modal('show');
                    $("#vehicle_no_detail").val(msg.vehicle_details.vehicle_no);
                    $("#containers_count_detail").val(msg.vehicle_details.containers_count);
                    $("#comments_detail").val(msg.vehicle_details.comments);
                   
                    $("#invoice_no_detail").val(msg.vehicle_details.invoice_no);
                    $("#invoice_date_detail").val(displayDate(msg.vehicle_details.invoice_date));
                    //$("#products_count_detail").val(msg.vehicle_details.products_count);
                    $("#po_detail_id").val(id);
                    
                    var images_list = msg.vehicle_details.images_list;
                    var str = '';
                    str+='<div class="row">';
                    for(var i=0;i<images_list.length;i++){
                        var img_url = ROOT_PATH+'/images/po_images/'+msg.vehicle_details.po_id+"/thumbs/"+images_list[i];
                        str+='<div class="col-md-3"><img src="'+img_url+'" class="inventory-container-image"></div>';
                        if(i > 0 && (i+1)%4 == 0 ){
                            str+='</div><div class="separator-10">&nbsp;</div><div class="row">';
                        } 
                    }
                    str+='</div>';
                    
                    $("#container_images_detail").html(str);
                }
            }else{
                displayResponseError(msg,"inventoryVehicleDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryVehicleDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitUpdateAccessoriesPOInvoiceDetails(){
    $("#updateInvoiceDetailsErrorMessage,#updateInvoiceDetailsSuccessMessage").html('').hide();
    $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var form_data = $("#accessoriesVehicleDetailsForm").serialize();
    form_data+="&po_id="+po_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/accessories/items/import/"+po_detail_id+"?action=update_po_invoice_details",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateInvoiceDetailsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateInvoiceDetailsSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#po_invoice_detail_dialog").modal('hide');window.location.reload();  }, 1000);
                }
            }else{
                displayResponseError(msg,"updateInvoiceDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',false);
            $("#updateInvoiceDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayAddAccessoriesPOGRN(){
    $("#addInventoryGRNSuccessMessage,#addInventoryGRNErrorMessage").html('').hide();
    $("#add_inventory_grn_comments").val('');
    $("#add_inventory_grn_dialog").modal('show');
}

function submitAddAccessoriesPOGRN(){
    $("#addInventoryGRNSuccessMessage,#addInventoryGRNErrorMessage").html('').hide();
    $("#add_inventory_grn_spinner").show();
    $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var add_inventory_grn_comments = $("#add_inventory_grn_comments").val();
    
    var form_data = $("#importAccessoriesPOItemsFrm").serialize();
    form_data+="&po_id="+po_id+"&add_inventory_grn_comments="+add_inventory_grn_comments+"&po_detail_id="+po_detail_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/purchase-order/accessories/items/import/"+po_detail_id+"?action=add_bulk_po_grn",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#add_inventory_grn_spinner").hide();
            $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryGRNErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryGRNSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_inventory_grn_dialog").modal('hide');window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,"addInventoryGRNErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_inventory_grn_spinner").hide();
            $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',false);
            $("#addInventoryGRNErrorMessage").html('Error in processing request').show();
        }
    });
}

function createStaticPurchaseOrder(){
    $("#purchaseOrderCreateErrorMessage,#purchaseOrderCreateSuccessMessage,.invalid-feedback").html('').hide();
    $("#create_purchase_order_submit").attr("disabled",true);
    var vendor_id = $('#vendor_id').val();
    var delivery_date = $("#delivery_date").val();
    var category_id = $("#category_id").val();
    var other_cost = $("#other_cost").val();
    var other_comments = $("#other_comments").val();
   
    ajaxSetup();    
    
    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/static/create?create_order=1",
        method:"POST",
        data:{vendor_id:vendor_id,other_cost:other_cost,other_comments:other_comments,delivery_date:delivery_date,category_id:category_id},
        success:function(data){
            $("#create_purchase_order_submit").attr("disabled",false);
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#purchaseOrderCreateSuccessMessage").html(data.message).show();
                    $("#purchaseOrderCreateErrorMessage").html('').hide();
                   
                    var url = ROOT_PATH+"/purchase-order/product/static/edit/"+data.po_data.id;
                    setTimeout(function(){  window.location.href = url; }, 1000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#purchaseOrderCreateErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"purchaseOrderCreateErrorMessage");
            }
        },error:function(obj,status,error){
            $("#purchaseOrderCreateErrorMessage").html(error).show();
            $("#create_purchase_order_submit").attr("disabled",false);
        }
    });
}

function uploadStaticPurchaseOrderCsvFile(){
    $("#editStaticPurchaseOrderForm").submit();
}

$("#editStaticPurchaseOrderForm").on('submit', function(event){
    event.preventDefault(); 
    $("#purchaseOrderEditErrorMessage,#purchaseOrderEditSuccessMessage").html('').hide();
    var po_id = $("#po_id").val();
    
    var formData = new FormData(this);
    formData.append('po_id',$("#po_id").val());
    $("#edit_purchase_order_spinner").show();
    $("#edit_purchase_order_submit").attr('disabled',true);
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
        url:ROOT_PATH+"/purchase-order/product/static/edit/"+po_id+"?action=upload_po_csv",
        success: function(msg){		
            $("#edit_purchase_order_spinner").hide();
            $("#edit_purchase_order_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#purchaseOrderEditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#purchaseOrderEditSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/purchase-order/product/detail/"+po_id;
                    setTimeout(function(){  window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"purchaseOrderEditErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#purchaseOrderEditErrorMessage").html('Error in processing request').show();
            $("#edit_purchase_order_spinner").hide();
            $("#edit_purchase_order_submit").attr('disabled',false);
        }
    });
});


function editAccessory(id){
    $("#edit_accessory_dialog").modal('show');
    $("#accessory_edit_id").val(id);
    $("#editAccessorySuccessMessage,#editAccessoryErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/accessories/list?action=get_acc_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#acc_name_edit").val(msg.acc_data.accessory_name);
                    $("#acc_rate_edit").val(msg.acc_data.rate);
                    $("#acc_gst_edit").val(msg.acc_data.gst_percent);
                    $("#acc_desc_edit").val(msg.acc_data.description);
                }
            }else{
                displayResponseError(msg,"editAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editAccessoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateAccessory(){
    var form_data = $("#editAccessoryFrm").serialize();
    $("#editAccessorySuccessMessage,#editAccessoryErrorMessage,.invalid-feedback").html('').hide();
    $("#accessory_edit_submit,#accessory_edit_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/accessories/list?action=update_acc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#accessory_edit_submit,#accessory_edit_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editAccessorySuccessMessage").html(msg.message).show();
                    $("#editAccessoryErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_accessory_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editAccessoryErrorMessage").html('Error in processing request').show();
            $("#accessory_edit_submit,#accessory_edit_cancel").attr('disabled',false);
        }
    });
}

function addAccessory(){
    $("#addAccessorySuccessMessage,#addAccessoryErrorMessage,.invalid-feedback").html('').hide();
    $("#addAccessoryFrm .form-control").val('');
    $("#add_accessory_dialog").modal('show');
}

function submitAddAccessory(){
    var form_data = $("#addAccessoryFrm").serialize();
    $("#addAccessorySuccessMessage,#addAccessoryErrorMessage,.invalid-feedback").html('').hide();
    $("#accessory_add_submit,#accessory_add_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/accessories/list?action=add_acc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#accessory_add_submit,#accessory_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addAccessorySuccessMessage").html(msg.message).show();
                    $("#addAccessoryErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_accessory_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addAccessoryErrorMessage").html('Error in processing request').show();
            $("#accessory_add_submit,#accessory_add_cancel").attr('disabled',false);
        }
    });
}

function addVendorAccessory(){
    $("#addVendorAccessorySuccessMessage,#addVendorAccessoryErrorMessage,.invalid-feedback").html('').hide();
    $("#addVendorAccessoryFrm .form-control").val('');
    $("#add_vendor_accessory_dialog").modal('show');
}

function submitAddVendorAccessory(){
    var form_data = $("#addVendorAccessoryFrm").serialize();
    $("#addVendorAccessorySuccessMessage,#addVendorAccessoryErrorMessage,.invalid-feedback").html('').hide();
    $("#vendor_accessory_add_submit,#vendor_accessory_add_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/accessories/list?action=add_vend_acc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#vendor_accessory_add_submit,#vendor_accessory_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addVendorAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addVendorAccessorySuccessMessage").html(msg.message).show();
                    $("#addVendorAccessoryErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_vendor_accessory_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addVendorAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addVendorAccessoryErrorMessage").html('Error in processing request').show();
            $("#vendor_accessory_add_submit,#vendor_accessory_add_cancel").attr('disabled',false);
        }
    });
}

function getVendorPOList(v_id,type,sel_id){
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/accessories/list?action=get_vendor_po_list&v_id="+v_id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#"+type+"VendorAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var po_list = msg.po_list,str = '<option value="">Select</option>', sel = '';
                    for(var i=0;i<po_list.length;i++){
                        sel = (sel_id == po_list[i].id)?'selected':'';
                        str+='<option '+sel+' value="'+po_list[i].id+'">'+po_list[i].order_no+" ("+displayDate(po_list[i].created_at)+')</option>';
                    }
                    
                    $("#po_id_"+type).html(str);
                }
            }else{
                displayResponseError(msg,"#"+type+"VendorAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#"+type+"VendorAccessoryErrorMessage").html('Error in processing request').show();
        }
    });
}


function editVendorAccessory(id){
    $("#edit_vendor_accessory_dialog").modal('show');
    $("#vendor_acc_edit_id").val(id);
    $("#editVendorAccessorySuccessMessage,#editVendorAccessoryErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/accessories/list?action=get_vend_acc_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editVendorAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#vendor_id_edit").val(msg.vend_acc_data.vendor_id);
                    //$("#po_id_edit").val(msg.vend_acc_data.po_id);
                    $("#acc_id_edit").val(msg.vend_acc_data.accessory_id);
                    $("#quantity_edit").val(msg.vend_acc_data.quantity);
                    $("#date_provided_edit").val(displayDate(msg.vend_acc_data.date_provided));
                    getVendorPOList(msg.vend_acc_data.vendor_id,'edit',msg.vend_acc_data.po_id);
                }
            }else{
                displayResponseError(msg,"editVendorAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editVendorAccessoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateVendorAccessory(){
    var form_data = $("#editVendorAccessoryFrm").serialize();
    $("#editVendorAccessorySuccessMessage,#editVendorAccessoryErrorMessage,.invalid-feedback").html('').hide();
    $("#vendor_accessory_edit_cancel,#vendor_accessory_edit_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/accessories/list?action=update_vend_acc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#vendor_accessory_edit_cancel,#vendor_accessory_edit_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editVendorAccessoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editVendorAccessorySuccessMessage").html(msg.message).show();
                    $("#editVendorAccessoryErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_vendor_accessory_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editVendorAccessoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editVendorAccessoryErrorMessage").html('Error in processing request').show();
            $("#vendor_accessory_edit_cancel,#vendor_accessory_edit_submit").attr('disabled',false);
        }
    });
}

function updatePurchaseOrderData(){
    var form_data = $("#editPOFrm").serialize();
    var po_id = $("#po_id").val();
    $("#editPurchaseOrderErrorMessage,#editPurchaseOrderSuccessMessage,.invalid-feedback").html('').hide();
    $("#edit_po_cancel,#edit_po_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/purchase-order/edit/data/"+po_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#edit_po_cancel,#edit_po_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editPurchaseOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editPurchaseOrderSuccessMessage").html(msg.message).show();
                    $("#editPurchaseOrderErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){ var url = ROOT_PATH+"/purchase-order/product/list";window.location.href = url;  }, 1000);
                }
            }else{
                displayResponseError(msg,"editPurchaseOrderErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPurchaseOrderErrorMessage").html('Error in processing request').show();
            $("#edit_po_cancel,#edit_po_submit").attr('disabled',false);
        }
    });
}