		var tiles = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
				maxZoom: 18,
				attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>'
			}),
			latlng = L.latLng(50.0, 15.00);
		if ( jQuery("#map").width()<400){
			var map = L.map('map', {center: latlng, zoom: 5, layers: [tiles]});
		}else{
		var map = L.map('map', {center: latlng, zoom: 6, layers: [tiles]});
		}
		
		var MyIcon = L.Icon.extend({
    options: {

      
    }
});



		var markerscg = L.markerClusterGroup({showCoverageOnHover:false,spiderfyDistanceMultiplier:2});
		var markers = [];
		var icons=  [];
		for (var i = 0; i < teams.length; i++) {
			var a = teams[i];
			var title = a[2];
			
			var ico=L.divIcon({
			html:'<a href="'+a[4]+'"><img src="'+a[3]+'" width=40px ></a>',
			iconSize:[40, 40]
			}) ;
		
			var marker = L.marker(new L.LatLng(a[0], a[1]), {icon: ico});
			
	
	   
		  //marker.addTo(map);
			 
			markers.push(marker);
		   
		}
		markerscg.addLayers(markers);			
		map.addLayer(markerscg);
