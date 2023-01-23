@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'HSN Codes GST')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'HSN Codes GST'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="hsnGstErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="hsnGstSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="ID" value="{{request('id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="hsn_code" id="hsn_code" class="form-control" placeholder="HSN Code" value="{{request('hsn_code')}}" />
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" ><input type="button" name="hsn_gst_add_btn" id="hsn_gst_add_btn" value="Add HSN GST" class="btn btn-dialog" onclick="addHsnGst();"></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('HSN Codes GST'); ?></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr"><th>ID</th><th>HSN Code</th><th>Min Amount</th><th>Max Amount</th><th>GST %</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php  ?>
                                @for($i=0;$i<count($hsn_gst_rates);$i++)
                                    <tr>
                                        <td>{{$hsn_gst_rates[$i]['id']}}</td>
                                        <td>{{$hsn_gst_rates[$i]['hsn_code']}}</td>
                                        <td>{{$hsn_gst_rates[$i]['min_amount']}}</td>
                                        <td>@if($max_amount == $hsn_gst_rates[$i]['max_amount']) {{$max_amount_name}}  @else {{$hsn_gst_rates[$i]['max_amount']}} @endif</td>
                                        <td>{{$hsn_gst_rates[$i]['rate_percent']}} %</td>
                                        <td>
                                            <a href="javascript:;" class="setting-list-edit" onclick="editHsnGst({{$hsn_gst_rates[$i]['id']}});"><i title="Edit" class="far fa-edit"></i></a>
                                            &nbsp;&nbsp;
                                            <a href="javascript:;" class="setting-list-edit" onclick="deleteHsnGst({{$hsn_gst_rates[$i]['id']}});"><i title="Delete" class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="add_hsn_gst_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add HSN Code GST</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addHsnGstSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addHsnGstErrorMessage"></div>

                <form class="" name="addHsnGstFrm" id="addHsnGstFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>HSN Code</label>
                            <input id="hsn_code_add" type="text" class="form-control" name="hsn_code_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_hsn_code_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Min Amount</label>
                            <input id="min_amount_add" type="text" class="form-control" name="min_amount_add" value="" maxlength="8">
                            <div class="invalid-feedback" id="error_validation_min_amount_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Max Amount</label>
                            <div class="row" >
                                <div class="col-md-6" >    
                                    <input id="max_amount_add" type="text" class="form-control" name="max_amount_add" value="" maxlength="13">
                                    <div class="invalid-feedback" id="error_validation_max_amount_add"></div>
                                </div>
                                <div class="col-md-6" > 
                                    <label style="margin-top: 15px;"><input onclick="updateGstMaxAmount(this,'max_amount_add');" type="checkbox" name="max_amount_chk_add" id="max_amount_chk_add" > &nbsp;Maximum Amount</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" >
                            <label>GST %</label>
                            <input id="rate_percent_add" type="text" class="form-control" name="rate_percent_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_rate_percent_add"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="hsn_gst_add_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="hsn_gst_add_cancel" name="hsn_gst_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="hsn_gst_add_submit" name="hsn_gst_add_submit" class="btn btn-dialog" onclick="submitAddHsnGst();">Submit</button>
                        
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_hsn_gst_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit HSN Code GST</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editHsnGstSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editHsnGstErrorMessage"></div>

                <form class="" name="editHsnGstFrm" id="editHsnGstFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>HSN Code</label>
                            <input id="hsn_code_edit" type="text" class="form-control" name="hsn_code_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_hsn_code_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Min Amount</label>
                            <input id="min_amount_edit" type="text" class="form-control" name="min_amount_edit" value="" maxlength="8">
                            <div class="invalid-feedback" id="error_validation_min_amount_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Max Amount</label>
                            <div class="row" >
                                <div class="col-md-6" >    
                                    <input id="max_amount_edit" type="text" class="form-control" name="max_amount_edit" value="" maxlength="13">
                                    <div class="invalid-feedback" id="error_validation_max_amount_edit"></div>
                                </div>
                                <div class="col-md-6" > 
                                    <label style="margin-top: 15px;"><input onclick="updateGstMaxAmount(this,'max_amount_edit');" type="checkbox" name="max_amount_chk_edit" id="max_amount_chk_edit" > &nbsp;Maximum Amount</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" >
                            <label>GST %</label>
                            <input id="rate_percent_edit" type="text" class="form-control" name="rate_percent_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_rate_percent_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="hsn_gst_edit_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="hsn_gst_edit_cancel" name="hsn_gst_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="hsn_gst_edit_submit" name="hsn_gst_edit_submit" class="btn btn-dialog" onclick="updateHsnGst();">Submit</button>
                        <input type="hidden" name="hsn_gst_id" id="hsn_gst_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete_hsn_gst_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Delete HSN Code GST</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="deleteHsnGstSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="deleteHsnGstErrorMessage"></div>

                <form class="" name="deleteHsnGstFrm" id="deleteHsnGstFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            Are you sure to delete HSN GST Record ?
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="hsn_gst_delete_cancel" name="hsn_gst_delete_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="hsn_gst_delete_submit" name="hsn_gst_delete_submit" class="btn btn-dialog" onclick="updateHsnGst();">Submit</button>
                        <input type="hidden" name="hsn_gst_id" id="hsn_gst_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php echo CommonHelper::displayDownloadDialogHtml(count($hsn_gst_rates),1000,'/hsn/gst/list','Download HSN Codes GST List','HSN Codes GST'); ?>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js?v=1.1') }}" ></script>
@endsection
