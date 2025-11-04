/*
 * Copyright (c) Mondial-IT BV - Blue Marloc 2024
 *   Created on 2024-11-21 at 11:23:21
 */

/**
 * note bm_main is a dependency in most blue marloc
 * javascript. It provides the general available settings
 *
 * #### offers
 * * BM.log.color1, BM.log.color2, BM.log.textColor as indicator
 * * ajaxAutoDownload(selector) download div content as download.csv
 * * throbber(Onoff, message, force)
 * * throbberSuccess(successFail)
 * * triggerPulseAnimation(element)
 *
 * #### and offers the click next to a modal window to close it
 *
 * @type {{attach: Drupal.behaviors.bm_main.attach}}
 */
Drupal.behaviors.bm_cache = {
  attach: function (context, settings) {
    console.log('%c BMCache: %c bm_cache.js v1.0.0 is running Ok  ', BM.log.color1, BM.log.color2);
  }
}
