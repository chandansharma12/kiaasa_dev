"use strict";

var INV_TYPE_ARNON = 'Arnon';
var INV_TYPE_NORTH = 'NorthCorp';

$(document).ready(function(){
    if($('.static-header-tbl').length > 0){
        $('.static-header-tbl').stickyTableHeaders();
        /*$('.static-header-tbl tr').bind('click',function(){
           if($(this).hasClass('report-sel-tr'))  $(this).removeClass('report-sel-tr');else $(this).addClass('report-sel-tr');
        });*/
    }
})

function getResponseErrors(obj,separator_tag,prefix_elem){
    var errors = '';
    if(typeof obj.errors !== 'undefined'){
        if(typeof obj.errors  === 'string'){
            errors = obj.errors;
        }else{
            if(prefix_elem != ''){
                $.each( obj.errors, function( key, value) {
                    $("#"+prefix_elem+key).html(value).show();
                });
            }else{
                $.each( obj.errors, function( key, value) {
                    errors+=value+separator_tag;
                });
            }
        }
    }else{
        if(typeof obj.message !== 'undefined'){
            errors = obj.message;  
        }
    }
    
    return errors;
}

function ajaxSetup(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
}

function checkAllCheckboxes(elem,type){
    // console.log(elem);
    // console.log(type);
    if($(elem).is(':checked')){
        $("."+type+"-chk").prop("checked", true);
    }else{
        $("."+type+"-chk").prop("checked", false);
    }
}

function switchRoleHeader(role_id){
    $("#switch_role_id_header").val(role_id);
    $("#switchRoleFrmHeader").submit();
}

function sendNotification(type_id,ref_id){
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:{type_id:type_id,ref_id:ref_id},
        url:ROOT_PATH+"/user/sendnotification",
        success: function(msg){		
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#errortatusMessage").html(errors).show();
                } 
            }else{ 
                displayStatusText(msg.message,'success');
                setTimeout(function(){  window.location.reload(); }, 2000);
            }
        },
        error:function(obj,status,error){
            $("#errortatusMessage").html('Error in processing request').show();
        }
    });
}

function displayNotifications(){
    ajaxSetup();
    $.ajax({
        type: "GET",
        url:ROOT_PATH+"/user/getnotificationslist",
        success: function(msg){		
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#notificationContent").html(errors);
                } 
            }else{ 
                if(objectPropertyExists(msg,'notifications_list')){
                    var notifications_list = msg.notifications_list;
                    var str = '';
                    if(notifications_list.length > 0){
                        $("#notificationBadge").show();
                        for(var i=0;i<notifications_list.length;i++){
                            str+='<a class="dropdown-item" href="javascript:;" onclick="readNotification('+notifications_list[i].id+');">'+notifications_list[i].notification_text+' </a>';
                        }
                    }else{
                        str+='<span class="dropdown-item" href="javascript:;" >No Notifications !</span>';
                    }

                    $("#notificationContent").html(str);
                }
            }
        },
        error:function(obj,status,error){
            $("#notificationContent").html(error);
        }
    });
}

/*displayNotifications();*/

function readNotification(id){
     ajaxSetup();
    $.ajax({
        type: "GET",
        url:ROOT_PATH+"/user/readnotification/"+id,
        success: function(msg){		
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#notificationContent").html(errors);
                } 
            }else{ 
                var url = msg.url;
                window.location.href = url;
            }
        },
        error:function(obj,status,error){
            $("#notificationContent").html(error);
        }
    });
}

function validateFileExt(filevalue) { 
    var filePath = filevalue;//fileInput.value; 

    // Allowing file type 
    var allowedExtensions =  /(\.jpg|\.jpeg|\.png|\.gif|\.bmp)$/i; 

    if (!allowedExtensions.exec(filePath)) { 
        return false; 
    }else{
        return true;
    }
} 

function displayDialogImage(img_url){
    $("#image_common_dialog_content").html('<img src="'+img_url+'" class="design-item-image">');
    $("#image_common_dialog").modal('show');
}

function displayResponseError(msg,error_elem_id){
    msg = msg+"";
    if(msg.indexOf('access_denied_page') !== false){
        $("#"+error_elem_id).html('Access Denied').show();
    }else{
        $("#"+error_elem_id).html('Error in processing request').show();
    }
}

function objectPropertyExists(msg,property){
    return msg.hasOwnProperty(property);
}

function loadColorDropdown(){
    for(const dropdown of document.querySelectorAll(".custom-select-wrapper")) {
        dropdown.addEventListener('click', function() {
            this.querySelector('.custom-select').classList.toggle('open');
        })
    }

    window.addEventListener('click', function(e) {
        for (const select of document.querySelectorAll('.custom-select')) {
            if (!select.contains(e.target)) {
                select.classList.remove('open');
            }
        }
    });

    $(".span-custom-option").bind('click',function(){
        var id = $(this).attr('data-value');
        $(this).parents('.custom-select').find('.custom-select__trigger span').html($(this).html());
        $(this).parents('.custom-select-wrapper').find(".color-hdn").val(id);
        $(this).parents('.custom-select-wrapper').find(".color-name-hdn").val($(this).html());
    });

    $(".span-custom-option").bind('mouseover',function(){
        var val = $(this).attr('data-value');
        $(this).parents('.custom-options').find('.span-text-'+val).css('background-color','#C7DFF9');
    });
    $(".span-custom-option").bind('mouseout',function(){
        var val = $(this).attr('data-value');
        $(this).parents('.custom-options').find('.span-text-'+val).css('background-color','#ffffff');
    });
}

function validateNumericField(elem){
    if(isNaN($(elem).val())){
        $(elem).val('');
    }
}

var gst_rules = '';
function loadGSTRules(){
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/setting/gstrules/data",
        method:"GET",
        success:function(data){
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    gst_rules = data.gst_rules;
                }else{    
                    
                }
            }else{
                
            }
        },error:function(obj,status,error){
            
        }
    });
}

function getGSTData(hsn_code,amount){
    var gst_data = '';
    if(gst_rules.length > 0){
        for(var i=0;i<gst_rules.length;i++){
            if(gst_rules[i]['hsn_code'] == hsn_code && amount > gst_rules[i]['min_amount'] && amount <= gst_rules[i]['max_amount']){
                gst_data = gst_rules[i];
            }
        }
    }
    
    return gst_data;
}

function getProductInventoryStatus(id){
    var status_arr = [];
    status_arr[0] = 'Warehouse In Pending';
    status_arr[1] = 'Available in Warehouse';
    status_arr[2] = 'Reserved for Store';
    status_arr[3] = 'Transit to store';
    status_arr[4] = 'Ready for Sale by Store';
    status_arr[5] = 'Sold from Store';
    status_arr[6] = 'Transit to Warehouse from Store';
    status_arr[7] = 'Returned to Vendor';
    //status_arr[8] = 'Returned to Vendor';
    status_arr[-1] = 'Added to Queue';
    return (typeof status_arr[id] != 'undefined')?status_arr[id]:'';
}

function getProductInventoryQCStatus(id){
    var status_arr = [];
    status_arr[0] = 'Pending';
    status_arr[1] = 'Accepted';
    status_arr[2] = 'Defective';
    
    return (typeof status_arr[id] != 'undefined')?status_arr[id]:'';
}

function formatDate(d){
    d = new Date(d); 
    var datestring = ("0" + d.getDate()).slice(-2) + "-" + ("0"+(d.getMonth()+1)).slice(-2) + "-" +
    d.getFullYear() + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);

    return datestring;
}

function displayDate(d){
    d = new Date(d); 
    var datestring = ("0" + d.getDate()).slice(-2) + "-" + ("0"+(d.getMonth()+1)).slice(-2) + "-" +d.getFullYear();

    return datestring;
}

function getCurrentDate(type){
    var currentdate = new Date(); 
    
    if(type == 1){
        var datetime = currentdate.getDate()+"/"+(currentdate.getMonth()+1)+"/"+currentdate.getFullYear()+" "  
        +currentdate.getHours()+":"+currentdate.getMinutes()+":"+currentdate.getSeconds();
    }else if(type == 2){
        var datetime = currentdate.getFullYear()+"/"+(currentdate.getMonth()+1)+"/"+currentdate.getDate()+" "  
        +currentdate.getHours()+":"+currentdate.getMinutes()+":"+currentdate.getSeconds();
    }
        
    return datetime;
}

function getAjaxPagingLinks(product_list){
    var paging_links = '';
    if(product_list.total > 0){
        paging_links+=' <div class="separator-10"></div> Displaying Page '+product_list.current_page+" of total "+product_list.last_page+" Pages";
        paging_links+=' | Displaying Records '+product_list.from+" to "+product_list.to+" of total "+product_list.total+" Records";
    }
    
    return paging_links;
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function displayPageDescription(page_name){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/page/description/list?action=get_page_desc_list",
        method:"GET",
        data:{page_name:page_name},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#page_desc_div").html(errors).show();
                    } 
                }else{ 
                    var page_desc = msg.page_desc;
                    var str = '<hr><div class="table-responsive"><h6 style="text-align:left;">Page Description:</h6><table class="table table-striped clearfix admin-table desc-table" cellspacing="0"><thead><tr class="header-tr"><th>SNo</th><th>Name</th><th class="desc-detail">Description</th><th>Type</th></tr></thead>';
                    str+='<tbody>'
                    for(var i=0;i<page_desc.length;i++){
                        if(page_desc[i].desc_type == 'column') var page_type = 'Table Column';
                        if(page_desc[i].desc_type == 'filter') var page_type = 'Filter';
                        if(page_desc[i].desc_type == 'page_description') var page_type = 'Page Description';
                        str+='<tr><td>'+(i+1)+'</td><td>'+page_desc[i].desc_name+'</td><td>'+page_desc[i].desc_detail+'</td><td>'+page_type+'</td></tr>';
                    }
                    str+='</tbody></table></div><br/>'
                    $("#page_desc_div").html(str);
                }
            }else{
                displayResponseError(msg,"page_desc_div");
            }
        },error:function(obj,status,error){
            $("#page_desc_div").html('Error in processing request').show();
        }
    });
}

function downloadReportData(){
    $("#downloadReportDialog .form-control").val('');
    $("#error_validation_report_rec_count").html('').hide();
    $("#downloadReportDialog").modal('show');
}

function submitDownloadReportData(url){
    var inv_count = $("#report_rec_count").val(), str = '';
    $("#error_validation_report_rec_count").html('').hide();
    if(inv_count == ''){
        $("#error_validation_report_rec_count").html('Report Records is Required Field').show();
        return false;
    }
    
    const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);

    var url = ROOT_PATH+url+"?action=download_csv&report_rec_count="+inv_count+"&"+str;
    window.location.href = url;
}