<?php
mb_regex_encoding('UTF-8');
mb_internal_encoding('UTF-8');

//初始化變量



header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchBusiness.jsp";    
$verify_code_url = "http://cpabm.cpami.gov.tw/img_code.jsp";    //取得驗證碼圖片
$timeout = 5;

$MAX = 10000; //最大化表格列數，解決翻頁問題(暫時)

if(1){
  $GLOBALS['ckfile']=$cookie_file;
  @unlink($GLOBALS['ckfile']);
  do{
    $err=false;
    if(!file_exists("code.txt")){ 
      $m=getURLContent($verify_code_url); //直接從圖片獲得server端的session id
      file_put_contents("valid.jpg",$m);  //儲存圖片  
      if(0){
        passthru("imgcat valid.jpg"); //直接在終端機顯示圖片,必須搭配mac os + iTerm2 + imgcat
        $code=readline("驗證碼: ");  //等待使用者輸入驗證碼
      }
      else
        $code=readline("驗證碼: ");  //等待使用者自行查看圖片,並輸入驗證碼
    }
    else
      $code=file_get_contents('code.txt');
    echo "使用驗證碼: {$code}\n";
    $m=getURLContent($login_url,[
      "showRows" => $MAX, 
      "com_id_no" => "", 
      "come_name" => "", 
      "com_id_area" => "B", 
      "pwd_name" => "", 
      "pwd_id" => "", 
      "insrand" => $code]);
    $m=utf8($m);
    if(strpos($m,"驗證碼輸入錯誤")!==false){
      $err=true;
      echo "驗證碼輸入錯誤\n";
    }
  }while($err==true);
  $html=$m;
}
else{
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $login_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
  if(!file_exists($cookie_file))
    curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie_file); //獲取COOKIE並存儲
  else
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
  echo $cookie_file;
  $contents = curl_exec($curl);
  curl_close($curl);


  if(!file_exists('code.txt')){
    //取出驗證碼
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $verify_code_url);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    curl_close($curl);

    //保存驗證碼圖片
    $fp = fopen("valid.jpg","wb");
    fwrite($fp, $data);
    fclose($fp);

    $code = "";

    //直到寫入驗證碼到code.txt
    do{
      $code = @file_get_contents('code.txt');
      echo "waiting code.txt....\n";
      usleep(0.1*1000*1000);
    }while($code == FALSE);
  }
  else
    $code=file_get_contents("code.txt");

  //登錄需要驗證的參數，根據登錄的網站要求而定
  $post = http_build_query(array("showRows" => $MAX, "com_id_no" => "", "come_name" => "", "com_id_area" => "B", "pwd_name" => "", "pwd_id" => "", "insrand" => $code));

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $login_url);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
  curl_setopt($curl, CURLOPT_POST,1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
  $html = curl_exec($curl);
  echo $html;
  curl_close($curl);
  //clear code.txt
  $erase = "";

  file_put_contents("code.txt",$erase);
  //BIG5 to UTF8。加上IGNORE以忽略非法字眼
  //$html = iconv("Big5", "UTF-8//IGNORE", $html); 
  $html=utf8($html);
}


/*$dom = new DOMDocument;
$encoding = mb_detect_encoding($html);
$html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);               //告訴dom檔案格式為utf-8
$xpath =new DOMXpath($dom);



foreach ($xpath->query('//table/tr[@class = "list0"]') as $node) {   //取table,tr.class = list0之列
    
    if($xpath->query('td/img', $node)) {                             //判斷每一列開頭是否有圖片
        foreach ($xpath->query('td/img/@src', $node) as $cell) {     //取出圖片網址
            $rowcleaned = $cell->textContent;
            if($rowcleaned == "/images/notation/hand.gif")           //判斷何種圖片
                echo "註銷";
            else
                echo "失效";
        }
        foreach ($xpath->query('td', $node) as $cell) {              //印出有圖片列之相關資訊
            $rowcleaned = $cell->textContent;
            echo $rowcleaned . "<br />";
        }
        echo "<br />";
        echo "<br />";
    } 
    
    else {                                                            //沒有圖片的列
        foreach ($xpath->query('td', $node) as $cell) {
            $rowcleaned = $cell->textContent;
            echo $rowcleaned . "<br />";
            
        }
        echo "<br />";
        echo "<br />";
    }   
}

foreach ($xpath->query('//table/tr[@class = "list1"]') as $node) {   
    
    if($xpath->query('td/img', $node)) {
        foreach ($xpath->query('td/img/@src', $node) as $cell) {
            $rowcleaned = $cell->textContent;
            if($rowcleaned == "/images/notation/hand.gif")
                echo "註銷";
            else
                echo "失效";
        }
        foreach ($xpath->query('td', $node) as $cell) {
            $rowcleaned = $cell->textContent;
            echo $rowcleaned . "<br />";
        }
        echo "<br />";
        echo "<br />";
    } 
    
    else {
        foreach ($xpath->query('td', $node) as $cell) {
            $rowcleaned = $cell->textContent;
            echo $rowcleaned . "<br />";
            
        }
        echo "<br />";
        echo "<br />";
    }   
}*/
  
function utf8($m){
  return mb_convert_encoding($m,'UTF-8','BIG-5');
}

function getURLContent($url,$post=false,$timeout=30) {
  if (!function_exists('curl_init')) {
    throw new Exception('server not install curl');
  }
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, false);  //curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //從伺服器接收緩衝完成前需要等待多少時間
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout-2); //成功連接伺服器前須要等待多久
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false); //連接https
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
  
  //設定cookies file
  curl_setopt($ch,CURLOPT_COOKIEJAR, $GLOBALS['ckfile']);   //收到的cookie
  curl_setopt($ch,CURLOPT_COOKIEFILE, $GLOBALS['ckfile']);  //欲傳送的cookie
  
  curl_setopt($ch, CURLOPT_HTTPHEADER, [  //要傳輸給header的數組
    'Accept-Language:zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7'
  ]);
  
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'); //使用者代理
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 

  if($post!==false){
    if(is_array($post))
      $post=http_build_query($post);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
  }
  
  if(1){  //debug
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $response = curl_exec($ch);   
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $result = substr($response, $header_size);
    echo "[header]\n";
    print_r($header);
  }
  else
    $result = curl_exec($ch);
  curl_close($ch);
  //echo "[cookie_file] {$url}\n".file_get_contents($GLOBALS['ckfile'])."\n";
  if($result!==false){
    return $result;
    echo $result;
  }
  else
    return false;
}

?>