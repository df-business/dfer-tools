<?php

namespace Dfer\Tools;

/**
 * 常用的方法
 */
class Common
{
    
    
    /**
     * 简介
     *
     * @param Type $var Description
     * @return mixed
     **/
    public static function about()
    {
        $host='http://www.dfer.top';
        header("Location:".$host);
        return $host;
    }
    
	/**
	 * 打印
	 **/
	public static function print($str=null)
	{
		echo $str.PHP_EOL;		
	}
	
    /**
     * 省份转化为简称
     *
     * @return mixed
     **/
    public static function provinceAbbreviation($province = null)
    {
        $province=str_replace('省', '', $province);
        $data=[
    '北京'=>'京',
    '天津'=>'津',
    '河北'=>'冀',
    '山西'=>'晋',
    '内蒙古'=>'蒙',
    '辽宁'=>'辽',
    '吉林'=>'吉',
    '黑龙江'=>'黑',
    '上海'=>'沪',
    '江苏'=>'苏',
    '浙江'=>'浙',
    '安徽'=>'皖',
    '福建'=>'闽',
    '江西'=>'赣',
    '山东'=>'鲁',
    '河南'=>'豫',
    '湖北'=>'鄂',
    '湖南'=>'湘',
    '广东'=>'粤',
    '广西'=>'桂',
    '海南'=>'琼',
    '重庆'=>'渝',
    '四川'=>'川',
    '贵州'=>'贵',
    '云南'=>'云',
    '西藏'=>'藏',
    '陕西'=>'陕',
    '甘肃'=>'甘',
    '青海'=>'青',
    '宁夏'=>'宁',
    '新疆'=>'新',
    '台湾'=>'台',
    '香港'=>'港',
    '澳门'=>'澳',
    ];
        return $data[$province];
    }
}
