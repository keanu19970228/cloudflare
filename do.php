<?php
require 'api.php';

// $act        = @$_GET['act'];
$data       = json_decode($_POST['data'], true);

// DNS 操作
$dnsAct     = $data['dns_act'];
// email
$email      = trim($data['email']);
// apikey
$apiKey     = trim($data['apikey']);
// IP 地址
$ip         = trim($data['ip']);
// domains
$domains    = trim($data['domains']);
// 代理状态
$proxied    = $data['proxied'];

if (empty($email) || empty($apiKey)) {
    echo json_encode(['code' => 0, 'msg' => '缺少参数']);
    die;
}
// delete 操作除外
if ($dnsAct !== 'delete') {
    if (empty($ip) || empty($domains)) {
        echo json_encode(['code' => 0, 'msg' => '缺少参数']);
        die;
    }
}
// domains
$domains    = explode("\n", trim($data['domains']));

$cf = new Api($email, $apiKey);

switch ($dnsAct) {
    // 新建 DNS 记录
    case 'create':
        $msg_create = '';
        foreach ($domains as $v) {
            $zone = $cf->getZone($v);
            if ($zone['success'] == false) {
                $msg_create .= '❌： ' . $zone['errors'][0]['message'] . ',';
                continue;
            } else {
                $res = $cf->createDnsRecord('A', $v, $ip, 1, $proxied, $zone['result']['id']);
                $cf->createDnsRecord('A', 'www', $ip, 1, $proxied, $zone['result']['id']);
                $res = json_decode($res, true);
                $msg_create .= '✅： ' . $v . ' 添加成功了,';
                continue;
            }
        };
        $msg_create = explode(',', $msg_create);
        echo json_encode(['code' => 1, 'msg' => $msg_create]);
        break;
    // 更新 DNS 记录
    case 'update':
        $msg_update = '';
        foreach ($domains as $v) {
            $zoneId = $cf->getDomainZoneId($v);
            // 如果有 www 记录，更新 www 的记录
            $recordWwwId = $cf->getDomainWwwRecordId($v); // www
            if (!empty($recordWwwId)) {
                $cf->patchDnsRecord('A', 'www', $ip, 1, $proxied, $zoneId, $recordWwwId);
            }
            // 更新 @ 的记录
            $recordId = $cf->getDomainRecordId($v); // @
            $res = $cf->patchDnsRecord('A', '@', $ip, 1, $proxied, $zoneId, $recordId);
            $res = json_decode($res, true);
            if ($res['success'] == false) {
                $msg_update .= '❌： ' . $res['errors'][0]['message'] . ',';
                continue;
            } else {
                $msg_update .= '✅： ' . $v . ' 更新成功了,';
                continue;
            }
        };
        $msg_update = explode(',', $msg_update);
        echo json_encode(['code' => 1, 'msg' => $msg_update]);
        break;
    // 删除网站
    case 'delete':
        $msg_delete = '';
        foreach ($domains as $v) {
            // 删除网站的 DNS 记录(@, www)
            $recordWwwId = $cf->getDomainWwwRecordId($v); // www
            $recordId = $cf->getDomainRecordId($v); // @
            $cf->deleteDnsRecord($v, $recordWwwId); // www
            $cf->deleteDnsRecord($v, $recordId); // @

            // 删除网站
            $res = $cf->deleteSite($v);
            $res = json_decode($res, true);
            if ($res['success'] == true) {
                $msg_delete .= '✅： ' . $v . ' 删除成功了,';
                continue;
            } else {
                $msg_delete .= '❌： ' . $res['errors'][0]['message'] . ',';
                continue;
            }
        };
        $msg_delete = explode(',', $msg_delete);
        echo json_encode(['code' => 1, 'msg' => $msg_delete]);
        break;

    default:
        return false;
}
