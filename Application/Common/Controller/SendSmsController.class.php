<?php
/**
 * Created by PhpStorm.
 * User: Adminlmcqistrator
 * Date: 2017/3/20 0020
 * Time: 09:26
 */
namespace Common\Controller;

use Think\Controller;

class SendSmsController extends Controller
{
    public function sendSms($phones, $content)
    {
        $username = urlencode('yms111');
        $password = urlencode('896($xkX');
        //$sign = env('SMS_SIGN', '【国金科技】');
        $sign = "【CITY服务平台】";
        if (!strpos($content, $sign)) {
            $content .= $sign;
        }
        $content = urlencode(iconv("UTF-8", "gb2312//IGNORE", trim($content)));
        $url = "http://api.1086sms.com/api/send.aspx?username=$username&password=$password&mobiles=$phones&content=$content";
        $ret = file_get_contents($url);
        $ret = urldecode($ret);
        $result = [];
        foreach (explode('&', $ret) as $v) {
            list($key, $value) = explode('=', $v);
            $result[$key] = iconv('gb2312', 'utf-8', $value);
        }
        return $result;
    }

}