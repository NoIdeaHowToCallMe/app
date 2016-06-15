/*global require*/

require([
	'wikia.window',
	'wikia.abTest',
	'wikia.log',
	'ext.wikia.recirculation.experiments.placement.control',
	'ext.wikia.recirculation.experiments.placement.cakeRelatedContent',
	'ext.wikia.recirculation.experiments.placement.fandomGenre',
	'ext.wikia.recirculation.experiments.placement.fandomTopic',
	'ext.wikia.recirculation.experiments.placement.googleIncontent',
	'ext.wikia.recirculation.experiments.placement.impactFooter',
	'ext.wikia.recirculation.experiments.placement.lateralBoth',
	'ext.wikia.recirculation.experiments.placement.lateralCommunity',
	'ext.wikia.recirculation.experiments.placement.lateralFandom',
	'ext.wikia.recirculation.experiments.placement.lateralScroller',
	'ext.wikia.recirculation.experiments.placement.linksScroller',
	require.optional('videosmodule.controllers.rail')
], function(
	w,
	abTest,
	log,
	Control,
	CakeRelatedContent,
	FandomGenre,
	FandomTopic,
	GoogleIncontent,
	ImpactFooter,
	LateralBoth,
	LateralCommunity,
	LateralFandom,
	LateralScroller,
	LinksScroller,
	VideosModule
) {
	var experimentName = 'RECIRCULATION_PLACEMENT',
		logGroup = 'ext.wikia.recirculation.experiments.placement',
		railSelector = '#RECIRCULATION_RAIL',
		group = abTest.getGroup(experimentName),
		isRail = false,
		errorHandled = false,
		experiment;

	if (w.wgContentLanguage !== 'en') {
		if (VideosModule) {
			VideosModule(railSelector);
		}
		return;
	}

	switch (group) {
		case 'LATERAL_BOTH':
			experiment = LateralBoth;
			return;
		case 'LATERAL_FANDOM':
			experiment = LateralFandom;
			break;
		case 'LATERAL_COMMUNITY':
			experiment = LateralCommunity;
			break;
		case 'LATERAL_SCROLLER':
			experiment = LateralScroller;
			break;
		case 'LINKS_SCROLLER':
			experiment = LinksScroller;
			break;
		case 'FANDOM_GENRE':
			experiment = FandomGenre;
			break;
		case 'FANDOM_TOPIC':
			experiment = FandomTopic;
			break;
		case 'CONTROL':
			experiment = Control;
			break;
		case 'GOOGLE_INCONTENT':
			experiment = GoogleIncontent;
			return;
		case 'CAKE_RELATED_CONTENT':
			experiment = CakeRelatedContent;
			break;
		case 'IMPACT_FOOTER':
			experiment = ImpactFooter;
			return;
		default:
			return;
	}

	experiment.run(experimentName)
		.fail(function(err) {
			if (err) {
				log(err, 'info', logGroup);
			}

			// If there is an error somewhere we render the control group with no tracking
			if (errorHandled) {
				return;
			}

			errorHandled = true;
			Control.run();
		});
});
