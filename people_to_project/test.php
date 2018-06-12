<?php

$car1 = array("鳳山", "red");
$car2 = array("中山", "blue");
$car3 = array("唐山", "black");
$column = array();


array_push($column, $car1);
array_push($column, $car2);
array_push($column, $car3);
//print_r($column);

foreach($column as $column_index => $column_value){
    
    echo "column[".$column_index."]"."\n";
    print_r($column_value);

    foreach($column_value as $car_index => $car_value){
        echo "car[".$car_index."]".$car_value."\n";
    }
}


?>
