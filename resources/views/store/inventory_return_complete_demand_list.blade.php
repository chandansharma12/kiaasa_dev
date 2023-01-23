@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Return Complete Demands')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Return Complete Demands'); ?>
    
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
                        <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="Debit Note No" value="{{request('invoice_no')}}">
                    </div>
                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    @if($user->user_type == 9)
                        <div class="col-md-3" ><input type="button" name="createReturnInvDemand" id="createReturnInvDemand" value="Return Complete Inventory" class="btn btn-dialog" onclick="createCompleteReturnInventoryDemand();"></div>  
                    @endif
                    
                    @if(in_array($user->user_type,array(1,6)))
                        <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Inventory Return Complete Demand List'); ?></div>
                    @endif
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                        <th> ID</th>
                        @if($user->user_type == 9 || $user->user_type == 1)
                            <th>Debit Note No</th>
                        @endif
                        @if($user->user_type == 6 || $user->user_type == 1)
                            <th>Credit Note No</th>
                        @endif
                        <th>Store Name</th><th>Code<th>Demand Status</th><th>Tax Invoice</th><th>Created On</th><!--<th>Status</th>-->
                        <th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($demands_list);$i++)
                                <tr>
                                    <td> {{$demands_list[$i]->id}}</td>
                                    @if($user->user_type == 9 || $user->user_type == 1)
                                        <td>{{$demands_list[$i]->invoice_no}}</td>
                                    @endif
                                    @if($user->user_type == 6 || $user->user_type == 1)
                                        <td>{{$demands_list[$i]->credit_invoice_no}}</td>
                                    @endif
                                    <td>{{$demands_list[$i]->store_name}}</td>
                                    <td>{{$demands_list[$i]->store_id_code}}</td>
                                    <td>{{ucwords(str_replace('_',' ',$demands_list[$i]->demand_status))}}</td>
                                    <td>{{$demands_list[$i]->tax_invoice_no}}</td>
                                    <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                    <td>
                                        @if($user->user_type == 9)
                                            <a href="{{url('store/demand/inventory-return-complete/detail/'.$demands_list[$i]->id)}}?type=dbt" ><i title="Demand Detail" class="fas fa-eye"></i></a>  &nbsp; 
                                        @endif
                                        @if($user->user_type == 6 || $user->user_type == 1)
                                            <a href="{{url('store/demand/inventory-return-complete/detail/'.$demands_list[$i]->id)}}?type=crt" ><i title="Demand Detail" class="fas fa-eye"></i></a>  &nbsp; 
                                        @endif
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

    <div class="modal fade" id="confirm_create_return_inventory_demand_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create Complete Return Inventory Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="createReturnInventoryErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="createReturnInventorySuccessMessage"></div>
                <div class="modal-body">
                    <div class="form-group col-md-12">
                        Are you sure to return complete inventory ? 
                    </div>
                    <div class="form-group col-md-12">
                        <label>Comments</label>
                        <input type="text" class="form-control" name="comments_return_inv" id="comments_return_inv">
                        <div class="invalid-feedback" id="error_validation_comments_return_inv"></div>
                    </div>
                </div>
                <div class="modal-footer center-footer">
                    <div id="create_return_inventory_demand_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="createReturnInvDemandCancel">Cancel</button>
                    <button type="button" class="btn btn-danger" id="createReturnInvDemandSubmit" name="createReturnInvDemandSubmit" onclick="submitCreateCompleteReturnInventoryDemand();">Create</button>
                </div>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($demands_list->total(),1000,'/store/demand/inventory-return-complete/list','Inventory Return Complete Demand List','Demand List'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.25') }}" ></script>
@endsection
