@extends('layouts.default')
@section('content')

<!-- Strat breadcrumb -->

<nav class="page_bbreadcrumb" aria-label="breadcrumb">
    @if($user->user_type == 5)
    <form method = "POST" action="{{url('/design/add')}}" name="design_add_frm" id="design_add_frm">
        <button type="submit" class="btn_add pull-right" style="border:none;">Add New Design</button> 
        @csrf
    </form> 
    @endif
  
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard  </a></li> 
    </ol>
    <h2 class="page_heading">Dashboard</h2>
</nav> 
<div style="clear:both;height:15px;"></div>
@if (session('error_message'))
    <br/>
    <div class="alert alert-danger">
        {{ session('error_message') }}
    </div>
@endif

@if (session('success_message'))
    <br/>
    <div class="alert alert-success">
        {{ session('success_message') }}
    </div>
@endif

<!-- End breadcrumb -->
<!-- Strat Search Area -->  
<section class="search_area" style="display:none;">
    <div class="container-fluid">
        <div class="row align-items-md-center">
            <!--<div class="col-md-6">
                <div class="form-group">
                    <i class="fa fa-search"></i>
                    <input type="text" class="form-control" id="search_text" name="search_text" placeholder="Search by SKU ID, Product and more">
                </div>  
            </div> 
            <div class="col-md-6 text-right">
                <a href="javascript:;" data-toggle="modal" data-target="#filters-div" >FILTERS</a> 
            </div> -->
        </div>
    </div>
</section>
<!-- End Search Area -->

<section class="product_area">
    <div class="container-fluid" >
        <div id="productsListErrorMessage" class="alert alert-danger" style="display:none;"></div>
        
        <div id="productsContainer">
            <!--<div id="productsListOverlay"><div id="products-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>-->
            <div id="productsList"></div>
        </div>
    </div>
</section>

<div class="modal fade" id="filters-div" tabindex="-1" role="dialog" aria-hidden="true" >
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Filters</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
            </div>
            <div class="alert alert-danger alert-dismissible" style="display:none" id="filterErrorMessage"></div>
            <div class="modal-body">
                <div class="table-responsive table-filter"><table class="table"><tr><th>Status</th>
                <?php $status_list = array('Approved','Rejected','Ongoing','Draft'); ?>            
                @for($i=0;$i<count($status_list);$i++)
                    <td><input type="checkbox" name="filter_status_{{$i}}" id="filter_status_{{$i}}" class="filter-status" value="{{strtolower($status_list[$i])}}"> {{ucfirst($status_list[$i])}}</td>
                @endfor    
                </tr>
                </table></div>
                            
                <div class="table-responsive table-filter"><table class="table"><tr><th>Product</th>
                @for($i=0;$i<count($products);$i++)
                    <td><input type="checkbox" name="filter_product_{{$i}}" id="filter_product_{{$i}}" class="filter-product" value="{{$products[$i]['id']}}"> {{ucfirst($products[$i]['name'])}}</td>
                @endfor    
                </tr>
                </table></div>

                <div class="table-responsive table-filter"><table class="table"><tr><th>Category</th>
                @for($i=0;$i<count($categories);$i++)
                    <td><input type="checkbox" name="filter_category_{{$i}}" id="filter_category_{{$i}}" class="filter-category" value="{{$categories[$i]['id']}}"> {{ucfirst($categories[$i]['name'])}}</td>
                @endfor    
                </tr>
                </table></div>
            </div>
            <div class="modal-footer">
                <div id="filter-apply-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                <a href="javascript:;" id="filter_clear_btn" name="filter_clear_btn" style="background-color:#fff;border:none;color:#7F232F;font-size: 16px;">CLEAR ALL</a> &nbsp;&nbsp;
                <button type="button"  class="btn_box" id="filter_apply_btn" name="filter_apply_btn" style="border:none;">APPLY FILTERS</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/dashboard.js') }}" ></script>
@endsection
