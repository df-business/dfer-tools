<?php

/**
 * +----------------------------------------------------------------------
 * | 钉钉类
 * +----------------------------------------------------------------------
 *                                            ...     .............
 *                                          ..   .:!o&*&&&&&ooooo&; .
 *                                        ..  .!*%*o!;.
 *                                      ..  !*%*!.      ...
 *                                     .  ;$$!.   .....
 *                          ........... .*#&   ...
 *                                     :$$: ...
 *                          .;;;;;;;:::#%      ...
 *                        . *@ooooo&&&#@***&&;.   .
 *                        . *@       .@%.::;&%$*!. . .
 *          ................!@;......$@:      :@@$.
 *                          .@!   ..!@&.:::::::*@@*.:..............
 *        . :!!!!!!!!!!ooooo&@$*%%%*#@&*&&&&&&&*@@$&&&oooooooooooo.
 *        . :!!!!!!!!;;!;;:::@#;::.;@*         *@@o
 *                           @$    &@!.....  .*@@&................
 *          ................:@* .  ##.     .o#@%;
 *                        . &@%..:;@$:;!o&*$#*;  ..
 *                        . ;@@#$$$@#**&o!;:   ..
 *                           :;:: !@;        ..
 *                               ;@*........
 *                       ....   !@* ..
 *                 ......    .!%$! ..     | AUTHOR: dfer
 *         ......        .;o*%*!  .       | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .        | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..          | WEBSITE: http://www.dfer.site
 * +----------------------------------------------------------------------
 *
 */

namespace Dfer\Tools;

use Dfer\Tools\Constants;

class DingTalk extends Common
{
    private $token = '';
    protected $appkey = '';
    protected $appsecret = '';
    protected $agent_id = '';
    protected $user_id = '';
    protected $corp_id = '';
    protected $sso_secret = '';

    /**
     * 自动初始化
     *
     *    Config::get('dingding.appkey', '')
     * @return mixed
     **/
    public function __construct($appkey, $appsecret, $agent_id, $user_id, $corp_id, $sso_secret)
    {
        $this->appkey = $appkey;
        $this->appsecret = $appsecret;
        $this->agent_id = $agent_id;
        $this->user_id = $user_id;
        $this->corp_id = $corp_id;
        $this->sso_secret = $sso_secret;
        $this->token = $this->getToken();
    }

    // ********************** Android接口 START **********************

    /**
     * 获取用户token
     * https://open.dingtalk.com/document/orgapp-server/obtain-user-token?spm=ding_open_doc.document.0.0.57f1722fCaCGSq#doc-api-dingtalk-GetUserToken
     *
     * refreshToken可以用来替代authCode，刷新token
     * @return mixed
     **/
    public function getUserAccessToken($authCode)
    {
        $data = [
            "clientId" => $this->appkey,
            "clientSecret" => $this->appsecret,
            "code" => $authCode,
            // "refreshToken" => "",
            "grantType" => "authorization_code"
        ];
        $rt = $this->httpRequest("https://api.dingtalk.com/v1.0/oauth2/userAccessToken", $data, Constants::REQ_JSON);
        // var_dump($rt);
        if (isset($rt['accessToken'])) {
            return $rt['accessToken'];
        } else {
            $this->log($rt);
            return null;
        }
    }

    /**
     * 获取用户通讯录个人信息
     * https://open.dingtalk.com/document/orgapp-server/dingtalk-retrieve-user-information?spm=ding_open_doc.document.0.0.57f1722fCaCGSq#doc-api-dingtalk-GetUser
     *
     * @return mixed
     **/
    public function getContactUsers($accessToken, $unionId = "me")
    {
        $data = [];
        $rt = $this->httpRequest(
            "https://api.dingtalk.com/v1.0/contact/users/{$unionId}",
            $data,
            Constants::REQ_JSON,
            ["x-acs-dingtalk-access-token" => $accessToken]
        );
        if (!isset($rt['code'])) {
            return $rt;
        } else {
            $this->log($rt);
            return null;
        }
    }

    // **********************  Android接口 END  **********************

    // ********************** H5接口 START **********************

    /**
     * 获取token
     *
     * access_token的有效期为7200秒（2小时），有效期内重复获取会返回相同结果并自动续期，过期后获取会返回新的access_token
     * @return mixed
     **/
    public function getToken()
    {
        $data = [
            'appkey' => $this->appkey,
            'appsecret' => $this->appsecret
        ];
        $rt = $this->httpRequest("https://oapi.dingtalk.com/gettoken", $data, Constants::REQ_GET);
        return $rt['access_token'];
    }

    /**
     * 通过免登码获取用户信息
     * https://open.dingtalk.com/document/orgapp-server/obtain-the-userid-of-a-user-by-using-the-log-free
     *
     * @return mixed
     **/
    public function getUserInfo($code)
    {
        $data = [
            'access_token' => $this->token,
            'code' => $code
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/v2/user/getuserinfo", $data);
        return $rt;
    }

    /**
     * 通过免登码获取用户信息
     * https://open.dingtalk.com/document/orgapp-server/obtain-the-userid-of-a-user-by-using-the-log-free
     *
     * @return mixed
     **/
    public function getUserInfoNew($code)
    {
        $data = [
            'access_token' => $this->token,
            'code' => $code
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/v2/user/getuserinfo", $data, Constants::REQ_GET);
        return $rt;
    }

    /**
     * 获取用户详情
     * https://open.dingtalk.com/document/orgapp-server/query-user-details
     *
     * @return mixed
     **/
    public function getUserDetail($userid)
    {
        $data = [
            'access_token' => $this->token,
            'userid' => $userid
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/v2/user/get", $data);
        return $rt;
    }

    /**
     * 获取部门详情
     * https://open.dingtalk.com/document/orgapp-server/query-department-details0-v2
     *
     * @return mixed
     **/
    public function getDepartment($dept_id)
    {
        $data = [
            'access_token' => $this->token,
            'dept_id' => $dept_id
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/v2/department/get", $data);
        return $rt;
    }

    /**
     * 获取部门列表
     *
     *
     * @return mixed
     **/
    public function getDeptList($dept_id = 1)
    {
        $data = [
            'access_token' => $this->token,
            'dept_id' => $dept_id
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/v2/department/listsub", $data);
        return $rt;
    }

    /**
     * 获取部门用户基础信息
     *
     *
     * @return mixed
     **/
    public function getDeptInfo($dept_id = 1)
    {
        $data = [
            'access_token' => $this->token,
            'dept_id' => $dept_id,
            'cursor' => 0,
            'size' => 100
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/user/listsimple", $data);
        return $rt;
    }

    /**
     * 发送信息
     *
     * @return mixed
     **/
    public function sendMsg($msg)
    {
        $data = [
            'access_token' => $this->token,
            'agent_id' => $this->agent_id,
            'userid_list' => $this->user_id,
            'msg' => json_encode([
                'msgtype' => 'text',
                'text' => ['content' => $msg]
            ])
        ];
        // var_dump($data);
        $rt = $this->httpRequest("https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2", $data);
        return $rt;
    }

    // **********************  H5接口 END  **********************
}
