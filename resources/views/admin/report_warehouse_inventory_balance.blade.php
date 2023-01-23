@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Warehouse Inventory Balance Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Warehouse Inventory Balance Report');$page_name = 'warehouse_inventory_balance_report'; ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <h6>Type: {{$report_types[$report_type]}} @if($report_type == 2) | Category: {{$category_data->name}} @endif</h6>

            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <?php unset($report_types[2]); ?>                
                        <select name="type_id" id="type_id" class="form-control">
                            <option value="">-- Report Type --</option>
                            @foreach($report_types as $id=>$name)
                                <?php if($report_type == $id) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$id}}">{{$name}}</option>
                            @endforeach    
                        </select>
                    </div> 
                    @if($report_type == 4)
                        <div class="col-md-2">
                            <select name="po_cat_id" id="po_cat_id" class="form-control">
                                <option value="">-- PO Category --</option>
                                @foreach($po_category_list as $id=>$name)
                                    <?php if($id == request('po_cat_id')) $sel = 'selected';else $sel = ''; ?>
                                    <option {{$sel}} value="{{$id}}">{{$name}}</option>
                                @endforeach   
                            </select>
                        </div>
                    @endif
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();  ?>
                    <div class="col-md-2"><a href="{{url('warehouse/report/inventory/balance?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    
                    <?php  $total_records = 0; ?>
                    <div class="table-responsive table-filter">
                        @if($report_type == 1)
                            <table class="table table-striped admin-table report-sort" cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>ID</th>
                                        <th style="width:33%;">Category</th>
                                        <th style="width:33%;">Inventory Units</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i=0;$i<count($category_list);$i++)
                                        <tr>
                                             <td>{{$category_list[$i]->category_id}}</td>
                                            <td>{{$category_list[$i]->category_name}}</td>
                                            <td>{{$category_list[$i]->inv_count}}</td>
                                            <td><a class="table-link" href="{{url('warehouse/report/inventory/balance?type_id=2&cat_id='.$category_list[$i]->category_id)}}">Subcategory Report <i class="fa fa-arrow-circle-right"></i></a></td>
                                        </tr>
                                        <?php $total_records+=$category_list[$i]->inv_count; ?>    
                                    @endfor
                                </tbody>    
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th>{{$total_records}}</th>
                                        <th></th>
                                   </tr>
                                </tfoot>
                            </table>
                        @endif
                        
                        @if($report_type == 2)
                            <table class="table table-striped admin-table report-sort" cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>ID</th>
                                        <th style="width:50%;">Subcategory</th>
                                        <th>Inventory Units</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i=0;$i<count($subcategory_list);$i++)
                                        <tr>
                                            <td>{{$subcategory_list[$i]->subcategory_id}}</td>
                                            <td>{{$subcategory_list[$i]->subcategory_name}}</td>
                                            <td>{{$subcategory_list[$i]->inv_count}}</td>
                                        </tr>
                                        <?php $total_records+=$subcategory_list[$i]->inv_count; ?>    
                                    @endfor
                                </tbody>    
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th>{{$total_records}}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                        
                        @if($report_type == 3)
                            <table class="table table-striped admin-table report-sort" cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>ID</th>
                                        <th style="width:50%;">Vendor</th>
                                        <th>Inventory Units</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i=0;$i<count($vendor_list);$i++)
                                        <tr>
                                            <td>{{$vendor_list[$i]->id}}</td>
                                            <td>{{$vendor_list[$i]->vendor_name}}</td>
                                            <td>{{$vendor_list[$i]->inv_count}}</td>
                                        </tr>
                                        <?php $total_records+=$vendor_list[$i]->inv_count; ?>    
                                    @endfor
                                </tbody>    
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th>{{$total_records}}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                        
                        @if($report_type == 4)
                            <table class="table table-striped admin-table report-sort" cellspacing="0">
                                <thead>
                                    <tr class="header-tr">
                                        <th>ID</th>
                                        <th style="width:25%;">Purchase Order No</th>
                                        <th style="width:25%;">PO Category</th>
                                        <th style="width:25%;">Vendor</th>
                                        <th>Inventory Units</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i=0;$i<count($po_list);$i++)
                                        <tr>
                                            <td>{{$po_list[$i]->id}}</td>
                                            <td>{{$po_list[$i]->order_no}}</td>
                                            <td>{{$po_category_list[$po_list[$i]->category_id]}}</td>
                                            <td>{{$po_list[$i]->vendor_name}}</td>
                                            <td>{{$po_list[$i]->inv_count}}</td>
                                        </tr>
                                        <?php $total_records+=$po_list[$i]->inv_count; ?>    
                                    @endfor
                                    
                                </tbody>    
                                <tfoot>
                                    @if($po_list->total() != $po_list->count())
                                        <tr>
                                            <th style="border-bottom: 1px solid #EB5D70;">Sub Total</th>
                                            <th style="border-bottom: 1px solid #EB5D70;"></th>
                                            <th style="border-bottom: 1px solid #EB5D70;"></th>
                                            <th style="border-bottom: 1px solid #EB5D70;"></th>
                                            <th style="border-bottom: 1px solid #EB5D70;">{{$total_records}}</th>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th>{{$po_list_count}}</th>
                                    </tr>
                                </tfoot>
                            </table>
                            <br>
                            {{ $po_list->withQueryString()->links() }} <p>Displaying {{$po_list->count()}} of {{ $po_list->total() }} Purchase Orders.</p>
                        @endif
                        
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/datatables.min.js') }}" ></script>
<link rel="stylesheet" type="text/css" href="{{asset('css/datatables.min.css')}}"/>
<script type="text/javascript">
    $(document).ready(function(){
        $('.report-sort').DataTable({ "autoWidth": true,"scroller":false,"paging":false,"scrollX":false,"scrollY":false,"searching":true,"fixedHeader":true,"order": [] });
    });
</script>
@endsection
