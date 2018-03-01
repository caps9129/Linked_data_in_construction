<?php
ini_set('max_execution_time', '0');
ini_set("memory_limit","8192M");
//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/cers/SearchLicList.do";    
$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";    //取得驗證碼圖片
$file = 'code.txt';    
$timeout = 10;   //設置等待時間
$data_Digits = 4; 
$page = 1;  //傳入與取得頁數(設為"1"是為了在迴圈跑動第一次，以取得正確的page頁數)



#可手動設定以下兩個陣列決定撈取的縣市以及年度
$countycode = array();
$countycode = array("台北市"=>"G00"/*, "高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                    "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0","南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                    "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
                    "金門縣"=>"J20"*/);
$year = array("0"=>"100"/*, "1"=>"101", "2"=>"102", "3"=>"103", "04"=>"104", "05"=>"105","06"=>"106", "07"=>"107"*/);

/**************************************main*************************************************/

$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url, $cookie_file, $file);

 //運作模式為取得先取得同一縣市同一年度不同頁數 >> 取得同一縣市不同年度不同頁數 >> 取得不同縣市不同年度不同頁數
foreach($countycode as $countycodeKey => $countycodeValue){  //跑縣市
    $fp = create_txt($countycodeKey);
    foreach($year as $yearKey => $yearValue){   //跑年度

        for($i = 1 ; $i <= $page ; $i++){   //跑頁數
            $post = http_build_query(array("d-16544-p" => "$i", "budare" => $countycodeValue, "license_yy" => $yearValue, "license_no1" => "", "insrand" => $code, "submit" => '%ACd%B8%DF'));
          
            $html = post($login_url, $post, $cookie_file);
            
            $html = iconv("Big5", "UTF-8//IGNORE", $html); //BIG5 to UTF8。加上IGNORE以忽略非法字眼
         
            $xpath = create_dom($html);
            
            $fp = getContent($xpath, $fp);
            if($i == 1){    //只要第一次拿到頁數就好了~~
                $page = getpage($xpath, $data_Digits, $page);
            }   
        }
        
        
        
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

//取得網頁中表格內容並寫入相對應的文件
function getContent($xpath, $fp){ 
    fwrite($fp, PHP_EOL);
    foreach($xpath->query('//tr[@class = "even"]') as $node){
        $content =  $node->textContent;
        $content = DeleteHtml($content);
        fprintf($fp, $content.PHP_EOL);
        echo $content."<br>";
    } 
    foreach($xpath->query('//tr[@class = "odd"]') as $node){
        $content =  $node->textContent;
        $content = DeleteHtml($content);
        fprintf($fp, $content.PHP_EOL);
        echo $content."<br>";
    } 
    return $fp;
    fclose($fp);
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


//取得頁數
function getpage($xpath, $data_Digits, $page){
    /*if($xpath){*/
    foreach($xpath->query('//span[@class = "pagebanner"]') as $node) {  //取得頁數
        $temp = array();    //儲存所有可能字元
        $temp_1 = array();  //儲存正確字元
        $temp = str_split($node->textContent, 1); //分割成1個字元存入陣列
        $temp = array_splice($temp, 17 ,19);/**/ 
        $temp = array_splice($temp, 0, $data_Digits);/*取4位數，之後筆數可以往後取*/
        foreach($temp as $tempKey => $tempValue){
            if(is_numeric($tempValue)){ //判斷是否為數字
                array_push($temp_1, $tempValue);
            }
        }  
        $page =  implode("", $temp_1);
        $page = ceil($page / 10.0); //每一頁10筆
        return $page;
    }
}

//創建文檔，傳入各縣市
function create_txt($countycodeKey){
    $fp=fopen("{$countycodeKey}.txt","w");
    fprintf($fp,"建造執照,建築地點,起造人,設計人,監造人,承造人\n");
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