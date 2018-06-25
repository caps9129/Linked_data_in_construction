var arr_county = [];
var ID = "";
var url = "";


$(function() {

    init_unit(); //初始化物件

    create_sparql(); //收集物件資訊並建立sparql

    //time checkbox勾選
    $('#time-check').click(function(){
        var x = document.getElementById("Timebox");
        if (document.getElementById("time-check").checked == false) {
            x.style.display = "none";
            
        } else {
            x.style.display = "block";
        }
    });
    //ID checkbox勾選
    $('#id-check').click(function(){
        var x = document.getElementById("IDbox");
        if (document.getElementById("id-check").checked == false) {
            x.style.display = "none";
            
        } else {
            x.style.display = "block";
        }
    });
    //county checkbox勾選
    $('#county-check').click(function(){
        var x = document.getElementById("Countybox");
        if (document.getElementById("county-check").checked == false) {
            x.style.display = "none";
            
        } else {
            x.style.display = "block";
        }
        
        
    });

    //縣市勾選 
    $("input[name='countybox']").change(function() {
        create_sparql();
        //checkbox_state();       
    });


  
    //時間選擇條
    $("#range").ionRangeSlider({
        type: "double",
        min: +moment().subtract(1, "years").format("X"),
        max: +moment().format("X"),
        from: +moment().subtract(6, "months").format("X"),
        prettify: function (num) {
            return moment(num, "X").format("LL");
        }
    });
    //時間關閉鈕
    $("#timeclose").click(function() {
    
        document.getElementById("Timebox").style.display = "none";
        document.getElementById("time-check").checked = false;
        
    });
    //ID關閉紐
    $("#IDclose").click(function() {
    
        document.getElementById("IDbox").style.display = "none";
        document.getElementById("id-check").checked = false;
        
        
    });
    //城市關閉紐
    $("#countyclose").click(function() {

        document.getElementById("Countybox").style.display = "none";
        document.getElementById("county-check").checked = false;
        
        
    });
    
    
});

//建立sparql
function create_sparql(){
    
    /*PREFIX archi: <http://archi.sgis.tw/ontology/>
    select distinct * where 
    { 
    ?a archi:project_address ?b .
    ?a archi:longitude ?c .
    ?a archi:latitude ?d 
    FILTER (regex(?b, "嘉義縣") || regex(?b, "高雄市"))

    } */

    var count = "(count(*) AS ?count)"

    var select = "distinct *";

    var attribute = "?license archi:license_type ?type ."+
                    "?license archi:license_url ?url ."+
                    "?license archi:project_address ?project_address ."+
                    "?license archi:accuracy ?accuracy ."+
                    "?license archi:longitude ?longitude ."+
                    "?license archi:latitude ?latitude ."+
                    "?license archi:creator ?creator ."+
                    "?license archi:designer ?designer ."+
                    "?license archi:supervisor ?supervisor ."+
                    "?license archi:contractor ?contractor ."+
                    "?license archi:year ?year ";


    var sparql = "PREFIX archi: <http://archi.sgis.tw/ontology/> select " + select + " where { " + attribute;

    var count_sparql = "PREFIX archi: <http://archi.sgis.tw/ontology/> select " + count + " where { " + attribute;
       

    get_ID(); 
    checkbox_state();
    if(ID && arr_county){

    }
    else if(ID){
        console.log(ID);
    }
    else if(arr_county){
        var count = 0;
        var county_sparql = "FILTER (";
        arr_county.forEach(function(county_value) {
            if(count == 0){ 
                county_sparql = county_sparql + "regex(?project_address,\"" + county_value + "\")";
            }
            else{
                county_sparql = county_sparql + " || regex(?project_address,\"" + county_value + "\")";
            }
            count ++;
        });
        county_sparql = county_sparql + ")";
        sparql = sparql + county_sparql +  "}";
        count_sparql = count_sparql + county_sparql +  "}";
    }
    //console.log(count_sparql);
    //url = "default-graph-uri=http%3A%2F%2Fsgis.tw&query=PREFIX+archi%3A+%3Chttp%3A%2F%2Farchi.sgis.tw%2Fontology%2F%3E+select+distinct+*+where%7B%3Flicense+archi%3Alicense_type+%3Ftype+.%3Flicense+archi%3Alicense_url+%3Furl+.%3Flicense+archi%3Aproject_address+%3Fproject_address+.%3Flicense+archi%3Aaccuracy+%3Faccuracy+.%3Flicense+archi%3Alongitude+%3Flongitude+.%3Flicense+archi%3Alatitude+%3Flatitude+.%3Flicense+archi%3Acreator+%3Fcreator+.%3Flicense+archi%3Adesigner+%3Fdesigner+.%3Flicense+archi%3Asupervisor+%3Fsupervisor+.%3Flicense+archi%3Acontractor+%3Fcontractor+.%3Flicense+archi%3Ayear+%3Fyear+FILTER+%28regex%28%3Fproject_address%2C%22%E5%8F%B0%E5%8C%97%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%AB%98%E9%9B%84%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%9F%BA%E9%9A%86%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%AE%9C%E8%98%AD%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%96%B0%E5%8C%97%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%A1%83%E5%9C%92%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%96%B0%E7%AB%B9%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%96%B0%E7%AB%B9%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E8%8B%97%E6%A0%97%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8F%B0%E4%B8%AD%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%BD%B0%E5%8C%96%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8D%97%E6%8A%95%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%9B%B2%E6%9E%97%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%98%89%E7%BE%A9%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%98%89%E7%BE%A9%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8F%B0%E5%8D%97%E5%B8%82%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%B1%8F%E6%9D%B1%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E8%8A%B1%E8%93%AE%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E5%8F%B0%E6%9D%B1%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E6%BE%8E%E6%B9%96%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%80%A3%E6%B1%9F%E7%B8%A3%22%29+%7C%7C+regex%28%3Fproject_address%2C%22%E9%87%91%E9%96%80%E7%B8%A3%22%29%29%7D&should-sponge=&format=text%2Fhtml&timeout=0&debug=on";
    
  
    $.ajax({
        'url': './request.php',
        'method': 'post',
        'data': {
            'sparql':sparql,
            'count_sparql':count_sparql
        },
        'success': function(result){
            //console.log(result);
            geojson = CreateGeojson(result);
            ImportGeojson(geojson);
            //return result;
            //result.length
        },
    });
        
  

}


//檢查checkbox勾選狀態
function checkbox_state(){
    arr_county = [];
    $("input[name='countybox']").each(function() {
        if($(this).is(':checked')){
            arr_county.push($(this).val());
            //console.log( $(this).val() );
        }
    });
    //console.log(arr_county);
}
//取得輸入的ID
function get_ID(){
    ID = "";
    ID = $("#search_ID").val();
    $("#search_ID").val("");
    //create_sparql();
}

//顯示checkbox
var expanded = false;
function showCheckboxes() {
    var checkboxes = document.getElementById("checkboxes");
    if (!expanded) {
        checkboxes.style.display = "block";
        expanded = true;
    } else {
        checkboxes.style.display = "none";
        expanded = false;
    }
}

//元件初始化
function init_unit(){
    document.getElementById("IDbox").style.display = "none";
    document.getElementById("Timebox").style.display = "none";
    document.getElementById("Countybox").style.display = "none";
    $( "#IDbox" ).draggable();
    $( "#Countybox" ).draggable();
    $("input[name='countybox']").each(function() {
        $(this).prop("checked", true);
    });
}


