"use strict";

function editAudit(id){
    $("#edit_audit_dialog .form-control").val('');
    $("#edit_audit_dialog").modal('show');
    $("#audit_edit_id").val(id);
    $("#editAuditSuccessMessage,#editAuditErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/audit/list?action=get_audit_data",
        method:"GET",
        data:{id:id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editAuditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var audit_data = msg.audit_data;
                    $("#store_id_edit").val(audit_data.store_id);
                    $("#members_present_edit").val(audit_data.members_present);
                    $("#counter_cash_edit").val(audit_data.counter_cash);
                    $("#manual_bills_edit").val(audit_data.manual_bills);
                    $("#cash_verified_edit").val(audit_data.cash_verified);
                    $("#cash_verified_comment_edit").val(audit_data.cash_verified_comment);
                    $("#wbc_sku_list_edit").val(audit_data.wbc_sku_list);
                    $("#audit_status_edit").val(audit_data.audit_status.replace('_',' '));
                    $("#audit_type_edit").val(audit_data.audit_type);
                    if(audit_data.audit_status.toLowerCase() == 'completed'){
                        $("#edit_audit_dialog .modal-footer").hide();
                    }else{
                        $("#edit_audit_dialog .modal-footer").show();
                    }
                    if(audit_data.audit_status.toLowerCase() == 'scan_completed'){
                        $("#complete_audit_div").show();
                    }else{
                        $("#complete_audit_div").hide();
                    }
                    
                    if(audit_data.audit_type == 'warehouse'){
                        $("#store_id_div_edit").hide();
                    }else{
                        $("#store_id_div_edit").show();
                    }
                }
            }else{
                displayResponseError(msg,"editAuditErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editAuditErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateAudit(){
    $("#audit_edit_spinner").show();
    $("#editAuditSuccessMessage,#editAuditErrorMessage,.invalid-feedback").html('').hide();
    $("#audit_edit_submit,#audit_edit_cancel").attr('disabled',true);
    var form_data = $("#editAuditFrm").serialize();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/audit/list?action=audit_update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#audit_edit_spinner").hide();
            $("#audit_edit_submit,#audit_edit_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editAuditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editAuditSuccessMessage").html(msg.message).show();
                    $("#editAuditErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_audit_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editAuditErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editAuditErrorMessage").html('Error in processing request').show();
            $("#audit_edit_spinner").hide();
            $("#audit_edit_submit,#audit_edit_cancel").attr('disabled',false);
        }
    });
}

function addAudit(){
    $("#add_audit_dialog .form-control").val('');
    $("#add_audit_dialog").modal('show');
}

function submitAddAudit(){
    $("#addAuditSuccessMessage,#addAuditErrorMessage,.invalid-feedback").html('').hide();
    $("#audit_add_submit,#audit_add_cancel").attr('disabled',true);
    var form_data = $("#addAuditFrm").serialize();
    ajaxSetup();
    
    $.ajax({
        url:ROOT_PATH+"/audit/list?action=audit_add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#audit_add_submit,#audit_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addAuditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addAuditSuccessMessage").html(msg.message).show();
                    var url = (msg.audit_type == 'store')?ROOT_PATH+"/audit/inventory/scan/"+msg.audit_id:ROOT_PATH+"/audit/inventory/scan/wh/"+msg.audit_id;
                    setTimeout(function(){  $("#add_audit_dialog").modal('hide');window.location.href = url; }, 1000);
                    
                }
            }else{
                displayResponseError(msg,"addAuditErrorMessage");
            }
        },error:function(obj,status,error){
            $("#audit_add_submit,#audit_add_cancel").attr('disabled',false);
            $("#addAuditErrorMessage").html('Error in processing request').show();
        }
    });
}

var inventory_products_audit_inv = [],  rec_per_page_audit_inv = '', page_global_audit_inv = 1, inventory_data_audit_inv = '',barcode_list = [],inv_audit_barcode = '',inv_audit_time = '';

function getAuditInventoryProductData(barcode){
    var audit_id = $("#audit_id").val(), store_id = $("#store_id").val();;
    
    if(barcode == '' || barcode.length < 6){
        $("#product_added_span").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_audit_barcode != '' && inv_audit_barcode == barcode && Math.abs((inv_audit_time) - time)/1000 < 1){
        return false;
    }
    
    inv_audit_barcode = barcode;
    inv_audit_time = time;
    
    /*if(barcode_list.indexOf(barcode) >= 0){
        error_msg = 'Product already added';//alert(error_msg);
    }
    
    if(error_msg != ''){
        $("#product_added_span").html(error_msg).addClass('alert-danger').removeClass('alert-success').show();
        $(".import-data").val('');
        return false;
    }*/
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/inventory/scan/"+audit_id+"?action=get_inventory_product",
        method:"GET",
        data:{barcode:barcode,audit_id:audit_id,store_id:store_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                $("#product_added_span").html('');
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        //$("#product_added_span").html(errors).addClass('alert-danger').removeClass('alert-success').show();
                        //$(".import-data,#piece_id").val('');
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode_audit_inv").val('').focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#product_added_span\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#product_added_span").html(errors).addClass('alert-danger').removeClass('alert-success').show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_audit_inv = msg.product_data;
                    $("#product_name").val(product_data_audit_inv.product_name);
                    $("#piece_size").val(product_data_audit_inv.size_name);
                    $("#piece_color").val(product_data_audit_inv.color_name);
                    $("#piece_vendor").val(product_data_audit_inv.vendor_name);
                    $("#piece_po_number").val(product_data_audit_inv.po_order_no);
                    $("#product_sku").val(product_data_audit_inv.product_sku);
                    $("#piece_cost").val(product_data_audit_inv.base_price);
                    $("#intake_date").val(getCurrentDate(1));
                    $("#piece_id").val(product_data_audit_inv.id);
                    
                    product_data_audit_inv.scan_status = 1;
                    product_data_audit_inv.scan_date = getCurrentDate(2);
                    inventory_products_audit_inv.push(product_data_audit_inv);
                    inventory_data_audit_inv.inv_imported = parseInt(inventory_data_audit_inv.inv_imported)+1;
                    
                    displayAddAuditInventoryData();
                    
                    /*$(".import-data,#piece_barcode_audit_inv,#piece_id").val('');
                    $("#piece_barcode_audit_inv").focus();
                    $("#product_added_span").html('Product added').addClass('alert-success').removeClass('alert-danger').show();
                    */
                   
                    setTimeout(function(){ 
                        var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#product_added_span\').html(\'\').hide();" href="javscript:;"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#product_added_span").html(success_msg).addClass('alert-success').removeClass('alert-danger').show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_barcode_audit_inv,#piece_id").val('');
                        $("#piece_barcode_audit_inv").focus();
                    }, 1000);
                }
            }else{
                $("#product_added_span").html(msg.message).addClass('alert-danger').removeClass('alert-success').show();
            }
        },error:function(obj,status,error){
            $("#product_added_span").html('Error in processing request').addClass('alert-danger').removeClass('alert-success').show();
        }
    });
}

$("#piece_barcode_audit_inv").on('propertychange change paste input', function(e){
    setTimeout(function(){   
        var val = $("#piece_barcode_audit_inv").val();
        getAuditInventoryProductData(val);
    }, 500);
    
});

function displayAddAuditInventoryData(){
    var str = ''; //'<hr><h6>Audit Inventory Details: </h6>';
    str+='Total Inventory: '+inventory_data_audit_inv.inv_total+" | Scanned Inventory: "+inventory_data_audit_inv.inv_imported;
    if(inventory_products_audit_inv.length > 0){
        str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:13px;"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>SKU</th><th>Cost</th><th>Scan Date</th><th>Intake Date</th><th>Status</th><th>Store</th><th>Present in System</th><th>Present in Store</th><th>Type</th></tr></thead><tbody>'

        var start = parseInt((page_global_audit_inv-1)*rec_per_page_audit_inv)+1;
        for(var i=0;i<inventory_products_audit_inv.length;i++){
            var product_data_audit_inv = inventory_products_audit_inv[i];
            var intake_date = product_data_audit_inv.intake_date != null?displayDate(product_data_audit_inv.intake_date):'-';
            var scan_date = (product_data_audit_inv.scan_date != null)?formatDate(product_data_audit_inv.scan_date):'-';
            var present_system = (product_data_audit_inv.present_system == 1)?'Yes':'No';
            var present_store = (product_data_audit_inv.present_store == 1)?'Yes':'No';
            var cost_price = product_data_audit_inv.store_base_price != null?product_data_audit_inv.store_base_price:'';
            var id = product_data_audit_inv.id;
             
            if(present_system == 'Yes'){
                var inv_type = (product_data_audit_inv.arnon_inventory == 1)?'Arnon':'Northcorp';
            }else{
                var inv_type = '';
            }
            
            if(present_system != present_store){
                var bg_color = 'style="background-color:#F7C1BD"';
            }else{
                var bg_color = '';
            }
            
            str+='<tr '+bg_color+'>';
            
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="audit-inv-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            
            str+='<td>'+product_data_audit_inv.peice_barcode+'</td><td>'+product_data_audit_inv.product_name+' '+product_data_audit_inv.size_name+' '+product_data_audit_inv.color_name+'</td>\
            <td>'+product_data_audit_inv.product_sku+'</td><td>'+cost_price+'</td><td>'+scan_date+'</td><td>'+intake_date+'</td><td>'+getProductInventoryStatus(product_data_audit_inv.product_status)+'</td>\
            <td>'+product_data_audit_inv.store_name+'</td><td>'+present_system+'</td><td>'+present_store+'</td><td>'+inv_type+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    if(inventory_data_audit_inv.inv_imported >= inventory_data_audit_inv.inv_total){
        $("#importPosInventoryErrorMessage").html("Total Inventory of Invoice imported").css('margin-top','10px').show();
        $("#piece_barcode_inv_import,#pos_add_inventory_submit").attr('disabled',true);
    }
    
    $("#products_imported_list").html(str);
}

function loadAuditInventory(page){
    var audit_id = $("#audit_id").val();
    var store_id = $("#store_id").val();
    var scan_status = $("#scan_status").val();
    var inv_status_search = $("#inv_status_search").val();
    var store_id_search = $("#store_id_search").val();
    var product_status_search = $("#product_status_search").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/inventory/scan/"+audit_id+"?action=load_audit_inventory&d="+new Date().getTime(),
        method:"get",
        data:{audit_id:audit_id,page:page,store_id:store_id,scan_status:scan_status,inv_status_search:inv_status_search,store_id_search:store_id_search,product_status_search:product_status_search},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#auditInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;
                    var paging_links = msg.paging_links;
                    inventory_products_audit_inv = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_audit_inv.push(product_list[i]);
                    }
                    
                    rec_per_page_audit_inv = msg.rec_per_page;
                    page_global_audit_inv = page;
                    inventory_data_audit_inv = msg.inventory_count_data;
                    barcode_list = msg.barcode_list;
                    displayAddAuditInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadAuditInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadAuditInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"auditInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#auditInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function searchAuditInventory(){
    loadAuditInventory(1);
}

function completeAuditScan(){
    $("#completeAuditScanSuccessMessage,#completeAuditScanErrorMessage").html('').hide();
    $("#complete_audit_scan_comment").val('');
    $("#completeAuditScanInventoryDialog").modal('show');
}

function submitcompleteAuditScan(){
    var comments = $("#complete_audit_scan_comment").val();
    var audit_id = $("#audit_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/inventory/scan/"+audit_id+"?action=complete_audit_scan&d="+new Date().getTime(),
        method:"POST",
        data:{comments:comments,audit_id:audit_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#completeAuditScanErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#completeAuditScanSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/audit/inventory/scan/detail/"+audit_id;
                    setTimeout(function(){  $("#completeAuditScanInventoryDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"completeAuditScanErrorMessage");
            }
        },error:function(obj,status,error){
            $("#completeAuditScanErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateVarianceReportType(audit_id,type){
    var url = ROOT_PATH+"/audit/inventory/report/variance/"+audit_id+"/"+type;
    window.location.href = url;
}

function submitScanBulkInventory(){
    $("#audit_scan_upload_submit").attr("disabled",true);
    $("#auditInventoryScanBulkFrm").submit();
}

function calculateAuditBill(){
    var error_msg = '';
    var discount = $("#discount").val();
    var gst = $("#gst").val();
    
    if(discount == ''){
        error_msg+='Discount is Required Field <br>';
    }
    
    if(isNaN(discount)){
        error_msg+='Discount should have numeric value <br>';
    }
    
    if(gst == ''){
        error_msg+='GST is Required Field <br>';
    }
    
    if(error_msg != ''){
        $("#calculateBillErrorMessage").html(error_msg).show();
        return false;
    }
    
    $("#bill_calculate_form").submit();
}

function createAuditBill(){
    var discount = $("#bill_discount").val().replace('%','');
    var gst = $("#bill_gst").val();
    gst = (gst.toLowerCase() == 'inclusive')?'inc':'exc';
    var audit_id = $("#audit_id").val();
    
    var customer_salutation = $("#customer_salutation").val();
    var customer_name = $("#customer_name").val();
    var customer_phone_new = $("#customer_phone_new").val();
    
    $("#createBillErrorMessage,#createBillSuccessMessage,.invalid-feedback").html('').hide();
    $("#bill_create_btn").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/bill/create/"+audit_id+"?action=create_bill&d="+new Date().getTime(),
        method:"POST",
        data:{discount:discount,gst:gst,audit_id:audit_id,customer_salutation:customer_salutation,customer_name:customer_name,customer_phone_new:customer_phone_new},
        success:function(msg){
            $("#bill_create_btn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_billing_');
                    if(errors != ''){
                        $("#createBillErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#createBillSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/pos/order/detail/"+msg.order_data.id;
                    setTimeout(function(){  window.location.href = url; }, 800);
                }
            }else{
                displayResponseError(msg,"createBillErrorMessage");
            }
        },error:function(obj,status,error){
            $("#bill_create_btn").attr('disabled',false);
            $("#createBillErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteAuditInventoryItems(){
    var audit_id = $("#audit_id").val();
    
    var chk_class = 'audit-inv-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#audit_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#deleteInventoryDemandItemsErrorMessage,#deleteInventoryDemandItemsSuccessMessage").html('').hide();
    
    $('#audit_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_audit_items_btn', function(e) {
        e.preventDefault();
        $("#delete_audit_items_btn,#delete_audit_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,audit_id:audit_id},
            url:ROOT_PATH+"/audit/inventory/scan/"+audit_id+"?action=delete_audit_inv_items",
            success: function(msg){	
                $("#delete_audit_items_btn,#delete_audit_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteAuditItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteAuditItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#audit_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteAuditItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteAuditItemsErrorMessage").html('Error in processing request').show();
                $("#delete_audit_items_btn,#delete_audit_items_cancel").attr('disabled',false);
            }
        });
    });
}

function calculateAuditDiscount(){
    var required_bill_amount = $("#required_bill_amount").val();
    var discount_gst_type = $("#discount_gst_type").val();
    var audit_id = $("#audit_id").val();
    
    $("#calculateDiscountErrorMessage,#calculateDiscountSuccessMessage,.invalid-feedback").html('').hide();
    $("#discount_calculate_btn").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/bill/create/"+audit_id+"?action=calculate_discount&d="+new Date().getTime(),
        method:"POST",
        data:{required_bill_amount:required_bill_amount,discount_gst_type:discount_gst_type,audit_id:audit_id},
        success:function(msg){
            $("#discount_calculate_btn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#calculateDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#calculateDiscountSuccessMessage").html(msg.message).show();
                    $("#discount").val(msg.discount);
                }
            }else{
                displayResponseError(msg,"calculateDiscountErrorMessage");
            }
        },error:function(obj,status,error){
            $("#discount_calculate_btn").attr('disabled',false);
            $("#calculateDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}




var inventory_products_audit_inv = [],  rec_per_page_audit_inv = '', page_global_audit_inv = 1, inventory_data_audit_inv = '',barcode_list = [],inv_audit_barcode = '',inv_audit_time = '';

function getWHAuditInventoryProductData(barcode){
    var audit_id = $("#audit_id").val(), store_id = $("#store_id").val();;
    
    if(barcode == '' || barcode.length < 6){
        $("#product_added_span").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_audit_barcode != '' && inv_audit_barcode == barcode && Math.abs((inv_audit_time) - time)/1000 < 1){
        return false;
    }
    
    inv_audit_barcode = barcode;
    inv_audit_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/inventory/scan/wh/"+audit_id+"?action=get_inventory_product",
        method:"GET",
        data:{barcode:barcode,audit_id:audit_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                $("#product_added_span").html('');
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode_audit_wh_inv").val('').focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#product_added_span\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#product_added_span").html(errors).addClass('alert-danger').removeClass('alert-success').show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_audit_inv = msg.product_data;
                    $("#product_name").val(product_data_audit_inv.product_name);
                    $("#piece_size").val(product_data_audit_inv.size_name);
                    $("#piece_color").val(product_data_audit_inv.color_name);
                    $("#piece_vendor").val(product_data_audit_inv.vendor_name);
                    $("#piece_po_number").val(product_data_audit_inv.po_order_no);
                    $("#product_sku").val(product_data_audit_inv.product_sku);
                    $("#piece_cost").val(product_data_audit_inv.base_price);
                    $("#intake_date").val(getCurrentDate(1));
                    $("#piece_id").val(product_data_audit_inv.id);
                    
                    product_data_audit_inv.scan_status = 1;
                    product_data_audit_inv.scan_date = getCurrentDate(2);
                    inventory_products_audit_inv.push(product_data_audit_inv);
                    inventory_data_audit_inv.inv_imported = parseInt(inventory_data_audit_inv.inv_imported)+1;
                    
                    displayAddWHAuditInventoryData();
                    
                    setTimeout(function(){ 
                        var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#product_added_span\').html(\'\').hide();" href="javscript:;"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#product_added_span").html(success_msg).addClass('alert-success').removeClass('alert-danger').show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_barcode_audit_wh_inv,#piece_id").val('');
                        $("#piece_barcode_audit_wh_inv").focus();
                    }, 1000);
                }
            }else{
                $("#product_added_span").html(msg.message).addClass('alert-danger').removeClass('alert-success').show();
            }
        },error:function(obj,status,error){
            $("#product_added_span").html('Error in processing request').addClass('alert-danger').removeClass('alert-success').show();
        }
    });
}

$("#piece_barcode_audit_wh_inv").on('propertychange change paste input', function(e){
    setTimeout(function(){   
        var val = $("#piece_barcode_audit_wh_inv").val();
        getWHAuditInventoryProductData(val);
    }, 500);
    
});

function displayAddWHAuditInventoryData(){
    var str = ''; 
    str+='Total Inventory: '+inventory_data_audit_inv.inv_total+" | Scanned Inventory: "+inventory_data_audit_inv.inv_imported;
    if(inventory_products_audit_inv.length > 0){
        str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:13px;"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>SKU</th><th>Cost</th><th>Scan Date</th><th>Intake Date</th><th>Status</th><th>Present in System</th><th>Present in Warehouse</th><th>Type</th></tr></thead><tbody>'

        var start = parseInt((page_global_audit_inv-1)*rec_per_page_audit_inv)+1;
        for(var i=0;i<inventory_products_audit_inv.length;i++){
            var product_data_audit_inv = inventory_products_audit_inv[i];
            var intake_date = product_data_audit_inv.intake_date != null?displayDate(product_data_audit_inv.intake_date):'-';
            var scan_date = (product_data_audit_inv.scan_date != null)?formatDate(product_data_audit_inv.scan_date):'-';
            var present_system = (product_data_audit_inv.present_system == 1)?'Yes':'No';
            var present_wh = (product_data_audit_inv.present_warehouse == 1)?'Yes':'No';
            var cost_price = product_data_audit_inv.base_price != null?product_data_audit_inv.base_price:'';
            var id = product_data_audit_inv.id;
            var product_status = product_data_audit_inv.product_status;
             
            if(present_system == 'Yes'){
                var inv_type = (product_data_audit_inv.arnon_inventory == 1)?'Arnon':'Northcorp';
            }else{
                var inv_type = '';
            }
            
            if(present_system != present_wh || product_status != 1){
                var bg_color = 'style="background-color:#F7C1BD"';
            }else{
                var bg_color = '';
            }
            
            str+='<tr '+bg_color+'>';
            
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="audit-inv-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            
            str+='<td>'+product_data_audit_inv.peice_barcode+'</td><td>'+product_data_audit_inv.product_name+' '+product_data_audit_inv.size_name+' '+product_data_audit_inv.color_name+'</td>\
            <td>'+product_data_audit_inv.product_sku+'</td><td>'+cost_price+'</td><td>'+scan_date+'</td><td>'+intake_date+'</td><td>'+getProductInventoryStatus(product_data_audit_inv.product_status)+'</td>\
            <td>'+present_system+'</td><td>'+present_wh+'</td><td>'+inv_type+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    if(inventory_data_audit_inv.inv_imported >= inventory_data_audit_inv.inv_total){
        $("#importPosInventoryErrorMessage").html("Total Inventory of Invoice imported").css('margin-top','10px').show();
        $("#piece_barcode_inv_import,#pos_add_inventory_submit").attr('disabled',true);
    }
    
    $("#products_imported_list").html(str);
}

function loadWHAuditInventory(page){
    var audit_id = $("#audit_id").val();
    var store_id = $("#store_id").val();
    var scan_status = $("#scan_status").val();
    var inv_status_search = $("#inv_status_search").val();
    var store_id_search = $("#store_id_search").val();
    var product_status_search = $("#product_status_search").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/inventory/scan/wh/"+audit_id+"?action=load_audit_inventory&d="+new Date().getTime(),
        method:"get",
        data:{audit_id:audit_id,page:page,store_id:store_id,scan_status:scan_status,inv_status_search:inv_status_search,store_id_search:store_id_search,product_status_search:product_status_search},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#auditInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;
                    var paging_links = msg.paging_links;
                    inventory_products_audit_inv = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_audit_inv.push(product_list[i]);
                    }
                    
                    rec_per_page_audit_inv = msg.rec_per_page;
                    page_global_audit_inv = page;
                    inventory_data_audit_inv = msg.inventory_count_data;
                    barcode_list = msg.barcode_list;
                    displayAddWHAuditInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadWHAuditInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadWHAuditInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"auditInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#auditInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function searchWHAuditInventory(){
    loadWHAuditInventory(1);
}

function deleteWHAuditInventoryItems(){
    var audit_id = $("#audit_id").val();
    
    var chk_class = 'audit-inv-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#audit_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#deleteInventoryDemandItemsErrorMessage,#deleteInventoryDemandItemsSuccessMessage").html('').hide();
    
    $('#audit_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_audit_items_btn', function(e) {
        e.preventDefault();
        $("#delete_audit_items_btn,#delete_audit_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,audit_id:audit_id},
            url:ROOT_PATH+"/audit/inventory/scan/wh/"+audit_id+"?action=delete_audit_inv_items",
            success: function(msg){	
                $("#delete_audit_items_btn,#delete_audit_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteAuditItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteAuditItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#audit_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteAuditItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteAuditItemsErrorMessage").html('Error in processing request').show();
                $("#delete_audit_items_btn,#delete_audit_items_cancel").attr('disabled',false);
            }
        });
    });
}

function submitScanBulkWHInventory(){
    $("#audit_scan_upload_submit").attr("disabled",true);
    $("#auditWHInventoryScanBulkFrm").submit();
}

function completeWHAuditScan(){
    $("#completeAuditScanSuccessMessage,#completeAuditScanErrorMessage").html('').hide();
    $("#complete_audit_scan_comment").val('');
    $("#completeAuditScanInventoryDialog").modal('show');
}

function submitCompleteWHAuditScan(){
    var comments = $("#complete_audit_scan_comment").val();
    var audit_id = $("#audit_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/audit/inventory/scan/wh/"+audit_id+"?action=complete_audit_scan&d="+new Date().getTime(),
        method:"POST",
        data:{comments:comments,audit_id:audit_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#completeAuditScanErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#completeAuditScanSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/audit/inventory/scan/detail/wh/"+audit_id;
                    setTimeout(function(){  $("#completeAuditScanInventoryDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"completeAuditScanErrorMessage");
            }
        },error:function(obj,status,error){
            $("#completeAuditScanErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateWHVarianceReportType(audit_id,type){
    var url = ROOT_PATH+"/audit/inventory/report/variance/wh/"+audit_id+"/"+type;
    window.location.href = url;
}

/*function downloadAuditData(audit_id){
    
    
    var url = ROOT_PATH+"/audit/inventory/scan/"+audit_id+"?action=download_csv&audit_id="+audit_id;
    window.location.href = url;
}*/

function downloadAuditData(){
    $("#downloadAuditInventoryDialog .form-control").val('');
    $("#error_validation_audit_inventory_count").html('').hide();
    $("#downloadAuditInventoryDialog").modal('show');
}

function submitDownloadAuditData(){
    var audit_inventory_count = $("#audit_inventory_count").val(), str = '';
    $("#error_validation_audit_inventory_count").html('').hide();
    if(audit_inventory_count == ''){
        $("#error_validation_audit_inventory_count").html('Audit Inventory Records is Required Field').show();
        return false;
    }
    
    /*const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);*/
    
    var audit_id = $("#audit_id").val();

    var url = ROOT_PATH+"/audit/inventory/scan/"+audit_id+"?action=download_csv&audit_id="+audit_id+"&audit_inventory_count="+audit_inventory_count; //ROOT_PATH+"/pos/order/list?action=download_csv&inv_count="+audit_inventory_count+"&"+str;
    window.location.href = url;
}