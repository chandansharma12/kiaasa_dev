@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Warehouse to Store Demands List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Warehouse to Store Demands List'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="invoice_id" id="invoice_id" value="{{request('invoice_id')}}" placeholder="Demand ID" class="form-control">
                    </div>
                    <div class="col-md-1" >
                        <input type="text" name="invoice_no" id="invoice_no" value="{{request('invoice_no')}}" placeholder="Invoice No" class="form-control">
                    </div>
                    <div class="col-md-2" >
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div>
                    @if(in_array($user->user_type,[1,6]))
                        <?php $status_list = array('1'=>'Warehouse Loading','2'=>'Warehouse Dispatched','3'=>'Store Loading','4'=>'Store Loaded'); ?>
                        <div class="col-md-2" >
                            <select name="status" id="status" class="form-control">
                                <option value="">All Statuses</option>
                                @foreach($status_list as $id=>$status)
                                    <?php $sel = ($id == request('status'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$id}}">{{$status}}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-1" >
                        <select name="type" id="type" class="form-control">
                            <option value="">All Types</option>
                            <?php $inv_types = array('1'=>'Inventory Push','2'=>'Complete Inventory Return') ?>
                            @foreach($inv_types as $id=>$type)
                                <?php $sel = ($id == request('type'))?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$type}}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))) {{request('startDate')}} @endif" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;margin-right: 5px;"> to </div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))) {{request('endDate')}} @endif" autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="col-md-1"><button type="submit" name="search" id="search" value="Search" class="btn btn-dialog" title="Search">Search</button></div>
                    
                    @if(in_array($user->user_type,[1,6]) || $is_fake_inventory_user)
                        <div class="col-md-1" ><a href="javascript:;" onclick="createInventoryPushDemand();" class="btn btn-dialog ">Add</a></div>
                    @endif
                    
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Demands List'); ?></div>
                    
                </div>
            </form> 
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div style="width:1870px">&nbsp;</div>
                <div class="table-responsive table-filter" style="width:1860px">
                        
                    <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:13px;">
                        <thead><tr class="header-tr">
                            @if($user->user_type != 6)
                                <th>SNo</th>
                            @endif
                            <th>ID</th>
                            <th>Invoice No</th>
                            <th>Created On</th>
                            <th>Store Name</th>
                            <th>Code</th>
                            <th>Qty</th>
                            <th>Total Amt</th>
                            <th>Transferred</th>
                            <th>Transporter</th>
                            <th>Docket No</th>
                            <th>LR No</th>
                            <th>E-way Bill No</th>
                            <th>Boxes</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Action</th>
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($demands_list);$i++)
                                <?php $total_data = json_decode($demands_list[$i]->total_data,true); ?>
                                <tr>
                                    @if($user->user_type != 6)
                                        <td>{{$sno+$i}}</td>
                                    @endif
                                    <td>{{$demands_list[$i]->id}}</td>
                                    <td>{{$demands_list[$i]->invoice_no}}</td>
                                    <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                    <td>{{$demands_list[$i]->store_name}}</td>
                                    <td>{{$demands_list[$i]->store_id_code}}</td>
                                    <td>{{isset($total_data['total_qty'])?$total_data['total_qty']:''}}</td>
                                    <td>{{isset($total_data['total_value'])?round($total_data['total_value'],2):''}}</td>
                                    <td>{{(isset($transfer_demands[$demands_list[$i]->id]))?$transfer_demands[$demands_list[$i]->id]:0}}</td>
                                    <td>{{$demands_list[$i]->transporter_name}}</td>
                                    <td>{{$demands_list[$i]->docket_no}}</td>
                                    <td>{{$demands_list[$i]->lr_no}}</td>
                                    <td>{{$demands_list[$i]->eway_bill_no}}</td>
                                    <td>{{$demands_list[$i]->boxes_count}}</td>
                                    <td>{{ucwords(str_replace('_',' ',$demands_list[$i]->demand_status))}}</td>
                                    <td>{{!empty($demands_list[$i]->push_demand_id)?'Complete Inv Return':'Inventory Push'}}</td>
                                    <td>
                                        @if(strtolower($demands_list[$i]->demand_status) == 'warehouse_loading' && (in_array($user->user_type,[1,6]) || $is_fake_inventory_user))
                                            <a href="{{url('warehouse/demand/inventory-push/edit/'.$demands_list[$i]->id)}}" ><i title="Edit Push Demand" class="far fa-edit"></i></a> &nbsp;
                                        @endif
                                        <a href="{{url('warehouse/demand/inventory-push/detail/'.$demands_list[$i]->id)}}" ><i title="Push Demand Details" class="fas fa-eye"></i></a> 
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                        <?php /* ?>
                        <tfoot>
                            <tr>
                                <th colspan="4"></th>
                                <th>{{$demands_list_count->inv_count}}</th>
                                <th>{{$demands_list_count->store_cost_price}}</th>
                                <th>{{$transfer_demands_count->inv_count}}</th>
                            </tr>
                        </tfoot> <?php */ ?>
                    </table>

                    {{ $demands_list->withQueryString()->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} demands.</p>
                        
                </div>
                
            </div>
        </div>
    </section>

    <div class="modal fade" id="add_inventory_push_demand_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create Push Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addInventoryPushDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addInventoryPushDemandErrorMessage"></div>

                <form class="" name="addInventoryPushDemandFrm" id="addInventoryPushDemandFrm" type="POST">
                    <div class="modal-body">
                        
                        <div class="form-group" >
                            <label>Store</label>
                            <select name="store_id" id="store_id" class="form-control" onchange="togglePushDemandTransferFields(this.value);">
                                <option value="">Select</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <option value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_store_id"></div>
                        </div>
                        
                        <div class="form-group transfer-field elem-hidden" >
                            <label>Transfer Type</label>
                            <select name="transfer_field" id="transfer_field" class="form-control">
                                <option value="">Select</option>
                                <option value="base_price">Base Price</option>  
                                <option value="sale_price">MRP</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_transfer_field"></div>
                        </div>
                        <div class="form-group transfer-field elem-hidden" >
                            <label>Transfer Percent</label>
                            <input type="text" name="transfer_percent" id="transfer_percent" class="form-control" value="">
                            <div class="invalid-feedback" id="error_validation_transfer_percent"></div>
                        </div>
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add_inventory_push_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="inventory_push_demand_add_cancel" name="inventory_push_demand_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="inventory_push_demand_add_submit" name="inventory_push_demand_add_submit" class="btn btn-dialog" onclick="submitCreateInventoryPushDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($demands_list->total(),5000,'/warehouse/demand/inventory-push/list','Download Warehouse to Store Demands','Demands List'); ?>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.15') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
@endsection
