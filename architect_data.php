<?php

include_once(__DIR__."/simple_html_dom.php");
ini_set('memory_limit', '-1');

//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/cers/SearchLicList.do";    
$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";    //取得驗證碼圖片   
$timeout = 10;   //設置等待時間
$data_Digits = 4;   //判讀為數字(頁數)的位數
$page = 1;   //傳入與取得頁數(設為"1"是為了在迴圈跑動第一次，以取得正確的page頁數)
$arr_data_design = array();   //設計人資訊
$arr_data_supervise = array();   //監造人資訊
$arr_total_data = array();   //結合設計人以及監造人資訊


#可手動設定以下兩個陣列決定撈取的縣市以及年度
$countycode = array();
$countycode = array("台北市"=>"G00"/*, "高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                    "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0","南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                    "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
"金門縣"=>"J20"*/);
$year = array("0"=>"100", "1"=>"101", "2"=>"102", "3"=>"103", "4"=>"104", "5"=>"105", "6"=>"106", "7"=>"107");

/**************************************main***************************************************************/

$db = dbConnect();

$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url, $cookie_file);


echo "Start Collect Data......\n";

 //運作模式為取得先取得同一縣市同一年度不同頁數 >> 取得同一縣市不同年度不同頁數 >> 取得不同縣市不同年度不同頁數
foreach($countycode as $countycodeKey => $countycodeValue){   //跑縣市
    foreach($year as $yearKey => $yearValue){   //跑年度
        $i=1;
        do{
            do{
                $post = http_build_query(array("d-16544-p" => $i, "budare" => $countycodeValue, "license_yy" => $yearValue, "license_no1" => "", "insrand" => $code, "submit" => '%ACd%B8%DF'));
            
                $html = post($login_url, $post, $cookie_file);

                $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼

                if(!$html)
                    echo "Lost resume page!!\n";

            }while(checkExpired($html, $verify_code_url, $cookie_file, $timeout, $code) || !$html);

            $xpath = create_dom($html);

            if($i == 1){    //只要第一次拿到頁數就好了~~
                $page = getpage($xpath, $data_Digits, $page);
              
            }

            echo "page: {$countycodeKey} | {$yearValue} | {$i}/{$page}<br>\n";

            $postURL = getURLContent($html);   //獲取往下一層的連結
            if($postURL){

                $login_url_p02_for_postURL = "http://cpabm.cpami.gov.tw/cers/SearchDesignDetial.do"; 
                $login_url_p03_for_postURL = "http://cpabm.cpami.gov.tw/cers/SearchSupDetial.do";
                foreach($postURL as $postURLValue){
                
                    if(preg_match('/(p02_code=)([\w]+)/', $postURLValue)){  //設計人連結
                        do{
                            $design_data = array();
                            $html = post($login_url_p02_for_postURL, $postURLValue, $cookie_file);
                            $html = iconv("Big5", "UTF-8//IGNORE", $html);
                            if(!$html)
                                echo "Lost html\n";
                            else{   
                                $design_data = getDesignContent($html);         //取得設計人資訊
                                print_r($design_data);
                            }
                        }while(!$design_data || checkExpired($html, $verify_code_url, $cookie_file, $timeout, $code) || !$html); //不確定能不能檢查出來這裡的過期
                        array_push($arr_data_design, $design_data);
                    }
                    else{                                                   //監造人連結
                        do{
                            $supervise_data = array();
                            $html = post($login_url_p03_for_postURL, $postURLValue, $cookie_file);
                            $html = iconv("Big5", "UTF-8//IGNORE", $html);
                            if(!$html)
                                echo "Lost html\n";
                            else{  
                                $supervise_data = getSuperviseContent($html);   //取得監造人資訊
                                print_r($supervise_data);
                            }
                        }while(!$supervise_data || checkExpired($html, $verify_code_url, $cookie_file, $timeout, $code) || !$html);   
                        array_push($arr_data_supervise, $supervise_data);
                    }
                }
            }
            $i++;
        }while($i<=$page);
        
    }

}

//將兩者資訊一起PUSH到$arr_total_data，準備以$arr_total_data來獲取剩下ID，存進資料庫
array_push($arr_total_data, $arr_data_design);
array_push($arr_total_data, $arr_data_supervise);



/***************************************Insert data*************************************************/

//建築師登記資料變數宣告

$info_login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchInfo.jsp";
$info_verify_code_url = "http://cpabm.cpami.gov.tw/img_code.jsp";
$info_page = 1;

$arr_update_data = array();
$education_level = array("博士"=>"00", "碩士"=>"01", "學士"=>"02", "專科"=>"03", "高中"=>"04", "國中"=>"05", "國小"=>"06");
$capacity_get = array("建築技師高考及格"=>"00", "建築技師檢覈"=>"01", "建築技師逕為登記"=>"02", "建築技師特考及格"=>"03", "土木技師高考及格"=>"04", 
                      "土木技師檢覈及格"=>"05", "土木技師逕為登記"=>"06", "建築師高考及格"=>"07", "建築師檢覈及格"=>"08", "外國人建築審核"=>"09");
$job = array("開業"=>"1", "專業工程人員"=>"2", "公務員"=>"3", "教授兼建築師"=>"4", "其他"=>"5");


$cookie_file = getCookie($info_verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($info_verify_code_url, $cookie_file);  

echo "Start Insert Data......\n";

foreach($arr_total_data as $rowdata){

    foreach($rowdata as $row){
    
        $total_data = array();

        for($i = 0 ; $i < 3 ; $i ++){
            array_push($total_data, $row[$i]);  //$total_data[name][outstanding][punishment]
        }

        $total_name = $row[0];
        $total_post_name = encode($total_name); //將名字轉進行轉碼以及urlencode才能被拿來作為post參數用
        
        do{
            $total_post_getID = "id_no_d21=&name_d21=$total_post_name&edu_level_d21=&capacity_get_d21=&job_d21=&insrand=$code";
        
            $html = post($info_login_url, $total_post_getID, $cookie_file);
            $html = iconv("Big5", "UTF-8//IGNORE", $html);

            if(!$html)
                echo "Lost profile page\n";

        }while(checkExpired($html, $info_verify_code_url, $cookie_file, $timeout, $code) || !$html);  //檢查SESSION過期
        
        if($html){
            $id = getID($html); //透過名字取得ID
            
            array_push($total_data, $id);   //$total_data[name][outstanding][punishment][ID]
        }
        else{
            echo "Query ID failed!!!\n";
        }
      
        $fp = insert_In_DB($db, $total_data, $fp);     //存進資料庫
      
    }
}  


/**************************************************update data****************************************************/

echo "Start Update Data......\n";

//將三個陣列做結合

array_push($arr_update_data, $education_level);
array_push($arr_update_data, $capacity_get);
array_push($arr_update_data, $job);   


//2D array => [0][edu] , [1][cap] , [2][job]

foreach($arr_update_data as $update_data_key => $update_data_value){
    
    foreach($update_data_value as $data_key => $data_value){

        

    $i = 1;    
    $array_data = array();    

    do{    

        do{
            if($update_data_key == 0){  
                $post = "id_no_d21=&name_d21=&showRows=15&edu_level_d21=$data_value&capacity_get_d21=&job_d21=&insrand=$code&pageNo=$i";  //post教育
                //echo $data_key ."=>". $post."\n";
            }
            elseif($update_data_key == 1){
                $post = "id_no_d21=&name_d21=&showRows=15&edu_level_d21=&capacity_get_d21=$data_value&job_d21=&insrand=$code&pageNo=$i";  //post執照  
                //echo $data_key ."=>". $post."\n";
            }
            elseif($update_data_key == 2){
                $post = "id_no_d21=&name_d21=&showRows=15&edu_level_d21=&capacity_get_d21=&job_d21=$data_value&insrand=$code&pageNo=$i";  //post職業
                //echo $data_key ."=>". $post."\n";
            }
        
            
            $html = post($info_login_url, $post, $cookie_file);
            $html = iconv("Big5", "UTF-8//IGNORE", $html);
            
        }while(checkExpired($html, $info_verify_code_url, $cookie_file, $timeout, $code)); //檢查session過期
        
        if($i == 1){
            $info_page = getinfopage($html);    //取得頁數
        }

        echo "page: {$data_key} | {$i}/{$info_page}<br>\n";

        $array_data = getinformation($html, $data_key); //取得[ID]=>職業/執照/教育
        
        if($array_data){
            update_info_DB($db, $array_data, $update_data_key); //存入資料庫
        }

        $i++;
    }while($i <= $info_page);
    }
}

mysqli_close($db);
exit;


/********************************************/

/***********************************************************************************************************/

//更新建築師細部資訊
function update_info_DB($db, $array_data, $classification){
    //更新教育
    if($classification == 0){
        foreach($array_data as $ID => $education){
            
            $sql = "INSERT INTO `architect_information` (architect_ID, education_level) VALUES (N'$ID', N'$education')";

            if(!mysqli_query($db , $sql)){
                if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
                    $sql = "UPDATE `architect_information` SET `education_level`= N'$education' where `architect_ID`= N'$ID'"; 
                    mysqli_query($db , $sql);
                    echo "Update: ".$ID." complete<br>\n";     
                }
                elseif(!mysqli_ping($db)){
                    echo 'Lost connection\n';
                    mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
                    $db = dbConnect();
                    update_info_DB($db, $array_data, $classification);
                }
                else{
                    echo "SQL Error: " . mysqli_error($db)."\n";
                    exit;
                }
            }
            else{
                echo "Insert: ".$ID." complete<br>\n";
            }

        }
    }
    //更新執照
    elseif($classification == 1){
        foreach($array_data as $ID => $capacity){

            $sql = "INSERT INTO `architect_information` (architect_ID, 	qualification_method) VALUES (N'$ID', N'$capacity')";
            
            if(!mysqli_query($db , $sql)){
                if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
                    $sql = "UPDATE `architect_information` SET `qualification_method`= N'$capacity' where `architect_ID`= N'$ID'"; 
                    mysqli_query($db , $sql);
                    echo "Update: ".$ID." complete<br>\n";     
                }
                elseif(!mysqli_ping($db)){
                    echo 'Lost connection\n';
                    mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
                    $db = dbConnect();
                    update_info_DB($db, $array_data, $classification);
                }
                else{
                    echo "SQL Error: " . mysqli_error($db)."\n";
                    exit;
                }
            }
            else{
                echo "Insert: ".$ID." complete<br>\n";
            }

        }
    }
    //更新職業
    elseif($classification == 2){
        foreach($array_data as $ID => $job){

            $sql = "INSERT INTO `architect_information` (architect_ID, 	practice_situation) VALUES (N'$ID', N'$job')";
            
            if(!mysqli_query($db , $sql)){
                if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
                    $sql = "UPDATE `architect_information` SET `practice_situation`= N'$job' where `architect_ID`= N'$ID'"; 
                    mysqli_query($db , $sql);
                    echo "Update: ".$ID." complete<br>\n";     
                }
                elseif(!mysqli_ping($db)){
                    echo 'Lost connection\n';
                    mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
                    $db = dbConnect();
                    update_info_DB($db, $array_data, $classification);
                }
                else{
                    echo "SQL Error: " . mysqli_error($db)."\n";
                    exit;
                }
            }
            else{
                echo "Insert: ".$ID." complete<br>\n";
            }

        }
    }
}

//取得建築師資訊對應建照
function getinformation($str, $key){

    $array_ID = array();
    $array_value = array();
    $array_data = array();

    $html = str_get_html($str);
    if($html){
        $table = $html->find('table', 2);

        foreach($table->find('tr') as $tr){
            foreach($tr->find('td') as $tdvalue){
                $ID = DeleteHtml($tdvalue->innertext);
                if(strpos($ID, "建證字第") !== false){
                    
                    array_push($array_ID, $ID);

                    array_push($array_value, $key);
                    
                }
                    
            }
        }
    }

    $array_data = array_combine($array_ID, $array_value);
    
    return $array_data;

}

//取得建築師登記頁面頁數
function getinfopage($str){
    
    $html = str_get_html($str);
    $info_page = 0;

    if($html){
        $td = $html->find('select[name=pageNo]', 0);
        if($td){
            foreach($td->find('option') as $tdvalue){
                if($tdvalue){
                    $temp = DeleteHtml($tdvalue->value);
                    $info_page = $temp;
                }
            }
        }
    }
    return $info_page;
}

//取得監造師內容
function getSuperviseContent($str){
    $html = str_get_html($str);

    

    $arr_data = array();

    if($html){
        $table = $html->find('div[class=content4]' ,0);
        if($table){
            foreach($table->find('td.td') as $row_data){
                $data = trim(DeleteHtml($row_data->innertext));
                if($data){
                    array_push($arr_data, $data);
                }
                else{
                    $error = "lost data";
                    array_push($arr_data, $error);
                }
            }
        }
    }
    array_splice($arr_data, 1, 3);
    if(!$arr_data){
        echo "Lost data!!\n";
        return 0;
    }
    else if(!strcmp($arr_data[1], "連結")){
        $arr_data[1] = "http://cpabm.cpami.gov.tw/cers/pages/information/rewards.html";
    }
    


    return $arr_data;
}

//讀取資料並存入資料庫
function insert_In_DB($db, $raw_data, $fp){

    //主鍵設為contractor_name，不會有一直加入相同資料的問題
    $sql = "INSERT INTO `architect_information` (architect_ID, architect_name, outstanding_ann, punishment_ann) 
    VALUES (N'$raw_data[3]', N'$raw_data[0]', N'$raw_data[1]', N'$raw_data[2]')";
    if(!mysqli_query($db , $sql)){  //插入失敗
        if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
            //當讀到contractor_name相同時，主動去判斷其他欄位是否異變
            $sql = "UPDATE `architect_information` SET `architect_name`= N'$raw_data[0]', `outstanding_ann`= N'$raw_data[1]', `punishment_ann`= N'$raw_data[2]' where `architect_ID`= N'$raw_data[3]'";
            mysqli_query($db , $sql);
            echo "Update: ".$raw_data[0]." complete<br>\n";
        }   //
        elseif(!mysqli_ping($db)){
            echo 'Lost connection\n';
            mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
            $db = dbConnect();
            insert_In_DB($db, $raw_data);
        }
        else{
            echo "SQL Error: " . mysqli_error($db)."\n";
            $fp = fopen("log.txt","w");
            fwrite($fp, $raw_data[3].$raw_data[0].$raw_data[1].$raw_data[2].PHP_EOL);
            return $fp;
            //exit;
        }
    }
    else    
        echo "Insert: ".$raw_data[0]." complete<br>\n";
    
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

//獲得建築師證號
function getID($str){
    $html = str_get_html($str);

    $arr_id = array();

    if($html){
        $tr = $html->find('tr[class=list0]' ,0);
        if($tr){
            foreach($tr->find('td') as $data){
                $data = DeleteHtml($data->innertext);
                if(strpos($data, "建證字第") !== false){
                    return $data;
                }
            }
        }
    }
    
}
//獲得設計師資料
function getDesignContent($str){
    
    $html = str_get_html($str);
    $arr_data = array();

    if($html){
        $table = $html->find('div[class=content4]' ,0);
        if($table){
            foreach($table->find('td.td') as $row_data){

                $data = trim(DeleteHtml($row_data->innertext));
                
                if($data){
                    array_push($arr_data, $data);
                }
                else{
                    $error = "lost data";
                    array_push($arr_data, $error);
                }

            }
        }
        
    }
    array_splice($arr_data, 1, 1); //不需要地址，array reindex
    
    if(!$arr_data){
        echo "Lost data!!\n";
        return 0;
    }

    elseif(!strcmp($arr_data[1], "連結")){
        $arr_data[1] = "http://cpabm.cpami.gov.tw/cers/pages/information/rewards.html";
    }
     
    /*echo $arr_data[1]."\n";*/
    

    return $arr_data;
 
}
//將名字轉乘big5並encode丟給post
function encode($name){
    $name = iconv("UTF-8", "Big5//IGNORE", $name);
    $name = urlencode($name);
    return $name;

}

//獲得設計師以及監造人連結
function getURLContent($str){ 
    $postURL = array();

    $html = str_get_html($str);
    if($html){
        //尋找網頁中id=row的table
        $table = $html->find('table[id=row]',0);    //第二參數表示:只取第一個表格
        if($table){
            foreach($table->find('a') as $a){
                /*echo $a->href."\n";*/
                $url = DeleteHtml($a->href);
                if(preg_match('/([^?]+)(\?)(p02_code=)([\w]+)/',$url,$matches)){
                    $postURL[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p03_code=)([\w]+)/',$url,$matches)){
                    $postURL[]="{$matches[3]}{$matches[4]}";
                }
            }
        }
    }
   
   
    return $postURL;  
}

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