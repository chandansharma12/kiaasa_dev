<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>receipt</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.css">

  <style>@page { size: 100mm 50mm;margin: 1mm; }
  body.receipt .sheet { width: 100mm; height: 50mm;margin: 1mm; } /* change height as you like */
@media print { body.receipt { width: 100mm;height: 50mm;margin: 1mm; } } /* this line is needed for fixing Chrome's bug */
  
</style>
</head>

<body class="receipt" style="margin:0px;">
  <section class="sheet padding-10mm">
    <section class="product_area">
        <div class="container-fluid" >
            
           <div id="demandContainer" class="table-container">
                
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        <table class="table center" cellspacing="0" cellpadding="0" border="0" style="width:100%;vertical-align:top;" valign="top"> 
                            <tbody><tr> 
                                @for($i=0;$i<count($products_list);$i++)
                                
                                    <td style="padding:0mm;font-size: 0.44rem;font-weight: bold;vertical-align:top;" valign="top">
                                        <table valign="top" style="vertical-align:top;">
                                            <tr>
                                                <td colspan="2" valign="top">
                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td valign="top">
                                                                <table cellpadding="0" cellspacing="0" width="100%">
                                                                    <tr><td style="padding-bottom:3px;padding-top:8px;padding-right:2px;">Style </td> <td style="padding-bottom:3px;padding-top:8px;font-size:0.47rem;">: {{($products_list[$i]->arnon_inventory == 0)?$products_list[$i]->vendor_sku:$products_list[$i]->product_sku}}</td></tr>
                                                                </table>    
                                                                <table cellpadding="0" cellspacing="0" width="100%">    
                                                                    <tr><td style="padding-bottom:3px;padding-right:1px;font-size: 0.41rem">Product </td> <td style="padding-bottom:3px;"> : {{$products_list[$i]->product_name}}</td></tr>
                                                                    <tr><td style="padding-bottom:3px;padding-right:1px;">Color </td> <td style="padding-bottom:3px;"> : {{$products_list[$i]->color_name}}</td></tr>
                                                                    <tr><td style="padding-bottom:3px;padding-right:1px;">Size </td> <td style="padding-bottom:3px;"> : {{$products_list[$i]->size_name}}</td></tr>
                                                                    <tr><td style="font-size:0.72rem;padding-bottom:1px;padding-right:1px;font-size: 0.58rem;">MRP </td> <td style="font-size:0.70rem;padding-bottom:1px;"> : <img src="{{url('images/rupee-indian.png')}}" style="height:7px;"> {{$products_list[$i]->sale_price}}/- </td></tr>
                                                                    <tr><td></td><td style="font-size: 0.34rem;"> Incl. of all GST</td></tr>
                                                                    @if(request('no_discount') == 1)
                                                                        <tr><td style="font-size: 0.38rem;padding-left: 1px;font-family:Helvetica;font-weight:800;" colspan="2"> NO DISCOUNT</td></tr>
                                                                    @endif
                                                                </table>    
                                                            </td>
                                                            <td valign="top" align="right">
                                                                <img src="data:image/png;base64,<?php echo DNS2D::getBarcodePNG(trim($products_list[$i]->peice_barcode), 'QRCODE',3.5,3.5) ?>" style="padding:3px;" />
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                
                                            </tr>
                                            
                                            <tr><td colspan="2" style="padding-left:0px;padding-top:1px;"><img src="data:image/png;base64,{{DNS1D::getBarcodePNG(trim(str_ireplace('u','',$products_list[$i]->product_barcode)), 'EAN13',1.9,58,array(0,0,0),true)}}" alt="barcode"  /></td></tr>
                                            
                                            <tr><td colspan="2" align="center" style="font-weight: normal;font-size: 0.41rem;text-align: center;font-family:Helvetica;">
                                                <table align="center" style="font-family:Helvetica">
                                                    <tr><td style="padding-top:0.05rem;padding-bottom:0.1rem;"><b>Month and Year of Manufacturing:</b>&nbsp; {{date('m-y')}} &nbsp;&nbsp;  <b>Qty:</b> 01 N</td></tr>
                                                    <tr><td style="padding-top:0.05rem;padding-bottom:0.1rem;"><b>MFD/ PKD/ MKT BY:</b>&nbsp; {{$company_data['company_name']}}</td></tr>
                                                    <tr><td style="padding-top:0.05rem;padding-bottom:0.1rem;"><b>Corporate Office:</b> Plot # 1/37, SSGT ROAD, LAL KUAN,  </td></tr>
                                                    <tr><td style="padding-top:0.05rem;padding-bottom:0.1rem;">Ghaziabad-201001 &nbsp; <b>Customer Care Contact: 9871556411</b></td></tr>
                                                    <tr><td style="padding-top:0.05rem;padding-bottom:0.05rem;"><b>Email:</b> <font style="font-size: 0.48rem;">customercare@kiaasaretail.com</font> &nbsp; </td></tr>
                                                </table>
                                            </td></tr>
                                        </table>    
                                    </td>
                                    @if(($i+1)%2 == 0) </tr><tr> @endif
                                @endfor        
                                                               
                            </tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
  </section>
</body>
</html>