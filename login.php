<?php

    function login_curl(){
        require_once(__DIR__."/simple_html_dom.php");
        header("Content-Type:text/html; charset=utf-8");
        $cookie_file = "valid.tmp";
        $login_url = "http://cpabm.cpami.gov.tw/search/bmg/queryArchBusiness.jsp";    
        $verify_code_url = "http://cpabm.cpami.gov.tw/img_code.jsp";    //取得驗證碼圖片

        $curl = curl_init();
        $timeout = 5;

        $MAX = 10000; //最大化表格列數，解決翻頁問題(暫時)


        curl_setopt($curl, CURLOPT_URL, $login_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie_file); //獲取COOKIE並存儲
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
        echo iconv("Big5", "UTF-8//IGNORE", $html); 

        foreach($html->find('table tr') as $key=>$a){
            if($key>0){
                foreach($a->find('td') as $tdkey=>$td){
                    if($tdkey==0){
                        $result=iconv("big5","UTF-8",$td->innertext);  //轉碼
                        echo $result."<br />";
                    }else       
                        echo $td->innertext."<br />";
                } 
            }
        }
    
    }




?>