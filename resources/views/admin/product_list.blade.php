@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Product List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Product List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateProductStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateProductStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >

                    <div class="col-md-2" >
                        <select name="product_action" id="product_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editProduct" id="editProduct" value="Update" class="btn btn-dialog" onclick="updateProductStatus();"></div>
                    <div class="col-md-1" ><input type="button" name="addProductBtn" id="addProductBtn" value="Add Product" class="btn btn-dialog" onclick="addProduct();"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="productContainer">
                <div id="productListOverlay"><div id="product-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>

                <div id="productList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr"><th><input type="checkbox" name="product_list_all" id="product_list_all"  value="1" onclick="selectAllProduct(this);"> 
                                    &nbsp;<?php echo CommonHelper::getSortLink('ID','id','product/list',true,'ASC'); ?>
                                    <th><?php echo CommonHelper::getSortLink('Name','name','product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Type','type','product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Parent','parent','product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created','product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Updated On','updated','product/list'); ?></th>
                                    <th>Action</th>
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($product_list);$i++)
                                <tr>
                                    <td><input type="checkbox" name="product_list" id="product_list_{{$product_list[$i]->id}}" class="product-list" value="{{$product_list[$i]->id}}"> &nbsp;{{$product_list[$i]->id}}</td>
                                    <td>{{$product_list[$i]->name}}</td>
                                    <td>{{$product_types[$product_list[$i]->type_id]}}</td>
                                    <td>{{$product_list[$i]->parent_product_name}}</td>
                                    <td>@if($product_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>@if(!empty($product_list[$i]->created_at)) {{date('d M Y',strtotime($product_list[$i]->created_at))}} @endif</td>
                                    <td>@if(!empty($product_list[$i]->updated_at)) {{date('d M Y',strtotime($product_list[$i]->updated_at))}} @endif</td>
                                    <td><a href="javascript:;" class="user-list-edit" onclick="editProduct({{$product_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $product_list->withQueryString()->links() }}
                        <p>Displaying {{$product_list->count()}} of {{ $product_list->total() }} products.</p>
                    </div>
                </div>

             </div>
        </div>
    </section>

    <div class="modal fade" id="edit_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editProductErrorMessage"></div>

                <form class="" name="editProductFrm" id="editProductFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Type</label>
                            <select name="productType_edit" id="productType_edit" class="form-control" onchange="updateProductData(this.value,'edit');">
                                <option value="">Select</option>
                                    @foreach($product_types as $id=>$type)
                                        <option value="{{$id}}">{{$type}}</option>
                                    @endforeach    
                            </select>
                            <div class="invalid-feedback" id="error_validation_productType_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="productName_edit" type="text" class="form-control" name="productName_edit" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_productName_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Style</label>
                            <input id="productStyle_edit" type="text" class="form-control" name="productStyle_edit" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_productStyle_edit"></div>
                        </div>
                        <div class="form-group" id="parent_product_div_edit" style="display:none;">
                            <label>Parent Product</label>
                            <select name="productParent_edit" id="productParent_edit" class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($parent_products);$i++)
                                    <option value="{{$parent_products[$i]['id']}}">{{$parent_products[$i]['name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_productParent_edit"></div>
                        </div>
                        <div class="form-group" id="parent_category_div_edit" style="display:none;">
                            <label>Parent Category</label>
                            <select name="productCategory_edit" id="productCategory_edit" class="form-control">
                                <option value="">Select</option>
                                @for($i=0;$i<count($parent_category);$i++)
                                    <option value="{{$parent_category[$i]['id']}}">{{$parent_category[$i]['name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_productCategory_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="product_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="product_edit_cancel" name="product_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="product_edit_submit" name="product_edit_submit" class="btn btn-dialog" onclick="updateProduct();">Submit</button>
                        <input type="hidden" name="product_edit_id" id="product_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_product_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addProductSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addProductErrorMessage"></div>

                <form class="" name="addProductFrm" id="addProductFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Type</label>
                                <select name="productType_add" id="productType_add" class="form-control" onchange="updateProductData(this.value,'add');">
                                    <option value="">Select</option>
                                        @foreach($product_types as $id=>$type)
                                            <option value="{{$id}}">{{$type}}</option>
                                        @endforeach    
                                </select>
                                <div class="invalid-feedback" id="error_validation_productType_add"></div>
                            </div>
                            <div class="form-group" >
                                <label>Name</label>
                                <input id="productName_add" type="text" class="form-control" name="productName_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_productName_add"></div>
                            </div>
                            <div class="form-group" >
                                <label>Style</label>
                                <input id="productStyle_add" type="text" class="form-control" name="productStyle_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_productStyle_add"></div>
                            </div>
                            <div class="form-group" id="parent_product_div_add" style="display:none;">
                                <label>Parent Product</label>
                                <select name="productParent_add" id="productParent_add" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($parent_products);$i++)
                                        <option value="{{$parent_products[$i]['id']}}">{{$parent_products[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_productParent_add"></div>
                            </div>
                            <div class="form-group" id="parent_category_div_add" style="display:none;">
                                <label>Parent Category</label>
                                <select name="productCategory_add" id="productCategory_add" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($parent_category);$i++)
                                        <option value="{{$parent_category[$i]['id']}}">{{$parent_category[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_productCategory_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="product_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="product_add_cancel" name="product_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="product_add_submit" name="product_add_submit" class="btn btn-dialog" onclick="submitAddProduct();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/product.js') }}" ></script>
@endsection
