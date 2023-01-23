@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    
    <?php //if($user->user_type == 1) $list_link = 'store/admin-demands-list';elseif($user->user_type == 6) $list_link = 'warehouse/demand/list';elseif($user->user_type == 9) $list_link = 'store/demand/list';else $list_link = '';?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Demands List','link'=>'store/demand/list'),array('name'=>'Demand Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Demand Detail'); ?>  

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <form id="demand_detail_form" name="demand_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Product">Demand Status</label>						
                        {{str_replace('_',' ',$demand_data->demand_status)}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Store</label>						
                        {{$demand_data->store_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Comments</label>						
                        {{$demand_data->comments}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($demand_data->created_at))}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$demand_data->user_name}}    
                    </div> 
                    <?php /* ?> @if($user->user_type == 6)
                        <div class="form-group col-md-2">
                            <a href="{{url('store/demand/pdf/invoice/'.$demand_data->id)}}" class="btn  btn-pdf">Invoice PDF</a>&nbsp;
                            <a href="{{url('store/demand/pdf/gatepass/'.$demand_data->id)}}" class="btn btn-pdf">Gate pass PDF</a>
                        </div>
                    @endif <?php */ ?>
                </div>    
            </form> 
            <hr/>
            <div id="demandContainer" class="table-container">
                <div id="demandsList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>Product Name</th><th>Price</th><th>Quantity</th><th>Total Price</th>
                            </tr></thead>
                            <tbody>
                                <?php $total_price = 0;$qty_total = 0; ?>
                                @for($i=0;$i<count($demand_products_list);$i++)
                                    <tr>
                                        <td>{{$demand_products_list[$i]->product_name}} {{$demand_products_list[$i]->size_name}} {{$demand_products_list[$i]->color_name}}</td>
                                        <td>{{$currency}} {{$demand_products_list[$i]->base_price}}</td>
                                        <td>{{$demand_products_list[$i]->product_quantity}}</td>
                                        <td>{{$currency}} {{round($demand_products_list[$i]->product_quantity*$demand_products_list[$i]->base_price,2)}}</td>
                                    </tr>
                                    <?php $total_price+=($demand_products_list[$i]->product_quantity*$demand_products_list[$i]->sale_price); ?>
                                    <?php $qty_total+=$demand_products_list[$i]->product_quantity; ?>
                                @endfor
                                <tr class="total-tr"><th colspan="2">Total</strong></th><th>{{$qty_total}}</th><th>{{$currency}} {{$total_price}}</th></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    @if(CommonHelper::hasPermission('update_store_demand',$user->user_type) && in_array(strtolower($demand_data->demand_status),array('waiting'))  )
                        <div class="table-responsive">
                            <div class="table_actions">
                                <div id="updateDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
                                <div id="updateDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
                                Comments: 
                                <textarea class="form-control design-comment" id="demand_comments"  name="demand_comments"></textarea>
                                <button class="btn_box" id="approveDemandBtn" name="approveDemandBtn" onclick="updateDemand({{$demand_data->id}},'approved');">Approve Demand</button>
                                <button class="btn_box" id="rejectDemandBtn" name="rejectDemandBtn" onclick="updateDemand({{$demand_data->id}},'rejected');">Reject Demand</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
