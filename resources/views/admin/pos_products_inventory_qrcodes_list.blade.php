<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>receipt</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.css">

  <style>@page { size: 100mm 57mm }
  body.receipt .sheet { width: 100mm; height: 57mm } /* change height as you like */
@media print { body.receipt { width: 100mm } } /* this line is needed for fixing Chrome's bug */
  
  .center {
  margin-left: auto;
  margin-right: auto;
}

  
  </style>
</head>

<body class="receipt">
  <section class="sheet padding-10mm">
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        <table class="table center" cellspacing="0" border="0" >
                            <tbody>
                                <?php  /* ?>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <?php  ?><td><img src="data:image/png;base64,{{DNS1D::getBarcodePNG($products_list[$i]->peice_barcode, 'C39',1,65,array(0,0,0),true)}}" alt="barcode" /><br></td> <?php  ?>
                                        <td><img src="data:image/png;base64,<?php echo DNS2D::getBarcodePNG($products_list[$i]->peice_barcode, 'QRCODE',2.5,2.5) ?>" style="margin: 12px" /><br></td>
                                    </tr>
                                    <?php ?>
                                @endfor
								<?php   */
								$rows= ceil(count($products_list)/4);		$cols =4;	
									echo "<table class='center' border='0'>";								
									for($i=1;$i<=$rows;$i++)
									{
										echo "<tr>";
										for($j=1;$j<=$cols;$j++)
										{ $index= 4*($i-1)+$j; 
											if($index<=count($products_list)){
												$index = $index-1;
												
												?>
												 <td><img src="data:image/png;base64,<?php echo DNS2D::getBarcodePNG(trim($products_list[$index]->peice_barcode), 'QRCODE',3.5,3.5) ?>" style="margin: 10px" /><br></td>
												<?php
												if($j ==2){ ?>
													<td>&nbsp;&nbsp;</td>
													<?php
												}
											}
										}
										echo "</tr>";
										if($i % 2 == 0){ 
											//echo "<tr style='height:10px'><td colspan='5' ></td></tr>";
										}
										}
										echo "</table>";
										?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
  </section>
</body>
</html>