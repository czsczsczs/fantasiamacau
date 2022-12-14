<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once( REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/wxpay/WxPay.Api.php' );
require_once( REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/wxpay/WxPay.JsApiPay.php' );
require_once( REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/wxpay/WxPay.Notify.php' );


class RAW_REST_Payment_Controller  extends WP_REST_Controller{

    public function __construct() {
        $this->namespace     = 'watch-life-net/v1';
        $this->resource_name = 'payment';
    }


     // Register our routes.
    public function register_routes() {

        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'post_payment' ),
               'permission_callback' => array( $this, 'post_payment_permissions_check' ),
                'args'               => array(            
                    'openid' => array(
                        'required' => true
                    ),
                    'totalfee' => array(
                        'required' => true
                    )
                   
                )
            ),
            // Register our schema callback.
            'schema' => array( $this, 'post_public_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name . '/notify', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'notify' ),
                'permission_callback' => array( $this, 'get_notify_permissions_check' ),
                'args'                => $this->get_collection_params()
            )
        ) );

    }

    public function  post_payment($request){
        
        date_default_timezone_set('Asia/Shanghai');
        $openId=isset($request['openid'])?$request['openid']:'';        
        $totalFee=isset($request['totalfee'])? $request['totalfee']:1;

        if(!is_numeric($totalFee))
        {

            return new WP_Error( 'error', "totalfee????????????", array( 'status' => 400 ) );
        }
        
        
        $appId=RAM_WxPayConfig::get_appid();
        $mchId=RAM_WxPayConfig::get_mchid();
        $key=RAM_WxPayConfig::get_key();
        $body=RAM_WxPayConfig::get_body();

        if(empty($appId) || empty($mchId) || empty($key) || empty($body)) {
            
            return new WP_Error( 'error', "?????????AppID????????????????????????????????????????????????", array( 'status' => 400 ) );
        }

        $tools = new RAM_JsApiPay();

        //??????????????????
        $input = new RAM_WxPayUnifiedOrder();
        $input->SetBody($body);
        $orderId =RAM_WxPayConfig::get_mchid().date("YmdHis");
        $input->SetOut_trade_no($orderId);
        $input->SetTotal_fee(strval($totalFee*100));
        //$input->SetTotal_fee(strval($totalFee));
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url(get_rest_url( null, $this->namespace . '/' . $this->resource_name . '/notify' ) );
        $input->SetTrade_type( 'JSAPI' );
        $input->SetOpenid($openId);

         $order = RAM_WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);

        $jsApiParameters['success'] = 'success';
        return  $jsApiParameters;

    }

    // ????????????
    public function notify( $request ) {        
        
        $notify = new RAW_PayNotifyCallBack();
        $notify->Handle( false );
    }
    public function post_payment_permissions_check($request) {
        $openId =isset($request['openid'])? $request['openid']:"";       
        if(empty($openId) || !username_exists($openId))
        {
            return new WP_Error( 'user_parameter_error', "??????????????????", array( 'status' => 400 ) );
        }
        $totalFee=isset($request['totalfee'])? (int)$request['totalfee']:1;
        if(!is_int($totalFee))
        {
            return new WP_Error( 'error', 'totalfee????????????', array( 'status' => 400 ) );
        }
        return true;
    }

    /**
     * Check whether a given request has permission to read order notes.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_notify_permissions_check( $request ) {
        return true;
    }


}  

class RAW_PayNotifyCallBack extends RAM_WxPayNotify {
    
    // ????????????????????????
    public function NotifyProcess( $data, &$msg ) {
        
        if( ! array_key_exists( 'transaction_id' , $data ) ) {
            $msg = '?????????????????????';
            return false;
        }
        if(!RAW_Util::check_notify_sign($data,get_option('raw_paykey'))){
            $msg = 'key??????';
            return false;
        }
        
        
        return true;
    }
}