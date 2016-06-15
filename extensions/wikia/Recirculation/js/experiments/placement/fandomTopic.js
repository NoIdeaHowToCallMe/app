/*global define*/
define('ext.wikia.recirculation.experiments.placement.fandomTopic', [
	'jquery',
	'ext.wikia.recirculation.utils',
	'ext.wikia.recirculation.helpers.fandom',
	'ext.wikia.recirculation.views.rail'
], function ($, utils, FandomHelper, RailView) {

	function run() {
		var view = RailView();

		return FandomHelper({
		    type: 'community',
			limit: 5
		}).loadData()
			.then(utils.waitForRail)
			.then(view.render)
			.then(view.setupTracking(experimentName));
	}

	return {
		run: run
	}

});
