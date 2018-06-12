<?php
define("DB_HOST", "140.109.161.93");
define("DB_USER", "ntpc");
define("DB_PASS", "ac6tmsks@a");
define("DB_NAME", "ntpc");


$DataBase = new DBClass();



class DBClass {

    var $conn, $query, $result, $sql, $move;
  
    
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
            echo "dbConnect Successful!!\n";
            if (!$this->conn->set_charset("utf8")) {
                printf("Error loading character set utf8: %s\n", $this->conn->error);
                
            } else {
                printf("Current character set: %s\n", $this->conn->character_set_name());
            }  
        }
    }

 

    //判斷資料表是否有資料同時做更新準備
    public function select($table){

        if(!mysqli_ping($this->conn)){
            $this->reconnect();
            $this->select();
        }
        
        $this->query = "SELECT * FROM $table";
        $this->result = $this->conn->query($this->query);

        if($this->result->num_rows <= 0){
            
            if(!mysqli_ping($this->conn)){
                $this->reconnect();
                $this->positioning();
                $this->select();
            }
            else if($this->result->num_rows == 0){
                $this->result = false;
                return 0;
            }
            else{
                echo "SQL Error: " . mysqli_error($this->conn)."\n";
                exit;
            }
        }



    }

    //insert record && raw
    public function update($table, $ID, $ID_value, $arr_project_ID) {


        if(!mysqli_ping($this->conn)){
            $this->reconnect();
            $this->update($table, $ID, $arr_project_ID);
        }
        //$escaped_values = array_map('mysql_real_escape_string', array_values($arr_project_ID));
        
        $escaped_values = $this->conn->real_escape_string(implode(";", $arr_project_ID));
        //$values  = implode(", ", $escaped_values);
    
        if($escaped_values == false){
            $escaped_values = '無相關資料';
        }

            $sql = "UPDATE $table SET project_ID = N'$escaped_values' WHERE $ID = N'$ID_value'";
        
        if ($this->conn->query($sql) === TRUE) {
            echo "$ID_value updated successfully\n";
        } 
        else {
            echo "Error updating record: " . $this->conn->error;
            exit;
        }
  
    }
    

}

?>