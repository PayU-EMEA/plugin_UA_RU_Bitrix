<?php
#ini_set( "display_errors", true );
#error_reporting( E_ALL );


if ($_SERVER["REQUEST_METHOD"] !== "POST") die();
if(!require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php")) die('prolog_before.php not found!');
if (CModule::IncludeModule('sale'))
{
  $ord = $_POST['REFNOEXT'];
  $ordArray = explode( "_", $ord );
  $ORDER_ID = $ordArray[1];
  $User_ID = $ordArray[2];

  $arOrder = CSaleOrder::GetByID($ORDER_ID);
  
  $payID = $arOrder['PAY_SYSTEM_ID'];

  $temp = CSalePaySystemAction::GetList(
            array(),
            array( "PAY_SYSTEM_ID" => $payID )
     );
  $payData = $temp->Fetch();

  include  $_SERVER['DOCUMENT_ROOT'].$payData['ACTION_FILE']."/payu.cls.php"; 
  
  $b = unserialize( $payData['PARAMS'] );
  
  foreach ( $b as $k => $v ) $payuOpt[$k] = $v['VALUE'];
  
  $option  = array( 
                'merchant' => $payuOpt["MERCHANT"], 
                'secretkey' => $payuOpt["SECURE_KEY"], 
                );

  $payansewer = PayU::getInst()->setOptions( $option )->IPN();

  $stmp = strtotime( $_POST['SALEDATE'] );
  $arFields = array(
        "STATUS_ID" => "P",
        "PAYED" => "Y",
        "PS_STATUS" => "Y", 
        "PS_STATUS_CODE" => $_POST['ORDERSTATUS'] ,
        "PS_STATUS_DESCRIPTION" => $_POST['ORDERSTATUS']. " " . $_POST['PAYMETHOD'] ,
        "PS_STATUS_MESSAGE" => " - ",
        "PS_SUM" => $_POST['IPN_TOTALGENERAL'],
        "PS_CURRENCY" =>$_POST['CURRENCY'],
        "PS_RESPONSE_DATE" => date( "d.m.Y H:i:s" ),
      );
    CSaleOrder::Update( $ORDER_ID, $arFields );
  echo $payansewer;
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>