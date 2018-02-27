<?php
require_once(__DIR__."/simple_html_dom.php");
header("Content-Type:text/html; charset=utf-8");

{
	//目標網址 //臺北榮民總醫院全院病床動態表 big5
	$url="https://www6.vghtpe.gov.tw/opd/servlet/opd.Paskeyq1";   
	$html = file_get_html($url);

	//$result=iconv("big5","UTF-8",$html);  //轉碼
	//$html = mb_convert_encoding($html,"big5","UTF-8");
	//echo $result;
	//$html2 = file_get_html($result);


	/*foreach($html->find('table tr') as $key=>$a){
		if($key>0){
			foreach($a->find('td') as $tdkey=>$td){
				if($tdkey==0){
					$result=iconv("big5","UTF-8",$td->innertext);  //轉碼
					echo $result."<br />";
				}else       
					echo $td->innertext."<br />";
			} 
		}
  }*/
  $tables = $html->getElementsByTagName('P');
  echo "Found : ".$tables->length. " items";
}

/*function Post($url, $post,$cookies=false){
  $context = array();
  if (is_array($post)){
    ksort($post);
    $context['http'] = array(
      'method' => 'POST',
      'content' => http_build_query($post, '', '&')
    );
  }
  else{
    $context['http'] = array(
      'method' => 'POST',
      'content' => $post
    );
  }
  
  if($cookies!=false){
    $context['http']['header']="Cookie: {$cookies}\r\n";
    
    
  }
    
  
  //$r=rand(100,999);
  //echo "POST: {$url} ... {$r}\n";
  $m=false;
  while($m==false){
    $m=@file_get_contents($url, true, stream_context_create($context));
    if($m!==false)
      return $m;
    else{//echo $e->getMessage();
      echo "[Network error]\n";
      usleep(200000);
    }
  }
}*/

?>