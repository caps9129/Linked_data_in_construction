<?php

define("DB_HOST", "db.sgis.tw");
define("DB_USER", "sinicaintern");
define("DB_PASS", "27857108311");
define("DB_NAME", "building");
define("geocoding_URL", "http://geocoding.sgis.tw/position.php");
define("timeout", 10);

//撈取資料需要表格名，主鍵，地址；更新資料需要表格名，主鍵，經緯度
define("architect_office", array('architect_office', 'office_ID', 'address', 'latitude', 'longitude'));
define("building_contractor", array('building_contractor', 'contractor_name', 'address', 'latitude', 'longitude'));
define("architect_project", array('architect_project', 'architect_license', 'address', 'latitude', 'longitude'));

do{
    printf("1.update architect office table\n");
    printf("2.update building contractor table\n");
    printf("3.update architect project table\n");
    printf("4.update all table\n");
    printf("0.EXIT\n");
    $choice = read_chioce();
    if($choice == 1){
        $DataBase = new DBClass();
        $DataBase->positioning(architect_office[0], architect_office[1], architect_office[2], architect_office[3], architect_office[4]);
        $DataBase->disconnrct();
    }
    else if($choice == 2){
        $DataBase = new DBClass();
        $DataBase->positioning(building_contractor[0], building_contractor[1], building_contractor[2], building_contractor[3], building_contractor[4]);
        $DataBase->disconnrct();
    }
    else if($choice == 3){
        $DataBase = new DBClass();
        $DataBase->positioning(architect_project[0], architect_project[1], architect_project[2], architect_project[3], architect_project[4]);
        $DataBase->disconnrct();
    }
    else if($choice == 4){
        $DataBase = new DBClass();
        $DataBase->positioning(architect_office[0], architect_office[1], architect_office[2], architect_office[3], architect_office[4]);
        $DataBase->positioning(building_contractor[0], building_contractor[1], building_contractor[2], building_contractor[3], building_contractor[4]);
        $DataBase->positioning(architect_project[0], architect_project[1], architect_project[2], architect_project[3], architect_project[4]);
        $DataBase->disconnrct();
    }
}while($choice != 0);

exit;




class DBClass {

    var $conn, $query, $result;
  
    
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


    public function update_DB($table_name, $primary_key, $primary_key_value, $lng, $lng_value, $lat, $lat_value){

        $this->sql = "UPDATE $table_name SET $lng= N'$lng_value', $lat= N'$lat_value' where $primary_key= N'$primary_key_value'"; 
        $this->rows = $this->conn->query($this->sql);

        if(!$this->rows){
            
            if(!mysqli_ping($this->conn)){
                $this->reconnect();
                $this->update_DB();
            }
            else if($this->rows->num_rows == 0){
                echo "0 results\n";
                exit;
            }
            else{
                echo "SQL Error: " . mysqli_error($this->conn)."\n";
                exit;
            }
        }
        else{
            echo $primary_key_value." update completed\n";
        }
       
    }

    //定位architect表格資料
    public function positioning($table_name, $primary_key, $address, $lat, $lng) {

        $this->query = "SELECT $primary_key, $address FROM $table_name";
        $this->result = $this->conn->query($this->query);
        

        if($this->result->num_rows <= 0){
            
            if(!mysqli_ping($this->conn)){
                $this->reconnect();
                $this->positioning();
            }
            else if($this->result->num_rows == 0){
                echo "0 results\n";
                exit;
            }
            else{
                echo "SQL Error: " . mysqli_error($this->conn)."\n";
                exit;
            }
        }
        
    
        while($this->row = $this->result->fetch_assoc()){

            echo 'receive: '.$this->row[$primary_key].",".$this->row[$address]."\n";

            $this->post = http_build_query(array("addr" => $this->row[$address]));

            $this->html_obj = post(geocoding_URL, $this->post, timeout);

            $this->html_str = json_decode($this->html_obj, true);

            $this->accuracy = $this->html_str["accuracy"];
            //準確度 >= 3 基本沒有經緯度，avoid warning
            if($this->accuracy < 3){
                $this->longitude = $this->html_str["lng"];
                $this->latitude = $this->html_str["lat"];
            }
            else{
                $this->longitude = -1;
                $this->latitude = -1;
            }

            echo $this->accuracy.",".$this->longitude.",".$this->latitude."\n";

            $this->update_DB($table_name, $primary_key, $this->row[$primary_key], $lng, $this->longitude, $lat, $this->latitude);
        } 
    }

}

function read_chioce(){
    
    $fp1=fopen("php://stdin", "r");
    $input=fgets($fp1, 255);
    fclose($fp1);

    return $input;
}

function post($url, $post, $timeout){  
        
    do {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);       
        curl_setopt($curl, CURLOPT_HEADER, false);       
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);      
        curl_setopt($curl, CURLOPT_NOSIGNAL,1);    
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);  //等待瀏覽器的回應時間    
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);      
        curl_setopt($curl, CURLOPT_POST,1); //開啟POST    
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);  //傳遞要求參數給伺服器   
        $info = curl_getinfo($curl);     
        $html = curl_exec($curl);
        $curl_errno = curl_errno($curl);  
        $curl_error = curl_error($curl);
        
        if(!$html || $curl_errno >0){
            echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url']."\n";
            echo "cURL Error ($curl_errno): $curl_error\n"; 
        }

        curl_close($curl);
        
    }while(!$html || $curl_errno >0);

    
    return $html;
}



?>