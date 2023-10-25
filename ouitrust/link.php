<?php

/**
 * Created by Nolovenodie <nolovenodie@outlook.com>
 * Version: 2023-10-24
 */

require_once('sdk.php');

class Link {
    public function getHTML($app, $params) {
        $systemurl = $params['systemurl'];
        $notify_url = $systemurl . '/modules/gateways/ouitrust/notify.php';

        $pay = new OuiTrust($params['mchId'], $params['key']);
        $expire = (new DateTime('now', new DateTimeZone('Asia/Shanghai')))->modify('+5 minutes')->format('YmdHis');
        $invoiceid = "whmcs-" . md5(uniqid()) . "-" . $params['invoiceid'];
        $ret = $pay->create($app, $invoiceid, $params['description'], $expire, $params['amount'], $notify_url);

        if (!$ret['success']){
            die($ret['message']);
        }

        $code_url = $ret['data']->get("code_url");
        $jump_url = $code_url;
        if ($app == OUITRUST_APP_ALIPAY) {
            $jump_url = "alipayqr://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=" . $code_url;
        }

        return <<<HTML
            <script src="https://cdn.jsdelivr.net/npm/jquery@3.3.1/dist/jquery.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/jquery.qrcode@1.0.3/jquery.qrcode.min.js"></script>
            <style>
                #qrcode canvas {
                    width: 100%;
                    border-radius: 4px;
                }
                #qrcode + p {
                    text-align: center;
                    margin-top: 6px;
                }
                .panel-footer {
                    background: #444649 !important;
                }
            </style>
            <a href="{$jump_url}">
                <div id="qrcode"></div>
                <p>Scan QR code for payment</p>
            </a>
            <script>
                $("#qrcode").qrcode({
                    text: "{$code_url}",
                    correctLevel: 0,
                    foreground: "#fff",
                    background: "transparent",
                });
                setInterval(()=>{
                    $.get("{$systemurl}/modules/gateways/ouitrust/query.php?invoiceid={$params['invoiceid']}", (data, status)=>{
                        if(status == "success" && data == "SUCCESS"){
                            window.location.reload();
                        }
                    })
                }, 3000);
            </script>
        HTML;
    }
}
