/*
 * noVNC: HTML5 VNC client
 * Copyright (C) 2012 Joel Martin
 * Copyright (C) 2016 Samuel Mannehed for Cendio AB
 * Copyright (C) 2016 Pierre Ossman for Cendio AB
 * Licensed under MPL 2.0 (see LICENSE.txt)
 *
 * See README.md for usage and integration instructions.
 */

/* jslint white: false, browser: true */
/* global window, document.getElementById, Util, WebUtil, RFB, Display */

/* [module]
 * import Util from "../core/util";
 * import KeyTable from "../core/input/keysym";
 * import keysyms from "./keysymdef";
 * import RFB from "../core/rfb";
 * import Display from "../core/display";
 * import WebUtil from "./webutil";
 */

var UI;

(function () {
    "use strict";

    // Fallback for all uncought errors
    window.addEventListener('error', function(event) {
        try {
            var msg = "";

            msg += "<div>";
            msg += event.message;
            msg += "</div>";

            msg += " <div class=\"noVNC_location\">";
            msg += event.filename;
            msg += ":" + event.lineno + ":" + event.colno;
            msg += "</div>";

            if ((event.error !== undefined) &&
                (event.error.stack !== undefined)) {
                msg += "<div class=\"noVNC_stack\">";
                msg += event.error.stack;
                msg += "</div>";
            }

            document.getElementById('noVNC_fallback_error')
                .classList.add("noVNC_open");
            document.getElementById('noVNC_fallback_errormsg').innerHTML = msg;
        } catch (exc) {
            document.write("noVNC encountered an error.");
        }
        // Don't return true since this would prevent the error
        // from being printed to the browser console.
        return false;
    });

    // Set up translations
    var LINGUAS = ["de", "el", "nl", "sv", "it"];
    Util.Localisation.setup(LINGUAS);
    if (Util.Localisation.language !== "en") {
        WebUtil.load_scripts(
            {'app': ["locale/" + Util.Localisation.language + ".js"]});
    }

    /* [begin skip-as-module] */
    // Load supporting scripts
    WebUtil.load_scripts(
        {'core': ["base64.js", "websock.js", "des.js", "input/keysymdef.js",
                  "input/xtscancodes.js", "input/util.js", "input/devices.js",
                  "display.js", "inflator.js", "rfb.js", "input/keysym.js"]});

    window.onscriptsload = function () { UI.load(); };
    /* [end skip-as-module] */

    var _ = Util.Localisation.get;

    UI = {

        connected: false,
        desktopName: "",

        resizeTimeout: null,
        statusTimeout: null,
        hideKeyboardTimeout: null,
        idleControlbarTimeout: null,
        closeControlbarTimeout: null,

        controlbarGrabbed: false,
        controlbarDrag: false,
        controlbarMouseDownClientY: 0,
        controlbarMouseDownOffsetY: 0,

        isSafari: false,
        rememberedClipSetting: null,
        lastKeyboardinput: null,
        defaultKeyboardinputLen: 100,

        // Setup rfb object, load settings from browser storage, then call
        // UI.init to setup the UI/menus
        load: function(callback) {
            WebUtil.initSettings(UI.start, callback);
        },

        // Render default UI and initialize settings menu
        start: function(callback) {

            // Setup global variables first
            UI.isSafari = (navigator.userAgent.indexOf('Safari') !== -1 &&
                           navigator.userAgent.indexOf('Chrome') === -1);


            // Translate the DOM
            Util.Localisation.translateDOM();

            // Adapt the interface for touch screen devices
            if (Util.isTouchDevice) {
                document.documentElement.classList.add("noVNC_touch");
                // Remove the address bar
                setTimeout(function() { window.scrollTo(0, 1); }, 100);
            }

            // Restore control bar position
            if (WebUtil.readSetting('controlbar_pos') === 'right') {
                UI.toggleControlbarSide();
            }

            // Setup and initialize event handlers
            UI.setupWindowEvents();

			//Autoconnects
            UI.connect();

            if (typeof callback === "function") {
                callback(UI.rfb);
            }
        },

        setupWindowEvents: function() {
            window.addEventListener('resize', UI.applyResizeMode);
            window.addEventListener('resize', UI.updateViewClip);
            window.addEventListener('resize', UI.updateViewDrag);

            document.getElementById("noVNC_status")
                .addEventListener('click', UI.hideStatus);
        },

        initRFB: function() {
            try {
                UI.rfb = new RFB({'target': document.getElementById('noVNC_canvas'),
                                  'onNotification': UI.notification,
                                  'onUpdateState': UI.updateState,
                                  'onDisconnected': UI.disconnectFinished,
                                  'onPasswordRequired': UI.passwordRequired,
                                  'onBell': UI.bell,
                                  'onFBUComplete': UI.initialResize,
                                  'onFBResize': UI.updateSessionSize,
                                  'onDesktopName': UI.updateDesktopName});
                return true;
            } catch (exc) {
                var msg = "Unable to create RFB client -- " + exc;
                Util.Error(msg);
                UI.showStatus(msg, 'error');
                return false;
            }
        },

/* ------^-------
 *     /INIT
 * ==============
 *     VISUAL
 * ------v------*/

        updateState: function(rfb, state, oldstate) {
            var msg;

            document.documentElement.classList.remove("noVNC_connecting");
            document.documentElement.classList.remove("noVNC_connected");
            document.documentElement.classList.remove("noVNC_disconnecting");

            switch (state) {
                case 'connecting':
                    document.getElementById("noVNC_transition_text").innerHTML = _("Connecting...");
                    document.documentElement.classList.add("noVNC_connecting");
                    break;
                case 'connected':
                    UI.connected = true;
                    document.documentElement.classList.add("noVNC_connected");
                    if (rfb && rfb.get_encrypt()) {
                        msg = _("Connected (encrypted) to ") + UI.desktopName;
                    } else {
                        msg = _("Connected (unencrypted) to ") + UI.desktopName;
                    }
                    UI.showStatus(msg);
                    break;
                case 'disconnecting':
                    UI.connected = false;
                    document.getElementById("noVNC_transition_text").innerHTML = _("Disconnecting...");
                    document.documentElement.classList.add("noVNC_disconnecting");
                    break;
                case 'disconnected':
                    UI.showStatus(_("Disconnected"));
					var backhomebox = document.createElement( 'h4');
					backhomebox.className = "errorbody ";
					backhomebox.className += "vncerrmsg";
					var backhometext = document.createTextNode ('Torna alla');

					var backhomelink = document.createElement( 'a');
					backhomelink.setAttribute('href', 'http://md-www.test.bncf.lan/index.php/opac/');
					var backhomelinktext = document.createTextNode (' ricerca');
					backhomelink.appendChild(backhomelinktext);

					backhomebox.appendChild(backhometext);
					backhomebox.appendChild(backhomelink);

					document.getElementById("noVNC_container").appendChild(backhomebox);
                    break;
                default:
                    msg = "Invalid UI state";
                    Util.Error(msg);
                    UI.showStatus(msg, 'error');
                    break;
            }
        },

        showStatus: function(text, status_type, time) {
            var statusElem = document.getElementById('noVNC_status');

            clearTimeout(UI.statusTimeout);

            if (typeof status_type === 'undefined') {
                status_type = 'normal';
            }

            statusElem.classList.remove("noVNC_status_normal",
                                        "noVNC_status_warn",
                                        "noVNC_status_error");

            switch (status_type) {
                case 'warning':
                case 'warn':
                    statusElem.classList.add("noVNC_status_warn");
                    break;
                case 'error':
                    statusElem.classList.add("noVNC_status_error");
                    break;
                case 'normal':
                case 'info':
                default:
                    statusElem.classList.add("noVNC_status_normal");
                    break;
            }

            statusElem.innerHTML = text;
            statusElem.classList.add("noVNC_open");

            // If no time was specified, show the status for 1.5 seconds
            if (typeof time === 'undefined') {
                time = 1500;
            }

            // Error messages do not timeout
            if (status_type !== 'error') {
                UI.statusTimeout = window.setTimeout(UI.hideStatus, time);
            }
        },

        hideStatus: function() {
            clearTimeout(UI.statusTimeout);
            document.getElementById('noVNC_status').classList.remove("noVNC_open");
        },

        notification: function (rfb, msg, level, options) {
            UI.showStatus(msg, level);
        },

/* ------^-------
 *    /VISUAL
 * ==============
 *  CONNECTION
 * ------v------*/
        connect: function() {
            var host = window.location.host; 
            var port = WebUtil.getConfigVar('port');
            var password = '';
            var token = '';
            var path = 'websockify';

            //if token is in path then ignore the new token variable
            if (token) {
                path = WebUtil.injectParamIfMissing(path, "token", token);
            }

            if ((!host) || (!port)) {
                var msg = _("Must set host and port");
                Util.Error(msg);
                UI.showStatus(msg, 'error');
                return;
            }

            if (!UI.initRFB()) return;


            UI.rfb.set_encrypt(false);
            UI.rfb.set_true_color(true);
            UI.rfb.set_local_cursor(true);
            UI.rfb.set_shared(false);
            UI.rfb.set_view_only(false);
            UI.rfb.set_repeaterID(false);

            UI.rfb.connect(host, port, password, path);
        },

        disconnect: function() {
            UI.rfb.disconnect();

            // Restore the callback used for initial resize
            UI.rfb.set_onFBUComplete(UI.initialResize);

            // Don't display the connection settings until we're actually disconnected
        },

        disconnectFinished: function (rfb, reason) {
            if (typeof reason !== 'undefined') {
                UI.showStatus(reason, 'error');
            }
        },

/* ------^-------
 *  /CONNECTION
 * ==============
 *     RESIZE
 * ------v------*/

        // Apply remote resizing or local scaling
        applyResizeMode: function() {
            if (!UI.rfb) return;

            var screen = UI.screenSize();

            if (screen && UI.connected && UI.rfb.get_display()) {

                var display = UI.rfb.get_display();
                var resizeMode = 'downscale';

                if (resizeMode === 'remote') {

                    // Request changing the resolution of the remote display to
                    // the size of the local browser viewport.

                    // In order to not send multiple requests before the browser-resize
                    // is finished we wait 0.5 seconds before sending the request.
                    clearTimeout(UI.resizeTimeout);
                    UI.resizeTimeout = setTimeout(function(){
                        // Request a remote size covering the viewport
                        if (UI.rfb.requestDesktopSize(screen.w, screen.h)) {
                            Util.Debug('Requested new desktop size: ' +
                                       screen.w + 'x' + screen.h);
                        }
                    }, 500);

                } else if (resizeMode === 'scale' || resizeMode === 'downscale') {
                    var downscaleOnly = resizeMode === 'downscale';
                    var scaleRatio = display.autoscale(screen.w, screen.h, downscaleOnly);

                    if (!UI.rfb.get_view_only()) {
                        UI.rfb.get_mouse().set_scale(scaleRatio);
                        Util.Debug('Scaling by ' + UI.rfb.get_mouse().get_scale());
                    }
                }
            }
        },

        // Gets the the size of the available viewport in the browser window
        screenSize: function() {
            var screen = document.getElementById('noVNC_screen');

            // Hide the scrollbars until the size is calculated
            screen.style.overflow = "hidden";

            var pos = Util.getPosition(screen);
            var w = pos.width;
            var h = pos.height;

            screen.style.overflow = "visible";

            if (isNaN(w) || isNaN(h)) {
                return false;
            } else {
                return {w: w, h: h};
            }
        },

        // Normally we only apply the current resize mode after a window resize
        // event. This means that when a new connection is opened, there is no
        // resize mode active.
        // We have to wait until the first FBU because this is where the client
        // will find the supported encodings of the server. Some calls later in
        // the chain is dependant on knowing the server-capabilities.
        initialResize: function(rfb, fbu) {
            UI.applyResizeMode();
            // After doing this once, we remove the callback.
            UI.rfb.set_onFBUComplete(function() { });
        },

/* ------^-------
 *    /RESIZE
 * ==============
 *    VIEWDRAG
 * ------v------*/

        toggleViewDrag: function() {
            if (!UI.rfb) return;

            var drag = UI.rfb.get_viewportDrag();
            UI.setViewDrag(!drag);
         },

        // Set the view drag mode which moves the viewport on mouse drags
        setViewDrag: function(drag) {
            if (!UI.rfb) return;

            UI.rfb.set_viewportDrag(drag);

            UI.updateViewDrag();
        },

        updateViewDrag: function() {
            var clipping = false;

            if (!UI.connected) return;

            // Check if viewport drag is possible. It is only possible
            // if the remote display is clipping the client display.
            if (UI.rfb.get_display().get_viewport() &&
                UI.rfb.get_display().clippingDisplay()) {
                clipping = true;
            }

            var viewDragButton = document.getElementById('noVNC_view_drag_button');

            if (!clipping &&
                UI.rfb.get_viewportDrag()) {
                // The size of the remote display is the same or smaller
                // than the client display. Make sure viewport drag isn't
                // active when it can't be used.
                UI.rfb.set_viewportDrag(false);
            }
        },

/* ------^-------
 *   /VIEWDRAG
 * ==============
 *     MISC
 * ------v------*/

        setMouseButton: function(num) {
            var view_only = UI.rfb.get_view_only();
            if (UI.rfb && !view_only) {
                UI.rfb.get_mouse().set_touchButton(num);
            }

            var blist = [0, 1,2,4];
            for (var b = 0; b < blist.length; b++) {
                var button = document.getElementById('noVNC_mouse_button' +
                                                     blist[b]);
                if (blist[b] === num && !view_only) {
                    button.classList.remove("noVNC_hidden");
                } else {
                    button.classList.add("noVNC_hidden");
                }
            }
        },

        displayBlur: function() {
            if (UI.rfb && !UI.rfb.get_view_only()) {
                UI.rfb.get_keyboard().set_focused(false);
                UI.rfb.get_mouse().set_focused(false);
            }
        },

        displayFocus: function() {
            if (UI.rfb && !UI.rfb.get_view_only()) {
                UI.rfb.get_keyboard().set_focused(true);
                UI.rfb.get_mouse().set_focused(true);
            }
        },

        updateSessionSize: function(rfb, width, height) {
            UI.updateViewDrag();
        },

        updateDesktopName: function(rfb, name) {
            UI.desktopName = name;
            // Display the desktop name in the document title
            document.title = name + " - noVNC";
        },

        bell: function(rfb) {
            if (WebUtil.getConfigVar('bell', 'on') === 'on') {
                document.getElementById('noVNC_bell').play();
            }
        },

        //Helper to add options to dropdown.
        addOption: function(selectbox, text, value) {
            var optn = document.createElement("OPTION");
            optn.text = text;
            optn.value = value;
            selectbox.options.add(optn);
        },

/* ------^-------
 *    /MISC
 * ==============
 */
    };

    /* [module] UI.load(); */
})();

/* [module] export default UI; */
