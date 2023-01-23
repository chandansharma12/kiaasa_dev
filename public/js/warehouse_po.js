"use strict";

function createInventoryPushDemand(){
    $("#addInventoryPushDemandSuccessMessage,#addInventoryPushDemandErrorMessage").html('').hide();
    $("#store_id").val('');
    $(".transfer-field").hide();
    $("#transfer_field,#transfer_percent").val('');
    $("#add_inventory_push_demand_dialog").modal('show');
}

function submitCreateInventoryPushDemand(){
    $("#add_inventory_push_demand_spinner").show();
    $("#inventory_push_demand_add_cancel,#inventory_push_demand_add_submit").attr('disabled',true);
    var store_id = $("#store_id").val();
    var transfer_field = $("#transfer_field").val();
    var transfer_percent = $("#transfer_percent").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/list?action=create_demand",
        method:"GET",
        data:{store_id:store_id,transfer_field:transfer_field,transfer_percent:transfer_percent},
        success:function(msg){
            $("#add_inventory_push_demand_spinner").hide();
            $("#inventory_push_demand_add_cancel,#inventory_push_demand_add_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryPushDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+msg.demand_details.id;
                    setTimeout(function(){  $("#add_inventory_push_demand_dialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"addInventoryPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_inventory_push_demand_spinner").hide();
            $("#inventory_push_demand_add_cancel,#inventory_push_demand_add_submit").attr('disabled',false);
            $("#addInventoryPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

var inventory_products_inv_push_demand = [],  rec_per_page_inv_push_demand = '', page_global_inv_push_demand = 1,inventory_data_push_demand = {},inv_push_barcode = '',inv_push_time = 0;

function getInventoryPushDemandProductData(barcode){
    $("#piece_barcode_inv_push_demand").attr('disabled',true).attr('readonly',true);
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    if(barcode == ''){
        $("#importPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    if(barcode.length < 6){
        $("#importPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_push_barcode != '' && inv_push_barcode == barcode && Math.abs((inv_push_time) - time)/1000 < 1){
        return false;
    }
    
    inv_push_barcode = barcode;
    inv_push_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,demand_id:demand_id,store_id:store_id},
        success:function(msg){
            $("#importPosInventoryErrorMessage,#importPosInventorySuccessMessage").html('').hide();
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode_inv_push_demand").val('').attr('disabled',false).attr('readonly',false).focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#importPosInventoryErrorMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#importPosInventoryErrorMessage").html(errors).show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_inv_push_demand = msg.product_data;
                    $("#product_name").val(product_data_inv_push_demand.product_name);
                    $("#piece_size").val(product_data_inv_push_demand.size_name);
                    $("#piece_color").val(product_data_inv_push_demand.color_name);
                    $("#piece_vendor").val('');
                    $("#piece_po_number").val('');
                    $("#product_sku").val(product_data_inv_push_demand.vendor_sku);
                    $("#piece_cost").val(product_data_inv_push_demand.base_price);
                    $("#intake_date").val(product_data_inv_push_demand.intake_date);
                    $("#piece_id").val(product_data_inv_push_demand.id);
                    $("#product_id").val(product_data_inv_push_demand.product_master_id);
                    
                    product_data_inv_push_demand.product_status = 2;
                    product_data_inv_push_demand.store_assign_date = getCurrentDate(2);
                    inventory_products_inv_push_demand.push(product_data_inv_push_demand);
                    inventory_data_push_demand.inventory_count = parseInt(inventory_data_push_demand.inventory_count)+1;
                    displayInventoryPushDemandInventoryData();
                    
                    setTimeout(function(){ 
                         var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#importPosInventorySuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#importPosInventorySuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#piece_barcode_inv_push_demand").val('').attr('disabled',false).attr('readonly',false).focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
                $("#piece_barcode_inv_push_demand").attr('disabled',false).attr('readonly',false);
            }
            
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
            $("#piece_barcode_inv_push_demand").attr('disabled',false).attr('readonly',false);;
        }
    });
}

$("#piece_barcode_inv_push_demand").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#piece_barcode_inv_push_demand").val();
        getInventoryPushDemandProductData(val);
    }, 500);
});

function displayInventoryPushDemandInventoryData(){
    var str = '';
    str+='Total Inventory: '+inventory_data_push_demand.inventory_count;
    str+='<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_push_demand" id="barcode_search_inv_push_demand" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_push_demand" id="sku_search_inv_push_demand" class="form-control"><option value="">SKU</option></select></div>';
    str+='<div class="col-md-2">';
    str+='<button type="button" id="pos_inv_push_demand_search" name="pos_inv_push_demand_search" class="btn btn-dialog" value="Submit" onclick="searchPushDemandInv();">Search</button>';
    //str+='<button type="button" id="pos_inv_push_demand_add" name="pos_inv_push_demand_add" class="btn btn-dialog" value="Submit" style="margin-left: 15px;" onclick="addPushDemandInv();">Add Inventory </button>';
    str+='</div></div><div class="separator-10"></div>';
    str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr">';
    str+='<th> <input type="checkbox" name="pos_product_list_all" id="pos_product_list_all"  value="1" onclick="checkAllCheckboxes(this,\'push-demand\');"> SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>Store Price</th><th>Out Date</th><th>Status</th></tr></thead><tbody>'
    
    if(inventory_products_inv_push_demand.length > 0){    
        var start = parseInt((page_global_inv_push_demand-1)*rec_per_page_inv_push_demand)+1;
        for(var i=0;i<inventory_products_inv_push_demand.length;i++){
            var product_data_inv_push_demand = inventory_products_inv_push_demand[i];
            var store_assign_date = formatDate(product_data_inv_push_demand.store_assign_date);
            var id = product_data_inv_push_demand.id;
            var sku = (product_data_inv_push_demand.vendor_sku != null)?product_data_inv_push_demand.vendor_sku:product_data_inv_push_demand.product_sku;
            str+='<tr>';
            // if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="push-demand-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
                
            // }else{
            //     str+='<td> '+(start+i)+'</td>';
            // }
            str+='<td>'+product_data_inv_push_demand.peice_barcode+'</td><td>'+product_data_inv_push_demand.product_name+'</td><td>'+product_data_inv_push_demand.size_name+'</td><td>'+product_data_inv_push_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_push_demand.base_price+'</td><td>'+product_data_inv_push_demand.store_base_price+'</td><td>'+store_assign_date+'</td><td>'+getProductInventoryStatus(product_data_inv_push_demand.product_status)+'</td></tr>';
        }
    }else{
        str+='<tr><td colspan="10" align="center">No Records</td></tr>';
    }    
    
    str+='</tbody></table></div>';
    // str+= '<input type="button" name="get_barcodes_btn" id="get_barcodes_btn" value="Delete" class="btn btn-dialog">';
    
    $("#products_imported_list").html(str);
    
}

function searchPushDemandInv(){
    $("#pos_inv_push_demand_search").attr('disabled',true);
    loadInventoryPushDemandInventory(1);
    $("#pos_inv_push_demand_search").attr('disabled',false);
}

function addPushDemandInv(){
    // alert('hello');
    $('#push_demand_add_items_dialog').modal('show');
}

function loadInventoryPushDemandInventory(page){
    var demand_id  = $("#demand_id").val();
    var barcode    = $("#barcode_search_inv_push_demand").val();
    var product_id = $("#sku_search_inv_push_demand").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page,barcode:barcode,product_id:product_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;//alert(msg);
                    var paging_links = msg.paging_links;
                    paging_links     += getAjaxPagingLinks(msg.product_list);
                    var sku_list     = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    
                    inventory_products_inv_push_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_push_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_push_demand               = msg.rec_per_page;
                    page_global_inv_push_demand                = page;
                    inventory_data_push_demand.inventory_count = msg.inventory_count;
                    displayInventoryPushDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    for(var i=0;i<sku_list.length;i++){
                        sku_option_str+='<option value="'+sku_list[i].id+'">'+sku_list[i].vendor_sku+" - "+sku_list[i].size_name+" ("+sku_list[i].inv_count+")"+'</option>';
                    }
                    
                    $("#sku_search_inv_push_demand").html(sku_option_str);
                    $("#sku_search_inv_push_demand").val(product_id);
                    $("#barcode_search_inv_push_demand").val(barcode);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadInventoryPushDemandInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadInventoryPushDemandInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitAddDemandDetailInventory(){
    var demand_id       = $("#demand_id_hdn").val();
    var store_id        = $("#store_id").val();
    var token           = $('meta[name="csrf-token"]').attr('content');
    var barcode_arr     = [];
    var arr = $('input[name="piece_barcode_audit_inv[]"]').map(function () {
        return this.value;
    }).get();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id+"?action=add-inventory-push",
        method:"post",
        data:{
            barcode:arr,
            demand_id:demand_id,
            store_id:store_id,
            _token: token
        },
        success:function(msg){
            $("#push_demand_add_items_dialog").modal('hide'); 
            setInterval('location.reload()',2000); 
            
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
    let myLength=arr.reduce((acc,cv)=>(cv)?acc+1:acc,0);
    $('#importPosInventorySuccessMessage').html(myLength+" record added successfully");
}



function closeInventoryPushDemand(){
    $("#closeInventoryPushDemandSuccessMessage,#closeInventoryPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#courier_detail,#vehicle_detail").val('');
    $("#closeInventoryPushDemandDialog").modal('show');
    
    var demand_id  = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=get_demand_preview_data",
        method:"GET",
        data:{demand_id:demand_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#closeInventoryPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var demand_data = msg.demand_data;
                    var store_data = msg.store_data;
                    var total_data = msg.total_data;
                    $("#demand_prev_type").val('Warehouse to Store');
                    $("#demand_prev_date_created").val(displayDate(demand_data.created_at));
                    $("#demand_prev_store_name").val(demand_data.store_name);
                    $("#demand_prev_store_gst_no").val(store_data.gst_no);
                    $("#demand_prev_total_inv").val(total_data.total_qty);
                    $("#demand_prev_taxable_value").val(total_data.total_taxable_val.toFixed(2));
                    $("#demand_prev_gst_amount").val(total_data.total_gst_amt.toFixed(2));
                    $("#demand_prev_total_amt").val(total_data.total_value.toFixed(2));
                    $("#demand_prev_total_sale_price").val(total_data.total_sale_price.toFixed(2));
                }
            }else{
                displayResponseError(msg,"closeInventoryPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitCloseInventoryPushDemand(){
    var demand_id = $("#demand_id").val();
    var courier_detail = $("#courier_detail").val();
    var vehicle_detail = $("#vehicle_detail").val();
    var boxes_count = $("#boxes_count").val();
    var transporter_name = $("#transporter_name").val();
    var transporter_gst = $("#transporter_gst").val();
    var docket_no = $("#docket_no").val();
    var eway_bill_no = $("#eway_bill_no").val();
    var lr_no = $("#lr_no").val();
    var discount_applicable = 0; //$("#discount_applicable").val();
    var discount_percent = 0; //$("#discount_percent").val();
    var gst_inclusive = 0; //$("#gst_inclusive").val();
    $("#closeInventoryPushDemandSpinner").show();
    $("#closeInventoryPushDemandSuccessMessage,#closeInventoryPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#closeInventoryPushDemandCancel,#closeInventoryPushDemandSubmit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=close_demand",
        method:"POST",
        data:{courier_detail:courier_detail,vehicle_detail:vehicle_detail,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no,discount_applicable:discount_applicable,discount_percent:discount_percent,gst_inclusive:gst_inclusive,lr_no:lr_no},
        success:function(msg){
            $("#closeInventoryPushDemandSpinner").hide();
            $("#closeInventoryPushDemandCancel,#closeInventoryPushDemandSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#closeInventoryPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#closeInventoryPushDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id;
                    setTimeout(function(){  $("#closeInventoryPushDemandDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"closeInventoryPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryPushDemandSpinner").hide();
            $("#closeInventoryPushDemandCancel,#closeInventoryPushDemandSubmit").attr('disabled',false);
            $("#closeInventoryPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function editGatePassData(){
    $("#editGatePassDialog").modal('show');
}

function submitEditGatePassData(){
    var demand_id = $("#demand_id").val();
    var boxes_count = $("#boxes_count").val();
    var transporter_name = $("#transporter_name").val();
    var transporter_gst = $("#transporter_gst").val();
    var docket_no = $("#docket_no").val();
    var eway_bill_no = $("#eway_bill_no").val();
    var discount_applicable = $("#discount_applicable").val();
    var discount_percent = $("#discount_percent").val();
    var gst_inclusive = $("#gst_inclusive").val();
    var lr_no = $("#lr_no").val();
    var ship_to = $("#ship_to").val();
    var dispatch_by = $("#dispatch_by").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=update_gate_pass_data",
        method:"POST",
        data:{demand_id:demand_id,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no,discount_applicable:discount_applicable,discount_percent:discount_percent,gst_inclusive:gst_inclusive,lr_no:lr_no,ship_to:ship_to,dispatch_by:dispatch_by},
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

function deleteInventoryPushDemandItems(){
    var demand_id = $("#demand_id").val();
    var chk_class = 'push-demand-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#push_demand_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#deletePushDemandItemsErrorMessage").html('').hide();
    
    $('#push_demand_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_push_demand_items_btn', function(e) {
        e.preventDefault();
        $("#delete_push_demand_items_btn,#delete_push_demand_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,demand_id:demand_id},
            url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=delete_push_demand_items",
            success: function(msg){	
                $("#delete_push_demand_items_btn,#delete_push_demand_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deletePushDemandItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deletePushDemandItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#push_demand_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deletePushDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deletePushDemandItemsErrorMessage").html('Error in processing request').show();
                $("#delete_push_demand_items_btn,#delete_push_demand_items_cancel").attr('disabled',false);
            }
        });
    });
}

function cancelInventoryPushDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_cancel_dialog").modal('show');
}

function submitCancelInventoryPushDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',true);
    var comments = $("#cancel_comments").val();
    var demand_id = $("#demand_id_hdn").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id+"?action=cancel_demand",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#push_demand_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            $("#cancelPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function cancelInventoryPushDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_cancel_dialog").modal('show');
}

function submitCancelInventoryPushDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',true);
    var comments = $("#cancel_comments").val();
    var demand_id = $("#demand_id_hdn").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id+"?action=cancel_demand",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#push_demand_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            $("#cancelPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function loadFakeInventoryDemandInStore(){
    $("#loadPushDemandSuccessMessage,#loadPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_load_dialog").modal('show');
}

function submitLoadFakeInventoryDemandInStore(){
    $("#loadPushDemandSuccessMessage,#loadPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#loadPushDemandSubmit,#loadPushDemandCancel").attr('disabled',true);
    var comments = $("#load_demand_comments").val();
    var demand_id = $("#demand_id_hdn").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id+"?action=load_fake_inventory_demand_in_store",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#loadPushDemandSubmit,#loadPushDemandCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#loadPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#loadPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#loadPushDemandCancel").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"loadPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#loadPushDemandSubmit,#loadPushDemandCancel").attr('disabled',false);
            $("#loadPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

var inventory_products_import_inv = [],  rec_per_page_import_inv = '', page_global_import_inv = 1, inventory_data_import_inv = '',barcode_list = [],id_import_inv = '',inv_import_barcode = '',inv_import_time = 0;;

function getInventoryProductData(barcode){
    $("#piece_barcode_inv_import").attr('disabled',true).attr('readonly',true);
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    if(barcode == ''){
        $("#product_added_span").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    if(barcode.length < 6){
        $("#product_added_span").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_import_barcode != '' && inv_import_barcode == barcode && Math.abs((inv_import_time) - time)/1000 < 1){
        return false;
    }
    
    inv_import_barcode = barcode;
    inv_import_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,po_id:po_id,po_detail_id:po_detail_id},
        success:function(msg){
            $("#product_added_span").html('').hide();
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        setTimeout(function(){
                            errors = errors+' &nbsp;&nbsp;<a onclick="$(\'#product_added_span\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#product_added_span").html(errors).addClass('alert-danger').removeClass('alert-success').show();
                        }, 200);
                        $("#piece_id,.import-data").val('');
                        setTimeout(function(){ $("#piece_barcode_inv_import").val('').attr('disabled',false).attr('readonly',false).focus();  }, 1000);
                    } 
                }else{ 
                    $("#product_added_span").html('');
                    var product_data_import_inv = msg.product_data;
                    $("#product_name").val(product_data_import_inv.product_name);
                    $("#piece_size").val(product_data_import_inv.size_name);
                    $("#piece_color").val(product_data_import_inv.color_name);
                    $("#piece_vendor").val(product_data_import_inv.vendor_name);
                    $("#piece_po_number").val(product_data_import_inv.po_order_no);
                    $("#product_sku").val(product_data_import_inv.vendor_sku);
                    $("#piece_cost").val(product_data_import_inv.base_price);
                    $("#intake_date").val(getCurrentDate(1));
                    $("#piece_id").val(product_data_import_inv.id);

                    product_data_import_inv.product_status = 1;
                    product_data_import_inv.intake_date = getCurrentDate(2);
                    inventory_products_import_inv.push(product_data_import_inv);
                    inventory_data_import_inv.inv_imported = parseInt(inventory_data_import_inv.inv_imported)+1;
                    displayAddInventoryData();
                    
                    setTimeout(function(){ 
                        var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#product_added_span\').html(\'\').hide();" href="javscript:;"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#product_added_span").html(success_msg).addClass('alert-success').removeClass('alert-danger').show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_barcode_inv_import,#piece_id").val('').attr('disabled',false).attr('readonly',false);
                        $("#piece_barcode_inv_import").focus();
                    }, 1000);
                }
            }else{
                $("#product_added_span").html(msg.message).addClass('alert-danger').removeClass('alert-success').show();
                $("#piece_barcode_inv_import").val('').attr('disabled',false).attr('readonly',false).focus(); 
            }
        },error:function(obj,status,error){
            $("#product_added_span").html('Error in processing request').addClass('alert-danger').removeClass('alert-success').show();
            $("#piece_barcode_inv_import").val('').attr('disabled',false).attr('readonly',false).focus(); 
        }
    });
}

$("#piece_barcode_inv_import").on('propertychange change paste input', function(e){
    setTimeout(function(){   
        var val = $("#piece_barcode_inv_import").val();
        getInventoryProductData(val);
    }, 500);
    
});

function displayAddInventoryData(){
    var str = '';//<hr><h6>Inventory Details: </h6>';
    str+='Total Inventory: '+inventory_data_import_inv.inv_total+" | Imported Inventory: "+inventory_data_import_inv.inv_imported;
    str+='<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_imp" id="barcode_search_inv_imp" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_imp" id="sku_search_inv_imp" class="form-control"><option value="">SKU</option></select></div><div class="col-md-1"><button type="button" id="pos_inv_imp_add" name="pos_inv_imp_add" class="btn btn-dialog" value="Submit" onclick="searchImportInv();">Search</button></div></div><div class="separator-10"></div>';
    str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:13px;"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>Vendor</th><th>PO No</th><th>SKU</th><th>Cost</th><th>Intake Date</th><th>Status</th><th>QC</th></tr></thead><tbody>'
    
    if(inventory_products_import_inv.length > 0){    
        var start = parseInt((page_global_import_inv-1)*rec_per_page_import_inv)+1;
        for(var i=0;i<inventory_products_import_inv.length;i++){
            var product_data_import_inv = inventory_products_import_inv[i];
            var intake_date = formatDate(product_data_import_inv.intake_date);
            var id = product_data_import_inv.id;
            str+='<tr>';
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="inv-import-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            str+='<td>'+product_data_import_inv.peice_barcode+'</td><td>'+product_data_import_inv.product_name+'</td><td>'+product_data_import_inv.size_name+'</td><td>'+product_data_import_inv.color_name+'</td>\
            <td>'+product_data_import_inv.vendor_name+'</td><td>'+product_data_import_inv.po_order_no+'</td><td>'+product_data_import_inv.vendor_sku+'</td><td>'+product_data_import_inv.base_price+'</td><td>'+intake_date+'</td><td>'+getProductInventoryStatus(product_data_import_inv.product_status)+'</td><td>'+getProductInventoryQCStatus(product_data_import_inv.qc_status)+'</td></tr>';
        }
    }else{
        str+='<tr><td colspan="12" align="center">No Records</td></tr>';
    }
    
    str+='</tbody></table></div>';
    
    if(inventory_data_import_inv.inv_imported >= inventory_data_import_inv.inv_total){
        $("#importPosInventoryErrorMessage").html("Total Inventory of Invoice imported").css('margin-top','10px').show();
        $("#piece_barcode_inv_import,#pos_add_inventory_submit,#upload_po_invoice_inv_link").attr('disabled',true);
    }
    
    $("#products_imported_list").html(str);
}

function searchImportInv(){
    $("#pos_inv_imp_search").attr('disabled',true);
    loadPOInventory(1);
    $("#pos_inv_imp_search").attr('disabled',false);
}

function loadPOInventory(page){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var barcode = $("#barcode_search_inv_imp").val();
    var product_id = $("#sku_search_inv_imp").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=load_po_inventory",
        method:"get",
        data:{po_id:po_id,page:page,po_detail_id:po_detail_id,barcode:barcode,product_id:product_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_data = msg.product_list.data;
                    var paging_links = msg.paging_links
                    paging_links+=getAjaxPagingLinks(msg.product_list);
                    
                    var sku_list = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    inventory_products_import_inv = [];
                    for(var i=0;i<product_data.length;i++){
                        inventory_products_import_inv.push(product_data[i]);
                    }
                    
                    rec_per_page_import_inv = msg.rec_per_page;
                    page_global_import_inv = page;
                    inventory_data_import_inv = msg.inventory_count_data;
                    
                    displayAddInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    for(var i=0;i<sku_list.length;i++){
                        sku_option_str+='<option value="'+sku_list[i].id+'">'+sku_list[i].vendor_sku+" - "+sku_list[i].size_name+" ("+sku_list[i].inv_count+")"+'</option>';
                    }
                    
                    $("#sku_search_inv_imp").html(sku_option_str);
                    $("#sku_search_inv_imp").val(product_id);
                    $("#barcode_search_inv_imp").val(barcode);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadPOInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadPOInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteInventoryImportItems(){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    var chk_class = 'inv-import-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#inventory_import_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#inventoryImportItemsDeleteErrorMessage").html('').hide();
    
    $('#inventory_import_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_inventory_import_items_btn', function(e) {
        e.preventDefault();
        $("#delete_inventory_import_items_btn,#delete_inventory_import_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,po_id:po_id,po_detail_id:po_detail_id},
            url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=delete_inv_import_items",
            success: function(msg){	
                $("#delete_inventory_import_items_btn,#delete_inventory_import_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#inventoryImportItemsDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#inventoryImportItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#inventory_import_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"inventoryImportItemsDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#inventoryImportItemsDeleteErrorMessage").html('Error in processing request').show();
                $("#delete_push_demand_items_btn,#delete_push_demand_items_cancel").attr('disabled',false);
            }
        });
    });
}

function submitAddPOInvoice(){
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
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=add_vehicle_details",
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

function displayAddPOInvoice(){
    $("#vehicle_no,#containers_count,#comments,.form-control").val('');
    var po_id = $("#po_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/purchase-order/product/invoice/list/"+po_id+"?action=get_pending_import_inv_count",
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

function displayPOInvoiceDetails(id){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+id+"?action=get_inventory_detail",
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

function submitUpdatePOInvoiceDetails(){
    $("#updateInvoiceDetailsErrorMessage,#updateInvoiceDetailsSuccessMessage").html('').hide();
    $("#update_po_invoice_detail_submit,#update_po_invoice_detail_cancel").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var form_data = $("#vehicleDetailForm").serialize();
    form_data+="&po_id="+po_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=update_po_invoice_details",
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

var inventory_products_qc_inv = [],  rec_per_page_qc_inv = '', page_global_qc_inv = 1,inventory_data_qc_inv = '',inv_qc_barcode = '',inv_qc_time = 0,qc_return_data = null;

function getInventoryProductDataForQC(barcode){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    //if(barcode == '' || barcode.length < 15){
    if(barcode == '' || barcode.length < 6){
        $("#qcPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_qc_barcode != '' && inv_qc_barcode == barcode && Math.abs((inv_qc_time) - time)/1000 < 1){
        return false;
    }
    
    inv_qc_barcode = barcode;
    inv_qc_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,po_id:po_id,po_detail_id:po_detail_id},
        success:function(msg){
            $("#qcPosInventoryErrorMessage,#qcPosInventorySuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode_inv_qc").val('').focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#qcPosInventoryErrorMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#qcPosInventoryErrorMessage").html(errors).show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_qc_inv = msg.product_data;
                    $("#product_name").val(product_data_qc_inv.product_name);
                    $("#piece_size").val(product_data_qc_inv.size_name);
                    $("#piece_color").val(product_data_qc_inv.color_name);
                    $("#piece_vendor").val(product_data_qc_inv.vendor_name);
                    $("#piece_po_number").val(product_data_qc_inv.po_order_no);
                    $("#product_sku").val(product_data_qc_inv.vendor_sku);
                    $("#piece_cost").val(product_data_qc_inv.base_price);
                    $("#qc_date").val(getCurrentDate(1));
                    $("#piece_id").val(product_data_qc_inv.id);
                    $("#qcPosInventoryErrorMessage").html('').hide();
                    
                    product_data_qc_inv.qc_status = 2;
                    product_data_qc_inv.qc_date = getCurrentDate(2);
                    inventory_products_qc_inv.push(product_data_qc_inv);
                    inventory_data_qc_inv.inventory_defective = parseInt(inventory_data_qc_inv.inventory_defective)+1;
                    inventory_data_qc_inv.inventory_qc_pending = parseInt(inventory_data_qc_inv.inventory_qc_pending)-1;
                    displayQCInventoryData();

                    setTimeout(function(){ 
                         var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#qcPosInventorySuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#qcPosInventorySuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#piece_barcode_inv_qc").val('').focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"qcPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#qcPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

$("#piece_barcode_inv_qc").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#piece_barcode_inv_qc").val();
        getInventoryProductDataForQC(val);
    }, 500);
});

function displayQCInventoryData(){
    var str = '';
    str+='Total Inventory: '+inventory_data_qc_inv.inventory_total+' &nbsp;|&nbsp; Accepted Inventory: '+inventory_data_qc_inv.inventory_accepted+' &nbsp;|&nbsp; Defective Inventory: '+inventory_data_qc_inv.inventory_defective+' &nbsp;|&nbsp; QC Pending Inventory: '+inventory_data_qc_inv.inventory_qc_pending;
    str+='<div class="separator-10"><div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_qc" id="barcode_search_inv_qc" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_qc" id="sku_search_inv_qc" class="form-control"><option value="">SKU</option></select></div><div class="col-md-1"><button type="button" id="pos_inv_qc_search" name="pos_inv_qc_search" class="btn btn-dialog" value="Submit" onclick="searchQCInv();">Search</button></div></div><div class="separator-10"></div>';
    
    str+='<div class="separator-10"></div><h6>QC Completed Inventory: </h6><div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>Vendor</th><th>PO No</th><th>SKU</th><th>Cost</th><th>QC Date</th><th>QC Status</th><th>Action</th></tr></thead><tbody>'
    
    if(inventory_products_qc_inv.length > 0){    
        var start = parseInt((page_global_qc_inv-1)*rec_per_page_qc_inv)+1;
        for(var i=0;i<inventory_products_qc_inv.length;i++){
            var product_data_qc_inv = inventory_products_qc_inv[i];
            var qc_date = formatDate(product_data_qc_inv.qc_date);
            var id = product_data_qc_inv.id;
            str+='<tr>';
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="inv-qc-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            
            var css_class = (product_data_qc_inv.qc_status == 2)?'text-sel':'';
            
            str+='<td>'+product_data_qc_inv.peice_barcode+'</td><td>'+product_data_qc_inv.product_name+'</td><td>'+product_data_qc_inv.size_name+'</td><td>'+product_data_qc_inv.color_name+'</td>\
            <td>'+product_data_qc_inv.vendor_name+'</td><td>'+product_data_qc_inv.po_order_no+'</td><td>'+product_data_qc_inv.vendor_sku+'</td><td>'+product_data_qc_inv.base_price+'</td><td>'+qc_date+'</td><td class="'+css_class+'">'+getProductInventoryQCStatus(product_data_qc_inv.qc_status)+'</td>';
            
            if(product_data_qc_inv.qc_id > 0 && qc_return_data == null && product_data_qc_inv.product_status == 1){
                var status_update_text = (product_data_qc_inv.qc_status == 1)?'Defective':'Accepted';
                $("#name_update_qc").html(status_update_text);
                str+='<td><a href="javascript:;" onclick="updateInventoryItemQC('+id+')"><i title="Update as '+status_update_text+'" class="fas fa-edit"></i></a></td>';
            }else{
                str+='<td></td>';
            }
            str+='</tr>';
        }
    }else{
        str+='<tr><td colspan="12" align="center">No Records</td></tr>';
    }    
    
    str+='</tbody></table></div>';
    $("#products_qc_list").html(str);
}

function loadPOQCInventory(page){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var barcode = $("#barcode_search_inv_qc").val();
    var product_id = $("#sku_search_inv_qc").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=load_po_inventory",
        method:"get",
        data:{po_id:po_id,po_detail_id:po_detail_id,page:page,barcode:barcode,product_id:product_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#qcPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;//alert(msg);
                    var paging_links = msg.paging_links;
                    var paging_links = msg.paging_links
                    qc_return_data   = msg.qc_return_data;
                    paging_links+=getAjaxPagingLinks(msg.product_list);
                    
                    var sku_list = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    inventory_products_qc_inv = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_qc_inv.push(product_list[i]);
                    }
                    
                    rec_per_page_qc_inv = msg.rec_per_page;
                    page_global_qc_inv = page;
                    inventory_data_qc_inv = msg.inventory_qc_data;
                    displayQCInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    for(var i=0;i<sku_list.length;i++){
                        sku_option_str+='<option value="'+sku_list[i].id+'">'+sku_list[i].vendor_sku+" - "+sku_list[i].size_name+" ("+sku_list[i].inv_count+")"+'</option>';
                    }
                    
                    $("#sku_search_inv_qc").html(sku_option_str);
                    $("#sku_search_inv_qc").val(product_id);
                    $("#barcode_search_inv_qc").val(barcode);
                   
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadPOQCInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadPOQCInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"qcPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#qcPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function searchQCInv(){
    $("#pos_inv_qc_search").attr('disabled',true);
    loadPOQCInventory(1);
    $("#pos_inv_qc_search").attr('disabled',false);
}

function displayConfirmQCInventory(){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_id+"?action=get_inventory_qc_data",
        method:"get",
        data:{po_id:po_id,po_detail_id:po_detail_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#qcPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#inventory_total").val(msg.inventory_qc_data.inventory_total);
                    $("#inventory_accepted").val(msg.inventory_qc_data.inventory_accepted);
                    $("#inventory_defective").val(msg.inventory_qc_data.inventory_defective);
                    $("#comments_complete_qc").focus();
                    $("#inventory_qc_complete_confirm_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"qcPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#qcPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitConfirmQCInventory(){
    $("#qc_inventory_confirm_spinner").show();
    $("#qc_inventory_confirm_submit,#qc_inventory_confirm_cancel").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var comments = $("#comments_complete_qc").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=confirm_complete_inventory_qc",
        method:"POST",
        data:{po_id:po_id,po_detail_id:po_detail_id,comments:comments},
        success:function(msg){
            $("#qc_inventory_confirm_spinner").hide();
            $("#qc_inventory_confirm_submit,#qc_inventory_confirm_cancel").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#qcInventoryConfirmErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#qcInventoryConfirmSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#qcInventoryConfirmSuccessMessage").modal('hide');window.location.reload(); }, 250);
                }
            }else{
                displayResponseError(msg,"qcInventoryConfirmErrorMessage");
            }
        },error:function(obj,status,error){
            $("#qc_inventory_confirm_spinner").hide();
            $("#qc_inventory_confirm_submit,#qc_inventory_confirm_cancel").attr('disabled',false);
            $("#qcInventoryConfirmErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteInventoryQcItems(){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    var chk_class = 'inv-qc-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#inventory_qc_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#inventoryQcItemsDeleteErrorMessage").html('').hide();
    
    $('#inventory_qc_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_inventory_qc_items_btn', function(e) {
        e.preventDefault();
        $("#delete_inventory_qc_items_btn,#delete_inventory_qc_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,po_id:po_id,po_detail_id:po_detail_id},
            url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=delete_inv_qc_items",
            success: function(msg){	
                $("#delete_inventory_qc_items_btn,#delete_inventory_qc_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#inventoryQcItemsDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#inventoryQcItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#inventory_qc_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"inventoryQcItemsDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#inventoryQcItemsDeleteErrorMessage").html('Error in processing request').show();
                $("#delete_inventory_qc_items_btn,#delete_inventory_qc_items_cancel").attr('disabled',false);
            }
        });
    });
}

function updateInventoryItemQC(id){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    $("#inventoryQcItemUpdateErrorMessage").html('').hide();
    
    $('#inventory_qc_update_item_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#update_inventory_qc_item_btn', function(e) {
        e.preventDefault();
        $("#update_inventory_qc_item_btn,#update_inventory_qc_item_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id,po_id:po_id,po_detail_id:po_detail_id},
            url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=update_inv_item_qc",
            success: function(msg){	
                $("#update_inventory_qc_item_btn,#update_inventory_qc_item_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#inventoryQcItemUpdateErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#inventoryQcItemUpdateSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#inventory_qc_update_item_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"inventoryQcItemUpdateErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#inventoryQcItemUpdateErrorMessage").html('Error in processing request').show();
                $("#update_inventory_qc_item_btn,#update_inventory_qc_item_cancel").attr('disabled',false);
            }
        });
    });
}

function deleteInvoiceQC(id){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    $("#invoiceQCDeleteErrorMessage").html('').hide();
    
    $('#invoice_qc_delete_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#invoice_qc_delete_btn', function(e) {
        e.preventDefault();
        $("#invoice_qc_delete_btn,#invoice_qc_delete_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id,po_id:po_id,po_detail_id:po_detail_id},
            url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=delete_invoice_qc",
            success: function(msg){	
                $("#invoice_qc_delete_btn,#invoice_qc_delete_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#invoiceQCDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#invoiceQCDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#invoice_qc_delete_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"invoiceQCDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#invoiceQCDeleteErrorMessage").html('Error in processing request').show();
                $("#invoice_qc_delete_btn,#invoice_qc_delete_cancel").attr('disabled',false);
            }
        });
    });
}

function deleteInvoiceGRN(id){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    $("#invoiceGRNDeleteErrorMessage").html('').hide();
    
    $('#invoice_grn_delete_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#invoice_grn_delete_btn', function(e) {
        e.preventDefault();
        $("#invoice_grn_delete_btn,#invoice_grn_delete_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id,po_id:po_id,po_detail_id:po_detail_id},
            url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=delete_invoice_grn",
            success: function(msg){	
                $("#invoice_grn_delete_btn,#invoice_grn_delete_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#invoiceGRNDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#invoiceGRNDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#invoice_grn_delete_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"invoiceGRNDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#invoiceGRNDeleteErrorMessage").html('Error in processing request').show();
                $("#invoice_grn_delete_btn,#invoice_grn_delete_cancel").attr('disabled',false);
            }
        });
    });
}

function deletePOInvoice(po_id,po_detail_id){
    $("#invoiceDeleteErrorMessage").html('').hide();
    
    $('#invoice_delete_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#invoice_delete_btn', function(e) {
        e.preventDefault();
        $("#invoice_delete_btn,#invoice_delete_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{po_id:po_id,po_detail_id:po_detail_id},
            url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=delete_po_invoice",
            success: function(msg){	
                $("#invoice_delete_btn,#invoice_delete_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#invoiceDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#invoiceDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#invoice_delete_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"invoiceDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#invoiceDeleteErrorMessage").html('Error in processing request').show();
                $("#invoice_delete_btn,#invoice_delete_cancel").attr('disabled',false);
            }
        });
    });
}

function displayAddInventoryGRN(){
    $("#addInventoryGRNSuccessMessage,#addInventoryGRNErrorMessage").html('').hide();
    $("#add_inventory_grn_comments").val('');
    $("#add_inventory_grn_dialog").modal('show');
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=get_grn_preview_data",
        method:"GET",
        data:{po_id:po_id,po_detail_id:po_detail_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryGRNErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var total_qty = 0, total_amt = 0;
                    var grn_sku = msg.grn_sku_list;
                    var str = '<div class="table-responsive"><h6 style="text-align:left;">SKU List:</h6><table class="table table-striped clearfix admin-table content-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo</th><th>SKU</th><th>Rate</th><th>Quantity</th><th>Amount</th></tr></thead>';
                    str+='<tbody>'
                    for(var i=0;i<grn_sku.length;i++){
                        var amount = (grn_sku[i].base_price*grn_sku[i].inv_count).toFixed(2);
                        str+='<tr><td>'+(i+1)+'</td><td>'+grn_sku[i].product_sku+'</td><td>'+grn_sku[i].base_price+'</td><td>'+grn_sku[i].inv_count+'</td><td>'+amount+'</td></tr>';
                        total_qty+=parseFloat(grn_sku[i].inv_count);
                        total_amt+=parseFloat(amount);
                    }
                    str+='</tbody>'
                    str+='<tr><th colspan="3">Total</th><th>'+total_qty+'</th><th>'+total_amt.toFixed(2)+'</th></tr>';
                    str+='</table></div>';
                    
                    $("#grn_preview_data").html(str);
                }
            }else{
                displayResponseError(msg,"addInventoryGRNErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addInventoryGRNErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitAddInventoryGRN(){
    $("#addInventoryGRNSuccessMessage,#addInventoryGRNErrorMessage").html('').hide();
    $("#add_inventory_grn_spinner").show();
    $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var add_inventory_grn_comments = $("#add_inventory_grn_comments").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=add_inventory_grn",
        method:"POST",
        data:{po_id:po_id,add_inventory_grn_comments:add_inventory_grn_comments,po_detail_id:po_detail_id},
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

function returnInventoryToVendor(id){
    $("#qc_return_id").val(id);
    $("#returnInventoryToVendorDialog").modal('show');
}

function submitReturnInventoryToVendor(){
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
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=confirm_return_inventory",
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

var inventory_products_inv_return_demand = [],  rec_per_page_inv_return_demand = '', page_global_inv_return_demand = 1,inv_return_barcode = '',inv_return_time = '',total_inventory_inv_return_demand = '',rec_inventory_inv_return_demand = 0;

function getInventoryReturnhDemandProductData(barcode){
    var demand_id = $("#demand_id").val();
    
    if(barcode == ''){
        $("#returnPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    //if(barcode.length != 16 && barcode.length != 19){
    if(barcode.length < 6){
        $("#returnPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_return_barcode != '' && inv_return_barcode == barcode && Math.abs((inv_return_time) - time)/1000 < 1){
        return false;
    }
    
    inv_return_barcode = barcode;
    inv_return_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return/load/"+demand_id+"?action=get_inventory_product",
        method:"GET",
        data:{barcode:barcode,demand_id:demand_id},
        success:function(msg){
            $("#returnPosInventoryErrorMessage,#returnPosInventorySuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#inv_return_piece_barcode").val('').focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#returnPosInventoryErrorMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#returnPosInventoryErrorMessage").html(errors).show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_inv_return_demand = msg.product_data;
                    $("#product_name").val(product_data_inv_return_demand.product_name);
                    $("#piece_size").val(product_data_inv_return_demand.size_name);
                    $("#piece_color").val(product_data_inv_return_demand.color_name);
                    $("#piece_vendor").val('');
                    $("#piece_po_number").val('');
                    $("#product_sku").val(product_data_inv_return_demand.vendor_sku);
                    $("#piece_cost").val(product_data_inv_return_demand.base_price);
                    $("#intake_date").val(product_data_inv_return_demand.intake_date);
                    $("#piece_id").val(product_data_inv_return_demand.id);
                    $("#product_id").val(product_data_inv_return_demand.product_master_id);
                    $("#returnPosInventoryErrorMessage").html('').hide();
                    
                    product_data_inv_return_demand.product_status = 1;
                    //product_data_inv_return_demand.store_intake_date = getCurrentDate(2);
                    inventory_products_inv_return_demand.push(product_data_inv_return_demand);
                    rec_inventory_inv_return_demand+=1;
                    displayInventoryReturnDemandInventoryData();
                    
                    setTimeout(function(){ 
                        var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#returnPosInventorySuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#returnPosInventorySuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#inv_return_piece_barcode").val('').focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"returnPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#returnPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

$("#inv_return_piece_barcode").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#inv_return_piece_barcode").val();
        getInventoryReturnhDemandProductData(val);
    }, 500);
});

function displayInventoryReturnDemandInventoryData(){
    var str = '';
    if(inventory_products_inv_return_demand.length > 0){
        str+='<div class="table-responsive">Total Inventory: '+total_inventory_inv_return_demand+' &nbsp;|&nbsp; Loaded Inventory: '+rec_inventory_inv_return_demand+'<table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>Size</th><th>Color</th><th>SKU</th><th>Base Price</th><th>Sale Price</th><!--<th>Store Out Date</th>--><th>Status</th></tr></thead><tbody>'

        var start = parseInt((page_global_inv_return_demand-1)*rec_per_page_inv_return_demand)+1;
        for(var i=0;i<inventory_products_inv_return_demand.length;i++){
            var product_data_inv_return_demand = inventory_products_inv_return_demand[i];
            
            var store_out_date = formatDate(product_data_inv_return_demand.store_out_date);
            var sku = (product_data_inv_return_demand.arnon_inventory == 0)?product_data_inv_return_demand.vendor_sku:product_data_inv_return_demand.product_sku;
            
            str+='<tr><td>'+(start+i)+'</td><td>'+product_data_inv_return_demand.peice_barcode+'</td><td>'+product_data_inv_return_demand.product_name+'</td><td>'+product_data_inv_return_demand.size_name+'</td><td>'+product_data_inv_return_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_return_demand.base_price+'</td><td>'+product_data_inv_return_demand.sale_price+'</td><!--<td>'+store_out_date+'</td>--><td>'+getProductInventoryStatus(product_data_inv_return_demand.product_status)+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    $("#products_imported_list").html(str);
}

function loadInventoryReturnDemandInventory(page){
    var demand_id = $("#demand_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return/load/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#returnPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;//alert(msg);
                    var paging_links = msg.paging_links;
                    paging_links+=getAjaxPagingLinks(msg.product_list);
                    inventory_products_inv_return_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_return_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_return_demand = msg.rec_per_page;
                    page_global_inv_return_demand = page;
                    total_inventory_inv_return_demand = msg.total_inventory;
                    rec_inventory_inv_return_demand = msg.received_inventory;
                    displayInventoryReturnDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadInventoryReturnDemandInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadInventoryReturnDemandInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"returnPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#returnPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function closeInventoryReturnDemand(){
    $("#closeInventoryReturnDemandSuccessMessage,#closeInventoryReturnDemandErrorMessage").html('').hide();
    $("#courier_detail,#vehicle_detail").val('');
    
    var store_id = $("#store_id").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return/load/"+demand_id+"?action=get_inventory_count_data",
        method:"get",
        data:{store_id:store_id,demand_id:demand_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{
                    //$("#close_demand_inv_rec").val(msg.inv_data.inv_total);
                    $("#close_demand_inv_loaded").val(msg.inv_data.rec_count);
                    $("#close_demand_base_price").val(msg.inv_data.rec_store_base_price_sum);
                    $("#close_demand_sale_price").val(msg.inv_data.rec_sale_price_sum);
                    $("#closeInventoryReturnDemandDialog").modal('show');
                }
            }else{
                displayResponseError(msg,"returnPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#returnPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitCloseInventoryReturnDemand(){
    var demand_id = $("#demand_id").val();
    var comments_close_demand = $("#comments_close_demand").val();
    $("#closeInventoryReturnDemandSubmit,#closeInventoryReturnDemandCancel").attr('disabled',true);
    $("#closeInventoryReturnDemandSpinner").show();
    $("#closeInventoryReturnDemandErrorMessage,#closeInventoryReturnDemandSuccessMessage").html('').hide();
    
    var store_id = $("#store_id").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return/load/"+demand_id+"?action=close_demand",
        method:"POST",
        data:{comments_close_demand:comments_close_demand,demand_id:demand_id},
        success:function(msg){
            $("#closeInventoryReturnDemandSubmit,#closeInventoryReturnDemandCancel").attr('disabled',false);
            $("#closeInventoryReturnDemandSpinner").hide();
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#closeInventoryReturnDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#closeInventoryReturnDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-return/detail/"+demand_id;
                    setTimeout(function(){  $("#closeInventoryPushDemandDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"closeInventoryReturnDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryReturnDemandErrorMessage").html('Error in processing request').show();
            $("#closeInventoryReturnDemandSubmit,#closeInventoryReturnDemandCancel").attr('disabled',false);
            $("#closeInventoryReturnDemandSpinner").hide();
        }
    });
}

function createCompleteInvReturnTaxInvoice(id){
    $("#comments_tax_invoice").val('');
    $("#create_return_inventory_tax_invoice_dialog").modal('show');
}

function submitCreateCompleteInvReturnTaxInvoice(){
    $("#create_return_inventory_tax_invoice_spinner").show();
    $("#create_return_inventory_tax_invoice_cancel,#create_return_inventory_tax_invoice_submit").attr('disabled',true);
    $("#createReturnInventoryTaxInvoiceErrorMessage,#createReturnInventoryTaxInvoiceSuccessMessage").html('').hide();
    
    var comments_tax_invoice = $("#comments_tax_invoice").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-return-complete/detail/"+demand_id+"?action=create_tax_invoice",
        method:"POST",
        data:{comments_tax_invoice:comments_tax_invoice,demand_id:demand_id},
        success:function(msg){
            $("#create_return_inventory_tax_invoice_spinner").hide();
            $("#create_return_inventory_tax_invoice_cancel,#create_return_inventory_tax_invoice_submit").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#createReturnInventoryTaxInvoiceErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#createReturnInventoryTaxInvoiceSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#create_return_inventory_tax_invoice_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"createReturnInventoryTaxInvoiceErrorMessage");
            }
        },error:function(obj,status,error){
            $("#create_return_inventory_tax_invoice_spinner").hide();
            $("#create_return_inventory_tax_invoice_cancel,#create_return_inventory_tax_invoice_submit").attr('disabled',false);
            $("#createReturnInventoryTaxInvoiceErrorMessage").html('Error in processing request').show();
        }
    });
}


function createInventoryAssignDemand(){
   $("#add_inventory_assign_demand_dialog").modal('show');
}

function submitCreateInventoryAssignDemand(){
    $("#add_inventory_assign_demand_spinner").show();
    $("#inventory_assign_demand_add_cancel,#inventory_assign_demand_add_submit").attr('disabled',true);
    $("#addInventoryAssignDemandSuccessMessage,#addInventoryAssignDemandErrorMessage").html('').hide();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-assign/list?action=create_demand",
        method:"POST",
        success:function(msg){
            $("#add_inventory_assign_demand_spinner").hide();
            $("#inventory_assign_demand_add_cancel,#inventory_assign_demand_add_submit").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addInventoryAssignDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryAssignDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_inventory_assign_demand_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addInventoryAssignDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_inventory_assign_demand_spinner").hide();
            $("#inventory_assign_demand_add_cancel,#inventory_assign_demand_add_submit").attr('disabled',false);
            $("#addInventoryAssignDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateInventoryAssignDemandQty(){
    if($("#product_sku").val() == ''){
        $("#product_sku_error_div").html('Please select Product SKU');
        $("#product_sku_error_dialog").modal('show');
        return false;
    }
    $("#update_inventory_assign_demand_spinner").css('display','inline-block');
    $("#updateQtyBtn").attr('disabled',true);
    $("#updateDemandQtySuccessMessage,#updateDemandQtyErrorMessage").html('').hide();
    var demand_id = $("#demand_id").val();
    var product_sku = $("#product_sku").val();
    var form_data = $("#updateInventoryAssignDemandFrm").serialize()+"&demand_id="+demand_id+"&product_sku="+product_sku;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-assign/edit/"+demand_id+"?action=update_qty",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#update_inventory_assign_demand_spinner").hide();
            $("#updateQtyBtn").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateDemandQtyErrorMessage").html(errors).show();
                        document.getElementById("updateDemandQtyErrorMessage").scrollIntoView();
                    } 
                }else{ 
                    $("#product_sku_success_div").html(msg.message).show();
                    $("#product_sku_success_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"updateDemandQtyErrorMessage");
                document.getElementById("updateDemandQtyErrorMessage").scrollIntoView();
            }
        },error:function(obj,status,error){
            $("#update_inventory_assign_demand_spinner").hide();
            $("#updateQtyBtn").attr('disabled',false);
            $("#updateDemandQtyErrorMessage").html('Error in processing request').show();
        }
    });
}

function getAssignDemandProductSKUSizeList(product_sku,type){
    $("#updateDemandQtySuccessMessage,#updateDemandQtyErrorMessage").html('').hide();
    $(".qty-text").val('').attr('readonly',true);;
    if(product_sku == ''){
        return false;
    }
    $("#updateQtyBtn").attr('disabled',true);
    var demand_id = $("#demand_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-assign/edit/"+demand_id+"?action=get_sku_size_list",
        method:"POST",
        data:{product_sku:product_sku,demand_id:demand_id},
        success:function(msg){
            $("#updateQtyBtn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateDemandQtyErrorMessage").html(errors).show();
                        document.getElementById("updateDemandQtyErrorMessage").scrollIntoView();
                    } 
                }else{ 
                    var product_list = msg.product_list;
                    var size_total = new Object;
                    
                    //Update readyonly and show/hide
                    $(".qty-text").attr('readonly',true).hide();
                    $(".size_th").css('color','#EB5D70');
                    
                    for(var i=0;i<product_list.length;i++){
                        if(type == 'edit'){
                            $(".size_"+product_list[i].size_id).attr('readonly',false).show();
                        }else{
                            $(".size_"+product_list[i].size_id).attr('readonly',true).show();
                        }
                        $("#qty_avl_"+product_list[i].size_id).show();
                        $("#th_size_"+product_list[i].size_id).css('color','#fff');
                        $("#qty_total_"+product_list[i].size_id).attr('readonly',true).show();
                    }
                    
                    //Display demand quantity and create total array
                    var demand_detail = msg.demand_detail;
                    for(var i=0;i<demand_detail.length;i++){
                        var qty = (demand_detail[i].product_quantity == 0 || demand_detail[i].product_quantity == null)?'':demand_detail[i].product_quantity;
                        $("#qty_"+demand_detail[i].store_id+'_'+demand_detail[i].size_id).val(qty);
                        
                        if(objectPropertyExists(size_total,demand_detail[i].size_id)){
                            size_total[demand_detail[i].size_id]+=parseInt(demand_detail[i].product_quantity);
                        }else{
                            size_total[demand_detail[i].size_id] = demand_detail[i].product_quantity;
                        }
                    }
                    
                    // Update total data 
                    for(var i=0;i<product_list.length;i++){
                        var size_id = product_list[i].size_id;
                        if(objectPropertyExists(size_total,size_id)){
                            $("#qty_total_"+size_id).val(size_total[size_id]);
                        }
                    }
                   
                    // update available quantity data
                    var product_qty = msg.product_qty;
                    $(".qty-avl").val(0);
                    for(var i=0;i<product_qty.length;i++){
                        var qty = (product_qty[i].qty == '' || product_qty[i].qty == null)?0:product_qty[i].qty;
                        $("#qty_avl_"+product_qty[i].size_id).val(qty);
                    }
                    
                    
                }
            }else{
                displayResponseError(msg,"updateDemandQtyErrorMessage");
                document.getElementById("updateDemandQtyErrorMessage").scrollIntoView();
            }
        },error:function(obj,status,error){
            $("#updateQtyBtn").attr('disabled',false);
            $("#updateDemandQtyErrorMessage").html('Error in processing request').show();
        }
    });
}

function checkAssignAvailableQty(store_id,size_id){
    if($("#qty_"+store_id+"_"+size_id).val() != '' && isNaN($("#qty_"+store_id+"_"+size_id).val())){
        $("#qty_"+store_id+"_"+size_id).val('');
        $("#product_sku_error_div").html('Assigned quantity should have numeric value');
        $("#product_sku_error_dialog").modal('show');
    }
    
    var qty_added = 0;
    $(".size_"+size_id).each(function(){
        if($(this).val() != ''){
            qty_added+=parseInt($(this).val());
        }
    });
    
    var available_qty = $("#qty_avl_"+size_id).val();
    if(qty_added > available_qty){
        $("#qty_"+store_id+"_"+size_id).val('');
        $("#product_sku_error_div").html('Assigned quantity should not be more than available quantity');
        $("#product_sku_error_dialog").modal('show');
        return false;
    }
    
    $("#qty_total_"+size_id).val(qty_added);
}

function updateAssignDemand(status){
    $("#updateAssignDemandDiv").html('Are you sure to '+status.toLocaleUpperCase()+" demand")
    $("#demand_update_action").val(status);
    $("#update_assign_demand_dialog").modal('show');
}

function submitUpdateAssignDemand(){
    var demand_id = $("#demand_id").val();
    var status = $("#demand_update_action").val()+"d";
    $("#updateAssignDemandCancel,#updateAssignDemandSubmit").attr('disabled',true);
    $("#update_demand-spinner").show();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-assign/detail/"+demand_id+"?action=update_demand",
        method:"POST",
        data:{demand_id:demand_id,status:status},
        success:function(msg){
            $("#update_demand-spinner").hide();
            $("#updateAssignDemandCancel,#updateAssignDemandSubmit").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateAssignDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateAssignDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#update_assign_demand_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateAssignDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#update_demand-spinner").show();
            $("#update_inventory_assign_demand_spinner").hide();
            $("#updateAssignDemandCancel,#updateAssignDemandSubmit").attr('disabled',false);
            $("#updateAssignDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function createInventoryReturnVendorDemand(){
    $("#po_id,#po_invoice_id").val('');
    $(".invalid-feedback").html('');
    $("#add_inventory_return_vendor_demand_dialog").modal('show');
}

function getPoInvoiceList(po_id){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/list?action=get_po_invoice_list",
        method:"GET",
        data:{po_id:po_id},
        success:function(msg){
           if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#createInventoryReturnVendorDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var option_str = '<option value="">Select One</option>';
                    var invoice_list = msg.invoice_list;
                    for(var i=0;i<invoice_list.length;i++){
                        option_str+='<option value="'+invoice_list[i].id+'">'+invoice_list[i].invoice_no+" - (Date: "+displayDate(invoice_list[i].invoice_date)+") - (Qty: "+invoice_list[i].products_count+') - (ID: '+invoice_list[i].id+')</option>';
                    }
                    
                    $("#po_invoice_id").html(option_str);
                }
            }else{
                displayResponseError(msg,"createInventoryReturnVendorDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#createInventoryReturnVendorDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitCreateInventoryReturnVendorDemand(){
    /*var po_id = $("#po_id").val();
    var po_invoice_id = $("#po_invoice_id").val();*/
    var vendor_id = $("#vendor_id").val();
    
    $("#inventory_return_vendor_add_cancel,#inventory_return_vendor_add_submit").attr('disabled',true);
    $("#add_inventory_return_vendor_demand_spinner").show();
    $("#createInventoryReturnVendorDemandErrorMessage,createInventoryReturnVendorDemandSuccessMessage").html('').hide();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/list?action=create_demand",
        method:"POST",
        data:{vendor_id:vendor_id},
        success:function(msg){
            $("#inventory_return_vendor_add_cancel,#inventory_return_vendor_add_submit").attr('disabled',false);
            $("#add_inventory_return_vendor_demand_spinner").hide();
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#createInventoryReturnVendorDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#createInventoryReturnVendorDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_inventory_return_vendor_demand_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"createInventoryReturnVendorDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#createInventoryReturnVendorDemandErrorMessage").html('Error in processing request').show();
            $("#inventory_return_vendor_add_cancel,#inventory_return_vendor_add_submit").attr('disabled',false);
            $("#add_inventory_return_vendor_demand_spinner").hide();
        }
    });
}


var inventory_products_inv_return_vendor_demand = [],  rec_per_page_inv_return_vendor_demand = '', page_global_inv_return_vendor_demand = 1,inv_return_vendor_barcode = '',inv_return_vendor_time = '';

function getInventoryReturnVendorDemandProductData(barcode){
    $("#inv_return_vendor_piece_barcode").attr('disabled',true).attr('readonly',true);
    var demand_id = $("#demand_id").val();
    
    if(barcode == ''){
        $("#inventoryReturnVendorErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    
    if(barcode.length < 6){
        $("#inventoryReturnVendorErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_return_vendor_barcode != '' && inv_return_vendor_barcode == barcode && Math.abs((inv_return_vendor_time) - time)/1000 < 1){
        return false;
    }
    
    inv_return_vendor_barcode = barcode;
    inv_return_vendor_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/edit/"+demand_id+"?action=get_inventory_product",
        method:"GET",
        data:{barcode:barcode,demand_id:demand_id},
        success:function(msg){
            $("#inventoryReturnVendorErrorMessage,#inventoryReturnVendorSuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#inv_return_vendor_piece_barcode").val('').attr('disabled',false).attr('readonly',false).focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#inventoryReturnVendorErrorMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#inventoryReturnVendorErrorMessage").html(errors).show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_inv_return_vendor_demand = msg.product_data;
                    $("#product_name").val(product_data_inv_return_vendor_demand.product_name);
                    $("#piece_size").val(product_data_inv_return_vendor_demand.size_name);
                    $("#piece_color").val(product_data_inv_return_vendor_demand.color_name);
                    $("#piece_vendor").val('');
                    $("#piece_po_number").val('');
                    $("#product_sku").val(product_data_inv_return_vendor_demand.vendor_sku);
                    $("#piece_cost").val(product_data_inv_return_vendor_demand.base_price);
                    $("#intake_date").val(product_data_inv_return_vendor_demand.intake_date);
                    $("#piece_id").val(product_data_inv_return_vendor_demand.id);
                    $("#product_id").val(product_data_inv_return_vendor_demand.product_master_id);
                    $("#inventoryReturnVendorErrorMessage").html('').hide();
                    
                    product_data_inv_return_vendor_demand.product_status = 1;
                    inventory_products_inv_return_vendor_demand.push(product_data_inv_return_vendor_demand);
                    displayInventoryReturnVendorDemandInventoryData();
                    
                    setTimeout(function(){ 
                        var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#inventoryReturnVendorSuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#inventoryReturnVendorSuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#inv_return_vendor_piece_barcode").val('').attr('disabled',false).attr('readonly',false).focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"inventoryReturnVendorErrorMessage");
                $("#inv_return_vendor_piece_barcode").attr('disabled',false).attr('readonly',false);
            }
            
        },error:function(obj,status,error){
            $("#inventoryReturnVendorErrorMessage").html('Error in processing request').show();
            $("#inv_return_vendor_piece_barcode").attr('disabled',false).attr('readonly',false);
        }
    });
}

$("#inv_return_vendor_piece_barcode").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#inv_return_vendor_piece_barcode").val();
        getInventoryReturnVendorDemandProductData(val);
    }, 500);
});

function displayInventoryReturnVendorDemandInventoryData(){
    var str = '';
    if(inventory_products_inv_return_vendor_demand.length > 0){
        str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>Size</th><th>Color</th><th>SKU</th><th>Base Price</th><th>Sale Price</th><!--<th>Store Out Date</th>--><th>Status</th></tr></thead><tbody>'

        var start = parseInt((page_global_inv_return_vendor_demand-1)*rec_per_page_inv_return_vendor_demand)+1;
        for(var i=0;i<inventory_products_inv_return_vendor_demand.length;i++){
            var product_data_inv_return_vendor_demand = inventory_products_inv_return_vendor_demand[i];
            var store_out_date = formatDate(product_data_inv_return_vendor_demand.store_out_date);
            var id = product_data_inv_return_vendor_demand.id;
            var sku = (product_data_inv_return_vendor_demand.vendor_sku != null)?product_data_inv_return_vendor_demand.vendor_sku:product_data_inv_return_vendor_demand.product_sku;
            
            str+='<tr>';
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="inv-return-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            
            str+='<td>'+product_data_inv_return_vendor_demand.peice_barcode+'</td><td>'+product_data_inv_return_vendor_demand.product_name+'</td><td>'+product_data_inv_return_vendor_demand.size_name+'</td><td>'+product_data_inv_return_vendor_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_return_vendor_demand.base_price+'</td><td>'+product_data_inv_return_vendor_demand.sale_price+'</td><!--<td>'+store_out_date+'</td>--><td>'+getProductInventoryStatus(product_data_inv_return_vendor_demand.product_status)+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    $("#products_imported_list").html(str);
}

function loadInventoryReturnVendorDemandInventory(page){
    var demand_id = $("#demand_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/edit/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#returnPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;//alert(msg);
                    var paging_links = msg.paging_links;
                    inventory_products_inv_return_vendor_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_return_vendor_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_return_vendor_demand = msg.rec_per_page;
                    page_global_inv_return_vendor_demand = page;
                    displayInventoryReturnVendorDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadInventoryReturnVendorDemandInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadInventoryReturnVendorDemandInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"inventoryReturnVendorErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryReturnVendorErrorMessage").html('Error in processing request').show();
        }
    });
}

function closeInventoryReturnVendorDemand(){
    $("#closeInventoryReturnDemandSuccessMessage,#closeInventoryReturnDemandErrorMessage").html('').hide();
    $("#courier_detail,#vehicle_detail").val('');
    
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/edit/"+demand_id+"?action=get_inventory_count_data",
        method:"get",
        data:{demand_id:demand_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#closeInventoryReturnDemandVendorErrorMessage").html(errors).show();
                    } 
                }else{
                    /*$("#close_demand_inv_loaded").val(msg.inv_data.rec_count);
                    $("#close_demand_base_price").val(msg.inv_data.rec_base_price_sum);
                    $("#close_demand_sale_price").val(msg.inv_data.rec_sale_price_sum);
                    $("#closeInventoryReturnVendorDemandDialog").modal('show');*/
                    
                    var demand_data = msg.demand_data;
                    var vendor_data = msg.vendor_data;
                    var total_data = msg.total_data;
                    $("#demand_prev_type").val('Warehouse to Vendor');
                    $("#demand_prev_date_created").val(displayDate(demand_data.created_at));
                    $("#demand_prev_vendor_name").val(vendor_data.name);
                    $("#demand_prev_vendor_gst_no").val(vendor_data.gst_no);
                    $("#demand_prev_total_inv").val(total_data.total_qty);
                    $("#demand_prev_taxable_value").val(total_data.total_taxable_val.toFixed(2));
                    $("#demand_prev_gst_amount").val(total_data.total_gst_amt.toFixed(2));
                    $("#demand_prev_total_amt").val(total_data.total_value.toFixed(2));
                    $("#demand_prev_total_sale_price").val(total_data.total_sale_price.toFixed(2));
                    $("#closeInventoryReturnVendorDemandDialog").modal('show');
                }
            }else{
                displayResponseError(msg,"closeInventoryReturnDemandVendorErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryReturnDemandVendorErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitCloseInventoryReturnVendorDemand(){
    var demand_id = $("#demand_id").val();
    var comments_close_demand = $("#comments_close_demand").val();
    $("#closeInventoryReturnDemandVendorSubmit,#closeInventoryReturnDemandVendorCancel").attr('disabled',true);
    $("#closeInventoryReturnDemandVendorSpinner").show();
    $("#closeInventoryReturnDemandVendorErrorMessage,#closeInventoryReturnDemandVendorSuccessMessage").html('').hide();
    
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/edit/"+demand_id+"?action=close_demand",
        method:"POST",
        data:{comments_close_demand:comments_close_demand,demand_id:demand_id},
        success:function(msg){
            $("#closeInventoryReturnDemandVendorSubmit,#closeInventoryReturnDemandVendorCancel").attr('disabled',false);
            $("#closeInventoryReturnDemandVendorSpinner").hide();
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#closeInventoryReturnDemandVendorErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#closeInventoryReturnDemandVendorSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/warehouse/demand/inventory-return-vendor/detail/"+demand_id;
                    setTimeout(function(){  $("#closeInventoryReturnVendorDemandDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"closeInventoryReturnDemandVendorErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryReturnDemandVendorErrorMessage").html('Error in processing request').show();
            $("#closeInventoryReturnDemandVendorSubmit,#closeInventoryReturnDemandVendorCancel").attr('disabled',false);
            $("#closeInventoryReturnDemandVendorSpinner").hide();
        }
    });
}

function editReturnVendorDemandGatePassData(){
    $("#editGatePassDialog").modal('show');
}

function submitEditReturnVendorDemandGatePassData(){
    $("#editGatePassCancel,#editGatePassSubmit").attr('disabled',true);
    var demand_id = $("#demand_id").val();
    var boxes_count = $("#boxes_count").val();
    var transporter_name = $("#transporter_name").val();
    var transporter_gst = $("#transporter_gst").val();
    var docket_no = $("#docket_no").val();
    var eway_bill_no = $("#eway_bill_no").val();
   
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/detail/"+demand_id+"?action=update_gate_pass_data",
        method:"POST",
        data:{demand_id:demand_id,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no},
        success:function(msg){
            $("#editGatePassCancel,#editGatePassSubmit").attr('disabled',false);
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
            $("#editGatePassCancel,#editGatePassSubmit").attr('disabled',false);
            $("#editGatePassErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteInventoryReturnVendorDemandItems(){
    var demand_id = $("#demand_id").val();
    
    var chk_class = 'inv-return-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#inventory_return_demand_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#deleteInventoryDemandItemsErrorMessage,#deleteInventoryDemandItemsSuccessMessage").html('').hide();
    
    $('#inventory_return_demand_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_inventory_return_demand_items_btn', function(e) {
        e.preventDefault();
        $("#delete_inventory_return_demand_items_btn,#delete_inventory_return_demand_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,demand_id:demand_id},
            url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/edit/"+demand_id+"?action=delete_inv_return_items",
            success: function(msg){	
                $("#delete_inventory_return_demand_items_btn,#delete_inventory_return_demand_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteInventoryDemandItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteInventoryDemandItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#inventory_return_demand_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteInventoryDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteInventoryDemandItemsErrorMessage").html('Error in processing request').show();
                $("#delete_inventory_return_demand_items_btn,#delete_inventory_return_demand_items_cancel").attr('disabled',false);
            }
        });
    });
}

function cancelInventoryReturnVendorDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_cancel_dialog").modal('show');
}

function submitCancelInventoryReturnVendorDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',true);
    var comments = $("#cancel_comments").val();
    var demand_id = $("#demand_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/detail/"+demand_id+"?action=cancel_demand",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#push_demand_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            $("#cancelPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

var inv_track_barcode = '',inv_track_time = '';

function trackInventoryProduct(){
    $(".import-data").val('');
    $("#trackInventorySuccessMessage,#trackInventoryErrorMessage,#track_product_data").html('').hide();
    
    var barcode = $("#inv_track_piece_barcode").val();
    if(barcode == ''){
        $("#trackInventoryErrorMessage").html("Barcode is Required Field").show();
        return false;
    }
    
    //if(barcode.length != 16 && barcode.length != 19){
    if(barcode.length < 6){
        $("#trackInventoryErrorMessage").html("Invalid Barcode").show();
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_track_barcode != '' && inv_track_barcode == barcode && Math.abs((inv_track_time) - time)/1000 < 1){
        return false;
    }
    
    inv_track_barcode = barcode;
    inv_track_time = time;
    
    $("#inventory_track_submit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/inventory/track?action=check_product",
        method:"POST",
        data:{barcode:barcode},
        success:function(msg){
            $("#inventory_track_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#trackInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_data = msg.product_data,product_stages = msg.product_stages,stages_str = '',ref_data = '',ref_str = '';
                    window.location.href = ROOT_PATH+'/pos/product/inventory/detail/'+product_data.id;
                    return;
                    
                    $("#piece_barcode").val(product_data.peice_barcode);
                    $("#piece_product_name").val(product_data.product_name);
                    $("#piece_size").val(product_data.size_name);
                    $("#piece_color").val(product_data.color_name);
                    $("#product_sku").val(product_data.product_sku);
                    $("#piece_cost").val(product_data.store_base_price);
                    $("#piece_store").val(product_data.store_name);
                    $("#piece_status").val(product_data.product_status_text);
                    $("#piece_reason").html(product_data.reason_str).show();
                    $("#inv_review_piece_barcode").val('');
                    $("#product_data_row").css('display','flex');
                    
                    stages_str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Date</th><th>Description</th><th>Ref No</th><th>Status</th></tr></thead><tbody>';
                    for(var i=0;i<product_stages.length;i++){
                        ref_data = product_stages[i].ref_data;
                        ref_str = '';
                        for(var q=0;q<ref_data.length;q++){
                            ref_str+=ref_data[q].name+": "+ref_data[q].ref_no+" &nbsp;&nbsp;";
                        }
                        stages_str+='<tr><td>'+(i+1)+'</td><td>'+displayDate(product_stages[i].date)+'</td><td>'+product_stages[i].desc+'</td><td>'+ref_str+'</td><td>'+getProductInventoryStatus(product_stages[i].product_status)+'</td></tr>';
                    }
                    
                    stages_str+='</tbody></table></div>';
                    
                    $("#track_product_data").html(stages_str).show();
                }
            }else{
                displayResponseError(msg,"trackInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#trackInventoryErrorMessage").html('Error in processing request').show();
            $("#inventory_track_submit").attr('disabled',false);
        }
    });
}

function inventoryTransferInInvoices(sku){
    var grn_id = $("#grn_id").val();
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    $("#transfer_sku").val(sku);
    $("#inventoryErrorMessage").html('').hide();
    $("#inventoryTransferInvoiceErrorMessage,#inventoryTransferInvoiceSuccessMessage").html('').hide();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=get_transfer_inventory_data",
        method:"GET",
        data:{grn_id:grn_id,sku:sku,po_id:po_id,po_detail_id:po_detail_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#inventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var size_data = msg.size_data;
                    var str_size_name = '', str_inv_count = '', str_txt = '',id_str = '';
                    var str = '<div class="table-responsive table-filter" style="font-size:12px; ">\
                    <table class="table table-striped admin-table" cellspacing="0">';
                    for(var i=0;i<size_data.length;i++){
                        str_size_name+='<td>'+size_data[i].size_name+'</td>';
                        str_inv_count+='<td>'+size_data[i].inv_count+'</td>';
                        str_txt+='<td><input type="text" name="transfer_'+size_data[i].size_id+'" id="transfer_'+size_data[i].size_id+'" class="form-control"></td>';
                        id_str+=size_data[i].size_id+",";
                    }
                    
                    str+='<thead><tr class="header-tr">'+str_size_name+'</tr></thead><tbody><tr>'+str_inv_count+'</tr><tr>'+str_txt+'</tr></tbody>';
                    str+='</table>';
                    
                    $("#size_list").html(str);
                    id_str = id_str.substring(0,id_str.length-1);   
                    $("#transfer_id_str").val(id_str);
                    $("#inventory_transfer_invoice_dialog .form-control").val('');
                    $("#inventory_transfer_invoice_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"inventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitInventoryTransferInInvoices(){
    $("#inventoryTransferInvoiceErrorMessage,#inventoryTransferInvoiceSuccessMessage").html('').hide();
    $("#inventory_transfer_invoice_submit,#inventory_transfer_invoice_cancel").attr('disabled',true);
    
    var grn_id = $("#grn_id").val();
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    var form_data = $("#inventoryTransferInvoiceForm").serialize();
    form_data+='&grn_id='+grn_id+"&po_id="+po_id+"&po_detail_id="+po_detail_id;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=update_transfer_inventory_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#inventory_transfer_invoice_submit,#inventory_transfer_invoice_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#inventoryTransferInvoiceErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#inventoryTransferInvoiceSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#inventory_transfer_invoice_dialog").modal('hide');window.location.reload();  }, 1000);
                }
            }else{
                displayResponseError(msg,"inventoryTransferInvoiceErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventory_transfer_invoice_submit,#inventory_transfer_invoice_cancel").attr('disabled',false);
            $("#inventoryTransferInvoiceErrorMessage").html('Error in processing request').show();
        }
    });
}

var $inp = $('.qty-text');
$inp.bind('keydown', function(e) {
    var key = e.which;//alert(key);
    
    if (key == 37) {
        e.preventDefault();
        var nxtIdx = $inp.index(this) - 1;
        $(":input:text:eq(" + nxtIdx + ")").focus();
    }
    
    if (key == 39) {
        e.preventDefault();
        var nxtIdx = $inp.index(this) + 1;
        $(":input:text:eq(" + nxtIdx + ")").focus();
    }

    if(key == 38){
        e.preventDefault();
        var txt_boxes = parseInt($(this).parents('tr').find('.qty-text').length);
        var nxtIdx = $inp.index(this) - txt_boxes;
        $(":input:text:eq(" + nxtIdx + ")").focus();
    }
    
    if(key == 40){
        e.preventDefault();
        var txt_boxes = parseInt($(this).parents('tr').find('.qty-text').length);
        var nxtIdx = $inp.index(this) + txt_boxes;
        $(":input:text:eq(" + nxtIdx + ")").focus();
    }
});

function updateDebitNoteTotalValue(elem,qty,qty_rec){
    var val = $(elem).val(), total_row_val = 0, total_val = 0;
    
    var qty_available = parseInt(qty)-parseInt(qty_rec);
    if(val > qty_available){
        $("#inv_error_text").html("Maximum available quantity is "+qty_available);
        $("#inventory_error_dialog").modal('show');
        $(elem).val(qty_available);
    }
    
    if(isNaN(val)){
        $(elem).val('');
        //return false;
    }
    
    $(elem).parents("tr").find('.debit-note-val').each(function(){
        if($(this).val() != ''){
            total_row_val+=parseInt($(this).val());
        }
    });
    
    $(elem).parents("tr").find(".debit-note-val-row-total").val(total_row_val);
    
    $('.debit-note-val').each(function(){
        if($(this).val() != ''){
            total_val+=parseInt($(this).val());
        }
    });
    
    $(".debit-note-val-total").val(total_val);
}

function addPOInvoiceDebitNote(){
    $("#add_debit_note_submit,#add_debit_note_cancel").attr('disabled',true);
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    var form_data = $("#addDebitNoteform").serialize();
    form_data+="&po_id="+po_id+"&po_detail_id="+po_detail_id; 
   
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/debit-note/add/"+po_detail_id+"?action=add_debit_note",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#add_debit_note_submit,#add_debit_note_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addDebitNoteErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addDebitNoteErrorMessage").html('').hide();
                    $("#addDebitNoteSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id;
                    setTimeout(function(){  window.location.href = url;  }, 1000);
                }
            }else{
                displayResponseError(msg,"addDebitNoteErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_debit_note_submit,#add_debit_note_cancel").attr('disabled',false);
            $("#addDebitNoteErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditQCData(){
    $("#editQCDataErrorMessage,#editQCDataSuccessMessage").html('').hide();
    $("#qc_data_edit_submit_btn,#qc_data_edit_cancel_btn").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var qc_id = $("#qc_id").val();
    var qr_codes = $("#editQcQRCodes").val();
    var comments = $("#editQcComments").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=edit_inventory_qc",
        method:"POST",
        data:{po_id:po_id,po_detail_id:po_detail_id,qr_codes:qr_codes,qc_id:qc_id,comments:comments},
        success:function(msg){
            
            $("#qc_data_edit_submit_btn,#qc_data_edit_cancel_btn").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editQCDataErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editQCDataSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editQCDataErrorMessage");
            }
        },error:function(obj,status,error){
           
            $("#qc_data_edit_submit_btn,#qc_data_edit_cancel_btn").attr('disabled',false);
            $("#editQCDataErrorMessage").html('Error in processing request').show();
        }
    });
}

var excess_amt_debit_note_items = [];

/*function updateSorExcessDebitNoteInvoiceAmount(elem,id,qty,po_cost){
    var invoice_cost = $(elem).val();
    var invoice_total_amount = 0,debit_note_total_amount = 0;
    if(isNaN(invoice_cost)){
        $(elem).val('');
        //$("#invoice_amount_"+id).html('');
        //$("#debit_note_amount_"+id).html('');
        //return;
    }
    
    var invoice_amount = (invoice_cost*qty).toFixed(2);
    if(invoice_amount != '' && invoice_amount > 0){
        $("#invoice_amount_"+id).html(invoice_amount);
        $("#debit_note_amount_"+id).html((invoice_amount-po_cost).toFixed(2));
    }else{
        $("#invoice_amount_"+id).html('');
        $("#debit_note_amount_"+id).html('')
    }
    
    $(".invoice-amount").each(function(){
        if($(this).html() != ''){
            invoice_total_amount+=parseFloat($(this).html());
        }
    });
    
    $(".debit-note-amount").each(function(){
        if($(this).html() != ''){
            debit_note_total_amount+=parseFloat($(this).html());
        }
    });
    
    $("#invoice_total_amount").html(invoice_total_amount.toFixed(2));
    $("#debit_note_total_amount").html(debit_note_total_amount.toFixed(2));
}*/

function addPOInvoiceExcessAmountDebitNote(){
    $("#debitNoteErrorMessage,#debitNoteSuccessMessage").html('').hide();
    $("#add_excess_amount_debit_note_submit,#add_excess_amount_debit_note_cancel").attr('disabled',true);
    //var form_data = excess_amt_debit_note_items;
    var invoice_id = $("#invoice_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/debit-note/excess-amount/add/"+invoice_id+"?action=add_debit_note",
        method:"POST",
        data:{items:excess_amt_debit_note_items},
        success:function(msg){
            $("#add_excess_amount_debit_note_submit,#add_excess_amount_debit_note_cancel").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#debitNoteErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#debitNoteSuccessMessage").html(msg.message).show();
                    
                    var url = ROOT_PATH+"/warehouse/sor/inventory/import/"+invoice_id;
                    setTimeout(function(){  window.location.href = url;  }, 1000);
                }
            }else{
                displayResponseError(msg,"debitNoteErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_excess_amount_debit_note_submit,#add_excess_amount_debit_note_cancel").attr('disabled',false);
            $("#debitNoteErrorMessage").html('Error in processing request').show();
        }
    });
}

function addExcessAmountDebitNoteItem(id,qty){
    var item_invoice_rate = $("#invoice_rate_"+id).val();
    var item_invoice_gst = $("#invoice_gst_"+id).val();
    var item_invoice_cost = $("#invoice_cost_"+id).val();
    var invoice_id = $("#invoice_id").val();
    
    for(var i=0;i<excess_amt_debit_note_items.length;i++){
        if(excess_amt_debit_note_items[i].id == id){
            $("#debitNoteItemAddErrorMessage").html('Product is already added').show();
            return false;
        }
    }
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/debit-note/excess-amount/add/"+invoice_id+"?action=get_item_data",
        method:"POST",
        data:{id:id},
        success:function(msg){
            $("#item_add_btn").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#debitNoteItemAddErrorMessage").html(errors).show();
                    } 
                }else{ 
                    //$("#debitNoteItemAddSuccessMessage").html(msg.message).show();
                    var item_data = msg.item_data;
                    item_data.item_invoice_rate = item_invoice_rate;
                    item_data.item_invoice_gst = item_invoice_gst;
                    item_data.item_invoice_cost = item_invoice_cost;
                    item_data.qty = qty;
                    excess_amt_debit_note_items.push(item_data);
                    displayExcessAmountDebitNoteItems();
                }
            }else{
                displayResponseError(msg,"debitNoteItemAddErrorMessage");
            }
        },error:function(obj,status,error){
            $("#item_add_btn").attr('disabled',false);
            $("#debitNoteItemAddErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayExcessAmountDebitNoteItems(){
    var str = '',total_data = [];
    total_data.amount = 0;total_data.invoice_amount = 0;total_data.debit_note_amount = 0;
    
    str+='<div class="table-responsive"><h6>Debit Note Items:</h6><table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px;"><thead><tr class="header-tr">';
    str+='<th>SNo.</th><th>Style</th><th>Color</th><th>Qty</th><th>Rate</th><th>GST</th><th>Cost</th><th>Amount</th>';
    str+='<th>Invoice Rate</th><th>Invoice GST</th><th>Invoice Cost</th><th>Invoice Amount</th><th>Debit Note Amount</th></tr></thead><tbody>'
    
    if(excess_amt_debit_note_items.length > 0){    
        for(var i=0;i<excess_amt_debit_note_items.length;i++){
            var item = excess_amt_debit_note_items[i];
            var sku = (item.vendor_sku != null)?item.vendor_sku:item.product_sku;
            var item_rate = parseFloat(item.rate);
            var item_gst_percent = parseFloat(item.gst_percent);
            var gst_amount = (item_rate*(item_gst_percent/100)).toFixed(2);
            var cost = parseFloat(item_rate)+parseFloat(gst_amount);
            var invoice_amount = parseFloat(item.item_invoice_cost*item.qty);
            var debit_note_amt = (parseFloat(invoice_amount)-parseFloat(cost*item.qty)).toFixed(2);
            excess_amt_debit_note_items[i]['item_gst_amount'] = gst_amount;
            excess_amt_debit_note_items[i]['item_cost'] = cost;
                    
            str+='<tr>';
            str+='<td> &nbsp;'+(i+1)+'</td>';
            str+='<td>'+sku+'</td><td>'+item.color_name+'</td><td>'+item.qty+'</td><td>'+item_rate+'</td><td>'+gst_amount+'</td><td>'+cost+'</td>\
            <td>'+parseFloat(cost*item.qty)+'</td><td>'+item.item_invoice_rate+'</td><td>'+item.item_invoice_gst+'</td><td>'+item.item_invoice_cost+'</td><td>'+invoice_amount+'</td><td>'+debit_note_amt+'</td></tr>';
            
            total_data.amount+=parseFloat(cost*item.qty);
            total_data.invoice_amount+=(invoice_amount);
            total_data.debit_note_amount+=parseFloat(debit_note_amt);
        }
        
        str+='<tr><td colspan="7"></td><td>'+total_data.amount+'</td><td colspan="3"></td><td>'+total_data.invoice_amount+'</td><td>'+total_data.debit_note_amount.toFixed(2)+'</td></tr>';
        
        str+='<tr><td colspan="15" align="center"><div class="alert alert-danger alert-dismissible elem-hidden" id="debitNoteErrorMessage"></div>\
        <div class="alert alert-success alert-dismissible elem-hidden" id="debitNoteSuccessMessage"></div>\
        <button type="button" id="add_excess_amount_debit_note_submit" class="btn btn-dialog" onclick="addPOInvoiceExcessAmountDebitNote();">Submit</button> &nbsp;\
        <button type="button" data-dismiss="modal" class="btn btn-secondary" id="add_excess_amount_debit_note_cancel" onclick="window.location.href=">Cancel</button></td></tr>';
        
    }else{
        str+='<tr><td colspan="15" align="center">No Records</td></tr>';
    }    
    
    str+='</tbody></table></div>';
    
    
    $("#debit_note_items_list").html(str);
}

function updateSorLessDebitNoteInvoiceAmount(elem,id,qty,po_cost){
    var invoice_cost = $(elem).val();
    var invoice_total_amount = 0,debit_note_total_amount = 0;
    if(isNaN(invoice_cost)){
        $(elem).val('');
    }
    
    var invoice_amount = (invoice_cost*qty).toFixed(2);
    if(invoice_amount != '' && invoice_amount > 0){
        $("#invoice_amount_"+id).html(invoice_amount);
        $("#debit_note_amount_"+id).html((po_cost-invoice_amount).toFixed(2));
    }else{
        $("#invoice_amount_"+id).html('');
        $("#debit_note_amount_"+id).html('')
    }
    
    $(".invoice-amount").each(function(){
        if($(this).html() != ''){
            invoice_total_amount+=parseFloat($(this).html());
        }
    });
    
    $(".debit-note-amount").each(function(){
        if($(this).html() != ''){
            debit_note_total_amount+=parseFloat($(this).html());
        }
    });
    
    $("#invoice_total_amount").html(invoice_total_amount.toFixed(2));
    $("#debit_note_total_amount").html(debit_note_total_amount.toFixed(2));
    
}

function togglePushDemandTransferFields(store_id){
    if(store_id == 50){
        $(".transfer-field").slideDown('slow');
    }else{
        $(".transfer-field").slideUp('slow');
    }
}

function addFakePushDemandInventory(){
    var demand_id = $("#demand_id").val();
    var chk_class = 'pos_product-list-chk';
    var demandChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(demandChkArray.length == 0){
        $("#push_demand_delete_error_dialog").modal('show');
        return false;
    }
    
    $('#push_demand_add_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#add_push_demand_items_btn', function(e) {
        e.preventDefault();
        $("#add_push_demand_items_btn,#add_push_demand_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{demandChkArray:demandChkArray,demand_id:demand_id},
            url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=add_fake_push_demand_items",
            success: function(msg){	
                $("#add_push_demand_items_btn,#add_push_demand_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#addPushDemandItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#addPushDemandItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#push_demand_add_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"addPushDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#addPushDemandItemsErrorMessage").html('Error in processing request').show();
                $("#add_push_demand_items_btn,#add_push_demand_items_cancel").attr('disabled',false);
            }
        });
    });
}

function editInventoryPushDemand(){
    $("#editPushDemandForm .form-control").val('');
    $("#editPushDemandSuccessMessage,#editPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#edit_push_demand_dialog").modal('show');
    $("#product_qr_code_add").focus();
}

function submitEditInventoryPushDemand(){
    var demand_id = $("#demand_id").val();
    $("#editPushDemandSuccessMessage,#editPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#editPushDemandCancel,#editPushDemandSubmit").attr('disabled',true);
    $("#edit_push_demand_spinner").show();
    var form_data = '';
    $(".edit-demand-qr-code").each(function(){
        form_data+=$(this).attr('id')+"="+$(this).val()+"&";
    });
    
    form_data = form_data.substring(0,form_data.length-1);
    form_data+="&total_add="+$("#edit_demand_add_total").val();
    form_data+="&total_delete="+$("#edit_demand_delete_total").val();
    form_data+="&demand_id="+demand_id;
        
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id+"?action=edit_push_demand",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#editPushDemandCancel,#editPushDemandSubmit").attr('disabled',false);
            $("#edit_push_demand_spinner").hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editPushDemandErrorMessage").html('').hide();
                    $("#editPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#edit_push_demand_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPushDemandCancel,#editPushDemandSubmit").attr('disabled',false);
            $("#edit_push_demand_spinner").hide();
            $("#editPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function toggleEditPushDemand(action){
    if(action == 'add'){
        $("#edit_push_demand_qr_code_add_div").removeClass('elem-hidden');
        $("#edit_push_demand_qr_code_delete_div").addClass('elem-hidden');
        $("#add_push_demand_inv_btn").css('opacity','1');
        $("#delete_push_demand_inv_btn").css('opacity','0.6');
    }else{
        $("#edit_push_demand_qr_code_add_div").addClass('elem-hidden');
        $("#edit_push_demand_qr_code_delete_div").removeClass('elem-hidden');
        $("#add_push_demand_inv_btn").css('opacity','0.6');
        $("#delete_push_demand_inv_btn").css('opacity','1');
    }
}

function editInvoiceGRNInventory(){
    $("#editGRNForm .form-control").val('');
    $("#editGRNSuccessMessage,#editGRNErrorMessage,.invalid-feedback").html('').hide();
    $("#edit_grn_dialog").modal('show');
    $("#product_qr_code_add").focus();
}

function submitEditInvoiceGRNInventory(){
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    $("#editGRNSuccessMessage,#editGRNErrorMessage,.invalid-feedback").html('').hide();
    $("#editGRNCancel,#editGRNSubmit").attr('disabled',true);
    $("#edit_grn_spinner").show();
    
    var form_data = '';
    $(".edit-grn-qr-code").each(function(){
        form_data+=$(this).attr('id')+"="+$(this).val()+"&";
    });
    
    form_data = form_data.substring(0,form_data.length-1);
    form_data+="&total_add="+$("#edit_demand_add_total").val();
    form_data+="&total_delete="+$("#edit_demand_delete_total").val();
    form_data+="&po_detail_id="+po_detail_id+"&po_id="+po_id;
        
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=edit_grn_inventory",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#editGRNCancel,#editGRNSubmit").attr('disabled',false);
            $("#edit_grn_spinner").hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editGRNErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editGRNErrorMessage").html('').hide();
                    $("#editGRNSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#edit_grn_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editGRNErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editGRNCancel,#editGRNSubmit").attr('disabled',false);
            $("#edit_grn_spinner").hide();
            $("#editGRNErrorMessage").html('Error in processing request').show();
        }
    });
}

function toggleEditGRN(action){
    if(action == 'add'){
        $("#edit_grn_qr_code_add_div").removeClass('elem-hidden');
        $("#edit_grn_qr_code_delete_div").addClass('elem-hidden');
        $("#add_grn_inv_btn").css('opacity','1');
        $("#delete_grn_inv_btn").css('opacity','0.6');
    }else{
        $("#edit_grn_qr_code_add_div").addClass('elem-hidden');
        $("#edit_grn_qr_code_delete_div").removeClass('elem-hidden');
         $("#add_grn_inv_btn").css('opacity','0.6');
        $("#delete_grn_inv_btn").css('opacity','1');
    }
}

function cancelInventoryReturnCompleteDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_cancel_dialog").modal('show');
}

function submitCancelInventoryReturnCompleteDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',true);
    var comments = $("#cancel_comments").val();
    var demand_id = $("#demand_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-return-complete/detail/"+demand_id+"?action=cancel_demand",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#push_demand_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',false);
            $("#cancelPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}


function cancelPendingInventoryDebitNote(id){
    $("#cancelPendingInventoryDebitNoteSuccessMessage,#cancelPendingInventoryDebitNoteErrorMessage,.invalid-feedback").html('').hide();
    $("#pending_inv_debit_note_id").val(id);
    $("#pending_inv_debit_note_cancel_dialog").modal('show');
}

function submitCancelPendingInventoryDebitNote(){
    $("#cancelPendingInventoryDebitNoteSuccessMessage,#cancelPendingInventoryDebitNoteErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPendingInventoryDebitNoteSubmit,#cancelPendingInventoryDebitNoteCancel").attr('disabled',true);
    var comments = $("#pending_inv_debit_note_cancel_comments").val();
    var debit_note_id = $("#pending_inv_debit_note_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/debit-note/add/"+debit_note_id+"?action=cancel_debit_note",
        method:"POST",
        data:{debit_note_id:debit_note_id,comments:comments},
        success:function(msg){
            $("#cancelPendingInventoryDebitNoteSubmit,#cancelPendingInventoryDebitNoteCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelPendingInventoryDebitNoteErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPendingInventoryDebitNoteSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#pending_inv_debit_note_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPendingInventoryDebitNoteErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPendingInventoryDebitNoteSubmit,#cancelPendingInventoryDebitNoteCancel").attr('disabled',false);
            $("#cancelPendingInventoryDebitNoteErrorMessage").html('Error in processing request').show();
        }
    });
}

function cancelPOInvoiceExcessAmountDebitNote(id){
    $("#cancelExcessAmountDebitNoteSuccessMessage,#cancelExcessAmountDebitNoteErrorMessage,.invalid-feedback").html('').hide();
    $("#excess_amount_debit_note_id").val(id);
    $("#excess_amount_debit_note_cancel_dialog").modal('show');
}

function submitCancelPOInvoiceExcessAmountDebitNote(){
    $("#cancelExcessAmountDebitNoteSuccessMessage,#cancelExcessAmountDebitNoteErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelExcessAmountDebitNoteSubmit,#cancelExcessAmountDebitNoteCancel").attr('disabled',true);
    var comments = $("#excess_amount_debit_note_cancel_comments").val();
    var debit_note_id = $("#excess_amount_debit_note_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/sor/inventory/debit-note/excess-amount/add/"+debit_note_id+"?action=cancel_debit_note",
        method:"POST",
        data:{debit_note_id:debit_note_id,comments:comments},
        success:function(msg){
            $("#cancelExcessAmountDebitNoteSubmit,#cancelExcessAmountDebitNoteCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelExcessAmountDebitNoteErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelExcessAmountDebitNoteSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#excess_amount_debit_note_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelExcessAmountDebitNoteErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelExcessAmountDebitNoteSubmit,#cancelExcessAmountDebitNoteCancel").attr('disabled',false);
            $("#cancelExcessAmountDebitNoteErrorMessage").html('Error in processing request').show();
        }
    });
}

function importPOInvoiceInventory(){
    $("#barcodeTxtFile").val('');
    $("#importPOInvoiceInventoryErrorMessage,#importPOInvoiceInventorySuccessMessage").html('').hide();
    $("#importPOInvoiceInventoryDialog").modal('show');
}

function submitImportPOInvoiceInventory(){
    $("#importPOInvoiceInventoryForm").submit();
}

$("#importPOInvoiceInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var po_detail_id = $("#po_detail_id").val(), po_id = $("#po_id").val();
    
    var formData = new FormData(this);
    formData.append('po_id',po_id);
    formData.append('po_detail_id',po_detail_id);
    
    $("#importPOInvoiceInventorySpinner").show();
    $("#importPOInvoiceInventoryCancel,#importPOInvoiceInventorySubmit").attr('disabled',true);
    $("#importPOInvoiceInventoryErrorMessage,#importPOInvoiceInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/warehouse/sor/inventory/import/"+po_detail_id+"?action=import_po_invoice_inventory",
        success: function(msg){		
            $("#importPOInvoiceInventorySpinner").hide();
            $("#importPOInvoiceInventoryCancel,#importPOInvoiceInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPOInvoiceInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importPOInvoiceInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importPOInvoiceInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importPOInvoiceInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importPOInvoiceInventoryErrorMessage").html('Error in processing request').show();
            $("#importPOInvoiceInventorySpinner").hide();
            $("#importPOInvoiceInventoryCancel,#importPOInvoiceInventorySubmit").attr('disabled',false);
        }
    });
});

function importPOQCInventory(){
    $("#barcodeTxtFile").val('');
    $("#importPOQCInventoryErrorMessage,#importPOQCInventorySuccessMessage").html('').hide();
    $("#importPOQCInventoryDialog").modal('show');
}

function submitImportPOQCInventory(){
    $("#importPOQCInventoryForm").submit();
}

$("#importPOQCInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var po_detail_id = $("#po_detail_id").val(), po_id = $("#po_id").val();
    
    var formData = new FormData(this);
    formData.append('po_id',po_id);
    formData.append('po_detail_id',po_detail_id);
    
    $("#importPOQCInventorySpinner").show();
    $("#importPOQCInventoryCancel,#importPOQCInventorySubmit").attr('disabled',true);
    $("#importPOQCInventoryErrorMessage,#importPOQCInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/warehouse/sor/inventory/qc/"+po_detail_id+"?action=import_po_qc_inventory",
        success: function(msg){		
            $("#importPOQCInventorySpinner").hide();
            $("#importPOQCInventoryCancel,#importPOQCInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPOQCInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importPOQCInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importPOQCInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importPOQCInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importPOQCInventoryErrorMessage").html('Error in processing request').show();
            $("#importPOQCInventorySpinner").hide();
            $("#importPOQCInventoryCancel,#importPOQCInventorySubmit").attr('disabled',false);
        }
    });
});

function importPushDemandInventory(){
    $("#barcodeTxtFile").val('');
    $("#importPushDemandInventoryErrorMessage,#importPushDemandInventorySuccessMessage").html('').hide();
    $("#importPushDemandInventoryDialog").modal('show');
}

function submitImportPushDemandInventory(){
    $("#importPushDemandInventoryForm").submit();
}

$("#importPushDemandInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    formData.append('store_id',store_id);
    
    $("#importPushDemandInventorySpinner").show();
    $("#importPushDemandInventoryCancel,#importPushDemandInventorySubmit").attr('disabled',true);
    $("#importPushDemandInventoryErrorMessage,#importPushDemandInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importPushDemandInventorySpinner").hide();
            $("#importPushDemandInventoryCancel,#importPushDemandInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPushDemandInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importPushDemandInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importPushDemandInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importPushDemandInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importPushDemandInventoryErrorMessage").html('Error in processing request').show();
            $("#importPushDemandInventorySpinner").hide();
            $("#importPushDemandInventoryCancel,#importPushDemandInventorySubmit").attr('disabled',false);
        }
    });
});

function importStoreToWHLoadDemandInventory(){
    $("#barcodeTxtFile").val('');
    $("#importStoreToWHLoadDemandInventoryErrorMessage,#importStoreToWHLoadDemandInventorySuccessMessage").html('').hide();
    $("#importStoreToWHLoadDemandInventoryDialog").modal('show');
}

function submitImportStoreToWHLoadDemandInventory(){
    $("#importStoreToWHLoadDemandInventoryForm").submit();
}

$("#importStoreToWHLoadDemandInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    formData.append('store_id',store_id);
    
    $("#importStoreToWHLoadDemandInventorySpinner").show();
    $("#importStoreToWHLoadDemandInventoryCancel,#importStoreToWHLoadDemandInventorySubmit").attr('disabled',true);
    $("#importStoreToWHLoadDemandInventoryErrorMessage,#importStoreToWHLoadDemandInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/warehouse/demand/inventory-return/load/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importStoreToWHLoadDemandInventorySpinner").hide();
            $("#importStoreToWHLoadDemandInventoryCancel,#importStoreToWHLoadDemandInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importStoreToWHLoadDemandInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importStoreToWHLoadDemandInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importStoreToWHLoadDemandInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importStoreToWHLoadDemandInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importStoreToWHLoadDemandInventoryErrorMessage").html('Error in processing request').show();
            $("#importStoreToWHLoadDemandInventorySpinner").hide();
            $("#importStoreToWHLoadDemandInventoryCancel,#importStoreToWHLoadDemandInventorySubmit").attr('disabled',false);
        }
    });
});


function importWHToVendorDemandInventory(){
    $("#barcodeTxtFile").val('');
    $("#importWHToVendorDemandInventoryErrorMessage,#importWHToVendorDemandInventorySuccessMessage").html('').hide();
    $("#importWHToVendorDemandInventoryDialog").modal('show');
}

function submitImportWHToVendorDemandInventory(){
    $("#importWHToVendorDemandInventoryForm").submit();
}

$("#importWHToVendorDemandInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    
    $("#importWHToVendorDemandInventorySpinner").show();
    $("#importWHToVendorDemandInventoryCancel,#importWHToVendorDemandInventorySubmit").attr('disabled',true);
    $("#importWHToVendorDemandInventoryErrorMessage,#importWHToVendorDemandInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/warehouse/demand/inventory-return-vendor/edit/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importWHToVendorDemandInventorySpinner").hide();
            $("#importWHToVendorDemandInventoryCancel,#importWHToVendorDemandInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importWHToVendorDemandInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importWHToVendorDemandInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importWHToVendorDemandInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importWHToVendorDemandInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importWHToVendorDemandInventoryErrorMessage").html('Error in processing request').show();
            $("#importWHToVendorDemandInventorySpinner").hide();
            $("#importWHToVendorDemandInventoryCancel,#importWHToVendorDemandInventorySubmit").attr('disabled',false);
        }
    });
});

function downloadWarehouseToStoreReport(){
    $("#state_list_div_download").html($("#state_list_div").html());
    $("#store_list_div_download").html($("#store_list_div").html());
    $("#store_type_div_download").html($("#store_type_div").html());
    $("#date_range_div_download").html($("#date_range_div").html());
    $('.input-daterange').datepicker({format: 'dd-mm-yyyy'});
    $("#report_download_dialog").modal('show');
}

function submitDownloadWarehouseToStoreReport(){
    var str = '';
    if($("#report_download_dialog #startDate").val() != '' && $("#report_download_dialog #endDate").val() != ''){
        str+="&startDate="+$("#report_download_dialog #startDate").val()+"&endDate="+$("#report_download_dialog #endDate").val();
    }
    
    if($("#report_download_dialog #state_id").val() != ''){
        str+="&state_id="+$("#report_download_dialog #state_id").val()
    }
    
    if($("#report_download_dialog #s_id").val() != ''){
        str+="&s_id="+$("#report_download_dialog #s_id").val()
    }
    
    if($("#report_download_dialog #store_type").val() != ''){
        str+="&store_type="+$("#report_download_dialog #store_type").val()
    }
    
    var report_type = $("#report_type").val();
    
    var url = ROOT_PATH+"/report/warehouse/to/store?action=download_csv&report_type="+report_type+str;
    window.location.href = url;
}

function openInventoryPushDemand(){
    $("#openPushDemandSuccessMessage,#openPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_open_dialog").modal('show');
}

function submitOpenInventoryPushDemand(){
    $("#openPushDemandSuccessMessage,#openPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#openPushDemandCancel,#openPushDemandSubmit").attr('disabled',true);
    var comments = $("#open_comments").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id+"?action=open_demand",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#openPushDemandCancel,#openPushDemandSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#openPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#openPushDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#push_demand_open_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"openPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#openPushDemandCancel,#openPushDemandSubmit").attr('disabled',false);
            $("#openPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function getStateStores(state_id,store_id){
   ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/report/store/to/customer?action=get_state_stores&state_id="+state_id,
        method:"GET",
        success:function(msg){
           if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#reportErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var stores = msg.stores_list,options_str = '<option value="">-- Store --</option>';
                    for(var i=0;i<stores.length;i++){
                        var sel =  (store_id != '' && store_id == stores[i].id)?'selected':'';
                        options_str+="<option "+sel+" value='"+stores[i].id+"'>"+stores[i].store_name+" ("+stores[i].store_id_code+")</option>";
                    }
                    $("#s_id").html(options_str);
                    $("#reportDownloadFrm #s_id").html(options_str);
                }
            }else{
                displayResponseError(msg,'reportErrorMessage');
            }
        },error:function(obj,status,error){
            $("#reportErrorMessage").html('Error in processing request').show();
        }
    });
}