"use strict";

function editUser(id){
    $("#edit_user_dialog").modal('show');
    $("#user_edit_id").val(id);
    $("#editUserErrorMessage,#editUserSuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#userName").val(msg.user_data.name);
                    $("#userEmail").val(msg.user_data.email);
                    $("#userType").val(msg.user_data.user_type);
                    if(msg.user_data.is_view_modified_inv == 1){
                        $("#viewModifiedReport").prop("checked", true);
                    }else{
                        $("#viewModifiedReport").prop("checked", false);
                    }
                    
                    $("#userParent_div_edit,#userParentPH_div_edit,#userParentWH_div_edit,#userStore_div_edit,#userStoreUserType_div_edit").hide();
                    if(msg.user_data.user_type == 5){
                        $("#userParent_div_edit").slideDown('slow');
                        $("#userParent").val(msg.user_data.parent_user);
                    }else if(msg.user_data.user_type == 2){
                        $("#userParentPH_div_edit").slideDown('slow');
                        $("#userParentPH_edit").val(msg.user_data.parent_user);
                    }else if(msg.user_data.user_type == 6){
                        $("#userParentWH_div_edit").slideDown('slow');
                        $("#userParentWH_edit").val(msg.user_data.parent_user);
                    }else if(msg.user_data.user_type == 9){
                        $("#userStore_div_edit,#userStoreUserType_div_edit").slideDown('slow');
                        $("#userStore_edit").val(msg.user_data.store_id);
                        var store_user_type = msg.user_data.store_owner == 1?2:1;
                        $("#userStoreUserType_edit").val(store_user_type);
                    }

                    if(msg.user_data.other_roles != null && msg.user_data.other_roles != ''){
                        var other_roles = msg.user_data.other_roles.split(",");
                        $("#otheruserType_edit").val(other_roles);
                    }
                }
            }else{
                displayResponseError(msg,'editUserErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editUserErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateUser(){
    var form_data = $("#editUserFrm").serialize();
    $("#user_edit_spinner").show();
    $("#editUserErrorMessage,#editUserSuccessMessage,.invalid-feedback").html('').hide();
    $("#user_edit_submit,#user_edit_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#user_edit_submit,#user_edit_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                $("#user_edit_spinner").hide();

                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editUserSuccessMessage").html(msg.message).show();
                    $("#editUserErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_user_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'editUserErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editUserErrorMessage").html('Error in processing request').show();
            $("#user_edit_spinner").hide();
            $("#user_edit_submit,#user_edit_cancel").attr('disabled',false);
        }
    });
}

function addUser(){
    $("#addUserSuccessMessage,#addUserErrorMessage,.invalid-feedback").html('').hide();
    $("#userName_add,#userEmail_add,#userPassword_add,#userType_add").val('');
    $("#add_user_dialog").modal('show');
}

function submitAddUser(){
    var form_data = $("#addUserFrm").serialize();
    $("#user_add_spinner").show();
    $("#addUserSuccessMessage,#addUserErrorMessage,.invalid-feedback").html('').hide();
    $("#user_add_submit,#user_add_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#user_add_submit,#user_add_cancel").attr('disabled',false);
            $("#user_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addUserSuccessMessage").html(msg.message).show();
                    $("#addUserErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_user_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'addUserSuccessMessage');
            }
        },error:function(obj,status,error){
            $("#addUserErrorMessage").html('Error in processing request').show();
            $("#user_add_spinner").hide();
            $("#user_add_submit,#user_add_cancel").attr('disabled',false);
        }
    });
}

function updateUserStatus(){
    $("#usersListOverlay").show();
    var user_ids = '';
    $(".user-list-chk").each(function(){
        if($(this).is(":checked")){
            user_ids+= $(this).val()+",";
        }
    });
    
    user_ids = user_ids.substring(0,user_ids.length-1);
    var form_data = "action="+$("#user_action").val()+"&ids="+user_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#usersListOverlay").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateUserStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateUserStatusSuccessMessage").html(msg.message).show();
                    $("#updateUserStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#usersListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'updateUserStatusErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateUserStatusErrorMessage").html('Error in processing request').show();
            $("#usersListOverlay").hide();
        }
    });
}

function checkParentUser(selectedUserType,type){
    if(selectedUserType == 5){
        $("#userParent_div_"+type).slideDown("slow");
    }else{
        $("#userParent_div_"+type).slideUp("slow");
    }
    
    if(selectedUserType == 2){
        $("#userParentPH_div_"+type).slideDown("slow");
    }else{
        $("#userParentPH_div_"+type).slideUp("slow");
    }
    
    if(selectedUserType == 6){
        $("#userParentWH_div_"+type).slideDown("slow");
    }else{
        $("#userParentWH_div_"+type).slideUp("slow");
    }
    
    if(selectedUserType == 9){
        $("#userStore_div_"+type).slideDown("slow");
    }else{
        $("#userStore_div_"+type).slideUp("slow");
    }
}

function displayAddMoreFiles(elem,file_type,file_count){
    var count = 1;
    var div_name = file_type+"_files";
    for(var i=2;i<=file_count;i++){
        count++;
        if($("#"+div_name+"_"+i).hasClass('elem-hidden')){
            $("#"+div_name+"_"+i).removeClass('elem-hidden');
            break;
        }
    }
    
    if(count == file_count){
        $(elem).hide();
    }
}

function updatePersonalProfile(){
    $("#editPersonalProfileFrm").submit();
}

$("#editPersonalProfileFrm").on('submit', function(event){
    event.preventDefault();
    $("#editProfileErrorMsg,#editProfileSuccessMsg").html('').hide();
    var user_id = $("#user_id").val();
    var formData = new FormData(this);
    
    formData.append('userGender_edit', $("#userGender_edit").val());
    formData.append('userMaritalStatus_edit', $("#userMaritalStatus_edit").val());
    formData.append('userDOB_edit', $("#userDOB_edit").val());
    formData.append('userBloodGroup_edit', $("#userBloodGroup_edit").val());
    formData.append('userGender_edit', $("#userGender_edit").val());
    for(var i=1;i<=5;i++){
        formData.append('userPersonalFileTitle_edit_'+i, $("#userPersonalFileTitle_edit_"+i).val());
        formData.append('userPersonalFileType_edit_'+i, $("#userPersonalFileType_edit_"+i).val());
    }
    
    $("#edit-personal-profile-spinner").show();
    $("#updateProfilePersonalBtn_submit,#updateProfilePersonalBtn_cancel").attr('disabled',true);
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
        url:ROOT_PATH+"/user/profile/update/"+user_id+"?type=personal&action=update_profile",
        success: function(msg){		
            $("#edit-personal-profile-spinner").hide();
            $("#updateProfilePersonalBtn_submit,#updateProfilePersonalBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editProfileErrorMsg").html(errors).show();
                    } 
                }else{ 
                    $("#editProfileSuccessMsg").html(msg.message).show();
                    getUserProfileData('personal');
                }
            }else{
                displayResponseError(msg,"editProfileErrorMsg");
            }
        },
        error:function(obj,status,error){
            $("#editProfileErrorMsg").html('Error in processing request').show();
            $("#edit-personal-profile-spinner").hide();
            $("#updateProfilePersonalBtn_submit,#updateProfilePersonalBtn_cancel").attr('disabled',false);
        }
    });
});

function updateContactProfile(){
    $("#editContactProfileFrm").submit();
}

$("#editContactProfileFrm").on('submit', function(event){
    event.preventDefault();
    $("#editProfileErrorMsg,#editProfileSuccessMsg").html('').hide();
    var user_id = $("#user_id").val();
    var formData = new FormData(this);
    
    formData.append('userAddress_edit', $("#userAddress_edit").val());
    formData.append('userCity_edit', $("#userCity_edit").val());
    formData.append('userState_edit', $("#userState_edit").val());
    formData.append('userPostalCode_edit', $("#userPostalCode_edit").val());
    formData.append('userMobileNo_edit', $("#userMobileNo_edit").val());
    formData.append('userHomePhoneNo_edit', $("#userHomePhoneNo_edit").val());
    formData.append('userEmailAddress_edit', $("#userEmailAddress_edit").val());
    formData.append('userEmergencyContactName_edit', $("#userEmergencyContactName_edit").val());
    formData.append('userEmergencyContactRelation_edit', $("#userEmergencyContactRelation_edit").val());
    formData.append('userEmergencyContactPhoneNo_edit', $("#userEmergencyContactPhoneNo_edit").val());
    
    for(var i=1;i<=5;i++){
        formData.append('userContactFileTitle_edit_'+i, $("#userContactFileTitle_edit_"+i).val());
        formData.append('userContactFileType_edit_'+i, $("#userContactFileType_edit_"+i).val());
    }
    
    $("#updateProfileContactBtn_submit,#updateProfileContactBtn_cancel").attr('disabled',true);
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
        url:ROOT_PATH+"/user/profile/update/"+user_id+"?type=contact&action=update_profile",
        success: function(msg){		
            
            $("#updateProfileContactBtn_submit,#updateProfileContactBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editProfileErrorMsg").html(errors).show();
                    } 
                }else{ 
                    $("#editProfileSuccessMsg").html(msg.message).show();
                    getUserProfileData('contact');
                }
            }else{
                displayResponseError(msg,"editProfileErrorMsg");
            }
        },
        error:function(obj,status,error){
            $("#editProfileErrorMsg").html('Error in processing request').show();
            $("#updateProfileContactBtn_submit,#updateProfileContactBtn_cancel").attr('disabled',false);
        }
    });
});

function updateJobProfile(){
    $("#editJobProfileFrm").submit();
}

$("#editJobProfileFrm").on('submit', function(event){
    event.preventDefault();
    $("#editProfileErrorMsg,#editProfileSuccessMsg").html('').hide();
    var user_id = $("#user_id").val();
    var formData = new FormData(this);
    
    formData.append('userJobTitle_edit', $("#userJobTitle_edit").val());
    formData.append('userEmploymentType_edit', $("#userEmploymentType_edit").val());
    formData.append('userEmploymentStatus_edit', $("#userEmploymentStatus_edit").val());
    formData.append('userJoiningDate_edit', $("#userJoiningDate_edit").val());
    formData.append('userRelievingDate_edit', $("#userRelievingDate_edit").val());
    
    for(var i=1;i<=5;i++){
        formData.append('userJobFileTitle_edit_'+i, $("#userJobFileTitle_edit_"+i).val());
        formData.append('userJobFileType_edit_'+i, $("#userJobFileType_edit_"+i).val());
    }
    
    $("#updateProfileJobBtn_submit,#updateProfileJobBtn_cancel").attr('disabled',true);
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
        url:ROOT_PATH+"/user/profile/update/"+user_id+"?type=job&action=update_profile",
        success: function(msg){		
            
            $("#updateProfileJobBtn_submit,#updateProfileJobBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editProfileErrorMsg").html(errors).show();
                    } 
                }else{ 
                    $("#editProfileSuccessMsg").html(msg.message).show();
                    getUserProfileData('job');
                }
            }else{
                displayResponseError(msg,"editProfileErrorMsg");
            }
        },
        error:function(obj,status,error){
            $("#editProfileErrorMsg").html('Error in processing request').show();
            $("#updateProfileJobBtn_submit,#updateProfileJobBtn_cancel").attr('disabled',false);
        }
    });
});

function displayProfileData(type){
    $(".profile-div").addClass('elem-hidden');
    $("#"+type+"_profile_div").removeClass('elem-hidden');
    getUserProfileData(type);
}

function toggleProfileBtns(profile_type,action){
    
    var profile_type_upper = capitalizeFirstLetter(profile_type);
    
    if(action == 'edit'){
        $('#updateProfile'+profile_type_upper+'Btn_submit,#updateProfile'+profile_type_upper+'Btn_cancel').removeClass('elem-hidden');
        $("#updateProfile"+profile_type_upper+"Btn").addClass('elem-hidden');
        $("#edit"+profile_type_upper+"ProfileFrm").find(".form-element").attr('readonly',false);
        $("#"+profile_type+"_files_edit").removeClass('elem-hidden');
        $("#"+profile_type+"_files_view").addClass('elem-hidden');
        $("#edit"+profile_type_upper+"ProfileFrm").find(".file-element").val('');
        if(profile_type == 'qualification'){
            $("#user_qualification_add_div").removeClass('elem-hidden');
            $("#user_exp_add_div").removeClass('elem-hidden');
        }
    }else{
        $('#updateProfile'+profile_type_upper+'Btn_submit,#updateProfile'+profile_type_upper+'Btn_cancel').addClass('elem-hidden');
        $("#updateProfile"+profile_type_upper+"Btn").removeClass('elem-hidden');
        $("#edit"+profile_type_upper+"ProfileFrm").find(".form-element").attr('readonly',true);
        $("#"+profile_type+"_files_edit").addClass('elem-hidden');
        $("#"+profile_type+"_files_view").removeClass('elem-hidden');
        if(profile_type == 'qualification'){
            $("#user_qualification_add_div").addClass('elem-hidden');
            $("#user_exp_add_div").addClass('elem-hidden');
        }
    }
}

var user_profile = {}, user_files = {};

function getUserProfileData(profile_type){
    var user_id = $("#user_id").val();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/profile/view/"+user_id+"?action=get_user_profile_data",
        method:"GET",
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#statusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var user_profile = msg.user_profile;
                    var user_files = msg.user_files;
                    var files_count = 0;
                    
                    if(user_profile != null && user_profile != ''){
                        if(profile_type == 'personal'){
                            $("#editPersonalProfileFrm").find(".form-element").attr('readonly',true);
                            $("#userGender_edit").val(user_profile.gender);
                            $("#userMaritalStatus_edit").val(user_profile.marital_status);
                            if(user_profile.dob != '' && user_profile.dob != null){
                                var dob = displayDate(user_profile.dob);
                                $("#userDOB_edit").val(dob);
                            }

                            $("#userBloodGroup_edit").val(user_profile.blood_group);
                            $("#userEmployeeId_edit").val(user_profile.employee_id);

                            if(user_profile.profile_picture != null && user_profile.profile_picture != ''){
                                var url = ROOT_PATH+'/documents/user_files/'+user_profile.user_id+"/thumbs/"+user_profile.profile_picture;
                            }else{
                                var url = ROOT_PATH+'/images/default_profile.png';
                            }

                            $("#headerProfilePictureImg").attr('src',url);
                        }

                        if(profile_type == 'contact'){
                            $("#editContactProfileFrm").find(".form-element").attr('readonly',true);
                            $("#userAddress_edit").val(user_profile.address);
                            $("#userCity_edit").val(user_profile.city);
                            $("#userState_edit").val(user_profile.state_id);
                            $("#userPostalCode_edit").val(user_profile.postal_code);
                            $("#userMobileNo_edit").val(user_profile.mobile_no);
                            $("#userHomePhoneNo_edit").val(user_profile.home_phone_no);
                            $("#userEmailAddress_edit").val(user_profile.personal_email);
                            $("#userEmergencyContactName_edit").val(user_profile.emergency_contact_name);
                            $("#userEmergencyContactRelation_edit").val(user_profile.emergency_contact_relation);
                            $("#userEmergencyContactPhoneNo_edit").val(user_profile.emergency_contact_phone_no);
                        }

                        if(profile_type == 'job'){
                            $("#editJobProfileFrm").find(".form-element").attr('readonly',true);
                            $("#userJobTitle_edit").val(user_profile.job_title);
                            $("#userEmploymentType_edit").val(user_profile.employment_type);
                            $("#userEmploymentStatus_edit").val(user_profile.employment_status);
                            $("#userJoiningDate_edit").val((user_profile.joining_date != '' && user_profile.joining_date != null)?displayDate(user_profile.joining_date):'');
                            $("#userRelievingDate_edit").val((user_profile.relieving_date != '' && user_profile.relieving_date != null)?displayDate(user_profile.relieving_date):'');
                            $("#userOvertimeHourlyRate_edit").val(user_profile.overtime_hourly_rate);
                            $("#userAnnualCtc_edit").val(user_profile.annual_ctc);
                        }
                        
                        if(profile_type == 'qualification'){
                            if(user_profile.qualification_details.length > 0){
                                var qualifications = user_profile.qualification_details;
                                for(var i=0;i<qualifications.length;i++){
                                    var q = i+1;
                                    $("#userQualificationType_edit_"+q).val(qualifications[i].type);
                                    $("#userQualificationName_edit_"+q).val(qualifications[i].name);
                                    $("#userQualificationFrom_edit_"+q).val(qualifications[i].from);//alert(qualifications[i].from);
                                    $("#userQualificationTo_edit_"+q).val(qualifications[i].to);
                                    $("#userQualificationCollege_edit_"+q).val(qualifications[i].college);
                                    $("#userQualificationPercentage_edit_"+q).val(qualifications[i].percentage);
                                    $("#user_qualification_"+q).removeClass('elem-hidden');
                                }
                            }
                            
                            if(user_profile.experience_details.length > 0){
                                var experience = user_profile.experience_details;
                                for(var i=0;i<experience.length;i++){
                                    var q = i+1;
                                    $("#userExpType_edit_"+q).val(experience[i].type);
                                    $("#userExpDesignation_edit_"+q).val(experience[i].designation);
                                    $("#userExpCompany_edit_"+q).val(experience[i].company);
                                    $("#userExpFrom_edit_"+q).val(experience[i].from);
                                    $("#userExpTo_edit_"+q).val(experience[i].to);
                                    $("#user_exp_"+q).removeClass('elem-hidden');
                                }
                            }
                        }

                        $("#"+profile_type+"_files_view").html('');

                        if(user_files.length > 0){
                            var files_str = '<div class="table-responsive table-filter div-files-list"><table class="table table-striped admin-table table-files-list" cellspacing="0" >\
                            <thead><tr class="header-tr"><th>SNo</th><th>File Title</th><th>File Type</th><th>Added On</th><th>Action</th></tr></thead><tbody>';

                            for(var i=0;i<user_files.length;i++){
                                if(user_files[i].file_category == profile_type){
                                    var download_link = '<a href="'+ROOT_PATH+'/documents/user_files/'+user_id+'/'+user_files[i].file_name+'" target="_blank"><i title="Download" class="fas fa-download"></i></a>';
                                    var delete_link = '<a href="javascript:;" onclick="deleteUserProfileFile('+user_files[i].id+','+'\''+profile_type+'\''+');"><i title="Delete" class="fas fa-trash"></i></a>';
                                    files_str+='<tr><td>'+(files_count+1)+'</td><td>'+user_files[i].file_title+'</td><td>'+user_files[i].file_type.replace('_',' ')+'</td><td>'+displayDate(user_files[i].created_at)+'</td><td> '+download_link+'&nbsp;&nbsp;'+delete_link+'</td></tr>'
                                    files_count++;
                                }
                            }

                            files_str+='</tbody></table></div>';
                            if(files_count > 0){
                                $("#"+profile_type+"_files_view").removeClass('elem-hidden').html(files_str);
                            }
                        }
                    }
                    
                    toggleProfileBtns(profile_type,'view');
                }
            }else{
                displayResponseError(msg,'statusErrorMessage');
            }
        },error:function(obj,status,error){
            $("#statusErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteUserProfileFile(id,profile_type){
    
    $("#userProfileFileDeleteErrorMessage,#userProfileFileDeleteSuccessMessage").html('').hide();
    
    $('#user_profile_file_delete_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_user_profile_file_btn', function(e) {
        e.preventDefault();
        $("#delete_user_profile_file_btn,#delete_user_profile_file_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id},
            url:ROOT_PATH+"/user/profile/update/"+user_id+"?action=delete_file",
            success: function(msg){	
                $("#delete_user_profile_file_btn,#delete_user_profile_file_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#userProfileFileDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#userProfileFileDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#user_profile_file_delete_dialog").modal('hide'); getUserProfileData(profile_type); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"userProfileFileDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#userProfileFileDeleteErrorMessage").html('Error in processing request').show();
                $("#delete_user_profile_file_btn,#delete_user_profile_file_cancel").attr('disabled',false);
            }
        });
    });
}

function displayAddMoreQualification(elem,file_type,file_count){
    var count = 1;
    var div_name = file_type;
    for(var i=2;i<=file_count;i++){
        count++;
        if($("#"+div_name+"_"+i).hasClass('elem-hidden')){
            $("#"+div_name+"_"+i).removeClass('elem-hidden');
            break;
        }
    }
    
    if(count == file_count){
        $(elem).hide();
    }
}

function updateQualificationProfile(){
    $("#editQualificationProfileFrm").submit();
}

$("#editQualificationProfileFrm").on('submit', function(event){
    event.preventDefault();
    $("#editProfileErrorMsg,#editProfileSuccessMsg").html('').hide();
    var user_id = $("#user_id").val();
    var formData = new FormData(this);
    
    for(var i=1;i<=10;i++){
        formData.append('userQualificationType_edit_'+i, $("#userQualificationType_edit_"+i).val());
        formData.append('userQualificationName_edit_'+i, $("#userQualificationName_edit_"+i).val());
        formData.append('userQualificationFrom_edit_'+i, $("#userQualificationFrom_edit_"+i).val());
        formData.append('userQualificationTo_edit_'+i, $("#userQualificationTo_edit_"+i).val());
        formData.append('userQualificationCollege_edit_'+i, $("#userQualificationCollege_edit_"+i).val());
        formData.append('userQualificationPercentage_edit_'+i, $("#userQualificationPercentage_edit_"+i).val());
        
        formData.append('userExpType_edit_'+i, $("#userExpType_edit_"+i).val());
        formData.append('userExpDesignation_edit_'+i, $("#userExpDesignation_edit_"+i).val());
        formData.append('userExpCompany_edit_'+i, $("#userExpCompany_edit_"+i).val());
        formData.append('userExpFrom_edit_'+i, $("#userExpFrom_edit_"+i).val());
        formData.append('userExpTo_edit_'+i, $("#userExpTo_edit_"+i).val());
    }
    
    for(var i=1;i<=5;i++){
        formData.append('userJobFileTitle_edit_'+i, $("#userJobFileTitle_edit_"+i).val());
        formData.append('userJobFileType_edit_'+i, $("#userJobFileType_edit_"+i).val());
    }
    
    $("#updateProfileQualificationBtn_submit,#updateProfileQualificationBtn_cancel").attr('disabled',true);
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
        url:ROOT_PATH+"/user/profile/update/"+user_id+"?type=qualification&action=update_profile",
        success: function(msg){		
            
            $("#updateProfileQualificationBtn_submit,#updateProfileQualificationBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editProfileErrorMsg").html(errors).show();
                    } 
                }else{ 
                    $("#editProfileSuccessMsg").html(msg.message).show();
                    getUserProfileData('qualification');
                }
            }else{
                displayResponseError(msg,"editProfileErrorMsg");
            }
        },
        error:function(obj,status,error){
            $("#editProfileErrorMsg").html('Error in processing request').show();
            $("#updateProfileQualificationBtn_submit,#updateProfileQualificationBtn_cancel").attr('disabled',false);
        }
    });
});

function editDailyAttendance(action){
    if(action == 'view'){
        $("#editAttendanceBtn,.attendance-view").removeClass('elem-hidden');
        $("#updateAttendanceBtn_cancel,#updateAttendanceBtn_submit,.attendance-edit").addClass('elem-hidden');
    }else{
        $("#editAttendanceBtn,.attendance-view").addClass('elem-hidden');
        $("#updateAttendanceBtn_cancel,#updateAttendanceBtn_submit,.attendance-edit").removeClass('elem-hidden');
    }
}

function updateDailyAttendance(){
    var form_data = $("#DailyAttendanceFrm").serialize();
    
    $("#updateAttendancErrorMessage,#updateAttendancSuccessMessage,.invalid-feedback").html('').hide();
    $("#updateAttendanceBtn_cancel,#updateAttendanceBtn_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/attendance/edit?action=update_daily_attendance",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#updateAttendanceBtn_cancel,#updateAttendanceBtn_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateAttendancErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateAttendancSuccessMessage").html(msg.message).show();
                    $("#updateAttendancErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'updateAttendancErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateAttendancErrorMessage").html('Error in processing request').show();
            $("#updateAttendanceBtn_cancel,#updateAttendanceBtn_submit").attr('disabled',false);
        }
    });
}

function addUserLeave(){
    $("#leaveAddSuccessMessage,#leaveAddErrorMessage,.invalid-feedback").html('').hide();
    $("#leave_add_dialog .form-control").val('');
    $("#leave_add_dialog").modal('show');
}

function submitAddUserLeave(){
    var form_data = $("#leaveAddFrm").serialize();
    $("#leaveAddSuccessMessage,#leaveAddErrorMessage,.invalid-feedback").html('').hide();
    $("#leave_add_cancel,#leave_add_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/leaves/list?action=add_leave",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#leave_add_cancel,#leave_add_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#leaveAddErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#leaveAddSuccessMessage").html(msg.message).show();
                    $("#leaveAddErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#leave_add_dialog").modal('hide'); window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'leaveAddErrorMessage');
            }
        },error:function(obj,status,error){
            $("#leaveAddErrorMessage").html('Error in processing request').show();
            $("#leave_add_cancel,#leave_add_submit").attr('disabled',false);
        }
    });
}

function editUserLeave(id){
    $("#leaveEditSuccessMessage,#leaveEditErrorMessage,.invalid-feedback").html('').hide();
    $("#leave_edit_dialog .form-control").val('');
    $("#leave_edit_dialog").modal('show');
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/leaves/list?action=get_leave_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#leaveEditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#userId_edit").val(msg.leave_data.user_id);
                    $("#leaveFrom_edit").val(displayDate(msg.leave_data.from_date));
                    $("#leaveTo_edit").val(displayDate(msg.leave_data.to_date));
                    $("#leaveType_edit").val(msg.leave_data.leave_type);
                    $("#leaveStatus_edit").val(msg.leave_data.leave_status);
                    $("#leaveComments_edit").val(msg.leave_data.comments);
                    $("#leave_id_edit").val(msg.leave_data.id);
                }
            }else{
                displayResponseError(msg,'leaveEditErrorMessage');
            }
        },error:function(obj,status,error){
            $("#leaveEditErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateUserLeave(){
    var form_data = $("#leaveEditFrm").serialize();
    $("#leaveEditSuccessMessage,#leaveEditErrorMessage,.invalid-feedback").html('').hide();
    $("#leave_edit_cancel,#leave_edit_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/leaves/list?action=update_leave",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#leave_edit_cancel,#leave_edit_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#leaveEditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#leaveEditSuccessMessage").html(msg.message).show();
                    $("#leaveEditErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#leave_edit_dialog").modal('hide'); window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'leaveEditErrorMessage');
            }
        },error:function(obj,status,error){
            $("#leaveEditErrorMessage").html('Error in processing request').show();
            $("#leave_edit_cancel,#leave_edit_submit").attr('disabled',false);
        }
    });
}

function deleteUserLeave(id){
    $("#leaveDeleteSuccessMessage,#leaveDeleteErrorMessage").html('').hide();
    
    $('#leave_delete_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#leave_delete_submit', function(e) {
        e.preventDefault();
        $("#leave_delete_submit,#leave_delete_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id},
            url:ROOT_PATH+"/user/leaves/list?action=delete_leave",
            success: function(msg){	
                $("#leave_delete_submit,#leave_delete_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#leaveDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#leaveDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#leave_delete_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deletePushDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#leaveDeleteErrorMessage").html('Error in processing request').show();
                $("#leave_delete_submit,#leave_delete_cancel").attr('disabled',false);
            }
        });
    });
}

function addUserOverTime(){
    $("#overtimeAddSuccessMessage,#overtimeAddErrorMessage,.invalid-feedback").html('').hide();
    $("#overtime_add_dialog .form-control").val('');
    $("#overtime_add_dialog").modal('show');
}

function submitAddUserOverTime(){
    var form_data = $("#overtimeAddFrm").serialize();
    $("#overtimeAddSuccessMessage,#overtimeAddErrorMessage,.invalid-feedback").html('').hide();
    $("#overtime_add_cancel,#overtime_add_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/overtime/list?action=add_overtime",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#overtime_add_cancel,#overtime_add_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#overtimeAddErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#overtimeAddSuccessMessage").html(msg.message).show();
                    $("#overtimeAddErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#overtime_add_dialog").modal('hide'); window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'overtimeAddErrorMessage');
            }
        },error:function(obj,status,error){
            $("#overtimeAddErrorMessage").html('Error in processing request').show();
            $("#overtime_add_cancel,#overtime_add_submit").attr('disabled',false);
        }
    });
}


function editUserOverTime(id){
    $("#overtimeEditSuccessMessage,#overtimeEditErrorMessage,.invalid-feedback").html('').hide();
    $("#overtime_edit_dialog .form-control").val('');
    $("#overtime_edit_dialog").modal('show');
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/overtime/list?action=get_overtime_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#overtimeEditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#userId_edit").val(msg.overtime_data.user_id);
                    $("#overTimeDate_edit").val(displayDate(msg.overtime_data.overtime_date));
                    $("#overTimeHours_edit").val(msg.overtime_data.overtime_hours);
                    
                    $("#overtimeStatus_edit").val(msg.overtime_data.overtime_status);
                    $("#overtimeComments_edit").val(msg.overtime_data.comments);
                    $("#overtime_id_edit").val(msg.overtime_data.id);
                }
            }else{
                displayResponseError(msg,'overtimeEditErrorMessage');
            }
        },error:function(obj,status,error){
            $("#overtimeEditErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateUserOverTime(){
    var form_data = $("#overtimeEditFrm").serialize();
    $("#overtimeEditSuccessMessage,#overtimeEditErrorMessage,.invalid-feedback").html('').hide();
    $("#overtime_edit_cancel,#overtime_edit_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/overtime/list?action=update_overtime",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#overtime_edit_cancel,#overtime_edit_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#overtimeEditErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#overtimeEditSuccessMessage").html(msg.message).show();
                    $("#overtimeEditErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#overtime_edit_dialog").modal('hide'); window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'overtimeEditErrorMessage');
            }
        },error:function(obj,status,error){
            $("#overtimeEditErrorMessage").html('Error in processing request').show();
            $("#overtime_edit_cancel,#overtime_edit_submit").attr('disabled',false);
        }
    });
}

function deleteUserOverTime(id){
    $("#overtimeDeleteSuccessMessage,#overtimeDeleteErrorMessage").html('').hide();
    
    $('#overtime_delete_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#overtime_delete_submit', function(e) {
        e.preventDefault();
        $("#overtime_delete_submit,#overtime_delete_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id},
            url:ROOT_PATH+"/user/overtime/list?action=delete_overtime",
            success: function(msg){	
                $("#overtime_delete_submit,#overtime_delete_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#overtimeDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#overtimeDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#overtime_delete_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deletePushDemandItemsErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#overtimeDeleteErrorMessage").html('Error in processing request').show();
                $("#overtime_delete_submit,#overtime_delete_cancel").attr('disabled',false);
            }
        });
    });
}

function addUserSalary(){
    $("#salaryAddSuccessMessage,#salaryAddErrorMessage,.invalid-feedback").html('').hide();
    $("#salary_add_dialog .form-control").val('');
    $("#salary_add_dialog").modal('show');
}

function submitAddUserSalary(){
    var form_data = $("#salaryAddFrm").serialize();
    $("#salaryAddSuccessMessage,#salaryAddErrorMessage,.invalid-feedback").html('').hide();
    $("#salary_add_cancel,#salary_add_submit").attr('disabled',true);
    var user_id = $("#user_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/salary/list/"+user_id+"?action=add_salary",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#salary_add_cancel,#salary_add_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#salaryAddErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#salaryAddSuccessMessage").html(msg.message).show();
                    $("#salaryAddErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#salary_add_dialog").modal('hide'); window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'salaryAddErrorMessage');
            }
        },error:function(obj,status,error){
            $("#salaryAddErrorMessage").html('Error in processing request').show();
            $("#salary_add_cancel,#salary_add_submit").attr('disabled',false);
        }
    });
}

function updateUserSalary(){
    var form_data = $("#salaryEditForm").serialize();
    $("#updateSalaryErrorMessage,#updateSalarySuccessMessage,.invalid-feedback").html('').hide();
    $("#update_salary_btn,#update_salary_cancel").attr('disabled',true);
    var salary_id = $("#salary_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/salary/edit/"+salary_id+"?action=update_salary",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#update_salary_btn,#update_salary_cancel").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateSalaryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateSalarySuccessMessage").html(msg.message).show();
                    $("#updateSalaryErrorMessage,.invalid-feedback").html('').hide();
                    document.getElementById("updateSalarySuccessMessage").scrollIntoView();
                    setTimeout(function(){  window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'updateSalaryErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateSalaryErrorMessage").html('Error in processing request').show();
            $("#update_salary_btn,#update_salary_cancel").attr('disabled',false);
        }
    });
}


function updateUserAttendance(){
    var form_data = $("#userAttendanceform").serialize();
    $("#updateAttendanceErrorMessage,#updateAttendanceSuccessMessage,.invalid-feedback").html('').hide();
    $("#update_attendance_btn,#update_attendance_cancel").attr('disabled',true);
    var user_id = $("#user_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/attendance/edit/"+user_id+"?action=update_attendance",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#update_attendance_btn,#update_attendance_cancel").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateAttendanceErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateAttendanceSuccessMessage").html(msg.message).show();
                    $("#updateAttendanceErrorMessage,.invalid-feedback").html('').hide();
                    document.getElementById("updateAttendanceSuccessMessage").scrollIntoView();
                    setTimeout(function(){  window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,'updateAttendanceErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateAttendanceErrorMessage").html('Error in processing request').show();
            $("#update_attendance_btn,#update_attendance_cancel").attr('disabled',false);
        }
    });
}

function getRoleUsers(role_id,user_id){
   ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/user/activity/list?action=get_role_users&role_id="+role_id,
        method:"GET",
        success:function(msg){
           if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateUserStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var users = msg.users_list,options_str = '<option value="">-- User --</option>';
                    for(var i=0;i<users.length;i++){
                        var sel =  (user_id != '' && user_id == users[i].id)?'selected':'';
                        options_str+="<option "+sel+" value='"+users[i].id+"'>"+users[i].name+"</option>";
                    }
                    $("#user_id").html(options_str);
                }
            }else{
                displayResponseError(msg,'updateUserStatusErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateUserStatusErrorMessage").html('Error in processing request').show();
            
        }
    });
}

function downloadStoreToCustomerReport(){
    $("#state_list_div_download").html($("#state_list_div").html());
    $("#store_list_div_download").html($("#store_list_div").html());
    $("#store_type_div_download").html($("#store_type_div").html());
    $("#date_range_div_download").html($("#date_range_div").html());
    $('.input-daterange').datepicker({format: 'dd-mm-yyyy'});
    $("#report_download_dialog").modal('show');
}

function submitDownloadStoreToCustomerReport(){
    var str = '';
    if($("#report_download_dialog #startDate").val() != '' && $("#report_download_dialog #endDate").val() != ''){
        str+="&startDate="+$("#report_download_dialog #startDate").val()+"&endDate="+$("#report_download_dialog #endDate").val();
    }
    
    if($("#report_download_dialog #state_id").val() != ''){
        str+="&state_id="+$("#report_download_dialog #state_id").val()
    }
    
    if($("#report_download_dialog #store_id").val() != ''){
        str+="&store_id="+$("#report_download_dialog #store_id").val()
    }
    
    if($("#report_download_dialog #store_type").val() != ''){
        str+="&store_type="+$("#report_download_dialog #store_type").val()
    }
    
    var report_type = $("#report_type").val();
    
    var url = ROOT_PATH+"/report/store/to/customer?action=download_csv&report_type="+report_type+str;
    window.location.href = url;
}

function downloadStoreToStoreReport(){
    $("#store_from_div_download").html($("#store_from_div").html());
    $("#store_to_div_download").html($("#store_to_div").html());
    $("#date_range_div_download").html($("#date_range_div").html());
    $('.input-daterange').datepicker({format: 'dd-mm-yyyy'});
    $("#report_download_dialog").modal('show');
}

function submitDownloadStoreToStoreReport(){
    var str = '';
    if($("#report_download_dialog #startDate").val() != '' && $("#report_download_dialog #endDate").val() != ''){
        str+="&startDate="+$("#report_download_dialog #startDate").val()+"&endDate="+$("#report_download_dialog #endDate").val();
    }
    
    if($("#report_download_dialog #from_store_id").val() != ''){
        str+="&from_store_id="+$("#report_download_dialog #from_store_id").val()
    }
    
    if($("#report_download_dialog #to_store_id").val() != ''){
        str+="&to_store_id="+$("#report_download_dialog #to_store_id").val()
    }
    
    var report_type = $("#report_type").val();
    
    var url = ROOT_PATH+"/report/store/to/store?action=download_csv&report_type="+report_type+str;
    window.location.href = url;
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
                    $("#store_id").html(options_str);
                    $("#reportDownloadFrm #store_id").html(options_str);
                }
            }else{
                displayResponseError(msg,'reportErrorMessage');
            }
        },error:function(obj,status,error){
            $("#reportErrorMessage").html('Error in processing request').show();
        }
    });
}

function downloadClosingStockReport(){
    $("#date_range_div_download").html($("#date_range_div").html());
    $('.input-daterange').datepicker({format: 'dd-mm-yyyy'});
    $("#report_download_dialog").modal('show');
}

function submitDownloadClosingStockReport(){
    var str = '';
    if($("#report_download_dialog #startDate").val() != '' && $("#report_download_dialog #endDate").val() != ''){
        str+="&startDate="+$("#report_download_dialog #startDate").val()+"&endDate="+$("#report_download_dialog #endDate").val();
    }
    
    if($("#report_download_dialog #store_id").val() == -1){
        str+="&s_id="+$("#report_download_dialog #store_id").val()
    }else if($("#report_download_dialog #store_id").val() != ''){
        str+="&store_id="+$("#report_download_dialog #store_id").val()
    }
    
    var url = ROOT_PATH+"/report/closing-stock/detail?action=download_csv"+str;
    window.location.href = url;
}

function downloadStoreClosingStockReport(){
    $("#date_range_store_div_download").html($("#date_range_div").html());
    $('.input-daterange').datepicker({format: 'dd-mm-yyyy'});
    $("#report_store_download_dialog").modal('show');
}

function submitDownloadStoreClosingStockReport(){
    var str = '';
    if($("#report_store_download_dialog #startDate").val() != '' && $("#report_store_download_dialog #endDate").val() != ''){
        str+="&startDate="+$("#report_store_download_dialog #startDate").val()+"&endDate="+$("#report_store_download_dialog #endDate").val();
    }
    
    if($("#report_store_download_dialog #store_id").val() == -1){
        str+="&s_id="+$("#report_store_download_dialog #store_id").val()
    }else if($("#report_store_download_dialog #store_id").val() != ''){
        str+="&s_id="+$("#report_store_download_dialog #store_id").val()
    }
    
    var url = ROOT_PATH+"/report/closing-stock/detail?action=download_csv"+str;
    window.location.href = url;
}