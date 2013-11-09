<?php
//***********************************Set defaults***************************************
//Debug
$debug = FALSE;
//Campaign setup
$campaignPost = 'http://post.leadmesh.net/1e7087523d05ab0d8ff2fdf3cd8cdea3b050807e1ede19722814cc74058ba7c8';
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
if (isset($_GET['interest_rate'])){}else{exit ("Missing Interest Rate");}
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

//************************************************ARRAY CREATION***********************************
//We want to update our records first, send call count and data to leadmesh second, and dial with customer last
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
$dataArray = array(
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
    Fannie                  => $_GET['fannie'],
    Freddie                 => $_GET['freddie'],
    ARM                     => $_GET['arm'],
    Fixed_Period            => $_GET['fixed_period'],
    Interest_Rate           => $_GET['interest'],
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POST, count($dataArray));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $dataArray_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//execute post
    $response = curl_exec($ch);
//**********************************LeadMesh data delivery and transfer number accept**************
// Get XML response & parse to variables
    try {
        // disable standard libxml errors, in case of bad xml, exception will be thrown
        libxml_use_internal_errors(1);
        $sxe = new SimpleXMLElement($response);
        libxml_use_internal_errors(0);
    } catch (Exception $e) {
        // @todo handle xml parsing exception
        exit;
    }
// hack to convert SimpleXMLElement object to array
    $response = json_decode(json_encode($sxe), true);

    /**
     * In case of success, $response variable should contain:
     *
     *   array(
     *     'response' => 'Accepted',
     *     'company' => 'This Company Inc.',
     *     'phone' => '1-800-123-4567',
     *   )
     *
     * or if failure, then
     *
     *   array(
     *     'response' => 'No Coverage'
     *   )
     */
//Process response and set transferNumber
$LMresponse = var_dump($response["response"]);
if ($LMresponse = "No Coverage") {exit ("No Match for Delivery");}

$LMcompany = var_dump($response["company"]);
$transferNumber = var_dump($response["phone"]);
//Build VOICE DELIVERY array
    $voiceArray = array (
        'source'                => $source,
        'user'                  => $user,
        'pass'                  => $pass,
        'agent_user'            => $user_agent,
        'function'              => 'transfer_conference',
        'value'                 => 'DIAL_WITH_CUSTOMER',
        'phone_number'          => $transferNumber,
        'dial_override'         => 'YES',
    );
//if ($debug){ print_r($voiceArray);}

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