@extends('layouts.default')

@section('content')

@if(!empty($design_data->id))
    <script>var design_id = {{ $design_data->id }}</script>
@endif    
<!-- Strat breadcrumb -->
<nav aria-label="breadcrumb" class="page_bbreadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard  </a></li>
    <li class="breadcrumb-item active" aria-current="page">Design Quotation Submissions</li>
  </ol>
    <h2 class="page_heading">Design Quotation Submissions</h2>
</nav> 
<!-- End breadcrumb -->
<!-- Strat Form Area -->  
<section class="form_area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger alert-dismissible" style="display:none" id="quotationErrorMessage"></div>
                @if (session('success_message'))
                    <br/>
                    <div class="alert alert-success">
                        {{ session('success_message') }}
                    </div>
                @endif
                @if(isset($error_msg) && !empty($error_msg))
                    <div class="alert alert-danger">{{$error_msg}} </div>
                @endif 
                
                @if(!empty($design_data->id))
                    <form id="" name="">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="Article">SKU</label>						
                                {{$design_data->sku}}
                            </div> <!--
                            <div class="form-group col-md-3">
                                <label for="Season">Version</label>						
                                {{$design_data->version}}    
                            </div>  -->
                            <div class="form-group col-md-3">
                                <label for="Story">Designer</label>						
                                {{$design_data->designer_name}}    
                            </div> 
                            <div class="form-group col-md-3">
                                <label for="Product">Reviewer</label>			
                                {{$design_data->reviewer_name}}   
                            </div> 
                            <div class="form-group col-md-3">
                                <button type="button" class="btn_add pull-right" onclick="purchaseOrder();" style="border:none;">Purchase order</button> 
                            </div> 
                        </div>
                    </form> 
                @endif 
                
               	<?php /* ?>
                <!--<div class="form-row">
                    <div class="col-md-12"> 
                        <div class="table-responsive table-filter">
                            <table class="table table-striped" id="vendor_table">
                            @if(1)
                            <thead><tr>
                                <th>Vendor</th>
                                <?php $counts['fabric'] = $counts['accessory']= $counts['process'] = $counts['packaging'] = 1; ?>
                                @for($i=0;$i<count($design_items_data);$i++)
                                    @if($design_items_data[$i]->type_id == 1)
                                        <th>Fabric {{$counts['fabric']}}</th>
                                    <?php $counts['fabric']++; ?>
                                    @endif
                                    
                                    @if($design_items_data[$i]->type_id == 2)
                                        <th>Accessory {{$counts['accessory']}}</th>
                                        <?php $counts['accessory']++; ?>
                                    @endif
                                    
                                    @if($design_items_data[$i]->type_id == 3)
                                        <th>Process {{$counts['process']}}</th>
                                        <?php $counts['process']++; ?>
                                    @endif
                                    
                                    @if($design_items_data[$i]->type_id == 4)
                                        <th>Packaging Sheet {{$counts['packaging']}}</th>
                                        <?php $counts['packaging']++; ?>
                                    @endif
                                @endfor
                            </tr></thead>
                                <tbody>
                                    @foreach($quotation_list as $quotation_id=>$quotation_data)
                                        <tr><td>{{$quotation_id}}</td>
                                        @for($i=0;$i<count($quotation_data);$i++)
                                            <td>
                                                @if(isset($quotation_data[$i]->price))
                                                    {{$quotation_data[$i]->price}}
                                                @endif
                                            </td>
                                        @endfor    
                                        </tr>
                                    @endforeach
                                </tbody>
                                @else
                                <td colspan="5" align="center">No Records</td>
                                @endif
                            </table>
                        </div>
                    </div>   
                </div>	-->
                <?php */ ?>
                
                <div class="form-row">
                    <div class="col-md-12"> 
                        <div class="table-responsive table-filter">
                            <table class="table table-striped" id="vendor_quotations_table">
                            <thead></thead>       
                            <tbody>
                                
                                <tr>
                                    @if(isset($design_items_data[0]->design_type_id))
                                        @if($design_items_data[0]->design_type_id == 1)
                                            <th>Select Fabric</th><th>Name</th><th>Quality</th><th>Color</th>
                                        @elseif($design_items_data[0]->design_type_id == 2)
                                            <th>Select Accessories</th><th>Category</th><th></th><th>Color</th>
                                        @elseif($design_items_data[0]->design_type_id == 3)
                                            <th>Select Process</th><th>Category</th><th>Type</th><th></th>    
                                        @elseif($design_items_data[0]->design_type_id == 4)   
                                            <th>Select Packaging Sheet</th><th>Name</th><th></th><th></th>    
                                        @endif
                                        @for($i=0;$i<count($vendor_ids);$i++)
                                            <th>Vendor {{$i+1}}</th>
                                        @endfor     
                                    @endif
                                </tr>
                                <?php $counts['fabric'] = $counts['accessory']= $counts['process'] = $counts['packaging'] = 1; ?>
                                @for($i=0;$i<count($design_items_data);$i++)
                                    <?php if($design_items_data[$i]->design_type_id == 4 && $design_items_data[$i]->qty == 0) continue; ?>
                                    @if(isset($design_items_data[$i-1]->design_type_id) && $design_items_data[$i]->design_type_id != $design_items_data[$i-1]->design_type_id && $design_items_data[$i]->design_type_id == 2)
                                        <tr >
                                            <th>Select Accessories</th><th>Category</th><th></th><th>Color</th>
                                            @for($q=0;$q<count($vendor_ids);$q++)
                                                <th>Vendor {{$q+1}}</th>
                                            @endfor     
                                        </tr>
                                    @endif
                                    @if(isset($design_items_data[$i-1]->design_type_id) && $design_items_data[$i]->design_type_id != $design_items_data[$i-1]->design_type_id && $design_items_data[$i]->design_type_id == 3)
                                        <tr>
                                            <th>Select Process</th><th>Category</th><th>Type</th><th></th>
                                            @for($q=0;$q<count($vendor_ids);$q++)
                                                <th>Vendor {{$q+1}}</th>
                                            @endfor     
                                        </tr>
                                    @endif
                                    @if(isset($design_items_data[$i-1]->design_type_id) && $design_items_data[$i]->design_type_id != $design_items_data[$i-1]->design_type_id && $design_items_data[$i]->design_type_id == 4)
                                        <tr>
                                            <th>Select Packaging Sheet</th><th>Name</th><th></th><th></th>
                                            @for($q=0;$q<count($vendor_ids);$q++)
                                                <th>Vendor {{$q+1}}</th>
                                            @endfor     
                                        </tr>
                                    @endif
                                    <tr>
                                    <td>
                                        <input class="chk-purchase-item" type="checkbox" id="chk_item_{{$design_items_data[$i]->id}}" name="chk_item_{{$design_items_data[$i]->id}}" value="{{$design_items_data[$i]->id}}">
                                        <select class="form-control vendor-sel-1" name="vendor_sel_{{$design_items_data[$i]->id}}" id="vendor_sel_{{$design_items_data[$i]->id}}" >
                                            <option value="">Vendor</option>
                                            @for($q=0;$q<count($vendors_list);$q++)
                                               <option value="{{$vendors_list[$q]['id']}}">{{$vendors_list[$q]['name']}}</option>
                                            @endfor     
                                        </select>
                                        <i class="fa fa-angle-down select_opt vendor-sel-fa" ></i>
                                        <select class="form-control vendor-sel-1" name="quantity_sel_{{$design_items_data[$i]->id}}" id="quantity_sel_{{$design_items_data[$i]->id}}" >
                                            <option value="">Quantity</option>
                                            @for($q=1;$q<=100;$q++)
                                                <option value="{{$q}}">{{$q}}</option>
                                            @endfor    
                                        </select>
                                        <i class="fa fa-angle-down select_opt quantity-sel-fa" ></i>
                                    </td>    
                                   <?php /* ?> <td>
                                            @if($design_items_data[$i]->design_type_id == 1)
                                                Fabric {{$counts['fabric']}}
                                                <?php $counts['fabric']++; ?>
                                            @endif

                                            @if($design_items_data[$i]->design_type_id == 2)
                                                Accessory {{$counts['accessory']}}
                                                <?php $counts['accessory']++; ?>
                                            @endif

                                            @if($design_items_data[$i]->design_type_id == 3)
                                                Process {{$counts['process']}}
                                                <?php $counts['process']++; ?>
                                            @endif

                                            @if($design_items_data[$i]->design_type_id == 4)
                                                Packaging Sheet {{$counts['packaging']}}
                                                <?php $counts['packaging']++; ?>
                                            @endif 
                                    </td><?php */ ?>
                                        <td>{{$design_items_data[$i]->name_id_name}}</td>
                                        <td>{{$design_items_data[$i]->quality_id_name}}</td>
                                        <td>{{$design_items_data[$i]->color_id_name}}</td>
                                        @for($q=0;$q<count($vendor_ids);$q++)

                                            <?php $vendor_id = $vendor_ids[$q]; ?>
                                            @if(isset($design_items_data[$i]->vendor_data[$vendor_id]->price))
                                                <?php if($design_items_data[$i]->vendor_data[$vendor_id]->price == $design_items_data[$i]->max_price) $css_max_class = 'max-price-span';else $css_max_class = ''; ?>
                                                <?php if($design_items_data[$i]->vendor_data[$vendor_id]->price == $design_items_data[$i]->min_price) $css_min_class = 'min-price-span';else $css_min_class = ''; ?>
                                                <td><span class="{{$css_max_class}}{{$css_min_class}}">{{$design_items_data[$i]->vendor_data[$vendor_id]->price}}</span></td>
                                            @else
                                                <td> </td>
                                            @endif

                                        @endfor     
                                    </tr> 
                                @endfor     
                                   
                            </tbody>
                            </table>
                        </div>
                    </div>   
                </div>
                
            </div> 
        </div>
    </div>
</section>

<div class="modal fade" id="purchase-order-items-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Purchase Order Items</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>
            
            <div class="alert alert-danger alert-dismissible" style="display:none" id="purchaseOrderErrorMessage"></div>
            <div class="alert alert-success alert-dismissible" style="display:none" id="purchaseOrderSuccessMessage"></div>
            
            <div class="modal-body" id="purchase_order_items_body"></div>
            <div class="modal-footer center-footer">
                <button type="button" data-dismiss="modal" class="btn btn-dialog" id="purchase_order_items_close_btn" name="purchase_order_items_close_btn">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="purchase-order-error-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>
            
            <div class="modal-body" id="purchase_order_error_body"></div>
            <div class="modal-footer center-footer">
                <button type="button" data-dismiss="modal" class="btn btn-dialog" id="purchase_order_error_close_btn" name="purchase_order_error_close_btn">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css" />
<script type="text/javascript">//$('#vendor_table').DataTable({paging: false});</script>
<script type="text/javascript">
    function displayComment(elem){
        $(elem).parents("td").find(".blur-tooltip").toggle();
    }
</script>    
<script src="{{ asset('js/quotation_submissions.js') }}" ></script>
@endsection