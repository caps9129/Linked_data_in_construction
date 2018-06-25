var map = undefined;
var VectorSource = undefined;

$(function() {


    init_map();

    
});

function loadFeatureFromGeojson(geojson){
    var features = (new ol.format.GeoJSON()).readFeatures(geojson, {
      featureProjection: 'EPSG:3857'
    });
    return features;
}




function init_map() {

    
    var osm = new ol.layer.Tile({
        source: new ol.source.OSM()
    });
    
    var geojson = {"type":"FeatureCollection","crs":{"type":"name","properties":{"name":"EPSG:4326"}},"features":[]};

    VectorSource = new ol.source.Vector({
        features: loadFeatureFromGeojson(geojson)
    });

    var clusterSource = new ol.source.Cluster({
        distance: parseInt(40, 10),
        source: VectorSource
    });
    
    var styleCache = {};

    var clusters = new ol.layer.Vector({
        source: clusterSource,
        style: function(feature) {
          var size = feature.get('features').length;
          var style = styleCache[size];
          if (!style) {
            style = new ol.style.Style({
              image: new ol.style.Circle({
                radius: 10,
                stroke: new ol.style.Stroke({
                  color: '#fff'
                }),
                fill: new ol.style.Fill({
                  color: '#3399CC'
                })
              }),
              text: new ol.style.Text({
                text: size.toString(),
                fill: new ol.style.Fill({
                  color: '#fff'
                })
              })
            });
            styleCache[size] = style;
          }
          return style;
        }
      });

    var view = new ol.View({
        center: ol.proj.transform([120.946727, 23.672028], 'EPSG:4326', 'EPSG:3857'),
        zoom: 8
    });

    map = new ol.Map({
        controls: ol.control.defaults().extend([
            new ol.control.ScaleLine({ className: 'ol-scale-line', target: document.getElementById('scale-line') })
        ]),
        layers: [osm, clusters],
        target: 'map',
        view: view
    });

}


function ImportGeojson(geojson) {


    console.log("geojson:", geojson)

    if(typeof(VectorSource)!=='undefined'){ 
        VectorSource.clear();
        VectorSource.addFeatures(loadFeatureFromGeojson(geojson));
    }

    click_info(map);

}


//create geojson
function CreateGeojson(arr_query_result) {
    
    //arr_query_result = ["http://archi.sgis.tw/ontology/0_(104)(1)(23)建管建師字第90033號", "0", "http%3A%2F%2Fbuilding.e-land.gov.tw%2Fbupic%2FIDCh…2740%26index_key%3D1041900330000I20%26canflag%3D1",
    //                    "宜蘭縣礁溪鄉三民一段356地號", "3", "-1", "-1", "A30合眾建築經理股份有限公司負責人:顏文澤", "建證字第1577號", "無此資料", "無此資料", "104"]
    
    var features = [], geojson = [],     

        json_query_result = JSON.parse(arr_query_result);


    for (var row = 0; row < json_query_result.length; row++) {

        // console.log(parseFloat(json_query_result[row][5]));
        // console.log(parseFloat(json_query_result[row][6]));

        if(json_query_result[row][5] != "-1" && parseFloat(json_query_result[row][5])){

            features.push({
                "type": "Feature",
                "properties": {
                    "license": json_query_result[row][0],
                    "license_type": json_query_result[row][1],
                    "license_url": json_query_result[row][2],
                    "address": json_query_result[row][3],
                    "accuracy": json_query_result[row][4],
                    "longtitude": parseFloat(json_query_result[row][5]),
                    "latitude": parseFloat(json_query_result[row][6]),
                    "builder": json_query_result[row][7],
                    "designer": json_query_result[row][8],
                    "supervisor": json_query_result[row][9],
                    "contractor": json_query_result[row][10],
                    "year": parseFloat(json_query_result[row][11]),
                },
                "geometry": {
                    "type": "Point",
                    "coordinates": [parseFloat(json_query_result[row][5]), parseFloat(json_query_result[row][6])]
                }
            })
        }
        else{
            continue;
        }
        
    }

    geojson = {
        "type": "FeatureCollection",
        "crs": {
            "type": "name",
            "properties": {
                "name": "EPSG:4326"              //坐標系統改成EPSG:4326
            }
        },
        "features": features
    }

    return geojson;


}

function click_info(map){
    var container = document.getElementById('popup');  // 顯示的框框
    var content = document.getElementById('popup-content'); //框框裡的小框框紀錄文字內容
    var closer = document.getElementById('popup-closer');  //右上角的關閉

    //宣告overlay為變數，紀錄關於他的屬性
    var overlay = new ol.Overlay({
        element: container,
        autoPan: true,
        autoPanAnimation: {
            duration: 250
        }
    });

    //替map上加一層名為overlay之圖層以供顯示
    map.addOverlay(overlay);

    //觸發popup關閉事件
    closer.onclick = function() {
        overlay.setPosition(undefined);
        closer.blur();
        return false;
    };

    //觸發點擊圖標事件
    map.on('click', function(evt) {
        //取得feature，提供內容顯示需求
        var feature = map.forEachFeatureAtPixel(evt.pixel,
            function(feature, layer) {

            if (feature) {
                //console.log(feature);
                var coord = map.getCoordinateFromPixel(evt.pixel);
       
                var arr_features = feature.get('features');
                console.log(arr_features);
                $(content).html("");
                for (var i = 0; i < arr_features.length; i++) {
                    var license = arr_features[i].get('license');
                    var license_type = arr_features[i].get('license_type');
                    var address = arr_features[i].get('address');
                    var builder = arr_features[i].get('builder');
                    var contractor = arr_features[i].get('contractor');
                    var designer = arr_features[i].get('designer');
                    var supervisor = arr_features[i].get('supervisor');
                    var year = arr_features[i].get('year');
                    //content.innerHTML ="<b> ----地址"+(i+1)+"資訊---- </b><br />"+'<article>' + address +"<br />"+"變更型態："+type+"<br / >"+"起始時間："+Stime +'</article>';
                    $(content).append("<b> ----建築"+license+"資訊---- </b><br />");
                    $(content).append('<article>'+"建築執照: "+license+"<br />"+"執照類別："+license_type+"<br / >"+"位置："+address+"<br / >"+"起造人: "+builder+"<br / >"+"設計人: "+designer+"<br / >"+"監造人: "+supervisor+"<br / >"+"承造人: "+contractor+"<br / >"+"年份: "+year+"<br / >"+'</article>');
                }
                
                overlay.setPosition(coord);
            }
        });
    });
}

// var Fspouse = new ol.layer.Vector({
//     source: vectorSource,
//     style: new ol.style.Style({
//         image: new ol.style.Circle({
//             fill: new ol.style.Fill({ color: [255, 255, 255, 1] }),
//             stroke: new ol.style.Stroke({ color: [0, 0, 0, 1] }),
//             radius: 5
//         })
//     })
    
// });









