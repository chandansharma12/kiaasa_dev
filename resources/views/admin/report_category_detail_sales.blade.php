@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Category Detail Sales Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Category Detail Sales Report');$page_name = 'category_detail_sales_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@else{{date('d-m-Y',strtotime(CommonHelper::getDefaultDaysInterval()))}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@else{{date('d-m-Y')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('category/detail/report/sales?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div style="width:6650px">&nbsp;</div>
                    <div class="table-responsive table-filter" style="width:6600px;">
                        
                        <table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th colspan="2" class="th-border-1"></th>
                                    
                                    @for($i=0;$i<count($category_list);$i++)
                                        @if(!in_array($category_list[$i]['id'],$category_ids))
                                            <th colspan="2" class="th-border-1">{{$category_list[$i]['name']}}</th>
                                        @endif
                                    @endfor    
                                    <th colspan="14" class="th-border-1">MNM</th>
                                    <th colspan="14" class="th-border-1">KURTA</th>
                                    <th colspan="12"  class="th-border-1">KURTA SET</th>
                                    <th colspan="10"  class="th-border-1">SKD</th>
                                    <th colspan="2" class="th-border-1">TOTAL CATEGORY</th>
                                </tr>
                                <tr class="header-tr">
                                    <th>SNo</th>
                                    <th>Store</th>
                                    
                                    @for($i=0;$i<count($category_list);$i++)
                                        @if(!in_array($category_list[$i]['id'],$category_ids))
                                            <th style="border-left:1px solid #fff;">Sale Qty</th>
                                            <th>Bal Qty</th>
                                        @endif
                                    @endfor
                                    
                                    <th style="border-left:1px solid #fff;">0 - 999 Sale</th>
                                    <th>Bal</th>
                                    <th>1000 - 1999 Sale</th>
                                    <th>Bal</th>
                                    <th>2000 - 2899 Sale</th>
                                    <th>Bal</th>
                                    <th>2900 - 3999 Sale</th>
                                    <th>Bal</th>
                                    <th>4000 - 4999 Sale</th>
                                    <th>Bal</th>
                                    <th> > 4999 Sale</th>
                                    <th>Bal</th>
                                    <th>Total Sale</th>
                                    <th>Total Bal</th>
                                    
                                    <th style="border-left:1px solid #fff;">0 - 999 Sale</th>
                                    <th>Bal</th>
                                    <th>1000 - 1999 Sale</th>
                                    <th>Bal</th>
                                    <th>2000 - 2899 Sale</th>
                                    <th>Bal</th>
                                    <th>2900 - 3999 Sale</th>
                                    <th>Bal</th>
                                    <th>4000 - 4999 Sale</th>
                                    <th>Bal</th>
                                    <th> > 4999 Sale</th>
                                    <th>Bal</th>
                                    <th>Total Sale</th>
                                    <th>Total Bal</th>
                                    
                                    <th style="border-left:1px solid #fff;">0 - 1999 Sale</th>
                                    <th>Bal</th>
                                    <th>2000 - 2999 Sale</th>
                                    <th>Bal</th>
                                    <th>3000 - 3999 Sale</th>
                                    <th>Bal</th>
                                    <th>4000 - 4999 Sale</th>
                                    <th>Bal</th>
                                    <th> > 4999 Sale</th>
                                    <th>Bal</th>
                                    <th>Total Sale</th>
                                    <th>Total Bal</th>
                                    
                                    <th style="border-left:1px solid #fff;">0 - 3999 Sale</th>
                                    <th>Bal</th>
                                    <th>4000 - 5999 Sale</th>
                                    <th>Bal</th>
                                    <th>6000 - 7999 Sale</th>
                                    <th>Bal</th>
                                    <th> > 7999 Sale</th>
                                    <th>Bal</th>
                                    <th>Total Sale</th>
                                    <th>Total Bal</th>
                                    
                                    <th style="border-left:1px solid #fff;">Sale</th>
                                    <th>Bal</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_data = array('inv_sold'=>0,'inv_bal'=>0,'cat_sold'=>0,'cat_bal'=>0); ?>
                                @for($i=0;$i<count($store_list);$i++)
                                    
                                    <?php $store_id = $store_list[$i]['id']; ?>
                                    <?php $ranges1 = array([0,999],[1000,1999],[2000,2899],[2900,3999],[4000,4999],[5000,100000]); ?>
                                    <?php $ranges2 = array([0,1999],[2000,2999],[3000,3999],[4000,4999],[5000,100000]); ?>
                                    <?php $ranges3 = array([0,3999],[4000,5999],[6000,7999],[8000,100000]); ?>
                                    
                                    <tr>
                                        <td>{{$i+1}}</td>
                                        <td>{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</td>
                                        
                                        @for($q=0;$q<count($category_list);$q++)
                                            @if(!in_array($category_list[$q]['id'],$category_ids))
                                                <?php $key = $store_list[$i]['id'].'_'.$category_list[$q]['id']; ?>

                                                <td style="border-left:1px solid #EB5D70;">{{$inv_sold = isset($category_sold[$key])?$category_sold[$key]:0}}</td>
                                                <td>{{$inv_bal = isset($category_balance[$key])?$category_balance[$key]:0}}</td>
                                                <?php $total_data['inv_sold']+=$inv_sold; ?>
                                                <?php $total_data['inv_bal']+=$inv_bal; ?>
                                            @endif
                                        @endfor  
                                        
                                        @for($q=0;$q<count($ranges1);$q++)
                                            <?php $range = $ranges1[$q];$min = $range[0];$max = $range[1]; ?>
                                            <?php $key = $store_list[$i]['id'].'_18_'.$min.'_'.$max; ?>
                                            <?php if($q == 0) $css_1 = 'border-left:1px solid #EB5D70;';else $css_1  = ''; ?>
                                           
                                            <td style="{{$css_1}}">{{$inv_sold = isset($category_sold[$key])?$category_sold[$key]:0}}</td>
                                            <td >{{$inv_bal = isset($category_balance[$key])?$category_balance[$key]:0}}</td>
                                            <?php $total_data['inv_sold']+=$inv_sold; ?>
                                            <?php $total_data['inv_bal']+=$inv_bal; ?>
                                            <?php $total_data['cat_sold']+=$inv_sold; ?>
                                            <?php $total_data['cat_bal']+=$inv_bal; ?>
                                        @endfor  
                                        <td><b>{{$total_data['cat_sold']}}</b></td>
                                        <td style="border-right:1px solid #EB5D70;"><b>{{$total_data['cat_bal']}}</b></td>
                                        
                                        <?php $total_data['cat_bal'] = $total_data['cat_sold'] = 0; ?>
                                        
                                        @for($q=0;$q<count($ranges1);$q++)
                                            <?php $range = $ranges1[$q];$min = $range[0];$max = $range[1]; ?>
                                            <?php $key = $store_list[$i]['id'].'_646_'.$min.'_'.$max; ?>
                                            
                                            <td>{{$inv_sold = isset($category_sold[$key])?$category_sold[$key]:0}}</td>
                                            <td>{{$inv_bal = isset($category_balance[$key])?$category_balance[$key]:0}}</td>
                                            <?php $total_data['inv_sold']+=$inv_sold; ?>
                                            <?php $total_data['inv_bal']+=$inv_bal; ?>
                                            <?php $total_data['cat_sold']+=$inv_sold; ?>
                                            <?php $total_data['cat_bal']+=$inv_bal; ?>
                                        @endfor    
                                        <td><b>{{$total_data['cat_sold']}}</b></td>
                                        <td style="border-right:1px solid #EB5D70;"><b>{{$total_data['cat_bal']}}</b></td>
    
                                        <?php $total_data['cat_bal'] = $total_data['cat_sold'] = 0; ?>
                                        
                                        @for($q=0;$q<count($ranges2);$q++)
                                            <?php $range = $ranges2[$q];$min = $range[0];$max = $range[1]; ?>
                                            <?php $key = $store_list[$i]['id'].'_139_'.$min.'_'.$max; ?>
                                            <td>{{$inv_sold = isset($category_sold[$key])?$category_sold[$key]:0}}</td>
                                            <td>{{$inv_bal = isset($category_balance[$key])?$category_balance[$key]:0}}</td>
                                            <?php $total_data['inv_sold']+=$inv_sold; ?>
                                            <?php $total_data['inv_bal']+=$inv_bal; ?>
                                            <?php $total_data['cat_sold']+=$inv_sold; ?>
                                            <?php $total_data['cat_bal']+=$inv_bal; ?>
                                        @endfor    
                                        <td><b>{{$total_data['cat_sold']}}</b></td>
                                        <td style="border-right:1px solid #EB5D70;"><b>{{$total_data['cat_bal']}}</b></td>
                                        
                                        <?php $total_data['cat_bal'] = $total_data['cat_sold'] = 0; ?>
                                        
                                         @for($q=0;$q<count($ranges3);$q++)
                                            <?php $range = $ranges3[$q];$min = $range[0];$max = $range[1]; ?>
                                            <?php $key = $store_list[$i]['id'].'_20_'.$min.'_'.$max; ?>
                                            <td>{{$inv_sold = isset($category_sold[$key])?$category_sold[$key]:0}}</td>
                                            <td>{{$inv_bal = isset($category_balance[$key])?$category_balance[$key]:0}}</td>
                                            <?php $total_data['inv_sold']+=$inv_sold; ?>
                                            <?php $total_data['inv_bal']+=$inv_bal; ?>
                                            <?php $total_data['cat_sold']+=$inv_sold; ?>
                                            <?php $total_data['cat_bal']+=$inv_bal; ?>
                                        @endfor    
                                        <td><b>{{$total_data['cat_sold']}}</b></td>
                                        <td style="border-right:1px solid #EB5D70;"><b>{{$total_data['cat_bal']}}</b></td>
                                        
                                        <td> {{$total_data['inv_sold']}}</td>
                                        <td>{{$total_data['inv_bal']}}</td>
                                    </tr>
                                    <?php $total_data['inv_sold'] = 0; ?>
                                    <?php $total_data['inv_bal'] = 0; ?>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr>
                                    <!--<th>Total</th>-->
                                    
                                </tr>
                            </tfoot>
                        </table>
                        <hr/>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
