"use strict";
var demand_prices = {},demand_names = {},asset_prices = {},asset_names = {},demand_inventory = {},demand_items = {},demand_list = {},current_demand_item = '';

function editStore(id){
    $("#edit_store_dialog").modal('show');
    $("#store_edit_id").val(id);
    $("#editStoreErrorMessage,#editStoreSuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoreErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#store_name_edit").val(msg.store_data.store_name);
                    $("#store_region_edit").val(msg.store_data.region_id);
                    $("#store_address_line1_edit").val(msg.store_data.address_line1);
                    $("#store_address_line2_edit").val(msg.store_data.address_line2);
                    $("#store_city_name_edit").val(msg.store_data.city_name);
                    $("#store_postal_code_edit").val(msg.store_data.postal_code);
                    $("#store_phone_no_edit").val(msg.store_data.phone_no);
                    $("#store_gst_no_edit").val(msg.store_data.gst_no);
                    $("#store_gst_name_edit").val(msg.store_data.gst_name);
                    $("#store_gst_applicable_edit").val(msg.store_data.gst_applicable);
                    $("#store_state_edit").val(msg.store_data.state_id);
                    $("#store_type_edit").val(msg.store_data.store_type);
                    $("#store_code_edit").val(msg.store_data.store_code);
                    $("#store_access_key_edit").val(msg.store_data.access_key);
                    $("#store_gst_type_edit").val(msg.store_data.gst_type);
                    $("#store_zone_edit").val(msg.store_data.zone_id);
                    $("#store_info_type_edit").val(msg.store_data.store_info_type);
                    $("#store_google_name_edit").val(msg.store_data.google_name);
                    $("#store_display_name_edit").val(msg.store_data.display_name);
                    $("#store_latitude_edit").val(msg.store_data.latitude);
                    $("#store_longitude_edit").val(msg.store_data.longitude);
                    $("#store_ecommerce_status_edit").val(msg.store_data.ecommerce_status);
                    $("#front_picture_img,#back_picture_img").attr('src','').hide();
                    if(msg.store_data.front_picture != null && msg.store_data.front_picture != ''){
                        var img_url = ROOT_PATH+'/images/store_images/'+msg.store_data.front_picture;
                        $("#front_picture_img").attr('src',img_url).show();
                    }
                    if(msg.store_data.back_picture != null && msg.store_data.back_picture != ''){
                        var img_url = ROOT_PATH+'/images/store_images/'+msg.store_data.back_picture;
                        $("#back_picture_img").attr('src',img_url).show();
                    }
                }
            }else{
                displayResponseError(msg,"editStoreErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateStore(){
    $("#editStoreFrm").submit();
}

$("#editStoreFrm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    $("#store_edit_spinner").show();
    $("#editStoreErrorMessage,#editStoreSuccessMessage,.invalid-feedback").html('').hide();
    $("#store_edit_submit,#store_edit_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/update",
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        success:function(msg){
            $("#store_edit_submit,#store_edit_cancel").attr('disabled',false);
            $("#store_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editStoreErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStoreSuccessMessage").html(msg.message).show();
                    $("#editStoreErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_store_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editStoreErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreErrorMessage").html('Error in processing request').show();
            $("#store_edit_spinner").hide();
            $("#store_edit_submit,#store_edit_cancel").attr('disabled',false);
        }
    });
}); 

function addStore(){
    $("#addStoreSuccessMessage,#addStoreErrorMessage,.invalid-feedback").html('').hide();
    $("#store_name_add,#store_region_add,#store_address_add").val('');
    $("#add_store_dialog").modal('show');
}

function submitAddStore(){
    $("#addStoreFrm").submit();
}

$("#addStoreFrm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    $("#store_add_spinner").show();
    $("#addStoreSuccessMessage,#addStoreErrorMessage,.invalid-feedback").html('').hide();
    $("#store_add_submit,#store_add_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/add",
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        success:function(msg){
            $("#store_add_submit,#store_add_cancel").attr('disabled',false);
            $("#store_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addStoreErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addStoreSuccessMessage").html(msg.message).show();
                    $("#addStoreErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_store_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addStoreErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addStoreErrorMessage").html('Error in processing request').show();
            $("#store_add_spinner").hide();
            $("#store_add_submit,#store_add_cancel").attr('disabled',false);
        }
    });
});    

function updateStoreStatus(){
    $("#storeListOverlay").show();
    var items_ids = '';
    $(".store-list-chk").each(function(){
        if($(this).is(":checked")){
            items_ids+= $(this).val()+",";
        }
    });
    
    items_ids = items_ids.substring(0,items_ids.length-1);
    var form_data = "action="+$("#store_action").val()+"&ids="+items_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#storeListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateStoreStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateStoreStatusSuccessMessage").html(msg.message).show();
                    $("#updateStoreStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#storeListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateStoreStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateStoreStatusErrorMessage").html('Error in processing request').show();
            $("#storeListOverlay").hide();
        }
    });
}

function editStoreAsset(id){
    $("#edit_item_dialog").modal('show');
    $("#item_edit_id").val(id);
    $("#editStoreItemErrorMessage,#editStoreItemSuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoreItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#item_name_edit").val(msg.item_data.item_name);
                    $("#item_desc_edit").val(msg.item_data.item_desc);
                    //$("#item_manufacturer_edit").val(msg.item_data.item_manufacturer);
                    $("#item_type_edit").val(msg.item_data.item_type);
                    $("#item_category_edit").val(msg.item_data.category_id);
                    $("#item_base_price_edit").val(msg.item_data.base_price);
                    getAssetSubcategories(msg.item_data.category_id,'edit',msg.item_data.subcategory_id);
                }
            }else{
                displayResponseError(msg,"editStoreItemErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreItemErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateStoreAsset(){
    var form_data = $("#editItemFrm").serialize();
    $("#item_edit_spinner").show();
    $("#editStoreItemErrorMessage,#editStoreItemSuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#item_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editStoreItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStoreItemSuccessMessage").html(msg.message).show();
                    $("#editStoreItemErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_item_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"editStoreItemErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreItemErrorMessage").html('Error in processing request').show();
            $("#item_edit_spinner").hide();
        }
    });
}

function addStoreAsset(){
    $("#addStoreItemSuccessMessage,#addStoreItemErrorMessage,.invalid-feedback").html('').hide();
    $("#item_name_add,#item_desc_add,#item_manufacturer_add,#item_type_add,#item_category_add,#item_subcategory_add,#item_base_price_add").val('');
    $("#add_item_dialog").modal('show');
}

function submitAddStoreAsset(){
    var form_data = $("#addItemFrm").serialize();
    $("#item_add_spinner").show();
    $("#addStoreItemSuccessMessage,#addStoreItemErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/create",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#item_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addStoreItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addStoreItemSuccessMessage").html(msg.message).show();
                    $("#addStoreItemErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_item_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"addStoreItemErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addStoreItemErrorMessage").html('Error in processing request').show();
            $("#item_add_spinner").hide();
        }
    });
}

function updateStoreAssetStatus(){
    $("#storeItemsListOverlay").show();
    var items_ids = '';
    $(".store-item-list-chk").each(function(){
        if($(this).is(":checked")){
            items_ids+= $(this).val()+",";
        }
    });
    
    items_ids = items_ids.substring(0,items_ids.length-1);
    var form_data = "action="+$("#store_item_action").val()+"&ids="+items_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#storeItemsListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateItemStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateItemStatusSuccessMessage").html(msg.message).show();
                    $("#updateItemStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#storeItemsListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateItemStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateItemStatusErrorMessage").html('Error in processing request').show();
            $("#storeItemsListOverlay").hide();
        }
    });
}

function getStoreAssetRegionPrices(id){
    $("#storeItemRegionPriceSuccessMessage,#storeItemRegionPriceErrorMessage").html("").hide();
    $("#store_item_region_price_dialog").modal('show');
    $("#store_region_price_item_id").val(id);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/getregionprices/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#storeItemRegionPriceErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var item_prices = msg.item_prices;
                    var id_str = '';
                    var str = '<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr><th>Region</th><th>Price</th></tr></thead>';
                    for(var i=0;i<item_prices.length;i++){
                        var region_id = item_prices[i].dlim_region_id;
                        if(item_prices[i].price != null) var price = item_prices[i].price;else price = '';
                        var region_price_textbox = '<input type="text" name="region_price_'+region_id+'" id="region_price_'+region_id+'" value="'+price+'" class="form-control">';

                        str+='<tr><td>'+item_prices[i].region_name+'</td><td>'+region_price_textbox+'</td></tr>';
                        id_str+=region_id+",";
                    }
                    str+='</table></div>';
                    $("#store_item_region_price_content").html(str);
                    $("#store_item_region_ids").val(id_str.substring(0,id_str.length-1));
                }
            }else{
                displayResponseError(msg,"storeItemRegionPriceErrorMessage");
            }
        },error:function(obj,status,error){
            $("#storeItemRegionPriceErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateStoreAssetRegionPrices(){
    $("#storeItemRegionPriceSuccessMessage,#storeItemRegionPriceErrorMessage").html("").hide();
    var form_data = $("#storeItemRegionPriceForm").serialize();
    $("#edit-StoreItemRegionPrices-spinner").show();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/store/asset/updateregionprices",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#edit-StoreItemRegionPrices-spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(data.status == 'success'){
                    $("#storeItemRegionPriceSuccessMessage").html(data.message).show();
                    $("#storeItemRegionPriceErrorMessage").html('').hide();
                    setTimeout(function(){  $("#store_item_region_price_dialog").modal('hide'); }, 2000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#storeItemRegionPriceErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(msg,"storeItemRegionPriceErrorMessage");
            }
        },error:function(obj,status,error){
            $("#storeItemRegionPriceErrorMessage").html(error).show();
            $("#edit-StoreItemRegionPrices-spinner").hide();
        }
    });
}

function createAssetsOrder(type){
    var error_msg = '',i=0,selected_items = [];
    
    $(".order-item_"+type).each(function(){
        var item_id = $(this).val();
        var quantity = $(this).parents('.order-items-row').find('.order-item-quantity_'+type).val();
        
        if(item_id != '' && quantity == ''){
           error_msg+='Please provide quantity for: '+asset_names[item_id]+"<br>";
        } 
        
        if(item_id != '' && quantity != '' && selected_items.indexOf(item_id) >= 0){
            error_msg+='Asset is already added: '+asset_names[item_id]+"<br>";
        }
        
        var file_value = $(this).parents('.order-items-row').find('.order-item-picture_'+type).val();
        if(item_id != '' && file_value != '' && !validateFileExt(file_value)){
           error_msg+='Please provide valid Picture type for: '+$(this).find('option:selected').text()+"<br>";
        } 
        
        if(item_id != ''){
            selected_items[i] = $(this).val();
        }
        
        i++;
    });
    
    if(selected_items.length == 0){
        error_msg = 'Please select order assets';
    }
    
    if(error_msg  == ''){
        $("#order_add_submit,#order_add_cancel").attr('disabled',true);
        $("#createOrderFrm").submit();
    }else{
        $("#createOrderErrorMessage").html(error_msg).show();;
        return false;
    }
}

function addAssetOrderRow(type){
    var id= "row_order_item_"+Math.floor(Math.random() * 10000);
    var html = $('<div>'+$("#order_items_row_first").html()+'</div>');
    html.find('.typeahead__result').remove();
    html = '<div class="form-row order-items-row" id="'+id+'">'+html.html()+'</div>';
    //var html = '<div class="form-row order-items-row" id="'+id+'">'+$("#order_items_row_first").html()+'</div>';
    $(".order-items-container").append(html);
    $("#"+id).find('.order-item_'+type+',.order-item-quantity_'+type+',.text-autosuggest').val('');
    $("#"+id).find('.order-item-price,.order-item-unit-price').html('');
    $("#"+id).find('.order-thumb-image,.label-text').remove();
    initializeAutoSuggest('asset',action_type);
}

function updateAssetOrderPrice(type){
    var total_price = 0,grand_total_price = 0;
    
    $(".order-item_"+type).each(function(){
        if($(this).val() != ''){
            var id = $(this).val();
            var qty = $(this).parents('.order-items-row').find('.order-item-quantity_'+type).val();
            if(qty.indexOf(".") >= 0){ 
                $(this).parents('.order-items-row').find('.order-item-quantity_'+type).val(0);
                qty = 0;
            }
            var price = asset_prices[id];
            $(this).parents('.order-items-row').find('.order-item-unit-price').html(currency+" "+price);
            
            if($(this).parents('.order-items-row').find('.order-item-quantity_'+type).val() != ''){
                total_price = price*qty;
                grand_total_price+=total_price;
                $(this).parents('.order-items-row').find('.order-item-price').html(currency+" "+total_price);
            }
        }
    });
    
    $("#order_grand_total").html("Grand Total: "+currency+" "+grand_total_price);
}

$(document).ready(function(){
    if(typeof page_type != 'undefined' && typeof action_type != 'undefined'){
        
        if(page_type == 'demand' && action_type == 'edit'){
            getDemandOrderData();
        }
        if(page_type == 'asset' && action_type == 'edit'){
            getAssetOrderData();
        }
        if(page_type == 'demand' || page_type == 'asset' || page_type == 'push_demand' || page_type == 'search_autosuggest'){
            initializeAutoSuggest(page_type,action_type);
        }
    }    
    
});

function getDemandOrderData(){
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/demand/products-list/"+demand_id,
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    
                } 
            }else{ 
                var products_list = msg.products_list;
                for(var i=0;i<products_list.length;i++){
                    var product_data = {};
                    product_data.id = products_list[i].product_id;
                    product_data.name = products_list[i].product_name+" "+products_list[i].size_name+" "+products_list[i].color_name;
                    product_data.price = products_list[i].sale_price;
                    product_data.quantity = products_list[i].product_quantity;
                    demand_items[product_data.id] = product_data;
                   //demand_prices[products_list[i].product_id] = products_list[i].sale_price;
                   //demand_names[products_list[i].product_id] = products_list[i].product_name;
                }
                getDemandRows();
            }
        },error:function(obj,status,error){
            
        }
    });
}

function getAssetOrderData(){
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/order/products/"+asset_order_id,
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    
                } 
            }else{ 
                var products_list = msg.products_list;
                for(var i=0;i<products_list.length;i++){
                   asset_prices[products_list[i].item_id] = (products_list[i].price == null || products_list[i].price == '')?products_list[i].base_price:products_list[i].price;
                   asset_names[products_list[i].item_id] = products_list[i].item_name;
                }
                updateAssetOrderPrice(action_type);
            }
        },error:function(obj,status,error){
            
        }
    });
}

function initializeAutoSuggest(type,action_type){
    if(type == 'demand' || type == 'search_autosuggest'){
        var url = ROOT_PATH+"/store/demand/autocomplete_data";
    }else if(type == 'push_demand'){
        var url = ROOT_PATH+"/store/demand/autocomplete_data";
    }else if(type == 'asset'){
        var url = ROOT_PATH+"/store/asset/autocomplete_data";
    }
    
    $.typeahead({
        input: '.js-typeahead',
        dynamic:true,
        minLength:3,
        searchOnFocus: true,
        display: ["name"],
        filter:false,
        cache: false,
        maxItem: 10,
        source: {
            ajax: {
                type: "POST",
                url: url,
                path: "products",
                data: {q: "{{query}}"}
            }
        },
        callback: {
            onClick: function (node, a, item, event) {
                if(type == 'demand'){
                    $(node).parents('.demand-items-row').find('.demand-item_'+action_type).val(item.id);
                    demand_list[item.id] = item;
                    $("#product_id_"+action_type).val(item.id);
                    current_demand_item = item;
                    /*demand_prices[item.id] = item.price;
                    demand_names[item.id] = item.name;*/
                    updateDemandPrice(action_type);
                }else if(type == 'asset'){
                    $(node).parents('.order-items-row').find('.order-item_'+action_type).val(item.id);
                    asset_prices[item.id] = item.price;
                    asset_names[item.id] = item.name;
                    updateAssetOrderPrice(action_type);
                }else if(type == 'push_demand'){
                    $(node).parents('.demand-items-row').find('.demand-item_'+action_type).val(item.id);
                    demand_prices[item.id] = item.price;
                    demand_names[item.id] = item.name;
                    demand_inventory[item.id] = item.inventory_count;
                    updatePushDemandPrice(action_type,node);
                }else if(type == 'search_autosuggest'){
                    $(node).parents('.autosuggest-container').find(".p-id-search").val(item.id);
                }
            },
            onSubmit: function (node, form, items, event) {
                //event.preventDefault();
            },
            onCancel: function (node, item, event) {
                if(type == 'search_autosuggest'){
                    $(node).parents('.autosuggest-container').find(".p-id-search").val('');
                }
            }
        },
        debug: false
    });
}

function deleteAssetOrderItem(id){
    $("#deleteOrderItemErrorMessage").html('').hide();
    
    $('#confirm_delete_order_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_rows_btn', function(e) {
        e.preventDefault();
        $("#delete-order_item-spinner").show();
        $("#delete_order_item_btn,#delete_order_item_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id},
            url:ROOT_PATH+"/store/asset/order/deleteitem",
            success: function(msg){	
                $("#delete-order_item-spinner").hide();
                $("#delete_order_item_btn,#delete_order_item_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){    
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteOrderItemErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteOrderItemSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#confirm_delete_order_item").modal('hide');window.location.reload(); }, 2000);
                    }
                }else{
                    displayResponseError(msg,"deleteOrderItemErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteOrderItemErrorMessage").html('Error in processing request').show();
                $("#delete-order_item-spinner").hide();
                $("#delete_order_item_btn,#delete_order_item_cancel").attr('disabled',false);
            }
        });
    });
}

function updateAssetOrderStatus(){
    $("#orderListOverlay").show();
    var items_ids = '';
    $(".order_id-chk").each(function(){
        if($(this).is(":checked")){
            items_ids+= $(this).val()+",";
        }
    });
    
    items_ids = items_ids.substring(0,items_ids.length-1);//alert(items_ids);
    var form_data = "action="+$("#order_action").val()+"&ids="+items_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/order/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    $("#orderListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateOrderStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateOrderStatusSuccessMessage").html(msg.message).show();
                    $("#updateOrderStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#orderListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateOrderStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateOrderStatusErrorMessage").html('Error in processing request').show();
            $("#orderListOverlay").hide();
        }
    });
}

function updateAssetOrder(id,order_status){
    $("#updateOrderStatusErrorMessage,#updateOrderStatusSuccessMessage").html('').hide();
    var form_data = "action="+order_status+"&ids="+id+"&comments="+$("#order_comments").val();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/order/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateOrderStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateOrderStatusSuccessMessage").html(msg.message).show();
                    $("#updateOrderStatusErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateOrderStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateOrderStatusErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitAssetOrderToAccounts(id){
    
    $("#updateOrderStatusErrorMessage,#updateOrderStatusSuccessMessage").html('').hide();
    var form_data = "action=accounts_submitted&ids="+id;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/asset/order/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateOrderStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateOrderStatusSuccessMessage").html(msg.message).show();
                    $("#updateOrderStatusErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateOrderStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateOrderStatusErrorMessage").html('Error in processing request').show();
        }
    });
}

function addDemandRow(type){
    
    var id = $("#product_id_"+type).val();
    var qty = $("#productQuantity_"+type).val();
    
    if(id == ''){
        $("#"+type+"DemandErrorMessage").html('Product is Required Field').show();
        return false;
    }
    
    if(qty == ''){
        $("#"+type+"DemandErrorMessage").html('Quantity is Required Field').show();
        return false;
    }
    
    demand_items[id] = demand_list[id];
    demand_items[id]['quantity'] = qty;
    $("#product_id_"+type).val('');

    $(".demand-item-unit-price").html(currency+" "+current_demand_item.price);
    
    $("#q,#productQuantity_"+type).val('');
    $(".demand-item-unit-price,.demand-item-price").html('');
    
    $("#"+type+"DemandErrorMessage").html('').hide();
    getDemandRows();
    /*var id= "row_order_item_"+Math.floor(Math.random() * 10000);
    var html = $('<div>'+$("#demand_items_row_first").html()+'</div>');
    html.find('.typeahead__result').remove();
    html = '<div class="form-row demand-items-row" id="'+id+'">'+html.html()+'</div>';
    
    $(".demand-items-container").append(html);
    $("#"+id).find('.demand-item_'+type+',.demand-item-quantity_'+type+',.text-autosuggest'+',.demand-item-size_'+type+',.demand_item_inventory_count_'+type).val('');
    
    $("#"+id).find('.demand-item-price,.demand-item-unit-price,.demand-item-inventory-count_'+type).html('');
    $("#"+id).find('.label-text').remove();
    initializeAutoSuggest(page_type,action_type);*/
}

function getDemandRows(){
    var qty_total = 0, unit_price_total = 0, grand_total = 0;
    var str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total Price</th><th>Delete</th></tr></thead><tbody>';
    for(var id in demand_items){
        var total_price = (demand_items[id].price*demand_items[id].quantity).toFixed(2);
        str+='<tr><td>'+demand_items[id].name+'</td><td>'+demand_items[id].quantity+'</td><td>'+currency+ " "+demand_items[id].price+'</td><td>'+currency+ " "+total_price+'</td>\
        <td><button type="button" id="demand_add_submit" name="demand_add_submit" class="btn btn-dialog" value="Submit" onclick="deleteDemandItem('+id+');">X</button></td></tr>';
        
        qty_total+=parseInt(demand_items[id].quantity);
        unit_price_total+=parseFloat(demand_items[id].price);
        grand_total+=(demand_items[id].price*demand_items[id].quantity);
    }
    
    
    str+='<tr><th>Total</th><th>'+qty_total+'</th><th>'+currency+ " "+unit_price_total.toFixed(2)+'</th><th>'+currency+ " "+grand_total.toFixed(2)+'</th><th></th></tr>';
    str+='</tbody></table></div>';
    
    $("#demands_list_div").html(str);
}

function updateDemandPrice(type){
    var total_price = 0,grand_total_price=0;
    
    if(typeof current_demand_item.id != 'undefined'){
        var unit_price = current_demand_item.price;
        $(".demand-item-unit-price").html(currency+" "+unit_price);
        var qty = $("#productQuantity_"+type).val();
        if(qty != ''){
            var total_price = unit_price*qty;
            $(".demand-item-price").html(currency+" "+total_price);
        }
    }
}

function updatePushDemandPrice(type,node){
    //$(".demand-item_"+type).each(function(){
        var elem = $(node).parents('.demand-items-row').find('.demand-item_'+type);
        if($(elem).val() != ''){
            var id = $(elem).val();
            $(elem).parents('.demand-items-row').find('.demand-item-unit-price').html(currency+" "+demand_prices[id]);
            $(elem).parents('.demand-items-row').find('.demand-item-inventory-count_'+type).html(demand_inventory[id]);
            $(elem).parents('.demand-items-row').find('.demand_item_inventory_count_'+type).val(demand_inventory[id]);
            $(elem).parents('.demand-items-row').find('.demand-item-quantity_'+type).val(0);
        }
    //});
}

function updatePushDemandQuantity(type,elem){
    var total_quantity = 0;
    $(".demand-item_"+type).each(function(){
        total_quantity = 0;
        if($(this).val() != ''){
            
            var qty = $(elem).val();
            if(qty.indexOf(".") >= 0){ 
                $(elem).val(0);
            }
            var orig_inventory = parseInt($(this).parents('.demand-items-row').find('.demand_item_inventory_count_'+type).val());
            
            $(this).parents('.demand-items-row').find('.demand-item-quantity_'+type).each(function(){
               var qty = $(this).val();
               if(qty != ''){
                   total_quantity+=parseInt(qty);
               }
            });
            
            var available = orig_inventory-total_quantity;
            if(available < 0){ 
               $(elem).val(0);
                total_quantity = 0;
                $(this).parents('.demand-items-row').find('.demand-item-quantity_'+type).each(function(){
                    var qty = $(this).val();
                    if(qty != ''){
                        total_quantity+=parseInt(qty);
                    }
                });
                
                available = orig_inventory-total_quantity;
            }
            
            $(this).parents('.demand-items-row').find('.demand-item-inventory-count_'+type).html(available);
        }
    });
}

function createDemand(){
    if(Object.keys(demand_items).length == 0){
        $("#addDemandErrorMessage").html('Product is Required Field').show();
        return false;
    }
    
    $("#demand_add_submit,#demand_add_cancel").attr('disabled',true);
    
    ajaxSetup();        
    $.ajax({
        type: "POST",
        data:{demand_items:demand_items},
        url:ROOT_PATH+"/store/demand/create",
        success: function(msg){	
            $("#delete-demand_item-spinner").hide();
            $("#demand_add_submit,#demand_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){        
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  window.location.reload(); }, 2000);
                }
            }else{
                $("#addDemandErrorMessage").html('Error in processing request').show();
            }
        },
        error:function(obj,status,error){
            $("#addDemandErrorMessage").html('Error in processing request').show();
            $("#delete-demand_item-spinner").hide();
            $("#demand_add_submit,#demand_add_cancel").attr('disabled',false);
        }
    });
}

function submitEditDemand(id){
    if(Object.keys(demand_items).length == 0){
        $("#editDemandErrorMessage").html('Product is Required Field').show();
        return false;
    }
    
    $("#demand_edit_submit,#demand_edit_cancel").attr('disabled',true);
    
    ajaxSetup();        
    $.ajax({
        type: "POST",
        data:{demand_items:demand_items},
        url:ROOT_PATH+"/store/demand/edit/"+id,
        success: function(msg){	
            $("#delete-demand_item-spinner").hide();
            $("#demand_edit_submit,#demand_edit_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){        
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  window.location.reload(); }, 2000);
                }
            }else{
                $("#editDemandErrorMessage").html('Error in processing request').show();
            }
        },
        error:function(obj,status,error){
            $("#editDemandErrorMessage").html('Error in processing request').show();
            $("#delete-demand_item-spinner").hide();
            $("#demand_edit_submit,#demand_edit_cancel").attr('disabled',false);
        }
    });
}

function deleteDemandItem(id){
    $("#deleteDemandItemErrorMessage").html('').hide();
    
    $('#confirm_delete_demand_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_rows_btn', function(e) {
        e.preventDefault();
        $("#delete-demand_item-spinner").show();
        $("#delete_demand_item_btn,#delete_demand_item_cancel").attr('disabled',true);
        
        delete(demand_items[id]);
        getDemandRows();
        $("#confirm_delete_demand_item").modal('hide');
        $("#delete-demand_item-spinner").hide();
    });
}

function updateDemandStatus(){
    $("#demandListOverlay").show();
    var demand_ids = '';
    $(".demand_id-chk").each(function(){
        if($(this).is(":checked")){
            demand_ids+= $(this).val()+",";
        }
    });
    
    demand_ids = demand_ids.substring(0,demand_ids.length-1);
    var form_data = "action="+$("#demand_action").val()+"&ids="+demand_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/demandupdatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    $("#demandListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateOrderStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateDemandStatusSuccessMessage").html(msg.message).show();
                    $("#updateDemandStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#demandListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateDemandStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateDemandStatusErrorMessage").html('Error in processing request').show();
            $("#demandListOverlay").hide();
        }
    });
}

function updateDemand(id,demand_status){
    $("#updateDemandStatusErrorMessage,#updateDemandStatusSuccessMessage").html('').hide();
    var form_data = "action="+demand_status+"&ids="+id+"&comments="+$("#demand_comments").val();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/demandupdatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateDemandStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateDemandStatusSuccessMessage").html(msg.message).show();
                    $("#updateDemandStatusErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateDemandStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateDemandStatusErrorMessage").html('Error in processing request').show();
        }
    });
}

function getAssetSubcategories(category_id,type_id,sel_id){
    if(category_id == ''){
        $("#item_subcategory_"+type_id).html('<option value="">Select One</option>');
        return false;
    }
    
    var form_data = "pid="+category_id+"&type=STORE_ASSET_SUBCATEGORY"
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:form_data,
        url:ROOT_PATH+"/design/getlookupitemsdata",
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#"+type_id+"StoreItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var sub_category_list = msg.design_lookup_items;
                    var str = '<option value="">Select One</option>';
                    var sel = '';
                    for(var i=0;i<sub_category_list.length;i++){
                        if(sel_id > 0 && sel_id == sub_category_list[i].id) sel = 'selected';else sel = '';
                        str+='<option '+sel+' value="'+sub_category_list[i].id+'">'+sub_category_list[i].name+'</option>';
                    }
                    $("#item_subcategory_"+type_id).html(str);
                }
            }else{
                displayResponseError(msg,type_id+"StoreItemErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#"+type_id+"StoreItemErrorMessage").html('Error in processing request').show();
        }
    });
}

function addAssetOrderBill(){
    $("#asset_order_bill_add_dialog").find('.form-control').val('');
    $("#asset_order_bill_add_dialog").modal('show');
}

function updateAssetBillFields(payment_type,type){
    if(payment_type == 'online'){
        $(".asset-bank-payment-div-"+type).slideDown('slow');
    }else{
        $(".asset-bank-payment-div-"+type).slideUp('slow');
    }
}

$("#addAssetsBillFrm").on('submit', function(event){
    event.preventDefault(); 
    $("#addAssetsBillSuccessMessage,#addAssetsBillErrorMessage").html('').hide();
    var formData = new FormData(this);
    formData.append('order_id', $("#store_asset_order_id").val());
    formData.append('bill_amount_add', $("#bill_amount_add").val());
    formData.append('bill_payment_method_add', ($("input[name='bill_payment_method_add']").is(':checked'))?$("input[name='bill_payment_method_add']:checked").val():'');
    formData.append('bill_bank_name_add', $("#bill_bank_name_add").val());
    formData.append('bill_bank_account_no_add', $("#bill_bank_account_no_add").val());
    formData.append('bill_bank_ifsc_code_add', $("#bill_bank_ifsc_code_add").val());
    formData.append('bill_bank_customer_name_add', $("#bill_bank_customer_name_add").val());
    formData.append('bill_bank_account_type_add', $("#bill_bank_account_type_add").val());
    
    $("#asset_order_bill_add_spinner").show();
    $("#asset_order_bill_add_cancel,#asset_order_bill_add_submit").attr('disabled',true);
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
        url:ROOT_PATH+"/store/asset/order/bill/add",
        success: function(msg){		
            $("#asset_order_bill_add_spinner").hide();
            $("#asset_order_bill_add_cancel,#asset_order_bill_add_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addAssetsBillErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addAssetsBillSuccessMessage").html(msg.message).show();
                    $("#addAssetsBillErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addAssetsBillErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addAssetsBillErrorMessage").html('Error in processing request').show();
            $("#asset_order_bill_add_spinner").hide();
            $("#asset_order_bill_add_cancel,#asset_order_bill_add_submit").attr('disabled',false);
        }
    });
});

function submitAddAssetOrderBill(){
    $("#addAssetsBillFrm").submit();
}

function editAssetOrderBill(id){
    $("#asset_order_bill_edit_dialog").modal('show');
    $("#store_asset_bill_id").val(id);
   
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/asset/order/bill/data/"+id,
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#editAssetsBillErrorMessage").html(errors).show();
                } 
            }else{ 
                if(objectPropertyExists(msg,'status')){ 
                    var bill_data = msg.bill_data;
                    $("#bill_amount_edit").val(bill_data.bill_amount);
                    $("#bill_bank_name_edit").val(bill_data.vendor_bank_name);
                    $("#bill_bank_customer_name_edit").val(bill_data.vendor_bank_cust_name);
                    $("#bill_bank_account_no_edit").val(bill_data.vendor_bank_acc_no);
                    $("#bill_bank_account_type_edit").val(bill_data.vendor_bank_acc_type);
                    $("#bill_bank_ifsc_code_edit").val(bill_data.vendor_bank_ifsc_code);
                    $("#store_asset_edit_order_id").val(bill_data.order_id);
                    if(bill_data.payment_method == 'online'){
                        $("#bill_payment_method_edit_online").prop('checked',true);
                        $(".asset-bank-payment-div-edit").show();
                    }else{
                        $("#bill_payment_method_edit_cash").prop('checked',true);
                        $(".asset-bank-payment-div-edit").hide();
                    }
                    var img_url = ROOT_PATH+'/images/asset_order_images/'+bill_data.order_id+"/thumbs/"+bill_data.bill_picture;
                    $("#bill_image_edit").attr("src",img_url).show();
                    $("#bill_picture_edit").val('');
                }else{
                    displayResponseError(msg,"editAssetsBillErrorMessage");
                }
            }
        },error:function(obj,status,error){
            $("#editAssetsBillErrorMessage").html('Error in processing request').show();
        }
    });
}

$("#editAssetsBillFrm").on('submit', function(event){
    event.preventDefault(); 
    $("#editAssetsBillSuccessMessage,#editAssetsBillErrorMessage").html('').hide();
    var formData = new FormData(this);
    formData.append('bill_id', $("#store_asset_bill_id").val());
    formData.append('bill_amount_edit', $("#bill_amount_edit").val());
    formData.append('bill_payment_method_edit', ($("input[name='bill_payment_method_edit']").is(':checked'))?$("input[name='bill_payment_method_edit']:checked").val():'');
    formData.append('bill_bank_name_edit', $("#bill_bank_name_edit").val());
    formData.append('bill_bank_account_no_edit', $("#bill_bank_account_no_edit").val());
    formData.append('bill_bank_ifsc_code_edit', $("#bill_bank_ifsc_code_edit").val());
    formData.append('bill_bank_customer_name_edit', $("#bill_bank_customer_name_edit").val());
    formData.append('bill_bank_account_type_edit', $("#bill_bank_account_type_edit").val());
    formData.append('order_id', $("#store_asset_edit_order_id").val());
    
    $("#asset_order_bill_edit_spinner").show();
    $("#asset_order_bill_edit_cancel,#asset_order_bill_edit_submit").attr('disabled',true);
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
        url:ROOT_PATH+"/store/asset/order/bill/update",
        success: function(msg){		
            $("#asset_order_bill_edit_spinner").hide();
            $("#asset_order_bill_edit_cancel,#asset_order_bill_edit_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editAssetsBillErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editAssetsBillSuccessMessage").html(msg.message).show();
                    $("#editAssetsBillErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editAssetsBillErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editAssetsBillErrorMessage").html('Error in processing request').show();
            $("#asset_order_bill_edit_spinner").hide();
            $("#asset_order_bill_edit_cancel,#asset_order_bill_edit_submit").attr('disabled',false);
        }
    });
});

function updateAssetOrderBill(){
    $("#editAssetsBillFrm").submit();
}

function createInitialOrder(){
    $("#initialOrderForm").submit();
}

function addDemandCourier(){
    $("#store_demand_courier_dialog").find('.form-control').val('');
    $("#storeDemandCourierErrorMessage,#storeDemandCourierSuccessMessage").html('').hide();
    $("#store_demand_courier_dialog").modal('show');
}
   
function submitAddDemandCourier(id){
    var form_data = $("#storeDemandCourierForm").serialize();
    
    $("#store_demand_courier_add_spinner").show();      
    $("#store_demand_courier_add_submit,#store_demand_courier_add_cancel").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/courier/add/"+id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#store_demand_courier_add_spinner").hide();      
            $("#store_demand_courier_add_submit,#store_demand_courier_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#storeDemandCourierErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#storeDemandCourierSuccessMessage").html(msg.message).show();
                    $("#storeDemandCourierErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#pos_add_order_dialog").modal('hide');/*window.location.reload();*/ }, 2000);
                    //window.open(msg.pdf_link);
                }
            }else{
                displayResponseError(msg,"storeDemandCourierErrorMessage");
            }
        },error:function(obj,status,error){
            $("#storeDemandCourierErrorMessage").html('Error in processing request').show();
            $("#store_demand_courier_add_spinner").hide();
            $("#store_demand_courier_add_submit,#store_demand_courier_add_cancel").attr('disabled',false);
        }
    });
}

function createPushDemand(){
   var form_data = $("#createPushDemandFrm").serialize();
    $("#push_demand_add_spinner").show();
    $("#push_demand_add_submit,#push_demand_add_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/push/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#push_demand_add_spinner").hide();
            $("#push_demand_add_submit,#push_demand_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addPushDemandSuccessMessage").html(msg.message).show();
                    $("#addPushDemandErrorMessage").html('').hide();
                    setTimeout(function(){  window.location.href = ROOT_PATH+'/warehouse/demand/push/list' }, 2000);
                }
            }else{
                displayResponseError(msg,"addPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addPushDemandErrorMessage").html('Error in processing request').show();
            $("#push_demand_add_spinner").hide();
            $("#push_demand_add_submit,#push_demand_add_cancel").attr('disabled',false);
        }
    });
}

function updatePushDemand(id){
   var form_data = $("#editPushDemandFrm").serialize();
    $("#push_demand_edit_spinner").show();
    $("#push_demand_edit_submit,#push_demand_edit_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/push/edit/"+id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#push_demand_edit_spinner").hide();
            $("#push_demand_edit_submit,#push_demand_edit_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editPushDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editPushDemandSuccessMessage").html(msg.message).show();
                    $("#editPushDemandErrorMessage").html('').hide();
                    setTimeout(function(){  window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPushDemandErrorMessage").html('Error in processing request').show();
            $("#push_demand_edit_spinner").hide();
            $("#push_demand_edit_submit,#push_demand_edit_cancel").attr('disabled',false);
        }
    });
}

function deletePushDemandProduct(dem_id,prod_id){
    $("#deleteDemandItemErrorMessage").html('').hide();
    
    $('#confirm_delete_demand_item').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_rows_btn', function(e) {
        e.preventDefault();
        $("#delete-demand_item-spinner").show();
        $("#delete_demand_item_btn,#delete_demand_item_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{push_demand_id:dem_id,product_id:prod_id},
            url:ROOT_PATH+"/warehouse/demand/push/product/delete",
            success: function(msg){	
                $("#delete-demand_item-spinner").hide();
                $("#delete_demand_item_btn,#delete_demand_item_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteDemandItemErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteDemandItemSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#confirm_delete_demand_item").modal('hide');window.location.reload(); }, 2000);
                    }
                }else{
                    displayResponseError(msg,"deleteDemandItemErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteDemandItemErrorMessage").html('Error in processing request').show();
                $("#delete-demand_item-spinner").hide();
                $("#delete_demand_item_btn,#delete_demand_item_cancel").attr('disabled',false);
            }
        });
    });
}

function updatePushDemandStatus(){
    $("#demandListOverlay").show();
    var demand_ids = '';
    $(".demand_id-chk").each(function(){
        if($(this).is(":checked")){
            demand_ids+= $(this).val()+",";
        }
    });
    
    demand_ids = demand_ids.substring(0,demand_ids.length-1);
    var form_data = "action="+$("#demand_action").val()+"&ids="+demand_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/push/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    $("#demandListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateDemandStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateDemandStatusSuccessMessage").html(msg.message).show();
                    $("#updateDemandStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#demandListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateDemandStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateDemandStatusErrorMessage").html('Error in processing request').show();
            $("#demandListOverlay").hide();
        }
    });
}

function updatePushDemand(id,demand_status){
    $("#updatePushDemandStatusErrorMessage,#updatePushDemandStatusSuccessMessage").html('').hide();
    var form_data = "action="+demand_status+"&ids="+id+"&comments="+$("#demand_comments").val();//alert(form_data);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/demand/push/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){ 
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updatePushDemandStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updatePushDemandStatusSuccessMessage").html(msg.message).show();
                    $("#updatePushDemandStatusErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updatePushDemandStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updatePushDemandStatusErrorMessage").html('Error in processing request').show();
        }
    });
}

function addPosInventory(){
   var form_data = $("#addPosInventoryFrm").serialize();
    $("#add_pos_inventory_spinner").show();
    $("#pos_add_inventory_submit,#pos_add_inventory_cancel").attr('disabled',true);
    $("#addPosInventoryErrorMessage,#addPosInventorySuccessMessage").html('').hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/warehouse/pos/inventory/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#add_pos_inventory_spinner").hide();
            $("#pos_add_inventory_submit,#pos_add_inventory_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addPosInventorySuccessMessage").html(msg.message).show();
                    $("#addPosInventoryErrorMessage").html('').hide();
                    setTimeout(function(){  window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"addPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addPosInventoryErrorMessage").html('Error in processing request').show();
            $("#add_pos_inventory_spinner").hide();
            $("#pos_add_inventory_submit,#pos_add_inventory_cancel").attr('disabled',false);
        }
    });
}

var inventory_products_inv_push_demand = [],  rec_per_page_inv_push_demand = '', page_global_inv_push_demand = 1,inventory_data_push_demand = {},inv_push_barcode = '',inv_push_time = 0;;

function getInventoryPushDemandProductData(barcode){
    $("#piece_barcode").attr('disabled',true).attr('readonly',true);
    var demand_id = $("#demand_id").val();
    
    if(barcode == '' || barcode.length < 6){
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
        url:ROOT_PATH+"/store/demand/inventory-push/edit/"+demand_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,demand_id:demand_id},
        success:function(msg){
            
            $("#importPosInventoryErrorMessage,#importPosInventorySuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode").val('').attr('disabled',false).attr('readonly',false).focus();  }, 1000);
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
                    $("#piece_cost").val(product_data_inv_push_demand.store_base_rate);
                    $("#intake_date").val(product_data_inv_push_demand.intake_date);
                    $("#piece_id").val(product_data_inv_push_demand.id);
                    $("#product_id").val(product_data_inv_push_demand.product_master_id);
                    $("#importPosInventoryErrorMessage").html('').hide();
                    
                    product_data_inv_push_demand.product_status = 4;
                    product_data_inv_push_demand.store_intake_date = getCurrentDate(2);
                    inventory_products_inv_push_demand.push(product_data_inv_push_demand);
                    inventory_data_push_demand.inventory_received = parseInt(inventory_data_push_demand.inventory_received)+1;
                    displayInventoryPushDemandInventoryData();
                    
                    setTimeout(function(){ 
                         var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#importPosInventorySuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#importPosInventorySuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#piece_barcode").val('').attr('disabled',false).attr('readonly',false).focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
                $("#piece_barcode").attr('disabled',false).attr('readonly',false);
            }
            
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
            $("#piece_barcode").attr('disabled',false).attr('readonly',false);
        }
    });
}

$("#piece_barcode").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#piece_barcode").val();
        getInventoryPushDemandProductData(val);
    }, 500);
});

function displayInventoryPushDemandInventoryData(){
    var str = '';
    str+='Inventory Received: '+inventory_data_push_demand.inventory_total+" | "+'Inventory Loaded: '+inventory_data_push_demand.inventory_received;
    str+='<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_push_demand" id="barcode_search_inv_push_demand" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_push_demand" id="sku_search_inv_push_demand" class="form-control"><option value="">SKU</option></select></div><div class="col-md-1"><button type="button" id="pos_inv_push_demand_search" name="pos_inv_push_demand_search" class="btn btn-dialog" value="Submit" onclick="searchPushDemandInv();">Search</button></div></div><div class="separator-10"></div>';
    str+='<div class="separator-10"></div><div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>SKU</th><th>Cost Price</th><th>Sale Price</th><th>Store Intake Date</th><th>Status</th></tr></thead><tbody>'
    
    if(inventory_products_inv_push_demand.length > 0){    
        var start = parseInt((page_global_inv_push_demand-1)*rec_per_page_inv_push_demand)+1;
        for(var i=0;i<inventory_products_inv_push_demand.length;i++){
            var product_data_inv_push_demand = inventory_products_inv_push_demand[i];
            var store_intake_date = formatDate(product_data_inv_push_demand.store_intake_date);
            var sku = (product_data_inv_push_demand.vendor_sku != null)?product_data_inv_push_demand.vendor_sku:product_data_inv_push_demand.product_sku;
            str+='<tr><td>'+(start+i)+'</td><td>'+product_data_inv_push_demand.peice_barcode+'</td><td>'+product_data_inv_push_demand.product_name+'</td><td>'+product_data_inv_push_demand.size_name+'</td><td>'+product_data_inv_push_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_push_demand.store_base_price+'</td><td>'+product_data_inv_push_demand.sale_price+'</td><td>'+store_intake_date+'</td><td>'+getProductInventoryStatus(product_data_inv_push_demand.product_status)+'</td></tr>';
        }
    }else{
        str+='<tr><td colspan="12" align="center">No Records</td></tr>';
    }       
    
    str+='</tbody></table></div>';
    $("#products_imported_list").html(str);
}

function loadInventoryPushDemandInventory(page){
    var demand_id = $("#demand_id").val();
    var barcode = $("#barcode_search_inv_push_demand").val();
    var product_id = $("#sku_search_inv_push_demand").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-push/edit/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page,barcode:barcode,product_id:product_id,page_type:page_type},
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
                    paging_links+=getAjaxPagingLinks(msg.product_list);
                    
                    var inv_count_data = msg.inv_count_data;
                    var sku_list = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    inventory_products_inv_push_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_push_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_push_demand = msg.rec_per_page;
                    page_global_inv_push_demand = page;
                    
                    inventory_data_push_demand.inventory_total = inv_count_data.inventory_total;
                    inventory_data_push_demand.inventory_received = inv_count_data.inventory_received;
                    
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

function searchPushDemandInv(){
    $("#pos_inv_push_demand_search").attr('disabled',true);
    loadInventoryPushDemandInventory(1);
    $("#pos_inv_push_demand_search").attr('disabled',false);
}

function closeInventoryPushDemand(){
    $("#closeInventoryPushDemandSuccessMessage,#closeInventoryPushDemandErrorMessage").html('').hide();
    $("#courier_detail,#vehicle_detail").val('');
    
    var store_id = $("#store_id").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-push/edit/"+demand_id+"?action=get_inventory_count_data",
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
                    $("#close_demand_inv_rec").val(msg.inv_data.inv_total);
                    $("#close_demand_inv_loaded").val(msg.inv_data.rec_count);
                    $("#close_demand_base_price").val(msg.inv_data.rec_sale_store_base_price);
                    $("#close_demand_sale_price").val(msg.inv_data.rec_sale_price_sum);
                    $("#closeInventoryPushDemandDialog").modal('show');
                }
            }else{
                displayResponseError(msg,"importPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#importPosInventoryErrorMessage").html('Error in processing request').show();
            
        }
    });
}

function submitCloseInventoryPushDemand(){
    var demand_id = $("#demand_id").val();
    var comments_close_demand = $("#comments_close_demand").val();
    $("#closeInventoryPushDemandSpinner").show();
    $("#closeInventoryPushDemandCancel,#closeInventoryPushDemandSubmit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-push/edit/"+demand_id+"?action=close_demand",
        method:"POST",
        data:{comments_close_demand:comments_close_demand},
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
                    var url = ROOT_PATH+"/store/demand/inventory-push/detail/"+demand_id;
                    setTimeout(function(){  $("#closeInventoryPushDemandDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"closeInventoryPushDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryPushDemandErrorMessage").html('Error in processing request').show();
            $("#closeInventoryPushDemandSpinner").hide();
            $("#closeInventoryPushDemandCancel,#closeInventoryPushDemandSubmit").attr('disabled',false);
        }
    });
}

function updatePushDemandDebitNote(){
    var demand_id = $("#demand_id").val();
    var inv_ids = '';
    $(".debit-note-chk").each(function(){
        if($(this).is(":checked")){
            inv_ids+=$(this).val()+",";
        } 
    });
    
    inv_ids = inv_ids.substring(0,inv_ids.length-1);
    $("#debite_note_submit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-push/debit-note/"+demand_id+"?action=update_debit_note",
        method:"POST",
        data:{demand_id:demand_id,inv_ids:inv_ids},
        success:function(msg){
            $("#debit_note_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateDebitNoteErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateDebitNoteSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-push/detail/"+demand_id;
                    setTimeout(function(){  window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"updateDebitNoteErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateDebitNoteErrorMessage").html('Error in processing request').show();
            $("#debit_note_submit").attr('disabled',false);
        }
    });
}

function cancelPushDemandDebitNote(id){
    $("#cancelPushDemandDebitNoteSuccessMessage,#cancelPushDemandDebitNoteErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_debit_note_id").val(id);
    $("#push_demand_debit_note_cancel_dialog").modal('show');
}

function submitCancelPushDemandDebitNote(){
    $("#cancelPushDemandDebitNoteSuccessMessage,#cancelPushDemandDebitNoteErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPushDemandDebitNoteSubmit,#cancelPushDemandDebitNoteCancel").attr('disabled',true);
    var comments = $("#push_demand_debit_note_cancel_comments").val();
    var debit_note_id = $("#push_demand_debit_note_id").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-push/debit-note/"+demand_id+"?action=cancel_debit_note",
        method:"POST",
        data:{debit_note_id:debit_note_id,comments:comments,demand_id:demand_id},
        success:function(msg){
            $("#cancelPushDemandDebitNoteSubmit,#cancelPushDemandDebitNoteCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#cancelPushDemandDebitNoteErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelPushDemandDebitNoteSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#push_demand_debit_note_cancel_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelPushDemandDebitNoteErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelPushDemandDebitNoteSubmit,#cancelPushDemandDebitNoteCancel").attr('disabled',false);
            $("#cancelPushDemandDebitNoteErrorMessage").html('Error in processing request').show();
        }
    });
}

function createReturnInventoryDemand(){
    $("#createReturnInventoryErrorMessage,#createReturnInventorySuccessMessage,.invalid-feedback").html('').hide();
    $("#push_demand_add,#inv_type_add").val('');
    $("#confirm_create_return_inventory_demand_dialog").modal('show');
}

function submitCreateReturnInventoryDemand(){
    $("#createReturnInventoryErrorMessage,#createReturnInventorySuccessMessage,.invalid-feedback").html('').hide();
    $("#createReturnInvDemandSubmit,#createReturnInvDemandCancel").attr('disabled',true);
    $("#create_return_inventory_demand_spinner").show();
    
    var push_demand_add = $("#push_demand_add").val();
    var inv_type_add = $("#inv_type_add").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-return/list?action=create_demand",
        method:"POST",
        data:{push_demand_add:push_demand_add,inv_type_add:inv_type_add},
        success:function(msg){
            $("#createReturnInvDemandSubmit").attr('disabled',false);
            $("#create_return_inventory_demand_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#createReturnInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var demand_id = msg.demand_detail.id;
                    $("#createReturnInventorySuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id;
                    setTimeout(function(){  $("#confirm_create_return_inventory_demand_dialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"createReturnInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#createReturnInvDemandSubmit,#createReturnInvDemandCancel").attr('disabled',false);
            $("#create_return_inventory_demand_spinner").hide();
            $("#createReturnInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}


var inventory_products_inv_return_demand = [],  rec_per_page_inv_return_demand = '', page_global_inv_return_demand = 1,inv_return_barcode = '',inv_return_time = '';

function getInventoryReturnhDemandProductData(barcode){
    $("#inv_return_piece_barcode").attr('disabled',true).attr('readonly',true);
    var demand_id = $("#demand_id").val();
    
    if(barcode == ''){
        $("#returnPosInventoryErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
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
        url:ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id+"?action=get_inventory_product",
        method:"GET",
        data:{barcode:barcode,demand_id:demand_id},
        success:function(msg){
            $("#returnPosInventoryErrorMessage,#returnPosInventorySuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#inv_return_piece_barcode").val('').attr('disabled',false).attr('readonly',false).focus();  }, 1000);
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
                    $("#piece_cost").val(product_data_inv_return_demand.store_base_price);
                    $("#intake_date").val(product_data_inv_return_demand.intake_date);
                    $("#piece_id").val(product_data_inv_return_demand.id);
                    $("#product_id").val(product_data_inv_return_demand.product_master_id);
                    $("#returnPosInventoryErrorMessage").html('').hide();
                    
                    product_data_inv_return_demand.product_status = 4;
                    product_data_inv_return_demand.store_intake_date = getCurrentDate(2);
                    inventory_products_inv_return_demand.push(product_data_inv_return_demand);
                    displayInventoryReturnDemandInventoryData();
                    
                    setTimeout(function(){ 
                        var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#returnPosInventorySuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#returnPosInventorySuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#inv_return_piece_barcode").val('').attr('disabled',false).attr('readonly',false).focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"returnPosInventoryErrorMessage");
                $("#inv_return_piece_barcode").attr('disabled',false).attr('readonly',false);
            }
        },error:function(obj,status,error){
            $("#returnPosInventoryErrorMessage").html('Error in processing request').show();
            $("#inv_return_piece_barcode").attr('disabled',false).attr('readonly',false);
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
    
    str+='<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_return_demand" id="barcode_search_inv_return_demand" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_return_demand" id="sku_search_inv_return_demand" class="form-control"><option value="">SKU</option></select></div>';
    str+='<div class="col-md-2">';
    str+='<button type="button" id="pos_inv_return_demand_search" name="pos_inv_return_demand_search" class="btn btn-dialog" value="Submit" onclick="searchReturnDemandInv();">Search</button>';
    str+='</div></div><div class="separator-10"></div>';
    str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>Store Out Date</th><th>Push Demand</th><th>Status</th></tr></thead><tbody>'
    
    if(inventory_products_inv_return_demand.length > 0){    
        var start = parseInt((page_global_inv_return_demand-1)*rec_per_page_inv_return_demand)+1;
        for(var i=0;i<inventory_products_inv_return_demand.length;i++){
            var product_data_inv_return_demand = inventory_products_inv_return_demand[i];
            var store_intake_date = (product_data_inv_return_demand.arnon_inventory == 0)?formatDate(product_data_inv_return_demand.store_intake_date):'';
            var id = product_data_inv_return_demand.id;
            
            str+='<tr>';
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="inv-return-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            
            var sku = (product_data_inv_return_demand.arnon_inventory == 0)?product_data_inv_return_demand.vendor_sku:product_data_inv_return_demand.product_sku;
            var push_demand_no = (product_data_inv_return_demand.push_demand_no != null)?product_data_inv_return_demand.push_demand_no:'';
            
            str+='<td>'+product_data_inv_return_demand.peice_barcode+'</td><td>'+product_data_inv_return_demand.product_name+'</td><td>'+product_data_inv_return_demand.size_name+'</td><td>'+product_data_inv_return_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_return_demand.store_base_price+'</td><td>'+store_intake_date+'</td><td>'+push_demand_no+'</td><td>'+getProductInventoryStatus(product_data_inv_return_demand.product_status)+'</td></tr>';
        }
    }    
    
    str+='</tbody></table></div>';
    
    $("#products_imported_list").html(str);
}

function searchReturnDemandInv(){
    $("#pos_inv_return_demand_search").attr('disabled',true);
    loadInventoryReturnDemandInventory(1);
    $("#pos_inv_return_demand_search").attr('disabled',false);
}

function loadInventoryReturnDemandInventory(page){
    var demand_id = $("#demand_id").val();
    var barcode    = $("#barcode_search_inv_return_demand").val();
    var product_id = $("#sku_search_inv_return_demand").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page,barcode:barcode,product_id:product_id},
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
                    var sku_list = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    inventory_products_inv_return_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_return_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_return_demand = msg.rec_per_page;
                    page_global_inv_return_demand = page;
                    displayInventoryReturnDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    for(var i=0;i<sku_list.length;i++){
                        sku_option_str+='<option value="'+sku_list[i].id+'">'+sku_list[i].product_sku+" - "+sku_list[i].size_name+" ("+sku_list[i].inv_count+")"+'</option>';
                    }
                    
                    $("#sku_search_inv_return_demand").html(sku_option_str);
                    $("#sku_search_inv_return_demand").val(product_id);
                    $("#barcode_search_inv_return_demand").val(barcode);
                    
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
        url:ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id+"?action=get_inventory_count_data",
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
                    var demand_data = msg.demand_data;
                    var store_data = msg.store_data;
                    var total_data = msg.total_data;
                    $("#demand_prev_type").val('Store to Warehouse');
                    $("#demand_prev_date_created").val(displayDate(demand_data.created_at));
                    $("#demand_prev_store_name").val(store_data.store_name);
                    $("#demand_prev_store_gst_no").val(store_data.gst_no);
                    $("#demand_prev_total_inv").val(total_data.total_qty);
                    $("#demand_prev_taxable_value").val(total_data.total_taxable_val.toFixed(2));
                    $("#demand_prev_gst_amount").val(total_data.total_gst_amt.toFixed(2));
                    $("#demand_prev_total_amt").val(total_data.total_value.toFixed(2));
                    $("#demand_prev_total_sale_price").val(total_data.total_sale_price.toFixed(2));
                    
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
        url:ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id+"?action=close_demand",
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

function deleteInventoryReturnDemandItems(){
    var store_id = $("#store_id").val();
    var demand_id = $("#demand_id").val();
    
    var chk_class = 'inv-return-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#return_demand_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#deleteReturnDemandItemsErrorMessage,#deleteReturnDemandItemsSuccessMessage").html('').hide();
    
    $('#return_demand_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_return_demand_items_btn', function(e) {
        e.preventDefault();
        $("#delete_return_demand_items_btn,#delete_return_demand_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,store_id:store_id,demand_id:demand_id},
            url:ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id+"?action=delete_inv_return_items",
            success: function(msg){	
                $("#delete_inventory_qc_items_btn,#delete_inventory_qc_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteReturnDemandItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteReturnDemandItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#return_demand_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteReturnDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteReturnDemandItemsErrorMessage").html('Error in processing request').show();
                $("#delete_return_demand_items_btn,#delete_return_demand_items_cancel").attr('disabled',false);
            }
        });
    });
}

function cancelInventoryReturnDemand(){
    $("#cancelInventoryReturnDemandSuccessMessage,#cancelInventoryReturnDemandErrorMessage").html('').hide();
    $("#inventory_return_demand_cancel_dialog").modal('show');
}

function submitCancelInventoryReturnDemand(){
    var demand_id = $("#demand_id").val();
    var comments_cancel_demand = $("#cancel_comments").val();
    
    $("#cancelInventoryReturnDemandCancel,#cancelInventoryReturnDemandSubmit").attr('disabled',true);
    $("#cancelInventoryReturnDemandSpinner").show();
    $("#cancelInventoryReturnDemandErrorMessage,#cancelInventoryReturnDemandSuccessMessage").html('').hide();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-return/detail/"+demand_id+"?action=cancel_demand",
        method:"POST",
        data:{comments_cancel_demand:comments_cancel_demand,demand_id:demand_id},
        success:function(msg){
            $("#cancelInventoryReturnDemandSubmit,#cancelInventoryReturnDemandCancel").attr('disabled',false);
            $("#cancelInventoryReturnDemandSpinner").hide();
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#cancelInventoryReturnDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#cancelInventoryReturnDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-return/detail/"+demand_id;
                    setTimeout(function(){  $("#inventory_return_demand_cancel_dialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"cancelInventoryReturnDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#cancelInventoryReturnDemandErrorMessage").html('Error in processing request').show();
            $("#cancelInventoryReturnDemandSubmit,#cancelInventoryReturnDemandCancel").attr('disabled',false);
            $("#cancelInventoryReturnDemandSpinner").hide();
        }
    });
}

function createCompleteReturnInventoryDemand(){
    $("#createReturnInventoryErrorMessage,#createReturnInventorySuccessMessage").html('').hide();
    $("#comments_return_inv").val('');
    $("#confirm_create_return_inventory_demand_dialog").modal('show');
}

function submitCreateCompleteReturnInventoryDemand(){
    $("#createReturnInvDemandSubmit,#createReturnInvDemandCancel").attr('disabled',true);
    $("#create_return_inventory_demand_spinner").show();
    var comments_return_inv = $("#comments_return_inv").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-return-complete/list?action=create_demand",
        method:"POST",
        data:{comments_return_inv:comments_return_inv},
        success:function(msg){
            $("#createReturnInvDemandSubmit,#createReturnInvDemandCancel").attr('disabled',false);
            $("#create_return_inventory_demand_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#createReturnInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var demand_id = msg.demand_detail.id;
                    $("#createReturnInventorySuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-return-complete/detail/"+demand_id;
                    setTimeout(function(){  $("#confirm_create_return_inventory_demand_dialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"createReturnInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#createReturnInvDemandSubmit,#createReturnInvDemandCancel").attr('disabled',false);
            $("#create_return_inventory_demand_spinner").hide();
            $("#createReturnInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

var inv_review_barcode = '',inv_review_time = '';
function checkInventoryReviewProduct(){
    $(".import-data").val('');
    $("#piece_reason,#reviewPosInventoryErrorMessage").html('').hide();
    
    var barcode = $("#inv_review_piece_barcode").val();
    if(barcode == ''){
        $("#reviewPosInventoryErrorMessage").html("Barcode is Required Field").show();
        return false;
    }
    
    //if(barcode.length != 16 && barcode.length != 19){
    if(barcode.length < 6){
        $("#reviewPosInventoryErrorMessage").html("Invalid Barcode").show();
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_review_barcode != '' && inv_review_barcode == barcode && Math.abs((inv_review_time) - time)/1000 < 1){
        return false;
    }
    
    inv_review_barcode = barcode;
    inv_review_time = time;
    
    $("#pos_review_inventory_submit").attr('disabled',true);
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/inventory/review?action=check_product",
        method:"GET",
        data:{barcode:barcode},
        success:function(msg){
            $("#pos_review_inventory_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#reviewPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_data = msg.product_data;
                    $("#piece_barcode").val(product_data.peice_barcode);
                    $("#piece_product_name").val(product_data.product_name);
                    $("#piece_size").val(product_data.size_name);
                    $("#piece_color").val(product_data.color_name);
                    $("#product_sku").val(product_data.product_sku);
                    //$("#piece_cost").val(product_data.store_base_price);
                    $("#piece_store").val(product_data.store_name);
                    $("#piece_status").val(product_data.product_status_text);
                    $("#piece_reason").html(product_data.reason_str).show();
                    $("#inv_review_piece_barcode").val('');
                    $("#product_data_row").css('display','flex');
                    loadInventoryReviewData(1);
                }
            }else{
                displayResponseError(msg,"reviewPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#pos_review_inventory_submit").attr('disabled',false);
            $("#reviewPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
    
}

function loadInventoryReviewData(page){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/inventory/review?action=load_inventory",
        method:"GET",
        data:{page:page},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#reviewPosInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;
                    var paging_links = msg.paging_links;
                    
                    var str = '';
                    if(product_list.length > 0){
                        str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>Piece Barcode</th><th>Product</th>';
                        str+='<th>Size</th><th>Color</th><th>SKU</th><!--<th>Cost</th>--><th>Store</th><th>Status</th><th>Date</th><th>Reason</th></tr></thead><tbody>'

                        for(var i=0;i<product_list.length;i++){
                            var product_data = product_list[i];
                            
                            var barcode = (product_data.peice_barcode != null)?product_data.peice_barcode:''
                            var product_name = (product_data.product_name != null)?product_data.product_name:''
                            var size_name = (product_data.size_name != null)?product_data.size_name:''
                            var color_name = (product_data.color_name != null)?product_data.color_name:''
                            var vendor_sku = (product_data.vendor_sku != null)?product_data.vendor_sku:''
                            var store_base_price = (product_data.store_base_price != null)?product_data.store_base_price:''
                            var store_name = (product_data.store_name != null)?product_data.store_name:''
                            var created_at = displayDate(product_data.str_created_at);
                            
                            str+='<tr><td>'+barcode+'</td><td>'+product_name+'</td><td>'+size_name+'</td><td>'+color_name+'</td>\
                            <td>'+vendor_sku+'</td><!--<td>'+store_base_price+'</td>--><td>'+store_name+'</td><td>'+getProductInventoryStatus(product_data.product_status)+'</td><td>'+created_at+'</td><td>'+product_data.reason_str+'</td></tr>';
                        }

                        str+='</tbody></table></div>';
                    }
                    
                    $("#products_list").html(str);
                    $("#products_paging_links").html(paging_links);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadInventoryReviewData(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadInventoryReviewData(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"reviewPosInventoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#reviewPosInventoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function createInventoryTransferStoreDemand(){
    $("#addInventoryTransferStoreDemandSuccessMessage,#addInventoryTransferStoreDemandErrorMessage").html('').hide();
    $("#store_id").val('');
    $("#add_inventory_transfer_store_demand_dialog").modal('show');
}

function submitCreateInventoryTransferStoreDemand(){
    $("#add_inventory_transfer_store_demand_spinner").show();
    $("#inventory_transfer_store_demand_add_submit,#inventory_transfer_store_demand_add_cancel").attr('disabled',true);
    var store_id = $("#store_id").val();
    var transfer_field = $("#transfer_field").val();
    var transfer_percent = $("#transfer_percent").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/list?action=create_demand",
        method:"GET",
        data:{store_id:store_id,transfer_field:transfer_field,transfer_percent:transfer_percent},
        success:function(msg){
            $("#add_inventory_transfer_store_demand_spinner").hide();
            $("#inventory_transfer_store_demand_add_submit,#inventory_transfer_store_demand_add_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addInventoryTransferStoreDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addInventoryTransferStoreDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+msg.demand_details.id;
                    setTimeout(function(){  $("#add_inventory_transfer_store_demand_dialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"addInventoryTransferStoreDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_inventory_transfer_store_demand_spinner").hide();
            $("#inventory_transfer_store_demand_add_submit,#inventory_transfer_store_demand_add_cancel").attr('disabled',false);
            $("#addInventoryTransferStoreDemandErrorMessage").html('Error in processing request').show();
        }
    });
}


var inventory_products_inv_transfer_store_demand = [],  rec_per_page_inv_transfer_store_demand = '', page_global_inv_transfer_store_demand = 1,inventory_data_transfer_store_demand = {},inv_transfer_store_barcode = '',inv_transfer_store_time = 0;

function getInventoryTransferStoreDemandProductData(barcode){
    $("#piece_barcode_inv_transfer_store_demand").attr('disabled',true).attr('readonly',true);
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    if(barcode == ''){
        $("#inventoryTransferStoreDemandErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    
    if(barcode.length < 6){
        $("#inventoryTransferStoreDemandErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_transfer_store_barcode != '' && inv_transfer_store_barcode == barcode && Math.abs((inv_transfer_store_time) - time)/1000 < 1){
        return false;
    }
    
    inv_transfer_store_barcode = barcode;
    inv_transfer_store_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,demand_id:demand_id,store_id:store_id},
        success:function(msg){
            $("#inventoryTransferStoreDemandErrorMessage,#inventoryTransferStoreDemandSuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode_inv_transfer_store_demand").val('').attr('disabled',false).attr('readonly',false).focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#inventoryTransferStoreDemandErrorMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#inventoryTransferStoreDemandErrorMessage").html(errors).show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_inv_transfer_store_demand = msg.product_data;
                    $("#product_name").val(product_data_inv_transfer_store_demand.product_name);
                    $("#piece_size").val(product_data_inv_transfer_store_demand.size_name);
                    $("#piece_color").val(product_data_inv_transfer_store_demand.color_name);
                    $("#piece_vendor").val('');
                    $("#piece_po_number").val('');
                    $("#product_sku").val(product_data_inv_transfer_store_demand.vendor_sku);
                    $("#piece_cost").val(product_data_inv_transfer_store_demand.base_price);
                    $("#intake_date").val(product_data_inv_transfer_store_demand.intake_date);
                    $("#piece_id").val(product_data_inv_transfer_store_demand.id);
                    $("#product_id").val(product_data_inv_transfer_store_demand.product_master_id);
                    
                    product_data_inv_transfer_store_demand.product_status = 2;
                    product_data_inv_transfer_store_demand.store_assign_date = getCurrentDate(2);
                    inventory_products_inv_transfer_store_demand.push(product_data_inv_transfer_store_demand);
                    inventory_data_transfer_store_demand.inventory_count = parseInt(inventory_data_transfer_store_demand.inventory_count)+1;
                    displayInventoryTransferStoreDemandInventoryData();
                    
                    setTimeout(function(){ 
                         var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#inventoryTransferStoreDemandSuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#inventoryTransferStoreDemandSuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#piece_barcode_inv_transfer_store_demand").val('').attr('disabled',false).attr('readonly',false).focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"inventoryTransferStoreDemandErrorMessage");
                $("#piece_barcode_inv_transfer_store_demand").attr('disabled',false).attr('readonly',false);
            }
        },error:function(obj,status,error){
            $("#inventoryTransferStoreDemandErrorMessage").html('Error in processing request').show();
            $("#piece_barcode_inv_transfer_store_demand").attr('disabled',false).attr('readonly',false);
        }
    });
}

$("#piece_barcode_inv_transfer_store_demand").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#piece_barcode_inv_transfer_store_demand").val();
        getInventoryTransferStoreDemandProductData(val);
    }, 500);
});

function displayInventoryTransferStoreDemandInventoryData(){
    var str = '';
    str+='Total Inventory: '+inventory_data_transfer_store_demand.inventory_count;
    str+='<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_transfer_store_demand" id="barcode_search_inv_transfer_store_demand" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_transfer_store_demand" id="sku_search_inv_transfer_store_demand" class="form-control"><option value="">SKU</option></select></div><div class="col-md-1"><button type="button" id="pos_inv_transfer_store_demand_search" name="pos_inv_transfer_store_demand_search" class="btn btn-dialog" value="Submit" onclick="searchTransferStoreDemandInv();">Search</button></div></div><div class="separator-10"></div>';
    str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>Out Date</th><th>Status</th><th>Type</th></tr></thead><tbody>';
    
    if(inventory_products_inv_transfer_store_demand.length > 0){    
        var start = parseInt((page_global_inv_transfer_store_demand-1)*rec_per_page_inv_transfer_store_demand)+1;
        for(var i=0;i<inventory_products_inv_transfer_store_demand.length;i++){
            var product_data_inv_transfer_store_demand = inventory_products_inv_transfer_store_demand[i];
            var store_assign_date = formatDate(product_data_inv_transfer_store_demand.store_assign_date);
            var id = product_data_inv_transfer_store_demand.id;
            var inv_type = (product_data_inv_transfer_store_demand.arnon_inventory == 1)?INV_TYPE_ARNON:INV_TYPE_NORTH;
            var sku = (product_data_inv_transfer_store_demand.vendor_sku != null)?product_data_inv_transfer_store_demand.vendor_sku:product_data_inv_transfer_store_demand.product_sku
            
            str+='<tr>';
            if(page_type == 'edit'){
                str+='<td> <input type="checkbox" class="inv-transfer-store-demand-chk" name="chk_'+id+'" id="chk_'+id+'" value="'+id+'"> &nbsp;'+(start+i)+'</td>';
            }else{
                str+='<td> '+(start+i)+'</td>';
            }
            str+='<td>'+product_data_inv_transfer_store_demand.peice_barcode+'</td><td>'+product_data_inv_transfer_store_demand.product_name+'</td><td>'+product_data_inv_transfer_store_demand.size_name+'</td><td>'+product_data_inv_transfer_store_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_transfer_store_demand.spdi_store_base_price+'</td><td>'+store_assign_date+'</td><td>'+getProductInventoryStatus(product_data_inv_transfer_store_demand.product_status)+'</td><td>'+inv_type+'</td></tr>';
        }
    }else{
        str+='<tr><td colspan="10" align="center">No Records</td></tr>';
    }    
    
    str+='</tbody></table></div>';
    
    $("#products_imported_list").html(str);
}

function searchTransferStoreDemandInv(){
    $("#pos_inv_transfer_store_demand_search").attr('disabled',true);
    loadInventoryTransferStoreDemandInventory(1);
    $("#pos_inv_transfer_store_demand_search").attr('disabled',false);
}

function loadInventoryTransferStoreDemandInventory(page){
    var demand_id = $("#demand_id").val();
    var barcode = $("#barcode_search_inv_transfer_store_demand").val();
    var product_id = $("#sku_search_inv_transfer_store_demand").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page,barcode:barcode,product_id:product_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#inventoryTransferStoreDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;//alert(msg);
                    var paging_links = msg.paging_links;
                    paging_links+=getAjaxPagingLinks(msg.product_list);
                    var sku_list = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    
                    inventory_products_inv_transfer_store_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_transfer_store_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_transfer_store_demand = msg.rec_per_page;
                    page_global_inv_transfer_store_demand = page;
                    inventory_data_transfer_store_demand.inventory_count = msg.inventory_count;
                    displayInventoryTransferStoreDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    for(var i=0;i<sku_list.length;i++){
                        var sku = (sku_list[i].vendor_sku != null)?sku_list[i].vendor_sku:sku_list[i].product_sku;
                        sku_option_str+='<option value="'+sku_list[i].id+'">'+sku+" - "+sku_list[i].size_name+" ("+sku_list[i].inv_count+")"+'</option>';
                    }
                    
                    $("#sku_search_inv_transfer_store_demand").html(sku_option_str);
                    $("#sku_search_inv_transfer_store_demand").val(product_id);
                    $("#barcode_search_inv_transfer_store_demand").val(barcode);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        loadInventoryTransferStoreDemandInventory(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                loadInventoryTransferStoreDemandInventory(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"inventoryTransferStoreDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryTransferStoreDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function closeInventoryTransferStoreDemand(){
    $("#closeInventoryTransferStoreDemandSuccessMessage,#closeInventoryTransferStoreDemandErrorMessage").html('').hide();
    $("#closeInventoryTransferStoreDemandForm .form-control").val('');
    $("#closeInventoryTransferStoreDemandDialog").modal('show');
    
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=get_demand_preview_data",
        method:"GET",
        data:{demand_id:demand_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#closeInventoryTransferStoreDemandErrorMessage").html(errors).show();
                    } 
                }else{
                    var demand_data = msg.demand_data;
                    var store_data = msg.store_data;
                    var total_data = msg.total_data;
                    var from_store_data = msg.from_store_data;
                    $("#demand_prev_type").val('Store to Store');
                    $("#demand_prev_date_created").val(displayDate(demand_data.created_at));
                    $("#demand_prev_from_store_name").val(from_store_data.store_name);
                    $("#demand_prev_from_store_gst_no").val(from_store_data.gst_no);
                    $("#demand_prev_store_name").val(store_data.store_name);
                    $("#demand_prev_store_gst_no").val(store_data.gst_no);
                    
                    $("#demand_prev_total_inv").val(total_data.total_qty);
                    $("#demand_prev_taxable_value").val(total_data.total_taxable_val.toFixed(2));
                    $("#demand_prev_gst_amount").val(total_data.total_gst_amt.toFixed(2));
                    $("#demand_prev_total_amt").val(total_data.total_value.toFixed(2));
                    $("#demand_prev_total_sale_price").val(total_data.total_sale_price.toFixed(2));
                }
            }else{
                displayResponseError(msg,"closeInventoryTransferStoreDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryTransferStoreDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitCloseInventoryTransferStoreDemand(){
    var demand_id = $("#demand_id").val();
    var courier_detail = $("#courier_detail").val();
    var vehicle_detail = $("#vehicle_detail").val();
    var boxes_count = $("#boxes_count").val();
    var transporter_name = $("#transporter_name").val();
    var transporter_gst = $("#transporter_gst").val();
    var docket_no = $("#docket_no").val();
    var eway_bill_no = $("#eway_bill_no").val();
    
    $("#closeInventoryTransferStoreDemandSpinner").show();
    $("#closeInventoryTransferStoreDemandCancel,#closeInventoryTransferStoreDemandSubmit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=close_demand",
        method:"POST",
        data:{courier_detail:courier_detail,vehicle_detail:vehicle_detail,boxes_count:boxes_count,transporter_name:transporter_name,transporter_gst:transporter_gst,docket_no:docket_no,eway_bill_no:eway_bill_no},
        success:function(msg){
            $("#closeInventoryTransferStoreDemandSpinner").hide();
            $("#closeInventoryTransferStoreDemandCancel,#closeInventoryTransferStoreDemandSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#closeInventoryTransferStoreDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#closeInventoryTransferStoreDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-transfer-store/detail/"+demand_id;
                    setTimeout(function(){ $("#closeInventoryTransferStoreDemandDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"closeInventoryTransferStoreDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryTransferStoreDemandSpinner").hide();
            $("#closeInventoryTransferStoreDemandCancel,#closeInventoryTransferStoreDemandSubmit").attr('disabled',false);
            $("#closeInventoryTransferStoreDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function editDemandTransportationData(){
    $("#editGatePassDialog").modal('show');
}

function submitEditDemandTransportationData(){
    var demand_id = $("#demand_id").val();
    var boxes_count = $("#boxes_count").val();
    var transporter_name = $("#transporter_name").val();
    var transporter_gst = $("#transporter_gst").val();
    var docket_no = $("#docket_no").val();
    var eway_bill_no = $("#eway_bill_no").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=update_gate_pass_data",
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

function deleteInventoryTransferStoreDemandItems(){
    var demand_id = $("#demand_id").val();
    var chk_class = 'inv-transfer-store-demand-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#inv_transfer_store_demand_delete_error_dialog").modal('show');
        return false;
    }
        
    $("#deleteInventoryTransferDemandItemsErrorMessage,#deleteInventoryTransferDemandItemsSuccessMessage").html('').hide();
    
    $('#inv_transfer_store_demand_delete_items_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_inv_transfer_store_demand_items_btn', function(e) {
        e.preventDefault();
        $("#delete_inv_transfer_store_demand_items_btn,#delete_inv_transfer_store_demand_items_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray,demand_id:demand_id},
            url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=delete_push_demand_items",
            success: function(msg){	
                $("#delete_inv_transfer_store_demand_items_btn,#delete_inv_transfer_store_demand_items_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteInventoryTransferDemandItemsErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteInventoryTransferDemandItemsSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#inv_transfer_store_demand_delete_items_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteInventoryTransferDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteInventoryTransferDemandItemsErrorMessage").html('Error in processing request').show();
                $("#delete_inv_transfer_store_demand_items_btn,#delete_inv_transfer_store_demand_items_cancel").attr('disabled',false);
            }
        });
    });
}

var inventory_products_inv_transfer_store_rec_demand = [],  rec_per_page_inv_transfer_store_rec_demand = '', page_global_inv_transfer_store_rec_demand = 1,inventory_data_transfer_store_rec_demand = {},inv_transfer_store_rec_barcode = '',inv_transfer_store_rec_time = 0;

function getInventoryTransferStoreReceiveDemandProductData(barcode){
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    if(barcode == ''){
        $("#inventoryTransferStoreRecDemandErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    //if(barcode.length != 16 && barcode.length != 19){
    if(barcode.length < 6){
        $("#inventoryTransferStoreRecDemandErrorMessage").html('').hide();
        $(".import-data").val('');
        return false;
    }
    
    var time = new Date().getTime();
    if(inv_transfer_store_rec_barcode != '' && inv_transfer_store_rec_barcode == barcode && Math.abs((inv_transfer_store_rec_time) - time)/1000 < 1){
        return false;
    }
    
    inv_transfer_store_rec_barcode = barcode;
    inv_transfer_store_rec_time = time;
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/load/"+demand_id+"?action=get_inventory_product",
        method:"get",
        data:{barcode:barcode,demand_id:demand_id,store_id:store_id},
        success:function(msg){
            $("#inventoryTransferStoreRecDemandErrorMessage,#inventoryTransferStoreRecDemandSuccessMessage").html('').hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $(".import-data,#piece_id").val('');
                        setTimeout(function(){ $("#piece_barcode_inv_transfer_store_rec_demand").val('').focus();  }, 1000);
                        setTimeout(function(){
                            errors+=' &nbsp;&nbsp;<a onclick="$(\'#inventoryTransferStoreRecDemandErrorMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i title="Close" class="fas fa-times-circle"></i></a>';
                            $("#inventoryTransferStoreRecDemandErrorMessage").html(errors).show();
                        }, 200);
                    } 
                }else{ 
                    var product_data_inv_transfer_store_rec_demand = msg.product_data;
                    $("#product_name").val(product_data_inv_transfer_store_rec_demand.product_name);
                    $("#piece_size").val(product_data_inv_transfer_store_rec_demand.size_name);
                    $("#piece_color").val(product_data_inv_transfer_store_rec_demand.color_name);
                    $("#piece_vendor").val('');
                    $("#piece_po_number").val('');
                    $("#product_sku").val(product_data_inv_transfer_store_rec_demand.vendor_sku);
                    $("#piece_cost").val(product_data_inv_transfer_store_rec_demand.base_price);
                    $("#intake_date").val(product_data_inv_transfer_store_rec_demand.intake_date);
                    $("#piece_id").val(product_data_inv_transfer_store_rec_demand.id);
                    $("#product_id").val(product_data_inv_transfer_store_rec_demand.product_master_id);
                    
                    product_data_inv_transfer_store_rec_demand.product_status = 4;
                    product_data_inv_transfer_store_rec_demand.store_assign_date = getCurrentDate(2);
                    inventory_products_inv_transfer_store_rec_demand.push(product_data_inv_transfer_store_rec_demand);
                    inventory_data_transfer_store_rec_demand.inventory_received = parseInt(inventory_data_transfer_store_rec_demand.inventory_received)+1;
                    displayInventoryTransferStoreRecDemandInventoryData();
                    
                    setTimeout(function(){ 
                         var success_msg = msg.message+' &nbsp;&nbsp;<a onclick="$(\'#inventoryTransferStoreRecDemandSuccessMessage\').html(\'\').hide();" href="javscript:;" class="table-link"><i style="color:#81A305;" title="Close" class="fas fa-times-circle"></i></a>';
                        $("#inventoryTransferStoreRecDemandSuccessMessage").html(success_msg).show();
                    }, 200);
                    
                    setTimeout(function(){ 
                        $(".import-data,#piece_id").val('');
                        $("#piece_barcode_inv_transfer_store_rec_demand").val('').focus();
                    }, 1000);
                }
            }else{
                displayResponseError(msg,"inventoryTransferStoreRecDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryTransferStoreRecDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

$("#piece_barcode_inv_transfer_store_rec_demand").on('propertychange change paste input', function(){
    setTimeout(function(){   
        var val = $("#piece_barcode_inv_transfer_store_rec_demand").val();
        getInventoryTransferStoreReceiveDemandProductData(val);
    }, 500);
});

function displayInventoryTransferStoreRecDemandInventoryData(){
    var str = '';
    str+='Total Inventory: '+inventory_data_transfer_store_rec_demand.inventory_count+" | Loaded Inventory: "+inventory_data_transfer_store_rec_demand.inventory_received;
    str+='<div class="row"><div class="col-md-2"><input type="text" value="" class="form-control" name="barcode_search_inv_transfer_store_demand" id="barcode_search_inv_transfer_store_demand" placeholder="Peice Barcode"></div><div class="col-md-3"><select name="sku_search_inv_transfer_store_demand" id="sku_search_inv_transfer_store_demand" class="form-control"><option value="">SKU</option></select></div><div class="col-md-1"><button type="button" id="pos_inv_transfer_store_demand_search" name="pos_inv_transfer_store_demand_search" class="btn btn-dialog" value="Submit" onclick="searchTransferStoreDemandInv();">Search</button></div></div><div class="separator-10"></div>';
    str+='<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo.</th><th>Piece Barcode</th><th>Product</th>';
    str+='<th>Size</th><th>Color</th><th>SKU</th><th>Cost</th><th>Out Date</th><th>Status</th><th>Type</th></tr></thead><tbody>';
    
    if(inventory_products_inv_transfer_store_rec_demand.length > 0){    
        var start = parseInt((page_global_inv_transfer_store_rec_demand-1)*page_global_inv_transfer_store_rec_demand)+1;
        for(var i=0;i<inventory_products_inv_transfer_store_rec_demand.length;i++){
            var product_data_inv_transfer_store_rec_demand = inventory_products_inv_transfer_store_rec_demand[i];
            var store_assign_date = formatDate(product_data_inv_transfer_store_rec_demand.store_assign_date);
            var inv_type = (product_data_inv_transfer_store_rec_demand.arnon_inventory == 1)?INV_TYPE_ARNON:INV_TYPE_NORTH;
            var sku = (product_data_inv_transfer_store_rec_demand.vendor_sku != null)?product_data_inv_transfer_store_rec_demand.vendor_sku:product_data_inv_transfer_store_rec_demand.product_sku;
            
            str+='<tr><td> '+(start+i)+'</td>';
            
            str+='<td>'+product_data_inv_transfer_store_rec_demand.peice_barcode+'</td><td>'+product_data_inv_transfer_store_rec_demand.product_name+'</td><td>'+product_data_inv_transfer_store_rec_demand.size_name+'</td><td>'+product_data_inv_transfer_store_rec_demand.color_name+'</td>\
            <td>'+sku+'</td><td>'+product_data_inv_transfer_store_rec_demand.spdi_store_base_price+'</td><td>'+store_assign_date+'</td><td>'+getProductInventoryStatus(product_data_inv_transfer_store_rec_demand.product_status)+'</td><td>'+inv_type+'</td></tr>';
        }
    }else{
        str+='<tr><td colspan="10" align="center">No Records</td></tr>';
    }    
    
    str+='</tbody></table></div>';
    
    $("#products_imported_list").html(str);
}

function searchTransferStoreDemandInv(){
    $("#pos_inv_transfer_store_demand_search").attr('disabled',true);
    loadInventoryTransferStoreRecDemandInventory(1);
    $("#pos_inv_transfer_store_demand_search").attr('disabled',false);
}

function loadInventoryTransferStoreRecDemandInventory(page){
    var demand_id = $("#demand_id").val();
    var barcode = $("#barcode_search_inv_transfer_store_demand").val();
    var product_id = $("#sku_search_inv_transfer_store_demand").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/load/"+demand_id+"?action=load_demand_inventory",
        method:"get",
        data:{demand_id:demand_id,page:page,barcode:barcode,product_id:product_id},
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#inventoryTransferStoreRecDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var product_list = msg.product_list.data;//alert(msg);
                    var paging_links = msg.paging_links;
                    paging_links+=getAjaxPagingLinks(msg.product_list);
                    var sku_list = msg.sku_list,sku_option_str = '<option value="">SKU</option>';
                    
                    inventory_products_inv_transfer_store_rec_demand = [];
                    for(var i=0;i<product_list.length;i++){
                        inventory_products_inv_transfer_store_rec_demand.push(product_list[i]);
                    }
                    
                    rec_per_page_inv_transfer_store_rec_demand = msg.rec_per_page;
                    page_global_inv_transfer_store_rec_demand = page;
                    inventory_data_transfer_store_rec_demand.inventory_count = msg.inv_count_data.inventory_total;
                     inventory_data_transfer_store_rec_demand.inventory_received = msg.inv_count_data.inventory_received;
                    displayInventoryTransferStoreRecDemandInventoryData();
                    $("#products_paging_links").html(paging_links);
                    
                    for(var i=0;i<sku_list.length;i++){
                        var sku = (sku_list[i].vendor_sku != null)?sku_list[i].vendor_sku:sku_list[i].product_sku;
                        sku_option_str+='<option value="'+sku_list[i].id+'">'+sku+" - "+sku_list[i].size_name+" ("+sku_list[i].inv_count+")"+'</option>';
                    }
                    
                    $("#sku_search_inv_transfer_store_demand").html(sku_option_str);
                    $("#sku_search_inv_transfer_store_demand").val(product_id);
                    $("#barcode_search_inv_transfer_store_demand").val(barcode);
                    
                    $(document).on('click', '.pagination-ajax a',function(event){
                        event.preventDefault();
                        $('li').removeClass('active');
                        $(this).parent('li').addClass('active');
                        var page=$(this).attr('href').split('page=')[1];
                        displayInventoryTransferStoreRecDemandInventoryData(page);
                    });
                    
                    $(window).on('hashchange', function() {
                        if (window.location.hash) {
                            var page = window.location.hash.replace('#', '');
                            if (page == Number.NaN || page <= 0) {
                                return false;
                            }else{
                                displayInventoryTransferStoreRecDemandInventoryData(page);
                            }
                        }
                    });
                }
            }else{
                displayResponseError(msg,"inventoryTransferStoreRecDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#inventoryTransferStoreRecDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function closeInventoryTransferStoreRecDemand(){
    $("#closeInventoryTransferStoreDemandSuccessMessage,#closeInventoryTransferStoreDemandErrorMessage").html('').hide();
    $("#courier_detail,#vehicle_detail").val('');
    $("#closeInventoryTransferStoreDemandDialog").modal('show');
}

function submitCloseInventoryTransferStoreRecDemand(){
    var demand_id = $("#demand_id").val();
    var comments_close_demand = $("#comments_close_demand").val();
    
    $("#closeInventoryTransferStoreDemandSpinner").show();
    $("#closeInventoryTransferStoreDemandCancel,#closeInventoryTransferStoreDemandSubmit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/load/"+demand_id+"?action=close_demand",
        method:"GET",
        data:{demand_id:demand_id,comments_close_demand:comments_close_demand},
        success:function(msg){
            $("#closeInventoryTransferStoreDemandSpinner").hide();
            $("#closeInventoryTransferStoreDemandCancel,#closeInventoryTransferStoreDemandSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#closeInventoryTransferStoreDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#closeInventoryTransferStoreDemandSuccessMessage").html(msg.message).show();
                    var url = ROOT_PATH+"/store/demand/inventory-transfer-store/detail/"+demand_id;
                    setTimeout(function(){  $("#closeInventoryTransferStoreDemandDialog").modal('hide');window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"closeInventoryTransferStoreDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#closeInventoryTransferStoreDemandSpinner").hide();
            $("#closeInventoryTransferStoreDemandCancel,#closeInventoryTransferStoreDemandSubmit").attr('disabled',false);
            $("#closeInventoryTransferStoreDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateInventoryPushDemandDocketNo(){
    var demand_id = $("#demand_id").val();
    var docket_no = $("#docket_no").val();
    var receive_date = $("#receive_date").val();
    
    $("#updatePushDemandDocketNoSuccessMessage,#updatePushDemandDocketNoErrorMessage,.invalid-feedback").html('').hide();
    $("#docket_no_submit").attr('disabled',true);
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-push/edit/"+demand_id+"?action=update_docket_no",
        method:"POST",
        data:{docket_no:docket_no,receive_date:receive_date,demand_id:demand_id},
        success:function(msg){
            
            $("#docket_no_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updatePushDemandDocketNoErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updatePushDemandDocketNoSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"updatePushDemandDocketNoErrorMessage");
            }
        },error:function(obj,status,error){
            $("#docket_no_submit").attr('disabled',false);
            $("#updatePushDemandDocketNoErrorMessage").html('Error in processing request').show();
        }
    });
}

function toggleInventoryReturnDemandType(type_id){
    if(type_id == 2){
        $("#push_demand_div").slideUp("slow")
    }else{
        $("#push_demand_div").slideDown("slow")
    }
}

function editStoreAccessKey(store_id){
    $("#editStoreAccessKeyErrorMessage,#editStoreAccessKeySuccessMessage").html('').hide();
    $("#access_key_store_id").val(store_id);
    $("#store_api_url").val('');
    $("#store_name_api").html('');
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/data/"+store_id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoreAccessKeyErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#store_access_key_edit").val(msg.store_data.api_access_key);
                    if(msg.store_data.api_url != ''){
                        $("#store_api_url").val(msg.store_data.api_url);
                    }
                    $("#store_name_api").html('Store: '+msg.store_data.store_name);
                    $("#access_key_update_div").hide();
                    $("#store_access_key_edit_btn").show();
                    $("#edit_store_access_key_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"editStoreAccessKeyErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreAccessKeyErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditStoreAccessKey(){
    
    var store_id = $("#access_key_store_id").val();
    $("#editStoreAccessKeyErrorMessage,#editStoreAccessKeySuccessMessage").html('').hide();
    $("#store_access_key_update_submit,#store_access_key_update_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/list?action=update_access_key&id="+store_id,
        method:"GET",
        success:function(msg){
            $("#store_access_key_update_submit,#store_access_key_update_cancel").attr('disabled',false);
            $("#store_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editStoreAccessKeyErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStoreAccessKeySuccessMessage").html(msg.message).show();
                    $("#editStoreAccessKeyErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_store_access_key_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editStoreAccessKeyErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreErrorMessage").html('Error in processing request').show();
            
            $("#store_access_key_update_submit,#store_access_key_update_cancel").attr('disabled',false);
        }
    });
}

function cancelStoreToStoreTransferInventoryPushDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#push_demand_cancel_dialog").modal('show');
}

function submitCancelStoreToStoreTransferInventoryPushDemand(){
    $("#cancelPushDemandSuccessMessage,#cancelPushDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#cancelPushDemandSubmit,#cancelPushDemandCancel").attr('disabled',true);
    var comments = $("#cancel_comments").val();
    var demand_id = $("#demand_id_hdn").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=cancel_demand",
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

function updateUserStores(){
    $("#updateUserStoresErrorMessage,#updateUserStoresSuccessMessage,.invalid-feedback").html('').hide();
    $("#edit_user_stores_btn").attr('disabled',true);
    var stores_ids = '';
    $(".store-list-chk").each(function(){
        if($(this).is(":checked")){
            stores_ids+= $(this).val()+",";
        }
    });
    
    stores_ids = stores_ids.substring(0,stores_ids.length-1);
    
    var user_id = $("#user_id").val();
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/user/stores/list?action=update_user_stores",
        method:"POST",
        data:{user_id:user_id,stores_ids:stores_ids},
        success:function(msg){
            $("#edit_user_stores_btn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateUserStoresErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateUserStoresSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateUserStoresErrorMessage");
            }
        },error:function(obj,status,error){
            $("#edit_user_stores_btn").attr('disabled',false);
            $("#updateUserStoresErrorMessage").html('Error in processing request').show();
        }
    });
}

function editPosOrderProduct(order_id,id){
    $("#editOrderProductForm .form-control").val('');
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=get_order_product_data",
        method:"GET",
        data:{id:id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editOrderProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editOrderProductErrorMessage").html('').hide();
                    var product_data = msg.product_data;
                    var gst_inclusive = (product_data.gst_inclusive == 1)?'Inc':'Exc';
                    $("#product_name_edit").val(product_data.product_name+" "+product_data.size_name+" "+product_data.color_name);
                    $("#order_product_mrp_edit").val(product_data.sale_price);
                    $("#order_product_discount_percent_edit").val(parseFloat(product_data.discount_percent).toFixed(2));
                    $("#order_product_discount_amount_edit").val(parseFloat(product_data.discount_amount).toFixed(2));
                    $("#order_product_discounted_amt_edit").val(parseFloat(product_data.discounted_price).toFixed(2));
                    $("#order_product_gst_percent_edit").val(parseFloat(product_data.gst_percent).toFixed(2)+" ("+gst_inclusive+")");
                    $("#order_product_gst_amount_edit").val(parseFloat(product_data.gst_amount).toFixed(2));
                    $("#order_product_net_price_edit").val(parseFloat(product_data.net_price).toFixed(2));
                    
                    $("#order_product_edit_id_hdn").val(id);
                    $("#edit_order_product_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"editOrderProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editOrderProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function calculateEditOrderProduct(){
    $("#editOrderProductErrorMessage,#editOrderProductSuccessMessage,.invalid-feedback").html('').hide();
    var order_id = $("#order_id_hdn").val();
    var id = $("#order_product_edit_id_hdn").val();
    var discount_edit_type = $("#order_discount_modify_type_edit").val();
    var discount_percent = $("#order_discount_modify_percent_edit").val();
    var discount_amount = $("#order_discount_modify_amount_edit").val();
    var gst_type = $("#order_gst_type_modify_edit").val();
    $("#edit_order_product_calculate_btn").attr('disabled',true);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=update_order_product_data&calculate=1",
        method:"GET",
        data:{order_id:order_id,id:id,discount_edit_type:discount_edit_type,discount_percent:discount_percent,discount_amount:discount_amount,gst_type:gst_type},
        success:function(msg){
            $("#edit_order_product_calculate_btn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editOrderProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var calculate_data = msg.calculate_data;
                    var gst_inclusive = (calculate_data.gst_type == 'inclusive')?'Inc':'Exc';
                    $("#order_product_mrp_calculate").val(calculate_data.sale_price);
                    $("#order_product_discount_percent_calculate").val(parseFloat(calculate_data.discount_percent).toFixed(2));
                    $("#order_product_discount_amount_calculate").val(parseFloat(calculate_data.discount_amount).toFixed(2));
                    $("#order_product_discounted_amt_calculate").val(parseFloat(calculate_data.discounted_price).toFixed(2));
                    $("#order_product_gst_percent_calculate").val(parseFloat(calculate_data.gst_percent).toFixed(2)+" ("+gst_inclusive+")");
                    $("#order_product_gst_amount_calculate").val(parseFloat(calculate_data.gst_amount).toFixed(2));
                    $("#order_product_net_price_calculate").val(parseFloat(calculate_data.net_price).toFixed(2));
                }
            }else{
                displayResponseError(msg,"editOrderProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#edit_order_product_calculate_btn").attr('disabled',false);
            $("#editOrderProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditPosOrderProduct(){
    $("#editOrderProductErrorMessage,#editOrderProductSuccessMessage,.invalid-feedback").html('').hide();
    var order_id = $("#order_id_hdn").val();
    var id = $("#order_product_edit_id_hdn").val();
    var discount_edit_type = $("#order_discount_modify_type_edit").val();
    var discount_percent = $("#order_discount_modify_percent_edit").val();
    var discount_amount = $("#order_discount_modify_amount_edit").val();
    var gst_type = $("#order_gst_type_modify_edit").val();
    $("#edit_order_product_submit,#edit_order_product_cancel").attr('disabled',true);
    
    ajaxSetup();
    
    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=update_order_product_data&calculate=0",
        method:"POST",
        data:{order_id:order_id,id:id,discount_edit_type:discount_edit_type,discount_percent:discount_percent,discount_amount:discount_amount,gst_type:gst_type},
        success:function(msg){
            $("#edit_order_product_submit,#edit_order_product_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editOrderProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editOrderProductSuccessMessage").html(msg.message).show();
                    $("#editOrderProductErrorMessage").html('').hide();
                    
                    setTimeout(function(){ $("#edit_order_product_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editOrderProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#edit_order_product_submit,#edit_order_product_cancel").attr('disabled',false);
            $("#editOrderProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function toggleEditOrderDiscountType(discount_type){
    if(discount_type == 'percent'){
        $("#discount_type_edit_percent").removeClass('elem-hidden');
        $("#discount_type_edit_amount").addClass('elem-hidden');
    }else{
        $("#discount_type_edit_percent").addClass('elem-hidden');
        $("#discount_type_edit_amount").removeClass('elem-hidden');
    }
}

function deletePosOrderProduct(id){
    var order_id = $("#order_id_hdn").val();
    $("#deleteOrderProductErrorMessage,#deleteOrderProductSuccessMessage").html('').hide();
     
    $('#confirm_delete_order_product').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_order_product_btn', function(e) {
        e.preventDefault();
        
        ajaxSetup();
    
        $.ajax({
            url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=delete_order_product",
            method:"POST",
            data:{order_id:order_id,id:id},
            success:function(msg){
                if(objectPropertyExists(msg,'status')){    
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','error_validation_');
                        if(errors != ''){
                            $("#deleteOrderProductErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteOrderProductSuccessMessage").html(msg.message).show();
                        $("#deleteOrderProductErrorMessage").html('').hide();

                        setTimeout(function(){ $("#confirm_delete_order_product").modal('hide'); window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteOrderProductErrorMessage");
                }
            },error:function(obj,status,error){
                $("#deleteOrderProductErrorMessage").html('Error in processing request').show();
            }
        });
    });
}

function addPosOrderProduct(){
    $("#addOrderProductForm .form-control").val('');
    $("#addOrderProductErrorMessage,#addOrderProductSuccessMessage,.invalid-feedback").html('').hide();
    $("#add_order_product_dialog").modal('show');
    $("#product_qr_code_add").focus();
}

function editOrderGetProductData(){
    var qr_code = $("#product_qr_code_add").val();
    var order_id = $("#order_id_hdn").val();
    $("#addOrderProductErrorMessage,#addOrderProductSuccessMessage,.invalid-feedback").html('').hide();
    $("#product_qr_code_submit").attr('disabled',true);
    $("#addOrderProductForm .form-control").val('');
    $("#product_qr_code_add").val(qr_code);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=get_order_product_data_qr_code",
        method:"GET",
        data:{qr_code:qr_code,order_id:order_id},
        success:function(msg){
            $("#product_qr_code_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addOrderProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addOrderProductErrorMessage").html('').hide();
                    var product_data = msg.product_data;
                    
                    $("#product_name_add").val(product_data.product_name);
                    $("#product_size_add").val(product_data.size_name);
                    $("#product_color_add").val(product_data.color_name);
                    $("#product_category_add").val(product_data.category_name);
                    $("#product_sku_add").val(product_data.product_sku);
                    
                    $("#order_product_mrp_add").val(product_data.sale_price);
                    var gst_inclusive = (product_data.gst_inclusive == 1)?'Inc':'Exc';
                    $("#order_product_discount_percent_add").val(parseFloat(product_data.discount_percent).toFixed(2));
                    $("#order_product_discount_amount_add").val(parseFloat(product_data.discount_amount).toFixed(2));
                    $("#order_product_discounted_amt_add").val(parseFloat(product_data.discounted_price).toFixed(2));
                    $("#order_product_gst_percent_add").val(parseFloat(product_data.gst_percent).toFixed(2)+" ("+gst_inclusive+")");
                    $("#order_product_gst_amount_add").val(parseFloat(product_data.gst_amount).toFixed(2));
                    $("#order_product_net_price_add").val(parseFloat(product_data.net_price).toFixed(2));
                    
                    $("#order_product_add_id_hdn").val(product_data.id);
                }
            }else{
                displayResponseError(msg,"addOrderProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#product_qr_code_submit").attr('disabled',false);
            $("#addOrderProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitAddPosOrderProduct(){
    var qr_code = $("#product_qr_code_add").val();
    var order_id = $("#order_id_hdn").val();
    $("#addOrderProductErrorMessage,#addOrderProductSuccessMessage,.invalid-feedback").html('').hide();
    $("#add_order_product_submit,#add_order_product_cancel").attr('disabled',true);
    $("#addOrderProductForm .form-control").val('');
    $("#product_qr_code_add").val(qr_code);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=add_order_product",
        method:"GET",
        data:{qr_code:qr_code,order_id:order_id},
        success:function(msg){
            $("#add_order_product_submit,#add_order_product_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addOrderProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addOrderProductErrorMessage").html('').hide();
                    $("#addOrderProductSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#add_order_product_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addOrderProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add_order_product_submit,#add_order_product_cancel").attr('disabled',false);
            $("#addOrderProductErrorMessage").html('Error in processing request').show();
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

function editPosOrderProductStaff(order_id,id){
    $("#editOrderProductStaffForm .form-control").val('');
    $("#editOrderProductErrorMessage,#editOrderProductStaffSuccessMessage,.invalid-feedback").html('').hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=get_order_product_data",
        method:"GET",
        data:{id:id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editOrderProductStaffErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editOrderProductStaffErrorMessage").html('').hide();
                    var product_data = msg.product_data;
                    $("#order_ataff_product_id_hdn").val(product_data.id);
                    $("#order_product_staff_edit").val(product_data.staff_id);
                    $("#edit_order_product_staff_dialog").modal('show');
                }
            }else{
                displayResponseError(msg,"editOrderProductStaffErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editOrderProductStaffErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditPosOrderProductStaff(){
    $("#editOrderProductErrorMessage,#editOrderProductStaffSuccessMessage,.invalid-feedback").html('').hide();
    var order_id = $("#order_id_hdn").val();
    var id = $("#order_ataff_product_id_hdn").val();
    var staff_id = $("#order_product_staff_edit").val();
    $("#edit_order_product_ataff_submit,#edit_order_product_ataff_cancel").attr('disabled',true);
    
    ajaxSetup();
    
    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=update_order_product_staff",
        method:"POST",
        data:{order_id:order_id,id:id,staff_id:staff_id},
        success:function(msg){
            $("#edit_order_product_ataff_submit,#edit_order_product_ataff_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editOrderProductStaffErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editOrderProductStaffSuccessMessage").html(msg.message).show();
                    $("#editOrderProductStaffErrorMessage").html('').hide();
                    
                    setTimeout(function(){ $("#edit_order_product_staff_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editOrderProductStaffErrorMessage");
            }
        },error:function(obj,status,error){
            $("#edit_order_product_ataff_submit,#edit_order_product_ataff_cancel").attr('disabled',false);
            $("#editOrderProductStaffErrorMessage").html('Error in processing request').show();
        }
    });
}

function editStoreBags(id){
    $("#edit_store_bags_dialog").modal('show');
    $("#editStoreBagsFrm .form-control").val('');
    $("#store_bags_edit_id").val(id);
    $("#editStoreBagsSuccessMessage,#editStoreBagsErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/bags/inventory/list?action=get_store_bags_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoreBagsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#store_id_edit").val(msg.store_bags_data.store_id);
                    $("#bags_count_edit").val(msg.store_bags_data.bags_assigned);
                    $("#date_assigned_edit").val(displayDate(msg.store_bags_data.date_assigned));
                }
            }else{
                displayResponseError(msg,"editStoreBagsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreBagsErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditStoreBags(){
    var form_data = $("#editStoreBagsFrm").serialize();
    $("#editStoreBagsSuccessMessage,#editStoreBagsErrorMessage,.invalid-feedback").html('').hide();
    $("#store_bag_edit_cancel,#store_bag_edit_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/bags/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#store_bag_edit_cancel,#store_bag_edit_submit").attr('disabled',false);
           
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editStoreBagsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStoreBagsSuccessMessage").html(msg.message).show();
                    $("#editStoreBagsErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_store_bags_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editStoreBagsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreBagsErrorMessage").html('Error in processing request').show();
            $("#store_bag_edit_cancel,#store_bag_edit_submit").attr('disabled',false);
        }
    });
}

function addStoreBags(){
    $("#addStoreBagsSuccessMessage,#addStoreBagsErrorMessage,.invalid-feedback").html('').hide();
    $("#addStoreBagsFrm .form-control").val('');
    $("#add_store_bags_dialog").modal('show');
}

function submitAddStoreBags(){
    var form_data = $("#addStoreBagsFrm").serialize();
    $("#addStoreBagsSuccessMessage,#addStoreBagsErrorMessage,.invalid-feedback").html('').hide();
    $("#store_bag_add_cancel,#store_bag_add_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/bags/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#store_bag_add_cancel,#store_bag_add_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addStoreBagsErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addStoreBagsSuccessMessage").html(msg.message).show();
                    $("#addStoreBagsErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_store_bags_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addStoreBagsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addStoreBagsErrorMessage").html('Error in processing request').show();
            $("#store_bag_add_cancel,#store_bag_add_submit").attr('disabled',false);
        }
    });
}

function downloadPosOrderCustomers(){
    $("#downloadPosOrderCustomersDialog .form-control").val('');
    $("#error_validation_pos_order_cust_count").html('').hide();
    $("#downloadPosOrderCustomersDialog").modal('show');
}

function submitDownloadPosOrderCustomers(){
    var pos_order_cust_count = $("#pos_order_cust_count").val(), str = '';
    $("#error_validation_pos_order_cust_count").html('').hide();
    if(pos_order_cust_count == ''){
        $("#error_validation_pos_order_cust_count").html('Pos Orders Records is Required Field').show();
        return false;
    }
    
    const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);

    var url = ROOT_PATH+"/pos/customer/list?action=download_csv&pos_order_cust_count="+pos_order_cust_count+"&"+str;
    window.location.href = url;
}

function importWHToStoreDemandInventory(){
    $("#barcodeTxtFile").val('');
    $("#importWHToStoreDemandInventoryErrorMessage,#importWHToStoreDemandInventorySuccessMessage").html('').hide();
    $("#importWHToStoreDemandInventoryDialog").modal('show');
}

function submitImportWHToStoreDemandInventory(){
    $("#importWHToStoreDemandInventoryForm").submit();
}

$("#importWHToStoreDemandInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    formData.append('store_id',store_id);
    
    $("#importWHToStoreDemandInventorySpinner").show();
    $("#importWHToStoreDemandInventoryCancel,#importWHToStoreDemandInventorySubmit").attr('disabled',true);
    $("#importWHToStoreDemandInventoryErrorMessage,#importWHToStoreDemandInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/store/demand/inventory-push/edit/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importWHToStoreDemandInventorySpinner").hide();
            $("#importWHToStoreDemandInventoryCancel,#importWHToStoreDemandInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importWHToStoreDemandInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importWHToStoreDemandInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importWHToStoreDemandInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importWHToStoreDemandInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importWHToStoreDemandInventoryErrorMessage").html('Error in processing request').show();
            $("#importWHToStoreDemandInventorySpinner").hide();
            $("#importWHToStoreDemandInventoryCancel,#importWHToStoreDemandInventorySubmit").attr('disabled',false);
        }
    });
});


function importStoreToStoreDemandInventory(){
    $("#barcodeTxtFile").val('');
    $("#importStoreToStoreDemandInventoryErrorMessage,#importStoreToStoreDemandInventorySuccessMessage").html('').hide();
    $("#importStoreToStoreDemandInventoryDialog").modal('show');
}

function submitImportStoreToStoreDemandInventory(){
    $("#importStoreToStoreDemandInventoryForm").submit();
}

$("#importStoreToStoreDemandInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    formData.append('store_id',store_id);
    
    $("#importStoreToStoreDemandInventorySpinner").show();
    $("#importStoreToStoreDemandInventoryCancel,#importStoreToStoreDemandInventorySubmit").attr('disabled',true);
    $("#importStoreToStoreDemandInventoryErrorMessage,#importStoreToStoreDemandInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/edit/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importStoreToStoreDemandInventorySpinner").hide();
            $("#importStoreToStoreDemandInventoryCancel,#importStoreToStoreDemandInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importStoreToStoreDemandInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importStoreToStoreDemandInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importStoreToStoreDemandInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importStoreToStoreDemandInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importStoreToStoreDemandInventoryErrorMessage").html('Error in processing request').show();
            $("#importStoreToStoreDemandInventorySpinner").hide();
            $("#importStoreToStoreDemandInventoryCancel,#importStoreToStoreDemandInventorySubmit").attr('disabled',false);
        }
    });
});


function importStoreToStoreDemandInventoryLoad(){
    $("#barcodeTxtFile").val('');
    $("#importStoreToStoreDemandInventoryLoadErrorMessage,#importStoreToStoreDemandInventoryLoadSuccessMessage").html('').hide();
    $("#importStoreToStoreDemandInventoryLoadDialog").modal('show');
}

function submitImportStoreToStoreDemandInventoryLoad(){
    $("#importStoreToStoreDemandInventoryLoadForm").submit();
}

$("#importStoreToStoreDemandInventoryLoadForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    formData.append('store_id',store_id);
    
    $("#importStoreToStoreDemandInventoryLoadSpinner").show();
    $("#importStoreToStoreDemandInventoryLoadCancel,#importStoreToStoreDemandInventoryLoadSubmit").attr('disabled',true);
    $("#importStoreToStoreDemandInventoryLoadErrorMessage,#importStoreToStoreDemandInventoryLoadSuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/load/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importStoreToStoreDemandInventoryLoadSpinner").hide();
            $("#importStoreToStoreDemandInventoryLoadCancel,#importStoreToStoreDemandInventoryLoadSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importStoreToStoreDemandInventoryLoadErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importStoreToStoreDemandInventoryLoadSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importStoreToStoreDemandInventoryLoadDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importStoreToStoreDemandInventoryLoadErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importStoreToStoreDemandInventoryLoadErrorMessage").html('Error in processing request').show();
            $("#importStoreToStoreDemandInventoryLoadSpinner").hide();
            $("#importStoreToStoreDemandInventoryLoadCancel,#importStoreToStoreDemandInventoryLoadSubmit").attr('disabled',false);
        }
    });
});

function importStoreToWHDemandInventory(){
    $("#barcodeTxtFile").val('');
    $("#importStoreToWHDemandInventoryErrorMessage,#importStoreToWHDemandInventorySuccessMessage").html('').hide();
    $("#importStoreToWHDemandInventoryDialog").modal('show');
}

function submitImportStoreToWHDemandInventory(){
    $("#importStoreToWHDemandInventoryForm").submit();
}

$("#importStoreToWHDemandInventoryForm").on('submit', function(event){
    event.preventDefault(); 
    var demand_id = $("#demand_id").val();
    var store_id = $("#store_id").val();
    
    var formData = new FormData(this);
    formData.append('demand_id',demand_id);
    formData.append('store_id',store_id);
    
    $("#importStoreToWHDemandInventorySpinner").show();
    $("#importStoreToWHDemandInventoryCancel,#importStoreToWHDemandInventorySubmit").attr('disabled',true);
    $("#importStoreToWHDemandInventoryErrorMessage,#importStoreToWHDemandInventorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/store/demand/inventory-return/edit/"+demand_id+"?action=import_demand_inventory",
        success: function(msg){		
            $("#importStoreToWHDemandInventorySpinner").hide();
            $("#importStoreToWHDemandInventoryCancel,#importStoreToWHDemandInventorySubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#importStoreToWHDemandInventoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#importStoreToWHDemandInventorySuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#importStoreToWHDemandInventoryDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"importStoreToWHDemandInventoryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#importStoreToWHDemandInventoryErrorMessage").html('Error in processing request').show();
            $("#importStoreToWHDemandInventorySpinner").hide();
            $("#importStoreToWHDemandInventoryCancel,#importStoreToWHDemandInventorySubmit").attr('disabled',false);
        }
    });
});

function openStoreToStoreDemand(){
    $("#openStoreDemandSuccessMessage,#openStoreDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#store_demand_open_dialog").modal('show');
}

function submitOpenStoreToStoreDemand(){
    $("#openStoreDemandSuccessMessage,#openStoreDemandErrorMessage,.invalid-feedback").html('').hide();
    $("#openStoreDemandCancel,#openStoreDemandSubmit").attr('disabled',true);
    var comments = $("#open_comments").val();
    var demand_id = $("#demand_id").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/demand/inventory-transfer-store/detail/"+demand_id+"?action=open_demand",
        method:"POST",
        data:{demand_id:demand_id,comments:comments},
        success:function(msg){
            $("#openStoreDemandCancel,#openStoreDemandSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#openStoreDemandErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#openStoreDemandSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#store_demand_open_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"openStoreDemandErrorMessage");
            }
        },error:function(obj,status,error){
            $("#openStoreDemandCancel,#openStoreDemandSubmit").attr('disabled',false);
            $("#openStoreDemandErrorMessage").html('Error in processing request').show();
        }
    });
}

function editPosOrderCustomerData(){
    $("#editPosOrderCustomerDataDialog").modal('show');
    $("#editPosOrderCustomerDataSuccessMessage,#editPosOrderCustomerDataErrorMessage,.invalid-feedback").html('').hide();
}

function submitEditPosOrderCustomerData(){
    $("#editPosOrderCustomerDataSuccessMessage,#editPosOrderCustomerDataErrorMessage,.invalid-feedback").html('').hide();
    $("#editPosOrderCustomerDataCancel,#editPosOrderCustomerDataSubmit").attr('disabled',true);
    var order_id = $("#order_id_hdn").val();
    var customer_salutation = $("#customer_salutation").val();
    var customer_name = $("#customer_name").val();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/pos/order/edit/"+order_id+"?action=edit_customer_data",
        method:"POST",
        data:{customer_salutation:customer_salutation,customer_name:customer_name},
        success:function(msg){
            $("#editPosOrderCustomerDataCancel,#editPosOrderCustomerDataSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editPosOrderCustomerDataErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editPosOrderCustomerDataSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#editPosOrderCustomerDataDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editPosOrderCustomerDataErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPosOrderCustomerDataCancel,#editPosOrderCustomerDataSubmit").attr('disabled',false);
            $("#editPosOrderCustomerDataErrorMessage").html('Error in processing request').show();
        }
    });
}
