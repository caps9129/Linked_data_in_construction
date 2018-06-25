<?php

/************************************************************初始化變量*************************************************************************/
include_once(__DIR__."/simple_html_dom.php");
ini_set('memory_limit', '-1');


header("Content-Type:text/html; charset=utf-8");
$cookie_file = "valid.tmp";
$timeout = 5;
/******************************建築師開業登記資料***************************************/
$login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchBusiness.jsp";    
$verify_code_url = "http://cpabm.cpami.gov.tw/img_code.jsp";    //取得驗證碼圖片
$arr_data = array();
$opening_data = array();
$countycode = array("台北市"=>"B", "高雄市"=>"C", "基隆市"=>"F", "宜蘭縣"=>"G", "新北市"=>"H", "桃園市"=>"I", "新竹市"=>"J", "新竹縣"=>"K", 
                    "苗栗縣"=>"L", "台中市"=>"M", "台中縣"=>"N", "彰化縣"=>"O", "南投縣"=>"P", "雲林縣"=>"Q", "嘉義市"=>"R", "嘉義縣"=>"S", 
                    "台南市"=>"T", "台南縣"=>"U", "高雄縣"=>"V", "屏東縣"=>"W", "花蓮縣"=>"X", "台東縣"=>"Y", "澎湖縣"=>"Z", "連江縣"=>"E",
"金門縣"=>"D");
/*********************************建築師登記資料***************************************/
$login_url_ID = "http://cpabm.cpami.gov.tw/search/bmg/queryArchInfo.jsp"; 
$verify_code_url_ID = "http://cpabm.cpami.gov.tw/img_code.jsp"; //作為取的建築師ID得用途
$complete_data = array(); //有完整資料

/******************************************************************MAIN************************************************************************/
$db = dbConnect();
/******************************建築師開業登記資料***************************************/

$cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url, $cookie_file);

foreach($countycode as $countykey => $countyvalue){
    $page = 1;
    do{
        do{    
            $post = "com_id_no=&com_name=&com_id_area=$countyvalue&pwd_name=&pwd_id=&insrand=$code&pageNo=$page";

            $html = post($login_url, $post, $cookie_file, $timeout);

            $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼

            if(!$html){
                echo "Lost architect opening page!!\n";
            }

        }while(!$html || checkExpired($html, $verify_code_url, $cookie_file, $timeout, $code));

        
        

        if($page == 1){
            $get_page = getpage($html);
        }

        echo "page:{$countykey} | {$page}/{$get_page}\n";
        $arr_data = getcontent($html);  //[註記][姓名][縣別][開業證號][事務所名稱][地址]
        array_push($opening_data, $arr_data);
        
        

        $page ++;

    }while($page <= $get_page);
}

//print_r($opening_data);
/**********************************建築師登記資料*****************************************/

$cookie_file = getCookie($verify_code_url_ID, $cookie_file, $timeout);
$code = getCheckNumber($verify_code_url_ID, $cookie_file);

foreach($opening_data as $opening_data_key => $opening_data_value){
    $complete_data = array();
    foreach($opening_data_value as $raw_data_key => $raw_data_value){
        
        array_push($complete_data, $raw_data_value);

        if($raw_data_key % 6 == 1){
            
            do{
                $name = encode($raw_data_value);

                $post_getID = "id_no_d21=&name_d21=$name&edu_level_d21=&capacity_get_d21=&job_d21=&insrand=$code";
                
                $html = post($login_url_ID, $post_getID, $cookie_file, $timeout);

                if($html){
                    $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼

                    $ID =  getID($html);
                    if($ID){
                        array_push($complete_data, $ID);
                    }
                    else{
                        $Error =  "No match ID";
                        array_push($complete_data, $Error);
                    }
                }
                else{
                    echo "Lost architect login page!!\n";
                }

            }while(!$html || checkExpired($html, $verify_code_url_ID, $cookie_file, $timeout, $code));
           
        }
    }

    print_r($complete_data);
    
    $length = sizeof($complete_data);

    for($i = 0 ; $i < $length ; $i++){
        if($i % 7 == 0){
            $store_data = array();
        }

        array_push($store_data, $complete_data[$i]);

        if($i % 7 == 6){
            InsertInDB($db, $store_data);
        }
        
    }
}



//$complete_data = [註記][姓名][建築書證書字號][縣別][開業證號][事務所名稱][地址]

mysqli_close($db);
/****************************************************************FUNCTION*********************************************************************/


function InsertInDB(&$db, $raw_data){
        
    $sql = "INSERT INTO `architect_office` (architect_name, office_ID, office_name, office_address, county, mark, architect_ID) 
    
    VALUES (N'$raw_data[1]', N'$raw_data[4]', N'$raw_data[5]', N'$raw_data[6]', N'$raw_data[3]', N'$raw_data[0]', N'$raw_data[2]')";
    
    if(!mysqli_query($db , $sql)){  //插入失敗
        
        if(strpos(mysqli_error($db),"key 'PRIMARY'")!==false){
            //當讀到contractor_name相同時，主動去判斷其他欄位是否異變
            $sql = "UPDATE `architect_office` SET `architect_name`= N'$raw_data[1]', `office_name`= N'$raw_data[5]', `office_address`= N'$raw_data[6]', `county`= N'$raw_data[3]', `mark`= N'$raw_data[0]', `architect_ID`= N'$raw_data[2]' where `office_ID`= N'$raw_data[4]'";
            mysqli_query($db , $sql);
            echo "Update: ".$raw_data[1]." complete<br>\n";
        }   

        elseif(!mysqli_ping($db)){
            echo 'Lost connection\n';
            mysqli_close($db); //注意：一定要先執行數據庫關閉，這是關鍵 
            $db = dbConnect();
            InsertInDB($db, $raw_data);
        }

        else{
            echo "SQL Error: " . mysqli_error($db)."\n";
            exit;
        }
    }
    else    
        echo "Insert: ".$raw_data[1]." complete<br>\n";
}

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

function getcontent($str){

    $arr_information = array();

    $html = str_get_html($str);
    $table = $html->find('table', 2);
    foreach($table->find('tr') as $trkey => $trvalue){
        
        foreach($trvalue->find('td') as $tdkey => $tdvalue){
            
            if($tdvalue->find('img')){

                $src = trim(DeleteHtml($tdvalue->find('img', 0)->src));

                if($src == "/images/notation/hand.gif"){
                    $mark = "註銷";
                    array_push($arr_information, $mark);
                }
                elseif($src == "/images/notation/hand2.gif"){
                    $mark = "失效";
                    array_push($arr_information, $mark);
                }  
            }
            else{
                if(!$tdkey){
                    $mark = "正常";
                    array_push($arr_information, $mark);
                }
            }
            
            if($trkey){
                    $data = trim(DeleteHtml($tdvalue));
                    if($data){
                        array_push($arr_information, $data);
                    }
                    else{
                        $data = "No data";
                        array_push($arr_information, $data);
                    }
            }
        }
    }

    $length = sizeof($arr_information);
    unset($arr_information[0]); //移除第一個多出來的"正常"
    $arr_information = array_values($arr_information); //重新整理陣列
    for($i = 0 ; $i < $length ; $i++){
        if($i % 7 == 1){
            unset($arr_information[$i]);
        }
    }
    $arr_information = array_values($arr_information);
    print_r($arr_information);

    return $arr_information;


}

//取得建築師登記頁面頁數
function getpage($str){
    
    $html = str_get_html($str);
    $info_page = 0;

    if($html){
        $td = $html->find('select[name=pageNo]', 0);
        if($td){
            foreach($td->find('option') as $tdvalue){
                if($tdvalue){
                    $temp = DeleteHtml($tdvalue->value);
                    $page = $temp;
                }
            }
        }
    }
    return $page;
}


function checkExpired($str, $verify_code_url, $cookie_file, $timeout, &$code){
    $html = str_get_html($str);
    if($html){
        $info_expired = $html->find('td[class=memo]');
       
        if($info_expired){
            foreach($info_expired as $expired){
                $expired = DeleteHtml($expired->innertext);
                if(strpos($expired, "驗證碼輸入錯誤") !== false){
                    echo "cookie expired reconnect...1\n";
                    $cookie_file = getCookie($verify_code_url, $cookie_file, $timeout);
                    $code = getCheckNumber($verify_code_url, $cookie_file);  
                    return 1;
                }
            }
        }
        return 0;      
    }
    return 0; 

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
    // $db = mysqli_connect("db.sgis.tw", "sinicaintern", "27857108311", "building");
    $db = mysqli_connect("140.109.161.93", "ntpc", "ac6tmsks@a", "ntpc");;
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

//將名字轉乘big5並encode丟給post
function encode($name){
    $name = iconv("UTF-8", "Big5//IGNORE", $name);
    $name = urlencode($name);
    return $name;

}

?>