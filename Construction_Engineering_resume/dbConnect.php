<?php
 $db = mysqli_connect("db.sgis.tw", "sinicaintern", "27857108311", "building");
 if(!$db){
     die("無法對資料連線". mysqli_connect_error());
 }
 else
     echo "Successful!!";

?>