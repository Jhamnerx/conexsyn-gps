"use strict";

/*global L:true*/
L.MarkerClusterGroup.include({
    _noanimationUnspiderfy: function _noanimationUnspiderfy() {
        if (this._spiderfied) {
            this._spiderfied.unspiderfy();
        }
    }
});

L.MarkerCluster.include({
    spiderfy: function spiderfy() {
        var group = this._group;
        var options = group.options;

        if (group._spiderfied === this || group._inZoomAnimation) {
            return;
        }

        if (!options.spiderfyCallback) {
            return;
        }

        options.spiderfyCallback(this.getAllChildMarkers(), this);
    },
    unspiderfy: function unspiderfy(zoomDetails) {
        if (this._group._inZoomAnimation) {
            return;
        }

        this._group._spiderfied = null;
    }
});