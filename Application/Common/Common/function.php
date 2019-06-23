<?php
//图片上传
function upload($filename){
    $uploads = new \Think\Upload();// 实例化上传类
    $uploads->maxSize   =     5242880 ;// 设置附件上传大小5M
    $uploads->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    $uploads->savePath  =      '/Pic/'; // 设置附件上传目录
    //上传文件
    $info   =   $uploads->uploadOne($filename);
    if(!$info) {// 上传错误提示错误信息
        return array('status'=>0,'message'=>$uploads->getError());
    }else{// 上传成功 获取上传文件信息
        return array('status'=>1,'message'=>'/Uploads'.$info['savepath'].$info['savename']);
    }
}
//买入订单匹配
function buyMatch($sale_orders,$buy_order_id,$amount){
    foreach($sale_orders as $v){
        if($v['match_amount_not']>=$amount){
            //带匹配金额大于等于诚信金,单个订单
            oneMatch($buy_order_id,$v['id'],$amount);
            break;
        }else{
            oneMatch($buy_order_id,$v['id'],$v['match_amount_not']);
            $amount -= $v['match_amount_not'];//匹配一单,就减去匹配的金额
        }
        if($amount<=0){
            break;
        }
    }
    return true;

}

//卖出订单匹配
function saleMatch($buy_orders,$sale_order_id,$amount){
    foreach($buy_orders as $v){
        if($v['match_amount_not']>=$amount){
            //带匹配金额大于等于诚信金,单个订单
            oneMatch($v['id'],$sale_order_id,$amount);
            break;
        }else{
            oneMatch($v['id'],$sale_order_id,$v['match_amount_not']);
            $amount -= $v['match_amount_not'];//匹配一单,就减去匹配的金额
        }
        if($amount<=0){
            break;
        }
    }
    return true;

}


//单个订单进行匹配
function oneMatch($buy_order_id,$sale_order_id){
    $m = M();
    $m->startTrans();
    try{
        //创建匹配记录
        $buyinfo=M('HelpOrder')->where(array('id'=>$buy_order_id))->find();
        $saleinfo=M('AskhelpOrder')->where(array('id'=>$sale_order_id))->find();
        //判断买入/卖出金额大小
        if ($buyinfo['amount']==$saleinfo['amount']) {
            ppddadd($buy_order_id, $sale_order_id);
        }elseif ($buyinfo['amount']>$saleinfo['amount']) {
            $data=$buyinfo;
            $data['amount']=$saleinfo['amount'];
            $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
            unset($data['id']);//清空$data['id']
            M('help_order')->add($data);
            $id = M('help_order')->getLastInsID();//获取数据表中最大的ID值
            //未匹配
            $data2 = $data;
            $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
            $data2['amount'] = $buyinfo['amount'] - $saleinfo['amount'];//第二个买入订单中的买入数量
            M('help_order')->add($data2);
            M('help_order')->where(array('id' => $buyinfo['id']))->delete();//根据序号将原始的买入订单数据删除
            ppddadd($id, $saleinfo['id']);
        }else{
            $data=$saleinfo;
            $data['amount']=$buyinfo['amount'];
            $data['order_type']='2';   //订单类型为子订单
            $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
            unset($data['id']);//清空$data['id']
            M('askhelp_order')->add($data);
            $id = M('askhelp_order')->getLastInsID();//获取数据表中最大的ID值
            //未匹配
            $data2 = $data;
            $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
            $data2['amount'] = $saleinfo['amount'] - $buyinfo['amount'];//第二个卖出订单中的卖出数量
            $data2['order_type']='2';   //订单类型为子订单
            M('askhelp_order')->add($data2);
            if ($saleinfo['order_type']=='2') {
                M('askhelp_order')->where(array('id' => $saleinfo['id']))->delete();//根据序号将原始的卖出订单数据删除
            }
            ppddadd($buyinfo['id'],$id);
        }
        $m->commit();
    }catch (\PDOException $e){
        $m->rollback();
        return false;
    }
    return true;
}
//匹配订单
function ppddadd($p_id,$g_id){
    $buyinfo=M('HelpOrder')->where(array('id'=>$p_id))->find();
    $saleinfo=M('AskhelpOrder')->where(array('id'=>$g_id))->find();
    $order_number = date('YmdHis',time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $match = array(
        'buy_order_id'=>$p_id,
        'sale_order_id'=>$g_id,
        'buy_id'=>$buyinfo['user_id'],
        'sale_id'=>$saleinfo['user_id'],
        'buy_name'=>$buyinfo['user_name'],
        'sale_name'=>$saleinfo['user_name'],
        'amount'=>$saleinfo['amount'],
        'order_number'=>$order_number,
        'create_time'=>date('Y-m-d H:i:s',time()),
    );
    M('MatchOrder')->data($match)->add();
    //更改买入订单的状态
    M('HelpOrder')->where(['id'=>$p_id])->setField('matching',1);
    //更改卖出订单的状态
    M('AskhelpOrder')->where(['id'=>$g_id])->setField('matching',1);
    //创建通知记录
    $buy_order = M('HelpOrder')->where(['id'=>$p_id])->find();
    $log1 = array(
        'user_id'=>$buy_order['user_id'],
        // 'content'=>$buy_order['user_name']."您好,您买入".$buy_order['amount']."的订单,已经匹配成功!",
        'content'=>"亲爱的会员,您的买入订单".$buy_order['order_number']."已匹配,请在规定时间内完成打款操作.",
        'createtime'=>time()
    );
    M('UserNotice')->data($log1)->add();
    $sale_order = M('AskhelpOrder')->where(['id'=>$g_id])->find();
    $log2 = array(
        'user_id'=>$sale_order['user_id'],
        // 'content'=>$sale_order['user_name']."您好,您卖出的".$sale_order['amount']."的订单,已经匹配成功!",
        'content'=>"亲爱的会员,您的卖出订单".$sale_order['order_number']."已匹配,请等待对方打款.",
        'createtime'=>time()
    );
    M('UserNotice')->data($log2)->add();
    //发送短信通知买家,匹配成功
    $phone = $buy_order['user_phone'];
    $buy_order_number = $buy_order['order_number'];
    // $content = "您的订单号为".$buy_order_number."的排单，已为您匹配".$amount."元，请立即去打款";
    $content = "亲爱的会员,您的买入订单".$buy_order_number."已匹配，请在规定时间内完成打款操作.";
    sendSms2($phone, $content);
    //发送短信通知卖家,匹配成功
    $phone2 = $sale_order['user_phone'];
    $sale_order_number = $sale_order['order_number'];
    // $content2 = "您的订单号为".$sale_order_number."的排单，已为您匹配".$amount."元，待对方打款";
    $content2 = "亲爱的会员,您的卖出订单".$buy_order_number."已匹配，请等待对方打款.";
    sendSms2($phone2, $content2);
}
//发送短信验证码
function sendSms2($phones, $content)
{
    $username = urlencode('yms111');
    $password = urlencode('896($xkX');
    //$sign = env('SMS_SIGN', '【国金科技】');
    $sign = "【圆梦社】";
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
/**
 * 功能：生成二维码
 * @param string $qr_data   手机扫描后要跳转的网址
 * @param string $qr_level  默认纠错比例 分为L、M、Q、H四个等级，H代表最高纠错能力
 * @param string $qr_size   二维码图大小，1－10可选，数字越大图片尺寸越大
 * @param string $save_path 图片存储路径
 * @param string $save_prefix 图片名称前缀
 */
function createQRcode($save_path,$qr_data='PHP QR Code :)',$qr_level='L',$qr_size=4,$save_prefix='qrcode'){
    if(!isset($save_path)) return '';
    //设置生成png图片的路径
    $PNG_TEMP_DIR = & $save_path;
    //导入二维码核心程序
    vendor('phpqrcode.phpqrcode');  //PHPQRcode是文件夹名字，phpqrcode就代表phpqrcode.php文件名
    //检测并创建生成文件夹
    if (!file_exists($PNG_TEMP_DIR)){
        mkdir($PNG_TEMP_DIR);
    }
    $filename = $PNG_TEMP_DIR.'test.png';
    $errorCorrectionLevel = 'L';
    if (isset($qr_level) && in_array($qr_level, array('L','M','Q','H'))){
        $errorCorrectionLevel = & $qr_level;
    }
    $matrixPointSize = 4;
    if (isset($qr_size)){
        $matrixPointSize = & min(max((int)$qr_size, 1), 10);
    }
    if (isset($qr_data)) {
        if (trim($qr_data) == ''){
            die('data cannot be empty!');
        }
        //生成文件名 文件路径+图片名字前缀+md5(名称)+.png
        $filename = $PNG_TEMP_DIR.$save_prefix.md5($qr_data.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
        //开始生成
        QRcode::png($qr_data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    } else {
        //默认生成
        QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    if(file_exists($PNG_TEMP_DIR.basename($filename)))
        return basename($filename);
    else
        return FALSE;
}


