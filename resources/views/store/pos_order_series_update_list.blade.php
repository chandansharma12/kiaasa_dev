@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Orders Updates List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Orders Updates List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    
                    <div class="col-md-2">
                        <select name="store_id" id="store_id" class="form-control">
                            <option value="">Store</option>
                            @for($i=0;$i<count($store_list);$i++)
                                 <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>   
                                <option <?php echo $sel; ?> value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                            @endfor
                        </select>
                    </div>
                      
                    <div class="col-md-2">
                        <input name="order_no" id="order_no" class="form-control" placeholder="Order No" value="{{request('order_no')}}" />
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="{{request('startDate')}}" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="{{request('endDate')}}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>Store</th>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Created On</th>
                                <th>Action</th>
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($pos_orders_updates);$i++)
                                    <tr>
                                        <td>{{$pos_orders_updates[$i]->store_name}}</td>
                                        <td>{{date('d M Y',strtotime($pos_orders_updates[$i]->order_date))}}</td>
                                        <td>{{$pos_orders_updates[$i]->orders_count}}</td>
                                        <td>{{date('d M Y',strtotime($pos_orders_updates[$i]->created_at))}}</td>
                                        <td>
                                            <a href="{{url('pos/order/series/update/detail/'.$pos_orders_updates[$i]->id)}}" ><i title="Details" class="fas fa-eye"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $pos_orders_updates->withQueryString()->links() }} <p>Displaying {{$pos_orders_updates->count()}} of {{ $pos_orders_updates->total() }} records.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=1.1') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker();$("#fake_order_date").datepicker({format: 'dd-mm-yyyy',startDate: '-2M',endDate: '+0d'});</script>
@endsection
