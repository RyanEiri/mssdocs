<?php
require_once ('/var/www/htdocs/classes/classes.php');
require_once ('/var/www/htdocs/config/config.php');
require_once ('/var/www/htdocs/functions/php.php');
//require_once ('../functions/functions.php');
require ('/var/www/htdocs/fpdf153/fpdf.php');

if ($_GET[pdftype]==selection) {
	$print_request = explode(',', $_GET[print_request]);
	if (sizeof($print_request)>1) {
		foreach($print_request as $key => $value) {
			$printquery[$key]="SELECT * FROM customers WHERE customers_id='$value'";
			$printship[$key]="SELECT * FROM shipping WHERE contact_id='$value'";
		}	
		foreach($printquery as $key => $value) {
			$resultquery[$key]=$db->query($value) or die('select statement failed!');
			$resultprintship[$key]=$db->query($value) or die('select statement failed!');
		}
		$only_one=0;
	} else {
		$printquery="SELECT * FROM customers WHERE customers_id='$_GET[print_request]'";
		$printship="SELECT * FROM shipping WHERE contact_id='$_GET[print_request]'";
		$resultquery=$db->query($printquery) or die('select statement failed!');
		$resultprintship=$db->query($printship) or die('select statement failed!');
		$only_one=1;
	}

	$return_address = "1848  Portage Ave.\nWinnipeg, Manitoba, R3J 0G9\nPH:(204) 831-9773";

	$size=array('8.5', '11');
	$pdf=new FPDF('Landscape', 'in', $size);
	$pdf->SetFont('Arial','B',10);

	if ($only_one==0) {
		foreach($resultquery as $key => $value) {
			$env[$key]=$value->fetchArray();
		}
		foreach($resultprintship as $key => $value) {
			$ship[$key]=$value->fetchArray();
		}
	} else {
		$env=$resultquery->fetchArray();
		$ship=$resultprintship->fetchArray();
	}
	if ($only_one==1) {
		foreach($env as $value) {
			$phones = split_phone($value);
			$phone_number = "PH: $phones[b_phone1]-$phones[b_phone2]-$phones[b_phone3]";
//			if(preg_match('/Town of.*$/', $value[customers_organization])){
//				list($town, $sputz) = split(",", $value[customers_organization]);
//				$sputz = trim($sputz);
//			}
			if ($value[customers_address_2]==NULL) {
				if ($value[customers_organization]!=NULL) {
					if($town){
						$send_address = "ATT:$value[customers_name]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
					} else {
						if ($value[customers_department]!=NULL){
							$send_address = "ATT:$value[customers_name]\n$value[customers_organization]\nDept: $value[customers_department]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
						} else {
							$send_address = "ATT:$value[customers_name]\n$value[customers_organization]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
						}
					}
				} else {
					$send_address = "ATT:$value[customers_name]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
				}
			} elseif ($value[customers_address_2]!=NULL) {
				if ($value[customers_organization]!=NULL) {
					if($town){
						$send_address = "ATT:$value[customers_name]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
					} else {
						if ($value[customers_department]!=NULL) {
							$send_address = "ATT:$value[customers_name]\n$value[customers_organization]\nDept: $value[customers_department]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";	
						} else {
							$send_address = "ATT:$value[customers_name]\n$value[customers_organization]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
						}
					}
				} else {
					$send_address = "ATT:$value[customers_name]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]\n";
				}
			}
			$pdf->AddPage();
			$pdf->Image('../../fpdf153/smaller_logo-gray.png', 0.25, 0.25, 'PNG');
			$pdf->SetXY(0.25, 0.65);
			$pdf->MultiCell(0,0.25,$return_address,0,'L');
			$pdf->SetXY(3,2);
			$pdf->SetFont('Arial','B',32);
			$pdf->MultiCell(0,0.5,$send_address,0,'L');
			$pdf->SetLineWidth(0.05);
			$pdf->Line(0.5,5.5,10.5,5.5);
			$pdf->SetXY(3,5.75);
			$pdf->MultiCell(0,0.5,$phone_number,0,'L');
			$pdf->Line(0.5,6.4,10.5,6.4);
			$pdf->SetFont('Arial', 'B',14);
			$pdf->SetXY(1,6.7);
			$pdf->Cell(0,0,"Shipping Notes:",0,'L');
			$pdf->SetFont('Arial', '',14);
			$pdf->SetXY(1.1,6.8);
			$pdf->MultiCell(0,0.25,$ship[0][notes],0,'L');
		}
	} else {
		foreach($env as $value) {
//			print_r($value);
			$enter[customers_name]=$value[0][customers_name];
			$enter[customers_organization]=$value[0][customers_organization];
			$enter[customers_department]=$value[0][customers_department];
			$enter[customers_address_1]=$value[0][customers_address_1];
			$enter[customers_address_2]=$value[0][customers_address_2];
			$enter[customers_city]=$value[0][customers_city];
			$enter[customers_province]=$value[0][customers_province];
			$enter[customers_postal_code]=$value[0][customers_postal_code];
			$enter[business_phone]=$value[0][business_phone];
			$enter[fax_phone]=$value[0][fax_phone];
			$enter[home_phone]=$value[0][home_phone];
			$enter[cell_phone]=$value[0][cell_phone];
			$enter[toll_free_phone]=$value[0][toll_free_phone];
			$phones = split_phone($enter);
			$phone_number = "PH: $phones[b_phone1]-$phones[b_phone2]-$phones[b_phone3]";
			if ($enter[customers_address_2]==NULL) {
				if ($enter[customers_organization]!=NULL) {
					if ($enter[customers_department]!=NULL) {
						$send_address = "ATT:$enter[customers_name]\n$enter[customers_position]\n$enter[customers_organization]\nDept: $enter[customers_department]\n$enter[customers_city], $enter[customers_province]\n$enter[customers_postal_code]\n";
					} else {
						$send_address = "ATT:$enter[customers_name]\n$enter[customers_position]\n$enter[customers_organization]\n$enter[customers_city], $enter[customers_province]\n$enter[customers_postal_code]\n";
					}
				} else {
					$send_address = "ATT:$enter[customers_name]\n$enter[customers_position]\n$enter[customers_city], $enter[customers_province]\n$enter[customers_postal_code]\n";
				}
			} elseif ($enter[customers_address_2]!=NULL) {
				if ($enter[customers_organization]!=NULL) {
					if ($enter[customers_department]!=NULL) {
						$send_address = "ATT:$enter[customers_name]\n$enter[customers_position]\n$enter[customers_organization]\nDept: $enter[customers_department]\n$enter[customers_address_2]\n$enter[customers_city], $enter[customers_province]\n$enter[customers_postal_code]\n";	
					} else {
						$send_address = "ATT:$enter[customers_name]\n$enter[customers_position]\n$enter[customers_organization]\n$enter[customers_address_2]\n$enter[customers_city], $enter[customers_province]\n$enter[customers_postal_code]\n";
					}
				} else {
					$send_address = "ATT:$enter[customers_name]\n$enter[customers_position]\n$enter[customers_address_2]\n$enter[customers_city], $enter[customers_province]\n$enter[customers_postal_code]\n";
				}
			}
			$pdf->AddPage();
			$pdf->SetFont('Arial','B',10);
			$pdf->Image('../../fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
			$pdf->SetXY(0.25, 0.65);
			$pdf->MultiCell(0,0.25,$return_address,0,'L');
			$pdf->SetXY(3,2);
			$pdf->SetFont('Arial','B',32);
			$pdf->MultiCell(0,0.5,$send_address,0,'L');
			$pdf->SetLineWidth(0.05);
			$pdf->Line(0.5,5.5,10.5,5.5);
			$pdf->SetXY(3,5.75);
			$pdf->MultiCell(0,0.5,$phone_number,0,'L');
		}
	}
	$pdf->Output('shipping_labels-selection.pdf','I');
//	print_r($env);
}
?>
