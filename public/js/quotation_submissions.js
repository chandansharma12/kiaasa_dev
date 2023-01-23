"use strict";

function purchaseOrder(){
    
    $("#purchaseOrderSuccessMessage,#purchaseOrderErrorMessage").html('').hide();
    var chkArray = $('.chk-purchase-item').map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();
    if(chkArray.length == 0){
        $("#purchase_order_error_body").html('Please Select Type Checkboxes');
        $("#purchase-order-error-dialog").modal('show');
        return false;
    }
    
    var validated = true;
    for(var i=0;i<chkArray.length;i++){
        var id = chkArray[i];
        if($("#vendor_sel_"+id).val()  == ''){
            $("#vendor_sel_"+id).addClass('selected-elem');
            validated = false;
        }else{
            $("#vendor_sel_"+id).removeClass('selected-elem');
        }
        
        if($("#quantity_sel_"+id).val() == ''){
            $("#quantity_sel_"+id).addClass('selected-elem');
            validated = false;
        }else{
            $("#quantity_sel_"+id).removeClass('selected-elem');
        }
    }
    
    if(!validated){
        $("#purchase_order_error_body").html('Please Select Vendor and Quantity');
        $("#purchase-order-error-dialog").modal('show');
        return false;    
    }
    
    $("#vendor_quotations_table").find('.vendor-sel-1').removeClass('selected-elem');
    
    var form_data = '', id = '', id_str = '';
    for(var i=0;i<chkArray.length;i++){
        id = chkArray[i];
        form_data+="vendor_sel_"+id+"="+$("#vendor_sel_"+id).val()+"&";
        form_data+="quantity_sel_"+id+"="+$("#quantity_sel_"+id).val()+"&";
        id_str+=id+",";
    }
    
    id_str = id_str.substring(0,id_str.length-1);
    form_data+="ids="+id_str;
    
    ajaxSetup();
    
    $.ajax({
        type: "POST",
        data:form_data,
        url:ROOT_PATH+"/purchase-order/purchaseorderitems/"+design_id,
        success: function(msg){	
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#quotationErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var purchase_items = msg.purchase_items;
                    /*var str = '<div class="table-responsive table-filter"><table class="table table-striped" id="vendor_table" >\
                    <thead><tr><th>Type</th><th>Name</th><th>Quality</th><th>Color</th><th>Vendor</th><th>Quantity</th></tr></thead><tbody>';
                    for(var i=0;i<purchase_items.length;i++){
                        if(purchase_items[i].design_type_id == 1) var type_name = 'Fabric';if(purchase_items[i].design_type_id == 2) var type_name = 'Accessories';
                        if(purchase_items[i].design_type_id == 3) var type_name = 'Process';if(purchase_items[i].design_type_id == 4) var type_name = 'Packaging sheet';
                        if(purchase_items[i].quality_id_name != null) var quality_name = purchase_items[i].quality_id_name;else var quality_name = '';
                        if(purchase_items[i].color_id_name != null) var color_name = purchase_items[i].color_id_name;else var color_name = '';
                        str+='<tr><td>'+type_name+'</td><td>'+purchase_items[i].name_id_name+'</td><td>'+quality_name+'</td><td>'+color_name+'</td>\
                        <td>'+purchase_items[i].vendor_name+'</td><td>'+purchase_items[i].quantity+'</td></tr>';
                    }
                    str+='</tbody></table></div>';*/

                    var fabric_str = '', acc_str = '',process_str = '', pack_str = '';
                    var count = 0;
                    var str = '<div class="table-responsive table-filter"><table class="table table-striped" id="vendor_table_confirm">';

                    fabric_str = '<tr><th>Fabric</th><th>Name</th><th>Quality</th><th>Color</th><th>Vendor</th><th>Quantity</th></tr><tbody>';
                    for(var i=0;i<purchase_items.length;i++){
                        if(purchase_items[i].design_type_id == 1){
                            fabric_str+='<tr><td></td><td>'+purchase_items[i].name_id_name+'</td><td>'+purchase_items[i].quality_id_name+'</td><td>'+purchase_items[i].color_id_name+'</td>\
                            <td>'+purchase_items[i].vendor_name+'</td><td>'+purchase_items[i].quantity+'</td></tr>';
                            count++;
                        }
                    }
                    if(count) str+=fabric_str;

                    count = 0;
                    acc_str = '<tr><th>Accessories</th><th>Category</th><th></th><th>Color</th><th>Vendor</th><th>Quantity</th></tr>';
                    for(var i=0;i<purchase_items.length;i++){
                        if(purchase_items[i].design_type_id == 2){
                            acc_str+='<tr><td></td><td>'+purchase_items[i].name_id_name+'</td><td></td><td>'+purchase_items[i].color_id_name+'</td>\
                            <td>'+purchase_items[i].vendor_name+'</td><td>'+purchase_items[i].quantity+'</td></tr>';
                            count++;
                        }
                    }
                    if(count) str+=acc_str;

                    count = 0;
                    process_str = '<tr><th>Process</th><th>Category</th><th>Type</th><th></th><th>Vendor</th><th>Quantity</th></tr>';
                    for(var i=0;i<purchase_items.length;i++){
                        if(purchase_items[i].design_type_id == 3){
                            process_str+='<tr><td></td><td>'+purchase_items[i].name_id_name+'</td><td>'+purchase_items[i].quality_id_name+'</td><td></td>\
                            <td>'+purchase_items[i].vendor_name+'</td><td>'+purchase_items[i].quantity+'</td></tr>';
                            count++;
                        }
                    }
                    if(count) str+=process_str;

                    count = 0;
                    pack_str = '<tr><th>Packaging Sheet</th><th>Name</th><th></th><th></th><th>Vendor</th><th>Quantity</th></tr>';
                    for(var i=0;i<purchase_items.length;i++){
                        if(purchase_items[i].design_type_id == 4){
                            pack_str+='<tr><td></td><td>'+purchase_items[i].name_id_name+'</td><td></td><td></td>\
                            <td>'+purchase_items[i].vendor_name+'</td><td>'+purchase_items[i].quantity+'</td></tr>';
                            count++;
                        }
                    }
                    if(count) str+=pack_str;

                    str+='</tbody></table></div>';

                    str+='<div id="purchase-order-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>\
                    <button type="button" class="btn_add" onclick="confirmPurchaseOrder();" style="border:none;">Confirm Purchase order</button>';
                    $("#purchase-order-items-dialog").modal('show');
                    $("#purchase_order_items_body").html(str);
                }
            }else{
                displayResponseError(msg,"quotationErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#quotationErrorMessage").html('Error in processing request').show();
        }
    });
}

function confirmPurchaseOrder(){
    var chkArray = $('.chk-purchase-item').map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();
    $("#purchase-order-spinner").show();
    
    var form_data = '', id = '', id_str = '';
    for(var i=0;i<chkArray.length;i++){
        id = chkArray[i];
        form_data+="vendor_sel_"+id+"="+$("#vendor_sel_"+id).val()+"&";
        form_data+="quantity_sel_"+id+"="+$("#quantity_sel_"+id).val()+"&";
        id_str+=id+",";
    }
    
    id_str = id_str.substring(0,id_str.length-1);
    form_data+="ids="+id_str;
    
    ajaxSetup();
    
    $.ajax({
        type: "POST",
        data:form_data,
        url:ROOT_PATH+"/purchase-order/confirmpurchaseorderitems/"+design_id,
        success: function(msg){		
            $("#purchase-order-spinner").hide();
            if(objectPropertyExists(msg,'status')){
                var errors = '';
                if(msg.status == 'fail'){
                    $("#purchaseOrderSuccessMessage").html('').hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#purchaseOrderErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#purchaseOrderErrorMessage").html('').hide();
                    $("#purchaseOrderSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#purchase-order-items-dialog").modal('hide'); }, 2000);
                }
            }else{
                displayResponseError(msg,"purchaseOrderErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#purchaseOrderErrorMessage").html('Error in processing request').show();
            $("#purchase-order-spinner").hide();
        }
    });
}
