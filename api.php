<?php

class Api
{
    // 请求 URL
    private $url;

    private $email;

    private $apikey;

    public function __construct($email, $apikey)
    {
        $this->url      = 'https://api.cloudflare.com/client/v4/';
        $this->email    = $email;
        $this->apikey   = $apikey;
    }

    // 创建 DNS 记录
    public function createDnsRecord(
        $type = "A", // DNS record type.("A")
        $name, // DNS record name (or @ for the zone apex)(example.com).
        $content, // DNS record content.(127.0.0.1)
        $ttl = 1, // Time to live, in seconds, of the DNS record. Must be between 60 and 86400, or 1 for 'automatic'.
        $proxied,  // Whether the record is receiving the performance and security benefits of Cloudflare.
        $zoneId // zoneID
    ) {
        $url = $this->url . 'zones/' . $zoneId . '/dns_records';
        $data = [
            'type'      => $type,
            'name'      => $name,
            'content'   => $content,
            'ttl'       => $ttl,
            'priority'  => 10, // Required for MX, SRV and URI records; unused by other record types. Records with lower priorities are preferred.
            'proxied'   => $proxied == 1 ? true : false
        ];
        return $this->curl($url, 'POST', $data);
    }

    // 更新 DNS 记录
    public function updateDnsRecord(
        $type = "A", // DNS record type.("A")
        $name, // DNS record name (or @ for the zone apex)(example.com).
        $content, // DNS record content.(127.0.0.1)
        $ttl = 1, // Time to live, in seconds, of the DNS record. Must be between 60 and 86400, or 1 for 'automatic'.
        $proxied, // Whether the record is receiving the performance and security benefits of Cloudflare.
        $zoneId, // zoneID
        $recordId // recordID
    ) {
        $url = $this->url . 'zones/' . $zoneId . '/dns_records/' . $recordId;
        $data = [
            'type'      => $type,
            'name'      => $name,
            'content'   => $content,
            'ttl'       => $ttl,
            'proxied'   => $proxied == 1 ? true : false,
        ];
        return $this->curl($url, 'PUT', $data);
    }

    // 修补 DNS 记录
    public function patchDnsRecord(
        $type = "A", // DNS record type.("A")
        $name, // DNS record name (or @ for the zone apex)(example.com).
        $content, // DNS record content.(127.0.0.1)
        $ttl = 1, // Time to live, in seconds, of the DNS record. Must be between 60 and 86400, or 1 for 'automatic'.
        $proxied, // Whether the record is receiving the performance and security benefits of Cloudflare.
        $zoneId, // zoneID
        $recordId // recordID
    ) {
        $url = $this->url . 'zones/' . $zoneId . '/dns_records/' . $recordId;
        $data = [
            'type'      => $type,
            'name'      => $name,
            'content'   => $content,
            'ttl'       => $ttl,
            'proxied'   => $proxied == 1 ? true : false,
        ];
        return $this->curl($url, 'PATCH', $data);
    }

    // 删除 DNS 记录
    public function deleteDnsRecord($domain, $recordId)
    {
        $url = $this->url . 'zones/' . $this->getDomainZoneId($domain) . '/dns_records/' . $recordId;
        return $this->curl($url, 'DELETE');
    }

    // 删除网站
    public function deleteSite($domain)
    {
        $url = $this->url . 'zones/' . $this->getDomainZoneId($domain);
        return $this->curl($url, 'DELETE');
    }

    // 获取区域
    public function getZone($name)
    {
        $url = $this->url . 'zones';
        $zone = $this->curl($url, 'POST', ['name' => $name]);
        $zone = json_decode($zone, true);
        return $zone;
    }

    // 获取指定域名 Record ID (@)
    public function getDomainRecordId($domain)
    {
        return $this->matchDomainRecordId($domain);
    }

    // 获取指定域名 Record ID (www)
    public function getDomainWwwRecordId($domain)
    {
        return $this->matchDomainRecordId($domain, 'www');
    }

    // 匹配域名，返回 Record ID
    public function matchDomainRecordId($domain, $name = '')
    {
        $name = empty($name) ? '' : $name .= '.';
        $url = $this->url . 'zones/' . $this->getDomainZoneId($domain) . '/dns_records?name=' . $name . $domain . '&match=all';
        $res = $this->curl($url, 'GET');
        $res = json_decode($res, true);
        return $res['result'][0]['id'];
    }

    // 获取指定域名区域 ID
    public function getDomainZoneId($domain)
    {
        $url = $this->url . 'zones?name=' . $domain;
        $res = $this->curl($url, 'GET');
        $res = json_decode($res, true);
        return $res['result'][0]['id'];
    }

    // curl
    public function curl($url, $method = "GET", $data = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Auth-Email: ' . $this->email . '', // email
            'X-Auth-Key: ' . $this->apikey . '', // apikey
            'Cache-Control: no-cache',
            'Content-Type:application/json'
        ));

        if (!empty($data)) {
            $data_string = json_encode($data);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        $sonuc = curl_exec($ch);
        curl_close($ch);
        return $sonuc;
    }
}
