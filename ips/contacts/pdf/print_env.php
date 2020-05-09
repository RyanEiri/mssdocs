<?php
require_once ('/var/www/htdocs/classes/classes.php');
require_once ('/var/www/htdocs/config/config.php');
//require_once ('../functions/functions.php');
require ('/var/www/htdocs/fpdf153/fpdf.php');

if ($_GET[pdftype]==selection) {
	$print_request = explode(',', $_GET[print_request]);
	if (sizeof($print_request)>1) {
		foreach($print_request as $key => $value) {
			$printquery[$key]="SELECT * FROM customers WHERE customers_id='$value'";	
		}	
		foreach($printquery as $key => $value) {
			$resultquery[$key]=$db->query($value) or die('select statement failed!');
		}
		$only_one=0;
	} else {
		$printquery="SELECT * FROM customers WHERE customers_id='$_GET[print_request]'";
		$resultquery=$db->query($printquery) or die('select statement failed!');
		$only_one=1;
	}
} else {
	$querypop1="SELECT * FROM customers WHERE calendar_a='YES' AND calendar_n='NO'";
	$querypop2="SELECT * FROM customers WHERE calendar_a='NO' AND calendar_n='YES'";
	$querypop3="SELECT * FROM customers WHERE calendar_a='YES' AND calendar_N='YES'";
	$querypop4="SELECT * FROM customers WHERE catalog='YES'";

	$resultpop1=$db->query($querypop1) or die('select statement failed!');
	$resultpop2=$db->query($querypop2) or die('select statement failed!');
	$resultpop3=$db->query($querypop3) or die('select statement failed!');
	$resultpop4=$db->query($querypop4) or die('select statement failed!');

	$num_a=$resultpop1->size();
	$num_n=$resultpop2->size();
	$num_both=$resultpop3->size();
	$num_cat=$resultpop4->size();

}

$return_address = "1848  Portage Ave.\nWinnipeg, Manitoba\nR3J 0G9";

if ($_GET[pdfsize]=='9x12') {
	$size=array('9', '12');
	$pdf=new FPDF('Landscape', 'in', $size);
	$pdf->SetFont('Arial','B',14);
} else {
	$size=array('10', '13');
	$pdf=new FPDF('Landscape', 'in', $size);
	$pdf->SetFont('Arial','B',14);
}

if ($_GET[pdftype]==animals) {
	$animal_calendars = $resultpop1->fetchArray();
	foreach ($animal_calendars as $value) {
		if ($value[customers_address_2]==NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		} elseif ($value[customers_address_2]!=NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		}
		$pdf->AddPage();
		$pdf->Image('/var/www/htdocs/fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
		$pdf->SetXY(0.25, 0.65);
		$pdf->MultiCell(0,0.25,$return_address,0,'L');
		$pdf->SetXY(5.5,4.5);
		$pdf->SetFont('Arial','B',18);
		$pdf->MultiCell(0,0.25,$send_address,0,'L');
	}	
	$pdf->Output('envelope_labels-animal_calendars.pdf','D');
}

if ($_GET[pdftype]==nudies) {
	$nudie_calendars = $resultpop2->fetchArray();
	foreach ($nudie_calendars as $value) {
		if ($value[customers_address_2]==NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		} elseif ($value[customers_address_2]!=NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		}
		$pdf->AddPage();
		$pdf->Image('/var/www/htdocs/fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
		$pdf->SetXY(0.25, 0.65);
		$pdf->MultiCell(0,0.25,$return_address,0,'L');
		$pdf->SetXY(3, 3);
		$pdf->SetFont('Arial','B',24);
		$pdf->SetTextColor(255,0,0);
		$pdf->Cell(0,0,"CONFIDENTIAL");
		$pdf->SetFont('Arial','',22);
		$pdf->SetXY(3, 3.35);
		$pdf->Cell(0,0,"If you are not the recipient, do not open.");
		$pdf->SetXY(5.5,4.5);
		$pdf->SetFont('Arial','B',18);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0,0.25,$send_address,0,'L');
	}
	$pdf->Output('envelope_labels-nudie_calendars.pdf','D');
}

if ($_GET[pdftype]==both) {
	$both_calendars = $resultpop3->fetchArray();
	foreach ($both_calendars as $value) {
		if ($value[customers_address_2]==NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		} elseif ($value[customers_address_2]!=NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		}
		$pdf->AddPage();
		$pdf->Image('/var/www/htdocs/fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
		$pdf->SetXY(0.25, 0.65);
		$pdf->MultiCell(0,0.25,$return_address,0,'L');
		$pdf->SetXY(3, 3);
		$pdf->SetFont('Arial','B',24);
		$pdf->SetTextColor(255,0,0);
		$pdf->Cell(0,0,"CONFIDENTIAL");
		$pdf->SetFont('Arial','',22);
		$pdf->SetXY(3, 3.35);
		$pdf->Cell(0,0,"If you are not the recipient, do not open.");
		$pdf->SetXY(5.5,4.5);
		$pdf->SetFont('Arial','B',18);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0,0.25,$send_address,0,'L');
	}
	$pdf->Output('envelope_labels-both_calendar_types.pdf','D');
}

if ($_GET[pdftype]==catalogs) {
	$catalog_env = $resultpop4->fetchArray();
	foreach ($catalog_env as $value) {
		if ($value[customers_address_2]==NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		} elseif ($value[customers_address_2]!=NULL) {
			if ($value[customers_organization]!=NULL) {
				$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			} else {
				$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
			}
		}
		$pdf->AddPage();
		$pdf->Image('/var/www/htdocs/fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
		$pdf->SetXY(0.25, 0.65);
		$pdf->MultiCell(0,0.25,$return_address,0,'L');
		$pdf->SetXY(5,4);
		$pdf->SetFont('Arial','B',18);
		$pdf->MultiCell(0,0.25,$send_address,0,'L');
	}	
	$pdf->Output('envelope_labels-catalogs.pdf','D');
}

if ($_GET[pdftype]==selection) {
	if ($only_one==0) {
		foreach($resultquery as $key => $value) {
			$env[$key]=$value->fetchArray();
		}
	} else {
		$env=$resultquery->fetchArray();
	}
	if ($only_one==1) {
		foreach($env as $value) {
			if ($value[customers_address_2]==NULL) {
				if ($value[customers_organization]!=NULL) {
					$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				} else {
					$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				}
			} elseif ($value[customers_address_2]!=NULL) {
				if ($value[customers_organization]!=NULL) {
					$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				} else {
					$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				}
			}
			$pdf->AddPage();
			$pdf->Image('/var/www/htdocs/fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
			$pdf->SetXY(0.25, 0.65);
			$pdf->MultiCell(0,0.25,$return_address,0,'L');
			$pdf->SetXY(5,4);
			$pdf->SetFont('Arial','B',18);
			$pdf->MultiCell(0,0.25,$send_address,0,'L');
		}
	} else {
		foreach($env as $value) {
//			print_r($value);
			$value[customers_name]=$value[0][customers_name];
			$value[customers_organization]=$value[0][customers_organization];
			$value[customers_address_1]=$value[0][customers_address_1];
			$value[customers_address_2]=$value[0][customers_address_2];
			$value[customers_city]=$value[0][customers_city];
			$value[customers_province]=$value[0][customers_province];
			$value[customers_postal_code]=$value[0][customers_postal_code];
			if ($value[customers_address_2]==NULL) {
				if ($value[customers_organization]!=NULL) {
					$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				} else {
					$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				}
			} elseif ($value[customers_address_2]!=NULL) {
				if ($value[customers_organization]!=NULL) {
					$send_address = "$value[customers_name]\n$value[customers_organization]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				} else {
					$send_address = "$value[customers_name]\n$value[customers_address_1]\n$value[customers_address_2]\n$value[customers_city], $value[customers_province]\n$value[customers_postal_code]";
				}
			}
			$pdf->AddPage();
			$pdf->Image('/var/www/htdocs/fpdf153/smaller_logo-gray.png', 0.25, 0.25, 3, 0.35, 'PNG');
			$pdf->SetXY(0.25, 0.65);
			$pdf->MultiCell(0,0.25,$return_address,0,'L');
			if ($_GET[pdfsize]=='9x12') {
				$pdf->SetXY(5,4);
			} elseif ($_GET[pdfsize]=='10x13') {
				$pdf->SetXY(5.5,4.5);
			}
			$pdf->SetFont('Arial','B',18);
			$pdf->MultiCell(0,0.25,$send_address,0,'L');
		}
	}
	$pdf->Output('envelope_labels-selection.pdf','I');
//	print_r($env);
}
?>
