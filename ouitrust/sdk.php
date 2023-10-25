<?php

/**
 * Created by Nolovenodie <nolovenodie@outlook.com>
 * Version: 2023-10-24
 */

define('OUITRUST_APP_ALIPAY', 'pay.alipay.native.intl');
define('OUITRUST_APP_WECHAT', 'pay.weixin.native.intl');


class OuiTrustPackEncode {
    private $key;
    private $data;
    private $map;

    public function __construct($key) {
        $this->key = $key;
        $this->data = new SimpleXMLElement('<xml></xml>');
        $this->map = array();
    }

    public function add($key, $value) {
        $this->data->addChild($key, $value);
        if (!empty($value)) {
            $this->map[$key] = $value;
        }
    }

    public function sign() {
        ksort($this->map);
        $sorted_dict = $this->map + array('key' => $this->key);
        $sign_str = urldecode(http_build_query($sorted_dict));
        return strtoupper(md5($sign_str));
    }

    public function done() {
        return $this->data->asXML();
    }
}

class OuiTrustPackDecode {
    public $status;
    public $message;
    private $xml;

    public function __construct($res) {
        if ($res['status_code'] != 200) {
            $this->status = $res['status_code'];
            $this->message = '🤕Payment gateway connection failed, please contact technical personnel!';
            return;
        }
        $this->xml = simplexml_load_string($res['body']);
        $this->status = intval($this->xml->status);
        $this->message = $this->xml->message;
    }

    public function get($key) {
        return $this->xml->{$key};
    }
}

class OuiTrust {
    private $base_url;
    private $mch_id;
    private $key;

    public function __construct($mch_id, $key) {
        $this->base_url = 'https://app-gw.ouitrust.com/mcht-acquirer-api/xml/transaction';
        $this->mch_id = $mch_id;
        $this->key = $key;
    }

    public function create($app = OUITRUST_APP_ALIPAY, $out_trade_no = null, $body = '', $time_expire = null, $amount = 0.0, $notify_url = '') {
        $pack = new OuiTrustPackEncode($this->key);
        // 接口类型
        $pack->add('service', $app);
        // 商户号
        $pack->add('mch_id', $this->mch_id);
        // 商户订单号
        $pack->add('out_trade_no', $out_trade_no);
        // 商品描述
        $pack->add('body', $body);
        // 支付金额
        $pack->add('total_fee', strval(ceil($amount * 100)));
        // 请求方 IP
        $pack->add('mch_create_ip', $_SERVER['REMOTE_ADDR']);
        // 支付接口通知地址
        $pack->add('notify_url', $notify_url);
        // 订单失效时
        if ($time_expire) {
            $pack->add('time_expire', $time_expire);
        }
        // 随机字串
        $pack->add('nonce_str', join('', array_rand(array_flip(str_split(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"))), 32)));
        // 签名
        $pack->add('sign', $pack->sign());
        // 请求
        $headers = array('Content-Type: text/xml');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pack->done());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ret = new OuiTrustPackDecode(array('status_code' => $status_code, 'body' => $res));

        if ($ret->status != 0) {
            return [
                'success'=> false,
                'message'=>$ret->message->__toString()
            ];
        }
        return [
            'success'=> true,
            'data'=>$ret
        ];
    }

    public function query($out_trade_no = null) {
        $pack = new OuiTrustPackEncode($this->key);
        // 接口类型
        $pack->add('service', 'unified.trade.query');
        // 商户号
        $pack->add('mch_id', $this->mch_id);
        // 商户订单号
        $pack->add('out_trade_no', $out_trade_no);
        // 随机字串
        $pack->add('nonce_str', join('', array_rand(array_flip(str_split(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"))), 32)));
        // 签名
        $pack->add('sign', $pack->sign());
        // 请求
        $headers = array('Content-Type: text/xml');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pack->done());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ret = new OuiTrustPackDecode(array('status_code' => $status_code, 'body' => $res));
        if ($ret->status != 0) {
            return [
                'success'=> false,
                'message'=>$ret->message->__toString()
            ];
        }
        if (intval($ret->get('result_code')) != 0) {
            return [
                'success'=> false,
                'message'=>$ret->get('err_msg')
            ];
        }
        return [
            'success'=> true,
            'state'=>$ret->get('trade_state')
        ];
    }
}