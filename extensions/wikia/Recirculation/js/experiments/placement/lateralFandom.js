/*global define*/
define('ext.wikia.recirculation.experiments.placement.lateralFandom', [
	'jquery',
	'ext.wikia.recirculation.utils',
	'ext.wikia.recirculation.helpers.lateral',
	'ext.wikia.recirculation.views.rail',
], function ($, utils, LateralHelper, RailView) {

	function run() {
		var view = RailView();

		return LateralHelper().loadData()
			.then(utils.waitForRail)
			.then(view.render)
			.then(view.setupTracking(experimentName));
	}

	return {
		run: run
	}

});
