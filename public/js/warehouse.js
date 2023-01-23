"use strict";

function createInventoryPushDemand(){
    $("#addInventoryPushDemandSuccessMessage,#addInventoryPushDemandErrorMessage").html('').hide();
    $("#store_id").val('');
    $("#add_inventory_push_demand_dialog").modal('show');
}

function submitCreateInventoryPushDemand(){
    var store_id = $("#store_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/list?action=create_demand",
        method:"GET",
        data:{store_id:store_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryPushDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+msg.demand_details.id;
                    setTimeout(function(){  $("#add_inventory_push_demand_dialog").modal('hide');window.location.href = url; }, 2000);
                }
            }else{
                displayResponseError(msg,"addInventoryPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addInventoryPushDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

var product_data_inv_push_demand = '',inventory_products_inv_push_demand = [],  rec_per_page_inv_push_demand = '', page_global_inv_push_demand = 1;

function getInventoryPushDemandProductData(barcode){
    product_data_inv_push_demand = '';
    var demand_id = $("#demand_id").val();
    
    if(barcode == ''){
        $("#importPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,demand_id:demand_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPosInventoryErrorMessage").html(errors).show();
                        $(".import-data").val('');
                        $("#piece_id").val('');
                    } 
                }else{ 
                    product_data_inv_push_demand = msg.product_data;
                    $("#product_name").val(product_data_inv_push_demand.product_name);
                    $("#piece_size").val(product_data_inv_push_demand.size_name);
                    $("#piece_color").val(product_data_inv_push_demand.color_name);
                    $("#piece_vendor").val('');
                    $("#piece_po_number").val('');
                    $("#product_sku").val(product_data_inv_push_demand.product_sku);
                    $("#piece_cost").val(product_data_inv_push_demand.base_price);
                    $("#intake_date").val(product_data_inv_push_demand.intake_date);
                    $("#piece_id").val(product_data_inv_push_demand.id);
                    $("#product_id").val(product_data_inv_push_demand.product_master_id);
                    $("#importPosInventoryErrorMessage").html('').hide();
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

$("#piece_barcode_inv_push_demand").on('propertychange change keyup paste input', function(){
    getInventoryPushDemandProductData(this.value);
});

function addInventoryPushDemandProductData(){
    $("#importPosInventoryErrorMessage").html('').hide();
    var piece_id = $("#piece_id").val();
    
    if(piece_id == ''){
        $("#importPosInventoryErrorMessage").html('Product data is required field').show();
        return false;
    }
    
    var store_id = $("#store_id").val();
    var demand_id = $("#demand_id").val();
    var product_id = $("#product_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=add_inventory_product",
        method:"get",
        data:{id:piece_id,store_id:store_id,demand_id:demand_id,product_id:product_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    product_data_inv_push_demand.product_status = 2;
                    product_data_inv_push_demand.store_assign_date = getCurrentDate(2);
                    inventory_products_inv_push_demand.push(product_data_inv_push_demand);
                    displayInventoryPushDemandInventoryData();
                    $(".import-data").val('');
                    $("#piece_barcode_inv_push_demand,#piece_id").val('');
                    $("#piece_barcode_inv_push_demand").focus();
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayInventoryPushDemandInventoryData(){
    var str = '';
    if(inventory_products_inv_push_demand.length > 0){
        str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>Out Date</th><th>Status</th></tr></thead><tbody>'

        var start = parseInt((page_global_inv_push_demand-1)*rec_per_page_inv_push_demand)+1;
        for(var i=0;i<inventory_products_inv_push_demand.length;i++){
            product_data_inv_push_demand = inventory_products_inv_push_demand[i];
            var store_assign_date = formatDate(product_data_inv_push_demand.store_assign_date);
            str+='<tr><td>'+(start+i)+'</td><td>'+product_data_inv_push_demand.peice_barcode+'</td><td>'+product_data_inv_push_demand.product_name+'</td><td>'+product_data_inv_push_demand.size_name+'</td><td>'+product_data_inv_push_demand.color_name+'</td>\
            <td>'+product_data_inv_push_demand.product_sku+'</td><td>'+product_data_inv_push_demand.base_price+'</td><td>'+store_assign_date+'</td><td>'+getProductInventoryStatus(product_data_inv_push_demand.product_status)+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    $("#products_imported_list").html(str);
}

function loadInventoryPushDemandInventory(page){
    var demand_id = $("#demand_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page},
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
                    inventory_products_inv_push_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_push_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_push_demand = msg.rec_per_page;
                    page_global_inv_push_demand = page;
                    displayInventoryPushDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
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

function closeInventoryPushDemand(){
    $("#closeInventoryPushDemandSuccessMessage,#closeInventoryPushDemandErrorMessage").html('').hide();
    $("#courier_detail,#vehicle_detail").val('');
    $("#closeInventoryPushDemandDialog").modal('show');
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
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=close_demand",
        method:"POST",
        data:{courier_detail:courier_detail,vehicle_detail:vehicle_detail,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#closeInventoryPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#closeInventoryPushDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/warehouse/demand/inventory-push/detail/"+demand_id;
                    setTimeout(function(){  $("#closeInventoryPushDemandDialog").modal('hide');window.location.href = url; }, 2000);
                }
            }else{
                displayResponseError(msg,"closeInventoryPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
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
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/inventory-push/edit/"+demand_id+"?action=update_gate_pass_data",
        method:"POST",
        data:{demand_id:demand_id,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editGatePassErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editGatePassSuccessMessage").html(msg.message).show();
                    
                    setTimeout(function(){  $("#editGatePassDialog").modal('hide');window.location.reload();  }, 2000);
                }
            }else{
                displayResponseError(msg,"editGatePassErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editGatePassErrorMessage").html('Error in processing request').show();
        }
    });
}


var product_data_import_inv = '',inventory_products_import_inv = [],  rec_per_page_import_inv = '', page_global_import_inv = 1, inventory_data_import_inv = '',barcode_list = [];

function getInventoryProductData(barcode){
    var error_msg = '';
    product_data_import_inv = ''
    var po_id = $("#po_id").val();//alert(barcode);
    if(barcode == ''){
        $("#product_added_span").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    if(barcode.length < 13 ){
        $("#product_added_span").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    if(barcode_list.indexOf(barcode) >= 0){
        error_msg = 'Product already added';//alert(error_msg);
    }
    
    if(error_msg != ''){
        $("#product_added_span").html(error_msg).addClass('alert-danger').removeClass('alert-success').show();
        $(".import-data").val('');
        return false;
    }
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,po_id:po_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        //$("#importPosInventoryErrorMessage").html(errors).show();
                        $("#product_added_span").html(errors).addClass('alert-danger').removeClass('alert-success').show();
                        $(".import-data").val('');
                        $("#piece_id").val('');
                    } 
                }else{ 
                    $("#product_added_span").html('');
                    product_data_import_inv = msg.product_data;
                    $("#product_name").val(product_data_import_inv.product_name);
                    $("#piece_size").val(product_data_import_inv.size_name);
                    $("#piece_color").val(product_data_import_inv.color_name);
                    $("#piece_vendor").val(product_data_import_inv.vendor_name);
                    $("#piece_po_number").val(product_data_import_inv.po_order_no);
                    $("#product_sku").val(product_data_import_inv.product_sku);
                    $("#piece_cost").val(product_data_import_inv.base_price);
                    $("#intake_date").val(getCurrentDate(1));
                    $("#piece_id").val(product_data_import_inv.id);
                    //$("#importPosInventoryErrorMessage").html('').hide();
                    //$("#piece_barcode_inv_import").val('');
                    //$("#product_added_span").html('Product added');
                    //$("#product_added_span").html('Product added').addClass('alert-success').removeClass('alert-danger').show();
                    
                    /*setTimeout(function(){   
                        $("#product_added_span").html('');
                        product_data_import_inv.product_status = 1;
                        product_data_import_inv.intake_date = getCurrentDate(2);
                        inventory_products_import_inv.push(product_data_import_inv);
                        inventory_data_import_inv.inv_imported = parseInt(inventory_data_import_inv.inv_imported)+1;
                        displayAddInventoryData();
                        $(".import-data").val('');
                        $("#piece_barcode_inv_import,#piece_id").val('');
                        $("#piece_barcode_inv_import").focus();
                        $("#pos_add_inventory_grn_submit,#pos_add_inventory_grn_cancel").show();
                    }, 800);*/
                }
            }else{
                //displayResponseError(msg,"importPosInventoryErrorMessage");
                $("#product_added_span").html(msg.message).addClass('alert-danger').removeClass('alert-success').show();
            }
        },error:function(obj,status,error){
            //$("#importPosInventoryErrorMessage").html('Error in processing request').show();
             $("#product_added_span").html('Error in processing request').addClass('alert-danger').removeClass('alert-success').show();
        }
    });
}

$("#piece_barcode_inv_import").on('propertychange change paste input', function(e){
    setTimeout(function(){   
        var val = $("#piece_barcode_inv_import").val();
        getInventoryProductData(val);
    }, 800);
    
});

function addInventoryProductData(){
    $("#pos_add_inventory_submit").attr('disabled',true);
    setTimeout(function(){   
        submitAddInventoryProductData();
    }, 1000);
} 

function submitAddInventoryProductData(){
    //$("#importPosInventoryErrorMessage").html('').hide();
    
    $("#product_added_span").html('').hide();
    var piece_id = $("#piece_id").val();
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    
    if(piece_id == ''){
        //$("#importPosInventoryErrorMessage").html('Product data is required field').show();
        $("#product_added_span").html('Product data is required field').addClass('alert-danger').removeClass('alert-success').show();
        $("#pos_add_inventory_submit").attr('disabled',false);
        return false;
    }
    
    $("#pos_add_inventory_submit").attr('disabled',true);
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=add_inventory_product&d="+new Date().getTime(),
        method:"get",
        data:{id:piece_id,po_id:po_id,po_detail_id:po_detail_id},
        success:function(msg){
            $("#pos_add_inventory_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        //$("#importPosInventoryErrorMessage").html(errors).show();
                        $("#product_added_span").html(errors).addClass('alert-danger').removeClass('alert-success').show();
                    } 
                }else{ 
                    product_data_import_inv.product_status = 1;
                    product_data_import_inv.intake_date = getCurrentDate(2);
                    inventory_products_import_inv.push(product_data_import_inv);
                    inventory_data_import_inv.inv_imported = parseInt(inventory_data_import_inv.inv_imported)+1;
                    barcode_list.push(product_data_import_inv.peice_barcode);
                    displayAddInventoryData();
                    $(".import-data").val('');
                    $("#piece_barcode_inv_import,#piece_id").val('');
                    $("#piece_barcode_inv_import").focus();
                    $("#pos_add_inventory_grn_submit,#pos_add_inventory_grn_cancel").show();
                    $("#product_added_span").html('Product added').addClass('alert-success').removeClass('alert-danger').show();
                }
            }else{
                //displayResponseError(msg,"importPosInventoryErrorMessage");
                $("#product_added_span").html(msg.message).addClass('alert-danger').removeClass('alert-success').show();
            }
        },error:function(obj,status,error){
            //$("#importPosInventoryErrorMessage").html('Error in processing request').show();
            $("#product_added_span").html('Error in processing request').addClass('alert-danger').removeClass('alert-success').show();
            $("#pos_add_inventory_submit").attr('disabled',false);
        }
    });
}

function displayAddInventoryData(){
    var str = '<hr><h6>Inventory Details: </h6>';
    str+='Total Inventory: '+inventory_data_import_inv.inv_total+" | Imported Inventory: "+inventory_data_import_inv.inv_imported;
    if(inventory_products_import_inv.length > 0){
        str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>Size</th><th>Color</th><th>Vendor</th><th>PO No</th><th>SKU</th><th>Cost</th><th>Intake Date</th><th>Status</th></tr></thead><tbody>'

        var start = parseInt((page_global_import_inv-1)*rec_per_page_import_inv)+1;
        for(var i=0;i<inventory_products_import_inv.length;i++){
            product_data_import_inv = inventory_products_import_inv[i];
            var intake_date = formatDate(product_data_import_inv.intake_date);
            str+='<tr><td>'+(start+i)+'</td><td>'+product_data_import_inv.peice_barcode+'</td><td>'+product_data_import_inv.product_name+'</td><td>'+product_data_import_inv.size_name+'</td><td>'+product_data_import_inv.color_name+'</td>\
            <td>'+product_data_import_inv.vendor_name+'</td><td>'+product_data_import_inv.po_order_no+'</td><td>'+product_data_import_inv.product_sku+'</td><td>'+product_data_import_inv.base_price+'</td><td>'+intake_date+'</td><td>'+getProductInventoryStatus(product_data_import_inv.product_status)+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    $("#products_imported_list").html(str);
}

function displayGRNLIst(grn_list){
    var str = '';
    if(grn_list.length > 0){
        str+='<hr><h6>GRN List</h6><div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>GRN No</th><th>Inventory Count</th><th>Vehicle/Airways Number</th><th>Containers Count</th><th>Added On</th><th>Action</th></tr></thead><tbody>';
        for(var i=0;i<grn_list.length;i++){
            var created_at = formatDate(grn_list[i].created_at);
            str+='<tr><td>'+(i+1)+'</td><td>'+grn_list[i].po_grn_items_count+'</td><td>'+grn_list[i].vehicle_no+'</td><td>'+grn_list[i].containers_count+'</td><td>'+created_at+'</td>\
            <td><a href="javascript:;" onclick="displayInventoryDetails('+grn_list[i].po_detail_id+');"><i title="Details" class="fas fa-eye"></i></a>&nbsp;\
            <a href="'+ROOT_PATH+'/warehouse/pos/inventory/import/invoice/'+grn_list[i].id+'" ><i title="PDF Invoice" class="fas fa-eye"></i></a>\
            <!--&nbsp;<a href="javascript:;" onclick="deleteInventoryDetails('+grn_list[i].id+');"><i title="Delete" class="fas fa-trash"></i></a></td>--></tr>';
        }
        str+='</tbody></table></div>';
    }
    $("#vehicle_details_list").html(str);
}

function checkImportInventoryPO(){
    $("#importPosInventoryErrorMessage").html('').hide();
    var po_no = $("#po_no").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=check_po_no",
        method:"get",
        data:{po_no:po_no},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#products_import_div").show();
                    $("#po_check_div").hide();
                    $("#po_id").val(msg.po_data.id);
                    $("#importPosInventoryErrorMessage").html('').hide();
                    $("#po_no_span").html("PO No: "+msg.po_data.order_no).show();
                    loadPOInventory(1);
                    if(msg.inventory_import_pending_count == 0){    // All inventory imported
                       $("#products_add_div").hide();
                    }else if(msg.inventory_import_pending_count > 0 && (msg.po_details != '' && msg.po_details != null)){   // vehicle details are added
                        $("#products_add_div").show();
                        $("#vehicle_details_div").hide();
                        displayCurrentVehicleDetails(msg.po_details);
                        $("#po_detail_id").val(msg.po_details.id);
                    }else if(msg.inventory_import_pending_count > 0 && (msg.po_details == '' || msg.po_details == null)){ // vehicle details not added
                        $("#products_add_div").hide();
                        $("#vehicle_details_div").show();
                        //$("#pos_add_inventory_grn_submit,#pos_add_inventory_grn_cancel").hide();
                    }
                    
                    // If inventory products have grn pending
                    if(msg.inventory_grn_pending == 0){
                        $("#pos_add_inventory_grn_submit,#pos_add_inventory_grn_cancel").hide();
                    }else{
                        $("#pos_add_inventory_grn_submit,#pos_add_inventory_grn_cancel").show();
                    }
                    $("#piece_barcode_inv_import").focus();
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

$("#poCheckForm").on('submit', function(event){
    event.preventDefault(); 
    checkImportInventoryPO();
});

function displayCurrentVehicleDetails(po_details){
    var str = '';
    
    str+='<h6>Current Vehicle Details:</h6><div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>Vehicle/Airways Number</th><th>Containers Count</th><th>Added On</th><th>Action</th></tr></thead><tbody>';
    str+='<tr><td>'+po_details.vehicle_no+'</td><td>'+po_details.containers_count+'</td><td>'+formatDate(po_details.created_at)+'</td>\
    <td><a href="javascript:;" onclick="displayInventoryDetails('+po_details.id+');"><i title="Details" class="fas fa-eye"></i></a></td></tr>';
    str+='</tbody></table></div>';
    
    $("#current_vehicle_details").html(str);
}

function loadPOInventory(page){
    var po_id = $("#po_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=load_po_inventory",
        method:"get",
        data:{po_id:po_id,page:page},
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
                    inventory_products_import_inv = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_import_inv.push(product_list[i]);
                    }
                    
                    rec_per_page_import_inv = msg.rec_per_page;
                    page_global_import_inv = page;
                    inventory_data_import_inv = msg.inventory_count_data;
                    barcode_list = msg.barcode_list;
                    displayAddInventoryData();
                    $("#products_paging_links").html(paging_links);
                    displayGRNLIst(msg.grn_list);
                    
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

function submitAddVehicleDetails(){
    $("#addVehicleDetailsForm").submit();
}

$("#addVehicleDetailsForm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    formData.append('vehicle_no', $("#vehicle_no").val());
    formData.append('containers_count', $("#containers_count").val());
    formData.append('comments', $("#comments").val());
    formData.append('po_id', $("#po_id").val());
    
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
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=add_vehicle_details",
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
                    setTimeout(function(){  $("#add_vehicle_details_dialog").modal('hide');loadPOInventory(page_global_import_inv); }, 2000);
                    $("#addVehicleDetailsSuccessMessage").html(msg.message).show();
                    $("#addVehicleDetailsErrorMessage").html('').hide();
                    $("#po_detail_id").val(msg.po_details.id);
                    $("#products_add_div").show();
                    $("#vehicle_details_div").hide();
                    $("#piece_barcode_inv_import").focus();
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

/*function displayConfirmImportInventory(){
    $("#vehicle_no,#containers_count").val('');
    $("#inventory_import_confirm_dialog").modal('show');
}*/

function displayAddVehicleDetails(){
    $("#vehicle_no,#containers_count,#comments").val('');
    $("#add_vehicle_details_dialog").modal('show');
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

function displayInventoryDetails(id){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=get_inventory_detail",
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
                    $("#inventory_vehicle_detail_dialog").modal('show');
                    $("#vehicle_no_detail").val(msg.vehicle_details.vehicle_no);
                    $("#containers_count_detail").val(msg.vehicle_details.containers_count);
                    $("#comments_detail").val(msg.vehicle_details.comments);
                    $("#grn_comments_detail").val(msg.grn_data.comments);
                    
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
                    
                    var products_list = msg.products_list;
                    str = '';var grand_total_qty = 0, grand_total_tax = 0,grand_total_amount = 0;
                    if(products_list.length > 0){
                        str+='<div class="table-responsive" id="grn_products_div"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Item SKU</th><th>Item Name</th>';
                        str+='<th>Qty</th><th>Rate</th><th>TaxAmt</th><th>Total Amount</th></tr></thead><tbody>'

                        for(var i=0;i<products_list.length;i++){
                            var product_data = products_list[i];
                            var base_rate = (product_data.vendor_base_price != null && product_data.vendor_base_price != '')?product_data.vendor_base_price:product_data.rate;
                            var total_rate = (base_rate*product_data.products_count);
                            var gst_amount = (product_data.vendor_gst_amount != null && product_data.vendor_gst_amount != '')?product_data.vendor_gst_amount:(product_data.rate*(product_data.gst_percent/100));
                            var tax_amount = (gst_amount*product_data.products_count);
                            var total_amount = (parseFloat(total_rate)+parseFloat(tax_amount));
                            
                            str+='<tr><td>'+(i+1)+'</td><td>'+product_data.product_sku+'</td><td>'+product_data.product_name+'</td><td>'+product_data.products_count+'</td><td>'+base_rate+'</td>\
                            <td>'+tax_amount.toFixed(2)+'</td><td>'+total_amount.toFixed(2)+'</td></tr>';
                            
                            grand_total_qty+=parseInt(product_data.products_count);
                            grand_total_tax+=parseFloat(tax_amount)
                            grand_total_amount+=parseFloat(total_amount);
                        }
                        
                         str+='<tr><td colspan="3" ><b>Total</b></td><td><b>'+grand_total_qty+'</b></td><td></td><td><b>'+grand_total_tax.toFixed(2)+'</b></td><td><b>'+grand_total_amount.toFixed(2)+'</b></td></tr>';
                        str+='</tbody></table></div>';
                    }
                    
                    $("#po_detail_products_list").html(str);
                }
            }else{
                displayResponseError(msg,"inventoryVehicleDetailsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryVehicleDetailsErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteInventoryDetails(id){
    $("#deleteVehicleDetailsErrorMessage,#deleteVehicleDetailsSuccessMessage").html('').hide();
    
    $('#confirm_delete_vehicle_detail').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_vehicle_detail_btn', function(e) {
        e.preventDefault();
        $("#delete_vehicle_detail_spinner").show();
        $("#delete_vehicle_detail_btn,#delete_vehicle_detail_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "GET",
            data:{id:id},
            url:ROOT_PATH+"/warehouse/pos/inventory/import?action=delete_vehicle_detail",
            success: function(msg){	
                $("#delete_vehicle_detail_spinner").hide();
                $("#delete_vehicle_detail_btn,#delete_vehicle_detail_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){    
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteVehicleDetailsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteVehicleDetailsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#confirm_delete_vehicle_detail").modal('hide');loadPOInventory(page_global_import_inv); }, 2000);
                    }
                }else{
                    displayResponseError(msg,"deleteVehicleDetailsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteVehicleDetailsErrorMessage").html('Error in processing request').show();
                $("#delete_vehicle_detail_spinner").hide();
                $("#delete_vehicle_detail_btn,#delete_vehicle_detail_cancel").attr('disabled',false);
            }
        });
    });
}

var product_data_qc_inv = '',inventory_products_qc_inv = [],  rec_per_page_qc_inv = '', page_global_qc_inv = 1;

function getInventoryProductDataForQC(barcode){
    product_data_qc_inv = '';
    var po_id = $("#po_id").val();
    if(barcode == ''){
        $("#qcPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/qc/"+po_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,po_id:po_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#qcPosInventoryErrorMessage").html(errors).show();
                        $(".import-data").val('');
                        $("#piece_id").val('');
                    } 
                }else{ 
                    product_data_qc_inv = msg.product_data;
                    $("#product_name").val(product_data_qc_inv.product_name);
                    $("#piece_size").val(product_data_qc_inv.size_name);
                    $("#piece_color").val(product_data_qc_inv.color_name);
                    $("#piece_vendor").val(product_data_qc_inv.vendor_name);
                    $("#piece_po_number").val(product_data_qc_inv.po_order_no);
                    $("#product_sku").val(product_data_qc_inv.product_sku);
                    $("#piece_cost").val(product_data_qc_inv.base_price);
                    $("#qc_date").val(getCurrentDate(1));
                    $("#piece_id").val(product_data_qc_inv.id);
                    $("#qcPosInventoryErrorMessage").html('').hide();
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
    getInventoryProductDataForQC(this.value);
});

function addInventoryProductQC(){
    $("#qcPosInventoryErrorMessage").html('').hide();
    var piece_id = $("#piece_id").val();
    var po_id = $("#po_id").val();
    
    if(piece_id == ''){
        $("#importPosInventoryErrorMessage").html('Product data is required field').show();
        return false;
    }
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/qc/"+po_id+"?action=qc_inventory_product",
        method:"get",
        data:{id:piece_id,po_id:po_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#qcPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    product_data_qc_inv.qc_status = 2;
                    product_data_qc_inv.qc_date = getCurrentDate(2);
                    inventory_products_qc_inv.push(product_data_qc_inv);
                    displayQCInventoryData();
                    $(".import-data").val('');
                    $("#piece_barcode_inv_qc,#piece_id").val('');
                    $("#piece_barcode_inv_qc").focus();
                }
            }else{
                displayResponseError(msg,"qcPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#qcPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayQCInventoryData(){
    var str = '';
    if(inventory_products_qc_inv.length > 0){
        str+='<h6>QC Completed Inventory: </h6><div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
        str+='<th>Size</th><th>Color</th><th>Vendor</th><th>PO No</th><th>SKU</th><th>Cost</th><th>QC Date</th><th>QC Status</th></tr></thead><tbody>'

        var start = parseInt((page_global_qc_inv-1)*rec_per_page_qc_inv)+1;
        for(var i=0;i<inventory_products_qc_inv.length;i++){
            product_data_qc_inv = inventory_products_qc_inv[i];
            var qc_date = formatDate(product_data_qc_inv.qc_date);
            str+='<tr><td>'+(start+i)+'</td><td>'+product_data_qc_inv.peice_barcode+'</td><td>'+product_data_qc_inv.product_name+'</td><td>'+product_data_qc_inv.size_name+'</td><td>'+product_data_qc_inv.color_name+'</td>\
            <td>'+product_data_qc_inv.vendor_name+'</td><td>'+product_data_qc_inv.po_order_no+'</td><td>'+product_data_qc_inv.product_sku+'</td><td>'+product_data_qc_inv.base_price+'</td><td>'+qc_date+'</td><td>'+getProductInventoryQCStatus(product_data_qc_inv.qc_status)+'</td></tr>';
        }

        str+='</tbody></table></div>';
    }
    
    $("#products_qc_list").html(str);
}

function loadPOQCInventory(page){
    var po_id = $("#po_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/qc/"+po_id+"?action=load_po_inventory",
        method:"get",
        data:{po_id:po_id,page:page},
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
                    inventory_products_qc_inv = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_qc_inv.push(product_list[i]);
                    }
                    
                    rec_per_page_qc_inv = msg.rec_per_page;
                    page_global_qc_inv = page;
                    displayQCInventoryData();
                    $("#products_paging_links").html(paging_links);
                   
                    
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

function displayConfirmQCInventory(){
    var po_id = $("#po_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/qc/"+po_id+"?action=get_inventory_qc_data",
        method:"get",
        data:{po_id:po_id},
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
    var comments = $("#comments_complete_qc").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/qc/"+po_id+"?action=confirm_complete_inventory_qc",
        method:"POST",
        data:{po_id:po_id,comments:comments},
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
                    setTimeout(function(){  $("#qcInventoryConfirmSuccessMessage").modal('hide');window.location.reload(); }, 2000);
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

function displayAddInventoryGRN(){
    $("#add_inventory_grn_comments").val('');
    $("#add_inventory_grn_dialog").modal('show');
}

function submitAddInventoryGRN(){
    $("#add_inventory_grn_spinner").show();
    $("#add_inventory_grn_cancel,#add_inventory_grn_submit").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var po_detail_id = $("#po_detail_id").val();
    var add_inventory_grn_comments = $("#add_inventory_grn_comments").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/import?action=add_inventory_grn",
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
                    setTimeout(function(){  $("#add_inventory_grn_dialog").modal('hide');window.location.reload(); }, 2000);
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
    $("#return_inventory_dialog").modal('show');
}

function submitReturnInventoryToVendor(){
    $("#inventory_return_spinner").show();
    $("#inventory_return_confirm_submit,#inventory_return_confirm_cancel").attr('disabled',true);
    
    var po_id = $("#po_id").val();
    var qc_return_id = $("#qc_return_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/qc/"+po_id+"?action=confirm_return_inventory",
        method:"POST",
        data:{po_id:po_id,qc_return_id:qc_return_id},
        success:function(msg){
            $("#inventory_return_spinner").hide();
            $("#inventory_return_confirm_submit,#inventory_return_confirm_cancel").attr('disabled',false);
    
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#inventoryReturnErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#inventoryReturnSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#return_inventory_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"inventoryReturnErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventory_return_spinner").hide();
            $("#inventory_return_confirm_submit,#inventory_return_confirm_cancel").attr('disabled',false);
            $("#inventoryReturnErrorMessage").html('Error in processing request').show();
        }
    });
}