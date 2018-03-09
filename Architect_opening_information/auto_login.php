<?php

//初始化變量



header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchBusiness.jsp";    
$verify_code_url = "http://cpabm.cpami.gov.tw/img_code.jsp";    //取得驗證碼圖片
$curl = curl_init();
$timeout = 5;

$MAX = 10000; //最大化表格列數，解決翻頁問題(暫時)

$countycode = array("台北市"=>"B", "高雄市"=>"C", "基隆市"=>"F", "宜蘭縣"=>"G", "新北市"=>"H", "桃園市"=>"I", "新竹市"=>"J", "新竹縣"=>"K", 
                    "苗栗縣"=>"L", "台中市"=>"M", "台中縣"=>"N", "彰化縣"=>"O", "南投縣"=>"P", "雲林縣"=>"Q", "嘉義市"=>"R", "嘉義縣"=>"S", 
                    "台南市"=>"T", "台南縣"=>"U", "高雄縣"=>"V", "屏東縣"=>"W", "花蓮縣"=>"X", "台東縣"=>"Y", "澎湖縣"=>"Z", "連江縣"=>"E",
                    "金門縣"=>"D");

curl_setopt($curl, CURLOPT_URL, $login_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie_file); //獲取COOKIE並存儲
echo $cookie_file;
$contents = curl_exec($curl);
curl_close($curl);

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
    $code = file_get_contents('code.txt');
}while($code == FALSE);


//登錄需要驗證的參數，根據登錄的網站要求而定
$post = http_build_query(array("showRows" => $MAX, "com_id_no" => "", "come_name" => "", "com_id_area" => "B", "pwd_name" => "", "pwd_id" => "", "insrand" => $code));
echo $post;

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $login_url);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
curl_setopt($curl, CURLOPT_POST,1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
$html = curl_exec($curl);
curl_close($curl);

//clear code.txt
$erase = "";

file_put_contents("code.txt",$erase);

//BIG5 to UTF8。加上IGNORE以忽略非法字眼
$html = iconv("Big5", "UTF-8//IGNORE", $html); 

echo  $html;

$dom = new DOMDocument;
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
}
  
  
?>