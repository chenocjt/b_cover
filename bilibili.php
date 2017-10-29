<?php
	//开放接口
	header("Access-control-Allow-Origin: *");

	$url = null;
	$av = null;

	//判断是否带url
	if(isset($_REQUEST['url'])) {
		$url = $_REQUEST['url'];
		//判断url后面是否带/
		if(!preg_match("/\/$/", $url)) {
			$url .= "/";
		}
	}
	//判断是否带av号
	if(isset($_REQUEST['av'])) {
		$av = $_REQUEST['av'];
		//删除前面的 av
		$av = preg_replace("/av/i", '', $av);
		//判断av号为数字
		if(!preg_match("/^\d+$/", $av)) {
			die('{"error" : 2}');
		}
		//av号转换为url
		$url = 'https://www.bilibili.com/video/av' . $av . '/';
	}

	//判断是否为合格的url
	$pattern = '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?/';
	if(!preg_match($pattern, $url)) {
		die('{"error" : 2}');
	}
	//判断是否为B站url
	if(!preg_match('/\.bilibili\./', $url)) {
		die('{"error" : 2}');
	}
	//判断是否为手机版的url
	if(preg_match('/\/\/m.bilibili.com\/video\/av(\d+?)\.html/', $url, $tmpAv)) {
		$av = $tmpAv[1];
		$url = 'https://www.bilibili.com/video/av' . $av . '/';
	}
	//通过url拿到av号
	if(preg_match('/\/av(\d+?)\//', $url, $tmpAv)) {
		$av = $tmpAv[1];
	}

	//通过url获取内容
	$data = request($url);
	//分析
	$xx = analysis($data);
	//增加av号和视频url
	$xx->av = $av;
	$xx->videoSrc = $url;
	//返回json数据
	echo json_encode($xx);









	/*请求函数*/
	function request($url) {
		$header;
		$content;

		$conn = curl_init();
		curl_setopt($conn, CURLOPT_URL, $url);
		//判断是否是https请求
		if(preg_match('/https/', $url)) {
			curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
		}

		curl_setopt($conn, CURLOPT_HEADER, true);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
		//获取响应头和内容
		$con = curl_exec($conn);
		//获取响应头大小
		$headerSize = curl_getinfo($conn, CURLINFO_HEADER_SIZE);
		//响应头和内容分离
		$header = substr($con, 0, $headerSize);
		$content = substr($con, $headerSize);
		//是否gzip压缩
		if(preg_match('/Content-Encoding:\s?gzip/i', $header, $tmp)) {
			$content = gzdecode($content);
		}

		return $content;
	}

	//分析函数
	function analysis($data) {
		$src = null;

		//匹配第一张图片用的正则表达式
		$pattern = '/<img src="(\S*)"\s*style="display:none;"/';

		$tmpData = new Information;
		preg_match($pattern, $data, $imgSrc);
		preg_match('/card="(\S*)"/', $data, $tmpUp);
		preg_match('/<title>([\s\S]*?)<\/title>/', $data, $tmpTitle);


		//拿到第一个分组
		if(isset($imgSrc[1])) {
			$src = "http:" . $imgSrc[1];
			$src = preg_replace('/i\d+?/', 'i0', $src);
			$tmpData->imageSrc = $src;
		}
		//没有获取到？
		if($src == null) {
			$tmpData->error = 1;
		}
		//up主
		if(isset($tmpUp[1])) {
			$tmpData->up = $tmpUp[1];
		}
		//标题
		if (isset($tmpTitle[1])) {
			$tmpData->title = $tmpTitle[1];
		}

		return $tmpData;
	}

	//信息类
	class Information {
		var $imageSrc;
		var $videoSrc;
		var $title;
		var $up;
		var $av;
		var $error = 0;
	}

	/*
	错误码
	0 正常
	1 视频不存在
	2 格式错误
	 */
?>
