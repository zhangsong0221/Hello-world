<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
	require("../common.php");
	if ($_GET["title"]=="") exit();
	
	LogHit("sjt_keyword_cnvideo","title");
   function creat_my_page(){
   	//1.提取GET参数
	$title = $_GET["title"];
	$video_url = $_GET["addr"];
	
	//2.判断是使用缓存文件还是重新生成
	if(!is_dir('cnvideo_cache')){
		mkdir('cnvideo_cache');
	}
	$cache_file_name = "cnvideo_cache/".urlencode($title).".ini";
	$use_cache = false;
	if(file_exists($cache_file_name)){
	  $last_time = filemtime($cache_file_name);
	  $day_num = floor((time() - $last_time)/(60*60*24));
	  $use_cache = $day_num < 10;
	}
	
	if($use_cache){
		$argument = parse_ini_file($cache_file_name);
	}else{
		$argument = creat_summary_page($title,$video_url);
		//if fetch fails, we will use the cached version.
		if ($argument===false) 
			$argument = parse_ini_file($cache_file_name);
		else
		{
			$file = fopen($cache_file_name,'w');
			foreach($argument as $key=>$value) {
				fputs($file,"$key=\"$value\"\n");
			}
			fclose($file);
		}
		
		//每次更新缓存文件时，都去作一次垃圾文件清理
		clear_trash_file();	
	}
    $html_content = file_get_contents("cnvideo_title.htm");
	foreach($argument as $key=>$value) {
		$html_content = str_replace('$'.$key,$value,$html_content);
	}	
	echo $html_content;
  }
  
   //用1%概率去清理删除掉过期的缓存文件
  function clear_trash_file(){
  	if(rand(1,100) == 1){
	  	foreach (glob("cnvideo_cache/*.ini") as $filename) {
		  $last_time = filemtime($filename);
		  $day_num = floor((time() - $last_time)/(60*60*24));
		  if($day_num > 10){
		  	unlink($filename);
		  }  		
	  	}
  	}	
  }	  
  
  function creat_summary_page($title,$video_url){
  	
  	//1.读取原始Tencent网页
	$src = file_get_contents($video_url);
	$index= strpos($src, '正在跳转');
	$use_parse_kind = 1;
	if(is_int($index) && $index>0){
  		$video_url = preg_replace("/http:\/\/v(\d)?\.qq\.com/i", 'http://film.qq.com',$video_url);
  		$video_url = preg_replace("/\/(detail|prev)\//i","/cover/",$video_url);
  		$index2= strpos($video_url, 'ADTAG=INNER');
  		if(is_bool($index2) && !$index2){
  			$index3= strpos($video_url, '?');
			if(is_bool($index3) && !$index3){
				$video_url = $video_url.'?';	
			}else if(is_int($index3) && $index3>0){
				$video_url = $video_url.'&';
			}  				
			$video_url = $video_url.'ADTAG=INNER.TXV.COVER.REDIR';
		}
		$src = file_get_contents($video_url);
		$use_parse_kind = 2;
	}

	//2.查询指定字段值
  	$doc = new DOMDocument;
	$old_level=error_reporting();
	error_reporting($old_level & ~ E_WARNING);
  	$ok=$doc->loadHTML('<?xml encoding="UTF-8">'.$src);
	error_reporting($old_level);
	if ($ok===false) return false;
  	$xpath = new DOMXpath($doc);
  	$argument = array();
  	if($use_parse_kind === 1){
  		$argument = parase_page_1($xpath);	
  	}else if($use_parse_kind === 2){
  		$argument = parase_page_2($xpath);
  	}
  	
  	//3.替换字段值生成目标页面
  	$argument['title'] = $title;
  	$argument['video_url'] = $video_url;

	return $argument;
  }
    
  function parase_page_1($xpath){
  	$poster_path   = "//div[@class='mod_intro mod_intro_film']/div[@class='intro_figure']/a[@class='figure']/img";
	$director_path = "//div[@class='mod_intro mod_intro_film']/div[2]/div[2]/ul/li[2]/div/div/ul";
	$summary_path  = "//*[@id='mod_desc']/p[2]"; 
	$path = $poster_path."|".$director_path."|".$summary_path;
	$entries = $xpath->query($path);

	$img_node = $entries->item(0);
	$argument['poster']= $img_node->attributes->getNamedItem('src')->nodeValue;
	$argument['directors'] = '导演：'.$entries->item(1)->textContent;
	$argument['summary']   = '简介：'.$entries->item(2)->textContent;

	//单独提取演员表再拼成整串，不然没有分隔符
	$actor_path = "//div[@class='mod_intro mod_intro_film']/div[2]/div[2]/ul/li[3]/div/div/ul/li";
	$entries = $xpath->query($actor_path);
	$argument['actors']  = '主演：';
	foreach ($entries as $entry) {
		$argument['actors'] = $argument['actors'].$entry->textContent.' ';
	} 
	
	return $argument;
  }
  
  function parase_page_2($xpath){
	$poster_path   = "//*[@id='movie_img']/span[@class='a_cover']/img[@class='cover']";
	$director_path = "//dl[@class='detail_list']/dd[@class='type']/span[1]";
	$actor_path    = "//dl[@class='detail_list']/dd[@class='actor']";
	$summary_path  = "//dl[@class='detail_list']/dd[@id='mod_desc']/p[@class='detail_all']"; 
	
	$path = $poster_path."|".$director_path."|".$actor_path."|".$summary_path;
	$entries = $xpath->query($path);
	$img_node = $entries->item(0);
	$argument['poster']= $img_node->attributes->getNamedItem('lz_src')->nodeValue;	
	$argument['actors'] = $entries->item(1)->textContent;
	$argument['directors'] = $entries->item(2)->textContent;
	$argument['summary'] = $entries->item(3)->textContent;
	return $argument;
  }
  
  creat_my_page();
?>
</body>
