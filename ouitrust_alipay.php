<?php

/**
 * Created by Nolovenodie <nolovenodie@outlook.com>
 * Version: 2023-10-24
 */

function ouitrust_alipay_MetaData() {
    return array(
        'DisplayName' => 'Ouitrust',
        'APIVersion' => '1.0',
    );
}

function ouitrust_alipay_config() {
  return [
    'FriendlyName' => ['Type' => 'System', 'Value' => 'Alipay'],
    'mchId' => ['FriendlyName' => 'MchId', 'Type' => 'text', 'Size' => '32',],
    'key' => ['FriendlyName' => 'Key', 'Type' => 'text', 'Size' => '32',],
  ];
}

function ouitrust_alipay_link($params) {
  if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) return;

  require_once('ouitrust/link.php');
  return (new Link())->getHTML(OUITRUST_APP_ALIPAY, $params);
}
