<?php
declare(strict_types=1);

namespace cccms\services;

use think\App;
use cccms\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\{GuzzleException, RequestException};

class CrontabService extends Service
{
    private $baseUri;
    private $safeKey;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->baseUri = config('crontab.base_uri') ?: 'http://127.0.0.1:2345';
        $this->safeKey = config('crontab.safe_key') ?: null;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $form
     * @return array
     * @throws RequestException|GuzzleException
     */
    public function httpRequest(string $url, string $method = 'GET', array $form = []): array
    {
        try {
            $client = new Client([
                'base_uri' => $this->baseUri,
                'headers' => [
                    'key' => $this->safeKey
                ]
            ]);
            $response = $client->request($method, $url, ['form_params' => $form]);
            $data = [
                'ok' => true,
                'data' => json_decode($response->getBody()->getContents(), true)['data'],
                'msg' => 'success',
            ];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $msg = json_decode($e->getResponse()->getBody()->getContents(), true)['msg'];
            } else {
                $msg = $e->getMessage();
            }
            $data = [
                'ok' => false,
                'data' => [],
                'msg' => $msg
            ];
        }
        return $data;
    }

    /**
     * 时间戳转crontab规则
     * @param $time
     * @return string
     */
    public function timestampConvertCrontab($time): string
    {
        if (is_string($time)) $time = strtotime($time);
        $minute = date('i', $time); // 获取分钟
        $hour = date('H', $time); // 获取小时
        $dayOfMonth = date('d', $time); // 获取月份中的日期
        $month = date('m', $time); // 获取月份
        $dayOfWeek = date('w', $time); // 获取星期几
        return "$minute $hour $dayOfMonth $month *";
    }
}
