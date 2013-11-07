<?php
//***********************************Set defaults***************************************
//Debug
$debug = FALSE;
//Campaign setup
$campaignDefaultPhone = '918004509176'; //catch-all
$campaignHighLTVPhone = '918003379308';
$campaignLowLTVPhone = '918003870854';
$campaignTranche1Phone = '918005791987';
$campaignTranche2Phone = '918008530214';
$campaignTranche3Phone = '918008288966';
$campaignTranche4Phone = '918004990370';
$campaignPost = 'https://leads.versachannel.com/genericPostlead.php';
//Vicidial API setup
$source='test';
$user='8888';
$pass='APIadminMedia365';
$user_agent=$_GET['user'];
$leadUpdate = 'http://mediamix.ytel.com/non_agent_api.php';
$voicePost = 'http://mediamix.ytel.com/agc/api.php';

//************************Check to see if required fields are filled out***************************
if (isset($_GET['first_name'])){}else{exit ("Missing First Name");}
if (isset($_GET['last_name'])){}else{exit ("Missing Last Name");}
if (isset($_GET['phone_number'])){}else{exit ("Missing Phone Number");}
if (isset($_GET['address1'])){}else{exit ("Missing Address");}
if (isset($_GET['city'])){}else{exit ("Missing City");}
if (isset($_GET['state'])){}else{exit ("Missing State");}
if (isset($_GET['postal_code'])){}else{exit ("Missing Zip code");}
if (isset($_GET['fha'])){}else{exit ("Missing FHA");}
if (isset($_GET['va'])){}else{exit ("Missing VA");}
if (isset($_GET['fannie'])){}else{exit ("Missing Fannie");}
if (isset($_GET['freddie'])){}else{exit ("Missing Freddie");}
if (isset($_GET['interest'])){}else{exit ("Missing Interest Rate");}
if (isset($_GET['credit_score'])){}else{exit ("Missing Credit Score");}
if (isset($_GET['firstmortgage_balance'])){}else{exit ("Missing First Mortgage Balance");}
if (isset($_GET['home_value'])){}else{exit ("Missing Home Value");}
   
//**********************************Initial data processing****************************************
//LTV pre-processing
$fmb = $_GET['firstmortgage_balance'];
$smb = $_GET['secondmortgage_balance'];
$hv = $_GET['home_value'];
//Calculate LTV if pre-requisites are there, or at least set a value.
if (isset($fmb) && ($fmb > 0)) {
    if (isset($hv) && ($hv > 0)) {
        if (isset ($smb) && ($smb > 0)) {
            $loantovalue = ((($fmb + $smb) / $hv) * 100);
        } else { 
      	    $loantovalue = (($fmb / $hv) * 100);
        }
    } else {
   	    $loantovalue = 0;
    }
} else { 
    $loantovalue = 0;
}

if ($debug){ echo "LTV: $loantovalue"; }
//******************************************Data filtering*****************************************
//HARP filters on LTV, not interest
//Determine if it's a HARP campaign.
$fannie=$_GET['fannie'];
$freddie=$_GET['freddie'];
if ($fannie == "Yes" || $freddie == "Yes") {
   //Determine which campaign phone number to send the call to based on LTV ratio.
   if ($loantovalue >= 161) { exit ("Rejected due to too high a LTV Ratio.");} //150% LTV + 10%. Reject.
   elseif ($loantovalue >= 136) {
      $campaignNumber = $campaignHighLTVPhone; } //HARP2 125% LTV + 10%. Sound only
   elseif ($loantovalue >= 1) {
      $campaignNumber = $campaignLowLTVPhone; } //HARP1 1-125% LTV. Sound & PMB
   elseif ($loantovalue == 0) { 
      $campaignNumber = $campaignDefaultPhone; } //Default all-else-failed destination. Sound & PMB
}   
if ($debug){ echo "\nCampaign number to dial: $campaignNumber (at Data Filtering)"; }
//If it's not HARP, we tranche.

//****************************************TIERING**************************************************
if (isset($_GET['interest_rate'])) {
	$interest=$_GET['interest_rate'];
	//Set a variety of tiers, allowing granular tranching later.
	if ($interest >= 7.00) {
		$interest_tier=0;
		}elseif ($interest >= 6.76) {
			$interest_tier=1;
		}elseif ($interest >= 6.51) {
			$interest_tier=2;
		}elseif ($interest >= 6.26) {
			$interest_tier=3;
		}elseif ($interest >= 6.01) {
			$interest_tier = 4;
		}elseif ($interest >= 5.76) {
			$interest_tier = 5;
		}elseif ($interest >= 5.51) {
			$interest_tier = 6;
		}elseif ($interest >= 5.26) {
			$interest_tier = 7;
		}elseif ($interest >= 5.01) {
			$interest_tier = 8;
		}elseif ($interest == 5.00) {
			$interest_tier = 9;
		}elseif ($interest >= 4.76) {
			$interest_tier = 10;
		}elseif ($interest >= 4.51) {
			$interest_tier = 11;
		}elseif ($interest >= 4.26) {
			$interest_tier = 12;
		}elseif ($interest >= 4.01) {
			$interest_tier = 13;
		}elseif ($interest <= 4.00) {
			$interest_tier = 14;
		}
} else {
	$interest = 5.00;
	$interest_tier = 9;
}
//****************************************TRANCHING**************************************************
//Interest
if ($interest_tier == 0) {
	$interest_tranche = 0;
}elseif (($interest_tier >= 1) && ($interest_tier <= 4)) {
	$interest_tranche=1;
}elseif (($interest_tier >= 5) && ($interest_tier <= 6)) {
	$interest_tranche=2;
}elseif (($interest_tier >= 7) && ($interest_tier <= 8)) {
	$interest_tranche=3;
}elseif ($interest_tier  == 9) {
	$interest_tranche=99;
}
//Mortgage
if (isset ($_GET['firstmortgage_balance'])) {
	$mortgage = $_GET['firstmortgage_balance'];
	if ($mortgage >= 625500) {
		$mortgage_tier = 0;
	}elseif ($mortgage >= 417000) {
		$mortgage_tranche=1;
	}elseif ($mortgage >= 250000) {
		$mortgage_tranche=2;
	}elseif ($mortgage >= 175000) {
		$mortgage_tranche=3;
	}elseif ($mortgage >= 119999) {
		$mortgage_tranche=4;
	}elseif ($mortgage <= 119998) {
		$mortgage_tranche = 99;
	}
} else {
	$mortgage = 125001;
	$mortgage_tranche = 4;
}
//Rejection Handling
// We don't deliver leads below 5% interest rate
if ($interest_tier >= 10) {exit ("Rejected due to too low interest rate.");}
// Wee don't deliver leads below 125,000, unless they're ARM.
if (isset ($_GET['arm'])) {
   $arm = $_GET['arm'];
   if ($arm == 'Yes' && $mortgage_tranche == 99){
   }elseif ($arm == 'No' && $mortgage_tranche == 99 ){exit ("Rejected due to too low mortgage value.");}
}

//	if ($arm == 'Yes' && $mortgage_tranche == 99){
//	}elseif ($arm = 'No' && $mortgage_tranche == 99 ){exit ("Rejected due to too low mortgage value.");
if ($mortgage_tranche = 99) {$rng = (rand (1,4));}
if ($rng == 1) {
	$mortgage_tranche = 0;
	}elseif ($rng == 2) {
		$mortgage_tranche = 1;
	}elseif ($rng == 3) {
		$mortgage_tranche = 2;
	}elseif ($rng == 4) {
		$mortgage_tranche = 3;
		}

//Random off interest_tranche 99 into the other tranches to prevent cherry picking.
if ($interest_tranche = 99) {$rng =(rand (1,4));}
if ($rng == 1) {
		$interest_tranche = 0;
	}elseif ($rng == 2) {
		$interest_tranche = 1;
	}elseif ($rng == 3) {
		$interest_tranche = 2;
	}elseif ($rng == 4) {
		$interest_tranche = 3;
		}


//****************************************ASSIGNMENT**************************************************
//Check to see if campaignNumber is already set by LTV function
if (!isset($campaignNumber)) {
   //Call different campaigns based on better of mortgage_tranche and interest_tranche
   if ($mortgage_tranche == 0 || $interest_tranche == 0) { 
	   $campaignNumber = $campaignTranche1Phone; // Phone 1
   } elseif ($mortgage_tranche == 1 || $interest_tranche == 1) { 
	   $campaignNumber = $campaignTranche2Phone; // Phone 2
   } elseif ($mortgage_tranche == 2 || $interest_tranche == 2) { 
	   $campaignNumber = $campaignTranche3Phone; // Phone 3
   } elseif ($mortgage_tranche >= 3 || $interest_tranche == 3) { 
	   $campaignNumber = $campaignTranche4Phone; // Phone 4
   }
   if ($debug){ echo "Campaign number to dial: $campaignNumber (at Assignment)"; }
}

//************************************************ARRAY CREATION***********************************
//We want to update our records first, post data second, and dial with customer third
//Build DATA UPDATE array
$updateArray=array(
   'source'                => $source,
   'user'                  => $user,
   'pass'                  => $pass,
   'function'              => 'update_lead',
   'lead_id'               => $_GET['lead_id'],
   'last_name'             => $_GET['last_name'],
   'city'                  => $_GET['city'],
   'custom_fields'         => 'Y',
   'loantovalue'           => $loantovalue
);
//if ($debug){ print_r($updateArray);}
//Build DATA DELIVERY array
//Arrangement and variables set by Boberdoo
$dataArray = array(
   TYPE                    => '23',
   Test_Lead               => $datadebug, // 1 (Only when testing)
//Skip_XSL                 => '0', // Do not include XSL path in XML response
//Match_With_Partner_ID    => '22,456', //Comma separated list with Partner IDs to only match with
   SRC                     => 'LCCCallCenter',
   Landing_Page            => 'index.php',
   Redirect_URL            => '', //Set your redirect URL
   IP_Address              => '4.53.176.114',
   Lead_ID                 => $_GET['lead_id'],
   List_ID                 => $_GET['list_id'],
   Campaign                => $_GET['campaign'],
   First_Name              => $_GET['first_name'],
   Middle_Name             => $_GET['middle_initial'],
   Last_Name               => $_GET['last_name'],
   Primary_Phone           => $_GET['phone_number'],
   Address_1               => $_GET['address1'],
   Address_2               => $_GET['address2'],
   Address_3               => $_GET['address3'],
   City                    => $_GET['city'],
   State                   => $_GET['state'],
   Postal_Code             => $_GET['postal_code'],
   Alt_Phone               => $_GET['alt_phone'],
   Email                   => $_GET['email'],
   FHA                     => $_GET['fha'],
   VA                      => $_GET['va'],
   Fannie                  => $fannie,
   Freddie                 => $freddie,
   ARM                     => $arm,
   Fixed_Period            => $_GET['fixed_period'],
   Interest_Rate           => $interest,
   Credit_Score            => $_GET['credit_score'],
   Income_Source           => $_GET['income_source'],
   Late_Pay                => $_GET['late_pay'],
   First_Mortgage_Balance  => $_GET['firstmortgage_balance'],
   Second_Mortgage_Balance => $_GET['secondmortgage_balance'],
   Manufactured            => $_GET['manufactured'],
   Loan_Mod                => $_GET['loanmod'],
   Home_Value              => $_GET['home_value'],
   Owner_Occupied          => $_GET['owner_occupied'],
   Spanish                 => $_GET['spanish'],
   Bankruptcy              => $_GET['bankruptcy'],
   Foreclosure             => $_GET['foreclosure'],
   LTV                     => $loantovalue,
   Unsecured_Debt          => $_GET['unsecured_debt'],
);
if ($debug){ print_r($dataArray);}
//Build VOICE DELIVERY array
$voiceArray = array (
   'source'                => $source,
   'user'                  => $user,
   'pass'                  => $pass,
   'agent_user'            => $user_agent,
   'function'              => 'transfer_conference',
   'value'                 => 'DIAL_WITH_CUSTOMER',
   'phone_number'          => $campaignNumber,
   'dial_override'         => 'YES',
);
//if ($debug){ print_r($voiceArray);}
//url-ify the data array
//DATA UPDATE
foreach($updateArray as $key=>$value) { $updateArray_string .= $key.'='.$value.'&'; }
rtrim($updateArray_string, '&');
//DATA DELIVERY
foreach($dataArray as $key=>$value) { $dataArray_string .= $key.'='.$value.'&'; }
rtrim($dataArray_string, '&');
//VOICE DELIVERY
foreach($voiceArray as $key=>$value) { $voiceArray_string .= $key.'='.$value.'&'; }
rtrim($voiceArray_string, '&');

//******************************************DELIVERY TIME******************************************
if (!$debug) {
//open connection
$ch = curl_init();
//POST for DATA UPDATE
curl_setopt($ch,CURLOPT_URL, $dataUpdate);
curl_setopt($ch,CURLOPT_POST, count($updateArray));
curl_setopt($ch,CURLOPT_POSTFIELDS, $updateArray_string);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//execute post
$result = curl_exec($ch);
//POST for DATA DELIVERY
curl_setopt($ch,CURLOPT_URL, $campaignPost);
curl_setopt($ch,CURLOPT_POST, count($dataArray));
curl_setopt($ch,CURLOPT_POSTFIELDS, $dataArray_string);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//execute post
$result = curl_exec($ch);
//POST for VOICE DELIVERY
curl_setopt($ch,CURLOPT_URL, $voicePost);
curl_setopt($ch,CURLOPT_POST, count($voiceArray));
curl_setopt($ch,CURLOPT_POSTFIELDS, $voiceArray_string);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//execute post
$result = curl_exec($ch);
//close connection
curl_close($ch);
} else { echo 'Debug mode enabled, no deliveries occurred'; }
//Escort Elvis from the building
?>
