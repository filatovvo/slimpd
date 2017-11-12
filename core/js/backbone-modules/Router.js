/* Copyright (C) 2016 othmar52 <othmar52@users.noreply.github.com>
 *                    stt <stt@mmc-agentur.at>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/*
 * dependencies: jquery, backbonejs, window.sliMpd.modules.PageView
 */
(function() {
    "use strict";

    var $ = window.jQuery;
    window.sliMpd = $.extend(true, window.sliMpd, {
        modules : {}
    });
    window.sliMpd.modules.Router = window.Backbone.Router.extend({

        rendered : false,
        ajaxLoading : false,

        currentView : null,
        previousView : null,
        $content : null,
        $body : null,

        routes : {
            "" : "generic",
            [window.sliMpd.conf.backboneRoot + "maintainance/albumdebug/:albumUid"] : "editAlbum",
            [window.sliMpd.conf.backboneRoot + "auth/:subroute"] : "auth",
            [window.sliMpd.conf.backboneRoot + "stemplayer/*subroute"] : "stem",
            "*generic" : "generic",
        },

        initialize : function(options) {
            this.$content = $("#main");
            this.$body = $("body");
        },

        navigate : function(fragment, options) {
            if (this.ajaxLoading) {
                return;
            }

            /*
             * work against backbone"s default behaviour - begin
             *
             * force rendering of view - even if we request the same (current) route again
             * without pushing this to history
             */
            var pathStripper = /#.*$/;
            var Backbone = window.Backbone;

            // Normalize the fragment.
            fragment = Backbone.history.getFragment(fragment || "");

            // Don"t include a trailing slash on the root.
            var rootPath = Backbone.history.root;
            if (fragment === "" || fragment.charAt(0) === "?") {
                rootPath = rootPath.slice(0, -1) || "/";
            }
            var url = rootPath + fragment;

            // Strip the hash and decode for matching.
            fragment = Backbone.history.decodeFragment(fragment.replace(pathStripper, ""));
            if (Backbone.history.fragment === fragment) {
                Backbone.history.loadUrl(fragment);
                return;
            }
            /* work against backbone"s default behaviour - end */

            window.Backbone.Router.prototype.navigate.call(this, fragment, options);
        },

        editAlbum : function(albumUid, queryString) {
            this.generic(window.sliMpd.conf.backboneRoot + "maintainance/albumdebug/" + albumUid, queryString, window.sliMpd.modules.EditAlbumView);
        },

        auth : function(subroute, queryString) {
            this.generic(window.sliMpd.conf.backboneRoot + "auth/" + subroute, queryString, window.sliMpd.modules.AuthView);
        },

        stem : function(subroute, queryString) {
            //console.log("queryString", queryString);
            //console.log("subroute", subroute);
            this.generic(window.sliMpd.conf.backboneRoot + "stemplayer/" + subroute, queryString, window.sliMpd.modules.StemView);
        },

        generic : function(route, queryString, ViewClass) {
            var name = ((route === null) ? "home" : route + "?" + queryString),
                url = "/" + ((route === null) ? "" : route + "?" + queryString),
                View = ViewClass || window.sliMpd.modules.PageView;

            // remove view on ajax-done
            this.previousView = (this.currentView) ? this.currentView : null;

            // first time page loaded markup is delivered by backend, no need for ajax call!
            if (!this.rendered) {
                this.currentView = new View({
                    name: name,
                    templateString : "",
                    el : "#main>.main-content"
                });
                this.currentView.render(false); // renderMarkup flag false, to prevent markup re-rendering
                this.rendered = true;
                return;
            }

            // TODO: add proper loading animation
            this.$body.addClass("is-loading");
            window.NProgress.start();
            this.ajaxLoading = true;
            $.ajax({
                url: window.sliMpd.router.setGetParameter(url, "nosurrounding", "1")
            }).done(function(response) {
                if(this.previousView) {
                    this.previousView.remove();
                }
                this.currentView = new View({
                    name: name,
                    templateString : response
                });
                this.currentView.render(true);
                this.$content.html(this.currentView.$el);
                this.$body.removeClass("is-loading");
                window.NProgress.done();

                this.ajaxLoading = false;
            }.bind(this))
            .fail(function(jqXHR) {
                this.$body.removeClass("is-loading");
                window.NProgress.done();
                this.ajaxLoading = false;
                if(jqXHR.status === 401) {
                    window.sliMpd.handleUnauthorized(jqXHR.responseText);
                    return;
                }
                window.sliMpd.notifyError(url);
                return;
            }.bind(this));
        },

        // FIXME: how to refresh #main view without pushing anything to history?
        refreshIfName : function(routename) {
            // TODO: check if we can use a generic function
            // for now lets define each usecase separately
            switch(routename) {
                case "playlist":
                    var urlRegex = new RegExp("^" + window.sliMpd.conf.absRefPrefix.replace("/", "\\/") + "playlist\\/");
                    // TODO: which router variable to use for comparison?
                    // sliMpd.router.$body.context.location.pathname
                    // sliMpd.router.currentView.name
                    if(urlRegex.test("/"+window.sliMpd.router.currentView.name) === true) {
                        var targetUrl = window.sliMpd.router.currentView.name;
                        window.sliMpd.router.navigate(targetUrl, {
                            trigger : true
                        });
                    }
                    break;
                default:
                    break;
            }
            return;
        },

        // replacing backbones _extractParameters() method
        //    original method: "%2B" gets replaced with "+"
        //    overidden method: "%2B" gets preserved
        // TODO: combinations of "%" and "#" and "\" in urls still does not work
        // @see: issue #3
        _extractParameters : function(route, fragment) {
            var params = route.exec(fragment).slice(1);
            return window._.map(params, function(param, i) {
                // Don"t decode the search params.
                if (i === params.length - 1) {
                    return param;
                }

                return param
                    ? decodeURIComponent(param)
                        .replace(/%/g, "%25")
                        .replace(/#/g, "%23")
                        .replace(/\+/g, "%2B")
                        .replace(/\?/g, "%3F")
                    : null;
            });
        },
        /**
         * adds get-paramter to url, respecting existing and not-existing params
         * TODO: currently not compatible with urlstring that contains a #hash
         * @param {string} urlstring
         * @param {string} paramName
         * @param {string} paramValue
         */
        setGetParameter : function(urlstring, paramName, paramValue) {
            if (urlstring.indexOf(paramName + "=") >= 0) {
                var prefix = urlstring.substring(0, urlstring.indexOf(paramName));
                var suffix = urlstring.substring(urlstring.indexOf(paramName));
                suffix = suffix.substring(suffix.indexOf("=") + 1);
                suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
                urlstring = prefix + paramName + "=" + paramValue + suffix;
                return urlstring;
            }
            urlstring += (urlstring.indexOf("?") < 0)
                ? "?" + paramName + "=" + paramValue
                : "&" + paramName + "=" + paramValue;
            return urlstring;
        }
    });

}());
