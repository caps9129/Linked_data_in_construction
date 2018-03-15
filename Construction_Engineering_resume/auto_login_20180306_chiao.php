<?php
/*ni_set('max_execution_time', '0');
ini_set("memory_limit","8192M");

ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/

include_once(__DIR__."/simple_html_dom.php");


//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/cers/SearchLicList.do";    
$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";    //取得驗證碼圖片
$file = 'code.txt';    
$timeout = 10;   //設置等待時間
$data_Digits = 4;   //判讀為數字(頁數)的位數
$page = 1;  //傳入與取得頁數(設為"1"是為了在迴圈跑動第一次，以取得正確的page頁數)


#可手動設定以下兩個陣列決定撈取的縣市以及年度
$countycode = array();
$countycode = array("台北市"=>"G00", "高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                    "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0","南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                    "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
"金門縣"=>"J20");
$year = array("0"=>"100", "1"=>"101", "2"=>"102", "3"=>"103", "4"=>"104", "5"=>"105", "6"=>"106", "7"=>"107");

/**************************************main*************************************************/

$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url, $cookie_file, $file);
$db = dbConnect();
 //運作模式為取得先取得同一縣市同一年度不同頁數 >> 取得同一縣市不同年度不同頁數 >> 取得不同縣市不同年度不同頁數
foreach($countycode as $countycodeKey => $countycodeValue){   //跑縣市
    foreach($year as $yearKey => $yearValue){   //跑年度
        $i=1;
        do{

            $post = http_build_query(array("d-16544-p" => "$i", "budare" => $countycodeValue, "license_yy" => $yearValue, "license_no1" => "", "insrand" => $code, "submit" => '%ACd%B8%DF'));
          
            $html = post($login_url, $post, $cookie_file);

            $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼
        
            $xpath = create_dom($html);

            if($i == 1){    //只要第一次拿到頁數就好了~~
                $page = getpage($xpath, $data_Digits, $page);
                /*echo "PAGE:".$page."<br>\n";/**/
            }

            

            echo "page: {$countycodeKey} | {$yearValue} | {$i}/{$page}<br>\n";

            //$postURL = getURLContent($xpath);   //獲取往下一層的連結
            $postURL = getURLContent2($html);   //獲取往下一層的連結

            if($postURL){
        
                $login_url_for_postURL = "http://cpabm.cpami.gov.tw/cers/SearchContDetial.do"; 
                foreach($postURL as $postURLKey => $postURLValue){
                    do{
                        $html = post($login_url_for_postURL, $postURLValue, $cookie_file);
                        $html = iconv("Big5", "UTF-8//IGNORE", $html);
                        $xpath = create_dom($html);
                        $textcontent = getTEXTContent($xpath);   //回傳文字陣列
                        $urlcontent = getTEXTContentWithURL($xpath);    //回傳下一層的連結
                        $raw_data = match($textcontent, $urlcontent);   //將原字串"連結"替換成的url並存入原陣列(準備將資料寫入資料庫)
                    }while(!$raw_data);
                  
                    insertIndb($db, $raw_data);   //將資料存入資料庫

                }
            }
            $i++;
        }while($i<=$page);
        
    }
    
}
mysqli_close($db);
if(unlink($file))
    echo "成功刪除!!\n";
else
    echo "刪除失敗!!\n";

exit;


/*********************************************************************************************/

//濾掉多餘的字元，好讓字串可以順利存入資料庫
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

//讀取資料並存入資料庫
function insertIndb($db, $raw_data){
    //主鍵設為contractor_name，不會有一直加入相同資料的問題
    $sql = "INSERT INTO `building_contractor` (Industry_name, contractor_name, registration_code, uniform_number, capital, address, award_punishment, evaluation_level, construction_assessment) 
    VALUES (N'$raw_data[0]', N'$raw_data[1]', N'$raw_data[2]', N'$raw_data[3]', N'$raw_data[4]', N'$raw_data[5]', N'$raw_data[6]', N'$raw_data[7]', N'$raw_data[8]')";
    if(!mysqli_query($db , $sql)){  //插入失敗
        if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
            //當讀到contractor_name相同時，主動去判斷其他欄位是否異變
            $sql = "UPDATE `building_contractor` SET `Industry_name`= N'$raw_data[0]', `registration_code`= N'$raw_data[2]', `uniform_number`= N'$raw_data[3]', `capital`= N'$raw_data[4]', 
                   `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_name`= N'$raw_data[1]'";
            mysqli_query($db , $sql);
            echo "Update: ".$raw_data[0]." complete<br>\n";
        }   //
        /*elseif(!mysqli_ping($db)){
            echo 'Lost connection\n';
            mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
            dbConnect();
            insertIndb($db, $raw_data);
        }*/
        else{
            echo "SQL Error: " . mysqli_error($db)."\n";
            exit;
        }
    }
    else    
        echo "Insert: ".$raw_data[0]." complete<br>\n";
    
}

//將原字串"連結"替換成的url並存入原陣列
function match($textcontent, $urlcontent){
    $urlkey = 0;
    foreach($textcontent as $textkey => $content){
        if(!strcmp($content, "連結")){
            $textcontent[$textkey] = $urlcontent[$urlkey];
            $urlkey++;
        }
    }
    return $textcontent;
}

//回傳下一層的連結
function getTEXTContentWithURL($xpath){
    $urlcontent = array();
    foreach($xpath->query('//td[@class="td"]//@href') as $rowIdx=> $textnode){
        array_push($urlcontent, DeleteHtml($textnode->textContent));
    }
    return $urlcontent;
}


//回傳文字陣列
function getTEXTContent($xpath){
    $textcontent = array();
    foreach($xpath->query('//td[@class="td"]') as $rowIdx=> $textnode){
        array_push($textcontent, DeleteHtml($textnode->textContent));
    }
    return $textcontent;
}

//連接資料庫
function dbConnect(){
    $db = mysqli_connect("db.sgis.tw", "sinicaintern", "27857108311", "building");
    /*$db = mysqli_connect("10.21.100.7", "root", "", "building",53306);*/
    if(!$db){
        die("dbConnect fail". mysqli_connect_error()."\n");
        exit;
    }
    else{
        echo "dbConnect Successful!!<br>\n";
        return $db;    
    }
}

//取得網頁中表格內容並寫入相對應的文件
function getURLContent($xpath){ 
    $postURL = array();
    foreach($xpath->query('//div[@class="content2"]//tr//@href') as $rowIdx=> $urlnode){
        $url = trim($urlnode->childNodes[0]->textContent);
        if(strpos ($url, "p04")){
            $url = str_split($url, 1); //分割成1個字元存入陣列
            $url = array_splice($url, 26);
            $postURLValue =  implode("", $postURLValue);
            
            array_push($postURL, $url);
        }       
    }
    return $postURL;  
}

function getURLContent2($str){ 
    $postURL = array();

    $html = str_get_html($str);
    if($html){
        //尋找網頁中id=row的table
        $table = $html->find('table[id=row]',0);    //第二參數表示:只取第一個表格
        if($table){
            foreach($table->find('a') as $a){
                $url = DeleteHtml($a->href);
                if(preg_match('/([^?]+)(\?)(p04_id=)([\w]+)/',$url,$matches)){
                    /*
                    在 $url="/cers/SearchContDetial.do?p04_id=A05069"; 的情況下
                    $matches會是
                    Array
                    (
                        [0] => /cers/SearchContDetial.do?p04_id=A05069
                        [1] => /cers/SearchContDetial.do
                        [2] => ?
                        [3] => p04_id=
                        [4] => A05069
                    )
                    因此參數會是 matches[3~4] 之組合
                    */
                    $postURL[]="{$matches[3]}{$matches[4]}";
                }
            }
        }
    }
    return $postURL;  
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
    if(1){
        fopen("{$file}", "w");
        do{
            $code = file_get_contents($file);
        }while($code == FALSE);
    }
    else{
        passthru("imgcat valid.jpg");
        $code=readline("code:");
    }
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

function create_dom($html){
    $dom = new DOMDocument;
    $encoding = mb_detect_encoding($html);
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);               //告訴dom檔案格式為utf-8
    $xpath =new DOMXpath($dom);
    return $xpath;
}

  
  
?>