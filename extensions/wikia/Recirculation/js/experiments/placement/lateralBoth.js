/*global define*/
define('ext.wikia.recirculation.experiments.placement.lateralBoth', [
	'jquery',
	'ext.wikia.recirculation.utils',
	'ext.wikia.recirculation.helpers.lateral',
	'ext.wikia.recirculation.views.rail',
	'ext.wikia.recirculation.views.incontent',
], function ($, utils, LateralHelper, RailView, IncontentView) {

	function run() {
		var incontent = IncontentView(),
			rail = RailView(),
			incontentGroup,
			railGroup;

		incontentGroup = LateralHelper({
			type: 'community',
			count: 3
		}).loadData()
			.then(incontent.render)
			.then(incontent.setupTracking(experimentName));

		railGroup = LateralHelper({
			type: 'fandom',
			count: 5
		}).loadData()
			.then(rail.render)
			.then(rail.setupTracking(experimentName));

		return $.when(incontentGroup, railGroup);
	}

	return {
		run: run
	}

});
