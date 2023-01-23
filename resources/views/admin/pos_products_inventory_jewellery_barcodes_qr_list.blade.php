<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>receipt</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.css">

<style>
@page { size: 76mm 20mm;margin: 1mm;font-family:Arial, Helvetica, sans-serif;  }
body.receipt .sheet { width: 76mm; height: 20mm;margin: 0mm;font-family:Arial, Helvetica, sans-serif; } /* change height as you like */
@media print { body.receipt { width: 76mm;height: 20mm;margin: 0mm;font-family:Arial, Helvetica, sans-serif; } } /* this line is needed for fixing Chrome's bug */
</style>
</head>

<body class="receipt" style="margin:0px;margin-left:0px;text-align:left;float:left;" align="left">
  <section class="sheet padding-10mm" style="margin-left:0px;text-align:left;float:left;" align="left">
    
        <div class="table-responsive table-filter" style="text-align:left;margin-left:0px;float:left;">
            <table class="table" cellspacing="0" cellpadding="0" border="0" style="width:100%;vertical-align:top;text-align:left;margin-left:0px;" valign="top" align="left"> 
                <tbody><tr>
                    @for($i=0;$i<count($products_list);$i++)
                    <?php if(($i+1)%2 == 0) $css = 'padding-left:1.6rem;';else $css = '';  ?>
                        <td style="padding:0mm;font-size:0.35rem;font-weight:bold;vertical-align:top;text-align:left;{{$css}};padding-top:4px;" valign="top" align="left">
                            <table valign="top" style="vertical-align:top;text-align:left;" align="left">
                                <tr>
                                    <td colspan="2" valign="top" align="left" style="vertical-align:top;text-align:left;">
                                        <table cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td valign="top">
                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                        <tr><td style="padding-bottom:2px;padding-top:6px;padding-right:2px;">Style </td> <td style="padding-bottom:2px;padding-top:6px;font-size:0.36rem;">: {{($products_list[$i]->arnon_inventory == 0)?$products_list[$i]->vendor_sku:$products_list[$i]->product_sku}}</td></tr>
                                                    </table>    
                                                    <table cellpadding="0" cellspacing="0" width="100%">    
                                                        <tr><td style="padding-bottom:2px;padding-right:1px;font-size: 0.35rem">Product </td> <td style="padding-bottom:2px;"> : {{$products_list[$i]->product_name}}</td></tr>
                                                        <tr><td style="padding-bottom:2px;padding-right:1px;">Color </td> <td style="padding-bottom:2px;"> : {{$products_list[$i]->color_name}}</td></tr>
                                                        <tr><td style="padding-bottom:2px;padding-right:1px;">Size </td> <td style="padding-bottom:2px;"> : {{$products_list[$i]->size_name}}</td></tr>
                                                        <tr><td style="padding-bottom:1px;padding-right:1px;font-size: 0.37rem;">MRP </td> <td style="font-size:0.37rem;padding-bottom:1px;"> : {{$products_list[$i]->sale_price}} </td></tr>
                                                        <?php /* ?><tr><td ></td><td style="font-size: 0.33rem;"> Incl. of all GST</td></tr> <?php */ ?>
                                                        <tr><td style="font-size: 0.38rem;" colspan="2" align="left"> {{$products_list[$i]->product_barcode}}</td></tr>
                                                        <tr><td style="font-size: 0.35rem;" colspan="2" align="left"> KIAASA</td></tr>
                                                        
                                                        @if(request('no_discount') == 1)
                                                            <tr><td style="font-size: 0.38rem;padding-left: 1px;font-family:Helvetica;font-weight:800;" colspan="2"> NO DISCOUNT</td></tr>
                                                        @endif
                                                    </table>    
                                                </td>
                                                <td valign="top" align="right">
                                                    <img src="data:image/png;base64,<?php echo DNS2D::getBarcodePNG(trim($products_list[$i]->peice_barcode), 'QRCODE',3.0,3.0) ?>" style="padding:1px;" />
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                </tr>

                                <?php /* ?><tr><td colspan="2" style="padding-left:0px;padding-top:1px;"><img src="data:image/png;base64,{{DNS1D::getBarcodePNG(trim(str_ireplace('u','',$products_list[$i]->product_barcode)), 'EAN13',1,20,array(0,0,0),true)}}" alt="barcode"  /></td></tr>
                                <tr><td style="font-size: 0.38rem;">KIAASA</td><td style="padding-left:0px;padding-top:1px;font-size: 0.38rem;text-align:right;" align="right">{{$products_list[$i]->product_barcode}}</td></tr><?php */ ?>

                                <?php /* ?><tr><td colspan="2" align="center" style="font-weight: normal;font-size: 0.15rem;text-align: center;font-family:Helvetica;">
                                    <table align="center" style="font-family:Helvetica">
                                        <tr><td style="padding-top:0.05rem;padding-bottom:0.06rem;">MFD/PKD Month/year:&nbsp; {{date('m-y')}} &nbsp;&nbsp;  Qty: 01 N</td></tr>
                                        <tr><td style="padding-top:0.05rem;padding-bottom:0.06rem;">Corporate Office. KIAASA RETAIL LLP  </td></tr>
                                        <tr><td style="padding-top:0.05rem;padding-bottom:0.06rem;">Plot # 1/37, SSGT ROAD, LAL KUAN, GZB-201002</td></tr>
                                        <tr><td style="padding-top:0.05rem;padding-bottom:0.04rem;">Email: info@kiaasaretail.com</td></tr>
                                    </table>
                                </td> </tr><?php */ ?>
                            </table>    
                        </td>
                        @if(($i+1)%2 == 0) </tr><tr> @endif
                    @endfor        

                </tr></tbody>
            </table>
        </div>
                
    </section>
</body>
</html>