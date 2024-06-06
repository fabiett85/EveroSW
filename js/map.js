(($) => {
	$(() => {
		const map = L.map('map').setView([45.469, 10.515], 10);
		const isRetina = L.Browser.retina;
		const baseUrl = 'https://maps.geoapify.com/v1/tile/osm-carto/{z}/{x}/{y}.png?apiKey={apiKey}';
		const retinaUrl =
			'https://maps.geoapify.com/v1/tile/osm-carto/{z}/{x}/{y}@2x.png?apiKey={apiKey}';
		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution:
				'Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | <a href="https://openmaptiles.org/" target="_blank">© OpenMapTiles</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap</a> contributors',
			//apiKey: '16b4a39bf5e346a5864028ee7f48f207',
			maxZoom: 16,
			//id: 'osm-bright',
		}).addTo(map);
		var myIcon = L.divIcon({ className: 'mdi mdi-school mdi-36px', iconSize: [36, 54] });
		// you can set .my-div-icon styles in CSS

		var marker = L.marker([45.461064, 10.476923], {
			icon: myIcon,
			title: 'Scuola Lonato',
			alt: 'Scuola Lonato',
		}).addTo(map);
		var marker = L.marker([45.469, 10]).addTo(map);

		/* var popup = L.popup();

		function onMapClick(e) {
			popup
				.setLatLng(e.latlng)
				.setContent('You clicked the map at ' + e.latlng.toString())
				.openOn(map);
		}

		map.on('click', onMapClick); */
	});
})(jQuery);
