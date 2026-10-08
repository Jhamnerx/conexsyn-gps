function Aircraft() {
    var
        _this = this,
        map = null,
        layer = null,
        fetchInterval = null;

    _this.setMap = function (map) {
        const isShown = _this.isShown();

        _this.hide();

        _this.map = map;

        _this.layer = L.layerGroup().addTo(_this.map);

        if (isShown) {
            _this.show();
        }
    };

    _this.isEnabled = function () {
        return app.settings.aircraftEnabled;
    };

    _this.isShown = function () {
        return !!_this.fetchInterval;
    };

    _this.show = function () {
        if (!_this.isEnabled()) {
            return;
        }

        if (_this.fetchInterval) {
            return;
        }

        _this.fetchAircraft();

        _this.fetchInterval = setInterval(_this.fetchAircraft, 5000);
    };

    _this.hide = function () {
        if (!_this.isEnabled()) {
            return;
        }

        if (!_this.fetchInterval) {
            return;
        }

        clearInterval(_this.fetchInterval);

        _this.fetchInterval = null;

        _this.layer.clearLayers();
    };

    _this.fetchAircraft = function () {
        const zoom = _this.map.getZoom();

        // endpoint max dist is 250
        if (zoom <= 6) {
            _this.layer.clearLayers();
            return;
        }

        const center = _this.map.getCenter();
        const lat = center.lat.toFixed(5);
        const lon = center.lng.toFixed(5);

        fetch(`/flights?lat=${lat}&lon=${lon}`)
            .then(res => res.json())
            .then(data => {
                _this.layer.clearLayers();

                const bounds = _this.map.getBounds();

                data.aircraft.forEach(ac => {
                    if (!ac.lat || !ac.lon)
                        return;

                    if (!bounds.contains([ac.lat, ac.lon]))
                        return;

                    const heading = ac.track || 0;

                    const icon = L.divIcon({
                        className: 'aircraft-icon',
                        html: `<div style="transform: rotate(${heading}deg);">✈</div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10],
                    });

                    const marker = L.marker([ac.lat, ac.lon], {icon});

                    marker.bindPopup(_this.aircraftContentPopup(ac));
                    marker.addTo(_this.layer);
                });
            });
    };

    _this.aircraftContentPopup = function(ac) {
        var nav = '';
        nav += '<ul class="nav nav-tabs nav-default" role="tablist">';
        nav += '<li data-toggle="tooltip" data-placement="top" title="Close"><a href="javascript:" data-dismiss="popup"><i class="fa fa-times fa-1"></i></a></li>';
        nav += '</ul>';

        var parametersHTML = '<table class="table table-condensed"><tbody>';

        parametersHTML += '<tr><th>Flight:</th><td>' + (ac.flight || 'N/A') + '</td></tr>';
        parametersHTML += '<tr><th>Description:</th><td>' + (ac.desc || 'N/A') + '</td></tr>';

        parametersHTML += '</tbody></table>';

        var html  = '';
        html += '<div class="popup-content">';
        html += '   <div class="popup-header">'+nav+'<div class="popup-title"></div></div>';
        html += '   <div class="popup-body">'+parametersHTML+'</div>';
        html += '</div>';

        return html;
    };
}
