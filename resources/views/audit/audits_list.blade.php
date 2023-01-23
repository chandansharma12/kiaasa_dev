@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audits List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audits List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateVendorStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateVendorStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="audit_id" id="audit_id" class="form-control" placeholder="Audit ID" value="{{request('audit_id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="audit_no" id="audit_no" class="form-control" placeholder="Audit No" value="{{request('audit_no')}}" />
                    </div>
                    <div class="col-md-2" >
                        <select name="audit_type" id="audit_type" class="form-control">
                            <option value="">Audit Type</option>
                            <option value="store" @if(request('audit_type') == 'store') selected @endif>Store</option>
                            <option value="warehouse" @if(request('audit_type') == 'warehouse') selected @endif>Warehouse</option>
                        </select>
                    </div>
                    <div class="col-md-3" >
                        <select name="store_id" id="store_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor    
                        </select>
                    </div>

                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    @if(in_array($user->user_type,[1,14,16]))
                        <div class="col-md-2" ><input type="button" name="addAuditBtn" id="addAuditBtn" value="Create Audit" class="btn btn-dialog"  onclick="addAudit();"></div>
                    @endif
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Audit List'); ?></div>
                </div>
            </form>

            <div class="separator-10">&nbsp;</div>

            <div id="usersContainer">
                <div id="usersListOverlay"><div id="users-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="usersList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                    <th>ID</th>      
                                    <th>Audit No</th>      
                                    <th>Audit Type</th> 
                                    <th>Store Name</th> 
                                    <th>Code</th> 
                                    <th>Auditor</th> 
                                    <th>Status</th>
                                    <th>Created On</th> 
                                    <th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($audits_list);$i++)
                                <tr>
                                    <td> &nbsp;{{$audits_list[$i]->id}}</td>
                                    <td>{{$audits_list[$i]->audit_no}}</td>
                                    <td>{{ucfirst($audits_list[$i]->audit_type)}}</td>
                                    <td>{{$audits_list[$i]->store_name}}</td>
                                    <td>{{$audits_list[$i]->store_id_code}}</td>
                                    <td>{{$audits_list[$i]->auditor_name}}</td>
                                    <td>{{str_replace('_',' ',$audits_list[$i]->audit_status)}}</td>
                                    <td>@if(!empty($audits_list[$i]->created_at)) {{date('d M Y',strtotime($audits_list[$i]->created_at))}} @endif</td>
                                    <td>
                                        @if(strtolower($audits_list[$i]->audit_status) != 'completed')
                                            <a href="javascript:;" class="user-list-edit" onclick="editAudit({{$audits_list[$i]->id}});"><i title="Edit Audit" class="far fa-edit"></i></a>
                                        @endif
                                        &nbsp; <a href="{{url('audit/detail/'.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Audit Details" class="far fa-eye"></i></a>
                                        @if(strtolower($audits_list[$i]->audit_status) == 'scan_progress')
                                            @if($audits_list[$i]->audit_type == 'store')
                                                &nbsp; <a href="{{url('audit/inventory/scan/'.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Scan Inventory" class="far fa-list-alt"></i></a>
                                            @else
                                                &nbsp; <a href="{{url('audit/inventory/scan/wh/'.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Scan Inventory" class="far fa-list-alt"></i></a>
                                            @endif
                                            @if($user->user_type == 14)
                                                @if($audits_list[$i]->audit_type == 'store')
                                                    &nbsp; <a href="{{url('audit/inventory/scan/bulk/'.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Scan Bulk Inventory" class="far fa-list-alt"></i></a>
                                                @else
                                                    &nbsp; <a href="{{url('audit/inventory/scan/bulk/wh/'.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Scan Bulk Inventory" class="far fa-list-alt"></i></a>
                                                @endif
                                            @endif
                                        @else
                                            @if($audits_list[$i]->audit_type == 'store')
                                                &nbsp; <a href="{{url('audit/inventory/scan/detail/'.$audits_list[$i]->id.'?status_id=0')}}" class="user-list-edit"><i title="Inventory Scan Details" class="far fa-eye"></i></a>
                                                &nbsp; <a href="{{url('audit/inventory/report/variance/'.$audits_list[$i]->id)}}" class="user-list-edit" ><i title="Variance Report" class="far fa-list-alt"></i></a>
                                                &nbsp; <a href="{{url('audit/inventory/report/mismatch/'.$audits_list[$i]->id)}}" class="user-list-edit" ><i title="Mismatch Report" class="far fa-list-alt"></i></a>
                                            @else
                                                &nbsp; <a href="{{url('audit/inventory/scan/detail/wh/'.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Inventory Scan Details" class="far fa-eye"></i></a>
                                                &nbsp; <a href="{{url('audit/inventory/report/variance/wh/'.$audits_list[$i]->id)}}/1" class="user-list-edit" ><i title="Variance Report" class="far fa-list-alt"></i></a>
                                            @endif
                                        @endif
                                        @if(strtolower($audits_list[$i]->audit_status) == 'completed')
                                            @if($audits_list[$i]->audit_type == 'store')
                                                &nbsp; <a href="{{url('audit/list?action=get_audit_final_report_pdf&id='.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Audit Final Report PDF" class="far fa-list-alt"></i></a>
                                            @else
                                                &nbsp; <a href="{{url('audit/list?action=get_audit_wh_final_report_pdf&id='.$audits_list[$i]->id)}}" class="user-list-edit"><i title="Audit Final Report PDF" class="far fa-list-alt"></i></a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $audits_list->withQueryString()->links() }}
                        <p>Displaying {{$audits_list->count()}} of {{ $audits_list->total() }} audits.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <div class="modal fade data-modal" id="edit_audit_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Audit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="editAuditSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editAuditErrorMessage"></div>

                <form class="" name="editAuditFrm" id="editAuditFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                             <div class="form-group col-md-6" id="store_id_div_edit">
                                <label>Store</label>
                                <select name="store_id_edit" id="store_id_edit" class="form-control">
                                    <option value="">Store</option>
                                    @for($i=0;$i<count($store_list);$i++)
                                        <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
                                        <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_id_edit"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Present Members</label>
                                <input id="members_present_edit" type="text" class="form-control" name="members_present_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_members_present_edit"></div>
                            </div>
                        </div> 
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Cash in Counter</label>
                                <input id="counter_cash_edit" type="text" class="form-control" name="counter_cash_edit" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_counter_cash_edit"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Manual Bills</label>
                                <input id="manual_bills_edit" type="text" class="form-control" name="manual_bills_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_manual_bills_edit"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Cash Verified</label>
                                <select id="cash_verified_edit" type="text" class="form-control" name="cash_verified_edit" >
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_cash_verified_edit"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Cash Verified Comment</label>
                                <input id="cash_verified_comment_edit" type="text" class="form-control" name="cash_verified_comment_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_cash_verified_comment_edit"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-12" >
                                <label>Without Barcode SKU</label>
                                <input id="wbc_sku_list_edit" type="text" class="form-control" name="wbc_sku_list_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_wbc_sku_list_edit"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Audit Status</label>
                                <input id="audit_status_edit" type="text" class="form-control" name="audit_status_edit" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Audit Type</label>
                                <input id="audit_type_edit" type="text" class="form-control" name="audit_type_edit" value="" readonly="true">
                            </div>
                            <div class="form-group col-md-4" id="complete_audit_div">
                                <label>Complete Audit</label>
                                <div class="separator-10"><br/></div>
                                <input id="audit_complete_edit" type="checkbox"  name="audit_complete_edit" value="1"> Complete Audit
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="audit_edit_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="audit_edit_cancel" name="audit_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="audit_edit_submit" name="audit_edit_submit" class="btn btn-dialog" onclick="updateAudit();">Submit</button>
                        <input type="hidden" name="audit_edit_id" id="audit_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add_audit_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Audit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addAuditSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addAuditErrorMessage"></div>

                <form class="" name="addAuditFrm" id="addAuditFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Audit Type</label>
                                <select name="audit_type_add" id="audit_type_add" class="form-control" onchange="toggleStoreDiv(this.value);">
                                    <option value="">Audit Type</option>
                                    <option value="store">Store</option>   
                                    <option value="warehouse">Warehouse</option>   
                                </select>
                                <div class="invalid-feedback" id="error_validation_audit_type_add"></div>
                            </div>
                            <div class="form-group col-md-4" id="store_id_div" style="display:none;">
                                <label>Store</label>
                                <select name="store_id_add" id="store_id_add" class="form-control">
                                    <option value="">Store</option>
                                    @for($i=0;$i<count($store_list);$i++)
                                        <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
                                        <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_id_add"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Present Members</label>
                                <input id="members_present_add" type="text" class="form-control" name="members_present_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_members_present_add"></div>
                            </div>
                        </div> 
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Cash in Counter</label>
                                <input id="counter_cash_add" type="text" class="form-control" name="counter_cash_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_counter_cash_add"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Manual Bills</label>
                                <input id="manual_bills_add" type="text" class="form-control" name="manual_bills_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_manual_bills_add"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="audit_add_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="audit_add_cancel" name="audit_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="audit_add_submit" name="audit_add_submit" class="btn btn-dialog" onclick="submitAddAudit();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($audits_list->total(),1000,'/audit/list','Download Audits List','Audits'); ?>

@endif
@endsection

@section('scripts')

<script type="text/javascript">
function toggleStoreDiv(val){
    if(val == 'store'){
        $("#store_id_div").show();
    }else{
        $("#store_id_div").hide();
    }
}
</script>

<script src="{{ asset('js/audit.js?v=2.1') }}" ></script>
@endsection
