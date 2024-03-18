(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["map-chart"] = factory();
	else
		root["map-chart"] = factory();
})((typeof self !== 'undefined' ? self : this), function() {
return /******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 792:
/***/ (function(module) {

/**
 * chroma.js - JavaScript library for color conversions
 *
 * Copyright (c) 2011-2019, Gregor Aisch
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. The name Gregor Aisch may not be used to endorse or promote products
 * derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL GREGOR AISCH OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * -------------------------------------------------------
 *
 * chroma.js includes colors from colorbrewer2.org, which are released under
 * the following license:
 *
 * Copyright (c) 2002 Cynthia Brewer, Mark Harrower,
 * and The Pennsylvania State University.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific
 * language governing permissions and limitations under the License.
 *
 * ------------------------------------------------------
 *
 * Named colors are taken from X11 Color Names.
 * http://www.w3.org/TR/css3-color/#svg-color
 *
 * @preserve
 */

(function (global, factory) {
     true ? module.exports = factory() :
    0;
}(this, (function () { 'use strict';

    var limit = function (x, min, max) {
        if ( min === void 0 ) min=0;
        if ( max === void 0 ) max=1;

        return x < min ? min : x > max ? max : x;
    };

    var clip_rgb = function (rgb) {
        rgb._clipped = false;
        rgb._unclipped = rgb.slice(0);
        for (var i=0; i<=3; i++) {
            if (i < 3) {
                if (rgb[i] < 0 || rgb[i] > 255) { rgb._clipped = true; }
                rgb[i] = limit(rgb[i], 0, 255);
            } else if (i === 3) {
                rgb[i] = limit(rgb[i], 0, 1);
            }
        }
        return rgb;
    };

    // ported from jQuery's $.type
    var classToType = {};
    for (var i = 0, list = ['Boolean', 'Number', 'String', 'Function', 'Array', 'Date', 'RegExp', 'Undefined', 'Null']; i < list.length; i += 1) {
        var name = list[i];

        classToType[("[object " + name + "]")] = name.toLowerCase();
    }
    var type = function(obj) {
        return classToType[Object.prototype.toString.call(obj)] || "object";
    };

    var unpack = function (args, keyOrder) {
        if ( keyOrder === void 0 ) keyOrder=null;

    	// if called with more than 3 arguments, we return the arguments
        if (args.length >= 3) { return Array.prototype.slice.call(args); }
        // with less than 3 args we check if first arg is object
        // and use the keyOrder string to extract and sort properties
    	if (type(args[0]) == 'object' && keyOrder) {
    		return keyOrder.split('')
    			.filter(function (k) { return args[0][k] !== undefined; })
    			.map(function (k) { return args[0][k]; });
    	}
    	// otherwise we just return the first argument
    	// (which we suppose is an array of args)
        return args[0];
    };

    var last = function (args) {
        if (args.length < 2) { return null; }
        var l = args.length-1;
        if (type(args[l]) == 'string') { return args[l].toLowerCase(); }
        return null;
    };

    var PI = Math.PI;

    var utils = {
    	clip_rgb: clip_rgb,
    	limit: limit,
    	type: type,
    	unpack: unpack,
    	last: last,
    	PI: PI,
    	TWOPI: PI*2,
    	PITHIRD: PI/3,
    	DEG2RAD: PI / 180,
    	RAD2DEG: 180 / PI
    };

    var input = {
    	format: {},
    	autodetect: []
    };

    var last$1 = utils.last;
    var clip_rgb$1 = utils.clip_rgb;
    var type$1 = utils.type;


    var Color = function Color() {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var me = this;
        if (type$1(args[0]) === 'object' &&
            args[0].constructor &&
            args[0].constructor === this.constructor) {
            // the argument is already a Color instance
            return args[0];
        }

        // last argument could be the mode
        var mode = last$1(args);
        var autodetect = false;

        if (!mode) {
            autodetect = true;
            if (!input.sorted) {
                input.autodetect = input.autodetect.sort(function (a,b) { return b.p - a.p; });
                input.sorted = true;
            }
            // auto-detect format
            for (var i = 0, list = input.autodetect; i < list.length; i += 1) {
                var chk = list[i];

                mode = chk.test.apply(chk, args);
                if (mode) { break; }
            }
        }

        if (input.format[mode]) {
            var rgb = input.format[mode].apply(null, autodetect ? args : args.slice(0,-1));
            me._rgb = clip_rgb$1(rgb);
        } else {
            throw new Error('unknown format: '+args);
        }

        // add alpha channel
        if (me._rgb.length === 3) { me._rgb.push(1); }
    };

    Color.prototype.toString = function toString () {
        if (type$1(this.hex) == 'function') { return this.hex(); }
        return ("[" + (this._rgb.join(',')) + "]");
    };

    var Color_1 = Color;

    var chroma = function () {
    	var args = [], len = arguments.length;
    	while ( len-- ) args[ len ] = arguments[ len ];

    	return new (Function.prototype.bind.apply( chroma.Color, [ null ].concat( args) ));
    };

    chroma.Color = Color_1;
    chroma.version = '2.1.2';

    var chroma_1 = chroma;

    var unpack$1 = utils.unpack;
    var max = Math.max;

    var rgb2cmyk = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$1(args, 'rgb');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        r = r / 255;
        g = g / 255;
        b = b / 255;
        var k = 1 - max(r,max(g,b));
        var f = k < 1 ? 1 / (1-k) : 0;
        var c = (1-r-k) * f;
        var m = (1-g-k) * f;
        var y = (1-b-k) * f;
        return [c,m,y,k];
    };

    var rgb2cmyk_1 = rgb2cmyk;

    var unpack$2 = utils.unpack;

    var cmyk2rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        args = unpack$2(args, 'cmyk');
        var c = args[0];
        var m = args[1];
        var y = args[2];
        var k = args[3];
        var alpha = args.length > 4 ? args[4] : 1;
        if (k === 1) { return [0,0,0,alpha]; }
        return [
            c >= 1 ? 0 : 255 * (1-c) * (1-k), // r
            m >= 1 ? 0 : 255 * (1-m) * (1-k), // g
            y >= 1 ? 0 : 255 * (1-y) * (1-k), // b
            alpha
        ];
    };

    var cmyk2rgb_1 = cmyk2rgb;

    var unpack$3 = utils.unpack;
    var type$2 = utils.type;



    Color_1.prototype.cmyk = function() {
        return rgb2cmyk_1(this._rgb);
    };

    chroma_1.cmyk = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['cmyk']) ));
    };

    input.format.cmyk = cmyk2rgb_1;

    input.autodetect.push({
        p: 2,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$3(args, 'cmyk');
            if (type$2(args) === 'array' && args.length === 4) {
                return 'cmyk';
            }
        }
    });

    var unpack$4 = utils.unpack;
    var last$2 = utils.last;
    var rnd = function (a) { return Math.round(a*100)/100; };

    /*
     * supported arguments:
     * - hsl2css(h,s,l)
     * - hsl2css(h,s,l,a)
     * - hsl2css([h,s,l], mode)
     * - hsl2css([h,s,l,a], mode)
     * - hsl2css({h,s,l,a}, mode)
     */
    var hsl2css = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var hsla = unpack$4(args, 'hsla');
        var mode = last$2(args) || 'lsa';
        hsla[0] = rnd(hsla[0] || 0);
        hsla[1] = rnd(hsla[1]*100) + '%';
        hsla[2] = rnd(hsla[2]*100) + '%';
        if (mode === 'hsla' || (hsla.length > 3 && hsla[3]<1)) {
            hsla[3] = hsla.length > 3 ? hsla[3] : 1;
            mode = 'hsla';
        } else {
            hsla.length = 3;
        }
        return (mode + "(" + (hsla.join(',')) + ")");
    };

    var hsl2css_1 = hsl2css;

    var unpack$5 = utils.unpack;

    /*
     * supported arguments:
     * - rgb2hsl(r,g,b)
     * - rgb2hsl(r,g,b,a)
     * - rgb2hsl([r,g,b])
     * - rgb2hsl([r,g,b,a])
     * - rgb2hsl({r,g,b,a})
     */
    var rgb2hsl = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        args = unpack$5(args, 'rgba');
        var r = args[0];
        var g = args[1];
        var b = args[2];

        r /= 255;
        g /= 255;
        b /= 255;

        var min = Math.min(r, g, b);
        var max = Math.max(r, g, b);

        var l = (max + min) / 2;
        var s, h;

        if (max === min){
            s = 0;
            h = Number.NaN;
        } else {
            s = l < 0.5 ? (max - min) / (max + min) : (max - min) / (2 - max - min);
        }

        if (r == max) { h = (g - b) / (max - min); }
        else if (g == max) { h = 2 + (b - r) / (max - min); }
        else if (b == max) { h = 4 + (r - g) / (max - min); }

        h *= 60;
        if (h < 0) { h += 360; }
        if (args.length>3 && args[3]!==undefined) { return [h,s,l,args[3]]; }
        return [h,s,l];
    };

    var rgb2hsl_1 = rgb2hsl;

    var unpack$6 = utils.unpack;
    var last$3 = utils.last;


    var round = Math.round;

    /*
     * supported arguments:
     * - rgb2css(r,g,b)
     * - rgb2css(r,g,b,a)
     * - rgb2css([r,g,b], mode)
     * - rgb2css([r,g,b,a], mode)
     * - rgb2css({r,g,b,a}, mode)
     */
    var rgb2css = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var rgba = unpack$6(args, 'rgba');
        var mode = last$3(args) || 'rgb';
        if (mode.substr(0,3) == 'hsl') {
            return hsl2css_1(rgb2hsl_1(rgba), mode);
        }
        rgba[0] = round(rgba[0]);
        rgba[1] = round(rgba[1]);
        rgba[2] = round(rgba[2]);
        if (mode === 'rgba' || (rgba.length > 3 && rgba[3]<1)) {
            rgba[3] = rgba.length > 3 ? rgba[3] : 1;
            mode = 'rgba';
        }
        return (mode + "(" + (rgba.slice(0,mode==='rgb'?3:4).join(',')) + ")");
    };

    var rgb2css_1 = rgb2css;

    var unpack$7 = utils.unpack;
    var round$1 = Math.round;

    var hsl2rgb = function () {
        var assign;

        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];
        args = unpack$7(args, 'hsl');
        var h = args[0];
        var s = args[1];
        var l = args[2];
        var r,g,b;
        if (s === 0) {
            r = g = b = l*255;
        } else {
            var t3 = [0,0,0];
            var c = [0,0,0];
            var t2 = l < 0.5 ? l * (1+s) : l+s-l*s;
            var t1 = 2 * l - t2;
            var h_ = h / 360;
            t3[0] = h_ + 1/3;
            t3[1] = h_;
            t3[2] = h_ - 1/3;
            for (var i=0; i<3; i++) {
                if (t3[i] < 0) { t3[i] += 1; }
                if (t3[i] > 1) { t3[i] -= 1; }
                if (6 * t3[i] < 1)
                    { c[i] = t1 + (t2 - t1) * 6 * t3[i]; }
                else if (2 * t3[i] < 1)
                    { c[i] = t2; }
                else if (3 * t3[i] < 2)
                    { c[i] = t1 + (t2 - t1) * ((2 / 3) - t3[i]) * 6; }
                else
                    { c[i] = t1; }
            }
            (assign = [round$1(c[0]*255),round$1(c[1]*255),round$1(c[2]*255)], r = assign[0], g = assign[1], b = assign[2]);
        }
        if (args.length > 3) {
            // keep alpha channel
            return [r,g,b,args[3]];
        }
        return [r,g,b,1];
    };

    var hsl2rgb_1 = hsl2rgb;

    var RE_RGB = /^rgb\(\s*(-?\d+),\s*(-?\d+)\s*,\s*(-?\d+)\s*\)$/;
    var RE_RGBA = /^rgba\(\s*(-?\d+),\s*(-?\d+)\s*,\s*(-?\d+)\s*,\s*([01]|[01]?\.\d+)\)$/;
    var RE_RGB_PCT = /^rgb\(\s*(-?\d+(?:\.\d+)?)%,\s*(-?\d+(?:\.\d+)?)%\s*,\s*(-?\d+(?:\.\d+)?)%\s*\)$/;
    var RE_RGBA_PCT = /^rgba\(\s*(-?\d+(?:\.\d+)?)%,\s*(-?\d+(?:\.\d+)?)%\s*,\s*(-?\d+(?:\.\d+)?)%\s*,\s*([01]|[01]?\.\d+)\)$/;
    var RE_HSL = /^hsl\(\s*(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)%\s*,\s*(-?\d+(?:\.\d+)?)%\s*\)$/;
    var RE_HSLA = /^hsla\(\s*(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)%\s*,\s*(-?\d+(?:\.\d+)?)%\s*,\s*([01]|[01]?\.\d+)\)$/;

    var round$2 = Math.round;

    var css2rgb = function (css) {
        css = css.toLowerCase().trim();
        var m;

        if (input.format.named) {
            try {
                return input.format.named(css);
            } catch (e) {
                // eslint-disable-next-line
            }
        }

        // rgb(250,20,0)
        if ((m = css.match(RE_RGB))) {
            var rgb = m.slice(1,4);
            for (var i=0; i<3; i++) {
                rgb[i] = +rgb[i];
            }
            rgb[3] = 1;  // default alpha
            return rgb;
        }

        // rgba(250,20,0,0.4)
        if ((m = css.match(RE_RGBA))) {
            var rgb$1 = m.slice(1,5);
            for (var i$1=0; i$1<4; i$1++) {
                rgb$1[i$1] = +rgb$1[i$1];
            }
            return rgb$1;
        }

        // rgb(100%,0%,0%)
        if ((m = css.match(RE_RGB_PCT))) {
            var rgb$2 = m.slice(1,4);
            for (var i$2=0; i$2<3; i$2++) {
                rgb$2[i$2] = round$2(rgb$2[i$2] * 2.55);
            }
            rgb$2[3] = 1;  // default alpha
            return rgb$2;
        }

        // rgba(100%,0%,0%,0.4)
        if ((m = css.match(RE_RGBA_PCT))) {
            var rgb$3 = m.slice(1,5);
            for (var i$3=0; i$3<3; i$3++) {
                rgb$3[i$3] = round$2(rgb$3[i$3] * 2.55);
            }
            rgb$3[3] = +rgb$3[3];
            return rgb$3;
        }

        // hsl(0,100%,50%)
        if ((m = css.match(RE_HSL))) {
            var hsl = m.slice(1,4);
            hsl[1] *= 0.01;
            hsl[2] *= 0.01;
            var rgb$4 = hsl2rgb_1(hsl);
            rgb$4[3] = 1;
            return rgb$4;
        }

        // hsla(0,100%,50%,0.5)
        if ((m = css.match(RE_HSLA))) {
            var hsl$1 = m.slice(1,4);
            hsl$1[1] *= 0.01;
            hsl$1[2] *= 0.01;
            var rgb$5 = hsl2rgb_1(hsl$1);
            rgb$5[3] = +m[4];  // default alpha = 1
            return rgb$5;
        }
    };

    css2rgb.test = function (s) {
        return RE_RGB.test(s) ||
            RE_RGBA.test(s) ||
            RE_RGB_PCT.test(s) ||
            RE_RGBA_PCT.test(s) ||
            RE_HSL.test(s) ||
            RE_HSLA.test(s);
    };

    var css2rgb_1 = css2rgb;

    var type$3 = utils.type;




    Color_1.prototype.css = function(mode) {
        return rgb2css_1(this._rgb, mode);
    };

    chroma_1.css = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['css']) ));
    };

    input.format.css = css2rgb_1;

    input.autodetect.push({
        p: 5,
        test: function (h) {
            var rest = [], len = arguments.length - 1;
            while ( len-- > 0 ) rest[ len ] = arguments[ len + 1 ];

            if (!rest.length && type$3(h) === 'string' && css2rgb_1.test(h)) {
                return 'css';
            }
        }
    });

    var unpack$8 = utils.unpack;

    input.format.gl = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var rgb = unpack$8(args, 'rgba');
        rgb[0] *= 255;
        rgb[1] *= 255;
        rgb[2] *= 255;
        return rgb;
    };

    chroma_1.gl = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['gl']) ));
    };

    Color_1.prototype.gl = function() {
        var rgb = this._rgb;
        return [rgb[0]/255, rgb[1]/255, rgb[2]/255, rgb[3]];
    };

    var unpack$9 = utils.unpack;

    var rgb2hcg = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$9(args, 'rgb');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        var min = Math.min(r, g, b);
        var max = Math.max(r, g, b);
        var delta = max - min;
        var c = delta * 100 / 255;
        var _g = min / (255 - delta) * 100;
        var h;
        if (delta === 0) {
            h = Number.NaN;
        } else {
            if (r === max) { h = (g - b) / delta; }
            if (g === max) { h = 2+(b - r) / delta; }
            if (b === max) { h = 4+(r - g) / delta; }
            h *= 60;
            if (h < 0) { h += 360; }
        }
        return [h, c, _g];
    };

    var rgb2hcg_1 = rgb2hcg;

    var unpack$a = utils.unpack;
    var floor = Math.floor;

    /*
     * this is basically just HSV with some minor tweaks
     *
     * hue.. [0..360]
     * chroma .. [0..1]
     * grayness .. [0..1]
     */

    var hcg2rgb = function () {
        var assign, assign$1, assign$2, assign$3, assign$4, assign$5;

        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];
        args = unpack$a(args, 'hcg');
        var h = args[0];
        var c = args[1];
        var _g = args[2];
        var r,g,b;
        _g = _g * 255;
        var _c = c * 255;
        if (c === 0) {
            r = g = b = _g;
        } else {
            if (h === 360) { h = 0; }
            if (h > 360) { h -= 360; }
            if (h < 0) { h += 360; }
            h /= 60;
            var i = floor(h);
            var f = h - i;
            var p = _g * (1 - c);
            var q = p + _c * (1 - f);
            var t = p + _c * f;
            var v = p + _c;
            switch (i) {
                case 0: (assign = [v, t, p], r = assign[0], g = assign[1], b = assign[2]); break
                case 1: (assign$1 = [q, v, p], r = assign$1[0], g = assign$1[1], b = assign$1[2]); break
                case 2: (assign$2 = [p, v, t], r = assign$2[0], g = assign$2[1], b = assign$2[2]); break
                case 3: (assign$3 = [p, q, v], r = assign$3[0], g = assign$3[1], b = assign$3[2]); break
                case 4: (assign$4 = [t, p, v], r = assign$4[0], g = assign$4[1], b = assign$4[2]); break
                case 5: (assign$5 = [v, p, q], r = assign$5[0], g = assign$5[1], b = assign$5[2]); break
            }
        }
        return [r, g, b, args.length > 3 ? args[3] : 1];
    };

    var hcg2rgb_1 = hcg2rgb;

    var unpack$b = utils.unpack;
    var type$4 = utils.type;






    Color_1.prototype.hcg = function() {
        return rgb2hcg_1(this._rgb);
    };

    chroma_1.hcg = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['hcg']) ));
    };

    input.format.hcg = hcg2rgb_1;

    input.autodetect.push({
        p: 1,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$b(args, 'hcg');
            if (type$4(args) === 'array' && args.length === 3) {
                return 'hcg';
            }
        }
    });

    var unpack$c = utils.unpack;
    var last$4 = utils.last;
    var round$3 = Math.round;

    var rgb2hex = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$c(args, 'rgba');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        var a = ref[3];
        var mode = last$4(args) || 'auto';
        if (a === undefined) { a = 1; }
        if (mode === 'auto') {
            mode = a < 1 ? 'rgba' : 'rgb';
        }
        r = round$3(r);
        g = round$3(g);
        b = round$3(b);
        var u = r << 16 | g << 8 | b;
        var str = "000000" + u.toString(16); //#.toUpperCase();
        str = str.substr(str.length - 6);
        var hxa = '0' + round$3(a * 255).toString(16);
        hxa = hxa.substr(hxa.length - 2);
        switch (mode.toLowerCase()) {
            case 'rgba': return ("#" + str + hxa);
            case 'argb': return ("#" + hxa + str);
            default: return ("#" + str);
        }
    };

    var rgb2hex_1 = rgb2hex;

    var RE_HEX = /^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
    var RE_HEXA = /^#?([A-Fa-f0-9]{8}|[A-Fa-f0-9]{4})$/;

    var hex2rgb = function (hex) {
        if (hex.match(RE_HEX)) {
            // remove optional leading #
            if (hex.length === 4 || hex.length === 7) {
                hex = hex.substr(1);
            }
            // expand short-notation to full six-digit
            if (hex.length === 3) {
                hex = hex.split('');
                hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            }
            var u = parseInt(hex, 16);
            var r = u >> 16;
            var g = u >> 8 & 0xFF;
            var b = u & 0xFF;
            return [r,g,b,1];
        }

        // match rgba hex format, eg #FF000077
        if (hex.match(RE_HEXA)) {
            if (hex.length === 5 || hex.length === 9) {
                // remove optional leading #
                hex = hex.substr(1);
            }
            // expand short-notation to full eight-digit
            if (hex.length === 4) {
                hex = hex.split('');
                hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2]+hex[3]+hex[3];
            }
            var u$1 = parseInt(hex, 16);
            var r$1 = u$1 >> 24 & 0xFF;
            var g$1 = u$1 >> 16 & 0xFF;
            var b$1 = u$1 >> 8 & 0xFF;
            var a = Math.round((u$1 & 0xFF) / 0xFF * 100) / 100;
            return [r$1,g$1,b$1,a];
        }

        // we used to check for css colors here
        // if _input.css? and rgb = _input.css hex
        //     return rgb

        throw new Error(("unknown hex color: " + hex));
    };

    var hex2rgb_1 = hex2rgb;

    var type$5 = utils.type;




    Color_1.prototype.hex = function(mode) {
        return rgb2hex_1(this._rgb, mode);
    };

    chroma_1.hex = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['hex']) ));
    };

    input.format.hex = hex2rgb_1;
    input.autodetect.push({
        p: 4,
        test: function (h) {
            var rest = [], len = arguments.length - 1;
            while ( len-- > 0 ) rest[ len ] = arguments[ len + 1 ];

            if (!rest.length && type$5(h) === 'string' && [3,4,5,6,7,8,9].indexOf(h.length) >= 0) {
                return 'hex';
            }
        }
    });

    var unpack$d = utils.unpack;
    var TWOPI = utils.TWOPI;
    var min = Math.min;
    var sqrt = Math.sqrt;
    var acos = Math.acos;

    var rgb2hsi = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        /*
        borrowed from here:
        http://hummer.stanford.edu/museinfo/doc/examples/humdrum/keyscape2/rgb2hsi.cpp
        */
        var ref = unpack$d(args, 'rgb');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        r /= 255;
        g /= 255;
        b /= 255;
        var h;
        var min_ = min(r,g,b);
        var i = (r+g+b) / 3;
        var s = i > 0 ? 1 - min_/i : 0;
        if (s === 0) {
            h = NaN;
        } else {
            h = ((r-g)+(r-b)) / 2;
            h /= sqrt((r-g)*(r-g) + (r-b)*(g-b));
            h = acos(h);
            if (b > g) {
                h = TWOPI - h;
            }
            h /= TWOPI;
        }
        return [h*360,s,i];
    };

    var rgb2hsi_1 = rgb2hsi;

    var unpack$e = utils.unpack;
    var limit$1 = utils.limit;
    var TWOPI$1 = utils.TWOPI;
    var PITHIRD = utils.PITHIRD;
    var cos = Math.cos;

    /*
     * hue [0..360]
     * saturation [0..1]
     * intensity [0..1]
     */
    var hsi2rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        /*
        borrowed from here:
        http://hummer.stanford.edu/museinfo/doc/examples/humdrum/keyscape2/hsi2rgb.cpp
        */
        args = unpack$e(args, 'hsi');
        var h = args[0];
        var s = args[1];
        var i = args[2];
        var r,g,b;

        if (isNaN(h)) { h = 0; }
        if (isNaN(s)) { s = 0; }
        // normalize hue
        if (h > 360) { h -= 360; }
        if (h < 0) { h += 360; }
        h /= 360;
        if (h < 1/3) {
            b = (1-s)/3;
            r = (1+s*cos(TWOPI$1*h)/cos(PITHIRD-TWOPI$1*h))/3;
            g = 1 - (b+r);
        } else if (h < 2/3) {
            h -= 1/3;
            r = (1-s)/3;
            g = (1+s*cos(TWOPI$1*h)/cos(PITHIRD-TWOPI$1*h))/3;
            b = 1 - (r+g);
        } else {
            h -= 2/3;
            g = (1-s)/3;
            b = (1+s*cos(TWOPI$1*h)/cos(PITHIRD-TWOPI$1*h))/3;
            r = 1 - (g+b);
        }
        r = limit$1(i*r*3);
        g = limit$1(i*g*3);
        b = limit$1(i*b*3);
        return [r*255, g*255, b*255, args.length > 3 ? args[3] : 1];
    };

    var hsi2rgb_1 = hsi2rgb;

    var unpack$f = utils.unpack;
    var type$6 = utils.type;






    Color_1.prototype.hsi = function() {
        return rgb2hsi_1(this._rgb);
    };

    chroma_1.hsi = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['hsi']) ));
    };

    input.format.hsi = hsi2rgb_1;

    input.autodetect.push({
        p: 2,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$f(args, 'hsi');
            if (type$6(args) === 'array' && args.length === 3) {
                return 'hsi';
            }
        }
    });

    var unpack$g = utils.unpack;
    var type$7 = utils.type;






    Color_1.prototype.hsl = function() {
        return rgb2hsl_1(this._rgb);
    };

    chroma_1.hsl = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['hsl']) ));
    };

    input.format.hsl = hsl2rgb_1;

    input.autodetect.push({
        p: 2,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$g(args, 'hsl');
            if (type$7(args) === 'array' && args.length === 3) {
                return 'hsl';
            }
        }
    });

    var unpack$h = utils.unpack;
    var min$1 = Math.min;
    var max$1 = Math.max;

    /*
     * supported arguments:
     * - rgb2hsv(r,g,b)
     * - rgb2hsv([r,g,b])
     * - rgb2hsv({r,g,b})
     */
    var rgb2hsl$1 = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        args = unpack$h(args, 'rgb');
        var r = args[0];
        var g = args[1];
        var b = args[2];
        var min_ = min$1(r, g, b);
        var max_ = max$1(r, g, b);
        var delta = max_ - min_;
        var h,s,v;
        v = max_ / 255.0;
        if (max_ === 0) {
            h = Number.NaN;
            s = 0;
        } else {
            s = delta / max_;
            if (r === max_) { h = (g - b) / delta; }
            if (g === max_) { h = 2+(b - r) / delta; }
            if (b === max_) { h = 4+(r - g) / delta; }
            h *= 60;
            if (h < 0) { h += 360; }
        }
        return [h, s, v]
    };

    var rgb2hsv = rgb2hsl$1;

    var unpack$i = utils.unpack;
    var floor$1 = Math.floor;

    var hsv2rgb = function () {
        var assign, assign$1, assign$2, assign$3, assign$4, assign$5;

        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];
        args = unpack$i(args, 'hsv');
        var h = args[0];
        var s = args[1];
        var v = args[2];
        var r,g,b;
        v *= 255;
        if (s === 0) {
            r = g = b = v;
        } else {
            if (h === 360) { h = 0; }
            if (h > 360) { h -= 360; }
            if (h < 0) { h += 360; }
            h /= 60;

            var i = floor$1(h);
            var f = h - i;
            var p = v * (1 - s);
            var q = v * (1 - s * f);
            var t = v * (1 - s * (1 - f));

            switch (i) {
                case 0: (assign = [v, t, p], r = assign[0], g = assign[1], b = assign[2]); break
                case 1: (assign$1 = [q, v, p], r = assign$1[0], g = assign$1[1], b = assign$1[2]); break
                case 2: (assign$2 = [p, v, t], r = assign$2[0], g = assign$2[1], b = assign$2[2]); break
                case 3: (assign$3 = [p, q, v], r = assign$3[0], g = assign$3[1], b = assign$3[2]); break
                case 4: (assign$4 = [t, p, v], r = assign$4[0], g = assign$4[1], b = assign$4[2]); break
                case 5: (assign$5 = [v, p, q], r = assign$5[0], g = assign$5[1], b = assign$5[2]); break
            }
        }
        return [r,g,b,args.length > 3?args[3]:1];
    };

    var hsv2rgb_1 = hsv2rgb;

    var unpack$j = utils.unpack;
    var type$8 = utils.type;






    Color_1.prototype.hsv = function() {
        return rgb2hsv(this._rgb);
    };

    chroma_1.hsv = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['hsv']) ));
    };

    input.format.hsv = hsv2rgb_1;

    input.autodetect.push({
        p: 2,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$j(args, 'hsv');
            if (type$8(args) === 'array' && args.length === 3) {
                return 'hsv';
            }
        }
    });

    var labConstants = {
        // Corresponds roughly to RGB brighter/darker
        Kn: 18,

        // D65 standard referent
        Xn: 0.950470,
        Yn: 1,
        Zn: 1.088830,

        t0: 0.137931034,  // 4 / 29
        t1: 0.206896552,  // 6 / 29
        t2: 0.12841855,   // 3 * t1 * t1
        t3: 0.008856452,  // t1 * t1 * t1
    };

    var unpack$k = utils.unpack;
    var pow = Math.pow;

    var rgb2lab = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$k(args, 'rgb');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        var ref$1 = rgb2xyz(r,g,b);
        var x = ref$1[0];
        var y = ref$1[1];
        var z = ref$1[2];
        var l = 116 * y - 16;
        return [l < 0 ? 0 : l, 500 * (x - y), 200 * (y - z)];
    };

    var rgb_xyz = function (r) {
        if ((r /= 255) <= 0.04045) { return r / 12.92; }
        return pow((r + 0.055) / 1.055, 2.4);
    };

    var xyz_lab = function (t) {
        if (t > labConstants.t3) { return pow(t, 1 / 3); }
        return t / labConstants.t2 + labConstants.t0;
    };

    var rgb2xyz = function (r,g,b) {
        r = rgb_xyz(r);
        g = rgb_xyz(g);
        b = rgb_xyz(b);
        var x = xyz_lab((0.4124564 * r + 0.3575761 * g + 0.1804375 * b) / labConstants.Xn);
        var y = xyz_lab((0.2126729 * r + 0.7151522 * g + 0.0721750 * b) / labConstants.Yn);
        var z = xyz_lab((0.0193339 * r + 0.1191920 * g + 0.9503041 * b) / labConstants.Zn);
        return [x,y,z];
    };

    var rgb2lab_1 = rgb2lab;

    var unpack$l = utils.unpack;
    var pow$1 = Math.pow;

    /*
     * L* [0..100]
     * a [-100..100]
     * b [-100..100]
     */
    var lab2rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        args = unpack$l(args, 'lab');
        var l = args[0];
        var a = args[1];
        var b = args[2];
        var x,y,z, r,g,b_;

        y = (l + 16) / 116;
        x = isNaN(a) ? y : y + a / 500;
        z = isNaN(b) ? y : y - b / 200;

        y = labConstants.Yn * lab_xyz(y);
        x = labConstants.Xn * lab_xyz(x);
        z = labConstants.Zn * lab_xyz(z);

        r = xyz_rgb(3.2404542 * x - 1.5371385 * y - 0.4985314 * z);  // D65 -> sRGB
        g = xyz_rgb(-0.9692660 * x + 1.8760108 * y + 0.0415560 * z);
        b_ = xyz_rgb(0.0556434 * x - 0.2040259 * y + 1.0572252 * z);

        return [r,g,b_,args.length > 3 ? args[3] : 1];
    };

    var xyz_rgb = function (r) {
        return 255 * (r <= 0.00304 ? 12.92 * r : 1.055 * pow$1(r, 1 / 2.4) - 0.055)
    };

    var lab_xyz = function (t) {
        return t > labConstants.t1 ? t * t * t : labConstants.t2 * (t - labConstants.t0)
    };

    var lab2rgb_1 = lab2rgb;

    var unpack$m = utils.unpack;
    var type$9 = utils.type;






    Color_1.prototype.lab = function() {
        return rgb2lab_1(this._rgb);
    };

    chroma_1.lab = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['lab']) ));
    };

    input.format.lab = lab2rgb_1;

    input.autodetect.push({
        p: 2,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$m(args, 'lab');
            if (type$9(args) === 'array' && args.length === 3) {
                return 'lab';
            }
        }
    });

    var unpack$n = utils.unpack;
    var RAD2DEG = utils.RAD2DEG;
    var sqrt$1 = Math.sqrt;
    var atan2 = Math.atan2;
    var round$4 = Math.round;

    var lab2lch = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$n(args, 'lab');
        var l = ref[0];
        var a = ref[1];
        var b = ref[2];
        var c = sqrt$1(a * a + b * b);
        var h = (atan2(b, a) * RAD2DEG + 360) % 360;
        if (round$4(c*10000) === 0) { h = Number.NaN; }
        return [l, c, h];
    };

    var lab2lch_1 = lab2lch;

    var unpack$o = utils.unpack;



    var rgb2lch = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$o(args, 'rgb');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        var ref$1 = rgb2lab_1(r,g,b);
        var l = ref$1[0];
        var a = ref$1[1];
        var b_ = ref$1[2];
        return lab2lch_1(l,a,b_);
    };

    var rgb2lch_1 = rgb2lch;

    var unpack$p = utils.unpack;
    var DEG2RAD = utils.DEG2RAD;
    var sin = Math.sin;
    var cos$1 = Math.cos;

    var lch2lab = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        /*
        Convert from a qualitative parameter h and a quantitative parameter l to a 24-bit pixel.
        These formulas were invented by David Dalrymple to obtain maximum contrast without going
        out of gamut if the parameters are in the range 0-1.

        A saturation multiplier was added by Gregor Aisch
        */
        var ref = unpack$p(args, 'lch');
        var l = ref[0];
        var c = ref[1];
        var h = ref[2];
        if (isNaN(h)) { h = 0; }
        h = h * DEG2RAD;
        return [l, cos$1(h) * c, sin(h) * c]
    };

    var lch2lab_1 = lch2lab;

    var unpack$q = utils.unpack;



    var lch2rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        args = unpack$q(args, 'lch');
        var l = args[0];
        var c = args[1];
        var h = args[2];
        var ref = lch2lab_1 (l,c,h);
        var L = ref[0];
        var a = ref[1];
        var b_ = ref[2];
        var ref$1 = lab2rgb_1 (L,a,b_);
        var r = ref$1[0];
        var g = ref$1[1];
        var b = ref$1[2];
        return [r, g, b, args.length > 3 ? args[3] : 1];
    };

    var lch2rgb_1 = lch2rgb;

    var unpack$r = utils.unpack;


    var hcl2rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var hcl = unpack$r(args, 'hcl').reverse();
        return lch2rgb_1.apply(void 0, hcl);
    };

    var hcl2rgb_1 = hcl2rgb;

    var unpack$s = utils.unpack;
    var type$a = utils.type;






    Color_1.prototype.lch = function() { return rgb2lch_1(this._rgb); };
    Color_1.prototype.hcl = function() { return rgb2lch_1(this._rgb).reverse(); };

    chroma_1.lch = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['lch']) ));
    };
    chroma_1.hcl = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['hcl']) ));
    };

    input.format.lch = lch2rgb_1;
    input.format.hcl = hcl2rgb_1;

    ['lch','hcl'].forEach(function (m) { return input.autodetect.push({
        p: 2,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$s(args, m);
            if (type$a(args) === 'array' && args.length === 3) {
                return m;
            }
        }
    }); });

    /**
    	X11 color names

    	http://www.w3.org/TR/css3-color/#svg-color
    */

    var w3cx11 = {
        aliceblue: '#f0f8ff',
        antiquewhite: '#faebd7',
        aqua: '#00ffff',
        aquamarine: '#7fffd4',
        azure: '#f0ffff',
        beige: '#f5f5dc',
        bisque: '#ffe4c4',
        black: '#000000',
        blanchedalmond: '#ffebcd',
        blue: '#0000ff',
        blueviolet: '#8a2be2',
        brown: '#a52a2a',
        burlywood: '#deb887',
        cadetblue: '#5f9ea0',
        chartreuse: '#7fff00',
        chocolate: '#d2691e',
        coral: '#ff7f50',
        cornflower: '#6495ed',
        cornflowerblue: '#6495ed',
        cornsilk: '#fff8dc',
        crimson: '#dc143c',
        cyan: '#00ffff',
        darkblue: '#00008b',
        darkcyan: '#008b8b',
        darkgoldenrod: '#b8860b',
        darkgray: '#a9a9a9',
        darkgreen: '#006400',
        darkgrey: '#a9a9a9',
        darkkhaki: '#bdb76b',
        darkmagenta: '#8b008b',
        darkolivegreen: '#556b2f',
        darkorange: '#ff8c00',
        darkorchid: '#9932cc',
        darkred: '#8b0000',
        darksalmon: '#e9967a',
        darkseagreen: '#8fbc8f',
        darkslateblue: '#483d8b',
        darkslategray: '#2f4f4f',
        darkslategrey: '#2f4f4f',
        darkturquoise: '#00ced1',
        darkviolet: '#9400d3',
        deeppink: '#ff1493',
        deepskyblue: '#00bfff',
        dimgray: '#696969',
        dimgrey: '#696969',
        dodgerblue: '#1e90ff',
        firebrick: '#b22222',
        floralwhite: '#fffaf0',
        forestgreen: '#228b22',
        fuchsia: '#ff00ff',
        gainsboro: '#dcdcdc',
        ghostwhite: '#f8f8ff',
        gold: '#ffd700',
        goldenrod: '#daa520',
        gray: '#808080',
        green: '#008000',
        greenyellow: '#adff2f',
        grey: '#808080',
        honeydew: '#f0fff0',
        hotpink: '#ff69b4',
        indianred: '#cd5c5c',
        indigo: '#4b0082',
        ivory: '#fffff0',
        khaki: '#f0e68c',
        laserlemon: '#ffff54',
        lavender: '#e6e6fa',
        lavenderblush: '#fff0f5',
        lawngreen: '#7cfc00',
        lemonchiffon: '#fffacd',
        lightblue: '#add8e6',
        lightcoral: '#f08080',
        lightcyan: '#e0ffff',
        lightgoldenrod: '#fafad2',
        lightgoldenrodyellow: '#fafad2',
        lightgray: '#d3d3d3',
        lightgreen: '#90ee90',
        lightgrey: '#d3d3d3',
        lightpink: '#ffb6c1',
        lightsalmon: '#ffa07a',
        lightseagreen: '#20b2aa',
        lightskyblue: '#87cefa',
        lightslategray: '#778899',
        lightslategrey: '#778899',
        lightsteelblue: '#b0c4de',
        lightyellow: '#ffffe0',
        lime: '#00ff00',
        limegreen: '#32cd32',
        linen: '#faf0e6',
        magenta: '#ff00ff',
        maroon: '#800000',
        maroon2: '#7f0000',
        maroon3: '#b03060',
        mediumaquamarine: '#66cdaa',
        mediumblue: '#0000cd',
        mediumorchid: '#ba55d3',
        mediumpurple: '#9370db',
        mediumseagreen: '#3cb371',
        mediumslateblue: '#7b68ee',
        mediumspringgreen: '#00fa9a',
        mediumturquoise: '#48d1cc',
        mediumvioletred: '#c71585',
        midnightblue: '#191970',
        mintcream: '#f5fffa',
        mistyrose: '#ffe4e1',
        moccasin: '#ffe4b5',
        navajowhite: '#ffdead',
        navy: '#000080',
        oldlace: '#fdf5e6',
        olive: '#808000',
        olivedrab: '#6b8e23',
        orange: '#ffa500',
        orangered: '#ff4500',
        orchid: '#da70d6',
        palegoldenrod: '#eee8aa',
        palegreen: '#98fb98',
        paleturquoise: '#afeeee',
        palevioletred: '#db7093',
        papayawhip: '#ffefd5',
        peachpuff: '#ffdab9',
        peru: '#cd853f',
        pink: '#ffc0cb',
        plum: '#dda0dd',
        powderblue: '#b0e0e6',
        purple: '#800080',
        purple2: '#7f007f',
        purple3: '#a020f0',
        rebeccapurple: '#663399',
        red: '#ff0000',
        rosybrown: '#bc8f8f',
        royalblue: '#4169e1',
        saddlebrown: '#8b4513',
        salmon: '#fa8072',
        sandybrown: '#f4a460',
        seagreen: '#2e8b57',
        seashell: '#fff5ee',
        sienna: '#a0522d',
        silver: '#c0c0c0',
        skyblue: '#87ceeb',
        slateblue: '#6a5acd',
        slategray: '#708090',
        slategrey: '#708090',
        snow: '#fffafa',
        springgreen: '#00ff7f',
        steelblue: '#4682b4',
        tan: '#d2b48c',
        teal: '#008080',
        thistle: '#d8bfd8',
        tomato: '#ff6347',
        turquoise: '#40e0d0',
        violet: '#ee82ee',
        wheat: '#f5deb3',
        white: '#ffffff',
        whitesmoke: '#f5f5f5',
        yellow: '#ffff00',
        yellowgreen: '#9acd32'
    };

    var w3cx11_1 = w3cx11;

    var type$b = utils.type;





    Color_1.prototype.name = function() {
        var hex = rgb2hex_1(this._rgb, 'rgb');
        for (var i = 0, list = Object.keys(w3cx11_1); i < list.length; i += 1) {
            var n = list[i];

            if (w3cx11_1[n] === hex) { return n.toLowerCase(); }
        }
        return hex;
    };

    input.format.named = function (name) {
        name = name.toLowerCase();
        if (w3cx11_1[name]) { return hex2rgb_1(w3cx11_1[name]); }
        throw new Error('unknown color name: '+name);
    };

    input.autodetect.push({
        p: 5,
        test: function (h) {
            var rest = [], len = arguments.length - 1;
            while ( len-- > 0 ) rest[ len ] = arguments[ len + 1 ];

            if (!rest.length && type$b(h) === 'string' && w3cx11_1[h.toLowerCase()]) {
                return 'named';
            }
        }
    });

    var unpack$t = utils.unpack;

    var rgb2num = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var ref = unpack$t(args, 'rgb');
        var r = ref[0];
        var g = ref[1];
        var b = ref[2];
        return (r << 16) + (g << 8) + b;
    };

    var rgb2num_1 = rgb2num;

    var type$c = utils.type;

    var num2rgb = function (num) {
        if (type$c(num) == "number" && num >= 0 && num <= 0xFFFFFF) {
            var r = num >> 16;
            var g = (num >> 8) & 0xFF;
            var b = num & 0xFF;
            return [r,g,b,1];
        }
        throw new Error("unknown num color: "+num);
    };

    var num2rgb_1 = num2rgb;

    var type$d = utils.type;



    Color_1.prototype.num = function() {
        return rgb2num_1(this._rgb);
    };

    chroma_1.num = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['num']) ));
    };

    input.format.num = num2rgb_1;

    input.autodetect.push({
        p: 5,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            if (args.length === 1 && type$d(args[0]) === 'number' && args[0] >= 0 && args[0] <= 0xFFFFFF) {
                return 'num';
            }
        }
    });

    var unpack$u = utils.unpack;
    var type$e = utils.type;
    var round$5 = Math.round;

    Color_1.prototype.rgb = function(rnd) {
        if ( rnd === void 0 ) rnd=true;

        if (rnd === false) { return this._rgb.slice(0,3); }
        return this._rgb.slice(0,3).map(round$5);
    };

    Color_1.prototype.rgba = function(rnd) {
        if ( rnd === void 0 ) rnd=true;

        return this._rgb.slice(0,4).map(function (v,i) {
            return i<3 ? (rnd === false ? v : round$5(v)) : v;
        });
    };

    chroma_1.rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['rgb']) ));
    };

    input.format.rgb = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var rgba = unpack$u(args, 'rgba');
        if (rgba[3] === undefined) { rgba[3] = 1; }
        return rgba;
    };

    input.autodetect.push({
        p: 3,
        test: function () {
            var args = [], len = arguments.length;
            while ( len-- ) args[ len ] = arguments[ len ];

            args = unpack$u(args, 'rgba');
            if (type$e(args) === 'array' && (args.length === 3 ||
                args.length === 4 && type$e(args[3]) == 'number' && args[3] >= 0 && args[3] <= 1)) {
                return 'rgb';
            }
        }
    });

    /*
     * Based on implementation by Neil Bartlett
     * https://github.com/neilbartlett/color-temperature
     */

    var log = Math.log;

    var temperature2rgb = function (kelvin) {
        var temp = kelvin / 100;
        var r,g,b;
        if (temp < 66) {
            r = 255;
            g = -155.25485562709179 - 0.44596950469579133 * (g = temp-2) + 104.49216199393888 * log(g);
            b = temp < 20 ? 0 : -254.76935184120902 + 0.8274096064007395 * (b = temp-10) + 115.67994401066147 * log(b);
        } else {
            r = 351.97690566805693 + 0.114206453784165 * (r = temp-55) - 40.25366309332127 * log(r);
            g = 325.4494125711974 + 0.07943456536662342 * (g = temp-50) - 28.0852963507957 * log(g);
            b = 255;
        }
        return [r,g,b,1];
    };

    var temperature2rgb_1 = temperature2rgb;

    /*
     * Based on implementation by Neil Bartlett
     * https://github.com/neilbartlett/color-temperature
     **/


    var unpack$v = utils.unpack;
    var round$6 = Math.round;

    var rgb2temperature = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        var rgb = unpack$v(args, 'rgb');
        var r = rgb[0], b = rgb[2];
        var minTemp = 1000;
        var maxTemp = 40000;
        var eps = 0.4;
        var temp;
        while (maxTemp - minTemp > eps) {
            temp = (maxTemp + minTemp) * 0.5;
            var rgb$1 = temperature2rgb_1(temp);
            if ((rgb$1[2] / rgb$1[0]) >= (b / r)) {
                maxTemp = temp;
            } else {
                minTemp = temp;
            }
        }
        return round$6(temp);
    };

    var rgb2temperature_1 = rgb2temperature;

    Color_1.prototype.temp =
    Color_1.prototype.kelvin =
    Color_1.prototype.temperature = function() {
        return rgb2temperature_1(this._rgb);
    };

    chroma_1.temp =
    chroma_1.kelvin =
    chroma_1.temperature = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return new (Function.prototype.bind.apply( Color_1, [ null ].concat( args, ['temp']) ));
    };

    input.format.temp =
    input.format.kelvin =
    input.format.temperature = temperature2rgb_1;

    var type$f = utils.type;

    Color_1.prototype.alpha = function(a, mutate) {
        if ( mutate === void 0 ) mutate=false;

        if (a !== undefined && type$f(a) === 'number') {
            if (mutate) {
                this._rgb[3] = a;
                return this;
            }
            return new Color_1([this._rgb[0], this._rgb[1], this._rgb[2], a], 'rgb');
        }
        return this._rgb[3];
    };

    Color_1.prototype.clipped = function() {
        return this._rgb._clipped || false;
    };

    Color_1.prototype.darken = function(amount) {
    	if ( amount === void 0 ) amount=1;

    	var me = this;
    	var lab = me.lab();
    	lab[0] -= labConstants.Kn * amount;
    	return new Color_1(lab, 'lab').alpha(me.alpha(), true);
    };

    Color_1.prototype.brighten = function(amount) {
    	if ( amount === void 0 ) amount=1;

    	return this.darken(-amount);
    };

    Color_1.prototype.darker = Color_1.prototype.darken;
    Color_1.prototype.brighter = Color_1.prototype.brighten;

    Color_1.prototype.get = function(mc) {
        var ref = mc.split('.');
        var mode = ref[0];
        var channel = ref[1];
        var src = this[mode]();
        if (channel) {
            var i = mode.indexOf(channel);
            if (i > -1) { return src[i]; }
            throw new Error(("unknown channel " + channel + " in mode " + mode));
        } else {
            return src;
        }
    };

    var type$g = utils.type;
    var pow$2 = Math.pow;

    var EPS = 1e-7;
    var MAX_ITER = 20;

    Color_1.prototype.luminance = function(lum) {
        if (lum !== undefined && type$g(lum) === 'number') {
            if (lum === 0) {
                // return pure black
                return new Color_1([0,0,0,this._rgb[3]], 'rgb');
            }
            if (lum === 1) {
                // return pure white
                return new Color_1([255,255,255,this._rgb[3]], 'rgb');
            }
            // compute new color using...
            var cur_lum = this.luminance();
            var mode = 'rgb';
            var max_iter = MAX_ITER;

            var test = function (low, high) {
                var mid = low.interpolate(high, 0.5, mode);
                var lm = mid.luminance();
                if (Math.abs(lum - lm) < EPS || !max_iter--) {
                    // close enough
                    return mid;
                }
                return lm > lum ? test(low, mid) : test(mid, high);
            };

            var rgb = (cur_lum > lum ? test(new Color_1([0,0,0]), this) : test(this, new Color_1([255,255,255]))).rgb();
            return new Color_1(rgb.concat( [this._rgb[3]]));
        }
        return rgb2luminance.apply(void 0, (this._rgb).slice(0,3));
    };


    var rgb2luminance = function (r,g,b) {
        // relative luminance
        // see http://www.w3.org/TR/2008/REC-WCAG20-20081211/#relativeluminancedef
        r = luminance_x(r);
        g = luminance_x(g);
        b = luminance_x(b);
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };

    var luminance_x = function (x) {
        x /= 255;
        return x <= 0.03928 ? x/12.92 : pow$2((x+0.055)/1.055, 2.4);
    };

    var interpolator = {};

    var type$h = utils.type;


    var mix = function (col1, col2, f) {
        if ( f === void 0 ) f=0.5;
        var rest = [], len = arguments.length - 3;
        while ( len-- > 0 ) rest[ len ] = arguments[ len + 3 ];

        var mode = rest[0] || 'lrgb';
        if (!interpolator[mode] && !rest.length) {
            // fall back to the first supported mode
            mode = Object.keys(interpolator)[0];
        }
        if (!interpolator[mode]) {
            throw new Error(("interpolation mode " + mode + " is not defined"));
        }
        if (type$h(col1) !== 'object') { col1 = new Color_1(col1); }
        if (type$h(col2) !== 'object') { col2 = new Color_1(col2); }
        return interpolator[mode](col1, col2, f)
            .alpha(col1.alpha() + f * (col2.alpha() - col1.alpha()));
    };

    Color_1.prototype.mix =
    Color_1.prototype.interpolate = function(col2, f) {
    	if ( f === void 0 ) f=0.5;
    	var rest = [], len = arguments.length - 2;
    	while ( len-- > 0 ) rest[ len ] = arguments[ len + 2 ];

    	return mix.apply(void 0, [ this, col2, f ].concat( rest ));
    };

    Color_1.prototype.premultiply = function(mutate) {
    	if ( mutate === void 0 ) mutate=false;

    	var rgb = this._rgb;
    	var a = rgb[3];
    	if (mutate) {
    		this._rgb = [rgb[0]*a, rgb[1]*a, rgb[2]*a, a];
    		return this;
    	} else {
    		return new Color_1([rgb[0]*a, rgb[1]*a, rgb[2]*a, a], 'rgb');
    	}
    };

    Color_1.prototype.saturate = function(amount) {
    	if ( amount === void 0 ) amount=1;

    	var me = this;
    	var lch = me.lch();
    	lch[1] += labConstants.Kn * amount;
    	if (lch[1] < 0) { lch[1] = 0; }
    	return new Color_1(lch, 'lch').alpha(me.alpha(), true);
    };

    Color_1.prototype.desaturate = function(amount) {
    	if ( amount === void 0 ) amount=1;

    	return this.saturate(-amount);
    };

    var type$i = utils.type;

    Color_1.prototype.set = function(mc, value, mutate) {
        if ( mutate === void 0 ) mutate=false;

        var ref = mc.split('.');
        var mode = ref[0];
        var channel = ref[1];
        var src = this[mode]();
        if (channel) {
            var i = mode.indexOf(channel);
            if (i > -1) {
                if (type$i(value) == 'string') {
                    switch(value.charAt(0)) {
                        case '+': src[i] += +value; break;
                        case '-': src[i] += +value; break;
                        case '*': src[i] *= +(value.substr(1)); break;
                        case '/': src[i] /= +(value.substr(1)); break;
                        default: src[i] = +value;
                    }
                } else if (type$i(value) === 'number') {
                    src[i] = value;
                } else {
                    throw new Error("unsupported value for Color.set");
                }
                var out = new Color_1(src, mode);
                if (mutate) {
                    this._rgb = out._rgb;
                    return this;
                }
                return out;
            }
            throw new Error(("unknown channel " + channel + " in mode " + mode));
        } else {
            return src;
        }
    };

    var rgb$1 = function (col1, col2, f) {
        var xyz0 = col1._rgb;
        var xyz1 = col2._rgb;
        return new Color_1(
            xyz0[0] + f * (xyz1[0]-xyz0[0]),
            xyz0[1] + f * (xyz1[1]-xyz0[1]),
            xyz0[2] + f * (xyz1[2]-xyz0[2]),
            'rgb'
        )
    };

    // register interpolator
    interpolator.rgb = rgb$1;

    var sqrt$2 = Math.sqrt;
    var pow$3 = Math.pow;

    var lrgb = function (col1, col2, f) {
        var ref = col1._rgb;
        var x1 = ref[0];
        var y1 = ref[1];
        var z1 = ref[2];
        var ref$1 = col2._rgb;
        var x2 = ref$1[0];
        var y2 = ref$1[1];
        var z2 = ref$1[2];
        return new Color_1(
            sqrt$2(pow$3(x1,2) * (1-f) + pow$3(x2,2) * f),
            sqrt$2(pow$3(y1,2) * (1-f) + pow$3(y2,2) * f),
            sqrt$2(pow$3(z1,2) * (1-f) + pow$3(z2,2) * f),
            'rgb'
        )
    };

    // register interpolator
    interpolator.lrgb = lrgb;

    var lab$1 = function (col1, col2, f) {
        var xyz0 = col1.lab();
        var xyz1 = col2.lab();
        return new Color_1(
            xyz0[0] + f * (xyz1[0]-xyz0[0]),
            xyz0[1] + f * (xyz1[1]-xyz0[1]),
            xyz0[2] + f * (xyz1[2]-xyz0[2]),
            'lab'
        )
    };

    // register interpolator
    interpolator.lab = lab$1;

    var _hsx = function (col1, col2, f, m) {
        var assign, assign$1;

        var xyz0, xyz1;
        if (m === 'hsl') {
            xyz0 = col1.hsl();
            xyz1 = col2.hsl();
        } else if (m === 'hsv') {
            xyz0 = col1.hsv();
            xyz1 = col2.hsv();
        } else if (m === 'hcg') {
            xyz0 = col1.hcg();
            xyz1 = col2.hcg();
        } else if (m === 'hsi') {
            xyz0 = col1.hsi();
            xyz1 = col2.hsi();
        } else if (m === 'lch' || m === 'hcl') {
            m = 'hcl';
            xyz0 = col1.hcl();
            xyz1 = col2.hcl();
        }

        var hue0, hue1, sat0, sat1, lbv0, lbv1;
        if (m.substr(0, 1) === 'h') {
            (assign = xyz0, hue0 = assign[0], sat0 = assign[1], lbv0 = assign[2]);
            (assign$1 = xyz1, hue1 = assign$1[0], sat1 = assign$1[1], lbv1 = assign$1[2]);
        }

        var sat, hue, lbv, dh;

        if (!isNaN(hue0) && !isNaN(hue1)) {
            // both colors have hue
            if (hue1 > hue0 && hue1 - hue0 > 180) {
                dh = hue1-(hue0+360);
            } else if (hue1 < hue0 && hue0 - hue1 > 180) {
                dh = hue1+360-hue0;
            } else{
                dh = hue1 - hue0;
            }
            hue = hue0 + f * dh;
        } else if (!isNaN(hue0)) {
            hue = hue0;
            if ((lbv1 == 1 || lbv1 == 0) && m != 'hsv') { sat = sat0; }
        } else if (!isNaN(hue1)) {
            hue = hue1;
            if ((lbv0 == 1 || lbv0 == 0) && m != 'hsv') { sat = sat1; }
        } else {
            hue = Number.NaN;
        }

        if (sat === undefined) { sat = sat0 + f * (sat1 - sat0); }
        lbv = lbv0 + f * (lbv1-lbv0);
        return new Color_1([hue, sat, lbv], m);
    };

    var lch$1 = function (col1, col2, f) {
    	return _hsx(col1, col2, f, 'lch');
    };

    // register interpolator
    interpolator.lch = lch$1;
    interpolator.hcl = lch$1;

    var num$1 = function (col1, col2, f) {
        var c1 = col1.num();
        var c2 = col2.num();
        return new Color_1(c1 + f * (c2-c1), 'num')
    };

    // register interpolator
    interpolator.num = num$1;

    var hcg$1 = function (col1, col2, f) {
    	return _hsx(col1, col2, f, 'hcg');
    };

    // register interpolator
    interpolator.hcg = hcg$1;

    var hsi$1 = function (col1, col2, f) {
    	return _hsx(col1, col2, f, 'hsi');
    };

    // register interpolator
    interpolator.hsi = hsi$1;

    var hsl$1 = function (col1, col2, f) {
    	return _hsx(col1, col2, f, 'hsl');
    };

    // register interpolator
    interpolator.hsl = hsl$1;

    var hsv$1 = function (col1, col2, f) {
    	return _hsx(col1, col2, f, 'hsv');
    };

    // register interpolator
    interpolator.hsv = hsv$1;

    var clip_rgb$2 = utils.clip_rgb;
    var pow$4 = Math.pow;
    var sqrt$3 = Math.sqrt;
    var PI$1 = Math.PI;
    var cos$2 = Math.cos;
    var sin$1 = Math.sin;
    var atan2$1 = Math.atan2;

    var average = function (colors, mode, weights) {
        if ( mode === void 0 ) mode='lrgb';
        if ( weights === void 0 ) weights=null;

        var l = colors.length;
        if (!weights) { weights = Array.from(new Array(l)).map(function () { return 1; }); }
        // normalize weights
        var k = l / weights.reduce(function(a, b) { return a + b; });
        weights.forEach(function (w,i) { weights[i] *= k; });
        // convert colors to Color objects
        colors = colors.map(function (c) { return new Color_1(c); });
        if (mode === 'lrgb') {
            return _average_lrgb(colors, weights)
        }
        var first = colors.shift();
        var xyz = first.get(mode);
        var cnt = [];
        var dx = 0;
        var dy = 0;
        // initial color
        for (var i=0; i<xyz.length; i++) {
            xyz[i] = (xyz[i] || 0) * weights[0];
            cnt.push(isNaN(xyz[i]) ? 0 : weights[0]);
            if (mode.charAt(i) === 'h' && !isNaN(xyz[i])) {
                var A = xyz[i] / 180 * PI$1;
                dx += cos$2(A) * weights[0];
                dy += sin$1(A) * weights[0];
            }
        }

        var alpha = first.alpha() * weights[0];
        colors.forEach(function (c,ci) {
            var xyz2 = c.get(mode);
            alpha += c.alpha() * weights[ci+1];
            for (var i=0; i<xyz.length; i++) {
                if (!isNaN(xyz2[i])) {
                    cnt[i] += weights[ci+1];
                    if (mode.charAt(i) === 'h') {
                        var A = xyz2[i] / 180 * PI$1;
                        dx += cos$2(A) * weights[ci+1];
                        dy += sin$1(A) * weights[ci+1];
                    } else {
                        xyz[i] += xyz2[i] * weights[ci+1];
                    }
                }
            }
        });

        for (var i$1=0; i$1<xyz.length; i$1++) {
            if (mode.charAt(i$1) === 'h') {
                var A$1 = atan2$1(dy / cnt[i$1], dx / cnt[i$1]) / PI$1 * 180;
                while (A$1 < 0) { A$1 += 360; }
                while (A$1 >= 360) { A$1 -= 360; }
                xyz[i$1] = A$1;
            } else {
                xyz[i$1] = xyz[i$1]/cnt[i$1];
            }
        }
        alpha /= l;
        return (new Color_1(xyz, mode)).alpha(alpha > 0.99999 ? 1 : alpha, true);
    };


    var _average_lrgb = function (colors, weights) {
        var l = colors.length;
        var xyz = [0,0,0,0];
        for (var i=0; i < colors.length; i++) {
            var col = colors[i];
            var f = weights[i] / l;
            var rgb = col._rgb;
            xyz[0] += pow$4(rgb[0],2) * f;
            xyz[1] += pow$4(rgb[1],2) * f;
            xyz[2] += pow$4(rgb[2],2) * f;
            xyz[3] += rgb[3] * f;
        }
        xyz[0] = sqrt$3(xyz[0]);
        xyz[1] = sqrt$3(xyz[1]);
        xyz[2] = sqrt$3(xyz[2]);
        if (xyz[3] > 0.9999999) { xyz[3] = 1; }
        return new Color_1(clip_rgb$2(xyz));
    };

    // minimal multi-purpose interface

    // @requires utils color analyze


    var type$j = utils.type;

    var pow$5 = Math.pow;

    var scale = function(colors) {

        // constructor
        var _mode = 'rgb';
        var _nacol = chroma_1('#ccc');
        var _spread = 0;
        // const _fixed = false;
        var _domain = [0, 1];
        var _pos = [];
        var _padding = [0,0];
        var _classes = false;
        var _colors = [];
        var _out = false;
        var _min = 0;
        var _max = 1;
        var _correctLightness = false;
        var _colorCache = {};
        var _useCache = true;
        var _gamma = 1;

        // private methods

        var setColors = function(colors) {
            colors = colors || ['#fff', '#000'];
            if (colors && type$j(colors) === 'string' && chroma_1.brewer &&
                chroma_1.brewer[colors.toLowerCase()]) {
                colors = chroma_1.brewer[colors.toLowerCase()];
            }
            if (type$j(colors) === 'array') {
                // handle single color
                if (colors.length === 1) {
                    colors = [colors[0], colors[0]];
                }
                // make a copy of the colors
                colors = colors.slice(0);
                // convert to chroma classes
                for (var c=0; c<colors.length; c++) {
                    colors[c] = chroma_1(colors[c]);
                }
                // auto-fill color position
                _pos.length = 0;
                for (var c$1=0; c$1<colors.length; c$1++) {
                    _pos.push(c$1/(colors.length-1));
                }
            }
            resetCache();
            return _colors = colors;
        };

        var getClass = function(value) {
            if (_classes != null) {
                var n = _classes.length-1;
                var i = 0;
                while (i < n && value >= _classes[i]) {
                    i++;
                }
                return i-1;
            }
            return 0;
        };

        var tMapLightness = function (t) { return t; };
        var tMapDomain = function (t) { return t; };

        // const classifyValue = function(value) {
        //     let val = value;
        //     if (_classes.length > 2) {
        //         const n = _classes.length-1;
        //         const i = getClass(value);
        //         const minc = _classes[0] + ((_classes[1]-_classes[0]) * (0 + (_spread * 0.5)));  // center of 1st class
        //         const maxc = _classes[n-1] + ((_classes[n]-_classes[n-1]) * (1 - (_spread * 0.5)));  // center of last class
        //         val = _min + ((((_classes[i] + ((_classes[i+1] - _classes[i]) * 0.5)) - minc) / (maxc-minc)) * (_max - _min));
        //     }
        //     return val;
        // };

        var getColor = function(val, bypassMap) {
            var col, t;
            if (bypassMap == null) { bypassMap = false; }
            if (isNaN(val) || (val === null)) { return _nacol; }
            if (!bypassMap) {
                if (_classes && (_classes.length > 2)) {
                    // find the class
                    var c = getClass(val);
                    t = c / (_classes.length-2);
                } else if (_max !== _min) {
                    // just interpolate between min/max
                    t = (val - _min) / (_max - _min);
                } else {
                    t = 1;
                }
            } else {
                t = val;
            }

            // domain map
            t = tMapDomain(t);

            if (!bypassMap) {
                t = tMapLightness(t);  // lightness correction
            }

            if (_gamma !== 1) { t = pow$5(t, _gamma); }

            t = _padding[0] + (t * (1 - _padding[0] - _padding[1]));

            t = Math.min(1, Math.max(0, t));

            var k = Math.floor(t * 10000);

            if (_useCache && _colorCache[k]) {
                col = _colorCache[k];
            } else {
                if (type$j(_colors) === 'array') {
                    //for i in [0.._pos.length-1]
                    for (var i=0; i<_pos.length; i++) {
                        var p = _pos[i];
                        if (t <= p) {
                            col = _colors[i];
                            break;
                        }
                        if ((t >= p) && (i === (_pos.length-1))) {
                            col = _colors[i];
                            break;
                        }
                        if (t > p && t < _pos[i+1]) {
                            t = (t-p)/(_pos[i+1]-p);
                            col = chroma_1.interpolate(_colors[i], _colors[i+1], t, _mode);
                            break;
                        }
                    }
                } else if (type$j(_colors) === 'function') {
                    col = _colors(t);
                }
                if (_useCache) { _colorCache[k] = col; }
            }
            return col;
        };

        var resetCache = function () { return _colorCache = {}; };

        setColors(colors);

        // public interface

        var f = function(v) {
            var c = chroma_1(getColor(v));
            if (_out && c[_out]) { return c[_out](); } else { return c; }
        };

        f.classes = function(classes) {
            if (classes != null) {
                if (type$j(classes) === 'array') {
                    _classes = classes;
                    _domain = [classes[0], classes[classes.length-1]];
                } else {
                    var d = chroma_1.analyze(_domain);
                    if (classes === 0) {
                        _classes = [d.min, d.max];
                    } else {
                        _classes = chroma_1.limits(d, 'e', classes);
                    }
                }
                return f;
            }
            return _classes;
        };


        f.domain = function(domain) {
            if (!arguments.length) {
                return _domain;
            }
            _min = domain[0];
            _max = domain[domain.length-1];
            _pos = [];
            var k = _colors.length;
            if ((domain.length === k) && (_min !== _max)) {
                // update positions
                for (var i = 0, list = Array.from(domain); i < list.length; i += 1) {
                    var d = list[i];

                  _pos.push((d-_min) / (_max-_min));
                }
            } else {
                for (var c=0; c<k; c++) {
                    _pos.push(c/(k-1));
                }
                if (domain.length > 2) {
                    // set domain map
                    var tOut = domain.map(function (d,i) { return i/(domain.length-1); });
                    var tBreaks = domain.map(function (d) { return (d - _min) / (_max - _min); });
                    if (!tBreaks.every(function (val, i) { return tOut[i] === val; })) {
                        tMapDomain = function (t) {
                            if (t <= 0 || t >= 1) { return t; }
                            var i = 0;
                            while (t >= tBreaks[i+1]) { i++; }
                            var f = (t - tBreaks[i]) / (tBreaks[i+1] - tBreaks[i]);
                            var out = tOut[i] + f * (tOut[i+1] - tOut[i]);
                            return out;
                        };
                    }

                }
            }
            _domain = [_min, _max];
            return f;
        };

        f.mode = function(_m) {
            if (!arguments.length) {
                return _mode;
            }
            _mode = _m;
            resetCache();
            return f;
        };

        f.range = function(colors, _pos) {
            setColors(colors, _pos);
            return f;
        };

        f.out = function(_o) {
            _out = _o;
            return f;
        };

        f.spread = function(val) {
            if (!arguments.length) {
                return _spread;
            }
            _spread = val;
            return f;
        };

        f.correctLightness = function(v) {
            if (v == null) { v = true; }
            _correctLightness = v;
            resetCache();
            if (_correctLightness) {
                tMapLightness = function(t) {
                    var L0 = getColor(0, true).lab()[0];
                    var L1 = getColor(1, true).lab()[0];
                    var pol = L0 > L1;
                    var L_actual = getColor(t, true).lab()[0];
                    var L_ideal = L0 + ((L1 - L0) * t);
                    var L_diff = L_actual - L_ideal;
                    var t0 = 0;
                    var t1 = 1;
                    var max_iter = 20;
                    while ((Math.abs(L_diff) > 1e-2) && (max_iter-- > 0)) {
                        (function() {
                            if (pol) { L_diff *= -1; }
                            if (L_diff < 0) {
                                t0 = t;
                                t += (t1 - t) * 0.5;
                            } else {
                                t1 = t;
                                t += (t0 - t) * 0.5;
                            }
                            L_actual = getColor(t, true).lab()[0];
                            return L_diff = L_actual - L_ideal;
                        })();
                    }
                    return t;
                };
            } else {
                tMapLightness = function (t) { return t; };
            }
            return f;
        };

        f.padding = function(p) {
            if (p != null) {
                if (type$j(p) === 'number') {
                    p = [p,p];
                }
                _padding = p;
                return f;
            } else {
                return _padding;
            }
        };

        f.colors = function(numColors, out) {
            // If no arguments are given, return the original colors that were provided
            if (arguments.length < 2) { out = 'hex'; }
            var result = [];

            if (arguments.length === 0) {
                result = _colors.slice(0);

            } else if (numColors === 1) {
                result = [f(0.5)];

            } else if (numColors > 1) {
                var dm = _domain[0];
                var dd = _domain[1] - dm;
                result = __range__(0, numColors, false).map(function (i) { return f( dm + ((i/(numColors-1)) * dd) ); });

            } else { // returns all colors based on the defined classes
                colors = [];
                var samples = [];
                if (_classes && (_classes.length > 2)) {
                    for (var i = 1, end = _classes.length, asc = 1 <= end; asc ? i < end : i > end; asc ? i++ : i--) {
                        samples.push((_classes[i-1]+_classes[i])*0.5);
                    }
                } else {
                    samples = _domain;
                }
                result = samples.map(function (v) { return f(v); });
            }

            if (chroma_1[out]) {
                result = result.map(function (c) { return c[out](); });
            }
            return result;
        };

        f.cache = function(c) {
            if (c != null) {
                _useCache = c;
                return f;
            } else {
                return _useCache;
            }
        };

        f.gamma = function(g) {
            if (g != null) {
                _gamma = g;
                return f;
            } else {
                return _gamma;
            }
        };

        f.nodata = function(d) {
            if (d != null) {
                _nacol = chroma_1(d);
                return f;
            } else {
                return _nacol;
            }
        };

        return f;
    };

    function __range__(left, right, inclusive) {
      var range = [];
      var ascending = left < right;
      var end = !inclusive ? right : ascending ? right + 1 : right - 1;
      for (var i = left; ascending ? i < end : i > end; ascending ? i++ : i--) {
        range.push(i);
      }
      return range;
    }

    //
    // interpolates between a set of colors uzing a bezier spline
    //

    // @requires utils lab




    var bezier = function(colors) {
        var assign, assign$1, assign$2;

        var I, lab0, lab1, lab2;
        colors = colors.map(function (c) { return new Color_1(c); });
        if (colors.length === 2) {
            // linear interpolation
            (assign = colors.map(function (c) { return c.lab(); }), lab0 = assign[0], lab1 = assign[1]);
            I = function(t) {
                var lab = ([0, 1, 2].map(function (i) { return lab0[i] + (t * (lab1[i] - lab0[i])); }));
                return new Color_1(lab, 'lab');
            };
        } else if (colors.length === 3) {
            // quadratic bezier interpolation
            (assign$1 = colors.map(function (c) { return c.lab(); }), lab0 = assign$1[0], lab1 = assign$1[1], lab2 = assign$1[2]);
            I = function(t) {
                var lab = ([0, 1, 2].map(function (i) { return ((1-t)*(1-t) * lab0[i]) + (2 * (1-t) * t * lab1[i]) + (t * t * lab2[i]); }));
                return new Color_1(lab, 'lab');
            };
        } else if (colors.length === 4) {
            // cubic bezier interpolation
            var lab3;
            (assign$2 = colors.map(function (c) { return c.lab(); }), lab0 = assign$2[0], lab1 = assign$2[1], lab2 = assign$2[2], lab3 = assign$2[3]);
            I = function(t) {
                var lab = ([0, 1, 2].map(function (i) { return ((1-t)*(1-t)*(1-t) * lab0[i]) + (3 * (1-t) * (1-t) * t * lab1[i]) + (3 * (1-t) * t * t * lab2[i]) + (t*t*t * lab3[i]); }));
                return new Color_1(lab, 'lab');
            };
        } else if (colors.length === 5) {
            var I0 = bezier(colors.slice(0, 3));
            var I1 = bezier(colors.slice(2, 5));
            I = function(t) {
                if (t < 0.5) {
                    return I0(t*2);
                } else {
                    return I1((t-0.5)*2);
                }
            };
        }
        return I;
    };

    var bezier_1 = function (colors) {
        var f = bezier(colors);
        f.scale = function () { return scale(f); };
        return f;
    };

    /*
     * interpolates between a set of colors uzing a bezier spline
     * blend mode formulas taken from http://www.venture-ware.com/kevin/coding/lets-learn-math-photoshop-blend-modes/
     */




    var blend = function (bottom, top, mode) {
        if (!blend[mode]) {
            throw new Error('unknown blend mode ' + mode);
        }
        return blend[mode](bottom, top);
    };

    var blend_f = function (f) { return function (bottom,top) {
            var c0 = chroma_1(top).rgb();
            var c1 = chroma_1(bottom).rgb();
            return chroma_1.rgb(f(c0, c1));
        }; };

    var each = function (f) { return function (c0, c1) {
            var out = [];
            out[0] = f(c0[0], c1[0]);
            out[1] = f(c0[1], c1[1]);
            out[2] = f(c0[2], c1[2]);
            return out;
        }; };

    var normal = function (a) { return a; };
    var multiply = function (a,b) { return a * b / 255; };
    var darken$1 = function (a,b) { return a > b ? b : a; };
    var lighten = function (a,b) { return a > b ? a : b; };
    var screen = function (a,b) { return 255 * (1 - (1-a/255) * (1-b/255)); };
    var overlay = function (a,b) { return b < 128 ? 2 * a * b / 255 : 255 * (1 - 2 * (1 - a / 255 ) * ( 1 - b / 255 )); };
    var burn = function (a,b) { return 255 * (1 - (1 - b / 255) / (a/255)); };
    var dodge = function (a,b) {
        if (a === 255) { return 255; }
        a = 255 * (b / 255) / (1 - a / 255);
        return a > 255 ? 255 : a
    };

    // # add = (a,b) ->
    // #     if (a + b > 255) then 255 else a + b

    blend.normal = blend_f(each(normal));
    blend.multiply = blend_f(each(multiply));
    blend.screen = blend_f(each(screen));
    blend.overlay = blend_f(each(overlay));
    blend.darken = blend_f(each(darken$1));
    blend.lighten = blend_f(each(lighten));
    blend.dodge = blend_f(each(dodge));
    blend.burn = blend_f(each(burn));
    // blend.add = blend_f(each(add));

    var blend_1 = blend;

    // cubehelix interpolation
    // based on D.A. Green "A colour scheme for the display of astronomical intensity images"
    // http://astron-soc.in/bulletin/11June/289392011.pdf

    var type$k = utils.type;
    var clip_rgb$3 = utils.clip_rgb;
    var TWOPI$2 = utils.TWOPI;
    var pow$6 = Math.pow;
    var sin$2 = Math.sin;
    var cos$3 = Math.cos;


    var cubehelix = function(start, rotations, hue, gamma, lightness) {
        if ( start === void 0 ) start=300;
        if ( rotations === void 0 ) rotations=-1.5;
        if ( hue === void 0 ) hue=1;
        if ( gamma === void 0 ) gamma=1;
        if ( lightness === void 0 ) lightness=[0,1];

        var dh = 0, dl;
        if (type$k(lightness) === 'array') {
            dl = lightness[1] - lightness[0];
        } else {
            dl = 0;
            lightness = [lightness, lightness];
        }

        var f = function(fract) {
            var a = TWOPI$2 * (((start+120)/360) + (rotations * fract));
            var l = pow$6(lightness[0] + (dl * fract), gamma);
            var h = dh !== 0 ? hue[0] + (fract * dh) : hue;
            var amp = (h * l * (1-l)) / 2;
            var cos_a = cos$3(a);
            var sin_a = sin$2(a);
            var r = l + (amp * ((-0.14861 * cos_a) + (1.78277* sin_a)));
            var g = l + (amp * ((-0.29227 * cos_a) - (0.90649* sin_a)));
            var b = l + (amp * (+1.97294 * cos_a));
            return chroma_1(clip_rgb$3([r*255,g*255,b*255,1]));
        };

        f.start = function(s) {
            if ((s == null)) { return start; }
            start = s;
            return f;
        };

        f.rotations = function(r) {
            if ((r == null)) { return rotations; }
            rotations = r;
            return f;
        };

        f.gamma = function(g) {
            if ((g == null)) { return gamma; }
            gamma = g;
            return f;
        };

        f.hue = function(h) {
            if ((h == null)) { return hue; }
            hue = h;
            if (type$k(hue) === 'array') {
                dh = hue[1] - hue[0];
                if (dh === 0) { hue = hue[1]; }
            } else {
                dh = 0;
            }
            return f;
        };

        f.lightness = function(h) {
            if ((h == null)) { return lightness; }
            if (type$k(h) === 'array') {
                lightness = h;
                dl = h[1] - h[0];
            } else {
                lightness = [h,h];
                dl = 0;
            }
            return f;
        };

        f.scale = function () { return chroma_1.scale(f); };

        f.hue(hue);

        return f;
    };

    var digits = '0123456789abcdef';

    var floor$2 = Math.floor;
    var random = Math.random;

    var random_1 = function () {
        var code = '#';
        for (var i=0; i<6; i++) {
            code += digits.charAt(floor$2(random() * 16));
        }
        return new Color_1(code, 'hex');
    };

    var log$1 = Math.log;
    var pow$7 = Math.pow;
    var floor$3 = Math.floor;
    var abs = Math.abs;


    var analyze = function (data, key) {
        if ( key === void 0 ) key=null;

        var r = {
            min: Number.MAX_VALUE,
            max: Number.MAX_VALUE*-1,
            sum: 0,
            values: [],
            count: 0
        };
        if (type(data) === 'object') {
            data = Object.values(data);
        }
        data.forEach(function (val) {
            if (key && type(val) === 'object') { val = val[key]; }
            if (val !== undefined && val !== null && !isNaN(val)) {
                r.values.push(val);
                r.sum += val;
                if (val < r.min) { r.min = val; }
                if (val > r.max) { r.max = val; }
                r.count += 1;
            }
        });

        r.domain = [r.min, r.max];

        r.limits = function (mode, num) { return limits(r, mode, num); };

        return r;
    };


    var limits = function (data, mode, num) {
        if ( mode === void 0 ) mode='equal';
        if ( num === void 0 ) num=7;

        if (type(data) == 'array') {
            data = analyze(data);
        }
        var min = data.min;
        var max = data.max;
        var values = data.values.sort(function (a,b) { return a-b; });

        if (num === 1) { return [min,max]; }

        var limits = [];

        if (mode.substr(0,1) === 'c') { // continuous
            limits.push(min);
            limits.push(max);
        }

        if (mode.substr(0,1) === 'e') { // equal interval
            limits.push(min);
            for (var i=1; i<num; i++) {
                limits.push(min+((i/num)*(max-min)));
            }
            limits.push(max);
        }

        else if (mode.substr(0,1) === 'l') { // log scale
            if (min <= 0) {
                throw new Error('Logarithmic scales are only possible for values > 0');
            }
            var min_log = Math.LOG10E * log$1(min);
            var max_log = Math.LOG10E * log$1(max);
            limits.push(min);
            for (var i$1=1; i$1<num; i$1++) {
                limits.push(pow$7(10, min_log + ((i$1/num) * (max_log - min_log))));
            }
            limits.push(max);
        }

        else if (mode.substr(0,1) === 'q') { // quantile scale
            limits.push(min);
            for (var i$2=1; i$2<num; i$2++) {
                var p = ((values.length-1) * i$2)/num;
                var pb = floor$3(p);
                if (pb === p) {
                    limits.push(values[pb]);
                } else { // p > pb
                    var pr = p - pb;
                    limits.push((values[pb]*(1-pr)) + (values[pb+1]*pr));
                }
            }
            limits.push(max);

        }

        else if (mode.substr(0,1) === 'k') { // k-means clustering
            /*
            implementation based on
            http://code.google.com/p/figue/source/browse/trunk/figue.js#336
            simplified for 1-d input values
            */
            var cluster;
            var n = values.length;
            var assignments = new Array(n);
            var clusterSizes = new Array(num);
            var repeat = true;
            var nb_iters = 0;
            var centroids = null;

            // get seed values
            centroids = [];
            centroids.push(min);
            for (var i$3=1; i$3<num; i$3++) {
                centroids.push(min + ((i$3/num) * (max-min)));
            }
            centroids.push(max);

            while (repeat) {
                // assignment step
                for (var j=0; j<num; j++) {
                    clusterSizes[j] = 0;
                }
                for (var i$4=0; i$4<n; i$4++) {
                    var value = values[i$4];
                    var mindist = Number.MAX_VALUE;
                    var best = (void 0);
                    for (var j$1=0; j$1<num; j$1++) {
                        var dist = abs(centroids[j$1]-value);
                        if (dist < mindist) {
                            mindist = dist;
                            best = j$1;
                        }
                        clusterSizes[best]++;
                        assignments[i$4] = best;
                    }
                }

                // update centroids step
                var newCentroids = new Array(num);
                for (var j$2=0; j$2<num; j$2++) {
                    newCentroids[j$2] = null;
                }
                for (var i$5=0; i$5<n; i$5++) {
                    cluster = assignments[i$5];
                    if (newCentroids[cluster] === null) {
                        newCentroids[cluster] = values[i$5];
                    } else {
                        newCentroids[cluster] += values[i$5];
                    }
                }
                for (var j$3=0; j$3<num; j$3++) {
                    newCentroids[j$3] *= 1/clusterSizes[j$3];
                }

                // check convergence
                repeat = false;
                for (var j$4=0; j$4<num; j$4++) {
                    if (newCentroids[j$4] !== centroids[j$4]) {
                        repeat = true;
                        break;
                    }
                }

                centroids = newCentroids;
                nb_iters++;

                if (nb_iters > 200) {
                    repeat = false;
                }
            }

            // finished k-means clustering
            // the next part is borrowed from gabrielflor.it
            var kClusters = {};
            for (var j$5=0; j$5<num; j$5++) {
                kClusters[j$5] = [];
            }
            for (var i$6=0; i$6<n; i$6++) {
                cluster = assignments[i$6];
                kClusters[cluster].push(values[i$6]);
            }
            var tmpKMeansBreaks = [];
            for (var j$6=0; j$6<num; j$6++) {
                tmpKMeansBreaks.push(kClusters[j$6][0]);
                tmpKMeansBreaks.push(kClusters[j$6][kClusters[j$6].length-1]);
            }
            tmpKMeansBreaks = tmpKMeansBreaks.sort(function (a,b){ return a-b; });
            limits.push(tmpKMeansBreaks[0]);
            for (var i$7=1; i$7 < tmpKMeansBreaks.length; i$7+= 2) {
                var v = tmpKMeansBreaks[i$7];
                if (!isNaN(v) && (limits.indexOf(v) === -1)) {
                    limits.push(v);
                }
            }
        }
        return limits;
    };

    var analyze_1 = {analyze: analyze, limits: limits};

    var contrast = function (a, b) {
        // WCAG contrast ratio
        // see http://www.w3.org/TR/2008/REC-WCAG20-20081211/#contrast-ratiodef
        a = new Color_1(a);
        b = new Color_1(b);
        var l1 = a.luminance();
        var l2 = b.luminance();
        return l1 > l2 ? (l1 + 0.05) / (l2 + 0.05) : (l2 + 0.05) / (l1 + 0.05);
    };

    var sqrt$4 = Math.sqrt;
    var atan2$2 = Math.atan2;
    var abs$1 = Math.abs;
    var cos$4 = Math.cos;
    var PI$2 = Math.PI;

    var deltaE = function(a, b, L, C) {
        if ( L === void 0 ) L=1;
        if ( C === void 0 ) C=1;

        // Delta E (CMC)
        // see http://www.brucelindbloom.com/index.html?Eqn_DeltaE_CMC.html
        a = new Color_1(a);
        b = new Color_1(b);
        var ref = Array.from(a.lab());
        var L1 = ref[0];
        var a1 = ref[1];
        var b1 = ref[2];
        var ref$1 = Array.from(b.lab());
        var L2 = ref$1[0];
        var a2 = ref$1[1];
        var b2 = ref$1[2];
        var c1 = sqrt$4((a1 * a1) + (b1 * b1));
        var c2 = sqrt$4((a2 * a2) + (b2 * b2));
        var sl = L1 < 16.0 ? 0.511 : (0.040975 * L1) / (1.0 + (0.01765 * L1));
        var sc = ((0.0638 * c1) / (1.0 + (0.0131 * c1))) + 0.638;
        var h1 = c1 < 0.000001 ? 0.0 : (atan2$2(b1, a1) * 180.0) / PI$2;
        while (h1 < 0) { h1 += 360; }
        while (h1 >= 360) { h1 -= 360; }
        var t = (h1 >= 164.0) && (h1 <= 345.0) ? (0.56 + abs$1(0.2 * cos$4((PI$2 * (h1 + 168.0)) / 180.0))) : (0.36 + abs$1(0.4 * cos$4((PI$2 * (h1 + 35.0)) / 180.0)));
        var c4 = c1 * c1 * c1 * c1;
        var f = sqrt$4(c4 / (c4 + 1900.0));
        var sh = sc * (((f * t) + 1.0) - f);
        var delL = L1 - L2;
        var delC = c1 - c2;
        var delA = a1 - a2;
        var delB = b1 - b2;
        var dH2 = ((delA * delA) + (delB * delB)) - (delC * delC);
        var v1 = delL / (L * sl);
        var v2 = delC / (C * sc);
        var v3 = sh;
        return sqrt$4((v1 * v1) + (v2 * v2) + (dH2 / (v3 * v3)));
    };

    // simple Euclidean distance
    var distance = function(a, b, mode) {
        if ( mode === void 0 ) mode='lab';

        // Delta E (CIE 1976)
        // see http://www.brucelindbloom.com/index.html?Equations.html
        a = new Color_1(a);
        b = new Color_1(b);
        var l1 = a.get(mode);
        var l2 = b.get(mode);
        var sum_sq = 0;
        for (var i in l1) {
            var d = (l1[i] || 0) - (l2[i] || 0);
            sum_sq += d*d;
        }
        return Math.sqrt(sum_sq);
    };

    var valid = function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        try {
            new (Function.prototype.bind.apply( Color_1, [ null ].concat( args) ));
            return true;
        } catch (e) {
            return false;
        }
    };

    // some pre-defined color scales:




    var scales = {
    	cool: function cool() { return scale([chroma_1.hsl(180,1,.9), chroma_1.hsl(250,.7,.4)]) },
    	hot: function hot() { return scale(['#000','#f00','#ff0','#fff'], [0,.25,.75,1]).mode('rgb') }
    };

    /**
        ColorBrewer colors for chroma.js

        Copyright (c) 2002 Cynthia Brewer, Mark Harrower, and The
        Pennsylvania State University.

        Licensed under the Apache License, Version 2.0 (the "License");
        you may not use this file except in compliance with the License.
        You may obtain a copy of the License at
        http://www.apache.org/licenses/LICENSE-2.0

        Unless required by applicable law or agreed to in writing, software distributed
        under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
        CONDITIONS OF ANY KIND, either express or implied. See the License for the
        specific language governing permissions and limitations under the License.
    */

    var colorbrewer = {
        // sequential
        OrRd: ['#fff7ec', '#fee8c8', '#fdd49e', '#fdbb84', '#fc8d59', '#ef6548', '#d7301f', '#b30000', '#7f0000'],
        PuBu: ['#fff7fb', '#ece7f2', '#d0d1e6', '#a6bddb', '#74a9cf', '#3690c0', '#0570b0', '#045a8d', '#023858'],
        BuPu: ['#f7fcfd', '#e0ecf4', '#bfd3e6', '#9ebcda', '#8c96c6', '#8c6bb1', '#88419d', '#810f7c', '#4d004b'],
        Oranges: ['#fff5eb', '#fee6ce', '#fdd0a2', '#fdae6b', '#fd8d3c', '#f16913', '#d94801', '#a63603', '#7f2704'],
        BuGn: ['#f7fcfd', '#e5f5f9', '#ccece6', '#99d8c9', '#66c2a4', '#41ae76', '#238b45', '#006d2c', '#00441b'],
        YlOrBr: ['#ffffe5', '#fff7bc', '#fee391', '#fec44f', '#fe9929', '#ec7014', '#cc4c02', '#993404', '#662506'],
        YlGn: ['#ffffe5', '#f7fcb9', '#d9f0a3', '#addd8e', '#78c679', '#41ab5d', '#238443', '#006837', '#004529'],
        Reds: ['#fff5f0', '#fee0d2', '#fcbba1', '#fc9272', '#fb6a4a', '#ef3b2c', '#cb181d', '#a50f15', '#67000d'],
        RdPu: ['#fff7f3', '#fde0dd', '#fcc5c0', '#fa9fb5', '#f768a1', '#dd3497', '#ae017e', '#7a0177', '#49006a'],
        Greens: ['#f7fcf5', '#e5f5e0', '#c7e9c0', '#a1d99b', '#74c476', '#41ab5d', '#238b45', '#006d2c', '#00441b'],
        YlGnBu: ['#ffffd9', '#edf8b1', '#c7e9b4', '#7fcdbb', '#41b6c4', '#1d91c0', '#225ea8', '#253494', '#081d58'],
        Purples: ['#fcfbfd', '#efedf5', '#dadaeb', '#bcbddc', '#9e9ac8', '#807dba', '#6a51a3', '#54278f', '#3f007d'],
        GnBu: ['#f7fcf0', '#e0f3db', '#ccebc5', '#a8ddb5', '#7bccc4', '#4eb3d3', '#2b8cbe', '#0868ac', '#084081'],
        Greys: ['#ffffff', '#f0f0f0', '#d9d9d9', '#bdbdbd', '#969696', '#737373', '#525252', '#252525', '#000000'],
        YlOrRd: ['#ffffcc', '#ffeda0', '#fed976', '#feb24c', '#fd8d3c', '#fc4e2a', '#e31a1c', '#bd0026', '#800026'],
        PuRd: ['#f7f4f9', '#e7e1ef', '#d4b9da', '#c994c7', '#df65b0', '#e7298a', '#ce1256', '#980043', '#67001f'],
        Blues: ['#f7fbff', '#deebf7', '#c6dbef', '#9ecae1', '#6baed6', '#4292c6', '#2171b5', '#08519c', '#08306b'],
        PuBuGn: ['#fff7fb', '#ece2f0', '#d0d1e6', '#a6bddb', '#67a9cf', '#3690c0', '#02818a', '#016c59', '#014636'],
        Viridis: ['#440154', '#482777', '#3f4a8a', '#31678e', '#26838f', '#1f9d8a', '#6cce5a', '#b6de2b', '#fee825'],

        // diverging

        Spectral: ['#9e0142', '#d53e4f', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#e6f598', '#abdda4', '#66c2a5', '#3288bd', '#5e4fa2'],
        RdYlGn: ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#d9ef8b', '#a6d96a', '#66bd63', '#1a9850', '#006837'],
        RdBu: ['#67001f', '#b2182b', '#d6604d', '#f4a582', '#fddbc7', '#f7f7f7', '#d1e5f0', '#92c5de', '#4393c3', '#2166ac', '#053061'],
        PiYG: ['#8e0152', '#c51b7d', '#de77ae', '#f1b6da', '#fde0ef', '#f7f7f7', '#e6f5d0', '#b8e186', '#7fbc41', '#4d9221', '#276419'],
        PRGn: ['#40004b', '#762a83', '#9970ab', '#c2a5cf', '#e7d4e8', '#f7f7f7', '#d9f0d3', '#a6dba0', '#5aae61', '#1b7837', '#00441b'],
        RdYlBu: ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee090', '#ffffbf', '#e0f3f8', '#abd9e9', '#74add1', '#4575b4', '#313695'],
        BrBG: ['#543005', '#8c510a', '#bf812d', '#dfc27d', '#f6e8c3', '#f5f5f5', '#c7eae5', '#80cdc1', '#35978f', '#01665e', '#003c30'],
        RdGy: ['#67001f', '#b2182b', '#d6604d', '#f4a582', '#fddbc7', '#ffffff', '#e0e0e0', '#bababa', '#878787', '#4d4d4d', '#1a1a1a'],
        PuOr: ['#7f3b08', '#b35806', '#e08214', '#fdb863', '#fee0b6', '#f7f7f7', '#d8daeb', '#b2abd2', '#8073ac', '#542788', '#2d004b'],

        // qualitative

        Set2: ['#66c2a5', '#fc8d62', '#8da0cb', '#e78ac3', '#a6d854', '#ffd92f', '#e5c494', '#b3b3b3'],
        Accent: ['#7fc97f', '#beaed4', '#fdc086', '#ffff99', '#386cb0', '#f0027f', '#bf5b17', '#666666'],
        Set1: ['#e41a1c', '#377eb8', '#4daf4a', '#984ea3', '#ff7f00', '#ffff33', '#a65628', '#f781bf', '#999999'],
        Set3: ['#8dd3c7', '#ffffb3', '#bebada', '#fb8072', '#80b1d3', '#fdb462', '#b3de69', '#fccde5', '#d9d9d9', '#bc80bd', '#ccebc5', '#ffed6f'],
        Dark2: ['#1b9e77', '#d95f02', '#7570b3', '#e7298a', '#66a61e', '#e6ab02', '#a6761d', '#666666'],
        Paired: ['#a6cee3', '#1f78b4', '#b2df8a', '#33a02c', '#fb9a99', '#e31a1c', '#fdbf6f', '#ff7f00', '#cab2d6', '#6a3d9a', '#ffff99', '#b15928'],
        Pastel2: ['#b3e2cd', '#fdcdac', '#cbd5e8', '#f4cae4', '#e6f5c9', '#fff2ae', '#f1e2cc', '#cccccc'],
        Pastel1: ['#fbb4ae', '#b3cde3', '#ccebc5', '#decbe4', '#fed9a6', '#ffffcc', '#e5d8bd', '#fddaec', '#f2f2f2'],
    };

    // add lowercase aliases for case-insensitive matches
    for (var i$1 = 0, list$1 = Object.keys(colorbrewer); i$1 < list$1.length; i$1 += 1) {
        var key = list$1[i$1];

        colorbrewer[key.toLowerCase()] = colorbrewer[key];
    }

    var colorbrewer_1 = colorbrewer;

    // feel free to comment out anything to rollup
    // a smaller chroma.js built

    // io --> convert colors















    // operators --> modify existing Colors










    // interpolators










    // generators -- > create new colors
    chroma_1.average = average;
    chroma_1.bezier = bezier_1;
    chroma_1.blend = blend_1;
    chroma_1.cubehelix = cubehelix;
    chroma_1.mix = chroma_1.interpolate = mix;
    chroma_1.random = random_1;
    chroma_1.scale = scale;

    // other utility methods
    chroma_1.analyze = analyze_1.analyze;
    chroma_1.contrast = contrast;
    chroma_1.deltaE = deltaE;
    chroma_1.distance = distance;
    chroma_1.limits = analyze_1.limits;
    chroma_1.valid = valid;

    // scale
    chroma_1.scales = scales;

    // colors
    chroma_1.colors = w3cx11_1;
    chroma_1.brewer = colorbrewer_1;

    var chroma_js = chroma_1;

    return chroma_js;

})));


/***/ }),

/***/ 88:
/***/ (function(module) {

module.exports =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __nested_webpack_require_187__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __nested_webpack_require_187__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__nested_webpack_require_187__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__nested_webpack_require_187__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__nested_webpack_require_187__.d = function(exports, name, getter) {
/******/ 		if(!__nested_webpack_require_187__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__nested_webpack_require_187__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__nested_webpack_require_187__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__nested_webpack_require_187__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__nested_webpack_require_187__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __nested_webpack_require_187__(__nested_webpack_require_187__.s = 1);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var DEVICE_TYPES = {
  MOBILE: "mobile",
  TABLET: "tablet",
  SMART_TV: "smarttv",
  CONSOLE: "console",
  WEARABLE: "wearable",
  BROWSER: undefined
};

var BROWSER_TYPES = {
  CHROME: "Chrome",
  FIREFOX: "Firefox",
  OPERA: "Opera",
  YANDEX: "Yandex",
  SAFARI: "Safari",
  INTERNET_EXPLORER: "Internet Explorer",
  EDGE: "Edge",
  CHROMIUM: "Chromium",
  IE: "IE",
  MOBILE_SAFARI: "Mobile Safari",
  EDGE_CHROMIUM: "Edge Chromium"
};

var OS_TYPES = {
  IOS: "iOS",
  ANDROID: "Android",
  WINDOWS_PHONE: "Windows Phone",
  WINDOWS: "Windows",
  MAC_OS: "Mac OS"
};

var defaultData = {
  isMobile: false,
  isTablet: false,
  isBrowser: false,
  isSmartTV: false,
  isConsole: false,
  isWearable: false
};

module.exports = { BROWSER_TYPES: BROWSER_TYPES, DEVICE_TYPES: DEVICE_TYPES, OS_TYPES: OS_TYPES, defaultData: defaultData };

/***/ }),
/* 1 */
/***/ (function(module, exports, __nested_webpack_require_3397__) {

"use strict";


var UAParser = __nested_webpack_require_3397__(2);

var _require = __nested_webpack_require_3397__(0),
    BROWSER_TYPES = _require.BROWSER_TYPES,
    OS_TYPES = _require.OS_TYPES,
    DEVICE_TYPES = _require.DEVICE_TYPES;

var _require2 = __nested_webpack_require_3397__(4),
    checkType = _require2.checkType,
    broPayload = _require2.broPayload,
    mobilePayload = _require2.mobilePayload,
    wearPayload = _require2.wearPayload,
    consolePayload = _require2.consolePayload,
    stvPayload = _require2.stvPayload,
    getNavigatorInstance = _require2.getNavigatorInstance,
    isIOS13Check = _require2.isIOS13Check;

var UA = new UAParser();

var browser = UA.getBrowser();
var device = UA.getDevice();
var engine = UA.getEngine();
var os = UA.getOS();
var ua = UA.getUA();

var CHROME = BROWSER_TYPES.CHROME,
    CHROMIUM = BROWSER_TYPES.CHROMIUM,
    IE = BROWSER_TYPES.IE,
    INTERNET_EXPLORER = BROWSER_TYPES.INTERNET_EXPLORER,
    OPERA = BROWSER_TYPES.OPERA,
    FIREFOX = BROWSER_TYPES.FIREFOX,
    SAFARI = BROWSER_TYPES.SAFARI,
    EDGE = BROWSER_TYPES.EDGE,
    YANDEX = BROWSER_TYPES.YANDEX,
    MOBILE_SAFARI = BROWSER_TYPES.MOBILE_SAFARI;
var MOBILE = DEVICE_TYPES.MOBILE,
    TABLET = DEVICE_TYPES.TABLET,
    SMART_TV = DEVICE_TYPES.SMART_TV,
    BROWSER = DEVICE_TYPES.BROWSER,
    WEARABLE = DEVICE_TYPES.WEARABLE,
    CONSOLE = DEVICE_TYPES.CONSOLE;
var ANDROID = OS_TYPES.ANDROID,
    WINDOWS_PHONE = OS_TYPES.WINDOWS_PHONE,
    IOS = OS_TYPES.IOS,
    WINDOWS = OS_TYPES.WINDOWS,
    MAC_OS = OS_TYPES.MAC_OS;


var isMobileType = function isMobileType() {
  return device.type === MOBILE;
};
var isTabletType = function isTabletType() {
  return device.type === TABLET;
};

var isMobileAndTabletType = function isMobileAndTabletType() {
  switch (device.type) {
    case MOBILE:
    case TABLET:
      return true;
    default:
      return false;
  }
};

var isEdgeChromiumType = function isEdgeChromiumType() {
  if (os.name === OS_TYPES.WINDOWS && os.version === '10') {
    return typeof ua === 'string' && ua.indexOf('Edg/') !== -1;
  }

  return false;
};

var isSmartTVType = function isSmartTVType() {
  return device.type === SMART_TV;
};
var isBrowserType = function isBrowserType() {
  return device.type === BROWSER;
};
var isWearableType = function isWearableType() {
  return device.type === WEARABLE;
};
var isConsoleType = function isConsoleType() {
  return device.type === CONSOLE;
};
var isAndroidType = function isAndroidType() {
  return os.name === ANDROID;
};
var isWindowsType = function isWindowsType() {
  return os.name === WINDOWS;
};
var isMacOsType = function isMacOsType() {
  return os.name === MAC_OS;
};
var isWinPhoneType = function isWinPhoneType() {
  return os.name === WINDOWS_PHONE;
};
var isIOSType = function isIOSType() {
  return os.name === IOS;
};
var isChromeType = function isChromeType() {
  return browser.name === CHROME;
};
var isFirefoxType = function isFirefoxType() {
  return browser.name === FIREFOX;
};
var isChromiumType = function isChromiumType() {
  return browser.name === CHROMIUM;
};
var isEdgeType = function isEdgeType() {
  return browser.name === EDGE;
};
var isYandexType = function isYandexType() {
  return browser.name === YANDEX;
};
var isSafariType = function isSafariType() {
  return browser.name === SAFARI || browser.name === MOBILE_SAFARI;
};

var isMobileSafariType = function isMobileSafariType() {
  return browser.name === MOBILE_SAFARI;
};
var isOperaType = function isOperaType() {
  return browser.name === OPERA;
};
var isIEType = function isIEType() {
  return browser.name === INTERNET_EXPLORER || browser.name === IE;
};

var isElectronType = function isElectronType() {
  var nav = getNavigatorInstance();
  var ua = nav && nav.userAgent.toLowerCase();

  return typeof ua === 'string' ? /electron/.test(ua) : false;
};

var getIOS13 = function getIOS13() {
  var nav = getNavigatorInstance();
  return nav && (/iPad|iPhone|iPod/.test(nav.platform) || nav.platform === 'MacIntel' && nav.maxTouchPoints > 1) && !window.MSStream;
};

var getIPad13 = function getIPad13() {
  return isIOS13Check('iPad');
};
var getIphone13 = function getIphone13() {
  return isIOS13Check('iPhone');
};
var getIPod13 = function getIPod13() {
  return isIOS13Check('iPod');
};

var getBrowserFullVersion = function getBrowserFullVersion() {
  return browser.major;
};
var getBrowserVersion = function getBrowserVersion() {
  return browser.version;
};
var getOsVersion = function getOsVersion() {
  return os.version ? os.version : "none";
};
var getOsName = function getOsName() {
  return os.name ? os.name : "none";
};
var getBrowserName = function getBrowserName() {
  return browser.name;
};
var getMobileVendor = function getMobileVendor() {
  return device.vendor ? device.vendor : "none";
};
var getMobileModel = function getMobileModel() {
  return device.model ? device.model : "none";
};
var getEngineName = function getEngineName() {
  return engine.name;
};
var getEngineVersion = function getEngineVersion() {
  return engine.version;
};
var getUseragent = function getUseragent() {
  return ua;
};
var getDeviceType = function getDeviceType() {
  return device.type;
};

var isSmartTV = isSmartTVType();
var isConsole = isConsoleType();
var isWearable = isWearableType();
var isMobileSafari = isMobileSafariType() || getIPad13();
var isChromium = isChromiumType();
var isMobile = isMobileAndTabletType() || getIPad13();
var isMobileOnly = isMobileType();
var isTablet = isTabletType() || getIPad13();
var isBrowser = isBrowserType();
var isAndroid = isAndroidType();
var isWinPhone = isWinPhoneType();
var isIOS = isIOSType() || getIPad13();
var isChrome = isChromeType();
var isFirefox = isFirefoxType();
var isSafari = isSafariType();
var isOpera = isOperaType();
var isIE = isIEType();
var osVersion = getOsVersion();
var osName = getOsName();
var fullBrowserVersion = getBrowserFullVersion();
var browserVersion = getBrowserVersion();
var browserName = getBrowserName();
var mobileVendor = getMobileVendor();
var mobileModel = getMobileModel();
var engineName = getEngineName();
var engineVersion = getEngineVersion();
var getUA = getUseragent();
var isEdge = isEdgeType() || isEdgeChromiumType();
var isYandex = isYandexType();
var deviceType = getDeviceType();
var isIOS13 = getIOS13();
var isIPad13 = getIPad13();
var isIPhone13 = getIphone13();
var isIPod13 = getIPod13();
var isElectron = isElectronType();
var isEdgeChromium = isEdgeChromiumType();
var isLegacyEdge = isEdgeType();
var isWindows = isWindowsType();
var isMacOs = isMacOsType();

var type = checkType(device.type);

function deviceDetect() {
  var isBrowser = type.isBrowser,
      isMobile = type.isMobile,
      isTablet = type.isTablet,
      isSmartTV = type.isSmartTV,
      isConsole = type.isConsole,
      isWearable = type.isWearable;

  if (isBrowser) {
    return broPayload(isBrowser, browser, engine, os, ua);
  }

  if (isSmartTV) {
    return stvPayload(isSmartTV, engine, os, ua);
  }

  if (isConsole) {
    return consolePayload(isConsole, engine, os, ua);
  }

  if (isMobile) {
    return mobilePayload(type, device, os, ua);
  }

  if (isTablet) {
    return mobilePayload(type, device, os, ua);
  }

  if (isWearable) {
    return wearPayload(isWearable, engine, os, ua);
  }
};

module.exports = {
  deviceDetect: deviceDetect,
  isSmartTV: isSmartTV,
  isConsole: isConsole,
  isWearable: isWearable,
  isMobileSafari: isMobileSafari,
  isChromium: isChromium,
  isMobile: isMobile,
  isMobileOnly: isMobileOnly,
  isTablet: isTablet,
  isBrowser: isBrowser,
  isAndroid: isAndroid,
  isWinPhone: isWinPhone,
  isIOS: isIOS,
  isChrome: isChrome,
  isFirefox: isFirefox,
  isSafari: isSafari,
  isOpera: isOpera,
  isIE: isIE,
  osVersion: osVersion,
  osName: osName,
  fullBrowserVersion: fullBrowserVersion,
  browserVersion: browserVersion,
  browserName: browserName,
  mobileVendor: mobileVendor,
  mobileModel: mobileModel,
  engineName: engineName,
  engineVersion: engineVersion,
  getUA: getUA,
  isEdge: isEdge,
  isYandex: isYandex,
  deviceType: deviceType,
  isIOS13: isIOS13,
  isIPad13: isIPad13,
  isIPhone13: isIPhone13,
  isIPod13: isIPod13,
  isElectron: isElectron,
  isEdgeChromium: isEdgeChromium,
  isLegacyEdge: isLegacyEdge,
  isWindows: isWindows,
  isMacOs: isMacOs
};

/***/ }),
/* 2 */
/***/ (function(module, exports, __nested_webpack_require_11768__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/*!
 * UAParser.js v0.7.18
 * Lightweight JavaScript-based User-Agent string parser
 * https://github.com/faisalman/ua-parser-js
 *
 * Copyright © 2012-2016 Faisal Salman <fyzlman@gmail.com>
 * Dual licensed under GPLv2 or MIT
 */
(function(window,undefined){"use strict";var LIBVERSION="0.7.18",EMPTY="",UNKNOWN="?",FUNC_TYPE="function",UNDEF_TYPE="undefined",OBJ_TYPE="object",STR_TYPE="string",MAJOR="major",MODEL="model",NAME="name",TYPE="type",VENDOR="vendor",VERSION="version",ARCHITECTURE="architecture",CONSOLE="console",MOBILE="mobile",TABLET="tablet",SMARTTV="smarttv",WEARABLE="wearable",EMBEDDED="embedded";var util={extend:function(regexes,extensions){var margedRegexes={};for(var i in regexes){if(extensions[i]&&extensions[i].length%2===0){margedRegexes[i]=extensions[i].concat(regexes[i])}else{margedRegexes[i]=regexes[i]}}return margedRegexes},has:function(str1,str2){if(typeof str1==="string"){return str2.toLowerCase().indexOf(str1.toLowerCase())!==-1}else{return false}},lowerize:function(str){return str.toLowerCase()},major:function(version){return typeof version===STR_TYPE?version.replace(/[^\d\.]/g,"").split(".")[0]:undefined},trim:function(str){return str.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"")}};var mapper={rgx:function(ua,arrays){var i=0,j,k,p,q,matches,match;while(i<arrays.length&&!matches){var regex=arrays[i],props=arrays[i+1];j=k=0;while(j<regex.length&&!matches){matches=regex[j++].exec(ua);if(!!matches){for(p=0;p<props.length;p++){match=matches[++k];q=props[p];if(typeof q===OBJ_TYPE&&q.length>0){if(q.length==2){if(typeof q[1]==FUNC_TYPE){this[q[0]]=q[1].call(this,match)}else{this[q[0]]=q[1]}}else if(q.length==3){if(typeof q[1]===FUNC_TYPE&&!(q[1].exec&&q[1].test)){this[q[0]]=match?q[1].call(this,match,q[2]):undefined}else{this[q[0]]=match?match.replace(q[1],q[2]):undefined}}else if(q.length==4){this[q[0]]=match?q[3].call(this,match.replace(q[1],q[2])):undefined}}else{this[q]=match?match:undefined}}}}i+=2}},str:function(str,map){for(var i in map){if(typeof map[i]===OBJ_TYPE&&map[i].length>0){for(var j=0;j<map[i].length;j++){if(util.has(map[i][j],str)){return i===UNKNOWN?undefined:i}}}else if(util.has(map[i],str)){return i===UNKNOWN?undefined:i}}return str}};var maps={browser:{oldsafari:{version:{"1.0":"/8",1.2:"/1",1.3:"/3","2.0":"/412","2.0.2":"/416","2.0.3":"/417","2.0.4":"/419","?":"/"}}},device:{amazon:{model:{"Fire Phone":["SD","KF"]}},sprint:{model:{"Evo Shift 4G":"7373KT"},vendor:{HTC:"APA",Sprint:"Sprint"}}},os:{windows:{version:{ME:"4.90","NT 3.11":"NT3.51","NT 4.0":"NT4.0",2000:"NT 5.0",XP:["NT 5.1","NT 5.2"],Vista:"NT 6.0",7:"NT 6.1",8:"NT 6.2",8.1:"NT 6.3",10:["NT 6.4","NT 10.0"],RT:"ARM"}}}};var regexes={browser:[[/(opera\smini)\/([\w\.-]+)/i,/(opera\s[mobiletab]+).+version\/([\w\.-]+)/i,/(opera).+version\/([\w\.]+)/i,/(opera)[\/\s]+([\w\.]+)/i],[NAME,VERSION],[/(opios)[\/\s]+([\w\.]+)/i],[[NAME,"Opera Mini"],VERSION],[/\s(opr)\/([\w\.]+)/i],[[NAME,"Opera"],VERSION],[/(kindle)\/([\w\.]+)/i,/(lunascape|maxthon|netfront|jasmine|blazer)[\/\s]?([\w\.]*)/i,/(avant\s|iemobile|slim|baidu)(?:browser)?[\/\s]?([\w\.]*)/i,/(?:ms|\()(ie)\s([\w\.]+)/i,/(rekonq)\/([\w\.]*)/i,/(chromium|flock|rockmelt|midori|epiphany|silk|skyfire|ovibrowser|bolt|iron|vivaldi|iridium|phantomjs|bowser|quark)\/([\w\.-]+)/i],[NAME,VERSION],[/(trident).+rv[:\s]([\w\.]+).+like\sgecko/i],[[NAME,"IE"],VERSION],[/(edge|edgios|edgea)\/((\d+)?[\w\.]+)/i],[[NAME,"Edge"],VERSION],[/(yabrowser)\/([\w\.]+)/i],[[NAME,"Yandex"],VERSION],[/(puffin)\/([\w\.]+)/i],[[NAME,"Puffin"],VERSION],[/((?:[\s\/])uc?\s?browser|(?:juc.+)ucweb)[\/\s]?([\w\.]+)/i],[[NAME,"UCBrowser"],VERSION],[/(comodo_dragon)\/([\w\.]+)/i],[[NAME,/_/g," "],VERSION],[/(micromessenger)\/([\w\.]+)/i],[[NAME,"WeChat"],VERSION],[/(qqbrowserlite)\/([\w\.]+)/i],[NAME,VERSION],[/(QQ)\/([\d\.]+)/i],[NAME,VERSION],[/m?(qqbrowser)[\/\s]?([\w\.]+)/i],[NAME,VERSION],[/(BIDUBrowser)[\/\s]?([\w\.]+)/i],[NAME,VERSION],[/(2345Explorer)[\/\s]?([\w\.]+)/i],[NAME,VERSION],[/(MetaSr)[\/\s]?([\w\.]+)/i],[NAME],[/(LBBROWSER)/i],[NAME],[/xiaomi\/miuibrowser\/([\w\.]+)/i],[VERSION,[NAME,"MIUI Browser"]],[/;fbav\/([\w\.]+);/i],[VERSION,[NAME,"Facebook"]],[/headlesschrome(?:\/([\w\.]+)|\s)/i],[VERSION,[NAME,"Chrome Headless"]],[/\swv\).+(chrome)\/([\w\.]+)/i],[[NAME,/(.+)/,"$1 WebView"],VERSION],[/((?:oculus|samsung)browser)\/([\w\.]+)/i],[[NAME,/(.+(?:g|us))(.+)/,"$1 $2"],VERSION],[/android.+version\/([\w\.]+)\s+(?:mobile\s?safari|safari)*/i],[VERSION,[NAME,"Android Browser"]],[/(chrome|omniweb|arora|[tizenoka]{5}\s?browser)\/v?([\w\.]+)/i],[NAME,VERSION],[/(dolfin)\/([\w\.]+)/i],[[NAME,"Dolphin"],VERSION],[/((?:android.+)crmo|crios)\/([\w\.]+)/i],[[NAME,"Chrome"],VERSION],[/(coast)\/([\w\.]+)/i],[[NAME,"Opera Coast"],VERSION],[/fxios\/([\w\.-]+)/i],[VERSION,[NAME,"Firefox"]],[/version\/([\w\.]+).+?mobile\/\w+\s(safari)/i],[VERSION,[NAME,"Mobile Safari"]],[/version\/([\w\.]+).+?(mobile\s?safari|safari)/i],[VERSION,NAME],[/webkit.+?(gsa)\/([\w\.]+).+?(mobile\s?safari|safari)(\/[\w\.]+)/i],[[NAME,"GSA"],VERSION],[/webkit.+?(mobile\s?safari|safari)(\/[\w\.]+)/i],[NAME,[VERSION,mapper.str,maps.browser.oldsafari.version]],[/(konqueror)\/([\w\.]+)/i,/(webkit|khtml)\/([\w\.]+)/i],[NAME,VERSION],[/(navigator|netscape)\/([\w\.-]+)/i],[[NAME,"Netscape"],VERSION],[/(swiftfox)/i,/(icedragon|iceweasel|camino|chimera|fennec|maemo\sbrowser|minimo|conkeror)[\/\s]?([\w\.\+]+)/i,/(firefox|seamonkey|k-meleon|icecat|iceape|firebird|phoenix|palemoon|basilisk|waterfox)\/([\w\.-]+)$/i,/(mozilla)\/([\w\.]+).+rv\:.+gecko\/\d+/i,/(polaris|lynx|dillo|icab|doris|amaya|w3m|netsurf|sleipnir)[\/\s]?([\w\.]+)/i,/(links)\s\(([\w\.]+)/i,/(gobrowser)\/?([\w\.]*)/i,/(ice\s?browser)\/v?([\w\._]+)/i,/(mosaic)[\/\s]([\w\.]+)/i],[NAME,VERSION]],cpu:[[/(?:(amd|x(?:(?:86|64)[_-])?|wow|win)64)[;\)]/i],[[ARCHITECTURE,"amd64"]],[/(ia32(?=;))/i],[[ARCHITECTURE,util.lowerize]],[/((?:i[346]|x)86)[;\)]/i],[[ARCHITECTURE,"ia32"]],[/windows\s(ce|mobile);\sppc;/i],[[ARCHITECTURE,"arm"]],[/((?:ppc|powerpc)(?:64)?)(?:\smac|;|\))/i],[[ARCHITECTURE,/ower/,"",util.lowerize]],[/(sun4\w)[;\)]/i],[[ARCHITECTURE,"sparc"]],[/((?:avr32|ia64(?=;))|68k(?=\))|arm(?:64|(?=v\d+;))|(?=atmel\s)avr|(?:irix|mips|sparc)(?:64)?(?=;)|pa-risc)/i],[[ARCHITECTURE,util.lowerize]]],device:[[/\((ipad|playbook);[\w\s\);-]+(rim|apple)/i],[MODEL,VENDOR,[TYPE,TABLET]],[/applecoremedia\/[\w\.]+ \((ipad)/],[MODEL,[VENDOR,"Apple"],[TYPE,TABLET]],[/(apple\s{0,1}tv)/i],[[MODEL,"Apple TV"],[VENDOR,"Apple"]],[/(archos)\s(gamepad2?)/i,/(hp).+(touchpad)/i,/(hp).+(tablet)/i,/(kindle)\/([\w\.]+)/i,/\s(nook)[\w\s]+build\/(\w+)/i,/(dell)\s(strea[kpr\s\d]*[\dko])/i],[VENDOR,MODEL,[TYPE,TABLET]],[/(kf[A-z]+)\sbuild\/.+silk\//i],[MODEL,[VENDOR,"Amazon"],[TYPE,TABLET]],[/(sd|kf)[0349hijorstuw]+\sbuild\/.+silk\//i],[[MODEL,mapper.str,maps.device.amazon.model],[VENDOR,"Amazon"],[TYPE,MOBILE]],[/\((ip[honed|\s\w*]+);.+(apple)/i],[MODEL,VENDOR,[TYPE,MOBILE]],[/\((ip[honed|\s\w*]+);/i],[MODEL,[VENDOR,"Apple"],[TYPE,MOBILE]],[/(blackberry)[\s-]?(\w+)/i,/(blackberry|benq|palm(?=\-)|sonyericsson|acer|asus|dell|meizu|motorola|polytron)[\s_-]?([\w-]*)/i,/(hp)\s([\w\s]+\w)/i,/(asus)-?(\w+)/i],[VENDOR,MODEL,[TYPE,MOBILE]],[/\(bb10;\s(\w+)/i],[MODEL,[VENDOR,"BlackBerry"],[TYPE,MOBILE]],[/android.+(transfo[prime\s]{4,10}\s\w+|eeepc|slider\s\w+|nexus 7|padfone)/i],[MODEL,[VENDOR,"Asus"],[TYPE,TABLET]],[/(sony)\s(tablet\s[ps])\sbuild\//i,/(sony)?(?:sgp.+)\sbuild\//i],[[VENDOR,"Sony"],[MODEL,"Xperia Tablet"],[TYPE,TABLET]],[/android.+\s([c-g]\d{4}|so[-l]\w+)\sbuild\//i],[MODEL,[VENDOR,"Sony"],[TYPE,MOBILE]],[/\s(ouya)\s/i,/(nintendo)\s([wids3u]+)/i],[VENDOR,MODEL,[TYPE,CONSOLE]],[/android.+;\s(shield)\sbuild/i],[MODEL,[VENDOR,"Nvidia"],[TYPE,CONSOLE]],[/(playstation\s[34portablevi]+)/i],[MODEL,[VENDOR,"Sony"],[TYPE,CONSOLE]],[/(sprint\s(\w+))/i],[[VENDOR,mapper.str,maps.device.sprint.vendor],[MODEL,mapper.str,maps.device.sprint.model],[TYPE,MOBILE]],[/(lenovo)\s?(S(?:5000|6000)+(?:[-][\w+]))/i],[VENDOR,MODEL,[TYPE,TABLET]],[/(htc)[;_\s-]+([\w\s]+(?=\))|\w+)*/i,/(zte)-(\w*)/i,/(alcatel|geeksphone|lenovo|nexian|panasonic|(?=;\s)sony)[_\s-]?([\w-]*)/i],[VENDOR,[MODEL,/_/g," "],[TYPE,MOBILE]],[/(nexus\s9)/i],[MODEL,[VENDOR,"HTC"],[TYPE,TABLET]],[/d\/huawei([\w\s-]+)[;\)]/i,/(nexus\s6p)/i],[MODEL,[VENDOR,"Huawei"],[TYPE,MOBILE]],[/(microsoft);\s(lumia[\s\w]+)/i],[VENDOR,MODEL,[TYPE,MOBILE]],[/[\s\(;](xbox(?:\sone)?)[\s\);]/i],[MODEL,[VENDOR,"Microsoft"],[TYPE,CONSOLE]],[/(kin\.[onetw]{3})/i],[[MODEL,/\./g," "],[VENDOR,"Microsoft"],[TYPE,MOBILE]],[/\s(milestone|droid(?:[2-4x]|\s(?:bionic|x2|pro|razr))?:?(\s4g)?)[\w\s]+build\//i,/mot[\s-]?(\w*)/i,/(XT\d{3,4}) build\//i,/(nexus\s6)/i],[MODEL,[VENDOR,"Motorola"],[TYPE,MOBILE]],[/android.+\s(mz60\d|xoom[\s2]{0,2})\sbuild\//i],[MODEL,[VENDOR,"Motorola"],[TYPE,TABLET]],[/hbbtv\/\d+\.\d+\.\d+\s+\([\w\s]*;\s*(\w[^;]*);([^;]*)/i],[[VENDOR,util.trim],[MODEL,util.trim],[TYPE,SMARTTV]],[/hbbtv.+maple;(\d+)/i],[[MODEL,/^/,"SmartTV"],[VENDOR,"Samsung"],[TYPE,SMARTTV]],[/\(dtv[\);].+(aquos)/i],[MODEL,[VENDOR,"Sharp"],[TYPE,SMARTTV]],[/android.+((sch-i[89]0\d|shw-m380s|gt-p\d{4}|gt-n\d+|sgh-t8[56]9|nexus 10))/i,/((SM-T\w+))/i],[[VENDOR,"Samsung"],MODEL,[TYPE,TABLET]],[/smart-tv.+(samsung)/i],[VENDOR,[TYPE,SMARTTV],MODEL],[/((s[cgp]h-\w+|gt-\w+|galaxy\snexus|sm-\w[\w\d]+))/i,/(sam[sung]*)[\s-]*(\w+-?[\w-]*)/i,/sec-((sgh\w+))/i],[[VENDOR,"Samsung"],MODEL,[TYPE,MOBILE]],[/sie-(\w*)/i],[MODEL,[VENDOR,"Siemens"],[TYPE,MOBILE]],[/(maemo|nokia).*(n900|lumia\s\d+)/i,/(nokia)[\s_-]?([\w-]*)/i],[[VENDOR,"Nokia"],MODEL,[TYPE,MOBILE]],[/android\s3\.[\s\w;-]{10}(a\d{3})/i],[MODEL,[VENDOR,"Acer"],[TYPE,TABLET]],[/android.+([vl]k\-?\d{3})\s+build/i],[MODEL,[VENDOR,"LG"],[TYPE,TABLET]],[/android\s3\.[\s\w;-]{10}(lg?)-([06cv9]{3,4})/i],[[VENDOR,"LG"],MODEL,[TYPE,TABLET]],[/(lg) netcast\.tv/i],[VENDOR,MODEL,[TYPE,SMARTTV]],[/(nexus\s[45])/i,/lg[e;\s\/-]+(\w*)/i,/android.+lg(\-?[\d\w]+)\s+build/i],[MODEL,[VENDOR,"LG"],[TYPE,MOBILE]],[/android.+(ideatab[a-z0-9\-\s]+)/i],[MODEL,[VENDOR,"Lenovo"],[TYPE,TABLET]],[/linux;.+((jolla));/i],[VENDOR,MODEL,[TYPE,MOBILE]],[/((pebble))app\/[\d\.]+\s/i],[VENDOR,MODEL,[TYPE,WEARABLE]],[/android.+;\s(oppo)\s?([\w\s]+)\sbuild/i],[VENDOR,MODEL,[TYPE,MOBILE]],[/crkey/i],[[MODEL,"Chromecast"],[VENDOR,"Google"]],[/android.+;\s(glass)\s\d/i],[MODEL,[VENDOR,"Google"],[TYPE,WEARABLE]],[/android.+;\s(pixel c)\s/i],[MODEL,[VENDOR,"Google"],[TYPE,TABLET]],[/android.+;\s(pixel xl|pixel)\s/i],[MODEL,[VENDOR,"Google"],[TYPE,MOBILE]],[/android.+;\s(\w+)\s+build\/hm\1/i,/android.+(hm[\s\-_]*note?[\s_]*(?:\d\w)?)\s+build/i,/android.+(mi[\s\-_]*(?:one|one[\s_]plus|note lte)?[\s_]*(?:\d?\w?)[\s_]*(?:plus)?)\s+build/i,/android.+(redmi[\s\-_]*(?:note)?(?:[\s_]*[\w\s]+))\s+build/i],[[MODEL,/_/g," "],[VENDOR,"Xiaomi"],[TYPE,MOBILE]],[/android.+(mi[\s\-_]*(?:pad)(?:[\s_]*[\w\s]+))\s+build/i],[[MODEL,/_/g," "],[VENDOR,"Xiaomi"],[TYPE,TABLET]],[/android.+;\s(m[1-5]\snote)\sbuild/i],[MODEL,[VENDOR,"Meizu"],[TYPE,TABLET]],[/android.+a000(1)\s+build/i,/android.+oneplus\s(a\d{4})\s+build/i],[MODEL,[VENDOR,"OnePlus"],[TYPE,MOBILE]],[/android.+[;\/]\s*(RCT[\d\w]+)\s+build/i],[MODEL,[VENDOR,"RCA"],[TYPE,TABLET]],[/android.+[;\/\s]+(Venue[\d\s]{2,7})\s+build/i],[MODEL,[VENDOR,"Dell"],[TYPE,TABLET]],[/android.+[;\/]\s*(Q[T|M][\d\w]+)\s+build/i],[MODEL,[VENDOR,"Verizon"],[TYPE,TABLET]],[/android.+[;\/]\s+(Barnes[&\s]+Noble\s+|BN[RT])(V?.*)\s+build/i],[[VENDOR,"Barnes & Noble"],MODEL,[TYPE,TABLET]],[/android.+[;\/]\s+(TM\d{3}.*\b)\s+build/i],[MODEL,[VENDOR,"NuVision"],[TYPE,TABLET]],[/android.+;\s(k88)\sbuild/i],[MODEL,[VENDOR,"ZTE"],[TYPE,TABLET]],[/android.+[;\/]\s*(gen\d{3})\s+build.*49h/i],[MODEL,[VENDOR,"Swiss"],[TYPE,MOBILE]],[/android.+[;\/]\s*(zur\d{3})\s+build/i],[MODEL,[VENDOR,"Swiss"],[TYPE,TABLET]],[/android.+[;\/]\s*((Zeki)?TB.*\b)\s+build/i],[MODEL,[VENDOR,"Zeki"],[TYPE,TABLET]],[/(android).+[;\/]\s+([YR]\d{2})\s+build/i,/android.+[;\/]\s+(Dragon[\-\s]+Touch\s+|DT)(\w{5})\sbuild/i],[[VENDOR,"Dragon Touch"],MODEL,[TYPE,TABLET]],[/android.+[;\/]\s*(NS-?\w{0,9})\sbuild/i],[MODEL,[VENDOR,"Insignia"],[TYPE,TABLET]],[/android.+[;\/]\s*((NX|Next)-?\w{0,9})\s+build/i],[MODEL,[VENDOR,"NextBook"],[TYPE,TABLET]],[/android.+[;\/]\s*(Xtreme\_)?(V(1[045]|2[015]|30|40|60|7[05]|90))\s+build/i],[[VENDOR,"Voice"],MODEL,[TYPE,MOBILE]],[/android.+[;\/]\s*(LVTEL\-)?(V1[12])\s+build/i],[[VENDOR,"LvTel"],MODEL,[TYPE,MOBILE]],[/android.+[;\/]\s*(V(100MD|700NA|7011|917G).*\b)\s+build/i],[MODEL,[VENDOR,"Envizen"],[TYPE,TABLET]],[/android.+[;\/]\s*(Le[\s\-]+Pan)[\s\-]+(\w{1,9})\s+build/i],[VENDOR,MODEL,[TYPE,TABLET]],[/android.+[;\/]\s*(Trio[\s\-]*.*)\s+build/i],[MODEL,[VENDOR,"MachSpeed"],[TYPE,TABLET]],[/android.+[;\/]\s*(Trinity)[\-\s]*(T\d{3})\s+build/i],[VENDOR,MODEL,[TYPE,TABLET]],[/android.+[;\/]\s*TU_(1491)\s+build/i],[MODEL,[VENDOR,"Rotor"],[TYPE,TABLET]],[/android.+(KS(.+))\s+build/i],[MODEL,[VENDOR,"Amazon"],[TYPE,TABLET]],[/android.+(Gigaset)[\s\-]+(Q\w{1,9})\s+build/i],[VENDOR,MODEL,[TYPE,TABLET]],[/\s(tablet|tab)[;\/]/i,/\s(mobile)(?:[;\/]|\ssafari)/i],[[TYPE,util.lowerize],VENDOR,MODEL],[/(android[\w\.\s\-]{0,9});.+build/i],[MODEL,[VENDOR,"Generic"]]],engine:[[/windows.+\sedge\/([\w\.]+)/i],[VERSION,[NAME,"EdgeHTML"]],[/(presto)\/([\w\.]+)/i,/(webkit|trident|netfront|netsurf|amaya|lynx|w3m)\/([\w\.]+)/i,/(khtml|tasman|links)[\/\s]\(?([\w\.]+)/i,/(icab)[\/\s]([23]\.[\d\.]+)/i],[NAME,VERSION],[/rv\:([\w\.]{1,9}).+(gecko)/i],[VERSION,NAME]],os:[[/microsoft\s(windows)\s(vista|xp)/i],[NAME,VERSION],[/(windows)\snt\s6\.2;\s(arm)/i,/(windows\sphone(?:\sos)*)[\s\/]?([\d\.\s\w]*)/i,/(windows\smobile|windows)[\s\/]?([ntce\d\.\s]+\w)/i],[NAME,[VERSION,mapper.str,maps.os.windows.version]],[/(win(?=3|9|n)|win\s9x\s)([nt\d\.]+)/i],[[NAME,"Windows"],[VERSION,mapper.str,maps.os.windows.version]],[/\((bb)(10);/i],[[NAME,"BlackBerry"],VERSION],[/(blackberry)\w*\/?([\w\.]*)/i,/(tizen)[\/\s]([\w\.]+)/i,/(android|webos|palm\sos|qnx|bada|rim\stablet\sos|meego|contiki)[\/\s-]?([\w\.]*)/i,/linux;.+(sailfish);/i],[NAME,VERSION],[/(symbian\s?os|symbos|s60(?=;))[\/\s-]?([\w\.]*)/i],[[NAME,"Symbian"],VERSION],[/\((series40);/i],[NAME],[/mozilla.+\(mobile;.+gecko.+firefox/i],[[NAME,"Firefox OS"],VERSION],[/(nintendo|playstation)\s([wids34portablevu]+)/i,/(mint)[\/\s\(]?(\w*)/i,/(mageia|vectorlinux)[;\s]/i,/(joli|[kxln]?ubuntu|debian|suse|opensuse|gentoo|(?=\s)arch|slackware|fedora|mandriva|centos|pclinuxos|redhat|zenwalk|linpus)[\/\s-]?(?!chrom)([\w\.-]*)/i,/(hurd|linux)\s?([\w\.]*)/i,/(gnu)\s?([\w\.]*)/i],[NAME,VERSION],[/(cros)\s[\w]+\s([\w\.]+\w)/i],[[NAME,"Chromium OS"],VERSION],[/(sunos)\s?([\w\.\d]*)/i],[[NAME,"Solaris"],VERSION],[/\s([frentopc-]{0,4}bsd|dragonfly)\s?([\w\.]*)/i],[NAME,VERSION],[/(haiku)\s(\w+)/i],[NAME,VERSION],[/cfnetwork\/.+darwin/i,/ip[honead]{2,4}(?:.*os\s([\w]+)\slike\smac|;\sopera)/i],[[VERSION,/_/g,"."],[NAME,"iOS"]],[/(mac\sos\sx)\s?([\w\s\.]*)/i,/(macintosh|mac(?=_powerpc)\s)/i],[[NAME,"Mac OS"],[VERSION,/_/g,"."]],[/((?:open)?solaris)[\/\s-]?([\w\.]*)/i,/(aix)\s((\d)(?=\.|\)|\s)[\w\.])*/i,/(plan\s9|minix|beos|os\/2|amigaos|morphos|risc\sos|openvms)/i,/(unix)\s?([\w\.]*)/i],[NAME,VERSION]]};var UAParser=function(uastring,extensions){if(typeof uastring==="object"){extensions=uastring;uastring=undefined}if(!(this instanceof UAParser)){return new UAParser(uastring,extensions).getResult()}var ua=uastring||(window&&window.navigator&&window.navigator.userAgent?window.navigator.userAgent:EMPTY);var rgxmap=extensions?util.extend(regexes,extensions):regexes;this.getBrowser=function(){var browser={name:undefined,version:undefined};mapper.rgx.call(browser,ua,rgxmap.browser);browser.major=util.major(browser.version);return browser};this.getCPU=function(){var cpu={architecture:undefined};mapper.rgx.call(cpu,ua,rgxmap.cpu);return cpu};this.getDevice=function(){var device={vendor:undefined,model:undefined,type:undefined};mapper.rgx.call(device,ua,rgxmap.device);return device};this.getEngine=function(){var engine={name:undefined,version:undefined};mapper.rgx.call(engine,ua,rgxmap.engine);return engine};this.getOS=function(){var os={name:undefined,version:undefined};mapper.rgx.call(os,ua,rgxmap.os);return os};this.getResult=function(){return{ua:this.getUA(),browser:this.getBrowser(),engine:this.getEngine(),os:this.getOS(),device:this.getDevice(),cpu:this.getCPU()}};this.getUA=function(){return ua};this.setUA=function(uastring){ua=uastring;return this};return this};UAParser.VERSION=LIBVERSION;UAParser.BROWSER={NAME:NAME,MAJOR:MAJOR,VERSION:VERSION};UAParser.CPU={ARCHITECTURE:ARCHITECTURE};UAParser.DEVICE={MODEL:MODEL,VENDOR:VENDOR,TYPE:TYPE,CONSOLE:CONSOLE,MOBILE:MOBILE,SMARTTV:SMARTTV,TABLET:TABLET,WEARABLE:WEARABLE,EMBEDDED:EMBEDDED};UAParser.ENGINE={NAME:NAME,VERSION:VERSION};UAParser.OS={NAME:NAME,VERSION:VERSION};if(typeof exports!==UNDEF_TYPE){if(typeof module!==UNDEF_TYPE&&module.exports){exports=module.exports=UAParser}exports.UAParser=UAParser}else{if("function"===FUNC_TYPE&&__nested_webpack_require_11768__(3)){!(__WEBPACK_AMD_DEFINE_RESULT__ = (function(){return UAParser}).call(exports, __nested_webpack_require_11768__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__))}else if(window){window.UAParser=UAParser}}var $=window&&(window.jQuery||window.Zepto);if(typeof $!==UNDEF_TYPE){var parser=new UAParser;$.ua=parser.getResult();$.ua.get=function(){return parser.getUA()};$.ua.set=function(uastring){parser.setUA(uastring);var result=parser.getResult();for(var prop in result){$.ua[prop]=result[prop]}}}})(typeof window==="object"?window:this);

/***/ }),
/* 3 */
/***/ (function(module, exports) {

/* WEBPACK VAR INJECTION */(function(__webpack_amd_options__) {/* globals __webpack_amd_options__ */
module.exports = __webpack_amd_options__;

/* WEBPACK VAR INJECTION */}.call(exports, {}))

/***/ }),
/* 4 */
/***/ (function(module, exports, __nested_webpack_require_29664__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _require = __nested_webpack_require_29664__(0),
    DEVICE_TYPES = _require.DEVICE_TYPES,
    defaultData = _require.defaultData;

var checkType = function checkType(type) {
    switch (type) {
        case DEVICE_TYPES.MOBILE:
            return { isMobile: true };
        case DEVICE_TYPES.TABLET:
            return { isTablet: true };
        case DEVICE_TYPES.SMART_TV:
            return { isSmartTV: true };
        case DEVICE_TYPES.CONSOLE:
            return { isConsole: true };
        case DEVICE_TYPES.WEARABLE:
            return { isWearable: true };
        case DEVICE_TYPES.BROWSER:
            return { isBrowser: true };
        default:
            return defaultData;
    }
};

var broPayload = function broPayload(isBrowser, browser, engine, os, ua) {
    return {
        isBrowser: isBrowser,
        browserMajorVersion: browser.major,
        browserFullVersion: browser.version,
        browserName: browser.name,
        engineName: engine.name || false,
        engineVersion: engine.version,
        osName: os.name,
        osVersion: os.version,
        userAgent: ua
    };
};

var mobilePayload = function mobilePayload(type, device, os, ua) {
    return _extends({}, type, {
        vendor: device.vendor,
        model: device.model,
        os: os.name,
        osVersion: os.version,
        ua: ua
    });
};

var stvPayload = function stvPayload(isSmartTV, engine, os, ua) {
    return {
        isSmartTV: isSmartTV,
        engineName: engine.name,
        engineVersion: engine.version,
        osName: os.name,
        osVersion: os.version,
        userAgent: ua
    };
};

var consolePayload = function consolePayload(isConsole, engine, os, ua) {
    return {
        isConsole: isConsole,
        engineName: engine.name,
        engineVersion: engine.version,
        osName: os.name,
        osVersion: os.version,
        userAgent: ua
    };
};

var wearPayload = function wearPayload(isWearable, engine, os, ua) {
    return {
        isWearable: isWearable,
        engineName: engine.name,
        engineVersion: engine.version,
        osName: os.name,
        osVersion: os.version,
        userAgent: ua
    };
};

var getNavigatorInstance = exports.getNavigatorInstance = function getNavigatorInstance() {
    if (typeof window !== 'undefined') {
        if (window.navigator || navigator) {
            return window.navigator || navigator;
        }
    }

    return false;
};

var isIOS13Check = exports.isIOS13Check = function isIOS13Check(type) {
    var nav = getNavigatorInstance();
    return nav && nav.platform && (nav.platform.indexOf(type) !== -1 || nav.platform === 'MacIntel' && nav.maxTouchPoints > 1 && !window.MSStream);
};

module.exports = {
    checkType: checkType,
    broPayload: broPayload,
    mobilePayload: mobilePayload,
    stvPayload: stvPayload,
    consolePayload: consolePayload,
    wearPayload: wearPayload,
    getNavigatorInstance: getNavigatorInstance,
    isIOS13Check: isIOS13Check
};

/***/ })
/******/ ]);

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	!function() {
/******/ 		__webpack_require__.p = "";
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
!function() {
"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

;// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
/* eslint-disable no-var */
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

;// CONCATENATED MODULE: ./node_modules/vue/dist/vue.runtime.esm.js
/*!
 * Vue.js v2.7.15
 * (c) 2014-2023 Evan You
 * Released under the MIT License.
 */
var emptyObject = Object.freeze({});
var isArray = Array.isArray;
// These helpers produce better VM code in JS engines due to their
// explicitness and function inlining.
function isUndef(v) {
    return v === undefined || v === null;
}
function isDef(v) {
    return v !== undefined && v !== null;
}
function isTrue(v) {
    return v === true;
}
function isFalse(v) {
    return v === false;
}
/**
 * Check if value is primitive.
 */
function isPrimitive(value) {
    return (typeof value === 'string' ||
        typeof value === 'number' ||
        // $flow-disable-line
        typeof value === 'symbol' ||
        typeof value === 'boolean');
}
function isFunction(value) {
    return typeof value === 'function';
}
/**
 * Quick object check - this is primarily used to tell
 * objects from primitive values when we know the value
 * is a JSON-compliant type.
 */
function isObject(obj) {
    return obj !== null && typeof obj === 'object';
}
/**
 * Get the raw type string of a value, e.g., [object Object].
 */
var _toString = Object.prototype.toString;
function toRawType(value) {
    return _toString.call(value).slice(8, -1);
}
/**
 * Strict object type check. Only returns true
 * for plain JavaScript objects.
 */
function isPlainObject(obj) {
    return _toString.call(obj) === '[object Object]';
}
function isRegExp(v) {
    return _toString.call(v) === '[object RegExp]';
}
/**
 * Check if val is a valid array index.
 */
function isValidArrayIndex(val) {
    var n = parseFloat(String(val));
    return n >= 0 && Math.floor(n) === n && isFinite(val);
}
function isPromise(val) {
    return (isDef(val) &&
        typeof val.then === 'function' &&
        typeof val.catch === 'function');
}
/**
 * Convert a value to a string that is actually rendered.
 */
function vue_runtime_esm_toString(val) {
    return val == null
        ? ''
        : Array.isArray(val) || (isPlainObject(val) && val.toString === _toString)
            ? JSON.stringify(val, null, 2)
            : String(val);
}
/**
 * Convert an input value to a number for persistence.
 * If the conversion fails, return original string.
 */
function toNumber(val) {
    var n = parseFloat(val);
    return isNaN(n) ? val : n;
}
/**
 * Make a map and return a function for checking if a key
 * is in that map.
 */
function makeMap(str, expectsLowerCase) {
    var map = Object.create(null);
    var list = str.split(',');
    for (var i = 0; i < list.length; i++) {
        map[list[i]] = true;
    }
    return expectsLowerCase ? function (val) { return map[val.toLowerCase()]; } : function (val) { return map[val]; };
}
/**
 * Check if a tag is a built-in tag.
 */
var isBuiltInTag = makeMap('slot,component', true);
/**
 * Check if an attribute is a reserved attribute.
 */
var isReservedAttribute = makeMap('key,ref,slot,slot-scope,is');
/**
 * Remove an item from an array.
 */
function remove$2(arr, item) {
    var len = arr.length;
    if (len) {
        // fast path for the only / last item
        if (item === arr[len - 1]) {
            arr.length = len - 1;
            return;
        }
        var index = arr.indexOf(item);
        if (index > -1) {
            return arr.splice(index, 1);
        }
    }
}
/**
 * Check whether an object has the property.
 */
var vue_runtime_esm_hasOwnProperty = Object.prototype.hasOwnProperty;
function hasOwn(obj, key) {
    return vue_runtime_esm_hasOwnProperty.call(obj, key);
}
/**
 * Create a cached version of a pure function.
 */
function cached(fn) {
    var cache = Object.create(null);
    return function cachedFn(str) {
        var hit = cache[str];
        return hit || (cache[str] = fn(str));
    };
}
/**
 * Camelize a hyphen-delimited string.
 */
var camelizeRE = /-(\w)/g;
var camelize = cached(function (str) {
    return str.replace(camelizeRE, function (_, c) { return (c ? c.toUpperCase() : ''); });
});
/**
 * Capitalize a string.
 */
var capitalize = cached(function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
});
/**
 * Hyphenate a camelCase string.
 */
var hyphenateRE = /\B([A-Z])/g;
var hyphenate = cached(function (str) {
    return str.replace(hyphenateRE, '-$1').toLowerCase();
});
/**
 * Simple bind polyfill for environments that do not support it,
 * e.g., PhantomJS 1.x. Technically, we don't need this anymore
 * since native bind is now performant enough in most browsers.
 * But removing it would mean breaking code that was able to run in
 * PhantomJS 1.x, so this must be kept for backward compatibility.
 */
/* istanbul ignore next */
function polyfillBind(fn, ctx) {
    function boundFn(a) {
        var l = arguments.length;
        return l
            ? l > 1
                ? fn.apply(ctx, arguments)
                : fn.call(ctx, a)
            : fn.call(ctx);
    }
    boundFn._length = fn.length;
    return boundFn;
}
function nativeBind(fn, ctx) {
    return fn.bind(ctx);
}
// @ts-expect-error bind cannot be `undefined`
var bind = Function.prototype.bind ? nativeBind : polyfillBind;
/**
 * Convert an Array-like object to a real Array.
 */
function toArray(list, start) {
    start = start || 0;
    var i = list.length - start;
    var ret = new Array(i);
    while (i--) {
        ret[i] = list[i + start];
    }
    return ret;
}
/**
 * Mix properties into target object.
 */
function extend(to, _from) {
    for (var key in _from) {
        to[key] = _from[key];
    }
    return to;
}
/**
 * Merge an Array of Objects into a single Object.
 */
function toObject(arr) {
    var res = {};
    for (var i = 0; i < arr.length; i++) {
        if (arr[i]) {
            extend(res, arr[i]);
        }
    }
    return res;
}
/* eslint-disable no-unused-vars */
/**
 * Perform no operation.
 * Stubbing args to make Flow happy without leaving useless transpiled code
 * with ...rest (https://flow.org/blog/2017/05/07/Strict-Function-Call-Arity/).
 */
function noop(a, b, c) { }
/**
 * Always return false.
 */
var no = function (a, b, c) { return false; };
/* eslint-enable no-unused-vars */
/**
 * Return the same value.
 */
var identity = function (_) { return _; };
/**
 * Check if two values are loosely equal - that is,
 * if they are plain objects, do they have the same shape?
 */
function looseEqual(a, b) {
    if (a === b)
        return true;
    var isObjectA = isObject(a);
    var isObjectB = isObject(b);
    if (isObjectA && isObjectB) {
        try {
            var isArrayA = Array.isArray(a);
            var isArrayB = Array.isArray(b);
            if (isArrayA && isArrayB) {
                return (a.length === b.length &&
                    a.every(function (e, i) {
                        return looseEqual(e, b[i]);
                    }));
            }
            else if (a instanceof Date && b instanceof Date) {
                return a.getTime() === b.getTime();
            }
            else if (!isArrayA && !isArrayB) {
                var keysA = Object.keys(a);
                var keysB = Object.keys(b);
                return (keysA.length === keysB.length &&
                    keysA.every(function (key) {
                        return looseEqual(a[key], b[key]);
                    }));
            }
            else {
                /* istanbul ignore next */
                return false;
            }
        }
        catch (e) {
            /* istanbul ignore next */
            return false;
        }
    }
    else if (!isObjectA && !isObjectB) {
        return String(a) === String(b);
    }
    else {
        return false;
    }
}
/**
 * Return the first index at which a loosely equal value can be
 * found in the array (if value is a plain object, the array must
 * contain an object of the same shape), or -1 if it is not present.
 */
function looseIndexOf(arr, val) {
    for (var i = 0; i < arr.length; i++) {
        if (looseEqual(arr[i], val))
            return i;
    }
    return -1;
}
/**
 * Ensure a function is called only once.
 */
function once(fn) {
    var called = false;
    return function () {
        if (!called) {
            called = true;
            fn.apply(this, arguments);
        }
    };
}
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/is#polyfill
function hasChanged(x, y) {
    if (x === y) {
        return x === 0 && 1 / x !== 1 / y;
    }
    else {
        return x === x || y === y;
    }
}

var SSR_ATTR = 'data-server-rendered';
var ASSET_TYPES = ['component', 'directive', 'filter'];
var LIFECYCLE_HOOKS = [
    'beforeCreate',
    'created',
    'beforeMount',
    'mounted',
    'beforeUpdate',
    'updated',
    'beforeDestroy',
    'destroyed',
    'activated',
    'deactivated',
    'errorCaptured',
    'serverPrefetch',
    'renderTracked',
    'renderTriggered'
];

var config = {
    /**
     * Option merge strategies (used in core/util/options)
     */
    // $flow-disable-line
    optionMergeStrategies: Object.create(null),
    /**
     * Whether to suppress warnings.
     */
    silent: false,
    /**
     * Show production mode tip message on boot?
     */
    productionTip: "production" !== 'production',
    /**
     * Whether to enable devtools
     */
    devtools: "production" !== 'production',
    /**
     * Whether to record perf
     */
    performance: false,
    /**
     * Error handler for watcher errors
     */
    errorHandler: null,
    /**
     * Warn handler for watcher warns
     */
    warnHandler: null,
    /**
     * Ignore certain custom elements
     */
    ignoredElements: [],
    /**
     * Custom user key aliases for v-on
     */
    // $flow-disable-line
    keyCodes: Object.create(null),
    /**
     * Check if a tag is reserved so that it cannot be registered as a
     * component. This is platform-dependent and may be overwritten.
     */
    isReservedTag: no,
    /**
     * Check if an attribute is reserved so that it cannot be used as a component
     * prop. This is platform-dependent and may be overwritten.
     */
    isReservedAttr: no,
    /**
     * Check if a tag is an unknown element.
     * Platform-dependent.
     */
    isUnknownElement: no,
    /**
     * Get the namespace of an element
     */
    getTagNamespace: noop,
    /**
     * Parse the real tag name for the specific platform.
     */
    parsePlatformTagName: identity,
    /**
     * Check if an attribute must be bound using property, e.g. value
     * Platform-dependent.
     */
    mustUseProp: no,
    /**
     * Perform updates asynchronously. Intended to be used by Vue Test Utils
     * This will significantly reduce performance if set to false.
     */
    async: true,
    /**
     * Exposed for legacy reasons
     */
    _lifecycleHooks: LIFECYCLE_HOOKS
};

/**
 * unicode letters used for parsing html tags, component names and property paths.
 * using https://www.w3.org/TR/html53/semantics-scripting.html#potentialcustomelementname
 * skipping \u10000-\uEFFFF due to it freezing up PhantomJS
 */
var unicodeRegExp = /a-zA-Z\u00B7\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u037D\u037F-\u1FFF\u200C-\u200D\u203F-\u2040\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD/;
/**
 * Check if a string starts with $ or _
 */
function isReserved(str) {
    var c = (str + '').charCodeAt(0);
    return c === 0x24 || c === 0x5f;
}
/**
 * Define a property.
 */
function def(obj, key, val, enumerable) {
    Object.defineProperty(obj, key, {
        value: val,
        enumerable: !!enumerable,
        writable: true,
        configurable: true
    });
}
/**
 * Parse simple path.
 */
var bailRE = new RegExp("[^".concat(unicodeRegExp.source, ".$_\\d]"));
function parsePath(path) {
    if (bailRE.test(path)) {
        return;
    }
    var segments = path.split('.');
    return function (obj) {
        for (var i = 0; i < segments.length; i++) {
            if (!obj)
                return;
            obj = obj[segments[i]];
        }
        return obj;
    };
}

// can we use __proto__?
var hasProto = '__proto__' in {};
// Browser environment sniffing
var inBrowser = typeof window !== 'undefined';
var UA = inBrowser && window.navigator.userAgent.toLowerCase();
var isIE = UA && /msie|trident/.test(UA);
var isIE9 = UA && UA.indexOf('msie 9.0') > 0;
var isEdge = UA && UA.indexOf('edge/') > 0;
UA && UA.indexOf('android') > 0;
var isIOS = UA && /iphone|ipad|ipod|ios/.test(UA);
UA && /chrome\/\d+/.test(UA) && !isEdge;
UA && /phantomjs/.test(UA);
var isFF = UA && UA.match(/firefox\/(\d+)/);
// Firefox has a "watch" function on Object.prototype...
// @ts-expect-error firebox support
var nativeWatch = {}.watch;
var supportsPassive = false;
if (inBrowser) {
    try {
        var opts = {};
        Object.defineProperty(opts, 'passive', {
            get: function () {
                /* istanbul ignore next */
                supportsPassive = true;
            }
        }); // https://github.com/facebook/flow/issues/285
        window.addEventListener('test-passive', null, opts);
    }
    catch (e) { }
}
// this needs to be lazy-evaled because vue may be required before
// vue-server-renderer can set VUE_ENV
var _isServer;
var isServerRendering = function () {
    if (_isServer === undefined) {
        /* istanbul ignore if */
        if (!inBrowser && typeof __webpack_require__.g !== 'undefined') {
            // detect presence of vue-server-renderer and avoid
            // Webpack shimming the process
            _isServer =
                __webpack_require__.g['process'] && __webpack_require__.g['process'].env.VUE_ENV === 'server';
        }
        else {
            _isServer = false;
        }
    }
    return _isServer;
};
// detect devtools
var devtools = inBrowser && window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
/* istanbul ignore next */
function isNative(Ctor) {
    return typeof Ctor === 'function' && /native code/.test(Ctor.toString());
}
var hasSymbol = typeof Symbol !== 'undefined' &&
    isNative(Symbol) &&
    typeof Reflect !== 'undefined' &&
    isNative(Reflect.ownKeys);
var _Set; // $flow-disable-line
/* istanbul ignore if */ if (typeof Set !== 'undefined' && isNative(Set)) {
    // use native Set when available.
    _Set = Set;
}
else {
    // a non-standard Set polyfill that only works with primitive keys.
    _Set = /** @class */ (function () {
        function Set() {
            this.set = Object.create(null);
        }
        Set.prototype.has = function (key) {
            return this.set[key] === true;
        };
        Set.prototype.add = function (key) {
            this.set[key] = true;
        };
        Set.prototype.clear = function () {
            this.set = Object.create(null);
        };
        return Set;
    }());
}

var currentInstance = null;
/**
 * This is exposed for compatibility with v3 (e.g. some functions in VueUse
 * relies on it). Do not use this internally, just use `currentInstance`.
 *
 * @internal this function needs manual type declaration because it relies
 * on previously manually authored types from Vue 2
 */
function getCurrentInstance() {
    return currentInstance && { proxy: currentInstance };
}
/**
 * @internal
 */
function setCurrentInstance(vm) {
    if (vm === void 0) { vm = null; }
    if (!vm)
        currentInstance && currentInstance._scope.off();
    currentInstance = vm;
    vm && vm._scope.on();
}

/**
 * @internal
 */
var VNode = /** @class */ (function () {
    function VNode(tag, data, children, text, elm, context, componentOptions, asyncFactory) {
        this.tag = tag;
        this.data = data;
        this.children = children;
        this.text = text;
        this.elm = elm;
        this.ns = undefined;
        this.context = context;
        this.fnContext = undefined;
        this.fnOptions = undefined;
        this.fnScopeId = undefined;
        this.key = data && data.key;
        this.componentOptions = componentOptions;
        this.componentInstance = undefined;
        this.parent = undefined;
        this.raw = false;
        this.isStatic = false;
        this.isRootInsert = true;
        this.isComment = false;
        this.isCloned = false;
        this.isOnce = false;
        this.asyncFactory = asyncFactory;
        this.asyncMeta = undefined;
        this.isAsyncPlaceholder = false;
    }
    Object.defineProperty(VNode.prototype, "child", {
        // DEPRECATED: alias for componentInstance for backwards compat.
        /* istanbul ignore next */
        get: function () {
            return this.componentInstance;
        },
        enumerable: false,
        configurable: true
    });
    return VNode;
}());
var createEmptyVNode = function (text) {
    if (text === void 0) { text = ''; }
    var node = new VNode();
    node.text = text;
    node.isComment = true;
    return node;
};
function createTextVNode(val) {
    return new VNode(undefined, undefined, undefined, String(val));
}
// optimized shallow clone
// used for static nodes and slot nodes because they may be reused across
// multiple renders, cloning them avoids errors when DOM manipulations rely
// on their elm reference.
function cloneVNode(vnode) {
    var cloned = new VNode(vnode.tag, vnode.data, 
    // #7975
    // clone children array to avoid mutating original in case of cloning
    // a child.
    vnode.children && vnode.children.slice(), vnode.text, vnode.elm, vnode.context, vnode.componentOptions, vnode.asyncFactory);
    cloned.ns = vnode.ns;
    cloned.isStatic = vnode.isStatic;
    cloned.key = vnode.key;
    cloned.isComment = vnode.isComment;
    cloned.fnContext = vnode.fnContext;
    cloned.fnOptions = vnode.fnOptions;
    cloned.fnScopeId = vnode.fnScopeId;
    cloned.asyncMeta = vnode.asyncMeta;
    cloned.isCloned = true;
    return cloned;
}

/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */

var __assign = function() {
    __assign = Object.assign || function __assign(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};

var uid$2 = 0;
var pendingCleanupDeps = [];
var cleanupDeps = function () {
    for (var i = 0; i < pendingCleanupDeps.length; i++) {
        var dep = pendingCleanupDeps[i];
        dep.subs = dep.subs.filter(function (s) { return s; });
        dep._pending = false;
    }
    pendingCleanupDeps.length = 0;
};
/**
 * A dep is an observable that can have multiple
 * directives subscribing to it.
 * @internal
 */
var Dep = /** @class */ (function () {
    function Dep() {
        // pending subs cleanup
        this._pending = false;
        this.id = uid$2++;
        this.subs = [];
    }
    Dep.prototype.addSub = function (sub) {
        this.subs.push(sub);
    };
    Dep.prototype.removeSub = function (sub) {
        // #12696 deps with massive amount of subscribers are extremely slow to
        // clean up in Chromium
        // to workaround this, we unset the sub for now, and clear them on
        // next scheduler flush.
        this.subs[this.subs.indexOf(sub)] = null;
        if (!this._pending) {
            this._pending = true;
            pendingCleanupDeps.push(this);
        }
    };
    Dep.prototype.depend = function (info) {
        if (Dep.target) {
            Dep.target.addDep(this);
            if (false) {}
        }
    };
    Dep.prototype.notify = function (info) {
        // stabilize the subscriber list first
        var subs = this.subs.filter(function (s) { return s; });
        if (false) {}
        for (var i = 0, l = subs.length; i < l; i++) {
            var sub = subs[i];
            if (false) {}
            sub.update();
        }
    };
    return Dep;
}());
// The current target watcher being evaluated.
// This is globally unique because only one watcher
// can be evaluated at a time.
Dep.target = null;
var targetStack = [];
function pushTarget(target) {
    targetStack.push(target);
    Dep.target = target;
}
function popTarget() {
    targetStack.pop();
    Dep.target = targetStack[targetStack.length - 1];
}

/*
 * not type checking this file because flow doesn't play well with
 * dynamically accessing methods on Array prototype
 */
var arrayProto = Array.prototype;
var arrayMethods = Object.create(arrayProto);
var methodsToPatch = [
    'push',
    'pop',
    'shift',
    'unshift',
    'splice',
    'sort',
    'reverse'
];
/**
 * Intercept mutating methods and emit events
 */
methodsToPatch.forEach(function (method) {
    // cache original method
    var original = arrayProto[method];
    def(arrayMethods, method, function mutator() {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            args[_i] = arguments[_i];
        }
        var result = original.apply(this, args);
        var ob = this.__ob__;
        var inserted;
        switch (method) {
            case 'push':
            case 'unshift':
                inserted = args;
                break;
            case 'splice':
                inserted = args.slice(2);
                break;
        }
        if (inserted)
            ob.observeArray(inserted);
        // notify change
        if (false) {}
        else {
            ob.dep.notify();
        }
        return result;
    });
});

var arrayKeys = Object.getOwnPropertyNames(arrayMethods);
var NO_INITIAL_VALUE = {};
/**
 * In some cases we may want to disable observation inside a component's
 * update computation.
 */
var shouldObserve = true;
function toggleObserving(value) {
    shouldObserve = value;
}
// ssr mock dep
var mockDep = {
    notify: noop,
    depend: noop,
    addSub: noop,
    removeSub: noop
};
/**
 * Observer class that is attached to each observed
 * object. Once attached, the observer converts the target
 * object's property keys into getter/setters that
 * collect dependencies and dispatch updates.
 */
var Observer = /** @class */ (function () {
    function Observer(value, shallow, mock) {
        if (shallow === void 0) { shallow = false; }
        if (mock === void 0) { mock = false; }
        this.value = value;
        this.shallow = shallow;
        this.mock = mock;
        // this.value = value
        this.dep = mock ? mockDep : new Dep();
        this.vmCount = 0;
        def(value, '__ob__', this);
        if (isArray(value)) {
            if (!mock) {
                if (hasProto) {
                    value.__proto__ = arrayMethods;
                    /* eslint-enable no-proto */
                }
                else {
                    for (var i = 0, l = arrayKeys.length; i < l; i++) {
                        var key = arrayKeys[i];
                        def(value, key, arrayMethods[key]);
                    }
                }
            }
            if (!shallow) {
                this.observeArray(value);
            }
        }
        else {
            /**
             * Walk through all properties and convert them into
             * getter/setters. This method should only be called when
             * value type is Object.
             */
            var keys = Object.keys(value);
            for (var i = 0; i < keys.length; i++) {
                var key = keys[i];
                defineReactive(value, key, NO_INITIAL_VALUE, undefined, shallow, mock);
            }
        }
    }
    /**
     * Observe a list of Array items.
     */
    Observer.prototype.observeArray = function (value) {
        for (var i = 0, l = value.length; i < l; i++) {
            observe(value[i], false, this.mock);
        }
    };
    return Observer;
}());
// helpers
/**
 * Attempt to create an observer instance for a value,
 * returns the new observer if successfully observed,
 * or the existing observer if the value already has one.
 */
function observe(value, shallow, ssrMockReactivity) {
    if (value && hasOwn(value, '__ob__') && value.__ob__ instanceof Observer) {
        return value.__ob__;
    }
    if (shouldObserve &&
        (ssrMockReactivity || !isServerRendering()) &&
        (isArray(value) || isPlainObject(value)) &&
        Object.isExtensible(value) &&
        !value.__v_skip /* ReactiveFlags.SKIP */ &&
        !isRef(value) &&
        !(value instanceof VNode)) {
        return new Observer(value, shallow, ssrMockReactivity);
    }
}
/**
 * Define a reactive property on an Object.
 */
function defineReactive(obj, key, val, customSetter, shallow, mock) {
    var dep = new Dep();
    var property = Object.getOwnPropertyDescriptor(obj, key);
    if (property && property.configurable === false) {
        return;
    }
    // cater for pre-defined getter/setters
    var getter = property && property.get;
    var setter = property && property.set;
    if ((!getter || setter) &&
        (val === NO_INITIAL_VALUE || arguments.length === 2)) {
        val = obj[key];
    }
    var childOb = !shallow && observe(val, false, mock);
    Object.defineProperty(obj, key, {
        enumerable: true,
        configurable: true,
        get: function reactiveGetter() {
            var value = getter ? getter.call(obj) : val;
            if (Dep.target) {
                if (false) {}
                else {
                    dep.depend();
                }
                if (childOb) {
                    childOb.dep.depend();
                    if (isArray(value)) {
                        dependArray(value);
                    }
                }
            }
            return isRef(value) && !shallow ? value.value : value;
        },
        set: function reactiveSetter(newVal) {
            var value = getter ? getter.call(obj) : val;
            if (!hasChanged(value, newVal)) {
                return;
            }
            if (false) {}
            if (setter) {
                setter.call(obj, newVal);
            }
            else if (getter) {
                // #7981: for accessor properties without setter
                return;
            }
            else if (!shallow && isRef(value) && !isRef(newVal)) {
                value.value = newVal;
                return;
            }
            else {
                val = newVal;
            }
            childOb = !shallow && observe(newVal, false, mock);
            if (false) {}
            else {
                dep.notify();
            }
        }
    });
    return dep;
}
function set(target, key, val) {
    if (false) {}
    if (isReadonly(target)) {
         false && 0;
        return;
    }
    var ob = target.__ob__;
    if (isArray(target) && isValidArrayIndex(key)) {
        target.length = Math.max(target.length, key);
        target.splice(key, 1, val);
        // when mocking for SSR, array methods are not hijacked
        if (ob && !ob.shallow && ob.mock) {
            observe(val, false, true);
        }
        return val;
    }
    if (key in target && !(key in Object.prototype)) {
        target[key] = val;
        return val;
    }
    if (target._isVue || (ob && ob.vmCount)) {
         false &&
            0;
        return val;
    }
    if (!ob) {
        target[key] = val;
        return val;
    }
    defineReactive(ob.value, key, val, undefined, ob.shallow, ob.mock);
    if (false) {}
    else {
        ob.dep.notify();
    }
    return val;
}
function del(target, key) {
    if (false) {}
    if (isArray(target) && isValidArrayIndex(key)) {
        target.splice(key, 1);
        return;
    }
    var ob = target.__ob__;
    if (target._isVue || (ob && ob.vmCount)) {
         false &&
            0;
        return;
    }
    if (isReadonly(target)) {
         false &&
            0;
        return;
    }
    if (!hasOwn(target, key)) {
        return;
    }
    delete target[key];
    if (!ob) {
        return;
    }
    if (false) {}
    else {
        ob.dep.notify();
    }
}
/**
 * Collect dependencies on array elements when the array is touched, since
 * we cannot intercept array element access like property getters.
 */
function dependArray(value) {
    for (var e = void 0, i = 0, l = value.length; i < l; i++) {
        e = value[i];
        if (e && e.__ob__) {
            e.__ob__.dep.depend();
        }
        if (isArray(e)) {
            dependArray(e);
        }
    }
}

function reactive(target) {
    makeReactive(target, false);
    return target;
}
/**
 * Return a shallowly-reactive copy of the original object, where only the root
 * level properties are reactive. It also does not auto-unwrap refs (even at the
 * root level).
 */
function shallowReactive(target) {
    makeReactive(target, true);
    def(target, "__v_isShallow" /* ReactiveFlags.IS_SHALLOW */, true);
    return target;
}
function makeReactive(target, shallow) {
    // if trying to observe a readonly proxy, return the readonly version.
    if (!isReadonly(target)) {
        if (false) { var existingOb; }
        var ob = observe(target, shallow, isServerRendering() /* ssr mock reactivity */);
        if (false) {}
    }
}
function isReactive(value) {
    if (isReadonly(value)) {
        return isReactive(value["__v_raw" /* ReactiveFlags.RAW */]);
    }
    return !!(value && value.__ob__);
}
function isShallow(value) {
    return !!(value && value.__v_isShallow);
}
function isReadonly(value) {
    return !!(value && value.__v_isReadonly);
}
function isProxy(value) {
    return isReactive(value) || isReadonly(value);
}
function toRaw(observed) {
    var raw = observed && observed["__v_raw" /* ReactiveFlags.RAW */];
    return raw ? toRaw(raw) : observed;
}
function markRaw(value) {
    // non-extensible objects won't be observed anyway
    if (Object.isExtensible(value)) {
        def(value, "__v_skip" /* ReactiveFlags.SKIP */, true);
    }
    return value;
}
/**
 * @internal
 */
function isCollectionType(value) {
    var type = toRawType(value);
    return (type === 'Map' || type === 'WeakMap' || type === 'Set' || type === 'WeakSet');
}

/**
 * @internal
 */
var RefFlag = "__v_isRef";
function isRef(r) {
    return !!(r && r.__v_isRef === true);
}
function ref$1(value) {
    return createRef(value, false);
}
function shallowRef(value) {
    return createRef(value, true);
}
function createRef(rawValue, shallow) {
    if (isRef(rawValue)) {
        return rawValue;
    }
    var ref = {};
    def(ref, RefFlag, true);
    def(ref, "__v_isShallow" /* ReactiveFlags.IS_SHALLOW */, shallow);
    def(ref, 'dep', defineReactive(ref, 'value', rawValue, null, shallow, isServerRendering()));
    return ref;
}
function triggerRef(ref) {
    if (false) {}
    if (false) {}
    else {
        ref.dep && ref.dep.notify();
    }
}
function unref(ref) {
    return isRef(ref) ? ref.value : ref;
}
function proxyRefs(objectWithRefs) {
    if (isReactive(objectWithRefs)) {
        return objectWithRefs;
    }
    var proxy = {};
    var keys = Object.keys(objectWithRefs);
    for (var i = 0; i < keys.length; i++) {
        proxyWithRefUnwrap(proxy, objectWithRefs, keys[i]);
    }
    return proxy;
}
function proxyWithRefUnwrap(target, source, key) {
    Object.defineProperty(target, key, {
        enumerable: true,
        configurable: true,
        get: function () {
            var val = source[key];
            if (isRef(val)) {
                return val.value;
            }
            else {
                var ob = val && val.__ob__;
                if (ob)
                    ob.dep.depend();
                return val;
            }
        },
        set: function (value) {
            var oldValue = source[key];
            if (isRef(oldValue) && !isRef(value)) {
                oldValue.value = value;
            }
            else {
                source[key] = value;
            }
        }
    });
}
function customRef(factory) {
    var dep = new Dep();
    var _a = factory(function () {
        if (false) {}
        else {
            dep.depend();
        }
    }, function () {
        if (false) {}
        else {
            dep.notify();
        }
    }), get = _a.get, set = _a.set;
    var ref = {
        get value() {
            return get();
        },
        set value(newVal) {
            set(newVal);
        }
    };
    def(ref, RefFlag, true);
    return ref;
}
function toRefs(object) {
    if (false) {}
    var ret = isArray(object) ? new Array(object.length) : {};
    for (var key in object) {
        ret[key] = toRef(object, key);
    }
    return ret;
}
function toRef(object, key, defaultValue) {
    var val = object[key];
    if (isRef(val)) {
        return val;
    }
    var ref = {
        get value() {
            var val = object[key];
            return val === undefined ? defaultValue : val;
        },
        set value(newVal) {
            object[key] = newVal;
        }
    };
    def(ref, RefFlag, true);
    return ref;
}

var rawToReadonlyFlag = "__v_rawToReadonly";
var rawToShallowReadonlyFlag = "__v_rawToShallowReadonly";
function readonly(target) {
    return createReadonly(target, false);
}
function createReadonly(target, shallow) {
    if (!isPlainObject(target)) {
        if (false) {}
        return target;
    }
    if (false) {}
    // already a readonly object
    if (isReadonly(target)) {
        return target;
    }
    // already has a readonly proxy
    var existingFlag = shallow ? rawToShallowReadonlyFlag : rawToReadonlyFlag;
    var existingProxy = target[existingFlag];
    if (existingProxy) {
        return existingProxy;
    }
    var proxy = Object.create(Object.getPrototypeOf(target));
    def(target, existingFlag, proxy);
    def(proxy, "__v_isReadonly" /* ReactiveFlags.IS_READONLY */, true);
    def(proxy, "__v_raw" /* ReactiveFlags.RAW */, target);
    if (isRef(target)) {
        def(proxy, RefFlag, true);
    }
    if (shallow || isShallow(target)) {
        def(proxy, "__v_isShallow" /* ReactiveFlags.IS_SHALLOW */, true);
    }
    var keys = Object.keys(target);
    for (var i = 0; i < keys.length; i++) {
        defineReadonlyProperty(proxy, target, keys[i], shallow);
    }
    return proxy;
}
function defineReadonlyProperty(proxy, target, key, shallow) {
    Object.defineProperty(proxy, key, {
        enumerable: true,
        configurable: true,
        get: function () {
            var val = target[key];
            return shallow || !isPlainObject(val) ? val : readonly(val);
        },
        set: function () {
             false &&
                0;
        }
    });
}
/**
 * Returns a reactive-copy of the original object, where only the root level
 * properties are readonly, and does NOT unwrap refs nor recursively convert
 * returned properties.
 * This is used for creating the props proxy object for stateful components.
 */
function shallowReadonly(target) {
    return createReadonly(target, true);
}

function computed(getterOrOptions, debugOptions) {
    var getter;
    var setter;
    var onlyGetter = isFunction(getterOrOptions);
    if (onlyGetter) {
        getter = getterOrOptions;
        setter =  false
            ? 0
            : noop;
    }
    else {
        getter = getterOrOptions.get;
        setter = getterOrOptions.set;
    }
    var watcher = isServerRendering()
        ? null
        : new Watcher(currentInstance, getter, noop, { lazy: true });
    if (false) {}
    var ref = {
        // some libs rely on the presence effect for checking computed refs
        // from normal refs, but the implementation doesn't matter
        effect: watcher,
        get value() {
            if (watcher) {
                if (watcher.dirty) {
                    watcher.evaluate();
                }
                if (Dep.target) {
                    if (false) {}
                    watcher.depend();
                }
                return watcher.value;
            }
            else {
                return getter();
            }
        },
        set value(newVal) {
            setter(newVal);
        }
    };
    def(ref, RefFlag, true);
    def(ref, "__v_isReadonly" /* ReactiveFlags.IS_READONLY */, onlyGetter);
    return ref;
}

var WATCHER = "watcher";
var WATCHER_CB = "".concat(WATCHER, " callback");
var WATCHER_GETTER = "".concat(WATCHER, " getter");
var WATCHER_CLEANUP = "".concat(WATCHER, " cleanup");
// Simple effect.
function watchEffect(effect, options) {
    return doWatch(effect, null, options);
}
function watchPostEffect(effect, options) {
    return doWatch(effect, null, ( false
        ? 0 : { flush: 'post' }));
}
function watchSyncEffect(effect, options) {
    return doWatch(effect, null, ( false
        ? 0 : { flush: 'sync' }));
}
// initial value for watchers to trigger on undefined initial values
var INITIAL_WATCHER_VALUE = {};
// implementation
function watch(source, cb, options) {
    if (false) {}
    return doWatch(source, cb, options);
}
function doWatch(source, cb, _a) {
    var _b = _a === void 0 ? emptyObject : _a, immediate = _b.immediate, deep = _b.deep, _c = _b.flush, flush = _c === void 0 ? 'pre' : _c, onTrack = _b.onTrack, onTrigger = _b.onTrigger;
    if (false) {}
    var warnInvalidSource = function (s) {
        warn("Invalid watch source: ".concat(s, ". A watch source can only be a getter/effect ") +
            "function, a ref, a reactive object, or an array of these types.");
    };
    var instance = currentInstance;
    var call = function (fn, type, args) {
        if (args === void 0) { args = null; }
        return invokeWithErrorHandling(fn, null, args, instance, type);
    };
    var getter;
    var forceTrigger = false;
    var isMultiSource = false;
    if (isRef(source)) {
        getter = function () { return source.value; };
        forceTrigger = isShallow(source);
    }
    else if (isReactive(source)) {
        getter = function () {
            source.__ob__.dep.depend();
            return source;
        };
        deep = true;
    }
    else if (isArray(source)) {
        isMultiSource = true;
        forceTrigger = source.some(function (s) { return isReactive(s) || isShallow(s); });
        getter = function () {
            return source.map(function (s) {
                if (isRef(s)) {
                    return s.value;
                }
                else if (isReactive(s)) {
                    return traverse(s);
                }
                else if (isFunction(s)) {
                    return call(s, WATCHER_GETTER);
                }
                else {
                     false && 0;
                }
            });
        };
    }
    else if (isFunction(source)) {
        if (cb) {
            // getter with cb
            getter = function () { return call(source, WATCHER_GETTER); };
        }
        else {
            // no cb -> simple effect
            getter = function () {
                if (instance && instance._isDestroyed) {
                    return;
                }
                if (cleanup) {
                    cleanup();
                }
                return call(source, WATCHER, [onCleanup]);
            };
        }
    }
    else {
        getter = noop;
         false && 0;
    }
    if (cb && deep) {
        var baseGetter_1 = getter;
        getter = function () { return traverse(baseGetter_1()); };
    }
    var cleanup;
    var onCleanup = function (fn) {
        cleanup = watcher.onStop = function () {
            call(fn, WATCHER_CLEANUP);
        };
    };
    // in SSR there is no need to setup an actual effect, and it should be noop
    // unless it's eager
    if (isServerRendering()) {
        // we will also not call the invalidate callback (+ runner is not set up)
        onCleanup = noop;
        if (!cb) {
            getter();
        }
        else if (immediate) {
            call(cb, WATCHER_CB, [
                getter(),
                isMultiSource ? [] : undefined,
                onCleanup
            ]);
        }
        return noop;
    }
    var watcher = new Watcher(currentInstance, getter, noop, {
        lazy: true
    });
    watcher.noRecurse = !cb;
    var oldValue = isMultiSource ? [] : INITIAL_WATCHER_VALUE;
    // overwrite default run
    watcher.run = function () {
        if (!watcher.active) {
            return;
        }
        if (cb) {
            // watch(source, cb)
            var newValue = watcher.get();
            if (deep ||
                forceTrigger ||
                (isMultiSource
                    ? newValue.some(function (v, i) {
                        return hasChanged(v, oldValue[i]);
                    })
                    : hasChanged(newValue, oldValue))) {
                // cleanup before running cb again
                if (cleanup) {
                    cleanup();
                }
                call(cb, WATCHER_CB, [
                    newValue,
                    // pass undefined as the old value when it's changed for the first time
                    oldValue === INITIAL_WATCHER_VALUE ? undefined : oldValue,
                    onCleanup
                ]);
                oldValue = newValue;
            }
        }
        else {
            // watchEffect
            watcher.get();
        }
    };
    if (flush === 'sync') {
        watcher.update = watcher.run;
    }
    else if (flush === 'post') {
        watcher.post = true;
        watcher.update = function () { return queueWatcher(watcher); };
    }
    else {
        // pre
        watcher.update = function () {
            if (instance && instance === currentInstance && !instance._isMounted) {
                // pre-watcher triggered before
                var buffer = instance._preWatchers || (instance._preWatchers = []);
                if (buffer.indexOf(watcher) < 0)
                    buffer.push(watcher);
            }
            else {
                queueWatcher(watcher);
            }
        };
    }
    if (false) {}
    // initial run
    if (cb) {
        if (immediate) {
            watcher.run();
        }
        else {
            oldValue = watcher.get();
        }
    }
    else if (flush === 'post' && instance) {
        instance.$once('hook:mounted', function () { return watcher.get(); });
    }
    else {
        watcher.get();
    }
    return function () {
        watcher.teardown();
    };
}

var activeEffectScope;
var EffectScope = /** @class */ (function () {
    function EffectScope(detached) {
        if (detached === void 0) { detached = false; }
        this.detached = detached;
        /**
         * @internal
         */
        this.active = true;
        /**
         * @internal
         */
        this.effects = [];
        /**
         * @internal
         */
        this.cleanups = [];
        this.parent = activeEffectScope;
        if (!detached && activeEffectScope) {
            this.index =
                (activeEffectScope.scopes || (activeEffectScope.scopes = [])).push(this) - 1;
        }
    }
    EffectScope.prototype.run = function (fn) {
        if (this.active) {
            var currentEffectScope = activeEffectScope;
            try {
                activeEffectScope = this;
                return fn();
            }
            finally {
                activeEffectScope = currentEffectScope;
            }
        }
        else if (false) {}
    };
    /**
     * This should only be called on non-detached scopes
     * @internal
     */
    EffectScope.prototype.on = function () {
        activeEffectScope = this;
    };
    /**
     * This should only be called on non-detached scopes
     * @internal
     */
    EffectScope.prototype.off = function () {
        activeEffectScope = this.parent;
    };
    EffectScope.prototype.stop = function (fromParent) {
        if (this.active) {
            var i = void 0, l = void 0;
            for (i = 0, l = this.effects.length; i < l; i++) {
                this.effects[i].teardown();
            }
            for (i = 0, l = this.cleanups.length; i < l; i++) {
                this.cleanups[i]();
            }
            if (this.scopes) {
                for (i = 0, l = this.scopes.length; i < l; i++) {
                    this.scopes[i].stop(true);
                }
            }
            // nested scope, dereference from parent to avoid memory leaks
            if (!this.detached && this.parent && !fromParent) {
                // optimized O(1) removal
                var last = this.parent.scopes.pop();
                if (last && last !== this) {
                    this.parent.scopes[this.index] = last;
                    last.index = this.index;
                }
            }
            this.parent = undefined;
            this.active = false;
        }
    };
    return EffectScope;
}());
function effectScope(detached) {
    return new EffectScope(detached);
}
/**
 * @internal
 */
function recordEffectScope(effect, scope) {
    if (scope === void 0) { scope = activeEffectScope; }
    if (scope && scope.active) {
        scope.effects.push(effect);
    }
}
function getCurrentScope() {
    return activeEffectScope;
}
function onScopeDispose(fn) {
    if (activeEffectScope) {
        activeEffectScope.cleanups.push(fn);
    }
    else if (false) {}
}

function provide(key, value) {
    if (!currentInstance) {
        if (false) {}
    }
    else {
        // TS doesn't allow symbol as index type
        resolveProvided(currentInstance)[key] = value;
    }
}
function resolveProvided(vm) {
    // by default an instance inherits its parent's provides object
    // but when it needs to provide values of its own, it creates its
    // own provides object using parent provides object as prototype.
    // this way in `inject` we can simply look up injections from direct
    // parent and let the prototype chain do the work.
    var existing = vm._provided;
    var parentProvides = vm.$parent && vm.$parent._provided;
    if (parentProvides === existing) {
        return (vm._provided = Object.create(parentProvides));
    }
    else {
        return existing;
    }
}
function inject(key, defaultValue, treatDefaultAsFactory) {
    if (treatDefaultAsFactory === void 0) { treatDefaultAsFactory = false; }
    // fallback to `currentRenderingInstance` so that this can be called in
    // a functional component
    var instance = currentInstance;
    if (instance) {
        // #2400
        // to support `app.use` plugins,
        // fallback to appContext's `provides` if the instance is at root
        var provides = instance.$parent && instance.$parent._provided;
        if (provides && key in provides) {
            // TS doesn't allow symbol as index type
            return provides[key];
        }
        else if (arguments.length > 1) {
            return treatDefaultAsFactory && isFunction(defaultValue)
                ? defaultValue.call(instance)
                : defaultValue;
        }
        else if (false) {}
    }
    else if (false) {}
}

var normalizeEvent = cached(function (name) {
    var passive = name.charAt(0) === '&';
    name = passive ? name.slice(1) : name;
    var once = name.charAt(0) === '~'; // Prefixed last, checked first
    name = once ? name.slice(1) : name;
    var capture = name.charAt(0) === '!';
    name = capture ? name.slice(1) : name;
    return {
        name: name,
        once: once,
        capture: capture,
        passive: passive
    };
});
function createFnInvoker(fns, vm) {
    function invoker() {
        var fns = invoker.fns;
        if (isArray(fns)) {
            var cloned = fns.slice();
            for (var i = 0; i < cloned.length; i++) {
                invokeWithErrorHandling(cloned[i], null, arguments, vm, "v-on handler");
            }
        }
        else {
            // return handler return value for single handlers
            return invokeWithErrorHandling(fns, null, arguments, vm, "v-on handler");
        }
    }
    invoker.fns = fns;
    return invoker;
}
function updateListeners(on, oldOn, add, remove, createOnceHandler, vm) {
    var name, cur, old, event;
    for (name in on) {
        cur = on[name];
        old = oldOn[name];
        event = normalizeEvent(name);
        if (isUndef(cur)) {
             false &&
                0;
        }
        else if (isUndef(old)) {
            if (isUndef(cur.fns)) {
                cur = on[name] = createFnInvoker(cur, vm);
            }
            if (isTrue(event.once)) {
                cur = on[name] = createOnceHandler(event.name, cur, event.capture);
            }
            add(event.name, cur, event.capture, event.passive, event.params);
        }
        else if (cur !== old) {
            old.fns = cur;
            on[name] = old;
        }
    }
    for (name in oldOn) {
        if (isUndef(on[name])) {
            event = normalizeEvent(name);
            remove(event.name, oldOn[name], event.capture);
        }
    }
}

function mergeVNodeHook(def, hookKey, hook) {
    if (def instanceof VNode) {
        def = def.data.hook || (def.data.hook = {});
    }
    var invoker;
    var oldHook = def[hookKey];
    function wrappedHook() {
        hook.apply(this, arguments);
        // important: remove merged hook to ensure it's called only once
        // and prevent memory leak
        remove$2(invoker.fns, wrappedHook);
    }
    if (isUndef(oldHook)) {
        // no existing hook
        invoker = createFnInvoker([wrappedHook]);
    }
    else {
        /* istanbul ignore if */
        if (isDef(oldHook.fns) && isTrue(oldHook.merged)) {
            // already a merged invoker
            invoker = oldHook;
            invoker.fns.push(wrappedHook);
        }
        else {
            // existing plain hook
            invoker = createFnInvoker([oldHook, wrappedHook]);
        }
    }
    invoker.merged = true;
    def[hookKey] = invoker;
}

function extractPropsFromVNodeData(data, Ctor, tag) {
    // we are only extracting raw values here.
    // validation and default values are handled in the child
    // component itself.
    var propOptions = Ctor.options.props;
    if (isUndef(propOptions)) {
        return;
    }
    var res = {};
    var attrs = data.attrs, props = data.props;
    if (isDef(attrs) || isDef(props)) {
        for (var key in propOptions) {
            var altKey = hyphenate(key);
            if (false) { var keyInLowerCase; }
            checkProp(res, props, key, altKey, true) ||
                checkProp(res, attrs, key, altKey, false);
        }
    }
    return res;
}
function checkProp(res, hash, key, altKey, preserve) {
    if (isDef(hash)) {
        if (hasOwn(hash, key)) {
            res[key] = hash[key];
            if (!preserve) {
                delete hash[key];
            }
            return true;
        }
        else if (hasOwn(hash, altKey)) {
            res[key] = hash[altKey];
            if (!preserve) {
                delete hash[altKey];
            }
            return true;
        }
    }
    return false;
}

// The template compiler attempts to minimize the need for normalization by
// statically analyzing the template at compile time.
//
// For plain HTML markup, normalization can be completely skipped because the
// generated render function is guaranteed to return Array<VNode>. There are
// two cases where extra normalization is needed:
// 1. When the children contains components - because a functional component
// may return an Array instead of a single root. In this case, just a simple
// normalization is needed - if any child is an Array, we flatten the whole
// thing with Array.prototype.concat. It is guaranteed to be only 1-level deep
// because functional components already normalize their own children.
function simpleNormalizeChildren(children) {
    for (var i = 0; i < children.length; i++) {
        if (isArray(children[i])) {
            return Array.prototype.concat.apply([], children);
        }
    }
    return children;
}
// 2. When the children contains constructs that always generated nested Arrays,
// e.g. <template>, <slot>, v-for, or when the children is provided by user
// with hand-written render functions / JSX. In such cases a full normalization
// is needed to cater to all possible types of children values.
function normalizeChildren(children) {
    return isPrimitive(children)
        ? [createTextVNode(children)]
        : isArray(children)
            ? normalizeArrayChildren(children)
            : undefined;
}
function isTextNode(node) {
    return isDef(node) && isDef(node.text) && isFalse(node.isComment);
}
function normalizeArrayChildren(children, nestedIndex) {
    var res = [];
    var i, c, lastIndex, last;
    for (i = 0; i < children.length; i++) {
        c = children[i];
        if (isUndef(c) || typeof c === 'boolean')
            continue;
        lastIndex = res.length - 1;
        last = res[lastIndex];
        //  nested
        if (isArray(c)) {
            if (c.length > 0) {
                c = normalizeArrayChildren(c, "".concat(nestedIndex || '', "_").concat(i));
                // merge adjacent text nodes
                if (isTextNode(c[0]) && isTextNode(last)) {
                    res[lastIndex] = createTextVNode(last.text + c[0].text);
                    c.shift();
                }
                res.push.apply(res, c);
            }
        }
        else if (isPrimitive(c)) {
            if (isTextNode(last)) {
                // merge adjacent text nodes
                // this is necessary for SSR hydration because text nodes are
                // essentially merged when rendered to HTML strings
                res[lastIndex] = createTextVNode(last.text + c);
            }
            else if (c !== '') {
                // convert primitive to vnode
                res.push(createTextVNode(c));
            }
        }
        else {
            if (isTextNode(c) && isTextNode(last)) {
                // merge adjacent text nodes
                res[lastIndex] = createTextVNode(last.text + c.text);
            }
            else {
                // default key for nested array children (likely generated by v-for)
                if (isTrue(children._isVList) &&
                    isDef(c.tag) &&
                    isUndef(c.key) &&
                    isDef(nestedIndex)) {
                    c.key = "__vlist".concat(nestedIndex, "_").concat(i, "__");
                }
                res.push(c);
            }
        }
    }
    return res;
}

/**
 * Runtime helper for rendering v-for lists.
 */
function renderList(val, render) {
    var ret = null, i, l, keys, key;
    if (isArray(val) || typeof val === 'string') {
        ret = new Array(val.length);
        for (i = 0, l = val.length; i < l; i++) {
            ret[i] = render(val[i], i);
        }
    }
    else if (typeof val === 'number') {
        ret = new Array(val);
        for (i = 0; i < val; i++) {
            ret[i] = render(i + 1, i);
        }
    }
    else if (isObject(val)) {
        if (hasSymbol && val[Symbol.iterator]) {
            ret = [];
            var iterator = val[Symbol.iterator]();
            var result = iterator.next();
            while (!result.done) {
                ret.push(render(result.value, ret.length));
                result = iterator.next();
            }
        }
        else {
            keys = Object.keys(val);
            ret = new Array(keys.length);
            for (i = 0, l = keys.length; i < l; i++) {
                key = keys[i];
                ret[i] = render(val[key], key, i);
            }
        }
    }
    if (!isDef(ret)) {
        ret = [];
    }
    ret._isVList = true;
    return ret;
}

/**
 * Runtime helper for rendering <slot>
 */
function renderSlot(name, fallbackRender, props, bindObject) {
    var scopedSlotFn = this.$scopedSlots[name];
    var nodes;
    if (scopedSlotFn) {
        // scoped slot
        props = props || {};
        if (bindObject) {
            if (false) {}
            props = extend(extend({}, bindObject), props);
        }
        nodes =
            scopedSlotFn(props) ||
                (isFunction(fallbackRender) ? fallbackRender() : fallbackRender);
    }
    else {
        nodes =
            this.$slots[name] ||
                (isFunction(fallbackRender) ? fallbackRender() : fallbackRender);
    }
    var target = props && props.slot;
    if (target) {
        return this.$createElement('template', { slot: target }, nodes);
    }
    else {
        return nodes;
    }
}

/**
 * Runtime helper for resolving filters
 */
function resolveFilter(id) {
    return resolveAsset(this.$options, 'filters', id, true) || identity;
}

function isKeyNotMatch(expect, actual) {
    if (isArray(expect)) {
        return expect.indexOf(actual) === -1;
    }
    else {
        return expect !== actual;
    }
}
/**
 * Runtime helper for checking keyCodes from config.
 * exposed as Vue.prototype._k
 * passing in eventKeyName as last argument separately for backwards compat
 */
function checkKeyCodes(eventKeyCode, key, builtInKeyCode, eventKeyName, builtInKeyName) {
    var mappedKeyCode = config.keyCodes[key] || builtInKeyCode;
    if (builtInKeyName && eventKeyName && !config.keyCodes[key]) {
        return isKeyNotMatch(builtInKeyName, eventKeyName);
    }
    else if (mappedKeyCode) {
        return isKeyNotMatch(mappedKeyCode, eventKeyCode);
    }
    else if (eventKeyName) {
        return hyphenate(eventKeyName) !== key;
    }
    return eventKeyCode === undefined;
}

/**
 * Runtime helper for merging v-bind="object" into a VNode's data.
 */
function bindObjectProps(data, tag, value, asProp, isSync) {
    if (value) {
        if (!isObject(value)) {
             false &&
                0;
        }
        else {
            if (isArray(value)) {
                value = toObject(value);
            }
            var hash = void 0;
            var _loop_1 = function (key) {
                if (key === 'class' || key === 'style' || isReservedAttribute(key)) {
                    hash = data;
                }
                else {
                    var type = data.attrs && data.attrs.type;
                    hash =
                        asProp || config.mustUseProp(tag, type, key)
                            ? data.domProps || (data.domProps = {})
                            : data.attrs || (data.attrs = {});
                }
                var camelizedKey = camelize(key);
                var hyphenatedKey = hyphenate(key);
                if (!(camelizedKey in hash) && !(hyphenatedKey in hash)) {
                    hash[key] = value[key];
                    if (isSync) {
                        var on = data.on || (data.on = {});
                        on["update:".concat(key)] = function ($event) {
                            value[key] = $event;
                        };
                    }
                }
            };
            for (var key in value) {
                _loop_1(key);
            }
        }
    }
    return data;
}

/**
 * Runtime helper for rendering static trees.
 */
function renderStatic(index, isInFor) {
    var cached = this._staticTrees || (this._staticTrees = []);
    var tree = cached[index];
    // if has already-rendered static tree and not inside v-for,
    // we can reuse the same tree.
    if (tree && !isInFor) {
        return tree;
    }
    // otherwise, render a fresh tree.
    tree = cached[index] = this.$options.staticRenderFns[index].call(this._renderProxy, this._c, this // for render fns generated for functional component templates
    );
    markStatic(tree, "__static__".concat(index), false);
    return tree;
}
/**
 * Runtime helper for v-once.
 * Effectively it means marking the node as static with a unique key.
 */
function markOnce(tree, index, key) {
    markStatic(tree, "__once__".concat(index).concat(key ? "_".concat(key) : ""), true);
    return tree;
}
function markStatic(tree, key, isOnce) {
    if (isArray(tree)) {
        for (var i = 0; i < tree.length; i++) {
            if (tree[i] && typeof tree[i] !== 'string') {
                markStaticNode(tree[i], "".concat(key, "_").concat(i), isOnce);
            }
        }
    }
    else {
        markStaticNode(tree, key, isOnce);
    }
}
function markStaticNode(node, key, isOnce) {
    node.isStatic = true;
    node.key = key;
    node.isOnce = isOnce;
}

function bindObjectListeners(data, value) {
    if (value) {
        if (!isPlainObject(value)) {
             false && 0;
        }
        else {
            var on = (data.on = data.on ? extend({}, data.on) : {});
            for (var key in value) {
                var existing = on[key];
                var ours = value[key];
                on[key] = existing ? [].concat(existing, ours) : ours;
            }
        }
    }
    return data;
}

function resolveScopedSlots(fns, res, 
// the following are added in 2.6
hasDynamicKeys, contentHashKey) {
    res = res || { $stable: !hasDynamicKeys };
    for (var i = 0; i < fns.length; i++) {
        var slot = fns[i];
        if (isArray(slot)) {
            resolveScopedSlots(slot, res, hasDynamicKeys);
        }
        else if (slot) {
            // marker for reverse proxying v-slot without scope on this.$slots
            // @ts-expect-error
            if (slot.proxy) {
                // @ts-expect-error
                slot.fn.proxy = true;
            }
            res[slot.key] = slot.fn;
        }
    }
    if (contentHashKey) {
        res.$key = contentHashKey;
    }
    return res;
}

// helper to process dynamic keys for dynamic arguments in v-bind and v-on.
function bindDynamicKeys(baseObj, values) {
    for (var i = 0; i < values.length; i += 2) {
        var key = values[i];
        if (typeof key === 'string' && key) {
            baseObj[values[i]] = values[i + 1];
        }
        else if (false) {}
    }
    return baseObj;
}
// helper to dynamically append modifier runtime markers to event names.
// ensure only append when value is already string, otherwise it will be cast
// to string and cause the type check to miss.
function prependModifier(value, symbol) {
    return typeof value === 'string' ? symbol + value : value;
}

function installRenderHelpers(target) {
    target._o = markOnce;
    target._n = toNumber;
    target._s = vue_runtime_esm_toString;
    target._l = renderList;
    target._t = renderSlot;
    target._q = looseEqual;
    target._i = looseIndexOf;
    target._m = renderStatic;
    target._f = resolveFilter;
    target._k = checkKeyCodes;
    target._b = bindObjectProps;
    target._v = createTextVNode;
    target._e = createEmptyVNode;
    target._u = resolveScopedSlots;
    target._g = bindObjectListeners;
    target._d = bindDynamicKeys;
    target._p = prependModifier;
}

/**
 * Runtime helper for resolving raw children VNodes into a slot object.
 */
function resolveSlots(children, context) {
    if (!children || !children.length) {
        return {};
    }
    var slots = {};
    for (var i = 0, l = children.length; i < l; i++) {
        var child = children[i];
        var data = child.data;
        // remove slot attribute if the node is resolved as a Vue slot node
        if (data && data.attrs && data.attrs.slot) {
            delete data.attrs.slot;
        }
        // named slots should only be respected if the vnode was rendered in the
        // same context.
        if ((child.context === context || child.fnContext === context) &&
            data &&
            data.slot != null) {
            var name_1 = data.slot;
            var slot = slots[name_1] || (slots[name_1] = []);
            if (child.tag === 'template') {
                slot.push.apply(slot, child.children || []);
            }
            else {
                slot.push(child);
            }
        }
        else {
            (slots.default || (slots.default = [])).push(child);
        }
    }
    // ignore slots that contains only whitespace
    for (var name_2 in slots) {
        if (slots[name_2].every(isWhitespace)) {
            delete slots[name_2];
        }
    }
    return slots;
}
function isWhitespace(node) {
    return (node.isComment && !node.asyncFactory) || node.text === ' ';
}

function isAsyncPlaceholder(node) {
    // @ts-expect-error not really boolean type
    return node.isComment && node.asyncFactory;
}

function normalizeScopedSlots(ownerVm, scopedSlots, normalSlots, prevScopedSlots) {
    var res;
    var hasNormalSlots = Object.keys(normalSlots).length > 0;
    var isStable = scopedSlots ? !!scopedSlots.$stable : !hasNormalSlots;
    var key = scopedSlots && scopedSlots.$key;
    if (!scopedSlots) {
        res = {};
    }
    else if (scopedSlots._normalized) {
        // fast path 1: child component re-render only, parent did not change
        return scopedSlots._normalized;
    }
    else if (isStable &&
        prevScopedSlots &&
        prevScopedSlots !== emptyObject &&
        key === prevScopedSlots.$key &&
        !hasNormalSlots &&
        !prevScopedSlots.$hasNormal) {
        // fast path 2: stable scoped slots w/ no normal slots to proxy,
        // only need to normalize once
        return prevScopedSlots;
    }
    else {
        res = {};
        for (var key_1 in scopedSlots) {
            if (scopedSlots[key_1] && key_1[0] !== '$') {
                res[key_1] = normalizeScopedSlot(ownerVm, normalSlots, key_1, scopedSlots[key_1]);
            }
        }
    }
    // expose normal slots on scopedSlots
    for (var key_2 in normalSlots) {
        if (!(key_2 in res)) {
            res[key_2] = proxyNormalSlot(normalSlots, key_2);
        }
    }
    // avoriaz seems to mock a non-extensible $scopedSlots object
    // and when that is passed down this would cause an error
    if (scopedSlots && Object.isExtensible(scopedSlots)) {
        scopedSlots._normalized = res;
    }
    def(res, '$stable', isStable);
    def(res, '$key', key);
    def(res, '$hasNormal', hasNormalSlots);
    return res;
}
function normalizeScopedSlot(vm, normalSlots, key, fn) {
    var normalized = function () {
        var cur = currentInstance;
        setCurrentInstance(vm);
        var res = arguments.length ? fn.apply(null, arguments) : fn({});
        res =
            res && typeof res === 'object' && !isArray(res)
                ? [res] // single vnode
                : normalizeChildren(res);
        var vnode = res && res[0];
        setCurrentInstance(cur);
        return res &&
            (!vnode ||
                (res.length === 1 && vnode.isComment && !isAsyncPlaceholder(vnode))) // #9658, #10391
            ? undefined
            : res;
    };
    // this is a slot using the new v-slot syntax without scope. although it is
    // compiled as a scoped slot, render fn users would expect it to be present
    // on this.$slots because the usage is semantically a normal slot.
    if (fn.proxy) {
        Object.defineProperty(normalSlots, key, {
            get: normalized,
            enumerable: true,
            configurable: true
        });
    }
    return normalized;
}
function proxyNormalSlot(slots, key) {
    return function () { return slots[key]; };
}

function initSetup(vm) {
    var options = vm.$options;
    var setup = options.setup;
    if (setup) {
        var ctx = (vm._setupContext = createSetupContext(vm));
        setCurrentInstance(vm);
        pushTarget();
        var setupResult = invokeWithErrorHandling(setup, null, [vm._props || shallowReactive({}), ctx], vm, "setup");
        popTarget();
        setCurrentInstance();
        if (isFunction(setupResult)) {
            // render function
            // @ts-ignore
            options.render = setupResult;
        }
        else if (isObject(setupResult)) {
            // bindings
            if (false) {}
            vm._setupState = setupResult;
            // __sfc indicates compiled bindings from <script setup>
            if (!setupResult.__sfc) {
                for (var key in setupResult) {
                    if (!isReserved(key)) {
                        proxyWithRefUnwrap(vm, setupResult, key);
                    }
                    else if (false) {}
                }
            }
            else {
                // exposed for compiled render fn
                var proxy = (vm._setupProxy = {});
                for (var key in setupResult) {
                    if (key !== '__sfc') {
                        proxyWithRefUnwrap(proxy, setupResult, key);
                    }
                }
            }
        }
        else if (false) {}
    }
}
function createSetupContext(vm) {
    var exposeCalled = false;
    return {
        get attrs() {
            if (!vm._attrsProxy) {
                var proxy = (vm._attrsProxy = {});
                def(proxy, '_v_attr_proxy', true);
                syncSetupProxy(proxy, vm.$attrs, emptyObject, vm, '$attrs');
            }
            return vm._attrsProxy;
        },
        get listeners() {
            if (!vm._listenersProxy) {
                var proxy = (vm._listenersProxy = {});
                syncSetupProxy(proxy, vm.$listeners, emptyObject, vm, '$listeners');
            }
            return vm._listenersProxy;
        },
        get slots() {
            return initSlotsProxy(vm);
        },
        emit: bind(vm.$emit, vm),
        expose: function (exposed) {
            if (false) {}
            if (exposed) {
                Object.keys(exposed).forEach(function (key) {
                    return proxyWithRefUnwrap(vm, exposed, key);
                });
            }
        }
    };
}
function syncSetupProxy(to, from, prev, instance, type) {
    var changed = false;
    for (var key in from) {
        if (!(key in to)) {
            changed = true;
            defineProxyAttr(to, key, instance, type);
        }
        else if (from[key] !== prev[key]) {
            changed = true;
        }
    }
    for (var key in to) {
        if (!(key in from)) {
            changed = true;
            delete to[key];
        }
    }
    return changed;
}
function defineProxyAttr(proxy, key, instance, type) {
    Object.defineProperty(proxy, key, {
        enumerable: true,
        configurable: true,
        get: function () {
            return instance[type][key];
        }
    });
}
function initSlotsProxy(vm) {
    if (!vm._slotsProxy) {
        syncSetupSlots((vm._slotsProxy = {}), vm.$scopedSlots);
    }
    return vm._slotsProxy;
}
function syncSetupSlots(to, from) {
    for (var key in from) {
        to[key] = from[key];
    }
    for (var key in to) {
        if (!(key in from)) {
            delete to[key];
        }
    }
}
/**
 * @internal use manual type def because public setup context type relies on
 * legacy VNode types
 */
function useSlots() {
    return getContext().slots;
}
/**
 * @internal use manual type def because public setup context type relies on
 * legacy VNode types
 */
function useAttrs() {
    return getContext().attrs;
}
/**
 * Vue 2 only
 * @internal use manual type def because public setup context type relies on
 * legacy VNode types
 */
function useListeners() {
    return getContext().listeners;
}
function getContext() {
    if (false) {}
    var vm = currentInstance;
    return vm._setupContext || (vm._setupContext = createSetupContext(vm));
}
/**
 * Runtime helper for merging default declarations. Imported by compiled code
 * only.
 * @internal
 */
function mergeDefaults(raw, defaults) {
    var props = isArray(raw)
        ? raw.reduce(function (normalized, p) { return ((normalized[p] = {}), normalized); }, {})
        : raw;
    for (var key in defaults) {
        var opt = props[key];
        if (opt) {
            if (isArray(opt) || isFunction(opt)) {
                props[key] = { type: opt, default: defaults[key] };
            }
            else {
                opt.default = defaults[key];
            }
        }
        else if (opt === null) {
            props[key] = { default: defaults[key] };
        }
        else if (false) {}
    }
    return props;
}

function initRender(vm) {
    vm._vnode = null; // the root of the child tree
    vm._staticTrees = null; // v-once cached trees
    var options = vm.$options;
    var parentVnode = (vm.$vnode = options._parentVnode); // the placeholder node in parent tree
    var renderContext = parentVnode && parentVnode.context;
    vm.$slots = resolveSlots(options._renderChildren, renderContext);
    vm.$scopedSlots = parentVnode
        ? normalizeScopedSlots(vm.$parent, parentVnode.data.scopedSlots, vm.$slots)
        : emptyObject;
    // bind the createElement fn to this instance
    // so that we get proper render context inside it.
    // args order: tag, data, children, normalizationType, alwaysNormalize
    // internal version is used by render functions compiled from templates
    // @ts-expect-error
    vm._c = function (a, b, c, d) { return createElement$1(vm, a, b, c, d, false); };
    // normalization is always applied for the public version, used in
    // user-written render functions.
    // @ts-expect-error
    vm.$createElement = function (a, b, c, d) { return createElement$1(vm, a, b, c, d, true); };
    // $attrs & $listeners are exposed for easier HOC creation.
    // they need to be reactive so that HOCs using them are always updated
    var parentData = parentVnode && parentVnode.data;
    /* istanbul ignore else */
    if (false) {}
    else {
        defineReactive(vm, '$attrs', (parentData && parentData.attrs) || emptyObject, null, true);
        defineReactive(vm, '$listeners', options._parentListeners || emptyObject, null, true);
    }
}
var currentRenderingInstance = null;
function renderMixin(Vue) {
    // install runtime convenience helpers
    installRenderHelpers(Vue.prototype);
    Vue.prototype.$nextTick = function (fn) {
        return nextTick(fn, this);
    };
    Vue.prototype._render = function () {
        var vm = this;
        var _a = vm.$options, render = _a.render, _parentVnode = _a._parentVnode;
        if (_parentVnode && vm._isMounted) {
            vm.$scopedSlots = normalizeScopedSlots(vm.$parent, _parentVnode.data.scopedSlots, vm.$slots, vm.$scopedSlots);
            if (vm._slotsProxy) {
                syncSetupSlots(vm._slotsProxy, vm.$scopedSlots);
            }
        }
        // set parent vnode. this allows render functions to have access
        // to the data on the placeholder node.
        vm.$vnode = _parentVnode;
        // render self
        var vnode;
        try {
            // There's no need to maintain a stack because all render fns are called
            // separately from one another. Nested component's render fns are called
            // when parent component is patched.
            setCurrentInstance(vm);
            currentRenderingInstance = vm;
            vnode = render.call(vm._renderProxy, vm.$createElement);
        }
        catch (e) {
            handleError(e, vm, "render");
            // return error render result,
            // or previous vnode to prevent render error causing blank component
            /* istanbul ignore else */
            if (false) {}
            else {
                vnode = vm._vnode;
            }
        }
        finally {
            currentRenderingInstance = null;
            setCurrentInstance();
        }
        // if the returned array contains only a single node, allow it
        if (isArray(vnode) && vnode.length === 1) {
            vnode = vnode[0];
        }
        // return empty vnode in case the render function errored out
        if (!(vnode instanceof VNode)) {
            if (false) {}
            vnode = createEmptyVNode();
        }
        // set parent
        vnode.parent = _parentVnode;
        return vnode;
    };
}

function ensureCtor(comp, base) {
    if (comp.__esModule || (hasSymbol && comp[Symbol.toStringTag] === 'Module')) {
        comp = comp.default;
    }
    return isObject(comp) ? base.extend(comp) : comp;
}
function createAsyncPlaceholder(factory, data, context, children, tag) {
    var node = createEmptyVNode();
    node.asyncFactory = factory;
    node.asyncMeta = { data: data, context: context, children: children, tag: tag };
    return node;
}
function resolveAsyncComponent(factory, baseCtor) {
    if (isTrue(factory.error) && isDef(factory.errorComp)) {
        return factory.errorComp;
    }
    if (isDef(factory.resolved)) {
        return factory.resolved;
    }
    var owner = currentRenderingInstance;
    if (owner && isDef(factory.owners) && factory.owners.indexOf(owner) === -1) {
        // already pending
        factory.owners.push(owner);
    }
    if (isTrue(factory.loading) && isDef(factory.loadingComp)) {
        return factory.loadingComp;
    }
    if (owner && !isDef(factory.owners)) {
        var owners_1 = (factory.owners = [owner]);
        var sync_1 = true;
        var timerLoading_1 = null;
        var timerTimeout_1 = null;
        owner.$on('hook:destroyed', function () { return remove$2(owners_1, owner); });
        var forceRender_1 = function (renderCompleted) {
            for (var i = 0, l = owners_1.length; i < l; i++) {
                owners_1[i].$forceUpdate();
            }
            if (renderCompleted) {
                owners_1.length = 0;
                if (timerLoading_1 !== null) {
                    clearTimeout(timerLoading_1);
                    timerLoading_1 = null;
                }
                if (timerTimeout_1 !== null) {
                    clearTimeout(timerTimeout_1);
                    timerTimeout_1 = null;
                }
            }
        };
        var resolve = once(function (res) {
            // cache resolved
            factory.resolved = ensureCtor(res, baseCtor);
            // invoke callbacks only if this is not a synchronous resolve
            // (async resolves are shimmed as synchronous during SSR)
            if (!sync_1) {
                forceRender_1(true);
            }
            else {
                owners_1.length = 0;
            }
        });
        var reject_1 = once(function (reason) {
             false &&
                0;
            if (isDef(factory.errorComp)) {
                factory.error = true;
                forceRender_1(true);
            }
        });
        var res_1 = factory(resolve, reject_1);
        if (isObject(res_1)) {
            if (isPromise(res_1)) {
                // () => Promise
                if (isUndef(factory.resolved)) {
                    res_1.then(resolve, reject_1);
                }
            }
            else if (isPromise(res_1.component)) {
                res_1.component.then(resolve, reject_1);
                if (isDef(res_1.error)) {
                    factory.errorComp = ensureCtor(res_1.error, baseCtor);
                }
                if (isDef(res_1.loading)) {
                    factory.loadingComp = ensureCtor(res_1.loading, baseCtor);
                    if (res_1.delay === 0) {
                        factory.loading = true;
                    }
                    else {
                        // @ts-expect-error NodeJS timeout type
                        timerLoading_1 = setTimeout(function () {
                            timerLoading_1 = null;
                            if (isUndef(factory.resolved) && isUndef(factory.error)) {
                                factory.loading = true;
                                forceRender_1(false);
                            }
                        }, res_1.delay || 200);
                    }
                }
                if (isDef(res_1.timeout)) {
                    // @ts-expect-error NodeJS timeout type
                    timerTimeout_1 = setTimeout(function () {
                        timerTimeout_1 = null;
                        if (isUndef(factory.resolved)) {
                            reject_1( false ? 0 : null);
                        }
                    }, res_1.timeout);
                }
            }
        }
        sync_1 = false;
        // return in case resolved synchronously
        return factory.loading ? factory.loadingComp : factory.resolved;
    }
}

function getFirstComponentChild(children) {
    if (isArray(children)) {
        for (var i = 0; i < children.length; i++) {
            var c = children[i];
            if (isDef(c) && (isDef(c.componentOptions) || isAsyncPlaceholder(c))) {
                return c;
            }
        }
    }
}

var SIMPLE_NORMALIZE = 1;
var ALWAYS_NORMALIZE = 2;
// wrapper function for providing a more flexible interface
// without getting yelled at by flow
function createElement$1(context, tag, data, children, normalizationType, alwaysNormalize) {
    if (isArray(data) || isPrimitive(data)) {
        normalizationType = children;
        children = data;
        data = undefined;
    }
    if (isTrue(alwaysNormalize)) {
        normalizationType = ALWAYS_NORMALIZE;
    }
    return _createElement(context, tag, data, children, normalizationType);
}
function _createElement(context, tag, data, children, normalizationType) {
    if (isDef(data) && isDef(data.__ob__)) {
         false &&
            0;
        return createEmptyVNode();
    }
    // object syntax in v-bind
    if (isDef(data) && isDef(data.is)) {
        tag = data.is;
    }
    if (!tag) {
        // in case of component :is set to falsy value
        return createEmptyVNode();
    }
    // warn against non-primitive key
    if (false) {}
    // support single function children as default scoped slot
    if (isArray(children) && isFunction(children[0])) {
        data = data || {};
        data.scopedSlots = { default: children[0] };
        children.length = 0;
    }
    if (normalizationType === ALWAYS_NORMALIZE) {
        children = normalizeChildren(children);
    }
    else if (normalizationType === SIMPLE_NORMALIZE) {
        children = simpleNormalizeChildren(children);
    }
    var vnode, ns;
    if (typeof tag === 'string') {
        var Ctor = void 0;
        ns = (context.$vnode && context.$vnode.ns) || config.getTagNamespace(tag);
        if (config.isReservedTag(tag)) {
            // platform built-in elements
            if (false) {}
            vnode = new VNode(config.parsePlatformTagName(tag), data, children, undefined, undefined, context);
        }
        else if ((!data || !data.pre) &&
            isDef((Ctor = resolveAsset(context.$options, 'components', tag)))) {
            // component
            vnode = createComponent(Ctor, data, context, children, tag);
        }
        else {
            // unknown or unlisted namespaced elements
            // check at runtime because it may get assigned a namespace when its
            // parent normalizes children
            vnode = new VNode(tag, data, children, undefined, undefined, context);
        }
    }
    else {
        // direct component options / constructor
        vnode = createComponent(tag, data, context, children);
    }
    if (isArray(vnode)) {
        return vnode;
    }
    else if (isDef(vnode)) {
        if (isDef(ns))
            applyNS(vnode, ns);
        if (isDef(data))
            registerDeepBindings(data);
        return vnode;
    }
    else {
        return createEmptyVNode();
    }
}
function applyNS(vnode, ns, force) {
    vnode.ns = ns;
    if (vnode.tag === 'foreignObject') {
        // use default namespace inside foreignObject
        ns = undefined;
        force = true;
    }
    if (isDef(vnode.children)) {
        for (var i = 0, l = vnode.children.length; i < l; i++) {
            var child = vnode.children[i];
            if (isDef(child.tag) &&
                (isUndef(child.ns) || (isTrue(force) && child.tag !== 'svg'))) {
                applyNS(child, ns, force);
            }
        }
    }
}
// ref #5318
// necessary to ensure parent re-render when deep bindings like :style and
// :class are used on slot nodes
function registerDeepBindings(data) {
    if (isObject(data.style)) {
        traverse(data.style);
    }
    if (isObject(data.class)) {
        traverse(data.class);
    }
}

/**
 * @internal this function needs manual public type declaration because it relies
 * on previously manually authored types from Vue 2
 */
function h(type, props, children) {
    if (!currentInstance) {
         false &&
            0;
    }
    return createElement$1(currentInstance, type, props, children, 2, true);
}

function handleError(err, vm, info) {
    // Deactivate deps tracking while processing error handler to avoid possible infinite rendering.
    // See: https://github.com/vuejs/vuex/issues/1505
    pushTarget();
    try {
        if (vm) {
            var cur = vm;
            while ((cur = cur.$parent)) {
                var hooks = cur.$options.errorCaptured;
                if (hooks) {
                    for (var i = 0; i < hooks.length; i++) {
                        try {
                            var capture = hooks[i].call(cur, err, vm, info) === false;
                            if (capture)
                                return;
                        }
                        catch (e) {
                            globalHandleError(e, cur, 'errorCaptured hook');
                        }
                    }
                }
            }
        }
        globalHandleError(err, vm, info);
    }
    finally {
        popTarget();
    }
}
function invokeWithErrorHandling(handler, context, args, vm, info) {
    var res;
    try {
        res = args ? handler.apply(context, args) : handler.call(context);
        if (res && !res._isVue && isPromise(res) && !res._handled) {
            res.catch(function (e) { return handleError(e, vm, info + " (Promise/async)"); });
            res._handled = true;
        }
    }
    catch (e) {
        handleError(e, vm, info);
    }
    return res;
}
function globalHandleError(err, vm, info) {
    if (config.errorHandler) {
        try {
            return config.errorHandler.call(null, err, vm, info);
        }
        catch (e) {
            // if the user intentionally throws the original error in the handler,
            // do not log it twice
            if (e !== err) {
                logError(e, null, 'config.errorHandler');
            }
        }
    }
    logError(err, vm, info);
}
function logError(err, vm, info) {
    if (false) {}
    /* istanbul ignore else */
    if (inBrowser && typeof console !== 'undefined') {
        console.error(err);
    }
    else {
        throw err;
    }
}

/* globals MutationObserver */
var isUsingMicroTask = false;
var callbacks = [];
var pending = false;
function flushCallbacks() {
    pending = false;
    var copies = callbacks.slice(0);
    callbacks.length = 0;
    for (var i = 0; i < copies.length; i++) {
        copies[i]();
    }
}
// Here we have async deferring wrappers using microtasks.
// In 2.5 we used (macro) tasks (in combination with microtasks).
// However, it has subtle problems when state is changed right before repaint
// (e.g. #6813, out-in transitions).
// Also, using (macro) tasks in event handler would cause some weird behaviors
// that cannot be circumvented (e.g. #7109, #7153, #7546, #7834, #8109).
// So we now use microtasks everywhere, again.
// A major drawback of this tradeoff is that there are some scenarios
// where microtasks have too high a priority and fire in between supposedly
// sequential events (e.g. #4521, #6690, which have workarounds)
// or even between bubbling of the same event (#6566).
var timerFunc;
// The nextTick behavior leverages the microtask queue, which can be accessed
// via either native Promise.then or MutationObserver.
// MutationObserver has wider support, however it is seriously bugged in
// UIWebView in iOS >= 9.3.3 when triggered in touch event handlers. It
// completely stops working after triggering a few times... so, if native
// Promise is available, we will use it:
/* istanbul ignore next, $flow-disable-line */
if (typeof Promise !== 'undefined' && isNative(Promise)) {
    var p_1 = Promise.resolve();
    timerFunc = function () {
        p_1.then(flushCallbacks);
        // In problematic UIWebViews, Promise.then doesn't completely break, but
        // it can get stuck in a weird state where callbacks are pushed into the
        // microtask queue but the queue isn't being flushed, until the browser
        // needs to do some other work, e.g. handle a timer. Therefore we can
        // "force" the microtask queue to be flushed by adding an empty timer.
        if (isIOS)
            setTimeout(noop);
    };
    isUsingMicroTask = true;
}
else if (!isIE &&
    typeof MutationObserver !== 'undefined' &&
    (isNative(MutationObserver) ||
        // PhantomJS and iOS 7.x
        MutationObserver.toString() === '[object MutationObserverConstructor]')) {
    // Use MutationObserver where native Promise is not available,
    // e.g. PhantomJS, iOS7, Android 4.4
    // (#6466 MutationObserver is unreliable in IE11)
    var counter_1 = 1;
    var observer = new MutationObserver(flushCallbacks);
    var textNode_1 = document.createTextNode(String(counter_1));
    observer.observe(textNode_1, {
        characterData: true
    });
    timerFunc = function () {
        counter_1 = (counter_1 + 1) % 2;
        textNode_1.data = String(counter_1);
    };
    isUsingMicroTask = true;
}
else if (typeof setImmediate !== 'undefined' && isNative(setImmediate)) {
    // Fallback to setImmediate.
    // Technically it leverages the (macro) task queue,
    // but it is still a better choice than setTimeout.
    timerFunc = function () {
        setImmediate(flushCallbacks);
    };
}
else {
    // Fallback to setTimeout.
    timerFunc = function () {
        setTimeout(flushCallbacks, 0);
    };
}
/**
 * @internal
 */
function nextTick(cb, ctx) {
    var _resolve;
    callbacks.push(function () {
        if (cb) {
            try {
                cb.call(ctx);
            }
            catch (e) {
                handleError(e, ctx, 'nextTick');
            }
        }
        else if (_resolve) {
            _resolve(ctx);
        }
    });
    if (!pending) {
        pending = true;
        timerFunc();
    }
    // $flow-disable-line
    if (!cb && typeof Promise !== 'undefined') {
        return new Promise(function (resolve) {
            _resolve = resolve;
        });
    }
}

function useCssModule(name) {
    if (name === void 0) { name = '$style'; }
    /* istanbul ignore else */
    {
        if (!currentInstance) {
             false && 0;
            return emptyObject;
        }
        var mod = currentInstance[name];
        if (!mod) {
             false &&
                0;
            return emptyObject;
        }
        return mod;
    }
}

/**
 * Runtime helper for SFC's CSS variable injection feature.
 * @private
 */
function useCssVars(getter) {
    if (!inBrowser && !false)
        return;
    var instance = currentInstance;
    if (!instance) {
         false &&
            0;
        return;
    }
    watchPostEffect(function () {
        var el = instance.$el;
        var vars = getter(instance, instance._setupProxy);
        if (el && el.nodeType === 1) {
            var style = el.style;
            for (var key in vars) {
                style.setProperty("--".concat(key), vars[key]);
            }
        }
    });
}

/**
 * v3-compatible async component API.
 * @internal the type is manually declared in <root>/types/v3-define-async-component.d.ts
 * because it relies on existing manual types
 */
function defineAsyncComponent(source) {
    if (isFunction(source)) {
        source = { loader: source };
    }
    var loader = source.loader, loadingComponent = source.loadingComponent, errorComponent = source.errorComponent, _a = source.delay, delay = _a === void 0 ? 200 : _a, timeout = source.timeout, // undefined = never times out
    _b = source.suspensible, // undefined = never times out
    suspensible = _b === void 0 ? false : _b, // in Vue 3 default is true
    userOnError = source.onError;
    if (false) {}
    var pendingRequest = null;
    var retries = 0;
    var retry = function () {
        retries++;
        pendingRequest = null;
        return load();
    };
    var load = function () {
        var thisRequest;
        return (pendingRequest ||
            (thisRequest = pendingRequest =
                loader()
                    .catch(function (err) {
                    err = err instanceof Error ? err : new Error(String(err));
                    if (userOnError) {
                        return new Promise(function (resolve, reject) {
                            var userRetry = function () { return resolve(retry()); };
                            var userFail = function () { return reject(err); };
                            userOnError(err, userRetry, userFail, retries + 1);
                        });
                    }
                    else {
                        throw err;
                    }
                })
                    .then(function (comp) {
                    if (thisRequest !== pendingRequest && pendingRequest) {
                        return pendingRequest;
                    }
                    if (false) {}
                    // interop module default
                    if (comp &&
                        (comp.__esModule || comp[Symbol.toStringTag] === 'Module')) {
                        comp = comp.default;
                    }
                    if (false) {}
                    return comp;
                })));
    };
    return function () {
        var component = load();
        return {
            component: component,
            delay: delay,
            timeout: timeout,
            error: errorComponent,
            loading: loadingComponent
        };
    };
}

function createLifeCycle(hookName) {
    return function (fn, target) {
        if (target === void 0) { target = currentInstance; }
        if (!target) {
             false &&
                0;
            return;
        }
        return injectHook(target, hookName, fn);
    };
}
function formatName(name) {
    if (name === 'beforeDestroy') {
        name = 'beforeUnmount';
    }
    else if (name === 'destroyed') {
        name = 'unmounted';
    }
    return "on".concat(name[0].toUpperCase() + name.slice(1));
}
function injectHook(instance, hookName, fn) {
    var options = instance.$options;
    options[hookName] = mergeLifecycleHook(options[hookName], fn);
}
var onBeforeMount = createLifeCycle('beforeMount');
var onMounted = createLifeCycle('mounted');
var onBeforeUpdate = createLifeCycle('beforeUpdate');
var onUpdated = createLifeCycle('updated');
var onBeforeUnmount = createLifeCycle('beforeDestroy');
var onUnmounted = createLifeCycle('destroyed');
var onActivated = createLifeCycle('activated');
var onDeactivated = createLifeCycle('deactivated');
var onServerPrefetch = createLifeCycle('serverPrefetch');
var onRenderTracked = createLifeCycle('renderTracked');
var onRenderTriggered = createLifeCycle('renderTriggered');
var injectErrorCapturedHook = createLifeCycle('errorCaptured');
function onErrorCaptured(hook, target) {
    if (target === void 0) { target = currentInstance; }
    injectErrorCapturedHook(hook, target);
}

/**
 * Note: also update dist/vue.runtime.mjs when adding new exports to this file.
 */
var version = '2.7.15';
/**
 * @internal type is manually declared in <root>/types/v3-define-component.d.ts
 */
function defineComponent(options) {
    return options;
}

var seenObjects = new _Set();
/**
 * Recursively traverse an object to evoke all converted
 * getters, so that every nested property inside the object
 * is collected as a "deep" dependency.
 */
function traverse(val) {
    _traverse(val, seenObjects);
    seenObjects.clear();
    return val;
}
function _traverse(val, seen) {
    var i, keys;
    var isA = isArray(val);
    if ((!isA && !isObject(val)) ||
        val.__v_skip /* ReactiveFlags.SKIP */ ||
        Object.isFrozen(val) ||
        val instanceof VNode) {
        return;
    }
    if (val.__ob__) {
        var depId = val.__ob__.dep.id;
        if (seen.has(depId)) {
            return;
        }
        seen.add(depId);
    }
    if (isA) {
        i = val.length;
        while (i--)
            _traverse(val[i], seen);
    }
    else if (isRef(val)) {
        _traverse(val.value, seen);
    }
    else {
        keys = Object.keys(val);
        i = keys.length;
        while (i--)
            _traverse(val[keys[i]], seen);
    }
}

var uid$1 = 0;
/**
 * A watcher parses an expression, collects dependencies,
 * and fires callback when the expression value changes.
 * This is used for both the $watch() api and directives.
 * @internal
 */
var Watcher = /** @class */ (function () {
    function Watcher(vm, expOrFn, cb, options, isRenderWatcher) {
        recordEffectScope(this, 
        // if the active effect scope is manually created (not a component scope),
        // prioritize it
        activeEffectScope && !activeEffectScope._vm
            ? activeEffectScope
            : vm
                ? vm._scope
                : undefined);
        if ((this.vm = vm) && isRenderWatcher) {
            vm._watcher = this;
        }
        // options
        if (options) {
            this.deep = !!options.deep;
            this.user = !!options.user;
            this.lazy = !!options.lazy;
            this.sync = !!options.sync;
            this.before = options.before;
            if (false) {}
        }
        else {
            this.deep = this.user = this.lazy = this.sync = false;
        }
        this.cb = cb;
        this.id = ++uid$1; // uid for batching
        this.active = true;
        this.post = false;
        this.dirty = this.lazy; // for lazy watchers
        this.deps = [];
        this.newDeps = [];
        this.depIds = new _Set();
        this.newDepIds = new _Set();
        this.expression =  false ? 0 : '';
        // parse expression for getter
        if (isFunction(expOrFn)) {
            this.getter = expOrFn;
        }
        else {
            this.getter = parsePath(expOrFn);
            if (!this.getter) {
                this.getter = noop;
                 false &&
                    0;
            }
        }
        this.value = this.lazy ? undefined : this.get();
    }
    /**
     * Evaluate the getter, and re-collect dependencies.
     */
    Watcher.prototype.get = function () {
        pushTarget(this);
        var value;
        var vm = this.vm;
        try {
            value = this.getter.call(vm, vm);
        }
        catch (e) {
            if (this.user) {
                handleError(e, vm, "getter for watcher \"".concat(this.expression, "\""));
            }
            else {
                throw e;
            }
        }
        finally {
            // "touch" every property so they are all tracked as
            // dependencies for deep watching
            if (this.deep) {
                traverse(value);
            }
            popTarget();
            this.cleanupDeps();
        }
        return value;
    };
    /**
     * Add a dependency to this directive.
     */
    Watcher.prototype.addDep = function (dep) {
        var id = dep.id;
        if (!this.newDepIds.has(id)) {
            this.newDepIds.add(id);
            this.newDeps.push(dep);
            if (!this.depIds.has(id)) {
                dep.addSub(this);
            }
        }
    };
    /**
     * Clean up for dependency collection.
     */
    Watcher.prototype.cleanupDeps = function () {
        var i = this.deps.length;
        while (i--) {
            var dep = this.deps[i];
            if (!this.newDepIds.has(dep.id)) {
                dep.removeSub(this);
            }
        }
        var tmp = this.depIds;
        this.depIds = this.newDepIds;
        this.newDepIds = tmp;
        this.newDepIds.clear();
        tmp = this.deps;
        this.deps = this.newDeps;
        this.newDeps = tmp;
        this.newDeps.length = 0;
    };
    /**
     * Subscriber interface.
     * Will be called when a dependency changes.
     */
    Watcher.prototype.update = function () {
        /* istanbul ignore else */
        if (this.lazy) {
            this.dirty = true;
        }
        else if (this.sync) {
            this.run();
        }
        else {
            queueWatcher(this);
        }
    };
    /**
     * Scheduler job interface.
     * Will be called by the scheduler.
     */
    Watcher.prototype.run = function () {
        if (this.active) {
            var value = this.get();
            if (value !== this.value ||
                // Deep watchers and watchers on Object/Arrays should fire even
                // when the value is the same, because the value may
                // have mutated.
                isObject(value) ||
                this.deep) {
                // set new value
                var oldValue = this.value;
                this.value = value;
                if (this.user) {
                    var info = "callback for watcher \"".concat(this.expression, "\"");
                    invokeWithErrorHandling(this.cb, this.vm, [value, oldValue], this.vm, info);
                }
                else {
                    this.cb.call(this.vm, value, oldValue);
                }
            }
        }
    };
    /**
     * Evaluate the value of the watcher.
     * This only gets called for lazy watchers.
     */
    Watcher.prototype.evaluate = function () {
        this.value = this.get();
        this.dirty = false;
    };
    /**
     * Depend on all deps collected by this watcher.
     */
    Watcher.prototype.depend = function () {
        var i = this.deps.length;
        while (i--) {
            this.deps[i].depend();
        }
    };
    /**
     * Remove self from all dependencies' subscriber list.
     */
    Watcher.prototype.teardown = function () {
        if (this.vm && !this.vm._isBeingDestroyed) {
            remove$2(this.vm._scope.effects, this);
        }
        if (this.active) {
            var i = this.deps.length;
            while (i--) {
                this.deps[i].removeSub(this);
            }
            this.active = false;
            if (this.onStop) {
                this.onStop();
            }
        }
    };
    return Watcher;
}());

var mark;
var measure;
if (false) { var perf_1; }

function initEvents(vm) {
    vm._events = Object.create(null);
    vm._hasHookEvent = false;
    // init parent attached events
    var listeners = vm.$options._parentListeners;
    if (listeners) {
        updateComponentListeners(vm, listeners);
    }
}
var target$1;
function add$1(event, fn) {
    target$1.$on(event, fn);
}
function remove$1(event, fn) {
    target$1.$off(event, fn);
}
function createOnceHandler$1(event, fn) {
    var _target = target$1;
    return function onceHandler() {
        var res = fn.apply(null, arguments);
        if (res !== null) {
            _target.$off(event, onceHandler);
        }
    };
}
function updateComponentListeners(vm, listeners, oldListeners) {
    target$1 = vm;
    updateListeners(listeners, oldListeners || {}, add$1, remove$1, createOnceHandler$1, vm);
    target$1 = undefined;
}
function eventsMixin(Vue) {
    var hookRE = /^hook:/;
    Vue.prototype.$on = function (event, fn) {
        var vm = this;
        if (isArray(event)) {
            for (var i = 0, l = event.length; i < l; i++) {
                vm.$on(event[i], fn);
            }
        }
        else {
            (vm._events[event] || (vm._events[event] = [])).push(fn);
            // optimize hook:event cost by using a boolean flag marked at registration
            // instead of a hash lookup
            if (hookRE.test(event)) {
                vm._hasHookEvent = true;
            }
        }
        return vm;
    };
    Vue.prototype.$once = function (event, fn) {
        var vm = this;
        function on() {
            vm.$off(event, on);
            fn.apply(vm, arguments);
        }
        on.fn = fn;
        vm.$on(event, on);
        return vm;
    };
    Vue.prototype.$off = function (event, fn) {
        var vm = this;
        // all
        if (!arguments.length) {
            vm._events = Object.create(null);
            return vm;
        }
        // array of events
        if (isArray(event)) {
            for (var i_1 = 0, l = event.length; i_1 < l; i_1++) {
                vm.$off(event[i_1], fn);
            }
            return vm;
        }
        // specific event
        var cbs = vm._events[event];
        if (!cbs) {
            return vm;
        }
        if (!fn) {
            vm._events[event] = null;
            return vm;
        }
        // specific handler
        var cb;
        var i = cbs.length;
        while (i--) {
            cb = cbs[i];
            if (cb === fn || cb.fn === fn) {
                cbs.splice(i, 1);
                break;
            }
        }
        return vm;
    };
    Vue.prototype.$emit = function (event) {
        var vm = this;
        if (false) { var lowerCaseEvent; }
        var cbs = vm._events[event];
        if (cbs) {
            cbs = cbs.length > 1 ? toArray(cbs) : cbs;
            var args = toArray(arguments, 1);
            var info = "event handler for \"".concat(event, "\"");
            for (var i = 0, l = cbs.length; i < l; i++) {
                invokeWithErrorHandling(cbs[i], vm, args, vm, info);
            }
        }
        return vm;
    };
}

var activeInstance = null;
var isUpdatingChildComponent = false;
function setActiveInstance(vm) {
    var prevActiveInstance = activeInstance;
    activeInstance = vm;
    return function () {
        activeInstance = prevActiveInstance;
    };
}
function initLifecycle(vm) {
    var options = vm.$options;
    // locate first non-abstract parent
    var parent = options.parent;
    if (parent && !options.abstract) {
        while (parent.$options.abstract && parent.$parent) {
            parent = parent.$parent;
        }
        parent.$children.push(vm);
    }
    vm.$parent = parent;
    vm.$root = parent ? parent.$root : vm;
    vm.$children = [];
    vm.$refs = {};
    vm._provided = parent ? parent._provided : Object.create(null);
    vm._watcher = null;
    vm._inactive = null;
    vm._directInactive = false;
    vm._isMounted = false;
    vm._isDestroyed = false;
    vm._isBeingDestroyed = false;
}
function lifecycleMixin(Vue) {
    Vue.prototype._update = function (vnode, hydrating) {
        var vm = this;
        var prevEl = vm.$el;
        var prevVnode = vm._vnode;
        var restoreActiveInstance = setActiveInstance(vm);
        vm._vnode = vnode;
        // Vue.prototype.__patch__ is injected in entry points
        // based on the rendering backend used.
        if (!prevVnode) {
            // initial render
            vm.$el = vm.__patch__(vm.$el, vnode, hydrating, false /* removeOnly */);
        }
        else {
            // updates
            vm.$el = vm.__patch__(prevVnode, vnode);
        }
        restoreActiveInstance();
        // update __vue__ reference
        if (prevEl) {
            prevEl.__vue__ = null;
        }
        if (vm.$el) {
            vm.$el.__vue__ = vm;
        }
        // if parent is an HOC, update its $el as well
        var wrapper = vm;
        while (wrapper &&
            wrapper.$vnode &&
            wrapper.$parent &&
            wrapper.$vnode === wrapper.$parent._vnode) {
            wrapper.$parent.$el = wrapper.$el;
            wrapper = wrapper.$parent;
        }
        // updated hook is called by the scheduler to ensure that children are
        // updated in a parent's updated hook.
    };
    Vue.prototype.$forceUpdate = function () {
        var vm = this;
        if (vm._watcher) {
            vm._watcher.update();
        }
    };
    Vue.prototype.$destroy = function () {
        var vm = this;
        if (vm._isBeingDestroyed) {
            return;
        }
        callHook$1(vm, 'beforeDestroy');
        vm._isBeingDestroyed = true;
        // remove self from parent
        var parent = vm.$parent;
        if (parent && !parent._isBeingDestroyed && !vm.$options.abstract) {
            remove$2(parent.$children, vm);
        }
        // teardown scope. this includes both the render watcher and other
        // watchers created
        vm._scope.stop();
        // remove reference from data ob
        // frozen object may not have observer.
        if (vm._data.__ob__) {
            vm._data.__ob__.vmCount--;
        }
        // call the last hook...
        vm._isDestroyed = true;
        // invoke destroy hooks on current rendered tree
        vm.__patch__(vm._vnode, null);
        // fire destroyed hook
        callHook$1(vm, 'destroyed');
        // turn off all instance listeners.
        vm.$off();
        // remove __vue__ reference
        if (vm.$el) {
            vm.$el.__vue__ = null;
        }
        // release circular reference (#6759)
        if (vm.$vnode) {
            vm.$vnode.parent = null;
        }
    };
}
function mountComponent(vm, el, hydrating) {
    vm.$el = el;
    if (!vm.$options.render) {
        // @ts-expect-error invalid type
        vm.$options.render = createEmptyVNode;
        if (false) {}
    }
    callHook$1(vm, 'beforeMount');
    var updateComponent;
    /* istanbul ignore if */
    if (false) {}
    else {
        updateComponent = function () {
            vm._update(vm._render(), hydrating);
        };
    }
    var watcherOptions = {
        before: function () {
            if (vm._isMounted && !vm._isDestroyed) {
                callHook$1(vm, 'beforeUpdate');
            }
        }
    };
    if (false) {}
    // we set this to vm._watcher inside the watcher's constructor
    // since the watcher's initial patch may call $forceUpdate (e.g. inside child
    // component's mounted hook), which relies on vm._watcher being already defined
    new Watcher(vm, updateComponent, noop, watcherOptions, true /* isRenderWatcher */);
    hydrating = false;
    // flush buffer for flush: "pre" watchers queued in setup()
    var preWatchers = vm._preWatchers;
    if (preWatchers) {
        for (var i = 0; i < preWatchers.length; i++) {
            preWatchers[i].run();
        }
    }
    // manually mounted instance, call mounted on self
    // mounted is called for render-created child components in its inserted hook
    if (vm.$vnode == null) {
        vm._isMounted = true;
        callHook$1(vm, 'mounted');
    }
    return vm;
}
function updateChildComponent(vm, propsData, listeners, parentVnode, renderChildren) {
    if (false) {}
    // determine whether component has slot children
    // we need to do this before overwriting $options._renderChildren.
    // check if there are dynamic scopedSlots (hand-written or compiled but with
    // dynamic slot names). Static scoped slots compiled from template has the
    // "$stable" marker.
    var newScopedSlots = parentVnode.data.scopedSlots;
    var oldScopedSlots = vm.$scopedSlots;
    var hasDynamicScopedSlot = !!((newScopedSlots && !newScopedSlots.$stable) ||
        (oldScopedSlots !== emptyObject && !oldScopedSlots.$stable) ||
        (newScopedSlots && vm.$scopedSlots.$key !== newScopedSlots.$key) ||
        (!newScopedSlots && vm.$scopedSlots.$key));
    // Any static slot children from the parent may have changed during parent's
    // update. Dynamic scoped slots may also have changed. In such cases, a forced
    // update is necessary to ensure correctness.
    var needsForceUpdate = !!(renderChildren || // has new static slots
        vm.$options._renderChildren || // has old static slots
        hasDynamicScopedSlot);
    var prevVNode = vm.$vnode;
    vm.$options._parentVnode = parentVnode;
    vm.$vnode = parentVnode; // update vm's placeholder node without re-render
    if (vm._vnode) {
        // update child tree's parent
        vm._vnode.parent = parentVnode;
    }
    vm.$options._renderChildren = renderChildren;
    // update $attrs and $listeners hash
    // these are also reactive so they may trigger child update if the child
    // used them during render
    var attrs = parentVnode.data.attrs || emptyObject;
    if (vm._attrsProxy) {
        // force update if attrs are accessed and has changed since it may be
        // passed to a child component.
        if (syncSetupProxy(vm._attrsProxy, attrs, (prevVNode.data && prevVNode.data.attrs) || emptyObject, vm, '$attrs')) {
            needsForceUpdate = true;
        }
    }
    vm.$attrs = attrs;
    // update listeners
    listeners = listeners || emptyObject;
    var prevListeners = vm.$options._parentListeners;
    if (vm._listenersProxy) {
        syncSetupProxy(vm._listenersProxy, listeners, prevListeners || emptyObject, vm, '$listeners');
    }
    vm.$listeners = vm.$options._parentListeners = listeners;
    updateComponentListeners(vm, listeners, prevListeners);
    // update props
    if (propsData && vm.$options.props) {
        toggleObserving(false);
        var props = vm._props;
        var propKeys = vm.$options._propKeys || [];
        for (var i = 0; i < propKeys.length; i++) {
            var key = propKeys[i];
            var propOptions = vm.$options.props; // wtf flow?
            props[key] = validateProp(key, propOptions, propsData, vm);
        }
        toggleObserving(true);
        // keep a copy of raw propsData
        vm.$options.propsData = propsData;
    }
    // resolve slots + force update if has children
    if (needsForceUpdate) {
        vm.$slots = resolveSlots(renderChildren, parentVnode.context);
        vm.$forceUpdate();
    }
    if (false) {}
}
function isInInactiveTree(vm) {
    while (vm && (vm = vm.$parent)) {
        if (vm._inactive)
            return true;
    }
    return false;
}
function activateChildComponent(vm, direct) {
    if (direct) {
        vm._directInactive = false;
        if (isInInactiveTree(vm)) {
            return;
        }
    }
    else if (vm._directInactive) {
        return;
    }
    if (vm._inactive || vm._inactive === null) {
        vm._inactive = false;
        for (var i = 0; i < vm.$children.length; i++) {
            activateChildComponent(vm.$children[i]);
        }
        callHook$1(vm, 'activated');
    }
}
function deactivateChildComponent(vm, direct) {
    if (direct) {
        vm._directInactive = true;
        if (isInInactiveTree(vm)) {
            return;
        }
    }
    if (!vm._inactive) {
        vm._inactive = true;
        for (var i = 0; i < vm.$children.length; i++) {
            deactivateChildComponent(vm.$children[i]);
        }
        callHook$1(vm, 'deactivated');
    }
}
function callHook$1(vm, hook, args, setContext) {
    if (setContext === void 0) { setContext = true; }
    // #7573 disable dep collection when invoking lifecycle hooks
    pushTarget();
    var prevInst = currentInstance;
    var prevScope = getCurrentScope();
    setContext && setCurrentInstance(vm);
    var handlers = vm.$options[hook];
    var info = "".concat(hook, " hook");
    if (handlers) {
        for (var i = 0, j = handlers.length; i < j; i++) {
            invokeWithErrorHandling(handlers[i], vm, args || null, vm, info);
        }
    }
    if (vm._hasHookEvent) {
        vm.$emit('hook:' + hook);
    }
    if (setContext) {
        setCurrentInstance(prevInst);
        prevScope && prevScope.on();
    }
    popTarget();
}

var MAX_UPDATE_COUNT = 100;
var queue = [];
var activatedChildren = [];
var has = {};
var circular = {};
var waiting = false;
var flushing = false;
var index = 0;
/**
 * Reset the scheduler's state.
 */
function resetSchedulerState() {
    index = queue.length = activatedChildren.length = 0;
    has = {};
    if (false) {}
    waiting = flushing = false;
}
// Async edge case #6566 requires saving the timestamp when event listeners are
// attached. However, calling performance.now() has a perf overhead especially
// if the page has thousands of event listeners. Instead, we take a timestamp
// every time the scheduler flushes and use that for all event listeners
// attached during that flush.
var currentFlushTimestamp = 0;
// Async edge case fix requires storing an event listener's attach timestamp.
var getNow = Date.now;
// Determine what event timestamp the browser is using. Annoyingly, the
// timestamp can either be hi-res (relative to page load) or low-res
// (relative to UNIX epoch), so in order to compare time we have to use the
// same timestamp type when saving the flush timestamp.
// All IE versions use low-res event timestamps, and have problematic clock
// implementations (#9632)
if (inBrowser && !isIE) {
    var performance_1 = window.performance;
    if (performance_1 &&
        typeof performance_1.now === 'function' &&
        getNow() > document.createEvent('Event').timeStamp) {
        // if the event timestamp, although evaluated AFTER the Date.now(), is
        // smaller than it, it means the event is using a hi-res timestamp,
        // and we need to use the hi-res version for event listener timestamps as
        // well.
        getNow = function () { return performance_1.now(); };
    }
}
var sortCompareFn = function (a, b) {
    if (a.post) {
        if (!b.post)
            return 1;
    }
    else if (b.post) {
        return -1;
    }
    return a.id - b.id;
};
/**
 * Flush both queues and run the watchers.
 */
function flushSchedulerQueue() {
    currentFlushTimestamp = getNow();
    flushing = true;
    var watcher, id;
    // Sort queue before flush.
    // This ensures that:
    // 1. Components are updated from parent to child. (because parent is always
    //    created before the child)
    // 2. A component's user watchers are run before its render watcher (because
    //    user watchers are created before the render watcher)
    // 3. If a component is destroyed during a parent component's watcher run,
    //    its watchers can be skipped.
    queue.sort(sortCompareFn);
    // do not cache length because more watchers might be pushed
    // as we run existing watchers
    for (index = 0; index < queue.length; index++) {
        watcher = queue[index];
        if (watcher.before) {
            watcher.before();
        }
        id = watcher.id;
        has[id] = null;
        watcher.run();
        // in dev build, check and stop circular updates.
        if (false) {}
    }
    // keep copies of post queues before resetting state
    var activatedQueue = activatedChildren.slice();
    var updatedQueue = queue.slice();
    resetSchedulerState();
    // call component updated and activated hooks
    callActivatedHooks(activatedQueue);
    callUpdatedHooks(updatedQueue);
    cleanupDeps();
    // devtool hook
    /* istanbul ignore if */
    if (devtools && config.devtools) {
        devtools.emit('flush');
    }
}
function callUpdatedHooks(queue) {
    var i = queue.length;
    while (i--) {
        var watcher = queue[i];
        var vm = watcher.vm;
        if (vm && vm._watcher === watcher && vm._isMounted && !vm._isDestroyed) {
            callHook$1(vm, 'updated');
        }
    }
}
/**
 * Queue a kept-alive component that was activated during patch.
 * The queue will be processed after the entire tree has been patched.
 */
function queueActivatedComponent(vm) {
    // setting _inactive to false here so that a render function can
    // rely on checking whether it's in an inactive tree (e.g. router-view)
    vm._inactive = false;
    activatedChildren.push(vm);
}
function callActivatedHooks(queue) {
    for (var i = 0; i < queue.length; i++) {
        queue[i]._inactive = true;
        activateChildComponent(queue[i], true /* true */);
    }
}
/**
 * Push a watcher into the watcher queue.
 * Jobs with duplicate IDs will be skipped unless it's
 * pushed when the queue is being flushed.
 */
function queueWatcher(watcher) {
    var id = watcher.id;
    if (has[id] != null) {
        return;
    }
    if (watcher === Dep.target && watcher.noRecurse) {
        return;
    }
    has[id] = true;
    if (!flushing) {
        queue.push(watcher);
    }
    else {
        // if already flushing, splice the watcher based on its id
        // if already past its id, it will be run next immediately.
        var i = queue.length - 1;
        while (i > index && queue[i].id > watcher.id) {
            i--;
        }
        queue.splice(i + 1, 0, watcher);
    }
    // queue the flush
    if (!waiting) {
        waiting = true;
        if (false) {}
        nextTick(flushSchedulerQueue);
    }
}

function initProvide(vm) {
    var provideOption = vm.$options.provide;
    if (provideOption) {
        var provided = isFunction(provideOption)
            ? provideOption.call(vm)
            : provideOption;
        if (!isObject(provided)) {
            return;
        }
        var source = resolveProvided(vm);
        // IE9 doesn't support Object.getOwnPropertyDescriptors so we have to
        // iterate the keys ourselves.
        var keys = hasSymbol ? Reflect.ownKeys(provided) : Object.keys(provided);
        for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            Object.defineProperty(source, key, Object.getOwnPropertyDescriptor(provided, key));
        }
    }
}
function initInjections(vm) {
    var result = resolveInject(vm.$options.inject, vm);
    if (result) {
        toggleObserving(false);
        Object.keys(result).forEach(function (key) {
            /* istanbul ignore else */
            if (false) {}
            else {
                defineReactive(vm, key, result[key]);
            }
        });
        toggleObserving(true);
    }
}
function resolveInject(inject, vm) {
    if (inject) {
        // inject is :any because flow is not smart enough to figure out cached
        var result = Object.create(null);
        var keys = hasSymbol ? Reflect.ownKeys(inject) : Object.keys(inject);
        for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            // #6574 in case the inject object is observed...
            if (key === '__ob__')
                continue;
            var provideKey = inject[key].from;
            if (provideKey in vm._provided) {
                result[key] = vm._provided[provideKey];
            }
            else if ('default' in inject[key]) {
                var provideDefault = inject[key].default;
                result[key] = isFunction(provideDefault)
                    ? provideDefault.call(vm)
                    : provideDefault;
            }
            else if (false) {}
        }
        return result;
    }
}

function FunctionalRenderContext(data, props, children, parent, Ctor) {
    var _this = this;
    var options = Ctor.options;
    // ensure the createElement function in functional components
    // gets a unique context - this is necessary for correct named slot check
    var contextVm;
    if (hasOwn(parent, '_uid')) {
        contextVm = Object.create(parent);
        contextVm._original = parent;
    }
    else {
        // the context vm passed in is a functional context as well.
        // in this case we want to make sure we are able to get a hold to the
        // real context instance.
        contextVm = parent;
        // @ts-ignore
        parent = parent._original;
    }
    var isCompiled = isTrue(options._compiled);
    var needNormalization = !isCompiled;
    this.data = data;
    this.props = props;
    this.children = children;
    this.parent = parent;
    this.listeners = data.on || emptyObject;
    this.injections = resolveInject(options.inject, parent);
    this.slots = function () {
        if (!_this.$slots) {
            normalizeScopedSlots(parent, data.scopedSlots, (_this.$slots = resolveSlots(children, parent)));
        }
        return _this.$slots;
    };
    Object.defineProperty(this, 'scopedSlots', {
        enumerable: true,
        get: function () {
            return normalizeScopedSlots(parent, data.scopedSlots, this.slots());
        }
    });
    // support for compiled functional template
    if (isCompiled) {
        // exposing $options for renderStatic()
        this.$options = options;
        // pre-resolve slots for renderSlot()
        this.$slots = this.slots();
        this.$scopedSlots = normalizeScopedSlots(parent, data.scopedSlots, this.$slots);
    }
    if (options._scopeId) {
        this._c = function (a, b, c, d) {
            var vnode = createElement$1(contextVm, a, b, c, d, needNormalization);
            if (vnode && !isArray(vnode)) {
                vnode.fnScopeId = options._scopeId;
                vnode.fnContext = parent;
            }
            return vnode;
        };
    }
    else {
        this._c = function (a, b, c, d) {
            return createElement$1(contextVm, a, b, c, d, needNormalization);
        };
    }
}
installRenderHelpers(FunctionalRenderContext.prototype);
function createFunctionalComponent(Ctor, propsData, data, contextVm, children) {
    var options = Ctor.options;
    var props = {};
    var propOptions = options.props;
    if (isDef(propOptions)) {
        for (var key in propOptions) {
            props[key] = validateProp(key, propOptions, propsData || emptyObject);
        }
    }
    else {
        if (isDef(data.attrs))
            mergeProps(props, data.attrs);
        if (isDef(data.props))
            mergeProps(props, data.props);
    }
    var renderContext = new FunctionalRenderContext(data, props, children, contextVm, Ctor);
    var vnode = options.render.call(null, renderContext._c, renderContext);
    if (vnode instanceof VNode) {
        return cloneAndMarkFunctionalResult(vnode, data, renderContext.parent, options, renderContext);
    }
    else if (isArray(vnode)) {
        var vnodes = normalizeChildren(vnode) || [];
        var res = new Array(vnodes.length);
        for (var i = 0; i < vnodes.length; i++) {
            res[i] = cloneAndMarkFunctionalResult(vnodes[i], data, renderContext.parent, options, renderContext);
        }
        return res;
    }
}
function cloneAndMarkFunctionalResult(vnode, data, contextVm, options, renderContext) {
    // #7817 clone node before setting fnContext, otherwise if the node is reused
    // (e.g. it was from a cached normal slot) the fnContext causes named slots
    // that should not be matched to match.
    var clone = cloneVNode(vnode);
    clone.fnContext = contextVm;
    clone.fnOptions = options;
    if (false) {}
    if (data.slot) {
        (clone.data || (clone.data = {})).slot = data.slot;
    }
    return clone;
}
function mergeProps(to, from) {
    for (var key in from) {
        to[camelize(key)] = from[key];
    }
}

function getComponentName(options) {
    return options.name || options.__name || options._componentTag;
}
// inline hooks to be invoked on component VNodes during patch
var componentVNodeHooks = {
    init: function (vnode, hydrating) {
        if (vnode.componentInstance &&
            !vnode.componentInstance._isDestroyed &&
            vnode.data.keepAlive) {
            // kept-alive components, treat as a patch
            var mountedNode = vnode; // work around flow
            componentVNodeHooks.prepatch(mountedNode, mountedNode);
        }
        else {
            var child = (vnode.componentInstance = createComponentInstanceForVnode(vnode, activeInstance));
            child.$mount(hydrating ? vnode.elm : undefined, hydrating);
        }
    },
    prepatch: function (oldVnode, vnode) {
        var options = vnode.componentOptions;
        var child = (vnode.componentInstance = oldVnode.componentInstance);
        updateChildComponent(child, options.propsData, // updated props
        options.listeners, // updated listeners
        vnode, // new parent vnode
        options.children // new children
        );
    },
    insert: function (vnode) {
        var context = vnode.context, componentInstance = vnode.componentInstance;
        if (!componentInstance._isMounted) {
            componentInstance._isMounted = true;
            callHook$1(componentInstance, 'mounted');
        }
        if (vnode.data.keepAlive) {
            if (context._isMounted) {
                // vue-router#1212
                // During updates, a kept-alive component's child components may
                // change, so directly walking the tree here may call activated hooks
                // on incorrect children. Instead we push them into a queue which will
                // be processed after the whole patch process ended.
                queueActivatedComponent(componentInstance);
            }
            else {
                activateChildComponent(componentInstance, true /* direct */);
            }
        }
    },
    destroy: function (vnode) {
        var componentInstance = vnode.componentInstance;
        if (!componentInstance._isDestroyed) {
            if (!vnode.data.keepAlive) {
                componentInstance.$destroy();
            }
            else {
                deactivateChildComponent(componentInstance, true /* direct */);
            }
        }
    }
};
var hooksToMerge = Object.keys(componentVNodeHooks);
function createComponent(Ctor, data, context, children, tag) {
    if (isUndef(Ctor)) {
        return;
    }
    var baseCtor = context.$options._base;
    // plain options object: turn it into a constructor
    if (isObject(Ctor)) {
        Ctor = baseCtor.extend(Ctor);
    }
    // if at this stage it's not a constructor or an async component factory,
    // reject.
    if (typeof Ctor !== 'function') {
        if (false) {}
        return;
    }
    // async component
    var asyncFactory;
    // @ts-expect-error
    if (isUndef(Ctor.cid)) {
        asyncFactory = Ctor;
        Ctor = resolveAsyncComponent(asyncFactory, baseCtor);
        if (Ctor === undefined) {
            // return a placeholder node for async component, which is rendered
            // as a comment node but preserves all the raw information for the node.
            // the information will be used for async server-rendering and hydration.
            return createAsyncPlaceholder(asyncFactory, data, context, children, tag);
        }
    }
    data = data || {};
    // resolve constructor options in case global mixins are applied after
    // component constructor creation
    resolveConstructorOptions(Ctor);
    // transform component v-model data into props & events
    if (isDef(data.model)) {
        // @ts-expect-error
        transformModel(Ctor.options, data);
    }
    // extract props
    // @ts-expect-error
    var propsData = extractPropsFromVNodeData(data, Ctor, tag);
    // functional component
    // @ts-expect-error
    if (isTrue(Ctor.options.functional)) {
        return createFunctionalComponent(Ctor, propsData, data, context, children);
    }
    // extract listeners, since these needs to be treated as
    // child component listeners instead of DOM listeners
    var listeners = data.on;
    // replace with listeners with .native modifier
    // so it gets processed during parent component patch.
    data.on = data.nativeOn;
    // @ts-expect-error
    if (isTrue(Ctor.options.abstract)) {
        // abstract components do not keep anything
        // other than props & listeners & slot
        // work around flow
        var slot = data.slot;
        data = {};
        if (slot) {
            data.slot = slot;
        }
    }
    // install component management hooks onto the placeholder node
    installComponentHooks(data);
    // return a placeholder vnode
    // @ts-expect-error
    var name = getComponentName(Ctor.options) || tag;
    var vnode = new VNode(
    // @ts-expect-error
    "vue-component-".concat(Ctor.cid).concat(name ? "-".concat(name) : ''), data, undefined, undefined, undefined, context, 
    // @ts-expect-error
    { Ctor: Ctor, propsData: propsData, listeners: listeners, tag: tag, children: children }, asyncFactory);
    return vnode;
}
function createComponentInstanceForVnode(
// we know it's MountedComponentVNode but flow doesn't
vnode, 
// activeInstance in lifecycle state
parent) {
    var options = {
        _isComponent: true,
        _parentVnode: vnode,
        parent: parent
    };
    // check inline-template render functions
    var inlineTemplate = vnode.data.inlineTemplate;
    if (isDef(inlineTemplate)) {
        options.render = inlineTemplate.render;
        options.staticRenderFns = inlineTemplate.staticRenderFns;
    }
    return new vnode.componentOptions.Ctor(options);
}
function installComponentHooks(data) {
    var hooks = data.hook || (data.hook = {});
    for (var i = 0; i < hooksToMerge.length; i++) {
        var key = hooksToMerge[i];
        var existing = hooks[key];
        var toMerge = componentVNodeHooks[key];
        // @ts-expect-error
        if (existing !== toMerge && !(existing && existing._merged)) {
            hooks[key] = existing ? mergeHook(toMerge, existing) : toMerge;
        }
    }
}
function mergeHook(f1, f2) {
    var merged = function (a, b) {
        // flow complains about extra args which is why we use any
        f1(a, b);
        f2(a, b);
    };
    merged._merged = true;
    return merged;
}
// transform component v-model info (value and callback) into
// prop and event handler respectively.
function transformModel(options, data) {
    var prop = (options.model && options.model.prop) || 'value';
    var event = (options.model && options.model.event) || 'input';
    (data.attrs || (data.attrs = {}))[prop] = data.model.value;
    var on = data.on || (data.on = {});
    var existing = on[event];
    var callback = data.model.callback;
    if (isDef(existing)) {
        if (isArray(existing)
            ? existing.indexOf(callback) === -1
            : existing !== callback) {
            on[event] = [callback].concat(existing);
        }
    }
    else {
        on[event] = callback;
    }
}

var warn = noop;
var tip = (/* unused pure expression or super */ null && (noop));
var generateComponentTrace; // work around flow check
var formatComponentName;
if (false) { var repeat_1, classify_1, classifyRE_1, hasConsole_1; }

/**
 * Option overwriting strategies are functions that handle
 * how to merge a parent option value and a child option
 * value into the final value.
 */
var strats = config.optionMergeStrategies;
/**
 * Options with restrictions
 */
if (false) {}
/**
 * Helper that recursively merges two data objects together.
 */
function mergeData(to, from, recursive) {
    if (recursive === void 0) { recursive = true; }
    if (!from)
        return to;
    var key, toVal, fromVal;
    var keys = hasSymbol
        ? Reflect.ownKeys(from)
        : Object.keys(from);
    for (var i = 0; i < keys.length; i++) {
        key = keys[i];
        // in case the object is already observed...
        if (key === '__ob__')
            continue;
        toVal = to[key];
        fromVal = from[key];
        if (!recursive || !hasOwn(to, key)) {
            set(to, key, fromVal);
        }
        else if (toVal !== fromVal &&
            isPlainObject(toVal) &&
            isPlainObject(fromVal)) {
            mergeData(toVal, fromVal);
        }
    }
    return to;
}
/**
 * Data
 */
function mergeDataOrFn(parentVal, childVal, vm) {
    if (!vm) {
        // in a Vue.extend merge, both should be functions
        if (!childVal) {
            return parentVal;
        }
        if (!parentVal) {
            return childVal;
        }
        // when parentVal & childVal are both present,
        // we need to return a function that returns the
        // merged result of both functions... no need to
        // check if parentVal is a function here because
        // it has to be a function to pass previous merges.
        return function mergedDataFn() {
            return mergeData(isFunction(childVal) ? childVal.call(this, this) : childVal, isFunction(parentVal) ? parentVal.call(this, this) : parentVal);
        };
    }
    else {
        return function mergedInstanceDataFn() {
            // instance merge
            var instanceData = isFunction(childVal)
                ? childVal.call(vm, vm)
                : childVal;
            var defaultData = isFunction(parentVal)
                ? parentVal.call(vm, vm)
                : parentVal;
            if (instanceData) {
                return mergeData(instanceData, defaultData);
            }
            else {
                return defaultData;
            }
        };
    }
}
strats.data = function (parentVal, childVal, vm) {
    if (!vm) {
        if (childVal && typeof childVal !== 'function') {
             false &&
                0;
            return parentVal;
        }
        return mergeDataOrFn(parentVal, childVal);
    }
    return mergeDataOrFn(parentVal, childVal, vm);
};
/**
 * Hooks and props are merged as arrays.
 */
function mergeLifecycleHook(parentVal, childVal) {
    var res = childVal
        ? parentVal
            ? parentVal.concat(childVal)
            : isArray(childVal)
                ? childVal
                : [childVal]
        : parentVal;
    return res ? dedupeHooks(res) : res;
}
function dedupeHooks(hooks) {
    var res = [];
    for (var i = 0; i < hooks.length; i++) {
        if (res.indexOf(hooks[i]) === -1) {
            res.push(hooks[i]);
        }
    }
    return res;
}
LIFECYCLE_HOOKS.forEach(function (hook) {
    strats[hook] = mergeLifecycleHook;
});
/**
 * Assets
 *
 * When a vm is present (instance creation), we need to do
 * a three-way merge between constructor options, instance
 * options and parent options.
 */
function mergeAssets(parentVal, childVal, vm, key) {
    var res = Object.create(parentVal || null);
    if (childVal) {
         false && 0;
        return extend(res, childVal);
    }
    else {
        return res;
    }
}
ASSET_TYPES.forEach(function (type) {
    strats[type + 's'] = mergeAssets;
});
/**
 * Watchers.
 *
 * Watchers hashes should not overwrite one
 * another, so we merge them as arrays.
 */
strats.watch = function (parentVal, childVal, vm, key) {
    // work around Firefox's Object.prototype.watch...
    //@ts-expect-error work around
    if (parentVal === nativeWatch)
        parentVal = undefined;
    //@ts-expect-error work around
    if (childVal === nativeWatch)
        childVal = undefined;
    /* istanbul ignore if */
    if (!childVal)
        return Object.create(parentVal || null);
    if (false) {}
    if (!parentVal)
        return childVal;
    var ret = {};
    extend(ret, parentVal);
    for (var key_1 in childVal) {
        var parent_1 = ret[key_1];
        var child = childVal[key_1];
        if (parent_1 && !isArray(parent_1)) {
            parent_1 = [parent_1];
        }
        ret[key_1] = parent_1 ? parent_1.concat(child) : isArray(child) ? child : [child];
    }
    return ret;
};
/**
 * Other object hashes.
 */
strats.props =
    strats.methods =
        strats.inject =
            strats.computed =
                function (parentVal, childVal, vm, key) {
                    if (childVal && "production" !== 'production') {}
                    if (!parentVal)
                        return childVal;
                    var ret = Object.create(null);
                    extend(ret, parentVal);
                    if (childVal)
                        extend(ret, childVal);
                    return ret;
                };
strats.provide = function (parentVal, childVal) {
    if (!parentVal)
        return childVal;
    return function () {
        var ret = Object.create(null);
        mergeData(ret, isFunction(parentVal) ? parentVal.call(this) : parentVal);
        if (childVal) {
            mergeData(ret, isFunction(childVal) ? childVal.call(this) : childVal, false // non-recursive
            );
        }
        return ret;
    };
};
/**
 * Default strategy.
 */
var defaultStrat = function (parentVal, childVal) {
    return childVal === undefined ? parentVal : childVal;
};
/**
 * Validate component names
 */
function checkComponents(options) {
    for (var key in options.components) {
        validateComponentName(key);
    }
}
function validateComponentName(name) {
    if (!new RegExp("^[a-zA-Z][\\-\\.0-9_".concat(unicodeRegExp.source, "]*$")).test(name)) {
        warn('Invalid component name: "' +
            name +
            '". Component names ' +
            'should conform to valid custom element name in html5 specification.');
    }
    if (isBuiltInTag(name) || config.isReservedTag(name)) {
        warn('Do not use built-in or reserved HTML elements as component ' +
            'id: ' +
            name);
    }
}
/**
 * Ensure all props option syntax are normalized into the
 * Object-based format.
 */
function normalizeProps(options, vm) {
    var props = options.props;
    if (!props)
        return;
    var res = {};
    var i, val, name;
    if (isArray(props)) {
        i = props.length;
        while (i--) {
            val = props[i];
            if (typeof val === 'string') {
                name = camelize(val);
                res[name] = { type: null };
            }
            else if (false) {}
        }
    }
    else if (isPlainObject(props)) {
        for (var key in props) {
            val = props[key];
            name = camelize(key);
            res[name] = isPlainObject(val) ? val : { type: val };
        }
    }
    else if (false) {}
    options.props = res;
}
/**
 * Normalize all injections into Object-based format
 */
function normalizeInject(options, vm) {
    var inject = options.inject;
    if (!inject)
        return;
    var normalized = (options.inject = {});
    if (isArray(inject)) {
        for (var i = 0; i < inject.length; i++) {
            normalized[inject[i]] = { from: inject[i] };
        }
    }
    else if (isPlainObject(inject)) {
        for (var key in inject) {
            var val = inject[key];
            normalized[key] = isPlainObject(val)
                ? extend({ from: key }, val)
                : { from: val };
        }
    }
    else if (false) {}
}
/**
 * Normalize raw function directives into object format.
 */
function normalizeDirectives$1(options) {
    var dirs = options.directives;
    if (dirs) {
        for (var key in dirs) {
            var def = dirs[key];
            if (isFunction(def)) {
                dirs[key] = { bind: def, update: def };
            }
        }
    }
}
function assertObjectType(name, value, vm) {
    if (!isPlainObject(value)) {
        warn("Invalid value for option \"".concat(name, "\": expected an Object, ") +
            "but got ".concat(toRawType(value), "."), vm);
    }
}
/**
 * Merge two option objects into a new one.
 * Core utility used in both instantiation and inheritance.
 */
function mergeOptions(parent, child, vm) {
    if (false) {}
    if (isFunction(child)) {
        // @ts-expect-error
        child = child.options;
    }
    normalizeProps(child, vm);
    normalizeInject(child, vm);
    normalizeDirectives$1(child);
    // Apply extends and mixins on the child options,
    // but only if it is a raw options object that isn't
    // the result of another mergeOptions call.
    // Only merged options has the _base property.
    if (!child._base) {
        if (child.extends) {
            parent = mergeOptions(parent, child.extends, vm);
        }
        if (child.mixins) {
            for (var i = 0, l = child.mixins.length; i < l; i++) {
                parent = mergeOptions(parent, child.mixins[i], vm);
            }
        }
    }
    var options = {};
    var key;
    for (key in parent) {
        mergeField(key);
    }
    for (key in child) {
        if (!hasOwn(parent, key)) {
            mergeField(key);
        }
    }
    function mergeField(key) {
        var strat = strats[key] || defaultStrat;
        options[key] = strat(parent[key], child[key], vm, key);
    }
    return options;
}
/**
 * Resolve an asset.
 * This function is used because child instances need access
 * to assets defined in its ancestor chain.
 */
function resolveAsset(options, type, id, warnMissing) {
    /* istanbul ignore if */
    if (typeof id !== 'string') {
        return;
    }
    var assets = options[type];
    // check local registration variations first
    if (hasOwn(assets, id))
        return assets[id];
    var camelizedId = camelize(id);
    if (hasOwn(assets, camelizedId))
        return assets[camelizedId];
    var PascalCaseId = capitalize(camelizedId);
    if (hasOwn(assets, PascalCaseId))
        return assets[PascalCaseId];
    // fallback to prototype chain
    var res = assets[id] || assets[camelizedId] || assets[PascalCaseId];
    if (false) {}
    return res;
}

function validateProp(key, propOptions, propsData, vm) {
    var prop = propOptions[key];
    var absent = !hasOwn(propsData, key);
    var value = propsData[key];
    // boolean casting
    var booleanIndex = getTypeIndex(Boolean, prop.type);
    if (booleanIndex > -1) {
        if (absent && !hasOwn(prop, 'default')) {
            value = false;
        }
        else if (value === '' || value === hyphenate(key)) {
            // only cast empty string / same name to boolean if
            // boolean has higher priority
            var stringIndex = getTypeIndex(String, prop.type);
            if (stringIndex < 0 || booleanIndex < stringIndex) {
                value = true;
            }
        }
    }
    // check default value
    if (value === undefined) {
        value = getPropDefaultValue(vm, prop, key);
        // since the default value is a fresh copy,
        // make sure to observe it.
        var prevShouldObserve = shouldObserve;
        toggleObserving(true);
        observe(value);
        toggleObserving(prevShouldObserve);
    }
    if (false) {}
    return value;
}
/**
 * Get the default value of a prop.
 */
function getPropDefaultValue(vm, prop, key) {
    // no default, return undefined
    if (!hasOwn(prop, 'default')) {
        return undefined;
    }
    var def = prop.default;
    // warn against non-factory defaults for Object & Array
    if (false) {}
    // the raw prop value was also undefined from previous render,
    // return previous default value to avoid unnecessary watcher trigger
    if (vm &&
        vm.$options.propsData &&
        vm.$options.propsData[key] === undefined &&
        vm._props[key] !== undefined) {
        return vm._props[key];
    }
    // call factory function for non-Function types
    // a value is Function if its prototype is function even across different execution context
    return isFunction(def) && getType(prop.type) !== 'Function'
        ? def.call(vm)
        : def;
}
/**
 * Assert whether a prop is valid.
 */
function assertProp(prop, name, value, vm, absent) {
    if (prop.required && absent) {
        warn('Missing required prop: "' + name + '"', vm);
        return;
    }
    if (value == null && !prop.required) {
        return;
    }
    var type = prop.type;
    var valid = !type || type === true;
    var expectedTypes = [];
    if (type) {
        if (!isArray(type)) {
            type = [type];
        }
        for (var i = 0; i < type.length && !valid; i++) {
            var assertedType = assertType(value, type[i], vm);
            expectedTypes.push(assertedType.expectedType || '');
            valid = assertedType.valid;
        }
    }
    var haveExpectedTypes = expectedTypes.some(function (t) { return t; });
    if (!valid && haveExpectedTypes) {
        warn(getInvalidTypeMessage(name, value, expectedTypes), vm);
        return;
    }
    var validator = prop.validator;
    if (validator) {
        if (!validator(value)) {
            warn('Invalid prop: custom validator check failed for prop "' + name + '".', vm);
        }
    }
}
var simpleCheckRE = /^(String|Number|Boolean|Function|Symbol|BigInt)$/;
function assertType(value, type, vm) {
    var valid;
    var expectedType = getType(type);
    if (simpleCheckRE.test(expectedType)) {
        var t = typeof value;
        valid = t === expectedType.toLowerCase();
        // for primitive wrapper objects
        if (!valid && t === 'object') {
            valid = value instanceof type;
        }
    }
    else if (expectedType === 'Object') {
        valid = isPlainObject(value);
    }
    else if (expectedType === 'Array') {
        valid = isArray(value);
    }
    else {
        try {
            valid = value instanceof type;
        }
        catch (e) {
            warn('Invalid prop type: "' + String(type) + '" is not a constructor', vm);
            valid = false;
        }
    }
    return {
        valid: valid,
        expectedType: expectedType
    };
}
var functionTypeCheckRE = /^\s*function (\w+)/;
/**
 * Use function string name to check built-in types,
 * because a simple equality check will fail when running
 * across different vms / iframes.
 */
function getType(fn) {
    var match = fn && fn.toString().match(functionTypeCheckRE);
    return match ? match[1] : '';
}
function isSameType(a, b) {
    return getType(a) === getType(b);
}
function getTypeIndex(type, expectedTypes) {
    if (!isArray(expectedTypes)) {
        return isSameType(expectedTypes, type) ? 0 : -1;
    }
    for (var i = 0, len = expectedTypes.length; i < len; i++) {
        if (isSameType(expectedTypes[i], type)) {
            return i;
        }
    }
    return -1;
}
function getInvalidTypeMessage(name, value, expectedTypes) {
    var message = "Invalid prop: type check failed for prop \"".concat(name, "\".") +
        " Expected ".concat(expectedTypes.map(capitalize).join(', '));
    var expectedType = expectedTypes[0];
    var receivedType = toRawType(value);
    // check if we need to specify expected value
    if (expectedTypes.length === 1 &&
        isExplicable(expectedType) &&
        isExplicable(typeof value) &&
        !isBoolean(expectedType, receivedType)) {
        message += " with value ".concat(styleValue(value, expectedType));
    }
    message += ", got ".concat(receivedType, " ");
    // check if we need to specify received value
    if (isExplicable(receivedType)) {
        message += "with value ".concat(styleValue(value, receivedType), ".");
    }
    return message;
}
function styleValue(value, type) {
    if (type === 'String') {
        return "\"".concat(value, "\"");
    }
    else if (type === 'Number') {
        return "".concat(Number(value));
    }
    else {
        return "".concat(value);
    }
}
var EXPLICABLE_TYPES = (/* unused pure expression or super */ null && (['string', 'number', 'boolean']));
function isExplicable(value) {
    return EXPLICABLE_TYPES.some(function (elem) { return value.toLowerCase() === elem; });
}
function isBoolean() {
    var args = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
    }
    return args.some(function (elem) { return elem.toLowerCase() === 'boolean'; });
}

/* not type checking this file because flow doesn't play well with Proxy */
var initProxy;
if (false) { var getHandler_1, hasHandler_1, isBuiltInModifier_1, hasProxy_1, warnReservedPrefix_1, warnNonPresent_1, allowedGlobals_1; }

var sharedPropertyDefinition = {
    enumerable: true,
    configurable: true,
    get: noop,
    set: noop
};
function proxy(target, sourceKey, key) {
    sharedPropertyDefinition.get = function proxyGetter() {
        return this[sourceKey][key];
    };
    sharedPropertyDefinition.set = function proxySetter(val) {
        this[sourceKey][key] = val;
    };
    Object.defineProperty(target, key, sharedPropertyDefinition);
}
function initState(vm) {
    var opts = vm.$options;
    if (opts.props)
        initProps$1(vm, opts.props);
    // Composition API
    initSetup(vm);
    if (opts.methods)
        initMethods(vm, opts.methods);
    if (opts.data) {
        initData(vm);
    }
    else {
        var ob = observe((vm._data = {}));
        ob && ob.vmCount++;
    }
    if (opts.computed)
        initComputed$1(vm, opts.computed);
    if (opts.watch && opts.watch !== nativeWatch) {
        initWatch(vm, opts.watch);
    }
}
function initProps$1(vm, propsOptions) {
    var propsData = vm.$options.propsData || {};
    var props = (vm._props = shallowReactive({}));
    // cache prop keys so that future props updates can iterate using Array
    // instead of dynamic object key enumeration.
    var keys = (vm.$options._propKeys = []);
    var isRoot = !vm.$parent;
    // root instance props should be converted
    if (!isRoot) {
        toggleObserving(false);
    }
    var _loop_1 = function (key) {
        keys.push(key);
        var value = validateProp(key, propsOptions, propsData, vm);
        /* istanbul ignore else */
        if (false) { var hyphenatedKey; }
        else {
            defineReactive(props, key, value);
        }
        // static props are already proxied on the component's prototype
        // during Vue.extend(). We only need to proxy props defined at
        // instantiation here.
        if (!(key in vm)) {
            proxy(vm, "_props", key);
        }
    };
    for (var key in propsOptions) {
        _loop_1(key);
    }
    toggleObserving(true);
}
function initData(vm) {
    var data = vm.$options.data;
    data = vm._data = isFunction(data) ? getData(data, vm) : data || {};
    if (!isPlainObject(data)) {
        data = {};
         false &&
            0;
    }
    // proxy data on instance
    var keys = Object.keys(data);
    var props = vm.$options.props;
    var methods = vm.$options.methods;
    var i = keys.length;
    while (i--) {
        var key = keys[i];
        if (false) {}
        if (props && hasOwn(props, key)) {
             false &&
                0;
        }
        else if (!isReserved(key)) {
            proxy(vm, "_data", key);
        }
    }
    // observe data
    var ob = observe(data);
    ob && ob.vmCount++;
}
function getData(data, vm) {
    // #7573 disable dep collection when invoking data getters
    pushTarget();
    try {
        return data.call(vm, vm);
    }
    catch (e) {
        handleError(e, vm, "data()");
        return {};
    }
    finally {
        popTarget();
    }
}
var computedWatcherOptions = { lazy: true };
function initComputed$1(vm, computed) {
    // $flow-disable-line
    var watchers = (vm._computedWatchers = Object.create(null));
    // computed properties are just getters during SSR
    var isSSR = isServerRendering();
    for (var key in computed) {
        var userDef = computed[key];
        var getter = isFunction(userDef) ? userDef : userDef.get;
        if (false) {}
        if (!isSSR) {
            // create internal watcher for the computed property.
            watchers[key] = new Watcher(vm, getter || noop, noop, computedWatcherOptions);
        }
        // component-defined computed properties are already defined on the
        // component prototype. We only need to define computed properties defined
        // at instantiation here.
        if (!(key in vm)) {
            defineComputed(vm, key, userDef);
        }
        else if (false) {}
    }
}
function defineComputed(target, key, userDef) {
    var shouldCache = !isServerRendering();
    if (isFunction(userDef)) {
        sharedPropertyDefinition.get = shouldCache
            ? createComputedGetter(key)
            : createGetterInvoker(userDef);
        sharedPropertyDefinition.set = noop;
    }
    else {
        sharedPropertyDefinition.get = userDef.get
            ? shouldCache && userDef.cache !== false
                ? createComputedGetter(key)
                : createGetterInvoker(userDef.get)
            : noop;
        sharedPropertyDefinition.set = userDef.set || noop;
    }
    if (false) {}
    Object.defineProperty(target, key, sharedPropertyDefinition);
}
function createComputedGetter(key) {
    return function computedGetter() {
        var watcher = this._computedWatchers && this._computedWatchers[key];
        if (watcher) {
            if (watcher.dirty) {
                watcher.evaluate();
            }
            if (Dep.target) {
                if (false) {}
                watcher.depend();
            }
            return watcher.value;
        }
    };
}
function createGetterInvoker(fn) {
    return function computedGetter() {
        return fn.call(this, this);
    };
}
function initMethods(vm, methods) {
    var props = vm.$options.props;
    for (var key in methods) {
        if (false) {}
        vm[key] = typeof methods[key] !== 'function' ? noop : bind(methods[key], vm);
    }
}
function initWatch(vm, watch) {
    for (var key in watch) {
        var handler = watch[key];
        if (isArray(handler)) {
            for (var i = 0; i < handler.length; i++) {
                createWatcher(vm, key, handler[i]);
            }
        }
        else {
            createWatcher(vm, key, handler);
        }
    }
}
function createWatcher(vm, expOrFn, handler, options) {
    if (isPlainObject(handler)) {
        options = handler;
        handler = handler.handler;
    }
    if (typeof handler === 'string') {
        handler = vm[handler];
    }
    return vm.$watch(expOrFn, handler, options);
}
function stateMixin(Vue) {
    // flow somehow has problems with directly declared definition object
    // when using Object.defineProperty, so we have to procedurally build up
    // the object here.
    var dataDef = {};
    dataDef.get = function () {
        return this._data;
    };
    var propsDef = {};
    propsDef.get = function () {
        return this._props;
    };
    if (false) {}
    Object.defineProperty(Vue.prototype, '$data', dataDef);
    Object.defineProperty(Vue.prototype, '$props', propsDef);
    Vue.prototype.$set = set;
    Vue.prototype.$delete = del;
    Vue.prototype.$watch = function (expOrFn, cb, options) {
        var vm = this;
        if (isPlainObject(cb)) {
            return createWatcher(vm, expOrFn, cb, options);
        }
        options = options || {};
        options.user = true;
        var watcher = new Watcher(vm, expOrFn, cb, options);
        if (options.immediate) {
            var info = "callback for immediate watcher \"".concat(watcher.expression, "\"");
            pushTarget();
            invokeWithErrorHandling(cb, vm, [watcher.value], vm, info);
            popTarget();
        }
        return function unwatchFn() {
            watcher.teardown();
        };
    };
}

var uid = 0;
function initMixin$1(Vue) {
    Vue.prototype._init = function (options) {
        var vm = this;
        // a uid
        vm._uid = uid++;
        var startTag, endTag;
        /* istanbul ignore if */
        if (false) {}
        // a flag to mark this as a Vue instance without having to do instanceof
        // check
        vm._isVue = true;
        // avoid instances from being observed
        vm.__v_skip = true;
        // effect scope
        vm._scope = new EffectScope(true /* detached */);
        vm._scope._vm = true;
        // merge options
        if (options && options._isComponent) {
            // optimize internal component instantiation
            // since dynamic options merging is pretty slow, and none of the
            // internal component options needs special treatment.
            initInternalComponent(vm, options);
        }
        else {
            vm.$options = mergeOptions(resolveConstructorOptions(vm.constructor), options || {}, vm);
        }
        /* istanbul ignore else */
        if (false) {}
        else {
            vm._renderProxy = vm;
        }
        // expose real self
        vm._self = vm;
        initLifecycle(vm);
        initEvents(vm);
        initRender(vm);
        callHook$1(vm, 'beforeCreate', undefined, false /* setContext */);
        initInjections(vm); // resolve injections before data/props
        initState(vm);
        initProvide(vm); // resolve provide after data/props
        callHook$1(vm, 'created');
        /* istanbul ignore if */
        if (false) {}
        if (vm.$options.el) {
            vm.$mount(vm.$options.el);
        }
    };
}
function initInternalComponent(vm, options) {
    var opts = (vm.$options = Object.create(vm.constructor.options));
    // doing this because it's faster than dynamic enumeration.
    var parentVnode = options._parentVnode;
    opts.parent = options.parent;
    opts._parentVnode = parentVnode;
    var vnodeComponentOptions = parentVnode.componentOptions;
    opts.propsData = vnodeComponentOptions.propsData;
    opts._parentListeners = vnodeComponentOptions.listeners;
    opts._renderChildren = vnodeComponentOptions.children;
    opts._componentTag = vnodeComponentOptions.tag;
    if (options.render) {
        opts.render = options.render;
        opts.staticRenderFns = options.staticRenderFns;
    }
}
function resolveConstructorOptions(Ctor) {
    var options = Ctor.options;
    if (Ctor.super) {
        var superOptions = resolveConstructorOptions(Ctor.super);
        var cachedSuperOptions = Ctor.superOptions;
        if (superOptions !== cachedSuperOptions) {
            // super option changed,
            // need to resolve new options.
            Ctor.superOptions = superOptions;
            // check if there are any late-modified/attached options (#4976)
            var modifiedOptions = resolveModifiedOptions(Ctor);
            // update base extend options
            if (modifiedOptions) {
                extend(Ctor.extendOptions, modifiedOptions);
            }
            options = Ctor.options = mergeOptions(superOptions, Ctor.extendOptions);
            if (options.name) {
                options.components[options.name] = Ctor;
            }
        }
    }
    return options;
}
function resolveModifiedOptions(Ctor) {
    var modified;
    var latest = Ctor.options;
    var sealed = Ctor.sealedOptions;
    for (var key in latest) {
        if (latest[key] !== sealed[key]) {
            if (!modified)
                modified = {};
            modified[key] = latest[key];
        }
    }
    return modified;
}

function Vue(options) {
    if (false) {}
    this._init(options);
}
//@ts-expect-error Vue has function type
initMixin$1(Vue);
//@ts-expect-error Vue has function type
stateMixin(Vue);
//@ts-expect-error Vue has function type
eventsMixin(Vue);
//@ts-expect-error Vue has function type
lifecycleMixin(Vue);
//@ts-expect-error Vue has function type
renderMixin(Vue);

function initUse(Vue) {
    Vue.use = function (plugin) {
        var installedPlugins = this._installedPlugins || (this._installedPlugins = []);
        if (installedPlugins.indexOf(plugin) > -1) {
            return this;
        }
        // additional parameters
        var args = toArray(arguments, 1);
        args.unshift(this);
        if (isFunction(plugin.install)) {
            plugin.install.apply(plugin, args);
        }
        else if (isFunction(plugin)) {
            plugin.apply(null, args);
        }
        installedPlugins.push(plugin);
        return this;
    };
}

function initMixin(Vue) {
    Vue.mixin = function (mixin) {
        this.options = mergeOptions(this.options, mixin);
        return this;
    };
}

function initExtend(Vue) {
    /**
     * Each instance constructor, including Vue, has a unique
     * cid. This enables us to create wrapped "child
     * constructors" for prototypal inheritance and cache them.
     */
    Vue.cid = 0;
    var cid = 1;
    /**
     * Class inheritance
     */
    Vue.extend = function (extendOptions) {
        extendOptions = extendOptions || {};
        var Super = this;
        var SuperId = Super.cid;
        var cachedCtors = extendOptions._Ctor || (extendOptions._Ctor = {});
        if (cachedCtors[SuperId]) {
            return cachedCtors[SuperId];
        }
        var name = getComponentName(extendOptions) || getComponentName(Super.options);
        if (false) {}
        var Sub = function VueComponent(options) {
            this._init(options);
        };
        Sub.prototype = Object.create(Super.prototype);
        Sub.prototype.constructor = Sub;
        Sub.cid = cid++;
        Sub.options = mergeOptions(Super.options, extendOptions);
        Sub['super'] = Super;
        // For props and computed properties, we define the proxy getters on
        // the Vue instances at extension time, on the extended prototype. This
        // avoids Object.defineProperty calls for each instance created.
        if (Sub.options.props) {
            initProps(Sub);
        }
        if (Sub.options.computed) {
            initComputed(Sub);
        }
        // allow further extension/mixin/plugin usage
        Sub.extend = Super.extend;
        Sub.mixin = Super.mixin;
        Sub.use = Super.use;
        // create asset registers, so extended classes
        // can have their private assets too.
        ASSET_TYPES.forEach(function (type) {
            Sub[type] = Super[type];
        });
        // enable recursive self-lookup
        if (name) {
            Sub.options.components[name] = Sub;
        }
        // keep a reference to the super options at extension time.
        // later at instantiation we can check if Super's options have
        // been updated.
        Sub.superOptions = Super.options;
        Sub.extendOptions = extendOptions;
        Sub.sealedOptions = extend({}, Sub.options);
        // cache constructor
        cachedCtors[SuperId] = Sub;
        return Sub;
    };
}
function initProps(Comp) {
    var props = Comp.options.props;
    for (var key in props) {
        proxy(Comp.prototype, "_props", key);
    }
}
function initComputed(Comp) {
    var computed = Comp.options.computed;
    for (var key in computed) {
        defineComputed(Comp.prototype, key, computed[key]);
    }
}

function initAssetRegisters(Vue) {
    /**
     * Create asset registration methods.
     */
    ASSET_TYPES.forEach(function (type) {
        // @ts-expect-error function is not exact same type
        Vue[type] = function (id, definition) {
            if (!definition) {
                return this.options[type + 's'][id];
            }
            else {
                /* istanbul ignore if */
                if (false) {}
                if (type === 'component' && isPlainObject(definition)) {
                    // @ts-expect-error
                    definition.name = definition.name || id;
                    definition = this.options._base.extend(definition);
                }
                if (type === 'directive' && isFunction(definition)) {
                    definition = { bind: definition, update: definition };
                }
                this.options[type + 's'][id] = definition;
                return definition;
            }
        };
    });
}

function _getComponentName(opts) {
    return opts && (getComponentName(opts.Ctor.options) || opts.tag);
}
function matches(pattern, name) {
    if (isArray(pattern)) {
        return pattern.indexOf(name) > -1;
    }
    else if (typeof pattern === 'string') {
        return pattern.split(',').indexOf(name) > -1;
    }
    else if (isRegExp(pattern)) {
        return pattern.test(name);
    }
    /* istanbul ignore next */
    return false;
}
function pruneCache(keepAliveInstance, filter) {
    var cache = keepAliveInstance.cache, keys = keepAliveInstance.keys, _vnode = keepAliveInstance._vnode;
    for (var key in cache) {
        var entry = cache[key];
        if (entry) {
            var name_1 = entry.name;
            if (name_1 && !filter(name_1)) {
                pruneCacheEntry(cache, key, keys, _vnode);
            }
        }
    }
}
function pruneCacheEntry(cache, key, keys, current) {
    var entry = cache[key];
    if (entry && (!current || entry.tag !== current.tag)) {
        // @ts-expect-error can be undefined
        entry.componentInstance.$destroy();
    }
    cache[key] = null;
    remove$2(keys, key);
}
var patternTypes = [String, RegExp, Array];
// TODO defineComponent
var KeepAlive = {
    name: 'keep-alive',
    abstract: true,
    props: {
        include: patternTypes,
        exclude: patternTypes,
        max: [String, Number]
    },
    methods: {
        cacheVNode: function () {
            var _a = this, cache = _a.cache, keys = _a.keys, vnodeToCache = _a.vnodeToCache, keyToCache = _a.keyToCache;
            if (vnodeToCache) {
                var tag = vnodeToCache.tag, componentInstance = vnodeToCache.componentInstance, componentOptions = vnodeToCache.componentOptions;
                cache[keyToCache] = {
                    name: _getComponentName(componentOptions),
                    tag: tag,
                    componentInstance: componentInstance
                };
                keys.push(keyToCache);
                // prune oldest entry
                if (this.max && keys.length > parseInt(this.max)) {
                    pruneCacheEntry(cache, keys[0], keys, this._vnode);
                }
                this.vnodeToCache = null;
            }
        }
    },
    created: function () {
        this.cache = Object.create(null);
        this.keys = [];
    },
    destroyed: function () {
        for (var key in this.cache) {
            pruneCacheEntry(this.cache, key, this.keys);
        }
    },
    mounted: function () {
        var _this = this;
        this.cacheVNode();
        this.$watch('include', function (val) {
            pruneCache(_this, function (name) { return matches(val, name); });
        });
        this.$watch('exclude', function (val) {
            pruneCache(_this, function (name) { return !matches(val, name); });
        });
    },
    updated: function () {
        this.cacheVNode();
    },
    render: function () {
        var slot = this.$slots.default;
        var vnode = getFirstComponentChild(slot);
        var componentOptions = vnode && vnode.componentOptions;
        if (componentOptions) {
            // check pattern
            var name_2 = _getComponentName(componentOptions);
            var _a = this, include = _a.include, exclude = _a.exclude;
            if (
            // not included
            (include && (!name_2 || !matches(include, name_2))) ||
                // excluded
                (exclude && name_2 && matches(exclude, name_2))) {
                return vnode;
            }
            var _b = this, cache = _b.cache, keys = _b.keys;
            var key = vnode.key == null
                ? // same constructor may get registered as different local components
                    // so cid alone is not enough (#3269)
                    componentOptions.Ctor.cid +
                        (componentOptions.tag ? "::".concat(componentOptions.tag) : '')
                : vnode.key;
            if (cache[key]) {
                vnode.componentInstance = cache[key].componentInstance;
                // make current key freshest
                remove$2(keys, key);
                keys.push(key);
            }
            else {
                // delay setting the cache until update
                this.vnodeToCache = vnode;
                this.keyToCache = key;
            }
            // @ts-expect-error can vnode.data can be undefined
            vnode.data.keepAlive = true;
        }
        return vnode || (slot && slot[0]);
    }
};

var builtInComponents = {
    KeepAlive: KeepAlive
};

function initGlobalAPI(Vue) {
    // config
    var configDef = {};
    configDef.get = function () { return config; };
    if (false) {}
    Object.defineProperty(Vue, 'config', configDef);
    // exposed util methods.
    // NOTE: these are not considered part of the public API - avoid relying on
    // them unless you are aware of the risk.
    Vue.util = {
        warn: warn,
        extend: extend,
        mergeOptions: mergeOptions,
        defineReactive: defineReactive
    };
    Vue.set = set;
    Vue.delete = del;
    Vue.nextTick = nextTick;
    // 2.6 explicit observable API
    Vue.observable = function (obj) {
        observe(obj);
        return obj;
    };
    Vue.options = Object.create(null);
    ASSET_TYPES.forEach(function (type) {
        Vue.options[type + 's'] = Object.create(null);
    });
    // this is used to identify the "base" constructor to extend all plain-object
    // components with in Weex's multi-instance scenarios.
    Vue.options._base = Vue;
    extend(Vue.options.components, builtInComponents);
    initUse(Vue);
    initMixin(Vue);
    initExtend(Vue);
    initAssetRegisters(Vue);
}

initGlobalAPI(Vue);
Object.defineProperty(Vue.prototype, '$isServer', {
    get: isServerRendering
});
Object.defineProperty(Vue.prototype, '$ssrContext', {
    get: function () {
        /* istanbul ignore next */
        return this.$vnode && this.$vnode.ssrContext;
    }
});
// expose FunctionalRenderContext for ssr runtime helper installation
Object.defineProperty(Vue, 'FunctionalRenderContext', {
    value: FunctionalRenderContext
});
Vue.version = version;

// these are reserved for web because they are directly compiled away
// during template compilation
var isReservedAttr = makeMap('style,class');
// attributes that should be using props for binding
var acceptValue = makeMap('input,textarea,option,select,progress');
var mustUseProp = function (tag, type, attr) {
    return ((attr === 'value' && acceptValue(tag) && type !== 'button') ||
        (attr === 'selected' && tag === 'option') ||
        (attr === 'checked' && tag === 'input') ||
        (attr === 'muted' && tag === 'video'));
};
var isEnumeratedAttr = makeMap('contenteditable,draggable,spellcheck');
var isValidContentEditableValue = makeMap('events,caret,typing,plaintext-only');
var convertEnumeratedValue = function (key, value) {
    return isFalsyAttrValue(value) || value === 'false'
        ? 'false'
        : // allow arbitrary string value for contenteditable
            key === 'contenteditable' && isValidContentEditableValue(value)
                ? value
                : 'true';
};
var isBooleanAttr = makeMap('allowfullscreen,async,autofocus,autoplay,checked,compact,controls,declare,' +
    'default,defaultchecked,defaultmuted,defaultselected,defer,disabled,' +
    'enabled,formnovalidate,hidden,indeterminate,inert,ismap,itemscope,loop,multiple,' +
    'muted,nohref,noresize,noshade,novalidate,nowrap,open,pauseonexit,readonly,' +
    'required,reversed,scoped,seamless,selected,sortable,' +
    'truespeed,typemustmatch,visible');
var xlinkNS = 'http://www.w3.org/1999/xlink';
var isXlink = function (name) {
    return name.charAt(5) === ':' && name.slice(0, 5) === 'xlink';
};
var getXlinkProp = function (name) {
    return isXlink(name) ? name.slice(6, name.length) : '';
};
var isFalsyAttrValue = function (val) {
    return val == null || val === false;
};

function genClassForVnode(vnode) {
    var data = vnode.data;
    var parentNode = vnode;
    var childNode = vnode;
    while (isDef(childNode.componentInstance)) {
        childNode = childNode.componentInstance._vnode;
        if (childNode && childNode.data) {
            data = mergeClassData(childNode.data, data);
        }
    }
    // @ts-expect-error parentNode.parent not VNodeWithData
    while (isDef((parentNode = parentNode.parent))) {
        if (parentNode && parentNode.data) {
            data = mergeClassData(data, parentNode.data);
        }
    }
    return renderClass(data.staticClass, data.class);
}
function mergeClassData(child, parent) {
    return {
        staticClass: concat(child.staticClass, parent.staticClass),
        class: isDef(child.class) ? [child.class, parent.class] : parent.class
    };
}
function renderClass(staticClass, dynamicClass) {
    if (isDef(staticClass) || isDef(dynamicClass)) {
        return concat(staticClass, stringifyClass(dynamicClass));
    }
    /* istanbul ignore next */
    return '';
}
function concat(a, b) {
    return a ? (b ? a + ' ' + b : a) : b || '';
}
function stringifyClass(value) {
    if (Array.isArray(value)) {
        return stringifyArray(value);
    }
    if (isObject(value)) {
        return stringifyObject(value);
    }
    if (typeof value === 'string') {
        return value;
    }
    /* istanbul ignore next */
    return '';
}
function stringifyArray(value) {
    var res = '';
    var stringified;
    for (var i = 0, l = value.length; i < l; i++) {
        if (isDef((stringified = stringifyClass(value[i]))) && stringified !== '') {
            if (res)
                res += ' ';
            res += stringified;
        }
    }
    return res;
}
function stringifyObject(value) {
    var res = '';
    for (var key in value) {
        if (value[key]) {
            if (res)
                res += ' ';
            res += key;
        }
    }
    return res;
}

var namespaceMap = {
    svg: 'http://www.w3.org/2000/svg',
    math: 'http://www.w3.org/1998/Math/MathML'
};
var isHTMLTag = makeMap('html,body,base,head,link,meta,style,title,' +
    'address,article,aside,footer,header,h1,h2,h3,h4,h5,h6,hgroup,nav,section,' +
    'div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,' +
    'a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,rtc,ruby,' +
    's,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,' +
    'embed,object,param,source,canvas,script,noscript,del,ins,' +
    'caption,col,colgroup,table,thead,tbody,td,th,tr,' +
    'button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,' +
    'output,progress,select,textarea,' +
    'details,dialog,menu,menuitem,summary,' +
    'content,element,shadow,template,blockquote,iframe,tfoot');
// this map is intentionally selective, only covering SVG elements that may
// contain child elements.
var isSVG = makeMap('svg,animate,circle,clippath,cursor,defs,desc,ellipse,filter,font-face,' +
    'foreignobject,g,glyph,image,line,marker,mask,missing-glyph,path,pattern,' +
    'polygon,polyline,rect,switch,symbol,text,textpath,tspan,use,view', true);
var isReservedTag = function (tag) {
    return isHTMLTag(tag) || isSVG(tag);
};
function getTagNamespace(tag) {
    if (isSVG(tag)) {
        return 'svg';
    }
    // basic support for MathML
    // note it doesn't support other MathML elements being component roots
    if (tag === 'math') {
        return 'math';
    }
}
var unknownElementCache = Object.create(null);
function isUnknownElement(tag) {
    /* istanbul ignore if */
    if (!inBrowser) {
        return true;
    }
    if (isReservedTag(tag)) {
        return false;
    }
    tag = tag.toLowerCase();
    /* istanbul ignore if */
    if (unknownElementCache[tag] != null) {
        return unknownElementCache[tag];
    }
    var el = document.createElement(tag);
    if (tag.indexOf('-') > -1) {
        // https://stackoverflow.com/a/28210364/1070244
        return (unknownElementCache[tag] =
            el.constructor === window.HTMLUnknownElement ||
                el.constructor === window.HTMLElement);
    }
    else {
        return (unknownElementCache[tag] = /HTMLUnknownElement/.test(el.toString()));
    }
}
var isTextInputType = makeMap('text,number,password,search,email,tel,url');

/**
 * Query an element selector if it's not an element already.
 */
function query(el) {
    if (typeof el === 'string') {
        var selected = document.querySelector(el);
        if (!selected) {
             false && 0;
            return document.createElement('div');
        }
        return selected;
    }
    else {
        return el;
    }
}

function createElement(tagName, vnode) {
    var elm = document.createElement(tagName);
    if (tagName !== 'select') {
        return elm;
    }
    // false or null will remove the attribute but undefined will not
    if (vnode.data &&
        vnode.data.attrs &&
        vnode.data.attrs.multiple !== undefined) {
        elm.setAttribute('multiple', 'multiple');
    }
    return elm;
}
function createElementNS(namespace, tagName) {
    return document.createElementNS(namespaceMap[namespace], tagName);
}
function createTextNode(text) {
    return document.createTextNode(text);
}
function createComment(text) {
    return document.createComment(text);
}
function insertBefore(parentNode, newNode, referenceNode) {
    parentNode.insertBefore(newNode, referenceNode);
}
function removeChild(node, child) {
    node.removeChild(child);
}
function appendChild(node, child) {
    node.appendChild(child);
}
function parentNode(node) {
    return node.parentNode;
}
function nextSibling(node) {
    return node.nextSibling;
}
function tagName(node) {
    return node.tagName;
}
function setTextContent(node, text) {
    node.textContent = text;
}
function setStyleScope(node, scopeId) {
    node.setAttribute(scopeId, '');
}

var nodeOps = /*#__PURE__*/Object.freeze({
  __proto__: null,
  createElement: createElement,
  createElementNS: createElementNS,
  createTextNode: createTextNode,
  createComment: createComment,
  insertBefore: insertBefore,
  removeChild: removeChild,
  appendChild: appendChild,
  parentNode: parentNode,
  nextSibling: nextSibling,
  tagName: tagName,
  setTextContent: setTextContent,
  setStyleScope: setStyleScope
});

var ref = {
    create: function (_, vnode) {
        registerRef(vnode);
    },
    update: function (oldVnode, vnode) {
        if (oldVnode.data.ref !== vnode.data.ref) {
            registerRef(oldVnode, true);
            registerRef(vnode);
        }
    },
    destroy: function (vnode) {
        registerRef(vnode, true);
    }
};
function registerRef(vnode, isRemoval) {
    var ref = vnode.data.ref;
    if (!isDef(ref))
        return;
    var vm = vnode.context;
    var refValue = vnode.componentInstance || vnode.elm;
    var value = isRemoval ? null : refValue;
    var $refsValue = isRemoval ? undefined : refValue;
    if (isFunction(ref)) {
        invokeWithErrorHandling(ref, vm, [value], vm, "template ref function");
        return;
    }
    var isFor = vnode.data.refInFor;
    var _isString = typeof ref === 'string' || typeof ref === 'number';
    var _isRef = isRef(ref);
    var refs = vm.$refs;
    if (_isString || _isRef) {
        if (isFor) {
            var existing = _isString ? refs[ref] : ref.value;
            if (isRemoval) {
                isArray(existing) && remove$2(existing, refValue);
            }
            else {
                if (!isArray(existing)) {
                    if (_isString) {
                        refs[ref] = [refValue];
                        setSetupRef(vm, ref, refs[ref]);
                    }
                    else {
                        ref.value = [refValue];
                    }
                }
                else if (!existing.includes(refValue)) {
                    existing.push(refValue);
                }
            }
        }
        else if (_isString) {
            if (isRemoval && refs[ref] !== refValue) {
                return;
            }
            refs[ref] = $refsValue;
            setSetupRef(vm, ref, value);
        }
        else if (_isRef) {
            if (isRemoval && ref.value !== refValue) {
                return;
            }
            ref.value = value;
        }
        else if (false) {}
    }
}
function setSetupRef(_a, key, val) {
    var _setupState = _a._setupState;
    if (_setupState && hasOwn(_setupState, key)) {
        if (isRef(_setupState[key])) {
            _setupState[key].value = val;
        }
        else {
            _setupState[key] = val;
        }
    }
}

/**
 * Virtual DOM patching algorithm based on Snabbdom by
 * Simon Friis Vindum (@paldepind)
 * Licensed under the MIT License
 * https://github.com/paldepind/snabbdom/blob/master/LICENSE
 *
 * modified by Evan You (@yyx990803)
 *
 * Not type-checking this because this file is perf-critical and the cost
 * of making flow understand it is not worth it.
 */
var emptyNode = new VNode('', {}, []);
var hooks = ['create', 'activate', 'update', 'remove', 'destroy'];
function sameVnode(a, b) {
    return (a.key === b.key &&
        a.asyncFactory === b.asyncFactory &&
        ((a.tag === b.tag &&
            a.isComment === b.isComment &&
            isDef(a.data) === isDef(b.data) &&
            sameInputType(a, b)) ||
            (isTrue(a.isAsyncPlaceholder) && isUndef(b.asyncFactory.error))));
}
function sameInputType(a, b) {
    if (a.tag !== 'input')
        return true;
    var i;
    var typeA = isDef((i = a.data)) && isDef((i = i.attrs)) && i.type;
    var typeB = isDef((i = b.data)) && isDef((i = i.attrs)) && i.type;
    return typeA === typeB || (isTextInputType(typeA) && isTextInputType(typeB));
}
function createKeyToOldIdx(children, beginIdx, endIdx) {
    var i, key;
    var map = {};
    for (i = beginIdx; i <= endIdx; ++i) {
        key = children[i].key;
        if (isDef(key))
            map[key] = i;
    }
    return map;
}
function createPatchFunction(backend) {
    var i, j;
    var cbs = {};
    var modules = backend.modules, nodeOps = backend.nodeOps;
    for (i = 0; i < hooks.length; ++i) {
        cbs[hooks[i]] = [];
        for (j = 0; j < modules.length; ++j) {
            if (isDef(modules[j][hooks[i]])) {
                cbs[hooks[i]].push(modules[j][hooks[i]]);
            }
        }
    }
    function emptyNodeAt(elm) {
        return new VNode(nodeOps.tagName(elm).toLowerCase(), {}, [], undefined, elm);
    }
    function createRmCb(childElm, listeners) {
        function remove() {
            if (--remove.listeners === 0) {
                removeNode(childElm);
            }
        }
        remove.listeners = listeners;
        return remove;
    }
    function removeNode(el) {
        var parent = nodeOps.parentNode(el);
        // element may have already been removed due to v-html / v-text
        if (isDef(parent)) {
            nodeOps.removeChild(parent, el);
        }
    }
    function isUnknownElement(vnode, inVPre) {
        return (!inVPre &&
            !vnode.ns &&
            !(config.ignoredElements.length &&
                config.ignoredElements.some(function (ignore) {
                    return isRegExp(ignore)
                        ? ignore.test(vnode.tag)
                        : ignore === vnode.tag;
                })) &&
            config.isUnknownElement(vnode.tag));
    }
    var creatingElmInVPre = 0;
    function createElm(vnode, insertedVnodeQueue, parentElm, refElm, nested, ownerArray, index) {
        if (isDef(vnode.elm) && isDef(ownerArray)) {
            // This vnode was used in a previous render!
            // now it's used as a new node, overwriting its elm would cause
            // potential patch errors down the road when it's used as an insertion
            // reference node. Instead, we clone the node on-demand before creating
            // associated DOM element for it.
            vnode = ownerArray[index] = cloneVNode(vnode);
        }
        vnode.isRootInsert = !nested; // for transition enter check
        if (createComponent(vnode, insertedVnodeQueue, parentElm, refElm)) {
            return;
        }
        var data = vnode.data;
        var children = vnode.children;
        var tag = vnode.tag;
        if (isDef(tag)) {
            if (false) {}
            vnode.elm = vnode.ns
                ? nodeOps.createElementNS(vnode.ns, tag)
                : nodeOps.createElement(tag, vnode);
            setScope(vnode);
            createChildren(vnode, children, insertedVnodeQueue);
            if (isDef(data)) {
                invokeCreateHooks(vnode, insertedVnodeQueue);
            }
            insert(parentElm, vnode.elm, refElm);
            if (false) {}
        }
        else if (isTrue(vnode.isComment)) {
            vnode.elm = nodeOps.createComment(vnode.text);
            insert(parentElm, vnode.elm, refElm);
        }
        else {
            vnode.elm = nodeOps.createTextNode(vnode.text);
            insert(parentElm, vnode.elm, refElm);
        }
    }
    function createComponent(vnode, insertedVnodeQueue, parentElm, refElm) {
        var i = vnode.data;
        if (isDef(i)) {
            var isReactivated = isDef(vnode.componentInstance) && i.keepAlive;
            if (isDef((i = i.hook)) && isDef((i = i.init))) {
                i(vnode, false /* hydrating */);
            }
            // after calling the init hook, if the vnode is a child component
            // it should've created a child instance and mounted it. the child
            // component also has set the placeholder vnode's elm.
            // in that case we can just return the element and be done.
            if (isDef(vnode.componentInstance)) {
                initComponent(vnode, insertedVnodeQueue);
                insert(parentElm, vnode.elm, refElm);
                if (isTrue(isReactivated)) {
                    reactivateComponent(vnode, insertedVnodeQueue, parentElm, refElm);
                }
                return true;
            }
        }
    }
    function initComponent(vnode, insertedVnodeQueue) {
        if (isDef(vnode.data.pendingInsert)) {
            insertedVnodeQueue.push.apply(insertedVnodeQueue, vnode.data.pendingInsert);
            vnode.data.pendingInsert = null;
        }
        vnode.elm = vnode.componentInstance.$el;
        if (isPatchable(vnode)) {
            invokeCreateHooks(vnode, insertedVnodeQueue);
            setScope(vnode);
        }
        else {
            // empty component root.
            // skip all element-related modules except for ref (#3455)
            registerRef(vnode);
            // make sure to invoke the insert hook
            insertedVnodeQueue.push(vnode);
        }
    }
    function reactivateComponent(vnode, insertedVnodeQueue, parentElm, refElm) {
        var i;
        // hack for #4339: a reactivated component with inner transition
        // does not trigger because the inner node's created hooks are not called
        // again. It's not ideal to involve module-specific logic in here but
        // there doesn't seem to be a better way to do it.
        var innerNode = vnode;
        while (innerNode.componentInstance) {
            innerNode = innerNode.componentInstance._vnode;
            if (isDef((i = innerNode.data)) && isDef((i = i.transition))) {
                for (i = 0; i < cbs.activate.length; ++i) {
                    cbs.activate[i](emptyNode, innerNode);
                }
                insertedVnodeQueue.push(innerNode);
                break;
            }
        }
        // unlike a newly created component,
        // a reactivated keep-alive component doesn't insert itself
        insert(parentElm, vnode.elm, refElm);
    }
    function insert(parent, elm, ref) {
        if (isDef(parent)) {
            if (isDef(ref)) {
                if (nodeOps.parentNode(ref) === parent) {
                    nodeOps.insertBefore(parent, elm, ref);
                }
            }
            else {
                nodeOps.appendChild(parent, elm);
            }
        }
    }
    function createChildren(vnode, children, insertedVnodeQueue) {
        if (isArray(children)) {
            if (false) {}
            for (var i_1 = 0; i_1 < children.length; ++i_1) {
                createElm(children[i_1], insertedVnodeQueue, vnode.elm, null, true, children, i_1);
            }
        }
        else if (isPrimitive(vnode.text)) {
            nodeOps.appendChild(vnode.elm, nodeOps.createTextNode(String(vnode.text)));
        }
    }
    function isPatchable(vnode) {
        while (vnode.componentInstance) {
            vnode = vnode.componentInstance._vnode;
        }
        return isDef(vnode.tag);
    }
    function invokeCreateHooks(vnode, insertedVnodeQueue) {
        for (var i_2 = 0; i_2 < cbs.create.length; ++i_2) {
            cbs.create[i_2](emptyNode, vnode);
        }
        i = vnode.data.hook; // Reuse variable
        if (isDef(i)) {
            if (isDef(i.create))
                i.create(emptyNode, vnode);
            if (isDef(i.insert))
                insertedVnodeQueue.push(vnode);
        }
    }
    // set scope id attribute for scoped CSS.
    // this is implemented as a special case to avoid the overhead
    // of going through the normal attribute patching process.
    function setScope(vnode) {
        var i;
        if (isDef((i = vnode.fnScopeId))) {
            nodeOps.setStyleScope(vnode.elm, i);
        }
        else {
            var ancestor = vnode;
            while (ancestor) {
                if (isDef((i = ancestor.context)) && isDef((i = i.$options._scopeId))) {
                    nodeOps.setStyleScope(vnode.elm, i);
                }
                ancestor = ancestor.parent;
            }
        }
        // for slot content they should also get the scopeId from the host instance.
        if (isDef((i = activeInstance)) &&
            i !== vnode.context &&
            i !== vnode.fnContext &&
            isDef((i = i.$options._scopeId))) {
            nodeOps.setStyleScope(vnode.elm, i);
        }
    }
    function addVnodes(parentElm, refElm, vnodes, startIdx, endIdx, insertedVnodeQueue) {
        for (; startIdx <= endIdx; ++startIdx) {
            createElm(vnodes[startIdx], insertedVnodeQueue, parentElm, refElm, false, vnodes, startIdx);
        }
    }
    function invokeDestroyHook(vnode) {
        var i, j;
        var data = vnode.data;
        if (isDef(data)) {
            if (isDef((i = data.hook)) && isDef((i = i.destroy)))
                i(vnode);
            for (i = 0; i < cbs.destroy.length; ++i)
                cbs.destroy[i](vnode);
        }
        if (isDef((i = vnode.children))) {
            for (j = 0; j < vnode.children.length; ++j) {
                invokeDestroyHook(vnode.children[j]);
            }
        }
    }
    function removeVnodes(vnodes, startIdx, endIdx) {
        for (; startIdx <= endIdx; ++startIdx) {
            var ch = vnodes[startIdx];
            if (isDef(ch)) {
                if (isDef(ch.tag)) {
                    removeAndInvokeRemoveHook(ch);
                    invokeDestroyHook(ch);
                }
                else {
                    // Text node
                    removeNode(ch.elm);
                }
            }
        }
    }
    function removeAndInvokeRemoveHook(vnode, rm) {
        if (isDef(rm) || isDef(vnode.data)) {
            var i_3;
            var listeners = cbs.remove.length + 1;
            if (isDef(rm)) {
                // we have a recursively passed down rm callback
                // increase the listeners count
                rm.listeners += listeners;
            }
            else {
                // directly removing
                rm = createRmCb(vnode.elm, listeners);
            }
            // recursively invoke hooks on child component root node
            if (isDef((i_3 = vnode.componentInstance)) &&
                isDef((i_3 = i_3._vnode)) &&
                isDef(i_3.data)) {
                removeAndInvokeRemoveHook(i_3, rm);
            }
            for (i_3 = 0; i_3 < cbs.remove.length; ++i_3) {
                cbs.remove[i_3](vnode, rm);
            }
            if (isDef((i_3 = vnode.data.hook)) && isDef((i_3 = i_3.remove))) {
                i_3(vnode, rm);
            }
            else {
                rm();
            }
        }
        else {
            removeNode(vnode.elm);
        }
    }
    function updateChildren(parentElm, oldCh, newCh, insertedVnodeQueue, removeOnly) {
        var oldStartIdx = 0;
        var newStartIdx = 0;
        var oldEndIdx = oldCh.length - 1;
        var oldStartVnode = oldCh[0];
        var oldEndVnode = oldCh[oldEndIdx];
        var newEndIdx = newCh.length - 1;
        var newStartVnode = newCh[0];
        var newEndVnode = newCh[newEndIdx];
        var oldKeyToIdx, idxInOld, vnodeToMove, refElm;
        // removeOnly is a special flag used only by <transition-group>
        // to ensure removed elements stay in correct relative positions
        // during leaving transitions
        var canMove = !removeOnly;
        if (false) {}
        while (oldStartIdx <= oldEndIdx && newStartIdx <= newEndIdx) {
            if (isUndef(oldStartVnode)) {
                oldStartVnode = oldCh[++oldStartIdx]; // Vnode has been moved left
            }
            else if (isUndef(oldEndVnode)) {
                oldEndVnode = oldCh[--oldEndIdx];
            }
            else if (sameVnode(oldStartVnode, newStartVnode)) {
                patchVnode(oldStartVnode, newStartVnode, insertedVnodeQueue, newCh, newStartIdx);
                oldStartVnode = oldCh[++oldStartIdx];
                newStartVnode = newCh[++newStartIdx];
            }
            else if (sameVnode(oldEndVnode, newEndVnode)) {
                patchVnode(oldEndVnode, newEndVnode, insertedVnodeQueue, newCh, newEndIdx);
                oldEndVnode = oldCh[--oldEndIdx];
                newEndVnode = newCh[--newEndIdx];
            }
            else if (sameVnode(oldStartVnode, newEndVnode)) {
                // Vnode moved right
                patchVnode(oldStartVnode, newEndVnode, insertedVnodeQueue, newCh, newEndIdx);
                canMove &&
                    nodeOps.insertBefore(parentElm, oldStartVnode.elm, nodeOps.nextSibling(oldEndVnode.elm));
                oldStartVnode = oldCh[++oldStartIdx];
                newEndVnode = newCh[--newEndIdx];
            }
            else if (sameVnode(oldEndVnode, newStartVnode)) {
                // Vnode moved left
                patchVnode(oldEndVnode, newStartVnode, insertedVnodeQueue, newCh, newStartIdx);
                canMove &&
                    nodeOps.insertBefore(parentElm, oldEndVnode.elm, oldStartVnode.elm);
                oldEndVnode = oldCh[--oldEndIdx];
                newStartVnode = newCh[++newStartIdx];
            }
            else {
                if (isUndef(oldKeyToIdx))
                    oldKeyToIdx = createKeyToOldIdx(oldCh, oldStartIdx, oldEndIdx);
                idxInOld = isDef(newStartVnode.key)
                    ? oldKeyToIdx[newStartVnode.key]
                    : findIdxInOld(newStartVnode, oldCh, oldStartIdx, oldEndIdx);
                if (isUndef(idxInOld)) {
                    // New element
                    createElm(newStartVnode, insertedVnodeQueue, parentElm, oldStartVnode.elm, false, newCh, newStartIdx);
                }
                else {
                    vnodeToMove = oldCh[idxInOld];
                    if (sameVnode(vnodeToMove, newStartVnode)) {
                        patchVnode(vnodeToMove, newStartVnode, insertedVnodeQueue, newCh, newStartIdx);
                        oldCh[idxInOld] = undefined;
                        canMove &&
                            nodeOps.insertBefore(parentElm, vnodeToMove.elm, oldStartVnode.elm);
                    }
                    else {
                        // same key but different element. treat as new element
                        createElm(newStartVnode, insertedVnodeQueue, parentElm, oldStartVnode.elm, false, newCh, newStartIdx);
                    }
                }
                newStartVnode = newCh[++newStartIdx];
            }
        }
        if (oldStartIdx > oldEndIdx) {
            refElm = isUndef(newCh[newEndIdx + 1]) ? null : newCh[newEndIdx + 1].elm;
            addVnodes(parentElm, refElm, newCh, newStartIdx, newEndIdx, insertedVnodeQueue);
        }
        else if (newStartIdx > newEndIdx) {
            removeVnodes(oldCh, oldStartIdx, oldEndIdx);
        }
    }
    function checkDuplicateKeys(children) {
        var seenKeys = {};
        for (var i_4 = 0; i_4 < children.length; i_4++) {
            var vnode = children[i_4];
            var key = vnode.key;
            if (isDef(key)) {
                if (seenKeys[key]) {
                    warn("Duplicate keys detected: '".concat(key, "'. This may cause an update error."), vnode.context);
                }
                else {
                    seenKeys[key] = true;
                }
            }
        }
    }
    function findIdxInOld(node, oldCh, start, end) {
        for (var i_5 = start; i_5 < end; i_5++) {
            var c = oldCh[i_5];
            if (isDef(c) && sameVnode(node, c))
                return i_5;
        }
    }
    function patchVnode(oldVnode, vnode, insertedVnodeQueue, ownerArray, index, removeOnly) {
        if (oldVnode === vnode) {
            return;
        }
        if (isDef(vnode.elm) && isDef(ownerArray)) {
            // clone reused vnode
            vnode = ownerArray[index] = cloneVNode(vnode);
        }
        var elm = (vnode.elm = oldVnode.elm);
        if (isTrue(oldVnode.isAsyncPlaceholder)) {
            if (isDef(vnode.asyncFactory.resolved)) {
                hydrate(oldVnode.elm, vnode, insertedVnodeQueue);
            }
            else {
                vnode.isAsyncPlaceholder = true;
            }
            return;
        }
        // reuse element for static trees.
        // note we only do this if the vnode is cloned -
        // if the new node is not cloned it means the render functions have been
        // reset by the hot-reload-api and we need to do a proper re-render.
        if (isTrue(vnode.isStatic) &&
            isTrue(oldVnode.isStatic) &&
            vnode.key === oldVnode.key &&
            (isTrue(vnode.isCloned) || isTrue(vnode.isOnce))) {
            vnode.componentInstance = oldVnode.componentInstance;
            return;
        }
        var i;
        var data = vnode.data;
        if (isDef(data) && isDef((i = data.hook)) && isDef((i = i.prepatch))) {
            i(oldVnode, vnode);
        }
        var oldCh = oldVnode.children;
        var ch = vnode.children;
        if (isDef(data) && isPatchable(vnode)) {
            for (i = 0; i < cbs.update.length; ++i)
                cbs.update[i](oldVnode, vnode);
            if (isDef((i = data.hook)) && isDef((i = i.update)))
                i(oldVnode, vnode);
        }
        if (isUndef(vnode.text)) {
            if (isDef(oldCh) && isDef(ch)) {
                if (oldCh !== ch)
                    updateChildren(elm, oldCh, ch, insertedVnodeQueue, removeOnly);
            }
            else if (isDef(ch)) {
                if (false) {}
                if (isDef(oldVnode.text))
                    nodeOps.setTextContent(elm, '');
                addVnodes(elm, null, ch, 0, ch.length - 1, insertedVnodeQueue);
            }
            else if (isDef(oldCh)) {
                removeVnodes(oldCh, 0, oldCh.length - 1);
            }
            else if (isDef(oldVnode.text)) {
                nodeOps.setTextContent(elm, '');
            }
        }
        else if (oldVnode.text !== vnode.text) {
            nodeOps.setTextContent(elm, vnode.text);
        }
        if (isDef(data)) {
            if (isDef((i = data.hook)) && isDef((i = i.postpatch)))
                i(oldVnode, vnode);
        }
    }
    function invokeInsertHook(vnode, queue, initial) {
        // delay insert hooks for component root nodes, invoke them after the
        // element is really inserted
        if (isTrue(initial) && isDef(vnode.parent)) {
            vnode.parent.data.pendingInsert = queue;
        }
        else {
            for (var i_6 = 0; i_6 < queue.length; ++i_6) {
                queue[i_6].data.hook.insert(queue[i_6]);
            }
        }
    }
    var hydrationBailed = false;
    // list of modules that can skip create hook during hydration because they
    // are already rendered on the client or has no need for initialization
    // Note: style is excluded because it relies on initial clone for future
    // deep updates (#7063).
    var isRenderedModule = makeMap('attrs,class,staticClass,staticStyle,key');
    // Note: this is a browser-only function so we can assume elms are DOM nodes.
    function hydrate(elm, vnode, insertedVnodeQueue, inVPre) {
        var i;
        var tag = vnode.tag, data = vnode.data, children = vnode.children;
        inVPre = inVPre || (data && data.pre);
        vnode.elm = elm;
        if (isTrue(vnode.isComment) && isDef(vnode.asyncFactory)) {
            vnode.isAsyncPlaceholder = true;
            return true;
        }
        // assert node match
        if (false) {}
        if (isDef(data)) {
            if (isDef((i = data.hook)) && isDef((i = i.init)))
                i(vnode, true /* hydrating */);
            if (isDef((i = vnode.componentInstance))) {
                // child component. it should have hydrated its own tree.
                initComponent(vnode, insertedVnodeQueue);
                return true;
            }
        }
        if (isDef(tag)) {
            if (isDef(children)) {
                // empty element, allow client to pick up and populate children
                if (!elm.hasChildNodes()) {
                    createChildren(vnode, children, insertedVnodeQueue);
                }
                else {
                    // v-html and domProps: innerHTML
                    if (isDef((i = data)) &&
                        isDef((i = i.domProps)) &&
                        isDef((i = i.innerHTML))) {
                        if (i !== elm.innerHTML) {
                            /* istanbul ignore if */
                            if (false) {}
                            return false;
                        }
                    }
                    else {
                        // iterate and compare children lists
                        var childrenMatch = true;
                        var childNode = elm.firstChild;
                        for (var i_7 = 0; i_7 < children.length; i_7++) {
                            if (!childNode ||
                                !hydrate(childNode, children[i_7], insertedVnodeQueue, inVPre)) {
                                childrenMatch = false;
                                break;
                            }
                            childNode = childNode.nextSibling;
                        }
                        // if childNode is not null, it means the actual childNodes list is
                        // longer than the virtual children list.
                        if (!childrenMatch || childNode) {
                            /* istanbul ignore if */
                            if (false) {}
                            return false;
                        }
                    }
                }
            }
            if (isDef(data)) {
                var fullInvoke = false;
                for (var key in data) {
                    if (!isRenderedModule(key)) {
                        fullInvoke = true;
                        invokeCreateHooks(vnode, insertedVnodeQueue);
                        break;
                    }
                }
                if (!fullInvoke && data['class']) {
                    // ensure collecting deps for deep class bindings for future updates
                    traverse(data['class']);
                }
            }
        }
        else if (elm.data !== vnode.text) {
            elm.data = vnode.text;
        }
        return true;
    }
    function assertNodeMatch(node, vnode, inVPre) {
        if (isDef(vnode.tag)) {
            return (vnode.tag.indexOf('vue-component') === 0 ||
                (!isUnknownElement(vnode, inVPre) &&
                    vnode.tag.toLowerCase() ===
                        (node.tagName && node.tagName.toLowerCase())));
        }
        else {
            return node.nodeType === (vnode.isComment ? 8 : 3);
        }
    }
    return function patch(oldVnode, vnode, hydrating, removeOnly) {
        if (isUndef(vnode)) {
            if (isDef(oldVnode))
                invokeDestroyHook(oldVnode);
            return;
        }
        var isInitialPatch = false;
        var insertedVnodeQueue = [];
        if (isUndef(oldVnode)) {
            // empty mount (likely as component), create new root element
            isInitialPatch = true;
            createElm(vnode, insertedVnodeQueue);
        }
        else {
            var isRealElement = isDef(oldVnode.nodeType);
            if (!isRealElement && sameVnode(oldVnode, vnode)) {
                // patch existing root node
                patchVnode(oldVnode, vnode, insertedVnodeQueue, null, null, removeOnly);
            }
            else {
                if (isRealElement) {
                    // mounting to a real element
                    // check if this is server-rendered content and if we can perform
                    // a successful hydration.
                    if (oldVnode.nodeType === 1 && oldVnode.hasAttribute(SSR_ATTR)) {
                        oldVnode.removeAttribute(SSR_ATTR);
                        hydrating = true;
                    }
                    if (isTrue(hydrating)) {
                        if (hydrate(oldVnode, vnode, insertedVnodeQueue)) {
                            invokeInsertHook(vnode, insertedVnodeQueue, true);
                            return oldVnode;
                        }
                        else if (false) {}
                    }
                    // either not server-rendered, or hydration failed.
                    // create an empty node and replace it
                    oldVnode = emptyNodeAt(oldVnode);
                }
                // replacing existing element
                var oldElm = oldVnode.elm;
                var parentElm = nodeOps.parentNode(oldElm);
                // create new node
                createElm(vnode, insertedVnodeQueue, 
                // extremely rare edge case: do not insert if old element is in a
                // leaving transition. Only happens when combining transition +
                // keep-alive + HOCs. (#4590)
                oldElm._leaveCb ? null : parentElm, nodeOps.nextSibling(oldElm));
                // update parent placeholder node element, recursively
                if (isDef(vnode.parent)) {
                    var ancestor = vnode.parent;
                    var patchable = isPatchable(vnode);
                    while (ancestor) {
                        for (var i_8 = 0; i_8 < cbs.destroy.length; ++i_8) {
                            cbs.destroy[i_8](ancestor);
                        }
                        ancestor.elm = vnode.elm;
                        if (patchable) {
                            for (var i_9 = 0; i_9 < cbs.create.length; ++i_9) {
                                cbs.create[i_9](emptyNode, ancestor);
                            }
                            // #6513
                            // invoke insert hooks that may have been merged by create hooks.
                            // e.g. for directives that uses the "inserted" hook.
                            var insert_1 = ancestor.data.hook.insert;
                            if (insert_1.merged) {
                                // start at index 1 to avoid re-invoking component mounted hook
                                // clone insert hooks to avoid being mutated during iteration.
                                // e.g. for customed directives under transition group.
                                var cloned = insert_1.fns.slice(1);
                                for (var i_10 = 0; i_10 < cloned.length; i_10++) {
                                    cloned[i_10]();
                                }
                            }
                        }
                        else {
                            registerRef(ancestor);
                        }
                        ancestor = ancestor.parent;
                    }
                }
                // destroy old node
                if (isDef(parentElm)) {
                    removeVnodes([oldVnode], 0, 0);
                }
                else if (isDef(oldVnode.tag)) {
                    invokeDestroyHook(oldVnode);
                }
            }
        }
        invokeInsertHook(vnode, insertedVnodeQueue, isInitialPatch);
        return vnode.elm;
    };
}

var directives = {
    create: updateDirectives,
    update: updateDirectives,
    destroy: function unbindDirectives(vnode) {
        // @ts-expect-error emptyNode is not VNodeWithData
        updateDirectives(vnode, emptyNode);
    }
};
function updateDirectives(oldVnode, vnode) {
    if (oldVnode.data.directives || vnode.data.directives) {
        _update(oldVnode, vnode);
    }
}
function _update(oldVnode, vnode) {
    var isCreate = oldVnode === emptyNode;
    var isDestroy = vnode === emptyNode;
    var oldDirs = normalizeDirectives(oldVnode.data.directives, oldVnode.context);
    var newDirs = normalizeDirectives(vnode.data.directives, vnode.context);
    var dirsWithInsert = [];
    var dirsWithPostpatch = [];
    var key, oldDir, dir;
    for (key in newDirs) {
        oldDir = oldDirs[key];
        dir = newDirs[key];
        if (!oldDir) {
            // new directive, bind
            callHook(dir, 'bind', vnode, oldVnode);
            if (dir.def && dir.def.inserted) {
                dirsWithInsert.push(dir);
            }
        }
        else {
            // existing directive, update
            dir.oldValue = oldDir.value;
            dir.oldArg = oldDir.arg;
            callHook(dir, 'update', vnode, oldVnode);
            if (dir.def && dir.def.componentUpdated) {
                dirsWithPostpatch.push(dir);
            }
        }
    }
    if (dirsWithInsert.length) {
        var callInsert = function () {
            for (var i = 0; i < dirsWithInsert.length; i++) {
                callHook(dirsWithInsert[i], 'inserted', vnode, oldVnode);
            }
        };
        if (isCreate) {
            mergeVNodeHook(vnode, 'insert', callInsert);
        }
        else {
            callInsert();
        }
    }
    if (dirsWithPostpatch.length) {
        mergeVNodeHook(vnode, 'postpatch', function () {
            for (var i = 0; i < dirsWithPostpatch.length; i++) {
                callHook(dirsWithPostpatch[i], 'componentUpdated', vnode, oldVnode);
            }
        });
    }
    if (!isCreate) {
        for (key in oldDirs) {
            if (!newDirs[key]) {
                // no longer present, unbind
                callHook(oldDirs[key], 'unbind', oldVnode, oldVnode, isDestroy);
            }
        }
    }
}
var emptyModifiers = Object.create(null);
function normalizeDirectives(dirs, vm) {
    var res = Object.create(null);
    if (!dirs) {
        // $flow-disable-line
        return res;
    }
    var i, dir;
    for (i = 0; i < dirs.length; i++) {
        dir = dirs[i];
        if (!dir.modifiers) {
            // $flow-disable-line
            dir.modifiers = emptyModifiers;
        }
        res[getRawDirName(dir)] = dir;
        if (vm._setupState && vm._setupState.__sfc) {
            var setupDef = dir.def || resolveAsset(vm, '_setupState', 'v-' + dir.name);
            if (typeof setupDef === 'function') {
                dir.def = {
                    bind: setupDef,
                    update: setupDef,
                };
            }
            else {
                dir.def = setupDef;
            }
        }
        dir.def = dir.def || resolveAsset(vm.$options, 'directives', dir.name, true);
    }
    // $flow-disable-line
    return res;
}
function getRawDirName(dir) {
    return (dir.rawName || "".concat(dir.name, ".").concat(Object.keys(dir.modifiers || {}).join('.')));
}
function callHook(dir, hook, vnode, oldVnode, isDestroy) {
    var fn = dir.def && dir.def[hook];
    if (fn) {
        try {
            fn(vnode.elm, dir, vnode, oldVnode, isDestroy);
        }
        catch (e) {
            handleError(e, vnode.context, "directive ".concat(dir.name, " ").concat(hook, " hook"));
        }
    }
}

var baseModules = [ref, directives];

function updateAttrs(oldVnode, vnode) {
    var opts = vnode.componentOptions;
    if (isDef(opts) && opts.Ctor.options.inheritAttrs === false) {
        return;
    }
    if (isUndef(oldVnode.data.attrs) && isUndef(vnode.data.attrs)) {
        return;
    }
    var key, cur, old;
    var elm = vnode.elm;
    var oldAttrs = oldVnode.data.attrs || {};
    var attrs = vnode.data.attrs || {};
    // clone observed objects, as the user probably wants to mutate it
    if (isDef(attrs.__ob__) || isTrue(attrs._v_attr_proxy)) {
        attrs = vnode.data.attrs = extend({}, attrs);
    }
    for (key in attrs) {
        cur = attrs[key];
        old = oldAttrs[key];
        if (old !== cur) {
            setAttr(elm, key, cur, vnode.data.pre);
        }
    }
    // #4391: in IE9, setting type can reset value for input[type=radio]
    // #6666: IE/Edge forces progress value down to 1 before setting a max
    /* istanbul ignore if */
    if ((isIE || isEdge) && attrs.value !== oldAttrs.value) {
        setAttr(elm, 'value', attrs.value);
    }
    for (key in oldAttrs) {
        if (isUndef(attrs[key])) {
            if (isXlink(key)) {
                elm.removeAttributeNS(xlinkNS, getXlinkProp(key));
            }
            else if (!isEnumeratedAttr(key)) {
                elm.removeAttribute(key);
            }
        }
    }
}
function setAttr(el, key, value, isInPre) {
    if (isInPre || el.tagName.indexOf('-') > -1) {
        baseSetAttr(el, key, value);
    }
    else if (isBooleanAttr(key)) {
        // set attribute for blank value
        // e.g. <option disabled>Select one</option>
        if (isFalsyAttrValue(value)) {
            el.removeAttribute(key);
        }
        else {
            // technically allowfullscreen is a boolean attribute for <iframe>,
            // but Flash expects a value of "true" when used on <embed> tag
            value = key === 'allowfullscreen' && el.tagName === 'EMBED' ? 'true' : key;
            el.setAttribute(key, value);
        }
    }
    else if (isEnumeratedAttr(key)) {
        el.setAttribute(key, convertEnumeratedValue(key, value));
    }
    else if (isXlink(key)) {
        if (isFalsyAttrValue(value)) {
            el.removeAttributeNS(xlinkNS, getXlinkProp(key));
        }
        else {
            el.setAttributeNS(xlinkNS, key, value);
        }
    }
    else {
        baseSetAttr(el, key, value);
    }
}
function baseSetAttr(el, key, value) {
    if (isFalsyAttrValue(value)) {
        el.removeAttribute(key);
    }
    else {
        // #7138: IE10 & 11 fires input event when setting placeholder on
        // <textarea>... block the first input event and remove the blocker
        // immediately.
        /* istanbul ignore if */
        if (isIE &&
            !isIE9 &&
            el.tagName === 'TEXTAREA' &&
            key === 'placeholder' &&
            value !== '' &&
            !el.__ieph) {
            var blocker_1 = function (e) {
                e.stopImmediatePropagation();
                el.removeEventListener('input', blocker_1);
            };
            el.addEventListener('input', blocker_1);
            // $flow-disable-line
            el.__ieph = true; /* IE placeholder patched */
        }
        el.setAttribute(key, value);
    }
}
var attrs = {
    create: updateAttrs,
    update: updateAttrs
};

function updateClass(oldVnode, vnode) {
    var el = vnode.elm;
    var data = vnode.data;
    var oldData = oldVnode.data;
    if (isUndef(data.staticClass) &&
        isUndef(data.class) &&
        (isUndef(oldData) ||
            (isUndef(oldData.staticClass) && isUndef(oldData.class)))) {
        return;
    }
    var cls = genClassForVnode(vnode);
    // handle transition classes
    var transitionClass = el._transitionClasses;
    if (isDef(transitionClass)) {
        cls = concat(cls, stringifyClass(transitionClass));
    }
    // set the class
    if (cls !== el._prevClass) {
        el.setAttribute('class', cls);
        el._prevClass = cls;
    }
}
var klass = {
    create: updateClass,
    update: updateClass
};

// in some cases, the event used has to be determined at runtime
// so we used some reserved tokens during compile.
var RANGE_TOKEN = '__r';
var CHECKBOX_RADIO_TOKEN = '__c';

// normalize v-model event tokens that can only be determined at runtime.
// it's important to place the event as the first in the array because
// the whole point is ensuring the v-model callback gets called before
// user-attached handlers.
function normalizeEvents(on) {
    /* istanbul ignore if */
    if (isDef(on[RANGE_TOKEN])) {
        // IE input[type=range] only supports `change` event
        var event_1 = isIE ? 'change' : 'input';
        on[event_1] = [].concat(on[RANGE_TOKEN], on[event_1] || []);
        delete on[RANGE_TOKEN];
    }
    // This was originally intended to fix #4521 but no longer necessary
    // after 2.5. Keeping it for backwards compat with generated code from < 2.4
    /* istanbul ignore if */
    if (isDef(on[CHECKBOX_RADIO_TOKEN])) {
        on.change = [].concat(on[CHECKBOX_RADIO_TOKEN], on.change || []);
        delete on[CHECKBOX_RADIO_TOKEN];
    }
}
var target;
function createOnceHandler(event, handler, capture) {
    var _target = target; // save current target element in closure
    return function onceHandler() {
        var res = handler.apply(null, arguments);
        if (res !== null) {
            remove(event, onceHandler, capture, _target);
        }
    };
}
// #9446: Firefox <= 53 (in particular, ESR 52) has incorrect Event.timeStamp
// implementation and does not fire microtasks in between event propagation, so
// safe to exclude.
var useMicrotaskFix = isUsingMicroTask && !(isFF && Number(isFF[1]) <= 53);
function add(name, handler, capture, passive) {
    // async edge case #6566: inner click event triggers patch, event handler
    // attached to outer element during patch, and triggered again. This
    // happens because browsers fire microtask ticks between event propagation.
    // the solution is simple: we save the timestamp when a handler is attached,
    // and the handler would only fire if the event passed to it was fired
    // AFTER it was attached.
    if (useMicrotaskFix) {
        var attachedTimestamp_1 = currentFlushTimestamp;
        var original_1 = handler;
        //@ts-expect-error
        handler = original_1._wrapper = function (e) {
            if (
            // no bubbling, should always fire.
            // this is just a safety net in case event.timeStamp is unreliable in
            // certain weird environments...
            e.target === e.currentTarget ||
                // event is fired after handler attachment
                e.timeStamp >= attachedTimestamp_1 ||
                // bail for environments that have buggy event.timeStamp implementations
                // #9462 iOS 9 bug: event.timeStamp is 0 after history.pushState
                // #9681 QtWebEngine event.timeStamp is negative value
                e.timeStamp <= 0 ||
                // #9448 bail if event is fired in another document in a multi-page
                // electron/nw.js app, since event.timeStamp will be using a different
                // starting reference
                e.target.ownerDocument !== document) {
                return original_1.apply(this, arguments);
            }
        };
    }
    target.addEventListener(name, handler, supportsPassive ? { capture: capture, passive: passive } : capture);
}
function remove(name, handler, capture, _target) {
    (_target || target).removeEventListener(name, 
    //@ts-expect-error
    handler._wrapper || handler, capture);
}
function updateDOMListeners(oldVnode, vnode) {
    if (isUndef(oldVnode.data.on) && isUndef(vnode.data.on)) {
        return;
    }
    var on = vnode.data.on || {};
    var oldOn = oldVnode.data.on || {};
    // vnode is empty when removing all listeners,
    // and use old vnode dom element
    target = vnode.elm || oldVnode.elm;
    normalizeEvents(on);
    updateListeners(on, oldOn, add, remove, createOnceHandler, vnode.context);
    target = undefined;
}
var events = {
    create: updateDOMListeners,
    update: updateDOMListeners,
    // @ts-expect-error emptyNode has actually data
    destroy: function (vnode) { return updateDOMListeners(vnode, emptyNode); }
};

var svgContainer;
function updateDOMProps(oldVnode, vnode) {
    if (isUndef(oldVnode.data.domProps) && isUndef(vnode.data.domProps)) {
        return;
    }
    var key, cur;
    var elm = vnode.elm;
    var oldProps = oldVnode.data.domProps || {};
    var props = vnode.data.domProps || {};
    // clone observed objects, as the user probably wants to mutate it
    if (isDef(props.__ob__) || isTrue(props._v_attr_proxy)) {
        props = vnode.data.domProps = extend({}, props);
    }
    for (key in oldProps) {
        if (!(key in props)) {
            elm[key] = '';
        }
    }
    for (key in props) {
        cur = props[key];
        // ignore children if the node has textContent or innerHTML,
        // as these will throw away existing DOM nodes and cause removal errors
        // on subsequent patches (#3360)
        if (key === 'textContent' || key === 'innerHTML') {
            if (vnode.children)
                vnode.children.length = 0;
            if (cur === oldProps[key])
                continue;
            // #6601 work around Chrome version <= 55 bug where single textNode
            // replaced by innerHTML/textContent retains its parentNode property
            if (elm.childNodes.length === 1) {
                elm.removeChild(elm.childNodes[0]);
            }
        }
        if (key === 'value' && elm.tagName !== 'PROGRESS') {
            // store value as _value as well since
            // non-string values will be stringified
            elm._value = cur;
            // avoid resetting cursor position when value is the same
            var strCur = isUndef(cur) ? '' : String(cur);
            if (shouldUpdateValue(elm, strCur)) {
                elm.value = strCur;
            }
        }
        else if (key === 'innerHTML' &&
            isSVG(elm.tagName) &&
            isUndef(elm.innerHTML)) {
            // IE doesn't support innerHTML for SVG elements
            svgContainer = svgContainer || document.createElement('div');
            svgContainer.innerHTML = "<svg>".concat(cur, "</svg>");
            var svg = svgContainer.firstChild;
            while (elm.firstChild) {
                elm.removeChild(elm.firstChild);
            }
            while (svg.firstChild) {
                elm.appendChild(svg.firstChild);
            }
        }
        else if (
        // skip the update if old and new VDOM state is the same.
        // `value` is handled separately because the DOM value may be temporarily
        // out of sync with VDOM state due to focus, composition and modifiers.
        // This  #4521 by skipping the unnecessary `checked` update.
        cur !== oldProps[key]) {
            // some property updates can throw
            // e.g. `value` on <progress> w/ non-finite value
            try {
                elm[key] = cur;
            }
            catch (e) { }
        }
    }
}
function shouldUpdateValue(elm, checkVal) {
    return (
    //@ts-expect-error
    !elm.composing &&
        (elm.tagName === 'OPTION' ||
            isNotInFocusAndDirty(elm, checkVal) ||
            isDirtyWithModifiers(elm, checkVal)));
}
function isNotInFocusAndDirty(elm, checkVal) {
    // return true when textbox (.number and .trim) loses focus and its value is
    // not equal to the updated value
    var notInFocus = true;
    // #6157
    // work around IE bug when accessing document.activeElement in an iframe
    try {
        notInFocus = document.activeElement !== elm;
    }
    catch (e) { }
    return notInFocus && elm.value !== checkVal;
}
function isDirtyWithModifiers(elm, newVal) {
    var value = elm.value;
    var modifiers = elm._vModifiers; // injected by v-model runtime
    if (isDef(modifiers)) {
        if (modifiers.number) {
            return toNumber(value) !== toNumber(newVal);
        }
        if (modifiers.trim) {
            return value.trim() !== newVal.trim();
        }
    }
    return value !== newVal;
}
var domProps = {
    create: updateDOMProps,
    update: updateDOMProps
};

var parseStyleText = cached(function (cssText) {
    var res = {};
    var listDelimiter = /;(?![^(]*\))/g;
    var propertyDelimiter = /:(.+)/;
    cssText.split(listDelimiter).forEach(function (item) {
        if (item) {
            var tmp = item.split(propertyDelimiter);
            tmp.length > 1 && (res[tmp[0].trim()] = tmp[1].trim());
        }
    });
    return res;
});
// merge static and dynamic style data on the same vnode
function normalizeStyleData(data) {
    var style = normalizeStyleBinding(data.style);
    // static style is pre-processed into an object during compilation
    // and is always a fresh object, so it's safe to merge into it
    return data.staticStyle ? extend(data.staticStyle, style) : style;
}
// normalize possible array / string values into Object
function normalizeStyleBinding(bindingStyle) {
    if (Array.isArray(bindingStyle)) {
        return toObject(bindingStyle);
    }
    if (typeof bindingStyle === 'string') {
        return parseStyleText(bindingStyle);
    }
    return bindingStyle;
}
/**
 * parent component style should be after child's
 * so that parent component's style could override it
 */
function getStyle(vnode, checkChild) {
    var res = {};
    var styleData;
    if (checkChild) {
        var childNode = vnode;
        while (childNode.componentInstance) {
            childNode = childNode.componentInstance._vnode;
            if (childNode &&
                childNode.data &&
                (styleData = normalizeStyleData(childNode.data))) {
                extend(res, styleData);
            }
        }
    }
    if ((styleData = normalizeStyleData(vnode.data))) {
        extend(res, styleData);
    }
    var parentNode = vnode;
    // @ts-expect-error parentNode.parent not VNodeWithData
    while ((parentNode = parentNode.parent)) {
        if (parentNode.data && (styleData = normalizeStyleData(parentNode.data))) {
            extend(res, styleData);
        }
    }
    return res;
}

var cssVarRE = /^--/;
var importantRE = /\s*!important$/;
var setProp = function (el, name, val) {
    /* istanbul ignore if */
    if (cssVarRE.test(name)) {
        el.style.setProperty(name, val);
    }
    else if (importantRE.test(val)) {
        el.style.setProperty(hyphenate(name), val.replace(importantRE, ''), 'important');
    }
    else {
        var normalizedName = normalize(name);
        if (Array.isArray(val)) {
            // Support values array created by autoprefixer, e.g.
            // {display: ["-webkit-box", "-ms-flexbox", "flex"]}
            // Set them one by one, and the browser will only set those it can recognize
            for (var i = 0, len = val.length; i < len; i++) {
                el.style[normalizedName] = val[i];
            }
        }
        else {
            el.style[normalizedName] = val;
        }
    }
};
var vendorNames = ['Webkit', 'Moz', 'ms'];
var emptyStyle;
var normalize = cached(function (prop) {
    emptyStyle = emptyStyle || document.createElement('div').style;
    prop = camelize(prop);
    if (prop !== 'filter' && prop in emptyStyle) {
        return prop;
    }
    var capName = prop.charAt(0).toUpperCase() + prop.slice(1);
    for (var i = 0; i < vendorNames.length; i++) {
        var name_1 = vendorNames[i] + capName;
        if (name_1 in emptyStyle) {
            return name_1;
        }
    }
});
function updateStyle(oldVnode, vnode) {
    var data = vnode.data;
    var oldData = oldVnode.data;
    if (isUndef(data.staticStyle) &&
        isUndef(data.style) &&
        isUndef(oldData.staticStyle) &&
        isUndef(oldData.style)) {
        return;
    }
    var cur, name;
    var el = vnode.elm;
    var oldStaticStyle = oldData.staticStyle;
    var oldStyleBinding = oldData.normalizedStyle || oldData.style || {};
    // if static style exists, stylebinding already merged into it when doing normalizeStyleData
    var oldStyle = oldStaticStyle || oldStyleBinding;
    var style = normalizeStyleBinding(vnode.data.style) || {};
    // store normalized style under a different key for next diff
    // make sure to clone it if it's reactive, since the user likely wants
    // to mutate it.
    vnode.data.normalizedStyle = isDef(style.__ob__) ? extend({}, style) : style;
    var newStyle = getStyle(vnode, true);
    for (name in oldStyle) {
        if (isUndef(newStyle[name])) {
            setProp(el, name, '');
        }
    }
    for (name in newStyle) {
        cur = newStyle[name];
        if (cur !== oldStyle[name]) {
            // ie9 setting to null has no effect, must use empty string
            setProp(el, name, cur == null ? '' : cur);
        }
    }
}
var style = {
    create: updateStyle,
    update: updateStyle
};

var whitespaceRE = /\s+/;
/**
 * Add class with compatibility for SVG since classList is not supported on
 * SVG elements in IE
 */
function addClass(el, cls) {
    /* istanbul ignore if */
    if (!cls || !(cls = cls.trim())) {
        return;
    }
    /* istanbul ignore else */
    if (el.classList) {
        if (cls.indexOf(' ') > -1) {
            cls.split(whitespaceRE).forEach(function (c) { return el.classList.add(c); });
        }
        else {
            el.classList.add(cls);
        }
    }
    else {
        var cur = " ".concat(el.getAttribute('class') || '', " ");
        if (cur.indexOf(' ' + cls + ' ') < 0) {
            el.setAttribute('class', (cur + cls).trim());
        }
    }
}
/**
 * Remove class with compatibility for SVG since classList is not supported on
 * SVG elements in IE
 */
function removeClass(el, cls) {
    /* istanbul ignore if */
    if (!cls || !(cls = cls.trim())) {
        return;
    }
    /* istanbul ignore else */
    if (el.classList) {
        if (cls.indexOf(' ') > -1) {
            cls.split(whitespaceRE).forEach(function (c) { return el.classList.remove(c); });
        }
        else {
            el.classList.remove(cls);
        }
        if (!el.classList.length) {
            el.removeAttribute('class');
        }
    }
    else {
        var cur = " ".concat(el.getAttribute('class') || '', " ");
        var tar = ' ' + cls + ' ';
        while (cur.indexOf(tar) >= 0) {
            cur = cur.replace(tar, ' ');
        }
        cur = cur.trim();
        if (cur) {
            el.setAttribute('class', cur);
        }
        else {
            el.removeAttribute('class');
        }
    }
}

function resolveTransition(def) {
    if (!def) {
        return;
    }
    /* istanbul ignore else */
    if (typeof def === 'object') {
        var res = {};
        if (def.css !== false) {
            extend(res, autoCssTransition(def.name || 'v'));
        }
        extend(res, def);
        return res;
    }
    else if (typeof def === 'string') {
        return autoCssTransition(def);
    }
}
var autoCssTransition = cached(function (name) {
    return {
        enterClass: "".concat(name, "-enter"),
        enterToClass: "".concat(name, "-enter-to"),
        enterActiveClass: "".concat(name, "-enter-active"),
        leaveClass: "".concat(name, "-leave"),
        leaveToClass: "".concat(name, "-leave-to"),
        leaveActiveClass: "".concat(name, "-leave-active")
    };
});
var hasTransition = inBrowser && !isIE9;
var TRANSITION = 'transition';
var ANIMATION = 'animation';
// Transition property/event sniffing
var transitionProp = 'transition';
var transitionEndEvent = 'transitionend';
var animationProp = 'animation';
var animationEndEvent = 'animationend';
if (hasTransition) {
    /* istanbul ignore if */
    if (window.ontransitionend === undefined &&
        window.onwebkittransitionend !== undefined) {
        transitionProp = 'WebkitTransition';
        transitionEndEvent = 'webkitTransitionEnd';
    }
    if (window.onanimationend === undefined &&
        window.onwebkitanimationend !== undefined) {
        animationProp = 'WebkitAnimation';
        animationEndEvent = 'webkitAnimationEnd';
    }
}
// binding to window is necessary to make hot reload work in IE in strict mode
var raf = inBrowser
    ? window.requestAnimationFrame
        ? window.requestAnimationFrame.bind(window)
        : setTimeout
    : /* istanbul ignore next */ function (/* istanbul ignore next */ fn) { return fn(); };
function nextFrame(fn) {
    raf(function () {
        // @ts-expect-error
        raf(fn);
    });
}
function addTransitionClass(el, cls) {
    var transitionClasses = el._transitionClasses || (el._transitionClasses = []);
    if (transitionClasses.indexOf(cls) < 0) {
        transitionClasses.push(cls);
        addClass(el, cls);
    }
}
function removeTransitionClass(el, cls) {
    if (el._transitionClasses) {
        remove$2(el._transitionClasses, cls);
    }
    removeClass(el, cls);
}
function whenTransitionEnds(el, expectedType, cb) {
    var _a = getTransitionInfo(el, expectedType), type = _a.type, timeout = _a.timeout, propCount = _a.propCount;
    if (!type)
        return cb();
    var event = type === TRANSITION ? transitionEndEvent : animationEndEvent;
    var ended = 0;
    var end = function () {
        el.removeEventListener(event, onEnd);
        cb();
    };
    var onEnd = function (e) {
        if (e.target === el) {
            if (++ended >= propCount) {
                end();
            }
        }
    };
    setTimeout(function () {
        if (ended < propCount) {
            end();
        }
    }, timeout + 1);
    el.addEventListener(event, onEnd);
}
var transformRE = /\b(transform|all)(,|$)/;
function getTransitionInfo(el, expectedType) {
    var styles = window.getComputedStyle(el);
    // JSDOM may return undefined for transition properties
    var transitionDelays = (styles[transitionProp + 'Delay'] || '').split(', ');
    var transitionDurations = (styles[transitionProp + 'Duration'] || '').split(', ');
    var transitionTimeout = getTimeout(transitionDelays, transitionDurations);
    var animationDelays = (styles[animationProp + 'Delay'] || '').split(', ');
    var animationDurations = (styles[animationProp + 'Duration'] || '').split(', ');
    var animationTimeout = getTimeout(animationDelays, animationDurations);
    var type;
    var timeout = 0;
    var propCount = 0;
    /* istanbul ignore if */
    if (expectedType === TRANSITION) {
        if (transitionTimeout > 0) {
            type = TRANSITION;
            timeout = transitionTimeout;
            propCount = transitionDurations.length;
        }
    }
    else if (expectedType === ANIMATION) {
        if (animationTimeout > 0) {
            type = ANIMATION;
            timeout = animationTimeout;
            propCount = animationDurations.length;
        }
    }
    else {
        timeout = Math.max(transitionTimeout, animationTimeout);
        type =
            timeout > 0
                ? transitionTimeout > animationTimeout
                    ? TRANSITION
                    : ANIMATION
                : null;
        propCount = type
            ? type === TRANSITION
                ? transitionDurations.length
                : animationDurations.length
            : 0;
    }
    var hasTransform = type === TRANSITION && transformRE.test(styles[transitionProp + 'Property']);
    return {
        type: type,
        timeout: timeout,
        propCount: propCount,
        hasTransform: hasTransform
    };
}
function getTimeout(delays, durations) {
    /* istanbul ignore next */
    while (delays.length < durations.length) {
        delays = delays.concat(delays);
    }
    return Math.max.apply(null, durations.map(function (d, i) {
        return toMs(d) + toMs(delays[i]);
    }));
}
// Old versions of Chromium (below 61.0.3163.100) formats floating pointer numbers
// in a locale-dependent way, using a comma instead of a dot.
// If comma is not replaced with a dot, the input will be rounded down (i.e. acting
// as a floor function) causing unexpected behaviors
function toMs(s) {
    return Number(s.slice(0, -1).replace(',', '.')) * 1000;
}

function enter(vnode, toggleDisplay) {
    var el = vnode.elm;
    // call leave callback now
    if (isDef(el._leaveCb)) {
        el._leaveCb.cancelled = true;
        el._leaveCb();
    }
    var data = resolveTransition(vnode.data.transition);
    if (isUndef(data)) {
        return;
    }
    /* istanbul ignore if */
    if (isDef(el._enterCb) || el.nodeType !== 1) {
        return;
    }
    var css = data.css, type = data.type, enterClass = data.enterClass, enterToClass = data.enterToClass, enterActiveClass = data.enterActiveClass, appearClass = data.appearClass, appearToClass = data.appearToClass, appearActiveClass = data.appearActiveClass, beforeEnter = data.beforeEnter, enter = data.enter, afterEnter = data.afterEnter, enterCancelled = data.enterCancelled, beforeAppear = data.beforeAppear, appear = data.appear, afterAppear = data.afterAppear, appearCancelled = data.appearCancelled, duration = data.duration;
    // activeInstance will always be the <transition> component managing this
    // transition. One edge case to check is when the <transition> is placed
    // as the root node of a child component. In that case we need to check
    // <transition>'s parent for appear check.
    var context = activeInstance;
    var transitionNode = activeInstance.$vnode;
    while (transitionNode && transitionNode.parent) {
        context = transitionNode.context;
        transitionNode = transitionNode.parent;
    }
    var isAppear = !context._isMounted || !vnode.isRootInsert;
    if (isAppear && !appear && appear !== '') {
        return;
    }
    var startClass = isAppear && appearClass ? appearClass : enterClass;
    var activeClass = isAppear && appearActiveClass ? appearActiveClass : enterActiveClass;
    var toClass = isAppear && appearToClass ? appearToClass : enterToClass;
    var beforeEnterHook = isAppear ? beforeAppear || beforeEnter : beforeEnter;
    var enterHook = isAppear ? (isFunction(appear) ? appear : enter) : enter;
    var afterEnterHook = isAppear ? afterAppear || afterEnter : afterEnter;
    var enterCancelledHook = isAppear
        ? appearCancelled || enterCancelled
        : enterCancelled;
    var explicitEnterDuration = toNumber(isObject(duration) ? duration.enter : duration);
    if (false) {}
    var expectsCSS = css !== false && !isIE9;
    var userWantsControl = getHookArgumentsLength(enterHook);
    var cb = (el._enterCb = once(function () {
        if (expectsCSS) {
            removeTransitionClass(el, toClass);
            removeTransitionClass(el, activeClass);
        }
        // @ts-expect-error
        if (cb.cancelled) {
            if (expectsCSS) {
                removeTransitionClass(el, startClass);
            }
            enterCancelledHook && enterCancelledHook(el);
        }
        else {
            afterEnterHook && afterEnterHook(el);
        }
        el._enterCb = null;
    }));
    if (!vnode.data.show) {
        // remove pending leave element on enter by injecting an insert hook
        mergeVNodeHook(vnode, 'insert', function () {
            var parent = el.parentNode;
            var pendingNode = parent && parent._pending && parent._pending[vnode.key];
            if (pendingNode &&
                pendingNode.tag === vnode.tag &&
                pendingNode.elm._leaveCb) {
                pendingNode.elm._leaveCb();
            }
            enterHook && enterHook(el, cb);
        });
    }
    // start enter transition
    beforeEnterHook && beforeEnterHook(el);
    if (expectsCSS) {
        addTransitionClass(el, startClass);
        addTransitionClass(el, activeClass);
        nextFrame(function () {
            removeTransitionClass(el, startClass);
            // @ts-expect-error
            if (!cb.cancelled) {
                addTransitionClass(el, toClass);
                if (!userWantsControl) {
                    if (isValidDuration(explicitEnterDuration)) {
                        setTimeout(cb, explicitEnterDuration);
                    }
                    else {
                        whenTransitionEnds(el, type, cb);
                    }
                }
            }
        });
    }
    if (vnode.data.show) {
        toggleDisplay && toggleDisplay();
        enterHook && enterHook(el, cb);
    }
    if (!expectsCSS && !userWantsControl) {
        cb();
    }
}
function leave(vnode, rm) {
    var el = vnode.elm;
    // call enter callback now
    if (isDef(el._enterCb)) {
        el._enterCb.cancelled = true;
        el._enterCb();
    }
    var data = resolveTransition(vnode.data.transition);
    if (isUndef(data) || el.nodeType !== 1) {
        return rm();
    }
    /* istanbul ignore if */
    if (isDef(el._leaveCb)) {
        return;
    }
    var css = data.css, type = data.type, leaveClass = data.leaveClass, leaveToClass = data.leaveToClass, leaveActiveClass = data.leaveActiveClass, beforeLeave = data.beforeLeave, leave = data.leave, afterLeave = data.afterLeave, leaveCancelled = data.leaveCancelled, delayLeave = data.delayLeave, duration = data.duration;
    var expectsCSS = css !== false && !isIE9;
    var userWantsControl = getHookArgumentsLength(leave);
    var explicitLeaveDuration = toNumber(isObject(duration) ? duration.leave : duration);
    if (false) {}
    var cb = (el._leaveCb = once(function () {
        if (el.parentNode && el.parentNode._pending) {
            el.parentNode._pending[vnode.key] = null;
        }
        if (expectsCSS) {
            removeTransitionClass(el, leaveToClass);
            removeTransitionClass(el, leaveActiveClass);
        }
        // @ts-expect-error
        if (cb.cancelled) {
            if (expectsCSS) {
                removeTransitionClass(el, leaveClass);
            }
            leaveCancelled && leaveCancelled(el);
        }
        else {
            rm();
            afterLeave && afterLeave(el);
        }
        el._leaveCb = null;
    }));
    if (delayLeave) {
        delayLeave(performLeave);
    }
    else {
        performLeave();
    }
    function performLeave() {
        // the delayed leave may have already been cancelled
        // @ts-expect-error
        if (cb.cancelled) {
            return;
        }
        // record leaving element
        if (!vnode.data.show && el.parentNode) {
            (el.parentNode._pending || (el.parentNode._pending = {}))[vnode.key] =
                vnode;
        }
        beforeLeave && beforeLeave(el);
        if (expectsCSS) {
            addTransitionClass(el, leaveClass);
            addTransitionClass(el, leaveActiveClass);
            nextFrame(function () {
                removeTransitionClass(el, leaveClass);
                // @ts-expect-error
                if (!cb.cancelled) {
                    addTransitionClass(el, leaveToClass);
                    if (!userWantsControl) {
                        if (isValidDuration(explicitLeaveDuration)) {
                            setTimeout(cb, explicitLeaveDuration);
                        }
                        else {
                            whenTransitionEnds(el, type, cb);
                        }
                    }
                }
            });
        }
        leave && leave(el, cb);
        if (!expectsCSS && !userWantsControl) {
            cb();
        }
    }
}
// only used in dev mode
function checkDuration(val, name, vnode) {
    if (typeof val !== 'number') {
        warn("<transition> explicit ".concat(name, " duration is not a valid number - ") +
            "got ".concat(JSON.stringify(val), "."), vnode.context);
    }
    else if (isNaN(val)) {
        warn("<transition> explicit ".concat(name, " duration is NaN - ") +
            'the duration expression might be incorrect.', vnode.context);
    }
}
function isValidDuration(val) {
    return typeof val === 'number' && !isNaN(val);
}
/**
 * Normalize a transition hook's argument length. The hook may be:
 * - a merged hook (invoker) with the original in .fns
 * - a wrapped component method (check ._length)
 * - a plain function (.length)
 */
function getHookArgumentsLength(fn) {
    if (isUndef(fn)) {
        return false;
    }
    // @ts-expect-error
    var invokerFns = fn.fns;
    if (isDef(invokerFns)) {
        // invoker
        return getHookArgumentsLength(Array.isArray(invokerFns) ? invokerFns[0] : invokerFns);
    }
    else {
        // @ts-expect-error
        return (fn._length || fn.length) > 1;
    }
}
function _enter(_, vnode) {
    if (vnode.data.show !== true) {
        enter(vnode);
    }
}
var transition = inBrowser
    ? {
        create: _enter,
        activate: _enter,
        remove: function (vnode, rm) {
            /* istanbul ignore else */
            if (vnode.data.show !== true) {
                // @ts-expect-error
                leave(vnode, rm);
            }
            else {
                rm();
            }
        }
    }
    : {};

var platformModules = [attrs, klass, events, domProps, style, transition];

// the directive module should be applied last, after all
// built-in modules have been applied.
var modules = platformModules.concat(baseModules);
var patch = createPatchFunction({ nodeOps: nodeOps, modules: modules });

/**
 * Not type checking this file because flow doesn't like attaching
 * properties to Elements.
 */
/* istanbul ignore if */
if (isIE9) {
    // http://www.matts411.com/post/internet-explorer-9-oninput/
    document.addEventListener('selectionchange', function () {
        var el = document.activeElement;
        // @ts-expect-error
        if (el && el.vmodel) {
            trigger(el, 'input');
        }
    });
}
var directive = {
    inserted: function (el, binding, vnode, oldVnode) {
        if (vnode.tag === 'select') {
            // #6903
            if (oldVnode.elm && !oldVnode.elm._vOptions) {
                mergeVNodeHook(vnode, 'postpatch', function () {
                    directive.componentUpdated(el, binding, vnode);
                });
            }
            else {
                setSelected(el, binding, vnode.context);
            }
            el._vOptions = [].map.call(el.options, getValue);
        }
        else if (vnode.tag === 'textarea' || isTextInputType(el.type)) {
            el._vModifiers = binding.modifiers;
            if (!binding.modifiers.lazy) {
                el.addEventListener('compositionstart', onCompositionStart);
                el.addEventListener('compositionend', onCompositionEnd);
                // Safari < 10.2 & UIWebView doesn't fire compositionend when
                // switching focus before confirming composition choice
                // this also fixes the issue where some browsers e.g. iOS Chrome
                // fires "change" instead of "input" on autocomplete.
                el.addEventListener('change', onCompositionEnd);
                /* istanbul ignore if */
                if (isIE9) {
                    el.vmodel = true;
                }
            }
        }
    },
    componentUpdated: function (el, binding, vnode) {
        if (vnode.tag === 'select') {
            setSelected(el, binding, vnode.context);
            // in case the options rendered by v-for have changed,
            // it's possible that the value is out-of-sync with the rendered options.
            // detect such cases and filter out values that no longer has a matching
            // option in the DOM.
            var prevOptions_1 = el._vOptions;
            var curOptions_1 = (el._vOptions = [].map.call(el.options, getValue));
            if (curOptions_1.some(function (o, i) { return !looseEqual(o, prevOptions_1[i]); })) {
                // trigger change event if
                // no matching option found for at least one value
                var needReset = el.multiple
                    ? binding.value.some(function (v) { return hasNoMatchingOption(v, curOptions_1); })
                    : binding.value !== binding.oldValue &&
                        hasNoMatchingOption(binding.value, curOptions_1);
                if (needReset) {
                    trigger(el, 'change');
                }
            }
        }
    }
};
function setSelected(el, binding, vm) {
    actuallySetSelected(el, binding, vm);
    /* istanbul ignore if */
    if (isIE || isEdge) {
        setTimeout(function () {
            actuallySetSelected(el, binding, vm);
        }, 0);
    }
}
function actuallySetSelected(el, binding, vm) {
    var value = binding.value;
    var isMultiple = el.multiple;
    if (isMultiple && !Array.isArray(value)) {
         false &&
            0;
        return;
    }
    var selected, option;
    for (var i = 0, l = el.options.length; i < l; i++) {
        option = el.options[i];
        if (isMultiple) {
            selected = looseIndexOf(value, getValue(option)) > -1;
            if (option.selected !== selected) {
                option.selected = selected;
            }
        }
        else {
            if (looseEqual(getValue(option), value)) {
                if (el.selectedIndex !== i) {
                    el.selectedIndex = i;
                }
                return;
            }
        }
    }
    if (!isMultiple) {
        el.selectedIndex = -1;
    }
}
function hasNoMatchingOption(value, options) {
    return options.every(function (o) { return !looseEqual(o, value); });
}
function getValue(option) {
    return '_value' in option ? option._value : option.value;
}
function onCompositionStart(e) {
    e.target.composing = true;
}
function onCompositionEnd(e) {
    // prevent triggering an input event for no reason
    if (!e.target.composing)
        return;
    e.target.composing = false;
    trigger(e.target, 'input');
}
function trigger(el, type) {
    var e = document.createEvent('HTMLEvents');
    e.initEvent(type, true, true);
    el.dispatchEvent(e);
}

// recursively search for possible transition defined inside the component root
function locateNode(vnode) {
    // @ts-expect-error
    return vnode.componentInstance && (!vnode.data || !vnode.data.transition)
        ? locateNode(vnode.componentInstance._vnode)
        : vnode;
}
var show = {
    bind: function (el, _a, vnode) {
        var value = _a.value;
        vnode = locateNode(vnode);
        var transition = vnode.data && vnode.data.transition;
        var originalDisplay = (el.__vOriginalDisplay =
            el.style.display === 'none' ? '' : el.style.display);
        if (value && transition) {
            vnode.data.show = true;
            enter(vnode, function () {
                el.style.display = originalDisplay;
            });
        }
        else {
            el.style.display = value ? originalDisplay : 'none';
        }
    },
    update: function (el, _a, vnode) {
        var value = _a.value, oldValue = _a.oldValue;
        /* istanbul ignore if */
        if (!value === !oldValue)
            return;
        vnode = locateNode(vnode);
        var transition = vnode.data && vnode.data.transition;
        if (transition) {
            vnode.data.show = true;
            if (value) {
                enter(vnode, function () {
                    el.style.display = el.__vOriginalDisplay;
                });
            }
            else {
                leave(vnode, function () {
                    el.style.display = 'none';
                });
            }
        }
        else {
            el.style.display = value ? el.__vOriginalDisplay : 'none';
        }
    },
    unbind: function (el, binding, vnode, oldVnode, isDestroy) {
        if (!isDestroy) {
            el.style.display = el.__vOriginalDisplay;
        }
    }
};

var platformDirectives = {
    model: directive,
    show: show
};

// Provides transition support for a single element/component.
var transitionProps = {
    name: String,
    appear: Boolean,
    css: Boolean,
    mode: String,
    type: String,
    enterClass: String,
    leaveClass: String,
    enterToClass: String,
    leaveToClass: String,
    enterActiveClass: String,
    leaveActiveClass: String,
    appearClass: String,
    appearActiveClass: String,
    appearToClass: String,
    duration: [Number, String, Object]
};
// in case the child is also an abstract component, e.g. <keep-alive>
// we want to recursively retrieve the real component to be rendered
function getRealChild(vnode) {
    var compOptions = vnode && vnode.componentOptions;
    if (compOptions && compOptions.Ctor.options.abstract) {
        return getRealChild(getFirstComponentChild(compOptions.children));
    }
    else {
        return vnode;
    }
}
function extractTransitionData(comp) {
    var data = {};
    var options = comp.$options;
    // props
    for (var key in options.propsData) {
        data[key] = comp[key];
    }
    // events.
    // extract listeners and pass them directly to the transition methods
    var listeners = options._parentListeners;
    for (var key in listeners) {
        data[camelize(key)] = listeners[key];
    }
    return data;
}
function placeholder(h, rawChild) {
    // @ts-expect-error
    if (/\d-keep-alive$/.test(rawChild.tag)) {
        return h('keep-alive', {
            props: rawChild.componentOptions.propsData
        });
    }
}
function hasParentTransition(vnode) {
    while ((vnode = vnode.parent)) {
        if (vnode.data.transition) {
            return true;
        }
    }
}
function isSameChild(child, oldChild) {
    return oldChild.key === child.key && oldChild.tag === child.tag;
}
var isNotTextNode = function (c) { return c.tag || isAsyncPlaceholder(c); };
var isVShowDirective = function (d) { return d.name === 'show'; };
var Transition = {
    name: 'transition',
    props: transitionProps,
    abstract: true,
    render: function (h) {
        var _this = this;
        var children = this.$slots.default;
        if (!children) {
            return;
        }
        // filter out text nodes (possible whitespaces)
        children = children.filter(isNotTextNode);
        /* istanbul ignore if */
        if (!children.length) {
            return;
        }
        // warn multiple elements
        if (false) {}
        var mode = this.mode;
        // warn invalid mode
        if (false) {}
        var rawChild = children[0];
        // if this is a component root node and the component's
        // parent container node also has transition, skip.
        if (hasParentTransition(this.$vnode)) {
            return rawChild;
        }
        // apply transition data to child
        // use getRealChild() to ignore abstract components e.g. keep-alive
        var child = getRealChild(rawChild);
        /* istanbul ignore if */
        if (!child) {
            return rawChild;
        }
        if (this._leaving) {
            return placeholder(h, rawChild);
        }
        // ensure a key that is unique to the vnode type and to this transition
        // component instance. This key will be used to remove pending leaving nodes
        // during entering.
        var id = "__transition-".concat(this._uid, "-");
        child.key =
            child.key == null
                ? child.isComment
                    ? id + 'comment'
                    : id + child.tag
                : isPrimitive(child.key)
                    ? String(child.key).indexOf(id) === 0
                        ? child.key
                        : id + child.key
                    : child.key;
        var data = ((child.data || (child.data = {})).transition =
            extractTransitionData(this));
        var oldRawChild = this._vnode;
        var oldChild = getRealChild(oldRawChild);
        // mark v-show
        // so that the transition module can hand over the control to the directive
        if (child.data.directives && child.data.directives.some(isVShowDirective)) {
            child.data.show = true;
        }
        if (oldChild &&
            oldChild.data &&
            !isSameChild(child, oldChild) &&
            !isAsyncPlaceholder(oldChild) &&
            // #6687 component root is a comment node
            !(oldChild.componentInstance &&
                oldChild.componentInstance._vnode.isComment)) {
            // replace old child transition data with fresh one
            // important for dynamic transitions!
            var oldData = (oldChild.data.transition = extend({}, data));
            // handle transition mode
            if (mode === 'out-in') {
                // return placeholder node and queue update when leave finishes
                this._leaving = true;
                mergeVNodeHook(oldData, 'afterLeave', function () {
                    _this._leaving = false;
                    _this.$forceUpdate();
                });
                return placeholder(h, rawChild);
            }
            else if (mode === 'in-out') {
                if (isAsyncPlaceholder(child)) {
                    return oldRawChild;
                }
                var delayedLeave_1;
                var performLeave = function () {
                    delayedLeave_1();
                };
                mergeVNodeHook(data, 'afterEnter', performLeave);
                mergeVNodeHook(data, 'enterCancelled', performLeave);
                mergeVNodeHook(oldData, 'delayLeave', function (leave) {
                    delayedLeave_1 = leave;
                });
            }
        }
        return rawChild;
    }
};

// Provides transition support for list items.
var props = extend({
    tag: String,
    moveClass: String
}, transitionProps);
delete props.mode;
var TransitionGroup = {
    props: props,
    beforeMount: function () {
        var _this = this;
        var update = this._update;
        this._update = function (vnode, hydrating) {
            var restoreActiveInstance = setActiveInstance(_this);
            // force removing pass
            _this.__patch__(_this._vnode, _this.kept, false, // hydrating
            true // removeOnly (!important, avoids unnecessary moves)
            );
            _this._vnode = _this.kept;
            restoreActiveInstance();
            update.call(_this, vnode, hydrating);
        };
    },
    render: function (h) {
        var tag = this.tag || this.$vnode.data.tag || 'span';
        var map = Object.create(null);
        var prevChildren = (this.prevChildren = this.children);
        var rawChildren = this.$slots.default || [];
        var children = (this.children = []);
        var transitionData = extractTransitionData(this);
        for (var i = 0; i < rawChildren.length; i++) {
            var c = rawChildren[i];
            if (c.tag) {
                if (c.key != null && String(c.key).indexOf('__vlist') !== 0) {
                    children.push(c);
                    map[c.key] = c;
                    (c.data || (c.data = {})).transition = transitionData;
                }
                else if (false) { var name_1, opts; }
            }
        }
        if (prevChildren) {
            var kept = [];
            var removed = [];
            for (var i = 0; i < prevChildren.length; i++) {
                var c = prevChildren[i];
                c.data.transition = transitionData;
                // @ts-expect-error .getBoundingClientRect is not typed in Node
                c.data.pos = c.elm.getBoundingClientRect();
                if (map[c.key]) {
                    kept.push(c);
                }
                else {
                    removed.push(c);
                }
            }
            this.kept = h(tag, null, kept);
            this.removed = removed;
        }
        return h(tag, null, children);
    },
    updated: function () {
        var children = this.prevChildren;
        var moveClass = this.moveClass || (this.name || 'v') + '-move';
        if (!children.length || !this.hasMove(children[0].elm, moveClass)) {
            return;
        }
        // we divide the work into three loops to avoid mixing DOM reads and writes
        // in each iteration - which helps prevent layout thrashing.
        children.forEach(callPendingCbs);
        children.forEach(recordPosition);
        children.forEach(applyTranslation);
        // force reflow to put everything in position
        // assign to this to avoid being removed in tree-shaking
        // $flow-disable-line
        this._reflow = document.body.offsetHeight;
        children.forEach(function (c) {
            if (c.data.moved) {
                var el_1 = c.elm;
                var s = el_1.style;
                addTransitionClass(el_1, moveClass);
                s.transform = s.WebkitTransform = s.transitionDuration = '';
                el_1.addEventListener(transitionEndEvent, (el_1._moveCb = function cb(e) {
                    if (e && e.target !== el_1) {
                        return;
                    }
                    if (!e || /transform$/.test(e.propertyName)) {
                        el_1.removeEventListener(transitionEndEvent, cb);
                        el_1._moveCb = null;
                        removeTransitionClass(el_1, moveClass);
                    }
                }));
            }
        });
    },
    methods: {
        hasMove: function (el, moveClass) {
            /* istanbul ignore if */
            if (!hasTransition) {
                return false;
            }
            /* istanbul ignore if */
            if (this._hasMove) {
                return this._hasMove;
            }
            // Detect whether an element with the move class applied has
            // CSS transitions. Since the element may be inside an entering
            // transition at this very moment, we make a clone of it and remove
            // all other transition classes applied to ensure only the move class
            // is applied.
            var clone = el.cloneNode();
            if (el._transitionClasses) {
                el._transitionClasses.forEach(function (cls) {
                    removeClass(clone, cls);
                });
            }
            addClass(clone, moveClass);
            clone.style.display = 'none';
            this.$el.appendChild(clone);
            var info = getTransitionInfo(clone);
            this.$el.removeChild(clone);
            return (this._hasMove = info.hasTransform);
        }
    }
};
function callPendingCbs(c) {
    /* istanbul ignore if */
    if (c.elm._moveCb) {
        c.elm._moveCb();
    }
    /* istanbul ignore if */
    if (c.elm._enterCb) {
        c.elm._enterCb();
    }
}
function recordPosition(c) {
    c.data.newPos = c.elm.getBoundingClientRect();
}
function applyTranslation(c) {
    var oldPos = c.data.pos;
    var newPos = c.data.newPos;
    var dx = oldPos.left - newPos.left;
    var dy = oldPos.top - newPos.top;
    if (dx || dy) {
        c.data.moved = true;
        var s = c.elm.style;
        s.transform = s.WebkitTransform = "translate(".concat(dx, "px,").concat(dy, "px)");
        s.transitionDuration = '0s';
    }
}

var platformComponents = {
    Transition: Transition,
    TransitionGroup: TransitionGroup
};

// install platform specific utils
Vue.config.mustUseProp = mustUseProp;
Vue.config.isReservedTag = isReservedTag;
Vue.config.isReservedAttr = isReservedAttr;
Vue.config.getTagNamespace = getTagNamespace;
Vue.config.isUnknownElement = isUnknownElement;
// install platform runtime directives & components
extend(Vue.options.directives, platformDirectives);
extend(Vue.options.components, platformComponents);
// install platform patch function
Vue.prototype.__patch__ = inBrowser ? patch : noop;
// public mount method
Vue.prototype.$mount = function (el, hydrating) {
    el = el && inBrowser ? query(el) : undefined;
    return mountComponent(this, el, hydrating);
};
// devtools global hook
/* istanbul ignore next */
if (inBrowser) {
    setTimeout(function () {
        if (config.devtools) {
            if (devtools) {
                devtools.emit('init', Vue);
            }
            else if (false) {}
        }
        if (false) {}
    }, 0);
}



;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/MapChart.vue?vue&type=template&id=3812216a&scoped=true
var render = function render(){var _vm=this,_c=_vm._self._c;return _c('div',{staticClass:"widget_container fr-grid-row",attrs:{"id":_vm.widgetId}},[_c('LeftCol',{attrs:{"props":_vm.leftColProps}}),_c('div',{staticClass:"r_col fr-col-12 fr-col-lg-9"},[(_vm.zoomDep !== undefined)?_c('button',{staticClass:"fr-btn fr-btn--sm fr-icon-arrow-go-back-fill fr-btn--icon-left fr-btn--tertiary-no-outline fr-ml-4w",on:{"click":_vm.resetGeoFilters}},[_vm._v(" Retour ")]):_vm._e(),_c('div',{staticClass:"map m-lg"},[_c('div',{ref:"mapTooltip",staticClass:"map_tooltip",style:({top:_vm.tooltip.top,left:_vm.tooltip.left,visibility:_vm.tooltip.visibility})},[_c('div',{staticClass:"tooltip_header"},[_vm._v(_vm._s(_vm.tooltip.place))]),_c('div',{staticClass:"tooltip_body"},[_c('div',{staticClass:"tooltip_value"},[_vm._v(_vm._s(_vm.convertStringToLocaleNumber(_vm.tooltip.value)))])])]),(_vm.isDep)?_c('div',{staticClass:"france_container no_select",style:({display:_vm.displayFrance})},[_c('france',{attrs:{"props":_vm.FranceProps,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1):_vm._e(),(_vm.isReg)?_c('div',{staticClass:"france_container no_select",style:({display:_vm.displayFrance})},[_c('france-reg',{attrs:{"props":_vm.FranceProps,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1):_vm._e(),(_vm.isAcad)?_c('div',{staticClass:"france_container no_select",style:({display:_vm.displayFrance})},[_c('france-acad',{attrs:{"props":_vm.FranceProps,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1):_vm._e(),_c('div',{staticClass:"om_container fr-grid-row no_select"},[_c('div',{staticClass:"om fr-col-4 fr-col-sm",style:({display:_vm.displayGuadeloupe})},[_c('span',{staticClass:"fr-text--xs fr-my-1w",style:({color:_vm.textMention})},[_vm._v("Guadeloupe")]),_c('guadeloupe',{attrs:{"props":_vm.colorStrokeDOM,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1),_c('div',{staticClass:"om fr-col-4 fr-col-sm fr-ml-1v",style:({display:_vm.displayMartinique})},[_c('span',{staticClass:"fr-text--xs fr-my-1w",style:({color:_vm.textMention})},[_vm._v("Martinique")]),_c('martinique',{attrs:{"props":_vm.colorStrokeDOM,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1),_c('div',{staticClass:"om fr-col-4 fr-col-sm fr-ml-1v",style:({display:_vm.displayGuyanne})},[_c('span',{staticClass:"fr-text--xs fr-my-1w",style:({color:_vm.textMention})},[_vm._v("Guyane")]),_c('guyane',{attrs:{"props":_vm.colorStrokeDOM,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1),_c('div',{staticClass:"om fr-col-4 fr-col-sm fr-ml-1v",style:({display:_vm.displayReunion})},[_c('span',{staticClass:"fr-text--xs fr-my-1w",style:({color:_vm.textMention})},[_vm._v("La Réunion")]),_c('reunion',{attrs:{"props":_vm.colorStrokeDOM,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1),_c('div',{staticClass:"om fr-col-4 fr-col-sm fr-ml-1v",style:({display:_vm.displayMayotte})},[_c('span',{staticClass:"fr-text--xs fr-my-1w",style:({color:_vm.textMention})},[_vm._v("Mayotte")]),_c('mayotte',{attrs:{"props":_vm.colorStrokeDOM,"onclick":_vm.changeGeoLevel,"ondblclick":_vm.resetGeoFilters,"onenter":_vm.displayTooltip,"onleave":_vm.hideTooltip}})],1)])])])],1)
}
var staticRenderFns = []


// EXTERNAL MODULE: ./node_modules/chroma-js/chroma.js
var chroma = __webpack_require__(792);
var chroma_default = /*#__PURE__*/__webpack_require__.n(chroma);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/LeftCol.vue?vue&type=template&id=ec5d0502&scoped=true
var LeftColvue_type_template_id_ec5d0502_scoped_true_render = function render(){var _vm=this,_c=_vm._self._c;return _c('div',{staticClass:"l_col fr-col-12 fr-col-lg-3"},[(_vm.props['levelNat'])?_c('div',{attrs:{"data-box":"number"}},[_c('p',{staticClass:"fr-text--xs fr-mb-1v",style:({color: _vm.props['textMention']})},[_vm._v("Mise à jour : "+_vm._s(_vm.props['date']))]),_c('p',{staticClass:"fr-text--xs fr-text--bold fr-mb-1v",style:({color: _vm.props['textMention']})},[_vm._v(_vm._s(_vm.props['names'])+", "+_vm._s(_vm.props['locaParent']))]),_c('p',{staticClass:"fr-text--xs fr-text--bold fr-mb-2w",style:({color: _vm.props['textMention']})},[_vm._v(_vm._s(_vm.convertFloatToHuman(_vm.props['valueNat'])))]),(_vm.props['levelNat'])?_c('div',{staticClass:"sep fr-mb-2w"}):_vm._e()]):_vm._e(),_c('div',[_c('p',{staticClass:"fr-text--xs fr-mb-1v",style:({color: _vm.props['textMention']})},[_vm._v("Localisation")]),_c('p',{staticClass:"flex fr-text--sm fr-text--bold fr-mb-2w"},[_c('span',[_vm._v(_vm._s(_vm.props['localisation']))])]),_c('p',{staticClass:"fr-text--xs fr-mb-1v",style:({color: _vm.props['textMention']})},[_vm._v("Mise à jour : "+_vm._s(_vm.props['date']))]),_c('p',{staticClass:"fr-text--sm fr-text--bold fr-mb-1v"},[_vm._v(_vm._s(_vm.props['names']))]),_c('p',{staticClass:"fr-text--md fr-text--bold fr-my-0"},[_vm._v(_vm._s(_vm.convertFloatToHuman(_vm.props['value'])))])]),_c('div',{staticClass:"scale"},[_c('div',{staticClass:"sep fr-my-2w"}),_c('p',{staticClass:"fr-text--xs fr-mb-1w",style:({color: _vm.props['textMention']})},[_vm._v("Légende")]),_c('div',{staticClass:"scale_container",style:({background:_vm.gradient})}),_c('div',[_c('span',{staticClass:"min fr-text--sm fr-text--bold fr-mb-0"},[_vm._v(_vm._s(_vm.convertFloatToHuman(_vm.props['min'])))]),_c('span',{staticClass:"max fr-text--sm fr-text--bold fr-mb-0"},[_vm._v(_vm._s(_vm.convertFloatToHuman(_vm.props['max'])))])])])])
}
var LeftColvue_type_template_id_ec5d0502_scoped_true_staticRenderFns = []


;// CONCATENATED MODULE: ./src/utils.js
const utils_capitalize = function (string) {
  if (string) {
    return string.charAt(0).toUpperCase() + string.slice(1)
  }
}
const convertStringToLocaleNumber = function (string) {
  return parseInt(string).toLocaleString('fr-FR')
}

const convertFloatToHuman = function (float) {
  if (Number.isInteger(parseFloat(float))) {
    return parseInt(float).toLocaleString('fr-FR')
  } else {
    return parseFloat(float).toFixed(2).toLocaleString('fr-FR')
  }
}

const convertIntToHuman = function (int) {
  let res = parseFloat(int)
  if (Math.floor(res / 1000000000) >= 10) {
    res = (res / 1000000000).toFixed(0).replace('.', ',') + ' milliards'
  } else if (Math.floor(res / 1000000000) >= 2) {
    res = (res / 1000000000).toFixed(1).replace('.', ',') + ' milliards'
  } else if (Math.floor(res / 1000000000) >= 1) {
    res = (res / 1000000000).toFixed(1).replace('.', ',') + ' milliard'
  } else if (Math.floor(res / 1000000) >= 10) {
    res = (res / 1000000).toFixed(0).replace('.', ',') + ' millions'
  } else if (Math.floor(res / 1000000) >= 2) {
    res = (res / 1000000).toFixed(1).replace('.', ',') + ' millions'
  } else if (Math.floor(res / 1000000) >= 1) {
    res = (res / 1000000).toFixed(1).replace('.', ',') + ' million'
  } else if (Number.isInteger(parseFloat(res))) {
    return parseInt(res).toLocaleString('fr-FR').replace('.', ',')
  } else {
    return parseFloat(res).toFixed(2).toLocaleString('fr-FR').replace('.', ',')
  }
  return res
}

const convertIntToHumanTable = function (int) {
  const res = parseFloat(int)
  if (isNaN(res)) {
    return int
  } else if (Number.isInteger(parseFloat(res))) {
    return parseInt(res).toLocaleString('fr-FR')
  } else {
    return parseFloat(res).toFixed(2).toLocaleString('fr-FR')
  }
}

const convertDateToHuman = function (string) {
  const date = new Date(string)
  return date.toLocaleDateString('fr-FR')
}

const testIfNaN = function (float) {
  return isNaN(parseFloat(float))
}

const colorsDSFR = [
  'green-bourgeon',
  'blue-ecume',
  'purple-glycine',
  'pink-macaron',
  'yellow-tournesol',
  'orange-terre-battue',
  'brown-cafe-creme',
  'beige-gris-galet',
  'green-emeraude',
  'blue-cumulus',
  'pink-tuile',
  'yellow-moutarde',
  'brown-caramel',
  'green-menthe',
  'brown-opera',
  'green-archipel',
  'green-tilleul-verveine'
]

const dep = [
  {
    value: '01',
    label: 'Ain',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-01'
  },
  {
    value: '02',
    label: 'Aisne',
    region: 'Hauts-de-France',
    region_value: '32',
    classMap: 'FR-dep-02'
  },
  {
    value: '03',
    label: 'Allier',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-03'
  },
  {
    value: '04',
    label: 'Alpes de Haute-Provence',
    region: "Provence-Alpes-Côte d'Azur",
    region_value: '93',
    classMap: 'FR-dep-04'
  },
  {
    value: '05',
    label: 'Hautes-Alpes',
    region: "Provence-Alpes-Côte d'Azur",
    region_value: '93',
    classMap: 'FR-dep-05'
  },
  {
    value: '06',
    label: 'Alpes-Maritimes',
    region: "Provence-Alpes-Côte d'Azur",
    region_value: '93',
    classMap: 'FR-dep-06'
  },
  {
    value: '07',
    label: 'Ardèche',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-07'
  },
  {
    value: '08',
    label: 'Ardennes',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-08'
  },
  {
    value: '09',
    label: 'Ariège',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-09'
  },
  {
    value: '10',
    label: 'Aube',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-10'
  },
  {
    value: '11',
    label: 'Aude',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-11'
  },
  {
    value: '12',
    label: 'Aveyron',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-12'
  },
  {
    value: '13',
    label: 'Bouches-du-Rhône',
    region: "Provence-Alpes-Côte d'Azur",
    region_value: '93',
    classMap: 'FR-dep-13'
  },
  {
    value: '14',
    label: 'Calvados',
    region: 'Normandie',
    region_value: '28',
    classMap: 'FR-dep-14'
  },
  {
    value: '15',
    label: 'Cantal',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-15'
  },
  {
    value: '16',
    label: 'Charente',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-16'
  },
  {
    value: '17',
    label: 'Charente-Maritime',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-17'
  },
  {
    value: '18',
    label: 'Cher',
    region: 'Centre-Val de Loire',
    region_value: '24',
    classMap: 'FR-dep-18'
  },
  {
    value: '19',
    label: 'Corrèze',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-19'
  },
  {
    value: '2A',
    label: 'Corse-du-Sud',
    region: 'Corse',
    region_value: '94',
    classMap: 'FR-dep-2A'
  },
  {
    value: '2B',
    label: 'Haute-Corse',
    region: 'Corse',
    region_value: '94',
    classMap: 'FR-dep-2B'
  },
  {
    value: '21',
    label: "Côte-d'Or",
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-21'
  },
  {
    value: '22',
    label: "Côtes d'Armor",
    region: 'Bretagne',
    region_value: '53',
    classMap: 'FR-dep-22'
  },
  {
    value: '23',
    label: 'Creuse',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-23'
  },
  {
    value: '24',
    label: 'Dordogne',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-24'
  },
  {
    value: '25',
    label: 'Doubs',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-25'
  },
  {
    value: '26',
    label: 'Drôme',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-26'
  },
  {
    value: '27',
    label: 'Eure',
    region: 'Normandie',
    region_value: '28',
    classMap: 'FR-dep-27'
  },
  {
    value: '28',
    label: 'Eure-et-Loir',
    region: 'Centre-Val de Loire',
    region_value: '24',
    classMap: 'FR-dep-28'
  },
  {
    value: '29',
    label: 'Finistère',
    region: 'Bretagne',
    region_value: '53',
    classMap: 'FR-dep-29'
  },
  {
    value: '30',
    label: 'Gard',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-30'
  },
  {
    value: '31',
    label: 'Haute-Garonne',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-31'
  },
  {
    value: '32',
    label: 'Gers',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-32'
  },
  {
    value: '33',
    label: 'Gironde',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-33'
  },
  {
    value: '34',
    label: 'Hérault',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-34'
  },
  {
    value: '35',
    label: 'Ille-et-Vilaine',
    region: 'Bretagne',
    region_value: '53',
    classMap: 'FR-dep-35'
  },
  {
    value: '36',
    label: 'Indre',
    region: 'Centre-Val de Loire',
    region_value: '24',
    classMap: 'FR-dep-36'
  },
  {
    value: '37',
    label: 'Indre-et-Loire',
    region: 'Centre-Val de Loire',
    region_value: '24',
    classMap: 'FR-dep-37'
  },
  {
    value: '38',
    label: 'Isère',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-38'
  },
  {
    value: '39',
    label: 'Jura',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-39'
  },
  {
    value: '40',
    label: 'Landes',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-40'
  },
  {
    value: '41',
    label: 'Loir-et-Cher',
    region: 'Centre-Val de Loire',
    region_value: '24',
    classMap: 'FR-dep-41'
  },
  {
    value: '42',
    label: 'Loire',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-42'
  },
  {
    value: '43',
    label: 'Haute-Loire',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-43'
  },
  {
    value: '44',
    label: 'Loire-Atlantique',
    region: 'Pays de la Loire',
    region_value: '52',
    classMap: 'FR-dep-44'
  },
  {
    value: '45',
    label: 'Loiret',
    region: 'Centre-Val de Loire',
    region_value: '24',
    classMap: 'FR-dep-45'
  },
  {
    value: '46',
    label: 'Lot',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-46'
  },
  {
    value: '47',
    label: 'Lot-et-Garonne',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-47'
  },
  {
    value: '48',
    label: 'Lozère',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-48'
  },
  {
    value: '49',
    label: 'Maine-et-Loire',
    region: 'Pays de la Loire',
    region_value: '52',
    classMap: 'FR-dep-49'
  },
  {
    value: '50',
    label: 'Manche',
    region: 'Normandie',
    region_value: '28',
    classMap: 'FR-dep-50'
  },
  {
    value: '51',
    label: 'Marne',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-51'
  },
  {
    value: '52',
    label: 'Haute-Marne',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-52'
  },
  {
    value: '53',
    label: 'Mayenne',
    region: 'Pays de la Loire',
    region_value: '52',
    classMap: 'FR-dep-53'
  },
  {
    value: '54',
    label: 'Meurthe-et-Moselle',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-54'
  },
  {
    value: '55',
    label: 'Meuse',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-55'
  },
  {
    value: '56',
    label: 'Morbihan',
    region: 'Bretagne',
    region_value: '53',
    classMap: 'FR-dep-56'
  },
  {
    value: '57',
    label: 'Moselle',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-57'
  },
  {
    value: '58',
    label: 'Nièvre',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-58'
  },
  {
    value: '59',
    label: 'Nord',
    region: 'Hauts-de-France',
    region_value: '32',
    classMap: 'FR-dep-59'
  },
  {
    value: '60',
    label: 'Oise',
    region: 'Hauts-de-France',
    region_value: '32',
    classMap: 'FR-dep-60'
  },
  {
    value: '61',
    label: 'Orne',
    region: 'Normandie',
    region_value: '28',
    classMap: 'FR-dep-61'
  },
  {
    value: '62',
    label: 'Pas-de-Calais',
    region: 'Hauts-de-France',
    region_value: '32',
    classMap: 'FR-dep-62'
  },
  {
    value: '63',
    label: 'Puy-de-Dôme',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-63'
  },
  {
    value: '64',
    label: 'Pyrénées-Atlantiques',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-64'
  },
  {
    value: '65',
    label: 'Hautes-Pyrénées',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-65'
  },
  {
    value: '66',
    label: 'Pyrénées-Orientales',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-66'
  },
  {
    value: '67',
    label: 'Bas-Rhin',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-67'
  },
  {
    value: '68',
    label: 'Haut-Rhin',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-68'
  },
  {
    value: '69',
    label: 'Rhône',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-69'
  },
  {
    value: '70',
    label: 'Haute-Saône',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-70'
  },
  {
    value: '71',
    label: 'Saône-et-Loire',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-71'
  },
  {
    value: '72',
    label: 'Sarthe',
    region: 'Pays de la Loire',
    region_value: '52',
    classMap: 'FR-dep-72'
  },
  {
    value: '73',
    label: 'Savoie',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-73'
  },
  {
    value: '74',
    label: 'Haute-Savoie',
    region: 'Auvergne-Rhône-Alpes',
    region_value: '84',
    classMap: 'FR-dep-74'
  },
  {
    value: '75',
    label: 'Paris',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-75'
  },
  {
    value: '76',
    label: 'Seine-Maritime',
    region: 'Normandie',
    region_value: '28',
    classMap: 'FR-dep-76'
  },
  {
    value: '77',
    label: 'Seine-et-Marne',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-77'
  },
  {
    value: '78',
    label: 'Yvelines',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-78'
  },
  {
    value: '79',
    label: 'Deux-Sèvres',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-79'
  },
  {
    value: '80',
    label: 'Somme',
    region: 'Hauts-de-France',
    region_value: '32',
    classMap: 'FR-dep-80'
  },
  {
    value: '81',
    label: 'Tarn',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-81'
  },
  {
    value: '82',
    label: 'Tarn-et-Garonne',
    region: 'Occitanie',
    region_value: '76',
    classMap: 'FR-dep-82'
  },
  {
    value: '83',
    label: 'Var',
    region: "Provence-Alpes-Côte d'Azur",
    region_value: '93',
    classMap: 'FR-dep-83'
  },
  {
    value: '84',
    label: 'Vaucluse',
    region: "Provence-Alpes-Côte d'Azur",
    region_value: '93',
    classMap: 'FR-dep-84'
  },
  {
    value: '85',
    label: 'Vendée',
    region: 'Pays de la Loire',
    region_value: '52',
    classMap: 'FR-dep-85'
  },
  {
    value: '86',
    label: 'Vienne',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-86'
  },
  {
    value: '87',
    label: 'Haute-Vienne',
    region: 'Nouvelle-Aquitaine',
    region_value: '75',
    classMap: 'FR-dep-87'
  },
  {
    value: '88',
    label: 'Vosges',
    region: 'Grand Est',
    region_value: '44',
    classMap: 'FR-dep-88'
  },
  {
    value: '89',
    label: 'Yonne',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-89'
  },
  {
    value: '90',
    label: 'Territoire-de-Belfort',
    region: 'Bourgogne-Franche-Comté',
    region_value: '27',
    classMap: 'FR-dep-90'
  },
  {
    value: '91',
    label: 'Essonne',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-91'
  },
  {
    value: '92',
    label: 'Hauts-de-Seine',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-92'
  },
  {
    value: '93',
    label: 'Seine-Saint-Denis',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-93'
  },
  {
    value: '94',
    label: 'Val-de-Marne',
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-94'
  },
  {
    value: '95',
    label: "Val-d'Oise",
    region: 'Île-de-France',
    region_value: '11',
    classMap: 'FR-dep-95'
  },
  {
    value: '971',
    label: 'Guadeloupe',
    region: 'Guadeloupe',
    region_value: '01',
    classMap: 'FR-DOM-971'
  },
  {
    value: '972',
    label: 'Martinique',
    region: 'Martinique',
    region_value: '02',
    classMap: 'FR-DOM-972'
  },
  {
    value: '973',
    label: 'Guyane française',
    region: 'Guyane',
    region_value: '03',
    classMap: 'FR-DOM-973'
  },
  {
    value: '974',
    label: 'Réunion',
    region: 'La Réunion',
    region_value: '04',
    classMap: 'FR-DOM-974'
  },
  {
    value: '976',
    label: 'Mayotte',
    region: 'Mayotte',
    region_value: '06',
    classMap: 'FR-DOM-976'
  }
]

const reg = [
  {
    value: '84',
    label: 'Auvergne-Rhône-Alpes',
    classMap: 'FR-reg-84'
  },
  {
    value: '32',
    label: 'Hauts-de-France',
    classMap: 'FR-reg-32'
  },
  {
    value: '93',
    label: "Provence-Alpes-Côte d'Azur",
    classMap: 'FR-reg-93'
  },
  {
    value: '44',
    label: 'Grand Est',
    classMap: 'FR-reg-44'
  },
  {
    value: '76',
    label: 'Occitanie',
    classMap: 'FR-reg-76'
  },
  {
    value: '28',
    label: 'Normandie',
    classMap: 'FR-reg-28'
  },
  {
    value: '75',
    label: 'Nouvelle-Aquitaine',
    classMap: 'FR-reg-75'
  },
  {
    value: '24',
    label: 'Centre-Val de Loire',
    classMap: 'FR-reg-24'
  },
  {
    value: '27',
    label: 'Bourgogne-Franche-Comté',
    classMap: 'FR-reg-27'
  },
  {
    value: '53',
    label: 'Bretagne',
    classMap: 'FR-reg-53'
  },
  {
    value: '94',
    label: 'Corse',
    classMap: 'FR-reg-94'
  },
  {
    value: '52',
    label: 'Pays de la Loire',
    classMap: 'FR-reg-52'
  },
  {
    value: '11',
    label: 'Île-de-France',
    classMap: 'FR-reg-11'
  },
  {
    value: '01',
    label: 'Guadeloupe',
    classMap: 'FR-DOM-971'
  },
  {
    value: '02',
    label: 'Martinique',
    classMap: 'FR-DOM-972'
  },
  {
    value: '03',
    label: 'Guyane',
    classMap: 'FR-DOM-973'
  },
  {
    value: '04',
    label: 'La Réunion',
    classMap: 'FR-DOM-974'
  },
  {
    value: '06',
    label: 'Mayotte',
    classMap: 'FR-DOM-976'
  }
]

const getHexaFromName = function (colorName, options = undefined) {
  return window.dsfr.colors.getColor('artwork', 'major', colorName, options)
}

const patternDraw = [
  'plus',
  'cross',
  'dash',
  'cross-dash',
  'dot',
  'dot-dash',
  'disc',
  'ring',
  'line',
  'line-vertical',
  'weave',
  'zigzag',
  'zigzag-vertical',
  'diagonal',
  'diagonal-right-left',
  'square',
  'box',
  'triangle',
  'triangle-inverted',
  'diamond',
  'diamond-box'
]

const getAllColors = function () {
  return colorsDSFR
}

const getAllPattern = function () {
  return patternDraw
}

const acad = [
  {
    value: '01',
    label: 'Académie de Clermont-Ferrand'
  },
  {
    value: '02',
    label: 'Académie de Grenoble'
  },
  {
    value: '03',
    label: 'Académie de Lyon'
  },
  {
    value: '04',
    label: 'Académie de Besançon'
  },
  {
    value: '05',
    label: 'Académie de Dijon'
  },
  {
    value: '06',
    label: 'Académie de Rennes'
  },
  {
    value: '07',
    label: "Académie d'Orléans-Tours"
  },
  {
    value: '08',
    label: 'Académie de Corse'
  },
  {
    value: '09',
    label: 'Académie de Nancy-Metz'
  },
  {
    value: '10',
    label: 'Académie de Reims'
  },
  {
    value: '11',
    label: 'Académie de Strasbourg'
  },
  {
    value: '14',
    label: "Académie d'Amiens"
  },
  {
    value: '15',
    label: 'Académie de Lille'
  },
  {
    value: '16',
    label: 'Académie de Créteil'
  },
  {
    value: '17',
    label: 'Académie de Paris'
  },
  {
    value: '18',
    label: 'Académie de Versailles'
  },
  {
    value: '20',
    label: 'Académie de Normandie'
  },
  {
    value: '21',
    label: 'Académie de Bordeaux'
  },
  {
    value: '22',
    label: 'Académie de Limoges'
  },
  {
    value: '23',
    label: 'Académie de Poitiers'
  },
  {
    value: '24',
    label: 'Académie de Montpellier'
  },
  {
    value: '25',
    label: 'Académie de Toulouse'
  },
  {
    value: '26',
    label: 'Académie de Nantes'
  },
  {
    value: '27',
    label: "Académie d'Aix-Marseille"
  },
  {
    value: '28',
    label: 'Académie de Nice'
  },
  {
    value: '971',
    label: 'Académie de Guadeloupe'
  },
  {
    value: '972',
    label: 'Académie de Martinique'
  },
  {
    value: '973',
    label: 'Académie de Guyane'
  },
  {
    value: '974',
    label: 'Académie de La Réunion'
  },
  {
    value: '976',
    label: 'Académie de Mayotte'
  }
]

const getDep = function (code) {
  const depObj = dep.find(obj => {
    return obj.value === code
  })

  return depObj
}

const getReg = function (code) {
  const regObj = reg.find(obj => {
    return obj.value === code
  })

  return regObj
}

const getAllReg = function (code) {
  const allReg = []
  reg.forEach(element => allReg.push(element.value))
  return allReg
}

const getAcad = function (code) {
  const acadObj = acad.find(obj => {
    return obj.value === code
  })

  return acadObj
}

const getClassMap = function (code, level) {
  let obj

  if (level === 'reg') {
    obj = getReg(code)
  } else if (level === 'dep') {
    obj = getDep(code)
  }

  return obj.classMap
}

const getDepsFromReg = function (code) {
  const depObj = dep.filter(obj => {
    return obj.region_value === code
  })

  const res = []
  depObj.forEach(function (dep, j) {
    res.push(dep.value)
  })
  return res
}

const allToken = {
  'background-contrats-grey': {
    light: '#EEEEEE',
    dark: '#242424'
  },
  'text-mention-grey': {
    light: '#666666',
    dark: '#929292'
  },
  'border-default-grey': {
    light: '#DDDDDD',
    dark: '#353535'
  }
}

const getHexaFromToken = function (token, theme) {
  return allToken[token][theme]
}

const mixin = {
  methods: {
    capitalize: utils_capitalize,
    convertStringToLocaleNumber,
    convertFloatToHuman,
    convertIntToHuman,
    convertIntToHumanTable,
    convertDateToHuman,
    testIfNaN,
    getDep,
    getReg,
    getAcad,
    getDepsFromReg,
    getAllColors,
    getHexaFromName,
    getAllPattern,
    getClassMap,
    getAllReg,
    getHexaFromToken
  }
}

;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/LeftCol.vue?vue&type=script&lang=js


/* harmony default export */ var LeftColvue_type_script_lang_js = ({
  name: 'LeftCol',
  mixins: [mixin],
  data () {
    return {
    }
  },
  props: {
    props: Object
  },
  computed: {
    gradient () {
      return 'linear-gradient(90deg,' + this.props.colMin + ' 0%,' + this.props.colMax + ' 100%)'
    }
  },
  methods: {
  }
});

;// CONCATENATED MODULE: ./src/components/LeftCol.vue?vue&type=script&lang=js
 /* harmony default export */ var components_LeftColvue_type_script_lang_js = (LeftColvue_type_script_lang_js); 
;// CONCATENATED MODULE: ./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-62.use[0]!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-62.use[1]!./node_modules/@vue/vue-loader-v15/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-62.use[2]!./node_modules/sass-loader/dist/cjs.js??clonedRuleSet-62.use[3]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/LeftCol.vue?vue&type=style&index=0&id=ec5d0502&prod&scoped=true&lang=scss
// extracted by mini-css-extract-plugin

;// CONCATENATED MODULE: ./src/components/LeftCol.vue?vue&type=style&index=0&id=ec5d0502&prod&scoped=true&lang=scss

;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/runtime/componentNormalizer.js
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent(
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier /* server only */,
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options =
    typeof scriptExports === 'function' ? scriptExports.options : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) {
    // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () {
          injectStyles.call(
            this,
            (options.functional ? this.parent : this).$root.$options.shadowRoot
          )
        }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functional component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection(h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing ? [].concat(existing, hook) : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}

;// CONCATENATED MODULE: ./src/components/LeftCol.vue



;


/* normalize component */

var component = normalizeComponent(
  components_LeftColvue_type_script_lang_js,
  LeftColvue_type_template_id_ec5d0502_scoped_true_render,
  LeftColvue_type_template_id_ec5d0502_scoped_true_staticRenderFns,
  false,
  null,
  "ec5d0502",
  null
  
)

/* harmony default export */ var LeftCol = (component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/France.vue?vue&type=template&id=15b6e23e
var Francevue_type_template_id_15b6e23e_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"viewBox":_vm.props.viewBox,"version":"1.1","xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{attrs:{"fill-rule":"nonzero","stroke":_vm.props.colorStroke,"stroke-width":"0.2%"}},[_c('g',{staticClass:"France"},[_c('polygon',{staticClass:"FR-dep-01",style:({display:_vm.props.displayDep['FR-dep-01']}),attrs:{"fill":"#6b6b6b","points":"175.696918 130.865522 178.539168 122.147668 178.709108 122.602254 179.29965 122.245383 180.582699 123.027101 181.967711 122.402576 182.520017 122.521534 182.54126 122.134923 183.212523 122.479049 183.735089 123.69411 185.120102 123.991503 185.120102 123.991503 185.281545 124.446089 184.848198 124.836948 186.220465 125.622914 185.944312 125.928804 186.675055 126.736013 186.692049 127.449755 187.053172 126.498098 187.278342 127.326549 187.953855 127.322301 187.630968 128.367424 187.924115 128.62658 189.07546 128.520369 189.551293 127.861856 190.078107 127.82362 190.910814 126.583068 190.974542 127.105629 192.159875 127.483743 192.363803 128.715798 194.908657 128.618083 198.349946 124.764724 198.349946 124.764724 200.206543 126.018022 199.012713 128.104019 199.407824 128.970706 196.858721 129.84589 196.586817 130.355706 197.11788 130.742317 196.442367 131.978621 196.442367 131.978621 195.320762 132.322746 195.210301 133.168191 194.033465 132.705108 193.782803 134.956796 194.279878 136.014664 194.177914 136.8856 194.177914 136.8856 193.243243 142.285401 192.784405 142.761229 191.947449 142.633775 191.68829 144.371398 190.592176 145.080892 190.592176 145.080892 190.158829 143.946552 189.156182 143.09261 189.334619 142.672012 187.044675 140.23764 187.180627 139.481413 185.791366 138.283345 184.907677 139.18402 184.346874 140.594511 183.522664 141.172303 182.923625 141.074588 182.22687 140.241889 181.615085 140.114435 181.470636 140.445815 181.381418 140.042211 181.381418 140.042211 178.144057 140.309864 178.144057 139.34971 177.489787 137.96471 176.899245 137.539864 176.134514 137.960462 176.206739 137.365676 174.817478 136.809127 175.242328 136.040155 175.106376 133.083222 176.096278 131.498544"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-02",style:({display:_vm.props.displayDep['FR-dep-02']}),attrs:{"fill":"#6b6b6b","points":"147.346645 29.8199884 148.86761 29.2464453 149.203242 29.7435159 150.112422 29.6967828 150.71571 29.1274883 152.95892 29.6373043 153.893591 28.6346661 154.773032 28.9235619 155.129906 29.4461233 155.588745 29.0892521 155.932874 29.3229177 156.009347 28.6644054 156.905781 28.2310618 157.44959 28.7026416 159.684303 29.1104944 159.951959 29.8879638 161.62587 28.9363073 161.859538 29.4248809 161.596131 30.3213074 163.036374 30.571967 163.478218 30.9883167 164.459623 30.6951725 164.459623 30.6951725 165.339063 30.9415836 165.390045 31.3281941 166.03157 31.2559701 166.03157 31.2559701 165.776659 32.4880255 166.447923 32.7556789 166.405438 33.7285778 165.572731 36.1374585 166.345959 36.7619831 165.203111 38.3424127 164.166476 38.9117073 163.966796 40.1352657 162.662505 40.0078117 162.352364 40.4369068 163.312527 41.8176585 162.815451 42.531401 163.278539 42.6121218 163.338018 43.0837016 162.666754 43.5637784 162.683748 44.6258951 163.146835 45.2249289 162.607275 45.4203584 162.785712 46.2870456 162.785712 46.2870456 162.581784 47.5318464 161.222262 47.0687635 160.589235 46.2743002 159.386908 46.9922911 159.310435 47.9481961 158.014641 47.638058 155.673715 48.793641 156.034838 50.0002056 155.796921 50.3995614 156.25576 50.9178744 155.826661 51.5084113 156.327985 51.933258 156.621131 51.669853 156.901533 52.4770617 157.50482 52.3623531 157.492075 52.9613869 156.727344 53.4074759 156.395961 53.0888409 155.265858 53.1865557 154.887741 54.0150067 155.54626 55.1535958 154.641328 55.6081817 154.611589 56.2327063 155.767182 56.0670161 156.243014 56.8444856 155.60149 57.112139 155.240367 58.1275225 154.696559 58.2889643 154.925978 58.7520471 154.40766 58.8455134 154.348181 59.5082742 153.600444 59.5762497 152.844211 61.1779217 152.844211 61.1779217 152.41936 61.5687806 152.147456 60.9612499 151.042845 60.6511118 150.77094 59.1429061 150.091179 59.6867098 149.666329 58.5991023 148.956829 58.8625073 148.723161 57.9618323 147.244681 56.8954672 147.656786 55.8800836 146.998268 55.0091479 147.197948 54.5758043 145.57077 54.0957275 145.57077 54.0957275 145.974378 53.398979 146.263277 53.7091171 146.161312 53.2460342 146.896304 52.9273992 146.203798 52.7362182 146.356744 52.498304 146.356744 52.498304 146.360992 52.2773838 146.360992 52.2773838 146.080591 51.9205126 145.859669 52.9571385 145.783196 52.060712 144.432171 51.4319389 144.351449 52.1541782 144.937743 52.2688868 144.695578 52.6512488 144.177261 51.9799911 144.041309 52.2731353 143.739665 52.0819543 143.578222 51.1472916 144.342952 50.8881351 144.716821 51.4021996 145.018465 50.9858499 144.984477 49.5201288 144.062551 49.4733957 143.760907 48.4240244 144.325958 48.0586563 145.090689 48.3815397 145.987124 47.0772604 145.961633 45.5478124 147.214942 45.3651283 146.1953 44.6556344 146.492696 44.1415699 145.961633 43.3853428 146.526684 43.0454654 146.739109 41.7156953 145.944639 40.4241614 146.31001 39.9780724 146.033857 39.4979957 146.552175 39.3620447 146.420471 38.1554802 146.420471 38.1554802 146.420471 38.1554802 146.420471 38.1554802 146.411974 38.0790078 146.411974 38.0790078 146.059348 36.5070751 145.443315 36.3966149 145.825681 35.4619522 145.269126 34.722719 145.80019 34.4508171 145.647243 33.4311851 145.851172 33.7965533 146.420471 33.2994826 146.471453 32.1906328 147.890454 30.571967 147.028007 30.372289"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-75",style:({display:_vm.props.displayDep['FR-dep-75']}),attrs:{"fill":"#6b6b6b","points":"132.345175 59.916127 133.585738 59.9331209 133.917121 61.0462192 133.917121 61.0462192 133.934115 61.6792408 134.881532 61.517799 134.737083 62.0191181 133.691951 61.7047316 132.502369 62.0743482 132.502369 62.0743482 130.628779 61.1226916 130.981405 60.561894 131.440243 60.8295474"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-92",style:({display:_vm.props.displayDep['FR-dep-92']}),attrs:{"fill":"#6b6b6b","points":"131.631426 58.57786 132.578842 58.832768 132.345175 59.916127 132.345175 59.916127 131.440243 60.8295474 130.981405 60.561894 130.628779 61.1226916 132.502369 62.0743482 132.502369 62.0743482 132.047779 64.1306061 132.047779 64.1306061 131.661165 64.3175386 131.440243 63.6377839 130.734991 63.3149005 130.067976 63.4933361 130.067976 63.4933361 129.502925 63.0684894 129.188536 61.4370782 129.485931 61.3053757 129.26076 60.7063419 129.626132 60.0605749 130.203928 59.6867098 130.203928 59.6867098"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-93",style:({display:_vm.props.displayDep['FR-dep-93']}),attrs:{"fill":"#6b6b6b","points":"136.372757 56.912461 136.687147 56.8869702 136.925063 57.740912 136.580934 57.7791483 137.294683 58.9814644 136.997287 60.0138418 136.517206 60.2560044 136.954802 60.7020934 136.767868 61.2374002 137.154482 62.409977 137.154482 62.409977 136.058368 61.1396855 133.917121 61.0462192 133.917121 61.0462192 133.585738 59.9331209 132.345175 59.916127 132.345175 59.916127 132.578842 58.832768 131.631426 58.57786 131.631426 58.57786 131.911827 58.1445164 132.570345 58.3781821 133.118403 57.9490869 134.409948 58.4631514 135.306383 58.0722925"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-94",style:({display:_vm.props.displayDep['FR-dep-94']}),attrs:{"fill":"#6b6b6b","points":"133.917121 61.0462192 136.058368 61.1396855 137.154482 62.409977 137.154482 62.409977 137.010033 63.2766643 137.485866 63.5698085 136.980293 64.139103 136.746626 65.4603762 136.746626 65.4603762 136.283539 65.4646247 135.73973 64.20283 133.968104 64.776373 133.237361 64.7296399 133.177882 64.2453147 132.498121 63.9649159 132.047779 64.1306061 132.047779 64.1306061 132.502369 62.0743482 132.502369 62.0743482 133.691951 61.7047316 134.737083 62.0191181 134.881532 61.517799 133.934115 61.6792408"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-04",style:({display:_vm.props.displayDep['FR-dep-04']}),attrs:{"fill":"#6b6b6b","points":"191.497108 180.377154 193.617112 179.83335 195.613909 180.402645 195.677637 180.041525 195.036112 179.80786 194.126932 178.155206 194.339357 177.751602 194.857675 177.802583 195.830583 178.979409 195.902807 177.343749 195.418478 177.309761 195.494951 176.795697 196.149221 176.230651 196.361646 175.342721 198.201248 174.004454 198.549626 174.306095 198.689826 173.337445 200.002614 173.732552 199.875159 174.221126 200.291513 174.263611 201.217687 175.691095 201.83372 174.947614 201.323899 173.702813 202.437008 173.235482 202.976568 173.673074 203.333442 172.82338 203.070035 172.54723 203.601098 172.232843 204.472042 173.524377 204.87565 173.409669 205.478937 173.991709 208.363672 174.089423 209.200627 171.774009 210.267002 171.463871 210.75558 170.626923 212.085362 170.100113 213.274944 168.876555 213.96745 168.965772 213.96745 168.965772 214.260597 169.870696 213.941959 169.896187 213.636067 170.992291 212.280793 172.067153 212.680153 173.282215 213.916468 174.518519 212.939312 174.722445 212.85859 176.213657 212.85859 176.213657 211.516063 176.935896 211.129449 178.350636 210.704598 178.206188 210.097062 178.898688 209.319586 180.920958 210.568646 183.113167 210.38596 184.060575 211.970653 185.301127 212.174581 186.184808 212.607928 186.197554 213.844243 187.527324 212.824602 187.790729 211.868689 187.076986 210.432694 188.207078 209.081669 187.799226 209.59149 188.559701 208.975457 189.205468 209.765679 189.613321 208.414654 190.216603 208.414654 190.216603 208.219223 189.834241 207.590444 189.821496 206.770483 190.556481 206.341384 189.72803 205.763587 190.165622 204.531521 190.144379 204.518775 190.900606 203.970718 191.546373 202.963822 191.231987 201.621295 189.906465 201.005262 189.953198 200.01536 191.253229 199.093434 191.282968 197.602209 193.114058 196.986176 192.009456 196.110984 191.614349 195.907056 190.964333 195.499199 191.053551 195.465211 191.588858 194.564528 191.924487 194.207654 191.202248 193.349456 191.066297 192.984084 191.571864 192.984084 191.571864 191.123239 189.315928 189.72973 189.20122 189.194418 189.536849 189.228406 188.687155 190.311775 187.132216 189.823197 187.140713 189.585281 186.494946 188.540148 186.252784 189.173176 183.419056 188.472172 183.504026 188.36596 182.220989 188.36596 182.220989 189.780712 181.337308 189.53005 180.878473 189.767966 180.453627 190.32452 180.398397 190.855584 180.950697 190.706886 181.375544 191.539593 181.477507 191.611817 181.056909 190.919311 180.929455 191.072257 180.423887"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-05",style:({display:_vm.props.displayDep['FR-dep-05']}),attrs:{"fill":"#6b6b6b","points":"201.803981 157.265495 202.394523 157.724329 203.087029 157.346216 203.630838 157.86028 203.643583 158.667489 205.164548 159.117826 205.789078 159.041354 205.678617 158.098194 206.20968 157.690342 206.761986 157.928256 207.40351 157.329222 208.333932 157.698839 208.333932 157.698839 209.077421 159.950526 210.326481 160.090725 210.228766 160.719498 210.683356 161.428992 210.313736 161.960051 210.445439 162.796999 212.429491 164.160757 213.25795 164.322198 213.704043 163.871861 215.233504 164.832014 214.834145 165.681708 216.189418 168.269024 214.613223 168.111831 213.96745 168.965772 213.96745 168.965772 213.274944 168.876555 212.085362 170.100113 210.75558 170.626923 210.267002 171.463871 209.200627 171.774009 208.363672 174.089423 205.478937 173.991709 204.87565 173.409669 204.472042 173.524377 203.601098 172.232843 203.070035 172.54723 203.333442 172.82338 202.976568 173.673074 202.437008 173.235482 201.323899 173.702813 201.83372 174.947614 201.217687 175.691095 200.291513 174.263611 199.875159 174.221126 200.002614 173.732552 198.689826 173.337445 198.549626 174.306095 198.201248 174.004454 196.361646 175.342721 196.149221 176.230651 195.494951 176.795697 195.418478 177.309761 195.902807 177.343749 195.830583 178.979409 194.857675 177.802583 194.339357 177.751602 194.126932 178.155206 195.036112 179.80786 195.677637 180.041525 195.613909 180.402645 193.617112 179.83335 191.497108 180.377154 191.497108 180.377154 191.684042 178.520574 190.987287 178.520574 190.804601 177.692123 190.299029 177.551924 190.672898 176.884915 189.236903 176.961387 188.769568 176.460068 188.238505 176.765957 187.724435 176.056463 187.210366 175.967246 187.393052 175.465927 186.955456 174.645973 187.303833 174.429301 187.983594 174.773427 188.340469 174.382568 187.762672 174.076678 187.647962 172.810635 190.188568 173.647583 190.655904 172.789393 191.267688 172.751156 190.09935 171.710282 190.995784 170.036386 190.885323 169.093226 192.42753 169.365128 192.784405 168.795834 193.493905 169.012506 194.216151 168.094837 193.659597 167.699729 193.659597 167.699729 194.156672 166.386953 195.142325 166.709837 196.344652 166.361462 196.858721 165.838901 196.319161 165.261109 197.602209 164.458149 198.205497 164.984959 199.573515 163.786891 201.570313 164.092781 202.432759 163.544729 202.895846 164.084284 203.48214 164.020557 203.528873 161.921815 202.900095 161.564943 202.921337 160.447597 201.646786 160.502827 200.814079 160.014253 201.085983 158.811937 201.519331 158.658992 201.289911 157.830541"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-06",style:({display:_vm.props.displayDep['FR-dep-06']}),attrs:{"fill":"#6b6b6b","points":"213.160234 176.290129 214.294585 178.108473 214.727932 178.189194 214.757672 178.894439 215.36096 179.578442 216.07046 179.374516 217.34926 180.160482 218.126736 180.134992 219.749665 181.621955 220.87127 181.490253 221.126181 182.24648 221.746462 182.008565 222.311513 182.3272 222.417726 181.881111 222.953038 182.000069 224.733161 181.243841 225.73156 181.409532 226.054446 180.742522 226.887153 180.789255 226.581261 181.859869 227.43521 183.045191 227.26527 184.04783 226.517533 184.374961 226.330599 185.704732 224.826628 186.494946 224.694925 187.586802 223.603059 188.156097 224.219092 190.271833 223.437367 191.206496 222.829831 190.981327 222.069349 191.869257 220.828785 192.094426 220.726821 192.978107 220.412432 192.238873 220.221249 192.752938 219.125135 192.659472 218.538841 193.594134 217.621164 193.534656 217.081604 194.762463 217.281284 196.138966 216.584529 195.527187 215.5309 196.393874 215.21651 196.041251 214.345567 196.143214 213.746528 196.839963 214.086408 197.315791 213.59783 197.808613 213.59783 197.808613 212.807608 197.171343 213.003039 196.593552 212.701395 196.534073 213.296186 194.851681 211.312134 194.142187 210.666362 193.220269 210.883035 192.298352 210.504918 191.397677 209.79117 191.440162 209.311089 191.00257 208.826759 191.219241 208.410406 190.229349 208.410406 190.229349 209.76143 189.626066 208.971208 189.218214 209.587241 188.576695 209.077421 187.81622 210.428445 188.219824 211.86444 187.089732 212.820354 187.803474 213.839995 187.544318 212.607928 186.210299 212.170332 186.197554 211.966404 185.313873 210.38596 184.07332 210.564398 183.125912 209.315337 180.933703 210.092813 178.911433 210.70035 178.218933 211.1252 178.363381 211.511814 176.948642 212.854342 176.222154 212.854342 176.222154"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-10",style:({display:_vm.props.displayDep['FR-dep-10']}),attrs:{"fill":"#6b6b6b","points":"154.15275 67.4444102 154.577601 68.2898551 155.350828 68.5022784 155.580248 69.5431528 157.309389 69.5346558 158.855845 70.1591805 159.556848 69.5558982 159.32318 68.4215575 160.17713 68.5745023 160.330076 67.8607599 161.035328 67.8140268 161.969999 66.1996094 162.972646 66.1273855 163.35926 65.1969713 164.26844 65.5963271 164.854734 65.0270326 166.057061 65.1842259 166.222752 64.7976154 166.579627 65.0780142 167.229648 64.8528454 167.824439 65.8172474 167.531292 67.4996403 169.031014 68.935622 170.666688 69.5474012 170.942841 69.2967417 171.54188 69.8872786 172.378836 69.1480454 173.279519 69.126803 173.122324 69.390208 173.742606 69.7300853 173.742606 69.7300853 173.457956 71.3487512 173.02036 71.5824168 173.895552 72.138966 174.388379 73.4517422 175.340044 73.5324631 175.246577 74.0932607 176.750547 74.7432761 176.283212 75.2445952 177.132913 76.744304 176.678323 77.9636139 177.175398 78.5201631 176.699565 79.3316202 176.954476 79.9943811 176.57211 80.3470038 176.661329 80.9842738 174.834472 80.6656388 174.38413 81.404872 173.538678 81.6725254 174.690023 82.560455 174.375633 83.2019735 174.375633 83.2019735 173.763848 83.388906 172.259878 82.9470655 171.397431 83.5843355 171.724566 84.5954706 167.488807 84.7739062 167.106441 85.7298112 167.106441 85.7298112 166.25674 85.5938603 165.857381 85.0925412 165.933854 84.4935074 165.3773 84.6167129 165.691689 84.7611608 165.509004 85.2964676 164.888722 84.8206393 164.888722 84.8206393 164.548841 85.1307774 164.548841 85.1307774 163.881826 85.7043204 163.520703 85.2072498 162.998137 85.1860075 162.726233 85.7255628 162.016733 85.2709768 161.693846 85.5896118 160.083663 85.6193511 160.410798 84.2938294 160.079414 83.7202864 159.824504 84.3193202 159.32318 84.2088601 159.102258 83.6608079 159.658812 83.6990441 159.680055 83.3676637 158.804863 82.6029397 158.256806 80.6613903 157.347626 80.2747799 157.500572 79.3953472 156.901533 79.174427 157.05023 79.6502553 156.068826 80.0751019 155.418805 78.7580772 154.509624 78.8982766 155.257361 77.8149176 154.89199 77.6322335 155.214876 76.6763285 154.479885 76.2727242 154.654074 75.8351321 153.885094 75.3295645 153.179842 74.0890122 152.521324 73.8298558 151.845812 74.2971871 151.599399 73.4602391 151.599399 73.4602391 151.731102 72.7804845 151.191542 72.5383219 151.442204 71.7778463 151.042845 71.0768493 152.003007 70.792202 151.450701 69.7980608 152.763489 69.4326926 152.478839 68.6679686 153.345534 68.1539041 153.12886 67.7587967"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-13",style:({display:_vm.props.displayDep['FR-dep-13']}),attrs:{"fill":"#6b6b6b","points":"174.995915 186.941035 176.882251 187.127968 178.785581 188.062631 180.098369 189.290438 180.463741 190.276082 182.698454 191.508137 183.603386 191.193751 185.03938 191.448659 187.061669 192.748689 188.897023 193.360469 190.235302 193.373214 191.424883 192.55326 192.253342 192.536266 192.894866 191.801281 192.894866 191.801281 193.93575 192.629732 193.634106 193.351972 191.91771 193.747079 191.54809 194.537294 191.786006 195.195806 191.152979 195.442217 192.368051 195.998767 191.91771 197.65142 193.498154 199.155377 192.818393 199.61846 191.624563 199.677939 192.019674 200.723062 191.43338 201.623737 192.419033 201.653476 193.052061 202.503169 193.009575 202.855792 191.637308 203.629013 191.43338 204.96728 191.43338 204.96728 190.570934 204.80159 190.256544 205.443108 189.049969 204.130332 188.404196 204.555179 187.613974 204.202556 185.859342 204.279028 185.668159 203.917909 186.203471 203.157433 185.685153 202.507418 185.944312 201.810669 185.336776 200.723062 184.775973 200.625347 183.560901 201.385822 180.472238 201.466543 179.907187 201.054442 179.618288 199.699181 179.138207 199.601466 179.061734 199.040669 178.182294 198.874979 177.625739 199.325316 177.931632 198.938706 177.506781 199.189365 177.14141 198.326926 177.434557 199.39754 176.504134 199.057663 177.145658 199.614212 176.750547 199.508 176.60185 199.78415 176.979967 199.941344 176.695317 200.230239 177.451551 200.714565 177.137161 201.368829 176.712311 201.398568 175.620445 200.897249 172.221641 200.612602 171.796791 200.119779 172.459558 199.56323 172.370339 199.12139 171.359195 198.394902 166.014576 198.199472 166.014576 198.199472 166.20151 197.243567 167.646002 196.54257 167.471813 195.884058 167.824439 196.355638 170.084643 195.04711 170.267329 194.486312 169.460113 194.205914 170.509494 192.425806 171.473904 192.183643 173.024609 192.710453 172.756953 191.737554 173.440962 190.369548 173.292264 189.179977 173.721363 188.835852 173.266773 188.304793"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-25",style:({display:_vm.props.displayDep['FR-dep-25']}),attrs:{"fill":"#6b6b6b","points":"191.934704 102.927605 192.682441 103.004077 193.298474 102.24785 194.483807 102.018433 195.15507 101.461884 195.426975 101.746531 195.528939 101.143249 196.021765 101.291945 195.953789 100.845856 196.854472 101.368417 198.53688 100.692911 198.728063 100.132114 199.212393 100.450749 199.288866 99.5245829 200.202294 99.7072669 200.644139 99.3376503 200.219288 99.1167301 200.848067 98.6196594 201.4641 99.0912393 201.332396 98.6409018 201.736004 98.7895981 201.880454 98.2160551 202.330795 98.1990612 202.551717 97.0604721 202.895846 97.2134169 203.125266 96.6611163 204.098173 96.6866071 204.47629 96.240518 205.389719 96.5209168 205.529919 97.1751807 206.031243 96.5421592 207.029641 97.0817145 207.505474 96.56765 207.225073 96.0153493 207.45874 95.6627266 207.981307 96.0323432 208.65257 95.8836468 209.030687 95.3355946 208.85225 94.7365608 210.75558 95.8496591 211.435341 95.2081406 211.435341 95.2081406 211.618027 95.5989996 213.105003 95.539521 213.240956 96.1725426 213.861237 96.4869291 213.93771 97.2134169 213.385405 96.8692911 213.202719 97.5363004 213.793261 98.5856717 213.793261 98.5856717 213.746528 99.2909172 212.811857 100.11512 212.714141 100.66742 215.135789 100.144859 215.709337 100.421009 215.828295 101.160243 215.029576 101.398157 214.787411 102.175626 213.844243 102.371056 214.060917 103.526639 212.051374 105.361976 212.361515 105.54466 212.008889 105.952513 209.608484 107.681639 209.413053 108.064001 209.897382 108.484599 208.168241 109.98006 205.971764 110.460136 204.824667 111.649707 205.393967 112.656594 204.807673 114.627882 205.18579 115.583787 199.437563 120.337822 199.157162 120.664954 199.64574 121.149279 199.64574 121.149279 198.073793 119.874739 199.067943 118.740398 198.447662 117.780245 200.848067 115.855689 199.875159 114.377223 198.349946 113.38733 197.776398 113.659232 197.678682 113.298112 197.678682 113.298112 197.733913 113.009216 197.733913 113.009216 197.321808 113.060198 197.368541 112.482407 196.905454 112.16802 197.134874 111.445781 196.450864 111.110152 196.786496 110.417652 195.660643 110.107514 195.843328 109.372529 195.380241 109.912084 194.513546 109.733649 193.931501 109.34279 193.846531 108.590811 193.506651 109.245075 193.001078 109.491486 192.682441 109.240826 192.852381 108.769247 192.546488 108.365642 193.039315 108.646041 193.408935 108.357145 193.141279 107.34601 194.165169 106.15644 193.430177 104.924384 192.588973 104.57601"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-27",style:({display:_vm.props.displayDep['FR-dep-27']}),attrs:{"fill":"#6b6b6b","points":"96.6322429 45.5690547 98.1574561 45.2546682 100.205235 44.1543153 101.721952 45.6072909 102.707605 45.2631651 102.469689 45.8749443 102.886042 45.8749443 103.060231 46.3975057 104.891336 45.9429198 105.235465 46.1850824 105.524364 45.7092541 105.859995 46.5462021 106.862643 46.5419536 106.888134 47.2047144 107.572143 46.8563402 107.601882 48.1096379 106.55675 47.8844691 106.284846 48.5004968 107.24076 48.7171686 107.304487 49.2099907 107.963005 48.5472299 108.383607 49.2822147 108.243407 49.9322301 108.540802 49.6730736 109.14409 50.4760339 110.13824 50.2721074 109.879081 49.1929969 111.421288 48.8956042 111.837642 48.028917 112.623615 48.1818618 112.551391 47.6720458 113.85993 47.9099599 114.501454 45.6710179 114.977287 45.6922603 115.113239 45.1484565 115.856727 44.7533491 115.814242 45.1399596 117.454165 45.0634872 118.516291 45.6497756 119.179058 45.399116 119.697376 46.1425977 120.589562 46.4824751 121.022909 46.2827971 121.269323 46.6056806 121.621948 46.2615548 121.621948 46.2615548 122.076538 46.3082879 121.753652 46.6396683 122.450407 47.3789016 122.650087 49.3926748 123.09618 49.439408 123.206641 49.8557577 122.943233 50.6247302 122.395176 49.9152362 121.536978 50.1234111 121.435014 51.0113407 121.435014 51.0113407 120.912448 51.5593929 120.619301 53.7006201 119.756855 55.1535958 119.756855 55.1535958 118.244387 55.4042553 118.019216 54.9326755 117.645348 55.8673382 117.190758 55.510467 116.935848 55.7908658 117.471159 56.848734 117.32671 57.8726145 118.184908 57.8003906 117.717572 58.1062802 117.870519 58.8242711 117.870519 58.8242711 117.870519 58.8242711 117.870519 58.8242711 116.931599 59.2703601 117.343704 59.9841025 117.148273 60.6511118 115.414883 61.65375 115.597569 63.0769863 114.837086 63.6462809 113.566783 63.7100079 113.328867 63.3701305 112.954999 63.693014 111.174875 62.8560661 111.055917 64.0074006 110.146737 63.6845171 109.288539 64.5597012 109.288539 64.5597012 108.787215 64.5597012 108.787215 64.5597012 107.657113 64.9972933 106.849897 64.8273546 106.624726 65.5665879 105.775025 66.0381677 105.775025 66.0381677 104.984803 66.0551615 104.955064 65.3669099 104.220073 65.0567719 105.031537 64.2368178 104.751136 63.4423545 102.151051 61.7132285 102.626883 61.1141947 102.163796 61.1056977 102.257263 60.4939185 101.798425 60.6468633 101.64123 60.0860657 101.114416 60.5831363 98.8499624 60.2560044 98.1702016 59.5720012 98.7012647 58.5651146 98.7012647 58.5651146 99.2790614 56.6957892 97.8813033 55.8036112 98.3656329 55.1323534 98.8032289 55.1960804 98.467597 54.3676294 99.0411451 53.4372152 98.1914442 53.0930894 98.3571359 51.5721383 97.0528449 50.5567547 98.1659531 50.1234111 98.1659531 49.5498681 97.7963332 49.2354816 97.1590575 49.749546 96.8574136 49.6050982 97.1038269 49.2524754 96.6110004 48.729914 97.129318 48.0798986 96.708716 47.2344537"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-30",style:({display:_vm.props.displayDep['FR-dep-30']}),attrs:{"fill":"#6b6b6b","points":"161.914768 173.779285 162.611523 174.119163 162.581784 174.769178 163.15958 175.117552 162.658257 175.461678 162.968398 176.141433 162.586032 176.991126 162.862185 177.28427 164.234452 176.808442 164.463871 177.381985 165.09265 177.263028 166.481911 178.563059 167.034217 177.891801 167.021471 177.331004 167.701232 176.761709 168.861074 176.562031 168.924801 177.938534 169.672538 178.11697 169.940194 176.761709 170.857871 176.630007 170.976829 176.999623 171.626851 177.080344 171.796791 177.649639 173.398477 178.427108 173.398477 178.427108 173.814831 179.77812 174.418118 179.80786 174.66878 180.466372 174.379882 182.41217 174.651786 183.057937 175.815876 183.444547 176.831269 184.876281 176.780287 185.411587 176.274715 185.373351 176.304454 185.955391 175.00866 186.941035 175.00866 186.941035 173.262525 188.309042 173.717115 188.8401 173.288016 189.184226 173.436714 190.373797 172.752704 191.741803 173.02036 192.714702 171.469656 192.187892 170.505245 192.430054 169.455865 194.210162 170.26308 194.490561 170.080395 195.051358 167.82019 196.359886 167.467564 195.888306 167.641753 196.546819 166.197261 197.247816 166.010327 198.203721 166.010327 198.203721 164.400144 197.65142 164.0985 196.746497 164.400144 196.461849 163.69914 195.909549 163.69914 195.909549 163.856335 195.595162 163.372006 195.038613 164.612569 195.153322 165.368803 193.785315 164.480865 191.478398 162.891925 190.620208 162.071963 189.541097 161.324226 189.851235 161.587634 188.891082 160.63172 188.529962 160.474525 187.782232 159.32318 188.279302 158.490474 188.003152 158.906827 186.609655 158.320533 186.426971 158.197327 185.866173 157.228668 185.742968 156.383215 186.206051 156.158044 187.276664 155.31684 187.085483 154.509624 188.207078 154.89199 188.657416 154.518122 188.882585 153.625935 188.610683 153.354031 187.586802 151.926534 188.406756 151.731102 188.181588 152.100722 187.807723 151.790581 187.217186 150.63074 187.161956 150.63074 187.161956 150.609497 186.299517 151.463447 185.798198 152.21968 184.48967 151.14056 183.652722 150.482042 183.8524 150.057191 183.138658 149.470898 183.406311 148.914344 182.794532 149.963724 182.424915 150.193144 181.197108 150.860159 180.891219 150.860159 180.891219 152.13471 181.881111 153.8681 182.267722 155.452793 182.089286 155.818164 181.486004 155.376319 181.299072 155.550508 180.729777 156.14105 180.487614 158.354521 181.936342 158.949312 181.68993 159.735285 181.966081 160.657211 181.099394 160.593483 180.581081 161.485669 181.069654 161.184026 180.313427 160.8314 180.270943 161.018334 179.089869 161.506912 178.737246 160.780418 178.303902 160.593483 177.577415 161.107553 176.923151 159.934965 175.555144 160.487271 175.117552 161.05657 175.245006"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-42",style:({display:_vm.props.displayDep['FR-dep-42']}),attrs:{"fill":"#6b6b6b","points":"160.160136 128.333436 160.342822 129.081166 160.00719 129.896872 161.460178 130.194265 161.82555 130.967486 161.82555 130.967486 162.267394 130.967486 162.267394 130.967486 163.754371 130.291979 164.285434 130.840032 165.084153 130.925001 165.602471 130.402439 165.921109 130.827286 166.426681 130.576627 166.345959 131.31586 166.796301 131.37109 167.718226 130.640354 168.482957 130.653099 168.325762 130.279234 168.780352 129.748176 168.780352 129.748176 169.3539 129.990338 168.933298 130.546887 169.630053 131.26063 169.281676 131.889403 168.652897 131.549525 167.629007 132.038099 167.297624 133.563299 166.345959 134.090109 167.463316 135.198958 167.229648 135.623805 166.252492 135.475109 168.032615 137.115017 167.629007 137.777778 167.985882 138.423545 168.933298 138.890876 168.606164 140.798438 168.092095 141.146812 168.321514 141.614143 169.039511 141.78833 168.38949 143.640662 169.727769 144.821736 170.233341 145.773392 172.795189 146.032549 173.266773 146.835509 173.938037 146.393668 173.423968 147.128653 173.466453 148.237503 174.235433 148.500908 174.694271 148.063316 175.306056 149.044712 175.306056 149.044712 175.284813 151.283654 175.284813 151.283654 172.799438 152.634666 172.93539 153.144482 172.370339 153.586323 172.553025 154.164114 170.454263 154.529482 170.454263 154.529482 169.630053 153.560832 168.818589 153.977182 168.818589 153.977182 168.606164 153.896461 168.606164 153.896461 168.10484 153.560832 168.487205 152.889574 167.794699 152.481721 168.423478 152.001645 168.053858 151.364375 167.386843 151.130709 166.749567 151.474835 166.061309 150.697365 164.75277 150.697365 164.663551 151.304896 163.384751 151.470586 163.206314 152.031384 162.760221 151.534313 162.314128 151.695755 162.271643 152.231062 161.532403 151.037243 160.822903 151.134957 160.330076 152.099359 160.143142 151.496077 160.143142 151.496077 160.041178 150.438209 161.307232 149.529037 161.685349 148.029328 161.264747 147.761675 160.784666 145.964573 158.843099 144.622058 158.01889 143.118101 158.082617 142.272656 157.232916 141.635386 157.211674 141.023606 156.463937 140.607257 157.054479 140.042211 156.931272 138.614726 157.61953 138.21537 156.531913 137.102272 156.531913 137.102272 156.79532 136.006167 157.407105 136.205845 157.628027 135.768253 158.193078 136.010416 158.957809 135.356152 158.503219 133.983897 158.787869 133.091719 158.286545 132.501182 158.222818 131.383835 158.562698 131.2054 157.844701 129.268099 158.481977 129.229863 158.460734 128.783774 159.595085 128.677562 159.782019 128.333436 159.782019 128.333436"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-58",style:({display:_vm.props.displayDep['FR-dep-58']}),attrs:{"fill":"#6b6b6b","points":"143.884114 95.029705 144.674336 95.3568369 146.097585 94.5496283 146.488447 94.8470209 146.445962 95.794429 147.197948 96.3467297 147.881957 96.2277726 148.408772 97.1199507 149.131017 97.0732175 149.317952 96.7290917 150.418314 97.6382636 151.221282 96.6823586 152.912187 96.9924967 153.103369 95.2463768 153.366777 96.121561 154.539364 96.9032789 154.658322 97.9738925 155.168143 97.7104875 156.200529 98.1268373 157.113958 99.4736013 158.18883 99.2994141 158.724141 99.9791688 158.792117 99.380135 159.590836 98.5261932 160.079414 99.0572515 159.471878 99.6265461 159.697049 100.319046 160.963103 99.9239387 161.043825 99.5755645 161.460178 100.051393 161.45593 101.19423 161.986993 101.759276 162.428838 101.657313 162.709239 100.986055 163.822347 100.998801 163.822347 100.998801 164.46812 100.603693 163.958299 101.80601 164.302428 101.86124 163.962548 102.226608 164.302428 103.624353 165.096899 103.208004 165.967842 104.389077 165.649204 105.769829 165.649204 105.769829 165.266839 105.897283 165.266839 105.897283 163.988039 106.01624 164.017778 106.577038 164.017778 106.577038 162.832446 106.878679 162.624269 107.758111 163.24455 108.352897 162.951404 109.436256 161.834047 110.166992 162.105951 110.53236 162.62002 110.166992 162.917416 110.89348 162.603026 112.117038 163.703389 113.200397 162.709239 113.986364 162.90467 115.498818 162.539299 115.239661 161.889277 115.469079 160.665708 116.454723 158.35877 117.46161 158.112357 116.577929 157.309389 116.153082 155.937122 116.531195 155.393314 116.238051 155.393314 116.238051 154.815517 115.817453 154.620086 116.484462 154.620086 116.484462 154.824014 116.875321 154.824014 116.875321 153.95307 116.913557 154.186738 117.780245 153.388019 117.928941 152.801726 118.770137 152.109219 118.451502 152.274911 117.759002 151.714108 117.138726 150.983365 117.172714 150.414066 117.907699 149.823524 117.767499 149.598353 117.087745 149.041799 117.062254 147.860714 118.120122 146.569169 116.7946 145.320109 116.18707 144.848524 115.065474 144.848524 115.065474 145.51554 113.604002 145.167162 112.16802 145.698225 110.927468 145.422073 110.434646 145.668486 109.172851 144.610608 108.127728 144.835779 106.657759 143.913853 103.88351 143.973332 103.021071 142.171966 101.597835 141.96379 101.032789 143.076898 98.4454723 142.082748 96.2914996 142.082748 96.2914996 141.606915 95.8156714 141.772607 95.4715456 142.057257 95.666975 142.796497 95.1231713 143.569725 95.3525885"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-59",style:({display:_vm.props.displayDep['FR-dep-59']}),attrs:{"d":"M145.409327,25.393086 L145.689728,25.4525645 L145.689728,25.4525645 L145.991372,25.6734848 L145.511291,26.0770891 L145.596261,26.5359235 L144.954737,26.5741597 L144.49165,27.0542365 L144.351449,26.0133621 L145.018465,25.9751259 L145.328606,25.5460308 L145.328606,25.5460308 L145.409327,25.393086 Z M136.568189,0.951656559 L136.793359,2.42162607 L137.383901,2.76575188 L137.821497,3.96381951 L137.086506,4.73704046 L137.413641,5.34032275 L137.247949,6.69133518 L137.876728,7.72796108 L138.463022,7.46030767 L139.418935,7.78743961 L140.587274,10.1198479 L141.084349,10.421489 L141.59417,10.2260595 L142.647799,11.0800014 L143.459263,9.41885086 L145.260629,8.64138144 L145.706722,8.84105938 L146.271774,8.24202556 L146.960031,8.35673416 L147.665283,10.1580841 L148.888853,10.812348 L148.969574,11.513345 L148.510736,12.023161 L149.432661,15.7235756 L150.923886,16.6412444 L152.181444,16.2163977 L152.665773,15.4771645 L153.44325,15.7448179 L153.120363,16.7474561 L155.023694,16.5052935 L155.941371,17.5929009 L156.204778,19.0671189 L155.954116,20.0825025 L156.791072,21.7648953 L157.487826,20.4946038 L158.549953,20.4096344 L158.668911,20.7027786 L159.119252,20.4351252 L160.215367,21.161613 L162.377855,20.277932 L163.291284,21.6034536 L164.0985,21.9773187 L164.374653,23.0606777 L164.897219,23.0521808 L164.591327,22.5933464 L164.812249,22.2024874 L165.627962,22.6740672 L165.853132,23.2051256 L164.655054,24.3139754 L164.684794,25.6692363 L164.217458,26.4806935 L165.483513,26.4976873 L165.43253,27.2539144 L166.014576,28.2438072 L164.778261,28.8215987 L164.35341,29.607565 L164.812249,30.1131326 L164.455374,30.6951725 L164.455374,30.6951725 L163.469721,30.9840682 L163.032125,30.571967 L161.587634,30.3213074 L161.851041,29.4248809 L161.617373,28.9363073 L159.947711,29.8879638 L159.675806,29.1147429 L157.441093,28.7068901 L156.897284,28.2310618 L156.00085,28.6686539 L155.924377,29.3271662 L155.584496,29.0935005 L155.121409,29.4503717 L154.764535,28.9278103 L153.885094,28.6389146 L152.950423,29.6415527 L150.707213,29.1317367 L150.103925,29.7010313 L149.194745,29.7435159 L148.859113,29.2464453 L147.338148,29.8199884 L147.338148,29.8199884 L146.943037,29.3951417 L146.382235,29.4206325 L145.880911,28.6601569 L145.880911,28.6601569 L145.851172,27.8614452 L146.297265,27.632028 L145.906402,26.8163223 L146.577666,26.3617364 L146.067845,25.6989756 L147.053498,25.1806626 L146.671133,24.4541748 L147.610053,23.8593895 L146.900552,23.3878096 L146.909049,22.9417206 L145.141671,22.5253709 L144.49165,22.7462912 L144.555378,22.3384383 L145.264878,22.3894199 L145.269126,21.8541131 L145.80019,21.616199 L144.440668,20.277932 L144.020066,19.0883613 L145.511291,18.4383458 L145.532534,17.584404 L144.580869,17.966766 L144.415177,17.5759071 L144.801791,16.8366739 L143.739665,16.0889437 L142.545835,16.3863364 L142.210203,16.1654161 L142.295173,15.3667044 L141.220301,15.6895878 L140.812445,15.4644191 L140.667996,15.0140816 L141.012125,15.0055847 L141.02487,14.5977319 L140.60002,14.0199404 L141.504951,12.7921335 L142.002026,12.7581457 L140.944149,11.7087745 L140.187915,11.8702162 L140.102945,12.2653236 L140.60002,12.6774249 L140.060459,12.6264433 L139.805549,13.5143728 L139.219256,12.7496488 L138.530998,12.6009525 L137.774764,13.115017 L137.494363,12.6731764 L136.300533,12.8771028 L135.998889,12.405523 L135.229909,12.724158 L133.734436,11.3561517 L133.224615,11.6195567 L133.041929,11.2711824 L133.46678,10.9185596 L132.617079,9.7332374 L133.895879,9.00250111 L133.207621,8.49693357 L133.173633,8.84955631 L132.175234,8.44595196 L131.754633,8.69236304 L130.42485,7.64299174 L129.035589,3.48799123 L128.198634,2.28142666 L128.198634,2.28142666 L129.66012,1.89481619 L129.872545,2.39613527 L130.072225,1.70788365 L132.583091,0.926165759 L133.488022,1.10884983 L136.262296,-4.67181849e-13 L136.568189,0.951656559 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-60",style:({display:_vm.props.displayDep['FR-dep-60']}),attrs:{"fill":"#6b6b6b","points":"121.621948 46.2615548 122.968724 43.6529962 122.424916 43.4363244 122.165757 44.0396067 121.668682 43.6190085 122.229485 42.7183335 121.796137 42.5441464 121.626197 41.6562168 121.197098 41.5839929 121.749404 41.3418303 121.269323 41.0104499 121.749404 40.4071676 121.324553 39.837873 122.280467 38.4613698 122.148763 38.2616918 121.477499 38.8394833 121.175855 38.4613698 121.630445 38.1299894 121.609203 37.3822592 122.187 37.2590537 122.24223 36.5962929 122.875257 36.7577346 122.875257 36.7577346 123.839668 37.2420598 123.508285 37.7561243 123.988366 38.2829342 124.697866 38.372152 125.479591 37.7688697 125.768489 38.2914311 126.155103 37.790112 127.255466 38.0195292 127.820517 38.7927502 129.881042 38.2064618 131.011144 38.2914311 132.005294 38.8522287 132.238962 38.5973207 133.207621 39.5234865 134.520409 39.5999589 135.093957 40.475143 135.591032 39.973824 136.215563 40.611094 136.661656 40.5218762 136.738129 41.1251585 137.128991 40.6960633 137.702539 40.7555419 137.774764 41.6009867 138.140135 41.8049131 138.824145 40.3434406 140.536292 40.7088087 140.723226 39.3832871 141.589921 39.4045294 141.853329 38.7205263 142.401386 39.0604036 141.938299 38.2489464 142.244191 37.9345599 142.414131 38.3636551 142.788 38.0407716 143.493251 39.2558331 143.378542 38.4656183 143.909605 38.1597286 144.393934 38.2404495 144.784797 38.8692226 145.281872 38.4571213 145.218144 37.9430568 145.77045 37.9770446 145.766202 38.5718299 146.420471 38.1554802 146.420471 38.1554802 146.552175 39.3620447 146.033857 39.4979957 146.31001 39.9780724 145.944639 40.4241614 146.739109 41.7156953 146.526684 43.0454654 145.961633 43.3853428 146.492696 44.1415699 146.1953 44.6556344 147.214942 45.3651283 145.961633 45.5478124 145.987124 47.0772604 145.090689 48.3815397 144.325958 48.0586563 143.760907 48.4240244 144.062551 49.4733957 144.984477 49.5201288 145.018465 50.9858499 144.716821 51.4021996 144.342952 50.8881351 143.578222 51.1472916 143.739665 52.0819543 144.041309 52.2731353 144.177261 51.9799911 144.695578 52.6512488 144.937743 52.2688868 144.351449 52.1541782 144.432171 51.4319389 145.783196 52.060712 145.859669 52.9571385 146.080591 51.9205126 146.360992 52.2773838 146.360992 52.2773838 146.356744 52.498304 146.356744 52.498304 146.203798 52.7362182 146.896304 52.9273992 146.161312 53.2460342 146.263277 53.7091171 145.974378 53.398979 145.57077 54.0957275 145.57077 54.0957275 145.473055 54.936924 144.457662 54.7669853 144.079545 55.3150375 143.748162 54.7669853 143.259584 55.1535958 142.541586 54.9666632 141.683388 55.3362799 140.914409 54.6267859 139.924507 55.5826909 139.363705 55.1960804 138.943103 55.4977216 137.821497 54.3166478 137.069512 55.0813718 137.069512 55.0813718 136.500212 54.5843012 136.432236 53.8960496 136.045622 54.5503135 135.306383 54.3761264 135.544299 54.0702367 134.771071 53.4499606 134.316481 53.6496385 134.418445 53.3012643 133.271349 52.9486415 132.965456 53.2630281 132.192229 52.2306506 131.695153 52.9486415 130.667015 53.1653133 130.514069 52.4218316 129.668617 52.8466783 129.269257 52.1754206 129.065329 52.4303286 128.933625 52.0352212 128.360077 52.1329359 128.088173 51.5891321 126.694663 52.5152979 126.235825 52.307123 125.79398 52.6639942 123.754698 52.8296844 123.780189 52.4898071 123.346841 52.5790249 123.193895 52.2603899 122.102029 52.3750985 121.626197 51.6910954 122.07229 51.2152671 121.609203 50.7649296 121.435014 51.0113407 121.435014 51.0113407 121.536978 50.1234111 122.395176 49.9152362 122.943233 50.6247302 123.206641 49.8557577 123.09618 49.439408 122.650087 49.3926748 122.450407 47.3789016 121.753652 46.6396683 122.076538 46.3082879"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-62",style:({display:_vm.props.displayDep['FR-dep-62']}),attrs:{"d":"M128.100918,2.22194813 L128.202882,2.28142666 L128.202882,2.28142666 L129.039838,3.48799123 L130.429099,7.64299174 L131.758881,8.69236304 L132.179483,8.44595196 L133.177882,8.84955631 L133.21187,8.49693357 L133.908624,9.00250111 L132.629825,9.7332374 L133.479525,10.9185596 L133.054675,11.2711824 L133.237361,11.6195567 L133.747181,11.3561517 L135.242655,12.724158 L136.011634,12.405523 L136.313278,12.8771028 L137.507108,12.6731764 L137.787509,13.115017 L138.543743,12.6009525 L139.232001,12.7496488 L139.818295,13.5143728 L140.073205,12.6264433 L140.612765,12.6774249 L140.11569,12.2653236 L140.20066,11.8702162 L140.956894,11.7087745 L142.014772,12.7581457 L141.517697,12.7921335 L140.60002,14.0199404 L141.037616,14.5977319 L141.02487,15.0055847 L140.680741,15.0140816 L140.82519,15.4644191 L141.233047,15.6895878 L142.307919,15.3667044 L142.222948,16.1654161 L142.55858,16.3863364 L143.75241,16.0889437 L144.814536,16.8366739 L144.427922,17.5759071 L144.593614,17.966766 L145.545279,17.584404 L145.524037,18.4383458 L144.032812,19.0883613 L144.453414,20.277932 L145.812935,21.616199 L145.281872,21.8541131 L145.277623,22.3894199 L144.568123,22.3384383 L144.504396,22.7462912 L145.154417,22.5253709 L146.921795,22.9417206 L146.913298,23.3878096 L147.622798,23.8593895 L146.683879,24.4541748 L147.066244,25.1806626 L146.080591,25.6989756 L146.590411,26.3617364 L145.919148,26.8163223 L146.31001,27.632028 L145.863917,27.8614452 L145.893657,28.6601569 L145.893657,28.6601569 L145.651492,28.8895741 L144.563875,28.515709 L143.799144,28.9957858 L143.361548,28.6771508 L141.908559,29.5735773 L141.662146,29.2974269 L142.120984,28.8725803 L141.632406,27.9888992 L139.962744,29.0722582 L140.383346,26.9735156 L139.168273,26.6123959 L139.261741,27.1307089 L138.943103,27.3303868 L139.350959,27.7424881 L139.079055,27.9166752 L137.745024,27.1392058 L136.81885,27.0329941 L137.01853,26.3659849 L136.491715,26.2172885 L136.071113,27.0117518 L135.743979,26.2682701 L134.766822,26.5359235 L134.057322,27.7382396 L133.305337,27.1307089 L133.513514,25.9241443 L134.078565,25.2996197 L135.39985,24.7983006 L134.63087,23.8126563 L133.8364,23.6979477 L132.995196,24.4711687 L132.532109,23.5789906 L132.226217,23.7021962 L132.438642,24.2927331 L131.945815,24.3394662 L131.431746,23.8593895 L131.006896,24.364957 L130.420602,24.1440367 L129.303245,24.9172577 L128.950619,24.3861993 L128.168894,24.6326104 L127.795026,23.6936992 L128.0117,23.217871 L125.713259,22.2364751 L125.458348,21.9178401 L126.04889,21.3103094 L125.730253,21.0469044 L125.046243,21.5864597 L123.219386,20.2311988 L122.463152,20.2014596 L120.759502,21.0808922 L120.347397,20.469113 L120.347397,20.469113 L118.800941,19.1350944 L119.370241,15.3284682 L120.22419,16.0209682 L119.187555,14.4702779 L118.89016,10.8973173 L119.710121,8.76458697 L119.272525,6.08380443 L120.67878,5.59947922 L121.842871,4.23996985 L122.862512,3.71315997 L128.100918,2.22194813 Z M145.022713,25.9751259 L144.355698,26.0133621 L144.495899,27.0542365 L144.958986,26.5741597 L145.60051,26.5359235 L145.51554,26.0770891 L145.995621,25.6734848 L145.693977,25.4525645 L145.693977,25.4525645 L145.337103,25.5502792 L145.022713,25.9751259 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-69",style:({display:_vm.props.displayDep['FR-dep-69']}),attrs:{"fill":"#6b6b6b","points":"168.780352 129.748176 168.941795 128.150752 169.477107 127.653681 170.556227 128.010553 170.828132 128.545859 171.580117 128.380169 171.843524 127.853359 172.230138 128.503375 172.846171 128.596841 173.181803 127.696166 173.933789 127.564464 174.473349 128.265461 173.925292 128.76678 174.92369 129.416795 174.626295 129.565491 174.906696 129.947853 174.741005 130.772056 175.696918 130.865522 175.696918 130.865522 176.096278 131.498544 175.106376 133.083222 175.242328 136.040155 174.817478 136.809127 176.206739 137.365676 176.134514 137.960462 176.899245 137.539864 177.489787 137.96471 178.144057 139.34971 178.144057 140.309864 181.381418 140.042211 181.381418 140.042211 181.156247 140.806935 180.642178 140.603008 180.74839 141.1808 181.304945 141.138315 181.241217 141.903039 181.993202 142.098468 182.320337 142.846199 181.547109 142.833453 180.629432 143.772364 180.242818 145.05965 179.660773 144.821736 179.38462 144.932196 179.541815 145.306061 177.825419 145.259328 177.128664 145.616199 177.086179 146.007058 175.633191 145.743653 177.336841 147.183883 175.730906 148.628362 175.718161 149.031966 175.306056 149.044712 175.306056 149.044712 174.694271 148.063316 174.235433 148.500908 173.466453 148.237503 173.423968 147.128653 173.938037 146.393668 173.266773 146.835509 172.795189 146.032549 170.233341 145.773392 169.727769 144.821736 168.38949 143.640662 169.039511 141.78833 168.321514 141.614143 168.092095 141.146812 168.606164 140.798438 168.933298 138.890876 167.985882 138.423545 167.629007 137.777778 168.032615 137.115017 166.252492 135.475109 167.229648 135.623805 167.463316 135.198958 166.345959 134.090109 167.297624 133.563299 167.629007 132.038099 168.652897 131.549525 169.281676 131.889403 169.630053 131.26063 168.933298 130.546887 169.3539 129.990338"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-76",style:({display:_vm.props.displayDep['FR-dep-76']}),attrs:{"fill":"#6b6b6b","points":"115.691036 28.3840066 116.230596 28.5836845 116.944345 28.2268133 117.008072 29.0637613 118.095689 29.7817521 119.68463 31.7063076 120.984673 32.4030562 122.875257 36.7619831 122.875257 36.7619831 122.24223 36.6005413 122.187 37.2633022 121.609203 37.3865077 121.630445 38.1342378 121.175855 38.4656183 121.477499 38.8479803 122.148763 38.2659403 122.280467 38.4656183 121.324553 39.8421215 121.745155 40.411416 121.265074 41.0146983 121.745155 41.3503272 121.197098 41.5924898 121.626197 41.6647137 121.796137 42.5526433 122.229485 42.7268304 121.668682 43.6275054 122.165757 44.0481036 122.424916 43.4448213 122.968724 43.6572447 121.621948 46.2658033 121.621948 46.2658033 121.269323 46.6099291 121.018661 46.2870456 120.585313 46.4867235 119.693127 46.1468462 119.17481 45.4033645 118.512043 45.6540241 117.449917 45.0677356 115.814242 45.144208 115.856727 44.7575976 115.113239 45.152705 114.977287 45.6965087 114.501454 45.6752664 113.855682 47.9142084 112.547142 47.6720458 112.619367 48.1818618 111.829145 48.028917 111.412791 48.8956042 109.870584 49.1929969 110.133991 50.2721074 109.139841 50.4760339 108.532305 49.6688252 108.23491 49.9279816 108.37511 49.2822147 107.958757 48.5472299 107.300239 49.2099907 107.236511 48.7171686 106.280597 48.5004968 106.552502 47.8844691 107.597634 48.1096379 107.567894 46.8520917 106.883885 47.200466 106.854146 46.5419536 105.855747 46.5462021 105.520115 45.7092541 105.231217 46.1850824 104.887088 45.9386713 103.055982 46.3975057 102.881794 45.8749443 102.46544 45.8706959 102.703356 45.2589166 101.717703 45.6030425 100.200987 44.1500668 98.1532076 45.2504197 96.6279944 45.5648062 96.6279944 45.5648062 96.6279944 45.5648062 96.6279944 45.5648062 96.4070721 45.5733032 96.4070721 45.5733032 96.3348475 45.0677356 96.1606588 45.4161099 93.3863852 44.5621681 92.5451812 43.4830575 94.2700742 38.6780416 94.7926403 38.1469832 97.3162522 37.0381334 101.721952 34.0472128 105.014543 33.5628876 108.315631 32.165142 109.356515 32.4667832 110.890225 31.8592524"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-77",style:({display:_vm.props.displayDep['FR-dep-77']}),attrs:{"fill":"#6b6b6b","points":"145.57077 54.0957275 147.197948 54.5758043 146.998268 55.0091479 147.656786 55.8800836 147.244681 56.8954672 148.723161 57.9618323 148.956829 58.8625073 149.666329 58.5991023 150.091179 59.6867098 150.77094 59.1429061 151.042845 60.6511118 152.147456 60.9612499 152.41936 61.5687806 152.844211 61.1779217 152.844211 61.1779217 152.878199 62.1975537 151.399719 62.3377531 152.070983 62.4864494 152.104971 62.8263268 151.497435 62.9962655 151.242524 63.6760201 152.542567 64.2155754 152.691264 65.2734437 152.045492 66.1911125 152.389621 66.4885052 152.240923 66.951588 153.706657 66.6541954 153.460244 67.0110666 154.15275 67.4444102 154.15275 67.4444102 153.12886 67.7587967 153.345534 68.1539041 152.478839 68.6679686 152.763489 69.4326926 151.450701 69.7980608 152.003007 70.792202 151.042845 71.0768493 151.442204 71.7778463 151.191542 72.5383219 151.731102 72.7804845 151.599399 73.4602391 151.599399 73.4602391 150.919638 73.2010827 150.724207 73.931819 150.214386 73.9997944 149.271218 73.6854079 148.778391 74.1357454 146.773097 73.923322 146.118827 74.5520951 145.128926 74.2759448 144.563875 75.7119265 145.128926 77.1733991 144.372692 78.3034913 143.191608 78.9237674 143.187359 79.4590742 143.187359 79.4590742 141.938299 79.6417583 141.114089 80.3342584 140.791202 80.2875253 140.752966 79.3103779 140.11569 79.7139823 139.648355 79.3613595 139.962744 79.9264056 139.104546 80.5041971 136.525703 80.0411142 135.858688 80.4829547 134.486421 80.4914517 135.183176 79.4165896 135.595281 79.6332614 135.884179 78.4861754 135.582535 77.4835372 134.133795 76.9142426 133.789666 75.4357762 133.789666 75.4357762 133.585738 74.9217117 134.312232 74.9302087 134.499167 74.3311748 134.244256 74.2037208 135.208667 73.6429232 135.025981 73.4687361 136.304781 73.1628465 135.54005 72.4193648 135.748227 70.40984 135.45508 69.3392264 136.253799 67.8395176 135.718488 67.1300236 136.228308 67.0025696 136.011634 66.2760818 136.971796 65.8427382 136.746626 65.4603762 136.746626 65.4603762 136.980293 64.139103 137.485866 63.5698085 137.010033 63.2766643 137.154482 62.409977 137.154482 62.409977 136.767868 61.2374002 136.954802 60.7020934 136.517206 60.2560044 136.997287 60.0138418 137.294683 58.9814644 136.580934 57.7791483 136.925063 57.740912 136.687147 56.8869702 136.372757 56.912461 136.372757 56.912461 136.27929 56.5980745 137.13324 55.956556 136.695644 55.5911879 137.069512 55.0813718 137.069512 55.0813718 137.821497 54.3166478 138.943103 55.4977216 139.363705 55.1960804 139.924507 55.5826909 140.914409 54.6267859 141.683388 55.3362799 142.541586 54.9666632 143.259584 55.1535958 143.748162 54.7669853 144.079545 55.3150375 144.457662 54.7669853 145.473055 54.936924"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-78",style:({display:_vm.props.displayDep['FR-dep-78']}),attrs:{"fill":"#6b6b6b","points":"117.870519 58.8242711 117.717572 58.1062802 118.184908 57.8003906 117.32671 57.8726145 117.471159 56.848734 116.935848 55.7908658 117.190758 55.510467 117.645348 55.8673382 118.019216 54.9326755 118.244387 55.4042553 119.756855 55.1535958 119.756855 55.1535958 120.895454 55.1366019 121.779143 56.0330284 123.669728 55.183335 124.294258 55.701648 124.035099 56.3431665 124.472695 56.7680132 125.033498 55.9480591 125.560312 56.6490561 126.520475 56.6533046 127.068532 57.2863261 128.784928 56.7637647 128.776431 57.4477678 129.566653 57.6559427 129.732344 58.4758968 130.271904 58.5353753 130.203928 59.6867098 130.203928 59.6867098 129.626132 60.0605749 129.26076 60.7063419 129.485931 61.3053757 129.188536 61.4370782 129.502925 63.0684894 130.067976 63.4933361 130.067976 63.4933361 128.462041 64.2453147 128.355829 65.3371707 128.071179 65.677048 127.4424 65.6005756 127.161999 66.4162812 126.851858 66.3313119 127.752541 67.4911433 127.234223 67.7587967 126.966568 69.0503306 125.522076 68.9696098 126.27831 69.8830301 125.704762 70.1124473 125.28416 71.68438 125.28416 71.68438 125.020752 72.1602083 124.362234 72.1517114 123.899147 71.9860212 123.763195 71.4422174 123.159907 71.4549628 122.705317 70.218659 122.904997 69.1480454 122.378182 68.5957447 121.528481 68.5235207 121.634694 67.5293795 120.772247 67.5378765 120.355894 66.5862199 119.68463 66.2633364 119.824831 65.549594 119.204549 65.1757289 120.08399 64.0031521 119.285271 63.6207901 119.247034 61.7557132 119.582666 61.526296 119.204549 61.3860965 119.340501 60.9782437 118.656492 60.7020934 118.962384 60.1625381"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-80",style:({display:_vm.props.displayDep['FR-dep-80']}),attrs:{"fill":"#6b6b6b","points":"120.092487 20.277932 120.343148 20.4776099 120.343148 20.4776099 120.755253 21.0893891 122.458904 20.2099565 123.215138 20.2396958 125.041995 21.5949567 125.726004 21.0554014 126.044642 21.3188063 125.4541 21.9263371 125.70901 22.2449721 128.007451 23.2263679 127.790777 23.7021962 128.164646 24.6411073 128.946371 24.3946963 129.298997 24.9257546 130.416353 24.1525337 131.002647 24.3734539 131.427498 23.8678864 131.941567 24.3479631 132.434393 24.30123 132.221968 23.7106931 132.52786 23.5874876 132.990947 24.4796656 133.832151 23.7064447 134.626622 23.8211533 135.395601 24.8067975 134.074316 25.3081166 133.509265 25.9326412 133.301088 27.1392058 134.053074 27.7467366 134.762574 26.5444205 135.73973 26.2767671 136.066865 27.0202487 136.487467 26.2257855 137.014282 26.3744818 136.814602 27.0414911 137.740776 27.1477027 139.074806 27.9251722 139.346711 27.750985 138.938854 27.3388837 139.257492 27.1392058 139.164025 26.6208929 140.379097 26.9820125 139.958495 29.0807551 141.628158 27.9973961 142.116736 28.8810772 141.657897 29.3059239 141.904311 29.5820742 143.357299 28.6856477 143.794895 29.0042827 144.559626 28.524206 145.647243 28.8980711 145.889408 28.6686539 145.889408 28.6686539 146.390732 29.4291294 146.951534 29.4036386 147.346645 29.8284853 147.346645 29.8284853 147.028007 30.380786 147.890454 30.5804639 146.467205 32.1948813 146.416223 33.3037311 145.846923 33.8050502 145.642995 33.4396821 145.80019 34.4593141 145.269126 34.731216 145.821432 35.4746976 145.439067 36.4093603 146.0551 36.5198205 146.407726 38.0917532 146.407726 38.0917532 146.416223 38.1682256 146.416223 38.1682256 146.416223 38.1682256 146.416223 38.1682256 145.761953 38.5845753 145.77045 37.98979 145.218144 37.9558022 145.277623 38.4656183 144.780548 38.8777195 144.385437 38.2489464 143.901108 38.1682256 143.374293 38.4741152 143.489003 39.26433 142.779503 38.053517 142.405634 38.3764005 142.235694 37.9473053 141.929802 38.2616918 142.392889 39.073149 141.844832 38.7290232 141.581424 39.4130263 140.714729 39.3960325 140.527795 40.7173056 138.815648 40.3519375 138.131638 41.8134101 137.766267 41.6094837 137.694042 40.7640388 137.120494 40.7045602 136.73388 41.1379039 136.657407 40.5346216 136.211314 40.6238394 135.586784 39.9865694 135.093957 40.475143 134.520409 39.5999589 133.207621 39.5234865 132.238962 38.5973207 132.009543 38.8522287 131.015393 38.2914311 129.88529 38.2064618 127.824766 38.7927502 127.259714 38.0195292 126.159352 37.790112 125.772738 38.2914311 125.483839 37.7646212 124.702114 38.372152 123.992614 38.2786857 123.512533 37.7518758 123.843916 37.2378114 122.879506 36.7534861 122.879506 36.7534861 120.988921 32.3945592 119.688879 31.6978107 118.099938 29.7732552 117.012321 29.0552643 116.948593 28.2183164 116.234844 28.5751876 115.695284 28.3755096 115.695284 28.3755096 117.003824 27.1646966 117.556129 25.3760921 118.248636 24.4626717 119.0601 24.1950183 118.856172 24.4074417 119.39998 25.010724 119.816334 24.8322883 120.929442 25.3378559 121.167358 24.9767362 120.971927 24.4031932 120.007517 24.2587453 119.446714 23.1116593 118.758456 23.0521808 118.533285 22.6103402 118.822184 20.2099565"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-83",style:({display:_vm.props.displayDep['FR-dep-83']}),attrs:{"fill":"#6b6b6b","points":"192.984084 191.571864 193.349456 191.062048 194.211902 191.202248 194.568777 191.920238 195.46946 191.588858 195.503448 191.049303 195.911304 190.960085 196.115233 191.614349 196.990425 192.009456 197.606458 193.114058 199.097683 191.282968 200.023857 191.253229 201.013759 189.953198 201.629792 189.906465 202.972319 191.231987 203.979215 191.542125 204.527272 190.896358 204.540018 190.140131 205.772084 190.165622 206.349881 189.72803 206.774731 190.552232 207.594693 189.817247 208.223471 189.834241 208.418903 190.212355 208.418903 190.212355 208.835256 191.202248 209.319586 190.985576 209.799667 191.423168 210.513415 191.380683 210.891532 192.281358 210.674859 193.203275 211.320631 194.125193 213.304683 194.834687 212.709892 196.51708 213.011536 196.576558 212.816105 197.15435 213.606327 197.79162 213.606327 197.79162 213.440635 198.462877 212.94356 198.942954 212.357267 198.845239 212.297788 199.350807 210.326481 199.253092 209.863394 200.901497 207.547959 202.545654 207.692408 202.966252 208.59734 202.681605 208.945717 202.906774 209.187882 202.558399 209.532011 202.864289 208.96696 203.437832 209.247361 204.482955 208.499624 205.137219 208.130004 205.379381 207.807118 204.873814 207.131606 204.733614 205.993006 205.608798 204.017452 205.812725 203.592601 206.534964 203.73705 207.146743 203.08278 207.133998 202.071636 206.377771 200.843818 206.49248 200.062094 207.163737 199.977123 208.646452 198.919246 208.595471 198.889506 208.323569 199.522533 208.225854 199.433315 207.384658 197.614955 207.410148 197.266577 206.743139 195.936795 206.794121 195.877316 206.292802 195.231544 206.479734 195.172064 206.828108 195.681885 206.857848 195.414229 207.333676 196.174712 207.248707 196.3489 207.660808 195.350502 207.397403 194.789699 208.174872 194.241642 208.0984 193.693585 207.622572 193.884767 206.526467 193.315468 206.475486 193.247492 205.957173 191.88797 205.842464 191.883722 205.239182 191.450374 204.975777 191.450374 204.975777 191.654302 203.63751 193.026569 202.864289 193.069055 202.511666 192.436027 201.661973 191.450374 201.632234 192.036668 200.731559 191.641557 199.686436 192.835387 199.626957 193.515148 199.163874 191.934704 197.659917 192.385045 196.007264 191.169973 195.450714 191.803 195.204303 191.565084 194.545791 191.934704 193.755576 193.6511 193.360469 193.952744 192.638229 192.91186 191.809778 192.91186 191.809778"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-84",style:({display:_vm.props.displayDep['FR-dep-84']}),attrs:{"d":"M173.411223,176.965635 L175.845616,177.377737 L176.380927,179.484976 L177.455799,178.665022 L178.203536,178.690513 L180.633681,177.517936 L180.926828,178.099976 L181.491879,178.257169 L182.537011,177.3395 L182.180137,178.231678 L182.320337,179.417001 L183.633125,179.880084 L183.977254,179.387261 L185.49397,180.088258 L185.846596,179.80786 L186.369162,180.151985 L186.335174,181.256587 L187.261348,181.341556 L187.550247,182.10628 L188.374457,182.220989 L188.374457,182.220989 L188.480669,183.504026 L189.181673,183.414808 L188.548645,186.252784 L189.593778,186.490698 L189.831694,187.136465 L190.320272,187.127968 L189.241152,188.687155 L189.207164,189.536849 L189.742475,189.20122 L191.131736,189.315928 L192.992581,191.571864 L192.992581,191.571864 L192.899114,191.792784 L192.899114,191.792784 L192.25759,192.527769 L191.429132,192.544763 L190.23955,193.364717 L188.901271,193.351972 L187.065917,192.740193 L185.043629,191.440162 L183.607634,191.185254 L182.702703,191.49964 L180.467989,190.267585 L180.102618,189.281941 L178.78983,188.054134 L176.8865,187.119471 L175.000163,186.932538 L175.000163,186.932538 L176.295957,185.946894 L176.266218,185.364854 L176.77179,185.40309 L176.822772,184.867784 L175.807379,183.43605 L174.643289,183.04944 L174.371385,182.403673 L174.660283,180.457875 L174.409621,179.799363 L173.806334,179.769623 L173.38998,178.418611 L173.38998,178.418611 L173.411223,176.965635 Z M179.410111,174.722445 L179.618288,174.934868 L179.618288,174.934868 L179.902938,174.849899 L179.851956,175.414945 L180.837609,175.657108 L180.837609,175.657108 L180.833361,175.805804 L180.833361,175.805804 L179.992157,176.200911 L179.350632,177.86631 L177.629988,177.577415 L177.723455,176.736218 L177.353835,176.464316 L177.995359,175.304485 L177.995359,175.304485 L178.199288,175.007092 L178.199288,175.007092 L178.237524,175.032583 L178.237524,175.032583 L178.577404,174.888135 L178.577404,174.888135 L178.866303,174.722445 L178.866303,174.722445 L179.193438,174.637476 L179.193438,174.637476 L179.376123,174.658718 L179.376123,174.658718 L179.410111,174.722445 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-91",style:({display:_vm.props.displayDep['FR-dep-91']}),attrs:{"fill":"#6b6b6b","points":"130.067976 63.4933361 130.734991 63.3149005 131.440243 63.6377839 131.661165 64.3175386 132.047779 64.1306061 132.047779 64.1306061 132.498121 63.9649159 133.177882 64.2453147 133.237361 64.7296399 133.968104 64.776373 135.73973 64.20283 136.283539 65.4646247 136.746626 65.4603762 136.746626 65.4603762 136.971796 65.8427382 136.011634 66.2760818 136.228308 67.0025696 135.718488 67.1300236 136.253799 67.8395176 135.45508 69.3392264 135.748227 70.40984 135.54005 72.4193648 136.304781 73.1628465 135.025981 73.4687361 135.208667 73.6429232 134.244256 74.2037208 134.499167 74.3311748 134.312232 74.9302087 133.585738 74.9217117 133.789666 75.4357762 133.789666 75.4357762 132.990947 75.6439511 132.464133 74.9854387 132.141246 75.0916504 132.141246 75.0916504 131.945815 75.5419879 131.945815 75.5419879 131.36377 75.4485216 130.981405 75.8903621 130.989902 75.0066811 130.237916 74.6837976 129.855551 75.5504848 129.332985 75.4272793 129.541161 75.8903621 128.504526 75.6694419 127.378673 76.2642272 126.550214 76.2089972 126.550214 76.2089972 125.912938 75.588721 126.299552 75.3890431 126.261316 73.1798403 125.386124 73.0608833 125.641034 72.1262206 125.28416 71.68438 125.28416 71.68438 125.704762 70.1124473 126.27831 69.8830301 125.522076 68.9696098 126.966568 69.0503306 127.234223 67.7587967 127.752541 67.4911433 126.851858 66.3313119 127.161999 66.4162812 127.4424 65.6005756 128.071179 65.677048 128.355829 65.3371707 128.462041 64.2453147"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-95",style:({display:_vm.props.displayDep['FR-dep-95']}),attrs:{"fill":"#6b6b6b","points":"121.435014 51.0113407 121.609203 50.7649296 122.07229 51.2152671 121.626197 51.6910954 122.102029 52.3750985 123.193895 52.2603899 123.346841 52.5790249 123.780189 52.4898071 123.754698 52.8296844 125.79398 52.6639942 126.235825 52.307123 126.694663 52.5152979 128.088173 51.5891321 128.360077 52.1329359 128.933625 52.0352212 129.065329 52.4303286 129.269257 52.1754206 129.668617 52.8466783 130.514069 52.4218316 130.667015 53.1653133 131.695153 52.9486415 132.192229 52.2306506 132.965456 53.2630281 133.271349 52.9486415 134.418445 53.3012643 134.316481 53.6496385 134.771071 53.4499606 135.544299 54.0702367 135.306383 54.3761264 136.045622 54.5503135 136.432236 53.8960496 136.500212 54.5843012 137.069512 55.0813718 137.069512 55.0813718 136.695644 55.5911879 137.13324 55.956556 136.27929 56.5980745 136.372757 56.912461 136.372757 56.912461 135.306383 58.0722925 134.409948 58.4631514 133.118403 57.9490869 132.570345 58.3781821 131.911827 58.1445164 131.631426 58.57786 131.631426 58.57786 130.203928 59.6867098 130.203928 59.6867098 130.271904 58.5353753 129.732344 58.4758968 129.566653 57.6559427 128.776431 57.4477678 128.784928 56.7637647 127.068532 57.2863261 126.520475 56.6533046 125.560312 56.6490561 125.033498 55.9480591 124.472695 56.7680132 124.035099 56.3431665 124.294258 55.701648 123.669728 55.183335 121.779143 56.0330284 120.895454 55.1366019 119.756855 55.1535958 119.756855 55.1535958 120.619301 53.7006201 120.912448 51.5593929"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-03",style:({display:_vm.props.displayDep['FR-dep-03']}),attrs:{"fill":"#6b6b6b","points":"131.605935 124.667009 131.920324 123.379724 132.35792 123.375475 133.020687 122.19865 135.246903 121.790797 136.122095 122.134923 137.498611 121.259739 137.307428 120.206119 136.64891 119.840751 137.167228 119.059033 136.712638 118.553466 137.328671 118.234831 137.732279 118.561963 137.668551 117.750505 139.032321 117.024018 139.282983 116.340014 139.597372 116.284784 140.081702 117.066502 140.744469 116.607668 141.585673 116.841333 141.645152 116.331517 142.898461 115.116456 143.654695 114.823312 144.848524 115.065474 144.848524 115.065474 145.320109 116.18707 146.569169 116.7946 147.860714 118.120122 149.041799 117.062254 149.598353 117.087745 149.823524 117.767499 150.414066 117.907699 150.983365 117.172714 151.714108 117.138726 152.274911 117.759002 152.109219 118.451502 152.801726 118.770137 153.388019 117.928941 154.186738 117.780245 153.95307 116.913557 154.824014 116.875321 154.824014 116.875321 154.620086 116.484462 154.620086 116.484462 154.815517 115.817453 155.393314 116.238051 155.393314 116.238051 155.559005 117.32141 156.608386 118.553466 157.033236 119.37342 156.888787 119.840751 157.292395 120.01069 157.390111 121.633604 158.511716 122.14342 159.102258 121.820537 159.590836 122.903896 161.11605 122.814678 161.107553 123.192791 161.936011 123.596396 161.519658 125.210813 161.672604 126.986672 162.020981 127.02066 161.719337 127.343543 160.997091 127.169356 160.958855 127.704663 160.130396 127.891596 160.160136 128.333436 160.160136 128.333436 159.782019 128.333436 159.782019 128.333436 159.595085 128.677562 158.460734 128.783774 158.481977 129.229863 157.844701 129.268099 158.562698 131.2054 158.222818 131.383835 158.286545 132.501182 158.787869 133.091719 158.503219 133.983897 158.957809 135.356152 158.193078 136.010416 157.628027 135.768253 157.407105 136.205845 156.79532 136.006167 156.531913 137.102272 156.531913 137.102272 156.234517 136.401275 155.452793 136.30356 154.726298 134.740124 152.487336 135.135231 152.444851 133.818207 152.444851 133.818207 152.024249 133.818207 152.024249 133.818207 151.688617 133.44859 150.154907 134.187823 149.636589 133.661013 146.581914 133.554802 146.038106 133.210676 145.914899 132.420461 144.555378 132.730599 143.748162 132.186795 143.459263 131.005722 142.779503 130.818789 142.783751 129.90112 143.353051 129.217117 142.885715 128.966458 141.339259 129.064172 140.944149 130.355706 139.584627 129.688697 139.423184 130.721074 138.556489 130.971734 137.931959 132.322746 136.670153 131.70247 136.670153 131.70247 135.17043 128.222976 134.273996 127.815123 134.095559 128.146504 133.500768 126.914448 133.09716 127.454003 131.992549 126.617056 132.566097 125.950046 131.729141 125.644157"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-08",style:({display:_vm.props.displayDep['FR-dep-08']}),attrs:{"fill":"#6b6b6b","points":"176.669826 25.9113989 177.39632 25.9496351 177.740449 26.3914757 177.234877 26.858807 177.31135 27.7297427 176.94173 27.3558776 176.712311 28.2990372 176.393673 28.3585158 176.767541 29.097749 175.858361 30.9925652 175.883852 31.2814609 176.878003 31.5533628 177.600248 32.611231 176.873754 33.7795594 177.362332 35.0625964 176.954476 35.7890842 177.914638 35.9887621 179.490833 35.5979032 179.724501 36.0864769 181.236969 36.6132867 181.823262 37.9558022 182.435047 37.8963237 182.507271 38.5038545 184.346874 38.435879 185.425994 39.5744681 184.979901 40.2372289 185.115853 40.7342995 185.714893 40.1989927 186.560345 40.5898516 186.560345 40.5898516 186.245956 41.2271217 185.532207 41.2058793 185.081865 42.1362936 184.206673 42.6163703 183.722344 41.8686401 182.464786 42.0428273 182.107912 41.2908487 181.564103 41.248364 181.568352 42.1065543 181.168992 42.5993764 181.168992 42.5993764 181.168992 42.8033028 181.168992 42.8033028 180.620935 43.7294686 181.538612 44.8850516 181.610837 45.9259259 180.935325 46.3805119 181.360175 47.0390242 180.544462 47.3066776 180.064381 48.1946072 180.017648 48.8828588 180.616687 49.430911 180.467989 49.8472608 178.713357 50.8456505 178.713357 50.8456505 178.182294 50.4547915 178.229027 50.0639326 177.404817 50.1531504 177.213634 51.1005585 176.321448 50.5015247 175.233831 50.7436873 174.112226 50.2933498 173.122324 50.9900983 172.374587 49.877 172.485049 49.3799294 169.816987 49.885497 168.933298 49.3884264 168.640152 48.4919999 167.998627 48.7851441 167.888166 48.4537637 167.369849 48.4495152 166.299225 46.9115702 165.483513 46.8988248 165.309324 46.4229965 162.785712 46.2870456 162.785712 46.2870456 162.607275 45.4203584 163.146835 45.2249289 162.683748 44.6258951 162.666754 43.5637784 163.338018 43.0837016 163.278539 42.6121218 162.815451 42.531401 163.312527 41.8176585 162.352364 40.4369068 162.662505 40.0078117 163.966796 40.1352657 164.166476 38.9117073 165.203111 38.3424127 166.345959 36.7619831 165.572731 36.1374585 166.405438 33.7285778 166.447923 32.7556789 165.776659 32.4880255 166.03157 31.2559701 166.03157 31.2559701 167.403837 30.9713228 169.80849 31.8380101 170.964084 31.5703567 172.081441 30.6441909 174.052747 30.1726111 174.269421 28.7366293 173.925292 28.3160311 174.316154 27.5258163 175.165855 27.0797273 175.386777 26.3957241 176.470146 25.5162915"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-11",style:({display:_vm.props.displayDep['FR-dep-11']}),attrs:{"fill":"#6b6b6b","points":"127.183241 198.790009 127.582601 198.904718 127.935227 199.750163 129.66012 199.244595 130.437596 200.03481 130.560803 198.879227 131.206575 198.195224 131.635674 198.658307 132.052028 198.505362 133.645217 199.248844 135.025981 198.734779 136.746626 199.155377 136.746626 199.155377 137.120494 199.554733 136.555443 199.796896 136.173078 200.952479 137.001536 201.266865 137.55809 202.439442 138.564986 202.027341 138.802902 201.50478 139.125788 202.681605 140.289879 203.076712 141.084349 201.865899 140.893166 201.572755 141.993529 201.326344 141.968038 200.077295 142.371646 200.485147 142.371646 201.390071 143.361548 201.793675 144.385437 201.581252 144.500147 202.690102 145.256381 202.545654 145.706722 203.004488 146.807085 203.051221 147.061995 203.454826 147.712017 203.280639 148.009412 203.616268 148.009412 203.616268 148.41302 204.062357 148.41302 204.062357 148.531978 204.181314 148.531978 204.181314 146.705121 206.309795 145.456061 208.816391 144.988725 210.736698 145.383836 211.267756 145.077944 213.154075 145.077944 213.154075 141.908559 211.255011 140.578777 211.781821 139.682343 213.239045 135.501814 212.877925 132.621327 213.111591 132.311187 213.532189 132.714795 214.071744 132.910226 215.83061 131.93307 216.459383 131.342528 216.289444 130.08497 217.65745 129.549658 217.360058 129.549658 217.360058 129.668617 216.565594 128.160397 215.24857 125.742998 215.596944 125.135462 215.087128 125.373378 214.69202 124.855061 213.884812 124.192294 213.629904 124.45995 212.826944 126.422759 212.389351 126.312298 210.974612 125.492336 210.740946 125.917187 210.141912 126.779633 210.201391 126.401516 210.06544 126.410013 209.203001 126.129612 209.079796 126.410013 208.489259 125.636786 208.055915 125.636786 207.677802 126.019151 207.635317 125.755744 206.377771 125.173698 206.080378 124.727605 206.505225 124.553417 205.727756 124.132815 205.804228 124.047845 205.43886 124.043596 205.774489 122.858263 205.638538 121.570966 204.797341 121.804634 203.883921 121.180104 202.711344 121.180104 202.711344 121.494493 201.94662 122.000065 201.7257 121.770646 201.054442 123.372332 201.071436 123.159907 199.830884 123.597503 199.750163 123.431811 199.389043 123.86091 199.282831 124.056342 198.620071 124.952776 199.805393 125.207687 199.14688 125.861956 199.015178 126.456747 199.440025 127.340436 199.06616"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-12",style:({display:_vm.props.displayDep['FR-dep-12']}),attrs:{"fill":"#6b6b6b","points":"130.322886 169.904684 130.62453 169.743242 130.458839 169.105972 131.865094 168.634392 132.421648 168.58341 132.876238 169.322644 135.195921 169.071984 135.472074 168.124576 136.542697 167.355604 136.360012 166.531401 137.252198 165.575496 137.328671 164.241477 139.6611 161.870833 140.319618 162.597321 140.294127 163.990818 140.859178 163.527735 141.823589 163.531983 142.329161 165.639223 142.898461 165.533011 143.191608 166.017336 142.741266 166.331723 143.153371 166.75657 143.183111 168.345496 143.977581 169.224929 143.977581 169.224929 145.808687 171.32792 145.473055 172.717169 146.84957 174.306095 146.407726 176.14993 147.015262 177.505191 146.445962 178.078734 146.433217 178.626786 146.985522 178.312399 147.138469 179.06013 147.61855 178.936924 148.362038 179.408504 147.894702 180.398397 149.509134 179.999041 150.673225 180.143489 150.860159 180.88697 150.860159 180.88697 150.193144 181.19286 149.963724 182.420667 148.918592 182.790283 149.475146 183.402063 150.06144 183.134409 150.48629 183.848152 151.144809 183.648474 152.223929 184.485422 151.463447 185.793949 150.609497 186.295268 150.63074 187.161956 150.63074 187.161956 150.354587 187.642032 148.965326 187.629287 148.447008 188.402508 148.689173 189.213965 147.962679 189.64306 145.434818 189.069517 145.175659 189.876726 145.630249 190.688183 145.235138 191.261726 145.379588 192.532018 144.028563 192.149656 143.15762 192.502278 143.15762 192.502278 142.766757 191.380683 142.010523 191.410422 141.190562 190.866619 139.758816 191.661082 138.26759 190.981327 136.551194 188.844349 136.886826 188.062631 136.372757 187.67602 136.402497 186.877308 135.489068 186.388735 136.037125 185.607017 135.510311 185.386097 134.80081 183.826909 133.89163 183.68671 133.522011 182.747799 132.519363 182.029808 132.060525 182.140268 131.444492 181.439271 130.853949 181.783397 129.91503 181.68993 130.828458 181.099394 130.050982 181.086648 129.158796 180.13924 128.504526 180.330421 128.355829 180.789255 128.355829 180.44513 127.620837 180.428136 127.722801 180.835989 126.482238 181.405283 126.482238 181.405283 126.664924 181.188611 126.24857 180.674547 125.713259 180.615068 125.590052 180.954946 125.479591 180.457875 125.114219 180.542844 124.753097 179.999041 125.207687 179.884332 125.505082 179.072875 125.87895 179.119608 126.155103 178.388872 124.387725 177.913043 124.591653 177.624148 124.183797 177.216295 124.595902 176.761709 124.595902 176.761709 125.131213 176.319868 124.349489 175.34697 124.45995 174.705451 124.052093 174.416555 123.86091 173.277966 124.62989 173.197245 124.710611 172.678932 125.224681 173.12927 125.135462 172.700175 127.705807 170.750128 127.803523 171.187721 128.1519 170.643917 129.014347 171.085757 129.83006 170.427245 130.135952 170.694898"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-14",style:({display:_vm.props.displayDep['FR-dep-14']}),attrs:{"fill":"#6b6b6b","points":"96.6322429 45.5690547 96.708716 47.2344537 97.129318 48.0798986 96.6110004 48.729914 97.1038269 49.2524754 96.8574136 49.6050982 97.1590575 49.749546 97.7963332 49.2354816 98.1659531 49.5498681 98.1659531 50.1234111 97.0528449 50.5567547 98.3571359 51.5721383 98.1914442 53.0930894 99.0411451 53.4372152 98.467597 54.3676294 98.8032289 55.1960804 98.3656329 55.1323534 97.8813033 55.8036112 99.2790614 56.6957892 98.7012647 58.5651146 98.7012647 58.5651146 98.030001 57.9703293 97.3289977 58.7605441 96.8149286 58.4164183 96.2838655 58.5906054 96.279617 58.1147771 95.7273114 58.2847158 95.4936436 58.8455134 94.8861074 58.7520471 94.1128795 59.2533662 93.6030589 58.5311269 92.787346 58.9007435 92.34975 59.8481516 91.2621327 60.2772467 91.2748783 60.6468633 90.9095068 60.5236578 90.1830125 61.5050536 89.1931109 61.2926303 88.7682604 61.6197622 89.1633714 62.1423236 88.0205235 61.4668174 85.9982352 61.0334738 85.5776333 61.0802069 85.2717409 61.9766334 84.7831628 61.6410046 85.0168306 61.2883818 84.5749861 61.3606057 84.1756267 60.6808511 83.5510964 60.7190873 83.5510964 60.7190873 83.5510964 60.7190873 83.5510964 60.7190873 81.2823948 61.772707 79.9866009 61.8491794 80.0333344 61.4965567 79.1581424 61.3223695 78.9499657 61.581526 79.3408281 61.9851304 78.7885225 62.5671703 77.6754142 62.9027992 76.562306 63.8502073 76.562306 63.8502073 76.8809438 63.3276459 75.8103206 63.7439956 74.6037452 62.864563 74.2341253 63.3064035 72.2883101 63.1662041 71.9441812 62.1720629 70.941534 61.8491794 72.0249028 60.498167 72.4922383 60.689348 73.4014183 59.7291945 73.3504363 59.1726454 72.3902742 59.2916024 72.7344031 58.3441943 73.7625413 58.5608661 74.7057093 58.1445164 75.4364522 56.9039641 76.201183 56.4153904 75.6573744 55.9523075 76.0312429 55.3915099 75.8697997 54.0787337 75.0923233 54.4610957 75.7253505 53.7303594 74.4592961 52.8509268 75.0455897 52.7999452 75.2452695 52.0734574 75.7253505 51.805804 75.0455897 50.9816014 74.2001373 52.0819543 72.3605347 50.9221229 71.3408935 49.940727 71.6212948 49.6815706 71.2771659 48.8743619 71.7275074 48.4537637 71.6807739 47.9694384 71.6807739 47.9694384 71.6510343 47.5403433 72.4200137 46.7373831 74.6037452 46.5504505 76.8851923 47.6253126 84.4262884 48.2710796 87.4724664 49.6390859 91.3598484 48.4919999 93.6243014 46.427245 96.4113206 45.5775516 96.4113206 45.5775516"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-18",style:({display:_vm.props.displayDep['FR-dep-18']}),attrs:{"fill":"#6b6b6b","points":"130.870943 93.7041834 131.767378 93.4747662 133.220367 94.6091068 134.380209 93.9803337 135.323377 95.0594443 136.589431 94.9617295 137.536848 95.7689382 137.50286 96.2107788 138.237851 96.5761469 138.441779 97.2431562 139.435929 96.9797513 139.563384 95.8326652 140.893166 96.9627574 142.082748 96.2914996 142.082748 96.2914996 143.076898 98.4454723 141.96379 101.032789 142.171966 101.597835 143.973332 103.021071 143.913853 103.88351 144.835779 106.657759 144.610608 108.127728 145.668486 109.172851 145.422073 110.434646 145.698225 110.927468 145.167162 112.16802 145.51554 113.604002 144.848524 115.065474 144.848524 115.065474 143.654695 114.823312 142.898461 115.116456 141.645152 116.331517 141.585673 116.841333 140.744469 116.607668 140.081702 117.066502 139.597372 116.284784 139.282983 116.340014 139.032321 117.024018 137.668551 117.750505 137.732279 118.561963 137.328671 118.234831 136.712638 118.553466 137.167228 119.059033 136.64891 119.840751 137.307428 120.206119 137.498611 121.259739 136.122095 122.134923 135.246903 121.790797 133.020687 122.19865 132.35792 123.375475 131.920324 123.379724 131.605935 124.667009 131.605935 124.667009 129.626132 124.548052 129.626132 124.548052 129.34573 123.685613 130.271904 123.03135 129.451943 121.183267 129.995751 118.95707 129.107814 118.396272 129.383967 117.695275 128.194385 117.172714 127.850257 116.191318 128.343083 116.097852 128.725449 115.443588 127.421158 113.91414 128.003203 114.050091 128.360077 113.523281 128.139155 113.179155 128.908134 112.864769 129.396712 112.070305 128.610739 112.027821 127.922481 111.505259 128.559757 109.878096 127.161999 108.556823 127.625086 107.92805 127.523122 107.018878 126.7329 107.354507 126.571457 106.521808 125.313899 107.154829 124.485441 107.150581 122.705317 106.445335 123.899147 105.175044 123.856662 104.10443 123.856662 104.10443 124.447204 104.478295 124.927285 104.278617 125.309651 103.586117 124.812576 103.195258 125.636786 102.324322 127.098271 103.038065 127.998954 102.460273 128.933625 102.591976 129.451943 102.08216 128.772182 101.206976 128.670218 100.246822 128.368574 100.323295 128.691461 98.8915613 129.099317 99.252681 129.991503 98.4497208 130.280401 99.2909172 130.726494 99.2441841 131.028138 97.0519752 130.140201 97.1751807 130.458839 96.2532634 130.165692 95.6584781 128.929377 95.5140302 128.776431 94.6855792"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-19",style:({display:_vm.props.displayDep['FR-dep-19']}),attrs:{"fill":"#6b6b6b","points":"124.859309 142.943913 125.33939 142.553054 125.687768 142.795217 125.900193 142.268407 126.499232 142.378867 126.694663 141.660876 127.552861 141.253024 128.160397 142.157947 128.993104 142.026245 129.422203 142.327886 129.541161 141.962518 130.297395 142.493576 130.080722 142.918423 130.403608 142.587042 130.62453 143.00764 131.410504 143.105355 131.410504 143.449481 131.410504 143.449481 131.380764 143.793607 131.380764 143.793607 132.323932 143.678898 132.799765 142.663515 134.354717 142.901429 135.336122 141.932778 135.336122 141.932778 135.901173 142.642272 135.709991 143.738377 136.011634 143.938055 135.837446 144.388392 135.238406 144.388392 134.80081 145.514236 135.293637 146.397917 135.794961 146.563607 135.633517 148.462672 135.633517 148.462672 135.259649 149.953884 135.867185 150.387227 135.901173 150.888546 135.136442 151.185939 132.910226 150.060095 133.003693 152.018638 132.234714 152.396752 131.758881 153.229451 131.406255 153.157228 130.904932 154.223593 130.080722 154.899099 130.19968 155.905986 130.773228 156.228869 129.825811 157.010587 129.711102 158.378593 129.180039 158.272382 128.398314 158.930894 129.180039 160.388118 128.691461 160.795971 128.691461 160.795971 128.338835 160.778977 128.338835 160.778977 127.744044 161.02114 127.744044 161.02114 126.414262 161.063624 125.747247 161.607428 125.615543 161.093363 125.046243 160.978655 123.176901 162.342413 122.267721 161.981293 122.259224 161.496968 121.494493 161.18683 120.432367 159.712612 119.179058 159.393977 118.656492 159.682873 118.52054 159.249529 116.931599 159.920787 116.931599 159.920787 115.695284 157.792305 116.294323 157.299483 115.202458 156.900127 114.48446 157.104053 113.498807 156.330832 113.498807 156.330832 113.757966 156.330832 113.757966 156.330832 114.131834 155.710556 113.048466 155.319697 113.940652 154.39778 113.893918 154.015418 113.039969 153.62031 113.197163 152.401 113.706984 152.358516 114.764862 150.90554 113.596523 150.484942 113.583777 149.941138 113.936403 150.115325 114.204059 149.601261 113.494559 149.333607 113.494559 149.333607 114.068107 148.169528 115.198209 148.747319 116.213602 147.260356 116.825386 147.362319 117.008072 146.572104 117.454165 146.291705 117.717572 146.686813 119.306513 146.521122 119.531684 145.977319 120.279421 145.896598 121.549724 144.401137 122.743554 143.9508 122.892251 143.377257 123.707964 143.84034 124.438707 143.78511 124.574659 143.364512 125.071734 143.406996"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-21",style:({display:_vm.props.displayDep['FR-dep-21']}),attrs:{"d":"M164.017778,106.577038 L163.988039,106.020489 L165.266839,105.897283 L165.266839,105.897283 L165.075656,106.861685 L164.017778,106.577038 Z M167.106441,85.7298112 L167.488807,84.7739062 L171.728815,84.5997191 L171.397431,83.5843355 L172.259878,82.9513139 L173.763848,83.388906 L174.375633,83.2062219 L174.375633,83.2062219 L174.95343,83.6310686 L175.934834,83.6013294 L176.219484,84.0559153 L175.786137,84.7441669 L176.861009,84.7909001 L177.209386,85.3219584 L176.614595,86.1504094 L177.124416,86.4775414 L177.281611,85.9167438 L177.876401,85.8317744 L178.292755,87.1318053 L178.764339,87.2592593 L178.879048,87.9814986 L179.469591,88.4955631 L179.388869,88.9161613 L178.093075,89.8720663 L178.853557,90.0037688 L179.027746,91.9920513 L180.089872,91.3547812 L180.102618,91.8306095 L180.66342,91.7541371 L180.535965,92.4593826 L180.994804,92.3786617 L181.661819,93.0286772 L182.736691,92.1449961 L182.651721,92.8799808 L183.794568,93.8146435 L184.15994,93.6616987 L183.79032,94.3329565 L184.028236,94.8427725 L184.992647,93.9888306 L185.544952,93.9760852 L185.846596,94.4689074 L186.14824,94.0992908 L186.14824,94.0992908 L186.543351,94.3711927 L187.125396,93.4110392 L188.021831,94.065303 L188.179025,96.1258094 L187.295336,97.077466 L186.619824,96.8650427 L186.415896,97.3238771 L186.777019,97.4258403 L186.050525,97.7572207 L186.267198,98.1098434 L186.747279,97.8379415 L187.316579,98.1225888 L187.201869,99.6265461 L188.378705,99.7115154 L188.28099,101.147497 L187.843394,101.597835 L188.722834,101.933464 L188.722834,101.933464 L188.467924,102.46877 L188.179025,102.332819 L188.034576,104.168157 L187.418543,104.648234 L187.669205,105.039093 L187.31233,106.101209 L186.77277,106.504814 L186.364914,107.664645 L185.35377,107.834584 L184.576293,108.522836 L184.427596,109.100627 L185.18383,109.457498 L184.087715,110.307192 L184.087715,110.307192 L183.174287,110.175489 L183.012844,110.566348 L181.700056,110.749032 L181.292199,111.220612 L181.381418,110.884983 L180.442498,110.264707 L179.631034,110.502621 L179.537567,110.855244 L177.714958,110.64282 L177.693715,111.046425 L175.866858,111.53075 L175.709664,111.900367 L174.677277,111.8069 L174.065492,112.367698 L173.666133,111.972591 L173.916795,111.581732 L172.76545,111.301333 L171.928494,109.967314 L171.588614,109.988557 L171.711821,109.25782 L170.896108,109.45325 L170.54773,108.917943 L169.46861,108.811731 L169.009772,108.420872 L169.273179,107.783602 L169.031014,107.656148 L168.185562,108.153219 L168.041112,107.171823 L166.762312,107.001884 L166.252492,105.982252 L165.683192,106.058725 L165.640707,105.765581 L165.640707,105.765581 L165.959345,104.384829 L165.088402,103.199507 L164.29818,103.615856 L163.958299,102.218111 L164.29818,101.852743 L163.954051,101.797513 L164.463871,100.595196 L163.818099,100.990304 L163.818099,100.990304 L163.822347,100.025902 L163.35926,100.051393 L163.533449,99.6010553 L163.091604,99.2314387 L163.44423,98.8873128 L163.333769,98.3902422 L163.677898,98.6409018 L164.102748,98.1735704 L163.962548,96.4359475 L165.011928,95.475794 L165.011928,95.475794 L165.215857,94.7025731 L165.215857,94.7025731 L166.341711,92.6590605 L165.950848,92.1704869 L166.944998,92.0812691 L166.146279,90.9851646 L166.515899,90.5178333 L167.085199,90.743002 L167.815942,90.1312228 L167.76496,87.7733237 L166.71133,87.9857471 L166.592372,87.0808237 L166.277983,87.0723267 L167.433576,86.2566211 L167.106441,85.7298112 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-24",style:({display:_vm.props.displayDep['FR-dep-24']}),attrs:{"fill":"#6b6b6b","points":"102.499428 142.655018 102.907285 143.20307 104.101114 143.020386 105.05278 143.72988 104.640675 144.978929 105.061277 145.654435 105.59234 145.539727 105.711298 146.053791 106.289094 145.845616 106.709696 144.851475 107.21102 145.395279 108.064969 145.029911 109.343769 145.153116 110.533351 147.154144 111.068662 146.733546 111.960848 147.285846 111.157881 148.539144 111.892872 148.479666 112.15628 149.010724 112.717082 148.853531 113.192915 149.070202 113.163175 149.422825 113.494559 149.333607 113.494559 149.333607 114.204059 149.601261 113.936403 150.115325 113.583777 149.941138 113.596523 150.484942 114.764862 150.90554 113.706984 152.358516 113.197163 152.401 113.039969 153.62031 113.893918 154.015418 113.940652 154.39778 113.048466 155.319697 114.131834 155.710556 113.757966 156.330832 113.757966 156.330832 113.498807 156.330832 113.498807 156.330832 114.48446 157.104053 115.202458 156.900127 116.294323 157.299483 115.695284 157.792305 116.931599 159.920787 116.931599 159.920787 116.260335 160.264912 116.260335 160.264912 116.336808 160.596293 116.336808 160.596293 116.833883 162.503854 116.324063 162.652551 116.791398 163.493747 115.436125 164.326447 115.512598 165.133655 114.318769 165.50752 114.301775 166.004591 114.743619 166.280741 114.624661 166.888272 114.331514 166.803303 114.119089 167.478809 113.817445 167.325864 113.001732 168.281769 111.67195 168.621647 111.739926 169.484085 110.73303 170.507966 110.902971 171.005036 110.410144 170.992291 110.410144 170.992291 110.333671 170.431493 109.292787 169.968411 108.714991 169.241923 106.888134 169.947168 106.769175 170.406003 106.123403 170.325282 105.843001 169.662521 106.454786 168.630144 105.473381 167.83568 104.882839 168.387981 103.497827 168.451708 102.473937 167.695481 102.435701 168.039607 101.785679 167.861171 101.152652 168.689622 100.387921 168.383732 98.8032289 169.216432 97.7028661 168.957275 97.5711625 167.368349 96.6662309 166.28499 96.6662309 166.28499 96.0544462 164.725803 96.4538057 164.78953 96.9168927 164.237229 96.27112 163.706171 95.5871107 163.582965 95.6550868 163.965327 94.7459067 164.891493 93.9769274 164.657827 93.3311546 164.916984 93.1272264 164.560112 92.6386483 164.921232 91.7804503 164.160757 90.7905487 164.088533 91.9886271 162.550588 91.4235759 161.726385 92.6768849 158.667489 92.1798098 157.541645 91.2578842 157.452427 90.6928331 157.881523 90.6928331 157.881523 90.7438152 156.929866 91.3683454 156.454038 91.3683454 156.454038 91.0879441 156.186384 91.0879441 156.186384 91.466061 155.642581 91.466061 155.642581 92.3539985 154.7589 93.1272264 154.750403 93.7687506 155.281461 94.4697539 154.465755 94.4824994 153.913455 95.000817 153.841231 95.2982124 153.17847 96.0841858 153.004283 95.7485539 151.198684 96.254126 149.749957 96.9763718 149.507795 96.717213 148.942748 97.214288 149.376092 98.0979771 148.279988 98.9986601 148.275739 99.3045524 147.328331 100.281709 146.631583 100.396418 144.592318 100.73205 144.28218 100.880748 144.664542 101.35658 144.579573"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-26",style:({display:_vm.props.displayDep['FR-dep-26']}),attrs:{"d":"M176.083532,153.017028 L177.107422,152.698393 L177.472793,153.017028 L179.38462,151.8487 L179.75424,151.895433 L179.98366,152.494467 L180.531717,152.468976 L180.914082,153.395142 L182.005948,153.01278 L181.704304,154.253332 L182.384065,154.070648 L183.13605,154.996814 L182.51152,155.408915 L182.923625,156.207627 L182.859897,157.422688 L181.976208,158.58252 L182.494526,158.765204 L182.791921,158.302121 L183.301742,158.33186 L184.325632,159.054099 L185.595935,158.879912 L186.377659,159.555419 L186.407399,158.964882 L186.891728,159.279268 L187.732932,158.272382 L188.106801,158.327612 L188.306481,158.629253 L187.9666,158.658992 L187.788163,159.292014 L188.289487,160.490081 L187.775417,164.721554 L188.119546,164.81502 L187.660708,165.384315 L189.364358,166.178778 L189.258146,165.499024 L190.630413,166.539898 L190.995784,167.245143 L192.006928,167.126186 L192.937351,167.950389 L193.672342,167.695481 L193.672342,167.695481 L194.228896,168.090588 L193.506651,169.004009 L192.79715,168.791585 L192.440276,169.36088 L190.898069,169.088978 L191.00853,170.027889 L190.112095,171.701785 L191.284683,172.746908 L190.668649,172.785144 L190.201314,173.643334 L187.660708,172.806386 L187.775417,174.07243 L188.353214,174.378319 L188.000588,174.769178 L187.320827,174.425052 L186.97245,174.637476 L187.410046,175.45743 L187.231609,175.958749 L187.741429,176.047967 L188.255499,176.757461 L188.786562,176.451571 L189.253897,176.957138 L190.689892,176.876418 L190.316023,177.547675 L190.821595,177.683626 L191.00853,178.512077 L191.705284,178.512077 L191.51835,180.372906 L191.51835,180.372906 L191.0935,180.41539 L190.940554,180.920958 L191.637308,181.048412 L191.565084,181.46901 L190.732377,181.367047 L190.881075,180.9422 L190.350011,180.3899 L189.793457,180.44513 L189.555541,180.874225 L189.806203,181.328811 L188.391451,182.21674 L188.391451,182.21674 L187.567241,182.102032 L187.278342,181.337308 L186.352168,181.252338 L186.386156,180.147737 L185.86359,179.803611 L185.510964,180.08401 L183.994248,179.383013 L183.650119,179.875835 L182.337331,179.412752 L182.197131,178.22743 L182.554005,177.335252 L181.508873,178.252921 L180.943822,178.095728 L180.650675,177.513688 L178.22053,178.686264 L177.472793,178.660774 L176.397922,179.480728 L175.86261,177.373488 L173.428217,176.961387 L173.428217,176.961387 L173.704369,174.565252 L174.316154,173.724055 L174.192947,171.293932 L175.034151,170.588687 L175.730906,168.817076 L175.391026,166.030082 L176.844015,164.275465 L177.0267,162.90321 L177.591751,161.951554 L176.695317,160.205434 L177.137161,158.960633 L176.597601,158.60801 L176.130266,157.312228 L176.627341,156.496522 L176.223733,156.224621 L176.083532,153.017028 Z M178.233276,175.04108 L178.233276,175.04108 L178.195039,175.015589 L178.195039,175.015589 L177.991111,175.312982 L177.991111,175.312982 L177.349587,176.472813 L177.719207,176.744715 L177.625739,177.585912 L179.346384,177.874807 L179.987908,176.209408 L180.829112,175.814301 L180.829112,175.814301 L180.833361,175.665605 L180.833361,175.665605 L179.847707,175.423442 L179.898689,174.858396 L179.61404,174.943365 L179.61404,174.943365 L179.367626,174.675712 L179.367626,174.675712 L179.184941,174.654469 L179.184941,174.654469 L178.857806,174.739439 L178.857806,174.739439 L178.568907,174.905129 L178.233276,175.04108 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-28",style:({display:_vm.props.displayDep['FR-dep-28']}),attrs:{"fill":"#6b6b6b","points":"117.870519 58.8242711 118.962384 60.1625381 118.656492 60.7020934 119.340501 60.9782437 119.204549 61.3860965 119.582666 61.526296 119.247034 61.7557132 119.285271 63.6207901 120.08399 64.0031521 119.204549 65.1757289 119.824831 65.549594 119.68463 66.2633364 120.355894 66.5862199 120.772247 67.5378765 121.634694 67.5293795 121.528481 68.5235207 122.378182 68.5957447 122.904997 69.1480454 122.705317 70.218659 123.159907 71.4549628 123.763195 71.4422174 123.899147 71.9860212 124.362234 72.1517114 125.020752 72.1602083 125.28416 71.68438 125.28416 71.68438 125.641034 72.1262206 125.386124 73.0608833 126.261316 73.1798403 126.299552 75.3890431 125.912938 75.588721 126.550214 76.2089972 126.550214 76.2089972 125.985163 77.2838593 126.291055 78.401206 126.150855 78.719841 126.040393 78.4394422 125.759992 78.6263748 126.1636 79.1956693 125.305402 79.1829239 124.957025 79.548292 125.233178 79.9094117 124.591653 81.1839518 123.639988 81.6767739 123.168404 81.3878782 123.087683 81.7362524 122.15726 82.0888752 121.167358 81.7192586 120.844472 82.1058691 120.653289 81.7447494 120.041505 81.9189365 119.96928 82.6496728 119.59966 82.6114366 119.51469 82.9598109 118.652244 82.6071881 118.698977 82.9810532 118.057453 83.0532771 118.218896 84.2343509 118.218896 84.2343509 116.965587 83.5843355 116.736168 84.3872957 115.856727 84.2513448 115.555083 84.9650872 113.711232 84.7739062 113.379849 84.3193202 112.49616 84.608216 111.948103 83.0235379 111.213112 82.8451023 111.021929 81.6130469 110.350665 81.7999794 110.163731 81.4728475 108.906173 81.3751328 109.857838 80.6486449 109.747377 80.2790283 109.067617 80.3342584 108.141443 81.1712064 108.009739 80.7930928 106.221118 81.0352554 106.221118 81.0352554 106.395307 80.257786 107.538155 80.1133381 105.456387 78.6518656 105.456387 78.6518656 106.029936 78.1420495 105.286447 76.8930003 105.605085 76.1495186 104.708651 75.8691198 104.967809 75.2658375 105.27795 74.7687669 107.508415 73.931819 108.600281 72.138966 107.967254 71.7183678 107.895029 71.1788125 108.239158 71.0258677 108.00549 70.5542879 108.434589 69.9297633 107.691101 69.5771405 107.958757 69.2415116 107.720841 68.9568644 106.408053 68.3663275 106.331579 67.6440881 105.834504 67.5803611 106.063924 67.0025696 105.775025 66.0381677 105.775025 66.0381677 106.624726 65.5665879 106.849897 64.8273546 107.657113 64.9972933 108.787215 64.5597012 108.787215 64.5597012 109.288539 64.5597012 109.288539 64.5597012 110.146737 63.6845171 111.055917 64.0074006 111.174875 62.8560661 112.954999 63.693014 113.328867 63.3701305 113.566783 63.7100079 114.837086 63.6462809 115.597569 63.0769863 115.414883 61.65375 117.148273 60.6511118 117.343704 59.9841025 116.931599 59.2703601"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-31",style:({display:_vm.props.displayDep['FR-dep-31']}),attrs:{"fill":"#6b6b6b","points":"108.20517 190.242094 110.032027 189.982938 110.499363 189.562339 110.966698 190.038168 111.67195 189.396649 112.203013 190.055162 112.83604 190.110392 112.793555 190.730668 113.851433 190.305821 113.736723 189.978689 115.346907 189.524103 115.287428 189.065269 114.225301 188.831603 115.351155 188.687155 115.159973 188.249563 115.461616 187.760989 116.948593 188.224072 117.437171 187.297907 117.69633 187.820468 118.414327 186.949532 118.809438 187.034502 118.809438 187.034502 118.817935 188.275054 119.48495 188.874088 119.408477 189.528352 120.385634 189.931956 120.491846 191.155514 121.481748 191.97122 120.687277 192.251619 120.734011 192.527769 121.749404 192.650975 121.910847 193.398705 121.371287 193.60688 121.188601 194.163429 123.831171 195.340254 124.030851 196.037003 125.16945 197.196834 126.486486 197.766129 126.949574 197.528215 126.915585 197.018399 127.310696 197.239319 127.365927 196.937678 127.501879 197.570699 126.898591 198.441635 127.183241 198.790009 127.183241 198.790009 127.340436 199.06616 126.456747 199.440025 125.861956 199.015178 125.207687 199.14688 124.952776 199.805393 124.056342 198.620071 123.86091 199.282831 123.431811 199.389043 123.597503 199.750163 123.159907 199.830884 123.372332 201.071436 121.770646 201.054442 122.000065 201.7257 121.494493 201.94662 121.180104 202.711344 121.180104 202.711344 120.891206 202.52866 120.245433 203.599274 120.262427 203.131942 119.098337 202.677356 118.983627 203.187172 118.681983 202.600884 118.184908 202.80481 118.15092 202.286497 117.789797 202.248261 117.985228 203.96889 117.58162 203.845685 117.258734 204.279028 116.375045 203.854182 116.61721 203.178675 115.67829 202.337479 115.601817 201.806421 114.896565 201.67047 114.191313 202.333231 114.216804 202.881283 115.635805 203.395347 115.686787 204.075102 114.675643 204.71662 113.141933 204.754857 112.797804 205.379381 113.847185 205.727756 114.348508 206.356529 113.630511 206.662418 113.647505 207.091513 112.976241 207.223216 112.394196 206.127111 111.204615 205.566314 110.584333 206.080378 109.9683 205.778737 109.704892 206.887587 109.042126 206.539213 108.85944 207.070271 109.14409 207.312434 108.65976 207.554596 108.795712 208.659198 109.437237 209.04156 108.621524 209.925241 106.820158 210.303354 106.990098 210.915133 105.953463 211.33998 106.514265 213.523692 106.514265 213.523692 103.875944 212.610272 103.332135 212.767465 102.996503 213.166821 103.187686 214.012266 102.75009 214.488094 103.132455 214.683523 102.792575 215.231576 103.361875 215.919827 103.28965 216.6888 101.908886 216.340426 100.494134 216.676054 99.763391 216.480625 99.763391 216.480625 99.9120886 216.234214 99.2875584 215.677665 99.7931305 212.219413 101.292853 212.627266 101.964116 211.017097 102.779829 210.23113 102.21053 209.717066 102.452695 209.326207 102.104317 208.468017 100.702311 209.177511 100.868002 208.298078 101.343835 208.442526 101.365077 207.486621 100.230726 206.985302 100.026798 206.522219 99.393771 206.62843 99.5424687 206.360777 99.1516063 206.144105 100.583352 204.669887 100.506879 204.16432 101.492532 203.981636 101.10167 203.595025 102.588647 202.163292 102.104317 201.814918 102.104317 201.814918 103.123958 201.517525 103.986405 200.115531 104.339031 200.353445 104.891336 199.236098 105.609334 199.6482 105.843001 199.227601 107.555149 199.482509 108.804209 200.489396 109.11435 200.14527 108.893428 199.414534 109.547698 199.210607 109.339521 198.908966 109.811105 198.229212 109.318278 197.889334 110.303932 197.111865 109.913069 196.831466 110.367659 196.568061 109.913069 196.245178 110.580084 196.092233 110.796758 196.419365 111.01768 195.837325 112.551391 195.539932 112.258244 194.486312 111.833393 194.677493 111.523252 193.789564 110.567339 193.713091 110.822249 193.339226 109.887578 192.986604 110.19347 192.353582 108.850943 191.418919"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-33",style:({display:_vm.props.displayDep['FR-dep-33']}),attrs:{"fill":"#6b6b6b","points":"72.65793 146.397917 73.0360469 147.022441 72.6961665 147.043684 72.5602144 147.638469 74.9351286 149.431322 77.1910847 151.784973 79.2261185 158.234145 80.8830354 160.179943 81.0997091 160.02275 79.5872414 157.176277 78.7630315 152.243807 78.7630315 152.243807 80.8745384 152.422243 81.0189875 151.776476 81.4140985 153.029774 83.0455244 153.093501 83.9292134 153.518347 83.8697343 154.236338 84.2181117 154.440265 83.9589529 155.234728 84.6217197 156.844897 85.2037648 156.194881 85.8495376 156.976599 85.9599987 156.704697 86.4485768 156.857642 86.8266937 157.550142 88.0247721 158.059958 88.857479 158.178915 89.7114285 157.3887 90.6928331 157.877274 90.6928331 157.877274 91.2536357 157.448179 92.1755613 157.537397 92.6726364 158.658992 91.4193274 161.717888 91.9843786 162.542091 90.7863002 164.080036 91.7762018 164.156508 92.6343998 164.916984 93.1229779 164.555864 93.3311546 164.912735 93.9769274 164.653579 94.7416582 164.887244 95.6508383 163.961079 95.5871107 163.582965 96.2668715 163.706171 96.9126442 164.23298 96.4495572 164.78953 96.0501977 164.725803 96.6619824 166.28499 96.6619824 166.28499 95.9482336 166.59088 95.421419 166.225511 95.2642243 167.240895 94.9625805 167.296125 94.7246642 166.552643 94.503742 167.002981 93.9089513 166.939254 93.7857446 167.712475 93.5265858 167.508548 93.1867054 167.801693 93.8239812 168.664131 94.6779307 168.787337 94.3210562 169.492582 93.8834602 169.356631 94.0958855 170.05338 92.8765646 170.686401 92.6598908 171.540343 91.466061 171.510604 91.7974444 172.007675 91.1771627 172.708672 91.2238962 173.745298 91.6020131 174.021448 91.1771627 174.769178 91.8654204 175.814301 90.0555574 176.336862 89.9408477 176.744715 90.8117912 177.768596 90.8500278 178.371878 89.9068597 179.247062 89.278081 179.136602 88.9509461 179.480728 88.9509461 179.480728 88.0502631 178.350636 87.4299814 178.55881 87.4469754 180.037277 86.6227654 180.351663 84.5027615 179.97355 84.6004771 178.015007 83.7635217 177.666632 83.7422792 177.152568 82.2553025 176.753212 82.0938593 176.162675 80.0673225 175.219516 80.3477238 174.871141 80.0460799 174.000206 77.6796627 174.267859 77.1061146 174.760681 75.1602994 174.178641 73.2442237 174.696954 74.0684336 173.260972 73.979215 172.60246 73.2824602 172.623702 72.2670676 171.999178 71.8719566 172.738411 69.2633746 173.596601 69.2633746 173.596601 69.2166411 171.710282 70.3637374 168.83407 71.2134383 168.923288 71.2644204 169.301401 73.6733227 169.441601 73.3206968 168.375236 72.5177293 167.963134 73.1465081 167.954637 70.7928364 165.991846 70.6823752 166.743824 69.7179646 167.827183 69.3908298 169.964162 69.123174 169.522322 70.8778065 152.978792 70.9712736 148.539144 72.0716363 146.512625 72.6621785 146.079282"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-34",style:({display:_vm.props.displayDep['FR-dep-34']}),attrs:{"fill":"#6b6b6b","points":"150.63074 187.161956 151.790581 187.217186 152.100722 187.807723 151.731102 188.181588 151.926534 188.406756 153.354031 187.586802 153.625935 188.610683 154.518122 188.882585 154.89199 188.657416 154.509624 188.207078 155.308343 187.09398 156.149547 187.285161 156.374718 186.214548 157.220171 185.751465 158.18883 185.87467 158.312036 186.435468 158.89833 186.618152 158.481977 188.011649 159.314683 188.287799 160.466028 187.790729 160.623223 188.538459 161.579137 188.899579 161.315729 189.859732 162.063466 189.549594 162.883428 190.628705 164.472368 191.486895 165.360306 193.793812 164.604072 195.161819 163.363509 195.04711 163.847838 195.603659 163.690643 195.918046 163.690643 195.918046 162.441583 195.896803 160.266349 196.865454 158.354521 198.675301 155.660969 200.073046 153.422007 202.651866 151.85006 202.311988 150.439557 202.579642 148.540475 204.189811 148.540475 204.189811 148.417269 204.070853 148.417269 204.070853 148.013661 203.624764 148.013661 203.624764 147.716265 203.293384 147.066244 203.463323 146.811334 203.059718 145.710971 203.012985 145.260629 202.554151 144.500147 202.698599 144.385437 201.589749 143.361548 201.797924 142.371646 201.394319 142.371646 200.489396 141.968038 200.081543 141.993529 201.326344 140.893166 201.572755 141.080101 201.865899 140.28563 203.072464 139.12154 202.677356 138.798654 201.500531 138.560737 202.023092 137.553842 202.435194 136.997287 201.262617 136.164581 200.94823 136.546946 199.792647 137.111997 199.554733 136.738129 199.151129 136.738129 199.151129 138.429034 197.944564 138.203863 197.277555 138.26759 196.788981 138.539495 196.827218 137.50286 195.599411 138.033923 193.326481 139.346711 193.696098 139.966992 194.439579 140.999379 193.798061 142.67329 193.432693 143.153371 192.493781 143.153371 192.493781 144.024315 192.145407 145.375339 192.527769 145.23089 191.257478 145.626001 190.683935 145.171411 189.872477 145.43057 189.065269 147.95843 189.634563 148.684924 189.205468 148.438511 188.398259 148.956829 187.625039 150.350338 187.637784"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-35",style:({display:_vm.props.displayDep['FR-dep-35']}),attrs:{"d":"M54.1344488,66.7476616 L54.4106017,67.0280604 L55.2772966,66.8708672 L55.1413445,67.2107445 L55.5279584,67.1852537 L55.5619465,67.8012814 L56.1609857,68.3450851 L55.9740514,68.7784288 L55.9740514,68.7784288 L55.3197817,69.4411896 L55.2900422,68.8421558 L54.4573352,68.6977079 L53.8965326,67.9074931 L53.8965326,67.9074931 L53.4249485,67.015315 L54.1344488,66.7476616 Z M58.8630347,65.0822627 L59.0117324,65.7875081 L58.3659597,66.6796862 L58.9989869,67.5463734 L61.3908951,67.6908213 L63.6553482,66.9855758 L63.6553482,66.9855758 L65.1253309,70.8474321 L65.7965947,70.8984137 L66.3106637,71.5951622 L67.5002451,71.3827389 L67.9463381,70.5882756 L68.8172816,70.3078768 L69.2633746,69.3944564 L72.5049838,70.3418645 L72.5049838,70.3418645 L72.3095526,72.8272176 L72.9425798,75.1001473 L71.9441812,76.8377702 L72.8491127,81.3836297 L73.3674303,81.9614212 L73.1550051,82.9300716 L73.4524004,83.6862987 L72.8661067,84.1833693 L71.5575672,84.1238908 L71.5278277,84.5274951 L71.0095101,84.6931853 L70.6313932,86.4435536 L70.2830158,86.64748 L70.4402105,87.2422654 L69.8326743,87.6628636 L69.8624138,88.4913146 L69.5437759,88.758968 L69.5437759,88.758968 L69.3908298,89.6299037 L69.3908298,89.6299037 L67.3345534,89.0393668 L66.8969574,88.2788913 L65.2995196,88.0919587 L65.4014837,88.9374036 L62.7716592,89.9697811 L62.138632,91.3675266 L61.5056048,91.2953027 L61.0170267,91.6904101 L58.5741364,91.4057628 L56.9002255,92.3786617 L56.5773391,91.7923733 L54.3723651,93.428033 L54.3723651,93.428033 L53.824308,91.0276493 L54.1471944,90.8747045 L54.1471944,90.8747045 L54.2194189,90.6622812 L54.2194189,90.6622812 L55.0903624,90.615548 L55.0266349,90.2799191 L53.8625445,89.7786001 L53.8625445,89.7786001 L54.1302003,89.7786001 L54.1302003,89.7786001 L55.4897219,88.1344434 L55.162587,87.2040292 L54.5635478,87.8837839 L54.1684369,87.7435845 L54.4148502,87.3994587 L54.0792183,86.6347346 L54.7292395,85.8785076 L53.5609007,84.1918662 L53.0425831,84.3108233 L51.9209778,83.779765 L51.0160463,84.0049337 L51.4621393,82.7261452 L51.8105167,82.3735225 L52.7749273,82.3820194 L53.1360502,81.7702402 L52.2226217,81.6300408 L51.5980914,82.1016206 L51.627831,80.9417892 L51.0415373,80.2237983 L51.0415373,80.2237983 L51.3261871,79.7904547 L51.9464688,79.9731387 L52.0696755,78.2057765 L52.8726429,78.2227704 L52.8556489,77.1946414 L53.4164515,76.8845034 L54.2151704,77.1436598 L54.4190987,76.7655463 L54.1302003,76.5361291 L55.1413445,75.9243499 L55.6851531,76.361942 L56.5433511,75.8181382 L56.8407464,76.0730462 L56.6538122,74.7305307 L57.359064,74.6073252 L56.9427105,72.0837359 L57.6819504,71.0513585 L57.5587437,69.6196252 L57.3293245,69.2457601 L56.9554561,69.5601466 L56.9554561,69.5601466 L56.7090428,69.6196252 L56.3309258,68.3493336 L56.9172195,68.4852845 L55.8763358,67.8310207 L56.06327,67.5293795 L55.5152129,67.015315 L55.557698,66.569226 L56.2714468,66.2081063 L56.3139318,65.6685511 L57.0404262,65.7237811 L56.8152554,65.549594 L57.2443544,65.2012197 L57.7669205,65.5326001 L58.8630347,65.0822627 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-36",style:({display:_vm.props.displayDep['FR-dep-36']}),attrs:{"fill":"#6b6b6b","points":"114.794601 105.005105 116.948593 103.615856 117.904507 103.696577 118.40583 104.125672 118.282624 103.199507 119.480702 103.131531 119.467956 102.642957 119.812085 102.596224 120.79349 103.123034 121.655936 102.630212 122.72656 103.781547 123.856662 104.10443 123.856662 104.10443 123.899147 105.175044 122.705317 106.445335 124.485441 107.150581 125.313899 107.154829 126.571457 106.521808 126.7329 107.354507 127.523122 107.018878 127.625086 107.92805 127.161999 108.556823 128.559757 109.878096 127.922481 111.505259 128.610739 112.027821 129.396712 112.070305 128.908134 112.864769 128.139155 113.179155 128.360077 113.523281 128.003203 114.050091 127.421158 113.91414 128.725449 115.443588 128.343083 116.097852 127.850257 116.191318 128.194385 117.172714 129.383967 117.695275 129.107814 118.396272 129.995751 118.95707 129.451943 121.183267 130.271904 123.03135 129.34573 123.685613 129.626132 124.548052 129.626132 124.548052 128.219876 124.917669 126.286807 124.072224 125.458348 124.373865 124.62989 124.106212 123.822674 124.514064 122.832772 123.728098 122.208242 123.88954 122.429164 124.522561 121.881107 125.418988 121.116376 124.700997 120.109481 125.474218 119.773849 124.624525 118.63525 125.359509 118.299618 124.488574 116.349554 126.544832 116.349554 126.544832 115.266185 125.176825 114.548188 125.300031 114.378248 125.899065 113.588026 125.724878 112.878525 126.043513 112.712834 125.423236 112.38145 125.767362 112.164777 125.554939 112.164777 125.554939 112.759567 124.310138 112.249747 124.450337 112.334717 124.127454 111.680447 123.932025 111.400046 123.349985 111.799405 123.388221 111.395797 122.678727 111.603974 122.4748 110.660806 121.684586 109.36926 121.697331 109.275793 120.911365 108.379359 120.800905 107.444688 120.03618 107.12605 119.313941 107.737835 117.567821 107.487173 116.815843 106.692702 116.272039 106.692702 116.272039 107.725089 116.038373 108.285892 116.543941 108.727736 115.859938 109.220563 116.004386 108.753227 114.831809 109.195072 114.559907 109.658159 111.352314 110.159482 110.940213 109.993791 109.899339 110.826498 109.083633 111.990588 108.752253 113.107945 109.440504 113.498807 109.215336 114.161574 107.915305 114.501454 107.915305 114.526945 107.159078 115.440374 106.759722"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-37",style:({display:_vm.props.displayDep['FR-dep-37']}),attrs:{"fill":"#6b6b6b","points":"102.261512 91.7456402 102.66512 91.3590297 102.818066 92.026039 103.268407 91.6224346 103.918429 92.0982629 104.424001 91.7328948 106.029936 92.2384623 106.684205 91.8730942 106.267852 93.0669134 106.594987 94.2607325 107.253505 94.1247816 107.610379 93.3940453 108.872185 93.7126803 108.974149 94.647343 109.611425 94.0185699 110.13824 95.0849351 110.388902 94.9447357 109.815353 96.0026039 110.252949 96.2277726 110.554593 97.8082023 111.009183 97.7189845 111.383052 98.160825 111.026177 98.7088772 110.512108 98.6536472 111.187621 99.6605338 110.851989 100.229828 111.174875 100.654675 110.73303 101.291945 110.868983 101.882482 111.897121 102.723678 112.118043 102.337068 113.32037 102.366807 114.794601 105.005105 114.794601 105.005105 115.440374 106.759722 114.526945 107.159078 114.501454 107.915305 114.161574 107.915305 113.498807 109.215336 113.107945 109.440504 111.990588 108.752253 110.826498 109.083633 109.993791 109.899339 110.159482 110.940213 109.658159 111.352314 109.195072 114.559907 108.753227 114.831809 109.220563 116.004386 108.727736 115.859938 108.285892 116.543941 107.725089 116.038373 106.692702 116.272039 106.692702 116.272039 105.766528 115.218419 105.435145 113.66348 104.661917 113.408572 103.816465 112.286977 103.591294 110.468633 102.575901 110.154247 102.287003 109.597698 101.407562 109.635934 101.947122 110.897728 100.311448 110.855244 99.1303637 111.611471 97.8388183 111.097406 97.1038269 111.581732 96.5345273 110.03529 96.9126442 109.831363 96.742704 108.467605 96.1309193 108.688526 96.1096768 108.047007 95.7230628 107.940796 95.1580117 108.395382 94.546227 108.263679 94.9285924 107.422483 94.6057061 106.810703 94.2573287 107.176072 93.7772476 107.01463 93.8707147 106.555795 92.812837 106.70874 92.795843 106.01624 92.34975 105.548909 92.34975 105.548909 92.5834178 104.898893 92.3285075 104.733203 92.787346 104.163909 92.753358 102.507007 93.9769274 100.816117 93.8494722 100.433755 94.6057061 99.9111933 94.0873885 99.4523589 95.2982124 96.8310549 95.378934 96.1258094 94.8138828 95.8156714 95.5021406 94.3839381 95.5021406 94.3839381 95.421419 93.9973276 95.421419 93.9973276 95.9694761 93.9420975 98.072486 95.0551958 98.5058335 94.7917909 97.8260727 93.7211772 98.1574561 93.0966526 98.879702 93.8061466 99.3215465 93.7339226 99.5807053 93.0626649 100.515376 92.7610237 100.515376 92.7610237 100.884996 92.7482783 100.884996 92.7482783 101.908886 92.3574194"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-38",style:({display:_vm.props.displayDep['FR-dep-38']}),attrs:{"fill":"#6b6b6b","points":"181.381418 140.042211 181.470636 140.445815 181.615085 140.114435 182.22687 140.241889 182.923625 141.074588 183.522664 141.172303 184.346874 140.594511 184.907677 139.18402 185.791366 138.283345 187.180627 139.481413 187.044675 140.23764 189.334619 142.672012 189.156182 143.09261 190.158829 143.946552 190.592176 145.080892 190.592176 145.080892 191.441877 146.839757 191.441877 146.839757 191.441877 147.115908 191.441877 147.115908 192.138632 147.748929 192.605968 149.431322 193.498154 149.41008 195.265532 150.650632 195.592666 150.688868 195.919801 150.081338 195.477957 150.230034 195.694631 148.513653 196.608059 148.131291 198.073793 149.512043 199.518285 149.571522 200.601654 151.198684 200.202294 151.479083 200.644139 151.606537 200.486944 152.451982 199.505539 153.284682 199.679728 153.883715 199.352593 154.334053 200.053597 155.710556 199.739207 156.568746 200.38498 156.301093 201.132717 156.921369 201.612798 156.649467 201.803981 157.265495 201.803981 157.265495 201.289911 157.830541 201.519331 158.658992 201.085983 158.811937 200.814079 160.014253 201.646786 160.502827 202.921337 160.447597 202.900095 161.564943 203.528873 161.921815 203.48214 164.020557 202.895846 164.084284 202.432759 163.544729 201.570313 164.092781 199.573515 163.786891 198.205497 164.984959 197.602209 164.458149 196.319161 165.261109 196.858721 165.838901 196.344652 166.361462 195.142325 166.709837 194.156672 166.386953 193.659597 167.699729 193.659597 167.699729 192.924605 167.954637 191.998431 167.130435 190.987287 167.249392 190.621916 166.544146 189.253897 165.503272 189.355861 166.187275 187.652211 165.392812 188.111049 164.819269 187.76692 164.730051 188.276741 160.498578 187.775417 159.300511 187.953855 158.66324 188.293735 158.633501 188.094055 158.33186 187.724435 158.27663 186.878983 159.283517 186.394653 158.964882 186.369162 159.555419 185.583189 158.884161 184.317135 159.058348 183.293245 158.33186 182.787673 158.302121 182.490277 158.765204 181.97196 158.58252 182.859897 157.422688 182.923625 156.207627 182.51152 155.408915 183.13605 154.992565 182.384065 154.066399 181.708553 154.249083 182.005948 153.008531 180.914082 153.390893 180.531717 152.464727 179.98366 152.490218 179.75424 151.891184 179.38462 151.844451 177.472793 153.017028 177.107422 152.694145 176.083532 153.017028 176.083532 153.017028 175.40802 152.367013 175.284813 151.283654 175.284813 151.283654 175.306056 149.044712 175.306056 149.044712 175.718161 149.031966 175.730906 148.628362 177.336841 147.183883 175.633191 145.743653 177.086179 146.007058 177.128664 145.616199 177.825419 145.259328 179.541815 145.306061 179.38462 144.932196 179.660773 144.821736 180.242818 145.05965 180.629432 143.772364 181.547109 142.833453 182.320337 142.846199 181.993202 142.098468 181.241217 141.903039 181.304945 141.138315 180.74839 141.1808 180.642178 140.603008 181.156247 140.806935"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-39",style:({display:_vm.props.displayDep['FR-dep-39']}),attrs:{"fill":"#6b6b6b","points":"188.722834 101.94196 189.759469 103.241991 190.889572 103.19101 191.497108 102.655703 191.934704 102.927605 191.934704 102.927605 192.588973 104.57601 193.430177 104.924384 194.165169 106.15644 193.141279 107.34601 193.408935 108.357145 193.039315 108.646041 192.546488 108.365642 192.852381 108.769247 192.682441 109.240826 193.001078 109.491486 193.506651 109.245075 193.846531 108.590811 193.931501 109.34279 194.513546 109.733649 195.380241 109.912084 195.843328 109.372529 195.660643 110.107514 196.786496 110.417652 196.450864 111.110152 197.134874 111.445781 196.905454 112.16802 197.368541 112.482407 197.321808 113.060198 197.733913 113.009216 197.733913 113.009216 197.678682 113.298112 197.678682 113.298112 197.776398 113.659232 198.349946 113.38733 199.875159 114.377223 200.848067 115.855689 198.447662 117.780245 199.067943 118.740398 198.073793 119.874739 199.64574 121.149279 199.64574 121.149279 199.926141 121.697331 198.490147 123.575153 198.723815 124.063727 198.349946 124.764724 198.349946 124.764724 194.908657 128.618083 192.363803 128.715798 192.159875 127.483743 190.974542 127.105629 190.910814 126.583068 190.078107 127.82362 189.551293 127.861856 189.07546 128.520369 187.924115 128.62658 187.630968 128.367424 187.953855 127.322301 187.278342 127.326549 187.053172 126.498098 186.692049 127.449755 186.675055 126.736013 185.944312 125.928804 186.220465 125.622914 184.848198 124.836948 185.281545 124.446089 185.120102 123.991503 185.120102 123.991503 185.285794 123.562408 186.929965 123.209785 186.997941 122.55977 185.910324 122.117929 185.957057 120.626717 186.339423 120.5375 186.339423 120.5375 186.700546 120.533251 186.700546 120.533251 186.85774 120.061671 186.551848 119.798266 187.354816 119.101518 186.539103 117.890705 186.768522 117.563573 186.492369 116.777606 185.927318 116.743619 186.466878 115.702744 185.574692 115.184431 185.341024 114.547161 186.77277 114.088327 187.741429 114.232775 187.707441 113.55302 186.789764 113.293864 186.611327 112.567376 185.379261 112.69483 184.852446 111.441532 184.232164 111.632713 184.019739 111.237606 184.091964 110.31144 184.091964 110.31144 185.188078 109.461747 184.431844 109.104875 184.580542 108.527084 185.35377 107.843081 186.364914 107.673142 186.77277 106.513311 187.31233 106.109706 187.669205 105.04759 187.422792 104.656731 188.034576 104.176654 188.179025 102.341316 188.463675 102.477267"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-41",style:({display:_vm.props.displayDep['FR-dep-41']}),attrs:{"fill":"#6b6b6b","points":"106.221118 81.0352554 108.009739 80.7930928 108.141443 81.1712064 109.067617 80.3342584 109.747377 80.2790283 109.857838 80.6486449 108.906173 81.3751328 110.163731 81.4728475 110.350665 81.7999794 111.021929 81.6130469 111.213112 82.8451023 111.948103 83.0235379 112.49616 84.608216 113.379849 84.3193202 113.711232 84.7739062 115.555083 84.9650872 115.856727 84.2513448 116.736168 84.3872957 116.965587 83.5843355 118.218896 84.2343509 118.218896 84.2343509 118.992124 84.034673 118.817935 85.0033234 118.248636 85.5556241 119.293768 86.3245966 119.455211 86.9448727 118.303866 88.4743208 119.081343 89.0861 118.686232 89.8083393 119.620903 90.5305787 119.225792 90.9511769 120.147717 90.0972351 121.613451 90.751499 122.085035 92.6548121 123.028204 93.2793367 123.967123 92.7142906 124.171051 91.8306095 124.298506 92.280947 125.547567 92.1492445 125.62404 92.7015452 126.031896 92.7482783 126.813621 92.1874807 128.640478 92.4296433 130.242165 92.1917292 130.90918 93.1646281 130.870943 93.7041834 130.870943 93.7041834 128.776431 94.6855792 128.929377 95.5140302 130.165692 95.6584781 130.458839 96.2532634 130.140201 97.1751807 131.028138 97.0519752 130.726494 99.2441841 130.280401 99.2909172 129.991503 98.4497208 129.099317 99.252681 128.691461 98.8915613 128.368574 100.323295 128.670218 100.246822 128.772182 101.206976 129.451943 102.08216 128.933625 102.591976 127.998954 102.460273 127.098271 103.038065 125.636786 102.324322 124.812576 103.195258 125.309651 103.586117 124.927285 104.278617 124.447204 104.478295 123.856662 104.10443 123.856662 104.10443 122.72656 103.781547 121.655936 102.630212 120.79349 103.123034 119.812085 102.596224 119.467956 102.642957 119.480702 103.131531 118.282624 103.199507 118.40583 104.125672 117.904507 103.696577 116.948593 103.615856 114.794601 105.005105 114.794601 105.005105 113.32037 102.366807 112.118043 102.337068 111.897121 102.723678 110.868983 101.882482 110.73303 101.291945 111.174875 100.654675 110.851989 100.229828 111.187621 99.6605338 110.512108 98.6536472 111.026177 98.7088772 111.383052 98.160825 111.009183 97.7189845 110.554593 97.8082023 110.252949 96.2277726 109.815353 96.0026039 110.388902 94.9447357 110.13824 95.0849351 109.611425 94.0185699 108.974149 94.647343 108.872185 93.7126803 107.610379 93.3940453 107.253505 94.1247816 106.594987 94.2607325 106.267852 93.0669134 106.684205 91.8730942 106.029936 92.2384623 104.424001 91.7328948 103.918429 92.0982629 103.268407 91.6224346 102.818066 92.026039 102.66512 91.3590297 102.261512 91.7456402 102.261512 91.7456402 102.002353 92.026039 101.649727 91.2740604 102.439949 90.2799191 103.769731 89.8763148 103.608288 89.4514681 105.065525 87.9262685 104.806366 86.3798266 105.320435 86.0866824 105.698552 86.7027101 105.770777 85.5556241 106.361319 85.3474492 105.826007 84.1408846 106.242361 82.9937986 105.630576 82.9343201 105.388411 82.5052249 105.384163 81.9189365 106.255106 81.8552095 106.293343 81.2731696 105.719795 81.3029088"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-43",style:({display:_vm.props.displayDep['FR-dep-43']}),attrs:{"fill":"#6b6b6b","points":"146.276022 151.640525 147.469852 151.65327 148.468251 150.586905 149.394425 150.425463 149.594104 149.911399 150.06144 150.493439 150.567012 149.864666 150.983365 150.395724 151.714108 150.467948 152.457597 150.382979 153.137357 149.711721 153.647178 150.459451 154.360927 150.370233 155.202131 151.976154 156.017844 151.385617 156.124056 150.837565 156.799569 151.058485 156.667865 151.453592 157.980653 151.721246 158.456486 150.642135 159.047028 150.858807 159.076767 151.287902 160.143142 151.496077 160.143142 151.496077 160.330076 152.099359 160.822903 151.134957 161.532403 151.037243 162.271643 152.231062 162.314128 151.695755 162.760221 151.534313 163.206314 152.031384 163.384751 151.470586 164.663551 151.304896 164.75277 150.697365 166.061309 150.697365 166.749567 151.474835 167.386843 151.130709 168.053858 151.364375 168.423478 152.001645 167.794699 152.481721 168.487205 152.889574 168.10484 153.560832 168.606164 153.896461 168.606164 153.896461 168.818589 153.977182 168.818589 153.977182 169.630053 153.560832 170.454263 154.529482 170.454263 154.529482 170.394784 155.668071 169.75326 156.789667 170.00817 157.452427 169.672538 157.36321 169.625805 157.677596 169.400634 157.019084 168.533939 156.832151 168.975784 157.800802 168.270532 157.941001 168.045361 158.722719 168.686885 159.389728 167.170169 159.933532 167.063956 160.498578 167.476061 161.084867 165.908363 161.331278 164.701788 163.57022 162.552044 163.587213 162.441583 164.547367 162.042224 164.436907 161.944508 164.887244 161.634367 164.564361 161.736331 165.095419 161.048073 164.713057 160.368313 165.92387 160.593483 166.157536 159.497369 166.760818 159.497369 166.760818 159.042779 166.6716 159.170234 166.068318 158.579692 166.255251 157.725743 165.358824 157.411353 164.432658 156.05608 164.666324 156.175038 163.973824 155.410308 163.362045 154.768783 163.497996 154.679565 164.730051 154.35243 164.487888 152.729501 165.142152 151.650381 162.937198 151.429458 161.479974 151.191542 161.641416 150.668976 161.1316 150.668976 161.1316 150.371581 160.77048 150.830419 160.307397 150.069937 160.175695 150.244126 159.784836 149.819275 159.946277 149.572862 159.6064 149.802281 158.327612 149.288212 157.839038 150.656231 157.830541 149.394425 157.409943 148.901598 156.08867 148.901598 156.08867 148.901598 155.842259 148.901598 155.842259 149.114023 155.217734 148.468251 154.971323 148.226086 153.514099 147.193699 153.722274 147.117226 153.059513 145.749207 153.208209 146.31001 152.689896 145.893657 152.324528 146.284519 152.252304"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-44",style:({display:_vm.props.displayDep['FR-dep-44']}),attrs:{"fill":"#6b6b6b","points":"54.3808621 93.4195361 56.5858361 91.7838764 56.9087225 92.3701648 58.5826334 91.3972659 61.0255237 91.6819132 61.5141018 91.2868058 62.147129 91.3590297 62.7801562 89.9612841 65.4099807 88.9289067 65.3080166 88.0834618 66.9054544 88.2703944 67.3430504 89.0308699 69.3993268 89.6214068 69.3993268 89.6214068 69.6160005 90.1567136 69.2633746 90.8109775 70.3000098 91.0531401 70.6653812 91.7753794 70.6653812 91.7753794 70.6356417 92.0345359 70.6356417 92.0345359 70.5379261 92.5655943 70.9372855 92.6378182 71.311154 93.7169288 73.6393346 94.5113921 72.9935619 95.1996437 70.6993693 94.9489841 70.9797706 96.5803954 74.3190954 97.0392298 74.6504788 98.9085552 75.1093173 99.4481105 74.1661492 100.187344 71.2006928 100.234077 68.6133534 100.994552 67.5044936 101.91647 68.5326318 101.873985 69.4800484 103.492651 70.3212523 103.271731 70.8693095 105.009353 70.8693095 105.009353 70.8608124 105.119814 70.8608124 105.119814 69.552273 106.254154 69.8029347 106.721486 69.3695872 107.231302 70.7630968 107.388495 71.0095101 107.932299 71.6977679 108.170213 71.6892709 108.688526 71.1582078 109.053894 71.1582078 109.053894 70.2022942 108.752253 68.9872218 107.59667 68.4604072 108.905198 67.6616883 108.62055 67.0881401 108.964676 67.3260564 110.880735 65.5969149 111.666701 65.4397203 108.985918 64.8874146 108.78624 63.9527436 109.432007 64.1864113 109.754891 63.9357495 110.324185 64.5985163 111.326824 64.4200791 111.785658 64.1269323 111.713434 64.9256512 112.869017 64.1609203 113.034707 64.1014412 113.38733 62.0834014 113.030459 61.6585509 112.618357 61.0297722 112.779799 60.6134187 111.636962 59.2623942 111.58598 59.1604301 110.995443 57.8901271 110.791517 57.6139743 110.039538 57.1636328 109.963066 56.500866 109.0369 56.500866 109.0369 55.1965751 107.34601 51.7680316 106.351869 53.1955293 105.429952 53.1063107 102.953096 52.3968104 102.800151 50.8928396 103.726317 49.5078271 102.587727 48.7685872 102.630212 48.5136769 103.10604 46.5168796 102.281838 47.1541554 101.80601 47.2051374 101.037037 46.2577208 100.102374 48.0335959 98.8235858 48.0420929 98.1905643 48.0420929 98.1905643 48.6496291 97.4385857 49.0319945 97.978141 50.5189712 97.8549354 50.8503546 96.4189536 51.5428609 96.4741837 51.7935227 96.9797513 52.8514004 96.5081714 52.7791758 96.9670059 53.2295173 97.1029568 53.3399784 96.2745058 54.3851106 95.9813616 54.3383771 94.3839381 54.6782575 93.9336006"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-45",style:({display:_vm.props.displayDep['FR-dep-45']}),attrs:{"fill":"#6b6b6b","points":"126.550214 76.2089972 127.378673 76.2642272 128.504526 75.6694419 129.541161 75.8903621 129.332985 75.4272793 129.855551 75.5504848 130.237916 74.6837976 130.989902 75.0066811 130.981405 75.8903621 131.36377 75.4485216 131.945815 75.5419879 131.945815 75.5419879 132.141246 75.0916504 132.141246 75.0916504 132.464133 74.9854387 132.990947 75.6439511 133.789666 75.4357762 133.789666 75.4357762 134.133795 76.9142426 135.582535 77.4835372 135.884179 78.4861754 135.595281 79.6332614 135.183176 79.4165896 134.486421 80.4914517 135.858688 80.4829547 136.525703 80.0411142 139.104546 80.5041971 139.962744 79.9264056 139.648355 79.3613595 140.11569 79.7139823 140.752966 79.3103779 140.791202 80.2875253 141.114089 80.3342584 141.938299 79.6417583 143.187359 79.4590742 143.187359 79.4590742 144.818785 80.2450406 144.580869 80.6996265 144.929246 80.6741357 145.175659 81.8254702 146.496944 82.9683078 146.050851 83.2402097 146.505441 83.5758386 146.556423 84.4680166 145.982875 85.118032 145.982875 85.118032 145.774699 85.2624799 145.774699 85.2624799 145.184156 86.0909309 144.461911 86.2183849 144.525638 87.0723267 144.223994 87.2380169 144.874015 87.6968513 144.529887 88.121698 144.74656 89.3452564 141.683388 90.1184774 141.615412 91.1763456 142.154972 91.0871278 142.163469 91.5247199 142.996176 92.0940145 142.868721 92.4423887 143.476257 93.054168 143.106637 93.5384932 143.178862 94.2607325 143.884114 95.029705 143.884114 95.029705 143.569725 95.3525885 142.796497 95.1231713 142.057257 95.666975 141.772607 95.4715456 141.606915 95.8156714 142.082748 96.2914996 142.082748 96.2914996 140.893166 96.9627574 139.563384 95.8326652 139.435929 96.9797513 138.441779 97.2431562 138.237851 96.5761469 137.50286 96.2107788 137.536848 95.7689382 136.589431 94.9617295 135.323377 95.0594443 134.380209 93.9803337 133.220367 94.6091068 131.767378 93.4747662 130.870943 93.7041834 130.870943 93.7041834 130.90918 93.1646281 130.242165 92.1917292 128.640478 92.4296433 126.813621 92.1874807 126.031896 92.7482783 125.62404 92.7015452 125.547567 92.1492445 124.298506 92.280947 124.171051 91.8306095 123.967123 92.7142906 123.028204 93.2793367 122.085035 92.6548121 121.613451 90.751499 120.147717 90.0972351 119.225792 90.9511769 119.620903 90.5305787 118.686232 89.8083393 119.081343 89.0861 118.303866 88.4743208 119.455211 86.9448727 119.293768 86.3245966 118.248636 85.5556241 118.817935 85.0033234 118.992124 84.034673 118.218896 84.2343509 118.218896 84.2343509 118.057453 83.0532771 118.698977 82.9810532 118.652244 82.6071881 119.51469 82.9598109 119.59966 82.6114366 119.96928 82.6496728 120.041505 81.9189365 120.653289 81.7447494 120.844472 82.1058691 121.167358 81.7192586 122.15726 82.0888752 123.087683 81.7362524 123.168404 81.3878782 123.639988 81.6767739 124.591653 81.1839518 125.233178 79.9094117 124.957025 79.548292 125.305402 79.1829239 126.1636 79.1956693 125.759992 78.6263748 126.040393 78.4394422 126.150855 78.719841 126.291055 78.401206 125.985163 77.2838593"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-48",style:({display:_vm.props.displayDep['FR-dep-48']}),attrs:{"fill":"#6b6b6b","points":"150.668976 161.1316 151.191542 161.641416 151.429458 161.479974 151.650381 162.937198 152.729501 165.142152 154.35243 164.487888 154.679565 164.730051 154.768783 163.497996 155.410308 163.362045 156.175038 163.973824 156.05608 164.666324 157.411353 164.432658 157.725743 165.358824 158.579692 166.255251 159.170234 166.068318 159.042779 166.6716 159.497369 166.760818 159.497369 166.760818 160.232361 170.461233 161.315729 171.434132 161.914768 173.779285 161.914768 173.779285 161.048073 175.245006 160.478774 175.117552 159.926468 175.555144 161.099056 176.918902 160.584986 177.573166 160.771921 178.299654 161.498415 178.732998 161.014085 179.089869 160.827151 180.270943 161.179777 180.313427 161.481421 181.069654 160.589235 180.581081 160.657211 181.095145 159.735285 181.961832 158.949312 181.68993 158.354521 181.932093 156.14105 180.487614 155.554757 180.725528 155.380568 181.299072 155.822412 181.481756 155.452793 182.085038 153.8681 182.263473 152.13471 181.876863 150.860159 180.88697 150.860159 180.88697 150.673225 180.143489 149.509134 179.999041 147.894702 180.398397 148.362038 179.408504 147.61855 178.936924 147.138469 179.06013 146.985522 178.312399 146.433217 178.626786 146.445962 178.078734 147.015262 177.505191 146.407726 176.14993 146.84957 174.306095 145.473055 172.717169 145.808687 171.32792 143.977581 169.224929 143.977581 169.224929 144.602111 167.512797 144.988725 167.461815 144.767803 167.147429 145.124677 165.337582 145.575019 164.441155 146.042354 164.53887 146.123076 163.238839 146.807085 162.830986 147.682277 163.825128 148.459754 163.200603 148.238831 162.665296 148.69767 162.486861 148.561718 162.011032 149.003562 161.841094 149.334946 162.227704"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-49",style:({display:_vm.props.displayDep['FR-dep-49']}),attrs:{"fill":"#6b6b6b","points":"69.5480244 88.7547196 70.7588483 89.5619283 73.3206968 89.6001645 74.0939246 90.0207627 74.6079937 89.8380786 74.2128828 89.4982013 74.4295565 89.1285846 74.9521226 89.1710693 75.6403804 89.9527872 76.5835485 90.2416829 76.587797 89.8253332 76.9744109 89.7403639 78.3934115 90.6367904 80.6536161 90.6750266 81.0657211 90.105732 81.9791496 90.3139069 82.2553025 89.332511 83.3854048 90.1567136 84.1288931 89.9315449 84.05242 89.532189 84.6472107 90.0505019 84.6472107 90.0505019 85.1102977 90.7897352 84.9828426 91.2443211 86.3466126 91.1083702 87.2472957 91.5289684 87.8463348 90.9809162 88.3051734 91.189091 87.5489395 92.0855175 87.9100624 92.9267139 88.9594431 93.364306 89.4267787 93.3175729 89.7454165 92.5953335 89.766659 92.8969747 90.1150364 92.7015452 91.0242165 93.2793367 91.3555999 93.0329256 91.6147587 93.6489533 92.4772051 94.1502724 93.4076277 94.0950423 93.9386908 94.7195669 94.6269486 94.2862233 94.6524396 93.810395 95.0050655 94.1757632 95.421419 93.9973276 95.421419 93.9973276 95.421419 93.9973276 95.421419 93.9973276 95.5021406 94.3839381 95.5021406 94.3839381 94.8138828 95.8156714 95.378934 96.1258094 95.2982124 96.8310549 94.0873885 99.4523589 94.6057061 99.9111933 93.8494722 100.433755 93.9769274 100.816117 92.753358 102.507007 92.787346 104.163909 92.3285075 104.733203 92.5834178 104.898893 92.34975 105.548909 92.34975 105.548909 91.7209713 105.264261 91.2536357 105.650872 90.6673421 106.84894 90.7395667 107.558434 89.8558776 107.188817 89.5754763 108.131977 89.5754763 108.131977 89.0953953 108.395382 88.7470179 107.974783 88.2924279 108.059753 88.874473 107.159078 88.1309847 106.993387 87.7741103 107.354507 86.69499 107.057114 85.3779535 107.545688 84.3838034 107.435228 84.1926207 108.064001 83.2961861 108.008771 82.8925782 108.408127 83.2749436 107.681639 82.6589104 107.630657 81.5712932 108.131977 81.7964639 108.947682 80.8193078 109.865351 79.5617504 109.703909 79.4130527 110.107514 78.784274 110.20098 77.5012255 109.648679 77.1316056 110.116011 76.5198209 110.052284 76.3541292 110.566348 75.6573744 110.413403 75.6573744 110.413403 74.9818622 109.589201 74.4380535 109.912084 74.1746462 109.321547 72.7853851 109.682667 71.1539593 109.053894 71.1539593 109.053894 71.6850224 108.688526 71.6935194 108.170213 71.0095101 107.932299 70.7630968 107.388495 69.3695872 107.231302 69.8029347 106.721486 69.552273 106.254154 70.8608124 105.119814 70.8608124 105.119814 70.873558 105.009353 70.873558 105.009353 70.3255008 103.275979 69.4885454 103.496899 68.5368803 101.878233 67.5129906 101.920718 68.6218504 100.998801 71.2049413 100.238325 74.1703977 100.191592 75.1135658 99.4523589 74.6547273 98.9128036 74.3190954 97.0434783 70.9797706 96.5803954 70.6993693 94.9532326 72.9935619 95.2038921 73.6393346 94.5156405 71.311154 93.7211772 70.9372855 92.6420667 70.5379261 92.5698427 70.6356417 92.0387844 70.6356417 92.0387844 70.6653812 91.7796279 70.6653812 91.7796279 70.3000098 91.0531401 69.2633746 90.815226 69.6160005 90.1609621 69.3993268 89.6256553 69.3993268 89.6256553"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-51",style:({display:_vm.props.displayDep['FR-dep-51']}),attrs:{"fill":"#6b6b6b","points":"152.844211 61.1779217 153.600444 59.5762497 154.348181 59.5082742 154.40766 58.8455134 154.925978 58.7520471 154.696559 58.2889643 155.240367 58.1275225 155.60149 57.112139 156.243014 56.8444856 155.767182 56.0670161 154.611589 56.2327063 154.641328 55.6081817 155.54626 55.1535958 154.887741 54.0150067 155.265858 53.1865557 156.395961 53.0888409 156.727344 53.4074759 157.492075 52.9613869 157.50482 52.3623531 156.901533 52.4770617 156.621131 51.669853 156.327985 51.933258 155.826661 51.5084113 156.25576 50.9178744 155.796921 50.3995614 156.034838 50.0002056 155.673715 48.793641 158.014641 47.638058 159.310435 47.9481961 159.386908 46.9922911 160.589235 46.2743002 161.222262 47.0687635 162.581784 47.5318464 162.785712 46.2870456 162.785712 46.2870456 165.309324 46.4229965 165.483513 46.8988248 166.299225 46.9115702 167.369849 48.4495152 167.888166 48.4537637 167.998627 48.7851441 168.640152 48.4919999 168.933298 49.3884264 169.816987 49.885497 172.485049 49.3799294 172.374587 49.877 173.122324 50.9900983 174.112226 50.2933498 175.233831 50.7436873 176.321448 50.5015247 177.213634 51.1005585 177.404817 50.1531504 178.229027 50.0639326 178.182294 50.4547915 178.713357 50.8456505 178.713357 50.8456505 179.431354 51.5721383 178.50518 52.251893 179.490833 54.2104362 179.66927 55.6294241 178.964018 56.6405592 179.728749 56.2624456 180.217327 56.5853291 180.179091 57.4010347 179.478088 57.6516942 180.272558 57.9193477 179.864701 58.8455134 178.488186 59.414808 178.488186 59.414808 178.411713 59.5635043 178.411713 59.5635043 178.042093 59.9288724 178.033596 60.7445781 178.47544 61.4328297 177.629988 62.099839 177.65123 62.6054065 178.47544 62.8390722 179.261414 64.0456368 179.758489 64.1178607 179.851956 64.9633056 179.541815 64.9845479 179.380372 65.6430603 179.380372 65.6430603 178.696363 65.5198547 178.483937 65.9022167 177.995359 65.5326001 177.234877 66.1103916 176.346939 65.7620173 175.713912 66.0296707 175.556718 66.5097475 176.77179 66.5989653 176.996961 66.9770788 176.996961 67.5718642 175.399523 68.043444 175.633191 68.816665 175.981568 68.7486895 175.743652 69.1055607 176.024053 69.7853154 174.588058 69.4496865 173.742606 69.7300853 173.742606 69.7300853 173.122324 69.390208 173.279519 69.126803 172.378836 69.1480454 171.54188 69.8872786 170.942841 69.2967417 170.666688 69.5474012 169.031014 68.935622 167.531292 67.4996403 167.824439 65.8172474 167.229648 64.8528454 166.579627 65.0780142 166.222752 64.7976154 166.057061 65.1842259 164.854734 65.0270326 164.26844 65.5963271 163.35926 65.1969713 162.972646 66.1273855 161.969999 66.1996094 161.035328 67.8140268 160.330076 67.8607599 160.17713 68.5745023 159.32318 68.4215575 159.556848 69.5558982 158.855845 70.1591805 157.309389 69.5346558 155.580248 69.5431528 155.350828 68.5022784 154.577601 68.2898551 154.15275 67.4444102 154.15275 67.4444102 153.460244 67.0110666 153.706657 66.6541954 152.240923 66.951588 152.389621 66.4885052 152.045492 66.1911125 152.691264 65.2734437 152.542567 64.2155754 151.242524 63.6760201 151.497435 62.9962655 152.104971 62.8263268 152.070983 62.4864494 151.399719 62.3377531 152.878199 62.1975537"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-52",style:({display:_vm.props.displayDep['FR-dep-52']}),attrs:{"fill":"#6b6b6b","points":"173.742606 69.7300853 174.588058 69.4496865 176.024053 69.7853154 175.743652 69.1055607 175.981568 68.7486895 175.633191 68.816665 175.399523 68.043444 176.996961 67.5718642 176.996961 66.9770788 176.77179 66.5989653 175.556718 66.5097475 175.713912 66.0296707 176.346939 65.7620173 177.234877 66.1103916 177.995359 65.5326001 178.483937 65.9022167 178.696363 65.5198547 179.380372 65.6430603 179.380372 65.6430603 179.758489 65.8087505 179.465342 66.3185665 179.605543 67.5293795 180.162097 67.5378765 180.595444 67.0365574 180.841858 68.0052078 181.68731 68.0179532 182.163143 68.9568644 182.520017 68.8379073 183.322984 69.8023092 185.370764 70.2993799 185.69365 71.0046253 186.60283 71.2552849 186.794013 72.0285058 187.847642 72.6530305 187.847642 72.6530305 186.513612 73.4347483 187.367561 73.7491349 186.934214 74.3396718 187.099905 75.0194265 188.026079 74.3609141 188.824798 74.5818344 189.959149 76.5361291 190.358508 76.0645493 190.885323 76.489396 190.885323 77.3900709 191.696787 77.5939973 192.474264 78.6391202 191.577829 79.0427245 191.641557 79.875424 191.161476 80.5849179 191.403641 80.9162984 190.651655 81.5195806 191.165724 81.9316819 191.152979 81.5068352 191.862479 81.7489978 192.312821 82.6114366 192.669695 82.4584918 193.277231 82.8833385 193.604366 83.8519889 193.468414 85.0075719 193.859276 85.1222805 194.517795 84.4000411 195.18481 85.7043204 195.18481 85.7043204 195.176313 86.3798266 194.581522 86.2141364 194.07595 87.1403022 194.182163 87.6331243 192.99683 87.4419433 192.661198 88.4743208 191.769012 88.4530784 191.505605 89.5449344 192.028171 89.8423271 192.083401 90.3903793 191.654302 91.0276493 191.764764 92.0132936 190.107847 92.3404255 189.589529 91.4185082 188.850289 92.3191832 188.098304 92.0345359 187.732932 92.3489225 186.819504 92.280947 186.14824 94.1035392 186.14824 94.1035392 185.846596 94.4689074 185.544952 93.9760852 184.992647 93.9888306 184.023988 94.8427725 183.786071 94.3329565 184.15994 93.6574502 183.79032 93.810395 182.647472 92.8757323 182.736691 92.1407476 181.661819 93.0244287 180.994804 92.3744133 180.535965 92.4551341 180.66342 91.7498886 180.102618 91.8263611 180.089872 91.3505328 179.027746 91.9878028 178.857806 89.9995203 178.093075 89.8635694 179.388869 88.9076644 179.469591 88.4870662 178.879048 87.9772501 178.764339 87.2550108 178.288506 87.1275568 177.872153 85.8232775 177.277362 85.9124953 177.120167 86.4732929 176.610347 86.146161 177.205137 85.3177099 176.85676 84.7866516 175.781888 84.7399185 176.215236 84.0516668 175.930586 83.5970809 174.95343 83.6268202 174.375633 83.2019735 174.375633 83.2019735 174.690023 82.560455 173.538678 81.6725254 174.38413 81.404872 174.834472 80.6656388 176.661329 80.9842738 176.57211 80.3470038 176.954476 79.9943811 176.699565 79.3316202 177.175398 78.5201631 176.678323 77.9636139 177.132913 76.744304 176.283212 75.2445952 176.750547 74.7432761 175.246577 74.0932607 175.340044 73.5324631 174.388379 73.4517422 173.895552 72.138966 173.02036 71.5824168 173.457956 71.3487512"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-54",style:({display:_vm.props.displayDep['FR-dep-54']}),attrs:{"fill":"#6b6b6b","points":"193.357953 42.1575359 193.995229 42.7438243 194.343606 42.595128 194.343606 43.2239011 195.252786 43.8101895 195.252786 43.8101895 195.414229 44.2945147 195.953789 44.1670607 196.2087 44.6513859 196.204451 45.6285333 195.635151 46.2530579 196.030262 46.5589475 195.885813 47.1877206 196.816236 47.9439476 196.242688 48.0331655 196.272427 48.4622606 196.952188 49.0145613 197.292068 48.8616165 197.313311 50.3103436 197.937841 50.7649296 196.80349 52.0012334 197.780646 52.761709 196.892709 53.335252 197.279323 54.2104362 196.00902 54.299654 195.881565 54.7584884 196.472107 55.9523075 197.483251 56.2454517 197.99732 56.8784733 197.954835 57.8343783 198.494395 57.7196697 198.65159 58.2209888 199.259126 58.0765409 199.543776 58.9644705 200.253276 58.7265563 202.691918 59.3298386 202.976568 59.8863878 202.237328 61.3308665 203.205987 61.6664954 202.526226 61.9256518 203.48214 62.2485353 203.524625 63.119471 204.178895 63.2384281 204.84591 62.8518176 205.21553 63.4805907 205.810321 63.4508514 206.260662 63.9011889 207.135854 63.7312502 207.777378 64.8273546 208.894735 65.0780142 208.983954 66.0933977 209.315337 65.6303149 210.139547 66.2251002 210.58564 66.0381677 210.989248 66.760407 211.928168 66.7306678 212.263799 67.3212046 213.279192 67.3551924 213.610576 66.951588 214.018432 67.6355912 215.029576 67.6058519 214.732181 68.0731833 215.462924 68.4385514 215.773064 69.1777846 216.316873 69.3562202 215.858035 69.5558982 216.193666 69.6153767 216.193666 69.6153767 216.248897 70.218659 216.928658 70.1889197 216.928658 70.1889197 214.468773 71.1150855 212.939312 72.7337513 212.442237 72.2239353 211.826203 72.7507452 211.826203 72.7507452 211.647766 73.094871 211.647766 73.094871 211.010491 73.5154692 211.108206 73.158598 210.730089 73.2308219 210.696101 72.9206839 208.588843 72.457601 208.172489 71.2722788 207.220824 72.1177236 207.433249 72.776236 206.630282 72.3641347 204.076931 73.4304999 203.953724 72.8102237 203.02755 73.0778771 202.428511 72.2834138 201.332396 73.2435673 199.875159 72.9631685 199.896402 73.6854079 199.271872 74.4288896 198.838524 74.1952239 198.762051 73.6301778 198.477401 74.2631994 197.266577 74.1612362 196.544332 74.5266043 196.336155 73.3030459 195.533187 73.1925857 196.00902 72.3089046 195.418478 72.2451776 195.35475 73.0821256 194.679238 72.9419262 195.405732 71.9987666 195.193307 71.365745 195.545933 70.6944873 194.704729 70.2866345 193.459917 71.0131223 193.069055 70.6392572 193.069055 70.6392572 193.277231 69.4879227 192.215105 68.9398705 192.20236 68.2006373 193.53639 67.6908213 193.73607 67.2362353 192.954345 67.3254531 192.673944 66.5097475 193.086049 65.2139651 192.206608 64.0328914 192.686689 63.8417104 192.584725 63.3616336 193.323965 62.6988728 192.924605 62.5586734 193.36645 61.8321856 193.073303 60.6341179 193.510899 60.2560044 192.754665 59.4318018 193.680839 58.641587 194.56028 58.4928907 194.326612 57.8981053 193.702082 57.5837188 194.636753 56.0882585 194.326612 55.4637339 193.782803 55.5062185 194.063205 54.3378902 193.086049 54.3888718 192.750417 53.8663103 193.19651 53.4966937 192.699435 53.0038716 193.069055 51.9162641 192.355306 51.9545003 192.253342 51.414945 192.62721 51.0368315 192.317069 50.0936718 193.013824 49.7155583 193.175267 48.9678281 193.039315 48.7086717 192.5125 48.8701134 192.5125 48.8701134 192.431779 48.5684723 192.431779 48.5684723 192.601719 47.6890396 192.121638 47.4298832 192.049413 46.8988248 192.546488 46.4994689 191.769012 46.3890088 191.773261 46.0066468 190.740874 45.4118614 190.417988 45.8749443 188.348966 46.2488094 188.111049 46.8945764 187.51201 46.4187481 188.510409 45.8749443 187.915618 45.726248 188.174777 45.1612019 187.720187 45.0210025 187.800909 44.3539932 187.163633 44.0863398 187.384555 43.6487477 187.915618 44.1330729 187.618223 44.2647754 187.919867 44.2010484 187.924115 43.8016925 187.924115 43.8016925 189.394098 42.9350053 190.362757 43.551033 190.906566 42.3912016 192.733423 42.6970912 192.954345 42.0938089"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-55",style:({display:_vm.props.displayDep['FR-dep-55']}),attrs:{"fill":"#6b6b6b","points":"187.053172 41.0486861 187.609726 41.9153733 187.817903 43.0029808 187.520507 43.2493919 187.924115 43.8016925 187.924115 43.8016925 187.919867 44.2010484 187.618223 44.2647754 187.915618 44.1330729 187.384555 43.6487477 187.163633 44.0863398 187.800909 44.3539932 187.720187 45.0210025 188.174777 45.1612019 187.915618 45.726248 188.510409 45.8749443 187.51201 46.4187481 188.111049 46.8945764 188.348966 46.2488094 190.417988 45.8749443 190.740874 45.4118614 191.773261 46.0066468 191.769012 46.3890088 192.546488 46.4994689 192.049413 46.8988248 192.121638 47.4298832 192.601719 47.6890396 192.431779 48.5684723 192.431779 48.5684723 192.5125 48.8701134 192.5125 48.8701134 193.039315 48.7086717 193.175267 48.9678281 193.013824 49.7155583 192.317069 50.0936718 192.62721 51.0368315 192.253342 51.414945 192.355306 51.9545003 193.069055 51.9162641 192.699435 53.0038716 193.19651 53.4966937 192.750417 53.8663103 193.086049 54.3888718 194.063205 54.3378902 193.782803 55.5062185 194.326612 55.4637339 194.636753 56.0882585 193.702082 57.5837188 194.326612 57.8981053 194.56028 58.4928907 193.680839 58.641587 192.754665 59.4318018 193.510899 60.2560044 193.073303 60.6341179 193.36645 61.8321856 192.924605 62.5586734 193.323965 62.6988728 192.584725 63.3616336 192.686689 63.8417104 192.206608 64.0328914 193.086049 65.2139651 192.673944 66.5097475 192.954345 67.3254531 193.73607 67.2362353 193.53639 67.6908213 192.20236 68.2006373 192.215105 68.9398705 193.277231 69.4879227 193.069055 70.6392572 193.069055 70.6392572 192.244845 71.62915 190.898069 71.2935211 191.114742 71.6249015 190.46897 72.0837359 190.532697 72.521328 190.027125 72.075239 188.714337 72.3641347 188.501912 72.9631685 187.847642 72.6530305 187.847642 72.6530305 186.794013 72.0285058 186.60283 71.2552849 185.69365 71.0046253 185.370764 70.2993799 183.322984 69.8023092 182.520017 68.8379073 182.163143 68.9568644 181.68731 68.0179532 180.841858 68.0052078 180.595444 67.0365574 180.162097 67.5378765 179.605543 67.5293795 179.465342 66.3185665 179.758489 65.8087505 179.380372 65.6430603 179.380372 65.6430603 179.541815 64.9845479 179.851956 64.9633056 179.758489 64.1178607 179.261414 64.0456368 178.47544 62.8390722 177.65123 62.6054065 177.629988 62.099839 178.47544 61.4328297 178.033596 60.7445781 178.042093 59.9288724 178.411713 59.5635043 178.411713 59.5635043 178.488186 59.414808 178.488186 59.414808 179.864701 58.8455134 180.272558 57.9193477 179.478088 57.6516942 180.179091 57.4010347 180.217327 56.5853291 179.728749 56.2624456 178.964018 56.6405592 179.66927 55.6294241 179.490833 54.2104362 178.50518 52.251893 179.431354 51.5721383 178.713357 50.8456505 178.713357 50.8456505 180.467989 49.8472608 180.616687 49.430911 180.017648 48.8828588 180.064381 48.1946072 180.544462 47.3066776 181.360175 47.0390242 180.935325 46.3805119 181.610837 45.9259259 181.538612 44.8850516 180.620935 43.7294686 181.168992 42.8033028 181.168992 42.8033028 181.168992 42.5993764 181.168992 42.5993764 181.568352 42.1065543 181.564103 41.248364 182.107912 41.2908487 182.464786 42.0428273 183.722344 41.8686401 184.206673 42.6163703 185.081865 42.1362936 185.532207 41.2058793 186.245956 41.2271217 186.560345 40.5898516 186.560345 40.5898516"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-57",style:({display:_vm.props.displayDep['FR-dep-57']}),attrs:{"fill":"#6b6b6b","points":"201.557567 43.4958029 203.643583 44.8383184 203.690317 44.5409258 204.187392 44.7151129 204.739697 44.379484 206.77898 45.5860486 207.038139 45.9131805 206.732246 46.3932573 207.811366 47.38315 207.216576 47.8844691 208.045034 49.112276 209.030687 49.694316 208.869244 50.2636105 209.374816 50.5100216 209.489526 51.4191935 209.986601 51.3554665 209.774176 52.188166 210.250008 52.8084421 210.976503 52.689485 211.957907 53.1058348 212.425243 52.3623531 212.004641 51.5041628 212.395503 51.2322609 212.969051 51.5976291 213.772019 51.2322609 214.150136 51.7718162 214.383803 51.5848837 215.450178 52.0989482 215.75607 54.163703 216.389098 53.7431048 216.27014 53.2460342 216.597274 53.0463563 216.682245 53.4924453 217.060361 53.4159729 217.616916 53.9980128 219.184614 53.7431048 220.030066 54.1467092 220.599366 53.3777367 221.24089 53.3862336 221.283375 52.6300065 222.68963 52.3028746 222.532436 52.8126906 223.560574 52.697982 223.666786 53.7133655 224.299814 54.7245006 225.98647 55.2640559 226.084186 55.7823689 226.084186 55.7823689 225.056048 58.3696851 224.321056 59.0281975 222.825583 58.0637955 220.599366 58.7350533 219.979084 58.4079213 220.038563 57.9193477 219.044413 57.5369856 218.572829 57.7621544 217.901565 57.0654058 217.073107 57.0611574 216.631262 55.5487032 216.907415 55.3575222 216.576032 55.1450988 216.032223 55.4679823 216.206412 55.8673382 215.382202 57.5709734 215.411942 58.3951759 214.672702 58.259225 214.464525 59.0112036 213.85274 59.0069552 214.043923 60.0733203 215.70084 60.5788879 215.734828 60.9952376 216.520801 60.9017713 216.537795 61.2119094 215.743325 61.3945935 215.760319 62.1465721 216.414589 62.2655292 216.023726 62.5756672 216.223406 62.9835201 217.073107 62.5161887 217.362005 61.4753144 218.105494 61.2586425 218.751266 62.0828451 218.781006 61.7854524 219.431027 61.9766334 219.239844 62.3589954 219.928102 62.6181519 220.399686 63.4423545 219.524494 65.2734437 219.125135 65.4476308 219.532991 66.2378456 220.221249 66.3015726 219.5075 67.0832905 219.775156 67.1385206 219.524494 68.1496557 217.761365 69.8235516 216.193666 69.6153767 216.193666 69.6153767 215.858035 69.5558982 216.316873 69.3562202 215.773064 69.1777846 215.462924 68.4385514 214.732181 68.0731833 215.029576 67.6058519 214.018432 67.6355912 213.610576 66.951588 213.279192 67.3551924 212.263799 67.3212046 211.928168 66.7306678 210.989248 66.760407 210.58564 66.0381677 210.139547 66.2251002 209.315337 65.6303149 208.983954 66.0933977 208.894735 65.0780142 207.777378 64.8273546 207.135854 63.7312502 206.260662 63.9011889 205.810321 63.4508514 205.21553 63.4805907 204.84591 62.8518176 204.178895 63.2384281 203.524625 63.119471 203.48214 62.2485353 202.526226 61.9256518 203.205987 61.6664954 202.237328 61.3308665 202.976568 59.8863878 202.691918 59.3298386 200.253276 58.7265563 199.543776 58.9644705 199.259126 58.0765409 198.65159 58.2209888 198.494395 57.7196697 197.954835 57.8343783 197.99732 56.8784733 197.483251 56.2454517 196.472107 55.9523075 195.881565 54.7584884 196.00902 54.299654 197.279323 54.2104362 196.892709 53.335252 197.780646 52.761709 196.80349 52.0012334 197.937841 50.7649296 197.313311 50.3103436 197.292068 48.8616165 196.952188 49.0145613 196.272427 48.4622606 196.242688 48.0331655 196.816236 47.9439476 195.885813 47.1877206 196.030262 46.5589475 195.635151 46.2530579 196.204451 45.6285333 196.2087 44.6513859 195.953789 44.1670607 195.414229 44.2945147 195.252786 43.8101895 195.252786 43.8101895 196.620805 43.9291465 196.858721 45.0634872 197.950587 45.1739473 198.222491 44.6556344 198.978725 45.0125056 199.454557 43.9418919 199.722213 44.1075821 200.287264 43.4915545"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-61",style:({display:_vm.props.displayDep['FR-dep-61']}),attrs:{"fill":"#6b6b6b","points":"76.562306 63.8502073 77.6754142 62.9027992 78.7885225 62.5671703 79.3408281 61.9851304 78.9499657 61.581526 79.1581424 61.3223695 80.0333344 61.4965567 79.9866009 61.8491794 81.2823948 61.772707 83.1389915 60.689348 84.1756267 60.6808511 84.5749861 61.3606057 85.0168306 61.2883818 84.7831628 61.6410046 85.2717409 61.9766334 85.5776333 61.0802069 85.9982352 61.0334738 88.0205235 61.4668174 89.1633714 62.1423236 88.7682604 61.6197622 89.1931109 61.2926303 90.1830125 61.5050536 90.9095068 60.5236578 91.2748783 60.6468633 91.2621327 60.2772467 92.34975 59.8481516 92.787346 58.9007435 93.6030589 58.5311269 94.1128795 59.2533662 94.8861074 58.7520471 95.4936436 58.8455134 95.7273114 58.2847158 96.279617 58.1147771 96.2838655 58.5906054 96.8149286 58.4164183 97.3289977 58.7605441 98.030001 57.9703293 98.7012647 58.5651146 98.7012647 58.5651146 98.1702016 59.5720012 98.8499624 60.2560044 101.114416 60.5831363 101.64123 60.0860657 101.798425 60.6468633 102.257263 60.4939185 102.163796 61.1056977 102.626883 61.1141947 102.151051 61.7132285 104.751136 63.4423545 105.031537 64.2368178 104.220073 65.0567719 104.955064 65.3669099 104.984803 66.0551615 105.775025 66.0381677 105.775025 66.0381677 106.063924 67.0025696 105.834504 67.5803611 106.331579 67.6440881 106.408053 68.3663275 107.720841 68.9568644 107.958757 69.2415116 107.691101 69.5771405 108.434589 69.9297633 108.00549 70.5542879 108.239158 71.0258677 107.895029 71.1788125 107.967254 71.7183678 108.600281 72.138966 107.508415 73.931819 105.27795 74.7687669 104.967809 75.2658375 104.708651 75.8691198 105.605085 76.1495186 105.286447 76.8930003 106.029936 78.1420495 105.456387 78.6518656 105.456387 78.6518656 104.797869 79.0129852 104.402758 78.8090588 103.446845 77.0586905 102.915782 76.8122794 102.524919 77.0162058 102.652374 77.6237366 102.388967 77.2838593 100.829766 77.2116353 100.082029 76.2599788 100.02255 75.588721 99.839864 75.8946106 98.3019053 75.2785829 97.9152913 72.8951931 98.1319651 72.5468188 97.6646296 71.6376469 96.6959705 71.0641039 94.4315174 71.4762052 94.4570084 71.8628156 93.9386908 71.6801316 94.1553646 72.1049782 92.6471453 72.9759139 92.4092291 73.7916196 91.6402497 73.7108987 91.3683454 73.2733066 90.3954378 73.7024018 90.3954378 73.7024018 90.4931534 71.8458218 88.7979999 71.692877 88.3221674 70.2696406 88.8192425 69.8065577 87.7273767 68.8633981 86.9881369 68.8421558 86.7459721 69.2585055 87.124089 69.5898859 86.5972744 70.3588584 85.7178339 69.9595025 85.1527828 70.9748861 84.3838034 70.2908829 82.3870061 70.3163737 81.6562633 71.2680303 80.8405503 71.310515 79.8888853 72.0370028 79.5957384 70.9154075 79.230367 71.3954843 78.4953757 71.2595334 78.7715285 71.9137972 77.8878395 72.2324322 77.6414262 71.4719567 77.0381385 71.748107 76.2096801 70.5118032 76.2096801 70.5118032 77.7221478 68.8888889 78.346678 67.3381985 77.7433903 66.378045 78.4019086 65.6175695 76.9616654 64.4110049 76.3838688 64.4237503"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-63",style:({display:_vm.props.displayDep['FR-dep-63']}),attrs:{"fill":"#6b6b6b","points":"136.670153 131.70247 137.931959 132.322746 138.556489 130.971734 139.423184 130.721074 139.584627 129.688697 140.944149 130.355706 141.339259 129.064172 142.885715 128.966458 143.353051 129.217117 142.783751 129.90112 142.779503 130.818789 143.459263 131.005722 143.748162 132.186795 144.555378 132.730599 145.914899 132.420461 146.038106 133.210676 146.581914 133.554802 149.636589 133.661013 150.154907 134.187823 151.688617 133.44859 152.024249 133.818207 152.024249 133.818207 152.444851 133.818207 152.444851 133.818207 152.487336 135.135231 154.726298 134.740124 155.452793 136.30356 156.234517 136.401275 156.531913 137.102272 156.531913 137.102272 157.61953 138.21537 156.931272 138.614726 157.054479 140.042211 156.463937 140.607257 157.211674 141.023606 157.232916 141.635386 158.082617 142.272656 158.01889 143.118101 158.843099 144.622058 160.784666 145.964573 161.264747 147.761675 161.685349 148.029328 161.307232 149.529037 160.041178 150.438209 160.143142 151.496077 160.143142 151.496077 159.076767 151.287902 159.047028 150.858807 158.456486 150.642135 157.980653 151.721246 156.667865 151.453592 156.799569 151.058485 156.124056 150.837565 156.017844 151.385617 155.202131 151.976154 154.360927 150.370233 153.647178 150.459451 153.137357 149.711721 152.457597 150.382979 151.714108 150.467948 150.983365 150.395724 150.567012 149.864666 150.06144 150.493439 149.594104 149.911399 149.394425 150.425463 148.468251 150.586905 147.469852 151.65327 146.276022 151.640525 146.276022 151.640525 145.362594 152.158838 145.213896 152.876829 144.300467 153.182718 143.395536 152.723884 142.936697 151.228424 142.256937 150.846062 141.96379 151.032994 140.999379 150.421215 140.498055 150.820571 139.750319 150.599651 139.265989 150.888546 138.662701 150.251276 138.802902 149.486552 137.6728 149.32511 137.562339 148.679344 137.027027 149.197656 136.636165 148.794052 136.130593 149.014972 136.253799 148.458423 135.633517 148.462672 135.633517 148.462672 135.794961 146.563607 135.293637 146.397917 134.80081 145.514236 135.238406 144.388392 135.837446 144.388392 136.011634 143.938055 135.709991 143.738377 135.901173 142.642272 135.336122 141.932778 135.336122 141.932778 134.486421 141.410217 133.513514 139.693836 134.541652 139.247747 134.511912 138.703944 135.353116 138.767671 135.48482 138.194128 136.483218 137.510124 136.266545 137.042793 136.619171 136.422517 137.409392 136.159112 137.124743 135.551581 137.277689 134.442731 136.746626 134.132593 136.368509 133.146949"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-66",style:({display:_vm.props.displayDep['FR-dep-66']}),attrs:{"fill":"#6b6b6b","points":"129.549658 217.360058 130.08497 217.65745 131.342528 216.289444 131.93307 216.463631 132.910226 215.834858 132.714795 214.080241 132.311187 213.540686 132.621327 213.120088 135.501814 212.886422 139.682343 213.247542 140.578777 211.790318 141.908559 211.263508 145.077944 213.158324 145.077944 213.158324 144.908004 217.304827 145.222393 220.223524 146.802837 220.882037 146.484199 221.132696 147.38913 222.815089 145.829929 223.036009 145.026962 221.901669 143.726919 222.084353 143.374293 221.684997 142.855976 222.301024 141.475212 222.241546 140.396091 223.358893 139.605869 223.069997 138.569234 223.53308 138.165626 223.970672 138.518252 225.062528 136.840093 224.64193 136.020131 225.24946 135.17043 225.075273 134.303735 223.80923 131.198078 222.747113 130.220922 223.278172 129.409458 223.091239 128.173143 224.548463 126.890094 224.794874 126.142358 224.263816 125.50933 222.390242 124.566162 222.47946 123.546521 221.574537 121.881107 221.383356 122.046799 219.934628 122.8965 219.492788 122.8965 219.492788 124.353737 219.344092 124.910291 218.507144 125.496585 218.757803 126.201837 218.464659 126.630936 217.406791"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-67",style:({display:_vm.props.displayDep['FR-dep-67']}),attrs:{"fill":"#6b6b6b","points":"216.193666 69.6153767 217.761365 69.8235516 219.524494 68.1496557 219.775156 67.1385206 219.5075 67.0832905 220.221249 66.3015726 219.532991 66.2378456 219.125135 65.4476308 219.524494 65.2734437 220.399686 63.4423545 219.928102 62.6181519 219.239844 62.3589954 219.431027 61.9766334 218.781006 61.7854524 218.751266 62.0828451 218.105494 61.2586425 217.362005 61.4753144 217.073107 62.5161887 216.223406 62.9835201 216.023726 62.5756672 216.414589 62.2655292 215.760319 62.1465721 215.743325 61.3945935 216.537795 61.2119094 216.520801 60.9017713 215.734828 60.9952376 215.70084 60.5788879 214.043923 60.0733203 213.85274 59.0069552 214.464525 59.0112036 214.672702 58.259225 215.411942 58.3951759 215.382202 57.5709734 216.206412 55.8673382 216.032223 55.4679823 216.576032 55.1450988 216.907415 55.3575222 216.631262 55.5487032 217.073107 57.0611574 217.901565 57.0654058 218.572829 57.7621544 219.044413 57.5369856 220.038563 57.9193477 219.979084 58.4079213 220.599366 58.7350533 222.825583 58.0637955 224.321056 59.0281975 225.056048 58.3696851 226.084186 55.7823689 226.084186 55.7823689 226.729958 56.0245315 227.766594 55.701648 227.817576 56.0500223 228.854211 55.4509885 230.128762 56.3176757 230.519625 55.9098229 231.07193 56.0967554 231.318344 55.6634118 232.074578 56.4748689 234.207327 57.4775071 236.58649 58.1275225 235.970457 58.3654367 235.116507 59.7759276 234.067126 62.5034433 233.013497 62.9410354 232.767084 63.6207901 231.993856 63.7100079 231.853655 64.648919 229.65293 66.8028917 228.964672 68.3195943 229.070885 70.1634289 228.408118 70.8134443 227.775091 73.3540275 227.991764 75.1341351 227.082584 75.8053928 226.611 77.9211293 225.030557 80.5849179 225.030557 80.5849179 224.010915 80.4234762 224.015164 79.8201939 223.135723 79.5440436 223.25893 78.401206 220.412432 77.1309144 220.705579 76.6210984 220.017321 76.6508377 220.166018 76.3194573 219.719925 75.7204235 218.360404 75.5802241 218.360404 75.5802241 218.020524 74.8027547 216.907415 74.9726933 216.185169 74.4671258 216.754469 73.8001165 216.342364 73.2520643 216.928658 70.9876315 216.427334 70.4608216 217.209059 70.6137664 216.928658 70.1889197 216.928658 70.1889197 216.248897 70.218659"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-71",style:({display:_vm.props.displayDep['FR-dep-71']}),attrs:{"fill":"#6b6b6b","points":"165.649204 105.769829 165.691689 106.062973 166.260989 105.990749 166.77081 107.010381 168.053858 107.176072 168.198307 108.157467 169.039511 107.660397 169.281676 107.787851 169.018269 108.425121 169.477107 108.820228 170.556227 108.92644 170.900356 109.461747 171.716069 109.266317 171.597111 109.997053 171.932743 109.975811 172.769698 111.30983 173.921043 111.590229 173.670381 111.976839 174.069741 112.371946 174.681526 111.811149 175.713912 111.904615 175.871107 111.534998 177.697964 111.050673 177.723455 110.651317 179.546064 110.859492 179.639531 110.506869 180.450995 110.273204 181.389915 110.889232 181.300696 111.22486 181.708553 110.753281 183.017092 110.570596 183.178535 110.179738 184.091964 110.31144 184.091964 110.31144 184.019739 111.237606 184.232164 111.632713 184.852446 111.441532 185.379261 112.69483 186.611327 112.567376 186.789764 113.293864 187.707441 113.55302 187.741429 114.232775 186.77277 114.088327 185.341024 114.547161 185.574692 115.184431 186.466878 115.702744 185.927318 116.743619 186.492369 116.777606 186.768522 117.563573 186.539103 117.890705 187.354816 119.101518 186.551848 119.798266 186.85774 120.061671 186.700546 120.533251 186.700546 120.533251 186.339423 120.5375 186.339423 120.5375 185.957057 120.626717 185.910324 122.117929 186.997941 122.55977 186.929965 123.209785 185.285794 123.562408 185.120102 123.991503 185.120102 123.991503 183.735089 123.69411 183.212523 122.479049 182.54126 122.134923 182.520017 122.521534 181.967711 122.402576 180.582699 123.027101 179.29965 122.245383 178.709108 122.602254 178.539168 122.147668 175.696918 130.865522 175.696918 130.865522 174.741005 130.772056 174.906696 129.947853 174.626295 129.565491 174.92369 129.416795 173.925292 128.76678 174.473349 128.265461 173.933789 127.564464 173.181803 127.696166 172.846171 128.596841 172.230138 128.503375 171.843524 127.853359 171.580117 128.380169 170.828132 128.545859 170.556227 128.010553 169.477107 127.653681 168.941795 128.150752 168.780352 129.748176 168.780352 129.748176 168.325762 130.279234 168.482957 130.653099 167.718226 130.640354 166.796301 131.37109 166.345959 131.31586 166.426681 130.576627 165.921109 130.827286 165.602471 130.402439 165.084153 130.925001 164.285434 130.840032 163.754371 130.291979 162.267394 130.967486 162.267394 130.967486 161.82555 130.967486 161.82555 130.967486 161.460178 130.194265 160.00719 129.896872 160.342822 129.081166 160.160136 128.333436 160.160136 128.333436 160.130396 127.891596 160.958855 127.704663 160.997091 127.169356 161.719337 127.343543 162.020981 127.02066 161.672604 126.986672 161.519658 125.210813 161.936011 123.596396 161.107553 123.192791 161.11605 122.814678 159.590836 122.903896 159.102258 121.820537 158.511716 122.14342 157.390111 121.633604 157.292395 120.01069 156.888787 119.840751 157.033236 119.37342 156.608386 118.553466 155.559005 117.32141 155.393314 116.238051 155.393314 116.238051 155.937122 116.531195 157.309389 116.153082 158.112357 116.577929 158.35877 117.46161 160.665708 116.454723 161.889277 115.469079 162.539299 115.239661 162.90467 115.498818 162.709239 113.986364 163.703389 113.200397 162.603026 112.117038 162.917416 110.89348 162.62002 110.166992 162.105951 110.53236 161.834047 110.166992 162.951404 109.436256 163.24455 108.352897 162.624269 107.758111 162.832446 106.878679 164.017778 106.577038 164.017778 106.577038 165.075656 106.861685 165.266839 105.897283 165.266839 105.897283"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-72",style:({display:_vm.props.displayDep['FR-dep-72']}),attrs:{"fill":"#6b6b6b","points":"90.3954378 73.7024018 91.3683454 73.2733066 91.6402497 73.7108987 92.4092291 73.7916196 92.6471453 72.9759139 94.1553646 72.1049782 93.9386908 71.6801316 94.4570084 71.8628156 94.4315174 71.4762052 96.6959705 71.0641039 97.6646296 71.6376469 98.1319651 72.5468188 97.9152913 72.8951931 98.3019053 75.2785829 99.839864 75.8946106 100.02255 75.588721 100.082029 76.2599788 100.829766 77.2116353 102.388967 77.2838593 102.652374 77.6237366 102.524919 77.0162058 102.915782 76.8122794 103.446845 77.0586905 104.402758 78.8090588 104.797869 79.0129852 105.456387 78.6518656 105.456387 78.6518656 107.538155 80.1133381 106.395307 80.257786 106.221118 81.0352554 106.221118 81.0352554 105.719795 81.3029088 106.293343 81.2731696 106.255106 81.8552095 105.384163 81.9189365 105.388411 82.5052249 105.630576 82.9343201 106.242361 82.9937986 105.826007 84.1408846 106.361319 85.3474492 105.770777 85.5556241 105.698552 86.7027101 105.320435 86.0866824 104.806366 86.3798266 105.065525 87.9262685 103.608288 89.4514681 103.769731 89.8763148 102.439949 90.2799191 101.649727 91.2740604 102.002353 92.026039 102.261512 91.7456402 102.261512 91.7456402 101.908886 92.3574194 100.884996 92.7482783 100.884996 92.7482783 100.515376 92.7610237 100.515376 92.7610237 99.5807053 93.0626649 99.3215465 93.7339226 98.879702 93.8061466 98.1574561 93.0966526 97.8260727 93.7211772 98.5058335 94.7917909 98.072486 95.0551958 95.9694761 93.9420975 95.421419 93.9973276 95.421419 93.9973276 95.0050655 94.1757632 94.6524396 93.810395 94.6269486 94.2862233 93.9386908 94.7195669 93.4076277 94.0950423 92.4772051 94.1502724 91.6147587 93.6489533 91.3555999 93.0329256 91.0242165 93.2793367 90.1150364 92.7015452 89.766659 92.8969747 89.7454165 92.5953335 89.4267787 93.3175729 88.9594431 93.364306 87.9100624 92.9267139 87.5489395 92.0855175 88.3051734 91.189091 87.8463348 90.9809162 87.2472957 91.5289684 86.3466126 91.1083702 84.9828426 91.2443211 85.1102977 90.7897352 84.6472107 90.0505019 84.6472107 90.0505019 84.5749861 88.9374036 83.8357463 88.831192 83.5341024 88.1769281 84.1586326 87.4674341 84.8383934 87.4801795 84.7916599 86.7239524 84.1968692 86.5327714 84.1118991 85.9719738 86.5080558 84.8716209 86.0449688 84.6591976 85.9515017 83.6098263 85.365208 83.452633 85.365208 82.6496728 86.5972744 82.042142 87.3067747 82.2120807 87.1878166 81.2009456 87.570182 80.8143351 86.8904213 80.1600713 87.1878166 79.875424 86.9159123 79.6077706 88.823491 78.3629698 88.436877 76.8122794 88.9339521 76.03481 88.6025687 74.7900093 89.2270989 74.8367424 88.9679401 74.1654846"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-73",style:({display:_vm.props.displayDep['FR-dep-73']}),attrs:{"fill":"#6b6b6b","points":"194.177914 136.8856 194.802445 137.144756 194.968136 139.515401 195.507696 139.693836 195.681885 140.280125 196.552829 140.059204 197.105134 141.70761 197.976078 141.924281 198.180006 141.626889 198.999967 142.030493 199.012713 141.342241 199.416321 141.648131 200.134318 141.503683 200.661133 142.085723 200.593157 143.041628 201.132717 142.744235 201.230432 143.356015 203.014804 143.079864 203.771038 141.546168 203.609595 141.1808 204.709958 140.301367 205.164548 138.810155 206.201183 137.641827 207.297297 138.351321 207.246315 138.818652 206.643028 138.827149 207.093369 139.897763 207.760384 140.577517 208.869244 140.394833 209.396059 141.134067 209.374816 142.217426 209.79117 142.323637 210.547404 141.231781 211.329128 140.904649 211.329128 140.904649 211.813458 142.846199 213.164482 143.623668 213.355665 144.129236 214.855387 144.443622 214.443282 145.735156 214.770417 146.075033 214.868133 147.846644 215.811301 148.08031 215.713585 148.641107 216.61002 148.704834 216.869179 149.618255 218.152227 150.310755 217.723128 151.43235 216.792706 152.26505 217.230302 154.049406 216.291382 155.030801 214.872381 154.945832 213.886728 156.203378 212.960554 156.241614 212.956306 157.031829 212.174581 157.269743 210.742835 156.462535 210.292493 156.997842 209.200627 156.934115 208.333932 157.698839 208.333932 157.698839 207.40351 157.329222 206.761986 157.928256 206.20968 157.690342 205.678617 158.098194 205.789078 159.041354 205.164548 159.117826 203.643583 158.667489 203.630838 157.86028 203.087029 157.346216 202.394523 157.724329 201.803981 157.265495 201.803981 157.265495 201.612798 156.649467 201.132717 156.921369 200.38498 156.301093 199.739207 156.568746 200.053597 155.710556 199.352593 154.334053 199.679728 153.883715 199.505539 153.284682 200.486944 152.451982 200.644139 151.606537 200.202294 151.479083 200.601654 151.198684 199.518285 149.571522 198.073793 149.512043 196.608059 148.131291 195.694631 148.513653 195.477957 150.230034 195.919801 150.081338 195.592666 150.688868 195.265532 150.650632 193.498154 149.41008 192.605968 149.431322 192.138632 147.748929 191.441877 147.115908 191.441877 147.115908 191.441877 146.839757 191.441877 146.839757 190.592176 145.080892 190.592176 145.080892 191.68829 144.371398 191.947449 142.633775 192.784405 142.761229 193.243243 142.285401"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-74",style:({display:_vm.props.displayDep['FR-dep-74']}),attrs:{"fill":"#6b6b6b","points":"211.197425 126.009525 210.815059 126.238942 211.057224 126.846473 212.467728 128.218728 211.397104 130.164525 211.278146 131.84267 213.062518 132.169802 212.616425 134.017885 212.918069 134.243053 213.738031 133.72474 215.288735 135.88721 215.021079 136.091136 215.513906 136.486244 215.632864 137.246719 214.893624 137.862747 214.787411 138.572241 213.806007 139.205263 211.6945 139.447425 211.329128 140.904649 211.329128 140.904649 210.547404 141.231781 209.79117 142.323637 209.374816 142.217426 209.396059 141.134067 208.869244 140.394833 207.760384 140.577517 207.093369 139.897763 206.643028 138.827149 207.246315 138.818652 207.297297 138.351321 206.201183 137.641827 205.164548 138.810155 204.709958 140.301367 203.609595 141.1808 203.771038 141.546168 203.014804 143.079864 201.230432 143.356015 201.132717 142.744235 200.593157 143.041628 200.661133 142.085723 200.134318 141.503683 199.416321 141.648131 199.012713 141.342241 198.999967 142.030493 198.180006 141.626889 197.976078 141.924281 197.105134 141.70761 196.552829 140.059204 195.681885 140.280125 195.507696 139.693836 194.968136 139.515401 194.802445 137.144756 194.177914 136.8856 194.177914 136.8856 194.279878 136.014664 193.782803 134.956796 194.033465 132.705108 195.210301 133.168191 195.320762 132.322746 196.442367 131.978621 196.442367 131.978621 197.105134 131.672731 197.874114 131.859664 198.129024 131.494295 199.4758 131.800185 201.306905 130.122041 202.420014 129.633467 202.670676 128.847501 201.812478 128.966458 201.404621 128.307945 201.587307 127.640936 201.102977 127.428513 201.668028 126.200706 206.371123 123.728098 209.25161 123.791825 211.686003 124.492822"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-79",style:({display:_vm.props.displayDep['FR-dep-79']}),attrs:{"fill":"#6b6b6b","points":"75.6573744 110.413403 76.3541292 110.566348 76.5198209 110.052284 77.1316056 110.116011 77.5012255 109.648679 78.784274 110.20098 79.4130527 110.107514 79.5617504 109.703909 80.8193078 109.865351 81.7964639 108.947682 81.5712932 108.131977 82.6589104 107.630657 83.2749436 107.681639 82.8925782 108.408127 83.2961861 108.008771 84.1926207 108.064001 84.3838034 107.435228 85.3779535 107.545688 86.69499 107.057114 87.7741103 107.354507 88.1309847 106.993387 88.874473 107.159078 88.2924279 108.059753 88.7470179 107.974783 89.0953953 108.395382 89.5754763 108.131977 89.5754763 108.131977 90.0215693 109.559461 89.7369195 109.45325 89.8558776 110.149998 90.3954378 109.929078 90.7905487 110.243465 90.573875 110.804262 91.2153992 112.172268 90.7565607 113.017713 91.9758816 113.548772 91.6487467 114.054339 91.1431746 113.714462 90.5356384 114.406962 91.5212915 114.564155 90.9222524 115.260904 90.9902284 115.757974 92.0523547 116.671395 91.3301088 117.096241 91.4108304 117.512591 90.9944769 117.669784 91.2323932 117.933189 90.1660185 119.339432 91.3725939 118.965567 91.1431746 119.687806 91.7762018 119.679309 91.7804503 120.184877 92.1160822 120.265598 91.2451387 120.856135 91.1601686 121.306472 91.5127945 121.548635 91.2621327 122.037208 90.8457793 121.973481 90.8797673 122.993113 90.573875 123.052592 90.641851 123.405215 91.2111507 123.447699 90.9732344 125.176825 91.9758816 125.865077 91.6232557 126.370644 92.0438576 126.578819 91.6572437 127.046151 92.7491094 127.581457 93.5818164 126.476856 94.5079905 126.956933 94.0534004 127.628191 94.4230204 128.248467 93.6752835 128.545859 93.9301938 129.506013 93.3906337 129.926611 93.2971666 130.606366 93.8537207 130.729571 94.4272689 131.490047 95.2429818 131.31586 94.7841433 132.92178 94.7841433 132.92178 94.3677898 133.2829 93.7857446 132.688115 93.1187294 132.666872 93.0847413 133.074725 92.5451812 133.03224 92.4857021 133.512317 91.7124743 134.017885 90.7395667 133.971152 91.0157195 134.591428 90.3699467 135.351903 90.7140756 135.52609 89.5712278 136.10813 89.5712278 136.10813 88.9679401 135.878713 88.9382006 135.351903 88.5006046 135.080001 88.5813262 134.400247 88.0757541 134.676397 87.990784 133.983897 87.6381581 134.238805 86.8266937 134.03063 86.2616425 133.138452 84.9658486 133.440093 84.8213994 132.972762 84.3030818 133.223421 83.9419589 132.989756 83.9674499 132.462946 83.606327 132.909035 82.4677277 132.662624 82.3360241 132.025354 81.6350208 131.634495 80.4581849 131.86816 80.1735351 131.515538 80.7130952 131.328605 79.8761397 131.103436 79.5872414 130.640354 79.187882 130.755062 79.230367 129.807654 78.1087617 129.102409 78.4146541 128.528866 78.1639923 127.606948 78.1639923 127.606948 78.7162979 127.645184 79.0774208 127.067393 79.9653583 127.284065 80.1183045 126.740261 80.7130952 126.561825 80.7895683 126.18796 81.5288081 126.196457 81.872937 125.546442 80.6961012 124.807209 80.4369424 125.380752 80.0673225 125.21931 80.1352985 124.344126 80.6451191 123.753589 79.9568613 122.083941 80.6366221 121.952239 80.7428347 121.365951 80.3137357 120.677699 80.543155 119.496625 79.7614301 119.025045 80.1692866 118.43026 79.4173012 117.843972 79.8294062 117.4871 78.5760973 115.749478 79.0816693 114.683112 77.8665969 114.050091 76.7407432 112.762805 77.1443511 111.828143 76.2351711 111.484017 76.3286382 111.135643 75.9037877 111.186624 76.1629465 110.770274 75.5426648 110.689554"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-81",style:({display:_vm.props.displayDep['FR-dep-81']}),attrs:{"fill":"#6b6b6b","points":"126.482238 181.405283 127.722801 180.835989 127.620837 180.428136 128.355829 180.44513 128.355829 180.789255 128.504526 180.330421 129.158796 180.13924 130.050982 181.086648 130.828458 181.099394 129.91503 181.68993 130.853949 181.783397 131.444492 181.439271 132.060525 182.140268 132.519363 182.029808 133.522011 182.747799 133.89163 183.68671 134.80081 183.826909 135.510311 185.386097 136.037125 185.607017 135.489068 186.388735 136.402497 186.877308 136.372757 187.67602 136.886826 188.062631 136.551194 188.844349 138.26759 190.981327 139.758816 191.661082 141.190562 190.866619 142.010523 191.410422 142.766757 191.380683 143.15762 192.502278 143.15762 192.502278 142.677538 193.44119 141.003628 193.806558 139.971241 194.448076 139.355208 193.704595 138.04242 193.334978 137.511357 195.607908 138.547992 196.835715 138.276087 196.797478 138.21236 197.281804 138.437531 197.948813 136.746626 199.155377 136.746626 199.155377 135.025981 198.734779 133.645217 199.248844 132.052028 198.505362 131.635674 198.658307 131.206575 198.195224 130.560803 198.879227 130.437596 200.03481 129.66012 199.244595 127.935227 199.750163 127.582601 198.904718 127.183241 198.790009 127.183241 198.790009 126.898591 198.441635 127.501879 197.570699 127.365927 196.937678 127.310696 197.239319 126.915585 197.018399 126.949574 197.528215 126.486486 197.766129 125.16945 197.196834 124.030851 196.037003 123.831171 195.340254 121.188601 194.163429 121.371287 193.60688 121.910847 193.398705 121.749404 192.650975 120.734011 192.527769 120.687277 192.251619 121.481748 191.97122 120.491846 191.155514 120.385634 189.931956 119.408477 189.528352 119.48495 188.874088 118.817935 188.275054 118.809438 187.034502 118.809438 187.034502 119.136573 187.076986 119.208798 186.575667 118.461061 186.031864 119.982026 186.023367 120.126475 185.309624 120.640544 185.135437 120.491846 184.76582 121.205595 184.5449 121.443511 183.958612 120.759502 183.43605 120.67878 182.208243 122.182751 182.229486 122.752051 182.718059 123.223635 181.983075 123.763195 182.471648 123.848165 181.52424 125.075983 181.859869 125.046243 181.086648 125.662277 181.477507"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-82",style:({display:_vm.props.displayDep['FR-dep-82']}),attrs:{"fill":"#6b6b6b","points":"110.180725 175.78881 111.442531 175.436187 111.391549 175.979991 110.618321 175.975743 110.47812 176.430329 111.21736 177.3395 112.232753 177.496694 112.21151 177.95128 112.810549 178.50358 112.946502 178.180697 113.430831 178.53332 113.549789 178.082982 114.382496 177.845068 114.02987 179.298044 114.977287 179.455237 115.389392 180.088258 117.046309 178.860452 117.352201 177.794086 117.564626 178.397369 118.308115 178.703258 118.567273 179.476479 119.370241 179.013396 118.983627 178.359132 119.123828 177.670881 119.909801 177.789838 119.905552 178.218933 120.355894 178.456847 120.53858 178.06174 120.236936 177.738856 120.661786 178.159455 121.367038 177.403227 122.127521 177.250283 122.692572 177.607154 123.049446 176.867921 123.618746 177.127077 124.595902 176.761709 124.595902 176.761709 124.183797 177.216295 124.591653 177.624148 124.387725 177.913043 126.155103 178.388872 125.87895 179.119608 125.505082 179.072875 125.207687 179.884332 124.753097 179.999041 125.114219 180.542844 125.479591 180.457875 125.590052 180.954946 125.713259 180.615068 126.24857 180.674547 126.664924 181.188611 126.482238 181.405283 126.482238 181.405283 125.662277 181.477507 125.046243 181.086648 125.075983 181.859869 123.848165 181.52424 123.763195 182.471648 123.223635 181.983075 122.752051 182.718059 122.182751 182.229486 120.67878 182.208243 120.759502 183.43605 121.443511 183.958612 121.205595 184.5449 120.491846 184.76582 120.640544 185.135437 120.126475 185.309624 119.982026 186.023367 118.461061 186.031864 119.208798 186.575667 119.136573 187.076986 118.809438 187.034502 118.809438 187.034502 118.414327 186.949532 117.69633 187.820468 117.437171 187.297907 116.948593 188.224072 115.461616 187.760989 115.159973 188.249563 115.351155 188.687155 114.225301 188.831603 115.287428 189.065269 115.346907 189.524103 113.736723 189.978689 113.851433 190.305821 112.793555 190.730668 112.83604 190.110392 112.203013 190.055162 111.67195 189.396649 110.966698 190.038168 110.499363 189.562339 110.032027 189.982938 108.20517 190.242094 108.20517 190.242094 107.168535 190.148628 107.69535 189.209717 107.160038 188.971803 107.453185 188.648919 106.934867 188.09237 107.32573 187.544318 106.603484 186.936787 104.984803 186.966526 104.776627 186.6224 105.96196 185.143934 105.745286 184.421695 106.267852 184.451434 106.463283 183.877891 105.889735 183.682461 104.891336 184.306986 104.48348 183.43605 104.48348 183.43605 104.759633 182.420667 105.515867 182.280467 105.337429 181.532737 106.620478 181.949087 107.185529 181.672937 107.032583 180.763765 106.577993 180.861479 106.467532 180.542844 107.406451 180.381403 107.788817 178.520574 108.183928 178.333642 107.572143 177.649639 107.177032 177.81108 106.752181 177.462706 107.228014 175.716586 107.652864 175.648611 108.022484 176.600267 108.209419 176.196663 109.93856 176.171172"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-85",style:({display:_vm.props.displayDep['FR-dep-85']}),attrs:{"d":"M51.6915585,109.070888 L52.2821007,109.546716 L52.05693,110.447391 L53.3527239,111.084661 L53.4801791,112.490903 L52.4477924,110.855244 L51.5088728,110.817008 L50.8248636,109.997053 L50.9948038,109.474492 L50.7016569,109.134615 L51.6915585,109.070888 Z M56.4881205,109.0369 L57.1508873,109.963066 L57.6012288,110.039538 L57.8773816,110.791517 L59.1476846,110.995443 L59.2496487,111.58598 L60.6006732,111.636962 L61.0170267,112.779799 L61.6458054,112.618357 L62.0706559,113.030459 L64.0886957,113.38733 L64.1481748,113.034707 L64.9129057,112.869017 L64.1141867,111.713434 L64.4073336,111.785658 L64.5857708,111.326824 L63.923004,110.324185 L64.1736658,109.754891 L63.939998,109.432007 L64.8746691,108.78624 L65.4269747,108.985918 L65.5841694,111.666701 L67.3133109,110.880735 L67.0753946,108.964676 L67.6489428,108.62055 L68.4476617,108.905198 L68.9744763,107.59667 L70.1895487,108.752253 L71.1454623,109.053894 L71.1454623,109.053894 L72.7768881,109.682667 L74.1619007,109.317299 L74.425308,109.912084 L74.9733651,109.589201 L75.6488774,110.413403 L75.6488774,110.413403 L75.5341678,110.685305 L76.1544495,110.766026 L75.8952907,111.182376 L76.3201412,111.127146 L76.2266741,111.47552 L77.1401026,111.819646 L76.7364947,112.754308 L77.8623484,114.041594 L79.0774208,114.674615 L78.5718488,115.740981 L79.8209092,117.478603 L79.4088042,117.835475 L80.1607896,118.421763 L79.7571816,119.016548 L80.534658,119.488128 L80.3052387,120.669202 L80.7343377,121.357454 L80.6281251,121.943742 L79.9483643,122.075445 L80.6408706,123.74934 L80.1268015,124.335629 L80.063074,125.210813 L80.4326939,125.372255 L80.6918527,124.80296 L81.86444,125.542193 L81.5203111,126.18796 L80.7810713,126.179463 L80.7130952,126.561825 L80.1183045,126.740261 L79.9611098,127.284065 L79.0731723,127.067393 L78.7120494,127.640936 L78.1639923,127.6027 L78.1639923,127.6027 L77.2123272,127.067393 L77.2463152,126.634049 L76.5580575,126.693528 L76.3923658,127.271319 L75.7338475,127.037654 L74.9223831,127.445507 L74.425308,127.156611 L74.9818622,125.907562 L74.1916402,126.404632 L72.7981307,126.447117 L72.4242622,127.275568 L71.7020164,127.292562 L71.7020164,127.292562 L70.1895487,127.335046 L70.1173241,128.482132 L68.1842544,126.850721 L68.5411288,127.942577 L67.6956763,126.642546 L65.5034478,126.638298 L64.6792379,125.070614 L62.3085722,124.658512 L60.0271251,122.806181 L59.4068434,122.746702 L58.62087,119.806763 L57.0149351,117.563573 L56.6962973,117.665536 L55.8211053,116.289033 L53.5906402,114.372974 L53.4164515,112.6396 L53.9560116,112.614109 L53.9899997,112.138281 L55.3282787,110.621578 L55.600183,109.555213 L56.4881205,109.0369 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-86",style:({display:_vm.props.displayDep['FR-dep-86']}),attrs:{"fill":"#6b6b6b","points":"89.5754763 108.131977 89.8558776 107.188817 90.7395667 107.558434 90.6673421 106.84894 91.2536357 105.650872 91.7209713 105.264261 92.34975 105.548909 92.34975 105.548909 92.795843 106.01624 92.812837 106.70874 93.8707147 106.555795 93.7772476 107.01463 94.2573287 107.176072 94.6057061 106.810703 94.9285924 107.422483 94.546227 108.263679 95.1580117 108.395382 95.7230628 107.940796 96.1096768 108.047007 96.1309193 108.688526 96.742704 108.467605 96.9126442 109.831363 96.5345273 110.03529 97.1038269 111.581732 97.8388183 111.097406 99.1303637 111.611471 100.311448 110.855244 101.947122 110.897728 101.407562 109.635934 102.287003 109.597698 102.575901 110.154247 103.591294 110.468633 103.816465 112.286977 104.661917 113.408572 105.435145 113.66348 105.766528 115.218419 106.692702 116.272039 106.692702 116.272039 107.487173 116.815843 107.737835 117.567821 107.12605 119.313941 107.444688 120.03618 108.379359 120.800905 109.275793 120.911365 109.36926 121.697331 110.660806 121.684586 111.603974 122.4748 111.395797 122.678727 111.799405 123.388221 111.400046 123.349985 111.680447 123.932025 112.334717 124.127454 112.249747 124.450337 112.759567 124.310138 112.164777 125.554939 112.164777 125.554939 111.799405 125.431733 111.315076 126.502347 110.822249 126.081749 109.879081 126.119985 109.101605 128.235721 107.304487 128.006304 106.344325 129.013191 106.365568 129.489019 105.668813 129.472025 105.371417 129.939357 106.127651 130.784801 106.323082 131.84267 105.923723 132.089081 105.923723 132.089081 105.681558 131.804433 105.239714 132.046596 103.956665 131.800185 103.28965 132.513927 103.502075 132.909035 102.291251 132.994004 101.887643 133.512317 100.893493 133.138452 100.056538 131.876657 99.2025883 132.764587 99.3003039 133.146949 99.831367 133.159694 99.8611066 133.678007 98.905193 134.115599 98.2594202 133.605783 96.262623 133.813958 95.9099971 133.295645 94.7841433 132.92178 94.7841433 132.92178 95.2429818 131.31586 94.4272689 131.490047 93.8537207 130.729571 93.2971666 130.606366 93.3906337 129.926611 93.9301938 129.506013 93.6752835 128.545859 94.4230204 128.248467 94.0534004 127.628191 94.5079905 126.956933 93.5818164 126.476856 92.7491094 127.581457 91.6572437 127.046151 92.0438576 126.578819 91.6232557 126.370644 91.9758816 125.865077 90.9732344 125.176825 91.2111507 123.447699 90.641851 123.405215 90.573875 123.052592 90.8797673 122.993113 90.8457793 121.973481 91.2621327 122.037208 91.5127945 121.548635 91.1601686 121.306472 91.2451387 120.856135 92.1160822 120.265598 91.7804503 120.184877 91.7762018 119.679309 91.1431746 119.687806 91.3725939 118.965567 90.1660185 119.339432 91.2323932 117.933189 90.9944769 117.669784 91.4108304 117.512591 91.3301088 117.096241 92.0523547 116.671395 90.9902284 115.757974 90.9222524 115.260904 91.5212915 114.564155 90.5356384 114.406962 91.1431746 113.714462 91.6487467 114.054339 91.9758816 113.548772 90.7565607 113.017713 91.2153992 112.172268 90.573875 110.804262 90.7905487 110.243465 90.3954378 109.929078 89.8558776 110.149998 89.7369195 109.45325 90.0215693 109.559461"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-87",style:({display:_vm.props.displayDep['FR-dep-87']}),attrs:{"fill":"#6b6b6b","points":"112.164777 125.554939 112.38145 125.767362 112.712834 125.423236 112.878525 126.043513 113.588026 125.724878 114.378248 125.899065 114.548188 125.300031 115.266185 125.176825 116.349554 126.544832 116.349554 126.544832 116.859374 126.820982 116.833883 127.632439 116.319814 128.197485 116.740416 128.439648 116.166868 128.635077 116.404784 129.191626 115.614563 129.87563 116.298572 130.848528 117.131279 130.801795 117.029315 131.48155 117.556129 131.906397 117.777052 131.73221 117.968234 132.22928 117.568875 132.671121 118.546031 133.321136 118.724468 134.463974 118.410079 135.092747 119.187555 135.874465 118.019216 137.042793 118.987875 137.488882 119.378738 137.059787 120.304912 137.208483 120.024511 137.442149 120.377137 138.07517 119.982026 137.883989 119.960783 138.359818 119.875813 138.100661 119.463708 138.334327 119.680382 138.941858 120.67878 139.506904 121.885356 139.328468 122.628844 138.699695 122.654335 139.566382 123.606 140.144174 123.758946 139.749066 124.583156 140.4883 124.910291 141.1808 124.447204 141.503683 124.408968 142.234419 124.859309 142.943913 124.859309 142.943913 125.071734 143.406996 124.574659 143.364512 124.438707 143.78511 123.707964 143.84034 122.892251 143.377257 122.743554 143.9508 121.549724 144.401137 120.279421 145.896598 119.531684 145.977319 119.306513 146.521122 117.717572 146.686813 117.454165 146.291705 117.008072 146.572104 116.825386 147.362319 116.213602 147.260356 115.198209 148.747319 114.068107 148.169528 113.494559 149.333607 113.494559 149.333607 113.163175 149.422825 113.192915 149.070202 112.717082 148.853531 112.15628 149.010724 111.892872 148.479666 111.157881 148.539144 111.960848 147.285846 111.068662 146.733546 110.533351 147.154144 109.343769 145.153116 108.064969 145.029911 107.21102 145.395279 106.709696 144.851475 106.289094 145.845616 105.711298 146.053791 105.59234 145.539727 105.061277 145.654435 104.640675 144.978929 105.05278 143.72988 104.101114 143.020386 102.907285 143.20307 102.499428 142.655018 102.499428 142.655018 102.911533 141.894542 103.990653 141.342241 103.935423 140.284373 105.184483 140.603008 105.957711 138.300339 105.702801 137.055538 106.548253 137.433652 107.979999 136.422517 107.720841 135.101244 106.973104 134.50221 106.514265 134.854833 105.741037 134.043375 106.10216 132.883544 105.660316 132.276013 105.923723 132.089081 105.923723 132.089081 106.323082 131.84267 106.127651 130.784801 105.371417 129.939357 105.668813 129.472025 106.365568 129.489019 106.344325 129.013191 107.304487 128.006304 109.101605 128.235721 109.879081 126.119985 110.822249 126.081749 111.315076 126.502347 111.799405 125.431733"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-88",style:({display:_vm.props.displayDep['FR-dep-88']}),attrs:{"fill":"#6b6b6b","points":"216.928658 70.1889197 217.209059 70.6137664 216.427334 70.4608216 216.928658 70.9876315 216.342364 73.2520643 216.754469 73.8001165 216.185169 74.4671258 216.907415 74.9726933 218.020524 74.8027547 218.360404 75.5802241 218.360404 75.5802241 217.88882 75.9116045 215.896271 79.939151 216.338116 80.3385069 216.202163 80.8823106 214.851139 83.2019735 213.85274 83.7755165 213.287689 85.8870045 213.593581 86.137664 213.015785 86.6347346 213.423641 87.7095967 211.953659 88.4360846 211.953659 88.4360846 211.732736 88.6230171 211.732736 88.6230171 211.078467 87.6883544 208.614334 86.2863604 207.8581 85.181759 207.161345 85.5301333 206.804471 86.3033542 205.653126 86.7664371 204.896892 85.2922191 204.085428 84.7951485 202.28831 84.926851 200.822576 85.5471271 200.882055 85.1435228 199.84542 84.6422037 200.117324 84.3957926 199.968626 83.580087 199.539527 83.133998 197.925096 83.6693048 197.270826 84.9056087 196.710023 84.8843663 196.331906 85.4451639 195.949541 85.3856854 196.51884 84.5954706 196.038759 84.353308 195.18481 85.7043204 195.18481 85.7043204 194.517795 84.4000411 193.859276 85.1222805 193.468414 85.0075719 193.604366 83.8519889 193.277231 82.8833385 192.669695 82.4584918 192.312821 82.6114366 191.862479 81.7489978 191.152979 81.5068352 191.165724 81.9316819 190.651655 81.5195806 191.403641 80.9162984 191.161476 80.5849179 191.641557 79.875424 191.577829 79.0427245 192.474264 78.6391202 191.696787 77.5939973 190.885323 77.3900709 190.885323 76.489396 190.358508 76.0645493 189.959149 76.5361291 188.824798 74.5818344 188.026079 74.3609141 187.099905 75.0194265 186.934214 74.3396718 187.367561 73.7491349 186.513612 73.4347483 187.847642 72.6530305 187.847642 72.6530305 188.501912 72.9631685 188.714337 72.3641347 190.027125 72.075239 190.532697 72.521328 190.46897 72.0837359 191.114742 71.6249015 190.898069 71.2935211 192.244845 71.62915 193.069055 70.6392572 193.069055 70.6392572 193.459917 71.0131223 194.704729 70.2866345 195.545933 70.6944873 195.193307 71.365745 195.405732 71.9987666 194.679238 72.9419262 195.35475 73.0821256 195.418478 72.2451776 196.00902 72.3089046 195.533187 73.1925857 196.336155 73.3030459 196.544332 74.5266043 197.266577 74.1612362 198.477401 74.2631994 198.762051 73.6301778 198.838524 74.1952239 199.271872 74.4288896 199.896402 73.6854079 199.875159 72.9631685 201.332396 73.2435673 202.428511 72.2834138 203.02755 73.0778771 203.953724 72.8102237 204.076931 73.4304999 206.630282 72.3641347 207.433249 72.776236 207.220824 72.1177236 208.172489 71.2722788 208.588843 72.457601 210.696101 72.9206839 210.730089 73.2308219 211.108206 73.158598 211.010491 73.5154692 211.647766 73.094871 211.647766 73.094871 211.826203 72.7507452 211.826203 72.7507452 212.442237 72.2239353 212.939312 72.7337513 214.468773 71.1150855"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-89",style:({display:_vm.props.displayDep['FR-dep-89']}),attrs:{"fill":"#6b6b6b","points":"143.187359 79.4590742 143.191608 78.9237674 144.372692 78.3034913 145.128926 77.1733991 144.563875 75.7119265 145.128926 74.2759448 146.118827 74.5520951 146.773097 73.923322 148.778391 74.1357454 149.271218 73.6854079 150.214386 73.9997944 150.724207 73.931819 150.919638 73.2010827 151.599399 73.4602391 151.599399 73.4602391 151.845812 74.2971871 152.521324 73.8298558 153.179842 74.0890122 153.885094 75.3295645 154.654074 75.8351321 154.479885 76.2727242 155.214876 76.6763285 154.89199 77.6322335 155.257361 77.8149176 154.509624 78.8982766 155.418805 78.7580772 156.068826 80.0751019 157.05023 79.6502553 156.901533 79.174427 157.500572 79.3953472 157.347626 80.2747799 158.256806 80.6613903 158.804863 82.6029397 159.680055 83.3676637 159.658812 83.6990441 159.102258 83.6608079 159.32318 84.2088601 159.824504 84.3193202 160.079414 83.7202864 160.410798 84.2938294 160.083663 85.6193511 161.693846 85.5896118 162.016733 85.2709768 162.726233 85.7255628 162.998137 85.1860075 163.520703 85.2072498 163.881826 85.7043204 164.548841 85.1307774 164.548841 85.1307774 164.888722 84.8206393 164.888722 84.8206393 165.509004 85.2964676 165.691689 84.7611608 165.3773 84.6167129 165.933854 84.4935074 165.857381 85.0925412 166.25674 85.5938603 167.106441 85.7298112 167.106441 85.7298112 167.437825 86.2523726 166.282231 87.0680783 166.592372 87.0765752 166.71133 87.9814986 167.76496 87.7690753 167.82019 90.1312228 167.089447 90.743002 166.515899 90.5178333 166.146279 90.9851646 166.949247 92.0812691 165.950848 92.1704869 166.341711 92.6590605 165.215857 94.7025731 165.215857 94.7025731 165.011928 95.475794 165.011928 95.475794 163.962548 96.4359475 164.102748 98.1735704 163.677898 98.6409018 163.333769 98.3902422 163.44423 98.8915613 163.091604 99.2356871 163.537697 99.6053037 163.363509 100.05989 163.826596 100.034399 163.822347 100.998801 163.822347 100.998801 162.709239 100.986055 162.428838 101.657313 161.986993 101.759276 161.45593 101.19423 161.460178 100.051393 161.043825 99.5755645 160.963103 99.9239387 159.697049 100.319046 159.471878 99.6265461 160.079414 99.0572515 159.590836 98.5261932 158.792117 99.380135 158.724141 99.9791688 158.18883 99.2994141 157.113958 99.4736013 156.200529 98.1268373 155.168143 97.7104875 154.658322 97.9738925 154.539364 96.9032789 153.366777 96.121561 153.103369 95.2463768 152.912187 96.9924967 151.221282 96.6823586 150.418314 97.6382636 149.317952 96.7290917 149.131017 97.0732175 148.408772 97.1199507 147.881957 96.2277726 147.197948 96.3467297 146.445962 95.794429 146.488447 94.8470209 146.097585 94.5496283 144.674336 95.3568369 143.884114 95.029705 143.884114 95.029705 143.178862 94.2607325 143.106637 93.5384932 143.476257 93.054168 142.868721 92.4423887 142.996176 92.0940145 142.163469 91.5247199 142.154972 91.0871278 141.615412 91.1763456 141.683388 90.1184774 144.74656 89.3452564 144.529887 88.121698 144.874015 87.6968513 144.223994 87.2380169 144.525638 87.0723267 144.461911 86.2183849 145.184156 86.0909309 145.774699 85.2624799 145.774699 85.2624799 145.982875 85.118032 145.982875 85.118032 146.556423 84.4680166 146.505441 83.5758386 146.050851 83.2402097 146.496944 82.9683078 145.175659 81.8254702 144.929246 80.6741357 144.580869 80.6996265 144.818785 80.2450406"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-90",style:({display:_vm.props.displayDep['FR-dep-90']}),attrs:{"fill":"#6b6b6b","points":"211.953659 88.4360846 212.484722 89.468462 213.789013 89.7701031 215.492663 91.0276493 215.318474 91.6139377 215.658355 92.3616679 215.04657 93.2156097 214.944606 94.1757632 215.212262 94.5283859 215.577633 94.2182479 216.367855 94.4179258 217.374751 96.1937849 217.187817 96.758831 217.187817 96.758831 216.189418 97.1454415 215.267492 96.7248433 214.549495 96.9924967 214.880878 98.0461164 213.793261 98.5856717 213.793261 98.5856717 213.202719 97.5363004 213.385405 96.8692911 213.93771 97.2134169 213.861237 96.4869291 213.240956 96.1725426 213.105003 95.539521 211.618027 95.5989996 211.435341 95.2081406 211.435341 95.2081406 211.690251 94.9149964 210.946763 93.8613766 211.333377 93.2580944 210.581392 90.3818823 211.732736 88.6230171 211.732736 88.6230171"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-07",style:({display:_vm.props.displayDep['FR-dep-07']}),attrs:{"fill":"#6b6b6b","points":"175.284813 151.283654 175.40802 152.367013 176.083532 153.017028 176.083532 153.017028 176.20249 156.224621 176.606098 156.496522 176.104775 157.316477 176.57211 158.60801 177.115919 158.964882 176.674074 160.209682 177.570509 161.955802 177.005458 162.907459 176.822772 164.279714 175.369783 166.03433 175.709664 168.821325 175.012909 170.592935 174.175953 171.298181 174.29916 173.728304 173.687375 174.5695 173.411223 176.965635 173.411223 176.965635 173.38998 178.427108 173.38998 178.427108 171.788294 177.64539 171.618354 177.080344 170.968332 176.999623 170.849374 176.625758 169.931697 176.757461 169.664041 178.112721 168.916304 177.938534 168.852577 176.562031 167.696984 176.761709 167.017223 177.331004 167.029968 177.891801 166.477663 178.563059 165.088402 177.263028 164.459623 177.377737 164.230204 176.804194 162.857937 177.280022 162.581784 176.986878 162.964149 176.141433 162.654008 175.461678 163.155332 175.113304 162.581784 174.769178 162.607275 174.119163 161.914768 173.779285 161.914768 173.779285 161.315729 171.434132 160.232361 170.461233 159.497369 166.760818 159.497369 166.760818 160.593483 166.157536 160.368313 165.92387 161.048073 164.713057 161.736331 165.095419 161.634367 164.564361 161.944508 164.887244 162.042224 164.436907 162.441583 164.547367 162.552044 163.587213 164.701788 163.57022 165.908363 161.331278 167.476061 161.084867 167.063956 160.498578 167.170169 159.933532 168.686885 159.389728 168.045361 158.722719 168.270532 157.941001 168.975784 157.800802 168.533939 156.832151 169.400634 157.019084 169.625805 157.677596 169.672538 157.36321 170.00817 157.452427 169.75326 156.789667 170.394784 155.668071 170.454263 154.529482 170.454263 154.529482 172.553025 154.164114 172.370339 153.586323 172.93539 153.144482 172.799438 152.634666"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-09",style:({display:_vm.props.displayDep['FR-dep-09']}),attrs:{"fill":"#6b6b6b","points":"106.514265 213.523692 105.953463 211.33998 106.990098 210.915133 106.820158 210.303354 108.621524 209.925241 109.437237 209.04156 108.795712 208.659198 108.65976 207.554596 109.14409 207.312434 108.85944 207.070271 109.042126 206.539213 109.704892 206.887587 109.9683 205.778737 110.584333 206.080378 111.204615 205.566314 112.394196 206.127111 112.976241 207.223216 113.647505 207.091513 113.630511 206.662418 114.348508 206.356529 113.847185 205.727756 112.797804 205.379381 113.141933 204.754857 114.675643 204.71662 115.686787 204.075102 115.635805 203.395347 114.216804 202.881283 114.191313 202.333231 114.896565 201.67047 115.601817 201.806421 115.67829 202.337479 116.61721 203.178675 116.375045 203.854182 117.258734 204.279028 117.58162 203.845685 117.985228 203.96889 117.789797 202.248261 118.15092 202.286497 118.184908 202.80481 118.681983 202.600884 118.983627 203.187172 119.098337 202.677356 120.262427 203.131942 120.245433 203.599274 120.891206 202.52866 121.180104 202.711344 121.180104 202.711344 121.804634 203.883921 121.570966 204.797341 122.858263 205.638538 124.043596 205.774489 124.047845 205.43886 124.132815 205.804228 124.553417 205.727756 124.727605 206.505225 125.173698 206.080378 125.755744 206.377771 126.019151 207.635317 125.636786 207.677802 125.636786 208.055915 126.410013 208.489259 126.129612 209.079796 126.410013 209.203001 126.401516 210.06544 126.779633 210.201391 125.917187 210.141912 125.492336 210.740946 126.312298 210.974612 126.422759 212.389351 124.45995 212.826944 124.192294 213.629904 124.855061 213.884812 125.373378 214.69202 125.135462 215.087128 125.742998 215.596944 128.160397 215.24857 129.668617 216.565594 129.549658 217.360058 129.549658 217.360058 126.622439 217.402542 126.19334 218.46041 125.488088 218.757803 124.901794 218.507144 124.349489 219.339843 122.892251 219.488539 122.892251 219.488539 122.994215 219.161407 121.81738 219.106177 121.991568 218.468907 119.582666 218.235242 118.724468 217.538493 117.483905 217.636208 117.445668 218.528386 116.731919 218.800288 115.899212 216.803508 115.215203 216.404153 115.30867 216.021791 113.494559 216.123754 113.095199 215.817864 112.01183 216.276699 110.42289 214.369137 108.740482 214.411622 108.324128 213.952787 107.720841 214.347895"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-15",style:({display:_vm.props.displayDep['FR-dep-15']}),attrs:{"fill":"#6b6b6b","points":"135.633517 148.462672 136.253799 148.458423 136.130593 149.014972 136.636165 148.794052 137.027027 149.197656 137.562339 148.679344 137.6728 149.32511 138.802902 149.486552 138.662701 150.251276 139.265989 150.888546 139.750319 150.599651 140.498055 150.820571 140.999379 150.421215 141.96379 151.032994 142.256937 150.846062 142.936697 151.228424 143.395536 152.723884 144.300467 153.182718 145.213896 152.876829 145.362594 152.158838 146.276022 151.640525 146.276022 151.640525 146.284519 152.252304 145.893657 152.324528 146.31001 152.689896 145.749207 153.208209 147.117226 153.059513 147.193699 153.722274 148.226086 153.514099 148.468251 154.971323 149.114023 155.217734 148.901598 155.842259 148.901598 155.842259 148.901598 156.08867 148.901598 156.08867 149.394425 157.409943 150.656231 157.830541 149.288212 157.839038 149.802281 158.327612 149.572862 159.6064 149.819275 159.946277 150.244126 159.784836 150.069937 160.175695 150.830419 160.307397 150.371581 160.77048 150.668976 161.1316 150.668976 161.1316 149.334946 162.227704 149.003562 161.841094 148.561718 162.011032 148.69767 162.486861 148.238831 162.665296 148.459754 163.200603 147.682277 163.825128 146.807085 162.830986 146.123076 163.238839 146.042354 164.53887 145.575019 164.441155 145.124677 165.337582 144.767803 167.147429 144.988725 167.461815 144.602111 167.512797 143.977581 169.224929 143.977581 169.224929 143.183111 168.345496 143.153371 166.75657 142.741266 166.331723 143.191608 166.017336 142.898461 165.533011 142.329161 165.639223 141.823589 163.531983 140.859178 163.527735 140.294127 163.990818 140.319618 162.597321 139.6611 161.870833 137.328671 164.241477 137.252198 165.575496 136.360012 166.531401 136.542697 167.355604 135.472074 168.124576 135.195921 169.071984 132.876238 169.322644 132.421648 168.58341 131.865094 168.634392 130.458839 169.105972 130.62453 169.743242 130.322886 169.904684 130.322886 169.904684 129.689859 169.411861 129.46044 167.958886 128.946371 167.933395 129.685611 165.660465 128.26661 163.557474 128.156149 162.809744 128.474787 162.393394 128.015948 162.049269 127.744044 161.02114 127.744044 161.02114 128.338835 160.778977 128.338835 160.778977 128.691461 160.795971 128.691461 160.795971 129.180039 160.388118 128.398314 158.930894 129.180039 158.272382 129.711102 158.378593 129.825811 157.010587 130.773228 156.228869 130.19968 155.905986 130.080722 154.899099 130.904932 154.223593 131.406255 153.157228 131.758881 153.229451 132.234714 152.396752 133.003693 152.018638 132.910226 150.060095 135.136442 151.185939 135.901173 150.888546 135.867185 150.387227 135.259649 149.953884"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-16",style:({display:_vm.props.displayDep['FR-dep-16']}),attrs:{"fill":"#6b6b6b","points":"94.7841433 132.92178 95.9099971 133.295645 96.262623 133.813958 98.2594202 133.605783 98.905193 134.115599 99.8611066 133.678007 99.831367 133.159694 99.3003039 133.146949 99.2025883 132.764587 100.056538 131.876657 100.893493 133.138452 101.887643 133.512317 102.291251 132.994004 103.502075 132.909035 103.28965 132.513927 103.956665 131.800185 105.239714 132.046596 105.681558 131.804433 105.923723 132.089081 105.923723 132.089081 105.660316 132.276013 106.10216 132.883544 105.741037 134.043375 106.514265 134.854833 106.973104 134.50221 107.720841 135.101244 107.979999 136.422517 106.548253 137.433652 105.702801 137.055538 105.957711 138.300339 105.184483 140.603008 103.935423 140.284373 103.990653 141.342241 102.911533 141.894542 102.499428 142.655018 102.499428 142.655018 101.35658 144.579573 100.880748 144.664542 100.73205 144.28218 100.396418 144.592318 100.281709 146.631583 99.3045524 147.328331 98.9986601 148.275739 98.0979771 148.279988 97.214288 149.376092 96.717213 148.942748 96.9763718 149.507795 96.254126 149.749957 95.7485539 151.198684 96.0841858 153.004283 95.2982124 153.17847 95.000817 153.841231 94.4824994 153.913455 94.4697539 154.465755 93.7687506 155.281461 93.1272264 154.750403 92.3539985 154.7589 91.466061 155.642581 91.466061 155.642581 91.4448185 154.72916 90.6758391 154.249083 89.3460571 154.223593 89.4352757 153.17847 88.831988 153.246445 88.5855747 152.787611 87.9313049 152.804605 87.502206 152.379758 86.8436877 152.978792 86.3678552 152.711139 86.4868133 151.721246 86.9753914 151.886936 87.06461 151.406859 86.4443282 151.530065 85.9047681 150.807825 87.2430472 150.162058 86.6737475 149.044712 86.3296186 149.023469 87.1198405 147.867886 86.6482565 147.723439 86.9201608 147.392058 85.7008399 147.052181 86.1639269 146.321444 85.9260107 145.994313 84.5494951 144.749512 83.7805157 144.796245 84.1841237 144.031521 84.6387137 144.040018 83.9334619 143.271045 83.9801954 142.285401 84.3158273 142.013499 83.2537011 141.801076 83.4661263 141.240278 84.1968692 141.185048 84.3030818 140.721965 85.9727442 140.721965 86.3168731 140.207901 87.2260531 140.309864 87.4937089 140.998116 88.5303441 140.577517 88.6493023 140.900401 88.831988 139.438928 89.4097846 138.584986 88.823491 138.024189 89.0571587 137.582348 88.7385209 137.199986 89.6646949 137.149005 89.8558776 136.588207 89.5712278 136.10813 89.5712278 136.10813 90.7140756 135.52609 90.3699467 135.351903 91.0157195 134.591428 90.7395667 133.971152 91.7124743 134.017885 92.4857021 133.512317 92.5451812 133.03224 93.0847413 133.074725 93.1187294 132.666872 93.7857446 132.688115 94.3677898 133.2829"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-17",style:({display:_vm.props.displayDep['FR-dep-17']}),attrs:{"d":"M66.565574,134.124096 L67.2198438,134.353514 L68.1885029,135.492103 L69.5565215,135.751259 L69.586261,137.144756 L70.4402105,138.206873 L70.0875846,139.167026 L70.3127553,139.668345 L69.6797281,140.407579 L69.0084643,138.355569 L66.9309455,136.511735 L66.565574,134.124096 Z M64.7429655,128.805016 L65.3887382,129.399801 L64.6877349,129.391304 L64.9044086,130.160277 L65.664891,129.556995 L66.4593614,129.501764 L65.9920259,130.049817 L67.4790026,130.122041 L68.5921109,130.589372 L68.9277427,131.303114 L69.3143567,131.222394 L68.2947155,131.727961 L65.6054119,130.224004 L64.3393575,130.228252 L63.8422824,129.170384 L64.7429655,128.805016 Z M71.6977679,127.301059 L72.4200137,127.284065 L72.7938822,126.455614 L74.1873917,126.413129 L74.9776136,125.916059 L74.4210595,127.165108 L74.9181346,127.454003 L75.729599,127.046151 L76.3881173,127.279816 L76.5538089,126.702025 L77.2420667,126.642546 L77.2080787,127.07589 L78.1597438,127.611197 L78.1597438,127.611197 L78.4061571,128.533114 L78.1002647,129.106657 L79.22187,129.811903 L79.1793849,130.763559 L79.5829929,130.648851 L79.8718912,131.111933 L80.7088467,131.337102 L80.1735351,131.524035 L80.4581849,131.876657 L81.6350208,131.64724 L82.3360241,132.033851 L82.4634792,132.671121 L83.6020785,132.917532 L83.9632014,132.471443 L83.9419589,133.002501 L84.3030818,133.236167 L84.8256479,132.985507 L84.9700971,133.452839 L86.265891,133.151197 L86.8266937,134.043375 L87.6381581,134.25155 L87.990784,133.996642 L88.0757541,134.689142 L88.5813262,134.408744 L88.5006046,135.088498 L88.9382006,135.3604 L88.9679401,135.88721 L89.5712278,136.116627 L89.5712278,136.116627 L89.8601261,136.596704 L89.6689434,137.153253 L88.7427694,137.208483 L89.0614072,137.590845 L88.8277395,138.028437 L89.4140331,138.589235 L88.840485,139.443177 L88.6535508,140.904649 L88.5345926,140.581766 L87.4979574,140.998116 L87.2303016,140.309864 L86.3211216,140.212149 L85.9769927,140.726214 L84.3030818,140.721965 L84.1968692,141.185048 L83.4703748,141.240278 L83.2579496,141.801076 L84.3200758,142.013499 L83.9886924,142.285401 L83.9419589,143.271045 L84.6472107,144.040018 L84.1926207,144.031521 L83.7890127,144.796245 L84.5579921,144.749512 L85.9345077,145.994313 L86.1724239,146.317196 L85.7135854,147.052181 L86.9329063,147.392058 L86.661002,147.723439 L87.1283375,147.867886 L86.3381156,149.023469 L86.6822445,149.044712 L87.2472957,150.15781 L85.9090166,150.807825 L86.4485768,151.530065 L87.0688585,151.406859 L86.9796399,151.886936 L86.4910618,151.721246 L86.3721037,152.70689 L86.8479362,152.978792 L87.510703,152.37551 L87.9355534,152.804605 L88.5898232,152.787611 L88.8362365,153.246445 L89.4395242,153.17847 L89.3503056,154.223593 L90.6800876,154.249083 L91.449067,154.72916 L91.4703095,155.646829 L91.4703095,155.646829 L91.0921926,156.190633 L91.0921926,156.190633 L91.3725939,156.454038 L91.3725939,156.454038 L90.7480637,156.934115 L90.6970816,157.885771 L90.6970816,157.885771 L89.715677,157.397197 L88.8617275,158.187412 L88.0290206,158.068455 L86.8309422,157.558639 L86.4528253,156.866139 L85.9642472,156.713194 L85.8537861,156.985096 L85.2080133,156.203378 L84.6259682,156.853394 L83.9632014,155.243225 L84.2223602,154.448761 L83.8739828,154.244835 L83.9334619,153.526844 L83.0497729,153.101997 L81.418347,153.03827 L81.0232361,151.784973 L80.8787869,152.43074 L78.76728,152.252304 L78.76728,152.252304 L78.1682408,150.077089 L76.9276774,148.139788 L75.2537665,146.708055 L74.4295565,146.516874 L73.3334423,144.868469 L73.0317984,145.008668 L71.7232589,144.265187 L70.1258211,143.011889 L69.5905095,143.118101 L69.5650185,140.76445 L71.3918756,140.339603 L70.6738782,138.980094 L71.6170463,138.997088 L71.9526782,138.640217 L72.4370077,137.229726 L71.9781692,136.753897 L72.3987712,136.392778 L71.8634596,135.496351 L72.8278702,135.300922 L71.9654237,132.964265 L71.2304324,132.671121 L71.4810942,132.152808 L70.6866237,131.876657 L70.9542796,131.42632 L69.6967221,131.464556 L70.2065427,129.998835 L71.8634596,128.618083 L71.6977679,127.301059 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-22",style:({display:_vm.props.displayDep['FR-dep-22']}),attrs:{"d":"M38.6274061,60.8550382 L38.2747802,61.581526 L38.2407922,61.0164799 L38.6274061,60.8550382 Z M34.5106049,60.7573235 L34.6593026,61.6155138 L36.9152587,60.8040566 L36.7198274,61.7599616 L37.2338965,61.6834892 L36.9662407,62.0403604 L38.2662832,61.9596396 L38.4022354,62.5841642 L37.6629955,62.9750231 L38.8440799,63.5868023 L39.3369064,63.3276459 L39.6597928,63.7015109 L39.4431191,64.6871552 L41.7033236,66.6074622 L41.6183535,68.0306986 L43.5259322,69.0800699 L43.4919442,70.0487203 L44.0272558,70.6095179 L44.0867349,69.700346 L44.9831694,69.8660362 L46.3469394,67.9117415 L47.7744371,67.3042108 L47.5110298,66.705177 L48.4159613,66.5012506 L48.7515932,67.0408058 L50.5104742,65.5453455 L51.0712768,66.2166033 L50.1833393,67.3679378 L50.5911958,67.6355912 L51.8360077,66.7221708 L51.619334,66.9473396 L52.4138044,68.2473704 L52.2821007,68.816665 L52.6644662,67.6143489 L52.9618615,68.5362661 L53.4674336,67.4231678 L53.9092781,67.9074931 L53.9092781,67.9074931 L54.4700807,68.6977079 L55.3027877,68.8421558 L55.3325272,69.4411896 L55.986797,68.7784288 L55.986797,68.7784288 L56.6070787,69.3944564 L56.4116474,70.5797787 L56.9851956,69.5431528 L56.9851956,69.5431528 L57.359064,69.2287662 L57.5884833,69.6026313 L57.7116899,71.0343646 L56.9724501,72.066742 L57.3888036,74.5903313 L56.6835518,74.7135368 L56.870486,76.0560524 L56.5730906,75.8011443 L55.7148926,76.3449481 L55.171084,75.907356 L54.1599399,76.5191352 L54.4488382,76.7485524 L54.24491,77.126666 L53.4461911,76.8675095 L52.8853884,77.1776476 L52.9023824,78.2057765 L52.099415,78.1887827 L51.9762084,79.9561449 L51.3559267,79.7734608 L51.0712768,80.2068044 L51.0712768,80.2068044 L49.9794111,80.4319731 L49.8307134,80.8228321 L48.6538776,79.174427 L46.6953168,79.6035221 L47.0691853,80.2407921 L46.4701461,81.7447494 L44.5625674,82.9088293 L44.2694206,82.0931237 L44.677277,81.1754548 L44.4945913,80.3130161 L43.7298605,80.7845959 L42.8334259,80.6868811 L42.4935455,81.4643506 L41.9539854,79.939151 L41.134024,80.0198719 L40.4245237,79.4123411 L39.5195921,79.2424024 L38.508448,79.5312982 L38.2365437,78.5414054 L37.8456812,78.3842121 L36.4734142,78.3034913 L36.0060786,79.4463288 L35.0926501,79.429335 L34.7060361,80.0241203 L34.2217066,79.7862062 L33.677898,80.0835989 L33.3677571,79.7054853 L32.7432269,79.8074485 L32.5520442,79.2636448 L31.1160495,79.9221571 L30.7464296,79.556789 L31.0820615,79.1999178 L30.623223,78.8133073 L30.1898755,79.1107 L28.5074676,78.847295 L28.5074676,78.847295 L29.318932,78.7368349 L28.6434197,77.8446569 L29.327429,76.3322027 L28.7198928,75.9243499 L29.335926,75.3932915 L28.7453838,75.4315277 L28.6816563,73.7703772 L27.8022158,73.4984753 L27.8616948,72.6572789 L28.7623779,71.9605304 L27.7809732,71.1320794 L27.8149613,70.6520026 L28.9748031,69.5474012 L28.3927579,68.999349 L27.9764045,69.0928153 L28.0911141,68.3705759 L27.1691885,68.0264501 L26.8547992,66.3398088 L26.8547992,66.3398088 L27.4793294,65.6090725 L27.8531978,66.0679069 L28.4564855,65.6982903 L28.1803327,64.585192 L28.7963659,64.011649 L28.1675872,63.7100079 L28.1675872,62.8305753 L28.477728,62.6903759 L28.1251021,62.7073697 L28.2610543,62.3887347 L28.6986503,62.7795937 L29.0427792,62.5034433 L28.9365666,61.9468942 L29.5865878,61.5050536 L29.5568483,61.8916641 L29.9689532,61.5560352 L30.3853067,62.1380752 L30.9333638,61.9978758 L30.7081931,62.6054065 L31.4601784,62.5501764 L31.6173731,62.1380752 L32.836694,61.5347929 L33.8393412,61.6410046 L34.5106049,60.7573235 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-32",style:({display:_vm.props.displayDep['FR-dep-32']}),attrs:{"fill":"#6b6b6b","points":"92.7236184 185.471066 93.6837805 185.01648 94.151116 185.717477 95.1707572 184.498167 96.691722 185.279885 96.9423837 184.82105 98.1107226 184.893274 99.1983398 184.340974 99.5297232 183.673964 101.203634 183.695207 101.853655 183.079179 103.102716 184.447185 104.48348 183.43605 104.48348 183.43605 104.891336 184.306986 105.889735 183.682461 106.463283 183.877891 106.267852 184.451434 105.745286 184.421695 105.96196 185.143934 104.776627 186.6224 104.984803 186.966526 106.603484 186.936787 107.32573 187.544318 106.934867 188.09237 107.453185 188.648919 107.160038 188.971803 107.69535 189.209717 107.168535 190.148628 108.20517 190.242094 108.20517 190.242094 108.850943 191.418919 110.19347 192.353582 109.887578 192.986604 110.822249 193.339226 110.567339 193.713091 111.523252 193.789564 111.833393 194.677493 112.258244 194.486312 112.551391 195.539932 111.01768 195.837325 110.796758 196.419365 110.580084 196.092233 109.913069 196.245178 110.367659 196.568061 109.913069 196.831466 110.303932 197.111865 109.318278 197.889334 109.811105 198.229212 109.339521 198.908966 109.547698 199.210607 108.893428 199.414534 109.11435 200.14527 108.804209 200.489396 107.555149 199.482509 105.843001 199.227601 105.609334 199.6482 104.891336 199.236098 104.339031 200.353445 103.986405 200.115531 103.123958 201.517525 102.104317 201.814918 102.104317 201.814918 99.2195823 201.084181 99.1813458 201.385822 98.6375372 201.36458 98.2509232 200.706068 97.2355306 201.020454 97.1123239 200.251482 96.8276741 200.434166 96.6194974 199.932847 94.6014576 200.374687 94.4485114 199.588721 94.08314 199.682187 93.71352 199.083153 94.2828197 198.573337 93.4543613 196.865454 92.3200105 196.788981 91.3471028 195.641895 91.1389261 194.668996 90.0937939 194.647754 89.690186 195.234043 89.690186 195.234043 88.5600837 195.272279 88.2669368 194.889917 87.1113435 195.166067 87.1113435 195.166067 87.0306219 194.418337 86.4910618 194.401343 86.4188372 193.764073 86.7587176 193.917018 86.7502206 193.530407 87.124089 193.470929 86.9881369 192.19214 87.9610445 191.461404 87.5404425 191.142769 87.4002418 189.736527 87.982287 189.694042 87.7188797 188.529962 88.1139907 188.355775 88.0332691 187.943674 87.1708226 187.561312 88.2287003 186.54168 88.5813262 186.822078 89.1676199 186.443965 89.7029315 186.805084 89.741168 186.329256 90.590869 186.010621 90.7523122 185.445575 91.5127945 186.184808 91.019968 186.792339 92.4304716 187.595299 92.7321154 187.089732 92.358247 186.048857"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-46",style:({display:_vm.props.displayDep['FR-dep-46']}),attrs:{"fill":"#6b6b6b","points":"116.931599 159.920787 118.52054 159.249529 118.656492 159.682873 119.179058 159.393977 120.432367 159.712612 121.494493 161.18683 122.259224 161.496968 122.267721 161.981293 123.176901 162.342413 125.046243 160.978655 125.615543 161.093363 125.747247 161.607428 126.414262 161.063624 127.744044 161.02114 127.744044 161.02114 128.015948 162.049269 128.474787 162.393394 128.156149 162.809744 128.26661 163.557474 129.685611 165.660465 128.946371 167.933395 129.46044 167.958886 129.689859 169.411861 130.322886 169.904684 130.322886 169.904684 130.135952 170.694898 129.83006 170.427245 129.014347 171.085757 128.1519 170.643917 127.803523 171.187721 127.705807 170.750128 125.135462 172.700175 125.224681 173.12927 124.710611 172.678932 124.62989 173.197245 123.86091 173.277966 124.052093 174.416555 124.45995 174.705451 124.349489 175.34697 125.131213 176.319868 124.595902 176.761709 124.595902 176.761709 123.618746 177.127077 123.049446 176.867921 122.692572 177.607154 122.127521 177.250283 121.367038 177.403227 120.661786 178.159455 120.236936 177.738856 120.53858 178.06174 120.355894 178.456847 119.905552 178.218933 119.909801 177.789838 119.123828 177.670881 118.983627 178.359132 119.370241 179.013396 118.567273 179.476479 118.308115 178.703258 117.564626 178.397369 117.352201 177.794086 117.046309 178.860452 115.389392 180.088258 114.977287 179.455237 114.02987 179.298044 114.382496 177.845068 113.549789 178.082982 113.430831 178.53332 112.946502 178.180697 112.810549 178.50358 112.21151 177.95128 112.232753 177.496694 111.21736 177.3395 110.47812 176.430329 110.618321 175.975743 111.391549 175.979991 111.442531 175.436187 110.180725 175.78881 110.180725 175.78881 109.985294 174.476034 109.373509 174.15315 109.365012 172.644945 108.740482 171.727276 109.331024 171.901463 110.410144 170.992291 110.410144 170.992291 110.902971 171.005036 110.73303 170.507966 111.739926 169.484085 111.67195 168.621647 113.001732 168.281769 113.817445 167.325864 114.119089 167.478809 114.331514 166.803303 114.624661 166.888272 114.743619 166.280741 114.301775 166.004591 114.318769 165.50752 115.512598 165.133655 115.436125 164.326447 116.791398 163.493747 116.324063 162.652551 116.833883 162.503854 116.336808 160.596293 116.336808 160.596293 116.260335 160.264912 116.260335 160.264912"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-47",style:({display:_vm.props.displayDep['FR-dep-47']}),attrs:{"fill":"#6b6b6b","points":"88.9509461 179.480728 89.278081 179.136602 89.9068597 179.247062 90.8500278 178.371878 90.8160397 177.768596 89.9450962 176.744715 90.0555574 176.341111 91.8654204 175.818549 91.1771627 174.773427 91.5977646 174.025696 91.2238962 173.749546 91.1729141 172.71292 91.7931959 172.016172 91.4618125 171.514853 92.6556423 171.544592 92.8765646 170.69065 94.0958855 170.061877 93.8834602 169.365128 94.3210562 169.501079 94.6821792 168.791585 93.8239812 168.66838 93.1867054 167.805941 93.5308343 167.512797 93.7899931 167.716723 93.9131998 166.939254 94.5079905 167.002981 94.7289127 166.552643 94.966829 167.296125 95.2684728 167.240895 95.4256675 166.225511 95.9524821 166.59088 96.6662309 166.28499 96.6662309 166.28499 97.5711625 167.368349 97.7028661 168.957275 98.8032289 169.216432 100.387921 168.383732 101.152652 168.689622 101.785679 167.861171 102.435701 168.039607 102.473937 167.695481 103.497827 168.451708 104.882839 168.387981 105.473381 167.83568 106.454786 168.630144 105.843001 169.662521 106.123403 170.325282 106.769175 170.406003 106.888134 169.947168 108.714991 169.241923 109.292787 169.968411 110.333671 170.431493 110.410144 170.992291 110.410144 170.992291 109.331024 171.901463 108.740482 171.727276 109.365012 172.644945 109.373509 174.15315 109.985294 174.476034 110.180725 175.78881 110.180725 175.78881 109.93856 176.171172 108.209419 176.196663 108.022484 176.600267 107.652864 175.648611 107.228014 175.716586 106.752181 177.462706 107.177032 177.81108 107.572143 177.649639 108.183928 178.333642 107.788817 178.520574 107.406451 180.381403 106.467532 180.542844 106.577993 180.861479 107.032583 180.763765 107.185529 181.672937 106.620478 181.949087 105.337429 181.532737 105.515867 182.280467 104.759633 182.420667 104.48348 183.43605 104.48348 183.43605 103.102716 184.447185 101.853655 183.079179 101.203634 183.695207 99.5297232 183.673964 99.1983398 184.340974 98.1107226 184.893274 96.9423837 184.82105 96.691722 185.279885 95.1707572 184.498167 94.151116 185.717477 93.6837805 185.01648 92.7236184 185.471066 92.7236184 185.471066 92.4814536 184.430192 93.8324782 181.99582 92.0311121 181.851372 91.3810909 181.362799 89.1676199 181.299072"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-50",style:({display:_vm.props.displayDep['FR-dep-50']}),attrs:{"fill":"#6b6b6b","points":"57.5077617 37.5861856 58.1195464 38.1639771 58.7398281 37.8410936 59.2241577 38.5845753 61.6330599 39.0391613 63.1072911 39.7911399 63.0605575 39.5064926 64.9723847 39.2473361 65.3887382 38.4018913 67.2198438 38.1342378 69.0594464 38.4231336 69.6967221 40.8490081 69.2378836 40.6875664 69.2081441 41.4140542 68.4816497 41.5245143 68.3329521 42.6928427 70.7970849 46.2700517 70.5336776 47.8462329 71.0095101 47.4723678 71.6850224 47.9736869 71.6850224 47.9736869 71.7317559 48.4580121 71.2814144 48.8786103 71.6212948 49.685819 71.3408935 49.940727 72.3605347 50.9221229 74.1958888 52.0819543 75.0455897 50.9816014 75.7253505 51.805804 75.2452695 52.0734574 75.0455897 52.7956967 74.4592961 52.8466783 75.7253505 53.7303594 75.0923233 54.4610957 75.8697997 54.0787337 76.0354914 55.3872615 75.6616229 55.9480591 76.2054316 56.4111419 75.4364522 56.8997156 74.7099578 58.1360195 73.7667898 58.5523692 72.7386516 58.3399459 72.3945227 59.2831055 73.3546848 59.1641484 73.4056669 59.7164491 72.4922383 60.6808511 72.0249028 60.4896701 70.941534 61.8406825 71.9441812 62.1678144 72.2925586 63.1619557 74.2341253 63.3021551 74.6037452 62.8603145 75.8103206 63.7397471 76.8851923 63.3191489 76.5665545 63.8417104 76.5665545 63.8417104 76.3881173 64.4195018 76.9659139 64.402508 78.4061571 65.6090725 77.7476388 66.3695481 78.3509265 67.3297016 77.7263963 68.880392 76.2139286 70.5033063 76.2139286 70.5033063 75.3514821 70.6350087 74.5272721 70.0869565 74.2256283 70.6944873 73.6733227 70.8304382 72.5177293 70.3291191 72.5177293 70.3291191 69.2761201 69.381711 68.8300271 70.2951314 67.9590836 70.5755302 67.5129906 71.3699935 66.3234093 71.5824168 65.8093402 70.8856683 65.1380764 70.8346867 63.6680937 66.9728304 63.6680937 66.9728304 64.781202 67.1767568 64.738717 66.887861 65.1678159 67.2192414 66.8459754 66.7009285 67.5087421 67.0280604 66.4848524 66.314318 66.9309455 65.8767259 65.8518252 66.4205297 65.8050917 65.9871861 64.9596392 65.6558057 64.4498186 64.4152534 63.7063303 63.9734128 63.6298572 61.9766334 62.9246054 61.6197622 63.4556685 61.2543941 63.8422824 59.2023846 64.1736658 59.0536883 63.8337854 58.7775379 63.923004 56.4961113 64.318115 56.2964333 64.8576751 56.657553 64.1906598 56.1052523 63.863525 56.2412033 63.5576326 57.2056052 63.1752672 56.1817247 62.99683 54.4568472 63.3027223 53.5604207 62.9118599 51.3469695 63.6043662 51.2110186 62.9076114 50.9136259 62.6909376 51.5806352 61.1954639 48.4452667 60.0526161 47.2259568 59.4620739 47.1749752 58.7865617 43.6232569 58.0855584 42.8033028 58.9182653 41.8049131 58.7780646 40.1692534 58.3064806 39.4130263 57.0361777 38.9796827 57.0276806 37.8708329"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-53",style:({display:_vm.props.displayDep['FR-dep-53']}),attrs:{"fill":"#6b6b6b","points":"76.2096801 70.5118032 77.0381385 71.748107 77.6414262 71.4719567 77.8878395 72.2324322 78.7715285 71.9137972 78.4953757 71.2595334 79.230367 71.3954843 79.5957384 70.9154075 79.8888853 72.0370028 80.8405503 71.310515 81.6562633 71.2680303 82.3870061 70.3163737 84.3838034 70.2908829 85.1527828 70.9748861 85.7178339 69.9595025 86.5972744 70.3588584 87.124089 69.5898859 86.7459721 69.2585055 86.9881369 68.8421558 87.7273767 68.8633981 88.8192425 69.8065577 88.3221674 70.2696406 88.7979999 71.692877 90.4931534 71.8458218 90.3954378 73.7024018 90.3954378 73.7024018 88.9679401 74.1654846 89.2270989 74.8367424 88.6025687 74.7900093 88.9339521 76.03481 88.436877 76.8122794 88.823491 78.3629698 86.9159123 79.6077706 87.1878166 79.875424 86.8904213 80.1600713 87.570182 80.8143351 87.1878166 81.2009456 87.3067747 82.2120807 86.5972744 82.042142 85.365208 82.6496728 85.365208 83.452633 85.9515017 83.6098263 86.0449688 84.6591976 86.5080558 84.8716209 84.1118991 85.9719738 84.1968692 86.5327714 84.7916599 86.7239524 84.8383934 87.4801795 84.1586326 87.4674341 83.5341024 88.1769281 83.8357463 88.831192 84.5749861 88.9374036 84.6472107 90.0505019 84.6472107 90.0505019 84.05242 89.532189 84.1288931 89.9315449 83.3854048 90.1567136 82.2553025 89.332511 81.9791496 90.3139069 81.0657211 90.105732 80.6536161 90.6750266 78.3934115 90.6367904 76.9744109 89.7403639 76.587797 89.8253332 76.5835485 90.2416829 75.6403804 89.9527872 74.9521226 89.1710693 74.4295565 89.1285846 74.2128828 89.4982013 74.6079937 89.8380786 74.0939246 90.0207627 73.3206968 89.6001645 70.7588483 89.5619283 69.5480244 88.7547196 69.5480244 88.7547196 69.8666623 88.4870662 69.8369228 87.6586151 70.444459 87.2380169 70.2872643 86.6432316 70.6356417 86.4393052 71.0095101 84.6889369 71.5278277 84.5232467 71.5575672 84.1196423 72.8661067 84.1791208 73.4524004 83.6820502 73.1592536 82.9258231 73.3674303 81.9529242 72.8491127 81.3793812 71.9484297 76.8335218 72.9468283 75.0916504 72.3138011 72.8229691 72.5134808 70.3376161 72.5134808 70.3376161 73.6690742 70.8389351 74.2171313 70.7029842 74.5230236 70.0954535 75.3472336 70.6435057"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-56",style:({display:_vm.props.displayDep['FR-dep-56']}),attrs:{"d":"M34.1027485,99.7667455 L35.6959378,100.374276 L36.0060786,101.2792 L37.3825942,101.670059 L37.1446779,102.392298 L36.8345371,102.613218 L35.3857969,101.997191 L34.612569,102.226608 L34.2641916,101.967451 L33.8350927,100.714154 L34.1027485,99.7667455 Z M28.5074676,78.8430466 L30.1898755,79.1064515 L30.623223,78.8090588 L31.0820615,79.1956693 L30.7464296,79.5525405 L31.1160495,79.9179087 L32.5520442,79.2593963 L32.7432269,79.8032001 L33.3677571,79.7012369 L33.677898,80.0793504 L34.2217066,79.7819577 L34.7060361,80.0198719 L35.0926501,79.4250865 L36.0060786,79.4420804 L36.4436746,78.3119882 L37.8159417,78.3927091 L38.2068041,78.5499024 L38.4787085,79.5397951 L39.4898526,79.2508994 L40.3947841,79.420838 L41.1042845,80.0283688 L41.9242459,79.9476479 L42.463806,81.4728475 L42.8036864,80.6953781 L43.7001209,80.7930928 L44.4648518,80.321513 L44.6475375,81.1839518 L44.239681,82.1016206 L44.5328279,82.9173262 L46.4404065,81.7532463 L47.0394457,80.2492891 L46.6655773,79.612019 L48.624138,79.1829239 L49.8009739,80.831329 L49.9496716,80.4404701 L51.0415373,80.2153013 L51.0415373,80.2153013 L51.627831,80.9332922 L51.5980914,82.0931237 L52.2226217,81.6215438 L53.1360502,81.7617432 L52.7749273,82.3735225 L51.8105167,82.3650255 L51.4621393,82.7176483 L51.0160463,83.9964368 L51.9209778,83.771268 L53.0425831,84.3023264 L53.5609007,84.1833693 L54.7292395,85.8700106 L54.0792183,86.6262377 L54.4148502,87.3909617 L54.1684369,87.7350875 L54.5635478,87.8752869 L55.162587,87.1955323 L55.4897219,88.1259465 L54.1302003,89.7701031 L54.1302003,89.7701031 L53.8625445,89.7701031 L53.8625445,89.7701031 L55.0266349,90.2714222 L55.0903624,90.6070511 L54.2194189,90.6537842 L54.2194189,90.6537842 L54.1471944,90.8662076 L54.1471944,90.8662076 L53.824308,91.0191524 L54.3723651,93.4195361 L54.3723651,93.4195361 L54.6655119,93.937849 L54.3256316,94.3881865 L54.3723651,95.98561 L53.3272329,96.2787542 L53.2167718,97.1072053 L52.7664303,96.9712543 L52.8386549,96.5124199 L51.7807771,96.9839997 L51.5301154,96.4784322 L50.8376091,96.4232021 L50.5062257,97.8591839 L49.019249,97.9823894 L48.6368836,97.4428341 L48.0293474,98.1948128 L48.0293474,98.1948128 L47.3665806,98.1438312 L47.2561195,97.0477267 L48.3267427,96.886285 L47.1159188,96.2107788 L45.1318671,96.7333402 L44.9789209,96.3339843 L44.5115853,96.367972 L44.0867349,96.9160243 L43.2922645,96.6866071 L41.7160692,97.2006715 L41.108533,96.8905335 L40.687931,95.98561 L40.1398739,95.9431254 L40.0251642,95.4630486 L39.0904932,95.3738308 L38.6444001,94.774797 L38.3767443,95.1189228 L38.0198699,94.4476651 L38.0368639,94.9574811 L36.2822314,95.0424504 L36.0868002,96.0195978 L36.8642766,97.5532943 L36.1462793,97.5532943 L35.6449557,96.249015 L36.0103271,95.7349505 L35.6661982,94.0738 L33.4994608,92.0642752 L32.4755711,91.7541371 L32.0932057,92.0005482 L32.0082356,91.3547812 L30.3258276,91.6691678 L29.0342822,89.9825265 L29.0342822,89.9825265 L29.1404948,87.8285538 L29.8032615,88.4020968 L30.3895552,88.0622195 L30.5425014,87.3527255 L31.4176934,87.3697194 L31.0565705,86.265118 L31.6301186,85.7170658 L31.5366515,84.8758694 L31.094807,84.4552712 L30.202621,85.1987529 L29.7947645,84.4255319 L29.0767672,84.0644122 L27.173437,84.1706239 L26.5616523,82.2078323 L25.9456191,81.9954089 L26.1835354,81.2944119 L25.5250172,81.2434303 L25.4952776,80.8355775 L25.8479035,80.0241203 L28.5074676,78.8430466 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-65",style:({display:_vm.props.displayDep['FR-dep-65']}),attrs:{"d":"M89.4352757,201.823415 L89.6307069,201.980608 L89.6307069,201.980608 L89.9790843,202.962004 L89.2440929,203.488814 L88.9127096,202.745332 L89.2313474,202.014596 L89.2313474,202.014596 L89.4352757,201.823415 Z M90.0767999,200.557371 L90.1235334,200.587111 L90.1235334,200.587111 L90.16177,201.768184 L90.16177,201.768184 L89.7624105,201.823415 L89.7624105,201.823415 L89.3460571,201.526022 L89.4692637,201.160654 L89.8516291,201.211635 L89.5584823,200.655086 L89.5584823,200.655086 L89.4905062,200.42142 L89.4905062,200.42142 L89.698683,200.272724 L89.698683,200.272724 L90.0767999,200.557371 Z M89.690186,195.234043 L90.0937939,194.647754 L91.1389261,194.664748 L91.3471028,195.637647 L92.3200105,196.788981 L93.4543613,196.865454 L94.2828197,198.573337 L93.7092715,199.083153 L94.08314,199.682187 L94.4485114,199.588721 L94.6014576,200.374687 L96.6194974,199.932847 L96.8276741,200.434166 L97.1080754,200.251482 L97.2312821,201.016206 L98.2466747,200.701819 L98.6332887,201.360332 L99.1770973,201.377326 L99.2153338,201.075684 L102.09582,201.806421 L102.09582,201.806421 L102.584398,202.154795 L101.097421,203.586528 L101.488284,203.973139 L100.502631,204.155823 L100.579104,204.665639 L99.1473578,206.135608 L99.5382202,206.35228 L99.3895225,206.619934 L100.02255,206.513722 L100.226478,206.976805 L101.360829,207.478124 L101.339586,208.434029 L100.859505,208.289581 L100.698062,209.169014 L102.100069,208.45952 L102.448446,209.321958 L102.206281,209.708569 L102.779829,210.222633 L101.964116,211.0086 L101.292853,212.61452 L99.788882,212.206667 L99.2833099,215.664919 L99.9120886,216.221468 L99.7591425,216.463631 L99.7591425,216.463631 L98.8202229,216.680303 L97.7198601,215.88584 L96.5600183,217.096653 L95.9482336,216.085518 L94.4782509,215.57995 L91.3343573,216.824751 L90.3699467,216.650564 L90.16177,216.047281 L89.3927906,215.847603 L88.5473381,214.122726 L88.0630086,214.386131 L85.8580346,212.890671 L85.8580346,212.890671 L85.6073728,211.263508 L86.4230857,210.864152 L86.3041276,209.15202 L86.8096997,208.931099 L86.7884571,208.387296 L88.2117063,208.034673 L87.8803229,207.669305 L87.9865355,206.530716 L88.814994,206.178093 L89.320566,204.946038 L89.5159973,205.268921 L90.1915095,205.018262 L90.0980424,203.909412 L90.5186444,204.011375 L91.0836955,202.758077 L90.573875,202.010347 L90.9180039,201.339089 L91.5680251,201.432556 L91.912154,200.926988 L91.3258603,200.298215 L91.3768424,198.530853 L90.633354,199.380546 L90.204255,199.363552 L90.4974019,199.168123 L90.136279,198.773015 L90.2297461,198.097509 L91.0667015,197.880837 L90.5101474,197.405009 L90.6843361,196.90369 L90.2254976,196.041251 L89.7454165,196.15596 L89.690186,195.234043 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-68",style:({display:_vm.props.displayDep['FR-dep-68']}),attrs:{"fill":"#6b6b6b","points":"218.360404 75.5802241 219.719925 75.7204235 220.166018 76.3194573 220.017321 76.6508377 220.705579 76.6210984 220.412432 77.1309144 223.25893 78.401206 223.135723 79.5440436 224.015164 79.8201939 224.010915 80.4234762 225.030557 80.5849179 225.030557 80.5849179 224.903101 82.8663446 225.833524 84.5020043 224.673682 86.9406243 224.771398 87.9347655 224.19785 89.5576798 224.520736 90.7472505 223.879212 91.698907 224.08314 92.6420667 225.285467 94.1247816 225.170757 94.6813307 223.654041 95.8156714 224.210595 96.1173125 223.467107 97.2983863 222.460211 96.9627574 222.273277 97.3493679 222.885062 97.5915305 221.665741 98.6026656 220.6461 98.3647514 219.142129 98.8830644 218.946698 98.415733 217.850583 98.296776 218.411386 96.9924967 217.187817 96.758831 217.187817 96.758831 217.374751 96.1937849 216.367855 94.4179258 215.577633 94.2182479 215.212262 94.5283859 214.944606 94.1757632 215.04657 93.2156097 215.658355 92.3616679 215.318474 91.6139377 215.492663 91.0276493 213.789013 89.7701031 212.484722 89.468462 211.953659 88.4360846 211.953659 88.4360846 213.423641 87.7095967 213.015785 86.6347346 213.593581 86.137664 213.287689 85.8870045 213.85274 83.7755165 214.851139 83.2019735 216.202163 80.8823106 216.338116 80.3385069 215.896271 79.939151 217.88882 75.9116045"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-2A",style:({display:_vm.props.displayDep['FR-dep-2A']}),attrs:{"fill":"#6b6b6b","points":"242.589627 224.089629 243.783457 224.28081 246.07765 225.423648 246.940096 225.627574 247.679336 225.296194 247.641099 226.031178 248.461061 227.110289 250.500343 228.308356 250.920945 228.21489 251.379784 230.050228 252.318703 230.640765 252.424916 231.843081 252.960227 232.539829 254.030851 232.476102 253.92039 234.502621 254.192294 234.719293 253.886402 235.071916 254.566162 235.509508 254.213536 236.682084 255.704762 237.089937 256.737148 236.286977 257.208732 236.448419 257.208732 236.448419 257.280957 239.876932 256.686166 240.692637 256.975065 241.21095 256.252819 241.686778 256.376025 242.158358 255.556064 241.882208 255.177947 243.12276 255.73875 242.438757 256.639433 242.689417 256.184843 243.517868 254.957025 244.189125 255.041995 245.744064 254.064839 246.25388 253.945881 247.03135 254.816824 246.67023 253.911893 248 251.796137 247.41796 252.161509 246.317607 251.435014 246.275122 251.311808 245.722822 250.82323 245.930997 250.440864 245.421181 248.715971 245.170521 247.938495 244.346319 247.347953 244.499263 247.390438 243.823757 246.366548 243.602837 246.336808 242.132867 246.680937 241.584815 246.940096 241.869462 247.874767 241.46161 248.63525 240.501456 246.804144 239.876932 246.264584 240.216809 246.145626 239.24391 244.947547 239.706993 244.905062 239.290643 244.097846 239.260904 245.363901 238.41121 245.036766 237.80368 246.090395 237.582759 246.315566 237.13667 245.950194 236.818035 246.38779 236.669339 246.646949 235.607222 245.67829 234.710796 245.164221 235.305581 243.800451 235.2546 243.197163 235.645459 243.456322 234.744784 242.933756 233.954569 244.208307 233.657176 244.127586 232.832974 245.665545 231.953541 245.16847 231.626409 244.828589 230.466578 244.110592 230.746976 243.277885 229.935519 242.725579 230.092713 242.887022 229.59989 242.313474 229.676363 242.946502 229.141056 242.372953 229.064584 242.797804 228.886148 242.729828 228.253126 242.470669 228.27012 242.593876 227.71782 242.01183 227.496899 244.709631 226.761915 244.123337 226.031178 243.146181 225.878233 243.031472 225.559598 243.579529 225.066776 243.214157 224.828862 242.237001 225.211224 242.092552 224.229828 242.5259 224.374276"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-70",style:({display:_vm.props.displayDep['FR-dep-70']}),attrs:{"fill":"#6b6b6b","points":"195.18481 85.7043204 196.038759 84.353308 196.51884 84.5954706 195.949541 85.3856854 196.331906 85.4451639 196.710023 84.8843663 197.270826 84.9056087 197.925096 83.6693048 199.539527 83.133998 199.968626 83.580087 200.117324 84.3957926 199.84542 84.6422037 200.882055 85.1435228 200.822576 85.5471271 202.28831 84.926851 204.085428 84.7951485 204.896892 85.2922191 205.653126 86.7664371 206.804471 86.3033542 207.161345 85.5301333 207.8581 85.181759 208.614334 86.2863604 211.078467 87.6883544 211.732736 88.6230171 211.732736 88.6230171 210.581392 90.3818823 211.333377 93.2580944 210.946763 93.8613766 211.690251 94.9149964 211.435341 95.2081406 211.435341 95.2081406 210.75558 95.8496591 208.85225 94.7365608 209.030687 95.3355946 208.65257 95.8836468 207.981307 96.0323432 207.45874 95.6627266 207.225073 96.0153493 207.505474 96.56765 207.029641 97.0817145 206.031243 96.5421592 205.529919 97.1751807 205.389719 96.5209168 204.47629 96.240518 204.098173 96.6866071 203.125266 96.6611163 202.895846 97.2134169 202.551717 97.0604721 202.330795 98.1990612 201.880454 98.2160551 201.736004 98.7895981 201.332396 98.6409018 201.4641 99.0912393 200.848067 98.6196594 200.219288 99.1167301 200.644139 99.3376503 200.202294 99.7072669 199.288866 99.5245829 199.212393 100.450749 198.728063 100.132114 198.53688 100.692911 196.854472 101.368417 195.953789 100.845856 196.021765 101.291945 195.528939 101.143249 195.426975 101.746531 195.15507 101.461884 194.483807 102.018433 193.298474 102.24785 192.682441 103.004077 191.934704 102.927605 191.934704 102.927605 191.497108 102.655703 190.889572 103.19101 189.759469 103.241991 188.722834 101.94196 188.722834 101.94196 187.843394 101.606332 188.28099 101.151746 188.378705 99.7200123 187.201869 99.635043 187.316579 98.1310858 186.747279 97.8464385 186.267198 98.1183404 186.050525 97.7657176 186.777019 97.4343372 186.415896 97.332374 186.619824 96.8692911 187.295336 97.0817145 188.179025 96.1300579 188.021831 94.0695515 187.125396 93.4152876 186.543351 94.3754411 186.14824 94.1035392 186.14824 94.1035392 186.819504 92.280947 187.732932 92.3489225 188.098304 92.0345359 188.850289 92.3191832 189.589529 91.4185082 190.107847 92.3404255 191.764764 92.0132936 191.654302 91.0276493 192.083401 90.3903793 192.028171 89.8423271 191.505605 89.5449344 191.769012 88.4530784 192.661198 88.4743208 192.99683 87.4419433 194.182163 87.6331243 194.07595 87.1403022 194.581522 86.2141364 195.176313 86.3798266"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-23",style:({display:_vm.props.displayDep['FR-dep-23']}),attrs:{"fill":"#6b6b6b","points":"116.349554 126.544832 118.299618 124.488574 118.63525 125.359509 119.773849 124.624525 120.109481 125.474218 121.116376 124.700997 121.881107 125.418988 122.429164 124.522561 122.208242 123.88954 122.832772 123.728098 123.822674 124.514064 124.62989 124.106212 125.458348 124.373865 126.286807 124.072224 128.219876 124.917669 129.626132 124.548052 129.626132 124.548052 131.605935 124.667009 131.605935 124.667009 131.729141 125.644157 132.566097 125.950046 131.992549 126.617056 133.09716 127.454003 133.500768 126.914448 134.095559 128.146504 134.273996 127.815123 135.17043 128.222976 136.670153 131.70247 136.670153 131.70247 136.368509 133.146949 136.746626 134.132593 137.277689 134.442731 137.124743 135.551581 137.409392 136.159112 136.619171 136.422517 136.266545 137.042793 136.483218 137.510124 135.48482 138.194128 135.353116 138.767671 134.511912 138.703944 134.541652 139.247747 133.513514 139.693836 134.486421 141.410217 135.336122 141.932778 135.336122 141.932778 134.354717 142.901429 132.799765 142.663515 132.323932 143.678898 131.380764 143.793607 131.380764 143.793607 131.410504 143.449481 131.410504 143.449481 131.410504 143.105355 130.62453 143.00764 130.403608 142.587042 130.080722 142.918423 130.297395 142.493576 129.541161 141.962518 129.422203 142.327886 128.993104 142.026245 128.160397 142.157947 127.552861 141.253024 126.694663 141.660876 126.499232 142.378867 125.900193 142.268407 125.687768 142.795217 125.33939 142.553054 124.859309 142.943913 124.859309 142.943913 124.408968 142.234419 124.447204 141.503683 124.910291 141.1808 124.583156 140.4883 123.758946 139.749066 123.606 140.144174 122.654335 139.566382 122.628844 138.699695 121.885356 139.328468 120.67878 139.506904 119.680382 138.941858 119.463708 138.334327 119.875813 138.100661 119.960783 138.359818 119.982026 137.883989 120.377137 138.07517 120.024511 137.442149 120.304912 137.208483 119.378738 137.059787 118.987875 137.488882 118.019216 137.042793 119.187555 135.874465 118.410079 135.092747 118.724468 134.463974 118.546031 133.321136 117.568875 132.671121 117.968234 132.22928 117.777052 131.73221 117.556129 131.906397 117.029315 131.48155 117.131279 130.801795 116.298572 130.848528 115.614563 129.87563 116.404784 129.191626 116.166868 128.635077 116.740416 128.439648 116.319814 128.197485 116.833883 127.632439 116.859374 126.820982"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-29",style:({display:_vm.props.displayDep['FR-dep-29']}),attrs:{"d":"M1.17258734,71.5526776 L1.8396026,72.1856991 L0.667015262,72.7847329 L0.361122913,72.7379998 L0.73499134,72.2706684 L9.48574552e-13,72.4703464 L1.17258734,71.5526776 Z M21.1405601,64.5766951 L21.4634465,64.6446706 L21.3784764,65.8214959 L21.854309,66.1741186 L22.1899408,65.9999315 L22.1474558,65.2691952 L22.3981176,66.0339192 L23.3837707,65.9277075 L23.5409654,66.2888272 L23.3837707,65.5156063 L23.8001242,64.848597 L23.9913069,65.2139651 L23.9488219,64.721143 L24.6115886,65.2224621 L25.7246969,65.0822627 L26.1368019,65.5283517 L27.105461,65.3456676 L26.8463022,66.3355604 L26.8463022,66.3355604 L27.1606915,68.0222017 L28.0826171,68.3663275 L27.9679074,69.0885668 L28.3842609,68.9951006 L28.9663061,69.5431528 L27.8064643,70.6477541 L27.7724762,71.1278309 L28.7538808,71.9562819 L27.8531978,72.6530305 L27.8022158,73.4984753 L28.6774078,73.7661288 L28.7411353,75.4272793 L29.3316775,75.3890431 L28.7156443,75.9201014 L29.3231805,76.3279542 L28.6391712,77.8404084 L29.3146835,78.7325864 L28.5032191,78.8430466 L28.5032191,78.8430466 L25.8648975,80.0198719 L25.5122716,80.831329 L25.5420112,81.2391818 L26.2005294,81.2901634 L25.9668617,81.9954089 L26.5828949,82.2078323 L27.1946796,84.1706239 L29.0980097,84.0644122 L29.8160071,84.4255319 L30.2238635,85.1987529 L31.1160495,84.4552712 L31.557894,84.8758694 L31.6513612,85.7170658 L31.077813,86.265118 L31.4389359,87.3697194 L30.5637439,87.3527255 L30.4107977,88.0622195 L29.8245041,88.4020968 L29.1617373,87.8285538 L29.0555247,89.9825265 L29.0555247,89.9825265 L26.4341972,89.6171583 L25.6482238,88.8821736 L25.0406876,89.2645356 L23.4050132,89.2560386 L22.2664139,87.5311611 L22.4703422,87.3272347 L21.6291382,86.528523 L21.0343475,86.8259156 L21.2892578,87.5821427 L20.1251675,87.8412992 L18.9780712,87.1360537 L18.2600739,87.5226642 L17.469852,89.1030938 L14.3641949,89.1073423 L14.1347756,88.4700723 L14.6573417,88.257649 L14.7083238,87.6543667 L13.4210268,84.7484154 L11.7471159,83.6013294 L11.1693193,83.4568815 L10.8846694,83.7670196 L9.69933658,83.0107925 L8.55648877,83.0830164 L7.85973398,82.7388906 L8.39079708,82.6199335 L8.25484493,82.0676329 L11.1140887,81.7617432 L11.4072355,81.3921266 L12.2824275,81.4813444 L13.5102454,80.9502861 L14.4619105,80.8865591 L15.4730547,81.4176174 L15.753456,81.1159763 L16.0381058,79.7054853 L15.6387464,79.5695344 L15.290369,78.3927091 L12.2399425,77.5472642 L11.3350109,79.2976325 L11.0036276,79.2806386 L11.1183372,78.2397643 L10.7657113,77.5897489 L11.2755319,77.2031384 L9.93300435,76.808031 L9.77580967,76.3959297 L10.8081963,76.3279542 L10.7784568,75.0449173 L11.4709631,74.7730154 L11.0376156,75.779902 L11.3987385,76.2727242 L11.6961339,75.9668345 L11.9765352,76.3576935 L13.4550149,76.0050708 L13.8161378,76.5191352 L15.4220726,75.907356 L16.0508513,75.9540891 L16.2377855,76.3789358 L16.9260433,75.4910063 L16.4459623,75.7883989 L14.886761,75.4570185 L15.5027942,75.0024326 L15.0354587,74.3949018 L14.3599464,74.6625552 L14.2409883,75.1298866 L13.4890029,75.1978621 L13.6546946,74.8155001 L12.7625086,75.1468805 L13.7863982,73.6471717 L13.3700448,73.3157913 L13.144874,73.6939048 L12.3079186,73.6726625 L10.1284356,74.8367424 L8.82839308,74.3864049 L8.36105755,75.0194265 L7.20971274,75.0746565 L6.94205693,74.0932607 L7.40939246,73.7746257 L6.81460178,72.8484599 L7.41788947,71.4209751 L7.14598516,70.1634289 L7.58358116,69.3987049 L8.28883297,69.126803 L8.42903363,68.6977079 L10.073205,68.5235207 L10.1879146,67.8310207 L10.8974149,67.9542262 L10.8039478,67.3551924 L11.2882774,67.2574776 L11.2797804,66.9133518 L12.4226282,67.2659746 L13.0768979,66.7943948 L13.6759371,66.8708672 L13.144874,66.4460205 L14.7550574,65.8427382 L15.6217523,66.1783671 L15.341351,66.7901463 L15.6260008,67.0025696 L16.0083663,66.5862199 L17.3381483,66.5012506 L16.9175463,66.3398088 L17.5548221,65.5878302 L19.0800353,65.3626615 L19.7980326,65.8724775 L19.9042452,65.0822627 L21.1405601,64.5766951 Z M20.4947874,63.7992257 L21.0258505,64.1900846 L20.2016406,64.1306061 L20.4947874,63.7992257 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-40",style:({display:_vm.props.displayDep['FR-dep-40']}),attrs:{"fill":"#6b6b6b","points":"69.2633746 173.596601 71.8719566 172.738411 72.2670676 171.999178 73.2824602 172.623702 73.979215 172.60246 74.0641851 173.260972 73.2399752 174.696954 75.1560509 174.178641 77.1018661 174.760681 77.6754142 174.267859 80.0418314 173.995957 80.3434753 174.871141 80.063074 175.215267 82.0896108 176.158427 82.251054 176.748964 83.7422792 177.148319 83.7592732 177.662384 84.6004771 178.015007 84.5027615 179.969301 86.6227654 180.351663 87.4469754 180.037277 87.4299814 178.55881 88.0502631 178.350636 88.9509461 179.480728 88.9509461 179.480728 89.1676199 181.299072 91.3810909 181.362799 92.0311121 181.851372 93.8324782 181.99582 92.4814536 184.430192 92.7236184 185.471066 92.7236184 185.471066 92.358247 186.048857 92.7321154 187.089732 92.4304716 187.595299 91.019968 186.792339 91.5127945 186.184808 90.7523122 185.445575 90.590869 186.010621 89.741168 186.329256 89.7029315 186.805084 89.1676199 186.443965 88.5813262 186.822078 88.2287003 186.54168 87.1708226 187.561312 88.0332691 187.943674 88.1139907 188.355775 87.7188797 188.529962 87.982287 189.694042 87.4002418 189.736527 87.5404425 191.142769 87.9610445 191.461404 86.9881369 192.19214 87.124089 193.470929 86.7502206 193.530407 86.7587176 193.917018 86.4188372 193.764073 86.4910618 194.401343 87.0306219 194.418337 87.1113435 195.166067 87.1113435 195.166067 86.3933462 195.136328 85.4671721 195.99027 84.4305369 195.548429 83.4661263 196.01576 83.2367071 195.697125 83.7805157 195.153322 83.4703748 194.885668 81.7369849 196.130469 81.3716135 195.816083 80.8702899 196.308905 79.7826726 195.867064 80.0588255 195.416727 78.9074806 195.765101 78.7162979 196.245178 77.8113664 195.306266 76.4560933 196.21119 75.4067126 196.003015 74.6334848 196.453353 73.9154874 196.198445 73.8560084 197.128859 72.5262263 197.018399 72.3010556 196.674273 71.2006928 197.536712 70.7163633 197.094871 71.4216151 196.771988 70.503938 196.087984 68.5878623 197.273307 66.3828883 197.303046 66.1577176 196.890945 65.5119448 196.831466 65.3250106 196.279165 64.5050492 196.508583 64.5050492 196.508583 65.8730677 193.623874 68.239485 181.507246"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('polygon',{staticClass:"FR-dep-2B",style:({display:_vm.props.displayDep['FR-dep-2B']}),attrs:{"fill":"#6b6b6b","points":"257.586849 209.028814 258.300598 209.627848 258.818916 213.969781 257.986209 217.117895 259.51567 220.117313 259.507173 224.348785 260 226.44328 259.978757 228.508034 259.821563 230.666256 257.391418 234.234968 257.225726 236.452667 257.225726 236.452667 256.754142 236.291226 255.721756 237.094186 254.23053 236.686333 254.583156 235.513756 253.903396 235.076164 254.209288 234.723541 253.937384 234.506869 254.047845 232.480351 252.977221 232.544078 252.44191 231.847329 252.335697 230.645013 251.396778 230.054476 250.937939 228.219139 250.517337 228.312605 248.478055 227.114537 247.658093 226.035427 247.69633 225.300442 246.95709 225.631822 246.094644 225.427896 243.800451 224.285058 242.606621 224.093877 242.606621 224.093877 243.2354 223.966423 243.175921 223.256929 244.076604 223.239936 243.932155 222.636653 244.488709 222.003632 243.978888 221.89742 244.255041 220.886285 245.202458 220.656868 245.011275 219.429061 245.270434 219.356837 245.334161 219.794429 246.379293 219.87515 246.646949 218.817282 247.789797 218.677082 247.997974 218.180012 248.771202 217.929352 250.402628 217.895364 251.167358 217.466269 251.137619 216.676054 252.335697 215.732895 254.047845 215.643677 255.403118 217.045671 256.214582 215.626683 256.172097 214.216192 255.590052 213.302772 256.133861 212.521054 255.810974 211.7011 256.452498 211.089321 256.129612 209.445164"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-dep-64",style:({display:_vm.props.displayDep['FR-dep-64']}),attrs:{"d":"M64.5050492,196.508583 L65.3250106,196.274917 L65.5119448,196.827218 L66.1619661,196.886696 L66.3871368,197.298797 L68.5921109,197.269058 L70.5081865,196.079487 L71.4216151,196.763491 L70.7206118,197.086374 L71.2006928,197.528215 L72.3010556,196.665776 L72.5262263,197.005653 L73.8560084,197.116113 L73.9154874,196.189948 L74.6334848,196.444856 L75.4067126,195.994518 L76.4560933,196.202693 L77.8156149,195.29777 L78.7205464,196.236681 L78.9117291,195.756604 L80.063074,195.40823 L79.7826726,195.858567 L80.8702899,196.296159 L81.375862,195.807586 L81.7412334,196.121972 L83.4746234,194.877171 L83.7890127,195.144825 L83.2452041,195.688628 L83.4746234,196.007264 L84.439034,195.539932 L85.4714206,195.981773 L86.3975947,195.132079 L87.115592,195.161819 L87.115592,195.161819 L88.2711853,194.88142 L88.5643322,195.26803 L89.6944345,195.229794 L89.6944345,195.229794 L89.7454165,196.164457 L90.2254976,196.049748 L90.6843361,196.912187 L90.5101474,197.413506 L91.0667015,197.889334 L90.2297461,198.106006 L90.136279,198.781512 L90.4974019,199.17662 L90.204255,199.372049 L90.633354,199.389043 L91.3768424,198.53935 L91.3258603,200.306712 L91.912154,200.935485 L91.5680251,201.441053 L90.9180039,201.347586 L90.573875,202.018844 L91.0836955,202.766574 L90.5186444,204.019872 L90.0980424,203.917909 L90.1915095,205.026758 L89.5159973,205.277418 L89.320566,204.954535 L88.814994,206.18659 L87.9865355,206.539213 L87.8803229,207.677802 L88.2117063,208.04317 L86.7884571,208.395793 L86.8096997,208.939596 L86.3041276,209.160517 L86.4230857,210.872649 L85.6073728,211.272005 L85.8580346,212.899167 L85.8580346,212.899167 L84.4220399,214.10998 L83.572339,214.19495 L82.4507337,213.430226 L81.6052812,214.568815 L79.4130527,212.049474 L78.7162979,211.994244 L78.0747737,210.048446 L77.1146116,210.456299 L75.3982156,210.154658 L74.7354489,210.405317 L71.7190104,208.705931 L71.2261839,209.092541 L70.6951208,208.370302 L69.7052191,207.953952 L69.1104284,208.234351 L68.290467,207.635317 L68.9659793,206.433001 L67.8741135,206.7134 L67.5002451,208.599719 L65.9835289,208.174872 L65.4014837,207.163737 L66.4381189,206.216329 L67.011667,204.733614 L66.8969574,203.076712 L64.3138665,202.210025 L63.8252884,202.345976 L63.7063303,203.144688 L63.022321,203.225409 L62.7419197,201.895638 L61.9474493,201.695961 L60.8895716,202.150546 L60.7451224,201.326344 L59.8741789,200.833522 L60.1673257,200.485147 L59.9081669,200.306712 L61.9771888,199.911604 L62.6442041,199.325316 L64.5050492,196.508583 Z M89.5584823,200.646589 L89.5584823,200.646589 L89.8516291,201.203138 L89.4692637,201.152157 L89.3460571,201.517525 L89.7624105,201.814918 L89.7624105,201.814918 L90.16177,201.759688 L90.16177,201.759688 L90.1235334,200.578614 L90.1235334,200.578614 L89.698683,200.25573 L89.698683,200.25573 L89.4905062,200.404427 L89.5584823,200.646589 Z M89.2313474,202.014596 L89.2313474,202.014596 L88.9127096,202.745332 L89.2440929,203.488814 L89.9790843,202.962004 L89.6307069,201.980608 L89.6307069,201.980608 L89.4352757,201.823415 L89.2313474,202.014596 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}})])])])
}
var Francevue_type_template_id_15b6e23e_staticRenderFns = []


;// CONCATENATED MODULE: ./src/components/maps/base.js
/* harmony default export */ var base = ({
  props: {
    onenter: Function,
    onleave: Function,
    onclick: Function,
    ondblclick: Function
  }
});

;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/France.vue?vue&type=script&lang=js



/* harmony default export */ var Francevue_type_script_lang_js = ({
  mixins: [base],
  props: {
    props: Object
  }
});

;// CONCATENATED MODULE: ./src/components/maps/France.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_Francevue_type_script_lang_js = (Francevue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/France.vue





/* normalize component */
;
var France_component = normalizeComponent(
  maps_Francevue_type_script_lang_js,
  Francevue_type_template_id_15b6e23e_render,
  Francevue_type_template_id_15b6e23e_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var France = (France_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/FranceReg.vue?vue&type=template&id=d6a41bf8
var FranceRegvue_type_template_id_d6a41bf8_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"version":"1.1","viewBox":_vm.props.viewBox,"xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{attrs:{"fill-rule":"nonzero","stroke-width":"0.2%","stroke":_vm.props.colorStroke}},[_c('path',{staticClass:"FR-reg-11",style:({display:_vm.props.displayDep['FR-reg-11']}),attrs:{"d":"M 437.3289 178.718 432.7007 186.0876 436.5814 187.7645 436.1409 194.4978 439.6953 195.2743 436.4307 201.0464 437.1898 203.0352 432.6755 207.9117 433.8411 215.3611 431.2297 216.795 425.3835 217.3864 419.2183 216.8318 414.3713 217.9734 412.7077 222.073 414.3401 224.8914 412.3072 229.3276 408.5704 233.3988 403.2443 235.7224 398.9636 234.7733 396.1798 236.4772 393.0304 235.4193 386.5339 236.168 383.2654 235.4106 386.6407 230.8333 381.1841 222.3316 368.5806 221.4153 361.1616 223.4878 356.8687 221.5197 358.217 217.4255 357.7108 214.18 354.2227 211.0874 351.0406 210.448 347.8957 206.9991 347.5808 202.0349 343.615 199.4008 338.4312 192.7018 337.3189 190.0059 339.888 186.4005 337.2553 182.9595 337.8168 177.4561 335.5101 172.4787 333.6028 170.9758 330.8877 162.5661 338.8469 159.7271 341.7658 156.1761 343.0201 149.664 344.5893 148.0406 347.1419 152.6733 349.6633 151.8775 354.4089 153.456 360.0379 152.7431 363.6784 150.5395 365.6861 151.9405 371.3392 152.5869 373.0858 154.7434 376.2022 152.1363 386.0427 157.6207 392.935 158.9027 399.0928 162.1968 401.99 159.2879 405.1628 161.4716 414.5398 160.2093 415.4408 157.9728 419.0666 158.4882 421.8395 163.3754 420.7321 165.6821 427.8424 171.0739 429.2085 174.2482 437.3289 178.718 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-24",style:({display:_vm.props.displayDep['FR-reg-24']}),attrs:{"d":"M 408.5704 233.3988 413.4886 235.7896 414.6146 240.5759 416.1989 241.5628 418.7707 248.652 412.4285 253.8513 413.432 262.1281 408.211 265.2143 404.9984 264.9867 404.4854 269.3333 407.992 271.6027 409.4617 274.4575 405.9419 285.2503 408.2092 290.609 404.9037 298.6867 411.0198 304.9623 410.944 308.0384 413.4625 315.4537 413.3527 320.8718 416.0382 324.1136 416.229 329.2515 414.6649 333.0717 415.7063 337.2479 413.6778 341.8867 409.7861 341.1901 402.5349 346.5979 395.6414 347.7056 390.5091 351.7536 388.8725 356.6527 390.601 361.2262 386.5862 363.622 384.4207 362.5055 377.3765 363.6588 373.9039 367.0353 372.5351 371.3806 368.1062 370.7324 361.1872 371.3038 356.7506 370.3515 347.2534 370.3194 346.0568 368.3461 342.1368 373.3864 339.7981 371.1405 335.99 372.0172 330.3391 370.7782 326.3611 375.7895 321.8232 372.232 318.8945 374.4701 313.9809 374.432 313.6597 370.2521 310.71 368.1426 310.5302 363.6331 305.3386 361.0887 298.1039 356.0962 296.97 353.5307 298.9534 348.2431 292.852 340.2132 292.7853 337.7543 287.3055 331.5611 286.7074 325.8019 280.3545 323.2262 281.6218 327.0691 276.3912 326.8911 272.8964 328.9666 269.0365 327.2228 266.0043 328.0651 265.6207 318.8472 256.3724 312.6157 253.8131 312.8896 252.7136 309.6899 254.3835 300.2945 257.7501 296.2035 260.5507 289.2815 260.4051 286.9972 263.0241 281.2792 263.5917 274.6068 268.3624 276.0057 271.3407 278.1872 275.7081 272.7594 282.627 270.6517 282.4953 267.1763 290.4933 260.5111 293.1685 257.3949 292.3471 253.2697 297.3201 249.3869 295.8429 245.8654 296.8884 239.2354 298.6331 234.1662 295.1871 229.2889 296.9464 227.7991 293.5786 220.2961 294.8506 217.7209 300.0703 216.588 304.6644 210.7298 303.2171 208.3961 304.8459 203.4382 302.9844 200.88 298.7691 198.4791 296.831 192.0332 300.02 189.4185 304.9451 187.4151 308.8154 187.2524 315.606 184.2394 324.2152 185.1233 326.7401 182.3486 326.0711 179.3929 331.3822 176.3497 331.4935 171.2152 333.6028 170.9758 335.5101 172.4787 337.8168 177.4561 337.2553 182.9595 339.888 186.4005 337.3189 190.0059 338.4312 192.7018 343.615 199.4008 347.5808 202.0349 347.8957 206.9991 351.0406 210.448 354.2227 211.0874 357.7108 214.18 358.217 217.4255 356.8687 221.5197 361.1616 223.4878 368.5806 221.4153 381.1841 222.3316 386.6407 230.8333 383.2654 235.4106 386.5339 236.168 393.0304 235.4193 396.1798 236.4772 398.9636 234.7733 403.2443 235.7224 408.5704 233.3988 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-27",style:({display:_vm.props.displayDep['FR-reg-27']}),attrs:{"d":"M 633.6931 279.9675 625.9942 281.1261 627.0185 284.1837 623.7781 286.0008 623.2142 292.2308 629.9595 291.3938 628.0347 296.1489 624.3964 297.6074 625.3542 300.946 621.4825 304.5878 619.9913 308.1613 614.6339 312.0736 608.8862 321.2474 602.0252 324.1327 598.2436 327.235 600.1087 330.1939 598.7958 337.3009 599.1179 340.437 590.8531 346.4288 581.8641 355.853 584.4847 358.2187 580.1488 364.7228 579.8106 368.6261 575.5982 372.9514 571.7236 379.4431 568.7002 381.3315 561.687 381.6374 560.974 377.8295 557.3322 375.473 551.5867 381.2529 547.8718 381.8076 547.8245 377.7575 537.0152 367.7611 533.2398 365.7764 530.8999 362.6573 524.4841 365.081 521.3459 362.9214 517.8213 362.8241 515.5619 368.6598 515.4871 371.5031 509.8793 388.8588 505.8889 381.8018 502.6446 379.8161 500.7618 381.7159 494.6049 382.8743 489.4213 380.7772 486.9139 388.3881 484.3413 390.7542 478.3957 388.9235 476.1248 390.7021 472.6694 388.721 468.5 391.0865 465.451 388.4802 460.8972 387.6069 461.2172 381.4127 465.9586 379.2868 465.5065 372.9126 467.0109 369.3782 464.3716 365.685 459.0464 365.5799 452.5015 361.9679 451.8437 356.7582 445.9921 346.4237 441.9281 348.0451 442.523 350.1808 437.9739 352.9849 434.7427 348.5039 429.0344 350.2433 426.6278 348.0575 423.0799 351.0697 418.9929 347.2286 414.5952 344.7834 413.6778 341.8867 415.7063 337.2479 414.6649 333.0717 416.229 329.2515 416.0382 324.1136 413.3527 320.8718 413.4625 315.4537 410.944 308.0384 411.0198 304.9623 404.9037 298.6867 408.2092 290.609 405.9419 285.2503 409.4617 274.4575 407.992 271.6027 404.4854 269.3333 404.9984 264.9867 408.211 265.2143 413.432 262.1281 412.4285 253.8513 418.7707 248.652 416.1989 241.5628 414.6146 240.5759 413.4886 235.7896 408.5704 233.3988 412.3072 229.3276 414.3401 224.8914 412.7077 222.073 414.3713 217.9734 419.2183 216.8318 425.3835 217.3864 431.2297 216.795 433.8411 215.3611 438.23 216.9706 443.0429 222.3815 444.9975 226.0472 443.7349 230.217 447.383 235.1669 451.5287 232.9261 454.7149 238.1194 455.8206 242.7898 460.6314 248.0066 459.5731 251.7085 466.0856 251.6118 474.0178 249.075 480.8248 251.6746 481.6418 248.8627 487.3925 248.168 489.004 249.1138 494.6889 248.1117 495.8751 243.2348 502.2916 243.4941 507.1672 244.6971 507.6683 248.2101 510.191 248.2862 516.175 255.6357 517.8179 260.9066 516.5285 268.7613 519.6639 268.3223 521.8811 271.2246 528.4066 272.3897 531.8344 274.7084 538.1622 276.8411 540.7565 270.4079 544.812 269.2792 547.2058 270.1369 553.7029 269.7196 556.623 262.3107 555.1991 259.0104 558.3582 255.8012 562.1872 255.6635 563.9229 251.0674 568.3871 245.0775 571.7985 246.8039 573.7218 242.895 578.4522 241.1457 580.2244 246.2667 584.4188 248.1498 586.4448 246.4024 590.901 245.6173 594.9643 247.4136 597.376 251.3598 600.8157 249.8065 603.8654 246.3567 606.1208 249.4103 620.8622 258.7813 627.6939 262.6813 627.4958 273.085 630.9567 272.9683 633.6931 279.9675 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-28",style:({display:_vm.props.displayDep['FR-reg-01']}),attrs:{"d":"M 329.1395 81.572 332.8802 83.878 340.3463 91.5516 345.8394 96.0939 346.8716 102.0225 349.4074 106.3467 345.6291 108.4595 345.9863 117.0069 344.5917 118.877 347.2348 123.7666 345.6053 126.3453 348.6384 128.9962 345.8438 132.0106 345.6722 135.3048 347.6958 137.6261 347.9621 141.4698 344.5893 148.0406 343.0201 149.664 341.7658 156.1761 338.8469 159.7271 330.8877 162.5661 333.6028 170.9758 331.4935 171.2152 331.3822 176.3497 326.0711 179.3929 326.7401 182.3486 324.2152 185.1233 315.606 184.2394 308.8154 187.2524 304.9451 187.4151 300.02 189.4185 296.831 192.0332 298.7691 198.4791 302.9844 200.88 304.8459 203.4382 303.2171 208.3961 304.6644 210.7298 300.0703 216.588 294.8506 217.7209 293.5786 220.2961 296.9464 227.7991 295.1871 229.2889 291.258 228.8967 289.2196 224.3303 282.7501 224.8612 273.5423 217.5816 272.8365 208.1718 269.6083 205.816 265.2301 206.1994 258.8256 211.0202 254.6207 213.2436 250.4404 212.4494 250.9122 207.2546 245.8421 206.683 246.3086 201.2112 243.0645 198.1831 239.5511 202.5493 237.0423 201.1978 234.1969 203.2207 227.0104 201.8813 224.3728 204.6958 219.3297 206.7011 218.889 203.5104 213.607 207.0511 212.7267 204.6762 208.5113 201.6279 203.5795 200.1182 202.3498 201.9856 197.5098 200.4819 192.3498 198.4341 186.9911 197.9053 180.8953 203.7269 175.3677 200.2846 173.3339 192.0046 171.6943 189.7396 178.2794 190.0719 180.8231 187.9741 175.8384 185.4132 172.1133 179.4703 172.5797 170.382 173.9275 160.3025 172.2833 158.1901 171.8137 152.1694 172.9353 149.4095 172.5076 141.6629 170.8065 142.4187 167.7695 134.4822 162.8258 129.9167 161.2705 118.8862 159.7068 116.2725 162.1011 113.5489 161.4668 107.7023 157.2008 105.2395 157.6781 101.4803 163.6009 104.6659 169.2695 105.9559 171.202 107.6731 178.8695 108.2564 183.5606 105.0755 186.8952 104.6978 191.1474 106.0611 193.9227 111.7462 189.7739 115.4593 189.5172 118.1659 196.1479 128.943 194.9811 132.9583 198.4522 132.9001 201.0997 130.6137 208.6924 130.8513 213.0625 133.696 225.1839 135.8163 227.5774 135.405 236.4461 137.0981 244.3378 140.9099 251.6684 140.4167 258.722 136.5601 262.2669 133.0332 268.0877 130.593 262.9629 127.5448 260.3002 123.2247 268.0971 107.8063 274.1584 105.8177 287.2805 97.6858 294.3959 96.0022 298.5148 95.967 307.4695 92.6762 310.4768 93.0522 319.8574 88.8484 329.1395 81.572 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-32",style:({display:_vm.props.displayDep['FR-reg-32']}),attrs:{"d":"M 344.5893 148.0406 347.9621 141.4698 347.6958 137.6261 345.6722 135.3048 345.8438 132.0106 348.6384 128.9962 345.6053 126.3453 347.2348 123.7666 344.5917 118.877 345.9863 117.0069 345.6291 108.4595 349.4074 106.3467 346.8716 102.0225 345.8394 96.0939 340.3463 91.5516 332.8802 83.878 329.1395 81.572 332.921 78.2016 336.3986 70.5848 341.7215 69.9937 337.5257 64.1845 340.056 44.6558 339.2543 31.3932 340.9577 28.8927 341.689 23.8203 340.3471 19.2009 343.5953 17.2518 347.9055 12.6702 357.0523 9.2309 367.2132 7.4543 370.3501 4.4011 372.2992 5.4042 378.3374 3.5474 382.1407 3.8723 389.1579 1 390.6503 6.9856 393.3478 12.616 392.0412 17.0399 393.5444 22.9854 397.7312 22.96 401.301 29.6976 406.8199 32.4851 408.8478 28.4111 412.8743 26.1415 419.4136 24.8264 422.6773 30.9882 425.3003 33.7525 426.0587 44.8201 428.6433 47.2401 438.2963 48.264 442.7395 48.0118 445.5169 51.1511 445.7744 59.9255 448.0678 63.3899 449.9277 59.5737 455.3347 59.516 457.0967 61.2772 464.2042 58.8261 469.0856 63.5596 469.196 65.4809 474.1504 66.6124 471.1184 71.1331 470.9032 74.2627 473.0538 76.4345 474.8708 81.3137 470.1035 85.5668 473.2437 90.7297 475.129 90.5061 476.3529 94.8193 474.0318 104.5569 475.1749 107.2962 472.9795 111.1026 470.0076 112.811 468.6267 116.7667 464.9196 117.6478 467.5243 121.3163 466.1122 123.3917 465.6384 138.1236 461.7682 136.7875 459.8611 134.4093 453.5293 138.3423 445.3517 142.0846 447.221 150.4717 450.909 154.3888 444.3273 154.9206 443.8158 157.5288 445.8373 163.5044 445.3589 166.5581 442.0726 173.0202 439.5864 173.9109 437.3289 178.718 429.2085 174.2482 427.8424 171.0739 420.7321 165.6821 421.8395 163.3754 419.0666 158.4882 415.4408 157.9728 414.5398 160.2093 405.1628 161.4716 401.99 159.2879 399.0928 162.1968 392.935 158.9027 386.0427 157.6207 376.2022 152.1363 373.0858 154.7434 371.3392 152.5869 365.6861 151.9405 363.6784 150.5395 360.0379 152.7431 354.4089 153.456 349.6633 151.8775 347.1419 152.6733 344.5893 148.0406 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-44",style:({display:_vm.props.displayDep['FR-reg-44']}),attrs:{"d":"M 433.8411 215.3611 432.6755 207.9117 437.1898 203.0352 436.4307 201.0464 439.6953 195.2743 436.1409 194.4978 436.5814 187.7645 432.7007 186.0876 437.3289 178.718 439.5864 173.9109 442.0726 173.0202 445.3589 166.5581 445.8373 163.5044 443.8158 157.5288 444.3273 154.9206 450.909 154.3888 447.221 150.4717 445.3517 142.0846 453.5293 138.3423 459.8611 134.4093 461.7682 136.7875 465.6384 138.1236 466.1122 123.3917 467.5243 121.3163 464.9196 117.6478 468.6267 116.7667 470.0076 112.811 472.9795 111.1026 475.1749 107.2962 474.0318 104.5569 476.3529 94.8193 475.129 90.5061 479.0167 89.4994 486.0451 91.9559 489.4022 91.1547 492.9503 88.1354 498.2004 86.0861 498.9219 79.1143 505.075 73.8263 507.6628 74.2659 504.0061 89.9221 506.6923 90.6767 508.8881 93.7154 507.0322 98.2572 507.3705 103.0343 515.2182 103.6394 519.639 104.9746 523.4133 110.4678 528.7743 110.0655 533.2559 115.1464 537.074 117.6227 539.4035 124.6769 556.142 120.7027 558.4847 121.3385 560.1147 124.6699 565.6098 125.1419 569.3432 128.4601 573.6057 124.9433 580.2682 123.1007 584.3946 126.336 589.2254 125.3318 594.8621 128.37 597.8052 132.4144 598.0994 137.2424 601.9968 140.236 604.9361 147.5537 611.4631 150.1906 615.5825 144.3179 618.5619 145.2189 621.4513 148.1998 623.2109 152.7205 625.5855 149.1472 628.0275 151.4352 632.9283 150.7795 635.759 151.641 638.9988 147.1014 642.6339 146.2798 647.8233 152.571 649.9984 153.8273 660.5597 155.7608 661.7883 154.2323 665.7695 156.5794 668.9658 154.4105 683.3698 161.448 680.6191 166.7881 678.0716 174.5394 672.6906 177.9279 672.3141 180.9177 668.8412 185.7517 666.4335 187.3821 664.4401 192.2537 665.0051 197.966 663.2276 199.8612 661.728 208.3573 662.7057 212.225 660.1234 215.1032 659.1433 221.3119 654.8369 230.0569 654.7722 236.5217 657.841 240.9463 654.8633 248.9629 653.9613 256.1825 655.0607 260.1716 653.8028 265.6558 658.0307 270.7225 657.7309 272.8777 653.6301 275.5862 654.1452 279.3168 647.7013 284.8543 644.6644 284.2433 640.7178 285.8302 636.0688 284.5417 637.0047 280.9388 633.6931 279.9675 630.9567 272.9683 627.4958 273.085 627.6939 262.6813 620.8622 258.7813 606.1208 249.4103 603.8654 246.3567 600.8157 249.8065 597.376 251.3598 594.9643 247.4136 590.901 245.6173 586.4448 246.4024 584.4188 248.1498 580.2244 246.2667 578.4522 241.1457 573.7218 242.895 571.7985 246.8039 568.3871 245.0775 563.9229 251.0674 562.1872 255.6635 558.3582 255.8012 555.1991 259.0104 556.623 262.3107 553.7029 269.7196 547.2058 270.1369 544.812 269.2792 540.7565 270.4079 538.1622 276.8411 531.8344 274.7084 528.4066 272.3897 521.8811 271.2246 519.6639 268.3223 516.5285 268.7613 517.8179 260.9066 516.175 255.6357 510.191 248.2862 507.6683 248.2101 507.1672 244.6971 502.2916 243.4941 495.8751 243.2348 494.6889 248.1117 489.004 249.1138 487.3925 248.168 481.6418 248.8627 480.8248 251.6746 474.0178 249.075 466.0856 251.6118 459.5731 251.7085 460.6314 248.0066 455.8206 242.7898 454.7149 238.1194 451.5287 232.9261 447.383 235.1669 443.7349 230.217 444.9975 226.0472 443.0429 222.3815 438.23 216.9706 433.8411 215.3611 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-52",style:({display:_vm.props.displayDep['FR-reg-52']}),attrs:{"d":"M 295.1871 229.2889 298.6331 234.1662 296.8884 239.2354 295.8429 245.8654 297.3201 249.3869 292.3471 253.2697 293.1685 257.3949 290.4933 260.5111 282.4953 267.1763 282.627 270.6517 275.7081 272.7594 271.3407 278.1872 268.3624 276.0057 263.5917 274.6068 263.0241 281.2792 260.4051 286.9972 260.5507 289.2815 257.7501 296.2035 254.3835 300.2945 252.7136 309.6899 250.8585 308.6438 247.1961 315.0649 243.9799 317.1685 239.842 316.6182 239.6178 313.5652 227.8148 316.0601 219.3327 316.2143 218.964 318.862 215.7524 321.7412 204.5053 321.1892 201.6822 323.9793 204.1377 325.8752 203.8435 329.6012 207.1526 333.8761 210.2158 335.0377 209.0871 339.4602 212.6616 344.7679 214.6247 351.0017 214.9972 358.1218 213.6246 360.8961 214.138 367.5714 217.9571 369.9547 216.5725 371.9798 211.2417 375.2625 206.9091 375.8568 201.2462 372.7761 196.0694 374.8882 195.5984 372.7647 191.8211 371.106 186.6602 374.0652 181.2651 373.9143 179.5994 376.6626 172.892 370.7021 166.9563 370.8044 164.511 365.7464 160.5585 365.1943 148.6915 357.7043 146.8136 348.3401 142.3645 341.116 136.586 334.5198 132.3454 330.7071 131.9357 325.4359 137.6099 320.7495 141.0798 312.2074 137.5869 308.8157 130.0514 306.5128 132.6753 303.502 133.4423 299.8782 132.351 294.1986 126.1142 297.4653 123.1272 294.211 119.0495 295.053 115.0104 291.4332 113.237 285.8922 118.6413 279.9774 126.2926 279.461 127.824 275.3682 130.4172 277.1357 138.3894 274.5157 138.5515 269.537 141.3782 265.5065 146.1228 262.5923 153.8827 261.5998 159.3556 262.7235 163.025 261.8262 164.8056 258.1054 173.4777 254.694 178.0837 253.8582 178.8753 255.8469 185.1218 257.9546 188.5499 250.9634 190.4504 243.8892 193.7265 241.5408 197.9273 241.3677 198.2962 235.4968 196.8775 233.699 194.8151 219.876 198.0902 214.8875 196.6466 205.8127 197.5098 200.4819 202.3498 201.9856 203.5795 200.1182 208.5113 201.6279 212.7267 204.6762 213.607 207.0511 218.889 203.5104 219.3297 206.7011 224.3728 204.6958 227.0104 201.8813 234.1969 203.2207 237.0423 201.1978 239.5511 202.5493 243.0645 198.1831 246.3086 201.2112 245.8421 206.683 250.9122 207.2546 250.4404 212.4494 254.6207 213.2436 258.8256 211.0202 265.2301 206.1994 269.6083 205.816 272.8365 208.1718 273.5423 217.5816 282.7501 224.8612 289.2196 224.3303 291.258 228.8967 295.1871 229.2889 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-53",style:({display:_vm.props.displayDep['FR-reg-53']}),attrs:{"d":"M 171.6943 189.7396 173.3339 192.0046 175.3677 200.2846 180.8953 203.7269 186.9911 197.9053 192.3498 198.4341 197.5098 200.4819 196.6466 205.8127 198.0902 214.8875 194.8151 219.876 196.8775 233.699 198.2962 235.4968 197.9273 241.3677 193.7265 241.5408 190.4504 243.8892 188.5499 250.9634 185.1218 257.9546 178.8753 255.8469 178.0837 253.8582 173.4777 254.694 164.8056 258.1054 163.025 261.8262 159.3556 262.7235 153.8827 261.5998 146.1228 262.5923 141.3782 265.5065 138.5515 269.537 138.3894 274.5157 130.4172 277.1357 127.824 275.3682 126.2926 279.461 118.6413 279.9774 119.9515 276.0434 115.909 273.5166 110.1909 274.8928 108.0103 273.6138 99.766 275.5492 95.9384 270.8269 101.7238 270.4774 104.4679 271.4509 105.6215 267.0882 103.9862 265.1094 98.764 265.0519 93.1946 267.698 83.6289 268.0012 82.0292 264.6158 75.9036 257.8902 72.9834 255.8253 67.0646 256.4332 63.1318 250.3957 55.2552 248.9313 47.076 246.608 41.3646 238.1763 40.0672 241.5123 32.5408 239.9751 29.7674 243.7525 25.1394 245.1457 18.8581 244.1588 20.371 240.8392 18.5325 233.2077 15.8637 229.1672 11.5351 226.2952 6.6254 224.4443 7.0562 221.4358 21.5112 219.4901 24.3587 221.3539 26.5643 216.5885 25.2063 212.885 17.0828 208.694 15.3012 208.9912 12.621 213.7264 10.9445 207.2181 12.818 203.8811 18.2279 204.5764 21.781 206.2601 28.483 204.203 18.6695 201.1936 19.6838 197.4761 9.2057 200.1181 6.5559 198.4197 2.1087 200.1977 2.4508 196.7445 1 193.162 2.6594 186.1389 4.3593 183.2489 7.8787 181.2819 11.812 181.626 15.7945 177.383 19.1632 178.2797 26.537 174.6978 32.901 176.7542 35.1014 174.6913 41.8522 174.2933 45.4128 172.7094 47.0654 177.6676 52.3235 180.7556 53.1365 174.782 59.3748 175.3356 64.7682 177.5641 68.6582 172.7322 66.9634 170.9486 71.2659 166.6248 76.9547 169.2835 81.1433 166.6674 86.6793 164.802 97.3344 170.6053 96.0869 172.7181 101.0264 174.9448 100.0007 177.021 105.9226 183.1934 105.8906 187.9356 111.3442 191.6208 113.0965 194.8921 120.0255 189.088 131.9916 183.7387 134.2222 184.1305 138.3527 191.022 143.135 186.9902 147.7388 186.6229 153.0214 183.2082 157.2165 182.5294 155.9944 187.1139 157.5151 189.8607 161.358 190.9571 171.6943 189.7396 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-75",style:({display:_vm.props.displayDep['FR-reg-75']}),attrs:{"d":"M 372.5351 371.3806 372.8688 374.4113 375.4729 379.1073 378.5501 378.6019 383.1657 382.53 385.2451 385.7844 385.844 389.9367 387.7053 391.0879 387.5167 399.3609 389.9962 402.1737 389.4646 405.5893 386.7006 412.1376 383.7926 415.3167 378.1054 418.7032 380.6043 423.2042 383.3244 424.7097 385.5973 429.0945 385.0809 433.3926 382.2269 436.9949 385.2291 442.6872 383.3518 450.7639 380.951 453.6466 376.7471 451.0016 375.6363 458.0057 373.7433 458.3847 366.8352 466.3945 367.1571 472.9243 365.5923 477.3822 361.1094 479.3908 363.5708 483.2583 359.2193 485.6637 350.5222 485.4312 345.6873 489.3962 341.7265 488.5784 335.9348 481.2997 329.2446 479.7576 322.7286 483.2886 324.162 492.8622 319.5511 495.7609 319.6462 498.0963 315.1731 505.6732 311.5483 508.0187 307.1201 509.0204 307.466 511.4697 299.8188 518.7515 299.5563 526.6895 301.4638 528.1451 301.0328 533.2673 291.7894 532.7735 290.9517 536.1478 295.2374 540.0664 292.4442 546.2347 289.4429 547.8529 282.8618 556.3114 279.0178 559.3071 273.6265 555.1966 272.1787 556.8906 266.7283 556.6763 265.6643 558.745 260.7874 559.555 257.5755 561.4694 252.5919 558.7514 244.0029 562.3915 244.2204 566.7069 241.7501 568.0805 238.9106 565.6318 232.9623 564.2599 226.2899 567.9943 228.7334 569.1032 228.5283 574.8807 227.1893 575.611 227.769 581.1457 225.0001 582.6983 225.507 585.7286 223.0162 587.776 224.7783 592.0219 228.7738 591.7161 233.4285 593.0186 236.2731 597.6455 234.5501 604.9077 239.0951 605.9367 235.4918 615.4578 237.1076 617.8106 230.9796 624.8272 229.1687 628.8989 226.0548 630.867 226.6431 634.4579 222.3667 635.7615 220.1765 639.2817 220.8216 643.557 218.0178 646.2046 218.536 650.5935 213.8542 654.2965 210.7284 654.495 207.4947 651.8319 203.4573 655.39 201.6119 651.2674 194.2874 645.8011 193.4043 640.0955 189.9222 641.2081 182.0902 640.6535 177.9391 637.2422 171.4581 635.488 165.9607 631.7085 164.8688 632.6725 159.3531 628.5713 158.6297 633.4717 153.6329 631.7648 152.0238 628.9309 155.5046 625.4347 157.6869 619.8025 157.9538 615.5726 149.4336 611.8627 146.7893 615.1913 144.8519 614.9214 141.6949 609.7341 138.0346 611.0319 135.7257 604.7584 142.1465 603.9298 146.2101 600.656 149.5117 595.9599 156.0777 584.197 156.7298 579.4016 160.9754 564.5305 166.1378 544.0213 170.9239 518.0917 170.7509 514.5087 173.9395 508.5019 174.7497 504.8535 180.9755 506.642 185.2984 506.1049 182.9228 501.4717 177.2339 496.0204 174.4393 500.1697 171.7573 500.5142 175.5253 479.6614 179.3576 453.8218 180.3652 440.803 184.2108 433.4574 189.8334 432.7542 187.2414 429.4226 176.7819 421.6173 176.7625 416.001 182.9095 414.5265 183.8435 410.1806 186.2538 407.073 186.4408 400.8279 188.2434 398.104 185.9483 391.5434 182.6966 387.5833 179.1034 385.5049 184.4091 378.8822 186.6602 374.0652 191.8211 371.106 195.5984 372.7647 196.0694 374.8882 201.2462 372.7761 206.9091 375.8568 211.2417 375.2625 216.5725 371.9798 217.9571 369.9547 214.138 367.5714 213.6246 360.8961 214.9972 358.1218 214.6247 351.0017 212.6616 344.7679 209.0871 339.4602 210.2158 335.0377 207.1526 333.8761 203.8435 329.6012 204.1377 325.8752 201.6822 323.9793 204.5053 321.1892 215.7524 321.7412 218.964 318.862 219.3327 316.2143 227.8148 316.0601 239.6178 313.5652 239.842 316.6182 243.9799 317.1685 247.1961 315.0649 250.8585 308.6438 252.7136 309.6899 253.8131 312.8896 256.3724 312.6157 265.6207 318.8472 266.0043 328.0651 269.0365 327.2228 272.8964 328.9666 276.3912 326.8911 281.6218 327.0691 280.3545 323.2262 286.7074 325.8019 287.3055 331.5611 292.7853 337.7543 292.852 340.2132 298.9534 348.2431 296.97 353.5307 298.1039 356.0962 305.3386 361.0887 310.5302 363.6331 310.71 368.1426 313.6597 370.2521 313.9809 374.432 318.8945 374.4701 321.8232 372.232 326.3611 375.7895 330.3391 370.7782 335.99 372.0172 339.7981 371.1405 342.1368 373.3864 346.0568 368.3461 347.2534 370.3194 356.7506 370.3515 361.1872 371.3038 368.1062 370.7324 372.5351 371.3806 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-76",style:({display:_vm.props.displayDep['FR-reg-76']}),attrs:{"d":"M 505.8798 540.9766 510.1815 547.4676 509.486 554.1421 517.426 561.4742 517.4109 563.1948 512.1912 567.6589 506.2027 575.8651 506.8093 579.6438 504.1443 587.3679 500.2398 585.735 497.3987 586.4829 494.1336 592.676 493.0158 597.5924 483.5063 602.5406 483.004 605.6193 478.206 604.329 475.5573 598.3228 470.4029 598.443 464.3055 601.3784 458.004 607.4699 450.4203 611.3239 446.909 614.2475 441.9442 620.5565 437.1472 619.7016 432.2711 621.0774 425.3336 626.1739 418.8089 634.4945 415.7394 641.0267 414.3077 646.7859 415.4629 649.2769 414.1984 658.6225 413.9428 669.6361 414.9003 678.6313 419.0839 680.6507 422.1621 687.5014 416.9829 688.2886 414.7137 685.7989 408.7085 683.9906 404.3206 684.9614 398.43 689.378 392.7727 689.8855 392.8209 694.8537 385.6706 693.8895 381.36 694.9016 377.4534 690.8829 368.2313 687.0398 365.258 688.7033 361.8624 688.3935 359.9164 691.3667 353.9875 694.1598 350.7887 690.6405 350.6826 687.9999 341.6899 682.6134 337.3518 682.3869 337.6619 677.8971 336.5252 672.6701 329.9092 671.6818 326.9492 669.2288 322.8104 669.5053 320.3461 673.3126 316.9354 665.9659 308.3317 663.1391 304.6027 664.5521 301.3482 659.4742 297.295 658.1772 291.1543 657.8119 286.6888 654.7396 282.1298 653.8708 278.1772 651.7557 275.2696 653.4604 274.2824 657.8693 275.8847 665.2343 267.3946 664.0378 261.0342 664.7995 257.4976 662.0076 253.5576 665.9201 251.2342 662.2569 246.9538 660.6582 239.1483 663.6069 232.7255 663.4324 227.5796 656.3849 222.7408 654.0032 218.536 650.5935 218.0178 646.2046 220.8216 643.557 220.1765 639.2817 222.3667 635.7615 226.6431 634.4579 226.0548 630.867 229.1687 628.8989 230.9796 624.8272 237.1076 617.8106 235.4918 615.4578 239.0951 605.9367 234.5501 604.9077 236.2731 597.6455 233.4285 593.0186 228.7738 591.7161 224.7783 592.0219 223.0162 587.776 225.507 585.7286 225.0001 582.6983 227.769 581.1457 227.1893 575.611 228.5283 574.8807 228.7334 569.1032 226.2899 567.9943 232.9623 564.2599 238.9106 565.6318 241.7501 568.0805 244.2204 566.7069 244.0029 562.3915 252.5919 558.7514 257.5755 561.4694 260.7874 559.555 265.6643 558.745 266.7283 556.6763 272.1787 556.8906 273.6265 555.1966 279.0178 559.3071 282.8618 556.3114 289.4429 547.8529 292.4442 546.2347 295.2374 540.0664 290.9517 536.1478 291.7894 532.7735 301.0328 533.2673 301.4638 528.1451 299.5563 526.6895 299.8188 518.7515 307.466 511.4697 307.1201 509.0204 311.5483 508.0187 315.1731 505.6732 319.6462 498.0963 319.5511 495.7609 324.162 492.8622 322.7286 483.2886 329.2446 479.7576 335.9348 481.2997 341.7265 488.5784 345.6873 489.3962 350.5222 485.4312 359.2193 485.6637 360.3037 492.8352 364.9963 498.8798 364.0575 507.6542 364.8812 512.438 373.8578 510.1209 374.2707 511.8609 382.733 511.7075 383.6813 508.566 386.4286 506.5357 387.2233 501.6758 391.113 494.1392 396.6807 489.0914 399.3178 491.29 398.8128 494.7474 403.5197 494.2781 405.2398 499.9564 408.4008 501.979 407.6008 505.7079 409.4452 511.0891 411.8985 509.84 414.7792 501.4805 414.5153 500.0555 419.957 491.8091 422.4765 495.0882 428.0499 489.9259 432.8405 487.2065 435.3106 488.7009 435.48 491.9155 438.9294 499.1185 444.3434 497.0981 445.5149 493.7582 447.9723 493.608 449.3353 497.4538 453.9506 496.8519 459.105 503.8214 462.7278 512.0537 462.5263 514.4669 465.6757 517.6464 468.0233 523.1204 468.0037 525.5804 472.6449 530.8686 474.1267 536.446 478.7568 537.6843 483.5464 541.836 487.3986 535.9212 497.4586 535.6326 500.7215 538.5655 505.8798 540.9766 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-84",style:({display:_vm.props.displayDep['FR-reg-84']}),attrs:{"d":"M 615.566 470.7748 612.5169 469.8248 607.2517 472.6203 607.6976 475.3573 602.6362 475.1832 599.1561 470.6042 593.0782 471.8292 592.0497 479.4798 597.7753 479.8556 600.9353 484.7568 600.9557 491.7211 598.0723 490.2905 595.1047 492.0906 588.4698 491.3597 584.8876 494.9175 582.7667 493.6766 578.3189 496.5207 576.7538 500.7772 571.0856 502.6232 571.749 506.11 566.8791 509.9061 561.9137 508.9317 561.7328 512.5723 559.124 517.8922 560.8991 521.4238 559.7008 524.0511 552.6139 522.2307 551.8461 525.6995 549.2874 527.6018 553.7185 534.3925 560.8533 534.5765 564.2928 538.9364 565.1393 545.2103 564.6507 549.1359 560.7431 545.812 558.8633 546.0827 557.0944 550.4299 554.3835 551.9833 550.9343 549.2891 547.8808 548.6217 547.9106 545.3926 540.5294 543.3012 536.2848 544.3135 534.2647 539.5276 530.1787 539.3237 529.2107 537.4257 521.4417 541.4674 519.0324 541.3643 515.329 543.7942 514.3783 538.168 512.2444 536.4633 507.4557 536.9493 505.8798 540.9766 500.7215 538.5655 497.4586 535.6326 487.3986 535.9212 483.5464 541.836 478.7568 537.6843 474.1267 536.446 472.6449 530.8686 468.0037 525.5804 468.0233 523.1204 465.6757 517.6464 462.5263 514.4669 462.7278 512.0537 459.105 503.8214 453.9506 496.8519 449.3353 497.4538 447.9723 493.608 445.5149 493.7582 444.3434 497.0981 438.9294 499.1185 435.48 491.9155 435.3106 488.7009 432.8405 487.2065 428.0499 489.9259 422.4765 495.0882 419.957 491.8091 414.5153 500.0555 414.7792 501.4805 411.8985 509.84 409.4452 511.0891 407.6008 505.7079 408.4008 501.979 405.2398 499.9564 403.5197 494.2781 398.8128 494.7474 399.3178 491.29 396.6807 489.0914 391.113 494.1392 387.2233 501.6758 386.4286 506.5357 383.6813 508.566 382.733 511.7075 374.2707 511.8609 373.8578 510.1209 364.8812 512.438 364.0575 507.6542 364.9963 498.8798 360.3037 492.8352 359.2193 485.6637 363.5708 483.2583 361.1094 479.3908 365.5923 477.3822 367.1571 472.9243 366.8352 466.3945 373.7433 458.3847 375.6363 458.0057 376.7471 451.0016 380.951 453.6466 383.3518 450.7639 385.2291 442.6872 382.2269 436.9949 385.0809 433.3926 385.5973 429.0945 383.3244 424.7097 380.6043 423.2042 378.1054 418.7032 383.7926 415.3167 386.7006 412.1376 389.4646 405.5893 389.9962 402.1737 387.5167 399.3609 387.7053 391.0879 385.844 389.9367 385.2451 385.7844 383.1657 382.53 378.5501 378.6019 375.4729 379.1073 372.8688 374.4113 372.5351 371.3806 373.9039 367.0353 377.3765 363.6588 384.4207 362.5055 386.5862 363.622 390.601 361.2262 388.8725 356.6527 390.5091 351.7536 395.6414 347.7056 402.5349 346.5979 409.7861 341.1901 413.6778 341.8867 414.5952 344.7834 418.9929 347.2286 423.0799 351.0697 426.6278 348.0575 429.0344 350.2433 434.7427 348.5039 437.9739 352.9849 442.523 350.1808 441.9281 348.0451 445.9921 346.4237 451.8437 356.7582 452.5015 361.9679 459.0464 365.5799 464.3716 365.685 467.0109 369.3782 465.5065 372.9126 465.9586 379.2868 461.2172 381.4127 460.8972 387.6069 465.451 388.4802 468.5 391.0865 472.6694 388.721 476.1248 390.7021 478.3957 388.9235 484.3413 390.7542 486.9139 388.3881 489.4213 380.7772 494.6049 382.8743 500.7618 381.7159 502.6446 379.8161 505.8889 381.8018 509.8793 388.8588 515.4871 371.5031 515.5619 368.6598 517.8213 362.8241 521.3459 362.9214 524.4841 365.081 530.8999 362.6573 533.2398 365.7764 537.0152 367.7611 547.8245 377.7575 547.8718 381.8076 551.5867 381.2529 557.3322 375.473 560.974 377.8295 561.687 381.6374 568.7002 381.3315 571.7236 379.4431 575.5982 372.9514 579.8106 368.6261 585.7794 372.38 582.3299 378.9522 583.6476 381.5716 575.768 384.5727 575.1609 390.3621 579.9873 389.6381 584.6504 390.2287 589.7349 384.9185 593.0809 383.2595 589.8054 378.9128 592.2123 372.8815 595.2408 371.6546 597.9441 373.9087 606.4243 369.217 615.9277 367.7438 619.6595 368.7474 619.6906 373.5814 624.1582 377.2517 621.1116 383.7319 620.9861 388.8633 626.5913 390.1502 628.3205 394.3305 633.3362 399.4797 635.4605 405.2432 633.0795 409.4142 628.6386 412.1029 623.3308 412.77 623.2294 421.5647 625.4908 424.2619 633.6088 428.8695 634.7136 438.456 637.5613 440.8971 640.7185 441.0553 641.6381 443.8339 645.4034 445.6562 644.424 449.4327 641.9378 452.0723 643.4075 457.7043 634.0048 462.0455 633.4848 464.1992 627.8554 468.7601 623.1914 466.4208 618.2828 468.195 615.566 470.7748 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-93",style:({display:_vm.props.displayDep['FR-reg-93']}),attrs:{"d":"M 483.004 605.6193 483.5063 602.5406 493.0158 597.5924 494.1336 592.676 497.3987 586.4829 500.2398 585.735 504.1443 587.3679 506.8093 579.6438 506.2027 575.8651 512.1912 567.6589 517.4109 563.1948 517.426 561.4742 509.486 554.1421 510.1815 547.4676 505.8798 540.9766 507.4557 536.9493 512.2444 536.4633 514.3783 538.168 515.329 543.7942 519.0324 541.3643 521.4417 541.4674 529.2107 537.4257 530.1787 539.3237 534.2647 539.5276 536.2848 544.3135 540.5294 543.3012 547.9106 545.3926 547.8808 548.6217 550.9343 549.2891 554.3835 551.9833 557.0944 550.4299 558.8633 546.0827 560.7431 545.812 564.6507 549.1359 565.1393 545.2103 564.2928 538.9364 560.8533 534.5765 553.7185 534.3925 549.2874 527.6018 551.8461 525.6995 552.6139 522.2307 559.7008 524.0511 560.8991 521.4238 559.124 517.8922 561.7328 512.5723 561.9137 508.9317 566.8791 509.9061 571.749 506.11 571.0856 502.6232 576.7538 500.7772 578.3189 496.5207 582.7667 493.6766 584.8876 494.9175 588.4698 491.3597 595.1047 492.0906 598.0723 490.2905 600.9557 491.7211 600.9353 484.7568 597.7753 479.8556 592.0497 479.4798 593.0782 471.8292 599.1561 470.6042 602.6362 475.1832 607.6976 475.3573 607.2517 472.6203 612.5169 469.8248 615.566 470.7748 618.3289 477.7665 622.2392 477.8223 623.6993 482.2751 622.9164 485.3159 629.6617 490.6396 633.433 489.4124 637.7641 491.0891 639.4937 499.717 635.1796 505.975 634.6643 512.4126 630.4429 515.8817 630.9117 518.3719 636.0072 523.934 632.9773 524.6566 633.3907 528.1509 637.0538 534.196 639.2967 535.3945 640.2996 538.9452 643.8264 538.9432 649.2196 540.6256 656.1946 545.6002 659.8384 545.068 660.6981 547.3113 668.9128 544.6846 675.4925 543.8241 681.0428 548.3487 680.7382 551.9412 677.0539 558.4049 674.5599 559.325 673.39 563.608 669.7859 566.2279 672.0295 573.0379 662.6007 578.466 656.1498 581.3735 651.1054 584.631 648.5138 591.3253 645.7372 593.2651 639.7648 594.7048 638.4442 601.2763 635.0115 604.2852 627.8671 605.1881 626.7306 610.2948 619.9173 615.5506 624.3658 618.6349 624.2834 622.1912 622.2009 624.8201 618.6433 623.0337 614.797 626.203 607.6404 627.6161 607.2122 631.4647 602.2708 629.1737 597.9332 629.7232 593.6834 632.9138 589.3034 632.9886 582.4352 630.1168 578.1387 635.9042 574.9697 633.7197 575.1877 630.7438 568.4255 628.1332 565.7972 625.1644 561.2841 626.56 554.5738 623.7063 549.368 624.1813 548.88 615.8939 545.5135 612.0662 540.6687 614.8499 529.0717 614.3115 526.8016 610.1133 536.9691 608.2608 540.5716 604.9563 539.2583 601.8827 535.1588 603.6221 533.6369 599.3982 529.1673 597.9128 527.5949 603.8377 530.5805 604.7487 530.2542 609.1529 526.2672 608.9505 523.4305 606.9918 519.4798 609.5735 520.8761 612.5933 518.1781 615.3887 515.2352 614.0081 509.1989 614.236 503.4445 613.0716 504.042 609.598 500.561 605.9881 494.3526 606.3732 483.004 605.6193 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-reg-94",style:({display:_vm.props.displayDep['FR-reg-94']}),attrs:{"d":"M 745.434 672.9775 744.3805 669.4702 747.4757 665.7329 752.1577 664.3399 752.2662 661.5622 756.2391 659.3118 764.3318 657.526 767.7517 651.5716 772.1287 649.5258 775.4882 649.3658 779.5838 653.762 782.7354 648.6965 782.2232 644.0257 780.3327 640.4578 780.2864 636.3721 782.125 634.4399 780.8198 628.3368 785.7455 626.6757 787.9987 628.3511 790.8756 642.4178 789.7642 645.7449 789.2274 653.9011 794.9667 662.9196 796.323 672.2185 795.9658 675.6717 798.2556 683.5921 799 695.9155 797.7445 699.3354 791.7584 710.2555 791.2878 716.6556 792.6466 725.2157 792.6801 730.8979 791.3064 739.3639 787.4526 743.5756 786.234 752.6295 783.9743 757.0422 776.0954 755.5704 776.8431 753.3085 772.5925 750.4604 765.7384 749.3384 758.4137 744.5479 756.3844 741.6893 757.1362 738.6247 761.8161 736.8054 763.7976 733.4538 751.3391 731.9061 751.7339 725.8112 754.5043 724.6446 756.0523 718.0732 754.4185 715.4583 750.8966 717.1208 746.3886 717.2641 743.1974 713.1091 746.4757 712.5971 746.6999 709.333 751.7909 706.0393 748.711 701.0002 746.5813 701.6633 741.6344 698.345 740.4861 691.5477 747.2319 689.0394 743.2022 683.8845 738.6663 681.5325 744.0805 677.3101 745.434 672.9775 Z","fill":"#6b6b6b"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}})])])
}
var FranceRegvue_type_template_id_d6a41bf8_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/FranceReg.vue?vue&type=script&lang=js



/* harmony default export */ var FranceRegvue_type_script_lang_js = ({
  mixins: [base],
  props: {
    props: Object
  }
});

;// CONCATENATED MODULE: ./src/components/maps/FranceReg.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_FranceRegvue_type_script_lang_js = (FranceRegvue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/FranceReg.vue





/* normalize component */
;
var FranceReg_component = normalizeComponent(
  maps_FranceRegvue_type_script_lang_js,
  FranceRegvue_type_template_id_d6a41bf8_render,
  FranceRegvue_type_template_id_d6a41bf8_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var FranceReg = (FranceReg_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/FranceAcad.vue?vue&type=template&id=4c714fc1
var FranceAcadvue_type_template_id_4c714fc1_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"version":"1.1","viewBox":_vm.props.viewBox,"xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{attrs:{"fill-rule":"nonzero","stroke":"#FFFFFF","stroke-width":"0.2%"}},[_c('path',{staticClass:"FR-acad-09",style:({display:_vm.props.displayDep['FR-acad-09']}),attrs:{"fill":"#6b6b6b","d":"M529.125,126.425l-9-10.3l-2-6.6l-3.3-2.3l-12.4-2.8l-5,3.9l-3.1,0.1l-5.8-2.6l-0.2-0.1l-0.2-0.1l-3.6-3.1\n            h-7.7l-1.4,3l-6,0.9h-0.3l-3.8-8.1h-0.1l-2,1.4l-2.3,3l-6.4-2.3l-2.1,2.2l0.9,9.8l-3.6,5.7l1.5,2.9l-4.6,4.1l2.4,10.1l-1.1,4.7\n            l2.9,1.4l-0.6,4.4l-3.5,1.2l-1.3,6.6l3.6,5.2l0.6,4.5l3.8,5.9l18.9,11.6l-2.6,2.7l14.3,10.7l-2,9.6l6.2,5.1l0.3,3.6l3-1.2l1.4,2.8\n            v0.1l9.1-6.4l7.9,4.7l6.4-1.8l5.5,4.1l5.7-2.5l9.4,7.4l1.1-0.7l3.1-2.7v-6.9l5.3-8.8l4.7-15.2l1.2-1l-5.8-2.6l1.5-11.2l-2.2-1.5\n            l3.9,0.4l2.5-2l3.4-15l-4.9-3.7l-3.2-0.5l-1.6,2.8l-2.6-1.8l1.9-2.8l-6.1-2.8l5.4-12.1l1,3.1l10.1,5.3l10.1-0.4l1.6-2.9l1.5-4.7\n            h-0.2l-9.3-8.5l-7.2,4.6l-7.7-1.3l-2.9,1.4l-2.5-4.4l-3.4-1.6l-3.2,1.8l-0.3,3.3L529.125,126.425z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-11",style:({display:_vm.props.displayDep['FR-acad-11']}),attrs:{"fill":"#6b6b6b","d":"M539.125,143.625l6.1,2.8l-1.9,2.8l2.6,1.8l1.6-2.8l3.2,0.5l4.9,3.7l-3.4,15l-2.5,2l-3.9-0.4l2.2,1.5\n            l-1.5,11.2l5.8,2.6l-1.2,1l-4.7,15.2l-5.3,8.8v6.9l-3.1,2.7l8.5,5.6l0.5,10.2l3.1-0.2l2.5,5.7l-0.5,0.1l3.3,0.7l-0.8,3.4h8.5\n            l3-1.1l0.5-3.4l3.3-0.2l0.2-3.5l2.5-2.2l-3.2-8.7l3-17.3l-2.1-11.6l6.6-15.7l1.6-17.6l11.8-15.3l4.5-12l-9.7-3.4l-16.3-0.5l0,0\n            l-1.5,4.7l-1.6,2.9l-10.1,0.4l-10.1-5.3l-1-3.1L539.125,143.625z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-15",style:({display:_vm.props.displayDep['FR-acad-15']}),attrs:{"fill":"#6b6b6b","d":"M407.925,55.425l-4.6-5.5l-6.8,1.2l-6.1-1.5l-2.5,2.3l-2.4-10.2l-6.1-1.5l-1.3-3l-3.2,2.4l-3.6-1.2\n            l-3.4-9.5l0.5-3.4l-4.4-5l-6.5,0.6l-6.3,4.4l-3.3-1.5l-3.2-5.9h-3.7l-1.5-3l1.2-6.4l-2.9-6.8l-3.1-1.8l-18.3,4.5l-15.3,4.8\n            l-6.5,5.5l-0.1,20.3l3.3,4l-3.1-2l-0.8,6.6l0.5,4.3l2.6,1.8l13.7,1.2l1.1,3.4l8,6.5l13.4-2.9l-2.9,6.5l1.9,3.5l2.8-3.5l9.4,3.9\n            l1-3.1l3.1,5.2l2.7-1.9l0.9,3.5l9.6-2l3.7,3l14.2-0.7l2.9-2.5l2.6,1.9l5.8-2l17.6,4.7v0.1l3.6-6.8l-3.4-6l2.3-7.2L407.925,55.425z\n            "},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-14",style:({display:_vm.props.displayDep['FR-acad-14']}),attrs:{"fill":"#6b6b6b","d":"M408.425,75.025l0.1-0.4l-17.6-4.7l-5.8,2l-2.6-1.9l-2.9,2.5l-14.2,0.7l-3.7-3l-9.6,2l-0.9-3.5l-2.7,1.9\n            l-3.1-5.2l-1,3.1l-9.4-3.9l-2.8,3.5l-1.9-3.5l2.9-6.5l-13.4,2.9l-8-6.5l-1.1-3.4l-13.7-1.2l-4.2,1.6l-0.4,3.4l3.4,6.2l-3.1-1.7\n            l-7.3,10l0,0l14.1,12l3.9,9.5l-3.5,5.2l1.5,9.9l-1.5,9l4,9.4l-4.5,2.9l1.8,3.6l6.3,1.2l7.1-2l10.3,2.5l2.5-1.9l12.2,7.2l7.7,1.1\n            l2.3-2.4l2.8,1.8l9.1-3.3l3,0.8l2,2.8l-0.8,3.2l4.4,4.7l10.4,6.8l8.6-11.5l-2.8-1.5l1-3l-1.8-2.8l6.1-2.5l-3.6-9l1.4-3l8.9-4.4\n            l6,2l1.1-3.2l-0.3-16l3.1-0.3l5.6-8.1l-1.5-3.5l1.5-3.9l-0.9-6.4l-0.4,0.1L408.425,75.025z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-10",style:({display:_vm.props.displayDep['FR-acad-10']}),attrs:{"fill":"#6b6b6b","d":"M412.925,76.325l0.9,6.4l-1.5,3.9l1.5,3.5l-5.6,8.1l-3.1,0.3l0.3,16l-1.1,3.2l-6-2l-8.9,4.4l-1.4,3l3.6,9\n            l-6.1,2.5l1.8,2.8l-1,3l2.8,1.5l-8.6,11.5l-0.5,2.2l-2.8,2.5l3.9,9.3l2.9,1.9l-3.4,5.6l-3.3,0.8l0.5,9.6l4.1,1.6l5,6.3l2.8,9.3\n            l2.2-2.5l4.1,4.7l5.6,12.3l14.1-1.8l3.3,1.5l2.5-2.2l6.9-1l5.3-4.4l4.3,0.6v-0.1l3.9,0.8l0.6,3.1l3,1.1l-0.4,3.2l3.3,0.4l1.9,2.4\n            l1.2,3.5l-3.1,2.8l2.5,5.3l10.4,2.3l3.4,1.7v3.2l5.4-2.1l1.3-2.8l7.4-4.4l2.5,2l3.4-1.1l0.3-9.4l2.4-2.7l3.2,0.4l2.5-3.6l-0.2-1.6\n            v-0.1l-1.4-2.8l-3,1.2l-0.3-3.6l-6.2-5.1l2-9.6l-14.3-10.7l2.6-2.7l-18.9-11.6l-3.8-5.9l-0.6-4.5l-3.6-5.2l1.3-6.6l3.5-1.2\n            l0.6-4.4l-2.9-1.4l1.1-4.7l-2.4-10.1l4.6-4.1l-1.5-2.9l3.6-5.7l-0.9-9.8l2.1-2.2l6.4,2.3l2.3-3l2-1.4l-3.6,0.3l0.1-3.6l-6.8-1.6\n            l-7.6-6.7l-6.6,0.2l1-7.3l-3.2-6.4v-4.7l3-5.6l-3.1-1.8l-4.5,4.8l-2,7.2l-9.8,4.4l-6-2L412.925,76.325z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-16",style:({display:_vm.props.displayDep['FR-acad-16']}),attrs:{"fill":"#6b6b6b","d":"M347.325,139.025l-7.7-1.1l0.3,2.3l-2,2.6l-0.1,0.1c-0.7,0.5-1.5,1-2.3,1.6c-1,0.7-2,1.4-3,2.1l-2.9-1.2\n            l-3.7,1.5l1.5,2.2l-0.1,1.2l0.3,3.4l2.3-1.6v-2.2h5.7l5.4,2.5l5.1,5v3.6l-1.8,4.9h0.2l5.4,2.3l3.9,0.3v4.7l-1.2,0.7l-2.4-3.4h-4.9\n            l-0.5,1.5h-5.2v0.8h-1.8l-2.8,12.4l-3.9,5.3l0.8,3.5l4.7,4.8l-0.6,3l-3,1.7l10.3,0.8l12.6-3l4.3-6l0.3-6.6l17.3-3.4l-0.5-9.6\n            l3.3-0.8l3.4-5.6l-2.9-1.9l-3.9-9.3l2.8-2.5l0.5-2.2l-10.4-6.8l-4.4-4.7l0.8-3.2l-2-2.8l-3-0.8l-9.1,3.3l-2.8-1.8L347.325,139.025\n            z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-18",style:({display:_vm.props.displayDep['FR-acad-18']}),attrs:{"fill":"#6b6b6b","d":"M339.925,140.225l-0.3-2.3l-12.2-7.2l-2.5,1.9l-10.3-2.5l-7.1,2l-6.3-1.2l-1.8-3.6l-4.5,10.6h-4.2\n            l-2.6,2.8l2,6.6l4.1,7.4l0.5,12.2l7,6.1l1.4,6.3l2.4,2.4l3.5-0.7l3.2,11.9l0,0l7.8-0.9l2.2-3.1l1.6,2.9l3.1-1.9l4.2,0.6l3.9-5.3\n            l2.8-12.4h-6.4l-4.3-4.3l-5.2-2v-1.7h-4.5l-2.1-1.7l-0.1-0.7c-0.1-0.4-0.1-0.7,0-1.1l-0.1-0.3l0.1-0.1l3-3.7h4.4l1.6-1\n            c0.3-0.3,0.5-0.4,0.8-0.6l2.6-1.9l-0.3-3.4l0.1-1.2l-1.5-2.2l3.7-1.5l2.9,1.2c1-0.7,2-1.4,3-2.1c0.8-0.6,1.6-1.1,2.3-1.6l0.1-0.1\n            L339.925,140.225z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-17",style:({display:_vm.props.displayDep['FR-acad-17']}),attrs:{"fill":"#6b6b6b","d":"M335.625,149.925h-5.7v2.2l-2.3,1.6l-2.6,1.9c-0.3,0.2-0.5,0.3-0.8,0.6l-1.6,1h-4.4l-3,3.7l-0.1,0.1\n            l0.1,0.3c-0.1,0.4-0.1,0.7,0,1.1l0.1,0.7l2.1,1.7h4.5v1.7l5.2,2l4.3,4.3h6.4h1.8v-0.8h5.2l0.5-1.5h4.9l2.4,3.4l1.2-0.7v-4.7\n            l-3.9-0.3l-5.4-2.3h-0.2l1.8-4.9v-3.6l-5.1-5L335.625,149.925z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-05",style:({display:_vm.props.displayDep['FR-acad-05']}),attrs:{"fill":"#6b6b6b","d":"M448.425,218.225l-3.3-0.4l0.4-3.2l-3-1.1l-0.6-3.1l-3.9-0.8v0.1l-4.3-0.6l-5.3,4.4l-6.9,1l-2.5,2.2\n            l-3.3-1.5l-14.1,1.8l-5.6-12.3l-4.1-4.7l-2.2,2.5l-2.8-9.3l-5-6.3l-4.1-1.6l-17.3,3.4l-0.3,6.6l-4.3,6l7.5,8.4l0.5,6.5l-5.2,5.5\n            l1.2,3.3l-1.7,2.7l-6,3.7l3.4,3.2l2.4,7.8l-3.1,0.7l-1.7,2.7l2.2,7.3l-1.9,6.5l3.1,2.4l3.5,8.5l0.3,6.6l2.5,2.2l-0.3,6.6l-1.5,10\n            l0,0l6.5,7.1l7.2,0.2l3.2-2l4.8,4.2l6.7-6.8l7.5,15.2l8.9,3.3l1.8,2.7l-0.5,6.1l-4.2,5.2v3.4l5.8,3.7l9-1.7l2.3,2.4l6.4-4.3\n            l0.4-3.4l2.6-2.1l2.4,2l2.6-1.9l2.7,1.7l1.8-2.6l6.5,8.7l6.6-23.5l6.6,1.7l6-1.4l5.4,3.6h0.1l3.1-1.2l-1-5.9l3.2-5.6l-2.4-9.8\n            l-2.8-1.8l5.6-4.2l-9.3-4.9l-0.5-3.4l1.2-4.5l7-7.6l2-9.3l1.3-1.4l-4.3-9.2l-3-1.6l5.3-4.6l-0.2-3.5l-2.3-3.5l-3,1.9l-5.4,2.1\n            v-3.2l-3.4-1.7l-10.4-2.3l-2.5-5.3l3.1-2.8l-1.2-3.5L448.425,218.225z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-04",style:({display:_vm.props.displayDep['FR-acad-04']}),attrs:{"fill":"#6b6b6b","d":"M470.125,237.325l3-1.9l2.3,3.5l0.2,3.5l-5.3,4.6l3,1.6l4.3,9.2l-1.3,1.4l-2,9.3l-7,7.6l-1.2,4.5l0.5,3.4\n            l9.3,4.9l-5.6,4.2l2.8,1.8l2.4,9.8l-3.2,5.6l1,5.9l-3.1,1.2l4.7,8.7h3.3l0.1,3.4l3.3-0.1l4.5-5l3.8,1.8l0.5,3.2l4.3-0.3l6.1-3.6\n            l5.3-7.3l-0.2-0.2l3-10.1l0.1-0.2l0.9-3.2l13.2-12.1l-1-10.9l9.7-6.7l13.1-16.2l-0.2-3.2l2.6-2.4l-4.8-4l1-3.4l0.3-0.2l4-4.5\n            l0.3-0.1l0.1-0.1l4.4-0.5l-2.5-5.7l-3.1,0.2l-0.5-10.2l-8.5-5.6l-1.1,0.7l-9.4-7.4l-5.7,2.5l-5.5-4.1l-6.4,1.8l-7.9-4.7l-9.1,6.4\n            l0.2,1.6l-2.5,3.6l-3.2-0.4l-2.4,2.7l-0.3,9.4l-3.4,1.1l-2.5-2l-7.4,4.4L470.125,237.325z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-07",style:({display:_vm.props.displayDep['FR-acad-07']}),attrs:{"fill":"#6b6b6b","d":"M358.225,227.725l1.7-2.7l-1.2-3.3l5.2-5.5l-0.5-6.5l-7.5-8.4l-12.6,3l-10.3-0.8l3-1.7l0.6-3l-4.7-4.8\n            l-0.8-3.5l-4.2-0.6l-3.1,1.9l-1.6-2.9l-2.2,3.1l-7.8,0.9l0,0l-3.2-11.9l-3.5,0.7l-2.4-2.4l-1.4-6.3l-7-6.1l-0.5-12.2l-4.1-7.4\n            l-6.8,7.8l0.3,3.1l-6.7,1.5l-3.2-1.8l-6,3.6l-6.7,1.2l-2.6,2.8v3.7l5.4,4.9l-0.2,5l0.6,5.2l-8.4,5.8l1.1,8.4l0,0l2.4,4.3l-0.4,2\n            v0.1l-2.3,2.3l1.9,2.5l0.4,6.3l-7,11.9l-3.6,1.2l-0.7,3.8v0.1l-8.6,5l-3.1-0.3l0.5,3.7l-6.8-2.9l-0.9,2.7l-1.8,10.4l-6.5,17.2v0.1\n            l6.1,3.6l0.4,3.6l3.2-0.3l1.5,6.8l3.2,1.6l11.2-0.5l-1.6-3.2l6,3.3l0.2,3.5l10.3,14.7l-0.8,6.5l5.2,4.9h3.3l2.8,6.1l2.9,1.4\n            l-1.7,3.1l2.9,1.3l6.5-1.2l1.9,2.7l3.2-3.2l12.8-1.2l2.2-2.8l14.8,3l3.1-0.9l5.5,0.2v-0.1l3.9-6.6l9.8-1.1l1.9-2.7l-1.9-6l2.5-2.1\n            l9.5-3.3l5.4-4.1h4.5l1.5-10l0.3-6.6l-2.5-2.2l-0.3-6.6l-3.5-8.5l-3.1-2.4l1.9-6.5l-2.2-7.3l1.7-2.7l3.1-0.7l-2.4-7.8l-3.4-3.2\n            L358.225,227.725z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-01",style:({display:_vm.props.displayDep['FR-acad-01']}),attrs:{"fill":"#6b6b6b","d":"M382.825,305.425l-4.8-4.2l-3.2,2l-7.2-0.2l-6.5-7.1l0,0h-4.5l-5.4,4.1l-9.5,3.3l-2.5,2.1l1.9,6l-1.9,2.7\n            l-9.8,1.1l-3.9,6.6v0.1l1.2,4.4c2.8,1.8,5.6,3.6,8.3,5.4l4.1,9.4l-0.6,4.6l2.6,7.6l-10.5,9.7l5,6.1l1.4,6.2l-2,2.8l2.2,15.7\n            l-7-2.2l-0.7,3.8l-8.5,10.7l2,2.5l-2.6,2.1l-0.3,3.9l-3.5,1.3l1.7,3.7l-3.7,2.1l1.1,6.2l4.3,6.7l-2,6.5l3.7,5.6l5.8-3.6l7.1,1.8\n            l4.3-5.5l3.4-10.2l5.3-4.8l5.9,4.6l1.4,4.7l2.7,1.8l-0.6,3.4l2.9,5.7l7.7-17.8l2.1,2.5l8.7-7.5l2.6,1.9l1.6,6.4l7.2,1.4l0.2-3.2\n            l3.2,0.3l10.1,8.6l7.5-7.2l6.7-2.1l3.8-6.7l3.8-0.4l-0.8-3.2l3-2.5l1.7-5.9l2.9,1.4v-3.5l1-4.7l-2.5-2.6l-3,1.1l-0.7-6.2l-6.7-2.5\n            l-7.1,3.4l-3.1,0.5l-2.6-2.3l-3.1,0.9l3.3-8l-3-8.4l-5.1-4.2l-5.3-8.7l2.4-7l-2.8-3.1l6.2-5.3l-1.7-14.1l0.5-3.3l4.4-1.5l4.2-5.2\n            l0.5-6.1l-1.8-2.7l-8.9-3.3l-7.5-15.2L382.825,305.425z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-22",style:({display:_vm.props.displayDep['FR-acad-22']}),attrs:{"fill":"#6b6b6b","d":"M325.525,321.925L325.525,321.925l-5.5-0.2l-3.1,0.9l-14.8-3l-2.2,2.8l-12.8,1.2l-3.2,3.2l-1.9-2.7\n            l-6.5,1.2l-2.9-1.3l-1-0.3l-1.4,2.7l-3.8-0.9l-1.3,3.3l-6.4,3.1l-3.3,5.6l-0.2,9.6l5.2,4l-0.7,3.6l-2.9,1.2l-4.5,9l-3,0.7\n            l-3.9,4.3h0.1l7,3.4l-0.1,5.3l10.7-1.3l8,6.3l-2.3,3.1l6.5,2.4l3.1,5.3l-4,5.2l2,3.3l-2.3,2l2.6,1.7l0.2,3.4l3.7,0.8l3.8,7.8\n            l9.2-0.8l7.5,6.8l5.9-3l7.6,0.1l3.7-2.1l-1.7-3.7l3.5-1.3l0.3-3.9l2.6-2.1l-2-2.5l8.5-10.7l0.7-3.8l7,2.2l-2.2-15.7l2-2.8\n            l-1.4-6.2l-5-6.1l10.5-9.7l-2.6-7.6l0.6-4.6l-4.1-9.4c-2.7-1.8-5.5-3.6-8.3-5.4L325.525,321.925z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-03",style:({display:_vm.props.displayDep['FR-acad-03']}),attrs:{"fill":"#6b6b6b","d":"M470.225,317.425h-0.1l-5.4-3.6l-6,1.4l-6.6-1.7l-6.6,23.5l-6.5-8.7l-1.8,2.6l-2.7-1.7l-2.6,1.9l-2.4-2\n            l-2.6,2.1l-0.4,3.4l-6.4,4.3l-2.3-2.4l-9,1.7l-5.8-3.7v-3.4l-4.4,1.5l-0.5,3.3l1.7,14.1l-6.2,5.3l2.8,3.1l-2.4,7l5.3,8.7l5.1,4.2\n            l3,8.4l-3.3,8l3.1-0.9l2.6,2.3l3.1-0.5l7.1-3.4l6.7,2.5l0.7,6.2l3-1.1l2.5,2.6l3.9-0.8l3.6-5.5l5.6-3v-5l-0.2-1.2l0,0l5-4.5\n            l-2.1-2.8l2.8-2.8l7.8-1.7l2.8-5.5l-0.4-8l6.8,2.2l3.4-5.7l3.2-0.8l12.4,17.1l6.5-7.6l2.5-15.2v-0.1l-0.8-10.4h3.2l3.1-3.2h-0.3\n            v-0.1l0.6-5.6l6.5-4l1.2-7.8l-3.9-2.2l-5.3,7.3l-6.1,3.6l-4.3,0.3l-0.5-3.2l-3.8-1.8l-4.5,5l-3.3,0.1l-0.1-3.4h-3.3\n            L470.225,317.425z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-02",style:({display:_vm.props.displayDep['FR-acad-02']}),attrs:{"fill":"#6b6b6b","d":"M459.325,375.125l-7.8,1.7l-2.8,2.8l2.1,2.8l-5,4.5l0,0l0.2,1.2v5l-5.6,3l-3.6,5.5l-3.9,0.8l-1,4.7v3.5\n            l-2.9-1.4l-1.7,5.9l-3,2.5l0.8,3.2l-3.8,0.4l-3.8,6.7l-6.7,2.1l-7.5,7.2l2.3,10.2l7.9,16l8.7,5.6l7.9-4.9l0.2,3.5l2.8-2.6h3.1\n            l6.8,3.9l-0.1-4.1l6.5,0.7l4,4.8l9.7-4.4l2.7,1.6l2.9-2.5l0.5,6.5l10.5,1l0.2,3.2l5.8,2.5l4.8-5.3l2.6-0.1l1.1-0.3l0.2-5.2\n            l-11.2-6.4l-1.7-3l3.7-5.7l4.6,1.6l2.8-2.9l-3.3-2.4l2.8-7.6l6.4-0.4l0.4-3.8l1.1-3.5l6.1-1.3l1.1-3.2l14.1-4.3l3.5,1.2l0.4-6.5\n            l-4.9-3.5l-2-4.7l1.8-4.3l8.7,4l2.2-2.3l6.9-1.3v-0.1l21.6-9.3l2.3-4l-1.3-3l3.1-5.8l-8.2-5.7l-2.5-6.2l0.8-3.5l-6.3-2.6l-3.2-3\n            l-0.6-3.4v-0.2l0.2-0.2l9.3-9l1.1-3.5l-3.8-5.1l-8.8-8.3l2.7-7l-4.2-6l0.5-3.2l-3.7-0.8l-9.5,0.7l-5.6,4.7l-3.9-1.7l-3.2,6.6\n            l2.7,3.4l-7.8,6l-7.6,1.1l-3.1,3.2h-3.2l0.8,10.4v0.1l-2.5,15.2l-6.5,7.6l-12.4-17.1l-3.2,0.8l-3.4,5.7l-6.8-2.2l0.4,8\n            L459.325,375.125z M463.725,460.525l-2.7,4l-4.5,1.4l-2.2-2.5l1.4-3.5l3-2.5L463.725,460.525z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-20",style:({display:_vm.props.displayDep['FR-acad-20']}),attrs:{"fill":"#6b6b6b","d":"M202.425,118.225l-14.1-1.5l-10.7-3.7l-5.8,2.5v0.1l-0.2,0.1l-7.2-12l2.7-8.7l-1.1-3.1l-7.3-0.8l-2.8,2\n            l-7.3,0.5l-12.8-5.2l-0.2,3.1l3.7,2.1l0.5,5.5l-2.1,2.3l2.9,11.5l9.5,10.2l-1.5,11.3l2.2,3.1l-0.6,6.8l-2.5,6.4l4.6,10.6l3,1.4\n            l2.8-1.8l-1.1,3.3l-8,0.8l5.4,10.8l3,1l7.9-5.3l6.2,2.6l9.7,0.9l3.7,4.1l2.7-1.7l2.8,1.9l7.6-3.7h10.2l0.8-3l3.1,0.2l2.2,6.8\n            l3.6,1.7v3.3l3.4,0.1l12.3-6.5l3.1,1.7l1.8,9.4l8.1,5.6l3.2-1.1l3.6,3.9l3.5,0.8l0,0l-1.1-8.4l8.4-5.8l-0.6-5.2l0.2-5l-5.4-4.9\n            v-3.7l-3.9-2.3l1.9-2.5l-8.8-10.7l-7.1-0.6l-0.1-3.5l0.7-2.5l-2.6-4.7l1.6-3.3l-3.1-10.5l2.5-2.4h-3.1l-0.4-9.8h-0.2l-7.7,1.8\n            l-5.4,4.7l-10.9,3L202.425,118.225z M285.425,69.425l-0.2,0.3l-12.2,8.5l-24.8,6.2l-16,8.6l-6.5,14.2l1.9,2.5l9.9,2.2l0.3,0.1l-1.8,0.6v0.1\n            l0.4,9.8h3.1l-2.5,2.4l3.1,10.5l-1.6,3.3l2.6,4.7l-0.7,2.5l0.1,3.5l7.1,0.6l8.8,10.7l-1.9,2.5l3.9,2.3l2.6-2.8l6.7-1.2l6-3.6\n            l3.2,1.8l6.7-1.5l-0.3-3.1l6.8-7.8l-2-6.6l2.6-2.8h4.2l4.5-10.6l4.5-2.9l-4-9.4l1.5-9l-1.5-9.9l3.5-5.2l-3.9-9.5L285.425,69.425z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-06",style:({display:_vm.props.displayDep['FR-acad-06']}),attrs:{"fill":"#6b6b6b","d":"M165.325,171.525l-7.9,5.3l-3-1l-5.4-10.8h-0.2l-12.6,0.1l0.3-6.3l-3.7,0.8l-4.5,4.3l2.7,5.8l0.2,0.4v0.1\n            l-2.6-2.1l-0.1-0.1l-1.9-4.9h-3l-0.2,1.9v0.2l-4.6,0.4l-1.1-3.5l-3.4,1.6l-1.2-2.9l-4,0.3l-12.1,10l-1-4.1l-4.5-2.9l-0.1-3.5\n            l-5.1-8.8l-3.3-0.7l0.7-3.2l-3.4,0.4l-1.8,3.4l1.4-7.5l-5.3,3.6l-0.6-4l-10,4.1l-1.6-2.7l-5,4.1l-0.3,6.9l-3.6,0.6h-0.2l-5.4-3.4\n            l-3.2,0.7l-0.7,3.8l-2.5-3l-1.8,3.6l-0.1-7.3l-16.2,4.8l-1.7-2.9l-11.4,6.2l2.9,1.5l-9.4-0.9l-2.1,2.7l-1.6,10l2,2.6l3-1.2l3,1.3\n            l13.8-4.1l-4.2,2l-0.6,3.8l6.7-1.7l-0.5,3.3l3.3,1.4l-3.5,0.7l7,3l-6.6-1.6l-1.5-2.8l-4.3,1.2l-6.5-1.3l-1.9-2.7l-1.7,4.1l2.8,5.9\n            l1.9-3.1l3.1,0.3l6.1,3.5l1,3.6l-1.8,2.8l-2.8-1.5l-14.1,1.4l-1.7,2.8l10.7,4l4.1,7l-1.1,6.2l8.2,0.7l4.1-4.7v-0.4v-0.1l-0.9-3.2\n            l1.2-0.2l0.4-3.5l1.6,3.2l-2,0.3l-0.3,3.5l3.4,2.2l5.1-1.9l5.3,6.7l3.2-0.7v-3.2l0.4,3.1v0.1l2.9-1.1l-0.7,3.2l3.3,0.7l4.2-1.7\n            l0.2-3.4l0.8,8.6l2.9,1.8l3-1.5v-0.1l-0.5-6.5l0.6,3.4l3.3,0.6l-2.4,2.4l1.7,3l4.1,1.7l3-1.3l-2.7,1.4l0.3,3.1l2.4,3.2l-0.9,4.1\n            l2.4,2.6l-1.4-3.2l3.1-2.7l3.6-0.8l2.8,2l-1.4-6.6l3.4,5.5l1.7-2.9l4.2,0.7l2.3,2.7l-2.2,2.8l-7-0.9l5.3,4.2l13.9-1.1l3.1,1.7\n            l-3.4,0.2l1.8,2.9l17.3-5.1l0.4-6.7l5.9-3.8l14.7-0.5l1.8-3.2l10.1-4.4l7.7,4l0.5-2.4l4-9.7l6.2-2.5l-2.1-19.5l1.7-2.6l0.1-13.6\n            L165.325,171.525z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-26",style:({display:_vm.props.displayDep['FR-acad-26']}),attrs:{"fill":"#6b6b6b","d":"M184.925,179.125l-3.7-4.1l-9.7-0.9l-0.1,13.6l-1.7,2.6l2.1,19.5l-6.2,2.5l-4,9.7l-0.5,2.4l-7.7-4\n            l-10.1,4.4l-1.8,3.2l-14.7,0.5l-5.9,3.8l-0.4,6.7l-17.3,5.1l-5,4.9l2.7,6.9l6.8,1.1l2.3,2.3l5.6-4.1l8-0.7l-7.6,2.9l-0.7,7.4\n            l-3.4,1.5l6.7,2.4l5.4,5.6l-8.8,9.3l0.5,5l11.7,13.7l2.3,7.9l9.1,7.6l5.4,1.2l1.8,4l6.7,1.3l5.3,4.3l0.7-3.1l3.2,0.4l9.9-3.8\n            l-1.5,3.3l7.2-0.3l2.7,2l10.2-5.1l-2.8-2.1l0.3-8.4l-5.4-20.1l-4.7-4.7l-2.5-6.3l13.7-1l4.2-5.3l6.3-0.8l10.9-0.3l2.5,2.6l7.5-6.7\n            v-0.1l6.5-17.2l1.8-10.4l0.9-2.7l6.8,2.9l-0.5-3.7l3.1,0.3l8.6-5v-0.1l0.7-3.8l3.6-1.2l7-11.9l-0.4-6.3l-1.9-2.5l2.3-2.3v-0.1\n            l0.4-2l-2.4-4.3l-3.5-0.8l-3.6-3.9l-3.2,1.1l-8.1-5.6l-1.8-9.4l-3.1-1.7l-12.3,6.5l-3.4-0.1v-3.3l-3.6-1.7l-2.2-6.8l-3.1-0.2\n            l-0.8,3h-10.2l-7.6,3.7l-2.8-1.9L184.925,179.125z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-23",style:({display:_vm.props.displayDep['FR-acad-01']}),attrs:{"fill":"#6b6b6b","d":"M199.525,273.325l-6.3,0.8l-4.2,5.3l-13.7,1l2.5,6.3l4.7,4.7l5.4,20.1l-0.3,8.4l2.8,2.1l-10.2,5.1l-2.7-2\n            l-7.2,0.3l1.5-3.3l-9.9,3.8l-5.1,10.9l3.3,1l4.6,9l-3.1,0.5l1.8,6l-3.3,6.5l-5.3,3.7l1.4,4.7l8.8,5.5l11.7,12.6l2.4,8.4l6.6-0.9\n            l0.7,3.2l7.2,1.9l0.6,6.5l6.9,4.8l10.6,1.1l0,0l2.3-5.5v-0.6l0,0h0.1l2.2-2.1l6.2-0.9l4.5-6.4l0.9-6.5l8.8-6.6l2.4-5.9l6.4-6.9\n            h-0.1l3.9-4.3l3-0.7l4.5-9l2.9-1.2l0.7-3.6l-5.2-4l0.2-9.6l3.3-5.6l6.4-3.1l1.3-3.3l3.8,0.9l1.4-2.7l1,0.3l1.7-3.1l-2.9-1.4\n            l-2.8-6.1h-3.3l-5.2-4.9l0.8-6.5l-10.3-14.7l-0.2-3.5l-6-3.3l1.6,3.2l-11.2,0.5l-3.2-1.6l-1.5-6.8l-3.2,0.3l-0.4-3.6l-6.1-3.6l0,0\n            l-7.5,6.7l-2.5-2.6L199.525,273.325z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-21",style:({display:_vm.props.displayDep['FR-acad-21']}),attrs:{"fill":"#6b6b6b","d":"M245.525,369.925L245.525,369.925l-6.4,6.9l-2.4,5.9l-8.8,6.6l-0.9,6.5l-4.5,6.4l-6.2,0.9l-2.2,2.1h-0.1\n            v0.6l-2.3,5.5l0,0l-10.6-1.1l-6.9-4.8l-0.6-6.5l-7.2-1.9l-0.7-3.2l-6.6,0.9l0.6,3.1l-2.4,3.8l-2.8-8.8l-12-11.8l-1.1-3.2l-3.3,6.5\n            l-6.9,56.3l4.4-8.7l5,5.8l0.3,3.2l-7.2-1l-3.6,7.7l0.1,5.3l-12.4,56.2l-4.2,8l-5.2,7.1l-7.1,5.2l6.5,3l0.6,3.5l2.5-2.3l7.4,1.5\n            l1.6,3.2l-2.1,6.6l-3,2.6l1.5,3.2l4.4,1l0.5-4l3.3-1l0.3,3.7l6.5,3.6l10.9,4.2l7-0.4l3,5.3l8.8,7.2l2.6-1.9l2.6,1.6l6.7-3.4h0.1\n            l1.4-9.7l1.8-3.1l3.8-0.9l-0.5-3.3l9.1-10.4l-0.9-3.5l3-1.5l-0.6-8.2h-3.2l1.6-3.1l-2.7-6.5l-0.1-0.1l-7.4-0.3l-1.7-3l4.7-12.5\n            l-0.5-7.1l10.4-3.1l0.8,3.4l2.8-1.5l0.4-3.3v-0.1l4.2,0.8l1.5-2.8l9.4,0.8l5.6-3.2l9.6,1.1l3-1.5l4.1-5.3l3.1,0.3l-1.5-3.1l5-6.2\n            l-2.8-1.5v-3.3l8.5-2.2l-3-10.3l3.9-3.5l5.1-7.2l6.9-3.9l-0.7-4l5.1-3.6l1.5-6l0.7-5.6l-3.8-7.8l-3.7-0.8l-0.2-3.4l-2.6-1.7l2.3-2\n            l-2-3.3l4-5.2l-3.1-5.3l-6.5-2.4l2.3-3.1l-8-6.3l-10.7,1.3l0.1-5.3L245.525,369.925z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-25",style:({display:_vm.props.displayDep['FR-acad-25']}),attrs:{"fill":"#6b6b6b","d":"M284.425,418.625l-0.7,5.6l-1.5,6l-5.1,3.6l0.7,4l-6.9,3.9l-5.1,7.2l-3.9,3.5l3,10.3l-8.5,2.2v3.3l2.8,1.5\n            l-5,6.2l1.5,3.1l-3.1-0.3l-4.1,5.3l-3,1.5l-9.6-1.1l-5.6,3.2l-9.4-0.8l-1.5,2.8l-4.2-0.8v0.1l-0.4,3.3l-2.8,1.5l-0.8-3.4\n            l-10.4,3.1l0.5,7.1l-4.7,12.5l1.7,3l7.4,0.3l0.1,0.1l2.7,6.5l-1.6,3.1h3.2l0.6,8.2l-3,1.5l0.9,3.5l-9.1,10.4l0.5,3.3l-3.8,0.9\n            l-1.8,3.1l-1.4,9.7v0.1l12.6,11.2l13.7-1.9l2.8,2.3l18.2,0.3l2.5-2.5l-0.4-7.4l2.8-1.4l18.7,5.5l5.4,4.9l7.1-0.5l6.5,8.5l1.2-3.3\n            l3.7-0.2l11.9,5.7l-0.1,0.1l10-3l1.4-2.9l8.3-0.2l-5.4-6.3l-3.7,1.3l-3.8-1.6l-1-6.5l3.7-0.7l1.4-3.3l-2.8-2.8l2.5-2.6l-0.2-3.4\n            l-2.5-6l-9.7-4.3l-2.6-6.2l9.5-11.2l1.5,3l6-2l0.4-0.9l2,2.6l7.1,1l1.3-3.7l3.2-0.5l13.4,1.5l3.8-2.3l1-3.3l-0.8-10.9l6.8,2.4\n            c2.6-1.6,5.1-3.2,7.7-4.8l6.3,0.1l0.2-9.8l7,1.4l3.4-6l4.2-1.1l-0.4-1.7l4.9-5.9l-9.4-4.6l3-1.3l2.3-4.3l-10.5-5l-1.8-2.8l0.7-9.7\n            l-3.3-9.9l-4.8-5.1v-0.1l-2.9-5.7l0.6-3.4l-2.7-1.8l-1.4-4.7l-5.9-4.6l-5.3,4.8l-3.4,10.2l-4.3,5.5l-7.1-1.8l-5.8,3.6l-3.7-5.6\n            l2-6.5l-4.3-6.7l-1.1-6.2l-7.6-0.1l-5.9,3l-7.5-6.8L284.425,418.625z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-24",style:({display:_vm.props.displayDep['FR-acad-24']}),attrs:{"fill":"#6b6b6b","d":"M297.825,545.725l9.7,4.3l2.5,6l0.2,3.4l-2.5,2.6l2.8,2.8l-1.4,3.3l-3.7,0.7l1,6.5l3.8,1.6l3.7-1.3\n            l5.4,6.3l-8.3,0.2l-1.4,2.9l-10,3l-2,5.3l9.8,3l2.6,6.4l3.7,0.2l4.9-4.5l3.6-0.2l10.6,2.4l5.1,4l7.1-0.5v-3.3l9.1-4.8l7-1.2\n            l9.7,2.6l-6.5-8.1l-0.4-19.7l0.5-11.1l6.3-12l3-3.1l6.1-4.5h7.7l7.4-8.2l12.3-8.9l9.4-2.6l0.1,0.1l0,0l3.2,5.4l3.4,0.8h0.2\n            l4.3-6.7l6.5-1.9l-1.1-3.1l4.7-5.4l3.7,1l2.1-11.9l9.5-10.2l-7.1-7.2l-0.2-6.1l-2.9-5.1l-6.8-3.9h-3.1l-2.8,2.6l-0.2-3.5l-7.9,4.9\n            l-8.7-5.6l-7.9-16l-2.3-10.2l-10.1-8.6l-3.2-0.3l-0.2,3.2l-7.2-1.4l-1.6-6.4l-2.6-1.9l-8.7,7.5l-2.1-2.5l-7.7,17.8v0.1l4.8,5.1\n            l3.3,9.9l-0.7,9.7l1.8,2.8l10.5,5l-2.3,4.3l-3,1.3l9.4,4.6l-4.9,5.9l0.4,1.7l-4.2,1.1l-3.4,6l-7-1.4l-0.2,9.8l-6.3-0.1\n            c-2.6,1.6-5.1,3.2-7.7,4.8l-6.8-2.4l0.8,10.9l-1,3.3l-3.8,2.3l-13.4-1.5l-3.2,0.5l-1.3,3.7l-7.1-1l-2-2.6l-0.4,0.9l-6,2l-1.5-3\n            l-9.5,11.2L297.825,545.725z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-27",style:({display:_vm.props.displayDep['FR-acad-27']}),attrs:{"fill":"#6b6b6b","d":"M553.625,444.225l1.5-6.7l0.3-0.4l2-2.3l4,0.1l-4.3-10.5l-6.8-0.3l-5.8-3.8l-0.9-7.4l-3.1-0.1l-2.6-5.8\n            l0,0l-6.9,1.3l-2.2,2.3l-8.7-4l-1.8,4.3l2,4.7l4.9,3.5l-0.4,6.5l-3.5-1.2l-14.1,4.3l-1.1,3.2l-6.1,1.3l-1.1,3.5l-0.4,3.8l-6.4,0.4\n            l-2.8,7.6l3.3,2.4l-2.8,2.9l-4.6-1.6l-3.7,5.7l1.7,3l11.2,6.4l-0.2,5.2l-1.1,0.3l-2.6,0.1l-4.8,5.3l-5.8-2.5l-0.2-3.2l-10.5-1\n            l-0.5-6.5l-2.9,2.5l-2.7-1.6l-9.7,4.4l-4-4.8l-6.5-0.7l0.1,4.1l2.9,5.1l0.2,6.1l7.1,7.2l-9.5,10.2l-2.1,11.9l-3.7-1l-4.7,5.4\n            l1.1,3.1l-6.5,1.9l-4.3,6.7l16.1,0.5l2.2,2.8l-0.6,3.1l4.2,0.9l10.9-1.1l-2.5-2.7l1.4-2.8l3.1,0.3l5.1,6.1l14.9-1.7l4.4,9.9\n            l10.9,2.6l3.1-0.9l0.2,0.2l0.4-3.8l2.9-2.2l-2.6-7l1.3-3l3.1-0.5l-6.2-10.2l2.2-6l3.7-0.9l-2.2-4.3v-0.1l0.2-0.4l5.9,0.3l3.1-2\n            l4.4,5.4l11.4-9.5l6.6,4l1.4-3.9l10.9-0.5l3.5-1.6l-1.5-2.9l5.9-4.4l5.8,0.8l-7.8-8.7l-4.8-9.5l2.2-6.1l6.9-7.7l-0.2-0.2l2.1-5.5\n            l-4.4-6.1L553.625,444.225z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-28",style:({display:_vm.props.displayDep['FR-acad-28']}),attrs:{"fill":"#6b6b6b","d":"M567.625,467.725l-7.1-1.4l-6.9-8.3l-6.9,7.7l-2.2,6.1l4.8,9.5l7.8,8.7l-5.8-0.8l-5.9,4.4l1.5,2.9\n            l-3.5,1.6l-10.9,0.5l-1.4,3.9l-6.6-4l-11.4,9.5l-4.4-5.4l-3.1,2l-5.9-0.3l-0.2,0.5l2.2,4.3l-3.7,0.9l-2.2,6l6.2,10.2l-3.1,0.5\n            l-1.3,3l2.6,7l-2.9,2.2l-0.4,3.8l6.9,4l-0.4,3.1l10-2.9l6.7,1.8l1.3,3.2l2.2-6.2l8.4,1.4l0.1-3.2l6.3-1.4l3-2.8l4.2,0.4l2.5-6.1\n            l-3.9-1.8l6.9-9.2l6-0.8l2.2-3.7l0.1-0.2l2.6-4.7l6.5-3l1.2-4.7l16.2-8.8l0.5-8.2l7-9.6l2.3-4.9l-4.5-6l-13.4,4.8L567.625,467.725\n            z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}}),_c('path',{staticClass:"FR-acad-08",style:({display:_vm.props.displayDep['FR-acad-08']}),attrs:{"fill":"#7E6078","d":"M561.625,556.825l0.5,19.5l-9.6-1.5l-4,5.5l-12,4.8l-1,3.2l-3.1,0.3l-2.5,3.2l0.4,3.8l-4.4,5.6l-0.6,3.3\n            l2.9-1.1l4.3,4.7l-5.9,4.2l1.2,6.2l8.8,4.9l-2.9,1.9l-3.3,9.4l8.1-2.9l1,6.5l-2.9,7.9l10.5,2.1l-5.4,3.7l-0.2,3.3l5.4,5l12.5,3.8\n            l1.4,3.1l3.5,1.4l6.5-16.5l-3.7-0.4l3-2.2l2.3-6.1l-0.8-16.6l6-13.4l-4.2-29.2l-4.5-6.4l0.6-11.9l-2-10.2l-3.2-1.2\n            L561.625,556.825z"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}})])])
}
var FranceAcadvue_type_template_id_4c714fc1_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/FranceAcad.vue?vue&type=script&lang=js



/* harmony default export */ var FranceAcadvue_type_script_lang_js = ({
  mixins: [base],
  props: {
    props: Object
  }
});

;// CONCATENATED MODULE: ./src/components/maps/FranceAcad.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_FranceAcadvue_type_script_lang_js = (FranceAcadvue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/FranceAcad.vue





/* normalize component */
;
var FranceAcad_component = normalizeComponent(
  maps_FranceAcadvue_type_script_lang_js,
  FranceAcadvue_type_template_id_4c714fc1_render,
  FranceAcadvue_type_template_id_4c714fc1_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var FranceAcad = (FranceAcad_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Guadeloupe.vue?vue&type=template&id=451dcb34
var Guadeloupevue_type_template_id_451dcb34_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"width":"57px","height":"50px","viewBox":"0 0 57 50","version":"1.1","xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{staticClass:"FR-DOM-971",attrs:{"fill":"#EEEEEE","fill-rule":"nonzero","stroke":_vm.colorStroke,"stroke-width":"0.2%"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}},[_c('path',{staticClass:"FR-DOM-971",attrs:{"d":"M22.8086899,0 L22.6179806,0.2310345 L20.5146709,1.3765517 L19.8613024,1.8565517 L19.584533,2.322069 L18.5160103,3.6082759 L17.9266705,4.5331034 L17.9569637,5.2331034 L18.2750419,5.8689655 L18.654395,8.4062069 L19.1969181,8.5275862 L19.7614726,8.7558621 L20.2351476,9.0537931 L20.5146709,9.3717241 L20.583519,9.8482759 L20.5642415,10.5103448 L20.4589039,11.1055172 L20.2819643,11.3565517 L19.0254863,11.66 L18.3776256,12.4489655 L17.2595323,16.1606897 L17.2595323,16.1662069 L17.2540245,16.1724138 L16.9965326,17.1889655 L16.9717472,18.1131034 L17.2595323,18.9910345 L17.9569637,19.8627586 L18.8705781,20.602069 L20.0272263,21.2896552 L21.3670105,21.8151724 L22.8334752,22.0662069 L24.0376286,21.8724138 L26.3323361,20.8503448 L38.3215457,18.0958621 L39.3818066,18.3848276 L39.8995444,18.6331034 L41.8087024,19.1006897 L42.3787647,19.1124138 L43.1429787,18.4710345 L42.7608717,18.0668966 L41.4548231,17.7027586 L37.7680069,15.1096552 L37.0595599,14.7510345 L36.6581754,14.48 L35.1827604,13.2144828 L34.5300804,12.8131034 L33.7493428,12.6827586 L33.0574193,12.7089655 L32.3985429,12.6565517 L31.7011116,12.2841379 L31.230879,12.8131034 L30.2484165,12.2751724 L29.393323,11.6689655 L28.6511404,10.9062069 L27.9812483,9.9034483 L27.4586911,8.7910345 L27.3561075,8.0882759 L27.5220314,6.2241379 L27.2232306,4.3510345 L26.4204617,2.8855172 L25.2362742,1.6972414 L23.0276269,0.0924138 L22.8086899,0 Z M16.7445485,16.1606897 L16.3156248,15.7248276 L15.4605313,15.8634483 L14.8016549,16.4034483 L14.4725609,17.1717241 L13.758606,16.437931 L13.542423,16.1606897 L13.2518839,16.397931 L13.2436222,16.4696552 L13.1410385,16.3917241 L12.576484,16.1606897 L13.542423,15.7248276 L12.3802669,14.2944828 L10.0470045,13.0524138 L7.5395564,12.1744828 L5.8376311,11.842069 L5.1567233,11.5213793 L4.7105876,10.9027586 L4.0799389,10.4986207 L2.8179531,10.8282759 L1.8382445,11.6689655 L0.6974313,13.6937931 L0.0337356,14.2689655 L0.2712615,15.202069 L0,17.4144828 L0.0337356,18.6268966 L0.4041384,19.3896552 L0.9411536,20.1517241 L1.2509701,21.0096552 L0.9218762,22.0662069 L1.9318779,24.6096552 L1.9876449,30.202069 L2.8179531,32.7151724 L3.404539,33.1048276 L3.6868162,33.7710345 L3.9305385,34.5848276 L4.4255564,35.4241379 L5.2228175,36.142069 L5.9202489,36.6006897 L6.5178504,37.1317241 L6.9997872,38.0834483 L6.4930651,38.5675862 L6.8916957,39.6027586 L6.9997872,40.017931 L10.5034674,38.5158621 L13.6284831,36.6468966 L15.7703477,34.0393103 L16.3321483,30.2944828 L15.8674236,26.422069 L15.0013144,23.94 L14.8546679,20.6482759 L15.3166387,19.9206897 L16.5455774,19.6406897 L16.9965326,19.5048276 L16.4898105,18.0986207 L16.9139148,16.5131034 L16.7445485,16.1606897 Z M14.3809929,11.5793103 L15.0453772,11.3972414 L14.7520842,11.1889655 L14.5331472,11.0648276 L14.2260847,11.1772414 L13.8997446,11.6165517 L14.0656686,11.9117241 L14.3809929,11.5793103 Z M55,11.842069 L53.2560774,11.302069 L51.6064767,11.977931 L48.9275968,14.2689655 L48.0360138,14.7510345 L49.6029968,14.9765517 L51.5541522,14.1475862 L55,11.842069 Z M47.0728288,23.5703448 L47.0700749,23.7517241 L47.1974439,23.6137931 L47.0728288,23.5703448 Z M46.3285807,24.337931 L46.8125829,24.2572414 L47.0397817,23.9193103 L46.6686904,23.8648276 L45.9994868,23.8965517 L45.609118,23.8648276 L46.1378715,24.3324138 L46.3285807,24.337931 Z M41.0727161,41.2289655 L40.8427634,40.3089655 L40.331222,39.7441379 L39.821746,39.3262069 L39.0327467,37.2986207 L37.6798813,36.1337931 L36.019265,35.7586207 L34.5300804,36.5924138 L33.3011416,37.9965517 L32.8219588,38.757931 L32.6257417,39.7786207 L31.9723731,40.7186207 L31.684588,41.237931 L31.9090329,41.4710345 L32.1555091,41.6703448 L32.3875272,42.1489655 L32.5568936,42.7165517 L32.6257417,43.1834483 L33.5166362,45.0255172 L35.5703753,45.36 L37.8953759,44.7889655 L39.5945472,43.9303448 L40.3752848,43.3048276 L40.8179781,42.7510345 L41.0258994,42.1137931 L41.0727161,41.2289655 Z M15.7373007,45.0344828 L15.634717,44.8441379 L15.2333325,44.68 L14.8209323,44.8613793 L14.469807,45.5793103 L14.1042235,45.797931 L13.7503442,46.1324138 L13.957577,46.4496552 L14.668778,46.2075862 L15.4605313,45.6537931 L15.8371304,45.3137931 L15.7675938,45.1468966 L15.7373007,45.0344828 Z M14.0932078,44.8841379 L13.7283128,44.8296552 L13.8549934,45.0627586 L14.2178229,45.117931 L14.0932078,44.8841379 Z M11.7792229,45.5386207 L11.1045114,45.8986207 L10.8497734,46.8151724 L11.5830058,47.2565517 L12.1778534,46.8813793 L12.3637433,46.3310345 L12.0229452,45.7862069 L11.7792229,45.5386207 Z M14.41404,48 L14.3809929,47.7206897 L14.0684225,47.4606897 L14.0987157,47.7406897 L14.41404,48 Z M12.9338057,47.6944828 L13.050159,47.8213793 L13.2188368,47.8241379 L13.1162532,47.6855172 L12.9338057,47.6944828 Z","id":"GP-GP"}})])])
}
var Guadeloupevue_type_template_id_451dcb34_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Guadeloupe.vue?vue&type=script&lang=js



/* harmony default export */ var Guadeloupevue_type_script_lang_js = ({
  mixins: [base],
  props: {
    colorStroke: String
  }
});

;// CONCATENATED MODULE: ./src/components/maps/Guadeloupe.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_Guadeloupevue_type_script_lang_js = (Guadeloupevue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/Guadeloupe.vue





/* normalize component */
;
var Guadeloupe_component = normalizeComponent(
  maps_Guadeloupevue_type_script_lang_js,
  Guadeloupevue_type_template_id_451dcb34_render,
  Guadeloupevue_type_template_id_451dcb34_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Guadeloupe = (Guadeloupe_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Martinique.vue?vue&type=template&id=7ba429fe
var Martiniquevue_type_template_id_7ba429fe_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"width":"43px","height":"50px","viewBox":"0 0 43 50","version":"1.1","xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{staticClass:"FR-DOM-972",attrs:{"fill":"#EEEEEE","fill-rule":"nonzero","stroke":_vm.colorStroke,"stroke-width":"0.2%"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}},[_c('polygon',{staticClass:"FR-DOM-972",attrs:{"id":"MQ","points":"35.1029857 48 36.490277 47.8708464 37.5907726 47.3837975 38.6076407 46.0103089 39.7999725 43.0299219 41 42.4889292 41 41.7975168 40.3899817 40.5531818 40.0790725 38.9416151 39.5444728 37.5344118 38.2608179 36.9016112 38.5835273 36.4311602 38.7471907 36.1064609 38.9903772 35.8274062 39.5926997 35.5063377 38.7872087 34.4030862 38.4521861 33.1784614 38.5081087 31.8791455 38.8708361 30.5549324 37.5907726 31.3085875 37.6785044 30.4631245 37.614886 29.5300029 37.2798634 28.7638992 36.5862177 28.455798 35.6452812 28.1891918 35.2825537 27.5849191 35.1388993 26.8852076 34.8515905 26.3561448 32.8701854 25.2103608 32.1206172 24.4146919 31.5064945 22.8606996 32.0564857 22.6397381 32.387917 22.4234447 32.7742448 22.2398288 33.5161173 22.1651376 30.2346397 20.6982851 29.3932352 21.8606672 28.5482394 21.5650144 28.3727757 20.6858365 29.5645944 20.0608163 28.8350352 19.3066425 31.5064945 17.8351217 30.9801036 17.2479658 30.3659809 16.8724349 29.6564307 16.8226408 28.8350352 17.205952 28.6277623 14.9221642 28.9068624 12.9713749 29.6564307 12.4334943 30.8364492 14.40555 32.9102035 12.2462476 32.6747128 11.5875126 32.9620217 11.1829351 33.5638311 10.7871754 34.249781 10.2077998 31.9092399 10.2285473 29.6164126 10.8369696 27.6267988 11.8375207 26.215394 13.0087205 24.4812799 9.4448082 23.3407663 9.1195902 22.8461846 8.3565987 22.6029982 7.4520051 22.2007658 6.7097611 20.5974798 5.6547476 16.7542108 3.9161021 12.4322647 1.0970273 9.1066647 0 5.6697325 0.0544623 1.7864454 2.3019418 0.9173361 3.1152462 0.259091 4.2413201 0 5.6298506 0.259091 7.0847732 0.9532497 8.3441502 3.7519458 11.3292054 4.4938184 12.3463546 5.067923 13.5217039 5.2952048 14.7556651 5.1115324 15.4470775 4.7806142 16.1099621 4.6251595 16.7894447 4.9919914 17.5228709 5.4147459 17.9979901 5.7415597 18.4897073 5.9529369 19.0395176 6.0247641 19.6858041 6.7861326 21.5105521 8.5484646 23.2357117 12.0490152 25.6564334 12.4081513 26.0895387 13.0222739 27.3141635 13.4445154 27.7555678 14.0027154 27.8888709 16.0605651 27.7555678 17.105138 27.8390767 18.1538154 27.8058806 19.3456341 27.5641716 20.808857 27.0558563 21.0202343 28.7887963 21.7379933 30.2214154 22.4834572 31.296139 22.8143755 31.9460563 22.0966164 33.4284695 20.6852116 33.9616818 19.1624747 33.4953804 18.1297019 31.9460563 17.2965062 32.7583233 14.7286833 34.0363731 12.0490152 36.9016112 12.9381335 37.4177067 13.3690968 37.7507051 13.4927422 38.1672124 13.4445154 38.9291665 15.6060015 42.0060298 16.4268839 42.4889292 17.8783067 42.2638182 20.3065796 41.2565241 21.8375253 41.0277823 27.5554846 41.7975168 30.2587532 41.3229163 30.9082764 41.4520699 30.8364492 42.4889292 32.407413 42.6009661 33.9624722 41.6144196 35.3061541 41.0568289 36.251195 42.4889292 36.1557675 44.0496645 35.2107265 44.5865076 34.1061266 44.9449217 33.5161173 45.9854119 33.9388718 47.4709372"}})])])
}
var Martiniquevue_type_template_id_7ba429fe_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Martinique.vue?vue&type=script&lang=js



/* harmony default export */ var Martiniquevue_type_script_lang_js = ({
  mixins: [base],
  props: {
    colorStroke: String
  }
});

;// CONCATENATED MODULE: ./src/components/maps/Martinique.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_Martiniquevue_type_script_lang_js = (Martiniquevue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/Martinique.vue





/* normalize component */
;
var Martinique_component = normalizeComponent(
  maps_Martiniquevue_type_script_lang_js,
  Martiniquevue_type_template_id_7ba429fe_render,
  Martiniquevue_type_template_id_7ba429fe_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Martinique = (Martinique_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Guyane.vue?vue&type=template&id=408c2ea0
var Guyanevue_type_template_id_408c2ea0_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"width":"40px","height":"50px","viewBox":"0 0 40 50","version":"1.1","xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{staticClass:"FR-DOM-973",attrs:{"fill":"#EEEEEE","fill-rule":"nonzero","stroke":_vm.colorStroke,"stroke-width":"0.2%"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}},[_c('polygon',{staticClass:"FR-DOM-973",attrs:{"id":"GU","points":"7.8092484 0 8.0718954 0.0593522 8.3736601 0.1882678 8.9275163 0.2067755 9.0964052 0.165931 9.2510131 0.2137957 9.3876144 0.221454 9.5925163 0.2808063 9.9234641 0.4582248 9.9992157 0.5245971 10.150098 0.5909695 10.2581373 0.6758496 10.5207843 0.8194437 10.6573856 0.9266607 10.9088562 1.0447269 11.1280392 1.1921502 11.4266993 1.3140456 11.5272876 1.3287242 11.5993137 1.3842472 11.721634 1.4027549 12.2283007 1.6573951 12.3003268 1.6612243 12.5486928 1.7569537 12.6312745 1.8048184 12.7033007 1.87502 12.7753268 1.8896985 12.8579085 1.9669202 12.883366 1.9669202 12.9442157 2.0224432 12.9945098 2.0301016 13.0125163 2.0709461 13.0448039 2.0632878 13.0845425 2.1073233 13.2136928 2.184545 13.3863072 2.2694251 13.4800654 2.2841036 13.9463725 2.4647131 13.9457516 2.5795884 13.9233987 2.6644684 13.939 2.6714568 13.9587908 2.5974579 13.9594118 2.4832208 14.1295425 2.5055576 14.2233007 2.4908791 14.359902 2.5017285 14.5505229 2.4762006 14.6653922 2.4800298 14.6871242 2.4576929 14.7914379 2.4653513 15.0106209 2.542573 15.046634 2.5317237 15.1546732 2.5649099 15.1975163 2.6019252 15.3235621 2.6389406 15.6507843 2.6944637 15.8339542 2.7863639 16.0643137 2.8272084 16.3337908 2.7423284 16.3443464 2.6721268 16.2474837 2.6166037 16.2760458 2.5502314 16.3480719 2.5247035 16.3517974 2.5061958 16.4095425 2.5023667 16.435 2.4653513 16.5287582 2.4800298 16.5244118 2.4308887 16.5318627 2.4493964 16.7690523 2.5419348 16.9522222 2.6491517 17.0310784 2.6676594 17.2502614 2.793384 17.3831373 2.8412487 17.4154248 2.8374196 17.6060458 2.933149 17.7861111 2.9739935 17.7898366 3.0033505 17.8475817 3.0365367 17.8941503 3.0218582 17.9587255 3.0658938 18.0413072 3.0767431 18.0593137 3.1099293 18.0878758 3.1022709 18.1164379 3.1316279 18.1561765 3.1316279 18.2027451 3.1724725 18.2604902 3.1724725 18.2679412 3.2056587 18.3002288 3.2094879 18.3287908 3.3128756 18.393366 3.3422326 18.415098 3.3977557 18.4691176 3.4232835 18.5411438 3.4271127 18.5771569 3.4896559 18.6529085 3.5636867 18.8435294 3.6485667 18.9081046 3.7149391 18.9658497 3.7442961 18.9981373 3.740467 19.2607843 3.8655534 19.5805556 3.8247088 19.7171569 3.8393873 19.9506209 3.9759613 20.2095425 4.0385045 20.4721895 4.1565708 20.5156536 4.2082646 20.8503268 4.385045 21.0012092 4.4884327 21.0372222 4.5324682 21.0769608 4.5286391 21.1452614 4.5911823 21.2998693 4.6722332 21.3216013 4.668404 21.3178758 4.697761 21.3427124 4.6939318 21.3930065 4.727118 21.5116013 4.834335 21.601634 4.8158273 21.7314052 4.9740999 21.7494118 4.9632506 21.7133987 4.9045365 21.7351307 4.863692 21.7854248 4.834335 21.7997059 4.863692 21.8785621 4.9045365 21.9611438 4.9817582 21.9934314 5.0372813 21.9685948 5.0959953 21.9934314 5.0736585 22.0437255 5.088337 22.1443137 5.1655587 22.2486275 5.2906451 22.259183 5.3385098 22.237451 5.3793544 22.2014379 5.4125406 22.1368627 5.3978621 22.1585948 5.4233899 22.237451 5.4418976 22.2554575 5.4642344 22.2877451 5.4418976 22.3274837 5.4457268 22.3672222 5.4935915 22.485817 5.5159283 22.6801634 5.6633516 22.6876144 5.7444025 22.7056209 5.7367441 22.7093464 5.6333564 22.7993791 5.6595224 22.8136601 5.7182365 22.8782353 5.7846089 22.8714052 5.8286444 22.8856863 5.8471521 22.9291503 5.8363027 23.004902 5.9467106 23.0154575 6.0386109 23.0477451 6.1011541 23.0837582 6.1381695 23.1272222 6.1158326 23.1737908 6.1898633 23.216634 6.2045418 23.2632026 6.3481359 23.2594771 6.4662022 23.3203268 6.5325746 23.3923529 6.5364038 23.435817 6.5619316 23.5618627 6.764878 23.6481699 6.8057225 23.6984641 6.8720949 23.7704902 6.9052811 23.9070915 7.0233473 24.0331373 7.0750412 24.1119935 7.1599213 24.1765686 7.178429 24.2268627 7.233952 24.3206209 7.2262937 24.4032026 7.4362602 24.6403922 7.7017497 24.6981373 7.7425943 24.7378758 7.7387651 24.7956209 7.790459 24.7887908 7.819816 24.7565033 7.7974791 24.7167647 7.8083285 24.7062092 7.8415147 24.655915 7.8855502 24.6701961 7.9078871 24.7347712 7.9334149 24.8285294 8.0189332 24.9185621 8.0514812 24.9725817 8.0955167 25.0160458 8.0993459 25.1781046 8.2244323 25.4115686 8.5269372 25.4581373 8.5492741 25.5009804 8.6194756 25.6375817 8.715205 25.7096078 8.8039143 25.8784967 8.8741158 25.9393464 8.877945 26.0653922 8.9404882 26.1156863 8.9921821 26.1299673 9.043876 26.22 9.1357762 26.3317647 9.1727916 26.4180719 9.2576717 26.4435294 9.2506515 26.4615359 9.291496 26.6086928 9.3833963 26.6372549 9.3502101 26.7378431 9.4567888 26.7303922 9.4752965 26.7123856 9.4714673 26.7198366 9.4938042 26.7775817 9.4976334 26.8030392 9.5308196 26.8278758 9.5346487 26.8278758 9.5786843 26.9247386 9.626549 26.9501961 9.6520768 26.9719281 9.7261075 27.1016993 9.8148168 27.155719 9.8888475 27.2097386 9.9111844 27.2277451 9.8856566 27.2420261 9.9150136 27.2817647 9.9335213 27.2854902 9.9890443 27.404085 9.981386 27.3898039 10.066266 27.4655556 10.1103016 27.5301307 10.0917939 27.5766993 10.1836941 27.5586928 10.2424081 27.6052614 10.2864437 27.6661111 10.3087805 27.6766667 10.3496251 27.6946732 10.3387757 27.7232353 10.3643036 27.8238235 10.515556 27.8815686 10.6629793 27.9498693 10.740201 27.9535948 10.7695581 27.9716013 10.7695581 28.1659477 10.9571877 28.2988235 11.0197309 28.4968954 11.0235601 28.5471895 10.964846 28.5726471 10.879966 28.5689216 10.8097644 28.5260784 10.7657289 28.5192484 10.7325427 28.5490523 10.7178642 28.5521569 10.6699995 28.6564706 10.6699995 28.6819281 10.6591501 28.6782026 10.6297931 28.6962092 10.65213 28.7216667 10.6559592 28.7471242 10.6336223 28.7688562 10.6559592 28.8986275 10.5602298 29.0103922 10.5640589 29.0283987 10.6380897 29.1041503 10.7006329 29.1618954 10.7153114 29.1979085 10.6821252 29.2196405 10.6897836 29.2482026 10.7306281 29.2264706 10.7784928 29.2482026 10.8301867 29.3630719 10.9003882 29.4748366 11.0184545 29.5716993 11.0809977 29.5716993 11.1288624 29.5214052 11.1141839 29.5033987 11.1397118 29.5071242 11.2539488 29.625719 11.3605276 29.7194771 11.4083923 29.7517647 11.5334787 30.0429739 11.8174759 30.2298693 11.846833 30.2584314 11.8908685 30.3410131 11.9240547 30.4018627 12.0382918 30.4558824 12.0861565 30.4664379 12.1965644 30.5061765 12.2042227 30.5496405 12.2597458 30.5999346 12.2820827 30.5999346 12.2265596 30.6142157 12.2265596 30.6645098 12.3037813 30.750817 12.2999521 30.772549 12.2776153 30.7868301 12.3146307 30.8153922 12.322289 30.8371242 12.3554752 30.9054248 12.381003 30.955719 12.4875818 30.955719 12.5724619 30.9811765 12.6241557 31.0246405 12.6720204 31.1432353 12.7307345 31.1792484 12.8449716 31.1866993 12.7932777 31.1581373 12.745413 31.1513072 12.6866989 31.1010131 12.6464926 31.086732 12.5839494 31.2196078 12.6541509 31.2736275 12.7281817 31.3276471 12.7428602 31.3313725 12.8054034 31.3065359 12.8277403 31.3462745 12.8717758 31.3605556 12.8679466 31.3605556 12.8347604 31.3928431 12.8200819 31.4040196 12.8456098 31.4723203 12.8787959 31.4828758 12.9119821 31.532549 12.9528267 31.5077124 12.9528267 31.5294444 13.0115407 31.5797386 13.0045206 31.5940196 13.0600436 31.6585948 13.1334362 31.6871569 13.1442855 31.6585948 13.0779131 31.6188562 13.0485561 31.6405882 13.0262192 31.8169281 13.1659842 31.9355229 13.2246982 31.9572549 13.2649045 31.985817 13.2610754 32.0503922 13.3236186 32.1472549 13.3382971 32.1578105 13.3714833 32.2515686 13.4340265 32.2913072 13.4340265 32.4279085 13.507419 32.4421895 13.5444344 32.4601961 13.5444344 32.4676471 13.5220975 32.5216667 13.5329469 32.5253922 13.5661331 32.5471242 13.5737914 32.5222876 13.5884699 32.5620261 13.6108068 32.5980392 13.6880285 32.5694771 13.7103654 32.5371895 13.6848375 32.5694771 13.7365314 32.6986275 13.777376 32.7632026 13.862256 32.7774837 13.832899 32.7954902 13.8437483 32.7992157 13.9062916 32.8315033 13.9209701 32.8420588 13.9726639 32.8637908 13.9873425 32.8817974 14.1162581 32.914085 14.1494442 32.914085 14.1749721 32.9681046 14.1788013 32.914085 14.2056055 32.9196732 14.2183694 33.0662092 14.3255864 33.3139542 14.4902409 33.7498366 14.5176834 34.0584314 14.3185662 34.1230065 14.2151784 34.2633333 14.2043291 34.2707843 14.2298569 34.4073856 14.2407063 34.4831373 14.2662341 34.5588889 14.3217572 34.594902 14.3217572 34.6271895 14.347285 34.6234641 14.3728129 34.7203268 14.3951497 34.7383333 14.4430144 34.7960784 14.4538638 34.9146732 14.542573 34.9755229 14.5572515 35.0152614 14.6127746 35.1090196 14.6753178 35.1630392 14.7601979 35.2567974 14.8118917 35.2319608 14.8597564 35.3145425 14.9222996 35.35 14.9214568 35.3567647 14.929123 35.366 14.9264568 35.4076797 14.9516567 35.4008497 14.9925012 35.5480065 15.1916184 35.5585621 15.1731107 35.5337255 15.1067383 35.5660131 15.1399245 35.6485948 15.3422326 35.6343137 15.353082 35.5983007 15.3128756 35.6088562 15.3683987 35.6591503 15.4124342 35.6666013 15.4826358 35.7131699 15.4973143 35.734902 15.5343296 35.7026144 15.6855821 35.764085 15.8330054 35.800098 16.0870074 35.8181046 16.0978567 35.8181046 16.1680583 35.8578431 16.2599585 35.8541176 16.3263309 35.8721242 16.3410094 35.8541176 16.3927033 35.8758497 16.5918205 35.8541176 16.691379 35.9006863 16.7762591 35.8826797 16.8349732 35.9112418 16.9198532 36.0118301 17.0711057 36.0801307 17.1298197 36.1161438 17.2000213 36.2061765 17.247886 36.1738889 17.2995799 36.1813399 17.3621231 36.1204902 17.3953093 36.1384967 17.4687018 36.1099346 17.5165665 36.1242157 17.6161251 36.1850654 17.6052758 36.1745098 17.6422911 36.2179739 17.6531405 36.3185621 17.8005638 36.3545752 17.8114131 36.5234641 17.951178 36.5954902 17.9658565 36.6457843 18.0322289 36.757549 18.009892 36.7972876 17.9288412 36.8581373 17.9473488 36.8798693 17.9364995 36.9121569 17.9658565 36.8873203 18.0654151 36.8978758 18.1132798 36.8333007 18.0947721 36.7252614 18.1649737 36.6892484 18.2128384 36.6532353 18.3455832 36.6246732 18.3564325 36.6426797 18.3857895 36.5923856 18.3896187 36.545817 18.5255544 36.5563725 18.6799979 36.6103922 18.879115 36.7004248 19.1222677 36.750719 19.1924693 36.7830065 19.2843695 36.8152941 19.3398926 36.8618627 19.3692496 36.9736275 19.5568792 37.0239216 19.6124023 37.0742157 19.6162315 37.0847712 19.6455885 37.0599346 19.660267 37.0599346 19.718981 37.1033987 19.8038611 37.1934314 19.884912 37.261732 19.8995905 37.3045752 19.9474552 37.3225817 20.0393554 37.3511438 20.0763708 38 20.0387172 37.9143137 20.5984152 37.890719 20.6226666 37.905 20.8619901 37.8478758 21.0566399 37.6448366 21.4491305 37.5697059 21.6437803 37.5026471 22.0700952 37.3287908 22.4989629 37.2306863 22.6266021 37.1294771 22.6808488 36.8444771 22.7555177 36.6215686 22.8614583 36.4048693 23.0190927 36.0956536 23.2035314 35.8628105 23.5500718 35.7665686 23.7421688 35.7280719 23.8423656 35.7243464 24.0804127 35.6516993 24.4186566 35.5467647 24.6362815 35.2729412 24.8877307 35.1537255 25.0638728 35.010915 25.2061905 34.9637255 25.22406 34.5172876 25.7250439 34.2794771 25.8399192 34.0503595 26.024996 33.9373529 26.0741371 33.9193464 26.1692283 33.9982026 26.5323619 33.9826797 26.6868053 33.9559804 26.7327554 33.7628758 26.8201883 33.5405882 26.8463543 33.4368954 26.9069829 33.2984314 27.0480243 33.1208497 27.5266713 33.0506863 27.9236292 32.9861111 28.1693347 32.7315359 28.4680104 32.5496078 28.632665 32.1739542 29.4304101 32.1727124 29.4916769 32.1329739 29.5440089 32.0448039 29.59315 31.9752614 29.7188747 31.8386601 30.140084 31.7747059 30.2390044 31.5561438 30.4368452 31.3965686 30.667234 30.8687908 31.5702813 30.7762745 31.8013083 30.5328758 32.0897729 30.4813399 32.2174121 30.4900327 32.3488805 30.4124183 32.500133 30.3379085 32.5696963 29.9479739 33.0981227 29.8560784 33.1402436 29.8088889 33.1402436 29.6611111 33.0483434 29.610817 33.0572781 29.2811111 33.2844759 29.0544771 33.7318513 29.0681373 34.0241451 28.9110458 34.0235069 28.8160458 34.0535021 28.6856536 34.1415732 28.5962418 34.3566452 28.4956536 34.4842844 28.5794771 34.5570388 28.5478105 34.590225 28.4546732 34.5985215 28.4149346 34.6234112 28.3987908 34.7274371 28.5161438 34.7574323 28.5453268 34.8206137 28.4770261 34.9693134 28.4844771 35.0458969 28.5192484 35.1141839 28.6688889 35.2399085 28.6639216 35.420518 28.6260458 35.5704941 28.4105882 35.9157581 28.3652614 36.0197841 28.2764706 36.1340212 28.2547386 36.2731479 28.1454575 36.4626921 28.0206536 36.9738872 27.813268 37.1423709 27.7896732 37.3204276 27.8138889 37.4627453 27.8430719 37.4793384 27.885915 37.4601925 27.9368301 37.4659363 27.9716013 37.5565601 27.9182026 37.647184 27.7822222 37.7626975 27.7393791 37.7792905 27.5953268 37.7767378 27.5524837 37.8341754 27.4723856 37.8450247 27.3624837 37.9426687 27.1172222 38.389406 26.6726471 39.3033027 26.7198366 39.4264745 26.7030719 39.4858267 26.6235948 39.5962347 26.658366 39.6785619 26.5254902 39.9140563 26.3975817 40.0870074 26.3038235 40.3435622 26.1560458 40.5918205 26.0337255 40.9555922 25.9883987 41.0021805 25.8337908 40.9913312 25.7536928 41.0296229 25.7164379 41.0845078 25.6953268 41.1751316 25.7089869 41.2268255 25.847451 41.3308515 25.8499346 41.3940329 25.7915686 41.4655108 25.7834967 41.5095464 25.8263399 41.5810243 25.9169935 41.6276126 26.0051634 41.7207892 26.0349673 41.8439611 25.9915033 41.960751 25.9014706 42.0947721 25.8722876 42.2211349 25.752451 42.1777376 25.6748366 42.3021858 25.6605556 42.4853481 25.679183 42.5300218 25.730098 42.5344892 25.7518301 42.6193693 25.7189216 42.722757 25.3892157 43.0246237 25.2693791 43.1560921 25.2134967 43.2741584 25.1203595 43.3756316 24.9794118 43.3596767 24.8496405 43.4503005 24.8415686 43.4783811 24.8924837 43.5224166 24.9402941 43.5307132 24.948366 43.5938946 24.9185621 43.6998351 24.7428431 43.7464235 24.7111765 43.7847152 24.6472222 43.7572728 24.6155556 43.7655693 24.5087582 43.9455406 24.5286275 44.0125512 24.4038235 44.1963517 24.3721569 44.295272 24.3802288 44.4075945 24.3324183 44.5473595 24.1995425 44.6820188 23.9921569 44.715205 23.8431373 44.7649843 23.6568627 44.8696485 23.6034641 44.9436792 23.5767647 45.0260065 23.4674837 45.0751476 23.3985621 45.1357762 23.3588235 45.1223741 23.0080065 45.297878 22.939085 45.3119183 22.8832026 45.369356 22.792549 45.3993512 22.6652614 45.5448599 22.5777124 45.5697495 22.4796078 45.6354837 22.325 45.6795192 22.2237908 45.6878158 22.1784641 45.7235548 22.0642157 45.7375951 22.0344118 45.8109876 22.0381373 45.8550231 21.8624183 45.8556613 21.835719 45.8888475 21.8729739 46.0228687 21.8313725 46.1217891 21.6388889 46.2175185 21.5743137 46.4089773 21.5345752 46.4613094 21.4513725 46.5091741 21.4395752 46.6508536 21.4079085 46.7274371 21.2936601 46.8889007 21.2352941 47.0861033 21.1899673 47.1192895 21.1210458 47.1167367 21.0012092 47.0675956 20.921732 47.095038 20.7913399 47.0490879 20.754085 47.1090783 20.7863725 47.1831091 20.6963399 47.2513961 20.6087908 47.2788385 20.4703268 47.2820295 20.1300654 47.3809499 19.9487582 47.2692656 19.8425817 47.260969 19.6705882 47.1920438 19.4035948 47.1524757 19.3421242 47.1224805 19.2086275 47.0159017 19.0080719 46.6942509 18.8869935 46.6597883 18.729902 46.6534064 18.6355229 46.6106472 18.3753595 46.655321 18.2896732 46.6336223 18.1567974 46.6578738 18.0711111 46.7031857 17.9500327 46.6757432 17.8910458 46.7153114 17.684281 46.7146732 17.5302941 46.6776578 17.4005229 46.6106472 17.3210458 46.5008775 17.0968954 46.102005 17.0379085 46.0318034 16.8932353 45.9494762 16.6535621 45.8939531 16.4287908 45.7624847 16.2394118 45.7407861 16.0115359 45.7643993 15.634 45.8844568 15.6333987 45.8856566 15.2931373 46.007552 15.1099673 46.0375472 14.8317974 46.0247833 14.710719 45.9788332 14.5070588 45.8626815 14.205915 45.4210498 14.0078431 45.262139 13.8687582 45.2346966 13.694281 45.2557571 13.5080065 45.3285114 13.2236275 45.5072063 12.9392484 45.5978301 12.6312745 45.6399511 12.4574183 45.7133436 12.1724183 45.7912035 11.6843791 45.7899272 11.1845425 45.8645961 10.9895752 45.8339627 10.8771895 45.7943945 10.6468301 45.6629261 10.3009804 45.3916928 10.2270915 45.2697974 10.1184314 44.9838856 10.0768301 44.9321917 9.9290523 44.8256129 9.7545752 44.819231 9.6508824 44.8766686 9.4906863 45.0617455 9.449085 45.271712 9.4379085 45.3655268 9.3696078 45.4382811 9.2218301 45.4772111 8.8430719 45.4701909 8.6183007 45.5365633 8.1861438 45.8760836 8.1302614 45.8518322 8.0271895 45.6903686 7.9234967 45.6993033 7.8378105 45.7292985 7.7546078 45.8205606 7.7341176 45.8965059 7.7428105 46.1945434 7.709902 46.3100569 7.5379085 46.5047067 7.336732 46.6049035 7.1771569 46.6412807 6.9467974 46.7440302 6.7859804 46.9112376 6.6127451 46.997394 6.5581046 47.0082434 6.4842157 46.9839919 6.3451307 46.8984737 6.2327451 46.8984737 6.1234641 46.9348508 6.0116993 47.0159017 5.9166993 47.1109929 5.8248039 47.3937138 5.7832026 47.5941073 5.7037255 47.7855661 5.590719 47.9342658 5.5385621 47.9374568 5.3969935 47.8219433 5.2610131 47.7945009 5.0244444 47.7849279 4.8710784 47.6113386 4.7680065 47.3496783 4.6705229 47.2705419 4.5699346 47.2520343 4.4010458 47.2730947 3.4895425 47.5634739 3.2796732 47.5724087 2.9543137 47.5322023 2.7146405 47.4741265 2.6320588 47.4007339 2.5463725 47.2092751 2.375 47.1148221 2.3545098 47.0631282 2.345817 46.9137904 2.1949346 46.7765782 1.7186928 46.8033825 1.5119281 46.7484976 1.1064706 46.6929745 0.935098 46.6291549 0.6333333 46.2424081 0.5209477 46.1664628 0.3762745 46.1390204 0.3377778 46.108387 0.1148693 46.0713716 0.014281 45.9833005 0 45.913099 0.0565033 45.8097112 0.314183 45.7950327 0.329085 45.7643993 0.3085948 45.7005797 0.2092484 45.626549 0.2471242 45.5793225 0.6674837 45.5493272 0.8301634 45.4523214 0.8922549 45.3795671 0.9543464 45.3495719 1.1021242 45.3495719 1.1878105 45.3221295 1.410098 45.1402436 1.4336928 45.070042 1.4014052 44.9666543 1.2598366 44.7720045 1.2455556 44.5071531 1.2610784 44.047652 1.2964706 43.8893794 1.3436601 43.8408765 1.5094444 43.8772536 1.6218301 43.819816 1.8583987 43.4624262 1.9974837 43.3820135 2.0080392 43.2805404 2.0583333 43.2256555 2.1564379 43.1790672 2.3184967 43.0361113 2.389902 42.9205978 2.5637582 42.4432272 2.5389216 42.2460246 2.5550654 42.20518 2.6879412 42.1196618 2.740719 42.0398873 2.8301307 41.7220656 2.8829085 41.59315 2.9549346 41.5114609 2.9033987 41.3525501 2.9164379 41.3225549 3.1474183 41.0206882 3.3330719 40.8477371 3.3889542 40.7737063 3.4789869 40.5982024 3.573366 40.2388981 3.6236601 40.1431687 3.6981699 40.085731 3.7484641 40.0008509 3.8521569 39.9568154 3.9713725 39.773015 4.0166993 39.7455725 4.0514706 39.6932405 4.1576471 39.6409084 4.1439869 39.5834707 4.1601307 39.5285859 4.2079412 39.545179 4.2427124 39.5311387 4.3352288 39.3690369 4.3401961 39.2401213 4.3718627 39.157794 4.4488562 39.0563208 4.4463725 39.001436 4.4060131 38.9057065 4.4320915 38.8042334 4.4128431 38.7027602 4.4314706 38.6427698 4.4712092 38.6095836 4.5537908 38.6038398 4.6307843 38.5681008 4.662451 38.513216 4.6804575 38.3983407 4.7226797 38.3185662 4.7226797 38.2802744 4.6742484 38.2145402 4.5860784 38.2036909 4.5326797 38.1653991 4.5134314 38.067117 4.5264706 37.9713876 4.6270588 37.6861139 4.6239542 37.5795352 4.5941503 37.4972079 4.6208497 37.4589161 4.6872876 37.442323 4.7456536 37.3733979 4.7829085 37.2719247 4.7798039 37.1787481 4.8139542 37.0989736 4.7841503 37.0198373 4.8425163 36.9458065 4.9493137 36.9451683 4.9679412 36.9043238 4.9170261 36.830293 4.8394118 36.8143381 4.7034314 36.6994629 4.6444444 36.5488486 4.6736275 36.4582248 4.7829085 36.3975961 4.8201634 36.3210126 4.75 36.1295538 4.6959804 35.8883157 4.5699346 35.6834548 4.5885621 35.5928309 4.7773203 35.4441313 4.7742157 35.3758443 4.728268 35.2469287 4.7996732 35.1263096 4.7431699 34.9016646 4.6785948 34.7701963 4.5879412 34.6968037 4.5053595 34.7082912 4.4389216 34.7880657 4.3855229 34.8046588 4.3159804 34.7612615 4.2917647 34.7095676 4.3315033 34.65213 4.4376797 34.5832048 4.3786928 34.52832 4.3091503 34.5174706 4.2874183 34.4708823 4.319085 34.4185502 4.3935948 34.3745147 4.4643791 34.075839 4.5568954 33.8894857 4.5140523 33.7963091 4.5351634 33.7631229 4.5780065 33.7407861 4.7698693 33.7375951 4.8046405 33.7101526 4.8760458 33.5589002 4.9294444 33.5129501 4.9517974 33.4453013 5.0679085 33.2538425 5.13 33.0898261 5.334902 32.9047493 5.4864052 32.685848 5.7086928 32.5492741 5.904281 32.5224698 6.0228758 32.4618412 6.1476797 32.32782 6.2072876 32.1848641 6.3383007 31.6551614 6.3563072 31.4088178 6.3979085 31.3232995 6.6009477 31.1254587 6.7164379 30.9793118 6.8319281 30.7891294 6.8611111 30.657661 6.8486928 30.4228049 6.9660458 29.983726 7.0144771 29.1476892 7.0734641 28.8400787 7.1312092 28.7379674 7.2460784 28.6211775 7.3044444 28.4603521 7.2907843 28.3576025 7.1920588 28.1955007 7.1225163 27.9600064 6.9946078 27.8272616 6.876634 27.7985428 6.6748366 28.0244642 6.5444444 28.0123385 6.4407516 27.9332022 6.3730719 27.7136627 6.1774837 27.5732596 6.0092157 27.3045791 6.0334314 26.9542094 5.8620588 26.6555337 5.7204902 26.3262245 5.4218301 25.8411956 5.3628431 25.7863107 5.2386601 25.774185 4.6636928 25.7729086 4.5183987 25.702707 4.4861111 25.605063 4.5041176 25.4921023 4.5935294 25.315322 4.5848366 25.1934266 4.4631373 25.1047173 4.0688562 24.9821837 3.8850654 24.8353986 3.7788889 24.6464926 3.7372876 24.5214062 3.6516013 24.387385 3.4976144 24.2680423 3.3585294 24.1155135 3.2610458 23.9597937 3.1666667 23.7370632 3.1784641 23.7370632 3.1163725 23.3739297 2.9120915 23.0171781 2.7816993 22.8889007 2.6097059 22.8308249 2.4824183 22.7638143 2.3756209 22.6508536 2.3222222 22.5653353 2.3197386 22.4159975 2.4979412 22.1511461 2.5662418 22.010743 2.6289542 21.6693081 2.8127451 21.4350902 2.857451 21.2825613 2.8276471 21.206616 2.6972549 21.10259 2.5196732 21.0413232 2.3184967 20.8856034 2.0490196 20.7662607 1.9782353 20.6622348 1.9844444 20.458012 2.0794444 20.1657182 2.0943464 19.985747 2.0738562 19.696006 1.9676797 19.3788225 1.9651961 19.0099452 1.980098 18.9033665 1.9577451 18.764878 1.7944444 18.4062118 1.6640523 18.3430304 1.5951307 18.2351752 1.5889216 18.0405255 1.4554248 17.7290858 1.4200327 17.5554965 1.4026471 17.2657555 1.4411438 17.1559858 1.4411438 16.9792055 1.2368627 16.2625113 1.3206863 16.1310429 1.5572549 15.9357549 1.6193464 15.8017338 1.6907516 15.5247567 1.6919935 15.3186194 1.6671569 15.0856778 1.5982353 14.968888 1.4045098 14.7372228 1.4554248 14.48705 1.5206209 14.3530288 1.4852288 14.2190076 1.4709477 13.8896985 1.5187582 13.7614211 1.5069608 13.7129182 1.3293791 13.5450726 1.2821895 13.5240121 1.1573856 13.5450726 1.059281 13.5176302 0.979183 13.383609 1.0536928 12.9566559 1.0654902 12.7920013 0.9916013 12.4531192 0.9680065 11.9802159 0.9773203 11.7026007 0.8680392 11.299899 0.876732 11.202255 0.9686275 11.0803595 1.0636275 10.8818806 1.2151307 10.7414774 1.271634 10.6100091 1.2958497 10.4300378 1.2809477 10.1919907 1.3076471 9.9756422 1.3703595 9.7407861 1.5218627 9.3808435 1.6379739 9.0451524 1.7987908 8.77711 2.0216993 8.6309632 2.170098 8.4235494 2.2415033 8.1855023 2.3160131 8.0910493 2.4377124 8.0176568 2.5060131 7.8440674 2.8003268 7.2952189 3.1213399 6.7463703 3.4721569 6.3960006 3.9831699 6.0335053 4.224085 5.9090571 4.3786928 5.7903526 4.6792157 5.439983 4.7444118 5.3914801 5.0356209 5.3002181 5.1933333 5.1847046 5.2529412 5.0749349 5.298268 4.7028666 5.3758824 4.5471467 5.7627124 4.0653087 6.021634 3.6900495 6.5388562 3.1660905 6.7027778 2.9529331 6.8275817 2.7091422 6.8828431 2.3364357 6.8648366 1.6790938 6.9660458 1.6312291 6.9604575 1.5897463 7.0213072 1.3063873 7.0771895 1.1245014 7.1622549 0.9675052 7.2876797 0.8079562 7.3385948 0.7856193 7.3597059 0.800936 7.3870261 0.7307345 7.3646732 0.6701058 7.3721242 0.4997075 7.4013072 0.4282295 7.5422549 0.2712333 7.6167647 0.065096 7.6484314 0.0319098"}})])])
}
var Guyanevue_type_template_id_408c2ea0_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Guyane.vue?vue&type=script&lang=js



/* harmony default export */ var Guyanevue_type_script_lang_js = ({
  mixins: [base],
  props: {
    colorStroke: String
  }
});

;// CONCATENATED MODULE: ./src/components/maps/Guyane.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_Guyanevue_type_script_lang_js = (Guyanevue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/Guyane.vue





/* normalize component */
;
var Guyane_component = normalizeComponent(
  maps_Guyanevue_type_script_lang_js,
  Guyanevue_type_template_id_408c2ea0_render,
  Guyanevue_type_template_id_408c2ea0_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Guyane = (Guyane_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Reunion.vue?vue&type=template&id=447a7126
var Reunionvue_type_template_id_447a7126_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"width":"56px","height":"50px","viewBox":"0 0 56 50","version":"1.1","xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{staticClass:"FR-DOM-974",attrs:{"fill":"#EEEEEE","fill-rule":"nonzero","stroke":_vm.colorStroke,"stroke-width":"0.2%"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}},[_c('polygon',{staticClass:"FR-DOM-974",attrs:{"id":"RE","points":"20.3669387 0 19.3645103 0.4598112 17.8247046 0.2399598 16.1023934 0.7399877 15.5008011 0.9397308 11.7655217 3.686533 11.1544663 4.619561 9.9282996 5.1712004 9.3084568 5.2670502 9.1333867 5.0391554 9.2030092 5.3065967 9.4078209 5.3374295 9.3740236 5.8964419 8.3830863 5.6960286 8.9177599 5.5063397 8.8961296 5.1148969 7.9376377 5.3360889 6.3282095 4.7750656 5.8489635 4.9801709 5.6806529 5.944702 6.0382285 5.932637 6.0098388 5.6135843 6.3255057 5.6718986 6.0503956 6.3689884 5.8469357 6.1819807 5.692144 6.808021 5.7827208 6.153829 5.9010114 6.1384126 5.5968356 6.0539574 5.5332966 7.204826 5.2399359 7.6639669 5.7110705 9.6319053 5.251427 11.790873 3.3486381 13.4022231 2.5848187 13.7259677 1.5803625 13.5255544 0.0060835 15.2361057 0 15.7622745 0.5961847 16.8467855 0.3697426 19.1840474 1.234278 20.3737921 3.2276437 22.0293806 3.1323353 22.4094286 4.6991789 24.0703793 4.7674494 25.7521086 5.6238734 26.1200916 6.0774334 26.7709322 6.1267775 28.8260068 5.5191017 30.6860303 5.9544112 31.0754622 6.6154867 33.188851 8.1640797 34.1165168 8.9650761 34.8772831 9.0117164 35.2740881 9.8938264 35.8625929 10.2365311 36.8479026 10.0465902 37.2038206 10.666433 37.9123052 12.8808332 38.2119198 14.9891098 38.8895716 15.7711797 39.371502 17.6976267 41.6906664 18.5351242 42.1062392 20.224314 42.3059822 20.6873373 42.5553259 20.9333817 43.3362006 21.8445574 43.7624979 22.7976417 43.9481651 22.3961296 43.7732224 22.7672241 43.7859577 22.8868666 43.5111434 22.7895303 44.1385243 23.0396305 44.1043401 23.2843231 44.4857286 24.8241288 44.3939005 26.710695 45.3436854 27.9983727 45.6010724 29.1076006 46.571636 29.4415181 46.2639781 30.9191368 46.7559627 32.1966753 46.3095571 32.4711096 46.7459085 33.3200981 47.0374798 34.2353295 47.7607105 36.5747797 47.3431269 37.4244442 48 38.5593581 47.3444674 39.5881484 47.4597553 40.3844132 46.6105122 42.8617565 46.7720494 44.5414831 46.1091437 46.5159223 45.9576607 47.022882 45.5079037 48.6546165 45.7478635 50.5695724 44.3476512 51.4530342 43.0741217 51.6233727 42.5244931 51.1448027 40.5364464 51.5341478 38.5296319 51.1116814 37.6582696 50.9453986 35.9443669 51.5611857 32.9167179 52.0573303 31.6726806 52.967154 30.6840194 53.055703 29.1115456 54 28.8554991 53.6525636 27.2280623 53.8587272 26.7273641 53.2199579 25.9665978 53.0570549 25.161593 51.6551422 24.6032509 50.4816994 23.4892476 49.2372822 23.3317321 48.320699 21.7699827 47.526462 21.1318773 46.9762417 20.1023292 45.3688414 18.3334637 44.8037503 17.3039155 44.9193371 16.4546724 43.6255758 14.8004245 42.7725315 14.4130034 42.325731 13.4806457 42.1384939 9.8986762 41.8086321 8.5902921 40.6405968 6.7329498 39.1102544 5.0069821 37.8901712 4.2200748 36.8661126 3.8065129 35.1505608 3.5953751 32.6961997 2.2420823 31.8411276 2.3949059 29.7876026 1.8338826 29.0832666 2.2058873 28.2640196 2.1194213 25.098563 0.9913422 23.7345033 1.3150869 22.2893301 1.2594537"}})])])
}
var Reunionvue_type_template_id_447a7126_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Reunion.vue?vue&type=script&lang=js



/* harmony default export */ var Reunionvue_type_script_lang_js = ({
  mixins: [base],
  props: {
    colorStroke: String
  }
});

;// CONCATENATED MODULE: ./src/components/maps/Reunion.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_Reunionvue_type_script_lang_js = (Reunionvue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/Reunion.vue





/* normalize component */
;
var Reunion_component = normalizeComponent(
  maps_Reunionvue_type_script_lang_js,
  Reunionvue_type_template_id_447a7126_render,
  Reunionvue_type_template_id_447a7126_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Reunion = (Reunion_component.exports);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/loaders/templateLoader.js??ruleSet[1].rules[2]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Mayotte.vue?vue&type=template&id=de12d894
var Mayottevue_type_template_id_de12d894_render = function render(){var _vm=this,_c=_vm._self._c;return _c('svg',{attrs:{"width":"38px","height":"50px","viewBox":"0 0 38 50","version":"1.1","xmlns":"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink"}},[_c('g',{staticClass:"FR-DOM-976",attrs:{"fill":"#EEEEEE","fill-rule":"nonzero","stroke":_vm.colorStroke,"stroke-width":"0.2%"},on:{"click":function($event){return _vm.onclick($event)},"dblclick":function($event){return _vm.ondblclick()},"mouseenter":function($event){return _vm.onenter($event)},"mouseleave":function($event){return _vm.onleave($event)}}},[_c('path',{staticClass:"FR-DOM-976",attrs:{"d":"M30.8268355,19.5321985 L30.8268355,19.5321985 L30.770532,19.4931803 L30.7007157,19.4400975 L30.6637806,19.2549883 L30.6345028,19.12886 L30.4493769,18.9410286 L30.3394725,18.9355842 L30.2300185,18.9782319 L30.1556979,19.0276851 L30.1372304,19.0571755 L30.1629048,19.1320359 L30.1629048,19.1320359 L30.1629048,19.1320359 L30.1597518,19.1796743 L30.1502928,19.2763122 L30.1678595,19.30671 L30.1881287,19.3312098 L30.2322707,19.3362005 L30.3187528,19.4096997 L30.3755067,19.4060701 L30.4300085,19.4351069 L30.476853,19.4396438 L30.5448676,19.4355606 L30.6137831,19.4518937 L30.6912567,19.4704954 L30.7439568,19.5031617 L30.861068,19.6056977 L30.9191732,19.7608628 L30.9191732,19.7608628 L30.9191732,19.7608628 L30.9052099,19.8715654 L30.8916971,19.9228334 L30.9178219,19.9514164 L31.0083579,19.974555 L31.0313298,19.96412 L31.0718683,19.9659348 L31.1038487,19.9609441 L31.1308743,19.912852 L31.2002402,19.8652136 L31.283119,19.8892596 L31.3254592,19.9273703 L31.3781593,20.0013233 L31.5376107,20.0956927 L31.7335469,20.1823492 L31.8583154,20.2540337 L31.8583154,20.2540337 L31.8583154,20.2540337 L31.7921025,20.3257181 L31.8033632,20.3343384 L31.8276863,20.3189126 L31.8961513,20.3474957 L31.9213753,20.3828842 L32.0614584,20.5099199 L32.1686602,20.567086 L32.2934288,20.6256132 L32.3880186,20.7422139 L32.416846,20.8216112 L32.5731445,21.4313827 L33.0078074,21.7702959 L33.2375256,22.1808936 L33.2375256,22.1808936 L33.2375256,22.1808936 L33.1785196,22.3183644 L33.1127571,22.3782527 L33.2591462,22.5887691 L33.2839197,22.7280547 L33.2839197,22.7280547 L33.2839197,22.7280547 L33.2708573,22.9535431 L33.2983334,23.0919213 L33.3028377,23.2057998 L33.3875181,23.2675029 L33.46319,23.32603 L33.5375106,23.3686778 L33.591562,23.3759369 L33.6289475,23.3673167 L33.6753416,23.3505298 L33.7974075,23.4158625 L33.9095641,23.4448992 L33.9640659,23.4748433 L33.982083,23.4816488 L34.0028027,23.4757507 L34.2307192,23.8700152 L34.2374756,23.8895243 L34.2374756,23.8895243 L34.2374756,23.8895243 L34.2352235,23.909487 L34.2446824,23.9181073 L34.2636004,23.9194684 L34.4266553,23.8228305 L34.4329613,23.8128491 L34.4293579,23.7947012 L34.0163155,23.0801251 L34.0073069,22.9503672 L34.0073069,22.9503672 L34.0073069,22.9503672 L34.0082078,22.8464701 L34.0199189,22.7203418 L34.0280266,22.5447602 L34.056854,22.2394208 L34.0721686,22.0919686 L34.1041489,21.9839882 L34.1888294,21.8515081 L34.3329663,21.7467036 L34.3703518,21.6972504 L34.356839,21.6468898 L34.344227,21.5997051 L34.344227,21.5997051 L34.344227,21.5997051 L34.4059356,21.528928 L34.4874631,21.4980765 L34.5455683,21.4994376 L35.0617587,21.5112338 L35.1162604,21.4903636 L35.2590461,21.4173181 L35.3225564,21.3488095 L35.3666984,21.29981 L35.4423703,21.2299404 L35.5266003,21.1337562 L35.5725439,21.0126185 L35.6513688,20.8774162 L35.6392072,20.8084539 L35.6392072,20.8084539 L35.6392072,20.8084539 L35.706321,20.8080002 L35.6901056,20.7748802 L35.6392072,20.6723442 L35.5383114,20.6551036 L35.5049797,20.6891311 L35.437866,20.6723442 L35.4130924,20.5416789 L35.4130924,20.5416789 L35.4130924,20.5416789 L35.5076823,20.5022071 L35.5833542,20.4001248 L35.5405635,20.3030332 L35.4509284,20.2712742 L35.3045393,20.1877936 L35.2946299,19.8919818 L35.2946299,19.8919818 L35.2946299,19.8919818 L35.3910215,19.8116771 L35.4612882,19.7694831 L35.4536309,19.6977986 L35.4536309,19.6977986 L35.4536309,19.6977986 L35.4648916,19.6134106 L35.5099344,19.5444483 L35.5932636,19.4496252 L35.6725389,19.3942739 L35.777939,19.3997183 L35.8144237,19.4550696 L35.8648716,19.5889108 L35.9441469,19.6306512 L35.987388,19.6129569 L36,19.5031617 L35.9801812,19.2409237 L35.9261298,19.1660633 L35.809469,19.133397 L35.8130724,19.1764984 L35.8130724,19.1764984 L35.8130724,19.1764984 L35.7468595,19.2377478 L35.6626295,19.2908306 L35.5860568,19.27359 L35.5211951,19.2209609 L35.4851609,19.1510912 L35.4698464,19.0771383 L35.4698464,19.0771383 L35.4698464,19.0771383 L35.4815575,18.9791393 L35.5797508,18.9378527 L35.7878485,18.6982996 L35.6369551,18.4605613 L35.4018317,18.4269875 L35.4018317,18.3589327 L35.2689555,18.344868 L35.2495871,17.3721372 L35.1099545,17.3957296 L34.9847355,17.2632494 L34.9302337,17.1920187 L34.8527601,17.1035474 L34.8433011,17.0350388 L34.8225815,17.0214278 L34.7924028,16.9996503 L34.7257395,16.9946596 L34.6982634,16.9315954 L34.6847505,16.8830496 L34.6847505,16.8830496 L34.6847505,16.8830496 L34.6897052,16.8639943 L34.7005155,16.828152 L34.6586257,16.7841432 L34.5644863,16.7560139 L34.4870127,16.6988478 L34.3906211,16.6235337 L34.3230569,16.4951369 L34.3167509,16.3780826 L34.2933287,16.3150184 L34.26315,16.2605745 L34.2158551,16.2083991 L34.09469,16.1503256 L34.0311796,16.1158445 L33.9906411,16.118113 L33.9559582,16.1489645 L33.9793804,16.2061306 L33.9793804,16.2061306 L33.9793804,16.2061306 L33.9383915,16.2964167 L33.8879435,16.3671938 L33.8055152,16.4892388 L33.7744357,16.501035 L33.7059707,16.6044784 L33.6415595,16.6988478 L33.5627346,16.819078 L33.5050798,16.9197992 L33.3109454,17.2101666 L33.1249187,17.3957296 L33.0514989,17.4769417 L32.97898,17.5477187 L32.7704319,17.7360039 L32.5416145,17.9469739 L31.8421,18.5771619 L31.4475251,19.2109795 L31.4979731,19.30671 L31.6835494,19.2853862 L31.7713828,19.1869335 L31.8506581,19.1869335 L32.0812772,19.0558144 L32.1474901,19.05037 L32.1528953,18.9759634 L32.038937,18.8103632 L31.9542565,18.7300585 L31.9542565,18.7300585 L31.9542565,18.7300585 L32.0470447,18.7355029 L32.0574045,18.5993932 L32.1317251,18.5190884 L32.1555978,18.6606426 L32.1555978,18.6606426 L32.1555978,18.6606426 L32.110555,18.7355029 L32.1659577,18.8825014 L32.2353236,18.8874921 L32.2375757,18.8394 L32.197938,18.7595489 L32.197938,18.7595489 L32.197938,18.7595489 L32.3411741,18.7327807 L32.3281117,18.5485789 L32.3281117,18.5485789 L32.3281117,18.5485789 L32.5375607,18.420182 L32.6911566,18.4469503 L32.7506131,18.5998469 L32.7506131,18.6338743 L32.7506131,18.6338743 L32.7506131,18.6338743 L32.716831,18.6338743 L32.6501677,18.8720663 L32.6163856,18.8720663 L32.3519844,19.1274989 L32.3055903,19.287201 L32.2407287,19.4500789 L32.1159602,19.4573381 L31.9429958,19.768122 L31.9119163,19.8071401 L31.9069616,19.8792782 L31.9123667,19.9958789 L31.9123667,19.9958789 L31.9123667,19.9958789 L31.8731795,20.0294526 L31.8204795,20.1047667 L31.6835494,20.090702 L31.5822031,20.0067677 L31.55788,19.9065002 L31.4912167,19.8556859 L31.4335619,19.8438897 L31.3583404,19.8575007 L31.2412292,19.7685757 L31.2412292,19.7685757 L31.2412292,19.7685757 L31.2961814,19.6773822 L31.3677994,19.6669471 L31.4398679,19.6569657 L31.6024723,19.633827 L31.6344527,19.5231245 L31.5722937,19.5993459 L31.516891,19.6111421 L31.3840148,19.5757536 L31.1403333,19.5884571 L31.1403333,19.5884571 L31.1403333,19.5884571 L31.1560983,19.56305 L31.2745608,19.553976 L31.4457234,19.2989971 L31.4214003,19.2454606 L31.2646514,19.3802093 L30.9367399,19.4827453 L30.9128672,19.4940877 L30.8691757,19.4750324 L30.8169261,19.4881896 L30.8268355,19.5321985 L30.8268355,19.5321985 Z M16.5622341,48 L16.8248336,47.9029084 L16.9928432,47.7840392 L17.1437365,47.6474758 L17.2153546,47.5231623 L17.2950803,47.3335161 L17.3090436,47.2173691 L17.4603874,47.0263618 L17.5504729,46.9660198 L17.5486712,46.8426137 L17.5486712,46.8426137 L17.5486712,46.8426137 L17.6558731,46.7083188 L17.7819929,46.6003384 L17.8819879,46.3870998 L17.8486562,46.3530724 L17.8482058,46.0468255 L17.7982083,45.978317 L17.7437065,45.9093547 L17.7166808,45.7519211 L17.7135279,45.6607276 L17.637856,45.6040152 L17.637856,45.6040152 L17.637856,45.6040152 L17.6405585,45.437054 L17.5901106,45.3735361 L17.5901106,45.3735361 L17.5901106,45.3735361 L17.6761423,45.2891481 L17.693709,45.2528522 L17.7103749,45.2378801 L17.7405535,45.2047601 L17.7734348,45.1675567 L17.8324408,45.1217331 L17.9833342,45.0500487 L18.0662129,45.0078547 L18.0851309,44.8749208 L18.1441369,44.8195696 L18.2274661,44.7905328 L18.3468295,44.8182085 L18.4405185,44.7923476 L18.4783544,44.7823662 L18.5697913,44.7524221 L18.9310345,44.7297371 L18.9576097,44.7356352 L19.1328262,44.7737459 L19.1328262,44.7737459 L19.1328262,44.7737459 L19.1134578,44.8127641 L19.1301236,44.8486063 L19.2765127,44.8758282 L19.3724538,44.9257351 L19.443171,44.9901604 L19.4769531,45.0264563 L19.5967669,45.1090296 L19.636855,45.1448718 L19.6584756,45.2056675 L19.65262,45.3009443 L19.6296482,45.3803416 L19.6769431,45.4733499 L19.7382013,45.5454881 L19.7449577,45.5790618 L19.8508083,45.8086335 L19.9296332,45.884855 L19.9481007,45.9483728 L19.9377409,45.9973723 L19.9575597,46.0663346 L19.9616135,46.1280377 L19.9224263,46.3957201 L19.958911,46.5672184 L19.9755768,46.6493379 L19.9818828,46.6584119 L20.0170162,46.7586794 L20.0719684,46.922011 L20.0872829,46.9465108 L20.1016966,47.010936 L20.1016966,47.010936 L20.1016966,47.010936 L20.0976428,47.1538512 L20.0881838,47.2654612 L20.1503428,47.3389605 L20.2643011,47.3189977 L20.4142936,47.3031182 L20.5692408,47.2504891 L20.5732946,47.216008 L20.5219458,47.145231 L20.4543817,47.0948704 L20.4070867,47.047232 L20.3692508,46.9855289 L20.3602422,46.9061316 L20.3458285,46.7813643 L20.3458285,46.7813643 L20.3458285,46.7813643 L20.3908713,46.7042355 L20.4196987,46.6425324 L20.4823082,46.5567833 L20.5232971,46.4587843 L20.5557279,46.3449058 L20.598969,46.2246756 L20.7286923,46.0577143 L20.8237325,46.011437 L20.8881437,45.9765022 L21.0448926,45.9093547 L21.2592963,45.8739662 L21.4128922,45.8912067 L21.489465,45.9538172 L21.5935138,46.0100759 L21.7750363,46.0413811 L21.8633202,46.0876584 L22.0128622,46.1534448 L22.0470947,46.1797594 L22.2033932,46.2754899 L22.2664531,46.3167765 L22.367349,46.3086099 L22.4443722,46.3326559 L22.4159952,46.2627863 L22.4159952,46.2627863 L22.4159952,46.2627863 L22.4763525,46.0985472 L22.4763525,46.0985472 L22.5466193,46.0046315 L22.6659827,45.8889382 L22.6939092,45.8789569 L22.6943596,45.8789569 L22.8092188,45.838124 L22.8119213,45.7024679 L22.8592163,45.6389501 L22.8997548,45.6031078 L22.961013,45.5491176 L23.004254,45.535053 L23.0317301,45.5001182 L23.0704669,45.4316096 L23.1542465,45.4216282 L23.2087483,45.4438595 L23.3173014,45.437054 L23.3258596,45.2891481 L23.3182023,45.1934176 L23.3182023,45.1934176 L23.3182023,45.1934176 L23.3781092,45.0967797 L23.4402683,45.0890668 L23.4799059,45.0468728 L23.5015265,44.9438831 L23.5465692,44.8803652 L23.5970172,44.8318194 L23.7740353,44.6870894 L23.7533156,44.6185809 L23.6983634,44.5713962 L23.5893599,44.5804701 L23.4754016,44.5958959 L23.3533357,44.5691277 L23.2335218,44.5736647 L23.0574045,44.5827386 L22.9110155,44.6417195 L22.8276863,44.7614961 L22.7623743,44.7846347 L22.670487,44.7605887 L22.3466293,44.5700351 L22.2943797,44.5192208 L22.1948351,44.4892766 L22.1002452,44.3613335 L21.9489015,44.1186045 L21.8471047,44.0723272 L21.7804414,44.0827623 L21.3079425,44.0156148 L21.1471398,43.9289583 L21.0106601,43.8128113 L20.8935489,43.780145 L20.6530204,43.5732582 L20.5516741,43.5061108 L20.4377158,43.4548428 L20.2944798,43.2620207 L20.1715129,42.8732006 L20.1715129,42.8732006 L20.1715129,42.8732006 L20.1476403,42.7311928 L20.1742155,42.6994338 L20.1769181,42.5302041 L20.1769181,42.5302041 L20.0895351,42.3532614 L20.0647615,42.2833918 L20.0571042,42.2620679 L20.0170162,42.1731429 L19.9066613,42.0860327 L19.7553176,41.9276917 L19.5764977,41.8315075 L19.5607327,41.8224335 L19.5445173,41.8188039 L19.4837095,41.7952116 L19.4472249,41.7530176 L19.4116411,41.6386854 L19.4116411,41.6386854 L19.3580401,41.5874174 L19.3143486,41.5189089 L19.2485862,41.2975037 L19.2584956,41.0969687 L19.2575947,41.0819967 L19.2593964,41.0729227 L19.2634503,40.989442 L19.3945248,40.6827415 L19.3999299,40.6704916 L19.4774035,40.5316597 L19.5319053,40.4368366 L19.6251439,40.3492727 L19.7066713,40.326134 L19.7760372,40.2911992 L19.9773785,40.2399312 L20.0935889,40.3810316 L20.2125019,40.4100683 L20.3264601,40.4450032 L20.3264601,40.4450032 L20.4142936,40.4268552 L20.4647415,40.4867435 L20.4647415,40.4871972 L20.4647415,40.4871972 L20.5823032,40.5121506 L20.58951,40.4808454 L20.627346,39.9527397 L20.2809669,39.518096 L20.2566438,39.4976795 L20.2413293,39.4681891 L20.1670087,39.3633846 L20.1764676,39.2803577 L20.1719634,39.1106742 L20.1877283,39.0226566 L20.2120514,38.9477963 L20.2719584,38.8357326 L20.3624944,38.6565215 L20.5133877,38.384302 L20.5967169,38.2522756 L20.6327511,38.2023687 L20.6786948,38.1556377 L20.727341,38.0984716 L20.7818428,38.0943883 L20.8962514,38.1188881 L21.0025524,38.0912124 L21.0660628,38.0499258 L21.1367799,37.9995652 L21.2151544,38.0031948 L21.2782143,38.0118151 L21.3403734,37.9982041 L21.2939793,37.8965755 L21.2836194,37.8294281 L21.2723587,37.7445863 L21.2471348,37.6783463 L21.1998398,37.6225413 L21.1930834,37.5640141 L21.1930834,37.5640141 L21.1930834,37.5640141 L21.2232621,37.4424228 L21.2854212,37.2967854 L21.3597418,37.1965179 L21.4155948,37.1248334 L21.5070317,37.0445287 L21.561083,37.0073253 L21.6908063,36.9510667 L21.8034132,36.9764738 L21.9480006,37.0399917 L22.089435,37.0009736 L22.2259146,37.0154919 L22.2587959,37.02139 L22.5533757,37.0790098 L22.5795005,37.086269 L22.6362544,37.1175742 L22.7290426,37.1910735 L22.751564,37.2332675 L22.7709324,37.27773 L22.8502077,37.2999612 L23.1875782,37.4378858 L23.4015315,37.4074879 L23.6456634,37.4628392 L23.7055703,37.4573948 L23.7438567,37.4387932 L23.7686302,37.4378858 L23.8542115,37.4546726 L24.2519393,37.5925971 L24.3397728,37.5817084 L24.2217607,37.4710058 L23.9379911,37.3770901 L23.8037636,37.3943307 L23.7033182,37.3303591 L23.6429608,37.2169343 L23.585306,37.1479721 L23.5181923,37.0885375 L23.426305,37.0454361 L23.3668485,36.8843729 L23.3249587,36.8439937 L23.2186577,36.7541613 L23.1267704,36.7378281 L23.0465943,36.7645963 L22.8916471,35.378092 L22.8916471,35.378092 L22.8916471,35.378092 L23.0601071,34.5682392 L22.9956959,34.2973808 L22.9956959,34.2973808 L22.9956959,34.2973808 L23.1393824,34.2438443 L23.2889245,34.1649007 L23.6015214,34.0351428 L23.7127771,33.9439493 L23.8046644,33.8582001 L23.8731295,33.8337004 L23.9542065,33.8817925 L24.1177118,33.8300708 L24.1834743,33.7692751 L24.3888694,33.7220904 L24.4330114,33.6640169 L24.504179,33.6227303 L24.6739903,33.683526 L24.6739903,33.5814437 L24.6739903,33.5814437 L24.6739903,33.5814437 L24.6825484,33.5192869 L24.6793954,33.5002316 L24.6793954,33.5002316 L24.6793954,33.5002316 L24.6938091,33.4657504 L24.7302938,33.4221953 L24.788399,33.3777328 L24.8320905,33.3205667 L24.9982984,33.1767441 L25.1996397,33.011144 L25.244232,32.9680426 L25.3735048,32.9866442 L25.51584,32.9358299 L25.4762024,32.8319329 L25.4762024,32.8319329 L25.4762024,32.8319329 L25.5356589,32.6776752 L25.4527801,32.6581661 L25.3352185,32.6558976 L25.2762124,32.581491 L25.2604474,32.5216027 L25.2563936,32.4503719 L25.2563936,32.4503719 L25.2563936,32.4503719 L25.2708073,32.3619006 L25.2969321,32.2865866 L25.3415244,32.1786062 L25.3613433,32.1314215 L25.3671988,32.1164494 L25.3811621,32.0420428 L25.450528,31.970812 L25.4951204,31.946766 L25.4586357,31.8628316 L25.4586357,31.8383319 L25.4586357,31.8383319 L25.4586357,31.8383319 L25.4698964,31.8043045 L25.5437666,31.6926945 L25.5951154,31.6337136 L25.8009609,31.4644839 L25.815825,31.4531414 L25.869426,31.4599469 L25.9653671,31.3220224 L26.0117612,31.2417176 L26.0428407,31.140089 L26.044192,31.088821 L26.0414894,31.0389141 L26.0167159,30.9876462 L25.9968971,30.9590631 L25.9635654,30.9477206 L25.8811371,30.9173228 L25.7730344,30.920045 L25.6653821,30.9404614 L25.563135,30.9776648 L25.2482859,31.1301077 L25.2000901,31.1568759 L25.0356839,31.2045143 L24.9739753,31.1813757 L24.7938041,31.037553 L24.7388519,30.9604242 L24.5541765,30.7222322 L24.5113858,30.6342146 L24.5113858,30.6342146 L24.5113858,30.6342146 L24.5708423,30.5861225 L24.5992193,30.5207898 L24.7046194,30.1433122 L24.7379511,30.1283401 L24.7956058,30.0884146 L24.8136229,30.0317022 L24.8573144,29.9736287 L24.7847956,29.8592966 L24.701016,29.7857973 L24.5366098,29.7690105 L24.3379711,29.9046665 L23.9569091,29.6936964 L23.8708773,29.5775495 L23.8285371,29.5716514 L23.7618738,29.5625774 L23.5235974,29.3996994 L23.5235974,29.3996994 L23.5235974,29.3996994 L23.5488214,29.3134966 L23.4938692,29.2005255 L23.4857615,29.0816564 L23.3677494,28.8911028 L23.3348681,28.7581689 L23.2938792,28.6892067 L23.2524398,28.6737809 L23.1956859,28.6828549 L23.1353286,28.7341229 L23.0925379,28.7704188 L23.0700165,28.7908353 L23.0479455,28.810798 L23.0065062,28.8303071 L22.9416446,28.810798 L22.8479556,28.7468265 L22.7835444,28.6533645 L22.7835444,28.6533645 L22.7835444,28.6533645 L22.8060658,28.6102631 L22.851559,28.5889392 L22.8411991,28.5608099 L22.8141735,28.516801 L22.8060658,28.4051911 L22.7934538,28.3466639 L22.7691307,28.3303307 L22.7524648,28.310368 L22.7479606,28.2804238 L22.7398529,28.2491186 L22.6596767,28.1869618 L22.5939142,28.1679065 L22.5912117,28.1583788 L22.5772484,28.1089256 L22.5619338,28.0077507 L22.5587808,27.9900564 L22.5529253,27.9015851 L22.5529253,27.9015851 L22.5529253,27.9015851 L22.5542766,27.8416968 L22.561033,27.7895214 L22.5808518,27.7060408 L22.5916621,27.6611246 L22.6011211,27.628912 L22.6087783,27.6021437 L22.6628297,27.539987 L22.7227366,27.4832746 L22.738952,27.4292844 L22.7150793,27.4156734 L22.6785947,27.410229 L22.6506681,27.4619507 L22.5880587,27.4379047 L22.5380612,27.3712109 L22.4646414,27.307693 L22.4114909,27.2305642 L22.3024874,27.0636029 L22.2529403,27.0599733 L22.2137531,27.0241311 L22.2137531,27.0236774 L22.2137531,27.0236774 L22.2335719,26.9574374 L22.2065462,26.9243173 L22.2065462,26.9243173 L22.2065462,26.9243173 L22.2313198,26.8553551 L22.2006907,26.8231424 L22.2006907,26.8231424 L22.2006907,26.8231424 L22.2398779,26.7283193 L22.2448326,26.6679774 L22.2718583,26.58858 L22.3290626,26.4002949 L22.3844652,26.3431288 L22.4002302,26.3204439 L22.4186978,26.2995737 L22.4804064,26.2324263 L22.5006756,26.1571122 L22.5470697,26.0817982 L22.6335519,26.0128359 L22.7740854,25.9307164 L22.8096692,25.8581245 L22.8434513,25.7837179 L22.8884941,25.6952466 L22.9209249,25.6503303 L22.9889395,25.6353583 L23.0785746,25.6135807 L23.0659627,25.5908958 L22.8209299,25.5673034 L22.7637255,25.5831829 L22.6646314,25.613127 L22.5254492,25.5065077 L22.4817577,25.1739463 L22.4817577,25.1739463 L22.4817577,25.1739463 L22.6272459,24.7919317 L22.7767879,24.7102659 L22.8875932,24.5977485 L22.9038086,24.5219808 L22.9407437,24.4879533 L23.2082979,24.3486677 L23.2443321,24.3146403 L23.3114459,24.2883258 L23.3542365,24.3255291 L23.4524298,24.2538446 L23.5470197,24.2574742 L23.6344027,24.2166413 L23.6992643,24.1758084 L23.7654772,24.085976 L23.7483609,24.0106619 L23.7222361,23.87183 L23.7222361,23.87183 L23.7222361,23.87183 L23.7749362,23.7833587 L23.8456534,23.7311833 L23.8600671,23.5419908 L24.0114108,23.279299 L24.016816,23.1613373 L23.9924929,23.0937361 L23.9767279,23.0492736 L23.9767279,23.0492736 L23.9767279,23.0492736 L23.9893399,22.9948297 L24.0767229,22.9027288 L24.1465392,22.8387572 L24.197888,22.7806838 L24.3987788,22.6168984 L24.5285021,22.5624545 L24.5640859,22.4526593 L24.5577799,22.3669102 L24.5577799,22.3669102 L24.5577799,22.3669102 L24.5807517,22.3165496 L24.6194885,22.2639205 L24.688404,22.2584761 L24.760022,22.1917824 L25.1009959,21.8896188 L25.1334268,21.8728319 L25.1672088,21.850147 L25.2009909,21.8478785 L25.2239628,21.8065919 L25.3068415,21.8392582 L25.3397227,21.8202028 L25.3365697,21.7802773 L25.2780141,21.6908986 L25.2780141,21.6908986 L25.2780141,21.6908986 L25.2991842,21.6505194 L25.3559381,21.6605008 L25.4144938,21.6278344 L25.4131425,21.6037884 L25.4041339,21.5992514 L25.3595416,21.6042421 L25.3554877,21.5901774 L25.3554877,21.5901774 L25.3554877,21.5901774 L25.3766578,21.5234837 L25.3793604,21.4567899 L25.4180972,21.4608732 L25.4437716,21.4472622 L25.437916,21.4077904 L25.4054852,21.3687723 L25.3807117,21.2421902 L25.3690006,21.1555337 L25.3690006,21.1555337 L25.3690006,21.1555337 L25.3735048,21.0979139 L25.3892698,21.0643402 L25.4581853,21.0325813 L25.4613383,20.9949242 L25.4338622,20.9763226 L25.4338622,20.9763226 L25.4338622,20.9763226 L25.437916,20.9627116 L25.4167459,20.9241472 L25.4167459,20.9241472 L25.4167459,20.9241472 L25.4171963,20.9055455 L25.4293579,20.8969252 L25.4622391,20.8955641 L25.5352084,20.8937494 L25.5861068,20.8592682 L25.6532206,20.7762413 L25.718983,20.7031957 L25.8716781,20.5970302 L26.0811271,20.4291615 L26.1428357,20.3878749 L26.3748061,20.1369793 L26.4243531,20.0639338 L26.6666833,19.8729265 L26.7396527,19.8153067 L26.8139733,19.7994272 L26.8819879,19.7740201 L26.9373905,19.7531499 L27.0351334,19.7776496 L27.1130574,19.7962513 L27.1540463,19.7885384 L27.1824233,19.7694831 L27.2076473,19.7154929 L27.1954857,19.6415399 L27.1954857,19.6415399 L27.1954857,19.6415399 L27.2044943,19.6011607 L27.2468345,19.5530686 L27.355838,19.5086061 L27.5188929,19.4854675 L27.587358,19.4918192 L27.5945648,19.4927266 L27.6486162,19.5326522 L27.6769931,19.6184013 L27.7058205,19.7173077 L27.7598719,19.7595017 L27.7918523,19.7676683 L27.8459036,19.7685757 L27.9157199,19.7304649 L27.9706721,19.712317 L28.0301286,19.633827 L28.0918372,19.5861886 L28.1008458,19.5086061 L28.1107552,19.3516262 L28.1332766,19.2658771 L28.1197638,19.1819428 L28.0900355,19.1406562 L28.0323808,19.0961937 L27.9386918,19.0812216 L27.8504079,19.0998232 L27.7909514,19.0993695 L27.752665,19.1211471 L27.7251889,19.1397488 L27.6481658,19.1070824 L27.4923177,18.9687042 L27.4328612,18.90564 L27.4197988,18.8752422 L27.4197988,18.8752422 L27.4197988,18.8752422 L27.4252039,18.8534647 L27.4166458,18.8312334 L27.4166458,18.8312334 L27.4166458,18.8312334 L27.4211501,18.825789 L27.4486262,18.8162613 L27.4481758,18.8130854 L27.4481758,18.8071873 L27.4396176,18.7908542 L27.4337621,18.7894931 L27.3995296,18.8008356 L27.3860167,18.7736136 L27.3765577,18.7622711 L27.3765577,18.7622711 L27.3765577,18.7622711 L27.3815124,18.755012 L27.3941244,18.755012 L27.396827,18.7309659 L27.3878184,18.7227993 L27.3878184,18.7227993 L27.3878184,18.7227993 L27.3882689,18.7069199 L27.4008808,18.7051051 L27.4026825,18.6973922 L27.403133,18.6896793 L27.3869176,18.685596 L27.3869176,18.685596 L27.3869176,18.685596 L27.3882689,18.6665406 L27.3878184,18.6610963 L27.387368,18.6533834 L27.3788099,18.6520223 L27.3788099,18.6520223 L27.3788099,18.6520223 L27.390521,18.6393187 L27.4148441,18.6325132 L27.4607877,18.6316058 L27.4765527,18.6229855 L27.4833091,18.6075598 L27.4824083,18.5621899 L27.4824083,18.5621899 L27.4824083,18.5621899 L27.487363,18.5567455 L27.4990741,18.5531159 L27.4990741,18.5417734 L27.4977228,18.5304309 L27.4855613,18.5299772 L27.477904,18.5249865 L27.477904,18.5249865 L27.477904,18.5249865 L27.477904,18.4914128 L27.487363,18.4678204 L27.5107852,18.4269875 L27.51574,18.4219968 L27.5265502,18.4188209 L27.5279015,18.4070248 L27.487363,18.400673 L27.3918723,18.3925064 L27.3360192,18.3816176 L27.3229568,18.3739047 L27.3089935,18.3444143 L27.2995346,18.3226367 L27.2837696,18.311748 L27.2607978,18.3063036 L27.2355738,18.3058499 L27.2175567,18.3122017 L27.2026926,18.3090258 L27.2008908,18.2976833 L27.2008908,18.2976833 L27.2008908,18.2976833 L27.2080977,18.2899704 L27.2162054,18.2827112 L27.2166558,18.269554 L26.9436965,18.1801752 L26.8432511,18.2314432 L26.8432511,18.2994981 L26.7085731,18.1633884 L26.7648766,18.1633884 L26.7581202,18.1461478 L26.7752365,18.0513247 L26.7923527,17.8911689 L26.725239,17.7890866 L26.8428007,17.7210318 L26.8423502,17.6529769 L26.9256794,17.6697638 L26.9626145,17.6311994 L27.0013513,17.5690426 L27.0603573,17.3789427 L27.1108053,17.3839334 L27.1108053,17.3807575 L27.1581002,17.3884704 L27.1657575,17.3893778 L27.1941344,17.2968232 L27.2351234,17.1053622 L27.2342225,17.1049085 L27.1441369,17.0745106 L27.1526951,16.9697061 L27.1234172,16.7982079 L27.1765677,16.7287919 L27.262149,16.8254298 L27.4797057,16.8531055 L27.5297032,16.9719746 L27.4630399,17.0400295 L27.4630399,17.1421118 L27.5634853,17.2101666 L27.5806016,17.5164135 L27.5134878,17.5164135 L27.5134878,17.4483586 L27.3792603,17.5504409 L27.312597,17.7205781 L27.1445874,17.7550592 L27.0247735,18.0286398 L27.118913,18.0495099 L27.3711526,18.0014178 L27.4981733,18.0218343 L27.6481658,18.0095844 L27.7152795,17.686097 L27.7823933,17.686097 L27.8490566,17.2437404 L27.9499525,17.2437404 L27.8657224,17.0395758 L27.8657224,17.0395758 L27.8657224,17.0395758 L27.9828337,16.7841432 L28.3521846,16.6988478 L28.4021821,16.5627381 L28.2846204,16.2909723 L28.049497,16.3082129 L27.8310395,15.9171243 L27.8310395,15.9171243 L27.8310395,15.9171243 L27.8477053,15.7810146 L27.7805916,15.7810146 L27.7805916,15.7810146 L27.7805916,15.7810146 L27.8477053,15.6616917 L27.8837396,15.6086089 L27.9170712,15.5709519 L27.9395926,15.4892861 L27.943196,15.4117035 L27.9143687,15.338658 L27.8675241,15.32051 L27.8184275,15.2628903 L27.8184275,15.2628903 L27.8184275,15.2229647 L27.8017617,15.1902984 L27.6459136,15.151734 L27.5116861,15.2538163 L27.2765627,15.203002 L27.209449,15.0668923 L27.0414394,14.9992911 L27.0414394,14.8972088 L26.8400981,14.7951265 L26.8400981,14.7270717 L26.8400981,14.7270717 L26.8400981,14.7270717 L26.9072119,14.7270717 L26.8396477,14.5229071 L26.6049747,14.3532236 L26.6049747,14.3191962 L26.6049747,14.3191962 L26.6049747,14.3191962 L26.6720885,14.3191962 L26.6716381,14.3191962 L26.5333567,14.082819 L26.528402,14.0810042 L26.4369651,14.0810042 L26.4369651,14.1132168 L26.4369651,14.1830865 L25.9333867,14.2175676 L25.9333867,14.1780958 L25.931585,14.1780958 L25.7320454,14.0819116 L25.7320454,14.0819116 L25.7320454,14.0819116 L25.6374556,13.8473492 L25.2955808,13.8949876 L25.1784695,13.808331 L25.0307292,13.7398225 L24.9609129,13.6477216 L24.8591162,13.5724075 L24.8199289,13.4136129 L24.7865973,13.345558 L24.7996597,13.3324007 L24.7582203,13.1640784 L25.0266753,13.0960235 L25.0266753,13.0960235 L25.0266753,13.0960235 L25.1266703,12.5856121 L24.8919974,12.6196395 L24.8919974,12.7897766 L24.8248836,12.7897766 L24.8248836,12.9258864 L24.7244382,12.9599138 L24.7244382,13.0279687 L24.6375056,13.0107281 L24.6375056,13.0107281 L24.5564286,13.0030152 L24.5140884,12.9680804 L24.5321055,12.920442 L24.5230969,12.8923126 L24.4222011,12.9431269 L24.3839147,12.9408584 L24.3771583,12.9771544 L24.2510385,12.9404047 L24.1812222,12.915905 L24.1812222,12.915905 L24.1357289,12.9013866 L24.1127571,12.8805165 L24.1114058,12.846489 L24.1078024,12.7784342 L24.1127571,12.7203607 L24.1258195,12.6672779 L24.1258195,12.6672779 L24.1397828,12.6296209 L24.1465392,12.5969545 L24.1478905,12.5674641 L24.1438366,12.5502235 L24.1303238,12.53752 L24.1028477,12.5429644 L24.0870827,12.575177 L24.0848306,12.5774455 L24.0654622,12.6005841 L24.0361844,12.5910565 L24.0051048,12.5679178 L23.9699715,12.5338904 L23.9699715,12.5338904 L23.9605125,12.4975944 L23.9605125,12.4975944 L23.9605125,12.4975944 L23.9708723,12.4567615 L23.9717732,12.4009565 L23.9717732,12.3732809 L23.9668185,12.3587625 L23.9497022,12.3728272 L23.8956509,12.4413358 L23.7654772,12.3846234 L23.7560182,12.3274573 L23.7524148,12.3220129 L23.701066,12.3124852 L23.6997147,12.3120315 L23.5830539,12.3487812 L23.4857615,12.2943373 L23.4537811,12.2031438 L23.320004,12.3029576 L23.3366698,12.421373 L23.1132576,12.4513171 L23.0384866,12.4154749 L23.01056,12.3760031 L22.9754267,12.3179296 L22.9299334,12.3501423 L22.848406,12.3397072 L22.7637255,12.4290859 L22.7173315,12.4363451 L22.6024723,12.4204656 L22.5186928,12.403225 L22.4556329,12.3828086 L22.4069866,12.3569477 L22.4002302,12.3474201 L22.3412242,12.3211055 L22.2880737,12.276643 L22.2880737,12.276643 L22.2880737,12.276643 L22.2952805,12.2199306 L22.267354,12.1958846 L22.1903308,12.211764 L22.0128622,12.160496 L21.9038587,12.0702099 L21.8687253,12.0797376 L21.8281868,12.0933486 L21.6944097,12.0180345 L21.5763976,12.0366362 L21.5426155,12.1509684 L21.4480256,12.1237464 L21.3777589,12.1428018 L21.3462289,12.0833672 L21.3363195,12.0507009 L21.3363195,12.0507009 L21.3363195,12.0507009 L21.3412742,12.0048773 L21.3273109,11.9572389 L21.2741605,11.9345539 L21.1966869,11.9200355 L21.1444372,11.8792026 L21.0998449,11.8660454 L21.0579551,11.8950821 L20.9849857,11.9250262 L20.9462489,11.9154985 L20.9444472,11.926841 L20.9480506,11.9812849 L20.876883,12.0334603 L20.8741805,12.0343677 L20.8647215,12.042988 L20.8385967,12.1296445 L20.6908563,12.1282834 L20.449427,12.122839 L19.7922526,11.9463501 L19.7598218,11.8519807 L19.6544217,11.7058896 L19.5679395,11.6922786 L19.3940744,11.5597985 L19.3706521,11.4917436 L19.3706521,11.4917436 L19.3706521,11.4917436 L19.4089385,11.4200592 L19.2756118,11.2340426 L19.1188629,10.9572861 L18.8922977,10.6596595 L18.8112207,10.6991314 L18.7499625,10.8211764 L18.674741,10.8996663 L18.5914118,10.9659064 L18.5639357,10.987684 L18.6396076,11.1201641 L18.6760923,11.1065531 L18.7350983,11.1773302 L19.0215705,11.2485609 L19.0215705,11.2485609 L19.0215705,11.2485609 L18.9661679,11.5638818 L18.8238326,11.5339376 L18.8206796,11.4468274 L18.5342075,11.3973742 L18.5387118,11.5475486 L18.6314999,11.6963619 L18.7468095,11.7421855 L18.7468095,11.7421855 L18.7468095,11.7421855 L18.2535909,12.0479787 L18.3053901,12.1781903 L18.3053901,12.1781903 L18.3053901,12.1781903 L17.8711776,12.7285273 L17.393724,12.9481176 L16.9338371,12.7675454 L16.9338371,12.7675454 L16.8460037,12.5488624 L16.8608678,12.407762 L16.9635654,12.2607635 L16.9694209,12.1827273 L16.8640208,12.1382648 L16.8041139,12.1323667 L16.756819,12.0960708 L16.6734898,12.0711173 L16.6329513,12.0488861 L16.6027726,12.0643118 L16.5243982,12.0543305 L16.5014264,12.0661266 L16.444222,12.0634044 L16.422151,12.0593212 L16.4014314,12.0643118 L16.3649467,12.047525 L16.1045994,12.0960708 L15.960012,12.0820061 L15.8329913,12.0298307 L15.7302938,11.9626833 L15.66318,11.9799238 L15.5775987,12.0094143 L15.360042,12.0838209 L15.360042,12.0838209 L15.2645513,11.9096005 L14.6717882,12.0874505 L14.6717882,12.0874505 L14.6717882,12.0874505 L14.8929483,11.6981767 L14.7312447,11.5325765 L14.4717982,11.2662552 L14.2700065,10.9600083 L14.2650518,10.7912323 L14.2650518,10.7912323 L14.2650518,10.7912323 L14.3033382,10.744955 L14.2830689,10.6455949 L14.2479355,10.5235498 L14.2249637,10.4232823 L14.207397,10.3493294 L14.0646114,10.2613118 L13.9587608,10.1347297 L13.9011061,10.040814 L13.8024623,9.9491668 L13.6619288,9.9432687 L13.5691407,9.867501 L13.4871628,9.6587994 L13.470497,9.4324036 L13.470497,9.4324036 L13.470497,9.4324036 L13.4745508,9.0807868 L13.5006756,9.0381391 L13.5754467,8.9165477 L13.8682248,8.7323459 L13.9551574,8.72917 L13.9839848,8.7491328 L14.0551524,8.7745399 L14.151544,8.8716315 L14.2641509,8.8689093 L14.3853161,8.9065663 L14.3528852,8.7890583 L14.3456784,8.6298099 L14.3456784,8.6298099 L14.3456784,8.6298099 L14.3600921,8.4088585 L14.2303689,8.4120344 L14.1393824,8.5132093 L14.0096592,8.5268202 L13.8497573,8.5113945 L13.7308443,8.4610339 L13.6475151,8.3952475 L13.5186928,8.350785 L13.4051849,8.2854523 L13.2119514,8.2051476 L13.0340323,8.1389075 L12.9110655,8.0291124 L12.7741354,7.9674093 L12.6417096,7.8476327 L12.3367699,7.8045313 L12.2570442,7.7333006 L12.1723637,7.6688753 L12.1318252,7.5867558 L12.0791252,7.5191546 L12.0300285,7.4833124 L11.9939943,7.4170723 L11.9647165,7.3903041 L11.9327361,7.3276936 L11.9327361,7.3276936 L11.9327361,7.3276936 L11.9331865,7.290944 L11.9291327,7.2628146 L11.8827386,7.197482 L11.8759822,7.0019377 L11.8759822,7.0019377 L11.8759822,7.0019377 L11.9061609,6.8798926 L11.9876883,6.8363375 L12.0669636,6.8504022 L12.117862,6.8440504 L12.1714629,6.8490411 L12.2056954,6.8345227 L12.198939,6.7841621 L12.0795756,6.6689226 L11.9813823,6.5291832 L11.7692308,6.4974243 L11.6219408,6.4987854 L11.5723938,6.4470637 L11.5395125,6.3917124 L11.511586,6.315491 L11.4944697,6.2465287 L11.4881638,6.1648629 L11.4773535,6.1231226 L11.4359141,6.1008913 L11.3660978,6.0800212 L11.346279,6.0822897 L11.2836695,6.1249374 L11.2318703,6.1439927 L11.1805215,6.1458075 L11.1228667,6.1358262 L11.0679145,6.1498908 L11.0161153,6.1780202 L10.9841349,6.1739369 L10.9300836,6.1612333 L10.858916,6.1834646 L10.7868475,6.1861868 L10.7616235,6.1802887 L10.7111756,6.133104 L10.6737901,6.0954469 L10.6544217,6.0387346 L10.6314499,5.9920036 L10.6246935,5.9284857 L10.6080276,5.8844769 L10.5792002,5.839107 L10.5656874,5.792376 L10.565237,5.765154 L10.565237,5.765154 L10.565237,5.765154 L10.5737951,5.7352099 L10.5706421,5.7175156 L10.5706421,5.7175156 L10.5706421,5.7175156 L10.6035233,5.709349 L10.6328012,5.7034509 L10.6918072,5.6812197 L10.7354987,5.6558125 L10.8300886,5.5941095 L10.8656724,5.5215176 L10.8963015,5.4443888 L10.9269306,5.4248797 L10.9458486,5.3545564 L10.9737751,5.3150845 L11.0098093,5.2942144 L11.043141,5.2792423 L11.0593564,5.2815108 L11.0665632,5.2697146 L11.0796256,5.2642703 L11.0814274,5.2583722 L11.0719684,5.2524741 L11.0544017,5.2533815 L11.0435914,5.2620018 L11.0197187,5.2479371 L10.9904409,5.2424927 L10.9440468,5.2461223 L10.8944998,5.2674461 L10.8143236,5.2824182 L10.7481107,5.2769738 L10.6755918,5.2170856 L10.611631,5.1594658 L10.5701917,5.1367808 L10.4850608,5.119994 L10.4301086,5.1231698 L10.362094,5.1317901 L10.2733597,5.1218087 L10.1877784,5.0932257 L10.1292228,5.0419577 L10.0895851,4.9757176 L10.074721,4.9303477 L10.074721,4.9303477 L10.074721,4.9303477 L10.0891347,4.8990425 L10.1103048,4.8618392 L10.1062509,4.8223674 L10.0882338,4.798775 L10.0332816,4.7720068 L9.9760773,4.7642939 L9.9580602,4.7629328 L9.840048,4.7479607 L9.7387018,4.7375256 L9.646364,4.7062204 L9.6211401,4.6930631 L9.5891597,4.6953316 L9.512587,4.6699245 L9.4756519,4.6740078 L9.4220509,4.6907946 L9.3616936,4.7202851 L9.3004354,4.7216462 L9.2391772,4.7125722 L9.1671088,4.6744615 L9.1085531,4.6154806 L9.0617086,4.5660274 L9.025224,4.5637589 L8.996847,4.5506016 L8.9738752,4.4734728 L8.9531555,4.3459833 L8.9531555,4.3459833 L8.9531555,4.3459833 L8.9590111,4.2956227 L8.9887393,4.228929 L9.028377,4.191272 L9.074771,4.16405 L9.1103548,4.1050691 L9.1333267,4.0678658 L9.1725139,4.0542548 L9.2418798,4.0533474 L9.3364696,4.0370143 L9.4134928,3.9966351 L9.4594365,3.9626076 L9.4963716,3.9263117 L9.5941144,3.8473681 L9.7405035,3.7625263 L9.8364446,3.7239619 L9.9265302,3.6962863 L10.0630099,3.6549997 L10.1859767,3.5878522 L10.3702017,3.5220658 L10.4634403,3.4776033 L10.6309994,3.4159003 L10.6981132,3.4022893 L10.7359492,3.4077337 L10.7945048,3.4059189 L10.9035083,3.3977523 L10.9985486,3.4095485 L11.0742205,3.3959375 L11.1264701,3.3786969 L11.1422351,3.3537435 L11.1751164,3.309281 L11.2003403,3.3029292 L11.2224113,3.2938552 L11.2426805,3.2743462 L11.2134027,3.2788831 L11.1913318,3.2884108 L11.1498924,3.3074662 L11.114759,3.3206235 L11.0877333,3.3265215 L11.0575547,3.3197161 L11.0372854,3.3011144 L11.0066563,3.2897719 L10.9746759,3.2743462 L10.9553075,3.2512075 L10.9309844,3.2366891 L10.9035083,3.2267078 L10.8719784,3.2126431 L10.8359441,3.1858748 L10.7427056,3.1241718 L10.724238,3.0570243 L10.7089235,2.9467754 L10.7089235,2.9467754 L10.7089235,2.9467754 L10.7174816,2.9027666 L10.7156799,2.8723688 L10.7156799,2.8723688 L10.7156799,2.8723688 L10.7485611,2.8510449 L10.7728842,2.8197397 L10.7981082,2.7866197 L10.7773885,2.7326295 L10.7476603,2.6895281 L10.7476603,2.6895281 L10.7476603,2.6895281 L10.7787398,2.6223806 L10.8152245,2.5851773 L10.8300886,2.5266501 L10.82108,2.4799191 L10.82108,2.4799191 L10.82108,2.4799191 L10.8890946,2.3596888 L10.933687,2.342902 L11.0314299,2.3097819 L11.1278214,2.2607824 L11.1818728,2.1895517 L11.2458335,2.1523484 L11.3012362,2.1478114 L11.3390721,2.1183209 L11.3543867,2.1006267 L11.3129473,2.057979 L11.292678,2.0293959 L11.243131,1.9844797 L11.217907,1.9590726 L11.1782694,1.9522671 L11.1413343,1.919147 L11.1111556,1.890564 L11.1111556,1.890564 L11.1111556,1.890564 L11.1138582,1.8710549 L11.0868325,1.8420182 L11.0625094,1.8161574 L11.0548521,1.8234165 L11.0575547,1.8424719 L11.0584555,1.8715086 L11.0584555,1.8715086 L11.0584555,1.8715086 L11.055753,1.9082583 L11.0602572,1.9205081 L11.0602572,1.9205081 L11.0602572,1.9205081 L11.0512487,1.9459153 L11.0309794,1.9617948 L11.0093589,1.9985444 L10.9980982,2.0121554 L10.9751264,2.0085258 L10.9571093,2.0166923 L10.9170212,2.0679603 L10.914769,2.0915527 L10.9192733,2.1246727 L10.9246784,2.1464503 L10.9246784,2.1464503 L10.9246784,2.1464503 L10.9125169,2.1945424 L10.9071118,2.2113292 L10.8940493,2.226755 L10.8751314,2.2335605 L10.868375,2.2340142 L10.8629698,2.2508011 L10.8571143,2.259875 L10.8472048,2.2684953 L10.8138732,2.2898192 L10.7863971,2.3016154 L10.7656774,2.3056986 L10.7535158,2.3256614 L10.717932,2.3401798 L10.7053201,2.3506149 L10.7062209,2.3769294 L10.7129773,2.4109568 L10.7129773,2.4109568 L10.7129773,2.4109568 L10.7057705,2.4427158 L10.6868525,2.4581415 L10.6764927,2.4871783 L10.6602773,2.5130391 L10.6373054,2.529826 L10.5922626,2.5407148 L10.5701917,2.5488813 L10.5598318,2.5797329 L10.5584806,2.6092233 L10.5350583,2.623288 L10.4931685,2.6269176 L10.4449727,2.6441582 L10.3958761,2.6623061 L10.3634453,2.6636672 L10.3449777,2.6759171 L10.3138982,2.6849911 L10.2859717,2.7117593 L10.271558,2.7326295 L10.2544417,2.7820827 L10.2247135,2.8097583 L10.1985887,2.8229156 L10.1449877,2.8415172 L10.1143586,2.8623874 L10.0769731,2.8859798 L10.0323808,2.9150165 L9.990491,2.9272664 L9.9567089,2.9281738 L9.9107652,2.9277201 L9.8468045,2.9240905 L9.8053651,2.9145628 L9.7783394,2.9059425 L9.7391522,2.9018592 L9.6941094,2.8818965 L9.6504179,2.8573967 L9.6148341,2.8415172 L9.5724939,2.7911566 L9.5445673,2.7612125 L9.51574,2.7231018 L9.4932186,2.7085834 L9.4923177,2.7203796 L9.5013263,2.74987 L9.5170912,2.7893419 L9.5242981,2.801138 L9.5242981,2.801138 L9.5242981,2.801138 L9.5080827,2.8133879 L9.4887143,2.8837113 L9.4914168,2.9340719 L9.5170912,2.9771733 L9.5472699,3.0175525 L9.5986187,3.0733575 L9.6166358,3.1241718 L9.6287974,3.188597 L9.6287974,3.188597 L9.6287974,3.188597 L9.6148341,3.2557445 L9.5995195,3.3011144 L9.5981683,3.322892 L9.6071768,3.3396788 L9.6152845,3.3705304 L9.6152845,3.3705304 L9.6152845,3.3705304 L9.603123,3.4199836 L9.5729443,3.4839551 L9.5224964,3.5570007 L9.4679946,3.6196111 L9.4472749,3.6359443 L9.4274561,3.6445646 L9.3963766,3.6645273 L9.3783594,3.6722402 L9.3630449,3.6690643 L9.3522346,3.6754161 L9.3517842,3.6940178 L9.3603423,3.7021844 L9.3603423,3.7021844 L9.3603423,3.7021844 L9.3594415,3.7130731 L9.3207047,3.7334896 L9.3076423,3.7380266 L9.2878234,3.7339433 L9.2734097,3.7461932 L9.2630499,3.7756836 L9.2445824,3.7951927 L9.206296,3.8074426 L9.1702617,3.8147017 L9.1594515,3.8024519 L9.1369301,3.8047204 L9.1148591,3.8119795 L9.0986437,3.8274053 L9.0626095,3.8487292 L9.0274761,3.870053 L9.0054051,3.8777659 L8.9806316,3.8764048 L8.9531555,3.8986361 L8.9319854,3.8954602 L8.9054101,3.9217747 L8.8585656,3.9326635 L8.8193784,3.9358394 L8.7982083,3.9226821 L8.7738852,3.9117933 L8.7401031,3.9058953 L8.71578,3.9136081 L8.6806466,3.9072563 L8.6491167,3.9127007 L8.6270457,3.9313024 L8.6135329,3.9476356 L8.5901106,3.9558021 L8.5820029,3.999811 L8.5783995,4.0324773 L8.5964166,4.061514 L8.5964166,4.061514 L8.5964166,4.061514 L8.5955157,4.0846527 L8.5901106,4.1018932 L8.5770482,4.1023469 L8.5752465,4.1273004 L8.5693909,4.1427262 L8.5590311,4.1826517 L8.5504729,4.210781 L8.5446174,4.2325586 L8.5590311,4.2507065 L8.5892097,4.2643175 L8.6022722,4.293808 L8.6022722,4.293808 L8.6022722,4.293808 L8.5950653,4.3137707 L8.5883089,4.3355483 L8.609479,4.3627702 L8.609479,4.3627702 L8.609479,4.3627702 L8.6027226,4.3899922 L8.5806516,4.3967976 L8.5752465,4.4217511 L8.5743456,4.4380843 L8.5320054,4.4639451 L8.5013763,4.4947966 L8.4536309,4.5238334 L8.43156,4.5401665 L8.4013813,4.5306389 L8.3653471,4.5143057 L8.3563385,4.5233797 L8.3117462,4.5097687 L8.2815675,4.4952503 L8.2685051,4.4988799 L8.2360743,4.5256482 L8.2329213,4.5428887 L8.206346,4.5437961 L8.206346,4.5637589 L8.206346,4.5637589 L8.196887,4.5977863 L8.1797708,4.6268231 L8.1712127,4.6395266 L8.1806716,4.6640264 L8.1910315,4.6853502 L8.1910315,4.6853502 L8.1910315,4.6853502 L8.1905811,4.7284517 L8.2207597,4.7838029 L8.2297683,4.8119323 L8.2311196,4.8328024 L8.2311196,4.8328024 L8.2311196,4.8328024 L8.2261649,4.8559411 L8.2085982,4.9040332 L8.1856263,4.929894 L8.1685101,4.9376069 L8.156799,4.9285329 L8.1450878,4.9208201 L8.1432861,4.9339773 L8.1576998,4.9729955 L8.1622041,5.0029396 L8.1622041,5.0029396 L8.1622041,5.0029396 L8.1531955,5.0315226 L8.1369801,5.0387818 L8.1383314,5.0700871 L8.1572494,5.0877813 L8.1653571,5.1240772 L8.1788699,5.1367808 L8.1788699,5.1367808 L8.1788699,5.1367808 L8.1676092,5.166725 L8.1806716,5.1857803 L8.215805,5.2198077 L8.2342726,5.2570111 L8.2423803,5.2973903 L8.2423803,5.2973903 L8.2423803,5.2973903 L8.2414794,5.3223437 L8.2455333,5.395843 L8.2856213,5.4729718 L8.303188,5.5369434 L8.303188,5.5369434 L8.303188,5.5369434 L8.3013863,5.5981928 L8.2667034,5.6453775 L8.1856263,5.7129786 L8.1495921,5.7755891 L8.0833792,5.8372922 L8.0306791,5.8717733 L8.0104099,5.8881065 L7.9496021,5.9366523 L7.8478054,6.0110589 L7.7518643,6.0686787 L7.6415094,6.1394558 L7.5487213,6.1839183 L7.5194435,6.1911774 L7.4108903,6.2070569 L7.3982784,6.2079643 L7.3762074,6.1934459 L7.3482809,6.1308355 L7.3329663,6.1303818 L7.2780141,6.1503445 L7.2059456,6.1684925 L7.1604524,6.1771128 L7.0717181,6.179835 L6.9699214,6.1698536 L6.8005605,6.1054283 L6.5965167,6.0201329 L6.485261,5.9643279 L6.4330114,5.9430041 L6.3032881,5.9148747 L6.2510385,5.8962731 L6.2123017,5.8486346 L6.188429,5.8368385 L6.1415845,5.8735881 L6.1420349,5.9103377 L6.172664,5.9856518 L6.1879786,6.0341976 L6.2496872,6.0750305 L6.2866223,6.1290207 L6.304189,6.1290207 L6.3776087,6.1839183 L6.4523798,6.2374547 L6.4902157,6.2365474 L6.5429158,6.2179457 L6.6257945,6.1839183 L6.6690356,6.1848257 L6.7329963,6.2229364 L6.8347931,6.3381759 L6.9316351,6.3967031 L7.0091087,6.4906188 L7.0212702,6.5427942 L7.0325309,6.6090343 L7.0424403,6.6739132 L7.0523497,6.7442366 L7.0595566,6.791875 L7.0595566,6.791875 L7.0595566,6.791875 L7.0532506,6.8517633 L7.0500976,6.9406883 L7.0424403,7.0200856 L7.0320805,7.0681777 L7.0001001,7.1135476 L6.97893,7.1475751 L6.966318,7.2115466 L6.9496522,7.2619072 L6.9149692,7.3050086 L6.8811871,7.3394898 L6.8429008,7.3553692 L6.8221811,7.3748783 L6.8145238,7.3966559 L6.7744357,7.4737847 L6.7023672,7.6035426 L6.6690356,7.6656994 L6.6217407,7.7097082 L6.5762474,7.7237729 L6.5460688,7.7237729 L6.5046294,7.702449 L6.482108,7.6720512 L6.482108,7.6720512 L6.482108,7.6321257 L6.4645413,7.6117092 L6.416796,7.5908391 L6.3478805,7.5536357 L6.3068915,7.5481913 L6.282118,7.5559042 L6.2704069,7.5658856 L6.2713077,7.5980982 L6.3023873,7.6248665 L6.3442771,7.6343942 L6.3649967,7.6811252 L6.3933737,7.7378376 L6.4460738,7.7677817 L6.4929183,7.8058924 L6.5100345,7.8458179 L6.5100345,7.8458179 L6.5100345,7.8458179 L6.5046294,7.8934563 L6.4717482,7.9565205 L6.4046344,8.0177699 L6.2897753,8.1402686 L6.2537411,8.2033328 L6.2415795,8.2237493 L6.1996897,8.2863597 L6.119964,8.3489702 L6.0614083,8.3829976 L5.9506031,8.4011456 L5.9361894,8.3770995 L5.9235774,8.3743773 L5.8488064,8.3934327 L5.7970072,8.4551358 L5.7195336,8.5231906 L5.6627796,8.5581255 L5.5848556,8.5962362 L5.2600971,8.4973298 L5.226315,8.4633024 L4.723187,8.4805429 L4.6894049,8.3784606 L4.3772584,8.3126743 L4.3461789,8.350785 L4.2506882,8.3675719 L4.2493369,8.5749123 L4.2380762,8.58444 L4.2223112,8.5876159 L4.2083479,8.5894307 L4.1970872,8.5948751 L4.18943,8.6044028 L4.1763675,8.6098472 L4.1678094,8.6193748 L4.1565487,8.6293562 L4.1398829,8.6293562 L4.1182623,8.6543097 L4.1074521,8.6670132 L4.1006957,8.6815316 L4.0732196,8.6856149 L4.0723187,8.6874297 L4.076823,8.6937815 L4.076823,8.6937815 L4.076823,8.6937815 L4.0763726,8.69605 L4.064211,8.7064851 L4.0493469,8.7146516 L4.0488965,8.7355218 L4.0448426,8.7400588 L4.045293,8.7913268 L4.045293,8.7913268 L4.045293,8.7913268 L4.0263751,8.8131043 L3.948451,8.9088348 L3.9849357,8.9228995 L4.1529453,8.9905007 L4.1696111,9.1606378 L4.2533907,9.330775 L4.2871728,9.330775 L4.2871728,9.330775 L4.2871728,9.3648024 L4.2871728,9.3648024 L4.2056454,9.4804957 L4.1997898,9.5027269 L4.2011411,9.5549023 L4.2011411,9.5549023 L4.2011411,9.5549023 L4.1786197,9.5989111 L4.1700616,9.6007259 L4.1448376,9.6070777 L4.1155598,9.6188739 L4.095741,9.6379292 L4.0790751,9.657892 L4.0511486,9.6792159 L4.0470947,9.691012 L4.0398879,9.7345671 L4.0430409,9.7658724 L4.0628597,9.8048905 L4.0628597,9.8048905 L4.0628597,9.8048905 L4.0115109,9.9146857 L3.9461989,10.0172217 L3.8344928,10.1197576 L3.8056654,10.2386268 L3.7660277,10.3075891 L3.7574696,10.3810883 L3.6354036,10.5766326 L3.564236,10.5943269 L3.514689,10.6492245 L3.408388,10.7308903 L3.3646965,10.8152783 L3.3286622,10.8320652 L3.3214554,10.8919535 L3.2692057,10.9268883 L3.211551,11.0235262 L3.1363295,11.1755154 L3.0665132,11.216802 L2.9998499,11.1932096 L2.9462489,11.2177094 L2.8575146,11.198654 L2.7741855,11.2009225 L2.727341,11.2240612 L2.7449077,11.26807 L2.7449077,11.26807 L2.7449077,11.26807 L2.7363495,11.3188843 L2.7741855,11.3941983 L2.7827436,11.4291332 L2.839948,11.4450126 L2.9237275,11.4377534 L2.9786797,11.4740494 L3.1129073,11.4740494 L3.164256,11.4377534 L3.2138031,11.4400219 L3.8016115,11.3755967 L4.0137631,11.3678838 L4.2024924,11.4899288 L4.4750013,11.5203267 L4.67364,11.4463737 L4.7889495,11.59065 L4.8159752,11.6382884 L4.857865,11.7117877 L4.9065112,11.7979905 L4.9902908,11.911869 L5.051549,12.0752006 L5.051549,12.0752006 L5.051549,12.0752006 L4.9664181,12.2090418 L4.7623743,12.3124852 L4.754717,12.5869732 L4.6565237,12.8537482 L4.5655373,13.0211632 L4.4637406,13.0683479 L4.3002352,13.2330406 L4.1178119,13.4140666 L3.7263901,13.5392875 L3.5119864,13.6041665 L3.5250488,13.699897 L3.5250488,13.699897 L3.5250488,13.699897 L3.4277564,13.826479 L3.2394775,14.0256529 L3.0876833,14.1926142 L3.0656123,14.2897058 L3.0683149,14.372279 L3.0683149,14.372279 L3.0683149,14.4153804 L3.0822782,14.4516763 L3.0993944,14.5424161 L3.1845253,14.7243495 L3.1948851,14.7574695 L3.2367749,14.8241633 L3.2813673,14.8758849 L3.3426255,14.9139957 L3.4061358,14.9534675 L3.4993744,15.0119947 L3.5164907,15.0800495 L3.6493669,15.17578 L3.7164807,15.1839466 L3.7430559,15.171243 L3.8002602,15.1281416 L3.84215,15.1254194 L3.879986,15.1363082 L4.0155648,15.2560848 L4.0191682,15.3132508 L4.0191682,15.3132508 L4.0191682,15.3132508 L3.9885391,15.3785835 L4.0205195,15.423046 L4.0930384,15.4711381 L4.1092538,15.5133321 L4.135829,15.5491744 L4.1439367,15.6018035 L4.1597017,15.6521641 L4.1885291,15.7115986 L4.2371753,15.7782924 L4.2795155,15.7714869 L4.3497823,15.730654 L4.4493269,15.6925433 L4.4844602,15.6344698 L4.5105851,15.5881925 L4.5623843,15.5668686 L4.5898604,15.5224061 L4.6191382,15.4384718 L4.6362544,15.3413802 L4.6236425,15.2864826 L4.6236425,15.2864826 L4.6236425,15.2864826 L4.645263,15.2560848 L4.8191282,15.2011872 L4.9150693,15.1875762 L5.0227216,15.1880299 L5.0704669,15.1866688 L5.129473,15.2066316 L5.1808218,15.2039094 L5.1943346,15.1671597 L5.226315,15.1449285 L5.2983835,15.1367619 L5.3650468,15.147197 L5.4808068,15.2175203 L5.6366548,15.2978251 L5.6717882,15.3450098 L5.6717882,15.3450098 L5.6717882,15.3450098 L5.6605275,15.3645188 L5.6961113,15.4135183 L5.7303438,15.4144257 L5.7375507,15.4189627 L5.7970072,15.4802121 L5.8573645,15.5627853 L5.8744808,15.6058867 L5.8933987,15.679386 L5.8933987,15.679386 L5.8933987,15.679386 L5.8528602,15.7011635 L5.8654722,15.7256633 L5.932586,15.7256633 L5.9334868,15.7841905 L5.9334868,15.7841905 L5.9334868,15.7841905 L5.907362,15.8395418 L5.9127671,15.8672174 L5.9240278,15.8926246 L5.9771783,15.9184854 L6.0253741,15.9389019 L6.0501476,15.9978827 L6.1159101,16.0545951 L6.1645563,16.0527803 L6.2123017,16.0473359 L6.2686052,16.0659376 L6.2686052,16.1339925 L6.6375056,16.2701022 L6.6375056,16.338157 L6.7046194,16.338157 L6.7046194,16.338157 L6.7046194,16.338157 L6.6712877,16.4402393 L6.8055152,16.4738131 L6.8055152,16.5078405 L6.872629,16.5078405 L6.9753266,16.692496 L7.0658626,16.8031986 L7.097843,16.8698923 L7.097843,16.8698923 L7.097843,16.8698923 L7.0874831,16.9275121 L7.1000951,16.9651691 L7.1289225,16.9692524 L7.1636054,16.9597248 L7.2018918,16.968345 L7.2118012,16.9874004 L7.234773,17.0209741 L7.2577449,17.0350388 L7.3352185,17.0413906 L7.4320605,17.093566 L7.4739503,17.1325841 L7.6712377,17.2056296 L7.759972,17.1883891 L7.950503,16.8540129 L8.0725689,16.8376797 L8.6851509,17.0686125 L8.8806366,17.2147036 L9.0049547,17.3077119 L9.0972924,17.3803038 L9.1490916,17.4234052 L9.3288124,17.5749407 L9.3544868,17.6842822 L9.4026825,17.7831886 L9.4860117,17.8017902 L9.5544768,17.7559666 L9.6567239,17.7341891 L9.743206,17.6910876 L9.8017617,17.6389123 L9.8080677,17.5486261 L9.8765327,17.4937286 L9.9688704,17.42522 L10.0332816,17.4474513 L10.0855313,17.4156923 L10.0612081,17.289564 L10.0612081,17.289564 L10.0612081,17.289564 L10.1035484,17.1861206 L10.1449877,17.0853994 L10.1922827,17.0082706 L10.2949802,16.948836 L10.4571343,16.9692524 L10.5760472,17.0518257 L10.6710875,17.1471025 L10.7174816,17.1965557 L10.7188329,17.2333053 L10.7188329,17.2333053 L10.7188329,17.2333053 L10.6544217,17.2609809 L10.5819028,17.3204155 L10.5129873,17.476488 L10.6161353,17.5622371 L10.7404534,17.5631445 L10.7913518,17.6820137 L10.836845,17.868484 L10.8408989,18.0567691 L10.8408989,18.0567691 L10.8408989,18.0567691 L10.6832491,18.0522321 L10.6107302,18.1130278 L10.5913618,18.1711013 L10.6089285,18.2432394 L10.6201892,18.349405 L10.649467,18.5689953 L10.6530704,18.5962173 L10.6530704,18.5962173 L10.6530704,18.5962173 L10.5508233,18.6365965 L10.5611831,18.7404936 L10.6409089,18.8421222 L10.6409089,18.8421222 L10.6409089,18.8421222 L10.5976678,19.0317684 L10.5949652,19.0426572 L10.5692908,19.0453794 L10.5364096,19.0322221 L10.4940694,19.0254166 L10.4301086,19.0213333 L10.3729043,19.0648884 L10.3584906,19.0898419 L10.330564,19.1374803 L10.3089435,19.1696929 L10.2846204,19.1869335 L10.2693058,19.180128 L10.2548921,19.171054 L10.2260648,19.1647022 L10.2021921,19.2082573 L10.1868775,19.246368 L10.1738151,19.278127 L10.1612031,19.2894695 L10.1152595,19.3012656 L10.0850808,19.3153303 L10.0017517,19.3665983 L9.9567089,19.3924591 L9.8990541,19.4192274 L9.793654,19.4718565 L9.7179821,19.4718565 L9.671588,19.4845601 L9.6423102,19.4972636 L9.6450128,19.511782 L9.7130274,19.5957163 L9.7459086,19.6537898 L9.7855463,19.7009745 L9.8445523,19.7536036 L9.9283319,19.8071401 L9.962114,19.8325472 L9.962114,19.8325472 L9.962114,19.8325472 L9.9607627,19.8624914 L9.9729243,19.8869911 L10.0436415,19.950509 L10.0508483,19.9872586 L10.0508483,19.9872586 L10.0508483,19.9872586 L10.0449927,20.0203786 L10.0377859,20.0671097 L10.0220209,20.1124796 L10.0031029,20.1505903 L9.990491,20.1868862 L9.99995,20.2449597 L10.0206696,20.3016721 L10.0954407,20.3924119 L10.0958911,20.5217161 L11.3043892,20.6061041 L11.3381713,20.4532076 L11.3715029,20.4532076 L11.3881688,20.5893173 L11.4723988,20.6233447 L11.4723988,20.9295916 L11.4723988,20.9295916 L11.4723988,20.9295916 L10.8012612,21.0829419 L10.5661378,21.2362921 L10.5999199,21.3724019 L10.5999199,21.3724019 L10.5999199,21.3724019 L10.5328062,21.3724019 L10.5328062,21.4064293 L10.5999199,21.4064293 L10.5999199,21.4064293 L10.5999199,21.4064293 L10.5328062,21.5765664 L10.4990241,21.5765664 L10.4994745,21.6786487 L10.4994745,21.6786487 L10.4994745,21.6786487 L10.4129923,21.765759 L10.4656924,21.9508682 L10.4994745,21.9508682 L10.4994745,21.9508682 L10.4994745,21.9508682 L10.4828087,22.1550328 L10.5332566,22.257115 L10.5665883,22.257115 L10.5665883,22.3251699 L10.9161203,22.6885829 L10.9372904,22.9458302 L10.9922426,23.1881055 L11.0498974,23.2874656 L11.0895351,23.362326 L11.1007958,23.3881868 L11.1143086,23.5002505 L11.1755668,23.8146639 L11.1773685,23.8287286 L11.1872779,23.9203758 L11.1935839,23.9802641 L11.1935839,23.9866159 L11.1940343,24.0832538 L11.1940343,24.0832538 L11.1940343,24.0832538 L11.1652069,24.1381514 L11.0881838,24.1844287 L11.014764,24.2320671 L10.9643161,24.2583816 L10.8553125,24.2651871 L10.8508083,24.2892332 L10.8791852,24.3382327 L10.8791852,24.3382327 L10.8791852,24.3382327 L10.8372954,24.3777045 L10.8796357,24.4198985 L10.8796357,24.4198985 L10.8796357,24.4198985 L10.8733297,24.8545422 L10.8458536,24.8867548 L10.8129723,24.9262266 L10.7535158,25.1703167 L10.6981132,25.4792858 L10.6269456,25.5237483 L10.5134378,26.0445948 L10.5850558,26.136242 L10.8057655,26.2596482 L10.9377409,26.2415002 L11.0877333,26.2605556 L11.1746659,26.3177217 L11.3480807,26.3767026 L11.4787048,26.3444899 L11.6124819,26.4025634 L11.683199,26.3962116 L11.7129273,26.424341 L11.7777889,26.430239 L11.8183274,26.4057393 L11.9692208,26.3943968 L12.0385867,26.4601832 L12.1102047,26.5191641 L12.2313698,26.5917559 L12.4223512,26.6706995 L12.5556779,26.7237823 L12.5556779,26.7237823 L12.5556779,26.7237823 L12.5529753,26.9070768 L12.5498223,27.1212227 L12.4682949,27.2909062 L12.2777639,27.3448964 L11.9701216,27.3430816 L11.7291427,27.3793775 L11.4886142,27.3095078 L11.2197087,27.4506082 L10.9098143,27.3430816 L10.824233,27.303156 L10.73685,27.3004338 L10.6679345,27.3961644 L10.6499174,27.5295519 L10.4215505,27.8593911 L10.2413793,28.0785277 L10.1819228,28.1506659 L10.155798,28.1828785 L10.0332816,28.2554704 L9.9409439,28.3108217 L9.9026575,28.2935811 L9.8648216,28.2849608 L9.8229318,28.3003866 L9.7242881,28.2559241 L9.6319504,28.2055635 L9.5657375,28.3017477 L9.5143887,28.3176272 L9.468445,28.3593675 L9.4544818,28.4201631 L9.4648416,28.4850421 L9.4846604,28.5335879 L9.5251989,28.5721523 L9.6238427,28.6293184 L9.7499625,28.6442905 L9.7990591,28.6710587 L9.8418498,28.7273174 L9.9274311,28.8225942 L10.0418397,28.8942787 L10.0801261,28.9196858 L10.0949902,28.9401023 L10.1103048,28.9750371 L10.1395826,29.0258514 L10.2161553,29.1292948 L10.2967819,29.2245716 L10.3846154,29.352061 L10.4323607,29.4201159 L10.4323607,29.4201159 L10.4323607,29.4201159 L10.4251539,29.4378101 L10.4697463,29.5063187 L10.4886642,29.5734662 L10.5882088,29.5870771 L10.6692858,29.6142991 L10.7503628,29.6283638 L10.8269356,29.720011 L10.939993,29.8497689 L10.9769281,29.9096572 L11.108453,29.9196386 L11.2070967,29.9191849 L11.3251089,29.9368791 L11.4404184,29.8992221 L11.5129373,29.9414161 L11.6012212,29.9804342 L11.6606776,29.9981285 L11.7228367,29.9940452 L11.773735,29.9709065 L11.8142736,29.9631937 L11.9539062,30.0961275 L12.0849807,30.2372279 L12.1574996,30.3520138 L12.2399279,30.5452896 L12.2876733,30.7018157 L12.3502828,30.7812131 L12.3552375,30.7930093 L12.3701016,30.8315737 L12.3984786,30.9427299 L12.408388,30.9636001 L12.4498273,31.0289328 L12.4777539,31.0765712 L12.5088334,31.1292003 L12.5363095,31.1831904 L12.5687403,31.2249308 L12.6214404,31.2489768 L12.6615285,31.3224761 L12.6971123,31.4163918 L12.7525149,31.5012335 L12.8151244,31.7031296 L12.8466543,31.7344348 L12.9367399,31.7344348 L12.9786297,31.7344348 L13.0034032,31.7734529 L13.0308793,31.8324338 L13.0488965,31.8714519 L13.1601521,32.0329688 L13.1826735,32.0787924 L13.2623993,32.1386807 L13.361043,32.2003838 L13.4241029,32.353734 L13.5344577,32.4027335 L13.7299434,32.4118075 L13.8164256,32.3950207 L13.9619138,32.4993714 L14.0668635,32.6223239 L14.15785,32.6722308 L14.5001752,33.3042336 L14.5609829,33.2933448 L14.6569241,33.2996966 L14.8163756,33.3418906 L14.9141184,33.3545942 L14.9717732,33.3917975 L15.0330314,33.4172046 L15.0902357,33.4040474 L15.1501426,33.4897965 L15.213653,33.5079445 L15.2222111,33.6032213 L15.2591462,33.7647381 L15.3582403,34.2651682 L15.3690506,34.766052 L15.4145438,34.8490789 L15.469496,34.9248466 L15.7496622,34.9842812 L15.8145238,34.9969848 L15.8708273,35.0532435 L15.8708273,35.0532435 L15.8708273,35.0532435 L15.8681247,35.1666682 L15.8284871,35.2868985 L15.8212802,35.3454257 L15.8316401,35.4134805 L15.9185726,35.5636549 L15.9802813,35.6112933 L16.0784746,35.6416912 L16.1559482,35.6875148 L16.2289175,35.75194 L16.2289175,35.75194 L16.2289175,35.75194 L16.2266653,35.8195412 L16.2892748,35.8735314 L16.3460287,35.9125495 L16.3879185,35.9470306 L16.4207998,35.9969375 L16.4523297,36.0817793 L16.4892648,36.2138057 L16.5113358,36.349008 L16.5113358,36.349008 L16.5113358,36.349008 L16.5054802,36.629394 L16.6225915,36.816318 L16.7090736,36.9850941 L16.7640258,37.1085002 L16.8225815,37.2650264 L16.8225815,37.2650264 L16.8225815,37.2650264 L16.7415044,37.6579298 L16.7329463,37.879335 L16.4753015,38.8003441 L16.1131575,39.2227379 L15.0803263,39.471365 L14.9893399,39.4949573 L14.8334918,39.4731798 L14.742055,39.4949573 L14.4821581,39.4060323 L13.851559,39.1428868 L13.8114709,39.1261 L13.5718433,38.874297 L13.5718433,38.874297 L13.5718433,38.874297 L13.6096792,38.6810212 L13.5335569,38.6234014 L13.4344627,38.5871055 L13.1844753,38.5657816 L13.0786247,38.5725871 L12.6881037,38.3811261 L12.5304539,38.2853956 L12.3885691,38.192841 L12.1448876,38.1311379 L11.9111156,38.029963 L11.5413142,37.9496583 L11.2940293,37.8933996 L11.1719634,37.8103727 L11.1719634,37.8103727 L11.1719634,37.8103727 L11.2043942,37.7091978 L11.1471898,37.6370597 L11.0899855,37.6298005 L10.9575597,37.4696447 L10.8963015,37.4383395 L10.7485611,37.1066854 L10.7485611,37.1066854 L10.7485611,37.1066854 L10.7512637,36.9742053 L10.752615,36.3603505 L10.6679345,36.2251482 L10.6679345,36.2251482 L10.6679345,36.2251482 L10.714779,36.1325936 L10.7872979,35.8154579 L10.8188279,35.6684594 L10.8454031,35.5441459 L10.8535108,35.3703791 L10.7809919,35.1789181 L10.4521796,34.9289299 L10.3814624,34.770589 L10.3814624,34.770589 L10.3814624,34.770589 L10.4220009,34.6049888 L10.0630099,34.5092583 L9.9224764,34.5469153 L9.7801411,34.5342117 L9.6702367,34.4738698 L9.5688904,34.4511848 L9.3783594,34.4625273 L9.2067464,34.5024528 L9.0864822,34.5042676 L8.9400931,34.514249 L8.8009109,34.5206008 L8.6356038,34.4275925 L8.4527301,34.4511848 L8.3261599,34.5228693 L8.0694159,34.5827575 L7.8779841,34.5723225 L7.6554727,34.4865733 L7.5541264,34.509712 L7.4707973,34.5355728 L7.353686,34.523323 L7.1825234,34.6204146 L7.0879335,34.6172387 L6.9582103,34.6335718 L6.850558,34.67259 L6.7514639,34.6467291 L6.7023672,34.6934601 L6.7118262,34.7220432 L6.7816426,34.7910054 L6.7816426,34.7910054 L6.7816426,34.906245 L6.7816426,34.906245 L6.7302938,34.9833738 L6.7478605,35.0350955 L6.8320905,35.0677618 L7.0019018,35.037364 L7.1176618,35.1131318 L7.2388269,35.1716589 L7.5271007,35.5600253 L7.7230369,36.1407601 L7.7523147,36.4234147 L7.769431,36.4882936 L7.859967,36.5254969 L7.925279,36.7437262 L7.9644662,36.9061505 L7.9644662,36.9061505 L7.9644662,36.9061505 L7.9554577,36.9692146 L7.9496021,37.0145845 L8.06311,37.1207501 L8.1914819,37.3993213 L8.2694059,37.6307079 L8.2694059,37.6307079 L8.2694059,37.6307079 L8.2630999,37.6860592 L8.4193984,37.7110126 L8.5671388,37.7078367 L8.60002,37.6765315 L8.6657825,37.6887813 L8.7027176,37.6806148 L8.9328862,38.0113614 L8.9869376,38.1021012 L9.0346829,38.2146186 L9.0572043,38.3176082 L9.0594565,38.3616171 L9.0594565,38.3616171 L9.0594565,38.3616171 L8.9909914,38.3743206 L8.9432461,38.444644 L8.8148741,38.5031712 L8.7720835,38.6193181 L8.7770382,38.7155023 L8.8409989,38.7400021 L9.071618,38.6714935 L9.2243131,38.6070683 L9.3549372,38.4977268 L9.5954657,38.4042648 L9.76843,38.3144324 L9.8751814,38.1533692 L10.0301286,38.1901188 L10.1301236,38.1692487 L10.2116511,38.1184344 L10.2247135,38.2309517 L10.2949802,38.2722383 L10.3562384,38.2282295 L10.4602873,38.2545441 L10.6138832,38.3956445 L10.724238,38.5689575 L10.7323457,38.5825685 L10.8706271,38.8207605 L10.9611631,39.1174797 L10.9611631,39.1174797 L10.9611631,39.1174797 L10.8935989,39.2068584 L10.8566638,39.397412 L10.7809919,39.5249015 L10.6602773,39.5639196 L10.6053251,39.8002968 L10.6228917,40.0820439 L10.6228917,40.0820439 L10.6228917,40.0820439 L10.5755968,40.2249591 L10.5900105,40.4454569 L10.5900105,40.4454569 L10.5900105,40.4454569 L10.5566788,40.6396401 L10.4909164,40.8288326 L10.3774085,41.0184788 L10.2179571,41.1827178 L10.0359842,41.4499466 L9.865272,41.6482131 L9.7369001,41.8251557 L9.5860067,41.95446 L9.5206947,41.9335898 L9.281067,42.0710606 L9.0630599,42.1354859 L9.0292778,42.1354859 L8.928382,42.2035407 L8.8950503,42.2035407 L8.8279365,42.2375682 L8.5707422,42.3623354 L8.5324558,42.3555299 L8.5266003,42.3940944 L8.4919173,42.4077053 L8.4585857,42.4589733 L8.3730044,42.4993525 L8.2743606,42.6154995 L8.1207647,42.6649527 L7.8874431,42.8505156 L7.7563686,42.9126724 L7.7108753,43.0147547 L7.7901506,43.076004 L7.9550073,43.0719208 L8.1991392,42.8718395 L8.4540814,42.9575886 L8.9162204,43.2488634 L9.4580852,43.6159059 L10.0152645,43.3391495 L10.387318,43.2937796 L10.5634353,42.9907086 L10.5377609,42.9421628 L10.5377609,42.9421628 L10.5377609,42.9421628 L10.7535158,42.7516092 L10.9170212,42.6454436 L10.9890896,42.501621 L11.5953656,42.8446175 L11.6701366,43.3377884 L11.6701366,43.3377884 L11.6701366,43.3377884 L11.6043742,44.1716873 L11.498974,44.3100655 L11.3381713,44.4098793 L11.2269156,44.634914 L11.3746559,44.9620311 L11.3746559,44.9620311 L11.3746559,44.9620311 L11.3444773,45.2074823 L11.4638406,45.6139966 L11.4638406,45.6139966 L11.4638406,45.6139966 L11.3066413,45.9733263 L11.1161103,46.2196849 L11.1111556,46.3503502 L11.1634052,46.5590518 L11.2755618,46.6919856 L11.4228517,46.8426137 L11.5061809,46.9882511 L11.5142886,47.0971389 L11.5674391,47.1937768 L11.6016716,47.1211849 L11.6498674,47.0095749 L11.6561734,46.8793633 L11.8629198,46.0613439 L11.9917422,45.9869372 L12.2561433,45.619441 L12.4525299,45.4924053 L12.6849507,45.4597389 L12.9439467,45.4021192 L13.2069966,45.5014792 L13.5344577,45.6412186 L13.5813022,45.6611813 L13.6443621,45.6884033 L13.6772434,45.8145316 L13.6772434,45.8145316 L13.6772434,45.8145316 L13.6533707,45.9424747 L13.8110205,46.2600641 L13.8110205,46.2600641 L13.8110205,46.2600641 L13.7560683,46.2759436 L13.6285972,46.7691144 L13.7110255,47.0408802 L13.7740854,47.1216386 L13.7889495,47.2164617 L13.7889495,47.2164617 L13.7889495,47.2164617 L13.7461589,47.3130996 L13.7781392,47.4147282 L13.9952455,47.285424 L14.1709124,47.3085626 L14.2686552,47.2804333 L14.307392,47.2033044 L14.3474801,47.1987675 L14.4546819,47.1234534 L14.5109854,46.9728253 L14.6253941,46.8049567 L14.691607,46.735087 L14.8488064,46.7514202 L15.0028527,46.7731977 L15.3622942,46.9192888 L15.4420199,46.9773623 L15.4420199,46.9773623 L15.4420199,46.9773623 L15.4203994,47.0304451 L15.4640909,47.1102961 L15.4640909,47.1102961 L15.4640909,47.1102961 L15.4316601,47.2051192 L15.5685902,47.4936718 L15.6492168,47.7563636 L15.8253341,47.939658 L16.053701,47.8874826 L16.5622341,48 Z M1.0918372,0 L1.0251739,0.004537 L0.9679696,0.0526291 L0.8729293,0.1964517 L0.8648216,0.3425428 L0.7814924,0.4890876 L0.6814974,0.5716608 L0.6305991,0.5807348 L0.5833041,0.6542341 L0.4959211,0.7204741 L0.3148491,0.7917049 L0.281067,0.8062232 L0.2752115,0.8434266 L0.2292678,0.8556764 L0.1112557,0.8425192 L0.0049547,0.8792688 L0,1.0135637 L0.0346829,1.0367024 L0.0990941,1.1551178 L0.0990941,1.1551178 L0.0990941,1.1551178 L0.0797257,1.298033 L0.0454932,1.3597361 L0.0590061,1.3796989 L0.1238677,1.4055597 L0.2382764,1.3946709 L0.3657475,1.4522907 L0.5049297,1.4128189 L0.5103348,1.3697175 L0.6296982,1.2776166 L0.7598719,1.3043848 L0.8450028,1.3397733 L0.9846354,1.4391334 L1.0391372,1.4477537 L1.2314699,1.5884004 L1.3584906,1.7018252 L1.5287523,1.9046287 L1.5467694,2.0180534 L1.5467694,2.0180534 L1.5467694,2.0180534 L1.4404684,2.2984395 L1.3706521,2.3977996 L1.2134528,2.4835487 L1.184175,2.5193909 L1.187328,2.5443444 L1.187328,2.5443444 L1.187328,2.5443444 L1.0900355,2.656408 L1.0994945,2.8528597 L1.2215605,2.6931577 L1.2940794,2.4522434 L1.3476803,2.4372714 L1.4021821,2.4508824 L1.449477,2.4254752 L1.6035233,2.4595026 L1.6314499,2.5098632 L1.6818978,2.5144002 L1.7048696,2.5470666 L1.7805415,2.5180298 L1.8485561,2.5461592 L1.8521596,2.5085021 L2.0057555,2.2979858 L2.0233222,2.2135977 L2.0701667,2.1296634 L2.2075472,1.9722298 L2.2809669,1.857444 L2.3760072,1.8088982 L2.7012162,1.7281397 L2.7935539,1.6564553 L2.8692258,1.656909 L2.9728242,1.5815949 L3.0133627,1.5239752 L3.0133627,1.4454852 L2.8516591,1.3107366 L2.7692308,1.2971256 L2.6962614,1.2150061 L2.4449227,1.0239988 L2.4079876,0.9600272 L2.4079876,0.9600272 L2.4079876,0.9600272 L2.4219509,0.9486847 L2.4142936,0.9250924 L2.3724038,0.9300831 L2.3066413,0.8493247 L2.0287273,0.6787338 L1.9255793,0.4750229 L1.7764877,0.349802 L1.7197337,0.307608 L1.5985686,0.2808397 L1.4611881,0.1814796 L1.3404734,0.1361097 L1.0918372,0 Z","id":"YT"}})])])
}
var Mayottevue_type_template_id_de12d894_staticRenderFns = []


;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/maps/Mayotte.vue?vue&type=script&lang=js


/* harmony default export */ var Mayottevue_type_script_lang_js = ({
  mixins: [base],
  props: {
    colorStroke: String
  }
});

;// CONCATENATED MODULE: ./src/components/maps/Mayotte.vue?vue&type=script&lang=js
 /* harmony default export */ var maps_Mayottevue_type_script_lang_js = (Mayottevue_type_script_lang_js); 
;// CONCATENATED MODULE: ./src/components/maps/Mayotte.vue





/* normalize component */
;
var Mayotte_component = normalizeComponent(
  maps_Mayottevue_type_script_lang_js,
  Mayottevue_type_template_id_de12d894_render,
  Mayottevue_type_template_id_de12d894_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var Mayotte = (Mayotte_component.exports);
;// CONCATENATED MODULE: ./src/components/maps/index.js









/* harmony default export */ var maps = ({
  France: France,
  FranceReg: FranceReg,
  FranceAcad: FranceAcad,
  Guadeloupe: Guadeloupe,
  Martinique: Martinique,
  Guyane: Guyane,
  Reunion: Reunion,
  Mayotte: Mayotte
});

;// CONCATENATED MODULE: ./node_modules/d3-array/src/ticks.js
const e10 = Math.sqrt(50),
    e5 = Math.sqrt(10),
    e2 = Math.sqrt(2);

function tickSpec(start, stop, count) {
  const step = (stop - start) / Math.max(0, count),
      power = Math.floor(Math.log10(step)),
      error = step / Math.pow(10, power),
      factor = error >= e10 ? 10 : error >= e5 ? 5 : error >= e2 ? 2 : 1;
  let i1, i2, inc;
  if (power < 0) {
    inc = Math.pow(10, -power) / factor;
    i1 = Math.round(start * inc);
    i2 = Math.round(stop * inc);
    if (i1 / inc < start) ++i1;
    if (i2 / inc > stop) --i2;
    inc = -inc;
  } else {
    inc = Math.pow(10, power) * factor;
    i1 = Math.round(start / inc);
    i2 = Math.round(stop / inc);
    if (i1 * inc < start) ++i1;
    if (i2 * inc > stop) --i2;
  }
  if (i2 < i1 && 0.5 <= count && count < 2) return tickSpec(start, stop, count * 2);
  return [i1, i2, inc];
}

function ticks(start, stop, count) {
  stop = +stop, start = +start, count = +count;
  if (!(count > 0)) return [];
  if (start === stop) return [start];
  const reverse = stop < start, [i1, i2, inc] = reverse ? tickSpec(stop, start, count) : tickSpec(start, stop, count);
  if (!(i2 >= i1)) return [];
  const n = i2 - i1 + 1, ticks = new Array(n);
  if (reverse) {
    if (inc < 0) for (let i = 0; i < n; ++i) ticks[i] = (i2 - i) / -inc;
    else for (let i = 0; i < n; ++i) ticks[i] = (i2 - i) * inc;
  } else {
    if (inc < 0) for (let i = 0; i < n; ++i) ticks[i] = (i1 + i) / -inc;
    else for (let i = 0; i < n; ++i) ticks[i] = (i1 + i) * inc;
  }
  return ticks;
}

function tickIncrement(start, stop, count) {
  stop = +stop, start = +start, count = +count;
  return tickSpec(start, stop, count)[2];
}

function tickStep(start, stop, count) {
  stop = +stop, start = +start, count = +count;
  const reverse = stop < start, inc = reverse ? tickIncrement(stop, start, count) : tickIncrement(start, stop, count);
  return (reverse ? -1 : 1) * (inc < 0 ? 1 / -inc : inc);
}

;// CONCATENATED MODULE: ./node_modules/d3-array/src/ascending.js
function ascending(a, b) {
  return a == null || b == null ? NaN : a < b ? -1 : a > b ? 1 : a >= b ? 0 : NaN;
}

;// CONCATENATED MODULE: ./node_modules/d3-array/src/descending.js
function descending(a, b) {
  return a == null || b == null ? NaN
    : b < a ? -1
    : b > a ? 1
    : b >= a ? 0
    : NaN;
}

;// CONCATENATED MODULE: ./node_modules/d3-array/src/bisector.js



function bisector(f) {
  let compare1, compare2, delta;

  // If an accessor is specified, promote it to a comparator. In this case we
  // can test whether the search value is (self-) comparable. We can’t do this
  // for a comparator (except for specific, known comparators) because we can’t
  // tell if the comparator is symmetric, and an asymmetric comparator can’t be
  // used to test whether a single value is comparable.
  if (f.length !== 2) {
    compare1 = ascending;
    compare2 = (d, x) => ascending(f(d), x);
    delta = (d, x) => f(d) - x;
  } else {
    compare1 = f === ascending || f === descending ? f : zero;
    compare2 = f;
    delta = f;
  }

  function left(a, x, lo = 0, hi = a.length) {
    if (lo < hi) {
      if (compare1(x, x) !== 0) return hi;
      do {
        const mid = (lo + hi) >>> 1;
        if (compare2(a[mid], x) < 0) lo = mid + 1;
        else hi = mid;
      } while (lo < hi);
    }
    return lo;
  }

  function right(a, x, lo = 0, hi = a.length) {
    if (lo < hi) {
      if (compare1(x, x) !== 0) return hi;
      do {
        const mid = (lo + hi) >>> 1;
        if (compare2(a[mid], x) <= 0) lo = mid + 1;
        else hi = mid;
      } while (lo < hi);
    }
    return lo;
  }

  function center(a, x, lo = 0, hi = a.length) {
    const i = left(a, x, lo, hi - 1);
    return i > lo && delta(a[i - 1], x) > -delta(a[i], x) ? i - 1 : i;
  }

  return {left, center, right};
}

function zero() {
  return 0;
}

;// CONCATENATED MODULE: ./node_modules/d3-array/src/number.js
function number(x) {
  return x === null ? NaN : +x;
}

function* numbers(values, valueof) {
  if (valueof === undefined) {
    for (let value of values) {
      if (value != null && (value = +value) >= value) {
        yield value;
      }
    }
  } else {
    let index = -1;
    for (let value of values) {
      if ((value = valueof(value, ++index, values)) != null && (value = +value) >= value) {
        yield value;
      }
    }
  }
}

;// CONCATENATED MODULE: ./node_modules/d3-array/src/bisect.js




const ascendingBisect = bisector(ascending);
const bisectRight = ascendingBisect.right;
const bisectLeft = ascendingBisect.left;
const bisectCenter = bisector(number).center;
/* harmony default export */ var bisect = (bisectRight);

;// CONCATENATED MODULE: ./node_modules/d3-color/src/define.js
/* harmony default export */ function src_define(constructor, factory, prototype) {
  constructor.prototype = factory.prototype = prototype;
  prototype.constructor = constructor;
}

function define_extend(parent, definition) {
  var prototype = Object.create(parent.prototype);
  for (var key in definition) prototype[key] = definition[key];
  return prototype;
}

;// CONCATENATED MODULE: ./node_modules/d3-color/src/color.js


function Color() {}

var darker = 0.7;
var brighter = 1 / darker;

var reI = "\\s*([+-]?\\d+)\\s*",
    reN = "\\s*([+-]?(?:\\d*\\.)?\\d+(?:[eE][+-]?\\d+)?)\\s*",
    reP = "\\s*([+-]?(?:\\d*\\.)?\\d+(?:[eE][+-]?\\d+)?)%\\s*",
    reHex = /^#([0-9a-f]{3,8})$/,
    reRgbInteger = new RegExp(`^rgb\\(${reI},${reI},${reI}\\)$`),
    reRgbPercent = new RegExp(`^rgb\\(${reP},${reP},${reP}\\)$`),
    reRgbaInteger = new RegExp(`^rgba\\(${reI},${reI},${reI},${reN}\\)$`),
    reRgbaPercent = new RegExp(`^rgba\\(${reP},${reP},${reP},${reN}\\)$`),
    reHslPercent = new RegExp(`^hsl\\(${reN},${reP},${reP}\\)$`),
    reHslaPercent = new RegExp(`^hsla\\(${reN},${reP},${reP},${reN}\\)$`);

var named = {
  aliceblue: 0xf0f8ff,
  antiquewhite: 0xfaebd7,
  aqua: 0x00ffff,
  aquamarine: 0x7fffd4,
  azure: 0xf0ffff,
  beige: 0xf5f5dc,
  bisque: 0xffe4c4,
  black: 0x000000,
  blanchedalmond: 0xffebcd,
  blue: 0x0000ff,
  blueviolet: 0x8a2be2,
  brown: 0xa52a2a,
  burlywood: 0xdeb887,
  cadetblue: 0x5f9ea0,
  chartreuse: 0x7fff00,
  chocolate: 0xd2691e,
  coral: 0xff7f50,
  cornflowerblue: 0x6495ed,
  cornsilk: 0xfff8dc,
  crimson: 0xdc143c,
  cyan: 0x00ffff,
  darkblue: 0x00008b,
  darkcyan: 0x008b8b,
  darkgoldenrod: 0xb8860b,
  darkgray: 0xa9a9a9,
  darkgreen: 0x006400,
  darkgrey: 0xa9a9a9,
  darkkhaki: 0xbdb76b,
  darkmagenta: 0x8b008b,
  darkolivegreen: 0x556b2f,
  darkorange: 0xff8c00,
  darkorchid: 0x9932cc,
  darkred: 0x8b0000,
  darksalmon: 0xe9967a,
  darkseagreen: 0x8fbc8f,
  darkslateblue: 0x483d8b,
  darkslategray: 0x2f4f4f,
  darkslategrey: 0x2f4f4f,
  darkturquoise: 0x00ced1,
  darkviolet: 0x9400d3,
  deeppink: 0xff1493,
  deepskyblue: 0x00bfff,
  dimgray: 0x696969,
  dimgrey: 0x696969,
  dodgerblue: 0x1e90ff,
  firebrick: 0xb22222,
  floralwhite: 0xfffaf0,
  forestgreen: 0x228b22,
  fuchsia: 0xff00ff,
  gainsboro: 0xdcdcdc,
  ghostwhite: 0xf8f8ff,
  gold: 0xffd700,
  goldenrod: 0xdaa520,
  gray: 0x808080,
  green: 0x008000,
  greenyellow: 0xadff2f,
  grey: 0x808080,
  honeydew: 0xf0fff0,
  hotpink: 0xff69b4,
  indianred: 0xcd5c5c,
  indigo: 0x4b0082,
  ivory: 0xfffff0,
  khaki: 0xf0e68c,
  lavender: 0xe6e6fa,
  lavenderblush: 0xfff0f5,
  lawngreen: 0x7cfc00,
  lemonchiffon: 0xfffacd,
  lightblue: 0xadd8e6,
  lightcoral: 0xf08080,
  lightcyan: 0xe0ffff,
  lightgoldenrodyellow: 0xfafad2,
  lightgray: 0xd3d3d3,
  lightgreen: 0x90ee90,
  lightgrey: 0xd3d3d3,
  lightpink: 0xffb6c1,
  lightsalmon: 0xffa07a,
  lightseagreen: 0x20b2aa,
  lightskyblue: 0x87cefa,
  lightslategray: 0x778899,
  lightslategrey: 0x778899,
  lightsteelblue: 0xb0c4de,
  lightyellow: 0xffffe0,
  lime: 0x00ff00,
  limegreen: 0x32cd32,
  linen: 0xfaf0e6,
  magenta: 0xff00ff,
  maroon: 0x800000,
  mediumaquamarine: 0x66cdaa,
  mediumblue: 0x0000cd,
  mediumorchid: 0xba55d3,
  mediumpurple: 0x9370db,
  mediumseagreen: 0x3cb371,
  mediumslateblue: 0x7b68ee,
  mediumspringgreen: 0x00fa9a,
  mediumturquoise: 0x48d1cc,
  mediumvioletred: 0xc71585,
  midnightblue: 0x191970,
  mintcream: 0xf5fffa,
  mistyrose: 0xffe4e1,
  moccasin: 0xffe4b5,
  navajowhite: 0xffdead,
  navy: 0x000080,
  oldlace: 0xfdf5e6,
  olive: 0x808000,
  olivedrab: 0x6b8e23,
  orange: 0xffa500,
  orangered: 0xff4500,
  orchid: 0xda70d6,
  palegoldenrod: 0xeee8aa,
  palegreen: 0x98fb98,
  paleturquoise: 0xafeeee,
  palevioletred: 0xdb7093,
  papayawhip: 0xffefd5,
  peachpuff: 0xffdab9,
  peru: 0xcd853f,
  pink: 0xffc0cb,
  plum: 0xdda0dd,
  powderblue: 0xb0e0e6,
  purple: 0x800080,
  rebeccapurple: 0x663399,
  red: 0xff0000,
  rosybrown: 0xbc8f8f,
  royalblue: 0x4169e1,
  saddlebrown: 0x8b4513,
  salmon: 0xfa8072,
  sandybrown: 0xf4a460,
  seagreen: 0x2e8b57,
  seashell: 0xfff5ee,
  sienna: 0xa0522d,
  silver: 0xc0c0c0,
  skyblue: 0x87ceeb,
  slateblue: 0x6a5acd,
  slategray: 0x708090,
  slategrey: 0x708090,
  snow: 0xfffafa,
  springgreen: 0x00ff7f,
  steelblue: 0x4682b4,
  tan: 0xd2b48c,
  teal: 0x008080,
  thistle: 0xd8bfd8,
  tomato: 0xff6347,
  turquoise: 0x40e0d0,
  violet: 0xee82ee,
  wheat: 0xf5deb3,
  white: 0xffffff,
  whitesmoke: 0xf5f5f5,
  yellow: 0xffff00,
  yellowgreen: 0x9acd32
};

src_define(Color, color, {
  copy(channels) {
    return Object.assign(new this.constructor, this, channels);
  },
  displayable() {
    return this.rgb().displayable();
  },
  hex: color_formatHex, // Deprecated! Use color.formatHex.
  formatHex: color_formatHex,
  formatHex8: color_formatHex8,
  formatHsl: color_formatHsl,
  formatRgb: color_formatRgb,
  toString: color_formatRgb
});

function color_formatHex() {
  return this.rgb().formatHex();
}

function color_formatHex8() {
  return this.rgb().formatHex8();
}

function color_formatHsl() {
  return hslConvert(this).formatHsl();
}

function color_formatRgb() {
  return this.rgb().formatRgb();
}

function color(format) {
  var m, l;
  format = (format + "").trim().toLowerCase();
  return (m = reHex.exec(format)) ? (l = m[1].length, m = parseInt(m[1], 16), l === 6 ? rgbn(m) // #ff0000
      : l === 3 ? new Rgb((m >> 8 & 0xf) | (m >> 4 & 0xf0), (m >> 4 & 0xf) | (m & 0xf0), ((m & 0xf) << 4) | (m & 0xf), 1) // #f00
      : l === 8 ? rgba(m >> 24 & 0xff, m >> 16 & 0xff, m >> 8 & 0xff, (m & 0xff) / 0xff) // #ff000000
      : l === 4 ? rgba((m >> 12 & 0xf) | (m >> 8 & 0xf0), (m >> 8 & 0xf) | (m >> 4 & 0xf0), (m >> 4 & 0xf) | (m & 0xf0), (((m & 0xf) << 4) | (m & 0xf)) / 0xff) // #f000
      : null) // invalid hex
      : (m = reRgbInteger.exec(format)) ? new Rgb(m[1], m[2], m[3], 1) // rgb(255, 0, 0)
      : (m = reRgbPercent.exec(format)) ? new Rgb(m[1] * 255 / 100, m[2] * 255 / 100, m[3] * 255 / 100, 1) // rgb(100%, 0%, 0%)
      : (m = reRgbaInteger.exec(format)) ? rgba(m[1], m[2], m[3], m[4]) // rgba(255, 0, 0, 1)
      : (m = reRgbaPercent.exec(format)) ? rgba(m[1] * 255 / 100, m[2] * 255 / 100, m[3] * 255 / 100, m[4]) // rgb(100%, 0%, 0%, 1)
      : (m = reHslPercent.exec(format)) ? hsla(m[1], m[2] / 100, m[3] / 100, 1) // hsl(120, 50%, 50%)
      : (m = reHslaPercent.exec(format)) ? hsla(m[1], m[2] / 100, m[3] / 100, m[4]) // hsla(120, 50%, 50%, 1)
      : named.hasOwnProperty(format) ? rgbn(named[format]) // eslint-disable-line no-prototype-builtins
      : format === "transparent" ? new Rgb(NaN, NaN, NaN, 0)
      : null;
}

function rgbn(n) {
  return new Rgb(n >> 16 & 0xff, n >> 8 & 0xff, n & 0xff, 1);
}

function rgba(r, g, b, a) {
  if (a <= 0) r = g = b = NaN;
  return new Rgb(r, g, b, a);
}

function rgbConvert(o) {
  if (!(o instanceof Color)) o = color(o);
  if (!o) return new Rgb;
  o = o.rgb();
  return new Rgb(o.r, o.g, o.b, o.opacity);
}

function color_rgb(r, g, b, opacity) {
  return arguments.length === 1 ? rgbConvert(r) : new Rgb(r, g, b, opacity == null ? 1 : opacity);
}

function Rgb(r, g, b, opacity) {
  this.r = +r;
  this.g = +g;
  this.b = +b;
  this.opacity = +opacity;
}

src_define(Rgb, color_rgb, define_extend(Color, {
  brighter(k) {
    k = k == null ? brighter : Math.pow(brighter, k);
    return new Rgb(this.r * k, this.g * k, this.b * k, this.opacity);
  },
  darker(k) {
    k = k == null ? darker : Math.pow(darker, k);
    return new Rgb(this.r * k, this.g * k, this.b * k, this.opacity);
  },
  rgb() {
    return this;
  },
  clamp() {
    return new Rgb(clampi(this.r), clampi(this.g), clampi(this.b), clampa(this.opacity));
  },
  displayable() {
    return (-0.5 <= this.r && this.r < 255.5)
        && (-0.5 <= this.g && this.g < 255.5)
        && (-0.5 <= this.b && this.b < 255.5)
        && (0 <= this.opacity && this.opacity <= 1);
  },
  hex: rgb_formatHex, // Deprecated! Use color.formatHex.
  formatHex: rgb_formatHex,
  formatHex8: rgb_formatHex8,
  formatRgb: rgb_formatRgb,
  toString: rgb_formatRgb
}));

function rgb_formatHex() {
  return `#${hex(this.r)}${hex(this.g)}${hex(this.b)}`;
}

function rgb_formatHex8() {
  return `#${hex(this.r)}${hex(this.g)}${hex(this.b)}${hex((isNaN(this.opacity) ? 1 : this.opacity) * 255)}`;
}

function rgb_formatRgb() {
  const a = clampa(this.opacity);
  return `${a === 1 ? "rgb(" : "rgba("}${clampi(this.r)}, ${clampi(this.g)}, ${clampi(this.b)}${a === 1 ? ")" : `, ${a})`}`;
}

function clampa(opacity) {
  return isNaN(opacity) ? 1 : Math.max(0, Math.min(1, opacity));
}

function clampi(value) {
  return Math.max(0, Math.min(255, Math.round(value) || 0));
}

function hex(value) {
  value = clampi(value);
  return (value < 16 ? "0" : "") + value.toString(16);
}

function hsla(h, s, l, a) {
  if (a <= 0) h = s = l = NaN;
  else if (l <= 0 || l >= 1) h = s = NaN;
  else if (s <= 0) h = NaN;
  return new Hsl(h, s, l, a);
}

function hslConvert(o) {
  if (o instanceof Hsl) return new Hsl(o.h, o.s, o.l, o.opacity);
  if (!(o instanceof Color)) o = color(o);
  if (!o) return new Hsl;
  if (o instanceof Hsl) return o;
  o = o.rgb();
  var r = o.r / 255,
      g = o.g / 255,
      b = o.b / 255,
      min = Math.min(r, g, b),
      max = Math.max(r, g, b),
      h = NaN,
      s = max - min,
      l = (max + min) / 2;
  if (s) {
    if (r === max) h = (g - b) / s + (g < b) * 6;
    else if (g === max) h = (b - r) / s + 2;
    else h = (r - g) / s + 4;
    s /= l < 0.5 ? max + min : 2 - max - min;
    h *= 60;
  } else {
    s = l > 0 && l < 1 ? 0 : h;
  }
  return new Hsl(h, s, l, o.opacity);
}

function hsl(h, s, l, opacity) {
  return arguments.length === 1 ? hslConvert(h) : new Hsl(h, s, l, opacity == null ? 1 : opacity);
}

function Hsl(h, s, l, opacity) {
  this.h = +h;
  this.s = +s;
  this.l = +l;
  this.opacity = +opacity;
}

src_define(Hsl, hsl, define_extend(Color, {
  brighter(k) {
    k = k == null ? brighter : Math.pow(brighter, k);
    return new Hsl(this.h, this.s, this.l * k, this.opacity);
  },
  darker(k) {
    k = k == null ? darker : Math.pow(darker, k);
    return new Hsl(this.h, this.s, this.l * k, this.opacity);
  },
  rgb() {
    var h = this.h % 360 + (this.h < 0) * 360,
        s = isNaN(h) || isNaN(this.s) ? 0 : this.s,
        l = this.l,
        m2 = l + (l < 0.5 ? l : 1 - l) * s,
        m1 = 2 * l - m2;
    return new Rgb(
      hsl2rgb(h >= 240 ? h - 240 : h + 120, m1, m2),
      hsl2rgb(h, m1, m2),
      hsl2rgb(h < 120 ? h + 240 : h - 120, m1, m2),
      this.opacity
    );
  },
  clamp() {
    return new Hsl(clamph(this.h), clampt(this.s), clampt(this.l), clampa(this.opacity));
  },
  displayable() {
    return (0 <= this.s && this.s <= 1 || isNaN(this.s))
        && (0 <= this.l && this.l <= 1)
        && (0 <= this.opacity && this.opacity <= 1);
  },
  formatHsl() {
    const a = clampa(this.opacity);
    return `${a === 1 ? "hsl(" : "hsla("}${clamph(this.h)}, ${clampt(this.s) * 100}%, ${clampt(this.l) * 100}%${a === 1 ? ")" : `, ${a})`}`;
  }
}));

function clamph(value) {
  value = (value || 0) % 360;
  return value < 0 ? value + 360 : value;
}

function clampt(value) {
  return Math.max(0, Math.min(1, value || 0));
}

/* From FvD 13.37, CSS Color Module Level 3 */
function hsl2rgb(h, m1, m2) {
  return (h < 60 ? m1 + (m2 - m1) * h / 60
      : h < 180 ? m2
      : h < 240 ? m1 + (m2 - m1) * (240 - h) / 60
      : m1) * 255;
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/basis.js
function basis(t1, v0, v1, v2, v3) {
  var t2 = t1 * t1, t3 = t2 * t1;
  return ((1 - 3 * t1 + 3 * t2 - t3) * v0
      + (4 - 6 * t2 + 3 * t3) * v1
      + (1 + 3 * t1 + 3 * t2 - 3 * t3) * v2
      + t3 * v3) / 6;
}

/* harmony default export */ function src_basis(values) {
  var n = values.length - 1;
  return function(t) {
    var i = t <= 0 ? (t = 0) : t >= 1 ? (t = 1, n - 1) : Math.floor(t * n),
        v1 = values[i],
        v2 = values[i + 1],
        v0 = i > 0 ? values[i - 1] : 2 * v1 - v2,
        v3 = i < n - 1 ? values[i + 2] : 2 * v2 - v1;
    return basis((t - i / n) * n, v0, v1, v2, v3);
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/basisClosed.js


/* harmony default export */ function basisClosed(values) {
  var n = values.length;
  return function(t) {
    var i = Math.floor(((t %= 1) < 0 ? ++t : t) * n),
        v0 = values[(i + n - 1) % n],
        v1 = values[i % n],
        v2 = values[(i + 1) % n],
        v3 = values[(i + 2) % n];
    return basis((t - i / n) * n, v0, v1, v2, v3);
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/constant.js
/* harmony default export */ var src_constant = (x => () => x);

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/color.js


function linear(a, d) {
  return function(t) {
    return a + t * d;
  };
}

function exponential(a, b, y) {
  return a = Math.pow(a, y), b = Math.pow(b, y) - a, y = 1 / y, function(t) {
    return Math.pow(a + t * b, y);
  };
}

function hue(a, b) {
  var d = b - a;
  return d ? linear(a, d > 180 || d < -180 ? d - 360 * Math.round(d / 360) : d) : constant(isNaN(a) ? b : a);
}

function gamma(y) {
  return (y = +y) === 1 ? nogamma : function(a, b) {
    return b - a ? exponential(a, b, y) : src_constant(isNaN(a) ? b : a);
  };
}

function nogamma(a, b) {
  var d = b - a;
  return d ? linear(a, d) : src_constant(isNaN(a) ? b : a);
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/rgb.js





/* harmony default export */ var rgb = ((function rgbGamma(y) {
  var color = gamma(y);

  function rgb(start, end) {
    var r = color((start = color_rgb(start)).r, (end = color_rgb(end)).r),
        g = color(start.g, end.g),
        b = color(start.b, end.b),
        opacity = nogamma(start.opacity, end.opacity);
    return function(t) {
      start.r = r(t);
      start.g = g(t);
      start.b = b(t);
      start.opacity = opacity(t);
      return start + "";
    };
  }

  rgb.gamma = rgbGamma;

  return rgb;
})(1));

function rgbSpline(spline) {
  return function(colors) {
    var n = colors.length,
        r = new Array(n),
        g = new Array(n),
        b = new Array(n),
        i, color;
    for (i = 0; i < n; ++i) {
      color = color_rgb(colors[i]);
      r[i] = color.r || 0;
      g[i] = color.g || 0;
      b[i] = color.b || 0;
    }
    r = spline(r);
    g = spline(g);
    b = spline(b);
    color.opacity = 1;
    return function(t) {
      color.r = r(t);
      color.g = g(t);
      color.b = b(t);
      return color + "";
    };
  };
}

var rgbBasis = rgbSpline(src_basis);
var rgbBasisClosed = rgbSpline(basisClosed);

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/array.js



/* harmony default export */ function array(a, b) {
  return (isNumberArray(b) ? numberArray : genericArray)(a, b);
}

function genericArray(a, b) {
  var nb = b ? b.length : 0,
      na = a ? Math.min(nb, a.length) : 0,
      x = new Array(na),
      c = new Array(nb),
      i;

  for (i = 0; i < na; ++i) x[i] = value(a[i], b[i]);
  for (; i < nb; ++i) c[i] = b[i];

  return function(t) {
    for (i = 0; i < na; ++i) c[i] = x[i](t);
    return c;
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/date.js
/* harmony default export */ function date(a, b) {
  var d = new Date;
  return a = +a, b = +b, function(t) {
    return d.setTime(a * (1 - t) + b * t), d;
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/number.js
/* harmony default export */ function src_number(a, b) {
  return a = +a, b = +b, function(t) {
    return a * (1 - t) + b * t;
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/object.js


/* harmony default export */ function object(a, b) {
  var i = {},
      c = {},
      k;

  if (a === null || typeof a !== "object") a = {};
  if (b === null || typeof b !== "object") b = {};

  for (k in b) {
    if (k in a) {
      i[k] = value(a[k], b[k]);
    } else {
      c[k] = b[k];
    }
  }

  return function(t) {
    for (k in i) c[k] = i[k](t);
    return c;
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/string.js


var reA = /[-+]?(?:\d+\.?\d*|\.?\d+)(?:[eE][-+]?\d+)?/g,
    reB = new RegExp(reA.source, "g");

function string_zero(b) {
  return function() {
    return b;
  };
}

function one(b) {
  return function(t) {
    return b(t) + "";
  };
}

/* harmony default export */ function string(a, b) {
  var bi = reA.lastIndex = reB.lastIndex = 0, // scan index for next number in b
      am, // current match in a
      bm, // current match in b
      bs, // string preceding current number in b, if any
      i = -1, // index in s
      s = [], // string constants and placeholders
      q = []; // number interpolators

  // Coerce inputs to strings.
  a = a + "", b = b + "";

  // Interpolate pairs of numbers in a & b.
  while ((am = reA.exec(a))
      && (bm = reB.exec(b))) {
    if ((bs = bm.index) > bi) { // a string precedes the next number in b
      bs = b.slice(bi, bs);
      if (s[i]) s[i] += bs; // coalesce with previous string
      else s[++i] = bs;
    }
    if ((am = am[0]) === (bm = bm[0])) { // numbers in a & b match
      if (s[i]) s[i] += bm; // coalesce with previous string
      else s[++i] = bm;
    } else { // interpolate non-matching numbers
      s[++i] = null;
      q.push({i: i, x: src_number(am, bm)});
    }
    bi = reB.lastIndex;
  }

  // Add remains of b.
  if (bi < b.length) {
    bs = b.slice(bi);
    if (s[i]) s[i] += bs; // coalesce with previous string
    else s[++i] = bs;
  }

  // Special optimization for only a single match.
  // Otherwise, interpolate each of the numbers and rejoin the string.
  return s.length < 2 ? (q[0]
      ? one(q[0].x)
      : string_zero(b))
      : (b = q.length, function(t) {
          for (var i = 0, o; i < b; ++i) s[(o = q[i]).i] = o.x(t);
          return s.join("");
        });
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/numberArray.js
/* harmony default export */ function src_numberArray(a, b) {
  if (!b) b = [];
  var n = a ? Math.min(b.length, a.length) : 0,
      c = b.slice(),
      i;
  return function(t) {
    for (i = 0; i < n; ++i) c[i] = a[i] * (1 - t) + b[i] * t;
    return c;
  };
}

function numberArray_isNumberArray(x) {
  return ArrayBuffer.isView(x) && !(x instanceof DataView);
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/value.js










/* harmony default export */ function value(a, b) {
  var t = typeof b, c;
  return b == null || t === "boolean" ? src_constant(b)
      : (t === "number" ? src_number
      : t === "string" ? ((c = color(b)) ? (b = c, rgb) : string)
      : b instanceof color ? rgb
      : b instanceof Date ? date
      : numberArray_isNumberArray(b) ? src_numberArray
      : Array.isArray(b) ? genericArray
      : typeof b.valueOf !== "function" && typeof b.toString !== "function" || isNaN(b) ? object
      : src_number)(a, b);
}

;// CONCATENATED MODULE: ./node_modules/d3-interpolate/src/round.js
/* harmony default export */ function round(a, b) {
  return a = +a, b = +b, function(t) {
    return Math.round(a * (1 - t) + b * t);
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-scale/src/constant.js
function constants(x) {
  return function() {
    return x;
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-scale/src/number.js
function number_number(x) {
  return +x;
}

;// CONCATENATED MODULE: ./node_modules/d3-scale/src/continuous.js





var unit = [0, 1];

function continuous_identity(x) {
  return x;
}

function continuous_normalize(a, b) {
  return (b -= (a = +a))
      ? function(x) { return (x - a) / b; }
      : constants(isNaN(b) ? NaN : 0.5);
}

function clamper(a, b) {
  var t;
  if (a > b) t = a, a = b, b = t;
  return function(x) { return Math.max(a, Math.min(b, x)); };
}

// normalize(a, b)(x) takes a domain value x in [a,b] and returns the corresponding parameter t in [0,1].
// interpolate(a, b)(t) takes a parameter t in [0,1] and returns the corresponding range value x in [a,b].
function bimap(domain, range, interpolate) {
  var d0 = domain[0], d1 = domain[1], r0 = range[0], r1 = range[1];
  if (d1 < d0) d0 = continuous_normalize(d1, d0), r0 = interpolate(r1, r0);
  else d0 = continuous_normalize(d0, d1), r0 = interpolate(r0, r1);
  return function(x) { return r0(d0(x)); };
}

function polymap(domain, range, interpolate) {
  var j = Math.min(domain.length, range.length) - 1,
      d = new Array(j),
      r = new Array(j),
      i = -1;

  // Reverse descending domains.
  if (domain[j] < domain[0]) {
    domain = domain.slice().reverse();
    range = range.slice().reverse();
  }

  while (++i < j) {
    d[i] = continuous_normalize(domain[i], domain[i + 1]);
    r[i] = interpolate(range[i], range[i + 1]);
  }

  return function(x) {
    var i = bisect(domain, x, 1, j) - 1;
    return r[i](d[i](x));
  };
}

function copy(source, target) {
  return target
      .domain(source.domain())
      .range(source.range())
      .interpolate(source.interpolate())
      .clamp(source.clamp())
      .unknown(source.unknown());
}

function transformer() {
  var domain = unit,
      range = unit,
      interpolate = value,
      transform,
      untransform,
      unknown,
      clamp = continuous_identity,
      piecewise,
      output,
      input;

  function rescale() {
    var n = Math.min(domain.length, range.length);
    if (clamp !== continuous_identity) clamp = clamper(domain[0], domain[n - 1]);
    piecewise = n > 2 ? polymap : bimap;
    output = input = null;
    return scale;
  }

  function scale(x) {
    return x == null || isNaN(x = +x) ? unknown : (output || (output = piecewise(domain.map(transform), range, interpolate)))(transform(clamp(x)));
  }

  scale.invert = function(y) {
    return clamp(untransform((input || (input = piecewise(range, domain.map(transform), src_number)))(y)));
  };

  scale.domain = function(_) {
    return arguments.length ? (domain = Array.from(_, number_number), rescale()) : domain.slice();
  };

  scale.range = function(_) {
    return arguments.length ? (range = Array.from(_), rescale()) : range.slice();
  };

  scale.rangeRound = function(_) {
    return range = Array.from(_), interpolate = round, rescale();
  };

  scale.clamp = function(_) {
    return arguments.length ? (clamp = _ ? true : continuous_identity, rescale()) : clamp !== continuous_identity;
  };

  scale.interpolate = function(_) {
    return arguments.length ? (interpolate = _, rescale()) : interpolate;
  };

  scale.unknown = function(_) {
    return arguments.length ? (unknown = _, scale) : unknown;
  };

  return function(t, u) {
    transform = t, untransform = u;
    return rescale();
  };
}

function continuous() {
  return transformer()(continuous_identity, continuous_identity);
}

;// CONCATENATED MODULE: ./node_modules/d3-scale/src/init.js
function initRange(domain, range) {
  switch (arguments.length) {
    case 0: break;
    case 1: this.range(domain); break;
    default: this.range(range).domain(domain); break;
  }
  return this;
}

function initInterpolator(domain, interpolator) {
  switch (arguments.length) {
    case 0: break;
    case 1: {
      if (typeof domain === "function") this.interpolator(domain);
      else this.range(domain);
      break;
    }
    default: {
      this.domain(domain);
      if (typeof interpolator === "function") this.interpolator(interpolator);
      else this.range(interpolator);
      break;
    }
  }
  return this;
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatSpecifier.js
// [[fill]align][sign][symbol][0][width][,][.precision][~][type]
var re = /^(?:(.)?([<>=^]))?([+\-( ])?([$#])?(0)?(\d+)?(,)?(\.\d+)?(~)?([a-z%])?$/i;

function formatSpecifier(specifier) {
  if (!(match = re.exec(specifier))) throw new Error("invalid format: " + specifier);
  var match;
  return new FormatSpecifier({
    fill: match[1],
    align: match[2],
    sign: match[3],
    symbol: match[4],
    zero: match[5],
    width: match[6],
    comma: match[7],
    precision: match[8] && match[8].slice(1),
    trim: match[9],
    type: match[10]
  });
}

formatSpecifier.prototype = FormatSpecifier.prototype; // instanceof

function FormatSpecifier(specifier) {
  this.fill = specifier.fill === undefined ? " " : specifier.fill + "";
  this.align = specifier.align === undefined ? ">" : specifier.align + "";
  this.sign = specifier.sign === undefined ? "-" : specifier.sign + "";
  this.symbol = specifier.symbol === undefined ? "" : specifier.symbol + "";
  this.zero = !!specifier.zero;
  this.width = specifier.width === undefined ? undefined : +specifier.width;
  this.comma = !!specifier.comma;
  this.precision = specifier.precision === undefined ? undefined : +specifier.precision;
  this.trim = !!specifier.trim;
  this.type = specifier.type === undefined ? "" : specifier.type + "";
}

FormatSpecifier.prototype.toString = function() {
  return this.fill
      + this.align
      + this.sign
      + this.symbol
      + (this.zero ? "0" : "")
      + (this.width === undefined ? "" : Math.max(1, this.width | 0))
      + (this.comma ? "," : "")
      + (this.precision === undefined ? "" : "." + Math.max(0, this.precision | 0))
      + (this.trim ? "~" : "")
      + this.type;
};

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatDecimal.js
/* harmony default export */ function formatDecimal(x) {
  return Math.abs(x = Math.round(x)) >= 1e21
      ? x.toLocaleString("en").replace(/,/g, "")
      : x.toString(10);
}

// Computes the decimal coefficient and exponent of the specified number x with
// significant digits p, where x is positive and p is in [1, 21] or undefined.
// For example, formatDecimalParts(1.23) returns ["123", 0].
function formatDecimalParts(x, p) {
  if ((i = (x = p ? x.toExponential(p - 1) : x.toExponential()).indexOf("e")) < 0) return null; // NaN, ±Infinity
  var i, coefficient = x.slice(0, i);

  // The string returned by toExponential either has the form \d\.\d+e[-+]\d+
  // (e.g., 1.2e+3) or the form \de[-+]\d+ (e.g., 1e+3).
  return [
    coefficient.length > 1 ? coefficient[0] + coefficient.slice(2) : coefficient,
    +x.slice(i + 1)
  ];
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/exponent.js


/* harmony default export */ function exponent(x) {
  return x = formatDecimalParts(Math.abs(x)), x ? x[1] : NaN;
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/precisionPrefix.js


/* harmony default export */ function precisionPrefix(step, value) {
  return Math.max(0, Math.max(-8, Math.min(8, Math.floor(exponent(value) / 3))) * 3 - exponent(Math.abs(step)));
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatGroup.js
/* harmony default export */ function formatGroup(grouping, thousands) {
  return function(value, width) {
    var i = value.length,
        t = [],
        j = 0,
        g = grouping[0],
        length = 0;

    while (i > 0 && g > 0) {
      if (length + g + 1 > width) g = Math.max(1, width - length);
      t.push(value.substring(i -= g, i + g));
      if ((length += g + 1) > width) break;
      g = grouping[j = (j + 1) % grouping.length];
    }

    return t.reverse().join(thousands);
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatNumerals.js
/* harmony default export */ function formatNumerals(numerals) {
  return function(value) {
    return value.replace(/[0-9]/g, function(i) {
      return numerals[+i];
    });
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatTrim.js
// Trims insignificant zeros, e.g., replaces 1.2000k with 1.2k.
/* harmony default export */ function formatTrim(s) {
  out: for (var n = s.length, i = 1, i0 = -1, i1; i < n; ++i) {
    switch (s[i]) {
      case ".": i0 = i1 = i; break;
      case "0": if (i0 === 0) i0 = i; i1 = i; break;
      default: if (!+s[i]) break out; if (i0 > 0) i0 = 0; break;
    }
  }
  return i0 > 0 ? s.slice(0, i0) + s.slice(i1 + 1) : s;
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatPrefixAuto.js


var prefixExponent;

/* harmony default export */ function formatPrefixAuto(x, p) {
  var d = formatDecimalParts(x, p);
  if (!d) return x + "";
  var coefficient = d[0],
      exponent = d[1],
      i = exponent - (prefixExponent = Math.max(-8, Math.min(8, Math.floor(exponent / 3))) * 3) + 1,
      n = coefficient.length;
  return i === n ? coefficient
      : i > n ? coefficient + new Array(i - n + 1).join("0")
      : i > 0 ? coefficient.slice(0, i) + "." + coefficient.slice(i)
      : "0." + new Array(1 - i).join("0") + formatDecimalParts(x, Math.max(0, p + i - 1))[0]; // less than 1y!
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatRounded.js


/* harmony default export */ function formatRounded(x, p) {
  var d = formatDecimalParts(x, p);
  if (!d) return x + "";
  var coefficient = d[0],
      exponent = d[1];
  return exponent < 0 ? "0." + new Array(-exponent).join("0") + coefficient
      : coefficient.length > exponent + 1 ? coefficient.slice(0, exponent + 1) + "." + coefficient.slice(exponent + 1)
      : coefficient + new Array(exponent - coefficient.length + 2).join("0");
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/formatTypes.js




/* harmony default export */ var formatTypes = ({
  "%": (x, p) => (x * 100).toFixed(p),
  "b": (x) => Math.round(x).toString(2),
  "c": (x) => x + "",
  "d": formatDecimal,
  "e": (x, p) => x.toExponential(p),
  "f": (x, p) => x.toFixed(p),
  "g": (x, p) => x.toPrecision(p),
  "o": (x) => Math.round(x).toString(8),
  "p": (x, p) => formatRounded(x * 100, p),
  "r": formatRounded,
  "s": formatPrefixAuto,
  "X": (x) => Math.round(x).toString(16).toUpperCase(),
  "x": (x) => Math.round(x).toString(16)
});

;// CONCATENATED MODULE: ./node_modules/d3-format/src/identity.js
/* harmony default export */ function src_identity(x) {
  return x;
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/locale.js









var map = Array.prototype.map,
    prefixes = ["y","z","a","f","p","n","µ","m","","k","M","G","T","P","E","Z","Y"];

/* harmony default export */ function locale(locale) {
  var group = locale.grouping === undefined || locale.thousands === undefined ? src_identity : formatGroup(map.call(locale.grouping, Number), locale.thousands + ""),
      currencyPrefix = locale.currency === undefined ? "" : locale.currency[0] + "",
      currencySuffix = locale.currency === undefined ? "" : locale.currency[1] + "",
      decimal = locale.decimal === undefined ? "." : locale.decimal + "",
      numerals = locale.numerals === undefined ? src_identity : formatNumerals(map.call(locale.numerals, String)),
      percent = locale.percent === undefined ? "%" : locale.percent + "",
      minus = locale.minus === undefined ? "−" : locale.minus + "",
      nan = locale.nan === undefined ? "NaN" : locale.nan + "";

  function newFormat(specifier) {
    specifier = formatSpecifier(specifier);

    var fill = specifier.fill,
        align = specifier.align,
        sign = specifier.sign,
        symbol = specifier.symbol,
        zero = specifier.zero,
        width = specifier.width,
        comma = specifier.comma,
        precision = specifier.precision,
        trim = specifier.trim,
        type = specifier.type;

    // The "n" type is an alias for ",g".
    if (type === "n") comma = true, type = "g";

    // The "" type, and any invalid type, is an alias for ".12~g".
    else if (!formatTypes[type]) precision === undefined && (precision = 12), trim = true, type = "g";

    // If zero fill is specified, padding goes after sign and before digits.
    if (zero || (fill === "0" && align === "=")) zero = true, fill = "0", align = "=";

    // Compute the prefix and suffix.
    // For SI-prefix, the suffix is lazily computed.
    var prefix = symbol === "$" ? currencyPrefix : symbol === "#" && /[boxX]/.test(type) ? "0" + type.toLowerCase() : "",
        suffix = symbol === "$" ? currencySuffix : /[%p]/.test(type) ? percent : "";

    // What format function should we use?
    // Is this an integer type?
    // Can this type generate exponential notation?
    var formatType = formatTypes[type],
        maybeSuffix = /[defgprs%]/.test(type);

    // Set the default precision if not specified,
    // or clamp the specified precision to the supported range.
    // For significant precision, it must be in [1, 21].
    // For fixed precision, it must be in [0, 20].
    precision = precision === undefined ? 6
        : /[gprs]/.test(type) ? Math.max(1, Math.min(21, precision))
        : Math.max(0, Math.min(20, precision));

    function format(value) {
      var valuePrefix = prefix,
          valueSuffix = suffix,
          i, n, c;

      if (type === "c") {
        valueSuffix = formatType(value) + valueSuffix;
        value = "";
      } else {
        value = +value;

        // Determine the sign. -0 is not less than 0, but 1 / -0 is!
        var valueNegative = value < 0 || 1 / value < 0;

        // Perform the initial formatting.
        value = isNaN(value) ? nan : formatType(Math.abs(value), precision);

        // Trim insignificant zeros.
        if (trim) value = formatTrim(value);

        // If a negative value rounds to zero after formatting, and no explicit positive sign is requested, hide the sign.
        if (valueNegative && +value === 0 && sign !== "+") valueNegative = false;

        // Compute the prefix and suffix.
        valuePrefix = (valueNegative ? (sign === "(" ? sign : minus) : sign === "-" || sign === "(" ? "" : sign) + valuePrefix;
        valueSuffix = (type === "s" ? prefixes[8 + prefixExponent / 3] : "") + valueSuffix + (valueNegative && sign === "(" ? ")" : "");

        // Break the formatted value into the integer “value” part that can be
        // grouped, and fractional or exponential “suffix” part that is not.
        if (maybeSuffix) {
          i = -1, n = value.length;
          while (++i < n) {
            if (c = value.charCodeAt(i), 48 > c || c > 57) {
              valueSuffix = (c === 46 ? decimal + value.slice(i + 1) : value.slice(i)) + valueSuffix;
              value = value.slice(0, i);
              break;
            }
          }
        }
      }

      // If the fill character is not "0", grouping is applied before padding.
      if (comma && !zero) value = group(value, Infinity);

      // Compute the padding.
      var length = valuePrefix.length + value.length + valueSuffix.length,
          padding = length < width ? new Array(width - length + 1).join(fill) : "";

      // If the fill character is "0", grouping is applied after padding.
      if (comma && zero) value = group(padding + value, padding.length ? width - valueSuffix.length : Infinity), padding = "";

      // Reconstruct the final output based on the desired alignment.
      switch (align) {
        case "<": value = valuePrefix + value + valueSuffix + padding; break;
        case "=": value = valuePrefix + padding + value + valueSuffix; break;
        case "^": value = padding.slice(0, length = padding.length >> 1) + valuePrefix + value + valueSuffix + padding.slice(length); break;
        default: value = padding + valuePrefix + value + valueSuffix; break;
      }

      return numerals(value);
    }

    format.toString = function() {
      return specifier + "";
    };

    return format;
  }

  function formatPrefix(specifier, value) {
    var f = newFormat((specifier = formatSpecifier(specifier), specifier.type = "f", specifier)),
        e = Math.max(-8, Math.min(8, Math.floor(exponent(value) / 3))) * 3,
        k = Math.pow(10, -e),
        prefix = prefixes[8 + e / 3];
    return function(value) {
      return f(k * value) + prefix;
    };
  }

  return {
    format: newFormat,
    formatPrefix: formatPrefix
  };
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/defaultLocale.js


var defaultLocale_locale;
var format;
var formatPrefix;

defaultLocale({
  thousands: ",",
  grouping: [3],
  currency: ["$", ""]
});

function defaultLocale(definition) {
  defaultLocale_locale = locale(definition);
  format = defaultLocale_locale.format;
  formatPrefix = defaultLocale_locale.formatPrefix;
  return defaultLocale_locale;
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/precisionRound.js


/* harmony default export */ function precisionRound(step, max) {
  step = Math.abs(step), max = Math.abs(max) - step;
  return Math.max(0, exponent(max) - exponent(step)) + 1;
}

;// CONCATENATED MODULE: ./node_modules/d3-format/src/precisionFixed.js


/* harmony default export */ function precisionFixed(step) {
  return Math.max(0, -exponent(Math.abs(step)));
}

;// CONCATENATED MODULE: ./node_modules/d3-scale/src/tickFormat.js



function tickFormat(start, stop, count, specifier) {
  var step = tickStep(start, stop, count),
      precision;
  specifier = formatSpecifier(specifier == null ? ",f" : specifier);
  switch (specifier.type) {
    case "s": {
      var value = Math.max(Math.abs(start), Math.abs(stop));
      if (specifier.precision == null && !isNaN(precision = precisionPrefix(step, value))) specifier.precision = precision;
      return formatPrefix(specifier, value);
    }
    case "":
    case "e":
    case "g":
    case "p":
    case "r": {
      if (specifier.precision == null && !isNaN(precision = precisionRound(step, Math.max(Math.abs(start), Math.abs(stop))))) specifier.precision = precision - (specifier.type === "e");
      break;
    }
    case "f":
    case "%": {
      if (specifier.precision == null && !isNaN(precision = precisionFixed(step))) specifier.precision = precision - (specifier.type === "%") * 2;
      break;
    }
  }
  return format(specifier);
}

;// CONCATENATED MODULE: ./node_modules/d3-scale/src/linear.js





function linearish(scale) {
  var domain = scale.domain;

  scale.ticks = function(count) {
    var d = domain();
    return ticks(d[0], d[d.length - 1], count == null ? 10 : count);
  };

  scale.tickFormat = function(count, specifier) {
    var d = domain();
    return tickFormat(d[0], d[d.length - 1], count == null ? 10 : count, specifier);
  };

  scale.nice = function(count) {
    if (count == null) count = 10;

    var d = domain();
    var i0 = 0;
    var i1 = d.length - 1;
    var start = d[i0];
    var stop = d[i1];
    var prestep;
    var step;
    var maxIter = 10;

    if (stop < start) {
      step = start, start = stop, stop = step;
      step = i0, i0 = i1, i1 = step;
    }
    
    while (maxIter-- > 0) {
      step = tickIncrement(start, stop, count);
      if (step === prestep) {
        d[i0] = start
        d[i1] = stop
        return domain(d);
      } else if (step > 0) {
        start = Math.floor(start / step) * step;
        stop = Math.ceil(stop / step) * step;
      } else if (step < 0) {
        start = Math.ceil(start * step) / step;
        stop = Math.floor(stop * step) / step;
      } else {
        break;
      }
      prestep = step;
    }

    return scale;
  };

  return scale;
}

function linear_linear() {
  var scale = continuous();

  scale.copy = function() {
    return copy(scale, linear_linear());
  };

  initRange.apply(scale, arguments);

  return linearish(scale);
}

// EXTERNAL MODULE: ./node_modules/mobile-device-detect/dist/index.js
var dist = __webpack_require__(88);
;// CONCATENATED MODULE: ./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/MapChart.vue?vue&type=script&lang=js








/* harmony default export */ var MapChartvue_type_script_lang_js = ({
  name: 'MapChart',
  mixins: [mixin],
  components: {
    LeftCol: LeftCol,
    ...maps
  },
  data () {
    return {
      dataParse: {},
      chart: undefined,
      widgetId: '',
      chartId: '',
      scaleMin: 0,
      scaleMax: 0,
      colLeft: '',
      colRight: '',
      isDep: true,
      isReg: false,
      isAcad: false,
      zoomDep: undefined,
      prefixClass: 'FR-',
      leftColProps: {
        localisation: '',
        names: [],
        min: 0,
        max: 0,
        colMin: '',
        colMax: '',
        value: 0,
        valueNat: 0,
        levelNat: false,
        locaParent: 'en France',
        date: '',
        textMention: '',
        borderDefault: ''
      },
      FranceProps: {
        viewBox: '0 0 262 262',
        displayDep: {},
        colorStroke: '#FFFFFF'
      },
      tooltip: {
        top: '0px',
        left: '0px',
        visibility: 'hidden',
        value: 0,
        place: ''
      },
      displayFrance: '',
      displayGuadeloupe: '',
      displayMartinique: '',
      displayMayotte: '',
      displayReunion: '',
      displayGuyanne: '',
      colorStrokeDOM: '#FFFFFF',
      textMention: ''
    }
  },
  props: {
    data: {
      type: String,
      required: true
    },
    valuenat: {
      type: Number,
      default: undefined
    },
    date: {
      type: String,
      default: undefined
    },
    level: {
      type: String,
      default: 'dep'
    },
    name: {
      type: String,
      default: 'Data'
    },
    color: {
      type: String,
      default: 'green-bourgeon'
    }
  },
  methods: {
    createChart () {
      this.leftColProps.date = this.date
      const parentWidget = document.getElementById(this.widgetId)
      const self = this

      // Define colorscale
      this.dataParse = JSON.parse(this.data)
      const values = []

      // Fill Map
      let listDep = []
      self.FranceProps.displayDep = {}

      if (this.zoomDep !== undefined) {
        if (this.level === 'dep') {
          const a = this.getDep(this.zoomDep).region_value
          listDep = this.getDepsFromReg(a)
        } else if (this.level === 'reg') {
          listDep = this.getAllReg()
        } else {
          listDep = [this.getAcad(this.zoomDep).value]
        }
        listDep.forEach(function (key, j) {
          values.push(self.dataParse[key])
        })
      } else {
        for (const key in self.dataParse) {
          values.push(self.dataParse[key])
        }
      }

      this.scaleMin = Math.min.apply(null, values)
      this.scaleMax = Math.max.apply(null, values)
      const x = linear_linear().domain([this.scaleMin, this.scaleMax]).range([this.colLeft, this.colRight])

      let xmin = []
      let xmax = []
      let ymin = []
      let ymax = []

      for (const key in self.dataParse) {
        const className = this.getClassMap(key, this.level)
        const elCol = parentWidget.getElementsByClassName(className)
        if (self.zoomDep === undefined) {
          elCol.length !== 0 && elCol[0].setAttribute('fill', x(self.dataParse[key]))
          self.FranceProps.displayDep[className] = ''
        } else {
          const polygon = document.querySelector('.' + className).getBBox()
          if (self.zoomDep === key) {
            elCol.length !== 0 && elCol[0].setAttribute('fill', x(self.dataParse[key]))
            self.FranceProps.displayDep[className] = ''
            xmin.push(polygon.x)
            ymin.push(polygon.y)
            xmax.push(polygon.x + polygon.width)
            ymax.push(polygon.y + polygon.height)
          } else if (listDep.includes(key)) {
            elCol.length !== 0 && elCol[0].setAttribute('fill', chroma_default()(self.colLeft).alpha(0.72).hex())
            self.FranceProps.displayDep[className] = ''
            xmin.push(polygon.x)
            ymin.push(polygon.y)
            xmax.push(polygon.x + polygon.width)
            ymax.push(polygon.y + polygon.height)
          } else {
            elCol.length !== 0 && elCol[0].setAttribute('fill', 'rgba(255, 255, 255, 0)')
            self.FranceProps.displayDep[className] = 'none'
          }
        }
      }

      if (this.zoomDep !== undefined) {
        if (this.level === 'dep') {
          this.leftColProps.localisation = this.getDep(this.zoomDep).label
          xmin = Math.min.apply(null, xmin)
          ymin = Math.min.apply(null, ymin)
          xmax = Math.max.apply(null, xmax)
          ymax = Math.max.apply(null, ymax)
          const width = xmax - xmin
          const height = ymax - ymin
          const size = Math.max(width, height)
          this.FranceProps.viewBox = xmin + ' ' + ymin + ' ' + size + ' ' + size
        } else if (this.level === 'reg') {
          this.leftColProps.localisation = this.getReg(this.zoomDep).label
        } else {
          this.leftColProps.localisation = this.getAcad(this.zoomDep).label
        }
        this.leftColProps.value = this.dataParse[this.zoomDep]
        this.leftColProps.levelNat = (this.valuenat !== undefined)
        this.leftColProps.valueNat = this.valuenat

        if (this.level === 'dep') {
          this.displayFrance = 'none'
          this.displayGuadeloupe = 'none'
          this.displayMartinique = 'none'
          this.displayMayotte = 'none'
          this.displayReunion = 'none'
          this.displayGuyanne = 'none'
          if ((self.zoomDep === '971' && self.level === 'dep') || (self.zoomDep === '01' && self.level === 'reg')) {
            this.displayGuadeloupe = ''
          } else if ((self.zoomDep === '972' && self.level === 'dep') || (self.zoomDep === '02' && self.level === 'reg')) {
            this.displayMartinique = ''
          } else if ((self.zoomDep === '973' && self.level === 'dep') || (self.zoomDep === '03' && self.level === 'reg')) {
            this.displayGuyanne = ''
          } else if ((self.zoomDep === '974' && self.level === 'dep') || (self.zoomDep === '04' && self.level === 'reg')) {
            this.displayReunion = ''
          } else if ((self.zoomDep === '976' && self.level === 'dep') || (self.zoomDep === '06' && self.level === 'reg')) {
            this.displayMayotte = ''
          } else {
            this.displayFrance = ''
          }
        }
      } else {
        this.leftColProps.localisation = 'France'
        this.leftColProps.value = this.valuenat
        this.leftColProps.levelNat = false
        if (this.level === 'dep') {
          this.FranceProps.viewBox = '0 0 262 262'
        } else if (this.level === 'reg') {
          this.FranceProps.viewBox = '0 0 800 800'
        } else {
          this.FranceProps.viewBox = '0 0 700 700'
        }
        this.displayFrance = ''
        this.displayGuadeloupe = ''
        this.displayMartinique = ''
        this.displayMayotte = ''
        this.displayReunion = ''
        this.displayGuyanne = ''
      }

      // Fill leftCol
      this.leftColProps.names = this.name
      this.leftColProps.min = this.scaleMin
      this.leftColProps.max = this.scaleMax
      this.leftColProps.colMin = this.colLeft
      this.leftColProps.colMax = this.colRight
    },
    displayTooltip (e) {
      if (dist.isMobile) return
      const parentWidget = document.getElementById(this.widgetId)
      let hoverdep = e.target.className.baseVal.replace(/FR|-|dep|reg|acad/g, '')

      let className
      if (hoverdep.includes('DOM')) {
        hoverdep = hoverdep.replace(/DOM/g, '')
        className = 'FR-DOM-' + hoverdep
        if (this.level === 'reg') {
          hoverdep = this.getDep(hoverdep).region_value
        }
      } else {
        className = this.getClassMap(hoverdep, this.level)
      }

      const elCol = parentWidget.getElementsByClassName(className)
      elCol[0].style.opacity = '0.72'
      this.tooltip.value = this.dataParse[hoverdep]
      if (this.level === 'dep') {
        this.tooltip.place = this.getDep(hoverdep).label
      } else if (this.level === 'reg') {
        this.tooltip.place = this.getReg(hoverdep).label
      } else {
        this.tooltip.place = this.getAcad(hoverdep).label
      }

      const elem = parentWidget.getElementsByClassName('map_tooltip')[0]
      const tooltipRect = elem.getBoundingClientRect()
      const tooltipWidth = tooltipRect.width
      const tooltipHeight = tooltipRect.height

      const containerRect = e.target.getBoundingClientRect()
      let tooltipX = containerRect.left + ((containerRect.width - tooltipWidth) / 2)
      let tooltipY = containerRect.top - tooltipHeight

      const limitsRect = parentWidget.getBoundingClientRect()
      if (tooltipY < limitsRect.top) {
        tooltipY = containerRect.bottom
      }
      if (tooltipX + tooltipWidth > limitsRect.right) {
        tooltipX = containerRect.right - tooltipWidth - 10
        tooltipY = containerRect.top - tooltipHeight / 2
      }

      this.tooltip.top = tooltipY + 'px'
      this.tooltip.left = tooltipX + 'px'
      this.tooltip.visibility = 'visible'
    },
    hideTooltip (e) {
      if (dist.isMobile) return
      this.tooltip.visibility = 'hidden'
      const parentWidget = document.getElementById(this.widgetId)
      let hoverdep = e.target.className.baseVal.replace(/FR|-|dep|reg|acad/g, '')
      let className
      if (hoverdep.includes('DOM')) {
        hoverdep = hoverdep.replace(/DOM/g, '')
        className = 'FR-DOM-' + hoverdep
        if (this.level === 'reg') {
          hoverdep = this.getDep(hoverdep).region_value
        }
      } else {
        className = this.getClassMap(hoverdep, this.level)
      }

      const elCol = parentWidget.getElementsByClassName(className)
      elCol[0].style.opacity = '1'
    },
    changeGeoLevel (e) {
      // Get clicked departement
      let clickdep
      try {
        clickdep = e.path[1]._prevClass
      } catch (error) {
        try {
          clickdep = e.explicitOriginalTarget && e.explicitOriginalTarget._prevClass
          if (clickdep === undefined) {
            clickdep = e.explicitOriginalTarget.parentNode._prevClass
          }
        } catch (error) {
          clickdep = e.toElement && e.toElement._prevClass
          if (clickdep === undefined) {
            clickdep = e.toElement.parentElement._prevClass
          }
        }
      }
      if (clickdep === 'France') {
        clickdep = e.target.className.baseVal.replace(/FR|-|dep|reg|acad/g, '')
      } else {
        clickdep = e.target.className.baseVal.replace(/FR|-|dep|reg|acad/g, '')
      }

      if (clickdep.includes('DOM')) {
        clickdep = clickdep.replace(/DOM/g, '')
        if (this.level === 'reg') {
          clickdep = this.getDep(clickdep).region_value
        }
      }
      this.zoomDep = clickdep
      this.createChart()
    },
    resetGeoFilters () {
      this.zoomDep = undefined
      this.createChart()
    },

    changeTheme (theme) {
      this.textMention = this.getHexaFromToken('text-mention-grey', theme)
      this.leftColProps.textMention = this.textMention
      this.leftColProps.borderDefault = this.getHexaFromToken('border-default-grey', theme)
      if (theme === 'light') {
        this.colLeft = '#eeeeee'
        this.colRight = this.getHexaFromName(this.color)
        this.FranceProps.colorStroke = '#FFFFFF'
        this.colorStrokeDOM = '#FFFFFF'
      } else {
        this.colLeft = this.getHexaFromName(this.color)
        this.colRight = '#eeeeee'
        this.FranceProps.colorStroke = '#161616'
        this.colorStrokeDOM = '#161616'
      }
      this.createChart()
    }
  },
  created () {
    this.chartId = 'myChart' + Math.floor(Math.random() * (1000))
    this.widgetId = 'widget' + Math.floor(Math.random() * (1000))
    this.isDep = (this.level === 'dep')
    this.isReg = (this.level === 'reg')
    this.isAcad = (this.level === 'acad')
    this.prefixClass = 'FR-' + this.level + '-'
  },
  mounted () {
    const element = document.documentElement // Reference à l'element <html> du DOM
    element.addEventListener('dsfr.theme', (e) => {
      this.changeTheme(e.detail.theme)
    })
  }
});


;// CONCATENATED MODULE: ./src/components/MapChart.vue?vue&type=script&lang=js
 /* harmony default export */ var components_MapChartvue_type_script_lang_js = (MapChartvue_type_script_lang_js); 
;// CONCATENATED MODULE: ./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-62.use[0]!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-62.use[1]!./node_modules/@vue/vue-loader-v15/lib/loaders/stylePostLoader.js!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-62.use[2]!./node_modules/sass-loader/dist/cjs.js??clonedRuleSet-62.use[3]!./node_modules/@vue/vue-loader-v15/lib/index.js??vue-loader-options!./src/components/MapChart.vue?vue&type=style&index=0&id=3812216a&prod&scoped=true&lang=scss
// extracted by mini-css-extract-plugin

;// CONCATENATED MODULE: ./src/components/MapChart.vue?vue&type=style&index=0&id=3812216a&prod&scoped=true&lang=scss

;// CONCATENATED MODULE: ./src/components/MapChart.vue



;


/* normalize component */

var MapChart_component = normalizeComponent(
  components_MapChartvue_type_script_lang_js,
  render,
  staticRenderFns,
  false,
  null,
  "3812216a",
  null
  
)

/* harmony default export */ var MapChart = (MapChart_component.exports);
;// CONCATENATED MODULE: ./node_modules/vue-custom-element/dist/vue-custom-element.esm.js
/**
  * vue-custom-element v3.3.0
  * (c) 2021 Karol Fabjańczuk
  * @license MIT
  */
/**
 * ES6 Object.getPrototypeOf Polyfill
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/setPrototypeOf
 */

Object.setPrototypeOf = Object.setPrototypeOf || setPrototypeOf;

function setPrototypeOf(obj, proto) {
  obj.__proto__ = proto;
  return obj;
}

var setPrototypeOf_1 = setPrototypeOf.bind(Object);

function isES2015() {
  if (typeof Symbol === 'undefined' || typeof Reflect === 'undefined' || typeof Proxy === 'undefined' || Object.isSealed(Proxy)) return false;

  return true;
}

var isES2015$1 = isES2015();

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function _CustomElement() {
  return Reflect.construct(HTMLElement, [], this.__proto__.constructor);
}


Object.setPrototypeOf(_CustomElement.prototype, HTMLElement.prototype);
Object.setPrototypeOf(_CustomElement, HTMLElement);
function registerCustomElement(tag) {
  var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

  if (typeof customElements === 'undefined') {
    return;
  }

  function constructorCallback() {
    if (options.shadow === true && HTMLElement.prototype.attachShadow) {
      this.attachShadow({ mode: 'open' });
    }
    typeof options.constructorCallback === 'function' && options.constructorCallback.call(this);
  }
  function connectedCallback() {
    typeof options.connectedCallback === 'function' && options.connectedCallback.call(this);
  }

  function disconnectedCallback() {
    typeof options.disconnectedCallback === 'function' && options.disconnectedCallback.call(this);
  }

  function attributeChangedCallback(name, oldValue, value) {
    typeof options.attributeChangedCallback === 'function' && options.attributeChangedCallback.call(this, name, oldValue, value);
  }

  function define(tagName, CustomElement) {
    var existingCustomElement = customElements.get(tagName);
    return typeof existingCustomElement !== 'undefined' ? existingCustomElement : customElements.define(tagName, CustomElement);
  }

  if (isES2015$1) {
    var CustomElement = function (_CustomElement2) {
      _inherits(CustomElement, _CustomElement2);

      function CustomElement(self) {
        var _ret;

        _classCallCheck(this, CustomElement);

        var _this = _possibleConstructorReturn(this, (CustomElement.__proto__ || Object.getPrototypeOf(CustomElement)).call(this));

        var me = self ? HTMLElement.call(self) : _this;

        constructorCallback.call(me);
        return _ret = me, _possibleConstructorReturn(_this, _ret);
      }

      _createClass(CustomElement, null, [{
        key: 'observedAttributes',
        get: function get() {
          return options.observedAttributes || [];
        }
      }]);

      return CustomElement;
    }(_CustomElement);

    CustomElement.prototype.connectedCallback = connectedCallback;
    CustomElement.prototype.disconnectedCallback = disconnectedCallback;
    CustomElement.prototype.attributeChangedCallback = attributeChangedCallback;

    define(tag, CustomElement);
    return CustomElement;
  } else {
    var _CustomElement3 = function _CustomElement3(self) {
      var me = self ? HTMLElement.call(self) : this;

      constructorCallback.call(me);
      return me;
    };

    _CustomElement3.observedAttributes = options.observedAttributes || [];

    _CustomElement3.prototype = Object.create(HTMLElement.prototype, {
      constructor: {
        configurable: true,
        writable: true,
        value: _CustomElement3
      }
    });

    _CustomElement3.prototype.connectedCallback = connectedCallback;
    _CustomElement3.prototype.disconnectedCallback = disconnectedCallback;
    _CustomElement3.prototype.attributeChangedCallback = attributeChangedCallback;

    define(tag, _CustomElement3);
    return _CustomElement3;
  }
}

var vue_custom_element_esm_camelizeRE = /-(\w)/g;
var vue_custom_element_esm_camelize = function camelize(str) {
  return str.replace(vue_custom_element_esm_camelizeRE, function (_, c) {
    return c ? c.toUpperCase() : '';
  });
};
var vue_custom_element_esm_hyphenateRE = /([^-])([A-Z])/g;
var vue_custom_element_esm_hyphenate = function hyphenate(str) {
  return str.replace(vue_custom_element_esm_hyphenateRE, '$1-$2').replace(vue_custom_element_esm_hyphenateRE, '$1-$2').toLowerCase();
};

function vue_custom_element_esm_toArray(list) {
  var start = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;

  var i = list.length - start;
  var ret = new Array(i);
  while (i--) {
    ret[i] = list[i + start];
  }
  return ret;
}

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function convertAttributeValue(value, overrideType) {
  if (value === null || value === undefined) {
    return overrideType === Boolean ? false : undefined;
  }
  var propsValue = value;
  var isBoolean = ['true', 'false'].indexOf(value) > -1;
  var valueParsed = parseFloat(propsValue, 10);
  var isNumber = !isNaN(valueParsed) && isFinite(propsValue) && typeof propsValue === 'string' && !propsValue.match(/^0+[^.]\d*$/g);

  if (overrideType && overrideType !== Boolean && (typeof propsValue === 'undefined' ? 'undefined' : _typeof(propsValue)) !== overrideType) {
    propsValue = overrideType(value);
  } else if (isBoolean || overrideType === Boolean) {
    propsValue = propsValue === '' ? true : propsValue === 'true' || propsValue === true;
  } else if (isNumber) {
    propsValue = valueParsed;
  }

  return propsValue;
}

function extractProps(collection, props) {
  if (collection && collection.length) {
    collection.forEach(function (prop) {
      var camelCaseProp = vue_custom_element_esm_camelize(prop);
      props.camelCase.indexOf(camelCaseProp) === -1 && props.camelCase.push(camelCaseProp);
    });
  } else if (collection && (typeof collection === 'undefined' ? 'undefined' : _typeof(collection)) === 'object') {
    for (var prop in collection) {
      var camelCaseProp = vue_custom_element_esm_camelize(prop);
      props.camelCase.indexOf(camelCaseProp) === -1 && props.camelCase.push(camelCaseProp);

      if (collection[camelCaseProp] && collection[camelCaseProp].type) {
        props.types[prop] = [].concat(collection[camelCaseProp].type)[0];
      }
    }
  }
}

function getProps() {
  var componentDefinition = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

  var props = {
    camelCase: [],
    hyphenate: [],
    types: {}
  };

  if (componentDefinition.mixins) {
    componentDefinition.mixins.forEach(function (mixin) {
      extractProps(mixin.props, props);
    });
  }

  if (componentDefinition.extends && componentDefinition.extends.props) {
    var parentProps = componentDefinition.extends.props;


    extractProps(parentProps, props);
  }

  extractProps(componentDefinition.props, props);

  props.camelCase.forEach(function (prop) {
    props.hyphenate.push(vue_custom_element_esm_hyphenate(prop));
  });

  return props;
}

function reactiveProps(element, props) {
  props.camelCase.forEach(function (name, index) {
    Object.defineProperty(element, name, {
      get: function get() {
        return this.__vue_custom_element__[name];
      },
      set: function set(value) {
        if (((typeof value === 'undefined' ? 'undefined' : _typeof(value)) === 'object' || typeof value === 'function') && this.__vue_custom_element__) {
          var propName = props.camelCase[index];
          this.__vue_custom_element__[propName] = value;
        } else {
          var type = props.types[props.camelCase[index]];
          this.setAttribute(props.hyphenate[index], convertAttributeValue(value, type));
        }
      }
    });
  });
}

function getPropsData(element, componentDefinition, props) {
  var propsData = componentDefinition.propsData || {};

  props.hyphenate.forEach(function (name, index) {
    var propCamelCase = props.camelCase[index];
    var propValue = element.attributes[name] || element[propCamelCase];

    var type = null;
    if (props.types[propCamelCase]) {
      type = props.types[propCamelCase];
    }

    if (propValue instanceof Attr) {
      propsData[propCamelCase] = convertAttributeValue(propValue.value, type);
    } else if (typeof propValue !== 'undefined') {
      propsData[propCamelCase] = propValue;
    }
  });

  return propsData;
}

function getAttributes(children) {
  var attributes = {};

  vue_custom_element_esm_toArray(children.attributes).forEach(function (attribute) {
    attributes[attribute.nodeName === 'vue-slot' ? 'slot' : attribute.nodeName] = attribute.nodeValue;
  });

  return attributes;
}

function getChildNodes(element) {
  if (element.childNodes.length) return element.childNodes;
  if (element.content && element.content.childNodes && element.content.childNodes.length) {
    return element.content.childNodes;
  }

  var placeholder = document.createElement('div');

  placeholder.innerHTML = element.innerHTML;

  return placeholder.childNodes;
}

function templateElement(createElement, element, elementOptions) {
  var templateChildren = getChildNodes(element);

  var vueTemplateChildren = vue_custom_element_esm_toArray(templateChildren).map(function (child) {
    if (child.nodeName === '#text') return child.nodeValue;

    return createElement(child.tagName, {
      attrs: getAttributes(child),
      domProps: {
        innerHTML: child.innerHTML
      }
    });
  });

  elementOptions.slot = element.id;

  return createElement('template', elementOptions, vueTemplateChildren);
}

function getSlots() {
  var children = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var createElement = arguments[1];

  var slots = [];
  vue_custom_element_esm_toArray(children).forEach(function (child) {
    if (child.nodeName === '#text') {
      if (child.nodeValue.trim()) {
        slots.push(createElement('span', child.nodeValue));
      }
    } else if (child.nodeName !== '#comment') {
      var attributes = getAttributes(child);
      var elementOptions = {
        attrs: attributes,
        domProps: {
          innerHTML: child.innerHTML === '' ? child.innerText : child.innerHTML
        }
      };

      if (attributes.slot) {
        elementOptions.slot = attributes.slot;
        attributes.slot = undefined;
      }

      var slotVueElement = child.tagName === 'TEMPLATE' ? templateElement(createElement, child, elementOptions) : createElement(child.tagName, elementOptions);

      slots.push(slotVueElement);
    }
  });

  return slots;
}

function customEvent(eventName, detail) {
  var params = { bubbles: false, cancelable: false, detail: detail };
  var event = void 0;
  if (typeof window.CustomEvent === 'function') {
    event = new CustomEvent(eventName, params);
  } else {
    event = document.createEvent('CustomEvent');
    event.initCustomEvent(eventName, params.bubbles, params.cancelable, params.detail);
  }
  return event;
}

function customEmit(element, eventName) {
  for (var _len = arguments.length, args = Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
    args[_key - 2] = arguments[_key];
  }

  var event = customEvent(eventName, [].concat(args));
  element.dispatchEvent(event);
}

function createVueInstance(element, Vue, componentDefinition, props, options) {
  if (element.__vue_custom_element__) {
    return Promise.resolve(element);
  }
  var ComponentDefinition = Vue.util.extend({}, componentDefinition);
  var propsData = getPropsData(element, ComponentDefinition, props);
  var vueVersion = Vue.version && parseInt(Vue.version.split('.')[0], 10) || 0;

  function beforeCreate() {
    this.$emit = function emit() {
      var _proto__$$emit;

      for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      customEmit.apply(undefined, [element].concat(args));
      this.__proto__ && (_proto__$$emit = this.__proto__.$emit).call.apply(_proto__$$emit, [this].concat(args));
    };
  }
  ComponentDefinition.beforeCreate = [].concat(ComponentDefinition.beforeCreate || [], beforeCreate);

  if (ComponentDefinition._compiled) {
    var constructorOptions = {};
    var _constructor = ComponentDefinition._Ctor;
    if (_constructor) {
      constructorOptions = Object.keys(_constructor).map(function (key) {
        return _constructor[key];
      })[0].options;
    }
    constructorOptions.beforeCreate = ComponentDefinition.beforeCreate;
  }

  var rootElement = void 0;

  if (vueVersion >= 2) {
    var elementOriginalChildren = element.cloneNode(true).childNodes;
    rootElement = {
      propsData: propsData,
      props: props.camelCase,
      computed: {
        reactiveProps: function reactiveProps$$1() {
          var _this = this;

          var reactivePropsList = {};
          props.camelCase.forEach(function (prop) {
            typeof _this[prop] !== 'undefined' && (reactivePropsList[prop] = _this[prop]);
          });

          return reactivePropsList;
        }
      },
      render: function render(createElement) {
        var data = {
          props: this.reactiveProps
        };

        return createElement(ComponentDefinition, data, getSlots(elementOriginalChildren, createElement));
      }
    };
  } else if (vueVersion === 1) {
    rootElement = ComponentDefinition;
    rootElement.propsData = propsData;
  } else {
    rootElement = ComponentDefinition;
    var propsWithDefault = {};
    Object.keys(propsData).forEach(function (prop) {
      propsWithDefault[prop] = { default: propsData[prop] };
    });
    rootElement.props = propsWithDefault;
  }

  var elementInnerHtml = vueVersion >= 2 ? '<div></div>' : ('<div>' + element.innerHTML + '</div>').replace(/vue-slot=/g, 'slot=');
  if (options.shadow && element.shadowRoot) {
    element.shadowRoot.innerHTML = elementInnerHtml;
    rootElement.el = element.shadowRoot.children[0];
  } else {
    element.innerHTML = elementInnerHtml;
    rootElement.el = element.children[0];
  }

  if (options.shadow && options.shadowCss && element.shadowRoot) {
    var style = document.createElement('style');
    style.type = 'text/css';
    style.appendChild(document.createTextNode(options.shadowCss));

    element.shadowRoot.appendChild(style);
  }

  reactiveProps(element, props);

  if (typeof options.beforeCreateVueInstance === 'function') {
    rootElement = options.beforeCreateVueInstance(rootElement) || rootElement;
  }

  return Promise.resolve(rootElement).then(function (vueOpts) {
    element.__vue_custom_element__ = new Vue(vueOpts);
    element.__vue_custom_element_props__ = props;
    element.getVueInstance = function () {
      var vueInstance = element.__vue_custom_element__;
      return vueInstance.$children.length ? vueInstance.$children[0] : vueInstance;
    };

    element.removeAttribute('vce-cloak');
    element.setAttribute('vce-ready', '');
    customEmit(element, 'vce-ready');
    return element;
  });
}

function install(Vue) {
  Vue.customElement = function vueCustomElement(tag, componentDefinition) {
    var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

    var isAsyncComponent = typeof componentDefinition === 'function';
    var optionsProps = isAsyncComponent && { props: options.props || [] };
    var props = getProps(isAsyncComponent ? optionsProps : componentDefinition);

    var CustomElement = registerCustomElement(tag, {
      constructorCallback: function constructorCallback() {
        typeof options.constructorCallback === 'function' && options.constructorCallback.call(this);
      },
      connectedCallback: function connectedCallback() {
        var _this = this;

        var asyncComponentPromise = isAsyncComponent && componentDefinition();
        var isAsyncComponentPromise = asyncComponentPromise && asyncComponentPromise.then && typeof asyncComponentPromise.then === 'function';

        typeof options.connectedCallback === 'function' && options.connectedCallback.call(this);

        if (isAsyncComponent && !isAsyncComponentPromise) {
          throw new Error('Async component ' + tag + ' do not returns Promise');
        }
        if (!this.__detached__) {
          if (isAsyncComponentPromise) {
            asyncComponentPromise.then(function (lazyComponent) {
              var lazyProps = getProps(lazyComponent);
              createVueInstance(_this, Vue, lazyComponent, lazyProps, options).then(function () {
                typeof options.vueInstanceCreatedCallback === 'function' && options.vueInstanceCreatedCallback.call(_this);
              });
            });
          } else {
            createVueInstance(this, Vue, componentDefinition, props, options).then(function () {
              typeof options.vueInstanceCreatedCallback === 'function' && options.vueInstanceCreatedCallback.call(_this);
            });
          }
        }

        this.__detached__ = false;
      },
      disconnectedCallback: function disconnectedCallback() {
        var _this2 = this;

        this.__detached__ = true;
        typeof options.disconnectedCallback === 'function' && options.disconnectedCallback.call(this);

        options.destroyTimeout !== null && setTimeout(function () {
          if (_this2.__detached__ && _this2.__vue_custom_element__) {
            _this2.__detached__ = false;
            _this2.__vue_custom_element__.$destroy(true);
            delete _this2.__vue_custom_element__;
            delete _this2.__vue_custom_element_props__;
          }
        }, options.destroyTimeout || 3000);
      },
      attributeChangedCallback: function attributeChangedCallback(name, oldValue, value) {
        if (this.__vue_custom_element__ && typeof value !== 'undefined') {
          var nameCamelCase = vue_custom_element_esm_camelize(name);
          typeof options.attributeChangedCallback === 'function' && options.attributeChangedCallback.call(this, name, oldValue, value);
          var type = this.__vue_custom_element_props__.types[nameCamelCase];
          this.__vue_custom_element__[nameCamelCase] = convertAttributeValue(value, type);
        }
      },


      observedAttributes: props.hyphenate,

      shadow: !!options.shadow && !!HTMLElement.prototype.attachShadow
    });

    return CustomElement;
  };
}

if (typeof window !== 'undefined' && window.Vue) {
  window.Vue.use(install);
  if (install.installed) {
    install.installed = false;
  }
}

/* harmony default export */ var vue_custom_element_esm = (install);

;// CONCATENATED MODULE: ./src/MapChart.js






Vue.config.productionTip = false

Vue.use(vue_custom_element_esm)

Vue.customElement('map-chart', MapChart)

;// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js



}();
/******/ 	return __webpack_exports__;
/******/ })()
;
});