<?php

/**
 * Created by Nolovenodie <nolovenodie@outlook.com>
 * Version: 2023-10-24
 */

# Required File Includes
require_once("../../../init.php");
require_once("../../../includes/functions.php");
require_once("../../../includes/gatewayfunctions.php");
require_once("../../../includes/invoicefunctions.php");
use \Illuminate\Database\Capsule\Manager as Capsule;

require_once("sdk.php");

$body = file_get_contents('php://input');
$xml = simplexml_load_string($body);

logModuleCall('ouitrust', 'notify', "", $body);

$GATEWAY = getGatewayVariables('ouitrust_alipay');
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

$pay = new OuiTrust($GATEWAY['mchId'], $GATEWAY['key']);
$ret = $pay->query($xml->out_trade_no->__toString());

logModuleCall('ouitrust', 'notify-query', "", json_encode($ret));

if (!$ret['success'] || strtolower($ret['state']) != "success") {
	logTransaction($GATEWAY["name"], $_POST, "Unsuccessful");
	die('FAIL');
}

$invoiceId = end(explode("-", $xml->out_trade_no->__toString()));
$transId = $xml->transaction_id->__toString();
$paymentAmount = intval($xml->total_fee->__toString()) / 100;
$feeAmount = 0;

$invoice = \Illuminate\Database\Capsule\Manager::table('tblinvoices')->where('id', $invoiceId)->first();
if ($invoice->status === 'Paid') {
    die('SUCCESS');
}

$method = $xml->trade_type->__toString() == OUITRUST_APP_ALIPAY ? "Alipay" : "Wechat";

checkCbTransID($transId);
addInvoicePayment($invoiceId, $transId, $paymentAmount, $feeAmount, $method);
logTransaction($GATEWAY["name"], $body, "Successful-A");
die('SUCCESS');
