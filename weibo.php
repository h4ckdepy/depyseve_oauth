<?php

namespace app\index\controller;
use app\index\service\BaseService;
use think\facade\Db;
use think\exception\ValidateException;

class Weibologin extends Base
{

	public function index()
	{
	    $redirect_uri = config('depyseve.weibo_redirect_uri');
	    $client_id = config('depyseve.weibo_client_id');
	    $_url = "https://api.weibo.com/oauth2/authorize?client_id=$client_id&response_type=code&redirect_uri=$redirect_uri";
	    header("location:$_url");
	}
	
	public function actionCallback()
	{
	    $redirect_uri = config('depyseve.weibo_redirect_uri');
	    $client_id = config('depyseve.weibo_client_id');
		$app_secret=config('depyseve.weibo_app_secret');
	    $code = $_GET['code'];
	    $url="https://api.weibo.com/oauth2/access_token?client_id=$client_id&client_secret=$app_secret&grant_type=authorization_code&redirect_uri=$redirect_uri&code=$code";
	    $json=$this->send_post($url, '');
		$res = json_decode($json,true);
		$access_token=$res['access_token'];
		$json= $this->send_post("https://api.weibo.com/oauth2/get_token_info?access_token=".$access_token,'');
		$res = json_decode($json,true);
		$uid=$res['uid'];
		$api_getinfo="https://api.weibo.com/2/users/show.json?uid=".$uid."&access_token=".$access_token;
		$json=file_get_contents($api_getinfo);
		$res = json_decode($json,true);
		$weibo=$res['screen_name'];
		$where['a.weibo'] = $weibo;
		try{
			$info = db('user')->alias('a')->join('role b', 'a.role_id in(b.role_id)')->field('a.user_id,a.name,a.user as username,a.status,a.role_id as user_role_ids,b.role_id,b.name as role_name,b.status as role_status')->where($where)->find();
		}catch(\Exception $e){
			abort(config('my.error_log_code'),$e->getMessage());
		}
		if($info == null){
			echo "当前微博用户未绑定后台用户,无法登陆,正在回到上一页。";
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
