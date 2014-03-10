/**
 * This code constructs adConfig2Late for use with Liftium
 * and passes it to AdEngine using AdEngine_setLateConfig
 *
 * Liftium must call AdEngine_loadLateAds to trigger showing ads
 */

/*global DartUrl, ScriptWriter, AdLogicPageLevelParams, SlotTweaker, AdTracker*/
/*global AdProviderNull, AdProviderRemnantDart, AdProviderLiftium, AdProviderSevenOneMedia, SevenOneMediaHelper*/
/*global AdConfig2Late, Wikia, window, document, Geo, Krux, jQuery*/
/*jslint newcap:true*/
/*jshint maxparams:false, camelcase:false, maxlen: 150*/

(function (log, tracker, window, document, Geo, Krux, $, abTest) {
	'use strict';

	var adConfig,
		scriptWriter,
		adLogicPageLevelParams,
		adLogicPageLevelParamsLegacy,
		dartUrl,
		adTracker,
		slotTweaker,
		wikiaFullGpt,
		fakeLiftium = {},
		adProviderRemanantDart,
		adProviderLiftium,
		adProviderNull,
		adProviderSevenOneMedia,
		sevenOneMediaHelper;

	// TODO: make Liftium and AdEngine2 rely less on order of execution
	fakeLiftium.callInjectedIframeAd = function (sizeOrSlot, iframeElement, placement) {
		return window.Liftium.callInjectedIframeAd(sizeOrSlot, iframeElement, placement);
	};

	adTracker = AdTracker(log, tracker, window);
	dartUrl = DartUrl();
	scriptWriter = ScriptWriter(document, log, window);
	adLogicPageLevelParams = AdLogicPageLevelParams(log, window, Krux); // omitted a few optional deps
	slotTweaker = SlotTweaker(log, document, window);
	wikiaFullGpt = WikiaFullGptHelper(log, window, document, adLogicPageLevelParams);

	// TODO: ad provider error
	adProviderNull = AdProviderNull(log, slotTweaker);

	sevenOneMediaHelper = SevenOneMediaHelper(adLogicPageLevelParams, scriptWriter, log, window, $, tracker);
	adProviderSevenOneMedia = AdProviderSevenOneMedia(log, window, adTracker, $, sevenOneMediaHelper);

	if (window.wgEnableRHonDesktop) {
        adProviderRemanantDart = AdProviderRemnantDart(adTracker, log, slotTweaker, wikiaFullGpt);
	} else {
		adProviderLiftium = AdProviderLiftium(log, document, slotTweaker, fakeLiftium, scriptWriter, window);
	}

	adConfig = AdConfig2Late(
		log,
		window,
		abTest,
		window.wgEnableRHonDesktop ? adProviderLiftium : adProviderRemanantDart,
		adProviderNull,
		adProviderSevenOneMedia
	);

	/*
	 * TODO: this is the right approach but it does compete with AdDriver:
	 *
	 * Liftium.init(function() {
	 *   window.AdEngine_loadLateAdsNow(adConfig);
	 * });
	 *
	 * (And remove AdEngine_setLateConfig)
	 *
	 * END OF TODO
	 */
	window.AdEngine_setLateAdsConfig(adConfig);

}(Wikia.log, Wikia.Tracker, window, document, Geo, Krux, jQuery, Wikia.AbTest));
