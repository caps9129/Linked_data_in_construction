<?php

include_once(__DIR__."/simple_html_dom.php");
ini_set('memory_limit', '-1');

//初始化變量

header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/cers/SearchLicList.do";             
$login_url_p01 = "http://cpabm.cpami.gov.tw/cers/SearchProDetial.do?";      //起造人
$login_url_p02 = "http://cpabm.cpami.gov.tw/cers/SearchDesignDetial.do";    //設計人
$login_url_p03 = "http://cpabm.cpami.gov.tw/cers/SearchSupDetial.do";       //監造人
$login_url_p04 = "http://cpabm.cpami.gov.tw/cers/SearchContDetial.do";      //承造人

$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";    //取得驗證碼圖片   
$timeout = 10;   //設置等待時間
$data_Digits = 4;   //判讀為數字(頁數)的位數
$page = 1;   //傳入與取得頁數(設為"1"是為了在迴圈跑動第一次，以取得正確的page頁數)
$arr_data = array();

#可手動設定以下兩個陣列決定撈取的縣市以及年度
$countycode = array();
$countycode = array("台北市"=>"G00", "高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                    "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0","南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                    "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
"金門縣"=>"J20");
$year = array("0"=>"100", "1"=>"101", "2"=>"102", "3"=>"103", "4"=>"104", "5"=>"105", "6"=>"106", "7"=>"107");

/**************************************main***************************************************************/

$db = DBConnect();

$cookie_file = GetCookie($verify_code_url, $cookie_file, $timeout);
$code = GetCheckNumber($verify_code_url, $cookie_file);

 //運作模式為取得先取得同一縣市同一年度不同頁數 >> 取得同一縣市不同年度不同頁數 >> 取得不同縣市不同年度不同頁數
foreach($countycode as $countycodeKey => $countycodeValue){   //跑縣市
    foreach($year as $yearKey => $yearValue){   //跑年度
        for($license = 1 ; $license <= 9 ; $license++){
            $i=1;
            $license_no = $license;
            $zerofilling = $license;
            $zerofilling_aft = 0;
            $arr_rowdata = array();
            $flag = true;
            $i = 1; //在還未拿到正確頁數時先預設頁數為一

            do{
                do{
                    $post = http_build_query(array("d-16544-p" => $i, "budare" => $countycodeValue, "license_yy" => $yearValue, "license_no1" => "$license_no", "insrand" => $code, "submit" => '%ACd%B8%DF'));

                    $html = PostURL($login_url, $post, $cookie_file, $timeout);

                    $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼


                }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));

                $xpath = CreateDom($html);

                if($i == 1){    //只要第一次拿到頁數就好了~~
                    $page = GetPage($xpath, $data_Digits);
                    if($page == 20){    //往右走
                        
                        $flag = false; 

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

                $postURL = GetURLContent($html);   //獲取往下一層的連結

                //print_r($postURL);

                if($i == $page && $flag == false){
                    
                    $license_no = $zerofilling_aft;
                    $i = 0;
                    $flag = true;
                }
                
                if($postURL){
                    
                    foreach($postURL as $postURLValue){
                    
                        //$arr_data = [classifaction:(0)/(1)][year][證照url][證照][地點][起造人][設計人][監造人][承造人]

                        //echo $postURLValue."\n";

                        $arr_data = GetHtmlPage($postURLValue, $verify_code_url, $cookie_file, $timeout, $code, $login_url_p01, $login_url_p02, $login_url_p03, $login_url_p04, $data_Digits);
                        //print_r($arr_data);

                        if($arr_data){  
                                
                           foreach($arr_data as $arr_data_value) {      //將二維陣列$arr_data切割
                            

                                $length = sizeof($arr_data_value);

                                for($j = 0 ; $j < $length ; $j++){      
                                    
                                    if($j % 10 == 0){
                                        $store_data = array();
                                    }

                                    array_push($store_data, $arr_data_value[$j]);   //每八個element重新給成新的一維陣列

                                    if($j % 10 == 9){       

                                       
                                        $store_data = QueryFromDB($db, $store_data, $fpQueryError);   //傳給db做query的動作回傳更新後陣列
                                        //print_r($store_data);
                                        InsertInDB($db, $store_data, $fp);

                                    }

                                    

                                }
                                
                           }

                        }
                        
                        $arr_data = array();
                    }
                
                }


                $i++;

            }while($i<=$page);
        }
        
    }

}

mysqli_close($db);

exit;

/****************************************************************************************************/

function QueryFromDB(&$db, $data, &$fpQueryError){
    
    if(!$fpQueryError){
        $fpQueryError = fopen("Query_Error.txt","w");
    }

    if(!mysqli_ping($db)){ 
        echo 'Lost connection\n';
        mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
        $db = DBConnect();
        QueryFromDB($db, $data, $fpQueryError); 
    }
        
    $sql_contractor = "SELECT `uniform_number` FROM `building_contractor` WHERE `Industry_name` LIKE N'$data[8]'";

    $result = $db->query($sql_contractor);
    if(!$result){
        echo 'Lost connection\n';
        mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
        $db = DBConnect();
        QueryFromDB($db, $data, $fpQueryError); 
    }
    else{
        $row = $result->fetch_array(MYSQLI_BOTH);
        if(!$row['uniform_number'] == false && mysqli_num_rows($result) != 0){
            $data[8] = $row['uniform_number'];
           // echo "select Cntoractor ID: ".$row['contractor_name']."completed\n";
        }
        else{
            if(!mysqli_ping($db)){ 
                echo 'Lost connection\n';
                mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
                $db = DBConnect();
                QueryFromDB($db, $data, $fpQueryError); 
            }
            else{
               // echo "select Contractor ID: ".$row['contractor_name']."failed\n";
                fwrite($fpQueryError, "No match Contractor ID:".$data[3].";".$data[0].";".$data[2].";".$data[4].";".$data[5].";".$data[6].";".$data[7].";".$data[8].";".$data[1].PHP_EOL);
            }
        }
    }

    
    $sql_designer = "SELECT `architect_ID` FROM `architect_information` WHERE `architect_name` LIKE N'$data[6]'";

    $result = $db->query($sql_designer);

    if(!$result){
        echo 'Lost connection\n';
        mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
        $db = DBConnect();
        QueryFromDB($db, $data, $fpQueryError); 
    }
    else{
        $row = $result->fetch_array(MYSQLI_BOTH);
        if(mysqli_num_rows($result) != 0 && !$row['architect_ID'] == false){
            $data[6] = $row['architect_ID'];
            //echo "select Architect ID: ".$row['architect_ID']."completed\n";
        }
        else{
            if(!mysqli_ping($db)){ 
                echo 'Lost connection\n';
                mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
                $db = DBConnect();
                QueryFromDB($db, $data, $fpQueryError); 
            }
            else{
                //echo "select Architect_ID: ".$row['architect_ID']."failed\n";
                fwrite($fpQueryError, "No match Architect ID:".$data[3].";".$data[0].";".$data[2].";".$data[4].";".$data[5].";".$data[6].";".$data[7].";".$data[8].";".$data[1].PHP_EOL);
            }
        }
    }
    

    $sql_supervisor = "SELECT `office_ID` FROM `architect_office` WHERE `architect_name` LIKE N'$data[7]'";

    $result = $db->query($sql_supervisor);

    if(!$result){
        echo 'Lost connection\n';
        mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
        $db = DBConnect();
        QueryFromDB($db, $data, $fpQueryError); 
    }
    else{
        $row = $result->fetch_array(MYSQLI_BOTH);
        if(mysqli_num_rows($result) != 0 && !$row['office_ID'] == false){
        

            $data[7] = $row['office_ID'];
            //echo "select Supervisor ID: ".$row['architect_ID']."completed\n";
        }
        else{
            if(!mysqli_ping($db)){ 
                echo 'Lost connection\n';
                mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
                $db = DBConnect();
                QueryFromDB($db, $data, $fpQueryError); 
            }
            else{
                //echo "select Supervisor_ID: ".$row['architect_ID']."failed\n";
                fwrite($fpQueryError, "No match Supervisor ID:".$data[3].";".$data[0].";".$data[2].";".$data[4].";".$data[5].";".$data[6].";".$data[7].";".$data[8].";".$data[1].PHP_EOL);
            }
        }
    }

    return $data;

}

function GetTableContent($str, $ID_define, $year, $ifcontractorID, $contractor_str){

    $arr_content = array();
    $arr_url = [];
    $html = str_get_html($str);
    $row = 0;
    if($ifcontractorID == 0){
        $contractorID = "無此資料";
    }
    else{
        $contractorID = GetContractorID($contractor_str);
        //echo $contractorID;
    }
    
    if($html){
        $table = $html->find('div[class=content2]', -1);
        if($table){
            foreach($table->find('tr') as $tr){

                $column = 0;
                
                if($row != 0){
                    array_push($arr_content, $contractorID);                                  //忽略第一行
                    array_push($arr_content, $ID_define);
                    array_push($arr_content, $year);
                }

                
                foreach($tr->find('td') as $td){
                    
                    if($column == 0){                           //指撈第一列資料
                        $a = $td->find('a', 0);

                        if(isset($a->href)){                    //取得證照連結
                            $url = DeleteHtml($a->href);
                            array_push($arr_content, $url);
                        }
                        else{
                            array_push($arr_content, "無此資料");
                        }
                    }

                    $text = DeleteHtml($td->innertext);


                    
                    if($text){
                        array_push($arr_content, $text);          //取得內容
                    }
                    else{
                        array_push($arr_content, "無此資料");
                    }
                    $column ++;
                }
                $row ++;
            } 
        }
    }
    
    return $arr_content;
}


function GetDetailURL($str){
    
  
    $arr_url = array();
    $html = str_get_html($str);

    if($html){
        $table = $html->find('table', 1);
        
        if($table){
            foreach($table->find('a') as $a){
                $url = trim(DeleteHtml($a->href));
                if(preg_match('/([^?]+)(\?)(p01_code=)([\s\S]+)/',$url,$matches)){
                    $arr_url[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p02_code=)([\s\S]+)/',$url,$matches)){
                    $arr_url[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p03_code=)([\s\S]+)/',$url,$matches)){
                    $arr_url[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p04_id=)([\s\S]+)/',$url,$matches)){
                    $arr_url[]="{$matches[3]}{$matches[4]}";
                }
            }
        }      
    }
    return $arr_url;
}


function GetContractorID($str){

    $html = str_get_html($str);
    
    $count = 0;
    $none_data = "無相關資料";

    foreach($html->find('td[class=td]') as $td_value){

        if($count == 0){
            $industry_name = DeleteHtml($td_value->innertext);
        }
        if($count == 3){
            $uniform_num = DeleteHtml($td_value->innertext);
        }

        $count++;

    }

    if($industry_name && $uniform_num){
        return $industry_name."_".$uniform_num;
    }
    else{
        return $industry_name;
    }

}
//回傳頁面
function GetHtmlPage($postURLValue, $verify_code_url, $cookie_file, $timeout, $code, $login_url_p01, $login_url_p02, $login_url_p03, $login_url_p04, $data_Digits){
    
    $arr_tmp = array();
    $arr_data = array();
    $arr_url = array();
    $page = 1;
    

    if(preg_match('/(p01_code=)([\w]+)/', $postURLValue)){                      //起造人頁面
        
        do{
            
            $html = PostURL($login_url_p01, $postURLValue, $cookie_file, $timeout);
            $html = iconv("Big5", "UTF-8//IGNORE", $html);

        }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));
                
        $arr_url = GetDetailURL($html);                                         //取得起造人詳細頁面的連結
       

        if(!empty($arr_url)){
            foreach($arr_url as $urlvalue){
                $i = 1;
                do{
                    
                    $url = $urlvalue."&d-16544-p=".$i;
               
                    do{
                        
                        $html = PostURL($login_url_p01, $url, $cookie_file, $timeout);   //取得起造人詳細頁面
                        $html = iconv("Big5", "UTF-8//IGNORE", $html);
                        //echo $html;

                    }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));
                    

                    if($i == 1){
                        $xpath = CreateDom($html);
                        $page = GetPage($xpath, $data_Digits);
                    }

                    if(strpos ($urlvalue, "s_y=1")){    //建造執照
                        $ID_define = 0;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }
                    elseif(strpos ($urlvalue, "s_y=2")){    //使用執照
                        $ID_define = 1;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }

                    $arr_tmp = GetTableContent($html, $ID_define, $year, 0, 0); //

                    array_push($arr_data, $arr_tmp);

                    $i++;

                }while($i <= $page);
            }  
        }

    }
    elseif(preg_match('/(p02_code=)([\w]+)/', $postURLValue)){                  //設計人頁面


        do{
            
            $html = PostURL($login_url_p02, $postURLValue, $cookie_file, $timeout);
            $html = iconv("Big5", "UTF-8//IGNORE", $html);

        }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));
        
        
        $arr_url = GetDetailURL($html);                                         //取得設計人詳細頁面的連結
        

        if(!empty($arr_url)){
            foreach($arr_url as $urlvalue){
                $i = 1;
                do{
                    
                    $url = $urlvalue."&d-16544-p=".$i;

                    
                    do{
                        
                        $html = PostURL($login_url_p02, $url, $cookie_file, $timeout);   //取得設計人詳細頁面
                        $html = iconv("Big5", "UTF-8//IGNORE", $html);
                        //echo $html;

                    }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));

                    
                    if($i == 1){
                        $xpath = CreateDom($html);
                        $page = GetPage($xpath, $data_Digits);
                    }
                    

                    if(strpos ($urlvalue, "s_y=1")){    //建造執照
                        $ID_define = 0;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }
                    elseif(strpos ($urlvalue, "s_y=2")){    //使用執照
                        $ID_define = 1;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }

                    $arr_tmp = GetTableContent($html, $ID_define, $year, 0, 0);

                    array_push($arr_data, $arr_tmp);

                    $i++;

                }while($i <= $page);
            }
        }

    }
    elseif(preg_match('/(p03_code=)([\w]+)/', $postURLValue)){                  //監造人頁面
        

        do{
            $html="";
            $html = PostURL($login_url_p03, $postURLValue, $cookie_file, $timeout);
            $html = iconv("Big5", "UTF-8//IGNORE", $html);

        }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));
        
        $arr_url = GetDetailURL($html);                                         //取得監造人詳細頁面的連結
        

        if(!empty($arr_url)){
            foreach($arr_url as $urlvalue){
                $i = 1;
                do{
                    
                    $url = $urlvalue."&d-16544-p=".$i;


                    do{
                        $html = PostURL($login_url_p03, $url, $cookie_file, $timeout);   //取得監造人詳細頁面
                        $html = iconv("Big5", "UTF-8//IGNORE", $html);
                        //echo $html;
                    
                    }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));

                    

                    if($i == 1){
                        $xpath = CreateDom($html);
                        $page = GetPage($xpath, $data_Digits);
                    }

                    if(strpos ($urlvalue, "s_y=1")){    //建造執照
                        $ID_define = 0;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }
                    elseif(strpos ($urlvalue, "s_y=2")){    //使用執照
                        $ID_define = 1;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }

                    $arr_tmp = GetTableContent($html, $ID_define, $year, 0, 0);

                    array_push($arr_data, $arr_tmp);

                    $i++;

                }while($i <= $page);
            }
        }
        
    }
    elseif(preg_match('/(p04_id=)([\w]+)/', $postURLValue)){                  //回傳承造人頁面
        

        do{
  
            $html = PostURL($login_url_p04, $postURLValue, $cookie_file, $timeout);
            $contractor_html = iconv("Big5", "UTF-8//IGNORE", $html);
            //echo $html;


        }while(CheckExpired($contractor_html, $verify_code_url, $cookie_file, $timeout, $code));

        
        $arr_url = GetDetailURL($contractor_html);                                         //取得承造人詳細頁面的連結
       
                                             
        if(!empty($arr_url)){
            foreach($arr_url as $urlvalue){
                $i = 1;
                do{
                    
                    $url = $urlvalue."&d-16544-p=".$i;

                    do{
                        
                        $html = PostURL($login_url_p04, $urlvalue, $cookie_file, $timeout);   //取得承造人詳細頁面
                        $html = iconv("Big5", "UTF-8//IGNORE", $html);
                        
                    }while(CheckExpired($html, $verify_code_url, $cookie_file, $timeout, $code));
                    
                    if($i == 1){
                        $xpath = CreateDom($html);
                        $page = GetPage($xpath, $data_Digits);
                    }
                    
                    if(strpos ($urlvalue, "s_y=1")){    //建造執照
                        $ID_define = 0;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }
                    elseif(strpos ($urlvalue, "s_y=2")){    //使用執照
                        $ID_define = 1;
                        if(strpos ($urlvalue, "s_x=1")){
                            $year = 103;
                        }
                        elseif(strpos ($urlvalue, "s_x=2")){
                            $year = 104;
                        }
                        elseif(strpos ($urlvalue, "s_x=3")){
                            $year = 105;
                        }
                        elseif(strpos ($urlvalue, "s_x=4")){
                            $year = 106;
                        }
                        elseif(strpos ($urlvalue, "s_x=5")){
                            $year = 107;
                        }
                    }
                    
                    $arr_tmp = GetTableContent($html, $ID_define, $year, 1 ,$contractor_html);
                    
                    array_push($arr_data, $arr_tmp);
                    
                    $i++;  
                
                }while($i <= $page);
            }
        }
 
    }
    
    return $arr_data;
}

//讀取資料並存入資料庫
function InsertInDB(&$db, $raw_data, &$fp){

    if(!$fp){
        $fp = fopen("SQL_Error.txt","w");
    }
    //主鍵設為contractor_name，不會有一直加入相同資料的問題
    $sql = "INSERT INTO `architect_project` (architect_ID, license_type, license_url, address, creator, designer, supervisor, contractor, contractor_ID, year) VALUES (N'$raw_data[4]', N'$raw_data[1]', N'$raw_data[3]', N'$raw_data[5]', N'$raw_data[6]', N'$raw_data[7]', N'$raw_data[8]', N'$raw_data[9]', N'$raw_data[0]', N'$raw_data[2]')";

                
    if(!mysqli_ping($db)){ 
        echo 'Lost connection\n';
        mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
        $db = DBConnect();
        InsertInDB($db, $raw_data, $fp); 
    }
    
    if(!mysqli_query($db , $sql)){  //插入失敗

        if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
           
            //當讀到contractor_name相同時，主動去判斷其他欄位是否異變
            if($raw_data[0] == "無此資料"){
                $sql = "UPDATE `architect_project` SET `license_type`= N'$raw_data[1]', `license_url`= N'$raw_data[3]', `address`= N'$raw_data[5]', `creator`= N'$raw_data[6]', `designer`= N'$raw_data[7]', `supervisor`= N'$raw_data[8]', `contractor`= N'$raw_data[9]' , `year`= N'$raw_data[2]' where `architect_ID`= N'$raw_data[4]'";
            }
            else{
                $sql = "UPDATE `architect_project` SET `license_type`= N'$raw_data[1]', `license_url`= N'$raw_data[3]', `address`= N'$raw_data[5]', `creator`= N'$raw_data[6]', `designer`= N'$raw_data[7]', `supervisor`= N'$raw_data[8]', `contractor`= N'$raw_data[9]' , `contractor_ID`= N'$raw_data[0]', `year`= N'$raw_data[2]' where `architect_ID`= N'$raw_data[4]'";
            }
            

            if(mysqli_query($db , $sql)){
                echo "Update: ".$raw_data[4]." complete\n";
            }
            else{
                InsertInDB($db, $raw_data, $fp);
            }
            
        }   

        else{
            echo "SQL Error: " . mysqli_error($db)."\n";
            fwrite($fp, $raw_data[3].";".$raw_data[0].";".$raw_data[2].";".$raw_data[4].";".$raw_data[5].";".$raw_data[6].";".$raw_data[7].";".$raw_data[8].";".$raw_data[1].PHP_EOL);
        }
    }

    else{   
        //echo $sql."\n";
        echo "Insert: ".$raw_data[4]." complete\n";
    }

        
    
}


//檢查驗證碼過期
function CheckExpired($str, $verify_code_url, $cookie_file, $timeout, &$code){
    
    if($str === false || $str == ""){
        return 1;
    }

    else{

        $html = str_get_html($str);

        if($html){
            
            $design_expired = $html->find('td[class=memo]');
            $resume_expired = $html->find('font');
            if($design_expired){
                foreach($design_expired as $expired){
                    $expired = DeleteHtml($expired->innertext);
                    if(strpos($expired, "驗證碼輸入錯誤") !== false){
                        echo "cookie expired reconnect...1\n";
                        $cookie_file = GetCookie($verify_code_url, $cookie_file, $timeout);
                        $code = GetCheckNumber($verify_code_url, $cookie_file);  
                        return 1;
                    }
                }
                
            }
            if($resume_expired){
                foreach($resume_expired as $expired){
                    $expired = DeleteHtml($expired->innertext);
                    if(strpos($expired, "驗證碼錯誤") !== false){
                        echo "cookie expired reconnect...2\n";
                        $cookie_file = GetCookie($verify_code_url, $cookie_file, $timeout);
                        $code = GetCheckNumber($verify_code_url, $cookie_file); 
                        return 1; 
                    }
                }    
            }
            return 0; 
            
        }
        return 1; 
    }
 

}

//為了得到開業證號
function GetSupervisorID($str){

    $count = 0;

    $html = str_get_html($str);
    foreach($html->find('td[class=td]') as $a){

        if($count == 1){
            $SupervisorID = DeleteHtml($a->innertext);
            return $SupervisorID;
        }

        $count++;
    }

}

//獲得設計師以及監造人連結
function GetURLContent($str){ 
    $postURL = array();

    $html = str_get_html($str);
    if($html){
        //尋找網頁中id=row的table
        $table = $html->find('table[id=row]',0);    //第二參數表示:只取第一個表格
        if($table){
            foreach($table->find('a') as $a){
               
                $url = DeleteHtml($a->href);
               
                if(preg_match('/([^?]+)(\?)(p01_code=)([\w]+)/',$url,$matches)){            //起造人url
                    $postURL[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p02_code=)([\w]+)/',$url,$matches)){        //設計人url
                    $postURL[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p03_code=)([\w]+)/',$url,$matches)){        //監造人url
                    $postURL[]="{$matches[3]}{$matches[4]}";
                }
                elseif(preg_match('/([^?]+)(\?)(p04_id=)([\w]+)/',$url,$matches)){        //承造人url
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
    $str = str_replace("&nbsp;","",$str);
    return $str;
}

//連接資料庫
function DBConnect(){
    //$db = mysqli_connect("db.sgis.tw", "sinicaintern", "27857108311", "building");
    $db = mysqli_connect("10.21.100.7", "root", "", "building");
    if(!$db){
        die("dbConnect fail". mysqli_connect_error()."\n");
        DBConnect();
    }
    else{
        echo "dbConnect Successful!!<br>\n";
        if (!$db->set_charset("utf8")) {
            printf("Error loading character set utf8: %s\n", $db->error);
            
        } else {
            printf("Current character set: %s\n", $db->character_set_name());
        }
        return $db;    
    }
}

//取得session
function GetCookie($cookie_url, $cookie_file, $timeout){
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
function PostURL($url, $post, $cookie_file, $timeout){  
    
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
function GetCheckNumber($image_url, $cookie_file){
    
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
function GetPage($xpath, $data_Digits){
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
  
function CreateDom($html){
    $dom = new DOMDocument;
    $encoding = mb_detect_encoding($html);
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);               //告訴dom檔案格式為utf-8
    $xpath =new DOMXpath($dom);
    return $xpath;
}
  
?>