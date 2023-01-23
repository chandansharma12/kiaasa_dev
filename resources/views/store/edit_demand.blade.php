@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Demands List','link'=>'store/demand/list'),array('name'=>'Edit Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Demand'); ?>  
        
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form id="demand_detail_form" name="demand_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Product">Demand Status</label>						
                        {{str_replace('_',' ',$demand_data->demand_status)}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Product">Comments</label>						
                        {{$demand_data->comments}}    
                    </div>
                
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($demand_data->created_at))}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$demand_data->user_name}}    
                    </div> 
                </div>
            </form> 
            <hr/>
            <?php /* ?>
            @if(!in_array(strtolower($demand_data->demand_status),array('approved','accounts_submitted','accounts_rejected')))
                <button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('edit');" class="btn btn-dialog">Add+</button>
            @endif <?php */ ?>
            <div class="separator-10"></div>
            <div id="demandList">
                <div id="editDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="editDemandSuccessMessage" class="alert alert-success" style="display:none;"></div>
                <div id="updateDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="updateDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
                <form class="" name="createDemandFrm" id="createDemandFrm" method="POST" enctype="multipart/form-data">
                   
                    <div class="demand-items-container" id="">
                        <div class="form-row demand-items-row"id="demand_items_row_first" >
                            <div class="form-group col-md-3">
                                 <label class="label-text">Product</label>
                                <div class="typeahead__container">
                                    <div class="typeahead__field">
                                        <div class="typeahead__query">
                                            <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" value="">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="product_add[]" id="product_add" class="form-control demand-item_edit" value="">
                                <input type="hidden" name="product_id_edit" id="product_id_edit" class="form-control" >
                            </div>
                            <div class="form-group col-md-1">
                                 <label class="label-text">Quantity</label> 
                                <input type="number" name="productQuantity_edit[]" id="productQuantity_edit" class="form-control demand-item-quantity_edit" value=""  onchange="updateDemandPrice('edit');" onkeyup="updateDemandPrice('edit');" >
                            </div>

                            <div class="form-group col-md-1">
                                <label class="label-text">Unit Price</label> 
                                <span class="demand-item-unit-price"></span>
                            </div>  
                            <div class="form-group col-md-1">
                                 <label class="label-text">Total Price</label> 
                                <span class="demand-item-price"></span>
                            </div>    
                            <div class="form-group col-md-1">
                                 <label class="label-text">&nbsp;</label> 
                                <button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('edit');" class="btn btn-dialog">Add+</button>
                            </div> 
                        </div>
                        
                        <?php /* ?>@for($i=0;$i<count($demand_products_list);$i++)
                            <div class="form-row demand-items-row" @if($i==0) id="demand_items_row_first" @endif >
                                <div class="form-group col-md-2">
                                    @if($i==0) <label class="label-text">Product</label> @endif
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                            <div class="typeahead__query">
                                                <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" value="{{$demand_products_list[$i]->product_name}}">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="product_add[]" id="product_add" class="form-control demand-item_edit" value="{{$demand_products_list[$i]->product_id}}">
                                </div>
                                <div class="form-group col-md-1">
                                    @if($i==0) <label class="label-text">Quantity</label> @endif
                                    <input type="number" name="productQuantity_add[]" id="productQuantity_add" class="form-control demand-item-quantity_edit" value="{{$demand_products_list[$i]->product_quantity}}"  onchange="updateDemandPrice('edit');" onkeyup="updateDemandPrice('edit');" @if(in_array(strtolower($demand_data->demand_status),array('approved','accounts_submitted','accounts_rejected'))) disabled @endif>
                                </div>
                                
                                <div class="form-group col-md-1">
                                    @if($i==0) <label class="label-text">Unit Price</label> @endif
                                    <span class="demand-item-unit-price"></span>
                                </div>  
                                <div class="form-group col-md-1">
                                    @if($i==0) <label class="label-text">Total Price</label> @endif
                                    <span class="demand-item-price"></span>
                                </div>    
                                
                                @if(!in_array(strtolower($demand_data->demand_status),array('approved','accounts_submitted','accounts_rejected')))
                                    <div class="form-group col-md-1 ">
                                        @if($i==0) <label class="label-text">Delete</label> @endif
                                        <button type="button" id="demand_add_submit" name="demand_add_submit" class="btn btn-dialog" value="Submit" onclick="deleteDemandItem({{$demand_products_list[$i]->id}});">X</button>
                                    </div>
                                @endif
                            </div>
                        @endfor   
                        
                        @if(count($demand_products_list) == 0)
                            <div class="form-row demand-items-row" id="demand_items_row_first">
                                <div class="form-group col-md-2">
                                    <label class="label-text">Store Item</label>
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                            <div class="typeahead__query">
                                                <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" value="">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="product_add[]" id="product_add" class="form-control demand-item_edit" value=""> 
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="label-text">Quantity</label>
                                    <input type="number" name="productQuantity_add[]" id="productQuantity_add" class="form-control demand-item-quantity_edit" onkeyup="updateDemandPrice('edit');">
                                </div>
                                
                                <div class="form-group col-md-1">
                                    <label class="label-text">Total Price</label>
                                    <span class="demand-item-price"></span>
                                </div>   
                            </div>  
                        @endif   <?php */ ?> 
                    </div>
                    <div class="form-row" >
                        <div class="form-group col-md-8" id="demands_list_div">
                            <span  id="demand_grand_total" name="demand_grand_total"></span>
                        </div> 
                    </div> 
                    <br/>
                    
                    <div class="separator-10"></div>
                    <div class="form-row" >
                        @if(!in_array(strtolower($demand_data->demand_status),array('accounts_submitted')))
                            <button type="button" id ="demand_edit_submit" name="demand_edit_submit" class="btn btn-dialog" value="Submit" onclick="submitEditDemand({{$demand_data->id}});">Update Demand</button>&nbsp;&nbsp;
                        @endif
                       
                        @if(!in_array(strtolower($demand_data->demand_status),array('accounts_submitted')))
                            <button type="button" id="demand_edit_cancel" name="demand_edit_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('store/demand/list')}}'">Cancel</button>
                            <input type="hidden" name="edit_demand" id="edit_demand" value="1">
                        @endif
                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="confirm_delete_demand_item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteDemandItemErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteDemandItemSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Demand Item<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-demand_item-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_rows_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_rows_btn" name="delete_rows_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    

@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >
        var action_type = 'edit',page_type = 'demand';
        var demand_id = {{$demand_data->id}};
    </script>
@endif
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
