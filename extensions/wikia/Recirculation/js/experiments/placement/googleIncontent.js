/*global define*/
define('ext.wikia.recirculation.experiments.placement.googleIncontent', [
	'jquery',
	'ext.wikia.recirculation.utils',
	'ext.wikia.recirculation.tracker',
	'ext.wikia.recirculation.helpers.googleMatch',
	'ext.wikia.recirculation.views.incontent'
], function ($, utils, tracker, GoogleMatchHelper, IncontentView) {

	function run() {
		var deferred = $.Deferred(),
			section = IncontentView().findSuitableSection();

		if (section.exists()) {
			GoogleMatchHelper.injectGoogleMatchedContent(section);
			tracker.trackVerboseImpression(experimentName, 'in-content');
			deferred.resolve();
		} else {
			deferred.reject('Could not render Google Match in-content. No suitable section found');
		}

		return deferred.promise();
	}

	return {
		run: run
	}

});
