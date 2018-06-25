<!DOCTYPE html>
<html>

<head>
    	<!-- Google font -->
	<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700%7CVarela+Round" rel="stylesheet">

    <!-- Bootstrap -->
    <link type="text/css" rel="stylesheet" href="css/bootstrap.min.css" />
    
    <!-- Owl Carousel -->
    <link type="text/css" rel="stylesheet" href="css/owl.carousel.css" />
    <link type="text/css" rel="stylesheet" href="css/owl.theme.default.css" />

    <!-- Magnific Popup -->
    <link type="text/css" rel="stylesheet" href="css/magnific-popup.css" />

    <!-- Font Awesome Icon -->
    <link rel="stylesheet" href="css/font-awesome.min.css">

    <!-- Custom stlylesheet -->
    <link type="text/css" rel="stylesheet" href="css/style.css" />
    <link type="text/css" rel="stylesheet" href="css/show.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Modify features example</title>
  

    <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">

    <!-- <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script> -->
    <!-- <link type="text/css" rel="stylesheet" href="css/bootstrap.css" /> -->
    <script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
    <!--draggable-->
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script> -->
    <script type="text/javascript" language="javascript" src="./unit.js" charset="utf-8"></script>
    <script type="text/javascript" language="javascript" src="./map.js" charset="utf-8"></script>
    <script type="text/javascript" language="javascript" src="./js/moment.js" charset="utf-8"></script>

    <!--time scroll bar-->    
    <link rel="stylesheet" href="./css/normalize.css" />
    <link rel="stylesheet" href="./css/ion.rangeSlider.css" />
    <link rel="stylesheet" href="./css/ion.rangeSlider.skinFlat.css" />
    <!-- <script src="./js/jquery-1.12.3.min.js"></script> -->
    <script src="./js/ion.rangeSlider.js"></script>
    

</head>

<script type='text/javascript'>


</script>

<body>
<header id="home">
        <div class="bg-img" style="background-image: url('./img/wooden.jpg'); background-size: contain;">
			<div class="overlay">
            </div>

        </div>

		<!-- Nav -->
		<nav id="nav" class="navbar nav-transparent">
			<div class="container">

				<div class="navbar-header">
					<!-- Logo -->
					<div class="navbar-brand">
						<a href="index.html">
							<img class="logo" src="img/geolocalization-alt.png" alt="logo">
							<img class="logo-alt" src="img/geolocalization.png" alt="logo">
						</a>
					</div>
					<!-- /Logo -->

					<!-- Collapse nav button -->
					<div class="nav-collapse">
						<span></span>
					</div>
					<!-- /Collapse nav button -->
				</div>

				<!--  Main navigation  -->
				<ul class="main-nav nav navbar-nav navbar-right">
					<li><a href="#home">Home</a></li>
					<li><a href="#about">About</a></li>
					<li><a href="#portfolio">Architecture</a></li>
					<li><a href="#contact">Contact</a></li>
					<li class="has-dropdown"><a href="#blog">Search</a>
						<ul class="dropdown">
                            <li><label><input type="checkbox" id="county-check" value="">County</label></li>
                            <li><label><input type="checkbox" id="id-check" value="">ID</label></li>
                            <li><label><input type="checkbox" id="time-check" value="">Time</label></li>
						</ul>
					</li>
				</ul>
				<!-- /Main navigation -->

			</div>
		</nav>
        <!-- /Nav -->
        <div class="container-fluid">

            <div class="row-fluid">
                <div class="span12">
                    <div id="map" class="map" style="width: 98.5%; height: 90%; position:fixed">
                        <div id="scale-line"></div>
                    </div>
                    <div id="popup" class="ol-popup">
                        <button type="button" id="popup-closer" class="ol-popup-closer">&times;</button>
                        <div id="popup-content"class="y"></div>
                    </div>

                   
                </div>
            </div>
        </div>

        
        <div id="Timebox" class = "box medium clearfix">
            <button type="button" class="closeX" id="timeclose">&times;</button>
            <div class="box-title">
                <h6>時間</h6>
            </div>
            <div class="rangebox">
                <input type="text" id="range" value="" name="range" />
            </div>
        </div>

        <div id="IDbox" class = "box medium clearfix">
            <button type="button" class="closeX" id="IDclose">&times;</button>
            <div class="box-title">
                <h6>ID</h6>_
            </div>
            <form action="javascript:create_sparql();" method="post" style="position: absolute; left: 6.5%; bottom: 25px">
                <input type="search" id="search_ID" name="user_search" placeholder="LicenseID/ArchitectID" />
                <input type="submit" />
            </form>
        </div>

        <div id="Countybox" class = "box medium clearfix">
            <button type="button" class="closeX" id="countyclose">&times;</button>
            <div class="box-title">
                <h6>County</h6>
            </div>
            <form>
                <div class="multiselect">
                    <div class="selectBox" onclick="showCheckboxes()">
                        <select>
                            <option>Select an county</option>
                        </select>
                        <div class="overSelect"></div>
                    </div>
                    <div id="checkboxes">
                        <label for="one">
                            <input type="checkbox" value="台北市" name="countybox" id="countybox"/>台北市</label>
                        <label for="two">
                            <input type="checkbox" value="高雄市" name="countybox"/>高雄市</label>
                        <label for="three">
                            <input type="checkbox" value="基隆市" name="countybox"/>基隆市</label>
                        <label for="three">
                            <input type="checkbox" value="宜蘭縣" name="countybox"/>宜蘭縣</label>
                        <label for="three">
                            <input type="checkbox" value="新北市" name="countybox"/>新北市</label>
                        <label for="three">
                            <input type="checkbox" value="桃園市" name="countybox"/>桃園市</label>
                        <label for="three">
                            <input type="checkbox" value="新竹市" name="countybox"/>新竹市</label>
                        <label for="three">
                            <input type="checkbox" value="新竹縣" name="countybox"/>新竹縣</label>
                        <label for="three">
                            <input type="checkbox" value="苗栗縣" name="countybox"/>苗栗縣</label>
                        <label for="three">
                            <input type="checkbox" value="台中市" name="countybox"/>台中市</label>
                        <label for="three">
                            <input type="checkbox" value="彰化縣" name="countybox"/>彰化縣</label>
                        <label for="three">
                            <input type="checkbox" value="南投縣" name="countybox"/>南投縣</label>
                        <label for="three">
                            <input type="checkbox" value="雲林縣" name="countybox"/>雲林縣</label>
                        <label for="three">
                            <input type="checkbox" value="嘉義市" name="countybox"/>嘉義市</label>
                        <label for="three">
                            <input type="checkbox" value="嘉義縣" name="countybox"/>嘉義縣</label>
                        <label for="three">
                            <input type="checkbox" value="台南市" name="countybox"/>台南市</label>
                        <label for="three">
                            <input type="checkbox" value="屏東縣" name="countybox"/>屏東縣</label>
                        <label for="three">
                            <input type="checkbox" value="花蓮縣" name="countybox"/>花蓮縣</label>
                        <label for="three">
                            <input type="checkbox" value="台東縣" name="countybox"/>台東縣</label>
                        <label for="three">
                            <input type="checkbox" value="澎湖縣" name="countybox"/>澎湖縣</label>
                        <label for="three">
                            <input type="checkbox" value="連江縣" name="countybox"/>連江縣</label>
                        <label for="three">
                            <input type="checkbox" value="金門縣" name="countybox"/>金門縣</label>
                    </div>
                </div>
            </form>
        </div>


<!-- jQuery Plugins -->
    <!-- <script type="text/javascript" src="js/jquery.min.js"></script> -->
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/owl.carousel.min.js"></script>
	<script type="text/javascript" src="js/jquery.magnific-popup.js"></script>
    <script type="text/javascript" src="js/main.js"></script>
    
</body>

</html>