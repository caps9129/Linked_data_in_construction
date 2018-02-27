<?php
/**
* 模擬登錄
*/
header("Content-type: text/html; charset=UTF-8");
//初始化變量
$cookie_file = "valid.tmp";
$login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchBusiness.jsp";
$verify_code_url = "http://cpabm.cpami.gov.tw/cers/img_code.jsp";

$curl = curl_init();
$timeout = 5;
curl_setopt($curl, CURLOPT_URL, $login_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie_file); //獲取COOKIE並存儲
$contents = curl_exec($curl);
$contents = iconv("Big5", "UTF-8//IGNORE", $contents); 
echo $contents;
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
//這裏取出驗證碼圖片後，程序休眠20s，這20s是用來手動查看驗證碼圖片，然後把驗證碼手動寫入當前目錄下的code.txt文件中，等待後續讀取
sleep(20);
//20s後開始讀取剛才手動寫入的驗證碼的TXT文件獲得驗證碼
/*可下載驗證碼識別工具類，從而識別驗證碼，此處不再細說*/
$code = file_get_contents('code.txt');

//登錄需要驗證的參數，根據登錄的網站要求而定
$post = http_build_query(array("com_id_no" => "", "come_name" => "", "com_id_area" => "B", "pwd_name" => "", "pwd_id" => "", "insrand" => $code));
echo $post;

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $login_url);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
curl_setopt($curl, CURLOPT_POST,1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
$result=curl_exec($curl);
curl_close($curl);
$result = iconv("Big5", "UTF-8//IGNORE", $result); 
echo $result;
?>