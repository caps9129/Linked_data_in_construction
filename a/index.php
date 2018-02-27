<?php
require_once(__DIR__."/simple_html_dom.php");
header("Content-Type:text/html; charset=utf-8");

//取得縣市代碼
$url="http://www.ris.gov.tw/doorplateX/map?searchType=village#";
$countyCodeArray=getCountyCode($url);
echo "<pre>".print_r($countyCodeArray,1)."</pre>";
//exit;

foreach($countyCodeArray as $cKey=>$cName){
  //echo $cName;
  //$cName=iconv("UTF-8","big5",$cName);
  $fp=fopen("{$cName}.txt","w");
  if($fp){
    fprintf($fp,"編釘日期,異動日期,變更前地址,變更後地址,編釘類別\n");  //title
    $page=-1;
    $townCodeArray=getTownCode($cKey);      //取得鄉鎮市區代碼
    echo "<pre>".print_r($townCodeArray,1)."</pre>";
    foreach($townCodeArray as $tKey=>$tName){
      if($page==-1){
        $paraArray=getPara($cKey,$tKey);   
        echo "<pre>".print_r($paraArray,1)."</pre>";
        //exit;
      }
      
      for($i=1;$i<=$paraArray["page"];$i++){           //每20頁(每頁50筆)取得一次tktValue
        //$paraArray["tktValue"]=(time()-1439020000)*1000;
        if(($i>1 && $i%20==1) || $paraArray["tktValue"]==""){
          //$paraArray["tktValue"]=(time()-1439020000)*1000;
          $paraArray=getPara($cKey,$tKey);
        }
        getData($paraArray["tktValue"],$cKey,$tKey,$i,50,$fp);   //抓資料   
      }
         
    }
    fclose($fp);    
  }else{
    echo "couldn't open file";
  }   
  
  
  //exit;
}
function getPara($cKey,$tKey){
  $tktValue=gettktValue($cKey);       //$tktVal會變
  $page=getPage($tktValue,$cKey,$tKey,50);  //取得page
  //echo "page:{$page}";
  //exit;
  return array("tktValue"=>$tktValue,"page"=>$page);
}

function getPage($tktV,$cKey,$tKey,$Num){
  $url="https://www.ris.gov.tw/doorplateX/villageQuery";
  $m=Post($url,array(      //查詢參數
      "tkt"=>"{$tktV}",
      "tkTimes"=>"1",
      "searchType"=>"village",
      "cityCode"=>"{$cKey}",
      "areaCode"=>"{$tKey}",
      "registerKind"=>"",
      "sDate"=>"104-08-20",      // "sDate"=>"101-08-01", "eDate"=>"104-08-20", 原始
      "eDate"=>"105-03-07",
      "includeNoDate"=>"true",
      "page"=>"1",
      "rows"=>"{$Num}"
    ));
  echo $m;
  $html = str_get_html($m);  
  
  $info=json_decode($html);           //將json資料轉成array  
  $page=ceil($info->total/(int)$Num);    //$page:page總數, $info->total 得到筆數
  
  return $page; 
}

function gettktValue($cCode){ //取得網頁參數tkt
  //return (time()-1439020000)*1000;
  $url="http://www.ris.gov.tw/doorplateX/query";   
  $m=Post($url,array(      //查詢參數
  "cityCode"=>"{$cCode}",
  "searchType"=>"village"
    ));
  $html = str_get_html($m);
  
  foreach($html->find('div div input') as $i){
    if($i->attr['name']=="tkt"){
      $value=$i->attr['value'];
    }
  }
  //return $value-rand(-10000,10000);
  return $value;
}

function getCountyCode($url){
  $html = file_get_html($url);  
  foreach($html->find('area') as $a){   //全台縣市
    $countyCode=str_replace("');","",str_replace("toQuery('","",$a->attr['onclick']));
    $countyName=str_replace("資料","",$a->attr['alt']);
    $result[$countyCode]=$countyName;
  }
  return $result;
}

function getTownCode($cKey){     //取得鄉鎮代碼
  $url="http://www.ris.gov.tw/doorplateX/query";
  $m=Post($url,array(      //查詢參數
  "cityCode"=>"{$cKey}",
  "searchType"=>"village"
    ));
  $html = str_get_html($m);  
  //echo $html;
  foreach($html->find('form div select[id=areaCode] option') as $option){
    if($option->attr['value']>0){
      $result[$option->attr['value']]=$option->plaintext;
    }    
  }
  return $result;
}

function getData($tktV,$cCode,$tCode,$i,$Num,&$fp){    //$pageNum=50
  $url="http://www.ris.gov.tw/doorplateX/villageQuery";  
  
  $m=Post($url,array(      //查詢參數
      "tkt"=>"{$tktV}",
      "tkTimes"=>"1",
      "searchType"=>"village",
      "cityCode"=>"{$cCode}",
      "areaCode"=>"{$tCode}",
      "registerKind"=>"",
      "sDate"=>"104-08-20",      // "sDate"=>"101-08-01", "eDate"=>"104-08-20", 原始
      "eDate"=>"105-03-07",
      "includeNoDate"=>"true",
      "page"=>"{$i}",
      "rows"=>"{$Num}"
    ));
  $html = str_get_html($m);
  $info=json_decode($html);           //將json資料轉成array
    
  if(count($info->rows)){
    foreach($info->rows as $record){
      if($record->modifyDate!=""){
        $a=$record->regsiterDate;             //編釘日期
        $b=$record->modifyDate;               //異動日期
        if($record->oldStrDoorplate==""){     //變更前地址
          $c="";
        }else{
          $c=$record->countyTownship.$record->oldStrDoorplate;
        }
        
        if($record->newStrDoorplate==""){     //變更前地址
          $d="";
        }else{
          $d=$record->countyTownship.$record->newStrDoorplate;
        }
        //$d=$record->countyTownship.$record->newStrDoorplate;   //變更後地址
        $e=$record->registerKind;                              //編釘類別
        fprintf($fp,"{$a},{$b},{$c},{$d},{$e}\n");        
      } 
    }
  }else{
    echo "[network error!!]";
  }
}


function Post($url, $post){
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
}