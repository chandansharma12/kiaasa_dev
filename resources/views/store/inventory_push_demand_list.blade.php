@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Stock In')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Stock In'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Demands List'); ?></div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div class="table-responsive table-filter">

                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>ID</th>
                            <th>Invoice No</th><th>Store</th><th>Demand Status</th><th>Type</th><th>Created On</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($demands_list);$i++)
                                <tr>
                                    <td>{{$demands_list[$i]->id}}</td>
                                    <td>{{$demands_list[$i]->invoice_no}}</td>
                                    <td>{{$demands_list[$i]->store_name}}</td>
                                    <td>{{ucwords(str_replace('_',' ',$demands_list[$i]->demand_status))}}</td>
                                    <td>{{!empty($demands_list[$i]->push_demand_id)?'Complete Inventory Return':'Inventory Push'}}</td>
                                    <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                    <td>
                                        @if( in_array(strtolower($demands_list[$i]->demand_status),array('warehouse_dispatched','store_loading')) )
                                            <a href="{{url('store/demand/inventory-push/edit/'.$demands_list[$i]->id)}}" ><i title="Receive Demand Inventory" class="far fa-edit"></i></a> &nbsp;
                                        @endif
                                        <a href="{{url('store/demand/inventory-push/detail/'.$demands_list[$i]->id)}}" ><i title="Demand Detail" class="fas fa-eye"></i></a> 
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

    <?php echo CommonHelper::displayDownloadDialogHtml($demands_list->total(),1000,'/store/demand/inventory-push/list','Download Demands List','Demands'); ?>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.1') }}" ></script>
@endsection
