<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RAM_REST_Weixin_Controller  extends WP_REST_Controller{

    public function __construct() {
        $this->namespace     = 'watch-life-net/v1';
        $this->resource_name = 'weixin';
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name.'/qrcodeimg', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'getWinxinQrcodeImg' ),
                'permission_callback' => array( $this, 'get_qrcodeimg_permissions_check' ),
                'args'               => array(              
                    'postid' => array(
                        'required' => true
                    ),                    
                    'path' => array(
                        'required' => true
                    )
                )
                 
            ),            
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );


        register_rest_route( $this->namespace, '/' . $this->resource_name.'/sendmessage', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'sendmessage' ),
                'permission_callback' => array( $this, 'send_message_permissions_check' ),
                'args'               => array(              
                    'openid' => array(
                        'required' => true
                    ),                    
                    'template_id' => array(
                        'required' => true
                    ),
                    'postid' => array(
                        'required' => true
                    ),
                    'form_id' => array(
                        'required' => true
                    ),
                    'total_fee' => array(
                        'required' => true
                    ),
                    'flag' => array(
                        'required' => true
                    ),
                    'fromUser' => array(
                        'required' => true
                    )
                    
                )
                 
            ),            
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name.'/getopenid', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'getOpenid' ),
                'permission_callback' => array( $this, 'get_openid_permissions_check' ),
                'args'               => array(              
                    'js_code' => array(
                        'required' => true
                    ),                    
                    'encryptedData' => array(
                        'required' => true
                    ),
                    'iv' => array(
                        'required' => true
                    ),
                    'avatarUrl' => array(
                        'required' => true
                    ),
                    'nickname' => array(
                        'required' => true
                    )
                )
                 
            ),            
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name.'/getuserinfo', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getUserInfo' ),
                'permission_callback' => array( $this, 'get_userInfo_permissions_check' ),
                'args'               => array(              
                    'openid' => array(
                        'required' => true
                    )
                )
                 
            ),            
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name.'/updateuserinfo', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'updateUserInfo' ),
                'permission_callback' => array( $this, 'update_userInfo_permissions_check' ),
                'args'               => array(              
                    'openid' => array(
                        'required' => true
                    ),
                    'avatarUrl' => array(
                        'required' => true
                    ),
                    'nickname' => array(
                        'required' => true
                    )
                )
                 
            ),            
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

    }

    function updateUserInfo($request)
    {
        $openId =$request['openid'];
        $nickname=empty($request['nickname'])?'':$request['nickname'];
        $nickname=filterEmoji($nickname);
        $_nickname=base64_encode($nickname);          
		$_nickname=strlen($_nickname)>49?substr($_nickname,49):$_nickname;
        $avatarUrl=empty($request['avatarUrl'])?'':$request['avatarUrl']; 
        $user = get_user_by( 'login', $openId);
        if(empty($user))
        {
            return new WP_Error( 'error', '??????????????????' , array( 'status' => 500 ) );
        }     
        $userdata =array(
            'ID'            => $user->ID,
            'first_name'	=> $nickname,
            'nickname'      => $nickname,
            'user_nicename' => $_nickname,
            'display_name'  => $nickname,
            'user_email'    => $openId.'@weixin.com'
        );
        $userId =wp_update_user($userdata);
        if(is_wp_error($userId)){
            return new WP_Error( 'error', '??????wp???????????????' , array( 'status' => 500 ) );
        } 
                
        update_user_meta($userId,'avatar',$avatarUrl);
        update_user_meta($userId,'usertype',"weixin","weixin");

        $userLevel= getUserLevel($userId);
        $result["code"]="success";
        $result["message"]= "????????????";
        $result["status"]="200";
        $result["openid"]=$openId;
        $result["userLevel"]=$userLevel;            
        $response = rest_ensure_response($result);
        return $response;

    }

    function getUserInfo($request)
    {
      
        $openId =$request['openid'];
        $_user = get_user_by( 'login', $openId);  
        if(empty($_user ))
        {
            return new WP_Error( 'error', '??????????????????', array( 'status' => 500 ) );
       
        }
        else{

            $user['nickname']=$_user->display_name;
            $avatar= get_user_meta($_user->ID, 'avatar', true );
            if(empty($avatar))
            {
                $avatar = plugins_url()."/".REST_API_TO_MINIPROGRAM_PLUGIN_NAME."/includes/images/gravatar.png";
            }

            $userLevel=getUserLevel($_user->ID);
            $user['userLevel']=$userLevel;            
            $user['avatar']=$avatar;            
            $result["code"]="success";
            $result["message"]= "????????????????????????";
            $result["status"]="200";
            $result["user"]=$user;
            $response = rest_ensure_response($result);
            return $response;

        }
    }
    function getOpenid($request)
    {
        $js_code= $request['js_code'];
        $encryptedData=$request['encryptedData'];
        $iv=$request['iv'];
        $avatarUrl=$request['avatarUrl'];
        $nickname=empty($request['nickname'])?'':$request['nickname'];
        $appid = get_option('wf_appid');
        $appsecret = get_option('wf_secret');
        if(empty($appid) || empty($appsecret) ){
            return new WP_Error( 'error', 'appid???appsecret??????', array( 'status' => 500 ) );
        }
        else
        {        
            $access_url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$js_code."&grant_type=authorization_code";
            $access_result = https_request($access_url);
            if($access_result=='ERROR') {
                return new WP_Error( 'error', 'API?????????' . json_encode($access_result), array( 'status' => 501 ) );
            } 
            $api_result  = json_decode($access_result,true);            
            if( empty( $api_result['openid'] ) || empty( $api_result['session_key'] )) {
                return new WP_Error('error', 'API?????????' . json_encode( $api_result ), array( 'status' => 502 ) );
            }            
            $openId = $api_result['openid']; 
            $sessionKey = $api_result['session_key'];                    
            // $access_result =decrypt_data($appid, $sessionKey,$encryptedData, $iv, $data);                   
            // if($access_result !=0) {
            //     return new WP_Error( 'error', '???????????????' . $access_result, array( 'status' => 503 ) );
            // }
            $userId=0;           
            // $data = json_decode( $data, true );  
            $nickname=filterEmoji($nickname);         
            $_nickname=base64_encode($nickname);          
		    $_nickname=strlen($_nickname)>49?substr($_nickname,49):$_nickname;
            // $avatarUrl= $data['avatarUrl'];             
            if(!username_exists($openId) ) {                
                $new_user_data = apply_filters( 'new_user_data', array(
                    'user_login'    => $openId,
                    'first_name'	=> $nickname ,
                    'nickname'      => $nickname,                    
                    'user_nicename' => $_nickname,
                    'display_name'  => $nickname,
                    'user_pass'     => $openId,
                    'user_email'    => $openId.'@weixin.com'
                ) );                
                $userId = wp_insert_user( $new_user_data );			
                if ( is_wp_error( $userId ) || empty($userId) ||  $userId==0 ) {
                    return new WP_Error( 'error', '??????wordpress???????????????', array( 'status' => 500 ) );				
                }

                update_user_meta( $userId,'avatar',$avatarUrl);
                update_user_meta($userId,'usertype',"weixin");

            }            
            else{
                $user = get_user_by( 'login', $openId);     
                $userdata =array(
                    'ID'            => $user->ID,
                    'first_name'	=> $nickname,
                    'nickname'      => $nickname,
                    'user_nicename' => $_nickname,
                    'display_name'  => $nickname,
                    'user_email'    => $openId.'@weixin.com'
                );
                $userId =wp_update_user($userdata);
                if(is_wp_error($userId)){
                    return new WP_Error( 'error', '??????wp???????????????' , array( 'status' => 500 ) );
                }             
                update_user_meta($userId,'avatar',$avatarUrl);
                update_user_meta($userId,'usertype',"weixin","weixin");
                
                  
            }
            $userLevel= getUserLevel($userId);
            $result["code"]="success";
            
            $result["message"]= "????????????????????????";
            $result["status"]="200";
            $result["openid"]=$openId;
            $result["userLevel"]=$userLevel;            
            $response = rest_ensure_response($result);
            return $response; 
        }  
    }

    function sendmessage($request)
    {
      $openid= $request['openid'];
      $template_id=$request['template_id'];
      $postid=$request['postid'];
      $form_id=$request['form_id'];
      $total_fee=$request['total_fee'];
      $flag=$request['flag'];
      $fromUser =$request['fromUser'];
      $parent=0;
      if (isset($request['parent'])) {
          $parent =(int)$request['parent'];
      }

        $appid = get_option('wf_appid');
        $appsecret = get_option('wf_secret');
        $page='';
        if($flag =='1'  || $flag=='2' )
        {
            $total_fee= $total_fee.'???';
        }

        
        if($flag=='1' || $flag=='3' )
        {
            $page='pages/detail/detail?id='.$postid;

        }
        elseif($flag=='2')
        {
            $page='pages/about/about';
        }

        if(empty($appid) || empty($appsecret) )
        {
                $result["code"]="success";
                $result["message"]= "appid  or  appsecret is  empty";
                $result["status"]="500";                   
                return $result;
        }
        else
        {
        
            $access_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
            $access_result = https_request($access_url);
            if($access_result !="ERROR")
            {
                $access_array = json_decode($access_result,true);
                if(empty($access_array['errcode']))
                {
                    $access_token = $access_array['access_token']; 
                    $expires_in = $access_array['expires_in'];
                    $data = array();
                    $data1 = array(
                            "keyword1"=>array(
                            "value"=>$total_fee,                     
                             "color" =>"#173177"
                            ),
                            "keyword2"=>array(
                                "value"=>'??????????????????,????????????,?????????????????????.',
                                "color"=> "#173177"
                            )
                        );  

                     date_default_timezone_set('PRC');
                     $datetime =date('Y-m-d H:i:s');
                     $data2 = array(
                            "keyword1"=>array(
                            "value"=>$fromUser,                     
                             "color" =>"#173177"
                            ),
                            "keyword2"=>array(
                                "value"=>$total_fee,
                                "color"=> "#173177"
                            ),
                            "keyword3"=>array(
                                "value"=>$datetime,
                                "color"=> "#173177"
                            )
                        );  


                    if($flag=='1' || $flag=='2' )
                    {
                        
                       $postdata['data']=$data1;

                    }
                    elseif ($flag=='3') {
                       
                        $postdata['data']=$data2;
                        
                    }

                    $postdata['touser']=$openid;
                    $postdata['template_id']=$template_id;
                    $postdata['page']=$page;
                    $postdata['form_id']=$form_id;
                    $postdata['template_id']=$template_id;
                    

                    $url ="https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$access_token;

                    $access_result = $this->https_curl_post($url,$postdata,'json');

                    if($access_result !="ERROR"){
                        $access_array = json_decode($access_result,true);
                        if($access_array['errcode'] =='0')
                        {

                            
                            if($parent  !=0)
                            {
                                $delFlag=delete_comment_meta($parent,"formId",$form_id);
                                if($delFlag)
                                {
                                  $result["message"]= "??????????????????(formId??????)";  
                                }
                                else
                                {
                                   $result["message"]= "??????????????????(formId????????????)"; 
                                }
                                
                            }
                            else
                            {
                                $result["message"]= "????????????????????????";
                            }
                            $result["code"]="success";
                            $result["status"]="200";                   
                            

                        }
                        else

                        {
                            $result["code"]=$access_array['errcode'];
                            $result["message"]= $access_array['errmsg'];
                            $result["status"]="500";
                            return $result;
                        }

                        
                    }
                    else{
                        $result["code"]="success";
                        $result["message"]= "https????????????";
                        $result["status"]="500";                   
                        return $result;
                    }
                }               
                else
                {
                
                    $result["code"]=$access_array['errcode'];
                    $result["message"]= $access_array['errmsg'];
                    $result["status"]="500";
                    return $result;
                
                }
                
            }
            else
            {
                    $result["code"]="success";
                    $result["message"]= "https????????????";
                    $result["status"]="500";
                    return $result;
            }
            
            
        }

        $response = rest_ensure_response( $result);
        return $response;


    }

    function https_curl_post($url,$data,$type){
        if($type=='json'){
            //$headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");
            $data=json_encode($data);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1); // ?????????????????????Post??????
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        $data = curl_exec($curl);
        if (curl_errno($curl)){
            return 'ERROR';
        }
        curl_close($curl);
        return $data;
    }
 

    function getWinxinQrcodeImg($request)
    {
        $postid= $request['postid'];      
        $path=$request['path'];
        $openid =$request['openid']; 

        $qrcodeName = 'qrcode-'.$postid.'.png';//?????????????????????????????????     
        $qrcodeurl = REST_API_TO_MINIPROGRAM_PLUGIN_DIR.'qrcode/'.$qrcodeName;//??????????????????????????????
        $qrcodeimgUrl = plugins_url().'/'.REST_API_TO_MINIPROGRAM_PLUGIN_NAME.'/qrcode/'.$qrcodeName;
        //???????????????????????????????????????      
        $appid = get_option('wf_appid');
        $appsecret = get_option('wf_secret');
       
        //?????????????????????????????????????????????????????????????????????????????????
        if(!is_file($qrcodeurl)) {
            //$ACCESS_TOKEN = getAccessToken($appid,$appsecret,$access_token);
            $access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;
             $access_token_result = https_request($access_token_url);
             if($access_token_result !="ERROR")
              {
                $access_token_array= json_decode($access_token_result,true);
                if(empty($access_token_array['errcode']))
                {
                  $access_token =$access_token_array['access_token'];
                  if(!empty($access_token))
                  {

                    //??????A????????????,??????10????????????????????????????????????path????????????????????????
                    $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token;
                    //??????B????????????,??????????????????????????????????????????????????????????????????scene??????????????????????????????
                    //$url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$ACCESS_TOKEN;
                    //??????C??????????????????,??????10????????????????????????????????????path????????????????????????
                    //$url = 'http://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$ACCESS_TOKEN;

                    //header('content-type:image/png');
                    $color = array(
                        "r" => "0",  //????????????????????????Photoshop??????
                        "g" => "0",  //????????????????????????Photoshop??????
                        "b" => "0",  //????????????????????????Photoshop??????
                    );
                    $data = array(
                        //$data['scene'] = "scene";//?????????????????????????????????????????????????????????????????????????????????????????????
                        //$data['page'] = "pages/index/index";//??????????????????path????????????????????????
                        'path' => $path, //????????????????????????path
                        'width' => intval(100), //?????????????????????
                        'auto_color' => false,
                        'line_color' => $color,
                    );
                    $data = json_encode($data);
                    //???????????????????????????????????????????????????
                    $QRCode = get_content_post($url,$data);//??????????????????
                    if($QRCode !='error')
                    {
                      //???????????????
                      file_put_contents($qrcodeurl,$QRCode);
                      //imagedestroy($QRCode);
                      $flag=true;
                    }
                    
                  }
                  else
                  {
                    $flag=false;
                  }

                }
                else
                {
                  $flag=false;
                }

              }
              else
              {
                $flag=false;
              }
            
        }
        else
        {

          $flag=true;
        }

        if($flag)
        {
          $result["code"]="success";
            $result["message"]= "????????????????????????";
            $result["qrcodeimgUrl"]=$qrcodeimgUrl; 
            $result["status"]="200"; 
            

        }
        else {
            $result["code"]="success";
            $result["message"]= "????????????????????????"; 
            $result["status"]="500"; 
            
        } 

        $response = rest_ensure_response( $result);
        return $response;
      
    }

    function send_message_permissions_check($request)
    {
      $openid= $request['openid'];
      $template_id=$request['template_id'];
      $postid=$request['postid'];
      $form_id=$request['form_id'];
      $total_fee=$request['total_fee'];
      $flag=$request['flag'];
      $fromUser =$request['fromUser'];
      //$parent=(int)$request['parent'];

      

      if(empty($openid)  || empty($template_id) || empty($postid) || empty($form_id) || empty($total_fee) || empty($flag) || empty($fromUser))
      {
          return new WP_Error( 'error', '????????????', array( 'status' => 500 ) );
      }
      else if(!function_exists('curl_init')) {
          return new WP_Error( 'error', 'php curl 
            ??????????????????', array( 'status' => 500 ) );
      }
      return true;      
      
    }

    function get_qrcodeimg_permissions_check($request)
    {
        $postid= $request['postid'];      
        $path=$request['path'];        

        if(empty($postid)  || empty($path))
        {
            return new WP_Error( 'error', '????????????', array( 'status' => 500 ) );
        }
        else if(get_post($postid)==null)
        {
             return new WP_Error( 'error', 'postId????????????', array( 'status' => 500 ) );
        }
        return true;
    }
    function  get_userInfo_permissions_check($request)
    {
        return true;
    }
    function  update_userInfo_permissions_check($request)
    {
        return true;
    }

    function get_openid_permissions_check($request)
    {
      $js_code= $request['js_code'];
      $encryptedData=$request['encryptedData'];
      $iv=$request['iv'];
      $avatarUrl=$request['avatarUrl'];
      $nickname=empty($request['nickname'])?'':$request['nickname'];
      if(empty($js_code))
      {
          return new WP_Error( 'error', 'js_code?????????', array( 'status' => 500 ) );
      }
      else if(!function_exists('curl_init')) {
          return new WP_Error( 'error', 'php  curl??????????????????', array( 'status' => 500 ) );
      }

      return true;
    }


}