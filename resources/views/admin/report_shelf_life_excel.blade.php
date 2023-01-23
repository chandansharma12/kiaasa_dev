<html>
<body>    
<table class="table table-striped admin-table static-header-tbl" cellspacing="0" style="font-size:12px; ">
<thead>
<tr class="header-tr">
<th>Picture</th>    
<th>Vendor</th>
<th>SKU</th>
<th>Vendor SKU</th>
<th>Category</th>
<th>WH In Date</th>
<th>WH In Qty</th>
<th>WH In Value</th>
<th>WH Ret Qty</th>
<th>WH Ret Value</th>
<th>WH Out Date</th>
<th>WH Out Qty</th>
<th>WH Out Value</th>
<th>Store Name</th>
<th>Store Code</th>
<th>Store In Date</th>
<th>Store In Qty</th>
<th>Store In Value</th>
<th>Sale Qty</th>
<th>Bal Qty</th>
<th>Sale Value</th>
<th>Sale Net Amt</th>
<th>MRP</th>
<th> Less than 1000</th>
<th>1000 - 1499</th>
<th>1500 - 1999</th>
<th>2000 - 2499</th>
<th>2500 - 2999</th>
<th> More than 3000</th>
<th>Sale %</th>
<th>Sale Value %</th>
<th>Shelf Life</th>
</tr>
</thead>
<tbody>
<?php $total = array('wh_in_qty'=>0,'wh_out_qty'=>0,'ret_qty'=>0,'store_in_qty'=>0,'sale_qty'=>0,'bal_qty'=>0,'1000'=>0,'1000_1499'=>0,'1500_1999'=>0,'2000_2499'=>0,'2500_2999'=>0,'3000'=>0);
$cur_date = date('Y/m/d') ?>
@for($i=0;$i<count($sku_list);$i++)
<?php $sku_data = $sku_list[$i]; ?>
<?php $key = $sku_list[$i]->grn_id.'__'.$sku_list[$i]->product_sku; ?>
<?php $key_next = (isset($sku_list[$i+1]->grn_id))?$sku_list[$i+1]->grn_id.'__'.$sku_list[$i+1]->product_sku:''; ?>
<tr>
<td><?php $img_name = 'images/pos_product_images/'.$sku_list[$i]->product_id.'/thumbs/'.$sku_list[$i]->image_name ?>@if(file_exists(public_path($img_name)))<img src="{{$img_name}}" width="75" height="75">@endif</td>        
<td style="width:100px;">{{(isset($vendors[$sku_list[$i]->vendor_id]))?$vendors[$sku_list[$i]->vendor_id]:''}} </td>
<td style="width:100px;">{{$sku_list[$i]->vendor_sku}}</td>
<td style="width:100px;">{{$sku_list[$i]->vendor_product_sku}}</td>
<td style="width:100px;">{{$categories[$sku_list[$i]->category_id]}}</td>
<td style="width:100px;">{{date('d-m-Y',strtotime($sku_list[$i]->grn_date))}}</td>
<td style="width:100px;">{{$wh_in_qty = $sku_list[$i]->inv_count}}</td>
<td style="width:100px;">{{$sku_list[$i]->inv_value}}</td>
<?php $store_sku = isset($store_sku_list[$key])?$store_sku_list[$key]:array(); ?>
@for($q=0;$q<count($store_sku);$q++)
@if($q>0)
@if($q == 1) </tr> @endif
<tr>
<td colspan="8"></td>
@endif
<?php $key = $store_sku[$q]->grn_id.'__'.$store_sku[$q]->product_sku.'__'.$store_sku[$q]->store_id; ?>
<td style="width:100px;">{{$ret_qty = (isset($store_sku_return_list[$key]))?$store_sku_return_list[$key]->inv_count:0}}</td>
<td style="width:100px;">{{(isset($store_sku_return_list[$key]))?$store_sku_return_list[$key]->inv_value:0}}</td>
<td style="width:100px;">{{date('d-m-Y',strtotime($store_sku[$q]->wh_out_date))}}</td>
<td>{{$wh_out_qty = $store_sku[$q]->inv_count}}</td>
<td>{{$store_sku[$q]->inv_value}}</td>
<td style="width:150px;">{{$stores[$store_sku[$q]->store_id]['store_name']}}</td>
<td>{{$stores[$store_sku[$q]->store_id]['store_id_code']}}</td>
<td>{{$store_in_date = isset($store_sku_received_list[$key])?date('d-m-Y',strtotime($store_sku_received_list[$key]->store_in_date)):''}}</td>
<td>{{$store_in_qty = (isset($store_sku_received_list[$key]))?$store_sku_received_list[$key]->inv_count:0}}</td>
<td>{{(isset($store_sku_received_list[$key]))?$store_sku_received_list[$key]->inv_value:0}}</td>
<td>{{$sale_qty = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['sale_qty']:0}}</td>
<td>{{$bal_qty = $wh_out_qty-($sale_qty+$ret_qty)}}</td>
<td>{{(isset($sku_sale_list[$key]))?$sku_sale_list[$key]['sale_value']:0}}</td>
<td>{{(isset($sku_sale_list[$key]))?$sku_sale_list[$key]['sale_net_amount']:0}}</td>
<td>{{$sku_list[$i]->sale_price}}</td>
<td>{{$sale_1000 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['1']:0}}</td>
<td>{{$sale_1000_1499 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['2']:0}}</td>
<td>{{$sale_1500_1999 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['3']:0}}</td>
<td>{{$sale_2000_2499 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['4']:0}}</td>
<td>{{$sale_2500_2999 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['5']:0}}</td>
<td>{{$sale_3000 = (isset($sku_sale_list[$key]))?$sku_sale_list[$key]['6']:0}}</td>
<td>{{round(($sale_qty/$wh_out_qty)*100,2)}} %</td>
<td>{{round(($sale_qty/$wh_out_qty)*100,2)}} %</td>
<td>{{($bal_qty>0)?CommonHelper::dateDiff($cur_date,$store_in_date):''}}</td>
@if($q>0)
</tr>
@endif
<?php $total['wh_in_qty'] = $wh_in_qty; ?>
<?php $total['wh_out_qty']+=$wh_out_qty; ?>
<?php $total['ret_qty']+=$ret_qty; ?>
<?php $total['store_in_qty']+=$store_in_qty; ?>
<?php $total['sale_qty']+=$sale_qty; ?>
<?php $total['bal_qty']+=$bal_qty; ?>
<?php $total['1000']+=$sale_1000; ?>
<?php $total['1000_1499']+=$sale_1000_1499; ?>
<?php $total['1500_1999']+=$sale_1500_1999; ?>
<?php $total['2000_2499']+=$sale_2000_2499; ?>
<?php $total['2500_2999']+=$sale_2500_2999; ?>
<?php $total['3000']+=$sale_3000; ?>
@endfor
@if(empty($store_sku))
<td colspan="25" align="center"><b> No Records</b></td></tr>
@endif

@if($key != $key_next)
<tr>
<th colspan="6">Total</th>
<th>{{$total['wh_in_qty']}}</th>
<th></th>
<th>{{$total['ret_qty']}}</th>
<th colspan="2"></th>
<th>{{$total['wh_out_qty']}}</th>
<th colspan="4"></th>
<th>{{$total['store_in_qty']}}</th>
<th></th>
<th>{{$total['sale_qty']}}</th>
<th>{{$total['bal_qty']}}</th>
<th colspan="3"></th>
<th>{{$total['1000']}}</th>
<th>{{$total['1000_1499']}}</th>
<th>{{$total['1500_1999']}}</th>
<th>{{$total['2000_2499']}}</th>
<th>{{$total['2500_2999']}}</th>
<th>{{$total['3000']}}</th>
<th colspan="3"></th>
</tr>
@endif
<?php $total = array('wh_in_qty'=>0,'wh_out_qty'=>0,'ret_qty'=>0,'store_in_qty'=>0,'sale_qty'=>0,'bal_qty'=>0,'1000'=>0,'1000_1499'=>0,'1500_1999'=>0,'2000_2499'=>0,'2500_2999'=>0,'3000'=>0); ?>
@endfor
</tbody>
</table>
</body>
</html>