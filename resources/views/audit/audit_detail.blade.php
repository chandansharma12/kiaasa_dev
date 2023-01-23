@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Audits List','link'=>'audit/list'),array('name'=>'Audit Details')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Audit Details'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" ></div>
                </div>
            </form>
            
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                   <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <tbody>
                                <tr>
                                    <th style="width:20%">Audit No: </th>
                                    <td>{{$audit_data->audit_no}}
                                        <input type="hidden" name="audit_id" id="audit_id" value="{{$audit_data->id}}">
                                    </td>
                                </tr>    
                                <tr>
                                    <th>Audit Type: </th>
                                    <td>{{ucfirst($audit_data->audit_type)}}</td>
                                </tr>  
                                @if($audit_data->audit_type == 'store')
                                    <tr>
                                        <th>Store: </th>
                                        <td>{{$audit_data->store_name}} ({{$audit_data->store_id_code}})</td>
                                    </tr>     
                                @endif
                                <tr>
                                    <th>Audit Status: </th>
                                    <td>{{str_replace('_',' ',$audit_data->audit_status)}}</td>
                                </tr>  
                                <tr>
                                    <th>Members Present: </th>
                                    <td>{{$audit_data->members_present}}</td>
                                </tr> 
                                <tr>
                                    <th>Counter Cash: </th>
                                    <td>{{$audit_data->counter_cash}}</td>
                                </tr> 
                                <tr>
                                    <th>Manual Bills: </th>
                                    <td>{{$audit_data->manual_bills}}</td>
                                </tr> 
                                @if(strtolower($audit_data->audit_status) != 'scan_progress')
                                    <tr>
                                        <th>Scan Complete Date: </th>
                                        <td>{{date('d M Y H:i',strtotime($audit_data->scan_complete_date))}}</td>
                                    </tr> 
                                    <tr>
                                        <th>Scan Complete Comment: </th>
                                        <td>{{$audit_data->scan_complete_comment}}</td>
                                    </tr> 
                                @endif
                                <tr>
                                    <th>Without Barcode SKU List: </th>
                                    <td>{{$audit_data->wbc_sku_list}}</td>
                                </tr> 
                                <tr>
                                    <th>Cash Verified: </th>
                                    <td>{{$audit_data->cash_verified}}</td>
                                </tr> 
                                <tr>
                                    <th>Cash Verified Comments: </th>
                                    <td>{{$audit_data->cash_verified_comment}}</td>
                                </tr>
                                <tr>
                                    <th>System Inventory Count: </th>
                                    <td>{{$audit_data->system_inv_quantity}}</td>
                                </tr>
                                <tr>
                                    <th>System Inventory Cost Price: </th>
                                    <td>{{$audit_data->system_inv_cost_price}}</td>
                                </tr>
                                <tr>
                                    <th>System Inventory Sale Price: </th>
                                    <td>{{$audit_data->system_inv_sale_price}}</td>
                                </tr>
                                <tr>
                                    <th>Store Inventory Count: </th>
                                    <td>{{$audit_data->store_inv_quantity}}</td>
                                </tr>
                                <tr>
                                    <th>Store Inventory Cost Price: </th>
                                    <td>{{$audit_data->store_inv_cost_price}}</td>
                                </tr>
                                <tr>
                                    <th>Store Inventory Sale Price: </th>
                                    <td>{{$audit_data->store_inv_sale_price}}</td>
                                </tr>
                                @if(strtolower($audit_data->audit_status) == 'completed' && $user->user_type == 14 && $audit_data->audit_type == 'store')
                                <tr>
                                    <th>POS Bill: </th>
                                    <td>
                                        @if(!empty($audit_data->pos_order_id))
                                            <a href="{{url('pos/order/detail/'.$audit_data->pos_order_id)}}" class="btn btn-dialog">View Bill</a>
                                        @else
                                            <a href="{{url('audit/bill/create/'.$audit_data->id)}}"  class="btn btn-dialog">Create Bill</a>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                
                                @if(strtolower($audit_data->audit_status) == 'completed' )
                                    <tr>
                                        <th>Download Audit Inventory </th>
                                        <td><a href="javascript:;" title="Download Inventory CSV" onclick="downloadAuditData({{$audit_data->id}});" class="btn btn-dialog"><i title="Audit Details" class="fa fa-download fas-icon"></i> Download</a></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadAuditInventoryDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Download Audit Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadAuditInventoryErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadAuditInventorySuccessMessage"></div>
                
                <form method="post" name="downloadAuditInventoryForm" id="downloadAuditInventoryForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Audit Inventory Records</label>
                                <select name="audit_inventory_count" id="audit_inventory_count" class="form-control" >
                                    <option value="">-- Audit Inventory Records --</option>
                                        @for($i=0;$i<=$inv_count;$i=$i+20000) 
                                            <?php $start = $i+1; $end = $i+20000; ?>
                                            <?php $end = ($end < $inv_count)?$end:$inv_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_audit_inventory_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <button name="downloadAuditInventoryCancel" id="downloadAuditInventoryCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadAuditInventoryBtn" id="downloadAuditInventoryBtn" value="Download AuditInventory" class="btn btn-dialog" onclick="submitDownloadAuditData();">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/audit.js?v=2.25') }}" ></script>
@endsection
