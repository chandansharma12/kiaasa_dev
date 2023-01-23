@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Quotation Requests ','link'=>'quotation/list'),array('name'=>'Quotation Submissions')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Quotation Submissions '); ?>

    <div class="alert alert-danger" id="statusErrorMsg" style="display:none;"></div>
    <div class="alert alert-success" id="statusSuccessMsg" style="display:none;"></div>

    <?php $fabric_ids = $acc_ids = $process_ids = $pack_sheet_ids = $prod_process_ids = $item_ids = array(); ?> 
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <div id="designListErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="designList">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}<hr style="margin-top:5px;margin-bottom: 5px;"></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(count($quote_submitted_vendors_ids) == 0)
                    <div class="alert alert-danger">Quotation not submitted by vendors</div>
                @endif

                <button type="button" class="btn_add pull-right" onclick="purchaseOrder({{$quotation_id}});" style="border:none;">Create Purchase order</button> 

                @if($quotation_detail->type_id == 1 && count($quote_submitted_vendors_ids) > 0)
                    <form method="post" name="purchase_order_form" id="purchase_order_form" >
                    <div class="separator-10">&nbsp;</div>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0">
                            <thead><tr class="header-tr"><th colspan="10">Fabric</th></tr></thead>
                            <tr><th>Name</th><th>Color</th><th>Width</th><th></th><th>Quantity</th>
                                @for($i=0;$i<count($quote_submitted_vendors_ids);$i++) 
                                    <th>Vendor {{$i+1}}</th>
                                @endfor
                                <th>Purchase</th>
                            </tr>
                            @for($i=0;$i<count($quotation_list);$i++) 
                                @if($quotation_list[$i]->type_id == 1)
                                <tr>
                                    <td>{{$quotation_list[$i]->name_name}}</td>
                                    <td>{{$quotation_list[$i]->color_name}}</td>
                                    <td>{{$quotation_list[$i]->width_name}} {{$quotation_list[$i]->unit_code}}</td>
                                    <td></td>
                                    <td>{{$quotation_list[$i]->quantity}}</td>

                                    @for($q=0;$q<count($quote_submitted_vendors_ids);$q++) 
                                        <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price; ?>
                                        <?php $vendor_comments = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->comments; ?>
                                        <?php if($vendor_price == $quotation_list[$i]->max_price) $css_max_class = 'max-price-span';else $css_max_class = ''; ?>
                                        <?php if($vendor_price == $quotation_list[$i]->min_price) $css_min_class = 'min-price-span';else $css_min_class = ''; ?>
                                        <td>
                                            <span class="{{$css_max_class}}{{$css_min_class}}">{{$currency}} <?php echo $vendor_price; ?></span> 
                                            @if(!empty($vendor_comments))
                                                <div class="clear"></div><span class="quotation-comments">({{$vendor_comments}})</span>
                                            @endif        
                                        </td>
                                    @endfor

                                    <td>
                                        <select name="vendor_{{$quotation_list[$i]->item_master_id}}" id="vendor_{{$quotation_list[$i]->item_master_id}}" class="form-control quotation-vendor-sel">
                                            <option value="">Select Vendor</option>
                                            @for($q=0;$q<count($quote_submitted_vendors_ids);$q++) 
                                                <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price; ?>
                                                <option value="{{$quote_submitted_vendors_ids[$q]}}">Vendor {{$q+1}} ({{$currency}} {{$vendor_price}})</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                                <?php $fabric_ids[] = $quotation_list[$i]->item_master_id;  ?>
                                @endif
                            @endfor

                            <thead><tr class="header-tr"><th colspan="10">Accessories</th></tr></thead>
                            <tr><th>Category</th><th>Sub Category</th><th>Color</th><th>Size</th><th>Quantity</th>
                                @for($i=0;$i<count($quote_submitted_vendors_ids);$i++) 
                                    <th>Vendor {{$i+1}}</th>
                                @endfor
                                <th>Purchase</th>
                            </tr>
                            @for($i=0;$i<count($quotation_list);$i++) 
                                @if($quotation_list[$i]->type_id == 2)
                                <tr>
                                    <td>{{$quotation_list[$i]->name_name}}</td>
                                    <td>{{$quotation_list[$i]->quality_name}}</td>
                                    <td>{{$quotation_list[$i]->color_name}}</td>
                                    <td>{{$quotation_list[$i]->content_name}}</td>
                                    <td>{{$quotation_list[$i]->quantity}}</td>
                                     @for($q=0;$q<count($quote_submitted_vendors_ids);$q++) 
                                        <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price; ?>
                                        <?php $vendor_comments = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->comments; ?>
                                        <?php if($vendor_price == $quotation_list[$i]->max_price) $css_max_class = 'max-price-span';else $css_max_class = ''; ?>
                                        <?php if($vendor_price == $quotation_list[$i]->min_price) $css_min_class = 'min-price-span';else $css_min_class = ''; ?>
                                        <td>
                                            <span class="{{$css_max_class}}{{$css_min_class}}"><?php echo $vendor_price; ?></span>
                                            @if(!empty($vendor_comments))
                                                <div class="clear"></div><span class="quotation-comments">({{$vendor_comments}})</span>
                                            @endif 
                                         </td>
                                    @endfor
                                    <td>
                                        <select name="vendor_{{$quotation_list[$i]->item_master_id}}" id="vendor_{{$quotation_list[$i]->item_master_id}}" class="form-control quotation-vendor-sel">
                                            <option value="">Select Vendor</option>
                                            @for($q=0;$q<count($quote_submitted_vendors_ids);$q++) 
                                                <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price; ?>
                                                <option value="{{$quote_submitted_vendors_ids[$q]}}">Vendor {{$q+1}} ({{$currency}} {{$vendor_price}})</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                                <?php $acc_ids[] = $quotation_list[$i]->item_master_id;  ?>
                                @endif
                            @endfor

                            <thead><tr class="header-tr"><th colspan="10">Fabric Process</th></tr></thead>
                            <tr><th>Category</th><th>Type</th><th></th><th></th><th>Quantity</th>
                                @for($i=0;$i<count($quote_submitted_vendors_ids);$i++) 
                                    <th>Vendor {{$i+1}}</th>
                                @endfor
                                <th>Purchase</th>
                                </tr>
                            @for($i=0;$i<count($quotation_list);$i++) 
                                @if($quotation_list[$i]->type_id == 3)
                                <tr>
                                    <td>{{$quotation_list[$i]->name_name}}</td>
                                    <td>{{$quotation_list[$i]->quality_name}}</td>
                                    <td></td>
                                    <td></td>
                                    <td>{{$quotation_list[$i]->quantity}}</td>
                                     @for($q=0;$q<count($quote_submitted_vendors_ids);$q++) 
                                        <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price; ?>
                                        <?php $vendor_comments = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->comments; ?>
                                        <?php if($vendor_price == $quotation_list[$i]->max_price) $css_max_class = 'max-price-span';else $css_max_class = ''; ?>
                                        <?php if($vendor_price == $quotation_list[$i]->min_price) $css_min_class = 'min-price-span';else $css_min_class = ''; ?>
                                        <td>
                                            <span class="{{$css_max_class}}{{$css_min_class}}"><?php echo $vendor_price; ?></span>
                                            @if(!empty($vendor_comments))
                                                <div class="clear"></div><span class="quotation-comments">({{$vendor_comments}})</span>
                                            @endif 
                                        </td>
                                    @endfor
                                    <td>
                                        <select name="vendor_{{$quotation_list[$i]->item_master_id}}" id="vendor_{{$quotation_list[$i]->item_master_id}}" class="form-control quotation-vendor-sel">
                                            <option value="">Select Vendor</option>
                                            @for($q=0;$q<count($quote_submitted_vendors_ids);$q++) 
                                                <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$q]][$quotation_list[$i]->item_master_id]->price; ?>
                                                <option value="{{$quote_submitted_vendors_ids[$q]}}">Vendor {{$q+1}} ({{$currency}} {{$vendor_price}})</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                                <?php $process_ids[] = $quotation_list[$i]->item_master_id;  ?>
                                @endif
                            @endfor 
                        </table>        

                        @csrf
                        <input type="hidden" name="fabric_ids" name="fabric_ids" value="{{implode(',',$fabric_ids)}}">
                        <input type="hidden" name="acc_ids" name="acc_ids" value="{{implode(',',$acc_ids)}}">
                        <input type="hidden" name="process_ids" name="process_ids" value="{{implode(',',$process_ids)}}">
                        </form>
                    @endif


                    @if($quotation_detail->type_id == 2 && count($quote_submitted_vendors_ids) > 0)
                        <form method="post" name="purchase_order_form" id="purchase_order_form">
                           <div class="separator-10">&nbsp;</div>
                           <div class="table-responsive table-filter" >
                               <table class="table table-striped admin-table" cellspacing="0">
                                   @for($q=0;$q<count($quotation_info);$q++) 
                                        <?php $design_data = $quotation_info[$q]['design_data']; $data = $quotation_info[$q]['data']; //print_r($data);exit;  ?>    
                                       <thead><tr class="header-tr"><th colspan="10">Design SKU: {{$design_data['sku']}}, Production Count: {{$design_data['production_count']}}</th></tr></thead>
                                        <tr><th colspan="10">Production Process</th></tr>
                                        <tr><th>Name</th><th>Quantity</th>
                                        @for($i=0;$i<count($quote_submitted_vendors_ids);$i++) 
                                            <th>Vendor {{$i+1}}</th>
                                        @endfor
                                        <th>Purchase</th>
                                        </tr>

                                        @for($i=0;$i<count($data);$i++) 
                                            @if($data[$i]['item_type_id'] == 5)
                                            <tr>
                                                <td>{{$data[$i]['name_name']}}</td>
                                                <td>{{$data[$i]['quantity']}}</td>
                                                <?php $key_id = $data[$i]['item_master_id'].'_'.$data[$i]['design_id'] ?>

                                                @for($z=0;$z<count($quote_submitted_vendors_ids);$z++) 
                                                    <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$z]][$key_id]->price; ?>
                                                    <?php $vendor_comments = $vendors_data[$quote_submitted_vendors_ids[$z]][$key_id]->comments; ?>
                                                    <?php if($vendor_price == $data[$i]['max_price']) $css_max_class = 'max-price-span';else $css_max_class = ''; ?>
                                                    <?php if($vendor_price == $data[$i]['min_price']) $css_min_class = 'min-price-span' ;else $css_min_class = ''; ?>
                                                    <td>
                                                        <span class="{{$css_max_class}}{{$css_min_class}}"><?php echo $vendor_price; ?></span>
                                                        @if(!empty($vendor_comments))
                                                            <div class="clear"></div><span class="quotation-comments">({{$vendor_comments}})</span>
                                                        @endif 
                                                    </td>
                                                @endfor
                                                <td>
                                                    <select name="vendor_{{$key_id}}" id="vendor_{{$key_id}}" class="form-control quotation-vendor-sel">
                                                        <option value="">Select Vendor</option>
                                                        @for($z=0;$z<count($quote_submitted_vendors_ids);$z++) 
                                                            <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$z]][$key_id]->price; ?>
                                                            <option value="{{$quote_submitted_vendors_ids[$z]}}">Vendor {{$z+1}} ({{$currency}} {{$vendor_price}})</option>
                                                        @endfor
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php $item_ids[] = $key_id; ?>
                                            @endif
                                        @endfor

                                        <tr><th colspan="10">Packaging Sheet</th></tr>
                                        <tr>
                                        <th>Name</th><th>Quantity</th>
                                        @for($i=0;$i<count($quote_submitted_vendors_ids);$i++) 
                                            <th>Vendor {{$i+1}}</th>
                                        @endfor
                                        <th>Purchase</th>
                                        </tr>

                                        @for($i=0;$i<count($data);$i++) 
                                            @if($data[$i]['item_type_id'] == 4)
                                            <tr>
                                                <td>{{$data[$i]['name_name']}}</td>
                                                <td>{{$data[$i]['quantity']}}</td>
                                                <?php $key_id = $data[$i]['item_master_id'].'_'.$data[$i]['design_id'] ?>

                                                @for($z=0;$z<count($quote_submitted_vendors_ids);$z++) 
                                                    <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$z]][$key_id]->price; ?>
                                                    <?php $vendor_comments = $vendors_data[$quote_submitted_vendors_ids[$z]][$key_id]->comments; ?>
                                                    <?php if($vendor_price == $data[$i]['max_price']) $css_max_class = 'max-price-span';else $css_max_class = ''; ?>
                                                    <?php if($vendor_price == $data[$i]['min_price']) $css_min_class = 'min-price-span';else $css_min_class = ''; ?>
                                                    <td>
                                                        <span class="{{$css_max_class}}{{$css_min_class}}"><?php echo $vendor_price; ?></span>
                                                        @if(!empty($vendor_comments))
                                                            <div class="clear"></div><span class="quotation-comments">({{$vendor_comments}})</span>
                                                        @endif 
                                                    </td>
                                                @endfor
                                                <td>
                                                    <select name="vendor_{{$key_id}}" id="vendor_{{$key_id}}" class="form-control quotation-vendor-sel">
                                                        <option value="">Select Vendor</option>
                                                        @for($z=0;$z<count($quote_submitted_vendors_ids);$z++) 
                                                            <?php $vendor_price = $vendors_data[$quote_submitted_vendors_ids[$z]][$key_id]->price; ?>
                                                            <option value="{{$quote_submitted_vendors_ids[$z]}}">Vendor {{$z+1}} ({{$currency}} {{$vendor_price}})</option>
                                                        @endfor
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php $item_ids[] = $key_id; ?>
                                            @endif
                                        @endfor

                                    @endfor
                               </table>
                           </div>    
                           @csrf
                           <input type="hidden" name="item_ids" name="item_ids" value="{{implode(',',$item_ids)}}">
                        </form>
                    @endif 

            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/quotation.js') }}" ></script>
@endsection
