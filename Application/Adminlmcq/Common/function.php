<?php
function getpage($count, $pagesize = 10) {
	$p = new Think\Page($count, $pagesize);
	$p->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
	$p->setConfig('prev', '上一页');
	$p->setConfig('next', '下一页');
	$p->setConfig('last', '末页');
	$p->setConfig('first', '首页');
	$p->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');
	$p->lastSuffix = false;//最后一页不显示为总页数
	return $p;
}

function cate($var){
		$user = M('user');
		$ztname=$user->where(array('UE_accName'=>$var,'UE_check'=>'1','UE_stop'=>'1'))->getField('ue_account',true);
		$zttj = count($ztname);
		$reg=$ztname;
		$datazs = $zttj;
		if($zttj<=10){
			$s=$zttj;
		}else{
			$s=10;
		}
		if($zttj!=0){

		  for($i=1;$i<$s;$i++){
				if($reg!=''){
					$reg=$user->where(array('UE_accName'=>array('IN',$reg),'UE_check'=>'1','UE_stop'=>'1'))->getField('ue_account',true);
					$datazs +=count($reg);
				}
			}

		}

	//	$this->ajaxReturn();

	return $datazs;
}
//后台获取用户vip等级
function getviplevel($directpush,$myteams){
	$config=M('config')->find(1);
	if ($directpush>=$config['push_vip7']&&$myteams>=$config['push_team7']) {
        $result='VIP至尊';
    }elseif ($directpush>=$config['push_vip6']&&$myteams>=$config['push_team6']) {
    	$result='VIP钻石';
    }elseif ($directpush>=$config['push_vip5']&&$myteams>=$config['push_team5']) {
    	$result='VIP5';
    }elseif ($directpush>=$config['push_vip4']&&$myteams>=$config['push_team4']) {
    	$result='VIP4';
    }elseif ($directpush>=$config['push_vip3']&&$myteams>=$config['push_team3']) {
    	$result='VIP3';
    }elseif ($directpush>=$config['push_vip2']) {
    	$result='VIP2';
    }else{
    	$result='VIP1';
    }
    return $result;
}
//后台获取用户vip等级(按照等级数返回)
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
//下级总人数
 function xiajirenshu($name,&$arr){
	 	$array = M('user')->where(['UE_accName'=>$name])->select();
	 	$num = count($array);
	 	$arr += $num;
	 	foreach($array as $key=>$value){
	 		$name = $value['ue_account'];
	 		if($name){
	 			xiajirenshu($name,$arr);
	 		}
	 	}

 }
 //团队业绩
 function xiajiyeji($name,&$arr){
	 	$array = M('user')->where(['UE_accName'=>$name])->select();
	 	foreach($array as $key=>$value){
	 		$name = $value['ue_account'];
	 		$num = M('ppdd')->where(['p_user'=>$name,'zt'=>2])->sum('jb');
	 		$arr += $num;
	 		if($name){
	 			xiajirenshu($name,$arr);
	 		}
	 	}

 }

function auto_match($where=array(),$timelimit=true,$str='',$equal=true,&$num=0){
		//初始where示例
		/* $where['UNIX_TIMESTAMP(date)'] = array('between',array('1449728981',time())); */

		if(empty($where)){
			if(IS_POST){
				$s = I('post.start');
				$e = I('post.end');
				$start = $s?$s:0;
				$end = $e?$e:time();

				if(empty($s) && empty($e)){
					$where = array();
				}else{
					$where['UNIX_TIMESTAMP(date)'] = array('between',array(strtotime($start),strtotime($end)));
				}
			}
		}

		$where['zt'] = 0;

		if(empty($timelimit)){
			$res = M('match')->find();
			if(!$res['math_switch']){return $num;}
			if($res && $res['math_switch']){

				if(!empty($res['supply_timelimit'])){
					$timelimit['s'] = 'UNIX_TIMESTAMP(date)+'.(3600*$res['supply_timelimit']);
				}
				if(!empty($res['accept_timelimit'])){
					$timelimit['a'] = 'UNIX_TIMESTAMP(date)+'.(3600*$res['accept_timelimit']);
				}

			}
		}

		$tgs = !empty($timelimit['s'])?" UNIX_TIMESTAMP(now()) > {$timelimit['s']}":array();

		$jsa = !empty($timelimit['a'])?" UNIX_TIMESTAMP(now()) > {$timelimit['a']}":array();

		/* if($equal){
			$model = M('tgbz');
			$tgbz_list = $model->where($where)->where($tgs)->order('id asc')->select();

			if(empty($tgbz_list)) return $num;
			$jsbz_list = M('jsbz')->where($where)->where($jsa)->order('id asc')->select();

			if(empty($jsbz_list)) return $num;

			foreach($tgbz_list as $key=>$val){
				foreach($jsbz_list as $key1=>$val1){

					//if条件为金币数量相等，切用户名不同.z
					if($val['jb']==$val1['jb']&&$val['user']<>$val1['user']){//如果匹配成功处理

						if(ppdd_add($val['id'],$val1['id'])){
							unset($jsbz_list[$key1]);
							++$num;
							M('tgbz')->where(array('id'=>$val['id']))->save(array('cf_ds'=>'1'));
							break;
						}
					}

				}
			}
		} */

		if(!empty($str)){
			$tgid['id'] = array('not in',trim($str,','));
		}else{
			$tgid = array();
		}
		$tgbz = M('tgbz')->where($where)->where($tgid)->where($tgs)->order('UNIX_TIMESTAMP(date)')->find();

		if(empty($tgbz)){
			return $num;
		}else{

			$jsuser['user'] = array('neq',$tgbz['user']);
			$jsbz = M('jsbz')->where($where)->where($jsuser)->where($jsa)->order('UNIX_TIMESTAMP(date)')->find();

			if(empty($jsbz)){
				$str .= $tgbz['id'].',';
				auto_match($where,$timelimit,$str,false,$num);
			}else{
				if($tgbz['jb']>$jsbz['jb']){

					$data = $tgbz;
					$data['jb'] = $jsbz['jb'];
					unset($data['id']);
					$id = M('tgbz')->add($data);

					//未匹配
					$data2 = $data;
					$data2['jb'] = $tgbz['jb']-$jsbz['jb'];
					$id2 = M('tgbz')->add($data2);

					M('tgbz')->where(array('id'=>$tgbz['id']))->delete();

					if(ppdd_add($id,$jsbz['id'])){

						++$num;
						M('tgbz')->where(array('id'=>$id))->save(array('cf_ds'=>'1'));

					}
				}else{
					//新匹配jsbz
					$data2 = $jsbz;
					$data2['jb'] = $tgbz['jb'];
					unset($data2['id']);
					$id2 = M('jsbz')->add($data2);

					//新未匹配jsbz
					$data3 = $data2;
					$data3['jb'] = $jsbz['jb']-$tgbz['jb'];
					$id3 = M('jsbz')->add($data3);

					//删除旧订单
					M('jsbz')->where(array('id'=>$jsbz['id']))->delete();

					if(ppdd_add($tgbz['id'],$id2)){

						++$num;
						M('tgbz')->where(array('id'=>$tgbz['id']))->save(array('cf_ds'=>'1'));
					}
				}

				auto_match($where,$timelimit,$str,false,$num);
			}
			return $num;
		}
	}
	/*
	买入与卖出订单匹配
	*/
	function auto_match_r($tgbzAll,$jsbzAll,$num=0){
		set_time_limit(0);
	    if(empty($tgbzAll) || empty($jsbzAll)){
	        return $num;
	    }
	    $num1 = $num;
	    $tgbz = $tgbzAll[$num];
	    if(!$tgbz){
	        return $num--;
	    }
	    $tgbzJb = $tgbz['amount'];//买入订单中买入的数量值
	    foreach($jsbzAll as $k=>$jsbz){
	        $jsbzJb = $jsbz['amount'];//卖出订单中卖出的数量值
	        if($tgbz['user_id'] != $jsbz['user_id']){//当买入和卖出订单中会员ID不相同,即不是同一账户时,可以继续执行,否则跳出本次执行
	            if ($tgbzJb == $jsbzJb && $tgbzJb != 0 && $jsbzJb != 0) {//当买入与卖出数量相等,且买入与卖出数量均不为空时
	                ppdd_add1($tgbz['id'], $jsbz['id']);
	                unset($jsbzAll[$k]);//释放给定的变量
	                unset($tgbzAll[$num]);
	                ++$num;
	                break;
	            }elseif($tgbzJb > $jsbzJb){//当买入数量大于卖出数量时,拆分买入订单,生成新的两个新订单,第二个再进行循环匹配
	                $data = $tgbz;
	                $data['amount'] = $jsbz['amount'];//第一个买入订单中的买入数量
	                $data['parent_id'] = $tgbz['parent_id'];//最原始的订单编号
	                $data['parent_amount']=$tgbz['parent_amount'];//最原始的提供帮助金额
	                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
	                $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
	                //$data['addtime']=time();  //订单拆分时添加时间不变,以以前的添加时间为准
	                unset($data['id']);//清空$data['id']
	                M('help_order')->add($data);
	                $id = M('help_order')->getLastInsID();//获取数据表中最大的ID值
	                //未匹配
	                $data2 = $data;
	                //最大ID对应最大的订单编号
	                // $themaxorder2=M('help_order')->where(array('id'=>$id))->getField('order_number');
	                // $data2['order_number'] = $themaxorder2+1;//订单编号增加1
	                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
	                $data2['parent_id'] = $tgbz['parent_id'];//最原始的订单编号
	                $data2['amount'] = $tgbz['amount'] - $jsbz['amount'];//第二个买入订单中的买入数量
	                $data2['parent_amount']=$tgbz['parent_amount'];//最原始的提供帮助金额
	                //$data2['addtime']=time();   //订单拆分时添加时间不变,以以前的添加时间为准
	                M('help_order')->add($data2);
	                $id2 = M('help_order')->getLastInsID();
	                $data2['id'] = $id2;//一定不能去!!!!传递新的订单序号
	                M('help_order')->where(array('id' => $tgbz['id']))->delete();//根据序号将原始的买入订单数据删除
	                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
	                //M('help_order')->where(array('id' => $tgbz['id']))->save(['status'=>4]);
	                unset($jsbzAll[$k]);
	                unset($tgbzAll[$num]);
	                $res = ppdd_add1($id, $jsbz['id']);
	                if ($res) {
	                    ++$num;
	                    array_push($tgbzAll,$data2);
	                }
	                break;
	            }elseif($tgbzJb < $jsbzJb) {//当买入数量小于卖出数量时,拆分卖出订单,生成新的两个新订单,第二个再等待下次新的匹配
	                $data2 = $jsbz;
	                $data2['amount'] = $tgbz['amount'];//第一个卖出订单中的卖出数量
	                $data2['parent_id'] = $jsbz['parent_id'];
	                $data2['parent_amount']=$jsbz['parent_amount'];
	                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
	                // $data2['order_number']=$themaxorder+1;//订单编号增加1
	                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
	                $data2['order_type']='2';   //订单类型为子订单
	                unset($data2['id']);//清空$data2['id']
	                M('askhelp_order')->add($data2);
	                $id2 = M('askhelp_order')->getLastInsID();
	                //新未匹配jsbz
	                $data3 = $data2;
	                // $themaxorder2=M('help_order')->where(array('id'=>$id2))->getField('order_number');
	                // $data3['order_number'] = $themaxorder2+1;//订单编号增加1
	                $data3['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
	                // $data3 = $data2;
	                // $data2['id'] = $id2;
	                $data3['amount'] = $jsbz['amount'] - $tgbz['amount'];
	                $data3['parent_id'] = $jsbz['parent_id'];
	                $data3['parent_amount']=$jsbz['parent_amount'];
	                $data3['order_type']='2';  //订单类型为子订单
	                //dump($data3);die;
	                M('askhelp_order')->add($data3);
	                $id3 = M('askhelp_order')->getLastInsID();
	                $data3['id'] = $id3;
	                //删除旧订单
	                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
	                if ($jsbz['order_type']!='1') {//不是总的卖出单子
	                	M('askhelp_order')->where(array('id' => $jsbz['id']))->delete();//根据序号将原始的卖出订单数据删除
	                }
	                //M('help_order')->where(array('id' => $jsbz['id']))->save(['status'=>4]);
	                unset($tgbzAll[$k]);
	                unset($jsbzAll[$num]);
	                if (ppdd_add1($tgbz['id'], $id2)) {
	                    ++$num;
	                    array_push($jsbzAll,$data3);
	                }
	                break;
	            }
	        }else{
	            continue;
	        }
	    }
	    if($num1 == $num){
	        ++$num;
	    }
	    auto_match_r($tgbzAll, $jsbzAll,$num);
	    return $num;
	}
	/*
	买入与卖出订单匹配,手动快速匹配
	*/
	function auto_match_c($tgbzAll,$jsbzAll,$num=0){
		set_time_limit(0);
	    if(empty($tgbzAll) || empty($jsbzAll)){
	        return $num;
	    }
	    $num1 = $num;
	    foreach ($tgbzAll as $key => $tgbz) {
	    	$tgbzJb = $tgbz['amount'];
	    	if ($tgbz['order_type']==1) {//预付款
	    		foreach($jsbzAll as $k=>$jsbz){
			        $jsbzJb = $jsbz['amount'];//卖出订单中卖出的数量值
			        if($tgbz['user_id'] != $jsbz['user_id']){//当买入和卖出订单中会员ID不相同,即不是同一账户时,可以继续执行,否则跳出本次执行
//			            dump($tgbz['user_id']);dump($jsbz['user_id']);die;
			            if ($tgbzJb == $jsbzJb && $tgbzJb != 0 && $jsbzJb != 0) {//当买入与卖出数量相等,且买入与卖出数量均不为空时
			                ppdd_add1($tgbz['id'], $jsbz['id']);
			                unset($jsbzAll[$k]);//释放给定的变量
			                unset($tgbzAll[$num]);
			                ++$num;
			                break;
			            }/*elseif($tgbzJb > $jsbzJb){//当买入数量大于卖出数量时,不拆分买入订单
		                    array_push($jsbzAll, $jsbz);
			                continue;
			            }*/elseif($tgbzJb > $jsbzJb){//当买入数量大于卖出数量时,拆分买入订单,生成新的两个新订单,第二个再进行循环匹配
                            $data = $tgbz;
                            $data['amount'] = $jsbz['amount'];//第一个买入订单中的买入数量
                            $data['parent_id'] = $tgbz['parent_id'];//最原始的订单编号
                            $data['parent_amount']=$tgbz['parent_amount'];//最原始的提供帮助金额
                            // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
                            $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
                            //$data['addtime']=time();  //订单拆分时添加时间不变,以以前的添加时间为准
                            unset($data['id']);//清空$data['id']
                            M('help_order')->add($data);
                            $id = M('help_order')->getLastInsID();//获取数据表中最大的ID值
                            //未匹配
                            $data2 = $data;
                            //最大ID对应最大的订单编号
                            // $themaxorder2=M('help_order')->where(array('id'=>$id))->getField('order_number');
                            // $data2['order_number'] = $themaxorder2+1;//订单编号增加1
                            $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
                            $data2['parent_id'] = $tgbz['parent_id'];//最原始的订单编号
                            $data2['amount'] = $tgbz['amount'] - $jsbz['amount'];//第二个买入订单中的买入数量
                            $data2['parent_amount']=$tgbz['parent_amount'];//最原始的提供帮助金额
                            //$data2['addtime']=time();   //订单拆分时添加时间不变,以以前的添加时间为准
                            M('help_order')->add($data2);
                            $id2 = M('help_order')->getLastInsID();
                            $data2['id'] = $id2;//一定不能去!!!!传递新的订单序号
                            M('help_order')->where(array('id' => $tgbz['id']))->delete();//根据序号将原始的买入订单数据删除
                            /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
                            //M('help_order')->where(array('id' => $tgbz['id']))->save(['status'=>4]);
                            unset($jsbzAll[$k]);
                            unset($tgbzAll[$num]);
                            $res = ppdd_add1($id, $jsbz['id']);
                            if ($res) {
                                ++$num;
                                array_push($tgbzAll,$data2);
                            }
                            break;
                        }elseif($tgbzJb < $jsbzJb) {//当买入数量小于卖出数量时,拆分卖出订单,生成新的两个新订单,第二个再等待下次新的匹配
			                $data2 = $jsbz;
			                $data2['amount'] = $tgbz['amount'];//第一个卖出订单中的卖出数量
			                $data2['parent_id'] = $jsbz['parent_id'];
			                $data2['parent_amount']=$jsbz['parent_amount'];
			                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
			                // $data2['order_number']=$themaxorder+1;//订单编号增加1
			                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
			                $data2['order_type']='2';   //订单类型为子订单
			                unset($data2['id']);//清空$data2['id']
			                M('askhelp_order')->add($data2);
			                $id2 = M('askhelp_order')->getLastInsID();
//                            $id2 = '1203';
			                //新未匹配jsbz
			                $data3 = $data2;
			                // $themaxorder2=M('help_order')->where(array('id'=>$id2))->getField('order_number');
			                // $data3['order_number'] = $themaxorder2+1;//订单编号增加1
			                $data3['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
			                // $data3 = $data2;
			                // $data2['id'] = $id2;
			                $data3['amount'] = $jsbz['amount'] - $tgbz['amount'];
			                $data3['parent_id'] = $jsbz['parent_id'];
			                $data3['parent_amount']=$jsbz['parent_amount'];
			                $data3['order_type']='2';  //订单类型为子订单
			                M('askhelp_order')->add($data3);
			                $id3 = M('askhelp_order')->getLastInsID();
//                            $id3 = '1205';
			                $data3['id'] = $id3;
			                //删除旧订单
			                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
			                if ($jsbz['order_type']!='1') {//不是总的卖出单子
			                	M('askhelp_order')->where(array('id' => $jsbz['id']))->delete();//根据序号将原始的卖出订单数据删除
			                }
			                //M('help_order')->where(array('id' => $jsbz['id']))->save(['status'=>4]);
			                unset($tgbzAll[$k]);
			                unset($jsbzAll[$num]);
			                if (ppdd_add1($tgbz['id'], $id2)) {
			                    ++$num;
			                    array_push($jsbzAll,$data3);
			                }
			                break;
			            }
			        }else{
			            continue;
			        }
			    }
	    	}else{//非预付款
	    		foreach($jsbzAll as $k=>$jsbz){
			        $jsbzJb = $jsbz['amount'];//卖出订单中卖出的数量值
			        if($tgbz['user_id'] != $jsbz['user_id']){//当买入和卖出订单中会员ID不相同,即不是同一账户时,可以继续执行,否则跳出本次执行
			            if ($tgbzJb == $jsbzJb && $tgbzJb != 0 && $jsbzJb != 0) {//当买入与卖出数量相等,且买入与卖出数量均不为空时
			                ppdd_add1($tgbz['id'], $jsbz['id']);
			                unset($jsbzAll[$k]);//释放给定的变量
			                unset($tgbzAll[$num]);
			                ++$num;
			                break;
			            }elseif($tgbzJb > $jsbzJb){//当买入数量大于卖出数量时,拆分买入订单,生成新的两个新订单,第二个再进行循环匹配
			                $data = $tgbz;
			                $data['amount'] = $jsbz['amount'];//第一个买入订单中的买入数量
			                $data['parent_id'] = $tgbz['parent_id'];//最原始的订单编号
			                $data['parent_amount']=$tgbz['parent_amount'];//最原始的提供帮助金额
			                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
			                $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
			                //$data['addtime']=time();  //订单拆分时添加时间不变,以以前的添加时间为准
			                unset($data['id']);//清空$data['id']
			                M('help_order')->add($data);
			                $id = M('help_order')->getLastInsID();//获取数据表中最大的ID值
			                //未匹配
			                $data2 = $data;
			                //最大ID对应最大的订单编号
			                // $themaxorder2=M('help_order')->where(array('id'=>$id))->getField('order_number');
			                // $data2['order_number'] = $themaxorder2+1;//订单编号增加1
			                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
			                $data2['parent_id'] = $tgbz['parent_id'];//最原始的订单编号
			                $data2['amount'] = $tgbz['amount'] - $jsbz['amount'];//第二个买入订单中的买入数量
			                $data2['parent_amount']=$tgbz['parent_amount'];//最原始的提供帮助金额
			                //$data2['addtime']=time();   //订单拆分时添加时间不变,以以前的添加时间为准
			                M('help_order')->add($data2);
			                $id2 = M('help_order')->getLastInsID();
			                $data2['id'] = $id2;//一定不能去!!!!传递新的订单序号
			                M('help_order')->where(array('id' => $tgbz['id']))->delete();//根据序号将原始的买入订单数据删除
			                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
			                //M('help_order')->where(array('id' => $tgbz['id']))->save(['status'=>4]);
			                unset($jsbzAll[$k]);
			                unset($tgbzAll[$num]);
			                $res = ppdd_add1($id, $jsbz['id']);
			                if ($res) {
			                    ++$num;
			                    array_push($tgbzAll,$data2);
			                }
			                break;
			            }elseif($tgbzJb < $jsbzJb) {//当买入数量小于卖出数量时,拆分卖出订单,生成新的两个新订单,第二个再等待下次新的匹配
			                $data2 = $jsbz;
			                $data2['amount'] = $tgbz['amount'];//第一个卖出订单中的卖出数量
			                $data2['parent_id'] = $jsbz['parent_id'];
			                $data2['parent_amount']=$jsbz['parent_amount'];
			                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
			                // $data2['order_number']=$themaxorder+1;//订单编号增加1
			                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
			                $data2['order_type']='2';   //订单类型为子订单
			                unset($data2['id']);//清空$data2['id']
			                M('askhelp_order')->add($data2);
			                $id2 = M('askhelp_order')->getLastInsID();
			                //新未匹配jsbz
			                $data3 = $data2;
			                // $themaxorder2=M('help_order')->where(array('id'=>$id2))->getField('order_number');
			                // $data3['order_number'] = $themaxorder2+1;//订单编号增加1
			                $data3['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
			                // $data3 = $data2;
			                // $data2['id'] = $id2;
			                $data3['amount'] = $jsbz['amount'] - $tgbz['amount'];
			                $data3['parent_id'] = $jsbz['parent_id'];
			                $data3['parent_amount']=$jsbz['parent_amount'];
			                $data3['order_type']='2';  //订单类型为子订单
			                //dump($data3);die;
			                M('askhelp_order')->add($data3);
			                $id3 = M('askhelp_order')->getLastInsID();
			                $data3['id'] = $id3;
			                //删除旧订单
			                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
			                if ($jsbz['order_type']!='1') {//不是总的卖出单子
			                	M('askhelp_order')->where(array('id' => $jsbz['id']))->delete();//根据序号将原始的卖出订单数据删除
			                }
			                //M('help_order')->where(array('id' => $jsbz['id']))->save(['status'=>4]);
			                unset($tgbzAll[$k]);
			                unset($jsbzAll[$num]);
			                if (ppdd_add1($tgbz['id'], $id2)) {
			                    ++$num;
			                    array_push($jsbzAll,$data3);
			                }
			                break;
			            }
			        }else{
			            continue;
			        }
			    }
	    	}
	    }
	    if($num1 == $num){
	        ++$num;
	    }
	    auto_match_c($tgbzAll, $jsbzAll);
	    return $num;
	}
	/*
	买入与卖出订单匹配,当多个买入和多个卖出进行匹配时,优先匹配预付款部分
	*/
	function auto_match_l($jsbzAll,$tgbzAll,$num=0){
		set_time_limit(0);
	    if(empty($tgbzAll) || empty($jsbzAll)){
	        return $num;
	    }
	    $num1 = $num;
	    $jsbz = $jsbzAll[$num];
	    if(!$jsbz){
	        return $num--;
	    }
	    $jsbzJb = $jsbz['amount'];//卖出订单中卖出的数量值
	    foreach($tgbzAll as $k=>$tgbz){
	        $tgbzJb = $tgbz['amount'];//买入订单中买入的数量值
	        if($jsbz['user_id'] != $tgbz['user_id']){//当卖出和买入订单中会员ID不相同,即不是同一账户时,可以继续执行,否则跳出本次执行
	        	//判断匹配的买入单子是否是预付款
	        	if ($tgbz['order_type']=='1') {//是预付款
	        		if ($jsbzJb == $tgbzJb && $tgbzJb != 0 && $jsbzJb != 0) {//当买入与卖出数量相等,且买入与卖出数量均不为空时
		                ppdd_add3($tgbz['id'],$jsbz['id']);
		                unset($tgbzAll[$k]);//释放给定的变量
		                unset($jsbzAll[$num]);
		                ++$num;
		                break;
		            }elseif($jsbzJb > $tgbzJb){//当卖出数量大于买入数量时,拆分卖出订单,生成新的两个新订单,第二个再进行循环匹配
		                $data = $jsbz;
		                $data['amount'] = $tgbz['amount'];//第一个买入订单中的买入数量
		                $data['parent_id'] = $jsbz['parent_id'];//最原始的订单编号
		                $data['parent_amount']=$jsbz['parent_amount'];//最原始的提供帮助金额
		                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
		                $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
		                $data['order_type']='2';  //订单类型为子订单
		                unset($data['id']);//清空$data['id']
		                M('askhelp_order')->add($data);
		                $id = M('askhelp_order')->getLastInsID();//获取数据表中最大的ID值
		                //未匹配
		                $data2 = $data;
		                //最大ID对应最大的订单编号
		                // $themaxorder2=M('help_order')->where(array('id'=>$id))->getField('order_number');
		                // $data2['order_number'] = $themaxorder2+1;//订单编号增加1
		                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
		                $data2['parent_id'] = $jsbz['parent_id'];//最原始的订单编号
		                $data2['amount'] = $jsbz['amount'] - $tgbz['amount'];//第二个买入订单中的买入数量
		                $data2['parent_amount']=$jsbz['parent_amount'];//最原始的提供帮助金额
		                $data2['order_type']='2';   //订单类型为子订单
		                M('askhelp_order')->add($data2);
		                $id2 = M('help_order')->getLastInsID();
		                $data2['id'] = $id2;
		                if ($jsbz['order_type']!='1') {//不是总单子
		                	M('askhelp_order')->where(array('id' => $jsbz['id']))->delete();//根据序号将原始的买入订单数据删除
		                }
		                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
		                //M('help_order')->where(array('id' => $tgbz['id']))->save(['status'=>4]);
		                unset($tgbzAll[$k]);
		                unset($jsbzAll[$num]);
		                $res = ppdd_add3($tgbz['id'],$id);
		                if ($res) {
		                    ++$num;
		                    array_push($jsbzAll,$data2);
		                }
		                break;
		            }else{//当卖出数量小于买入数量时,不拆分买入订单,跳出本次执行
		                    // array_push($tgbzAll,$tgbz);
		                    array_push($jsbzAll, $jsbz);
		                break;
		            }
	        	}else{//是非预付款
	        		if ($jsbzJb == $tgbzJb && $tgbzJb != 0 && $jsbzJb != 0) {//当买入与卖出数量相等,且买入与卖出数量均不为空时
		                ppdd_add3($tgbz['id'],$jsbz['id']);
		                unset($tgbzAll[$k]);//释放给定的变量
		                unset($jsbzAll[$num]);
		                ++$num;
		                break;
		            }elseif($jsbzJb > $tgbzJb){//当卖出数量大于买入数量时,拆分卖出订单,生成新的两个新订单,第二个再进行循环匹配
		                $data = $jsbz;
		                $data['amount'] = $tgbz['amount'];//第一个买入订单中的买入数量
		                $data['parent_id'] = $jsbz['parent_id'];//最原始的订单编号
		                $data['parent_amount']=$jsbz['parent_amount'];//最原始的提供帮助金额
		                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
		                $data['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
		                $data['addtime']='2';  //订单类型为子订单
		                unset($data['id']);//清空$data['id']
		                M('askhelp_order')->add($data);
		                $id = M('askhelp_order')->getLastInsID();//获取数据表中最大的ID值
		                //未匹配
		                $data2 = $data;
		                //最大ID对应最大的订单编号
		                // $themaxorder2=M('help_order')->where(array('id'=>$id))->getField('order_number');
		                // $data2['order_number'] = $themaxorder2+1;//订单编号增加1
		                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
		                $data2['parent_id'] = $jsbz['parent_id'];//最原始的订单编号
		                $data2['amount'] = $jsbz['amount'] - $tgbz['amount'];//第二个买入订单中的买入数量
		                $data2['parent_amount']=$jsbz['parent_amount'];//最原始的提供帮助金额
		                $data2['order_type']='2';   //订单类型为子订单
		                M('askhelp_order')->add($data2);
		                $id2 = M('help_order')->getLastInsID();
		                $data2['id'] = $id2;
		                if ($jsbz['order_type']!='1') {//不是总单子
		                	M('askhelp_order')->where(array('id' => $jsbz['id']))->delete();//根据序号将原始的买入订单数据删除
		                }
		                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
		                //M('help_order')->where(array('id' => $tgbz['id']))->save(['status'=>4]);
		                unset($tgbzAll[$k]);
		                unset($jsbzAll[$num]);
		                $res = ppdd_add3($tgbz['id'],$id);
		                if ($res) {
		                    ++$num;
		                    array_push($jsbzAll,$data2);
		                }
		                break;
		            }elseif($jsbzJb < $tgbzJb) {//当卖出数量小于买入数量时,拆分买入订单,生成新的两个新订单,第二个再等待下次新的匹配
		                $data2 = $tgbz;
		                $data2['amount'] = $jsbz['amount'];//第一个卖出订单中的卖出数量
		                $data2['parent_id'] = $tgbz['parent_id'];
		                $data2['parent_amount']=$tgbz['parent_amount'];
		                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
		                // $data2['order_number']=$themaxorder+1;//订单编号增加1
		                $data2['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
		                //$data2['addtime']=time();   //订单拆分时添加时间不变,以以前的添加时间为准
		                unset($data2['id']);//清空$data2['id']
		                M('help_order')->add($data2);
		                $id2 = M('help_order')->getLastInsID();
		                //新未匹配jsbz
		                $data3 = $data2;
		                // $themaxorder2=M('help_order')->where(array('id'=>$id2))->getField('order_number');
		                // $data3['order_number'] = $themaxorder2+1;//订单编号增加1
		                $data3['order_number']=date('YmdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//新的订单编号
		                // $data3 = $data2;
		                // $data2['id'] = $id2;
		                $data3['amount'] = $tgbz['amount'] - $jsbz['amount'];
		                $data3['parent_id'] = $tgbz['parent_id'];
		                $data3['parent_amount']=$tgbz['parent_amount'];
		                //$data3['addtime']=time();  //订单拆分时添加时间不变,以以前的添加时间为准
		                //dump($data3);die;
		                M('help_order')->add($data3);
		                $id3 = M('help_order')->getLastInsID();
		                $data3['id'] = $id3;
		                //删除旧订单
		                /*注意:不再删除原始订单,把原始订单的订单状态改成已匹配过  已匹配过代表匹配了一部分,未完全匹配完*/
		                M('help_order')->where(array('id' => $tgbz['id']))->delete();//根据序号将原始的买入订单数据删除
		                //M('help_order')->where(array('id' => $jsbz['id']))->save(['status'=>4]);
		                unset($jsbzAll[$k]);
		                unset($tgbzAll[$num]);
		                if (ppdd_add3($id2,$jsbz['id'])) {
		                    ++$num;
		                    array_push($tgbzAll,$data3);
		                }
		                break;
		            }
	        	}
	        }else{
	            continue;
	        }
	    }
	    if($num1 == $num){
	        $num++;
	    }
	    auto_match_l($jsbzAll, $tgbzAll,$num);
	    return $num;
	}
	/*
	将匹配的订单信息保存在匹配记录表中
	*/
	function ppdd_add1($p_id,$g_id){//$p_id为买入订单的id  $g_id为卖出订单的id
	    $p_user1 = M('help_order')->where(array('id'=>$p_id))->find();
	    $g_user1 = M('askhelp_order')->where(array('id'=>$g_id))->find();
	    if(!$g_user1 || !$p_user1){
	        return true;
	    }
	    //获取打款期限值(以小时为单位)
	    //$output=M('config')->where('id=1')->getField('output_cold');
	    $data_add['buy_order_id']=$p_user1['id'];
	    $data_add['sale_order_id']=$g_user1['id'];
	    $data_add['amount']=$g_user1['amount'];
	    $data_add['buy_id']=$p_user1['user_id'];
	    $data_add['sale_id']=$g_user1['user_id'];
	    $data_add['buy_name']=$p_user1['user_name'];
	    $data_add['sale_name']=$g_user1['user_name'];
	    $data_add['create_time']=date ( 'Y-m-d H:i:s', time () );//匹配订单添加时间
	    $data_add['status']=0;
	    // $data_add['pic']= '0';
	    // $data_add['getway_type']=$g_user1['get_way'];
	    // $data_add['account_info']=$g_user1['account_number'];
	    $data_add['order_number']=date('YmdHis',time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	    //修改买入与卖出订单表中的订单状态
	    M('help_order')->where(array('id'=>$p_id,'matching'=>'0'))->save(array('matching'=>'1'));
	    //每次匹配都修正一次买入总单子的匹配状态
	    $pareantorderid=M('help_order')->where(array('id'=>$p_id))->getField('parent_id');
	    M('help_order')->where(array('id'=>$pareantorderid))->save(array('matching'=>'1'));
	    M('askhelp_order')->where(array('id'=>$g_id,'matching'=>'0'))->save(array('matching'=>'1'));
	    //每次匹配都修正一次卖出总单子的匹配状态
	    $pareantsaleorderid=M('askhelp_order')->where(array('id'=>$g_id))->getField('parent_id');
	    M('askhelp_order')->where(array('id'=>$pareantsaleorderid))->save(array('matching'=>'1'));
	    if(M('match_order')->add($data_add)){
	      	//发送短信提醒
	        // vendor("Sendsms.sendsms");
	        // $send = new \Sendsms();
	        $get_user=M('user')->where(array('user_id'=>$g_user1['user_id']))->find();
	        if($get_user['user_phone']){
	        	$mes = sendSms22($get_user['user_phone'],"亲爱的会员，您的卖出订单".$g_user1['order_number']."已匹配，请等待对方打款。");
	        }
	        $log2 = array(
	            'user_id'=>$g_user1['user_id'],
	            // 'content'=>$g_user1['user_name']."您好,您卖出的".$g_user1['amount']."的订单,已经匹配成功!",
	            'content'=>"亲爱的会员,您的卖出订单".$g_user1['order_number']."已匹配,请等待对方打款.",
	            'createtime'=>time()
	        );
	        M('UserNotice')->data($log2)->add();
	        $put_user=M('user')->where(array('user_id'=>$p_user1['user_id']))->find();
	        if($put_user['user_phone']){
	        	$mes = sendSms22($put_user['user_phone'],"亲爱的会员，您的买入订单".$p_user1['order_number']."已匹配，请在规定的时间内完成打款操作。");
	        }
	        $log1 = array(
	            'user_id'=>$p_user1['user_id'],
	            // 'content'=>$p_user1['user_name']."您好,您买入".$p_user1['amount']."的订单,已经匹配成功!",
	            'content'=>"亲爱的会员,您的买入订单".$p_user1['order_number']."已匹配,请在规定时间内完成打款操作.",
	            'createtime'=>time()
	        );
	        M('UserNotice')->data($log1)->add();
	        return true;
	    }else{
	        return false;
	    }
	}
	function sendSms22($phones, $content){
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
	function ppdd_add3($p_id,$g_id){//$p_id为买入订单的id  $g_id为卖出订单的id
	    $p_user1 = M('help_order')->where(array('id'=>$p_id))->find();
	    $g_user1 = M('askhelp_order')->where(array('id'=>$g_id))->find();
	    if(!$g_user1 || !$p_user1){
	        return true;
	    }
	    //获取打款期限值(以小时为单位)
	    //$output=M('config')->where('id=1')->getField('output_cold');
	    //修改最近一次的买单数量
	    //M('user')->where(array('user_id'=>$p_user1['user_id']))->save(array('last_buy_amount'=>$p_user1['amount']));
	    $data_add['buy_order_id']=$p_user1['id'];
	    $data_add['sale_order_id']=$g_user1['id'];
	    $data_add['amount']=$p_user1['amount'];
	    $data_add['buy_id']=$p_user1['user_id'];
	    $data_add['sale_id']=$g_user1['user_id'];
	    $data_add['buy_name']=$p_user1['user_name'];
	    $data_add['sale_name']=$g_user1['user_name'];
	    $data_add['create_time']=date ( 'Y-m-d H:i:s', time () );//匹配订单添加时间
	    $data_add['status']=0;
	    // $data_add['pic']= '0';
	    // $data_add['getway_type']=$g_user1['get_way'];
	    // $data_add['account_info']=$g_user1['account_number'];
	    $data_add['order_number']=date('YmdHis',time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	    //修改买入与卖出订单表中的订单状态
	    M('help_order')->where(array('id'=>$p_id,'matching'=>'0'))->save(array('matching'=>'1'));
	    //每次匹配都修正一次总单子的匹配状态
	    $pareantorderid=M('help_order')->where(array('id'=>$p_id))->getField('parent_id');
	    M('help_order')->where(array('id'=>$pareantorderid))->save(array('matching'=>'1'));
	    M('askhelp_order')->where(array('id'=>$g_id,'matching'=>'0'))->save(array('matching'=>'1'));
	    //每次匹配都修正一次卖出总单子的匹配状态
	    $pareantsaleorderid=M('askhelp_order')->where(array('id'=>$g_id))->getField('parent_id');
	    M('askhelp_order')->where(array('id'=>$pareantsaleorderid))->save(array('matching'=>'1'));
	    if(M('match_order')->add($data_add)){
	        //发送短信提醒
	        // vendor("Sendsms.sendsms");
	        // $send = new \Sendsms();
	        $get_user=M('user')->where(array('user_id'=>$g_user1['user_id']))->find();
	        if($get_user['user_phone']){
	        	$mes = sendSms22($get_user['user_phone'],"亲爱的会员，您的卖出订单".$g_user1['order_number']."已匹配，请等待对方打款。");
	        }
	        $log2 = array(
	            'user_id'=>$g_user1['user_id'],
	            // 'content'=>$g_user1['user_name']."您好,您卖出的".$g_user1['amount']."的订单,已经匹配成功!",
	            'content'=>"亲爱的会员,您的卖出订单".$g_user1['order_number']."已匹配,请等待对方打款.",
	            'createtime'=>time()
	        );
	        M('UserNotice')->data($log2)->add();
	        $put_user=M('user')->where(array('user_id'=>$p_user1['user_id']))->find();
	        if($put_user['user_phone']){
	        	$mes = sendSms22($put_user['user_phone'],"亲爱的会员，您的买入订单".$p_user1['order_number']."已匹配，请在规定的时间内完成打款操作。");
	        }
	        $log1 = array(
	            'user_id'=>$p_user1['user_id'],
	            // 'content'=>$p_user1['user_name']."您好,您买入".$p_user1['amount']."的订单,已经匹配成功!",
	            'content'=>"亲爱的会员,您的买入订单".$p_user1['order_number']."已匹配,请在规定时间内完成打款操作.",
	            'createtime'=>time()
	        );
	        M('UserNotice')->data($log1)->add();
	        return true;
	    }else{
	        return false;
	    }
	}
	/*
	之前的auto_match_r函数,此处改为old_auto_match_r
	*/
	function old_auto_match_r($where=array()){

		$settings = include( $_SERVER['DOCUMENT_ROOT'] . '/User/Home/Conf/settings.php' );

		/* if(!$settings['auto_match']){
			return;
		} */
		$where = $where;
		$where['zt'] = 0;

		$tgbz_list = M('tgbz')->where ($where)->select();
		if(empty($tgbz_list)) return;
		$jsbz_list = M('jsbz')->where ($where)->select();
		if(empty($tgbz_list)) return;

		$pipeit = 0;
    	foreach($tgbz_list as $key=>$val){
			foreach($jsbz_list as $key1=>$val1){

				//if条件为金币数量相等，切用户名不同.z
				if($val['jb']==$val1['jb']&&$val['user']<>$val1['user']){//如果匹配成功处理

					if(ppdd_add($val['id'],$val1['id'])){

						unset($tgbz_list[$key],$jsbz_list[$key1]);
						++$pipeit;
						M('tgbz')->where(array('id'=>$val['id']))->save(array('cf_ds'=>'1'));
						break;
					}
				}

			}

    	}

		$pipeitss = $pipeits = $pipeit;

		reset($tgbz_list);
		print(' ');
		while(list($key,$val) = each($tgbz_list)){
			echo '11';
			ob_flush();
			flush();
		//foreach($tgbz_list as $key=>$val){
			if(empty($jsbz_list) || empty($tgbz_list)) return;

			$sum = 0;
			$arr = array();

			foreach($jsbz_list as $key1=>$val1){

				if($val['user']<>$val1['user']){
					$sum += $val1['jb'];
					$arr[$key1] = $val1;
					if($val['jb']<=$sum) break;
				}
			}

			//接受帮助金额不小于提供帮助金额
			if($val['jb']<=$sum){

				foreach($arr as $k=>$v){

					$val['jb'] = $val['jb']-$v['jb'];

					if($val['jb']>=0){

						$id = $val['id'];

						if($val['jb']>0){
							//已匹配
							$data = $val;
							$data['jb'] = $v['jb'];
							$data['user_nc'] = '一';
							unset($data['id']);
							$id = M('tgbz')->add($data);

							//未匹配
							$data2 = $val;
							$data2['jb'] = $val['jb'];
							$data2['user_nc'] = '七';
							unset($data2['id']);
							$id2 = M('tgbz')->add($data2);

							M('tgbz')->where(array('id'=>$val['id']))->delete();
						}

							unset($jsbz_list[$k],$tgbz_list[$key]);
							array_push($tgbz_list,$data2);
							$tgbz_list = array_values($tgbz_list);

						if(ppdd_add($id,$v['id'],'91')){

							++$pipeits;
							M('tgbz')->where(array('id'=>$id))->save(array('cf_ds'=>'1'));

						}
					}else{

						/* //插入新tgbz
						$data = $val;
						$data['jb'] = $v['jb']-abs($val['jb']);
						$data['user_nc'] = '二';
						unset($data['id']);
						$id = M('tgbz')->add($data); */

						//新未匹配jsbz
						$data2 = $v;
						$data2['jb'] = abs($val['jb']);
						$data['user_nc'] = '三';
						unset($data2['id']);
						$id2 = M('jsbz')->add($data2);

						//新匹配jsbz
						$data3 = $v;
						$data3['jb'] = $v['jb']-abs($val['jb']);
						$data['user_nc'] = '四';
						unset($data3['id']);
						$id3 = M('jsbz')->add($data3);

						$data2['id'] = $id2;


						//删除旧订单
						M('jsbz')->where(array('id'=>$v['id']))->delete();
						//M('tgbz')->where(array('id'=>$val['id']))->delete();

						if(ppdd_add(current($tgbz_list)['id'],$id3,'92')){

							unset($jsbz_list[$k],$tgbz_list[$key]);

							array_unshift($jsbz_list,$data2);
							++$pipeitss;
							M('tgbz')->where(array('id'=>$id))->save(array('cf_ds'=>'1'));
							break;
						}
					}
				}
				echo '22';
			ob_flush();
			flush();
			}else{
				echo '33';
			ob_flush();
			flush();
				foreach($arr as $k=>$v){

					$val['jb'] = $val['jb']-$v['jb'];

					$data = $val;
					$data['jb'] = $v['jb'];
					$data['user_nc'] = '五';
					unset($data['id']);

					$id = M('tgbz')->add($data);

					if(ppdd_add($id,$v['id'],'93')){

						unset($jsbz_list[$k],$tgbz_list[$key]);
						++$pipeits;
						M('tgbz')->where(array('id'=>$id))->save(array('cf_ds'=>'1'));

					}
				}

				M('tgbz')->where(array('id'=>$val['id']))->delete();
				$data = $val;
				$data['jb'] = $val['jb'];
				$data['user_nc'] = '六';
				unset($data['id']);

				$id = M('tgbz')->add($data);
				$data['id'] = $id;
				array_push($tgbz_list,$data2);
				$tgbz_list = array_values($tgbz_list);

			}
			unset($tgbz_list[$key]);
		}
    }

function sfjhff($r) {
	$a = array("正常用户", "已激活（禁用）","未激活");
	return $a[$r];
}





function tgbz_zd_cl($id){


		$tgbzuser=M('tgbz')->where(array('id'=>$id,'zt'=>'0'))->find();

		if($tgbzuser['zffs1']=='1'){$zffs1='1';}else{$zffs1='5';}
		if($tgbzuser['zffs2']=='1'){$zffs2='1';}else{$zffs2='5';}
		if($tgbzuser['zffs3']=='1'){$zffs3='1';}else{$zffs3='5';}
		$User = M ( 'jsbz' ); // 實例化User對象

		$where['zffs1']  = $zffs1;
		$where['zffs2']  = $zffs2;
		$where['zffs3']  = $zffs3;
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$map['zt']=0;

		$count = $User->where ( $map )->select(); // 查詢滿足要求的總記錄數
		return $count;



}






function jsbz_jb($id){


	$tgbzuser=M('jsbz')->where(array('id'=>$id))->find();


	return $tgbzuser['jb'];



}

function tgbz_jb($id){


	$tgbzuser=M('tgbz')->where(array('id'=>$id))->find();


	return $tgbzuser['jb'];



}

                //提供接受帮助
function ppdd_add($p_id,$g_id){


	 $g_user1 = M('jsbz')->where(array('id'=>$g_id,'zt'=>'0'))->find();
	 $p_user1=M('tgbz')->where(array('id'=>$p_id))->find();



	 M('user')->where(array('UE_account'=>$p_user1['user']))->save(array('pp_user'=>$g_user1['user']));
	 M('user')->where(array('UE_account'=>$g_user1['user']))->save(array('pp_user'=>$p_user1['user']));





    	      // echo $g_user['id'].'<br>';
    		    $data_add['p_id']=$p_user1['id'];
    		    $data_add['g_id']=$g_user1['id'];
    		    $data_add['jb']=$g_user1['jb'];
    		    $data_add['p_user']=$p_user1['user'];
    		    $data_add['g_user']=$g_user1['user'];
    		    $data_add['date']=date ( 'Y-m-d H:i:s', time () );
    		    $data_add['zt']='0';
    		    $data_add['pic']= '0';
    		    $data_add['zffs1']=$p_user1['zffs1'];
    		    $data_add['zffs2']=$p_user1['zffs2'];
    		    $data_add['zffs3']=$p_user1['zffs3'];
    		    M('tgbz')->where(array('id'=>$p_id,'zt'=>'0'))->save(array('zt'=>'1'));
    		    M('jsbz')->where(array('id'=>$g_id,'zt'=>'0'))->save(array('zt'=>'1'));
				M('user_jj')->where(array('tgbz_id'=>$p_id))->save(array('zt'=>3));
    		   // echo $p_user1['user'].'<br>';
    		    if($idz =M('ppdd')->add($data_add)){
    		    	//查询接受方用户信息
					M('user_jj')->where(array('tgbz_id'=>$p_id))->save(array('r_id'=>$idz));
					$get_user=M('user')->where(array('UE_account'=>$g_user1['user']))->find();
					if($get_user['ue_phone']) sendSMS($get_user['ue_phone'],"您好！您申请帮助的资金：".$g_user1['jb']."元，已匹配成功，请登录网站查看匹配信息！【测试短信】");
    		    	return true;
    		    }else{
    		    	return false;
    		    }


}
function diffBetweenTwoDays($day1, $day2)
 {
 	$second1 = $day1;
 	$second2 = $day2;

	/* $second1 = strtotime($day1);
 	$second2 = strtotime($day2); */

 	if ($second1 < $second2) {
 		$tmp = $second2;
 		$second2 = $second1;
 		$second1 = $tmp;
 	}
 	return ($second1 - $second2) / 5;
 	//return ($second1 - $second2) / 86400;
 }

 function diffBetweenTwoDays1 ($day1, $day2)
 {
 	$second1 = strtotime($day1);
 	$second2 = strtotime($day2);

 	if ($second1 < $second2) {
 		$tmp = $second2;
 		$second2 = $second1;
 		$second1 = $tmp;
 	}
 	return ($second1 - $second2) / 86400;
 }


 //利息计算,settings里面加上一个jixi_fangshi来判定是排单计息还是打款后计息
 function user_jj_lx($var){
 	//引入分成文件
 //$settings = include( dirname( dirname( __FILE__ ) ) . '/Conf/settings.php' );
 $settings = include( dirname( APP_PATH ) . '/User/Home/Conf/settings.php' );
 $proall = M('user_jj')->where(array('id'=>$var))->find();

  //对计息方式进行判断
   if($settings['jixi_fangshi']==1){
   	//打款后计息方式
  	$ppdd_hk=M('ppdd')->where(array('p_user'=>$proall['user'],'id'=>$proall['r_id']))->find();
  	$hktime=$ppdd_hk['date_hk'];

  	if(!empty($hktime))
  	{
  	$aab=strtotime($hktime);
 	$NowTime=date('Y-m-d',$aab);
 	$NowTime2=date('Y-m-d',time());
 	$day1 = $NowTime;
 	$day2 = $NowTime2;
 	$diff = diffBetweenTwoDays1($day1, $day2);//提供帮助时间到现在的时间间隔
       if($diff>$settings['knock_out_day_diff']){
 		$diff =$settings['knock_out_day_diff'];
 	    }
 	    if($diff>$settings['withdraw_day_diff']){
         $diff = $diff - $settings['withdraw_day_diff'];
		$cold=$settings['withdraw_day_diff']*1/100;
		$diff = $diff*floatval($settings['in_queue_interest'])/100;
		return $proall['jb']*$diff+$proall['jb']*$cold;
 	    }

	}else
	{
		return 0;
	}


  }else{
     //进行排单后计息,获取排单时间
  	//$proall1 = M('user_jj')->where(array('id'=>$var))->find();
  	$pdtime=$proall['date'];
  	$aac=strtotime($pdtime);
  	$NowTime3=date('Y-m-d',$aac);
  	$NowTime4=date('Y-m-d',time());
  	$day3=$NowTime3;
  	$day4=$NowTime4;
  	//当前时间,获取时间差
    $diff1=diffBetweenTwoDays1($day3,$day4);
    if($diff1>$settings['knock_out_day_diff']){
 		$diff1 =$settings['knock_out_day_diff'];
 	}
    if($diff1>$settings['withdraw_day_diff']){
         $diff1 = $diff1 - $settings['withdraw_day_diff'];
		$cold=$settings['withdraw_day_diff']*1/100;
		$diff1 = $diff1*floatval($settings['in_queue_interest'])/100;
		return $proall['jb']*$diff1+$proall['jb']*$cold;
 	    }

  }


/*
	// added by skyrim
 	// purpose: custom interest rate
 	// version: v10.0
 	$ppddxx = M('ppdd')->where(array('id'=>	$proall['r_id']))->find();
 	$pay_order = M('tgbz')->where(array('id'=>$ppddxx['p_id']))->find();
 	$pdtime=$proall['date'];
 	$aac=strtotime($pdtime);
 	$NowTime=date('Y-m-d',$aac);
 	$NowTime2=date('Y-m-d',time());
 	$diff=diffBetweenTwoDays($NowTime,$NowTime2);
 	//$days = ( strtotime( date( 'Y-m-d', time() ) ) - strtotime( date( 'Y-m-d', strtotime( $pay_order['date'] ) ) ) ) / 3600 / 24;
	//$diff-=$days;
	// added ends
	//冻结期利息1%,这个是排单后的时间有几天的冻结期,利息是固定的
	if($diff<=$settings['withdraw_day_diff']){
		$cold=$diff*1/100;
		return $proall['jb']*$cold;
	}elseif($diff>$settings['withdraw_day_diff']){
		$diff = $diff - $settings['withdraw_day_diff'];
		$cold=$settings['withdraw_day_diff']*1/100;
		$diff = $diff*floatval($settings['in_queue_interest'])/100;
		return $proall['jb']*$diff+$proall['jb']*$cold;
	}

 	//$diff = $diff*floatval($settings['in_queue_interest'])/100;
 	//if
	// added ends
 	//return $proall['jb']*$diff+$proall['jb']*$cold;
*/
 }


//利息计算,settings里面加上一个jixi_fangshi来判定是排单计息还是打款后计息
 function user_jj_lx1($var){
 	//引入分成文件
 //$settings = include( dirname( dirname( __FILE__ ) ) . '/Conf/settings.php' );
 $settings = include( dirname( APP_PATH ) . '/User/Home/Conf/settings.php' );
 $proall = M('user_jj')->where(array('id'=>$var))->find();

  //对计息方式进行判断
   if($settings['jixi_fangshi']==1){
   	//打款后计息方式
  	$ppdd_hk=M('ppdd')->where(array('p_user'=>$proall['user'],'id'=>$proall['r_id']))->find();
  	$hktime=$ppdd_hk['date_hk'];

  	if(!empty($hktime))
  	{
  	$aab=strtotime($hktime);
 	$NowTime=date('Y-m-d',$aab);
 	$NowTime2=date('Y-m-d',time());
 	$day1 = $NowTime;
 	$day2 = $NowTime2;
 	$diff = diffBetweenTwoDays1($day1, $day2);//提供帮助时间到现在的时间间隔
       if($diff>$settings['knock_out_day_diff']){
 		$diff =$settings['knock_out_day_diff'];
 	    }
 	    if($diff<=$settings['withdraw_day_diff'])
 	    {
        $cold=$diff*1/100;
		return $proall['jb']*$cold;
 	    }elseif($diff>$settings['withdraw_day_diff']){
         $diff = $diff - $settings['withdraw_day_diff'];
		$cold=$settings['withdraw_day_diff']*1/100;
		$diff = $diff*floatval($settings['in_queue_interest'])/100;
		return $proall['jb']*$diff+$proall['jb']*$cold;
 	    }

	}else
	{
		return 0;
	}


  }else{
     //进行排单后计息,获取排单时间
  	//$proall1 = M('user_jj')->where(array('id'=>$var))->find();
  	$pdtime=$proall['date'];
  	$aac=strtotime($pdtime);
  	$NowTime3=date('Y-m-d',$aac);
  	$NowTime4=date('Y-m-d',time());
  	$day3=$NowTime3;
  	$day4=$NowTime4;
  	//当前时间,获取时间差
    $diff1=diffBetweenTwoDays1($day3,$day4);
    if($diff1>$settings['knock_out_day_diff']){
 		$diff1 =$settings['knock_out_day_diff'];
 	}
    if($diff1<=$settings['withdraw_day_diff'])
 	    {
        $cold=$diff1*1/100;
		return $proall['jb']*$cold;
 	    }elseif($diff1>$settings['withdraw_day_diff']){
         $diff1 = $diff1 - $settings['withdraw_day_diff'];
		$cold=$settings['withdraw_day_diff']*1/100;
		$diff1 = $diff1*floatval($settings['in_queue_interest'])/100;
		return $proall['jb']*$diff1+$proall['jb']*$cold;
 	    }

  }


/*
	// added by skyrim
 	// purpose: custom interest rate
 	// version: v10.0
 	$ppddxx = M('ppdd')->where(array('id'=>	$proall['r_id']))->find();
 	$pay_order = M('tgbz')->where(array('id'=>$ppddxx['p_id']))->find();
 	$pdtime=$proall['date'];
 	$aac=strtotime($pdtime);
 	$NowTime=date('Y-m-d',$aac);
 	$NowTime2=date('Y-m-d',time());
 	$diff=diffBetweenTwoDays($NowTime,$NowTime2);
 	//$days = ( strtotime( date( 'Y-m-d', time() ) ) - strtotime( date( 'Y-m-d', strtotime( $pay_order['date'] ) ) ) ) / 3600 / 24;
	//$diff-=$days;
	// added ends
	//冻结期利息1%,这个是排单后的时间有几天的冻结期,利息是固定的
	if($diff<=$settings['withdraw_day_diff']){
		$cold=$diff*1/100;
		return $proall['jb']*$cold;
	}elseif($diff>$settings['withdraw_day_diff']){
		$diff = $diff - $settings['withdraw_day_diff'];
		$cold=$settings['withdraw_day_diff']*1/100;
		$diff = $diff*floatval($settings['in_queue_interest'])/100;
		return $proall['jb']*$diff+$proall['jb']*$cold;
	}

 	//$diff = $diff*floatval($settings['in_queue_interest'])/100;
 	//if
	// added ends
 	//return $proall['jb']*$diff+$proall['jb']*$cold;
*/
 }




 //解冻金额
 function tgzb_jd_jb($i){
		$settings = include( dirname( APP_PATH ) . '/User/Home/Conf/settings.php' );
		//$arr = M('user_jj')->where(array('zt'=>'0'))->select();
		$map['zt'] = ['neq','1'];
		$arr = M('user_jj')->where($map)->select();
		//dump($arr);

		$jd_jb = 0;
		foreach($arr as $k=>$v){

			$jd_time = $v['date'];
			$aab=strtotime($jd_time);

			$NowTime=date('Y-m-d',$aab);
			$NowTime2=date('Y-m-d',time());

			$day1 = $NowTime;
			$day2 = $NowTime2;

			$diff = diffBetweenTwoDays1($day1, $day2);

			//dump($settings['withdraw_day_diff']);
			//dump($diff);die();

			if($diff>$settings['withdraw_day_diff']){

				$jd_jb += user_jj_lx($v['id'])+($v['jb']);

			}

		//	dump($v[ue_account]);
			//echo $i;
			/* $jd_jb = $arr['jb'];
			if($v['ue_account']){
			countSql($v['ue_account'],$i);
			} */

		}
		//echo $i;

		return $jd_jb;
	}
	//利息金额
	function tgzb_jd_jb1($i){
		$settings = include( dirname( APP_PATH ) . '/User/Home/Conf/settings.php' );
		//$arr = M('user_jj')->where(array('zt'=>'0'))->select();
		//$map['zt'] = ['neq','1'];
		$arr = M('user_jj')->select();
		//dump($arr);

		$lx_jb = 0;
		foreach($arr as $k=>$v){

			$jd_time = $v['date'];
			$aab=strtotime($jd_time);

			$NowTime=date('Y-m-d',$aab);
			$NowTime2=date('Y-m-d',time());

			$day1 = $NowTime;
			$day2 = $NowTime2;

			$diff = diffBetweenTwoDays1($day1, $day2);
			//dump($diff);
			//dump($settings['withdraw_day_diff']);
			//dump($diff);die();
			//dump(empty($diff));
			if($diff){

				$lx_jb += user_jj_lx1($v['id']);

			}

		//	dump($v[ue_account]);
			//echo $i;
			/* $jd_jb = $arr['jb'];
			if($v['ue_account']){
			countSql($v['ue_account'],$i);
			} */

		}
		//echo $i;

		return $lx_jb;
	}





function user_sfxt($var){
	if($var[c]==0){
	$zctj=0;
	$zctjuser=M('ppdd')->where(array('p_user'=>$var[a]))->select();

	foreach($zctjuser as $value){
		if($value['g_user']==$var['b']){
			$zctj=1;
		}
	}

	if($zctj==1){
		return "<span style='color:#FF0000;'>匹配过</span>";
	}else{
		return "否";
	}
	}elseif($var[c]==1){
		$zctj=0;
		$zctjuser=M('ppdd')->where(array('g_user'=>$var[a]))->select();

		foreach($zctjuser as $value){
			if($value['p_user']==$var['b']){
				$zctj=1;
			}
		}

		if($zctj==1){
			return "<span style='color:#FF0000;'>匹配过</span>";
		}else{
			return "否";
		}
	}

// 	$userxx=M('user')->where(array('UE_account'=>$var[a]))->find();
// //	M('user')->where(array('UE_account'=>$g_user1['user']))->save(array('pp_user'=>$p_user1['user']));
// if($userxx['pp_user']==$var[b]){
// 	return "<span style='color:#FF0000;'>匹配过</span>";
// }else{
// 	return "否";
// }




}

function ppdd_add2($p_id,$g_id){


	$g_user1 = M('jsbz')->where(array('id'=>$g_id))->find();
	$p_user1=M('tgbz')->where(array('id'=>$p_id,'zt'=>'0'))->find();










	// echo $g_user['id'].'<br>';
	$data_add['p_id']=$p_user1['id'];
	$data_add['g_id']=$g_user1['id'];
	$data_add['jb']=$p_user1['jb'];
	$data_add['p_user']=$p_user1['user'];
	$data_add['g_user']=$g_user1['user'];
	$data_add['date']=date ( 'Y-m-d H:i:s', time () );
	$data_add['zt']='0';
	$data_add['pic']='0';
	$data_add['zffs1']=$p_user1['zffs1'];
	$data_add['zffs2']=$p_user1['zffs2'];
	$data_add['zffs3']=$p_user1['zffs3'];
	M('tgbz')->where(array('id'=>$p_id,'zt'=>'0'))->save(array('zt'=>'1'));
	M('jsbz')->where(array('id'=>$g_id,'zt'=>'0'))->save(array('zt'=>'1'));
	M('user_jj')->where(array('tgbz_id'=>$p_id))->save(array('zt'=>3));
	// echo $p_user1['user'].'<br>';
	if($idz=M('ppdd')->add($data_add)){
		//查询支付方用户信息
		M('user_jj')->where(array('tgbz_id'=>$p_id))->save(array('r_id'=>$idz));
		$pay_user=M('user')->where(array('UE_account'=>$p_user1['user']))->find();
		if($pay_user['ue_phone']) sendSMS($pay_user['ue_phone'],"您好！您提供帮助的资金：".$p_user1['jb']."元，已匹配成功，请登录网站查看匹配信息，并打款！【测试短信】");
		return true;
	}else{
		return false;
	}


}

function ipjc($auser){

	$tgbz_user_xx=M('user')->where(array('UE_regIP'=>$auser))->count();
	//echo $ppddxx['p_id'];die;


	return $tgbz_user_xx;

}
 /*--------------------------------
功能:		HTTP接口 发送短信
说明:		http://api.sms.cn/mt/?uid=用户账号&pwd=MD5位32密码&mobile=号码&mobileids=号码编号&content=内容
官网:		ww.sms.cn
状态:		sms&stat=101&message=验证失败

	100 发送成功
	101 验证失败
	102 短信不足
	103 操作失败
	104 非法字符
	105 内容过多
	106 号码过多
	107 频率过快
	108 号码内容空
	109 账号冻结
	110 禁止频繁单条发送
	112 号码不正确
	120 系统升级
--------------------------------*/
function sendSMS100z($mobile,$content,$mobileids,$time='',$mid='')
{
	$http= 'http://api.sms.cn/mt/';
	$data = array
		(
		'uid'=>'pl12000',					//用户账号
		'pwd'=>md5('1988922pl'.'pl12000'),			//MD5位32密码,密码和用户名拼接字符
		'mobile'=>$mobile,				//号码
		'content'=>$content,			//内容
		'mobileids'=>$mobileids,		//发送唯一编号
		'encode'=>'utf8'
		);

	//$re= postSMS($http,$data);			//POST方式提交

	$re = getSMS($http,$data);		//GET方式提交

	if( strstr($re,'stat=100'))
	{
		return "发送成功!";
	}
	else if( strstr($re,'stat=101'))
	{
		return "验证失败! 状态：".$re;
	}
	else
	{
		return "发送失败! 状态：".$re;
	}
}
 //GET方式
function getSMS($url,$data='')
{
	$get='';
	while (list($k,$v) = each($data))
	{
		$get .= $k."=".urlencode($v)."&";	//转URL标准码
	}
	return file_get_contents($url.'?'.$get);
}
 //POST方式
function postSMS($url,$data='')
{
	$row = parse_url($url);
	$host = $row['host'];
	$port = $row['port'] ? $row['port']:80;
	$file = $row['path'];
	while (list($k,$v) = each($data))
	{
		$post .= rawurlencode($k)."=".rawurlencode($v)."&";	//转URL标准码
	}
	$post = substr( $post , 0 , -1 );
	$len = strlen($post);
	$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
	if (!$fp) {
		return "$errstr ($errno)\n";
	} else {
		$receive = '';
		$out = "POST $file HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;
		fwrite($fp, $out);
		while (!feof($fp)) {
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n",$receive);
		unset($receive[0]);
		return implode("",$receive);
	}
}

function sendSMS($mobiles, $content)
{

$username=urlencode(trim('yunnan'));
$password=urlencode(trim('yunnan'));
$mobiles=trim($mobiles);
//$content=urlencode(iconv( "UTF-8", "gb2312//IGNORE" , trim($_REQUEST["contents"])));
$content=urlencode(mb_convert_encoding(trim($content),"gb2312" , "utf-8"));

     $fp=Fopen("http://api.sms1086.com/api/Send.aspx?username=$username&password=$password&mobiles=$mobiles&content=$content","r");
     $ret = fgetss($fp,255);
     //echo urldecode($ret);
     Fclose($fp);
     return urldecode($ret);

}

/**
 *
 * 获取注册ip添加到user表里
 * */
function getIP()
{
    global $realip;
    if (isset($_SERVER)) {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}