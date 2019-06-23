<?php

//获得可卖出的倍增钱包的金额
function getActiveDouble($user_id){
    $wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
    //计算用户可卖出的倍增钱包金额,总金额-正在生息的累加和
    $time = time();
    $count = M('DoubleLog')
        ->where("end_time > $time")//正在生息
        ->sum('grant_amount_interest');//生成的利息金总和
    $active_amount = $wallet['double_amount'] - $count;//可用的倍增钱包金额
    return $active_amount;
}


//卖出倍增正在生息的倍增钱包的钱,利息扣除
function isTakeOutIntrest($user_id,$sale_amount){
    //如果倍增钱包还在生息,那卖出利息将没有
    //1.计算生息结束的总金额
    $now =time();
    $count = M('DoubleLog')
        ->where(['user_id'=>$user_id])
        ->where("end_time < $now")
        ->sum('amount');
    if($count < $sale_amount){
        //卖出了生息的部分
        $cha_amount = $sale_amount - $count;
        $double = M('DoubleLog')
            ->where(['user_id'=>$user_id])
            ->where("add_time < $now and end_time > $now")
            ->order('add_time asc')
            ->select();//正在生息的订单,以找到用来生息的本金amount
        if($double){
            foreach($double as $v){
                //生息的累计金额清空
                $res = M('Wallet')
                    ->where(['user_id'=>$user_id])
                    ->setDec('double_amount',$v['grant_amount_interest']);//生成的利息根据本金的使用情况一点一点的扣除
                if($res){
                    //清空生息
                    M('DoubleLog')->where(['id'=>$v['id']])->setField('grant_amount_interest',0);//生成的利息根据本金的使用情况一点一点的扣除
                }
                if($v['amount']>$cha_amount){
                    //如果当次订单中的生息本金大于超额的部分值,只减去当次订单的生息,然后跳出循环就行了
                    M('DoubleLog')->where(['id'=>$v['id']])->setDec('amount',$cha_amount);
                    break;
                }else{
                    M('DoubleLog')->where(['id'=>$v['id']])->setDec('amount',$v['amount']);
                    $cha_amount = $cha_amount - $v['amount'];//如果不足的话就扣除本次的生息本金,余下部分进行下一次循环
                }
            }
        }
    }
}
//前台获取用户vip等级
function getviplevel($directpush,$myteams){
    $config=M('config')->find(1);
    if ($directpush>=$config['push_vip3']&&$myteams>=$config['push_team3']) {
        $result='T3';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip2']&&$myteams>=$config['push_team2']) {
        $result='T2';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip1']) {
        $result='T1';
        return $result;
        exit();
    }else{
        $result='普通用户';
        return $result;
    }
}
//前台获取用户vip等级(按照等级数返回)
function getvipleveltow($directpush,$myteams){
    $config=M('config')->find(1);
    if ($directpush>=$config['push_vip7']&&$myteams>=$config['push_team7']) {
        $result='7';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip6']&&$myteams>=$config['push_team6']) {
        $result='6';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip5']&&$myteams>=$config['push_team5']) {
        $result='5';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip4']&&$myteams>=$config['push_team4']) {
        $result='4';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip3']&&$myteams>=$config['push_team3']) {
        $result='3';
        return $result;
        exit();
    }elseif ($directpush>=$config['push_vip2']) {
        $result='2';
        return $result;
        exit();
    }else{
        $result='1';
        return $result;
    }
}
?>
