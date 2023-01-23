@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Vendor Inventory Payment List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Vendor Inventory Payment List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <input type="text" name="p_id" id="p_id" class="form-control" placeholder="Payment ID" value="{{request('p_id')}}" />
                    </div>
                    @if($user->user_type != 15)
                        <div class="col-md-2" >
                            <select name="v_id" id="v_id" class="form-control">
                                <option value="">All Vendors</option>
                                @for($i=0;$i<count($vendor_list);$i++)
                                    <?php $sel = ($vendor_list[$i]['id'] == request('v_id'))?'selected':''; ?>
                                    <option {{$sel}} value="{{$vendor_list[$i]['id']}}">{{$vendor_list[$i]['name']}}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" autocomplete="off" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{$start_date = request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" autocomplete="off" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{$end_date = request('endDate')}}@endif">
                        </div>
                    </div>
                    
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Vendors Inventory Payment List'); ?></div>
                </div>
            </form>

            <div class="separator-10">&nbsp;</div>

            <div id="usersContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>Payment ID</th>      
                            <th>Vendor </th> 
                            <th>Start Date </th> 
                            <th>End Date </th> 
                            <th>Inventory Count </th> 
                            <th>Cost Price </th> 
                            <th>Net Price </th> 
                            <th>Comment </th> 
                            <th>User </th> 
                            <th>Created On </th> 
                        <th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($payment_list);$i++)
                            <tr>
                                <td>{{$payment_list[$i]->id}}</td>
                                <td>{{$payment_list[$i]->vendor_name}}</td>
                                <td>{{date('d M Y',strtotime($payment_list[$i]->start_date))}}</td>
                                <td>{{date('d M Y',strtotime($payment_list[$i]->end_date))}}</td>
                                <td>{{$payment_list[$i]->inventory_count}}</td>
                                <td>{{$payment_list[$i]->inventory_cost_price}}</td>
                                <td>{{$payment_list[$i]->inventory_net_price}}</td>
                                <td>{{$payment_list[$i]->comment}}</td>
                                <td>{{$payment_list[$i]->user_name}}</td>
                                <td>{{date('d M Y',strtotime($payment_list[$i]->created_at))}}</td>
                                <td><a href="{{url('vendor/inventory/payment/detail/'.$payment_list[$i]->id)}}" class="user-list-edit" ><i title="Details" class="far fa-eye"></i></a></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $payment_list->withQueryString()->links() }}
                    <p>Displaying {{$payment_list->count()}} of {{ $payment_list->total() }} records.</p>
                </div>
            </div>
        </div>
    </section>

    <?php echo CommonHelper::displayDownloadDialogHtml($payment_list->total(),2000,'/vendor/inventory/payment/list','Download Vendors Inventory Payments','Vendors Inventory Payments'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/vendors.js') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
@endsection