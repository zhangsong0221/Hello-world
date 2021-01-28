<?php
//Admas@FlashPeak
function parse_Tencent_video_page(){
  //$result[0]表示视频热榜集  $result[1]表示标题<->地址表	
  $result = array('','');
  //产生表头
  $result[0] = "<table width='750' border='1'>
		<thead>
		    <tr>
		      <td width='30'></td>
		      <td width='170'><strong>名称</strong></td>
		      <td width='170'><strong>看点</strong></td>
		      <td width='170'><strong>演员</strong></td>
		      <td width='170'><strong>播放次数</strong></td>
		    </tr>
		</thead>";

    //读取html文件
  	$doc = new DOMDocument;
	$src = file_get_contents('http://v.qq.com/rank/detail/2_-1_-1_-1_4_1.html');
  	$doc->loadHTML('<?xml encoding="UTF-8">'.$src);
    
  	//查询指定结点集
  	$xpath = new DOMXpath($doc);
  	
  	//提取【名称】表再拼成整串，否则中间无法有分隔
  	$index = 0;
  	$actors = array();
    $p = $xpath->query("//*[@id='mod_list']/li");
    foreach($p as $item){
    	$p2 = $xpath->query("span[@class='mod_rankbox_con_item_impor']/a",$item);
    	$actors[$index] = '';
    	foreach($p2 as $item2){
    		$actors[$index] = $actors[$index].$item2->nodeValue.'&nbsp';
    	}	
    	$index++;
    }
  	
    //提取【名称】，【看点】和【播放次数】
  	$path = "//span[@class='mod_rankbox_con_item_title']/a  | 
  			 //span[@class='mod_rankbox_con_item_actor'] 	|
  			 //span[@class='mod_rankbox_con_list_click']/strong";
  	$entries = $xpath->query($path);
      	
	for ($index = 0;$index <$entries->length; $index = $index+3) {
		$a_node = $entries->item($index);
		$item_title = $a_node->textContent;
		$link_value = urlencode($a_node->textContent); 
		$item_link  = $a_node->attributes->getNamedItem('href')->nodeValue;
		$item_link2 = urlencode($item_link);
		
		$span1_node = $entries->item($index+1);
		$item_focus = $span1_node->textContent;
		
		$strong_node = $entries->item($index+2);
		$click_number = $strong_node->textContent;
		
		$seq = $index/3+1;
		 
	    $result[0] = $result[0]."<tr>";
	    $result[0] = $result[0]."<td>$seq</td>";
	    $result[0] = $result[0]."<td><a href=\"video_details.php?title=$link_value&addr=$item_link2\">$item_title</a></td>";
	    $result[0] = $result[0]."<td>$item_focus</td>";
	    $result[0] = $result[0]."<td>".$actors[$index/3]."</td>";
	    $result[0] = $result[0]."<td>$click_number</td>";
	    $result[0] = $result[0]."</tr>";
	    
		$result[1] = $result[1]."\"$item_title.\",$item_link.\"\n"; 			    
	}
	
	$result[0] = $result[0]. "
		</tbody>
	</table>";
	return $result;
}
//每隔10天从Tencent网站上刷新得到一次数据，其它时候从缓存文件(cache_entries.txt)中获取
$use_cache = false;
$cache_folder=".";
$video_table_cache_file="$cache_folder/video_table_cache.txt";
if(file_exists($video_table_cache_file)){
  $last_time = filemtime($video_table_cache_file);
  $day_num = floor((time() - $last_time)/(60*60*24));
  $use_cache = $day_num < 10;
}

//$use_cache = false;
if($use_cache ){
$hot_video_list = file_get_contents($video_table_cache_file);
}else{
$result = parse_Tencent_video_page();
file_put_contents($video_table_cache_file,$result[0]);
file_put_contents("$cache_folder/video_title_url.txt",$result[1]);
$hot_video_list = $result[0];
}
echo $hot_video_list;
?>