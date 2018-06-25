<?php

set_time_limit(-1);

define("timeout", 10);
define("url", "https://rdf.sgis.tw");
define("Default_Data_Set_Name", "http://sgis.tw");
//$arr_QueryResult_json;


if(isset($_POST['sparql']) && isset($_POST['count_sparql']))
{

    $arr_QueryResult = array();

    $count_sparql = $_POST['count_sparql'];

    //print($count_sparql);

    $count_sparql = urlencode($count_sparql);

    $url = "default-graph-uri=".Default_Data_Set_Name."&query=$count_sparql&should-sponge=&format=text/html&timeout=0&debug=on";

    //$html = file_get_contents("https://rdf.sgis.tw/?default-graph-uri=http://sgis.tw&query=PREFIX+archi%3A+%3Chttp%3A%2F%2Farchi.sgis.tw%2Fontology%2F%3E+select+%28count%28%2A%29+AS+%3Fcount%29+where+%7B+%3Flicense+archi%3Alicense_type+%3Ftype+.%3Flicense+archi%3Alicense_url+%3Furl+.%3Flicense+archi%3Aproject_address+%3Fproject_address+.%3Flicense+archi%3Aaccuracy+%3Faccuracy+.%3Flicense+archi%3Alongitude+%3Flongitude+.%3Flicense+archi%3Alatitude+%3Flatitude+.%3Flicense+archi%3Acreator+%3Fcreator+.%3Flicense+archi%3Adesigner+%3Fdesigner+.%3Flicense+archi%3Asupervisor+%3Fsupervisor+.%3Flicense+archi%3Acontractor+%3Fcontractor+.%3Flicense+archi%3Ayear+%3Fyear+FILTER+%28regex%28%3Fproject_address%2C%22%E5%8F%B0%E5%8C%97%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%AB%98%E9%9B%84%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%9F%BA%E9%9A%86%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%AE%9C%E8%98%AD%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%96%B0%E5%8C%97%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%A1%83%E5%9C%92%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%96%B0%E7%AB%B9%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%96%B0%E7%AB%B9%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E8%8B%97%E6%A0%97%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8F%B0%E4%B8%AD%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%BD%B0%E5%8C%96%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8D%97%E6%8A%95%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%9B%B2%E6%9E%97%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%98%89%E7%BE%A9%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%98%89%E7%BE%A9%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8F%B0%E5%8D%97%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%B1%8F%E6%9D%B1%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E8%8A%B1%E8%93%AE%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8F%B0%E6%9D%B1%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%BE%8E%E6%B9%96%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%80%A3%E6%B1%9F%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%87%91%E9%96%80%E7%B8%A3%22%29%29%7D&should-sponge=&format=text/html&timeout=0&debug=on");

    //print($html."\n");
    //print($url."\n");

    do {

        $html = PostURL($url);

    }while(!$html);
    
    //print($html."\n");

    $xpath = CreateDom($html);
    $count = GetCount($xpath);

    
    //print($count."\n");
    // PREFIX archi: <http://archi.sgis.tw/ontology/>
    // select  *  where 
    // { 
    //     ?a archi:project_address ?b .
    //     ?a archi:longitude ?c .
    //     ?a archi:latitude ?d .
    //     FILTER (regex(?b, "嘉義縣") || regex(?b, "高雄市"))
    // }
    // LIMIT   10000
    // OFFSET  20000

    $project_sparql = $_POST['sparql'];

    for ($row = 0 ; $row < $count ; ){
        $temp_project_sparql = $project_sparql."LIMIT 10000"." OFFSET $row";
        //print($temp_project_sparql."\n");
        $temp_project_sparql = urlencode($temp_project_sparql);
        $url = "default-graph-uri=".Default_Data_Set_Name."&query=$temp_project_sparql&should-sponge=&format=text/html&timeout=0&debug=on";
        
        do{
            $html = PostURL($url);
        
        }while(!$html);
        $xpath = CreateDom($html);
        
        if($count > 10000){
            $temp_QueryResult = GetQueryResult($xpath);
            
            if($row == 0){
                $arr_QueryResult = $temp_QueryResult;
            }
            else{
                $arr_QueryResult = array_merge($arr_QueryResult,  $temp_QueryResult);
            }
        }
        else{
            $arr_QueryResult = GetQueryResult($xpath);
        }
        
        $row = $row + 10000;
    }
    
    //print(count($arr_QueryResult));

    //print_r($arr_QueryResult[20000]);
    //print_r($arr_QueryResult);

    echo json_encode($arr_QueryResult, JSON_UNESCAPED_UNICODE);

    


    

    
   

}

function GetCount($xpath){
    $table = $xpath->query('//table[@class = "sparql"]/tr[position()>1]/td');
    if($table){   
        foreach($table as $value) {  
            if($value->textContent){
                return DeleteHtml($value->textContent);
            }
        }
    }

}

function GetQueryResult($xpath){
    
    $arr_QueryResult = array();
    $arr_temp = array();
    $row = 0;
    $table = $xpath->query('//table[@class = "sparql"]/tr[position()>1]/td');
    if($table){
          
        foreach($table as $value) {  
            if($value->textContent){
                
                if($row % 12 == 0 && $row > 0 && $arr_temp){
                    array_push($arr_QueryResult, $arr_temp);
                    $arr_temp = array();
                }
                array_push($arr_temp, DeleteHtml($value->textContent));
                $row ++;
            }
        }
    }
    //echo $row;
    //print_r($arr_QueryResult);
    return $arr_QueryResult;
}

function DeleteHtml($str){
    $str = trim($str);
    $str = strip_tags($str,"");
    $str = str_replace("\"","",$str);
    $str = str_replace("\t","",$str);
    $str = str_replace("\r\n","",$str); 
    $str = str_replace("\r","",$str); 
    $str = str_replace("\n","",$str); 
    $str = str_replace(" "," ",$str); 
    $str = str_replace("&nbsp;","",$str);
    return $str;
}

function PostURL($post){  
    
    $curl = curl_init();
   
    curl_setopt($curl, CURLOPT_URL, url);
   
    curl_setopt($curl, CURLOPT_HEADER, false);
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    
    curl_setopt($curl, CURLOPT_NOSIGNAL,1);
  
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, timeout);  //等待瀏覽器的回應時間
  
    curl_setopt($curl, CURLOPT_TIMEOUT, timeout);
    
    curl_setopt($curl, CURLOPT_POST,1); //開啟POST
  
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);  //傳遞要求參數給伺服器

    $info = curl_getinfo($curl);
   
    $html = curl_exec($curl);
    if(!$html){
        //echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
    }
    
    $curl_errno = curl_errno($curl);  
    $curl_error = curl_error($curl);

    if($curl_errno > 0){  
        //echo "cURL Error ($curl_errno): $curl_error\n";  
    }
    
    curl_close($curl);
    
    //echo $html;

    return $html;
}

function CreateDom($html){
    $dom = new DOMDocument;
    $encoding = mb_detect_encoding($html);
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);               //告訴dom檔案格式為utf-8
    $xpath =new DOMXpath($dom);
    return $xpath;
}

//print_r($arr_QueryResult);



?>

