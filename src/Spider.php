<?php
namespace Dfer\Tools;

use QL\QueryList;
use QL\Ext\CurlMulti;
use QL\Ext\AbsoluteUrl;

/**
 * +----------------------------------------------------------------------
 * | 爬虫程序
 * | composer require jaeger/querylist
 * | composer require jaeger/querylist-curl-multi
 * | composer require jaeger/querylist-absolute-url
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
 *                 ......    .!%$! ..        | AUTHOR: dfer
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .           | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..
 * +----------------------------------------------------------------------
 *
 */
class Spider extends Common
{


  // 主机
    const HOST='http://www.chinabz.org/';


    /**
     * 自动初始化
     */
    public function __construct()
    {
    }


    /**
     * 自动采集网络数据到本地数据库
     *
     */
    public function demo()
    {
        $ql = QueryList::use(CurlMulti::class);
        $ql->use(AbsoluteUrl::class);

        $ql->curlMulti([
          // 通知公告
          self::HOST.'xhgg/',
          // 新闻资讯
           self::HOST.'xwzx/',
          // 政策法规
          self::HOST.'zcfg/',
          // 标准化
          self::HOST.'bzh/',
      ])
       ->success(function (QueryList $ql, CurlMulti $curl, $r) {
           $current=$r['info']['url'];
           $menu_name=explode("/", $current);
           $menu_name=$menu_name[count($menu_name)-2];
           echo "\r\n当前路径:{$current}\r\n";

           // 菜单内的数据
           $menu_type = $ql->find('#banner_list a')->texts();
           $menu_url = $ql->absoluteUrl(self::HOST)->find('#banner_list a')->attrs('href');

           if (count($menu_url)==0) {
               $menu_type[]='';
               $menu_url[]=$current;
           }

           // \var_dump($menu_type,$menu_url);

           $num=0;
           foreach ($menu_url as $u) {
               $list=QueryList::get($u);
               $list->use(AbsoluteUrl::class);
               // 遍历列表URL，访问详情页
               $url = $list->absoluteUrl(self::HOST)->find('#box_middle h4 a')->attrs('href');
               // \var_dump($url);
               foreach ($url as $i) {
                   $article=QueryList::get($i);
                   $title=$article->find('.article h2')->text();
                   $content=$article->find('.article .box_gs')->text();

                   $data=[
                       'title'  =>  $title,
                       'content' =>  $content,
                       'type' =>  $menu_type[$num],
                       'menu' =>  $menu_name
                               ];
               }
               $num++;
           }
       })
      ->error(function ($errorInfo, CurlMulti $curl) {
          echo "\n当前路径:{$errorInfo['info']['url']}\n";
          var_dump($errorInfo['error']);
      })
      ->start([
          'maxThread' => 10,
          'maxTry' => 3
      ]);
    }

				/**
				 * 从页面中截取一段html代码
				 * eg:
				 * 	$a=new \Dfer\Tools\Spider;
				 * 	var_dump($a->cutHtml('http://yj.tye3.com.local/homepage/index/bgDetail.html?type=2&id=12&session_id=aed6c9eeaad3c42e1b192d9a5725433c'));die;
				 * @param {Object} $url 地址
				 * @param {Object} $element 元素
				 **/
				public function cutHtml($url,$element='#content_block')
				{
					$html=QueryList::get($url)->find($element)->html();
					return $html;
				}

}
