@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendors List','link'=>'vendor/list'),array('name'=>'Vendors Materials')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendors Materials'); ?>

    <script>var vendor_id = {{ $vendor_data->id }} </script>

    <section class="form_area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success alert-dismissible" style="display:none" id="savestatusMessage"></div>
                    <div class="alert alert-danger alert-dismissible" style="display:none" id="errortatusMessage"></div>

                    @if(isset($error_msg) && !empty($error_msg))
                        <div class="alert alert-danger">{{$error_msg}} </div>
                    @else 

                        @if(isset($vendor_data->id) && !empty($vendor_data->id))
                            <form id="" name="">
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="Article">Name</label>						
                                        {{$vendor_data->name}}
                                    </div> 
                                    <div class="form-group col-md-3">
                                        <label for="Season">Email</label>						
                                        {{$vendor_data->email}}    
                                    </div> 
                                    <div class="form-group col-md-3">
                                        <label for="Story">Created On</label>						
                                        {{$vendor_data->created_at}}    
                                    </div> 

                                </div>
                            </form> 
                        @endif 

                        <div class="form-row">
                            <div class="col-md-12"> 
                                <div class="requirements_blk"> 
                                    <h2>Material Types</h2>
                                    <div style="display:none;" id="requirementsMessage"></div>

                                    <div class="tabs_blk"> 
                                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                            <a class="nav-link active" id="v-pills-Fabrics-tab" data-toggle="pill" href="#v-pills-Fabrics" role="tab" aria-controls="v-pills-Fabrics" aria-selected="true" onclick="displayTabData('fabric');">Fabrics</a>
                                            <a class="nav-link" id="v-pills-Accessories-tab" data-toggle="pill" href="#v-pills-Accessories" role="tab" aria-controls="v-pills-Accessories" aria-selected="false" onclick="displayTabData('accessories');">Accessories</a>
                                            <a class="nav-link" id="v-pills-Process-tab" data-toggle="pill" href="#v-pills-Process" role="tab" aria-controls="v-pills-Process" aria-selected="false" onclick="displayTabData('process');">Fabric Process</a>
                                            <a class="nav-link" id="v-pills-Product_process-tab" data-toggle="pill" href="#v-pills-Product_process" role="tab" aria-controls="v-pills-Product_process" aria-selected="false" onclick="displayTabData('product_process');">Production Process</a>
                                            <a class="nav-link" id="v-pills-Packaging-tab" data-toggle="pill" href="#v-pills-Packaging" role="tab" aria-controls="v-pills-Packaging" aria-selected="false" onclick="displayTabData('packaging_sheet');">Packaging Sheet</a>
                                        </div>
                                        <div class="tab-content" id="v-pills-tabContent">
                                            <div class="tab-pane fade show active" id="v-pills-Fabrics" role="tabpanel" aria-labelledby="v-pills-Fabrics-tab">
                                                <div id="req_tab_data"></div>
                                                <div class="table-responsive">
                                                    <div class="table_actions">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>   
                                </div>   
                            </div>   
                        </div>

                    @endif 
                </div> 
            </div>
        </div>
    </section>

    <div class="modal fade content-dialog" id="add-material-Fabric" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"  aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Fabric Material</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addFabricErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addFabricSuccessMessage"></div>

                <form class="" name="addFabricFrm" id="addFabricFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Fabric Type</label>
                                <select name="addFabricName" id="addFabricName" class="form-control" onchange="updateFabricNameData(this.value,'add',0,'');">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'fabric_name')
                                            <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_name_id"></div>	
                            </div>

                            <div class="form-group col-md-4">
                                <label>GSM</label>
                                <select name="addFabricGSM" id="addFabricGSM" class="form-control">
                                    <option value="">Select One</option>
                                </select>   
                                <div class="invalid-feedback" id="error_addFabric_gsm_id"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Color</label>
                                <select name="addFabricColor" id="addFabricColor" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($design_items);$i++)
                                        @if(strtolower($design_items[$i]['type']) == 'color')
                                            <option style="background-color: {{$design_items[$i]['description']}}" value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                        @endif
                                    @endfor
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_color_id"></div>	
                            </div>
                        </div>    

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Width</label>
                                <select name="addFabricWidth" id="addFabricWidth" class="form-control">
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_width_id"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Unit</label>
                                <select name="addFabricMasterUnit" id="addFabricMasterUnit" class="form-control">
                                    <option value="">Select One</option>
                                    @for($i=0;$i<count($units);$i++)
                                        <option value="{{$units[$i]['id']}}">{{$units[$i]['name']}}</option>
                                    @endfor
                                </select>    
                                <div class="invalid-feedback" id="error_addFabric_unit_id"></div>	
                            </div>
                            <div class="form-group col-md-4">
                                <label>Content</label>
                                <select name="addFabricContent" id="addFabricContent" class="form-control">
                                    <option value="">Select One</option>
                                </select> 
                                <div class="invalid-feedback" id="error_addFabric_content_id"></div>	
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-fabric-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addFabricBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addFabricBtn_submit" class="btn btn-dialog" onclick="addVendorMaterial(1);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-material-Accessories" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"  aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Accessories Material</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addAccessoriesErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addAccessoriesSuccessMessage"></div>

                <form class="" name="addAccessoriesFrm" id="addAccessoriesFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Category</label>
                            <select name="addAccessoriesCategory" id="addAccessoriesCategory" class="form-control" onchange="getAccessorySubcategories(this.value,'addAccessoriesSubCategory',0);">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'accessory_category' && $design_items[$i]['pid'] == 0)
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor 
                            </select>    
                            <div class="invalid-feedback" id="error_addAccessories_category_id"></div>			
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>SubCategory</label>
                                <select name="addAccessoriesSubCategory" id="addAccessoriesSubCategory" class="form-control" onchange="getAccessorySize(this.value,'addAccessoriesSize',0);">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_addAccessories_subcategory_id"></div>			
                            </div>
                            <div class="form-group col-md-6">
                                <label>Size</label>
                                <select name="addAccessoriesSize" id="addAccessoriesSize" class="form-control">
                                    <option value="">Select One</option>
                                </select>    
                                <div class="invalid-feedback" id="error_addAccessories_size_id"></div>			
                            </div>
                        </div>    
                        <div class="form-group">
                            <label>Color</label>
                            <select name="addAccessoriesColor" id="addAccessoriesColor" class="form-control">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'color')
                                        <option style="background-color: {{$design_items[$i]['description']}}" value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor
                            </select>    
                            <div class="invalid-feedback" id="error_addAccessories_color_id"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-fabric-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addAccessoriesBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addAccessoriesBtn_submit" class="btn btn-dialog" onclick="addVendorMaterial(2);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-material-Process" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"  aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Process Material</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addProcessErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addProcessSuccessMessage"></div>

                <form class="" name="addProcessFrm" id="addProcessFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Category</label>
                            <select name="addProcessCategory" id="addProcessCategory" class="form-control" onchange="getProcessTypes(this.value,'addProcessType',0);">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'process_category')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor   
                            </select> 
                            <div class="invalid-feedback" id="error_validation_category_id"></div>	
                        </div>

                        <div class="form-group">
                            <label>Type</label>
                            <select name="addProcessType" id="addProcessType" class="form-control">
                            </select> 
                            <div class="invalid-feedback" id="error_validation_process_type_id"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-fabric-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addProcessBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addProcessBtn_submit" class="btn btn-dialog" onclick="addVendorMaterial(3);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-material-PackagingSheet" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"  aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Packaging Sheet Material</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addPackagingSheetErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addPackagingSheetSuccessMessage"></div>

                <form class="" name="addPackagingSheetFrm" id="addPackagingSheetFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Category</label>
                            <select name="addPackagingSheetName" id="addPackagingSheetName" class="form-control">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'packaging_sheet')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor       
                            </select> 
                            <div class="invalid-feedback" id="error_validation_packaging_sheet_name_id"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-fabric-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addPackagingSheetBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addPackagingSheetBtn_submit" class="btn btn-dialog" onclick="addVendorMaterial(4);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-material-ProductProcess" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Design Product Process</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="addProductProcessErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addProductProcessSuccessMessage"></div>
                <form class="" name="addProductProcessFrm" id="addProductProcessFrm" type="POST">
                    <div class="modal-body" >
                        <div class="form-group">
                            <label>Name</label>
                            <select name="addProductProcessName" id="addProductProcessName" class="form-control" onchange="updateProductProcessForm();">
                                <option value="">Select One</option>
                                @for($i=0;$i<count($design_items);$i++)
                                    @if(strtolower($design_items[$i]['type']) == 'production_process')
                                        <option value="{{$design_items[$i]['id']}}">{{$design_items[$i]['name']}}</option>
                                    @endif
                                @endfor
                            </select>    
                            <div class="invalid-feedback" id="error_validation_product_process_name_id"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add-ProductProcess-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="addProductProcessBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="addProductProcessBtn_submit" class="btn btn-dialog" onclick="addVendorMaterial(5);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_delete_rows" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Rows<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-rows-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_rows_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_rows_btn" name="delete_rows_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="error_delete_rows" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="modal-body">
                    <h6>Select the records to delete<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-primary" id="error_delete_rows_btn" name="error_delete_rows_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/design_common.js') }}" ></script>
<script src="{{ asset('js/vendors.js') }}" ></script>
@endsection