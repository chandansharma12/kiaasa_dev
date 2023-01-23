@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Products Inventory List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Products Inventory List'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                   
                </div>
            </form> 
            <div class="clear">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" style="font-size: 12px;">
                            <thead>
                                <tr class="header-tr">
                                <th>SNo</th>
                                <th>Product</th>
                                <th>Store</th>
                                <th>QR Code</th>
                                <th>Barcode</th>
                                <th>SKU</th>
                                <th>PO</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                            <tbody>
                                <?php  ?>
                                @for($i=0;$i<count($inventory_list);$i++)
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$inventory_list[$i]->product_name}} {{$inventory_list[$i]->size_name}} {{$inventory_list[$i]->color_name}}</td>
                                        <td>{{$inventory_list[$i]->store_name}}</td>
                                        <td>{{$inventory_list[$i]->peice_barcode}}</td>
                                        <td>{{$inventory_list[$i]->product_barcode}}</td>
                                        <td>{{$inventory_list[$i]->product_sku}}</td>
                                        <td>{{$inventory_list[$i]->po_no}}</td>
                                        <td>@if($inventory_list[$i]->product_status == 0) WAREHOUSE IN PENDING @else {{strtoupper(CommonHelper::getposProductStatusName($inventory_list[$i]->product_status))}} @endif </td>
                                        <td><a href="{{url('pos/product/inventory/detail/'.$inventory_list[$i]->id)}}" ><i title="Inventory Details" class="far fa-eye"></i></a></td>
                                    </tr>
                                    <?php ?>
                                @endfor
                            </tbody>
                        </table>
                        
                    </div>
               
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')

@endsection
