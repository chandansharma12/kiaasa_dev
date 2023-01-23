@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Coupons List','link'=>'coupon/list')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Coupons Detail'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            
            <form id="coupon_detail_form" name="coupon_detail_form">
                <div class="form-row">
                    
                    <div class="form-group col-md-2">
                        <label for="Color">Coupon Name</label>						
                        {{$coupon_data->coupon_name}}     
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Coupons Count</label>						
                        {{$coupon_data->items_count}}        
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$coupon_data->store_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Discount</label>						
                        {{$coupon_data->discount}} %    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Valid From</label>						
                        {{date('d M Y',strtotime($coupon_data->valid_from))}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Valid To</label>						
                        {{date('d M Y',strtotime($coupon_data->valid_to))}} 
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Coupon Type</label>						
                        {{$coupon_data->coupon_type}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Created By</label>						
                        {{$coupon_data->user_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{$coupon_data->status==1?'Enabled':'Disabled'}}
                    </div>
                </div>    
            </form>    

            <div class="separator-10">&nbsp;</div>
            
            <div id="couponsContainer">
               
               
                <h6><b>Coupons used:</b></h6>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="width:50%;">
                        <thead><tr class="header-tr"><th>SNo</th><th>Coupon</th><th>Order ID</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($coupon_items_used);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$coupon_items_used[$i]['coupon_no']}}</td>
                                    <td></td>
                                </tr>
                            @endfor
                            @if(empty($coupon_items_used))
                                <td colspan="5" align="center">No Records</td>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <hr>
                
                <h6><b>Coupons not used:</b></h6>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="width:50%;">
                        <thead><tr class="header-tr"><th>SNo</th><th>Coupon</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($coupon_items_not_used);$i++)
                                <tr>
                                    <td>{{$i+1}}</td>
                                    <td>{{$coupon_items_not_used[$i]['coupon_no']}}</td>
                                </tr>
                            @endfor
                             @if(empty($coupon_items_not_used))
                                <td colspan="5" align="center">No Records</td>
                            @endif
                        </tbody>
                    </table>
                </div>
                 
            </div>
        </div>
    </section>

    

@endif

@endsection

@section('scripts')

<script src="{{ asset('js/coupon.js?v=1.1') }}" ></script>
@endsection
