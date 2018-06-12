<?php
include_once(__DIR__."/simple_html_dom.php");
//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/cers/SearchLicList.do";    
$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";    //取得驗證碼圖片 
$timeout = 10;   //設置等待時間
$data_Digits = 4;   //判讀為數字(頁數)的位數
$page = 0;  


#可手動設定以下兩個陣列決定撈取的縣市以及年度
$countycode = array();
$countycode = array("台北市"=>"G00","高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                    "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0","南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                    "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
"金門縣"=>"J20");
$year = array("0"=>"100", "1"=>"101", "2"=>"102", "3"=>"103", "4"=>"104", "5"=>"105", "6"=>"106", "7"=>"107");

/**************************************main*************************************************/
$db = dbConnect();

$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);

$code = getCheckNumber($verify_code_url, $cookie_file);

 //運作模式為取得先取得同一縣市同一年度不同頁數 >> 取得同一縣市不同年度不同頁數 >> 取得不同縣市不同年度不同頁數
foreach($countycode as $countycodeKey => $countycodeValue){   //跑縣市
  
    foreach($year as $yearKey => $yearValue){   //跑年度
        
        for($license = 0 ; $license <= 9 ; $license++){
            $i=1;
            $license_no = $license;
            $zerofilling = $license;
            $zerofilling_aft = 0;
            $arr_rowdata = array();
            $flag = true;
            $i = 1; //在還未拿到正確頁數時先預設頁數為一
       
            do{   //跑頁數
                do{
                    $post = http_build_query(array("d-16544-p" => "$i", "budare" => $countycodeValue, "license_yy" => $yearValue, "license_no1" => "$license_no", "insrand" => $code, "submit" => '%ACd%B8%DF'));
                
                    $html = post($login_url, $post, $cookie_file, $timeout);
                    
                    $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼

                    if(!$html){
                        echo "Lost page 0!!\n";
                    }

                }while(checkExpired($html, $verify_code_url, $cookie_file, $timeout, $code) || !$html);
            
                $xpath = create_dom($html);

                if($i == 1){    //只要第一次拿到頁數就好了~~
                    $page = getpage($xpath, $data_Digits, $page);
                    if($page == 20){    //往右走
                        
                        $flag = false; 
                        if($zerofilling == 0){
                            $zerofilling = 1;
                        }
                        $zerofilling = str_pad($zerofilling, 2, "1", STR_PAD_LEFT);

                        $zerofilling_aft = $zerofilling;
                        
                    }

                    else{   //往下走

                        $zerofilling_lng = strlen($zerofilling_aft); //  111 211 311 411 
                        if($zerofilling_lng == 2 && floor($zerofilling_aft/10) != 9){
                            $zerofilling_aft = $zerofilling_aft + 10;
                            $flag = false;
                        }
                        else if($zerofilling_lng == 3 && floor($zerofilling_aft/100) != 9){
                            $zerofilling_aft = $zerofilling_aft + 100;
                            $flag = false;
                        }
                        else if($zerofilling_lng == 4 && floor($zerofilling_aft/1000) != 9){
                            $zerofilling_aft = $zerofilling_aft + 1000;
                            $flag = false;
                        }
                        else if($zerofilling_lng == 5 && floor($zerofilling_aft/10000) != 9){
                            $zerofilling_aft = $zerofilling_aft + 10000;
                            $flag = false;
                        }

                    }
                }   

                echo "page: {$countycodeKey} | {$yearValue} | {$license_no} | {$i}/{$page}\n";

                $postURL = getURLContent($xpath);   //獲取往下一層的連結

                if($i == $page && $flag == false){
                    
                    $license_no = $zerofilling_aft;
                    $i = 0;
                    $flag = true;
                }
                

                if($postURL){
                    $login_url_for_postURL = "http://cpabm.cpami.gov.tw/cers/SearchContDetial.do"; 
                    $check = 0;
                    foreach($postURL as $postURLKey => $postURLValue){
            
                        $postURLValue =  implode("", $postURLValue);   //將連結切割成字串使得可以當作post使用
                
                        do{
                            
                            $html = post($login_url_for_postURL, $postURLValue, $cookie_file, $timeout);
                            $html = iconv("Big5", "UTF-8//IGNORE", $html);

                            if(!$html){
                                echo "Lost page 1!!\n";
                            }

                        }while(checkExpired($html, $verify_code_url, $cookie_file, $timeout, $code) || !$html);
                    
                        $xpath = create_dom($html);
                        
                        $textcontent = getTEXTContent($xpath);   //回傳文字陣列
                        
                        $urlcontent = getTEXTContentWithURL($xpath);    //回傳下一層的連結
                        
                        $raw_data = match($textcontent, $urlcontent);   //將原字串"連結"替換成的url並存入原陣列(準備將資料寫入資料庫)

                        //print_r($raw_data);
                        
                        insertIndb($db, $raw_data, $fp);   //將資料存入資料庫
                    
                    }
                }
                $i++;
            }while($i <= $page);
        }
    }
    
}
mysqli_close($db);


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
    return trim($str);
}

//讀取資料並存入資料庫
function insertIndb(&$db, $raw_data, &$fp){

    if(!$fp){
        $fp = fopen("SQL_Error.txt","w");
    }
    
    //判斷過期
    if(!mysqli_ping($db)){
        echo 'Lost connection\n';
        mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
        dbConnect();
        insertIndb($db, $raw_data, $fp);
    }
   
    //主鍵設為contractor_name，不會有一直加入相同資料的問題

    $primary_key = $raw_data[0]."_".$raw_data[3];

    //

   $sql = "INSERT INTO `building_contractor` (contractor_ID, industry_name, contractor_name, registration_code, uniform_number, capital, address, award_punishment, evaluation_level, construction_assessment) 
   VALUES (N'$primary_key', N'$raw_data[0]', N'$raw_data[1]', N'$raw_data[2]', N'$raw_data[3]', N'$raw_data[4]', N'$raw_data[5]', N'$raw_data[6]', N'$raw_data[7]', N'$raw_data[8]')";

    if(!mysqli_query($db , $sql)){  //插入失敗
        
        if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){  //?
            if($raw_data[2] == "" && $raw_data[3] == "" && $raw_data[4] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";
            }
            else if($raw_data[2] == "" && $raw_data[3] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `capital`= N'$raw_data[4]', 
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";          
            }
            else if($raw_data[2] == "" && $raw_data[4] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `uniform_number`= N'$raw_data[3]', 
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'"; 
            }
            else if($raw_data[3] == "" && $raw_data[4] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `registration_code`= N'$raw_data[2]',  
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";         
            }
            else if($raw_data[2] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `uniform_number`= N'$raw_data[3]', `capital`= N'$raw_data[4]', 
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";          
            }
            else if($raw_data[3] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `registration_code`= N'$raw_data[2]', `capital`= N'$raw_data[4]', 
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";        
            }
            else if($raw_data[4] == ""){
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `registration_code`= N'$raw_data[2]', `uniform_number`= N'$raw_data[3]', 
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";
            }
            else{
                $sql = "UPDATE `building_contractor` SET `industry_name`= N'$raw_data[0]', `registration_code`= N'$raw_data[2]', `uniform_number`= N'$raw_data[3]', `capital`= N'$raw_data[4]', 
                `address`= N'$raw_data[5]', `award_punishment`= N'$raw_data[6]', `evaluation_level`= N'$raw_data[7]', `construction_assessment`= N'$raw_data[8]'where `contractor_ID`= N'$primary_key'";
            }
            //當讀到contractor_name相同時，主動去判斷其他欄位是否異變
           
            fwrite($fp, $sql.PHP_EOL);
            if(mysqli_query($db , $sql)){
                echo "Update: ".$raw_data[0]." complete<br>\n";
            }
            else{
                insertIndb($db, $raw_data);
            }
            
        }   
        else{
            echo "SQL Error: " . mysqli_error($db)."\n";
            fwrite($fp, $raw_data[0].",".$raw_data[1].",".$raw_data[2].",".$raw_data[3].",".$raw_data[4].",".$raw_data[5].",".$raw_data[6].",".$raw_data[7].",".$raw_data[8].PHP_EOL);
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
    //$db = mysqli_connect("db.sgis.tw", "sinicaintern", "27857108311", "building");
    $db = mysqli_connect("140.109.161.93", "ntpc", "ac6tmsks@a", "ntpc");
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
        $url = DeleteHtml($urlnode->childNodes[0]->textContent);
        if(strpos ($url, "p04")){

            $url = str_split($url, 1); //分割成1個字元存入陣列
            $url = array_splice($url, 26);
          
            array_push($postURL, $url);
        }       
    }
    return $postURL;  
}

//檢查驗證碼過期
function checkExpired($str, $verify_code_url, $cookie_file, $timeout, &$code){
    $html = str_get_html($str);
    if($html){
        $design_expired = $html->find('td[class=memo]');
        $resume_expired = $html->find('font');
        if($design_expired){
            foreach($design_expired as $expired){
                $expired = DeleteHtml($expired->innertext);
                if(strpos($expired, "驗證碼輸入錯誤") !== false){
                    echo "cookie expired reconnect...1\n";
                    $cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
                    $code = getCheckNumber($verify_code_url, $cookie_file);  
                    return 1;
                }
            }
            
        }
        if($resume_expired){
            foreach($resume_expired as $expired){
                $expired = DeleteHtml($expired->innertext);
                if(strpos($expired, "驗證碼錯誤") !== false){
                    echo "cookie expired reconnect...2\n";
                    $cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
                    $code = getCheckNumber($verify_code_url, $cookie_file); 
                    return 1; 
                }
            }    
        }
        return 0; 
          
    }
    return 1; 
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
function post($url, $post, $cookie_file, $timeout){  
    $curl = curl_init();
   
    curl_setopt($curl, CURLOPT_URL, $url);
   
    curl_setopt($curl, CURLOPT_HEADER, false);
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    
    curl_setopt($curl, CURLOPT_NOSIGNAL,1);
  
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);  //等待瀏覽器的回應時間
  
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    
    curl_setopt($curl, CURLOPT_POST,1); //開啟POST
  
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);  //傳遞要求參數給伺服器
   
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);

    $info = curl_getinfo($curl);
   
    $html = curl_exec($curl);
    if(!$html){
        echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
    }
    
    $curl_errno = curl_errno($curl);  
    $curl_error = curl_error($curl);
    if($curl_errno > 0){  
        echo "cURL Error ($curl_errno): $curl_error\n";  
    }

    
    curl_close($curl);
    
    return $html;
}

//取出驗證碼
function getCheckNumber($image_url, $cookie_file){
    
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
        echo "wait for code:\n";
        do{
            $handle = fopen ("php://stdin","r");
            $code = fgets($handle);
            fclose($handle);
        }while($code == FALSE);
        echo "enter complete\n";
    }
    else{
        passthru("imgcat valid.jpg");
        $code=readline("code:");
    }
    $code = DeleteHtml($code);
    return $code;
}


//取得頁數
function getpage($xpath, $data_Digits, $page){
  
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