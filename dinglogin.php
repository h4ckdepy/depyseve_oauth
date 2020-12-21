<?php

class Dinglogin extends Base
{

	public function index()
	{
	    $code=$_GET['code'];
	    $state=$_GET['state'];
	    if($code!=null && $state!=null){
	        date_default_timezone_set('Asia/Shanghai');
	        $timestamp = $this->getMillisecond();
	        $appId="Your APPID";
    	    $appSecret="YOUR APPSECRET";
            $s = hash_hmac('sha256',$timestamp, $appSecret, true);
            $signature = base64_encode($s);
            $api="https://oapi.dingtalk.com/sns/getuserinfo_bycode?accessKey=$appId&timestamp=".$timestamp."&signature=$signature";
            $post_data=json_encode(array("tmp_auth_code"=>$code));
            $json=$this->http_post_json($api, $post_data);
            $res=json_decode($json,true);

		    if($res['user_info']['openid']!=null){
		        //赋值
		    try{
	    	   //登录逻辑
		    }else{
           header("Refresh:0");
		    }

	    }else{
	        exit('Error!');
	    }
        
        
	}
	
	public function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }
    
	public function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

}
