@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Orders Errors List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'POS Orders Errors List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            
            <div class="table-responsive table-filter">
                <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                    <thead><tr class="header-tr">
                        <th>ID</th>    
                        <th>Store</th>
                        <th>Total Items</th>
                        <th>Client Total</th>
                        <th>Server Total</th>
                        <th>Cash</th>
                        <th>Card</th>
                        <th>E-Wallet</th>
                        <th>Voucher</th>
                        <th>Total Payment</th>
                        <th>Date</th>
                        <th>Action</th>
                        </tr></thead>
                    <tbody>
                        @for($i=0;$i<count($errors_list);$i++)
                            <tr>
                                <td>{{$errors_list[$i]->id}}</td>
                                <td>{{$errors_list[$i]->store_name}}</td>
                                <td>{{$errors_list[$i]->total_items}}</td>
                                <td>{{round($errors_list[$i]->client_total_price,3)}}</td>
                                <td>{{round($errors_list[$i]->server_total_price,3)}}</td>
                                <td>{{round($errors_list[$i]->cash_amount,3)}}</td>
                                <td>{{round($errors_list[$i]->card_amount,3)}}</td>
                                <td>{{round($errors_list[$i]->ewallet_amount,3)}}</td>
                                <td>{{round($errors_list[$i]->voucher_amount,3)}}</td>
                                <td>{{round($errors_list[$i]->cash_amount+$errors_list[$i]->card_amount+$errors_list[$i]->ewallet_amount+$errors_list[$i]->voucher_amount,3)}}</td>
                                <td>{{date('d/m/Y H:i',strtotime($errors_list[$i]->created_at))}}</td>
                                <td>
                                    <a href="{{url('pos/order/error/detail/'.$errors_list[$i]->id)}}" ><i title="Error Details" class="fas fa-eye"></i></a> &nbsp;
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                {{ $errors_list->withQueryString()->links() }} <p>Displaying {{$errors_list->count()}} of {{ $errors_list->total() }} records.</p>
            </div>
            
        </div>
    </section>

    

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=2.3') }}" ></script>

@endsection
