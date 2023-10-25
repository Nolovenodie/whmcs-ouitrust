<?php

/**
 * Created by Nolovenodie <nolovenodie@outlook.com>
 * Version: 2023-10-24
 */

require_once __DIR__ . '/../../../init.php';

use \Illuminate\Database\Capsule\Manager as Capsule;

$invoiceid = $_REQUEST['invoiceid'];

$ca = new WHMCS_ClientArea();
$userid = $ca->getUserID() ;
if (!$userid) {
    exit;
}

$invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->where('userid', $userid)->first();

if($invoice->status === "Paid"){
    echo "SUCCESS";
} else {
    echo "FAIL";
}
