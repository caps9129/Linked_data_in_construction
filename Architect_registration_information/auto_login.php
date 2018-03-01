<?php
ini_set('max_execution_time', '0');
ini_set("memory_limit","8192M");
//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchInfo.jsp";    
$verify_code_url = "http://cpabm.cpami.gov.tw/img_code.jsp";    //取得驗證碼圖片
$file = 'code.txt';    
$timeout = 10;   //設置等待時間
$data_Digits = 4; //取得可能為頁數字元之數量
$page = 0;  //傳入與取得頁數(設為"1"是為了在迴圈跑動第一次，以取得正確的page頁數)




#define city->jobcode
$jobcode = array("開業"=>"1"/*, "專業工程人員"=>"2", "公務員"=>"3","教授兼公務員"=>"4", "其它"=>"5"*/);


/**************************************main*************************************************/

$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url, $cookie_file, $file);
foreach($jobcode as $jobcodeKey => $jobcodeValue){
    $fp = create_txt($jobcodeKey);
    $post = http_build_query(array("id_no_d21" => "", "name_d21" => "", "edu_level_d21" => "AA", "capacity_get_d21" => "AA", "job_d21" => $jobcodeValue, "insrand" => $code));
    $html = post($login_url, $post, $cookie_file);        
    $html = iconv("Big5", "UTF-8//IGNORE", $html); //BIG5 to UTF8。加上IGNORE以忽略非法字眼
    $xpath = create_dom($html);
    $page = getpage($xpath, $data_Digits, $page);
    for($i = 1 ; $i <= $page ; $i++){
        $post = http_build_query(array("id_no_d21" => "", "name_d21" => "", "edu_level_d21" => "AA", "capacity_get_d21" => "AA", "job_d21" => $jobcodeValue, "insrand" => $code, "pageCount" => $page, "showRows" => "15", "pageNo" => $i));
        $html = post($login_url, $post, $cookie_file);
        $html = iconv("Big5", "UTF-8//IGNORE", $html);
        $xpath = create_dom($html);
        $fp = getContent($xpath, $fp);
    } 
           
}

 
    
if(unlink($file))
    echo "成功刪除!!";
else
    echo "刪除失敗!!";


exit;


/*********************************************************************************************/

//清除多餘字眼
function DeleteHtml($str){
    $str = trim($str);
    $str = strip_tags($str,"");
    $str = str_replace("\t","",$str);
    $str = str_replace("\r\n","",$str); 
    $str = str_replace("\r","",$str); 
    $str = str_replace("\n","",$str); 
    $str = str_replace(" "," ",$str); 
    return $str;
}

function get_mark($xpath, $node){
    $flag = TRUE;
    if($xpath->query('img/@src', $node)){
        foreach($xpath->query('td/img/@src', $node) as $src){
            if($src->textContent == "/images/notation/hand.gif"){
                $mark = "註銷  ";
                return $mark;
            }
            elseif($src->textContent == "/images/notation/hand2.gif"){
                $mark = "失效  ";
                return $mark;
            }  
        }
        $flag = FALSE;
    }
    if($flag == FALSE){
        $mark = "正常  ";
        return $mark;    
    }
}

//取得網頁中表格內容並寫入相對應的文件
function getContent($xpath, $fp){ 
    fwrite($fp, PHP_EOL);
    foreach($xpath->query('//tr[@class = "list0"]') as $node){
        $mark = get_mark($xpath, $node);
        $content =  $node->textContent;
        $content = DeleteHtml($content);
        fprintf($fp, $mark.$content.PHP_EOL);
    } 
    foreach($xpath->query('//tr[@class = "list1"]') as $node){
        $mark = get_mark($xpath, $node);
        $content =  $node->textContent;
        $content = DeleteHtml($content);
        fprintf($fp, $mark.$content.PHP_EOL);
    } 
    return $fp;
}

//取得session
function getCookie($cookie_url, $cookie_file, $timeout){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $cookie_url); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //以輸出文件的方式取代直接輸出
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);  //等待瀏覽器的回應時間
    curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie_file); //獲取COOKIE並存儲
    $contents = curl_exec($curl);
    curl_close($curl);
    return $cookie_file;
}

//傳入參數並回傳內容
function post($url, $post, $cookie_file){  
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl, CURLOPT_POST,1); //開啟POST
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);  //傳遞要求參數給伺服器
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
    $html = curl_exec($curl);
    curl_close($curl);
    return $html;
}

//取出驗證碼
function getCheckNumber($image_url, $cookie_file, $file){
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $image_url);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);   //發送COOKIE給瀏覽器
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    curl_close($curl);

    //保存驗證碼圖片
    $fp = fopen("valid.jpg","wb");
    fwrite($fp, $data);
    fclose($fp);
    //初始化
    $code = FALSE;
    $code = "";    
    fopen("{$file}", "w");
    do{
        $code = file_get_contents($file);
    }while($code == FALSE);
    return $code;
}

//echo  $html;



//取得頁數
function getpage($xpath, $data_Digits, $page){
    /*if($xpath){*/
    foreach($xpath->query('//td[@class = "listb"]') as $node) {  //取得頁數
        $temp = array();    //儲存所有可能字元
        $temp_1 = array();  //儲存正確字元
        $temp = str_split($node->textContent, 1); //分割成1個字元存入陣列
        
        $temp = array_splice($temp, 14 ,$data_Digits);
        
        foreach($temp as $tempKey => $tempValue){
            if(is_numeric($tempValue)){ //判斷是否為數字
                array_push($temp_1, $tempValue);
            }
        }
        $page =  implode("", $temp_1);
        return $page;
    }
}

//創建文檔，傳入各縣市
function create_txt($countycodeKey){
    $fp=fopen("{$countycodeKey}.txt","w");
    fprintf($fp,"註記,建築師姓名,建築師證書字號,開業證號,事務所名稱\n");
    return $fp;
}

function create_dom($html){
    $dom = new DOMDocument;
    $encoding = mb_detect_encoding($html);
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);               //告訴dom檔案格式為utf-8
    $xpath =new DOMXpath($dom);
    return $xpath;
}

  
  
?>