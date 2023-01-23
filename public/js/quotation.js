"use strict";

function quotationVendorsList(id){
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/quotation/quotationvendorslist/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#statusErrorMsg").html(errors).show();
                    } 
                }else{ 
                    if($("#quote_detail_"+id).length > 0){
                        $(".quote-detail-"+id).toggle();
                        return;
                    }
                    //<thead><tr class="header-tr"><th>ID</th><th>Created By </th><th>Created On</th><th>Vendors Count</th><th>Type</th><th>Details</th><th>Submissions</th></tr></thead>
                    var str = '<div class="table-responsive table-filter"><table class="table table-striped admin-table" cellspacing="0">';
                    str+='<thead><tr class="header-tr"><th>SNo</th><th>Name</th><th>Email</th><th>Quotation Submitted</th><th>Submission Date</th><!--<th>Quotation Link</th>--></tr></thead><tbody>';
                    var vendors_list = msg.vendors_list;
                    for(var i=0;i<vendors_list.length;i++){
                        var quotation_submitted = (vendors_list[i].quotation_submitted == 1)?'Yes':'No';
                        var submitted_on  = (vendors_list[i].quotation_submitted == 1)?formatDate(vendors_list[i].submitted_on):'';
                        var quotation_link = '<a href="'+ROOT_PATH+'/quotation/submit/'+id+'/'+vendors_list[i].vendor_id+'"><i title="Quotation Link" class="fas fa-eye"></i></a>';
                        str+='<tr><td>'+(i+1)+'</td><td>'+vendors_list[i].vendor_name+'</td><td>'+vendors_list[i].vendor_email+'</td><td>'+quotation_submitted+'</td><td>'+submitted_on+'</td><!--<td>'+quotation_link+'</td>--></tr>';
                    }
                    
                    str+='</tbody></table></div>';
                    //$(str).insertAfter("#tr_quote_"+id)
                    $("#quatation_list_dialog").modal('show');
                    $("#quatation_list").html(str);
                }
            }else{
                displayResponseError(msg,"statusErrorMsg");
            }
        },error:function(obj,status,error){
            $("#statusErrorMsg").html('Error in processing request').show();
        }
    });
}

function purchaseOrder(quotation_id){
    $("#statusSuccessMsg,#statusErrorMsg").html('').hide();
    ajaxSetup();
    var form_data = $("#purchase_order_form").serialize();
        
    $.ajax({
        url:ROOT_PATH+"/purchase-order/createpurchaseorder/"+quotation_id,
        method:"POST",
        data:form_data,
        success:function(data){
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#statusSuccessMsg").html(data.message).show();
                    setTimeout(function(){ window.location.reload(); }, 2000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    $("#statusErrorMsg").html(errors).show();
                }
            }else{
                displayResponseError(data,"statusErrorMsg");
            }
        },error:function(obj,status,error){
            $("#statusErrorMsg").html(error).show();
        }
    });
}