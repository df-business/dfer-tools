<?php

/**
 * +----------------------------------------------------------------------
 * | 宝塔面板站点操作类库
 * | 需要bt开启API接口
 * | @link https://www.bt.cn/api-doc.pdf
 * | @example
 * | $bt = new Bt(['panel_host'=>'http://127.0.0.1/8888','secret_key'=>'xxxxxxxxxxxxxxxx']);
 * | var_dump($bt->getSystemTotal());
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

use Exception;
use Dfer\Tools\Constants;

class Bt extends Common
{
    //面板地址
    private $panel_host = "http://localhost:8888";
    //接口密钥
    private $secret_key = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

    // 接口列表
    private $api_list = array(
        // ********************** 系统管理 START **********************
        'GetSystemTotal' => '/system?action=GetSystemTotal',       //获取系统基础统计
        'GetDiskInfo' => '/system?action=GetDiskInfo',                 //获取磁盘分区信息
        'GetDirSize' => '/files?action=GetDirSize',                 //获取目录的大小
        'GetNetWork' => '/system?action=GetNetWork',                   //获取实时状态信息(CPU、内存、网络、负载)
        'GetTaskCount' => '/ajax?action=GetTaskCount',                 //检查是否有安装任务
        'UpdatePanelCheck' => '/ajax?action=UpdatePanel',                   //检查面板更新
        // **********************  系统管理 END  **********************

        // ********************** 计划任务 START **********************
        'GetCrontab' => '/crontab?action=GetCrontab',                   //计划任务列表
        'GetLogs' => '/crontab?action=GetLogs',                   //计划任务日志
        // **********************  计划任务 END  **********************

        // ********************** 网站管理 START **********************
        'WebSites' => '/data?action=getData&table=sites',         //获取网站列表
        'WebTypes' => '/site?action=get_site_types',             //获取网站分类
        'WebPath' => '/data?action=getKey&table=sites&key=path',             //获取网站目录
        'WebSetPath' => '/site?action=SetPath',             //设置网站目录
        'GetDirUserINI' => '/site?action=GetDirUserINI',         //获取网站几项开关（防跨站、日志、密码访问），网站目录
        'SetSiteRunPath' => '/site?action=SetSiteRunPath',             //设置网站运行目录
        'WebAddSite' => '/site?action=AddSite',                     //创建网站
        'WebDeleteSite' => '/site?action=DeleteSite',             //删除网站
        'WebSiteStop' => '/site?action=SiteStop',                 //停用网站
        'WebSiteStart' => '/site?action=SiteStart',                 //启用网站
        'WebSetEdate' => '/site?action=SetEdate',                 //设置网站有效期
        'WebSetPs' => '/data?action=setPs&table=sites',             //修改网站备注
        'WebBackupList' => '/data?action=getData&table=backup',     //获取网站备份列表
        'WebToBackup' => '/site?action=ToBackup',                 //创建网站备份
        'WebDelBackup' => '/site?action=DelBackup',                 //删除网站备份
        'WebDoaminList' => '/data?action=getData&table=domain',     //获取网站域名列表
        'WebAddDomain' => '/site?action=AddDomain',                 //添加网站域名
        'WebDelDomain' => '/site?action=DelDomain',                 //删除网站域名
        'WebGetIndex' => '/site?action=GetIndex',                //获取网站默认文件
        'WebSetIndex' => '/site?action=SetIndex',                //设置网站默认文件
        'GetPHPVersion' => '/site?action=GetPHPVersion',         //获取已安装的 PHP 版本列表
        'GetSitePHPVersion' => '/site?action=GetSitePHPVersion', //获取指定网站运行的PHP版本
        'SetPHPVersion' => '/site?action=SetPHPVersion',         //修改指定网站的PHP版本
        'SetHasPwd' => '/site?action=SetHasPwd',                 //开启并设置网站密码访问
        'CloseHasPwd' => '/site?action=CloseHasPwd',             //关闭网站密码访问
        'GetDirBinding' => '/site?action=GetDirBinding',         //获取网站域名绑定二级目录信息
        'AddDirBinding' => '/site?action=AddDirBinding',         //添加网站子目录域名
        'DelDirBinding' => '/site?action=DelDirBinding',         //删除网站绑定子目录
        'GetDirRewrite' => '/site?action=GetDirRewrite',         //获取网站子目录伪静态规则
        'GetSiteLogs' => '/site?action=GetSiteLogs',             //获取网站日志
        'GetSecurity' => '/site?action=GetSecurity',             //获取网站盗链状态及规则信息
        'SetSecurity' => '/site?action=SetSecurity',             //设置网站盗链状态及规则信息
        'GetSSL' => '/site?action=GetSSL',                       //获取SSL状态及证书详情
        'HttpToHttps' => '/site?action=HttpToHttps',             //强制HTTPS
        'CloseToHttps' => '/site?action=CloseToHttps',           //关闭强制HTTPS
        'SetSSL' => '/site?action=SetSSL',                       //设置SSL证书
        'CloseSSLConf' => '/site?action=CloseSSLConf',           //关闭SSL
        'GetLimitNet' => '/site?action=GetLimitNet',             //获取网站流量限制信息
        'SetLimitNet' => '/site?action=SetLimitNet',             //设置网站流量限制信息
        'CloseLimitNet' => '/site?action=CloseLimitNet',         //关闭网站流量限制
        'Get301Status' => '/site?action=Get301Status',           //获取网站301重定向信息
        'Set301Status' => '/site?action=Set301Status',           //设置网站301重定向信息
        'GetRewriteList' => '/site?action=GetRewriteList',         //获取可选的预定义伪静态列表
        'GetFileBody' => '/files?action=GetFileBody',             //获取指定预定义伪静态规则内容(获取文件内容)
        'SaveFileBody' => '/files?action=SaveFileBody',             //保存伪静态规则内容(保存文件内容)
        'GetProxyList' => '/site?action=GetProxyList',           //获取网站反代信息及状态
        'CreateProxy' => '/site?action=CreateProxy',             //添加网站反代信息
        'ModifyProxy' => '/site?action=ModifyProxy',             //修改网站反代信息
        // **********************  网站管理 END  **********************

        // ********************** Ftp管理 START **********************
        'FtpList' => '/data?action=getData&table=ftps',       //获取FTP信息列表
        'FtpSetUserPassword' => '/ftp?action=SetUserPassword',      //修改FTP账号密码
        'FtpSetStatus' => '/ftp?action=SetStatus',                  //启用/禁用FTP
        // **********************  Ftp管理 END  **********************

        // ********************** Sql管理 START **********************
        'SqlList' => '/data?action=getData&table=databases',  //获取SQL信息列表
        'SqlResDatabasePass' => '/database?action=ResDatabasePassword',  //修改SQL账号密码
        'SqlToBackup' => '/database?action=ToBackup',            //创建sql备份
        'SqlDelBackup' => '/database?action=DelBackup',          //删除sql备份
        // **********************  Sql管理 END  **********************

        // ********************** 插件管理 START **********************
        'DeploymentList' => '/plugin?action=a&name=deployment&s=GetList&type=0',       //宝塔一键部署列表
        'DeploymentSetupPackage' => '/plugin?action=a&name=deployment&s=SetupPackage',       //部署任务
        // **********************  插件管理 END  **********************
    );

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    public function setConfig($config = [])
    {
        $this->panel_host = $config['panel_host'] ?? $this->panel_host;
        $this->secret_key = $config['secret_key'] ?? $this->secret_key;
    }

    ////////////////////////////////////////////////// 系统管理 START //////////////////////////////////////////////////
    /**
     * 获取系统基础统计
     */
    public function getSystemTotal()
    {
        $url = $this->getApi("GetSystemTotal");

        $p_data = $this->getKeyData();

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取磁盘分区信息
     */
    public function getDiskInfo()
    {
        $url = $this->getApi("GetDiskInfo");

        $p_data = $this->getKeyData();

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取目录的大小
     */
    public function getDirSize($path)
    {
        $url = $this->getApi("GetDirSize");
        $p_data = $this->getKeyData();
        $p_data['path'] = $path;
        $result = $this->httpPostCookie($url, $p_data);
        return $result;
    }

    /**
     * 获取实时状态信息
     * (CPU、内存、网络、负载)
     */
    public function getNetWork()
    {
        $url = $this->getApi("GetNetWork");

        $p_data = $this->getKeyData();

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 检查是否有安装任务
     */
    public function getTaskCount()
    {
        $url = $this->getApi("GetTaskCount");

        $p_data = $this->getKeyData();

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 检查面板更新
     */
    public function updatePanel($check = false, $force = false)
    {
        $url = $this->getApi("UpdatePanelCheck");

        $p_data = $this->getKeyData();
        $p_data['check'] = $check;
        $p_data['force'] = $force;

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }
    //////////////////////////////////////////////////  系统管理 END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// 计划任务 START //////////////////////////////////////////////////
    /**
     * 获取计划任务列表
     */
    public function getCrontab($count = 20, $p = 1)
    {
        $url = $this->getApi("GetCrontab");
        $p_data = $this->getKeyData();
        $p_data['count'] = $count;
        $p_data['p'] = $p;
        $result = $this->httpPostCookie($url, $p_data);
        return $result;
    }

    /**
     * 计划任务日志
     */
    public function getLogs($id, $day = 15)
    {
        $url = $this->getApi("GetLogs");
        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $end_timestamp = time();
        // 最近15天
        $start_timestamp = $end_timestamp - 60 * 60 * 24 * $day;
        $p_data['start_timestamp'] = $start_timestamp;
        $p_data['end_timestamp'] = $end_timestamp;
        $result = $this->httpPostCookie($url, $p_data);
        return $result;
    }
    //////////////////////////////////////////////////  计划任务 END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// 网站管理 START //////////////////////////////////////////////////
    /**
     * 获取网站列表
     * @param string $page   当前分页
     * @param string $limit  取出的数据行数
     * @param string $type   分类标识 -1: 分部分类 0: 默认分类
     * @param string $order  排序规则 使用 id 降序：id desc 使用名称升序：name desc
     * @param string $tojs   分页 JS 回调,若不传则构造 URI 分页连接
     * @param string $search 搜索内容
     */
    public function siteList($search = '', $page = '1', $limit = '15', $type = '-1', $order = 'id desc', $tojs = '')
    {
        $url = $this->getApi("WebSites");
        $p_data = $this->getKeyData();
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['order'] = $order;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $search;

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取所有网站分类
     */
    public function siteTypeList()
    {
        $url = $this->getApi("WebTypes");

        $p_data = $this->getKeyData();

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站目录
     * @param {Object} $id  网站ID
     */
    public function sitePath($id)
    {
        $url = $this->getApi("WebPath");
        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);
        return $result;
    }

    /**
     * 设置网站目录
     * @param {Object} $id  网站ID
     * @param {Object} $path    网站目录。/www/wwwroot/www.qq.com
     */
    public function sitePathUpdate($id, $path)
    {
        $url = $this->getApi("WebSetPath");
        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['path'] = $path;
        $result = $this->httpPostCookie($url, $p_data);
        return $result;
    }

    /**
     * 设置网站运行目录目录
     * @param {Object} $id  网站ID
     * @param {Object} $runPath    网站目录。/public
     */
    public function siteRunPathUpdate($id, $runPath)
    {
        $url = $this->getApi("SetSiteRunPath");
        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['runPath'] = $runPath;
        $result = $this->httpPostCookie($url, $p_data);
        return $result;
    }

    /**
     * 新增网站
     * @param [type] $webname      网站域名 json格式
     * @param [type] $path         网站路径
     * @param [type] $type_id      网站分类ID
     * @param string $type         网站类型
     * @param [type] $version      PHP版本
     * @param [type] $port         网站端口
     * @param [type] $ps           网站备注
     * @param [type] $ftp          网站是否开通FTP
     * @param [type] $ftp_username FTP用户名
     * @param [type] $ftp_password FTP密码
     * @param [type] $sql          网站是否开通数据库
     * @param [type] $codeing      数据库编码类型 utf8|utf8mb4|gbk|big5
     * @param [type] $datauser     数据库账号
     * @param [type] $datapassword 数据库密码
     */
    public function siteAdd($infoArr = [])
    {
        $url = $this->getApi("WebAddSite");

        //准备POST数据
        $p_data = $this->getKeyData();        //取签名
        $p_data['webname'] = $infoArr['webname'];
        $p_data['path'] = $infoArr['path'];
        $p_data['type_id'] = $infoArr['type_id'];
        $p_data['type'] = $infoArr['type'];
        $p_data['version'] = $infoArr['version'];
        $p_data['port'] = $infoArr['port'];
        $p_data['ps'] = $infoArr['ps'];
        $p_data['ftp'] = $infoArr['ftp'];
        $p_data['ftp_username'] = $infoArr['ftp_username'];
        $p_data['ftp_password'] = $infoArr['ftp_password'];
        $p_data['sql'] = $infoArr['sql'];
        $p_data['codeing'] = $infoArr['codeing'];
        $p_data['datauser'] = $infoArr['datauser'];
        $p_data['datapassword'] = $infoArr['datapassword'];

        //请求面板接口
        $result = $this->httpPostCookie($url, $p_data);

        //解析JSON数据
        return $result;
    }

    /**
     * 删除网站
     * @param [type] $id       网站ID
     * @param [type] $webname  网站名称
     * @param [type] $ftp      是否删除关联FTP
     * @param [type] $database 是否删除关联数据库
     * @param [type] $path     是否删除关联网站根目录
     *
     */
    public function siteDel($id, $webname, $ftp, $database, $path)
    {
        $url = $this->getApi("WebDeleteSite");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['webname'] = $webname;
        $p_data['ftp'] = $ftp;
        $p_data['database'] = $database;
        $p_data['path'] = $path;

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 停用站点
     * @param [type] $id   网站ID
     * @param [type] $name 网站域名
     */
    public function siteStop($id, $name)
    {
        $url = $this->getApi("WebSiteStop");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 启用网站
     * @param [type] $id   网站ID
     * @param [type] $name 网站域名
     */
    public function siteStart($id, $name)
    {
        $url = $this->getApi("WebSiteStart");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置网站到期时间
     * @param [type] $id    网站ID
     * @param [type] $edate 网站到期时间 格式：2019-01-01，永久：0000-00-00
     */
    public function siteExpireDateUpdate($id, $edate)
    {
        $url = $this->getApi("WebSetEdate");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['edate'] = $edate;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 修改网站备注
     * @param [type] $id 网站ID
     * @param [type] $ps 网站备注
     */
    public function siteRemarkUpdate($id, $ps)
    {
        $url = $this->getApi("WebSetPs");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['ps'] = $ps;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站备份列表
     * @param [type] $id    网站ID
     * @param string $page  当前分页
     * @param string $limit 每页取出的数据行数
     * @param string $type  备份类型 目前固定为0
     * @param string $tojs  分页js回调若不传则构造 URI 分页连接 get_site_backup
     */
    public function siteBackupList($id, $page = '1', $limit = '5', $type = '0', $tojs = '')
    {
        $url = $this->getApi("WebBackupList");

        $p_data = $this->getKeyData();
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 创建网站备份
     * @param [type] $id 网站ID
     */
    public function siteBackupAdd($id)
    {
        $url = $this->getApi("WebToBackup");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 删除网站备份
     * @param [type] $id 网站备份ID
     */
    public function siteBackupDel($id)
    {
        $url = $this->getApi("WebDelBackup");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站域名列表
     * @param [type]  $id   网站ID
     * @param boolean $list 固定传true
     */
    public function siteDomainList($id, $list = true)
    {
        $url = $this->getApi("WebDoaminList");

        $p_data = $this->getKeyData();
        $p_data['search'] = $id;
        $p_data['list'] = $list;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 添加域名
     * @param [type] $id      网站ID
     * @param [type] $webname 网站名称
     * @param [type] $domain  要添加的域名:端口 80 端品不必构造端口,多个域名用换行符隔开
     */
    public function siteDomainAdd($id, $webname, $domain)
    {
        $url = $this->getApi("WebAddDomain");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['webname'] = $webname;
        $p_data['domain'] = $domain;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 删除网站域名
     * @param [type] $id      网站ID
     * @param [type] $webname 网站名
     * @param [type] $domain  网站域名
     * @param [type] $port    网站域名端口
     */
    public function siteDomainDel($id, $webname, $domain, $port)
    {
        $url = $this->getApi("WebDelDomain");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['webname'] = $webname;
        $p_data['domain'] = $domain;
        $p_data['port'] = $port;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站默认文件
     * @param [type] $id 网站ID
     */
    public function siteDefaultPage($id)
    {
        $url = $this->getApi("WebGetIndex");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置网站默认文件
     * @param [type] $id    网站ID
     * @param [type] $index 内容
     */
    public function siteDefaultPageUpdate($id, $index)
    {
        $url = $this->getApi("WebSetIndex");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['Index'] = $index;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    //////////////////////////////////////////////////  网站管理 END  //////////////////////////////////////////////////

    /**
     * 获取网站FTP列表
     * @param string $page   当前分页
     * @param string $limit  取出的数据行数
     * @param string $type   分类标识 -1: 分部分类 0: 默认分类
     * @param string $order  排序规则 使用 id 降序：id desc 使用名称升序：name desc
     * @param string $tojs   分页 JS 回调,若不传则构造 URI 分页连接
     * @param string $search 搜索内容
     */
    public function webFtpList($search = '', $page = '1', $limit = '15', $type = '-1', $order = 'id desc', $tojs = '')
    {
        $url = $this->getApi("FtpList");

        $p_data = $this->getKeyData();
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['order'] = $order;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $search;

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站SQL列表
     * @param string $page   当前分页
     * @param string $limit  取出的数据行数
     * @param string $type   分类标识 -1: 分部分类 0: 默认分类
     * @param string $order  排序规则 使用 id 降序：id desc 使用名称升序：name desc
     * @param string $tojs   分页 JS 回调,若不传则构造 URI 分页连接
     * @param string $search 搜索内容
     */
    public function webSqlList($search = '', $page = '1', $limit = '15', $type = '-1', $order = 'id desc', $tojs = '')
    {
        $url = $this->getApi("SqlList");

        $p_data = $this->getKeyData();
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['order'] = $order;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $search;

        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取已安装的 PHP 版本列表
     */
    public function phpVersionList()
    {
        //拼接URL地址
        $url = $this->getApi("GetPHPVersion");

        //准备POST数据
        $p_data = $this->getKeyData();        //取签名

        //请求面板接口
        $result = $this->httpPostCookie($url, $p_data);

        //解析JSON数据
        $data = json_decode($result, true);

        return $data;
    }

    /**
     * 修改指定网站的PHP版本
     * @param [type] $site 网站名
     * @param [type] $php  PHP版本
     */
    public function phpVersionUpdate($site, $php)
    {

        $url = $this->getApi("SetPHPVersion");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $p_data['version'] = $php;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取指定网站运行的PHP版本
     * @param [type] $site 网站名
     */
    public function phpVersion($site)
    {
        $url = $this->getApi("GetSitePHPVersion");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 删除数据库备份
     * @param [type] $id 数据库备份ID
     */
    public function sqlBackupDel($id)
    {
        $url = $this->getApi("SqlDelBackup");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 备份数据库
     * @param [type] $id 数据库列表ID
     */
    public function sqlBackup($id)
    {
        $url = $this->getApi("SqlToBackup");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取可选的预定义伪静态列表
     * @param [type] $siteName 网站名
     */
    public function rewriteList($siteName)
    {
        $url = $this->getApi("GetRewriteList");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $siteName;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取预置伪静态规则内容（文件内容）
     * @param [type] $path 规则名
     * @param [type] $type 0->获取内置伪静态规则；1->获取当前站点伪静态规则
     */
    public function rewriteContent($path, $type = 0)
    {
        $url = $this->getApi("GetFileBody");
        $p_data = $this->getKeyData();
        $path_dir = $type ? 'vhost/rewrite' : 'rewrite/nginx';

        //获取当前站点伪静态规则
        ///www/server/panel/vhost/rewrite/user_hvVBT_1.test.com.conf
        //获取内置伪静态规则
        ///www/server/panel/rewrite/nginx/EmpireCMS.conf
        //保存伪静态规则到站点
        ///www/server/panel/vhost/rewrite/user_hvVBT_1.test.com.conf
        ///www/server/panel/rewrite/nginx/typecho.conf
        $p_data['path'] = '/www/server/panel/' . $path_dir . '/' . $path . '.conf';
        //var_dump($p_data['path']);
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 保存伪静态规则内容(保存文件内容)
     * @param [type] $path     规则名
     * @param [type] $data     规则内容
     * @param string $encoding 规则编码强转utf-8
     * @param number $type     0->系统默认路径；1->自定义全路径
     */
    public function rewriteContentUpdate($path, $data, $encoding = 'utf-8', $type = 0)
    {
        $url = $this->getApi("SaveFileBody");
        if ($type) {
            $path_dir = $path;
        } else {
            $path_dir = '/www/server/panel/vhost/rewrite/' . $path . '.conf';
        }
        $p_data = $this->getKeyData();
        $p_data['path'] = $path_dir;
        $p_data['data'] = $data;
        $p_data['encoding'] = $encoding;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置密码访问网站
     * @param [type] $id       网站ID
     * @param [type] $username 用户名
     * @param [type] $password 密码
     */
    public function sitePwdUpdate($id, $username, $password)
    {
        $url = $this->getApi("SetHasPwd");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['username'] = $username;
        $p_data['password'] = $password;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 关闭密码访问网站
     * @param [type] $id 网站ID
     */
    public function sitePwdClose($id)
    {
        $url = $this->getApi("CloseHasPwd");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站日志
     * @param [type] $site 网站名
     */
    public function siteLogList($site)
    {
        $url = $this->getApi("GetSiteLogs");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站盗链状态及规则信息
     * @param [type] $id   网站ID
     * @param [type] $site 网站名
     */
    public function securityInfo($id, $site)
    {
        $url = $this->getApi("GetSecurity");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['name'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置网站盗链状态及规则信息
     * @param [type] $id      网站ID
     * @param [type] $site    网站名
     * @param [type] $fix     URL后缀
     * @param [type] $domains 许可域名
     * @param [type] $status  状态
     */
    public function securityInfoUpdate($id, $site, $fix, $domains, $status)
    {
        $url = $this->getApi("SetSecurity");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['name'] = $site;
        $p_data['fix'] = $fix;
        $p_data['domains'] = $domains;
        $p_data['status'] = $status;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站三项配置开关（防跨站、日志、密码访问）
     * @param [type] $id   网站ID
     * @param [type] $path 网站运行目录
     */
    public function dirUserIni($id, $path)
    {
        $url = $this->getApi("GetDirUserINI");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['path'] = $path;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 开启强制HTTPS
     * @param [type] $site 网站域名（纯域名）
     */
    public function httpToHttps($site)
    {
        $url = $this->getApi("HttpToHttps");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 关闭强制HTTPS
     * @param [type] $site 域名(纯域名)
     */
    public function httpToHttpsClose($site)
    {
        $url = $this->getApi("CloseToHttps");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置SSL域名证书
     * @param [type] $type 类型
     * @param [type] $site 网站名
     * @param [type] $key  证书key
     * @param [type] $csr  证书PEM
     */
    public function sslUpdate($type, $site, $key, $csr)
    {
        $url = $this->getApi("SetSSL");

        $p_data = $this->getKeyData();
        $p_data['type'] = $type;
        $p_data['siteName'] = $site;
        $p_data['key'] = $key;
        $p_data['csr'] = $csr;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 关闭SSL
     * @param [type] $updateOf 修改状态码
     * @param [type] $site     域名(纯域名)
     */
    public function sslClose($updateOf, $site)
    {
        $url = $this->getApi("CloseSSLConf");

        $p_data = $this->getKeyData();
        $p_data['updateOf'] = $updateOf;
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取SSL状态及证书信息
     * @param [type] $site 域名（纯域名）
     */
    public function ssl($site)
    {
        $url = $this->getApi("GetSSL");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站流量限制信息
     * @param [type] $id [description]
     */
    public function getLimitNet($id)
    {
        $url = $this->getApi("GetLimitNet");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置网站流量限制信息
     * @param [type] $id         网站ID
     * @param [type] $perserver  并发限制
     * @param [type] $perip      单IP限制
     * @param [type] $limit_rate 流量限制
     */
    public function setLimitNet($id, $perserver, $perip, $limit_rate)
    {
        $url = $this->getApi("SetLimitNet");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['perserver'] = $perserver;
        $p_data['perip'] = $perip;
        $p_data['limit_rate'] = $limit_rate;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 关闭网站流量限制
     * @param [type] $id 网站ID
     */
    public function closeLimitNet($id)
    {
        $url = $this->getApi("CloseLimitNet");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站301重定向信息
     * @param [type] $site 网站名
     */
    public function get301Status($site)
    {
        $url = $this->getApi("Get301Status");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置网站301重定向信息
     * @param [type] $site      网站名
     * @param [type] $toDomain  目标Url
     * @param [type] $srcDomain 来自Url
     * @param [type] $type      类型
     */
    public function set301Status($site, $toDomain, $srcDomain, $type)
    {
        $url = $this->getApi("Set301Status");

        $p_data = $this->getKeyData();
        $p_data['siteName'] = $site;
        $p_data['toDomain'] = $toDomain;
        $p_data['srcDomain'] = $srcDomain;
        $p_data['type'] = $type;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站反代信息及状态
     * @param [type] $site [description]
     */
    public function getProxyList($site)
    {
        $url = $this->getApi("GetProxyList");

        $p_data = $this->getKeyData();
        $p_data['sitename'] = $site;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 添加网站反代信息
     * @param [type] $cache     是否缓存
     * @param [type] $proxyname 代理名称
     * @param [type] $cachetime 缓存时长 /小时
     * @param [type] $proxydir  代理目录
     * @param [type] $proxysite 反代URL
     * @param [type] $todomain  目标域名
     * @param [type] $advanced  高级功能：开启代理目录
     * @param [type] $sitename  网站名
     * @param [type] $subfilter 文本替换json格式[{"sub1":"百度","sub2":"白底"},{"sub1":"","sub2":""}]
     * @param [type] $type      开启或关闭 0关;1开
     */
    public function createProxy($cache, $proxyname, $cachetime, $proxydir, $proxysite, $todomain, $advanced, $sitename, $subfilter, $type)
    {
        $url = $this->getApi("CreateProxy");

        $p_data = $this->getKeyData();
        $p_data['cache'] = $cache;
        $p_data['proxyname'] = $proxyname;
        $p_data['cachetime'] = $cachetime;
        $p_data['proxydir'] = $proxydir;
        $p_data['proxysite'] = $proxysite;
        $p_data['todomain'] = $todomain;
        $p_data['advanced'] = $advanced;
        $p_data['sitename'] = $sitename;
        $p_data['subfilter'] = $subfilter;
        $p_data['type'] = $type;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 添加网站反代信息
     * @param [type] $cache     是否缓存
     * @param [type] $proxyname 代理名称
     * @param [type] $cachetime 缓存时长 /小时
     * @param [type] $proxydir  代理目录
     * @param [type] $proxysite 反代URL
     * @param [type] $todomain  目标域名
     * @param [type] $advanced  高级功能：开启代理目录
     * @param [type] $sitename  网站名
     * @param [type] $subfilter 文本替换json格式[{"sub1":"百度","sub2":"白底"},{"sub1":"","sub2":""}]
     * @param [type] $type      开启或关闭 0关;1开
     */
    public function modifyProxy($cache, $proxyname, $cachetime, $proxydir, $proxysite, $todomain, $advanced, $sitename, $subfilter, $type)
    {
        $url = $this->getApi("ModifyProxy");

        $p_data = $this->getKeyData();
        $p_data['cache'] = $cache;
        $p_data['proxyname'] = $proxyname;
        $p_data['cachetime'] = $cachetime;
        $p_data['proxydir'] = $proxydir;
        $p_data['proxysite'] = $proxysite;
        $p_data['todomain'] = $todomain;
        $p_data['advanced'] = $advanced;
        $p_data['sitename'] = $sitename;
        $p_data['subfilter'] = $subfilter;
        $p_data['type'] = $type;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站域名绑定二级目录信息
     * @param [type] $id 网站ID
     */
    public function getDirBinding($id)
    {
        $url = $this->getApi("GetDirBinding");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 设置网站域名绑定二级目录
     * @param [type] $id      网站ID
     * @param [type] $domain  域名
     * @param [type] $dirName 目录
     */
    public function addDirBinding($id, $domain, $dirName)
    {
        $url = $this->getApi("AddDirBinding");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['domain'] = $domain;
        $p_data['dirName'] = $dirName;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 删除网站域名绑定二级目录
     * @param [type] $dirid 子目录ID
     */
    public function delDirBinding($dirid)
    {
        $url = $this->getApi("DelDirBinding");

        $p_data = $this->getKeyData();
        $p_data['id'] = $dirid;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 获取网站子目录绑定伪静态信息
     * @param [type] $dirid 子目录绑定ID
     */
    public function getDirRewrite($dirid, $type = 0)
    {
        $url = $this->getApi("GetDirRewrite");

        $p_data = $this->getKeyData();
        $p_data['id'] = $dirid;
        if ($type) {
            $p_data['add'] = 1;
        }
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 修改FTP账号密码
     * @param [type] $id           FTPID
     * @param [type] $ftp_username 用户名
     * @param [type] $new_password 密码
     */
    public function setUserPassword($id, $ftp_username, $new_password)
    {
        $url = $this->getApi("FtpSetUserPassword");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['ftp_username'] = $ftp_username;
        $p_data['new_password'] = $new_password;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 修改SQL账号密码
     * @param [type] $id           SQLID
     * @param [type] $ftp_username 用户名
     * @param [type] $new_password 密码
     */
    public function resDatabasePass($id, $name, $password)
    {
        $url = $this->getApi("SqlResDatabasePass");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $p_data['password'] = $password;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 启用/禁用FTP
     * @param [type] $id       FTPID
     * @param [type] $username 用户名
     * @param [type] $status   状态 0->关闭;1->开启
     */
    public function setStatus($id, $username, $status)
    {
        $url = $this->getApi("FtpSetStatus");

        $p_data = $this->getKeyData();
        $p_data['id'] = $id;
        $p_data['username'] = $username;
        $p_data['status'] = $status;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 宝塔一键部署列表
     * @param  string $search 搜索关键词
     * @return [type]         [description]
     */
    public function deployment($search = '')
    {
        if ($search) {
            $url = $this->getApi("DeploymentList") . '&search=' . $search;
        } else {
            $url = $this->getApi("DeploymentList");
        }

        $p_data = $this->getKeyData();
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 宝塔一键部署执行
     * @param [type] $dname       部署程序名
     * @param [type] $site_name   部署到网站名
     * @param [type] $php_version PHP版本
     */
    public function setupPackage($dname, $site_name, $php_version)
    {
        $url = $this->getApi("DeploymentSetupPackage");

        $p_data = $this->getKeyData();
        $p_data['dname'] = $dname;
        $p_data['site_name'] = $site_name;
        $p_data['php_version'] = $php_version;
        $result = $this->httpPostCookie($url, $p_data);

        return $result;
    }

    /**
     * 构造带有签名的关联数组
     */
    private function getKeyData()
    {
        $now_time = time();
        $p_data = array(
            'request_token'    =>    md5($now_time . '' . md5($this->secret_key)),
            'request_time'    =>    $now_time
        );
        return $p_data;
    }

    /**
     * 发起POST请求
     * @param String $url 目标网填，带http://
     * @param Array|String $data 欲提交的数据
     * @return string
     */
    private function httpPostCookie($url, $data)
    {
        $name = md5($this->panel_host);
        return $this->httpRequest($url, $data, Constants::REQ_POST, null, compact('name'), 99);
    }

    /**
     * 加载宝塔数据接口
     * @param String $key 接口名
     */
    private function getApi($key)
    {
        if (!isset($this->api_list[$key])) {
            die("接口不存在:$key");
        }
        return $this->panel_host . $this->api_list[$key];
    }
}
