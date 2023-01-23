@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Quotation Requests ','link'=>'quotation/list'),array('name'=>'Quotation Submission')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Quotation Submission '); ?>

    <?php $fabric_ids = $acc_ids = $process_ids = $pack_sheet_ids = $prod_process_ids = array(); ?> 
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
                @if($quotation_vendor_detail->quotation_submitted == 1 && !session('success_message'))
                    <br/>
                    <div class="alert alert-danger">
                        Quotation already submitted
                    </div>
                @endif
                @if($quotation_vendor_detail->quotation_submitted == 0)
                    @if($quotation_detail->type_id == 1)
                        <form method="post">
                        <div class="separator-10">&nbsp;</div>
                        <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0">
                                <thead><tr class="header-tr"><th colspan="10">Fabric</th></tr></thead>
                                <tr><th>Name</th><th>Color</th><th>Width</th><th></th><th>Quantity</th><th>Price</th><th>Comments</th></tr>
                                    @for($i=0;$i<count($quotation_data);$i++) 
                                        @if($quotation_data[$i]->type_id == 1)
                                        <tr>
                                            <td>{{$quotation_data[$i]->name_name}}</td>
                                            <td>{{$quotation_data[$i]->color_name}}</td>
                                            <td>{{$quotation_data[$i]->width_name}} {{$quotation_data[$i]->unit_code}}</td>
                                            <td></td>
                                            <td>{{$quotation_data[$i]->quantity}}</td>
                                            <td><input type="text" name="fabric_price_{{$quotation_data[$i]->id}}" id="fabric_price_{{$quotation_data[$i]->id}}" class="form-control" value="{{old('fabric_price_'.$quotation_data[$i]->id)}}" style="width:80px;border:1px solid #ccc;"></td>
                                            <td><textarea name="fabric_comment_{{$quotation_data[$i]->id}}" id="fabric_comment_{{$quotation_data[$i]->id}}" class="form-control" value=""  style="width:200px;border:1px solid #ccc;"></textarea></td>
                                        </tr>
                                        <?php $fabric_ids[] = $quotation_data[$i]->id; ?>
                                        @endif
                                    @endfor

                                    <thead><tr class="header-tr"><th colspan="10">Accessories</th></tr></thead>
                                    <tr><th>Category</th><th>Sub Category</th><th>Color</th><th>Size</th><th>Quantity</th><th>Price</th><th>Comments</th></tr>
                                    @for($i=0;$i<count($quotation_data);$i++) 
                                        @if($quotation_data[$i]->type_id == 2)
                                        <tr>
                                            <td>{{$quotation_data[$i]->name_name}}</td>
                                            <td>{{$quotation_data[$i]->quality_name}}</td>
                                            <td>{{$quotation_data[$i]->color_name}}</td>
                                            <td>{{$quotation_data[$i]->content_name}}</td>
                                            <td>{{$quotation_data[$i]->quantity}}</td>
                                            <td><input type="text" name="acc_price_{{$quotation_data[$i]->id}}" id="acc_price_{{$quotation_data[$i]->id}}" class="form-control" value="{{old('acc_price_'.$quotation_data[$i]->id)}}" style="width:80px;border:1px solid #ccc;"></td>
                                            <td><textarea name="acc_comment_{{$quotation_data[$i]->id}}" id="acc_comment_{{$quotation_data[$i]->id}}" class="form-control" value=""  style="width:200px;border:1px solid #ccc;"></textarea></td>
                                        </tr>
                                        <?php $acc_ids[] = $quotation_data[$i]->id; ?>
                                        @endif
                                    @endfor

                                    <thead><tr class="header-tr"><th colspan="10">Process</th></tr></thead>
                                    <tr><th>Category</th><th>Type</th><th></th><th></th><th></th><th>Price</th><th>Comments</th></tr>
                                    @for($i=0;$i<count($quotation_data);$i++) 
                                        @if($quotation_data[$i]->type_id == 3)
                                        <tr>
                                            <td>{{$quotation_data[$i]->name_name}}</td>
                                            <td>{{$quotation_data[$i]->quality_name}}</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{$quotation_data[$i]->quantity}}</td>
                                            <td><input type="text" name="process_price_{{$quotation_data[$i]->id}}" id="process_price_{{$quotation_data[$i]->id}}" class="form-control" value="{{old('process_price_'.$quotation_data[$i]->id)}}" style="width:80px;border:1px solid #ccc;"></td>
                                            <td><textarea name="process_comment_{{$quotation_data[$i]->id}}" id="process_comment_{{$quotation_data[$i]->id}}" class="form-control" value=""  style="width:200px;border:1px solid #ccc;"></textarea></td>
                                        </tr>
                                        <?php $process_ids[] = $quotation_data[$i]->id; ?>
                                        @endif
                                    @endfor
                            </table>        
                            <input type="submit" name="submit_quotation" id="submit_quotation" class="btn_box" value="Submit Quotation">
                            <input type="hidden" name="fabric_ids" id="fabric_ids" value="<?php echo implode(',',$fabric_ids); ?>">
                            <input type="hidden" name="acc_ids" id="acc_ids" value="<?php echo implode(',',$acc_ids); ?>">
                            <input type="hidden" name="process_ids" id="process_ids" value="<?php echo implode(',',$process_ids); ?>">
                            @csrf
                            </form>
                        @endif

                        @if($quotation_detail->type_id == 2)
                        <form method="post">
                            <div class="separator-10">&nbsp;</div>
                            <div class="table-responsive table-filter">
                                <table class="table table-striped admin-table" cellspacing="0">
                                    @foreach($quotation_data as $design_id=>$quotation_info) 
                                    <thead><tr class="header-tr"><th colspan="10">Design SKU: {{$quotation_info['design_data']['sku']}}, Production Count: {{$quotation_info['design_data']['production_count']}}</th></tr></thead>

                                    <thead><tr class="header-tr"><th colspan="10">Packaging Sheet</th></tr></thead>
                                            <tr><th>Name</th><th>Quantity</th><th>Price</th><th>Comments</th></tr>
                                            @for($i=0;$i<count($quotation_info['rows']);$i++) 
                                                @if($quotation_info['rows'][$i]->type_id == 4)
                                                <tr>
                                                    <td>{{$quotation_info['rows'][$i]->name_name}}</td>
                                                    <td>{{$quotation_info['rows'][$i]->quantity}}</td>
                                                    <td><input type="text" name="pack_sheet_price_{{$quotation_info['rows'][$i]->id}}" id="pack_sheet_price_{{$quotation_info['rows'][$i]->id}}" class="form-control" value="{{old('pack_sheet_price_'.$quotation_info['rows'][$i]->id)}}" style="width:80px;border:1px solid #ccc;"></td>
                                                    <td><textarea name="pack_sheet_comment_{{$quotation_info['rows'][$i]->id}}" id="pack_sheet_comment_{{$quotation_info['rows'][$i]->id}}" class="form-control" value=""  style="width:200px;border:1px solid #ccc;"></textarea></td>
                                                </tr>
                                                <?php $pack_sheet_ids[] = $quotation_info['rows'][$i]->id; ?>
                                                @endif
                                            @endfor

                                            <thead><tr class="header-tr"><th colspan="10">Production Process</th></tr></thead>
                                            <tr><th>Name</th><th>Quantity</th><th>Price</th><th>Comments</th></tr>
                                            @for($i=0;$i<count($quotation_info['rows']);$i++) 
                                                @if($quotation_info['rows'][$i]->type_id == 5)
                                                <tr>
                                                    <td>{{$quotation_info['rows'][$i]->name_name}}</td>
                                                    <td>{{$quotation_info['rows'][$i]->quantity}}</td>
                                                    <td><input type="text" name="prod_process_price_{{$quotation_info['rows'][$i]->id}}" id="prod_process_price_{{$quotation_info['rows'][$i]->id}}" class="form-control" value="{{old('prod_process_price_'.$quotation_info['rows'][$i]->id)}}" style="width:80px;border:1px solid #ccc;"></td>
                                                    <td><textarea name="prod_process_comment_{{$quotation_info['rows'][$i]->id}}" id="prod_process_comment_{{$quotation_info['rows'][$i]->id}}" class="form-control" value=""  style="width:200px;border:1px solid #ccc;"></textarea></td>
                                                </tr>
                                                <?php $prod_process_ids[] = $quotation_info['rows'][$i]->id; ?>
                                                @endif
                                            @endfor

                                    @endforeach
                                </table>
                            </div>    
                            @csrf
                            <input type="submit" name="submit_quotation" id="submit_quotation" class="btn_box" value="Submit Quotation">
                            <input type="hidden" name="pack_sheet_ids" id="pack_sheet_ids" value="<?php echo implode(',',$pack_sheet_ids); ?>">
                            <input type="hidden" name="prod_process_ids" id="prod_process_ids" value="<?php echo implode(',',$prod_process_ids); ?>">
                        </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="error_request_quotation_sku" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteErrorMessage"></div>
                <div class="modal-body">
                    <h6>Select the records for quotation<br/></h6>
                </div>
                <div class="modal-footer center-footer">
                    <button type="button" data-dismiss="modal" class="btn btn-dialog" id="error_request_quotation_sku_btn" name="error_request_quotation_sku_btn">Close</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/quotation.js') }}" ></script>
@endsection
