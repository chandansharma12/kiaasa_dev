<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>receipt</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.css">

  <style>@page { size: 100mm 50mm;margin: 1mm; }
  body.receipt .sheet { width: 100mm; height: 50mm;margin: 1mm; } /* change height as you like */
@media print { body.receipt { width: 100mm;height: 50mm;margin: 1mm; } } /* this line is needed for fixing Chrome's bug */
  
  /*.center {
  margin-left: auto;
  margin-right: auto;
}*/

  </style>
</head>

<body class="receipt" style="margin:0px;">
  <section class="sheet padding-10mm">
    <section class="product_area">
        <div class="container-fluid" >
            
           <div id="demandContainer" class="table-container">
                
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        <table class="table center" cellspacing="0" border="0" style="width:100%;vertical-align:top;" valign="top"> 
                            <tbody><tr>
                                @for($i=0;$i<count($products_list);$i++)
                                
                                    <td style="padding:1mm;font-size: 0.64rem;font-weight: bold;vertical-align:top;" valign="top">
                                        <table valign="top" style="vertical-align:top;">
                                            <tr><td>Style</td> <td >:&nbsp;&nbsp;{{$products_list[$i]->product_sku}}</td></tr>
                                            <tr><td>Product</td> <td>:&nbsp;&nbsp; {{$products_list[$i]->product_name}}</td></tr>
                                            <tr><td>Color</td> <td>:&nbsp;&nbsp; {{$products_list[$i]->color_name}}</td></tr>
                                            <tr><td>Size</td> <td>:&nbsp;&nbsp; {{$products_list[$i]->size_name}}</td></tr>
                                            <tr><td>MRP</td> <td>:&nbsp;&nbsp; {{$products_list[$i]->sale_price}} &nbsp;&nbsp; Incl. of all GST</td></tr>
                                            <tr><td colspan="2" style="padding-left:0px;padding-top:2px;"><img src="data:image/png;base64,{{DNS1D::getBarcodePNG(trim($products_list[$i]->peice_barcode), 'EAN13',1.8,60,array(0,0,0),true)}}" alt="barcode"  /></td></tr>
                                            <tr><td colspan="2" style="font-size: 0.55rem;">MFD/PKD Month/year:&nbsp; {{date('m-y')}} &nbsp;&nbsp;<span style="font-weight: normal;font-size: 0.55rem"> Qty: 01 N</span></td> </tr>
                                            <tr><td colspan="2" style="height:3px;"></td> </tr>
                                            <tr><td colspan="2" align="center" style="font-weight: normal;font-size: 0.58rem;"><table>
                                                        <tr><td>Corporate Office. {{$company_data['company_name']}} </td></tr>
                                                        <tr><td>C9 SEC-63 INDIA (U.P) 201301</td></tr>
                                                        <tr><td>Email: info@kiaasa.com </td></tr>
                                                        <tr><td>C. Care No: 0120-4720800 </td></tr>
                                                </table></td> </tr>
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