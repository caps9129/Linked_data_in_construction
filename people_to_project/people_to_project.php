<?php

header("Content-Type:text/html; charset=utf-8");
include_once(__DIR__."/simple_html_dom.php");
ini_set('memory_limit', '-1');

define("DB_TABLE_architect_project", "architect_project");
define("DB_TABLE_architect_information", "architect_information");
define("DB_TABLE_architect_office", "architect_office");
define("DB_TABLE_building_contractor", "building_contractor");


require_once('./connectDB.php');

do{
    printf("1.update architect_project_ID in architect_information table\n");
    printf("2.update architect_project_ID in architect_office table\n");
    printf("3.update architect_project_ID in building_contractor table\n");
    printf("4.insert all table\n");
    printf("0.EXIT\n");
    $choice = read_chioce();
    if($choice == 1){
        $DataBase = new DBClass();
        $DataBase->select(DB_TABLE_architect_information);
        comparison($DataBase, $DataBase->result, DB_TABLE_architect_information);
        $DataBase->disconnect();
    }
    else if($choice == 2){
        $DataBase = new DBClass();
        $DataBase->select(DB_TABLE_architect_office);
        comparison($DataBase, $DataBase->result, DB_TABLE_architect_office);
        $DataBase->disconnect();
    }
    else if($choice == 3){
        $DataBase = new DBClass();
        $DataBase->select(DB_TABLE_building_contractor);
        comparison($DataBase, $DataBase->result, DB_TABLE_building_contractor);
        $DataBase->disconnect();
    }
    else if($choice == 4){
        $DataBase = new DBClass();
        $DataBase->select(DB_TABLE_architect_information);
        comparison($DataBase, $DataBase->result, DB_TABLE_architect_information);
        $DataBase->select(DB_TABLE_architect_office);
        comparison($DataBase, $DataBase->result, DB_TABLE_architect_office);
        $DataBase->select(DB_TABLE_building_contractor);
        comparison($DataBase, $DataBase->result, DB_TABLE_building_contractor);
        $DataBase->disconnect();
    }
}while($choice != 0);

exit;

function comparison($DataBase, $select_result, $table){

    $DataBase->select(DB_TABLE_architect_project);
    

    if($table ==  "architect_information"){
        foreach($select_result as $select_value){                               //建築師資訊
            $arr_project = array();
            foreach($DataBase->result as $result_value){                        //建案資訊
                if($select_value['architect_ID'] == $result_value['designer']){
                    $project = $result_value['license_type']."_".$result_value['architect_ID'];
                    array_push($arr_project, $project);
                }
            }
            $DataBase->update($table, 'architect_ID', $select_value['architect_ID'], $arr_project);
        }
    }
    else if($table ==  "architect_office"){
        foreach($select_result as $select_value){                               //建築師資訊
            $arr_project = array();
            foreach($DataBase->result as $result_value){                        //建案資訊
                if($select_value['office_ID'] == $result_value['supervisor']){
                    $project = $result_value['license_type']."_".$result_value['architect_ID'];
                    array_push($arr_project, $project);
                }
            }
            $DataBase->update($table, 'office_ID', $select_value['office_ID'], $arr_project);
        }
    }
    else if($table ==  "building_contractor"){
        foreach($select_result as $select_value){                               //建築師資訊
            $arr_project = array();
            foreach($DataBase->result as $result_value){                        //建案資訊
                if($select_value['contractor_ID'] == $result_value['contractor']){
                    $project = $result_value['license_type']."_".$result_value['architect_ID'];
                    array_push($arr_project, $project);
                }
            }
            $DataBase->update($table, 'contractor_ID', $select_value['contractor_ID'], $arr_project);
        }
    }
}

function read_chioce(){
    
    $fp1=fopen("php://stdin", "r");
    $input=fgets($fp1, 255);
    fclose($fp1);

    return $input;
}




?>