@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Assign Demands List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Assign Demands List'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <?php /* ?>
                    <?php $status_list = array('1'=>'Warehouse Loading','2'=>'Warehouse Dispatched','3'=>'Store Loading','4'=>'Store Loaded'); ?>
                    <div class="col-md-2" >
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            @foreach($status_list as $id=>$status)
                                <?php $sel = ($id == request('status'))?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$status}}</option>
                            @endforeach
                        </select>
                    </div> <?php */ ?>
                   
                    <?php /* ?><div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div><?php */ ?>
                    @if(in_array($user->user_type,array(6,12)))
                        <div class="col-md-2" ><a href="javascript:;" onclick="createInventoryAssignDemand();" class="btn btn-dialog ">Create Demand</a></div>
                    @endif
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div class="table-responsive table-filter">

                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>ID</th>
                            <th>Invoice No</th><th>Demand Status</th><th>Created by</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($demands_list);$i++)
                                <tr>
                                    <td> {{$demands_list[$i]->id}}</td>
                                    <td>{{$demands_list[$i]->invoice_no}}</td>

                                    <td>{{ucwords(str_replace('_',' ',$demands_list[$i]->demand_status))}}</td>
                                    <td>{{$demands_list[$i]->user_name}}</td>
                                    <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                    <td>@if($demands_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>
                                        @if(in_array($user->user_type,array(6,12)) && strtolower($demands_list[$i]->demand_status) == 'approval_pending')
                                            <a href="{{url('warehouse/demand/inventory-assign/edit/'.$demands_list[$i]->id)}}" ><i title="Edit Push Demand" class="far fa-edit"></i></a> &nbsp;
                                        @endif
                                        <a href="{{url('warehouse/demand/inventory-assign/detail/'.$demands_list[$i]->id)}}" ><i title="Push Demand Detail" class="fas fa-eye"></i></a> 
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    {{ $demands_list->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} demands.</p>

                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="add_inventory_assign_demand_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create Inventory Assign Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addInventoryAssignDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addInventoryAssignDemandErrorMessage"></div>

                <form class="" name="addInventoryAssignDemandFrm" id="addInventoryAssignDemandFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                Are you sure to create inventory assign demand ?
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="add_inventory_assign_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="inventory_assign_demand_add_cancel" name="inventory_assign_demand_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="inventory_assign_demand_add_submit" name="inventory_assign_demand_add_submit" class="btn btn-dialog" onclick="submitCreateInventoryAssignDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.35') }}" ></script>
@endsection
