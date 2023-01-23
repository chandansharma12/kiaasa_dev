@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Items ID List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Items ID List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >

                </div>
            </form>
            <div class="separator-10"></div>
            <div class="row" >
                <div class="col-md-3">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>ID</th>
                                    <th>Color Name</th>
                                    <th>Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($color_list);$i++)
                                    <tr>
                                        <td>{{$color_list[$i]['id']}}</td>
                                        <td>{{$color_list[$i]['name']}}</td>
                                        <td>{{$color_list[$i]['description']}}</td>
                                    </tr>
                                @endfor
                            </tbody>    
                        </table>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>ID</th>
                                    <th>Size Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($size_list);$i++)
                                    <tr>
                                        <td>{{$size_list[$i]['id']}}</td>
                                        <td>{{$size_list[$i]['size']}}</td>
                                    </tr>
                                @endfor
                            </tbody>    
                        </table>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>ID</th>
                                    <th>Story Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($story_list);$i++)
                                    <tr>
                                        <td>{{$story_list[$i]['id']}}</td>
                                        <td>{{$story_list[$i]['name']}}</td>
                                    </tr>
                                @endfor
                            </tbody>    
                        </table>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>ID</th>
                                    <th>Season Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($season_list);$i++)
                                    <tr>
                                        <td>{{$season_list[$i]['id']}}</td>
                                        <td>{{$season_list[$i]['name']}}</td>
                                    </tr>
                                @endfor
                            </tbody>    
                        </table>
                    </div>
                </div>
                
                
                
                <div class="col-md-3">
                    @for($i=0;$i<count($category_list);$i++)
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table " cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>ID</th>
                                    <th>Category Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                    <tr>
                                        <td>{{$category_list[$i]['id']}}</td>
                                        <td>{{$category_list[$i]['name']}}</td>
                                    </tr>
                                    <?php $subcategory_list = $category_list[$i]['subcategory_list']; ?>
                                    
                                    @if(!empty($subcategory_list))
                                    
                                    <tr class="header-tr" style="background-color: #EB8290 !important;">
                                            <th>ID</th>
                                            <th>SubCategory Name</th>
                                        </tr>
                                        @for($q=0;$q<count($subcategory_list);$q++)
                                            <tr >
                                                <td>{{$subcategory_list[$q]['id']}}</td>
                                                <td>{{$subcategory_list[$q]['name']}}</td>
                                            </tr>
                                        @endfor    
                                    @endif
                                        
                                
                            </tbody>    
                        </table>
                    </div>
                    @endfor    
                </div>
                
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')

<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>   
@endsection
