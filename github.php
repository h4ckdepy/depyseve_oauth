<?php

namespace app\index\controller;
use app\index\service\BaseService;
use think\facade\Db;

class Githublogin extends Base
{
/**
 * function: actionIndex
 * author: 小北 http://blog.happysec.cn
 * 请求授权码
 */
	public function index()
	{
	    $redirect_uri = "https://blog.happysec.cn/index/Githublogin/actionCallback";//回调地址
	    $client_id = "";//填写github登记表是返回的Client ID
	    $_url = "https://github.com/login/oauth/authorize?client_id=" . $client_id . "&state=1&redirect_uri=" . $redirect_uri;
	    header("location:$_url");
	}
	/**
	 * function: actionCallback
	 * author: 小北 http://blog.happysec.cn
	 * 请求令牌并回调用户信息
	 */
	public function actionCallback()
	{
	    $code = $_GET['code'];//GitHub回调返回的授权码
	    $client_id = "";//填写github登记表是返回的Client ID
	    $client_secret = "";//填写github登记表是返回的Client Secret
	    $_url = "https://github.com/login/oauth/access_token?client_id=" . $client_id . "&client_secret=" . $client_secret . "&code=" . $code;
	    $result = file_get_contents($_url);
	    $data = [];
	    parse_str($result, $data);//字符串解析到变量
	    //GitHub返回的令牌
	    $access_token = isset($data['access_token']) ? $data['access_token'] : '';
	    if ($access_token) {
	        //根据令牌获取到用户信息
	        $info_url = "https://api.github.com/user?access_token=" . $access_token;
	        $res = json_decode($this->curl($info_url),true);
	        
			if($res['login'] != 'h4ckdepy'){
				echo "该账户没有权限登陆本站,三秒后跳转主页。";
				header("refresh:3;url=/");
			}
			else{
				$where['a.user'] = '';
				$where['a.pwd']  = md5(''.config('my.password_secrect'));
				try{
				  //这里模拟数据库登陆后台,可根据自己的业务修改
				}catch(\Exception $e){
					abort(config('my.error_log_code'),$e->getMessage());
				}
			
				if(!($info['status']) || !($info['role_status'])){
					echo "该账户已被禁用,三秒后跳转主页。";
					header("refresh:3;url=/");
				}
	        
				$info['nodes'] = db("access")->where('role_id','in',$info['user_role_ids'])->column('purviewval','id');
				$info['nodes'] = array_unique($info['nodes']);
		        session('admin', $info);
				session('admin_sign', data_auth_sign($info));
				event('LoginLog',$info);	//写入登录日志
				header("location:https://blog.happysec.cn/admin");
			}
	        // echo $res['login'];
	    }
	}
	/**
	 * function: curl
	 * author: 小北 http://blog.happysec.cn
	 * 设置请求头和响应头（github API接口需要）
	 */
	public function curl($url)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
	    $header[] = "Content-type: application/x-www-form-urlencoded";
	    $user_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36";
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
	    curl_setopt($ch, CURLOPT_HEADER, false);
	    // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
	    curl_setopt($ch, CURLOPT_NOBODY, false);
	    // 使用上面定义的$user_agent
	    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    $res = curl_exec($ch);
	    
	    curl_close($ch);
	    return $res;
	}
	

}
