@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store to Store Inventory Transfer')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store to Store Inventory Transfer'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="GET">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="invoice_id" id="invoice_id" value="{{request('invoice_id')}}" placeholder="Demand ID" class="form-control">
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="Invoice No" value="{{request('invoice_no')}}">
                    </div>
                    @if($user->user_type == 9)
                        <div class="col-md-3" >
                            <select name="page_type" id="page_type" class="form-control" >
                            @foreach($page_types as $id=>$name)
                                <?php $sel = ($id == $page_type && $page_type != '')?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$name}}</option>
                            @endforeach
                            </select>
                        </div>
                    @endif
                    
                    @if($user->user_type != 9)
                        <div class="col-md-3" >
                            <select name="store_id" id="store_id" class="form-control">
                                <option value="">-- All Stores --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor    
                            </select>
                        </div>
                    @endif
                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    @if($user->user_type == 9)
                        <div class="col-md-2" ><a href="javascript:;" onclick="createInventoryTransferStoreDemand();" class="btn btn-dialog ">Create Demand</a></div>
                    @endif
                    
                    @if(in_array($user->user_type,array(1,9)))
                        <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Store to Store Inventory Transfer Demands'); ?></div>
                    @endif
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:13px; ">
                        <thead><tr class="header-tr">
                            <th>ID</th><th>Invoice No</th><th>From Store Name</th><th>Code</th><th>To Store Name</th><th>Code</th><th>Demand Status</th><th>Inventory</th><th>Created On</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($demands_list);$i++)
                                <?php $total_data = json_decode($demands_list[$i]->total_data,true); ?>
                                <tr>
                                    <td>{{$demands_list[$i]->id}}</td>
                                    <td>{{$demands_list[$i]->invoice_no}}</td>
                                    <td>{{$demands_list[$i]->from_store_name}}</td>
                                    <td>{{$demands_list[$i]->from_store_id_code}}</td>
                                    <td>{{$demands_list[$i]->to_store_name}}</td>
                                    <td>{{$demands_list[$i]->to_store_id_code}}</td>
                                    <td>{{CommonHelper::getDemandStatusText('inventory_transfer_to_store',$demands_list[$i]->demand_status)}}</td>
                                    <td>{{isset($total_data['total_qty'])?$total_data['total_qty']:''}}</td>
                                    <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                    <td>
                                        @if($user->user_type == 9)
                                            @if($store_user->id == $demands_list[$i]->store_id && in_array(strtolower($demands_list[$i]->demand_status),array('loaded','store_loading')) )
                                                <a href="{{url('store/demand/inventory-transfer-store/load/'.$demands_list[$i]->id)}}" ><i title="Load Demand Inventory" class="far fa-edit"></i></a> &nbsp;
                                            @endif
                                            @if($store_user->id == $demands_list[$i]->from_store_id && in_array(strtolower($demands_list[$i]->demand_status),array('loading')) )
                                                <a href="{{url('store/demand/inventory-transfer-store/edit/'.$demands_list[$i]->id)}}" ><i title="Edit Demand Inventory" class="far fa-edit"></i></a> &nbsp;
                                            @endif
                                        @endif
                                        <a href="{{url('store/demand/inventory-transfer-store/detail/'.$demands_list[$i]->id)}}" ><i title="Demand Detail" class="fas fa-eye"></i></a> 
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    {{ $demands_list->withQueryString()->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} demands.</p>
                </div>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="add_inventory_transfer_store_demand_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create Inventory Transfer Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addInventoryTransferStoreDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addInventoryTransferStoreDemandErrorMessage"></div>

                <form class="" name="addInventoryTransferStoreDemandFrm" id="addInventoryTransferStoreDemandFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Store</label>
                                <select name="store_id" id="store_id" class="form-control" >
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($store_list);$i++)
                                        <option value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_id"></div>
                            </div>
                            
                            @if(!empty($store_user) && $store_user->store_info_type == 3)
                                <div class="form-group transfer-field" >
                                    <label>Transfer Type</label>
                                    <select name="transfer_field" id="transfer_field" class="form-control">
                                        <option value="">Select</option>
                                        <option value="store_cost_price">Store Cost Price</option>  
                                        <option value="sale_price">MRP</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_transfer_field"></div>
                                </div>
                                <div class="form-group transfer-field" >
                                    <label>Transfer Margin Percent</label>
                                    <input type="text" name="transfer_percent" id="transfer_percent" class="form-control" value="">
                                    <div class="invalid-feedback" id="error_validation_transfer_percent"></div>
                                </div>
                            @endif
                            
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add_inventory_transfer_store_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="inventory_transfer_store_demand_add_cancel" name="inventory_transfer_store_demand_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="inventory_transfer_store_demand_add_submit" name="inventory_transfer_store_demand_add_submit" class="btn btn-dialog" onclick="submitCreateInventoryTransferStoreDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($demands_list->total(),1000,'/store/demand/inventory-transfer-store/list','Download Store to Store Inventory Transfer Demands List','Demands List'); ?>
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.58') }}" ></script>
@endsection