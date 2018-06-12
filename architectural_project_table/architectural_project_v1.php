<?php 

include_once(__DIR__."/simple_html_dom.php");
ini_set('memory_limit', '-1');
header("Content-Type:text/html; charset=utf-8");

//for DB
define("DB_HOST", "db.sgis.tw");
define("DB_USER", "sinicaintern");
define("DB_PASS", "27857108311");
define("DB_NAME", "building");
//for curl
define("search_URL", "http://cpabm.cpami.gov.tw/cers/SearchLicList.do");      //起造人
define("verify_URL", "http://cpabm.cpami.gov.tw/cers/img_code.jsp");
define("timeout", 10);
define("data_Digits", 4);
define("contractor_URL", "http://cpabm.cpami.gov.tw/cers/SearchSupDetial.do");
define("supervisor_URL", "http://cpabm.cpami.gov.tw/cers/SearchContDetial.do");

$countycode = array("台北市"=>"G00"/*, "高雄市"=>"H00", "基隆市"=>"I10","宜蘭縣"=>"I20", "新北市"=>"I30", "桃園市"=>"I40", "新竹市"=>"I50",  
                    "新竹縣"=>"I60", "苗栗縣"=>"I70", "台中市"=>"I80", "彰化縣"=>"IA0", "南投縣"=>"IB0", "雲林縣"=>"IC0", "嘉義市"=>"ID0",
                    "嘉義縣"=>"IE0", "台南市"=>"IF0", "屏東縣"=>"II0", "花蓮縣"=>"IJ0","台東縣"=>"IK0", "澎湖縣"=>"IL0", "連江縣"=>"J10",
"金門縣"=>"J20"*/);

$year = array("0"=>"100", "1"=>"101"/*, "2"=>"102", "3"=>"103", "4"=>"104", "5"=>"105", "6"=>"106", "7"=>"107"*/);


$DataBase = new DB();
$curl = new curl();
$dom = new dom();

$curl->GetCookie(verify_URL, $curl->cookie, timeout);
$curl->GetCheckNumber(verify_URL, $curl->cookie, $curl->code);

foreach($countycode as $countycodeKey => $countycodeValue){
    foreach($year as $yearKey => $yearValue){
        for($license = 1 ; $license <= 9 ; $license++){//建造執照編號
            
            $license_no = $license;
            $zerofilling = $license;
            $zerofilling_aft = 0;
            $arr_rowdata = array();
            $flag = true;
            $i = 1; //在還未拿到正確頁數時先預設頁數為一
            
            do{    
                do{
                    
                    //藉由第一頁取得建造執照的樣式
                    $post = http_build_query(array("d-16544-p" => "$i", "budare" => $countycodeValue, "license_yy" => $yearValue, "license_no1" => "$license_no", "insrand" => $curl->code, "submit" => '%ACd%B8%DF'));

                    $html = $curl->Post($post, $curl->cookie);

                    $html = iconv("Big5", "UTF-8//IGNORE", $html);  //BIG5 to UTF8。加上IGNORE以忽略非法字眼   
                
                }while($dom->CheckExpired($html, $curl->cookie, $curl->code));   
                    
                $arr_TableContent = $dom->GetTableContent($html, $yearValue);


                array_push($arr_rowdata, $arr_TableContent);   
                
                if($i == 1){   //我在第一頁的時候，我就要去判斷我要怎麼走

                    $page = $dom->GetPage($html); //真正的頁數
                    
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

                if($i == $page && $flag == false){
                    
                    $license_no = $zerofilling_aft;
                    $i = 0;
                    $flag = true;
                }

                $i++;
            
            }while($i <= $page);
        }
    }
}

print_r($arr_rowdata);

class DB{

    public $conn, $query, $rows;
  
    
    public function __construct() {
        $this->connect();
        
    }

    public function disconnect() {
        mysqli_close($this->conn);
    }

    public function reconnect() {
        $this->disconnect();
        $this->connect();
    }

    public function connect() {
        $this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if(!$this->conn){
            die("dbConnect fail". mysqli_connect_error()."\n");
            exit;
        }
        else{
            echo "dbConnect Successful!!<br>\n";
            if (!$this->conn->set_charset("utf8")) {
                printf("Error loading character set utf8: %s\n", $this->conn->error);
                
            } else {
                printf("Current character set: %s\n", $this->conn->character_set_name());
            }  
        }
    }
}

class dom{
    
    public $curl;

    public function __construct() {

    }
    
    function GetContractorURL(){

    }

    function GetTableContent($str, $year){

        $arr_content = array();
        $html = str_get_html($str);
        $row = 0;
        
        if($html){
            $table = $html->find('div[class=content2]', -1);
            if($table){
                foreach($table->find('tr') as $tr){
    
                    $column = 0;
                    
                    if($row != 0){                                  //忽略第一行
                        array_push($arr_content, $year);
                    }
                    
                    
                    foreach($tr->find('td') as $td){
                        
                        if($column == 0){                           //指撈第一列資料
                            $a = $td->find('a', 0);
    
                            if(isset($a->href)){                    //取得證照連結
                                $url = $this->DeleteHtml($a->href);
                                array_push($arr_content, $url);
                            }
                            else{
                                array_push($arr_content, "無此資料");
                            }
                        }
    
                        $text = $this->DeleteHtml($td->innertext);
                        
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
    
    //取得頁數
    function GetPage($str){
        
        $html = str_get_html($str);
        $temp = array();    //儲存所有可能字元
        $temp_1 = array();  //儲存正確字元    
       
        foreach($html->find('span[class=pagebanner]') as $node){   //取得頁數
        
            $temp = str_split($this->DeleteHtml($node->innertext), 1); //分割成1個字元存入陣列
            
        }
            
        $temp = array_splice($temp, 15, data_Digits);/*取4位數，之後筆數可以往後取*/
        
        foreach($temp as $tempKey => $tempValue){
            if(is_numeric($tempValue)){ //判斷是否為數字
                array_push($temp_1, $tempValue);
            }
        }  
        $page =  implode("", $temp_1);
        $page = ceil($page / 10.0); //每一頁10筆
        
        return $page;
        
        
    }

    public function DeleteHtml($str){
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

    public function CheckExpired($str, $cookie_file, &$code){
    
        $curl = new curl(); 
        
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
                        $expired = $this->DeleteHtml($expired->innertext);
                        if(strpos($expired, "驗證碼輸入錯誤") !== false){
                            echo "cookie expired reconnect...1\n";
                            $curl->GetCookie(verify_URL, $cookie_file, timeout);
                            $curl->GetCheckNumber(verify_URL, $cookie_file, $code);
                            return 1;
                        }
                    }
                    
                }
                if($resume_expired){
                    foreach($resume_expired as $expired){
                        $expired = $this->DeleteHtml($expired->innertext);
                        if(strpos($expired, "驗證碼錯誤") !== false){
                            echo "cookie expired reconnect...2\n";
                            $curl->GetCookie(verify_URL, $cookie_file, timeout);
                            $curl->GetCheckNumber(verify_URL, $cookie_file, $code);
                            return 1; 
                        }
                    }    
                }
                return 0; 
                
            }
            return 1; 
        }
     
    
    }

}

class curl{
    
    var $code,
        $cookie = "valid.tmp",
        $dom;

    public function __construct() {
        
    } 

    public function GetCheckNumber($image_url, $cookie_file, &$code){

        $dom = new dom(); 
        
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
        $code = $dom->DeleteHtml($code);
    
    }

    public function GetCookie($cookie_url, &$cookie_file, $timeout){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $cookie_url); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //以輸出文件的方式取代直接輸出
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);  //等待瀏覽器的回應時間
        curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie_file); //獲取COOKIE並存儲
        $contents = curl_exec($curl);
        curl_close($curl);
        //return $cookie_file;
    }

    public function Post($post, $cookie_file){  
    
        $curl = curl_init();
       
        curl_setopt($curl, CURLOPT_URL, search_URL);
       
        curl_setopt($curl, CURLOPT_HEADER, false);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        
        curl_setopt($curl, CURLOPT_NOSIGNAL,1);
      
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, timeout);  //等待瀏覽器的回應時間
      
        curl_setopt($curl, CURLOPT_TIMEOUT, timeout);
        
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

    


}


?>