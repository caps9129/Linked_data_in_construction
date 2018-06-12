
$(function() {

    test();
    
});


function test() {

    var map = new ol.Map({
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            })
        ],
        target: 'map',
        controls: ol.control.defaults({
            attributionOptions: {
                collapsible: false
            }
        }),
        view: new ol.View({
            center: [0, 0],
            zoom: 2
        })
    });
}


function ImportGeojson() {
    //console.log("geojson:", geojson)

    // var osm = new ol.layer.Tile({
    //     source: new ol.source.OSM()
    // });


    // var features = new ol.format.GeoJSON().readFeatures(geojson, {
    //     featureProjection: 'EPSG:3857'
    // });
    // var vectorSource = new ol.source.Vector({
    //     features: features
    // });
    // var Fspouse = new ol.layer.Vector({
    //     source: vectorSource,
    //     style: function (feature) {
    //         feature.setStyle(myStyleFunctionLev(feature));
    //     }
    // });

    // var view = new ol.View({
    //     center: ol.proj.transform([120.946727, 23.672028], 'EPSG:4326', 'EPSG:3857'),
    //     zoom: 8
    // });

    // var map = new ol.Map({
    //     controls: ol.control.defaults().extend([
    //         new ol.control.ScaleLine({ className: 'ol-scale-line', target: document.getElementById('scale-line') })
    //     ]),
    //     layers: [osm, Fspouse],
    //     target: 'map',
    //     view: view
    // });

}


//create geojson
// function CreateGeojson(arr_raindata) {
//     var features = [], geojson = [],
//         x, y;

//     //var file = new File(textfile, "write");

//     for (var row = 0; row < arr_raindata.length; row++) {


//         for (var column = 0; column < 75; column++) {

//             x = parseFloat(arr_raindata[row][column][1]) * 20037508.34 / 180;
//             y = Math.log(Math.tan((90 + parseFloat(arr_raindata[row][column][2])) * Math.PI / 360)) / (Math.PI / 180);
//             y = y * 20037508.34 / 180;

//             features.push({
//                 "type": "Feature",
//                 "properties": {
//                     "longtitude": parseFloat(arr_raindata[row][column][1]),
//                     "latitude": parseFloat(arr_raindata[row][column][2]),
//                     "rainfall": arr_raindata[row][column][0]
//                 },
//                 "geometry": {
//                     "type": "Point",
//                     "coordinates": [parseFloat(arr_raindata[row][column][1]), parseFloat(arr_raindata[row][column][2])]
//                 }
//             })
//         }
//     }

//     geojson = {
//         "type": "FeatureCollection",
//         "crs": {
//             "type": "name",
//             "properties": {
//                 "name": "EPSG:4326"              //坐標系統改成EPSG:4326
//             }
//         },
//         "features": features
//     }



//     return geojson;


// }










