<?php

namespace app\index\controller;
use app\index\service\BaseService;
use think\facade\Db;
use think\exception\ValidateException;

class Giteelogin extends Base
{

	public function index()
	{
	    $redirect_uri = config('depyseve.gitee_redirect_uri');
	    $client_id = config('depyseve.gitee_client_id');
	    $_url = "https://gitee.com/oauth/authorize?client_id=$client_id&redirect_uri=$redirect_uri&response_type=code&scope=user_info";
	    header("location:$_url");
	}
	
	public function actionCallback()
	{
	    $code = $_GET['code'];
	    $redirect_uri = config('depyseve.gitee_redirect_uri');
	    $client_id = config('depyseve.gitee_client_id');
	    $client_secret = config('depyseve.gitee_client_secret');
	    $_url="https://gitee.com/oauth/token?grant_type=authorization_code&code=$code&client_id=$client_id&redirect_uri=$redirect_uri&client_secret=$client_secret";
	    $result=$this->send_post($_url, '');
		  $res = json_decode($result,true);
	  	$access_token=$res['access_token'];
		  $api_info="https://gitee.com/api/v5/user?access_token=".$access_token;
		  $res=file_get_contents($api_info);
		  $res = json_decode($res,true);
		  	//$res['login'];  获得的gitee账号 用于比对数据库中的绑定用户
		  $where['a.gitee'] = $res['login'];
		  try{
	    	$info = db('user')->alias('a')->join('role b', 'a.role_id in(b.role_id)')->field('a.user_id,a.name,a.user as username,a.status,a.role_id as user_role_ids,b.role_id,b.name as role_name,b.status as role_status')->where($where)->find();
	  	}catch(\Exception $e){
		  	abort(config('my.error_log_code'),$e->getMessage());
      }
      if($info == null){
        echo "当前Gitee用户未绑定后台用户,无法登陆,正在回到上一页。";
        $refer=$_SERVER['HTTP_REFERER'];
        header("refresh:3;url=$refer");
      }else{
        if(!($info['status']) || !($info['role_status'])){
          echo "该账户已被禁用,三秒后跳转上一页。";
        $refer=$_SERVER['HTTP_REFERER'];
        header("refresh:3;url=$refer");
        }else{
          $info['nodes'] = db("access")->where('role_id','in',$info['user_role_ids'])->column('purviewval','id');
          $info['nodes'] = array_unique($info['nodes']);
          session('admin', $info);
          session('admin_sign', data_auth_sign($info));
          event('LoginLog',$info);	//写入登录日志
          header("location:https://blog.happysec.cn/admin");
        }
      }
    }
	
	public function send_post($url, $post_data) {
	    $postdata = http_build_query($post_data);
	    $options = array(
	    'http' => array(
	        'method' => 'POST',
	        'header' => 'Content-type:application/x-www-form-urlencoded',
	        'content' => $postdata,
	        'timeout' => 15 * 60 // 超时时间（单位:s）
	    )
	  );
	    $context = stream_context_create($options);
	    $result = file_get_contents($url, false, $context);
	    return $result;
	}

}
