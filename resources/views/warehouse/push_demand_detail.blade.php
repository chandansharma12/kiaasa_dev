@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php //if($user->user_type == 1) $list_link = 'warehouse/demand/push/admin-list';elseif($user->user_type == 6) $list_link = 'warehouse/demand/push/list';?>
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Push Demands List','link'=>'warehouse/demand/push/list'),array('name'=>'Push Demand Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Push Demand Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <form id="demand_detail_form" name="demand_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Push Demand ID</label>						
                        {{$push_demand_data->id}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Product">Demand Status</label>						
                        {{str_replace('_',' ',$push_demand_data->demand_status)}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($push_demand_data->created_at))}}    
                    </div> 
                    <?php /* ?><div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$push_demand_data->user_name}}    
                    </div> <?php */ ?>
                   
                </div>    
            </form> 
            <hr/>
            <div id="demandsList">
                <div id="detailPushDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="detailPushDemandSuccessMessage" class="alert alert-success" style="display:none;"></div>
                
                   
                   <div class="separator-10"></div>
                    
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th> Product</th><th>Available</th><th>Booked</th><th>Unit Price</th>
                                @for($q=0;$q<count($store_list);$q++)
                                    <th>{{$store_list[$q]['store_name']}}</th>
                                @endfor    
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <td>{{$products_list[$i]->product_name}} {{$products_list[$i]->size_name}} {{$products_list[$i]->color_name}}</td>
                                        <td>{{$products_list[$i]->inventory_count-$products_quantity[$products_list[$i]->id]}}</td>      
                                        <td>{{$products_list[$i]->push_demand_booked}}</td>    
                                        <td>{{$products_list[$i]->base_price}}</td>
                                        @for($q=0;$q<count($store_list);$q++)
                                            <td>{{$store_products[$store_list[$q]['id']][$products_list[$i]->id]}}</td>
                                        @endfor         
                                        
                                    </tr>
                                @endfor    
                            </tbody>
                        </table>   
                        
                    </div>
                    @if(CommonHelper::hasPermission('update_push_demand',$user->user_type) && in_array(strtolower($push_demand_data->demand_status),array('waiting'))  )
                        <div class="table-responsive">
                            <div class="table_actions">
                                <div id="updatePushDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
                                <div id="updatePushDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
                                Comments: 
                                <textarea class="form-control design-comment" id="demand_comments"  name="demand_comments"></textarea>
                                <button class="btn_box" id="approveDemandBtn" name="approveDemandBtn" onclick="updatePushDemand({{$push_demand_data->id}},'approved');">Approve Demand</button>
                                <button class="btn_box" id="rejectDemandBtn" name="rejectDemandBtn" onclick="updatePushDemand({{$push_demand_data->id}},'rejected');">Reject Demand</button>
                            </div>
                        </div>
                    @endif
                    
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >
        
    </script>
@endif


<script src="{{ asset('js/store.js') }}" ></script>
@endsection
