@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Return Demands: Store to Warehouse')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Return Demands: Store to Warehouse'); ?>
    
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
                    <div class="col-md-2" >
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="Credit/Debit Note No" value="{{request('invoice_no')}}">
                    </div>
                    
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
                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                    
                    @if($user->user_type == 9)
                        <div class="col-md-3" ><input type="button" name="createReturnInvDemand" id="createReturnInvDemand" value="Create Return Inventory Demand" class="btn btn-dialog" onclick="createReturnInventoryDemand();"></div>  
                    @endif
                    
                    @if(in_array($user->user_type,array(1,6,9)))
                        <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Store to Warehouse Demands'); ?></div>
                    @endif
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div id="demandListOverlay" class="table-list-overlay"><div id="demand-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table" cellspacing="0" style="font-size:13px; ">
                            <thead><tr class="header-tr">
                                <th> ID</th>
                                @if($user->user_type == 9)
                                    <th>Debit Note No</th>
                                @else
                                    <th>Credit Note No</th>
                                    <th>Debit Note No</th>
                                @endif
                                <th>Store Name</th>
                                <th>Code</th>
                                <!--<th>Push Demand</th>-->
                                <th>Demand Status</th>
                                <th>Inventory Type</th>
                                <th>Inventory Count</th>
                                <th>Created On</th>
                                <th>Action</th>
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($demands_list);$i++)
                                    <tr>
                                        <td> {{$demands_list[$i]->id}}</td>
                                        @if($user->user_type == 9)
                                            <td>{{$demands_list[$i]->invoice_no}}</td>
                                        @else
                                            <td>{{$demands_list[$i]->credit_invoice_no}}</td>
                                            <td>{{$demands_list[$i]->invoice_no}}</td>
                                        @endif
                                        <td>{{$demands_list[$i]->store_name}}</td>
                                        <td>{{$demands_list[$i]->store_id_code}}</td>
                                        <!--<td>{{$demands_list[$i]->push_demand_invoice_no}}</td>-->
                                        <td>{{CommonHelper::getDemandStatusText('inventory_return_to_warehouse',$demands_list[$i]->demand_status)}}</td>
                                        <td>{{CommonHelper::getInventoryType($demands_list[$i]->inv_type)}}</td>
                                        <td>{{$demands_list[$i]->inv_count}}</td>
                                        <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                        <td>
                                            @if($user->user_type == 9)
                                                @if(strtolower($demands_list[$i]->demand_status) == 'store_loading')
                                                    <a href="{{url('store/demand/inventory-return/edit/'.$demands_list[$i]->id)}}" ><i title="Load Return Demand Inventory" class="far fa-edit"></i></a> &nbsp;
                                                @endif
                                            @endif
                                            
                                            @if($user->user_type == 6)
                                                @if(in_array(strtolower($demands_list[$i]->demand_status),array('warehouse_loading','warehouse_dispatched')))
                                                    <a href="{{url('warehouse/demand/inventory-return/load/'.$demands_list[$i]->id)}}" ><i title="Load Inventory" class="far fa-edit"></i></a>  &nbsp; 
                                                @endif
                                            @endif    
                                            
                                            @if(strtolower($demands_list[$i]->demand_status) != 'store_loading')
                                                <a href="{{url('store/demand/inventory-return/detail/'.$demands_list[$i]->id)}}?type=crt" ><i title="Demand Detail" class="fas fa-eye"></i></a>  &nbsp; 
                                            @endif    
                                        </td>
                                    </tr>
                                @endfor
                                
                                @if($demands_list->count() == 0)
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>
                        </table>

                        {{ $demands_list->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} demands.</p>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="confirm_create_return_inventory_demand_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create Return Inventory Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="createReturnInventoryErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="createReturnInventorySuccessMessage"></div>
                <div class="modal-body">
                    <div class="form-group col-md-12">
                        <label>Inventory Type</label>
                        <select name="inv_type_add" id="inv_type_add"  class="form-control" onchange="toggleInventoryReturnDemandType(this.value);">
                            <option value="">Inventory Type</option>
                            <option value="1">{{CommonHelper::getInventoryType(1)}}</option>
                            <option value="2">{{CommonHelper::getInventoryType(2)}}</option>
                        </select>
                        <div class="invalid-feedback" id="error_validation_inv_type_add"></div>
                    </div>
                    <div class="form-group col-md-12" id="push_demand_div">
                        <label>Push Demand</label>
                        <select name="push_demand_add" id="push_demand_add"  class="form-control" >
                            <option value="">Select</option>
                            @for($i=0;$i<count($store_push_demands);$i++)
                                <option value="{{$store_push_demands[$i]['id']}}">{{$store_push_demands[$i]['invoice_no']}}</option>
                            @endfor    
                        </select>
                        <div class="invalid-feedback" id="error_validation_push_demand_add"></div>
                    </div>
                </div>
                <div class="modal-footer center-footer">
                    <div id="create_return_inventory_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="createReturnInvDemandCancel" name="createReturnInvDemandCancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="createReturnInvDemandSubmit" name="createReturnInvDemandSubmit" onclick="submitCreateReturnInventoryDemand();">Create</button>
                </div>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($demands_list->total(),1000,'/store/demand/inventory-return/list','Download Store to Warehouse Demands List','Store to Warehouse Demands'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.275') }}" ></script>
@endsection
