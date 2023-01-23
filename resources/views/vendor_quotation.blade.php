@extends('layouts.vendor')

@section('content')

@if(isset($design_data) && !empty($design_data))<script>var design_id = {{ $design_data['id'] }}</script>@endif
<!-- Strat breadcrumb -->
<nav aria-label="breadcrumb" class="page_bbreadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="javascript:;">Dashboard  </a></li>
    <li class="breadcrumb-item active" aria-current="page">Vendor Quotation Form</li>
  </ol> 
    <h2 class="page_heading">Vendor Quotation Form</h2>
</nav> 
<!-- End breadcrumb -->
<!-- Strat Form Area -->  
<section class="form_area">
    <div class="container-fluid">
        <form id="vendorQuotationForm" name="vendorQuotationForm" method="post">
        <div class="row">
            <div class="col-md-12">
                @if (session('success_message'))
                    <br/>
                    <div class="alert alert-success">
                        {{ session('success_message') }}
                    </div>
                @endif
                
                @if(isset($error_msg) && !empty($error_msg))
                    <div class="alert alert-danger">{{$error_msg}}</div>
                @endif
                
                @if(isset($design_data) && !empty($design_data))
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="Article">Design ID</label>						
                        {{$design_data['id']}}
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Article">Design SKU</label>						
                        {{$design_data['sku']}}
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Season">Version</label>						
                        {{$design_data['version']}}    
                    </div> 
                    <div class="form-group col-md-3">
                        <label for="Story">Date Created</label>						
                        {{date('d M Y',strtotime($design_data['created_at']))}}
                    </div> 
                </div>
                @endif
                
                <hr>	
                @if(isset($vendor_quote_added) && $vendor_quote_added == false)
                    <div class="form-row">
                        <div class="col-md-12"> 
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                        <li>{{ $error }}<hr style="margin-top:5px;margin-bottom: 5px;"></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <?php $count = 0; $design_ids_arr = $accessories_ids_arr = $process_ids_arr = $packaging_ids_arr = array(); ?>
                            <h4>Fabrics</h4>
                            <div class="table-responsive table-filter">
                                <table class="table table-striped vendor-quote-tbl">
                                <thead><tr><th>Body Parts</th><th>Quality</th><th>Color</th><th>Width</th><th>Average</th><th>Rate</th><th>Cost</th><th>Size</th><th>Qty</th><th>Unit</th><th>Quote Price</th><th>Quote Comment</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($vendor_quotation_data);$i++)
                                            @if($vendor_quotation_data[$i]->design_type == 'fabric')
                                                <tr>
                                                    <td>{{$vendor_quotation_data[$i]->body_part_name}} </td><td> {{$vendor_quotation_data[$i]->quality_id_name}} </td><td>{{$vendor_quotation_data[$i]->color_id_name}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->width}}</td><td>{{$vendor_quotation_data[$i]->avg}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->rate}}</td><td>{{$vendor_quotation_data[$i]->cost}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->size}}</td><td>{{$vendor_quotation_data[$i]->qty}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->unit_code}}</td>
                                                    <td><input type="text" name="data_price_{{$vendor_quotation_data[$i]->id}}" id="fabric_data_price_{{$vendor_quotation_data[$i]->id}}" value="{{old('data_price_'.$vendor_quotation_data[$i]->id)}}" class="form-control text-box"></td>
                                                    <td><textarea name="data_comment_{{$vendor_quotation_data[$i]->id}}" id="fabric_data_comment_{{$vendor_quotation_data[$i]->id}}" class="form-control text-area">{{old('data_comment_'.$vendor_quotation_data[$i]->id)}}</textarea></td>
                                                </tr>
                                                <?php $count++; $design_ids_arr[] = $vendor_quotation_data[$i]->id; ?>
                                            @endif
                                        @endfor
                                        
                                        @if($count == 0)    
                                            <tr><td colspan="15" align="center">No Records</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <?php $count = 0; ?>
                            <hr>    
                            <h4>Accessories</h4>
                            <div class="table-responsive table-filter">
                                <table class="table table-striped vendor-quote-tbl">
                                <thead><tr><th>Category</th><th>Color</th><th>Rate</th><th>Quantity</th><th>Cost</th><th>Unit</th><th>Quote Price</th><th>Quote Comment</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($vendor_quotation_data);$i++)
                                            @if($vendor_quotation_data[$i]->design_type == 'accessories')
                                                <tr>
                                                    <td>{{$vendor_quotation_data[$i]->name_id_name}}</td><td>{{$vendor_quotation_data[$i]->color_id_name}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->rate}}</td><td>{{$vendor_quotation_data[$i]->qty}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->cost}}</td><td>{{$vendor_quotation_data[$i]->unit_code}}</td>
                                                    <td><input type="text" name="data_price_{{$vendor_quotation_data[$i]->id}}" id="accessories_data_price_{{$vendor_quotation_data[$i]->id}}" value="{{old('data_price_'.$vendor_quotation_data[$i]->id)}}" class="form-control text-box"></td>
                                                    <td><textarea name="data_comment_{{$vendor_quotation_data[$i]->id}}" id="accessories_data_comment_{{$vendor_quotation_data[$i]->id}}" class="form-control text-area">{{old('data_comment_'.$vendor_quotation_data[$i]->id)}}</textarea></td>
                                                </tr>
                                                <?php $count++; $accessories_ids_arr[] = $vendor_quotation_data[$i]->id; ?>
                                            @endif
                                        @endfor
                                        
                                        @if($count == 0)      
                                            <tr><td colspan="10" align="center">No Records</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                             <?php $count = 0; ?>
                            <hr>    
                            <h4>Process</h4>
                            <div class="table-responsive table-filter">
                                <table class="table table-striped vendor-quote-tbl">
                                <thead><tr><th>Category</th><th>Type</th><th>Cost</th><th>Quote Price</th><th>Quote Comment</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($vendor_quotation_data);$i++)
                                            @if($vendor_quotation_data[$i]->design_type == 'process')
                                                <tr>
                                                    <td>{{$vendor_quotation_data[$i]->name_id_name}}</td><td>{{$vendor_quotation_data[$i]->quality_id_name}}</td>
                                                    <td>{{$vendor_quotation_data[$i]->cost}}</td>
                                                    <td><input type="text" name="data_price_{{$vendor_quotation_data[$i]->id}}" id="process_data_price_{{$vendor_quotation_data[$i]->id}}" value="{{old('data_price_'.$vendor_quotation_data[$i]->id)}}"  class="form-control text-box"></td>
                                                    <td><textarea name="data_comment_{{$vendor_quotation_data[$i]->id}}" id="process_data_comment_{{$vendor_quotation_data[$i]->id}}" class="form-control text-area">{{old('data_comment_'.$vendor_quotation_data[$i]->id)}}</textarea></td>
                                                </tr>
                                                <?php $count++; $process_ids_arr[] = $vendor_quotation_data[$i]->id; ?>
                                            @endif
                                        @endfor
                                        
                                        @if($count == 0)        
                                            <tr><td colspan="10" align="center">No Records</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <?php $count = 0; ?>
                            <hr>    
                            <h4>Packaging Sheet</h4> 
                            <div class="table-responsive table-filter">
                                <table class="table table-striped vendor-quote-tbl">
                                <thead><tr><th>Name</th><th>Cost</th><th>Quote Price</th><th>Quote Comment</th></tr></thead>
                                    <tbody>
                                        @for($i=0;$i<count($vendor_quotation_data);$i++)
                                            @if($vendor_quotation_data[$i]->design_type == 'packaging_sheet' && $vendor_quotation_data[$i]->qty > 0)
                                                <tr>
                                                    <td>{{$vendor_quotation_data[$i]->name_id_name}}</td><td>{{$vendor_quotation_data[$i]->cost}}</td>
                                                    <td><input type="text" name="data_price_{{$vendor_quotation_data[$i]->id}}" id="packaging_sheet_data_price_{{$vendor_quotation_data[$i]->id}}" value="{{old('data_price_'.$vendor_quotation_data[$i]->id)}}" class="form-control text-box"></td>
                                                    <td><textarea name="data_comment_{{$vendor_quotation_data[$i]->id}}" id="packaging_sheet_data_comment_{{$vendor_quotation_data[$i]->id}}" class="form-control text-area">{{old('data_comment_'.$vendor_quotation_data[$i]->id)}}</textarea></td>
                                                </tr>
                                                <?php $count++; $packaging_ids_arr[] = $vendor_quotation_data[$i]->id; ?>
                                            @endif
                                        @endfor

                                        @if($count == 0)  
                                            <tr><td colspan="10" align="center">No Records</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div> 

                            @csrf
                            <button type="submit" class="btn_box" name="submit_quote_btn" id="submit_quote_btn" style="float:right;">Submit Quote</button>
                        </div>   
                    </div>	
                    
                    <input type="hidden" name="design_data_id" id="design_data_id" value="<?php echo implode(',', $design_ids_arr); ?>" />
                    <input type="hidden" name="accessories_data_id" id="accessories_data_id" value="<?php echo implode(',', $accessories_ids_arr); ?>" />
                    <input type="hidden" name="process_data_id" id="process_data_id" value="<?php echo implode(',', $process_ids_arr); ?>" />
                    <input type="hidden" name="packaging_data_id" id="packaging_data_id" value="<?php echo implode(',', $packaging_ids_arr); ?>" />
                @endif
                
                @if(isset($vendor_quote_added) && $vendor_quote_added == true)
                    @if(!session('success_message'))
                        <div class="alert alert-danger">Vendor quote already submitted</div>
                    @endif
                @endif
            </div> 
        </div>
           
        </form> 
    </div>
</section>

@endsection

@section('scripts')
@endsection