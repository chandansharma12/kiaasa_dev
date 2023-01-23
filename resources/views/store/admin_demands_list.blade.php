@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Demands List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Demands List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div id="orderListOverlay" class="table-list-overlay"><div id="order-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead>
                                <tr class="header-tr">
                                    <th> <?php echo CommonHelper::getSortLink('ID','id','store/admin-demands-list',true,'DESC'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Store','store','store/admin-demands-list'); ?></th>
                                    <?php /* ?><th><?php echo CommonHelper::getSortLink('Demand Status','demand_status','store/admin-demands-list'); ?></th><?php */ ?>
                                    <th><?php echo CommonHelper::getSortLink('Created By','created_by','store/admin-demands-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Reviewer','reviewer','store/admin-demands-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created_on','store/admin-demands-list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','store/admin-demands-list'); ?></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($demands_list);$i++)
                                    <tr>
                                        <td> {{$demands_list[$i]->id}}</td>
                                        <td>{{$demands_list[$i]->store_name}}</td>
                                        <?php /* ?><td>{{$demands_list[$i]->demand_status}}</td><?php */ ?>
                                        <td>{{$demands_list[$i]->demand_user_name}}</td>
                                        <td>{{$demands_list[$i]->approver_name}}</td>
                                        <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                        <td>@if($demands_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                        <td>
                                            <a href="{{url('store/demand/detail/'.$demands_list[$i]->id)}}" ><i title="Demand Details" class="fas fa-eye"></i></a> &nbsp;
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $demands_list->withQueryString()->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} demands.</p>

                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
