<?php

//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/cers/SearchLicList.do";    
$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";    //取得驗證碼圖片
$file = 'code.txt';    
$timeout = 5;   //設置等待時間
/*$erase = "";*/

#define city->citycode
$countycode = array();
$countycode = array("台北市"=>"G00", "高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                  "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0","南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                  "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
                  "金門縣"=>"J20");

/*foreach($countycode as $conutyKey => $countyValue){
    echo $conutyKey;
    $fp = create_txt($conutyKey);

}*/
/**************************************main*************************************************/
$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url, $cookie_file, $file);
file_put_contents($file ,"");
$post = http_build_query(array("budare" => "G00", "license_yy" => "100", "license_no1" => "", "insrand" => $code, "submit" => '%ACd%B8%DF'));
$html = post($login_url, $post, $cookie_file);

//BIG5 to UTF8。加上IGNORE以忽略非法字眼
$html = iconv("Big5", "UTF-8//IGNORE", $html); 
$xpath = create_dom($html);
$page = getpage($xpath);


exit;

//登錄需要驗證的參數，根據登錄的網站要求而定

/*********************************************************************************************/


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

function getcountyValue($ckey){

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
    $code = "";    
    do{
        $code = file_get_contents($file);
    }while($code == FALSE);
 
    return $code;
}

//echo  $html;



//取得頁數
function getpage($xpath){
    foreach($xpath->query('//span[@class = "pagebanner"]') as $node) {  //取得頁數
        $temp = array();
        $temp = str_split($node->textContent, 1); //分割成1個字元存入陣列
        $temp = array_splice($temp, 17 ,19);/**/ 
        $temp = array_splice($temp, 20);/**/
        print_r($temp);
        
    }
}

//創建文檔，傳入各縣市
function create_txt($cKey){
    $fp=fopen("{$cKey}.txt","w");
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




/*foreach ($xpath->query('//table/tr[@class = "list0"]') as $node) {   //取table,tr.class = list0之列
    
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
  
  
?>