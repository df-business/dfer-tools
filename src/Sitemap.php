<?php

/**
 * +----------------------------------------------------------------------
 * | 自动生成sitemap文件（sitemap.xml、sitemap.html）
 * |
 * | 依赖拓展：xsl
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

use XMLWriter, DOMDocument, XSLTProcessor;
use Dfer\Tools\Constants;

class Sitemap extends Common
{
	// XMLWriter对象
	private $writer;
	// 网站地图根域名
	private $domain = "https://www.dfer.site";
	// 网站地图xml文件（不含后缀.xml）
	private $xmlFile = "sitemap";
	// xml模板文件
	private $xslFile = "";
	// 网站地图xml文件夹
	private $xmlFileFolder = "";
	// 网站地图xml文件当前全路径
	private $currXmlFileFullPath = "";
	// 网站地图是否添加额外的schema
	private $isSchemaMore = true;
	// 网站地图item个数（序号）
	private $current_item = 0;
	// 网站地图的个数（序号）
	private $current_sitemap = 0;

	public function __construct()
	{
		$root = $this->getRootPath();
		$this->xslFile = "{$root}/documents/sitemap-xml";
	}

	/**
	 * 设置网站地图根域名，开头用 http:// 或 https://, 结尾不要反斜杠/
	 * @param string $domain	：	网站地图根域名。例如: http://mimvp.com
	 */
	public function setDomain($domain)
	{
		if (substr($domain, -1) == "/") {
			$domain = substr($domain, 0, strlen($domain) - 1);
		}
		$this->domain = $domain;
		return $this;
	}

	/**
	 * 返回网站根域名
	 */
	private function getDomain()
	{
		return $this->domain;
	}

	/**
	 * 设置网站地图的xml文件名
	 * 如果需要访问站外目录，需要源站点关闭防跨站攻击(open_basedir)，更改设置通常需要重启php服务来重置缓存
	 */
	public function setXmlFile($xmlFile)
	{
		$base = basename($xmlFile);
		$dir = dirname($xmlFile);
		// var_dump($dir,is_dir('/www/wwwroot/www.df315.top'));

		if (!is_dir($dir)) {
			$res = mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
			if ($res) {
				// echo "mkdir $dir success";
			} else {
				die("创建目录失败：$dir");
			}
		}
		$this->xmlFile = $xmlFile;
		return $this;
	}

	/**
	 * 返回网站地图的xml文件名
	 */
	private function getXmlFile()
	{
		return $this->xmlFile;
	}

	public function setIsChemaMore($isSchemaMore)
	{
		$this->isSchemaMore = $isSchemaMore;
	}

	private function getIsSchemaMore()
	{
		return $this->isSchemaMore;
	}

	/**
	 * 设置XMLWriter对象
	 */
	private function setWriter(XMLWriter $writer)
	{
		$this->writer = $writer;
	}

	/**
	 * 返回XMLWriter对象
	 */
	private function getWriter()
	{
		return $this->writer;
	}

	/**
	 * 返回网站地图的当前item
	 * @return int
	 */
	private function getCurrentItem()
	{
		return $this->current_item;
	}

	/**
	 * 设置网站地图的item个数加1
	 */
	private function incCurrentItem()
	{
		$this->current_item = $this->current_item + 1;
	}

	/**
	 * 返回当前网站地图（默认50000个item则新建一个网站地图）
	 * @return int
	 */
	private function getCurrentSitemap()
	{
		return $this->current_sitemap;
	}

	/**
	 * 设置网站地图个数加1
	 */
	private function incCurrentSitemap()
	{
		$this->current_sitemap = $this->current_sitemap + 1;
	}

	private function getXMLFileFullPath()
	{
		$xmlfileFullPath = "";
		if ($this->getCurrentSitemap()) {
			// 第n个网站地图xml文件名 + -n + 后缀.xml
			$xmlfileFullPath = $this->getXmlFile() . Constants::SITEMAP_SEPERATOR . $this->getCurrentSitemap() . Constants::SITEMAP_EXT;
		} else {
			// 第一个网站地图xml文件名 + 后缀.xml
			$xmlfileFullPath = $this->getXmlFile() . Constants::SITEMAP_EXT;
		}
		$this->setCurrXmlFileFullPath($xmlfileFullPath);
		// 保存当前xml文件全路径
		return $xmlfileFullPath;
	}

	public function getCurrXmlFileFullPath()
	{
		return $this->currXmlFileFullPath;
	}

	private function setCurrXmlFileFullPath($currXmlFileFullPath)
	{
		$this->currXmlFileFullPath = $currXmlFileFullPath;
	}

	/**
	 * Prepares sitemap XML document
	 */
	private function startSitemap()
	{
		$this->setWriter(new XMLWriter());
		// 获取xml文件全路径
		$this->getWriter()->openURI($this->getXMLFileFullPath());

		$this->getWriter()->startDocument('1.0', 'UTF-8');
		$this->getWriter()->setIndentString("\t");
		$this->getWriter()->setIndent(true);
		$this->getWriter()->startElement('urlset');
		if ($this->getIsSchemaMore()) {
			$this->getWriter()->writeAttribute('xmlns:xsi', Constants::SCHEMA_XMLNS_XSI);
			$this->getWriter()->writeAttribute('xsi:schemaLocation', Constants::SCHEMA_XSI_SCHEMALOCATION);
		}
		$this->getWriter()->writeAttribute('xmlns', Constants::SCHEMA_XMLNS);
	}

	/**
	 * 写入item元素，url、loc、priority字段必选，changefreq、lastmod可选
	 */
	public function addItem($loc, $priority = Constants::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL)
	{
		if (($this->getCurrentItem() % Constants::SITEMAP_ITEMS) == 0) {
			if ($this->getWriter() instanceof XMLWriter) {
				$this->endSitemap();
			}
			$this->startSitemap();
			$this->incCurrentSitemap();
		}
		$this->incCurrentItem();
		$this->getWriter()->startElement('url');
		// 必选
		// $this -> getWriter() -> writeElement('loc', $this -> getDomain() .$loc);
		$this->getWriter()->writeElement('loc', $loc);
		// 必选
		$this->getWriter()->writeElement('priority', $priority);
		if ($changefreq) {
			// 可选
			$this->getWriter()->writeElement('changefreq', $changefreq);
		}
		if ($lastmod) {
			// 可选
			// $this -> getWriter() -> writeElement('lastmod', $this -> getLastModifiedDate($lastmod));
			$this->getWriter()->writeElement('lastmod', $lastmod);
		}
		$this->getWriter()->endElement();
		return $this;
	}

	/**
	 * 转义时间格式，返回时间格式为 2016-09-12
	 */
	private function getLastModifiedDate($date = null)
	{
		if (null == $date) {
			$date = time();
		}
		if (ctype_digit($date)) {
			return date('c', $date);
			// Y-m-d
		} else {
			$date = strtotime($date);
			return date('c', $date);
		}
	}

	/**
	 * 结束网站xml文档，配合开始xml文档使用
	 */
	public function endSitemap()
	{
		if (!$this->getWriter()) {
			$this->startSitemap();
		}
		$this->getWriter()->endElement();
		$this->getWriter()->endDocument();
		$this->getWriter()->flush();
		$rt_endSitemap = $this->getCurrXmlFileFullPath();
		// echo sprintf("<br><a href='%s' target='_blank'>%s</a> 生成完毕", $this -> getCurrXmlFileFullPath(), $this -> getCurrXmlFileFullPath());
		$rt_createXSL2Html = $this->createXSL2Html(true);
		$rt = array("xml" => $rt_endSitemap, "html" => $rt_createXSL2Html);
		return $rt;
	}

	/**
	 * Writes Google sitemap index for generated sitemap files
	 *
	 * @param string $loc Accessible URL path of sitemaps
	 * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
	 */
	public function createSitemapIndex($loc, $lastmod = 'Today')
	{
		$indexwriter = new XMLWriter();
		$indexwriter->openURI($this->getXmlFile() . Constants::SITEMAP_SEPERATOR . Constants::INDEX_SUFFIX . Constants::SITEMAP_EXT);
		$indexwriter->startDocument('1.0', 'UTF-8');
		$indexwriter->setIndent(true);
		$indexwriter->startElement('sitemapindex');
		$indexwriter->writeAttribute('xmlns:xsi', Constants::SCHEMA_XMLNS_XSI);
		$indexwriter->writeAttribute('xsi:schemaLocation', Constants::SCHEMA_XSI_SCHEMALOCATION);
		$indexwriter->writeAttribute('xmlns', Constants::SCHEMA_XMLNS);
		for ($index = 0; $index < $this->getCurrentSitemap(); $index++) {
			$indexwriter->startElement('sitemap');
			$indexwriter->writeElement('loc', $loc . $this->getFilename() . ($index ? Constants::SITEMAP_SEPERATOR . $index : '') . Constants::SITEMAP_EXT);
			$indexwriter->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
			$indexwriter->endElement();
		}
		$indexwriter->endElement();
		$indexwriter->endDocument();
	}

	/**
	 * 转化 xml + xsl 为 html
	 *
	 * extension=php_xsl.dll
	 * extension=xsl
	 *
	 * bt——安装拓展——xsl
	 *
	 * @param unknown $xmlFile		sitemap.xml 源文件
	 * @param unknown $xslFile		sitemap-xml.xsl 源文件
	 * @param unknown $htmlFile		sitemap.html 生成文件
	 * @param string $isopen_htmlfile	是否打开生成文件 sitemap.html
	 */
	public function createXSL2Html($isopen_htmlfile = false)
	{
		header("Content-Type: text/html; charset=UTF-8");
		$xml = new DOMDocument();
		// var_dump($this -> xmlFile . '.xml');
		$xml->Load($this->xmlFile . '.xml');
		$xsl = new DOMDocument();
		$xsl->Load($this->xslFile . '.xsl');
		$xslproc = new XSLTProcessor();
		$xslproc->importStylesheet($xsl);
		// 	echo $xslproc->transformToXML($xml);
		$htmlFile = $this->xmlFile . '.html';

		$f = fopen($htmlFile, 'w');
		fwrite($f, $xslproc->transformToXML($xml));
		fclose($f);

		$rt = '';
		// 是否打开生成的文件 sitemap.html
		if ($isopen_htmlfile) {
			$rt = $htmlFile;
		}
		return $rt;
	}

	/**
	 * 获取插件根目录
	 * @param {Object} $var 变量
	 **/
	public function getRootPath($var = null)
	{
		$root = dirname(__DIR__, 1);
		return $root;
	}
}
