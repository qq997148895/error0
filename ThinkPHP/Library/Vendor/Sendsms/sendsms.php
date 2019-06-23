<?php
//defined('IN_CART') or die;

/**
 *
 * etao设置
 * 
 */
class Sendsms {
    
    /**
     *
     * 构造函数，
     * 
     */
    
    public function __construct() {
        $this->username = "yms111";         //帐号
        $this->password = "896($xkX";         //密码
        //$mobiles  = "13939393939";  //号码
        //$content  = "测试信息";     //内容 
    }

    public function index() {
            $username=urlencode(trim($this->username));
            $password=urlencode(trim($this->password));
            $this->Query($username, $password);
    }
    
    public function my_send($phone,$post_content) {//添加短信
        //$_SESSION['SmsAuthCode']=rand(1000,9999);
        
        $mobiles=trim($phone);
        $content=urlencode(iconv( "UTF-8", "gb2312//IGNORE" , trim($post_content)));
        $re = $this->SendSms($mobiles, $content);
        
        return  $re;
        //exit(json_encode(array("statusCode"=>200,"message"=>"发送成功！",)));
        
    }
	
	function SendSms($mobiles, $content)
    {
        $username=urlencode(trim($this->username));
		$password=urlencode(trim($this->password));
        $fp=Fopen("http://120.25.66.83:9877/api/send.aspx?username=$username&password=$password&mobiles=$mobiles&content=$content","r");
        $ret = fgetss($fp,255);
        $str = urldecode($ret);
        Fclose($fp);
		return $str;
    }
       //修改密码
    function ChgPwd($username, $password,$new_password)
    {
      $fp=Fopen("http://www.sms1086.com/plan/api/ChgPwd.aspx?username=$username&password=$password&newpws=$new_password","r");
         $ret = fgetss($fp,255);
         echo urldecode($ret);
         Fclose($fp);
    }
      //查询余额
    function Query($username, $password)
    {
      $fp=Fopen("http://www.sms1086.com/plan/Api/Query.aspx?username=$username&password=$password","r");
         $ret = fgetss($fp,255);
      //var_dump($ret);die;
         echo urldecode($ret);
         Fclose($fp);
    }
}
    // $username = "test";         //帐号
    // $password = "111" ;         //密码
    // $mobiles  = "13939393939";  //号码
    // $content  = "测试信息";     //内容 
    /*
    $username=urlencode(trim($_REQUEST["username"]));
    $password=urlencode(trim($_REQUEST["pwd"]));
    $mobiles=trim($_REQUEST["mobiles"]);
    $content=urlencode(iconv( "UTF-8", "gb2312//IGNORE" , trim($_REQUEST["contents"])));

    SendSms($username, $password,$mobiles, $content);*/
        //发送短信
    
?>
