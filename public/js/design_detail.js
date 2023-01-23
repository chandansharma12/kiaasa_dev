"use strict";
var designDataObject;

$(document).ready(function(){
    getDesignData(design_id);
    //getDesignTotalCost(design_id,version,history_type);
    
    $("#upload_design_document_form").on('submit', function(event){
        event.preventDefault(); 
        $("#documentUploadSuccessMsg,#documentUploadErrorMsg").html('').hide();
        ajaxSetup();
        $.ajax({
            url:ROOT_PATH+"/production/uploaddesigndocument/"+design_id,
            method:"POST",
            data:new FormData(this),
            dataType:'JSON',
            contentType: false,
            cache: false,
            processData: false,
            success:function(data){
                if(data.status == 'success'){
                    $("#documentLinkDiv_"+data.document_type).show();
                    $("#documentUploadDiv_"+data.document_type).hide();
                    displayStatusText(data.message,'success');
                }else{
                    $("#documentUploadErrorMsg").html(data.message).show();
                    displayStatusText(data.message,'error');
                }
            },error:function(obj,status,error){
                 displayStatusText(error,'error');
            }
        });
    });
    
});

function getDesignData(id){
    //alert(version);alert(history_type);
    var url = ROOT_PATH+"/design/data/"+id;
    if(version != ''){
        url+="/"+version;
    }
    if(history_type != ''){
        url+="/"+history_type;
    }
    ajaxSetup();
    $.ajax({
        url:url,
        method:"GET",
        success:function(msg){
            designDataObject = msg.designdata;
            displayTabData('fabric');
            
            if(typeof designDataObject.review_data.id != 'undefined'){
                if(designDataObject.review_data.design_status == 'approved' || designDataObject.review_data.design_status == 'rejected'){
                    $("#design_status_sel").hide();
                }
                $("#comment").val(designDataObject.review_data.comment);
                $("#design_status_text").html(designDataObject.review_data.design_status);
                $("#update_status").val(0);
                $("#review_id").val(designDataObject.review_data.id);
            }else{
                $("#update_status").val(1);
            }
        }
    });
}

function displayTabData(tab_type){
    var str = '';
    var size_variation_types = designDataObject.size_variation_types;
    if(tab_type == 'fabric'){
        var fabric_data = designDataObject.fabric_data;
        
        str+='<div id="requirements_fabrics"><div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Fabrics</h2></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Body Part</th><th>Fabric Type</th><th>GSM</th><th>Color</th><th>Width</th><th>Content</th><th>Average</th><th>Rate</th>\
        <th>Cost</th><th>Unit</th>';
        if(size_variation_types.indexOf(1) >= 0) str+='<th>Size</th>';
        //if(designDataObject.fabric_editable_fields.length > 0) str+='<th>Edit</th>';
        str+='</tr></thead><tbody>';
        
        for(var i=0;i<fabric_data.length;i++){
            var img = '';
            if(fabric_data[i].image_name != null && fabric_data[i].image_name!= ''){
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+fabric_data[i].image_name;
                img = '<img onclick="displayDialogImage(\''+img_url.replace('/thumbs','')+'\');" src="'+img_url+'" class="list-design-image">';
            }
            str+='<tr><td>'+img+'</td><td>'+fabric_data[i].body_part_name+'</td><td>'+fabric_data[i].fabric_name+'</td>\
            <td>'+fabric_data[i].gsm_name+'</td><td>'+fabric_data[i].color_name+'</td><td>'+fabric_data[i].width_name+'</td><td>'+fabric_data[i].content_name+'</td>\
            <td>'+fabric_data[i].avg+'</td><td>'+currency+" "+fabric_data[i].rate+'</td><td>'+currency+" "+fabric_data[i].cost+'</td><td>'+fabric_data[i].unit_code+'</td>'
            if(size_variation_types.indexOf(1) >= 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit Size" onclick="getSizeVariations('+fabric_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            //if(designDataObject.fabric_editable_fields.length > 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit" onclick="editDesignFabric('+fabric_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            str+='</tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'accessories'){
        var accessories_data = designDataObject.accessories_data;
        
        str+='<div id="requirements_accessories">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Accessories</h2></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Category</th><th>Sub Category</th><th>Size</th><th>Color</th><th>Rate</th><th>Quantity</th><th>Cost</th><th>Unit</th>';
        if(size_variation_types.indexOf(2) >= 0) str+='<th>Size</th>';
        //if(designDataObject.acc_editable_fields.length > 0) str+='<th>Edit</th>';
        str+='</tr></thead><tbody>';
        
        for(var i=0;i<accessories_data.length;i++){
            var img = '';
            if(accessories_data[i].image_name != null && accessories_data[i].image_name!= ''){
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+accessories_data[i].image_name;
                img = '<img onclick="displayDialogImage(\''+img_url.replace('/thumbs','')+'\');" src="'+img_url+'" class="list-design-image">';
            }
            str+='<tr><td>'+img+'</td><td>'+accessories_data[i].category_name+'</td><td>'+accessories_data[i].quality_name+'</td><td>'+accessories_data[i].size_name+'</td>\
            <td>'+accessories_data[i].color_name+'</td><td>'+currency+" "+accessories_data[i].rate+'</td><td>'+accessories_data[i].avg+'</td>\
            <td>'+currency+" "+accessories_data[i].cost+'</td><td>'+accessories_data[i].unit_code+'</td>'
            if(size_variation_types.indexOf(2) >= 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit Size" onclick="getSizeVariations('+accessories_data[i].id+');"><i class="fa fa-edit"></i></a></td>'
            //if(designDataObject.acc_editable_fields.length > 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit" onclick="editDesignAccessories('+accessories_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            str+='</tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'process'){
        var process_data = designDataObject.process_data;
        
        str+='<div id="requirements_process">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Fabric Process</h2></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Fabric</th><th>Category</th><th>Type</th><th>Rate</th><th>Average</th><th>Cost</th><th>Unit</th>';
        if(size_variation_types.indexOf(3) >= 0) str+='<th>Size</th>';
        if(designDataObject.fp_editable_fields.length > 0) str+='<th>Edit</th>';
        str+='</tr></thead><tbody>';
        
        for(var i=0;i<process_data.length;i++){
            var img = '';
            if(process_data[i].image_name != null && process_data[i].image_name!= ''){
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+process_data[i].image_name;
                img = '<img onclick="displayDialogImage(\''+img_url.replace('/thumbs','')+'\');" src="'+img_url+'" class="list-design-image">';
            }
            
            str+='<tr><td>'+img+'</td><td>'+process_data[i].category_name+'</td><td>'+process_data[i].fabric_name+'</td><td>'+process_data[i].type_name+'</td>\
            <td>'+currency+" "+process_data[i].rate+'</td><td>'+process_data[i].avg+'</td><td>'+currency+" "+process_data[i].cost+'</td><td>'+process_data[i].unit_code+'</td>';
            if(size_variation_types.indexOf(3) >= 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit Size" onclick="getSizeVariations('+process_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            if(designDataObject.fp_editable_fields.length > 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit" onclick="editDesignProcess('+process_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            str+='</tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'packaging_sheet'){
        var packaging_sheet_data = designDataObject.packaging_sheet_data;
        
        str+='<div id="requirements_packaging_sheet">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Packaging Sheet</h2></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr><th>Image</th><th>Name</th><th>Rate</th><th>Quantity</th><th>Cost</th>';
        //if(size_variation_types.indexOf(4) >= 0) str+='<th>Size</th>';
        //if(designDataObject.ps_editable_fields.length > 0) str+='<th>Edit</th>';
        str+='</tr></thead><tbody>';
        
        for(var i=0;i<packaging_sheet_data.length;i++){
            var img = '';
            if(packaging_sheet_data[i].image_name != null && packaging_sheet_data[i].image_name!= ''){
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+packaging_sheet_data[i].image_name;
                img = '<img onclick="displayDialogImage(\''+img_url.replace('/thumbs','')+'\');" src="'+img_url+'" class="list-design-image">';
            }
            str+='<tr><td>'+img+'</td><td>'+packaging_sheet_data[i].packaging_sheet_name+'</td><td>'+currency+" "+packaging_sheet_data[i].rate+'</td>\
            <td>'+packaging_sheet_data[i].avg+'</td><td>'+currency+" "+packaging_sheet_data[i].cost+'</td>';
            //if(size_variation_types.indexOf(4) >= 0) str+='<td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+packaging_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>'
            //if(designDataObject.ps_editable_fields.length > 0) str+='<td class="tbl_actions"><a href="javascript:;" title="Edit" onclick="editDesignPackagingSheet('+packaging_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            str+='</tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'product_process'){
        var product_process_data = designDataObject.product_process_data;
        
        str+='<div id="requirements_product_process">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Production Process</h2></div><div class="col-md-6" >';
        if(designDataObject.pp_editable_fields.length > 0){
            str+='<div class="table_action" style="margin-right:15px;"><a href="javascript:;" onClick="displayDialogBox(\'add-design-product_process\');">Add Row</a>\
            <a href="javascript:;" onclick="deleteRows(\'product_process\');" >Delete Row</a></div>';
        }
        str+='</div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>';
        
        if(designDataObject.pp_editable_fields.length > 0){
            str+='<th><input type="checkbox" name="chk_all_product_process" id="chk_all_product_process" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'product_process\');" /> </th>';
        }
        
        str+='<th>Image</th><th>Body Part</th><th>Name</th><th>Total Cost</th>';
        if(designDataObject.pp_editable_fields.length > 0) str+='<th>Edit</th>';
        str+='</tr></thead><tbody>';

        for(var i=0;i<product_process_data.length;i++){
            var img = '';
            if(product_process_data[i].image_name != null && product_process_data[i].image_name!= ''){
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+product_process_data[i].image_name;
                img = '<img onclick="displayDialogImage(\''+img_url.replace('/thumbs','')+'\');" src="'+img_url+'" class="list-design-image">';
            }

            var cost = (product_process_data[i].cost != null)?product_process_data[i].cost:'';
            
            str+='<tr>';
            if(designDataObject.pp_editable_fields.length > 0){
                str+='<td><input type="checkbox" name="chk_product_process_'+i+'" id="chk_product_process_'+i+'" value="'+product_process_data[i].id+'" class="product_process-chk form-check-input"></td>';
            }
            str+='<td>'+img+'</td><td>'+product_process_data[i].body_part_name+'</td><td>'+product_process_data[i].product_process_name+'</td><td>'+currency+" "+cost+'</td>';
            if(designDataObject.pp_editable_fields.length > 0){
                str+='<td class="tbl_actions"><a href="javascript:;" onclick="editDesignProductProcess('+product_process_data[i].id+');"><i class="fa fa-edit"></i></a></td>';
            }
            str+='</tr>';
        }

        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'specification_sheet'){
        var specification_sheet_data = designDataObject.specification_sheet_data;
        var id_str = '';
        var save_spec_css = (specification_sheet_data.length == 0)?'elem-hidden':'';
        
        if(user_type == '2' && history_data == 0){
            str+='<form name="spec_sheet_form" id="spec_sheet_form"><div id="requirements_specification_sheet">\
            <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Specification Sheet</h2></div><div class="col-md-6" >\
            <div class="table_action">\
            <a href="javascript:;" onClick="displayDialogBox(\'add-design-specification_sheet\');addDesignSpecificationSheet();">Add Row</a>\
            <a href="javascript:;" onClick="updateDesignSpecificationSheet();" class="'+save_spec_css+'">Save Rows</a>\
            <a href="javascript:;" onclick="deleteSpecificationSheet();" >Delete Rows</a>\
            </div></div></div>';
            str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
            <th><input type="checkbox" name="chk_all_specification_sheet" id="chk_all_specification_sheet" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'specification_sheet\');" /> </th>\
            <th>Name</th><th>S</th><th style="color:#FF465F;font-size:15px;">M</th><th>L</th><th>XL</th><th>XXL</th><th>XXXL</th><th>Allowance</th><!--<th>Edit</th>--></tr></thead><tbody>';

            for(var i=0;i<specification_sheet_data.length;i++){
                var id = specification_sheet_data[i].id;

                var size_s = (specification_sheet_data[i].size_s == null || specification_sheet_data[i].size_s == 'undefined')?'':specification_sheet_data[i].size_s;
                var size_m = (specification_sheet_data[i].size_m == null || specification_sheet_data[i].size_m == 'undefined')?'':specification_sheet_data[i].size_m;
                var size_l = (specification_sheet_data[i].size_l == null || specification_sheet_data[i].size_l == 'undefined')?'':specification_sheet_data[i].size_l;
                var size_xl = (specification_sheet_data[i].size_xl == null || specification_sheet_data[i].size_xl == 'undefined')?'':specification_sheet_data[i].size_xl;
                var size_xxl = (specification_sheet_data[i].size_xxl == null || specification_sheet_data[i].size_xxl == 'undefined')?'':specification_sheet_data[i].size_xxl;
                var size_xxxl = (specification_sheet_data[i].size_xxxl == null || specification_sheet_data[i].size_xxxl == 'undefined')?'':specification_sheet_data[i].size_xxxl;
                var allowance = (specification_sheet_data[i].allowance == null || specification_sheet_data[i].allowance == 'undefined')?'':specification_sheet_data[i].allowance;

                str+='<tr><td><input type="checkbox" name="chk_specification_sheet_'+i+'" id="chk_specification_sheet_'+i+'" value="'+specification_sheet_data[i].id+'" class="specification_sheet-chk form-check-input"></td>\
                <td>'+specification_sheet_data[i].specification_sheet_name+'</td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_s_'+id+'" id="spec_sheet_size_s_'+id+'" value="'+size_s+'" /></td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_m_'+id+'" id="spec_sheet_size_m_'+id+'" value="'+size_m+'" /></td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_l_'+id+'" id="spec_sheet_size_l_'+id+'" value="'+size_l+'" /></td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_xl_'+id+'" id="spec_sheet_size_xl_'+id+'" value="'+size_xl+'" /></td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_xxl_'+id+'" id="spec_sheet_size_xxl_'+id+'" value="'+size_xxl+'" /></td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_xxxl_'+id+'" id="spec_sheet_size_xxxl_'+id+'" value="'+size_xxxl+'" /></td>\
                <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_allowance_'+id+'" id="spec_sheet_allowance_'+id+'" value="'+allowance+'" /></td>\
                <!--<td class="tbl_actions"><a href="javascript:;" onclick="editDesignProductProcess('+specification_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
                <td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+specification_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
                </tr>';

                id_str+=id+",";
            }

            id_str = id_str.substring(0,id_str.length-1);
            if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
            str+='<input type="hidden" name="id_str" id="id_str" value="'+id_str+'"></tbody></table></div></div></form>';
        }else{
            str+='<form name="spec_sheet_form" id="spec_sheet_form"><div id="requirements_specification_sheet">\
            <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Specification Sheet</h2></div><div class="col-md-6" ></div></div>';
            str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
            <th>Name</th><th>S</th><th style="color:#FF465F;font-size:15px;">M</th><th>L</th><th>XL</th><th>XXL</th><th>XXXL</th><th>Allowance</th></thead><tbody>';

            for(var i=0;i<specification_sheet_data.length;i++){
                var id = specification_sheet_data[i].id;

                var size_s = (specification_sheet_data[i].size_s == null || specification_sheet_data[i].size_s == 'undefined')?'':specification_sheet_data[i].size_s;
                var size_m = (specification_sheet_data[i].size_m == null || specification_sheet_data[i].size_m == 'undefined')?'':specification_sheet_data[i].size_m;
                var size_l = (specification_sheet_data[i].size_l == null || specification_sheet_data[i].size_l == 'undefined')?'':specification_sheet_data[i].size_l;
                var size_xl = (specification_sheet_data[i].size_xl == null || specification_sheet_data[i].size_xl == 'undefined')?'':specification_sheet_data[i].size_xl;
                var size_xxl = (specification_sheet_data[i].size_xxl == null || specification_sheet_data[i].size_xxl == 'undefined')?'':specification_sheet_data[i].size_xxl;
                var size_xxxl = (specification_sheet_data[i].size_xxxl == null || specification_sheet_data[i].size_xxxl == 'undefined')?'':specification_sheet_data[i].size_xxxl;
                var allowance = (specification_sheet_data[i].allowance == null || specification_sheet_data[i].allowance == 'undefined')?'':specification_sheet_data[i].allowance;

                str+='<tr>\
                <td>'+specification_sheet_data[i].specification_sheet_name+'</td>\
                <td>'+size_s+'</td>\
                <td>'+size_m+'</td>\
                <td>'+size_l+'</td>\
                <td>'+size_xl+'</td>\
                <td>'+size_xxl+'</td>\
                <td>'+size_xxxl+'</td>\
                <td>'+allowance+'</td>\
                </tr>';
            }

            if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
            str+='</tbody></table></div></div></form>';
        }
    }
    
    if(tab_type == 'embroidery'){
        var embroidery_data = designDataObject.embroidery_data;
        
        str+='<div id="requirements_embroidery">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Embroidery</h2></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Type</th><th>Unit</th><th>Cost</th>\
        </tr></thead><tbody>';
        
        for(var i=0;i<embroidery_data.length;i++){
            var img = '';
            if(embroidery_data[i].image_name != null && embroidery_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+embroidery_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+embroidery_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            
            var cost = (embroidery_data[i].cost != null)?embroidery_data[i].cost:'';
            
            str+='<tr>\
            <td>'+img+'</td><td>'+embroidery_data[i].embroidery_type+'</td><td>'+embroidery_data[i].unit_code+'</td>\
            <td>'+currency+" "+cost+'</td>\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'printing'){
        var printing_data = designDataObject.printing_data;
        
        str+='<div id="requirements_printing">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Printing</h2></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Type</th><th>Unit</th><th>Cost</th>\
        </tr></thead><tbody>';
        
        for(var i=0;i<printing_data.length;i++){
            var img = '';
            if(printing_data[i].image_name != null && printing_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+printing_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+printing_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            
            var cost = (printing_data[i].cost != null)?printing_data[i].cost:'';
            
            str+='<tr>\
            <td>'+img+'</td><td>'+printing_data[i].printing_type+'</td><td>'+printing_data[i].unit_code+'</td>\
            <td>'+currency+" "+cost+'</td>\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'garment_cmt'){
        var garment_cmt_data = designDataObject.garment_cmt_data;
        
        str+='<div id="requirements_garment_cmt">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Garment CMT</h2></div><div class="col-md-8" >\
        <div class="table_action"><!--<a href="javascript:;" onclick="deleteRows(\'garment_cmt\');" >Delete Row</a> \
        <a href="javascript:;"  onClick="displayDialogBox(\'add-design-garment_cmt\');">Add Row</a>--></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Name</th><th>Rate</th><th>Unit</th><th>Cost</th>\
        </tr></thead><tbody>';
        
        for(var i=0;i<garment_cmt_data.length;i++){
            var img = '';
            if(garment_cmt_data[i].image_name != null && garment_cmt_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+garment_cmt_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+garment_cmt_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            str+='<tr><td>'+img+'</td><td>'+garment_cmt_data[i].garment_cmt_name+'</td>';
            if(garment_cmt_data[i].garment_cmt_name.toLowerCase() == 'margin'){
                str+='<td>'+garment_cmt_data[i].rate+'</td><td>%</td><td></td>';
            }else{
                str+='<td>'+currency+" "+garment_cmt_data[i].rate+'</td><td>'+garment_cmt_data[i].unit_code+'</td><td>'+currency+" "+garment_cmt_data[i].cost+'</td>';
            }
            str+='</tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    $("#req_tab_data").html(str);
}

function reviewDesign(){
    var form_data = $("#reviewDesignFrm").serialize();
    form_data = form_data+"&version="+designDataObject.design_data.version;
    $("#review_design_submit,#review_design_cancel").attr("disabled",true);
    $("#review_design_spinner").show();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/design/review/"+design_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#review_design_submit,#review_design_cancel").attr("disabled",false);
            $("#review_design_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_design_');
                    if(errors != ''){
                        $("#reviewDesignErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#reviewDesignErrorMessage").html('').hide();
                    $("#reviewDesignSuccessMessage").html(msg.message).show();
                    $("#design_status_sel").hide();
                    $("#reviewer_status_title").html(msg.design_data.reviewer_status);
                    $("#design_status_text").html(msg.review_data.design_status);
                    setTimeout(function(){ $('#review_design_dialog').modal('hide');window.location.reload(); }, 2000);
                    designDataObject.design_data = msg.design_data;
                    designDataObject.review_data = msg.review_data;
                    $("#update_status").val(0);
                    $("#review_id").val(designDataObject.review_data.id);
                }
            }else{
                displayResponseError(msg,"reviewDesignErrorMessage");
            }
        },error:function(obj,status,error){
            $("#reviewDesignErrorMessage").html(error).show();
            $("#review_design_submit,#review_design_cancel").attr("disabled",false);
            $("#review_design_spinner").hide();
        }
    });
}

function displayReviewDesign(){
    $("#reviewDesignSuccessMessage,#reviewDesignErrorMessage").html("").hide();
    $(".invalid-feedback").html('');
    $("#review_design_dialog").modal('show');
}

function uploadDesignDocument(type){
    if(!checkRequisitionStatus()) return false;
    $("#document_type").val(type);
    $("#upload_design_document_form").submit();
}

function deleteDesignDocument(type){
    if(!checkRequisitionStatus()) return false;
    
    $('#confirm_delete_design_document').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_document_btn', function(e) {
        e.preventDefault();
        $("#delete-document-spinner").show();
        $("#delete_document_btn,#delete_document_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{design_id:design_id,type:type},
            url:ROOT_PATH+"/production/deletedesigndocument",
            success: function(msg){	
                $("#delete-document-spinner").hide();
                $("#delete_document_btn,#delete_document_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){    
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        displayStatusText(msg.message,'success');
                        $('#confirm_delete_design_document').modal('hide');
                    }

                    $("#documentLinkDiv_"+type).hide();
                    $("#documentUploadDiv_"+type).show();
                }else{
                    displayResponseError(msg,"deleteDocumentErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteDocumentErrorMessage").html('Error in processing request').show();
                $("#delete-document-spinner").hide();
                $("#delete_document_btn,#delete_document_cancel").attr('disabled',false);
            }
        });
    });
}

function displayProductionReviewDesign(){
    $("#reviewProductionDesignSuccessMessage,#reviewProductionDesignErrorMessage").html("").hide();
    $(".invalid-feedback").html('');
    if(typeof designDataObject.production_review_data.id != 'undefined'){
        if(designDataObject.production_review_data.design_status == 'approved' || designDataObject.production_review_data.design_status == 'rejected'){
            $("#production_status_sel").hide();
        }
        $("#production_comment").val(designDataObject.production_review_data.comment);
        $("#production_status_text").html(designDataObject.production_review_data.design_status);
    }
    $("#review_production_design_dialog").modal('show');
}

function reviewProductionDesign(){
    var form_data = $("#reviewProductionDesignFrm").serialize();
    $("#review_production_design_submit,#review_production_design_cancel").attr("disabled",true);
    $("#review_production_design_spinner").show();
    $(".invalid-feedback").html('');
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/production-head/reviewdesign/"+design_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#review_production_design_submit,#review_production_design_cancel").attr("disabled",false);
            $("#review_production_design_spinner").hide();
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_review_');
                    if(errors != ''){
                        $("#reviewProductionDesignErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#reviewProductionDesignErrorMessage").html('').hide();
                    $("#reviewProductionDesignSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $('#review_production_design_dialog').modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"reviewProductionDesignErrorMessage");
            }
        },error:function(obj,status,error){
            $("#reviewProductionDesignErrorMessage").html(error).show();
            $("#review_production_design_submit,#review_production_design_cancel").attr("disabled",false);
            $("#review_production_design_spinner").hide();
        }
    });
}

function submitProductionReview(){
    var form_data = '';
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/production/requestreview/"+design_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        displayStatusText(msg.message,'error');
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    setTimeout(function(){ window.location.reload(); }, 2000);
                }
            }else{
                displayStatusText("Error in processing request",'error');
            }
        },error:function(obj,status,error){
            displayStatusText(error,'error');
        }
    });
}

function designReviewByPurchaser(){
    $("#purchaserReviewDesignSuccessMessage,#purchaserReviewDesignErrorMessage,.invalid-feedback").html("").hide();
    $("#purchaser_review_sel,#purchaser_review_comment").val('');
    $("#purchaser_design_review_dialog").modal('show');
}

function submitDesignReviewByPurchaser(){
    $("#purchaserReviewDesignSuccessMessage,#purchaserReviewDesignErrorMessage,.invalid-feedback").html("").hide();
    $("#purchaser_review_design_cancel,#purchaser_review_design_submit").attr("disabled",true);
    var form_data = $("#purchaserReviewDesignFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/design/detail/"+design_id+"?action=submit_purchaser_review",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#purchaser_review_design_cancel,#purchaser_review_design_submit").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_design_');
                    if(errors != ''){
                        $("#purchaserReviewDesignErrorMessage").html(msg.message).show();
                    } 
                }else{ 
                    $("#purchaserReviewDesignSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ window.location.reload(); }, 800);
                }
            }else{
                $("#purchaserReviewDesignErrorMessage").html('Error in processing request').show();
            }
        },error:function(obj,status,error){
           $("#purchaserReviewDesignErrorMessage").html(error).show();
           $("#purchaser_review_design_cancel,#purchaser_review_design_submit").attr("disabled",false);
        }
    });
}

function designReviewByManagement(){
    $("#managementReviewDesignSuccessMessage,#managementReviewDesignErrorMessage,.invalid-feedback").html("").hide();
    $("#management_review_sel,#management_review_comment").val('');
    $("#management_design_review_dialog").modal('show');
}

function submitDesignReviewByManagement(){
    $("#managementReviewDesignSuccessMessage,#managementReviewDesignErrorMessage,.invalid-feedback").html("").hide();
    $("#management_review_design_cancel,#management_review_design_submit").attr("disabled",true);
    var form_data = $("#managementReviewDesignFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/design/detail/"+design_id+"?action=submit_management_review",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#management_review_design_cancel,#management_review_design_submit").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_design_');
                    if(errors != ''){
                        $("#managementReviewDesignErrorMessage").html(msg.message).show();
                    } 
                }else{ 
                    $("#managementReviewDesignSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ window.location.reload(); }, 800);
                }
            }else{
                $("#managementReviewDesignErrorMessage").html('Error in processing request').show();
            }
        },error:function(obj,status,error){
           $("#managementReviewDesignErrorMessage").html(error).show();
           $("#management_review_design_cancel,#management_review_design_submit").attr("disabled",false);
        }
    });
}