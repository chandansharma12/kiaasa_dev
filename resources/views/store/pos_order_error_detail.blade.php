@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Orders Errors List','link'=>'pos/order/error/list'),array('name'=>'POS Order Error Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'POS Order Error Detail'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form id="pos_order_error_detail_form" name="pos_order_error_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Store</label>						
                        {{$error_data->store_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Client Total Price</label>						
                        {{$currency}} {{round($error_data->client_total_price,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Server Total Price</label>						
                        {{$currency}} {{round($error_data->server_total_price,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Cash</label>						
                        {{$currency}} {{round($error_data->cash_amount,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Card</label>						
                        {{$currency}} {{round($error_data->card_amount,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">E Wallet</label>						
                        {{$currency}} {{round($error_data->ewallet_amount,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Voucher</label>						
                        {{$currency}} {{round($error_data->voucher_amount,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Total Payment</label>						
                        {{$currency}} {{round($error_data->cash_amount+$error_data->card_amount+$error_data->ewallet_amount+$error_data->voucher_amount,3)}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Date Created</label>						
                        {{date('d F Y H:i',strtotime($error_data->created_at))}}    
                    </div>              
                    <div class="form-group col-md-6">
                        <label for="Product">Error Text</label>						
                        {{$error_data->error_text}}    
                    </div>                  
                </div>    
            </form>    
            
            <?php $total_data = ['client_price'=>0,'server_price'=>0]; ?>
            <div class="table-responsive table-filter">
                <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                    <thead><tr class="header-tr">
                        <th>SNo</th>
                        <th>Inventory ID</th>
                        <th>Product</th>
                        <th>QR Code</th>
                        <th>Client Price</th>
                        <th>Server Price</th>
                    </tr></thead>
                    <tbody>
                        @for($i=0;$i<count($inv_list);$i++)
                            <tr>
                                <td>{{$i+1}}</td>
                                <td>{{$inv_list[$i]['id']}}</td>
                                <td>{{$inv_list[$i]['product_name']}} {{$inv_list[$i]['size_name']}} {{$inv_list[$i]['color_name']}}</td>
                                <td>{{$inv_list[$i]['peice_barcode']}}</td>
                                <td>{{$inv_list[$i]['client_price']}}</td>
                                <td>{{$inv_list[$i]['server_price']}}</td>
                                <?php $total_data['client_price']+=$inv_list[$i]['client_price']; ?>
                                <?php $total_data['server_price']+=$inv_list[$i]['server_price']; ?>
                            </tr>
                        @endfor
                        <tr><th colspan="4">Total</th><th>{{$total_data['client_price']}}</th><th>{{$total_data['server_price']}}</th></tr>
                    </tbody>
                </table>
            </div>
            
        </div>
    </section>

 @endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=2.3') }}" ></script>
@endsection
