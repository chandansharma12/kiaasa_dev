@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    
    <nav aria-label="breadcrumb" class="page_bbreadcrumb">
        <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'hrm/dashboard'),array('name'=>'User Profile')); ?>
        <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'User Profile'); ?>
    </nav> 

    <!-- End breadcrumb -->
    <!-- Strat Form Area -->  
    <section class="form_area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    
                    <div class="alert alert-success alert-dismissible elem-hidden" id="statusSuccessMessage"></div>
                    <div class="alert alert-danger alert-dismissible elem-hidden"  id="statusErrorMessage"></div>
                    <input type="hidden" name="user_id" id="user_id" value="{{$user_data->id}}"> 
                    <div class="form-row">
                        <div class="col-md-12"> 
                            <div class="requirements_blk"> 
                                
                                <div id="requirementsMessage" class="elem-hidden"></div>

                                <div class="tabs_blk"> 
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                        <img src="" id="headerProfilePictureImg" name="headerProfilePictureImg" class="img-thumbnail img-fluid">
                                        <a class="nav-link active" id="v-pills-Personal-tab" data-toggle="pill" href="#v-pills-Personal" role="tab" aria-controls="v-pills-Personal" aria-selected="true" onclick="displayProfileData('personal');">Personal Details</a>
                                        <a class="nav-link" id="v-pills-Contact-tab" data-toggle="pill" href="#v-pills-Contact" role="tab" aria-controls="v-pills-Contact" aria-selected="false" onclick="displayProfileData('contact');">Contact Details</a>
                                        <a class="nav-link" id="v-pills-Job-tab" data-toggle="pill" href="#v-pills-Job" role="tab" aria-controls="v-pills-Job" aria-selected="false" onclick="displayProfileData('job');">Job Details</a>
                                        <a class="nav-link" id="v-pills-Qualification-tab" data-toggle="pill" href="#v-pills-Qualification" role="tab" aria-controls="v-pills-Qualification" aria-selected="false" onclick="displayProfileData('qualification');">Qualification & Experience</a>
                                        <!--<a class="nav-link" id="v-pills-Salary-tab" data-toggle="pill" href="#v-pills-Salary" role="tab" aria-controls="v-pills-Salary" aria-selected="false" onclick="displayProfileData('salary');">Salary Details</a>-->
                                    </div>
                                    <div class="tab-content" id="v-pills-tabContent">
                                        <div class="tab-pane fade show active" id="v-pills-Fabrics" role="tabpanel" aria-labelledby="v-pills-Fabrics-tab">
                                            <div id="req_tab_data">
                                                <div class="alert alert-success alert-dismissible elem-hidden" id="editProfileSuccessMsg"></div>
                                                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editProfileErrorMsg"></div>
                                                
                                                <div id="personal_profile_div" class="profile-div">
                                                    <h5>Personal Details</h5><hr/>
                                                    <form class="profile-form" name="editPersonalProfileFrm " id="editPersonalProfileFrm" type="POST" enctype="multipart/form-data" >
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Gender</label>
                                                                <select name="userGender_edit" id="userGender_edit" class="form-control form-element" >
                                                                    <option value="">Select One</option>
                                                                    <option value="male">Male</option>
                                                                    <option value="female">Female</option>
                                                                    <option value="other">Other</option>
                                                                </select>   
                                                                <div class="invalid-feedback" id="error_validation_userGender_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Marital Status</label>
                                                                <select name="userMaritalStatus_edit" id="userMaritalStatus_edit" class="form-control form-element" >
                                                                    <option value="">Select One</option>
                                                                    <option value="single">Single</option>
                                                                    <option value="married">Married</option>
                                                                    <option value="other">Other</option>
                                                                </select>    
                                                                <div class="invalid-feedback" id="error_validation_userMaritalStatus_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4" >
                                                                <label>DOB</label>
                                                                <input id="userDOB_edit" type="text" class="form-control form-element date-field" name="userDOB_edit" value="" >
                                                                <div class="invalid-feedback" id="error_validation_userDOB_edit"></div>
                                                            </div>
                                                        </div>    

                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Blood Group</label>
                                                                <select name="userBloodGroup_edit" id="userBloodGroup_edit" class="form-control form-element" >
                                                                    <option value="">Select One</option>
                                                                    <option value="A+">A +</option>
                                                                    <option value="A-">A -</option>
                                                                    <option value="B+">B +</option>
                                                                    <option value="B-">B -</option>
                                                                    <option value="AB+">AB +</option>
                                                                    <option value="AB-">AB -</option>
                                                                    <option value="O+">O +</option>
                                                                    <option value="O-">O -</option>
                                                                </select>   
                                                                <div class="invalid-feedback" id="error_validation_userBloodGroup_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Employee ID</label>
                                                                <input id="userEmployeeId_edit" type="text" class="form-control" name="userEmployeeId_edit" value="" readonly="true">
                                                                <div class="invalid-feedback" id="error_validation_userMaritalStatus_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4" >
                                                                <label>Profile Picture</label>
                                                                <input id="userProfilePicture_edit" type="file" class="form-control form-element" name="userProfilePicture_edit"  >
                                                                <div class="invalid-feedback" id="error_validation_userProfilePicture_edit"></div>
                                                            </div>
                                                        </div>    

                                                        <div id="personal_files_edit" class="elem-hidden files-upload-div">
                                                            
                                                            @for($i=1;$i<=5;$i++)
                                                                <?php $css_class = ($i>1)?'elem-hidden':''; ?>
                                                                <div class="form-row <?php echo $css_class; ?>" id="personal_files_{{$i}}">
                                                                    <div class="form-group col-md-4">
                                                                        <label>Select File</label>
                                                                        <input id="userPersonalFile_edit_{{$i}}" type="file" class="form-control file-element" name="userPersonalFile_edit_{{$i}}" value="" >
                                                                        <div class="invalid-feedback" id="error_validation_userPersonalFile_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Title</label>
                                                                        <input id="userPersonalFileTitle_edit_{{$i}}" type="text" class="form-control file-element" name="userPersonalFileTitle_edit_{{$i}}" value="" >   
                                                                        <div class="invalid-feedback" id="error_validation_userPersonalFileTitle_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4" >
                                                                        <label>Type</label>
                                                                        <select id="userPersonalFileType_edit_{{$i}}" class="form-control file-element" name="userPersonalFileType_edit_{{$i}}"  >
                                                                            <option value="">Select One</option>
                                                                            <option value="PHOTOGRAPH">Employee Photograph</option>
                                                                            <option value="ID_PROOF">ID Proof</option>
                                                                            <option value="OTHER">Other</option>
                                                                        </select>    
                                                                        <div class="invalid-feedback" id="error_validation_userPersonalFileType_edit_{{$i}}"></div>
                                                                    </div>
                                                                </div>
                                                            @endfor
                                                        
                                                            <div class="form-row">
                                                                <div class="form-group col-md-12">
                                                                    <label></label>
                                                                    <button type="button" style="float:right;" class="btn btn-dialog" id="addMoreFilesPersonal" name="addMoreFilesPersonal" onclick="displayAddMoreFiles(this,'personal',5);">Add More <i title="Edit" class="fas fa-plus"></i></button>
                                                                </div>
                                                            </div>    
                                                            
                                                        </div>
                                                        
                                                        <div class="form-row" id="personal_files_view"></div>  

                                                        <div class="form-row">
                                                            <div class="form-group col-md-4 edit-btn-div" >
                                                                <button type="button" class="btn btn-dialog" id="updateProfilePersonalBtn" name="updateProfilePersonalBtn" onclick="toggleProfileBtns('personal','edit'); "><i title="Edit" class="fas fa-edit"></i> Edit</button>
                                                                <button type="button" id="updateProfilePersonalBtn_cancel" class="btn btn-secondary elem-hidden" onclick="toggleProfileBtns('personal','view'); "><i title="Cancel" class="fas fa-caret-left"></i> Cancel</button>
                                                                <button type="button" class="btn btn-dialog elem-hidden" id="updateProfilePersonalBtn_submit" name="updateProfilePersonalBtn_submit" onclick="updatePersonalProfile();"> <i title="Update" class="fas fa-save"></i> Update</button>
                                                            </div>
                                                        </div> 

                                                    </form>
                                                </div>
                                                
                                                <div id="contact_profile_div" class="profile-div elem-hidden">
                                                    <h5>Contact Details</h5><hr/>
                                                    <form class="profile-form" name="editContactProfileFrm" id="editContactProfileFrm" type="POST" enctype="multipart/form-data">
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Address</label>
                                                                <input id="userAddress_edit" type="text" class="form-control form-element" name="userAddress_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userAddress_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>City</label>
                                                                <input id="userCity_edit" type="text" class="form-control form-element" name="userCity_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userCity_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>State</label>
                                                                <select name="userState_edit" id="userState_edit" class="form-control form-element" >
                                                                    <option value="">Select One</option>
                                                                    @for($i=0;$i<count($state_list);$i++)
                                                                        <option value="{{$state_list[$i]->id}}">{{$state_list[$i]->state_name}}</option>
                                                                    @endfor    
                                                                </select>       
                                                                <div class="invalid-feedback" id="error_validation_userState_edit"></div>	
                                                            </div>
                                                        </div>
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Postal Code</label>
                                                                <input id="userPostalCode_edit" type="text" class="form-control form-element" name="userPostalCode_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userPostalCode_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Mobile No</label>
                                                                <input id="userMobileNo_edit" type="text" class="form-control form-element" name="userMobileNo_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userMobileNo_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Home Phone No</label>
                                                                <input id="userHomePhoneNo_edit" type="text" class="form-control form-element" name="userHomePhoneNo_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userHomePhoneNo_edit"></div>	
                                                            </div>
                                                        </div>    
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Email Address</label>
                                                                <input id="userEmailAddress_edit" type="text" class="form-control form-element" name="userEmailAddress_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userEmailAddress_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Emergency Contact Name</label>
                                                                <input id="userEmergencyContactName_edit" type="text" class="form-control form-element" name="userEmergencyContactName_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userEmergencyContactName_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Emergency Contact Relation</label>
                                                                <input id="userEmergencyContactRelation_edit" type="text" class="form-control form-element" name="userEmergencyContactRelation_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userEmergencyContactRelation_edit"></div>	
                                                            </div>
                                                        </div>    
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Emergency Contact Phone No</label>
                                                                <input id="userEmergencyContactPhoneNo_edit" type="text" class="form-control form-element" name="userEmergencyContactPhoneNo_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userEmergencyContactPhoneNo_edit"></div>	
                                                            </div>
                                                        </div>    
                                                        
                                                        <div id="contact_files_edit" class="elem-hidden files-upload-div">
                                                            
                                                            @for($i=1;$i<=5;$i++)
                                                                <?php $css_class = ($i>1)?'elem-hidden':''; ?>
                                                                <div class="form-row <?php echo $css_class; ?>" id="contact_files_{{$i}}">
                                                                    <div class="form-group col-md-4">
                                                                        <label>Select File</label>
                                                                        <input id="userContactFile_edit_{{$i}}" type="file" class="form-control file-element" name="userContactFile_edit_{{$i}}" value="" >
                                                                        <div class="invalid-feedback" id="error_validation_userContactFile_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Title</label>
                                                                        <input id="userContactFileTitle_edit_{{$i}}" type="text" class="form-control file-element" name="userContactFileTitle_edit_{{$i}}" value="" >   
                                                                        <div class="invalid-feedback" id="error_validation_userContactFileTitle_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4" >
                                                                        <label>Type</label>
                                                                        <select id="userContactFileType_edit_{{$i}}" class="form-control file-element" name="userContactFileType_edit_{{$i}}"  >
                                                                            <option value="">Select One</option>
                                                                            <option value="ADDRESS_PROOF">Address Proof</option>
                                                                            <option value="OTHER">Other</option>
                                                                        </select>    
                                                                        <div class="invalid-feedback" id="error_validation_userContactFileType_edit_{{$i}}"></div>
                                                                    </div>
                                                                </div>
                                                            @endfor
                                                        
                                                            <div class="form-row">
                                                                <div class="form-group col-md-12">
                                                                    <label></label>
                                                                    <button type="button" style="float:right;" class="btn btn-dialog" id="addMoreFilesContact" name="addMoreFilesContact" onclick="displayAddMoreFiles(this,'contact',5);">Add More <i title="Edit" class="fas fa-plus"></i></button>
                                                                </div>
                                                            </div>    
                                                            
                                                        </div>
                                                        
                                                        <div class="form-row" id="contact_files_view"></div>  
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4 edit-btn-div">
                                                                <button type="button" class="btn btn-dialog" id="updateProfileContactBtn" name="updateProfileContactBtn" onclick="toggleProfileBtns('contact','edit'); "><i title="Edit" class="fas fa-edit"></i> Edit</button>
                                                                <button type="button" id="updateProfileContactBtn_cancel" class="btn btn-secondary elem-hidden" onclick="toggleProfileBtns('contact','view'); "><i title="Cancel" class="fas fa-caret-left"></i> Cancel</button>
                                                                <button type="button" class="btn btn-dialog elem-hidden" id="updateProfileContactBtn_submit" name="updateProfileContactBtn_submit" onclick="updateContactProfile();"> <i title="Update" class="fas fa-save"></i> Update</button>
                                                            </div>
                                                        </div> 
                                                    </form>    
                                                </div>
                                                
                                                <div id="job_profile_div" class="profile-div elem-hidden">
                                                    <h5>Job Details</h5><hr/>
                                                    <form class="profile-form" name="editJobProfileFrm" id="editJobProfileFrm" type="POST" enctype="multipart/form-data">
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Job Title</label>
                                                                <input id="userJobTitle_edit" type="text" class="form-control form-element" name="userJobTitle_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userJobTitle_edit"></div>	
                                                            </div>
                                                            
                                                            <div class="form-group col-md-4">
                                                                <label>Employment Type</label>
                                                                <select name="userEmploymentType_edit" id="userEmploymentType_edit" class="form-control form-element" >
                                                                    <option value="">Select One</option>
                                                                    <option value="full_time">Full Time</option>
                                                                    <option value="part_time">Part Time</option>
                                                                    <option value="other">Other</option>
                                                                </select>       
                                                                <div class="invalid-feedback" id="error_validation_userEmploymentType_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Employment Status</label>
                                                                <select name="userEmploymentStatus_edit" id="userEmploymentStatus_edit" class="form-control form-element" >
                                                                    <option value="">Select One</option>
                                                                    <option value="permanent">Permanent</option>
                                                                    <option value="temporary">Temporary</option>
                                                                    <option value="contractual">Contractual</option>
                                                                    <option value="other">Other</option>
                                                                </select>       
                                                                <div class="invalid-feedback" id="error_validation_userEmploymentStatus_edit"></div>	
                                                            </div>
                                                        </div>
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Joining Date</label>
                                                                <input id="userJoiningDate_edit" type="text" class="form-control form-element date-field" name="userJoiningDate_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userJoiningDate_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Relieving Date</label>
                                                                <input id="userRelievingDate_edit" type="text" class="form-control form-element date-field" name="userRelievingDate_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userRelievingDate_edit"></div>	
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Overtime Hourly Rate</label>
                                                                <input id="userOvertimeHourlyRate_edit" type="text" class="form-control form-element" name="userOvertimeHourlyRate_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userOvertimeHourlyRate_edit"></div>	
                                                            </div>
                                                        </div>   
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Annual CTC</label>
                                                                <input id="userAnnualCtc_edit" type="text" class="form-control form-element" name="userAnnualCtc_edit" value="" >   
                                                                <div class="invalid-feedback" id="error_validation_userAnnualCtc_edit"></div>	
                                                            </div>
                                                        </div>    
                                                        
                                                        <div id="job_files_edit" class="elem-hidden files-upload-div">
                                                            
                                                            @for($i=1;$i<=5;$i++)
                                                                <?php $css_class = ($i>1)?'elem-hidden':''; ?>
                                                                <div class="form-row <?php echo $css_class; ?>" id="job_files_{{$i}}">
                                                                    <div class="form-group col-md-4">
                                                                        <label>Select File</label>
                                                                        <input id="userJobFile_edit_{{$i}}" type="file" class="form-control file-element" name="userJobFile_edit_{{$i}}" value="" >
                                                                        <div class="invalid-feedback" id="error_validation_userJobFile_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Title</label>
                                                                        <input id="userJobFileTitle_edit_{{$i}}" type="text" class="form-control file-element" name="userJobFileTitle_edit_{{$i}}" value="" >   
                                                                        <div class="invalid-feedback" id="error_validation_userJobFileTitle_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4" >
                                                                        <label>Type</label>
                                                                        <select id="userJobFileType_edit_{{$i}}" class="form-control file-element" name="userJobFileType_edit_{{$i}}"  >
                                                                            <option value="">Select One</option>
                                                                            <option value="EXPERIENCE_CERTIFICATE">Experience Certificate</option>
                                                                            <option value="JOINING_LETTER">Joining Letter</option>
                                                                            <option value="RELIEVING_LETTER">Relieving Letter</option>
                                                                            <option value="OTHER">Other</option>
                                                                        </select>    
                                                                        <div class="invalid-feedback" id="error_validation_userJobFileType_edit_{{$i}}"></div>
                                                                    </div>
                                                                </div>
                                                            @endfor
                                                        
                                                            <div class="form-row">
                                                                <div class="form-group col-md-12">
                                                                    <label></label>
                                                                    <button type="button" style="float:right;" class="btn btn-dialog" id="addMoreFilesJob" name="addMoreFilesJob" onclick="displayAddMoreFiles(this,'job',5);">Add More <i title="Edit" class="fas fa-plus"></i></button>
                                                                </div>
                                                            </div>    
                                                            
                                                        </div>
                                                        
                                                        <div class="form-row" id="job_files_view"></div>  
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4 edit-btn-div">
                                                                <button type="button" class="btn btn-dialog" id="updateProfileJobBtn" name="updateProfileJobBtn" onclick="toggleProfileBtns('job','edit'); "><i title="Edit" class="fas fa-edit"></i> Edit</button>
                                                                <button type="button" id="updateProfileJobBtn_cancel" class="btn btn-secondary elem-hidden" onclick="toggleProfileBtns('job','view'); "><i title="Cancel" class="fas fa-caret-left"></i> Cancel</button>
                                                                <button type="button" class="btn btn-dialog elem-hidden" id="updateProfileJobBtn_submit" name="updateProfileJobBtn_submit" onclick="updateJobProfile();"> <i title="Update" class="fas fa-save"></i> Update</button>
                                                            </div>
                                                        </div> 
                                                    </form> 
                                                </div>
                                                
                                                <div id="qualification_profile_div" class="profile-div elem-hidden">
                                                    <h5>Qualification Details</h5><hr/>
                                                    <form class="profile-form" name="editQualificationProfileFrm" id="editQualificationProfileFrm" type="POST" enctype="multipart/form-data">
                                                        @for($i=1;$i<=10;$i++)
                                                            <?php $css_class = ($i>1)?'elem-hidden':''; ?>
                                                            <div class="form-row <?php echo $css_class; ?>" id="user_qualification_{{$i}}">
                                                                <div class="form-group col-md-2">
                                                                    <label>Type</label>
                                                                    <select name="userQualificationType_edit_{{$i}}" id="userQualificationType_edit_{{$i}}" class="form-control form-element" >
                                                                        <option value="">Select One</option>
                                                                        <option value="high_school">High School</option>
                                                                        <option value="graduation">Graduation</option>
                                                                        <option value="post_graduation">Post Graduation</option>
                                                                        <option value="other">Other</option>
                                                                    </select>   
                                                                    <div class="invalid-feedback" id="error_validation_userQualificationType_edit_{{$i}}"></div>
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>Name</label>
                                                                    <input id="userQualificationName_edit_{{$i}}" type="text" class="form-control form-element" name="userQualificationName_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userQualificationName_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>From Date</label>
                                                                    <input id="userQualificationFrom_edit_{{$i}}" type="text" class="form-control form-element date-field" name="userQualificationFrom_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userQualificationFrom_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>To Date</label>
                                                                    <input id="userQualificationTo_edit_{{$i}}" type="text" class="form-control form-element date-field" name="userQualificationTo_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userQualificationTo_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-3">
                                                                    <label>College/University</label>
                                                                    <input id="userQualificationCollege_edit_{{$i}}" type="text" class="form-control form-element" name="userQualificationCollege_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userQualificationCollege_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-1">
                                                                    <label>Percentage</label>
                                                                    <input id="userQualificationPercentage_edit_{{$i}}" type="text" class="form-control form-element" name="userQualificationPercentage_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userQualificationPercentage_edit_{{$i}}"></div>	
                                                                </div>
                                                            </div>
                                                        @endfor
                                                        
                                                        <div class="form-row elem-hidden" id="user_qualification_add_div">
                                                            <div class="form-group col-md-12">
                                                                <label></label>
                                                                <button type="button" style="float:right;" class="btn btn-dialog" id="addMoreExp" name="addMoreExp" onclick="displayAddMoreQualification(this,'user_qualification',10);">Add More <i title="Edit" class="fas fa-plus"></i></button>
                                                            </div>
                                                        </div>  
                                                        
                                                        <h5>Experience Details</h5><hr/>
                                                        @for($i=1;$i<=10;$i++)
                                                            <?php $css_class = ($i>1)?'elem-hidden':''; ?>
                                                            <div class="form-row <?php echo $css_class; ?>" id="user_exp_{{$i}}">
                                                                <div class="form-group col-md-2">
                                                                    <label>Type</label>
                                                                    <select name="userExpType_edit_{{$i}}" id="userExpType_edit_{{$i}}" class="form-control form-element" >
                                                                        <option value="">Select One</option>
                                                                        <option value="full_time">Full Time</option>
                                                                        <option value="part_time">Part Time</option>
                                                                        <option value="other">Other</option>
                                                                    </select>   
                                                                    <div class="invalid-feedback" id="error_validation_userExpType_edit_{{$i}}"></div>
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>Designation</label>
                                                                    <input id="userExpDesignation_edit_{{$i}}" type="text" class="form-control form-element" name="userExpDesignation_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userExpDesignation_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>Company Name</label>
                                                                    <input id="userExpCompany_edit_{{$i}}" type="text" class="form-control form-element" name="userExpCompany_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userExpCompany_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>From Date</label>
                                                                    <input id="userExpFrom_edit_{{$i}}" type="text" class="form-control form-element date-field" name="userExpFrom_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userExpFrom_edit_{{$i}}"></div>	
                                                                </div>
                                                                <div class="form-group col-md-2">
                                                                    <label>To Date</label>
                                                                    <input id="userExpTo_edit_{{$i}}" type="text" class="form-control form-element date-field" name="userExpTo_edit_{{$i}}" value="" >   
                                                                    <div class="invalid-feedback" id="error_validation_userExpTo_edit_{{$i}}"></div>	
                                                                </div>
                                                                
                                                            </div>
                                                        @endfor
                                                        
                                                        <div class="form-row elem-hidden" id="user_exp_add_div">
                                                            <div class="form-group col-md-12">
                                                                <label></label>
                                                                <button type="button" style="float:right;" class="btn btn-dialog" id="addMoreExp" name="addMoreExp" onclick="displayAddMoreQualification(this,'user_exp',10);">Add More <i title="Edit" class="fas fa-plus"></i></button>
                                                            </div>
                                                        </div>  
                                                        
                                                        <div id="qualification_files_edit" class="elem-hidden files-upload-div">
                                                            
                                                            @for($i=1;$i<=5;$i++)
                                                                <?php $css_class = ($i>1)?'elem-hidden':''; ?>
                                                                <div class="form-row <?php echo $css_class; ?>" id="qualification_files_{{$i}}">
                                                                    <div class="form-group col-md-4">
                                                                        <label>Select File</label>
                                                                        <input id="userQualificationFile_edit_{{$i}}" type="file" class="form-control file-element" name="userQualificationFile_edit_{{$i}}" value="" >
                                                                        <div class="invalid-feedback" id="error_validation_userQualificationFile_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label>Title</label>
                                                                        <input id="userQualificationFileTitle_edit_{{$i}}" type="text" class="form-control file-element" name="userQualificationFileTitle_edit_{{$i}}" value="" >   
                                                                        <div class="invalid-feedback" id="error_validation_userQualificationFileTitle_edit_{{$i}}"></div>	
                                                                    </div>
                                                                    <div class="form-group col-md-4" >
                                                                        <label>Type</label>
                                                                        <select id="userQualificationFileType_edit_{{$i}}" class="form-control file-element" name="userQualificationFileType_edit_{{$i}}"  >
                                                                            <option value="">Select One</option>
                                                                            <option value="DEGREE_SHEET">Degree Sheet</option>
                                                                            <option value="MARK_SHEET">Mark Sheet</option>
                                                                             <option value="EXPERIENCE_CERTIFICATE">Experience Certificate</option>
                                                                            <option value="JOINING_LETTER">Joining Letter</option>
                                                                            <option value="RELIEVING_LETTER">Relieving Letter</option>
                                                                            <option value="OTHER">Other</option>
                                                                        </select>    
                                                                        <div class="invalid-feedback" id="error_validation_userQualificationFileType_edit_{{$i}}"></div>
                                                                    </div>
                                                                </div>
                                                            @endfor
                                                        
                                                            <div class="form-row">
                                                                <div class="form-group col-md-12">
                                                                    <label></label>
                                                                    <button type="button" style="float:right;" class="btn btn-dialog" id="addMoreFilesQualification" name="addMoreFilesQualification" onclick="displayAddMoreFiles(this,'exp',5);">Add More <i title="Edit" class="fas fa-plus"></i></button>
                                                                </div>
                                                            </div>    
                                                            
                                                        </div>
                                                        
                                                        <div class="form-row" id="qualification_files_view"></div> 
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4 edit-btn-div">
                                                                <button type="button" class="btn btn-dialog" id="updateProfileQualificationBtn" name="updateProfileQualificationBtn" onclick="toggleProfileBtns('qualification','edit'); "><i title="Edit" class="fas fa-edit"></i> Edit</button>
                                                                <button type="button" id="updateProfileQualificationBtn_cancel" class="btn btn-secondary elem-hidden" onclick="toggleProfileBtns('qualification','view'); "><i title="Cancel" class="fas fa-caret-left"></i> Cancel</button>
                                                                <button type="button" class="btn btn-dialog elem-hidden" id="updateProfileQualificationBtn_submit" name="updateProfileQualificationBtn_submit" onclick="updateQualificationProfile();"> <i title="Update" class="fas fa-save"></i> Update</button>
                                                            </div>
                                                        </div> 
                                                    </form>
                                                </div>
                                                
                                                <div id="salary_profile_div" class="profile-div elem-hidden">
                                                    <h5>Salary Details</h5><hr/>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>   
                            </div>   
                        </div>   
                    </div>
                    
                </div> 
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="user_profile_file_delete_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="userProfileFileDeleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="userProfileFileDeleteSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete File ?<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_user_profile_file_cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="delete_user_profile_file_btn" name="delete_user_profile_file_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/users.js?v=1.1') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">
    $('#userDOB_edit').datepicker({format: 'dd/mm/yyyy',endDate: '-10y'});$('.date-field').datepicker({format: 'dd/mm/yyyy',endDate: '-0d'});
    $(document).ready(function(){
        getUserProfileData('personal');
    });
</script>
@endsection