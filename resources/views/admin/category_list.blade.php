@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Category List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Category List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateCategoryStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateCategoryStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >

                    <div class="col-md-2" >
                        <select name="category_action" id="category_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editCategory" id="editCategory" value="Update" class="btn btn-primary" onclick="updateCategoryStatus();"></div>
                    <div class="col-md-1" ><input type="button" name="addCategoryBtn" id="addCategoryBtn" value="Add Category" class="btn btn-primary" onclick="addCategory();"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="categoryContainer">
                <div id="categoryListOverlay"><div id="category-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>

                <div id="categoryList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead><tr class="header-tr"><th><input type="checkbox" name="category_list_all" id="category_list_all"  value="1" onclick="selectAllCategory(this);"> &nbsp;Name</th>
                                    <th>Parent</th><th>Status</th><th>Created On</th><th>Updated On</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($category_list);$i++)
                                <tr>
                                    <td><input type="checkbox" name="category_list" id="category_list_{{$category_list[$i]->id}}" class="category-list" value="{{$category_list[$i]->id}}"> &nbsp;{{$category_list[$i]->name}}</td>
                                    <td>{{$category_list[$i]->parent_category_name}}</td>
                                    <td>@if($category_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>@if(!empty($category_list[$i]->created_at)) {{date('d M Y',strtotime($category_list[$i]->created_at))}} @endif</td>
                                    <td>@if(!empty($category_list[$i]->updated_at)) {{date('d M Y',strtotime($category_list[$i]->updated_at))}} @endif</td>
                                    <td><a href="javascript:;" class="user-list-edit" onclick="editCategory({{$category_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $category_list->links() }}
                        <p>Displaying {{$category_list->count()}} of {{ $category_list->total() }} categories.</p>
                    </div>
                </div>

             </div>
        </div>
    </section>

    <div class="modal fade" id="edit_category_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editCategorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editCategoryErrorMessage"></div>

                <form class="" name="editCategoryFrm" id="editCategoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="categoryName_edit" type="text" class="form-control" name="categoryName_edit" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_categoryName_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Parent Category</label>
                            <select name="categoryParent_edit" id="categoryParent_edit" class="form-control">
                                <option value="0">Select</option>
                                @for($i=0;$i<count($parent_categories);$i++)
                                    <option value="{{$parent_categories[$i]['id']}}">{{$parent_categories[$i]['name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_categoryParent_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="category_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="category_edit_cancel" name="category_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="category_edit_submit" name="category_edit_submit" class="btn btn-primary" onclick="updateCategory();">Submit</button>
                        <input type="hidden" name="category_edit_id" id="category_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_category_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addCategorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addCategoryErrorMessage"></div>

                <form class="" name="addCategoryFrm" id="addCategoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Name</label>
                                <input id="categoryName_add" type="text" class="form-control" name="categoryName_add" value="" autofocus >
                                <div class="invalid-feedback" id="error_validation_categoryName_add"></div>
                            </div>
                            <div class="form-group" >
                                <label>Parent Category</label>
                                <select name="categoryParent_add" id="categoryParent_add" class="form-control">
                                    <option value="0">Select</option>
                                    @for($i=0;$i<count($parent_categories);$i++)
                                        <option value="{{$parent_categories[$i]['id']}}">{{$parent_categories[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_categoryParent_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="category_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="category_add_cancel" name="category_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="category_add_submit" name="category_add_submit" class="btn btn-primary" onclick="submitAddCategory();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/category.js') }}" ></script>
@endsection
