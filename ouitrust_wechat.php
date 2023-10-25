<?php

/**
 * Created by Nolovenodie <nolovenodie@outlook.com>
 * Version: 2023-10-24
 */

function ouitrust_wechat_MetaData() {
    return array(
        'DisplayName' => 'Ouitrust',
        'APIVersion' => '1.0',
    );
}

function ouitrust_wechat_config() {
  return [
    'FriendlyName' => ['Type' => 'System', 'Value' => 'Wechat'],
    'mchId' => ['FriendlyName' => 'MchId', 'Type' => 'text', 'Size' => '32',],
    'key' => ['FriendlyName' => 'Key', 'Type' => 'text', 'Size' => '32',],
  ];
}

function ouitrust_wechat_link($params) {
  if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) return;

  require_once('ouitrust/link.php');
  return (new Link())->getHTML(OUITRUST_APP_WECHAT, $params);
}
