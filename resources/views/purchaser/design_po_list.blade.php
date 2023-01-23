@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Design List','link'=>'purchaser/design-list'),array('name'=>'Design Purchase Orders List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Design Purchase Orders List: '.$design_data->sku); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="designListErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="designList">
                
                <div class="separator-10"></div>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table headings-info-tbl" cellspacing="0">
                        <thead>
                            <tr class="header-tr">
                            <th>ID</th>
                            <th>Order No</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Added By</th>
                            <th>Date Added</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($po_list);$i++)
                                <tr>
                                    <td>{{$po_list[$i]->id}}</td>
                                    <td>{{$po_list[$i]->order_no}}</td>
                                    <td>{{$po_list[$i]->vendor_name}}</td>
                                    <td>{{$po_list[$i]->category_name}}</td>
                                    <td>{{$po_list[$i]->user_name}}</td>
                                    <td>{{date('d M Y',strtotime($po_list[$i]->created_at))}}</td>
                                    <td>
                                        @if($po_list[$i]->type_id == 4)
                                            <a href="{{url('purchase-order/bulk/detail/'.$po_list[$i]->id)}}" ><i title="PO Details" class="fas fa-eye"></i></a>&nbsp;
                                        @endif
                                        
                                        @if($po_list[$i]->type_id == 3 || $po_list[$i]->type_id == 5)
                                            <a href="{{url('purchase-order/product/detail/'.$po_list[$i]->id)}}" ><i title="PO Details" class="fas fa-eye"></i></a>&nbsp;
                                        @endif
                                    </td>
                                </tr>
                            @endfor

                            @if(is_object($po_list) && $po_list->count() == 0)
                                <tr><td colspan="10" align="center">No Records</td></tr>
                            @endif
                        </tbody>
                    </table>
                    
                    <div class="clear"></div>
                    @if(is_object($po_list))
                        {{ $po_list->links() }} <p>Displaying {{$po_list->count()}} of {{ $po_list->total() }} Purchase orders.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js') }}" ></script>
@endsection
