@extends('layouts.default')
@section('content')

@if(!empty($error_message))
    <div class="alert alert-danger">{{$error_message}}</div>
@endif

@if(empty($error_message))
    <nav class="page_bbreadcrumb" aria-label="breadcrumb">

        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard  </a></li> 
        </ol>
        <h2 class="page_heading">Story List</h2>
    </nav> 
    <?php echo CommonHelper::headerMessages(); ?>

        <section class="product_area">
        <div class="container-fluid" >

            <div id="updateStoryStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateStoryStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="s_id" id="s_id" class="form-control" placeholder="Story ID" value="{{request('s_id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="s_name" id="s_name" class="form-control" placeholder="Story Name" value="{{request('s_name')}}" />
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" >
                        <select name="story_action" id="story_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <!--<option value="delete">Delete</option>-->
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editStory" id="editStory" value="Update" class="btn btn-dialog" onclick="updateStoryStatus();"></div>
                    <div class="col-md-1" ><input type="button" name="addStoryBtn" id="addStoryBtn" value="Add Story" class="btn btn-dialog" onclick="addStory();"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="storyContainer">
                <div id="storyListOverlay"><div id="story-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="storyList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr"><th><input type="checkbox" name="story_list_all" id="story_list_all"  value="1" onclick="checkAllCheckboxes(this,'story-list');"> 
                                &nbsp;<?php echo CommonHelper::getSortLink('ID','id','story/list',true,'ASC'); ?> </th>
                                <th><?php echo CommonHelper::getSortLink('Name','name','story/list'); ?> </th>    
                                <th><?php echo CommonHelper::getSortLink('Year','year','story/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Design Count','design_count','story/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Production Count','production_count','story/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Status','status','story/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Created On','created','story/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Updated On','updated','story/list'); ?></th>
                                <th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($story_list);$i++)
                                <tr>
                                    <td><input type="checkbox" name="story_list" id="story_list_{{$story_list[$i]->id}}" class="story-list-chk" value="{{$story_list[$i]->id}}"> &nbsp;{{$story_list[$i]->id}}</td>
                                    <td>{{$story_list[$i]->name}}</td>
                                    <td>{{$story_list[$i]->story_year}}</td>
                                    <td>{{$story_list[$i]->design_count}}</td>
                                    <td>{{$story_list[$i]->production_design_count}}</td>
                                    <td>@if($story_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>@if(!empty($story_list[$i]->created_at)) {{date('d M Y',strtotime($story_list[$i]->created_at))}} @endif</td>
                                    <td>@if(!empty($story_list[$i]->updated_at)) {{date('d M Y',strtotime($story_list[$i]->updated_at))}} @endif</td>
                                    <td><a href="javascript:;" class="story-list-edit" onclick="editStory({{$story_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $story_list->withQueryString()->links() }}
                        <p>Displaying {{$story_list->count()}} of {{ $story_list->total() }} stories.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="edit_story_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Story</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editStorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editStoryErrorMessage"></div>

                <form class="" name="editStoryFrm" id="editStoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="name_edit" type="text" class="form-control" name="name_edit" value="" autofocus style="width:40%;display:inline;"> 
                            <input id="story_year" type="text" class="form-control readonly-field" name="story_year" value="" autofocus style="width:50%;display:inline;" readonly>
                            <div class="invalid-feedback" id="error_validation_edit_name_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Design Count</label>
                            <input id="designCount_edit" type="text" class="form-control" name="designCount_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_edit_designCount_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Production Design Count</label>
                            <input id="productionDesignCount_edit" type="text" class="form-control" name="productionDesignCount_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_edit_productionDesignCount_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="story_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="story_edit_cancel" name="story_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="story_edit_submit" name="story_edit_submit" class="btn btn-dialog" onclick="updateStory();">Submit</button>
                        <input type="hidden" name="story_edit_id" id="story_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_story_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Story</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addStorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addStoryErrorMessage"></div>

                <form class="" name="addStoryFrm" id="addStoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="name_add" type="text" class="form-control" name="name_add" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_add_name_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Design Count</label>
                            <input id="designCount_add" type="text" class="form-control" name="designCount_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_add_designCount_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Production Design Count</label>
                            <input id="designCount_add" type="text" class="form-control" name="productionDesignCount_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_add_productionDesignCount_add"></div>
                        </div>

                    </div>
                    <div class="modal-footer center-footer">
                        <div id="story_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="story_add_cancel" name="story_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="story_add_submit" name="story_add_submit" class="btn btn-dialog" onclick="submitAddStory();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/story.js') }}" ></script>
@endsection
