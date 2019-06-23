<?php
namespace Adminlmcq\Controller;
use Think\Controller;
use Common\Controller\AdminlmcqBaseController;
class DbController extends AdminlmcqBaseController {
  	//备份
    public function index(){
		$dir = $_SERVER["DOCUMENT_ROOT"];
        if(IS_POST){
			if(!is_dir($dir)){
				mkdir($dir);
			}
			$filename = date("YmdHis",time());
			$cmd = 'mysqldump.exe -u'.C("DB_USER").' -p'.C("DB_PWD").' '.C("DB_NAME").' > '.$dir.$filename.'.sql';//linux系统
			//$cmd = '"D:\phpStudy\MySQL\bin\mysqldump" -u'.C("DB_USER").' -p'.C("DB_PWD").' '.C("DB_NAME").' > '.$dir.$filename.'.sql';//windows系统
			//dump($cmd);die;."/db_backup/"
			$res = exec($cmd,$output,$status);
			//$res = passthru($cmd,$status);
			//$res = system($cmd,$output);"C:\Program Files\phpStudy\MySQL\bin\mysqldump"
			if($status){
				$this->error("备份失败");
			}else{
				$this->success("备份成功");
			}
        }
		$list = glob($dir."*.sql");
		foreach($list as $k=>$v){
			$v1 = explode(".",$v);
			$time = strtotime(str_replace($dir,'',$v1[0]));
			if(strlen($time) != 10){
				continue;
			}
			$list[$k] = date("Y-m-d H:i:s",$time);
		}
		$this->assign("list",$list);
		$this->display("Index/data");
    }
	//删除备份
	public function del(){
		$data = I("item");
		if(!empty($data)){
			$dir = $_SERVER["DOCUMENT_ROOT"];
			$file = $dir.date("YmdHis",strtotime($data)).".sql";
			if(file_exists($file)){
				if(unlink($file)){
					$this->success("删除成功");
				}else{
					$this->error("删除失败");
				}
			}
			
		}
	}
	//恢复备份
	public function recovery(){
		$dir = $_SERVER["DOCUMENT_ROOT"];
		$data = I("item");
		if(!empty($data)){
			$file = $dir.date("YmdHis",strtotime($data)).".sql";
			$cmd = 'mysql.exe -u'.C("DB_USER").' -p'.C("DB_PWD").' '.C("DB_NAME").' < '.$file;//liux系统
			//$cmd = '"D:\phpStudy\MySQL\bin\mysql" -u'.C("DB_USER").' -p'.C("DB_PWD").' '.C("DB_NAME").' < '.$file;//window系统
			//$res = exec($cmd,$output,$status);
			$res = system($cmd,$output);
			
			//$res = passthru($cmd,$output);
			
			if($output){
				
				$this->error("恢复失败");
			}else{
				
				$this->success("恢复成功");
			}
		}
	}	
}