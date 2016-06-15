/*global define*/
define('ext.wikia.recirculation.experiments.placement.lateralScroller', [
	'jquery',
	'ext.wikia.recirculation.utils',
	'ext.wikia.recirculation.helpers.lateral',
	'ext.wikia.recirculation.views.scroller',
], function ($, utils, LateralHelper, ScrollerView) {

	function run() {
		var view = ScrollerView();

		return LateralHelper({
			type: 'community',
			count: 12
		}).loadData()
			.then(utils.waitForRail)
			.then(view.render)
			.then(view.setupTracking(experimentName));
	}

	return {
		run: run
	}

});
