
		var tiles = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
				maxZoom: 18,
				attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>'
			}),
			latlng = L.latLng(50.0, 15.00);

		var map = L.map('map', {center: latlng, zoom: 7, layers: [tiles]});
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
			html:'<img src="'+a[3]+'" width=40px >',
			iconSize:[40, 40]
			}) ;
		
			var marker = L.marker(new L.LatLng(a[0], a[1]), {icon: ico});
			
			marker.bindPopup('<a href="'+a[4]+'">'+title+'<br><img src="'+a[3]+'" width=80px></a>');
		  //marker.addTo(map);
			 
			markers.push(marker);
		   
		}
		markerscg.addLayers(markers);			
		map.addLayer(markerscg);
