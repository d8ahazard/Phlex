/* _GlobalPrefix_ */
/* _Module_:homescreen */
try {
    'use strict';
    var k, aa = aa || {},
        n = this,
        ba = function(a) {
            return void 0 !== a
        },
        ca = function() {},
        da = function(a) {
            var b = typeof a;
            if ("object" == b)
                if (a) {
                    if (a instanceof Array) return "array";
                    if (a instanceof Object) return b;
                    var c = Object.prototype.toString.call(a);
                    if ("[object Window]" == c) return "object";
                    if ("[object Array]" == c || "number" == typeof a.length && "undefined" != typeof a.splice && "undefined" != typeof a.propertyIsEnumerable && !a.propertyIsEnumerable("splice")) return "array";
                    if ("[object Function]" == c || "undefined" != typeof a.call &&
                        "undefined" != typeof a.propertyIsEnumerable && !a.propertyIsEnumerable("call")) return "function"
                } else return "null";
            else if ("function" == b && "undefined" == typeof a.call) return "object";
            return b
        },
        p = function(a) {
            return "array" == da(a)
        },
        ea = function(a) {
            var b = da(a);
            return "array" == b || "object" == b && "number" == typeof a.length
        },
        q = function(a) {
            return "string" == typeof a
        },
        fa = function(a) {
            return "number" == typeof a
        },
        ia = function(a) {
            return "function" == da(a)
        },
        ja = function(a, b, c) {
            return a.call.apply(a.bind, arguments)
        },
        ka = function(a, b,
            c) {
            if (!a) throw Error();
            if (2 < arguments.length) {
                var d = Array.prototype.slice.call(arguments, 2);
                return function() {
                    var c = Array.prototype.slice.call(arguments);
                    Array.prototype.unshift.apply(c, d);
                    return a.apply(b, c)
                }
            }
            return function() {
                return a.apply(b, arguments)
            }
        },
        r = function(a, b, c) {
            Function.prototype.bind && -1 != Function.prototype.bind.toString().indexOf("native code") ? r = ja : r = ka;
            return r.apply(null, arguments)
        },
        la = function(a, b) {
            var c = Array.prototype.slice.call(arguments, 1);
            return function() {
                var b = c.slice();
                b.push.apply(b, arguments);
                return a.apply(this, b)
            }
        },
        t = Date.now || function() {
            return +new Date
        },
        ma = function(a, b) {
            a = a.split(".");
            var c = n;
            a[0] in c || !c.execScript || c.execScript("var " + a[0]);
            for (var d; a.length && (d = a.shift());) !a.length && ba(b) ? c[d] = b : c[d] && c[d] !== Object.prototype[d] ? c = c[d] : c = c[d] = {}
        },
        v = function(a, b) {
            function c() {}
            c.prototype = b.prototype;
            a.V = b.prototype;
            a.prototype = new c;
            a.prototype.constructor = a;
            a.Qb = function(a, c, f) {
                for (var d = Array(arguments.length - 2), e = 2; e < arguments.length; e++) d[e - 2] = arguments[e];
                return b.prototype[c].apply(a, d)
            }
        };
    var pa = function(a, b) {
            var c;
            c = a.release_track || "";
            if (-1 != c.indexOf("stable-channel")) c = "";
            else {
                b = "(" + b + ") Build=" + (a.cast_build_revision || a.build_version);
                var d = a.ip_address;
                d && (b = b + ", IP=" + d);
                c && (b = b + ", Channel=" + c);
                c = b
            }
            this.debugInfo = c;
            this.deviceName = a.name || "";
            c = new Date;
            this.time = 2013 <= c.getFullYear() ? c.toLocaleTimeString(navigator.language, {
                hour: "numeric",
                minute: "numeric",
                timeZone: a.timezone
            }) : "";
            this.wifiImageSrc = a.wpa_configured ? "//www.gstatic.com/chromecast/home/wifi_" + na(a) + ".png" : "";
            this.wifiSignalLevel =
                na(a);
            this.wifiSsid = a.ssid || "";
            this.ssdpUdn = a.ssdp_udn || "";
            this.b = a.time_format || 0;
            "signal_level" in a && "noise_level" in a ? (c = a.noise_level, c = 15 > a.signal_level - c && -90 <= c) : c = !1;
            this.jb = c;
            this.a = oa(a)
        },
        na = function(a) {
            return "signal_level" in a && (a = a.signal_level, !(-100 >= a)) ? -55 <= a ? 4 : Math.floor(4 * (a - -100) / 45) : 0
        };
    ma("imax.EurekaInfo.getWifiSignalLevel", na);
    var oa = function(a) {
        return "opt_in" in a && (a = a.opt_in, "stats" in a) ? a.stats : !1
    };
    var qa = function(a, b, c) {
        this.g = c;
        this.c = a;
        this.h = b;
        this.b = 0;
        this.a = null
    };
    qa.prototype.get = function() {
        var a;
        0 < this.b ? (this.b--, a = this.a, this.a = a.next, a.next = null) : a = this.c();
        return a
    };
    var ra = function(a, b) {
        a.h(b);
        a.b < a.g && (a.b++, b.next = a.a, a.a = b)
    };
    var sa = function(a) {
        if (Error.captureStackTrace) Error.captureStackTrace(this, sa);
        else {
            var b = Error().stack;
            b && (this.stack = b)
        }
        a && (this.message = String(a))
    };
    v(sa, Error);
    sa.prototype.name = "CustomError";
    var ta = function(a, b, c, d, e) {
        this.reset(a, b, c, d, e)
    };
    ta.prototype.a = null;
    var ua = 0;
    ta.prototype.reset = function(a, b, c, d, e) {
        "number" == typeof e || ua++;
        this.g = d || t();
        this.h = a;
        this.c = b;
        this.b = c;
        delete this.a
    };
    var va = function() {
            this.a = t()
        },
        wa = new va;
    va.prototype.set = function(a) {
        this.a = a
    };
    va.prototype.reset = function() {
        this.set(t())
    };
    va.prototype.get = function() {
        return this.a
    };
    var xa = {
            ka: {
                1E3: {
                    other: "0K"
                },
                1E4: {
                    other: "00K"
                },
                1E5: {
                    other: "000K"
                },
                1E6: {
                    other: "0M"
                },
                1E7: {
                    other: "00M"
                },
                1E8: {
                    other: "000M"
                },
                1E9: {
                    other: "0B"
                },
                1E10: {
                    other: "00B"
                },
                1E11: {
                    other: "000B"
                },
                1E12: {
                    other: "0T"
                },
                1E13: {
                    other: "00T"
                },
                1E14: {
                    other: "000T"
                }
            },
            xa: {
                1E3: {
                    other: "0 thousand"
                },
                1E4: {
                    other: "00 thousand"
                },
                1E5: {
                    other: "000 thousand"
                },
                1E6: {
                    other: "0 million"
                },
                1E7: {
                    other: "00 million"
                },
                1E8: {
                    other: "000 million"
                },
                1E9: {
                    other: "0 billion"
                },
                1E10: {
                    other: "00 billion"
                },
                1E11: {
                    other: "000 billion"
                },
                1E12: {
                    other: "0 trillion"
                },
                1E13: {
                    other: "00 trillion"
                },
                1E14: {
                    other: "000 trillion"
                }
            }
        },
        ya = xa,
        ya = xa;
    var za = {
        AED: [2, "dh", "\u062f.\u0625.", "DH"],
        ALL: [0, "Lek", "Lek"],
        AUD: [2, "$", "AU$"],
        BDT: [2, "\u09f3", "Tk"],
        BGN: [2, "lev", "lev"],
        BRL: [2, "R$", "R$"],
        CAD: [2, "$", "C$"],
        CDF: [2, "FrCD", "CDF"],
        CHF: [2, "CHF", "CHF"],
        CLP: [0, "$", "CL$"],
        CNY: [2, "\u00a5", "RMB\u00a5"],
        COP: [32, "$", "COL$"],
        CRC: [0, "\u20a1", "CR\u20a1"],
        CZK: [50, "K\u010d", "K\u010d"],
        DKK: [50, "kr.", "kr."],
        DOP: [2, "RD$", "RD$"],
        EGP: [2, "\u00a3", "LE"],
        ETB: [2, "Birr", "Birr"],
        EUR: [2, "\u20ac", "\u20ac"],
        GBP: [2, "\u00a3", "GB\u00a3"],
        HKD: [2, "$", "HK$"],
        HRK: [2, "kn", "kn"],
        HUF: [34,
            "Ft", "Ft"
        ],
        IDR: [0, "Rp", "Rp"],
        ILS: [34, "\u20aa", "IL\u20aa"],
        INR: [2, "\u20b9", "Rs"],
        IRR: [0, "Rial", "IRR"],
        ISK: [0, "kr", "kr"],
        JMD: [2, "$", "JA$"],
        JPY: [0, "\u00a5", "JP\u00a5"],
        KRW: [0, "\u20a9", "KR\u20a9"],
        LKR: [2, "Rs", "SLRs"],
        LTL: [2, "Lt", "Lt"],
        MNT: [0, "\u20ae", "MN\u20ae"],
        MVR: [2, "Rf", "MVR"],
        MXN: [2, "$", "Mex$"],
        MYR: [2, "RM", "RM"],
        NOK: [50, "kr", "NOkr"],
        PAB: [2, "B/.", "B/."],
        PEN: [2, "S/.", "S/."],
        PHP: [2, "\u20b1", "PHP"],
        PKR: [0, "Rs", "PKRs."],
        PLN: [50, "z\u0142", "z\u0142"],
        RON: [2, "RON", "RON"],
        RSD: [0, "din", "RSD"],
        RUB: [50, "\u20bd",
            "RUB"
        ],
        SAR: [2, "Rial", "Rial"],
        SEK: [50, "kr", "kr"],
        SGD: [2, "$", "S$"],
        THB: [2, "\u0e3f", "THB"],
        TRY: [2, "TL", "YTL"],
        TWD: [2, "NT$", "NT$"],
        TZS: [0, "TSh", "TSh"],
        UAH: [2, "\u0433\u0440\u043d.", "UAH"],
        USD: [2, "$", "US$"],
        UYU: [2, "$", "$U"],
        VND: [48, "\u20ab", "VN\u20ab"],
        YER: [0, "Rial", "Rial"],
        ZAR: [2, "R", "ZAR"]
    };
    var Aa = {
            Ea: ["BC", "AD"],
            Da: ["Before Christ", "Anno Domini"],
            Ma: "JFMAMJJASOND".split(""),
            Ya: "JFMAMJJASOND".split(""),
            Ka: "January February March April May June July August September October November December".split(" "),
            Xa: "January February March April May June July August September October November December".split(" "),
            Ua: "Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec".split(" "),
            ab: "Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec".split(" "),
            eb: "Sunday Monday Tuesday Wednesday Thursday Friday Saturday".split(" "),
            cb: "Sunday Monday Tuesday Wednesday Thursday Friday Saturday".split(" "),
            Wa: "Sun Mon Tue Wed Thu Fri Sat".split(" "),
            bb: "Sun Mon Tue Wed Thu Fri Sat".split(" "),
            Bb: "SMTWTFS".split(""),
            Za: "SMTWTFS".split(""),
            Va: ["Q1", "Q2", "Q3", "Q4"],
            Ra: ["1st quarter", "2nd quarter", "3rd quarter", "4th quarter"],
            wa: ["AM", "PM"],
            fa: ["EEEE, MMMM d, y", "MMMM d, y", "MMM d, y", "M/d/yy"],
            ga: ["h:mm:ss a zzzz", "h:mm:ss a z", "h:mm:ss a", "h:mm a"],
            ya: ["{1} 'at' {0}", "{1} 'at' {0}", "{1}, {0}", "{1}, {0}"],
            Ga: 6,
            Ob: [5, 6],
            Ha: 5
        },
        Ba = Aa,
        Ba = Aa;
    var Ca = {
            Aa: ".",
            la: ",",
            Oa: "%",
            oa: "0",
            Qa: "+",
            Ja: "-",
            Fa: "E",
            Pa: "\u2030",
            Ia: "\u221e",
            La: "NaN",
            za: "#,##0.###",
            Hb: "#E0",
            Fb: "#,##0%",
            xb: "\u00a4#,##0.00",
            Ba: "USD"
        },
        w = Ca,
        w = Ca;
    var Ea = function() {
            this.a = Da
        },
        Fa = function(a, b, c) {
            if (null == b) c.push("null");
            else {
                if ("object" == typeof b) {
                    if (p(b)) {
                        var d = b;
                        b = d.length;
                        c.push("[");
                        for (var e = "", f = 0; f < b; f++) c.push(e), e = d[f], Fa(a, a.a ? a.a.call(d, String(f), e) : e, c), e = ",";
                        c.push("]");
                        return
                    }
                    if (b instanceof String || b instanceof Number || b instanceof Boolean) b = b.valueOf();
                    else {
                        c.push("{");
                        f = "";
                        for (d in b) Object.prototype.hasOwnProperty.call(b, d) && (e = b[d], "function" != typeof e && (c.push(f), Ga(d, c), c.push(":"), Fa(a, a.a ? a.a.call(b, d, e) : e, c), f = ","));
                        c.push("}");
                        return
                    }
                }
                switch (typeof b) {
                    case "string":
                        Ga(b, c);
                        break;
                    case "number":
                        c.push(isFinite(b) && !isNaN(b) ? String(b) : "null");
                        break;
                    case "boolean":
                        c.push(String(b));
                        break;
                    case "function":
                        c.push("null");
                        break;
                    default:
                        throw Error("b`" + typeof b);
                }
            }
        },
        Ha = {
            '"': '\\"',
            "\\": "\\\\",
            "/": "\\/",
            "\b": "\\b",
            "\f": "\\f",
            "\n": "\\n",
            "\r": "\\r",
            "\t": "\\t",
            "\x0B": "\\u000b"
        },
        Ia = /\uffff/.test("\uffff") ? /[\\\"\x00-\x1f\x7f-\uffff]/g : /[\\\"\x00-\x1f\x7f-\xff]/g,
        Ga = function(a, b) {
            b.push('"', a.replace(Ia, function(a) {
                var b = Ha[a];
                b || (b = "\\u" + (a.charCodeAt(0) | 65536).toString(16).substr(1), Ha[a] = b);
                return b
            }), '"')
        };
    var Ja = function(a, b, c) {
            for (var d in a) b.call(c, a[d], d, a)
        },
        Ka = function(a) {
            var b = [],
                c = 0,
                d;
            for (d in a) b[c++] = a[d];
            return b
        },
        La = function(a) {
            var b = [],
                c = 0,
                d;
            for (d in a) b[c++] = d;
            return b
        },
        Ma = "constructor hasOwnProperty isPrototypeOf propertyIsEnumerable toLocaleString toString valueOf".split(" "),
        Na = function(a, b) {
            for (var c, d, e = 1; e < arguments.length; e++) {
                d = arguments[e];
                for (c in d) a[c] = d[c];
                for (var f = 0; f < Ma.length; f++) c = Ma[f], Object.prototype.hasOwnProperty.call(d, c) && (a[c] = d[c])
            }
        };
    var Oa = function(a) {
        Oa[" "](a);
        return a
    };
    Oa[" "] = ca;
    var Qa = function(a, b) {
        var c = Pa;
        return Object.prototype.hasOwnProperty.call(c, a) ? c[a] : c[a] = b(a)
    };
    var Ra = String.prototype.trim ? function(a) {
            return a.trim()
        } : function(a) {
            return a.replace(/^[\s\xa0]+|[\s\xa0]+$/g, "")
        },
        Sa = String.prototype.repeat ? function(a, b) {
            return a.repeat(b)
        } : function(a, b) {
            return Array(b + 1).join(a)
        },
        y = function(a, b) {
            a = ba(void 0) ? a.toFixed(void 0) : String(a);
            var c = a.indexOf("."); - 1 == c && (c = a.length);
            return Sa("0", Math.max(0, b - c)) + a
        },
        Ta = function(a, b) {
            return a < b ? -1 : a > b ? 1 : 0
        };
    var Ua = function() {
        this.m = this.m;
        this.D = this.D
    };
    Ua.prototype.m = !1;
    Ua.prototype.c = function() {
        if (this.D)
            for (; this.D.length;) this.D.shift()()
    };
    var Va = "closure_listenable_" + (1E6 * Math.random() | 0),
        Wa = function(a) {
            return !(!a || !a[Va])
        },
        Xa = 0;
    var Ya;
    a: {
        var Za = n.navigator;
        if (Za) {
            var $a = Za.userAgent;
            if ($a) {
                Ya = $a;
                break a
            }
        }
        Ya = ""
    }
    var z = function(a) {
        return -1 != Ya.indexOf(a)
    };
    var ab = function() {};
    ab.prototype.a = null;
    var cb = function(a) {
        var b;
        (b = a.a) || (b = {}, bb(a) && (b[0] = !0, b[1] = !0), b = a.a = b);
        return b
    };
    var eb = function(a, b) {
            var c = Array.prototype.slice.call(arguments),
                d = c.shift();
            if ("undefined" == typeof d) throw Error("c");
            return d.replace(/%([0\-\ \+]*)(\d+)?(\.(\d+))?([%sfdiu])/g, function(a, b, d, h, l, m, B, u) {
                if ("%" == m) return "%";
                var e = c.shift();
                if ("undefined" == typeof e) throw Error("d");
                arguments[0] = e;
                return db[m].apply(null, arguments)
            })
        },
        db = {
            s: function(a, b, c) {
                return isNaN(c) || "" == c || a.length >= Number(c) ? a : a = -1 < b.indexOf("-", 0) ? a + Sa(" ", Number(c) - a.length) : Sa(" ", Number(c) - a.length) + a
            },
            f: function(a, b, c,
                d, e) {
                d = a.toString();
                isNaN(e) || "" == e || (d = parseFloat(a).toFixed(e));
                var f;
                f = 0 > Number(a) ? "-" : 0 <= b.indexOf("+") ? "+" : 0 <= b.indexOf(" ") ? " " : "";
                0 <= Number(a) && (d = f + d);
                if (isNaN(c) || d.length >= Number(c)) return d;
                d = isNaN(e) ? Math.abs(Number(a)).toString() : Math.abs(Number(a)).toFixed(e);
                a = Number(c) - d.length - f.length;
                return d = 0 <= b.indexOf("-", 0) ? f + d + Sa(" ", a) : f + Sa(0 <= b.indexOf("0", 0) ? "0" : " ", a) + d
            },
            d: function(a, b, c, d, e, f, g, h) {
                return db.f(parseInt(a, 10), b, c, d, 0, f, g, h)
            }
        };
    db.i = db.d;
    db.u = db.d;
    var fb = Array.prototype.indexOf ? function(a, b, c) {
            return Array.prototype.indexOf.call(a, b, c)
        } : function(a, b, c) {
            c = null == c ? 0 : 0 > c ? Math.max(0, a.length + c) : c;
            if (q(a)) return q(b) && 1 == b.length ? a.indexOf(b, c) : -1;
            for (; c < a.length; c++)
                if (c in a && a[c] === b) return c;
            return -1
        },
        gb = Array.prototype.forEach ? function(a, b, c) {
            Array.prototype.forEach.call(a, b, c)
        } : function(a, b, c) {
            for (var d = a.length, e = q(a) ? a.split("") : a, f = 0; f < d; f++) f in e && b.call(c, e[f], f, a)
        },
        hb = Array.prototype.filter ? function(a, b, c) {
            return Array.prototype.filter.call(a,
                b, c)
        } : function(a, b, c) {
            for (var d = a.length, e = [], f = 0, g = q(a) ? a.split("") : a, h = 0; h < d; h++)
                if (h in g) {
                    var l = g[h];
                    b.call(c, l, h, a) && (e[f++] = l)
                }
            return e
        },
        ib = Array.prototype.map ? function(a, b, c) {
            return Array.prototype.map.call(a, b, c)
        } : function(a, b, c) {
            for (var d = a.length, e = Array(d), f = q(a) ? a.split("") : a, g = 0; g < d; g++) g in f && (e[g] = b.call(c, f[g], g, a));
            return e
        },
        kb = function(a) {
            var b;
            a: {
                b = jb;
                for (var c = a.length, d = q(a) ? a.split("") : a, e = 0; e < c; e++)
                    if (e in d && b.call(void 0, d[e], e, a)) {
                        b = e;
                        break a
                    }
                b = -1
            }
            return 0 > b ? null : q(a) ? a.charAt(b) :
                a[b]
        },
        lb = function(a, b) {
            b = fb(a, b);
            var c;
            (c = 0 <= b) && Array.prototype.splice.call(a, b, 1);
            return c
        },
        mb = function(a) {
            return Array.prototype.concat.apply([], arguments)
        },
        nb = function(a) {
            var b = a.length;
            if (0 < b) {
                for (var c = Array(b), d = 0; d < b; d++) c[d] = a[d];
                return c
            }
            return []
        },
        pb = function(a, b) {
            a.sort(b || ob)
        },
        ob = function(a, b) {
            return a > b ? 1 : a < b ? -1 : 0
        };
    var qb = function() {
            this.b = this.a = null
        },
        sb = new qa(function() {
            return new rb
        }, function(a) {
            a.reset()
        }, 100);
    qb.prototype.remove = function() {
        var a = null;
        this.a && (a = this.a, this.a = this.a.next, this.a || (this.b = null), a.next = null);
        return a
    };
    var rb = function() {
        this.next = this.b = this.a = null
    };
    rb.prototype.set = function(a, b) {
        this.a = a;
        this.b = b;
        this.next = null
    };
    rb.prototype.reset = function() {
        this.next = this.b = this.a = null
    };
    var tb = function(a, b) {
        this.type = a;
        this.a = this.target = b;
        this.defaultPrevented = this.b = !1;
        this.sa = !0
    };
    tb.prototype.stopPropagation = function() {
        this.b = !0
    };
    tb.prototype.preventDefault = function() {
        this.defaultPrevented = !0;
        this.sa = !1
    };
    var ub = function(a, b, c, d, e) {
            this.listener = a;
            this.a = null;
            this.src = b;
            this.type = c;
            this.capture = !!d;
            this.ea = e;
            this.key = ++Xa;
            this.aa = this.ca = !1
        },
        vb = function(a) {
            a.aa = !0;
            a.listener = null;
            a.a = null;
            a.src = null;
            a.ea = null
        };
    var wb = function(a, b, c, d) {
        this.c = a;
        this.g = b;
        this.a = this.b = a;
        this.h = c || 0;
        this.j = d || 2
    };
    wb.prototype.reset = function() {
        this.a = this.b = this.c
    };
    wb.prototype.getValue = function() {
        return this.b
    };
    var xb = function(a, b) {
            this.a = a;
            this.c = !!b.pb;
            this.b = b.v;
            this.h = b.type;
            this.g = !1;
            switch (this.b) {
                case 3:
                case 4:
                case 6:
                case 16:
                case 18:
                case 2:
                case 1:
                    this.g = !0
            }
        },
        yb = function(a) {
            return 11 == a.b || 10 == a.b
        };
    var zb = /^(?:([^:/?#.]+):)?(?:\/\/(?:([^/?#]*)@)?([^/#?]*?)(?::([0-9]+))?(?=[/#?]|$))?([^?#]+)?(?:\?([^#]*))?(?:#([\s\S]*))?$/,
        Ab = function(a, b) {
            if (a) {
                a = a.split("&");
                for (var c = 0; c < a.length; c++) {
                    var d = a[c].indexOf("="),
                        e, f = null;
                    0 <= d ? (e = a[c].substring(0, d), f = a[c].substring(d + 1)) : e = a[c];
                    b(e, f ? decodeURIComponent(f.replace(/\+/g, " ")) : "")
                }
            }
        };
    var Bb = function(a) {
            this.src = a;
            this.a = {};
            this.b = 0
        },
        Db = function(a, b, c, d, e, f) {
            var g = b.toString();
            b = a.a[g];
            b || (b = a.a[g] = [], a.b++);
            var h = Cb(b, c, e, f); - 1 < h ? (a = b[h], d || (a.ca = !1)) : (a = new ub(c, a.src, g, !!e, f), a.ca = d, b.push(a));
            return a
        };
    Bb.prototype.remove = function(a, b, c, d) {
        a = a.toString();
        if (!(a in this.a)) return !1;
        var e = this.a[a];
        b = Cb(e, b, c, d);
        return -1 < b ? (vb(e[b]), Array.prototype.splice.call(e, b, 1), 0 == e.length && (delete this.a[a], this.b--), !0) : !1
    };
    var Eb = function(a, b) {
            var c = b.type;
            c in a.a && lb(a.a[c], b) && (vb(b), 0 == a.a[c].length && (delete a.a[c], a.b--))
        },
        Fb = function(a, b, c, d, e) {
            a = a.a[b.toString()];
            b = -1;
            a && (b = Cb(a, c, d, e));
            return -1 < b ? a[b] : null
        },
        Cb = function(a, b, c, d) {
            for (var e = 0; e < a.length; ++e) {
                var f = a[e];
                if (!f.aa && f.listener == b && f.capture == !!c && f.ea == d) return e
            }
            return -1
        };
    var Gb = function() {},
        Ib = function(a) {
            if ("number" == typeof a) {
                var b = new Gb;
                b.c = a;
                var c;
                c = a;
                if (0 == c) c = "Etc/GMT";
                else {
                    var d = ["Etc/GMT", 0 > c ? "-" : "+"];
                    c = Math.abs(c);
                    d.push(Math.floor(c / 60) % 100);
                    c %= 60;
                    0 != c && d.push(":", y(c, 2));
                    c = d.join("")
                }
                b.g = c;
                c = a;
                0 == c ? c = "UTC" : (d = ["UTC", 0 > c ? "+" : "-"], c = Math.abs(c), d.push(Math.floor(c / 60) % 100), c %= 60, 0 != c && d.push(":", c), c = d.join(""));
                a = Hb(a);
                b.h = [c, c];
                b.a = {
                    Kb: a,
                    na: a
                };
                b.b = [];
                return b
            }
            b = new Gb;
            b.g = a.id;
            b.c = -a.std_offset;
            b.h = a.names;
            b.a = a.names_ext;
            b.b = a.transitions;
            return b
        },
        Hb =
        function(a) {
            var b = ["GMT"];
            b.push(0 >= a ? "+" : "-");
            a = Math.abs(a);
            b.push(y(Math.floor(a / 60) % 100, 2), ":", y(a % 60, 2));
            return b.join("")
        },
        Jb = function(a, b) {
            b = Date.UTC(b.getUTCFullYear(), b.getUTCMonth(), b.getUTCDate(), b.getUTCHours(), b.getUTCMinutes()) / 36E5;
            for (var c = 0; c < a.b.length && b >= a.b[c];) c += 2;
            return 0 == c ? 0 : a.b[c - 1]
        };
    var Kb, Lb = function() {};
    v(Lb, ab);
    var Mb = function(a) {
            return (a = bb(a)) ? new ActiveXObject(a) : new XMLHttpRequest
        },
        bb = function(a) {
            if (!a.b && "undefined" == typeof XMLHttpRequest && "undefined" != typeof ActiveXObject) {
                for (var b = ["MSXML2.XMLHTTP.6.0", "MSXML2.XMLHTTP.3.0", "MSXML2.XMLHTTP", "Microsoft.XMLHTTP"], c = 0; c < b.length; c++) {
                    var d = b[c];
                    try {
                        return new ActiveXObject(d), a.b = d
                    } catch (e) {}
                }
                throw Error("e");
            }
            return a.b
        };
    Kb = new Lb;
    var Nb = function(a, b) {
            this.b = a;
            this.a = {};
            for (a = 0; a < b.length; a++) {
                var c = b[a];
                this.a[c.a] = c
            }
        },
        Ob = function(a) {
            a = Ka(a.a);
            pb(a, function(a, c) {
                return a.a - c.a
            });
            return a
        };
    var Pb = function(a) {
            if (a.J && "function" == typeof a.J) return a.J();
            if (q(a)) return a.split("");
            if (ea(a)) {
                for (var b = [], c = a.length, d = 0; d < c; d++) b.push(a[d]);
                return b
            }
            return Ka(a)
        },
        Qb = function(a, b, c) {
            if (a.forEach && "function" == typeof a.forEach) a.forEach(b, c);
            else if (ea(a) || q(a)) gb(a, b, c);
            else {
                var d;
                if (a.N && "function" == typeof a.N) d = a.N();
                else if (a.J && "function" == typeof a.J) d = void 0;
                else if (ea(a) || q(a)) {
                    d = [];
                    for (var e = a.length, f = 0; f < e; f++) d.push(f)
                } else d = La(a);
                for (var e = Pb(a), f = e.length, g = 0; g < f; g++) b.call(c, e[g], d && d[g], a)
            }
        };
    var Rb = function(a) {
            n.setTimeout(function() {
                throw a;
            }, 0)
        },
        Sb, Tb = function() {
            var a = n.MessageChannel;
            "undefined" === typeof a && "undefined" !== typeof window && window.postMessage && window.addEventListener && !z("Presto") && (a = function() {
                var a = document.createElement("IFRAME");
                a.style.display = "none";
                a.src = "";
                document.documentElement.appendChild(a);
                var b = a.contentWindow,
                    a = b.document;
                a.open();
                a.write("");
                a.close();
                var c = "callImmediate" + Math.random(),
                    d = "file:" == b.location.protocol ? "*" : b.location.protocol + "//" + b.location.host,
                    a = r(function(a) {
                        if (("*" == d || a.origin == d) && a.data == c) this.port1.onmessage()
                    }, this);
                b.addEventListener("message", a, !1);
                this.port1 = {};
                this.port2 = {
                    postMessage: function() {
                        b.postMessage(c, d)
                    }
                }
            });
            if ("undefined" !== typeof a && !z("Trident") && !z("MSIE")) {
                var b = new a,
                    c = {},
                    d = c;
                b.port1.onmessage = function() {
                    if (ba(c.next)) {
                        c = c.next;
                        var a = c.pa;
                        c.pa = null;
                        a()
                    }
                };
                return function(a) {
                    d.next = {
                        pa: a
                    };
                    d = d.next;
                    b.port2.postMessage(0)
                }
            }
            return "undefined" !== typeof document && "onreadystatechange" in document.createElement("SCRIPT") ? function(a) {
                var b = document.createElement("SCRIPT");
                b.onreadystatechange = function() {
                    b.onreadystatechange = null;
                    b.parentNode.removeChild(b);
                    b = null;
                    a();
                    a = null
                };
                document.documentElement.appendChild(b)
            } : function(a) {
                n.setTimeout(a, 0)
            }
        };
    var Wb = function(a) {
            this.b = [];
            this.a = Ba;
            "number" == typeof a ? Ub(this, a) : Vb(this, a)
        },
        Xb = [/^\'(?:[^\']|\'\')*(\'|$)/, /^(?:G+|y+|M+|k+|S+|E+|a+|h+|K+|H+|c+|L+|Q+|d+|m+|s+|v+|V+|w+|z+|Z+)/, /^[^\'GyMkSEahKHcLQdmsvVwzZ]+/],
        Yb = function(a) {
            return a.getHours ? a.getHours() : 0
        },
        Vb = function(a, b) {
            for (Zb && (b = b.replace(/\u200f/g, "")); b;) {
                for (var c = b, d = 0; d < Xb.length; ++d) {
                    var e = b.match(Xb[d]);
                    if (e) {
                        var f = e[0];
                        b = b.substring(f.length);
                        0 == d && ("''" == f ? f = "'" : (f = f.substring(1, "'" == e[1] ? f.length - 1 : f.length), f = f.replace(/\'\'/g,
                            "'")));
                        a.b.push({
                            text: f,
                            type: d
                        });
                        break
                    }
                }
                if (c === b) throw Error("f`" + b);
            }
        },
        Ub = function(a, b) {
            var c;
            if (4 > b) c = a.a.fa[b];
            else if (8 > b) c = a.a.ga[b - 4];
            else if (12 > b) c = a.a.ya[b - 8], c = c.replace("{1}", a.a.fa[b - 8]), c = c.replace("{0}", a.a.ga[b - 8]);
            else {
                Ub(a, 10);
                return
            }
            Vb(a, c)
        },
        A = function(a, b) {
            b = String(b);
            a = a.a || Ba;
            if (void 0 !== a.fb) {
                for (var c = [], d = 0; d < b.length; d++) {
                    var e = b.charCodeAt(d);
                    c.push(48 <= e && 57 >= e ? String.fromCharCode(a.fb + e - 48) : b.charAt(d))
                }
                b = c.join("")
            }
            return b
        },
        Zb = !1,
        $b = function(a) {
            if (!(a.getHours && a.getSeconds &&
                    a.getMinutes)) throw Error("h");
        },
        ac = function(a, b, c, d, e) {
            var f = b.length;
            switch (b.charAt(0)) {
                case "G":
                    return c = 0 < d.getFullYear() ? 1 : 0, 4 <= f ? a.a.Da[c] : a.a.Ea[c];
                case "y":
                    return c = d.getFullYear(), 0 > c && (c = -c), 2 == f && (c %= 100), A(a, y(c, f));
                case "M":
                    a: switch (c = d.getMonth(), f) {
                        case 5:
                            f = a.a.Ma[c];
                            break a;
                        case 4:
                            f = a.a.Ka[c];
                            break a;
                        case 3:
                            f = a.a.Ua[c];
                            break a;
                        default:
                            f = A(a, y(c + 1, f))
                    }
                    return f;
                case "k":
                    return $b(e), A(a, y(Yb(e) || 24, f));
                case "S":
                    return A(a, (e.getTime() % 1E3 / 1E3).toFixed(Math.min(3, f)).substr(2) + (3 < f ?
                        y(0, f - 3) : ""));
                case "E":
                    return c = d.getDay(), 4 <= f ? a.a.eb[c] : a.a.Wa[c];
                case "a":
                    return $b(e), f = Yb(e), a.a.wa[12 <= f && 24 > f ? 1 : 0];
                case "h":
                    return $b(e), A(a, y(Yb(e) % 12 || 12, f));
                case "K":
                    return $b(e), A(a, y(Yb(e) % 12, f));
                case "H":
                    return $b(e), A(a, y(Yb(e), f));
                case "c":
                    a: switch (c = d.getDay(), f) {
                        case 5:
                            f = a.a.Za[c];
                            break a;
                        case 4:
                            f = a.a.cb[c];
                            break a;
                        case 3:
                            f = a.a.bb[c];
                            break a;
                        default:
                            f = A(a, y(c, 1))
                    }
                    return f;
                case "L":
                    a: switch (c = d.getMonth(), f) {
                        case 5:
                            f = a.a.Ya[c];
                            break a;
                        case 4:
                            f = a.a.Xa[c];
                            break a;
                        case 3:
                            f = a.a.ab[c];
                            break a;
                        default:
                            f = A(a, y(c + 1, f))
                    }
                    return f;
                case "Q":
                    return c = Math.floor(d.getMonth() / 3), 4 > f ? a.a.Va[c] : a.a.Ra[c];
                case "d":
                    return A(a, y(d.getDate(), f));
                case "m":
                    return $b(e), A(a, y(e.getMinutes(), f));
                case "s":
                    return $b(e), A(a, y(e.getSeconds(), f));
                case "v":
                    return f = Ib(c.getTimezoneOffset()), f.g;
                case "V":
                    return a = Ib(c.getTimezoneOffset()), 2 >= f ? a.g : 0 < Jb(a, c) ? ba(a.a.Ca) ? a.a.Ca : a.a.DST_GENERIC_LOCATION : ba(a.a.na) ? a.a.na : a.a.STD_GENERIC_LOCATION;
                case "w":
                    return c = a.a.Ha, e = new Date(e.getFullYear(), e.getMonth(),
                        e.getDate()), b = a.a.Ga || 0, c = e.valueOf() + 864E5 * (((ba(c) ? c : 3) - b + 7) % 7 - ((e.getDay() + 6) % 7 - b + 7) % 7), A(a, y(Math.floor(Math.round((c - (new Date((new Date(c)).getFullYear(), 0, 1)).valueOf()) / 864E5) / 7) + 1, f));
                case "z":
                    return a = Ib(c.getTimezoneOffset()), 4 > f ? a.h[0 < Jb(a, c) ? 2 : 0] : a.h[0 < Jb(a, c) ? 3 : 1];
                case "Z":
                    return e = Ib(c.getTimezoneOffset()), 4 > f ? (f = -(e.c - Jb(e, c)), a = [0 > f ? "-" : "+"], f = Math.abs(f), a.push(y(Math.floor(f / 60) % 100, 2), y(f % 60, 2)), f = a.join("")) : f = A(a, Hb(e.c - Jb(e, c))), f;
                default:
                    return ""
            }
        };
    var cc = function() {
            this.L = w.Ba;
            this.m = 40;
            this.a = 1;
            this.H = 0;
            this.h = 3;
            this.o = this.b = 0;
            this.M = !1;
            this.F = this.D = "";
            this.w = "-";
            this.B = "";
            this.c = 1;
            this.j = !1;
            this.g = [];
            this.C = this.K = !1;
            this.I = 1;
            var a = w.za;
            a.replace(/ /g, "\u00a0");
            var b = [0];
            this.D = bc(this, a, b);
            for (var c = b[0], d = -1, e = 0, f = 0, g = 0, h = -1, l = a.length, m = !0; b[0] < l && m; b[0]++) switch (a.charAt(b[0])) {
                case "#":
                    0 < f ? g++ : e++;
                    0 <= h && 0 > d && h++;
                    break;
                case "0":
                    if (0 < g) throw Error("n`" + a);
                    f++;
                    0 <= h && 0 > d && h++;
                    break;
                case ",":
                    0 < h && this.g.push(h);
                    h = 0;
                    break;
                case ".":
                    if (0 <=
                        d) throw Error("o`" + a);
                    d = e + f + g;
                    break;
                case "E":
                    if (this.C) throw Error("p`" + a);
                    this.C = !0;
                    this.o = 0;
                    b[0] + 1 < l && "+" == a.charAt(b[0] + 1) && (b[0]++, this.M = !0);
                    for (; b[0] + 1 < l && "0" == a.charAt(b[0] + 1);) b[0]++, this.o++;
                    if (1 > e + f || 1 > this.o) throw Error("q`" + a);
                    m = !1;
                    break;
                default:
                    b[0]--, m = !1
            }
            0 == f && 0 < e && 0 <= d && (f = d, 0 == f && f++, g = e - f, e = f - 1, f = 1);
            if (0 > d && 0 < g || 0 <= d && (d < e || d > e + f) || 0 == h) throw Error("r`" + a);
            g = e + f + g;
            this.h = 0 <= d ? g - d : 0;
            0 <= d && (this.b = e + f - d, 0 > this.b && (this.b = 0));
            this.a = (0 <= d ? d : g) - e;
            this.C && (this.m = e + this.a, 0 == this.h &&
                0 == this.a && (this.a = 1));
            this.g.push(Math.max(0, h));
            this.K = 0 == d || d == g;
            c = b[0] - c;
            this.F = bc(this, a, b);
            b[0] < a.length && ";" == a.charAt(b[0]) ? (b[0]++, 1 != this.c && (this.j = !0), this.w = bc(this, a, b), b[0] += c, this.B = bc(this, a, b)) : (this.w = this.D + this.w, this.B += this.F);
            this.b = 0;
            this.h = 2;
            if (0 < this.b) throw Error("i");
            this.H = 2
        },
        ec = function(a, b) {
            var c = Math.pow(10, a.h),
                d;
            if (0 >= a.H) d = Math.round(b * c);
            else {
                d = b * c;
                var e = a.h;
                d && (a = a.H - dc(d) - 1, a < -e ? (e = Math.pow(10, e), d = Math.round(d / e) * e) : (e = Math.pow(10, a), d = Math.round(d * e) / e));
                d =
                    Math.round(d)
            }
            isFinite(d) ? (b = Math.floor(d / c), c = Math.floor(d - b * c)) : c = 0;
            return {
                ra: b,
                hb: c
            }
        },
        fc = function(a, b, c, d) {
            if (a.b > a.h) throw Error("k");
            d || (d = []);
            b = ec(a, b);
            var e = Math.pow(10, a.h),
                f = b.ra,
                g = b.hb,
                h = 0 < a.b || 0 < g || !1;
            b = a.b;
            h && (b = a.b);
            for (var l = "", m = f; 1E20 < m;) l = "0" + l, m = Math.round(m / 10);
            var l = m + l,
                B = w.Aa,
                m = w.oa.charCodeAt(0),
                u = l.length,
                C = 0;
            if (0 < f || 0 < c) {
                for (f = u; f < c; f++) d.push(String.fromCharCode(m));
                if (2 <= a.g.length)
                    for (c = 1; c < a.g.length; c++) C += a.g[c];
                c = u - C;
                if (0 < c)
                    for (var f = a.g, C = u = 0, x, ga = w.la, I = l.length, ha =
                            0; ha < I; ha++) {
                        if (d.push(String.fromCharCode(m + 1 * Number(l.charAt(ha)))), 1 < I - ha)
                            if (x = f[C], ha < c) {
                                var Of = c - ha;
                                (1 === x || 0 < x && 1 === Of % x) && d.push(ga)
                            } else C < f.length && (ha === c ? C += 1 : x === ha - c - u + 1 && (d.push(ga), u += x, C += 1))
                    } else {
                        c = l;
                        l = a.g;
                        f = w.la;
                        x = c.length;
                        ga = [];
                        for (u = l.length - 1; 0 <= u && 0 < x; u--) {
                            C = l[u];
                            for (I = 0; I < C && 0 <= x - I - 1; I++) ga.push(String.fromCharCode(m + 1 * Number(c.charAt(x - I - 1))));
                            x -= C;
                            0 < x && ga.push(f)
                        }
                        d.push.apply(d, ga.reverse())
                    }
            } else h || d.push(String.fromCharCode(m));
            (a.K || h) && d.push(B);
            a = "" + (g + e);
            for (e = a.length;
                "0" ==
                a.charAt(e - 1) && e > b + 1;) e--;
            for (f = 1; f < e; f++) d.push(String.fromCharCode(m + 1 * Number(a.charAt(f))))
        },
        gc = function(a, b, c) {
            c.push(w.Fa);
            0 > b ? (b = -b, c.push(w.Ja)) : a.M && c.push(w.Qa);
            b = "" + b;
            for (var d = w.oa, e = b.length; e < a.o; e++) c.push(d);
            c.push(b)
        },
        bc = function(a, b, c) {
            for (var d = "", e = !1, f = b.length; c[0] < f; c[0]++) {
                var g = b.charAt(c[0]);
                if ("'" == g) c[0] + 1 < f && "'" == b.charAt(c[0] + 1) ? (c[0]++, d += "'") : e = !e;
                else if (e) d += g;
                else switch (g) {
                    case "#":
                    case "0":
                    case ",":
                    case ".":
                    case ";":
                        return d;
                    case "\u00a4":
                        c[0] + 1 < f && "\u00a4" == b.charAt(c[0] +
                            1) ? (c[0]++, d += a.L) : d += za[a.L][1];
                        break;
                    case "%":
                        if (!a.j && 1 != a.c) throw Error("l");
                        if (a.j && 100 != a.c) throw Error("m");
                        a.c = 100;
                        a.j = !1;
                        d += w.Oa;
                        break;
                    case "\u2030":
                        if (!a.j && 1 != a.c) throw Error("l");
                        if (a.j && 1E3 != a.c) throw Error("m");
                        a.c = 1E3;
                        a.j = !1;
                        d += w.Pa;
                        break;
                    default:
                        d += g
                }
            }
            return d
        },
        hc = {
            prefix: "",
            ua: "",
            ha: 0
        },
        ic = function(a, b) {
            a = 1 == a.I ? ya.ka : ya.xa;
            null == a && (a = ya.ka);
            if (3 > b) return hc;
            b = Math.min(14, b);
            var c = a[Math.pow(10, b)];
            for (--b; !c && 3 <= b;) c = a[Math.pow(10, b)], b--;
            if (!c) return hc;
            a = c.other;
            return a && "0" != a ? (a = /([^0]*)(0+)(.*)/.exec(a)) ? {
                prefix: a[1],
                ua: a[3],
                ha: b + 1 - (a[2].length - 1)
            } : hc : hc
        },
        dc = function(a) {
            if (!isFinite(a)) return 0 < a ? a : 0;
            for (var b = 0; 1 <= (a /= 10);) b++;
            return b
        };
    var jc = "StopIteration" in n ? n.StopIteration : {
            message: "StopIteration",
            stack: ""
        },
        kc = function() {};
    kc.prototype.next = function() {
        throw jc;
    };
    kc.prototype.gb = function() {
        return this
    };
    var D = function() {
            this.G = {};
            this.b = this.A().a;
            this.a = this.c = null
        },
        lc = function(a, b) {
            for (var c in a.G) {
                var d = Number(c);
                a.b[d] || b.call(a, d, a.G[c])
            }
        };
    D.prototype.has = function(a) {
        return null != this.G[a.a]
    };
    D.prototype.get = function(a, b) {
        return mc(this, a.a, b)
    };
    D.prototype.set = function(a, b) {
        E(this, a.a, b)
    };
    D.prototype.equals = function(a) {
        if (!a || this.constructor != a.constructor) return !1;
        for (var b = Ob(this.A()), c = 0; c < b.length; c++) {
            var d = b[c],
                e = d.a;
            if (null != this.G[e] != (null != a.G[e])) return !1;
            if (null != this.G[e]) {
                var f = yb(d),
                    g = nc(this, e),
                    e = nc(a, e);
                if (d.c) {
                    if (g.length != e.length) return !1;
                    for (d = 0; d < g.length; d++) {
                        var h = g[d],
                            l = e[d];
                        if (f ? !h.equals(l) : h != l) return !1
                    }
                } else if (f ? !g.equals(e) : g != e) return !1
            }
        }
        return !0
    };
    var pc = function(a, b) {
        for (var c = Ob(a.A()), d = 0; d < c.length; d++) {
            var e = c[d],
                f = e.a;
            if (null != b.G[f]) {
                a.a && delete a.a[e.a];
                var g = yb(e);
                if (e.c)
                    for (var e = nc(b, f) || [], h = 0; h < e.length; h++) oc(a, f, g ? e[h].clone() : e[h]);
                else e = nc(b, f), g ? (g = nc(a, f)) ? pc(g, e) : E(a, f, e.clone()) : E(a, f, e)
            }
        }
    };
    D.prototype.clone = function() {
        var a = new this.constructor;
        a != this && (a.G = {}, a.a && (a.a = {}), pc(a, this));
        return a
    };
    var nc = function(a, b) {
            var c = a.G[b];
            if (null == c) return null;
            if (a.c) {
                if (!(b in a.a)) {
                    var d = a.c,
                        e = a.b[b];
                    if (null != c)
                        if (e.c) {
                            for (var f = [], g = 0; g < c.length; g++) f[g] = d.b(e, c[g]);
                            c = f
                        } else c = d.b(e, c);
                    return a.a[b] = c
                }
                return a.a[b]
            }
            return c
        },
        mc = function(a, b, c) {
            var d = nc(a, b);
            return a.b[b].c ? d[c || 0] : d
        },
        qc = function(a, b) {
            return a.b[b].c ? null != a.G[b] ? a.G[b].length : 0 : null != a.G[b] ? 1 : 0
        },
        E = function(a, b, c) {
            a.G[b] = c;
            a.a && (a.a[b] = c)
        },
        oc = function(a, b, c) {
            a.G[b] || (a.G[b] = []);
            a.G[b].push(c);
            a.a && delete a.a[b]
        },
        rc = function(a) {
            delete a.G[4];
            a.a && delete a.a[4]
        },
        sc = function(a, b) {
            var c = [],
                d;
            for (d in b) 0 != d && c.push(new xb(d, b[d]));
            return new Nb(a, c)
        };
    var tc = z("Opera"),
        F = z("Trident") || z("MSIE"),
        uc = z("Edge"),
        vc = z("Gecko") && !(-1 != Ya.toLowerCase().indexOf("webkit") && !z("Edge")) && !(z("Trident") || z("MSIE")) && !z("Edge"),
        wc = -1 != Ya.toLowerCase().indexOf("webkit") && !z("Edge"),
        xc = function() {
            var a = n.document;
            return a ? a.documentMode : void 0
        },
        yc;
    a: {
        var zc = "",
            Ac = function() {
                var a = Ya;
                if (vc) return /rv\:([^\);]+)(\)|;)/.exec(a);
                if (uc) return /Edge\/([\d\.]+)/.exec(a);
                if (F) return /\b(?:MSIE|rv)[: ]([^\);]+)(\)|;)/.exec(a);
                if (wc) return /WebKit\/(\S+)/.exec(a);
                if (tc) return /(?:Version)[ \/]?(\S+)/.exec(a)
            }();Ac && (zc = Ac ? Ac[1] : "");
        if (F) {
            var Bc = xc();
            if (null != Bc && Bc > parseFloat(zc)) {
                yc = String(Bc);
                break a
            }
        }
        yc = zc
    }
    var Cc = yc,
        Pa = {},
        G = function(a) {
            return Qa(a, function() {
                for (var b = 0, c = Ra(String(Cc)).split("."), d = Ra(String(a)).split("."), e = Math.max(c.length, d.length), f = 0; 0 == b && f < e; f++) {
                    var g = c[f] || "",
                        h = d[f] || "";
                    do {
                        g = /(\d*)(\D*)(.*)/.exec(g) || ["", "", "", ""];
                        h = /(\d*)(\D*)(.*)/.exec(h) || ["", "", "", ""];
                        if (0 == g[0].length && 0 == h[0].length) break;
                        b = Ta(0 == g[1].length ? 0 : parseInt(g[1], 10), 0 == h[1].length ? 0 : parseInt(h[1], 10)) || Ta(0 == g[2].length, 0 == h[2].length) || Ta(g[2], h[2]);
                        g = g[3];
                        h = h[3]
                    } while (0 == b)
                }
                return 0 <= b
            })
        },
        Dc;
    var Ec = n.document;
    Dc = Ec && F ? xc() || ("CSS1Compat" == Ec.compatMode ? parseInt(Cc, 10) : 5) : void 0;
    var Jc = function(a, b) {
            Fc || Gc();
            Hc || (Fc(), Hc = !0);
            var c = Ic,
                d = sb.get();
            d.set(a, b);
            c.b ? c.b.next = d : c.a = d;
            c.b = d
        },
        Fc, Gc = function() {
            if (-1 != String(n.Promise).indexOf("[native code]")) {
                var a = n.Promise.resolve(void 0);
                Fc = function() {
                    a.then(Kc)
                }
            } else Fc = function() {
                var a = Kc;
                !ia(n.setImmediate) || n.Window && n.Window.prototype && !z("Edge") && n.Window.prototype.setImmediate == n.setImmediate ? (Sb || (Sb = Tb()), Sb(a)) : n.setImmediate(a)
            }
        },
        Hc = !1,
        Ic = new qb,
        Kc = function() {
            for (var a; a = Ic.remove();) {
                try {
                    a.a.call(a.b)
                } catch (b) {
                    Rb(b)
                }
                ra(sb, a)
            }
            Hc = !1
        };
    var Lc = !F || 9 <= Number(Dc),
        Mc = F && !G("9");
    !wc || G("528");
    vc && G("1.9b") || F && G("8") || tc && G("9.5") || wc && G("528");
    vc && !G("8") || F && G("9");
    var Nc = function() {};
    Nc.prototype.c = function(a, b) {
        return yb(a) ? Oc(this, b) : fa(b) && !isFinite(b) ? b.toString() : b
    };
    Nc.prototype.a = function(a) {
        new a.b;
        throw Error("u");
    };
    Nc.prototype.b = function(a, b) {
        if (yb(a)) return b instanceof D ? b : this.a(a.h.prototype.A(), b);
        if (14 == a.b) return q(b) && Pc.test(b) && (a = Number(b), 0 < a) ? a : b;
        if (!a.g) return b;
        a = a.h;
        if (a === String) {
            if (fa(b)) return String(b)
        } else if (a === Number && q(b) && ("Infinity" === b || "-Infinity" === b || "NaN" === b || Pc.test(b))) return Number(b);
        return b
    };
    var Pc = /^-?[0-9]+$/;
    var H = function(a, b) {
        this.b = {};
        this.a = [];
        this.g = this.c = 0;
        var c = arguments.length;
        if (1 < c) {
            if (c % 2) throw Error("s");
            for (var d = 0; d < c; d += 2) this.set(arguments[d], arguments[d + 1])
        } else if (a) {
            a instanceof H ? (c = a.N(), d = a.J()) : (c = La(a), d = Ka(a));
            for (var e = 0; e < c.length; e++) this.set(c[e], d[e])
        }
    };
    H.prototype.h = function() {
        return this.c
    };
    H.prototype.J = function() {
        Qc(this);
        for (var a = [], b = 0; b < this.a.length; b++) a.push(this.b[this.a[b]]);
        return a
    };
    H.prototype.N = function() {
        Qc(this);
        return this.a.concat()
    };
    H.prototype.equals = function(a, b) {
        if (this === a) return !0;
        if (this.c != a.h()) return !1;
        b = b || Rc;
        Qc(this);
        for (var c, d = 0; c = this.a[d]; d++)
            if (!b(this.get(c), a.get(c))) return !1;
        return !0
    };
    var Rc = function(a, b) {
        return a === b
    };
    H.prototype.remove = function(a) {
        return Sc(this.b, a) ? (delete this.b[a], this.c--, this.g++, this.a.length > 2 * this.c && Qc(this), !0) : !1
    };
    var Qc = function(a) {
        if (a.c != a.a.length) {
            for (var b = 0, c = 0; b < a.a.length;) {
                var d = a.a[b];
                Sc(a.b, d) && (a.a[c++] = d);
                b++
            }
            a.a.length = c
        }
        if (a.c != a.a.length) {
            for (var e = {}, c = b = 0; b < a.a.length;) d = a.a[b], Sc(e, d) || (a.a[c++] = d, e[d] = 1), b++;
            a.a.length = c
        }
    };
    k = H.prototype;
    k.get = function(a, b) {
        return Sc(this.b, a) ? this.b[a] : b
    };
    k.set = function(a, b) {
        Sc(this.b, a) || (this.c++, this.a.push(a), this.g++);
        this.b[a] = b
    };
    k.forEach = function(a, b) {
        for (var c = this.N(), d = 0; d < c.length; d++) {
            var e = c[d],
                f = this.get(e);
            a.call(b, f, e, this)
        }
    };
    k.clone = function() {
        return new H(this)
    };
    k.gb = function(a) {
        Qc(this);
        var b = 0,
            c = this.g,
            d = this,
            e = new kc;
        e.next = function() {
            if (c != d.g) throw Error("t");
            if (b >= d.a.length) throw jc;
            var e = d.a[b++];
            return a ? e : d.b[e]
        };
        return e
    };
    var Sc = function(a, b) {
        return Object.prototype.hasOwnProperty.call(a, b)
    };
    var Tc = function() {
        D.call(this)
    };
    v(Tc, D);
    var Uc = null,
        Vc = function() {
            D.call(this)
        };
    v(Vc, D);
    var Wc = null,
        Xc = function() {
            D.call(this)
        };
    v(Xc, D);
    var Yc = null,
        Zc = {
            Eb: 1,
            Pb: 2,
            Db: 3,
            qb: 4,
            zb: 5,
            ub: 6,
            wb: 7
        },
        $c = {
            Gb: 1,
            Lb: 2,
            yb: 3,
            vb: 4
        },
        ad = function() {
            D.call(this)
        };
    v(ad, D);
    var bd = null,
        cd = {
            Ib: 1,
            Jb: 2,
            Ab: 3
        },
        dd = function() {
            D.call(this)
        };
    v(dd, D);
    var ed = null,
        fd = function() {
            D.call(this)
        };
    v(fd, D);
    var gd = null,
        hd = function() {
            D.call(this)
        };
    v(hd, D);
    var id = null;
    hd.prototype.getValue = function() {
        return mc(this, 4)
    };
    hd.prototype.h = function() {
        return mc(this, 5)
    };
    var jd = {
            UNKNOWN: 0,
            Nb: 1,
            Cb: 2,
            Mb: 3
        },
        kd = function() {
            D.call(this)
        };
    v(kd, D);
    var ld = null,
        md = function() {
            D.call(this)
        };
    v(md, D);
    var nd = null,
        od = {
            tb: 0,
            rb: 1,
            sb: 2
        };
    Tc.prototype.A = function() {
        var a = Uc;
        a || (Uc = a = sc(Tc, {
            0: {
                name: "ChromecastExtension",
                R: "chrome.dongle.logging.ChromecastExtension"
            },
            1: {
                name: "app_context",
                v: 11,
                type: Vc
            },
            2: {
                name: "chromecast_device",
                v: 11,
                type: fd
            },
            3: {
                name: "session_id",
                v: 9,
                type: String
            },
            4: {
                name: "event",
                pb: !0,
                v: 11,
                type: hd
            },
            5: {
                name: "opencast_data",
                v: 11,
                type: kd
            },
            6: {
                name: "cloud_casting_service_extension",
                v: 11,
                type: md
            }
        }));
        return a
    };
    Tc.A = Tc.prototype.A;
    Vc.prototype.A = function() {
        var a = Wc;
        a || (Wc = a = sc(Vc, {
            0: {
                name: "AppContext",
                R: "chrome.dongle.logging.AppContext"
            },
            1: {
                name: "platform",
                v: 11,
                type: Xc
            },
            2: {
                name: "app",
                v: 11,
                type: ad
            },
            3: {
                name: "environment_property",
                v: 11,
                type: dd
            }
        }));
        return a
    };
    Vc.A = Vc.prototype.A;
    Xc.prototype.A = function() {
        var a = Yc;
        a || (Yc = a = sc(Xc, {
            0: {
                name: "Platform",
                R: "chrome.dongle.logging.Platform"
            },
            1: {
                name: "type",
                v: 14,
                defaultValue: 1,
                type: Zc
            },
            2: {
                name: "version",
                v: 9,
                type: String
            },
            3: {
                name: "device_type",
                v: 14,
                defaultValue: 1,
                type: $c
            }
        }));
        return a
    };
    Xc.A = Xc.prototype.A;
    ad.prototype.A = function() {
        var a = bd;
        a || (bd = a = sc(ad, {
            0: {
                name: "App",
                R: "chrome.dongle.logging.App"
            },
            1: {
                name: "version",
                v: 9,
                type: String
            },
            2: {
                name: "feature",
                v: 14,
                defaultValue: 1,
                type: cd
            }
        }));
        return a
    };
    ad.A = ad.prototype.A;
    dd.prototype.A = function() {
        var a = ed;
        a || (ed = a = sc(dd, {
            0: {
                name: "EnvironmentProperties",
                R: "chrome.dongle.logging.EnvironmentProperties"
            },
            1: {
                name: "chrome_installed",
                v: 8,
                type: Boolean
            },
            2: {
                name: "cast_extension_installed",
                v: 8,
                type: Boolean
            },
            3: {
                name: "youtube_installed",
                v: 8,
                type: Boolean
            },
            4: {
                name: "play_movies_installed",
                v: 8,
                type: Boolean
            }
        }));
        return a
    };
    dd.A = dd.prototype.A;
    fd.prototype.A = function() {
        var a = gd;
        a || (gd = a = sc(fd, {
            0: {
                name: "ChromecastDevice",
                R: "chrome.dongle.logging.ChromecastDevice"
            },
            1: {
                name: "uptime_seconds",
                v: 5,
                type: Number
            },
            2: {
                name: "device_id",
                v: 9,
                type: String
            },
            3: {
                name: "metrics_id",
                v: 9,
                type: String
            }
        }));
        return a
    };
    fd.A = fd.prototype.A;
    hd.prototype.A = function() {
        var a = id;
        a || (id = a = sc(hd, {
            0: {
                name: "Event",
                R: "chrome.dongle.logging.Event"
            },
            1: {
                name: "type",
                v: 5,
                type: Number
            },
            2: {
                name: "time_ms",
                v: 3,
                type: String
            },
            3: {
                name: "duration_ms",
                v: 3,
                type: String
            },
            4: {
                name: "value",
                v: 3,
                type: String
            },
            5: {
                name: "count",
                v: 3,
                type: String
            },
            6: {
                name: "cec_state",
                v: 14,
                defaultValue: 0,
                type: jd
            },
            7: {
                name: "num_linked_users",
                v: 5,
                type: Number
            }
        }));
        return a
    };
    hd.A = hd.prototype.A;
    kd.prototype.A = function() {
        var a = ld;
        a || (ld = a = sc(kd, {
            0: {
                name: "OpenCastData",
                R: "chrome.dongle.logging.OpenCastData"
            },
            1: {
                name: "app_id",
                v: 9,
                type: String
            },
            2: {
                name: "pin_type",
                v: 13,
                type: Number
            }
        }));
        return a
    };
    kd.A = kd.prototype.A;
    md.prototype.A = function() {
        var a = nd;
        a || (nd = a = sc(md, {
            0: {
                name: "CloudCastingServiceExtension",
                R: "chrome.dongle.logging.CloudCastingServiceExtension"
            },
            9: {
                name: "message_id",
                v: 9,
                type: String
            },
            1: {
                name: "request_type",
                v: 14,
                defaultValue: 0,
                type: od
            },
            2: {
                name: "client_context_id",
                v: 9,
                type: String
            },
            3: {
                name: "command_type",
                v: 5,
                type: Number
            },
            4: {
                name: "cast_app_id",
                v: 9,
                type: String
            },
            5: {
                name: "command_execution_status",
                v: 5,
                type: Number
            },
            6: {
                name: "content_deeplink",
                v: 9,
                type: String
            },
            7: {
                name: "source_device_id",
                v: 9,
                type: String
            },
            8: {
                name: "target_device_id",
                v: 9,
                type: String
            }
        }));
        return a
    };
    md.A = md.prototype.A;
    var pd = null;
    var qd = function(a, b) {
        tb.call(this, a ? a.type : "");
        this.relatedTarget = this.a = this.target = null;
        this.button = this.screenY = this.screenX = this.clientY = this.clientX = 0;
        this.key = "";
        this.metaKey = this.shiftKey = this.altKey = this.ctrlKey = !1;
        this.c = null;
        if (a) {
            var c = this.type = a.type,
                d = a.changedTouches ? a.changedTouches[0] : null;
            this.target = a.target || a.srcElement;
            this.a = b;
            if (b = a.relatedTarget) {
                if (vc) {
                    var e;
                    a: {
                        try {
                            Oa(b.nodeName);
                            e = !0;
                            break a
                        } catch (f) {}
                        e = !1
                    }
                    e || (b = null)
                }
            } else "mouseover" == c ? b = a.fromElement : "mouseout" == c && (b =
                a.toElement);
            this.relatedTarget = b;
            null === d ? (this.clientX = void 0 !== a.clientX ? a.clientX : a.pageX, this.clientY = void 0 !== a.clientY ? a.clientY : a.pageY, this.screenX = a.screenX || 0, this.screenY = a.screenY || 0) : (this.clientX = void 0 !== d.clientX ? d.clientX : d.pageX, this.clientY = void 0 !== d.clientY ? d.clientY : d.pageY, this.screenX = d.screenX || 0, this.screenY = d.screenY || 0);
            this.button = a.button;
            this.key = a.key || "";
            this.ctrlKey = a.ctrlKey;
            this.altKey = a.altKey;
            this.shiftKey = a.shiftKey;
            this.metaKey = a.metaKey;
            this.c = a;
            a.defaultPrevented &&
                this.preventDefault()
        }
    };
    v(qd, tb);
    qd.prototype.stopPropagation = function() {
        qd.V.stopPropagation.call(this);
        this.c.stopPropagation ? this.c.stopPropagation() : this.c.cancelBubble = !0
    };
    qd.prototype.preventDefault = function() {
        qd.V.preventDefault.call(this);
        var a = this.c;
        if (a.preventDefault) a.preventDefault();
        else if (a.returnValue = !1, Mc) try {
            if (a.ctrlKey || 112 <= a.keyCode && 123 >= a.keyCode) a.keyCode = -1
        } catch (b) {}
    };
    var J = function(a, b) {
            this.a = 0;
            this.m = void 0;
            this.g = this.b = this.c = null;
            this.h = this.j = !1;
            if (a != ca) try {
                var c = this;
                a.call(b, function(a) {
                    rd(c, 2, a)
                }, function(a) {
                    rd(c, 3, a)
                })
            } catch (d) {
                rd(this, 3, d)
            }
        },
        sd = function() {
            this.next = this.c = this.b = this.g = this.a = null;
            this.h = !1
        };
    sd.prototype.reset = function() {
        this.c = this.b = this.g = this.a = null;
        this.h = !1
    };
    var td = new qa(function() {
            return new sd
        }, function(a) {
            a.reset()
        }, 100),
        ud = function(a, b, c) {
            var d = td.get();
            d.g = a;
            d.b = b;
            d.c = c;
            return d
        };
    J.prototype.then = function(a, b, c) {
        return vd(this, ia(a) ? a : null, ia(b) ? b : null, c)
    };
    J.prototype.then = J.prototype.then;
    J.prototype.$goog_Thenable = !0;
    J.prototype.cancel = function(a) {
        0 == this.a && Jc(function() {
            var b = new wd(a);
            xd(this, b)
        }, this)
    };
    var xd = function(a, b) {
            if (0 == a.a)
                if (a.c) {
                    var c = a.c;
                    if (c.b) {
                        for (var d = 0, e = null, f = null, g = c.b; g && (g.h || (d++, g.a == a && (e = g), !(e && 1 < d))); g = g.next) e || (f = g);
                        e && (0 == c.a && 1 == d ? xd(c, b) : (f ? (d = f, d.next == c.g && (c.g = d), d.next = d.next.next) : yd(c), zd(c, e, 3, b)))
                    }
                    a.c = null
                } else rd(a, 3, b)
        },
        Bd = function(a, b) {
            a.b || 2 != a.a && 3 != a.a || Ad(a);
            a.g ? a.g.next = b : a.b = b;
            a.g = b
        },
        vd = function(a, b, c, d) {
            var e = ud(null, null, null);
            e.a = new J(function(a, g) {
                e.g = b ? function(c) {
                    try {
                        var e = b.call(d, c);
                        a(e)
                    } catch (m) {
                        g(m)
                    }
                } : a;
                e.b = c ? function(b) {
                    try {
                        var e = c.call(d,
                            b);
                        !ba(e) && b instanceof wd ? g(b) : a(e)
                    } catch (m) {
                        g(m)
                    }
                } : g
            });
            e.a.c = a;
            Bd(a, e);
            return e.a
        };
    J.prototype.w = function(a) {
        this.a = 0;
        rd(this, 2, a)
    };
    J.prototype.B = function(a) {
        this.a = 0;
        rd(this, 3, a)
    };
    var rd = function(a, b, c) {
            if (0 == a.a) {
                a === c && (b = 3, c = new TypeError("Promise cannot resolve to itself"));
                a.a = 1;
                var d;
                a: {
                    var e = c,
                        f = a.w,
                        g = a.B;
                    if (e instanceof J) Bd(e, ud(f || ca, g || null, a)),
                    d = !0;
                    else {
                        var h;
                        if (e) try {
                            h = !!e.$goog_Thenable
                        } catch (m) {
                            h = !1
                        } else h = !1;
                        if (h) e.then(f, g, a), d = !0;
                        else {
                            h = typeof e;
                            if ("object" == h && null != e || "function" == h) try {
                                var l = e.then;
                                if (ia(l)) {
                                    Cd(e, l, f, g, a);
                                    d = !0;
                                    break a
                                }
                            } catch (m) {
                                g.call(a, m);
                                d = !0;
                                break a
                            }
                            d = !1
                        }
                    }
                }
                d || (a.m = c, a.a = b, a.c = null, Ad(a), 3 != b || c instanceof wd || Dd(a, c))
            }
        },
        Cd = function(a,
            b, c, d, e) {
            var f = !1,
                g = function(a) {
                    f || (f = !0, c.call(e, a))
                },
                h = function(a) {
                    f || (f = !0, d.call(e, a))
                };
            try {
                b.call(a, g, h)
            } catch (l) {
                h(l)
            }
        },
        Ad = function(a) {
            a.j || (a.j = !0, Jc(a.o, a))
        },
        yd = function(a) {
            var b = null;
            a.b && (b = a.b, a.b = b.next, b.next = null);
            a.b || (a.g = null);
            return b
        };
    J.prototype.o = function() {
        for (var a; a = yd(this);) zd(this, a, this.a, this.m);
        this.j = !1
    };
    var zd = function(a, b, c, d) {
            if (3 == c && b.b && !b.h)
                for (; a && a.h; a = a.c) a.h = !1;
            if (b.a) b.a.c = null, Ed(b, c, d);
            else try {
                b.h ? b.g.call(b.c) : Ed(b, c, d)
            } catch (e) {
                Fd.call(null, e)
            }
            ra(td, b)
        },
        Ed = function(a, b, c) {
            2 == b ? a.g.call(a.c, c) : a.b && a.b.call(a.c, c)
        },
        Dd = function(a, b) {
            a.h = !0;
            Jc(function() {
                a.h && Fd.call(null, b)
            })
        },
        Fd = Rb,
        wd = function(a) {
            sa.call(this, a)
        };
    v(wd, sa);
    wd.prototype.name = "cancel";
    var Gd = function() {};
    v(Gd, Nc);
    Gd.prototype.a = function(a, b) {
        a = new a.b;
        a.c = this;
        a.G = b;
        a.a = {};
        return a
    };
    var K = function(a, b) {
        this.b = this.m = this.g = "";
        this.o = null;
        this.h = this.a = "";
        this.j = !1;
        var c;
        a instanceof K ? (this.j = ba(b) ? b : a.j, Hd(this, a.g), this.m = a.m, this.b = a.b, Id(this, a.o), this.a = a.a, Jd(this, a.c.clone()), this.h = a.h) : a && (c = String(a).match(zb)) ? (this.j = !!b, Hd(this, c[1] || "", !0), this.m = Kd(c[2] || ""), this.b = Kd(c[3] || "", !0), Id(this, c[4]), this.a = Kd(c[5] || "", !0), Jd(this, c[6] || "", !0), this.h = Kd(c[7] || "")) : (this.j = !!b, this.c = new Ld(null, 0, this.j))
    };
    K.prototype.toString = function() {
        var a = [],
            b = this.g;
        b && a.push(Md(b, Nd, !0), ":");
        var c = this.b;
        if (c || "file" == b) a.push("//"), (b = this.m) && a.push(Md(b, Nd, !0), "@"), a.push(encodeURIComponent(String(c)).replace(/%25([0-9a-fA-F]{2})/g, "%$1")), c = this.o, null != c && a.push(":", String(c));
        if (c = this.a) this.b && "/" != c.charAt(0) && a.push("/"), a.push(Md(c, "/" == c.charAt(0) ? Od : Pd, !0));
        (c = this.c.toString()) && a.push("?", c);
        (c = this.h) && a.push("#", Md(c, Qd));
        return a.join("")
    };
    K.prototype.resolve = function(a) {
        var b = this.clone(),
            c = !!a.g;
        c ? Hd(b, a.g) : c = !!a.m;
        c ? b.m = a.m : c = !!a.b;
        c ? b.b = a.b : c = null != a.o;
        var d = a.a;
        if (c) Id(b, a.o);
        else if (c = !!a.a) {
            if ("/" != d.charAt(0))
                if (this.b && !this.a) d = "/" + d;
                else {
                    var e = b.a.lastIndexOf("/"); - 1 != e && (d = b.a.substr(0, e + 1) + d)
                }
            e = d;
            if (".." == e || "." == e) d = "";
            else if (-1 != e.indexOf("./") || -1 != e.indexOf("/.")) {
                for (var d = 0 == e.lastIndexOf("/", 0), e = e.split("/"), f = [], g = 0; g < e.length;) {
                    var h = e[g++];
                    "." == h ? d && g == e.length && f.push("") : ".." == h ? ((1 < f.length || 1 == f.length &&
                        "" != f[0]) && f.pop(), d && g == e.length && f.push("")) : (f.push(h), d = !0)
                }
                d = f.join("/")
            } else d = e
        }
        c ? b.a = d : c = "" !== a.c.toString();
        c ? Jd(b, a.c.clone()) : c = !!a.h;
        c && (b.h = a.h);
        return b
    };
    K.prototype.clone = function() {
        return new K(this)
    };
    var Hd = function(a, b, c) {
            a.g = c ? Kd(b, !0) : b;
            a.g && (a.g = a.g.replace(/:$/, ""))
        },
        Id = function(a, b) {
            if (b) {
                b = Number(b);
                if (isNaN(b) || 0 > b) throw Error("v`" + b);
                a.o = b
            } else a.o = null
        },
        Jd = function(a, b, c) {
            b instanceof Ld ? (a.c = b, Rd(a.c, a.j)) : (c || (b = Md(b, Sd)), a.c = new Ld(b, 0, a.j))
        },
        Td = function(a, b) {
            return a.c.get(b)
        },
        Kd = function(a, b) {
            return a ? b ? decodeURI(a.replace(/%25/g, "%2525")) : decodeURIComponent(a) : ""
        },
        Md = function(a, b, c) {
            return q(a) ? (a = encodeURI(a).replace(b, Ud), c && (a = a.replace(/%25([0-9a-fA-F]{2})/g, "%$1")), a) : null
        },
        Ud = function(a) {
            a = a.charCodeAt(0);
            return "%" + (a >> 4 & 15).toString(16) + (a & 15).toString(16)
        },
        Nd = /[#\/\?@]/g,
        Pd = /[\#\?:]/g,
        Od = /[\#\?]/g,
        Sd = /[\#\?@]/g,
        Qd = /#/g,
        Ld = function(a, b, c) {
            this.b = this.a = null;
            this.c = a || null;
            this.g = !!c
        },
        Wd = function(a) {
            a.a || (a.a = new H, a.b = 0, a.c && Ab(a.c, function(b, c) {
                Vd(a, decodeURIComponent(b.replace(/\+/g, " ")), c)
            }))
        };
    Ld.prototype.h = function() {
        Wd(this);
        return this.b
    };
    var Vd = function(a, b, c) {
        Wd(a);
        a.c = null;
        b = Xd(a, b);
        var d = a.a.get(b);
        d || a.a.set(b, d = []);
        d.push(c);
        a.b += 1
    };
    Ld.prototype.remove = function(a) {
        Wd(this);
        a = Xd(this, a);
        return Sc(this.a.b, a) ? (this.c = null, this.b -= this.a.get(a).length, this.a.remove(a)) : !1
    };
    var Yd = function(a, b) {
        Wd(a);
        b = Xd(a, b);
        return Sc(a.a.b, b)
    };
    k = Ld.prototype;
    k.N = function() {
        Wd(this);
        for (var a = this.a.J(), b = this.a.N(), c = [], d = 0; d < b.length; d++)
            for (var e = a[d], f = 0; f < e.length; f++) c.push(b[d]);
        return c
    };
    k.J = function(a) {
        Wd(this);
        var b = [];
        if (q(a)) Yd(this, a) && (b = mb(b, this.a.get(Xd(this, a))));
        else {
            a = this.a.J();
            for (var c = 0; c < a.length; c++) b = mb(b, a[c])
        }
        return b
    };
    k.set = function(a, b) {
        Wd(this);
        this.c = null;
        a = Xd(this, a);
        Yd(this, a) && (this.b -= this.a.get(a).length);
        this.a.set(a, [b]);
        this.b += 1;
        return this
    };
    k.get = function(a, b) {
        a = a ? this.J(a) : [];
        return 0 < a.length ? String(a[0]) : b
    };
    k.toString = function() {
        if (this.c) return this.c;
        if (!this.a) return "";
        for (var a = [], b = this.a.N(), c = 0; c < b.length; c++)
            for (var d = b[c], e = encodeURIComponent(String(d)), d = this.J(d), f = 0; f < d.length; f++) {
                var g = e;
                "" !== d[f] && (g += "=" + encodeURIComponent(String(d[f])));
                a.push(g)
            }
        return this.c = a.join("&")
    };
    k.clone = function() {
        var a = new Ld;
        a.c = this.c;
        this.a && (a.a = this.a.clone(), a.b = this.b);
        return a
    };
    var Xd = function(a, b) {
            b = String(b);
            a.g && (b = b.toLowerCase());
            return b
        },
        Rd = function(a, b) {
            b && !a.g && (Wd(a), a.c = null, a.a.forEach(function(a, b) {
                var c = b.toLowerCase();
                b != c && (this.remove(b), this.remove(c), 0 < a.length && (this.c = null, this.a.set(Xd(this, c), nb(a)), this.b += a.length))
            }, a));
            a.g = b
        };
    Ld.prototype.extend = function(a) {
        for (var b = 0; b < arguments.length; b++) Qb(arguments[b], function(a, b) {
            Vd(this, b, a)
        }, this)
    };
    var L = function() {},
        Zd = "function" == typeof Uint8Array,
        M = function(a, b, c, d) {
            a.a = null;
            b || (b = c ? [c] : []);
            a.o = c ? String(c) : void 0;
            a.g = 0 === c ? -1 : 0;
            a.b = b;
            a: {
                if (a.b.length && (b = a.b.length - 1, (c = a.b[b]) && "object" == typeof c && !p(c) && !(Zd && c instanceof Uint8Array))) {
                    a.j = b - a.g;
                    a.c = c;
                    break a
                }
                a.j = Number.MAX_VALUE
            }
            a.m = {};
            if (d)
                for (b = 0; b < d.length; b++) c = d[b], c < a.j ? (c += a.g, a.b[c] = a.b[c] || $d) : a.c[c] = a.c[c] || $d
        },
        $d = [],
        N = function(a, b) {
            if (b < a.j) {
                b += a.g;
                var c = a.b[b];
                return c === $d ? a.b[b] = [] : c
            }
            c = a.c[b];
            return c === $d ? a.c[b] = [] : c
        },
        ae =
        function(a) {
            a = N(a, 3);
            return null == a ? a : +a
        },
        be = function(a, b, c) {
            a = N(a, b);
            return null == a ? c : a
        },
        ce = function(a, b, c) {
            b < a.j ? a.b[b + a.g] = c : a.c[b] = c
        },
        O = function(a, b, c) {
            a.a || (a.a = {});
            if (!a.a[c]) {
                var d = N(a, c);
                d && (a.a[c] = new b(d))
            }
            return a.a[c]
        },
        de = function(a, b, c) {
            a.a || (a.a = {});
            if (!a.a[c]) {
                for (var d = N(a, c), e = [], f = 0; f < d.length; f++) e[f] = new b(d[f]);
                a.a[c] = e
            }
            b = a.a[c];
            b == $d && (b = a.a[c] = []);
            return b
        },
        fe = function(a, b, c) {
            a.a || (a.a = {});
            var d = c ? ee(c) : c;
            a.a[b] = c;
            ce(a, b, d)
        },
        ge = function(a) {
            if (a.a)
                for (var b in a.a) {
                    var c = a.a[b];
                    if (p(c))
                        for (var d = 0; d < c.length; d++) c[d] && ee(c[d]);
                    else c && ee(c)
                }
        },
        ee = function(a) {
            ge(a);
            return a.b
        },
        he = n.JSON && n.JSON.stringify || "object" === typeof JSON && JSON.stringify;
    L.prototype.h = Zd ? function() {
        var a = Uint8Array.prototype.toJSON;
        Uint8Array.prototype.toJSON = function() {
            if (!pd) {
                pd = {};
                for (var a = 0; 65 > a; a++) pd[a] = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=".charAt(a)
            }
            for (var a = pd, b = [], e = 0; e < this.length; e += 3) {
                var f = this[e],
                    g = e + 1 < this.length,
                    h = g ? this[e + 1] : 0,
                    l = e + 2 < this.length,
                    m = l ? this[e + 2] : 0,
                    B = f >> 2,
                    f = (f & 3) << 4 | h >> 4,
                    h = (h & 15) << 2 | m >> 6,
                    m = m & 63;
                l || (m = 64, g || (h = 64));
                b.push(a[B], a[f], a[h], a[m])
            }
            return b.join("")
        };
        try {
            var b = he.call(null, ee(this), Da)
        } finally {
            Uint8Array.prototype.toJSON =
                a
        }
        return b
    } : he ? function() {
        return he.call(null, ee(this), Da)
    } : function() {
        var a = ee(this),
            b = [];
        Fa(new Ea, a, b);
        return b.join("")
    };
    var Da = function(a, b) {
        if (fa(b)) {
            if (isNaN(b)) return "NaN";
            if (Infinity === b) return "Infinity";
            if (-Infinity === b) return "-Infinity"
        }
        return b
    };
    L.prototype.toString = function() {
        ge(this);
        return this.b.toString()
    };
    L.prototype.clone = function() {
        return new this.constructor(ie(ee(this)))
    };
    var ie = function(a) {
        var b;
        if (p(a)) {
            for (var c = Array(a.length), d = 0; d < a.length; d++) null != (b = a[d]) && (c[d] = "object" == typeof b ? ie(b) : b);
            return c
        }
        if (Zd && a instanceof Uint8Array) return new Uint8Array(a);
        c = {};
        for (d in a) null != (b = a[d]) && (c[d] = "object" == typeof b ? ie(b) : b);
        return c
    };
    var je = "closure_lm_" + (1E6 * Math.random() | 0),
        ke = {},
        le = 0,
        me = function(a, b, c, d, e) {
            if (p(b)) {
                for (var f = 0; f < b.length; f++) me(a, b[f], c, d, e);
                return null
            }
            c = ne(c);
            return Wa(a) ? a.C(b, c, d, e) : oe(a, b, c, !1, d, e)
        },
        oe = function(a, b, c, d, e, f) {
            if (!b) throw Error("x");
            var g = !!e,
                h = pe(a);
            h || (a[je] = h = new Bb(a));
            c = Db(h, b, c, d, e, f);
            if (c.a) return c;
            d = qe();
            c.a = d;
            d.src = a;
            d.listener = c;
            if (a.addEventListener) a.addEventListener(b.toString(), d, g);
            else if (a.attachEvent) a.attachEvent(re(b.toString()), d);
            else throw Error("y");
            le++;
            return c
        },
        qe = function() {
            var a = se,
                b = Lc ? function(c) {
                    return a.call(b.src, b.listener, c)
                } : function(c) {
                    c = a.call(b.src, b.listener, c);
                    if (!c) return c
                };
            return b
        },
        te = function(a, b, c, d, e) {
            if (p(b)) {
                for (var f = 0; f < b.length; f++) te(a, b[f], c, d, e);
                return null
            }
            c = ne(c);
            return Wa(a) ? Db(a.b, String(b), c, !0, d, e) : oe(a, b, c, !0, d, e)
        },
        ue = function(a, b, c, d, e) {
            if (p(b))
                for (var f = 0; f < b.length; f++) ue(a, b[f], c, d, e);
            else c = ne(c), Wa(a) ? a.T(b, c, d, e) : a && (a = pe(a)) && (b = Fb(a, b, c, !!d, e)) && ve(b)
        },
        ve = function(a) {
            if (!fa(a) && a && !a.aa) {
                var b = a.src;
                if (Wa(b)) Eb(b.b,
                    a);
                else {
                    var c = a.type,
                        d = a.a;
                    b.removeEventListener ? b.removeEventListener(c, d, a.capture) : b.detachEvent && b.detachEvent(re(c), d);
                    le--;
                    (c = pe(b)) ? (Eb(c, a), 0 == c.b && (c.src = null, b[je] = null)) : vb(a)
                }
            }
        },
        re = function(a) {
            return a in ke ? ke[a] : ke[a] = "on" + a
        },
        xe = function(a, b, c, d) {
            var e = !0;
            if (a = pe(a))
                if (b = a.a[b.toString()])
                    for (b = b.concat(), a = 0; a < b.length; a++) {
                        var f = b[a];
                        f && f.capture == c && !f.aa && (f = we(f, d), e = e && !1 !== f)
                    }
            return e
        },
        we = function(a, b) {
            var c = a.listener,
                d = a.ea || a.src;
            a.ca && ve(a);
            return c.call(d, b)
        },
        se = function(a,
            b) {
            if (a.aa) return !0;
            if (!Lc) {
                if (!b) a: {
                    b = ["window", "event"];
                    for (var c = n, d; d = b.shift();)
                        if (null != c[d]) c = c[d];
                        else {
                            b = null;
                            break a
                        }
                    b = c
                }
                d = b;
                b = new qd(d, this);
                c = !0;
                if (!(0 > d.keyCode || void 0 != d.returnValue)) {
                    a: {
                        var e = !1;
                        if (0 == d.keyCode) try {
                            d.keyCode = -1;
                            break a
                        } catch (g) {
                            e = !0
                        }
                        if (e || void 0 == d.returnValue) d.returnValue = !0
                    }
                    d = [];
                    for (e = b.a; e; e = e.parentNode) d.push(e);a = a.type;
                    for (e = d.length - 1; !b.b && 0 <= e; e--) {
                        b.a = d[e];
                        var f = xe(d[e], a, !0, b),
                            c = c && f
                    }
                    for (e = 0; !b.b && e < d.length; e++) b.a = d[e],
                    f = xe(d[e], a, !1, b),
                    c = c && f
                }
                return c
            }
            return we(a, new qd(b, this))
        },
        pe = function(a) {
            a = a[je];
            return a instanceof Bb ? a : null
        },
        ye = "__closure_events_fn_" + (1E9 * Math.random() >>> 0),
        ne = function(a) {
            if (ia(a)) return a;
            a[ye] || (a[ye] = function(b) {
                return a.handleEvent(b)
            });
            return a[ye]
        };
    var ze = function() {};
    v(ze, Gd);
    var Oc = function(a, b) {
        for (var c = Ob(b.A()), d = [], e = 0; e < c.length; e++) {
            var f = c[e];
            if (b.has(f)) {
                var g = f.a;
                if (f.c) {
                    d[g] = [];
                    for (var h = 0; h < qc(b, f.a); h++) d[g][h] = a.c(f, b.get(f, h))
                } else d[g] = a.c(f, b.get(f))
            }
        }
        lc(b, function(a, b) {
            d[a] = b
        });
        return d
    };
    ze.prototype.c = function(a, b) {
        return 8 == a.b ? b ? 1 : 0 : Nc.prototype.c.apply(this, arguments)
    };
    ze.prototype.b = function(a, b) {
        return 8 == a.b ? !!b : Nc.prototype.b.apply(this, arguments)
    };
    ze.prototype.a = function(a, b) {
        return ze.V.a.call(this, a, b)
    };
    var Ae = function(a) {
            this.g = a;
            this.a = this.c = this.da = this.b = null
        },
        P = function(a, b) {
            this.name = a;
            this.value = b
        };
    P.prototype.toString = function() {
        return this.name
    };
    var Be = new P("SHOUT", 1200),
        Ce = new P("SEVERE", 1E3),
        Q = new P("WARNING", 900),
        De = new P("INFO", 800),
        Ee = new P("CONFIG", 700),
        Fe = new P("FINE", 500),
        Ge = [new P("OFF", Infinity), Be, Ce, Q, De, Ee, Fe, new P("FINER", 400), new P("FINEST", 300), new P("ALL", 0)],
        He = null,
        Ie = function(a) {
            return a.da ? a.da : a.b ? Ie(a.b) : null
        };
    Ae.prototype.log = function(a, b, c) {
        if (a.value >= Ie(this).value)
            for (ia(b) && (b = b()), a = new ta(a, String(b), this.g), c && (a.a = c), c = "log:" + a.c, n.console && (n.console.timeStamp ? n.console.timeStamp(c) : n.console.markTimeline && n.console.markTimeline(c)), n.msWriteProfilerMark && n.msWriteProfilerMark(c), c = this; c;) {
                var d = c,
                    e = a;
                if (d.a)
                    for (var f = 0; b = d.a[f]; f++) b(e);
                c = c.b
            }
    };
    var Je = {},
        Ke = null,
        Le = function() {
            Ke || (Ke = new Ae(""), Je[""] = Ke, Ke.da = Ee)
        },
        R = function(a) {
            Le();
            var b;
            if (!(b = Je[a])) {
                b = new Ae(a);
                var c = a.lastIndexOf("."),
                    d = a.substr(c + 1),
                    c = R(a.substr(0, c));
                c.c || (c.c = {});
                c.c[d] = b;
                b.b = c;
                Je[a] = b
            }
            return b
        };
    var Me = function(a) {
        Ua.call(this);
        this.b = a;
        this.a = {}
    };
    v(Me, Ua);
    var Ne = [];
    Me.prototype.C = function(a, b, c, d) {
        p(b) || (b && (Ne[0] = b.toString()), b = Ne);
        for (var e = 0; e < b.length; e++) {
            var f = me(a, b[e], c || this.handleEvent, d || !1, this.b || this);
            if (!f) break;
            this.a[f.key] = f
        }
        return this
    };
    var Oe = function(a, b, c, d, e, f) {
        if (p(c))
            for (var g = 0; g < c.length; g++) Oe(a, b, c[g], d, e, f);
        else(b = te(b, c, d || a.handleEvent, e, f || a.b || a)) && (a.a[b.key] = b)
    };
    Me.prototype.T = function(a, b, c, d, e) {
        if (p(b))
            for (var f = 0; f < b.length; f++) this.T(a, b[f], c, d, e);
        else c = c || this.handleEvent, e = e || this.b || this, c = ne(c), d = !!d, b = Wa(a) ? Fb(a.b, String(b), c, d, e) : a ? (a = pe(a)) ? Fb(a, b, c, d, e) : null : null, b && (ve(b), delete this.a[b.key]);
        return this
    };
    var Pe = function(a) {
        Ja(a.a, function(a, c) {
            this.a.hasOwnProperty(c) && ve(a)
        }, a);
        a.a = {}
    };
    Me.prototype.c = function() {
        Me.V.c.call(this);
        Pe(this)
    };
    Me.prototype.handleEvent = function() {
        throw Error("z");
    };
    var S = function() {
        Ua.call(this);
        this.b = new Bb(this);
        this.Na = this;
        this.S = null
    };
    v(S, Ua);
    S.prototype[Va] = !0;
    S.prototype.addEventListener = function(a, b, c, d) {
        me(this, a, b, c, d)
    };
    S.prototype.removeEventListener = function(a, b, c, d) {
        ue(this, a, b, c, d)
    };
    var T = function(a, b) {
        var c, d = a.S;
        if (d)
            for (c = []; d; d = d.S) c.push(d);
        a = a.Na;
        d = b.type || b;
        if (q(b)) b = new tb(b, a);
        else if (b instanceof tb) b.target = b.target || a;
        else {
            var e = b;
            b = new tb(d, a);
            Na(b, e)
        }
        var e = !0,
            f;
        if (c)
            for (var g = c.length - 1; !b.b && 0 <= g; g--) f = b.a = c[g], e = Qe(f, d, !0, b) && e;
        b.b || (f = b.a = a, e = Qe(f, d, !0, b) && e, b.b || (e = Qe(f, d, !1, b) && e));
        if (c)
            for (g = 0; !b.b && g < c.length; g++) f = b.a = c[g], e = Qe(f, d, !1, b) && e
    };
    S.prototype.c = function() {
        S.V.c.call(this);
        if (this.b) {
            var a = this.b,
                b = 0,
                c;
            for (c in a.a) {
                for (var d = a.a[c], e = 0; e < d.length; e++) ++b, vb(d[e]);
                delete a.a[c];
                a.b--
            }
        }
        this.S = null
    };
    S.prototype.C = function(a, b, c, d) {
        return Db(this.b, String(a), b, !1, c, d)
    };
    S.prototype.T = function(a, b, c, d) {
        return this.b.remove(String(a), b, c, d)
    };
    var Qe = function(a, b, c, d) {
        b = a.b.a[String(b)];
        if (!b) return !0;
        b = b.concat();
        for (var e = !0, f = 0; f < b.length; ++f) {
            var g = b[f];
            if (g && !g.aa && g.capture == c) {
                var h = g.listener,
                    l = g.ea || g.src;
                g.ca && Eb(a.b, g);
                e = !1 !== h.call(l, d) && e
            }
        }
        return e && 0 != d.sa
    };
    var Re = function(a) {
        this.g = a || "";
        this.h = wa
    };
    Re.prototype.a = !0;
    Re.prototype.b = !0;
    Re.prototype.c = !1;
    var Se = function(a) {
            return 10 > a ? "0" + a : String(a)
        },
        Te = function(a, b) {
            a = (a.g - b) / 1E3;
            b = a.toFixed(3);
            var c = 0;
            if (1 > a) c = 2;
            else
                for (; 100 > a;) c++, a *= 10;
            for (; 0 < c--;) b = " " + b;
            return b
        },
        Ue = function(a) {
            Re.call(this, a)
        };
    v(Ue, Re);
    var U = function(a, b) {
            a && a.log(De, b, void 0)
        },
        V = function(a, b) {
            a && a.log(Fe, b, void 0)
        };
    var Ve = function(a, b) {
        S.call(this);
        this.h = a || 1;
        this.g = b || n;
        this.j = r(this.w, this);
        this.o = t()
    };
    v(Ve, S);
    Ve.prototype.enabled = !1;
    Ve.prototype.a = null;
    Ve.prototype.w = function() {
        if (this.enabled) {
            var a = t() - this.o;
            0 < a && a < .8 * this.h ? this.a = this.g.setTimeout(this.j, this.h - a) : (this.a && (this.g.clearTimeout(this.a), this.a = null), T(this, "tick"), this.enabled && (this.a = this.g.setTimeout(this.j, this.h), this.o = t()))
        }
    };
    Ve.prototype.start = function() {
        this.enabled = !0;
        this.a || (this.a = this.g.setTimeout(this.j, this.h), this.o = t())
    };
    var We = function(a) {
        a.enabled = !1;
        a.a && (a.g.clearTimeout(a.a), a.a = null)
    };
    Ve.prototype.c = function() {
        Ve.V.c.call(this);
        We(this);
        delete this.g
    };
    var Xe = function(a, b, c) {
        if (ia(a)) c && (a = r(a, c));
        else if (a && "function" == typeof a.handleEvent) a = r(a.handleEvent, a);
        else throw Error("A");
        return 2147483647 < Number(b) ? -1 : n.setTimeout(a, b || 0)
    };
    var Ye = function(a) {
        M(this, a, 0, null)
    };
    v(Ye, L);
    var Ze = function(a) {
        M(this, a, 0, null)
    };
    v(Ze, L);
    var $e = function(a) {
        M(this, a, 0, null)
    };
    v($e, L);
    var af = function(a) {
        M(this, a, 0, null)
    };
    v(af, L);
    af.prototype.qa = function() {
        return N(this, 1)
    };
    var bf = function(a) {
        M(this, a, 0, null)
    };
    v(bf, L);
    var df = function(a) {
        M(this, a, 0, cf)
    };
    v(df, L);
    var cf = [1, 4],
        ff = function(a) {
            M(this, a, 0, ef)
        };
    v(ff, L);
    var ef = [15, 27, 29],
        W = function(a) {
            return be(a, 6, 1)
        };
    ff.prototype.qa = function() {
        return N(this, 3)
    };
    var gf = function() {
        this.h = r(this.c, this);
        this.a = new Ue;
        this.a.b = !1;
        this.a.c = !1;
        this.b = this.a.a = !1;
        this.g = {}
    };
    gf.prototype.c = function(a) {
        if (!this.g[a.b]) {
            var b;
            b = this.a;
            var c = [];
            c.push(b.g, " ");
            if (b.b) {
                var d = new Date(a.g);
                c.push("[", Se(d.getFullYear() - 2E3) + Se(d.getMonth() + 1) + Se(d.getDate()) + " " + Se(d.getHours()) + ":" + Se(d.getMinutes()) + ":" + Se(d.getSeconds()) + "." + Se(Math.floor(d.getMilliseconds() / 10)), "] ")
            }
            c.push("[", Te(a, b.h.get()), "s] ");
            c.push("[", a.b, "] ");
            c.push(a.c);
            b.c && (d = a.a) && c.push("\n", d instanceof Error ? d.message : d.toString());
            b.a && c.push("\n");
            b = c.join("");
            if (c = hf) switch (a.h) {
                case Be:
                    jf(c, "info", b);
                    break;
                case Ce:
                    jf(c, "error", b);
                    break;
                case Q:
                    jf(c, "warn", b);
                    break;
                default:
                    jf(c, "debug", b)
            }
        }
    };
    var kf = null,
        hf = n.console,
        jf = function(a, b, c) {
            if (a[b]) a[b](c);
        };
    var X = function(a) {
        S.call(this);
        this.headers = new H;
        this.K = a || null;
        this.g = !1;
        this.I = this.a = null;
        this.o = this.X = this.w = "";
        this.h = this.M = this.B = this.L = !1;
        this.j = 0;
        this.F = null;
        this.ma = "";
        this.H = this.U = !1
    };
    v(X, S);
    X.prototype.l = R("goog.net.XhrIo");
    var lf = /^https?$/i,
        mf = ["POST", "PUT"],
        nf = [],
        of = function(a, b, c, d, e, f, g) {
            var h = new X;
            nf.push(h);
            b && h.C("complete", b);
            Db(h.b, "ready", h.Sa, !0, void 0, void 0);
            f && (h.j = Math.max(0, f));
            g && (h.U = g);
            h.send(a, c, d, e)
        };
    X.prototype.Sa = function() {
        this.m || (this.m = !0, this.c());
        lb(nf, this)
    };
    X.prototype.send = function(a, b, c, d) {
        if (this.a) throw Error("B`" + this.w + "`" + a);
        b = b ? b.toUpperCase() : "GET";
        this.w = a;
        this.o = "";
        this.X = b;
        this.L = !1;
        this.g = !0;
        this.a = this.K ? Mb(this.K) : Mb(Kb);
        this.I = this.K ? cb(this.K) : cb(Kb);
        this.a.onreadystatechange = r(this.ba, this);
        try {
            V(this.l, pf(this, "Opening Xhr")), this.M = !0, this.a.open(b, String(a), !0), this.M = !1
        } catch (f) {
            V(this.l, pf(this, "Error opening Xhr: " + f.message));
            qf(this, f);
            return
        }
        a = c || "";
        var e = this.headers.clone();
        d && Qb(d, function(a, b) {
            e.set(b, a)
        });
        d = kb(e.N());
        c = n.FormData && a instanceof n.FormData;
        !(0 <= fb(mf, b)) || d || c || e.set("Content-Type", "application/x-www-form-urlencoded;charset=utf-8");
        e.forEach(function(a, b) {
            this.a.setRequestHeader(b, a)
        }, this);
        this.ma && (this.a.responseType = this.ma);
        "withCredentials" in this.a && this.a.withCredentials !== this.U && (this.a.withCredentials = this.U);
        try {
            rf(this), 0 < this.j && (this.H = sf(this.a), V(this.l, pf(this, "Will abort after " + this.j + "ms if incomplete, xhr2 " + this.H)), this.H ? (this.a.timeout = this.j, this.a.ontimeout = r(this.W,
                this)) : this.F = Xe(this.W, this.j, this)), V(this.l, pf(this, "Sending request")), this.B = !0, this.a.send(a), this.B = !1
        } catch (f) {
            V(this.l, pf(this, "Send error: " + f.message)), qf(this, f)
        }
    };
    var sf = function(a) {
            return F && G(9) && fa(a.timeout) && ba(a.ontimeout)
        },
        jb = function(a) {
            return "content-type" == a.toLowerCase()
        };
    X.prototype.W = function() {
        "undefined" != typeof aa && this.a && (this.o = "Timed out after " + this.j + "ms, aborting", V(this.l, pf(this, this.o)), T(this, "timeout"), this.abort(8))
    };
    var qf = function(a, b) {
            a.g = !1;
            a.a && (a.h = !0, a.a.abort(), a.h = !1);
            a.o = b;
            tf(a);
            uf(a)
        },
        tf = function(a) {
            a.L || (a.L = !0, T(a, "complete"), T(a, "error"))
        };
    X.prototype.abort = function() {
        this.a && this.g && (V(this.l, pf(this, "Aborting")), this.g = !1, this.h = !0, this.a.abort(), this.h = !1, T(this, "complete"), T(this, "abort"), uf(this))
    };
    X.prototype.c = function() {
        this.a && (this.g && (this.g = !1, this.h = !0, this.a.abort(), this.h = !1), uf(this, !0));
        X.V.c.call(this)
    };
    X.prototype.ba = function() {
        this.m || (this.M || this.B || this.h ? vf(this) : this.Ta())
    };
    X.prototype.Ta = function() {
        vf(this)
    };
    var vf = function(a) {
            if (a.g && "undefined" != typeof aa)
                if (a.I[1] && 4 == wf(a) && 2 == xf(a)) V(a.l, pf(a, "Local request error detected and ignored"));
                else if (a.B && 4 == wf(a)) Xe(a.ba, 0, a);
            else if (T(a, "readystatechange"), 4 == wf(a)) {
                V(a.l, pf(a, "Request complete"));
                a.g = !1;
                try {
                    yf(a) ? (T(a, "complete"), T(a, "success")) : (a.o = zf(a) + " [" + xf(a) + "]", tf(a))
                } finally {
                    uf(a)
                }
            }
        },
        uf = function(a, b) {
            if (a.a) {
                rf(a);
                var c = a.a,
                    d = a.I[0] ? ca : null;
                a.a = null;
                a.I = null;
                b || T(a, "ready");
                try {
                    c.onreadystatechange = d
                } catch (e) {
                    (a = a.l) && a.log(Ce, "Problem encountered resetting onreadystatechange: " +
                        e.message, void 0)
                }
            }
        },
        rf = function(a) {
            a.a && a.H && (a.a.ontimeout = null);
            fa(a.F) && (n.clearTimeout(a.F), a.F = null)
        },
        yf = function(a) {
            var b = xf(a),
                c;
            a: switch (b) {
                case 200:
                case 201:
                case 202:
                case 204:
                case 206:
                case 304:
                case 1223:
                    c = !0;
                    break a;
                default:
                    c = !1
            }
            if (!c) {
                if (b = 0 === b) a = String(a.w).match(zb)[1] || null, !a && n.self && n.self.location && (a = n.self.location.protocol, a = a.substr(0, a.length - 1)), b = !lf.test(a ? a.toLowerCase() : "");
                c = b
            }
            return c
        },
        wf = function(a) {
            return a.a ? a.a.readyState : 0
        },
        xf = function(a) {
            try {
                return 2 < wf(a) ? a.a.status :
                    -1
            } catch (b) {
                return -1
            }
        },
        zf = function(a) {
            try {
                return 2 < wf(a) ? a.a.statusText : ""
            } catch (b) {
                return V(a.l, "Can not get status: " + b.message), ""
            }
        },
        Af = function(a) {
            try {
                return a.a ? a.a.responseText : ""
            } catch (b) {
                return V(a.l, "Can not get responseText: " + b.message), ""
            }
        },
        Bf = function(a) {
            if (a.a && 4 == wf(a)) return a = a.a.getResponseHeader("Server"), null === a ? void 0 : a
        },
        pf = function(a, b) {
            return b + " [" + a.X + " " + a.w + " " + xf(a) + "]"
        };
    var Y = angular.module("home", ["home.constants", "ngAnimate"]);
    Y.config(["$locationProvider", "$compileProvider", "imaxClientLogLevel", function(a, b, c) {
        a.html5Mode(!0);
        a = R("");
        if (!He) {
            He = {};
            for (var d = 0, e; e = Ge[d]; d++) He[e.value] = e, He[e.name] = e
        }
        a.da = He[c] || null;
        c = kf = new gf;
        1 != c.b && (Le(), a = Ke, d = c.h, a.a || (a.a = []), a.a.push(d), c.b = !0);
        b.imgSrcSanitizationWhitelist(/^\s*(https?|ftp|chrome|chrome-resource|file):|data:image\//)
    }]);
    var Cf = function(a) {
        a.isSimpleTopic = !(!N(a.topic, 13) && !N(a.topic, 14) && 1 != W(a.topic) && 14 != W(a.topic) && 16 != W(a.topic));
        a.isOnDeviceImageTopic = 12 == W(a.topic);
        a.isReadyToCastTopic = 13 == W(a.topic);
        a.isTI = 16 == W(a.topic) || 15 == W(a.topic);
        if (O(a.topic, af, 23)) {
            var b = O(a.topic, af, 23);
            a.location = N(b, 5);
            a.profilePhoto = N(b, 7);
            a.ownerUserName = N(b, 6);
            a.caption = b.qa();
            a.likes = N(b, 4);
            a.comments = N(b, 8);
            a.timestamp = N(b, 9)
        }
    };
    ma("imax.CurrentTopicController", Cf);
    Cf.$inject = ["$scope"];
    Y.controller("imax.CurrentTopicController", Cf);
    var Df = function(a, b, c, d, e, f) {
        this.a = a;
        this.h = b;
        this.F = d;
        this.D = e;
        this.j = f;
        this.g = this.b = this.c = !1;
        b = r(this.B, this);
        a.formatNumber = b;
        this.m();
        this.F(r(this.m, this), 5E3);
        this.o = new K(c.absUrl());
        a.$on("eurekaInfoUpdated", r(this.w, this));
        a.$on("doneUpdatingBackground", r(this.H, this))
    };
    ma("imax.OverlaysController", Df);
    Df.$inject = "$scope $timeout $location $interval highInfoIdleInterval showAppUrlInterval wordyFlag".split(" ");
    Df.prototype.l = R("OverlaysCtrl");
    var Ef = {
            va: !0
        },
        Ff = {
            va: !1
        };
    Df.prototype.m = function() {
        var a = this.a,
            b = this.a.e,
            c = mb(Ba.fa, Ba.ga)[7].replace(/a | a|a/, ""),
            d;
        null != b ? d = b.b : d = (new K(window.location.href)).c.J("timeFormat")[0];
        2 == d && (c = c.replace(/h+/g, "HH"));
        1 == d && (c = c.replace(/H+/g, "h"));
        b = new Date;
        c = new Wb(c);
        if (!b) throw Error("g");
        d = [];
        for (var e = 0; e < c.b.length; ++e) {
            var f = c.b[e].text;
            1 == c.b[e].type ? d.push(ac(c, f, b, b, b)) : d.push(f)
        }
        a.formattedTime = d.join("")
    };
    Df.prototype.H = function(a, b) {
        this.c || 12 == W(b) || ("all" == Td(this.o, "client-logging-level") && console.log("Done updating background."), this.c = !0, Gf(this))
    };
    Df.prototype.w = function() {
        this.b || (this.b = !0, Gf(this))
    };
    var Gf = function(a) {
        !a.g && a.b && a.c && (U(a.l, "Showing detailed device info"), a.g = !0, a.a.chromecastLogoStyle = {
            opacity: 1
        }, a.a.deviceInfoStyle = {
            opacity: 1
        }, a.a.deviceNameOnlyStyle = {
            opacity: 0
        }, a.h(r(a.C, a), a.D))
    };
    Df.prototype.C = function() {
        V(this.l, "Hiding detailed device info");
        this.a.deviceInfoStyle = {
            opacity: 0
        };
        this.a.chromecastLogoStyle = {
            opacity: 0
        };
        this.a.deviceNameAndAppUrlStyle = {
            opacity: 1
        };
        this.a.$apply();
        0 < this.j && this.h(r(this.I, this), this.j)
    };
    Df.prototype.I = function() {
        V(this.l, "onHideAppUrl_");
        this.a.deviceNameAndAppUrlStyle = {
            opacity: 0
        };
        this.a.deviceNameOnlyStyle = {
            opacity: 1
        };
        this.a.$apply()
    };
    var Hf = function(a) {
        return (a = a.search()["ping-idle-interval"]) ? 1E3 * a : 2E4
    };
    Hf.$inject = ["$location"];
    var If = function(a) {
        return (a = a.search()["show-app-url-interval"]) ? 1E3 * a : 0
    };
    If.$inject = ["$location"];
    var Jf = function(a, b) {
        return (a = a.search().wordy) ? !!JSON.parse(a) : b.va
    };
    Jf.$inject = ["$location", "betaGroup"];
    Df.prototype.B = function(a) {
        var b;
        b = new cc;
        var c = a;
        if (isNaN(c)) b = w.La;
        else {
            a = [];
            var d;
            d = c;
            if (0 == b.I) d = hc;
            else {
                d = Math.abs(d);
                var e = ic(b, 1 >= d ? 0 : dc(d)).ha;
                d = ic(b, e + dc(ec(b, d / Math.pow(10, e)).ra))
            }
            c /= Math.pow(10, d.ha);
            a.push(d.prefix);
            e = 0 > c || 0 == c && 0 > 1 / c;
            a.push(e ? b.w : b.D);
            if (isFinite(c))
                if (c = c * (e ? -1 : 1) * b.c, b.C) {
                    var f = c;
                    if (0 == f) fc(b, f, b.a, a), gc(b, 0, a);
                    else {
                        c = Math.floor(Math.log(f) / Math.log(10) + 2E-15);
                        var g = Math.pow(10, c);
                        isFinite(g) && 0 !== g ? f /= g : (g = Math.pow(10, Math.floor(c / 2)), f = f / g / g, 1 == c % 2 && (f = 0 < c ? f / 10 :
                            10 * f));
                        g = b.a;
                        if (1 < b.m && b.m > b.a) {
                            for (; 0 != c % b.m;) f *= 10, c--;
                            g = 1
                        } else 1 > b.a ? (c++, f /= 10) : (c -= b.a - 1, f *= Math.pow(10, b.a - 1));
                        fc(b, f, g, a);
                        gc(b, c, a)
                    }
                } else fc(b, c, b.a, a);
            else a.push(w.Ia);
            a.push(e ? b.B : b.F);
            a.push(d.ua);
            b = a.join("")
        }
        return b
    };
    var Kf = function(a) {
        a = a.search()["beta-group"];
        return "B" == a || "b" == a ? Ff : Ef
    };
    Kf.$inject = ["$location"];
    Y.factory("highInfoIdleInterval", Hf).factory("showAppUrlInterval", If).factory("wordyFlag", Jf).factory("betaGroup", Kf).controller("imax.OverlaysController", Df);
    var Lf = function(a) {
        var b = ib(hb(de(a.topic, bf, 27), function(a) {
            return !!N(a, 3)
        }), function(a) {
            return N(a, 3)
        }).join(" \u2022 ");
        a.metadataLine1 = N(a.topic, 13);
        a.metadataLine2 = N(a.topic, 14);
        b && 10 != W(a.topic) && (N(a.topic, 14) ? a.breadcrumbs = " \u2022 " + b : a.breadcrumbs = b);
        a.metadataLine3 = N(a.topic, 21);
        a.showAppUrl = !(9 == W(a.topic) || 10 == W(a.topic) || 13 == W(a.topic))
    };
    ma("imax.SimpleTopicInfoController", Lf);
    Lf.$inject = ["$scope"];
    Y.controller("imax.SimpleTopicInfoController", Lf);
    var Nf = function(a, b) {
        return {
            restrict: "A",
            transclude: "element",
            link: la(Mf, b, a)
        }
    };
    ma("imax.CrossFadingContainer.create", Nf);
    Nf.$inject = ["$animate", "topicLoader"];
    var Mf = function(a, b, c, d, e, f, g) {
        var h, l, m = 0,
            B = Math.round(20 * (Math.random() - .5)),
            u = Math.round(20 * (Math.random() - .5));
        c.$on("topicUpdated", function(e, f) {
            var x, I;
            B += .5 > Math.random() ? -2 : 2;
            u += .5 > Math.random() ? -2 : 2;
            B = Math.min(Math.max(-20, B), 20);
            u = Math.min(Math.max(-20, u), 20);
            h && (x = h);
            l && (I = l);
            h = c.$new();
            e = Pf(a);
            if (0 < e.length) {
                var C = m % e.length;
                m += 1;
                h.appUrl = e[C].Z;
                h.appUrlLineTwo = e[C].ja
            }
            h.topic = f;
            h.leftOffset = {
                "margin-left": B + "px",
                "margin-top": u + "px"
            };
            h.rightOffset = {
                "margin-right": B + "px",
                "margin-top": u +
                    "px"
            };
            h.verticalOffset = {
                "margin-top": u + "px"
            };
            h.backgroundUrl = N(f, 1);
            h.showWeather = c.showWeather;
            "undefined" != typeof N(f, 16) && (h.portraitImageUrl = N(f, 16));
            g(h, function(a) {
                l = a;
                I ? (b.addClass(I, "wVkm7e-Pd96ce"), b.enter(a, d.parent(), angular.element(d.parent()[0].lastChild)).then(function() {
                    I.remove();
                    x && x.$destroy();
                    c.$emit("doneUpdatingBackground", f)
                })) : (d.parent().append(a), c.$emit("doneUpdatingBackground", f))
            })
        })
    };
    Y.directive("crossFadingContainer", Nf);
    var Rf = function(a) {
        return {
            restrict: "A",
            replace: !0,
            template: '<div class="needs-extender-banner" ng-style="bannerStyle">  <img src="https://www.gstatic.com/chromecast/home/hdmi_extender.png"></img></div>',
            link: function(b, c) {
                var d = new Qf(b, c, a);
                b.$on("showNeedsExtenderBanner", function() {
                    if (1 != d.b) {
                        d.b = 1;
                        var a;
                        a = parseInt(d.c.localStorage.getItem("HomeScreen.showExtenderCount"), 10);
                        isNaN(a) && (a = 0);
                        5 > a ? (a++, d.c.localStorage.setItem("HomeScreen.showExtenderCount", a + ""), a = !0) : a = !1;
                        a && (a = new Image, a.onload =
                            r(d.j, d), a.onerror = r(d.h, d), a.src = "https://www.gstatic.com/chromecast/home/hdmi_extender.png")
                    }
                })
            }
        }
    };
    Rf.$inject = ["$window"];
    var Qf = function(a, b, c) {
        this.b = 0;
        this.a = a;
        this.g = b;
        this.c = c
    };
    Qf.prototype.l = R("BannerLoader");
    Qf.prototype.j = function() {
        U(this.l, "Preload successful, showing banner");
        this.g[0].addEventListener("webkitAnimationEnd", r(function() {
            this.a.bannerStyle = {
                webkitAnimationName: ""
            };
            this.a.$apply();
            this.b = 0;
            U(this.l, "Done loading banner.")
        }, this));
        this.a.bannerStyle = {
            webkitAnimationName: "needs-extender-banner-move"
        };
        this.a.$apply()
    };
    Qf.prototype.h = function() {
        U(this.l, "Error loading needs extender banner.  Giving up.");
        this.b = 0
    };
    Y.directive("needsExtenderBanner", Rf);
    var Sf = function(a) {
        this.a = a;
        this.b = "chrome://home";
        "1" === Td(new K(this.a.location.href), "cast-test-auth") && (this.b = this.a.location.origin)
    };
    Sf.$inject = ["$window"];
    //Sf.prototype.loaded = function() {
        //this.a.parent.postMessage(JSON.stringify({
            //type: "REMOTE_WINDOW",
            //status: "LOADED"
        //}), this.b)
    //};
    Y.service("eurekaHomeScreenApi", Sf);
    Y.service("historyService", function() {
        this.a = []
    });
    Y.value("imageFactory", {
        create: function() {
            return new Image
        }
    });
    var Vf = function(a) {
        this.o = new Me(this);
        this.l = R("imax.LoggingService");
        this.D = t().toString();
        this.F = new ze;
        this.a = new Tc;
        this.m = 0;
        this.j = a;
        this.w = !1;
        this.c = this.b = null;
        U(this.l, "Initial CEC status: " + Tf(this));
        a = new fd;
        this.j.m && E(a, 3, this.j.m);
        var b = new Xc;
        E(b, 1, 6);
        E(b, 3, 4);
        var c = new Vc;
        E(c, 1, b);
        E(this.a, 1, c);
        E(this.a, 3, "BACKDROP:" + this.D);
        a && E(this.a, 2, a);
        this.h = new Ve(9E5);
        Uf(this);
        this.o.C(window, "beforeunload", r(this.C, this));
        this.log(67)
    };
    Vf.$inject = ["topicLoader"];
    var Wf = /^GFE/;
    Vf.prototype.log = function(a, b) {
        var c = new hd;
        E(c, 1, a);
        a = t().toString();
        E(c, 2, a);
        a = Tf(this);
        E(c, 6, a);
        E(c, 7, this.j.g);
        oc(this.a, 4, c);
        !b && 30 <= qc(this.a, 4) && this.g()
    };
    var Tf = function(a) {
            if (null == a.c && null == a.b) return 0;
            a.c && a.b && U(a.l, "Impossible state reached: Standby and Visibility are both true.");
            return null == a.b ? a.c ? 2 : 3 : a.b ? 1 : 2
        },
        Xf = function(a) {
            var b = Tf(a);
            a.m == b ? U(a.l, "CEC status is the same, not updated: " + b) : (a.m = b, U(a.l, "Updated CEC status: " + a.m), a.log(69))
        };
    Vf.prototype.B = function(a) {
        this.w = a
    };
    var Uf = function(a) {
        var b = r(a.g, a, !1);
        Oe(a.o, a.h, "tick", b, void 0);
        a.h.start()
    };
    Vf.prototype.g = function(a) {
        We(this.h);
        a || this.log(68, !0);
        var b = Oc(this.F, this.a),
            b = JSON.stringify(b);
        U(this.l, "Logging request generated: " + b);
        this.w && (U(this.l, "Dispatching logging XHR."), a ? (U(this.l, "Final flush, using native XMLHttpRequest."), a = new XMLHttpRequest, a.open("POST", "/cast/chromecast/home/log", !0), a.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=utf-8"), a.send(b)) : Yf(this, function(a) {
                U(this.l, "Logging request succeeded.");
                U(this.l, "Response as follows:" + a)
            },
            function(a) {
                U(this.l, "Logging request failed with code:" + a)
            }, b));
        rc(this.a);
        Uf(this)
    };
    Vf.prototype.C = function() {
        U(this.l, "Flushing before leaving..");
        this.log(71, !0);
        this.g(!0)
    };
    var $f = function(a, b, c, d, e, f) {
            return function(g) {
                g = g.target;
                if (yf(g)) {
                    if (c) {
                        var h = "";
                        0 < Af(g).length && (h = Af(g));
                        c.call(a, h)
                    }
                } else {
                    var h = xf(g),
                        l = 5 > b,
                        m = 2 >= b && 500 === h,
                        B = 502 === h && Wf.test(Bf(g));
                    l && (B || m || 503 === h || 504 === h) ? ((l = a.l) && l.log(Q, "Retrying XhrIo; failed with code: " + h, void 0), Zf(a, String(g.w), b + 1, $f(a, b + 1, c, d, e, f), e, f)) : ((l = a.l) && l.log(Q, "XhrIo failed with code: " + h, void 0), d && d.call(a, xf(g)))
                }
            }
        },
        Yf = function(a, b, c, d) {
            Zf(a, "/cast/chromecast/home/log", 0, $f(a, 0, b, c, "POST", d), "POST", d)
        },
        Zf = function(a, b, c, d, e, f) {
            c = 0 === c ? 0 : 2E3 * Math.pow(3, c - 1) * (Math.random() + .5);
            0 == c ? of (b, d, e, f) : n.setTimeout(r(function() { of (b, d, e, f)
            }, a), c)
        };
    Y.service("loggingService", Vf);
    var bg = function(a) {
            cast.receiver.analytics.logEvent("Cast.IMAX.NewTopics");
            cast.receiver.analytics.logInt("Cast.IMAX.NumLinkedUser", N(a, 7) || 0);
            O(a, $e, 6) && ag();
            (a = de(a, ff, 1)) && a[0] && N(a[0], 29) && gb(N(a[0], 29), function(a) {
                cast.receiver.analytics.logInt("Cast.IMAX.Experiment", a)
            })
        },
        cg = function() {
            cast.receiver.analytics.logEvent("Cast.IMAX.TopicsFailed")
        },
        ag = function() {
            cast.receiver.analytics.logEvent("Cast.IMAX.NewWeather")
        };
    Y.service("umaLoggingService", function() {});
    var dg = function(a, b, c, d, e, f, g, h, l) {
        this.a = c;
        this.m = d;
        this.g = e;
        this.j = f;
        this.w = g;
        this.m.addEventListener("message", r(this.o, this), !1);
        this.b = void 0;
        b.$on("weatherInfoUpdated", r(this.h, this));
        this.h(0, h.a);
        this.c = l;
        this.g(function() {
            c.infoBoxStyle = {
                webkitAnimationName: "move-info-box"
            };
            c.$apply()
        }, 1E4)
    };
    ma("imax.InfoController", dg);
    dg.$inject = "$injector $rootScope $scope $window $timeout idleInterval $location topicLoader loggingService".split(" ");
    var eg = [],
        fg = {
            clear: "LtZq3b-Bz112c-JbbQac",
            cloudy: "LtZq3b-Bz112c-OwRJw",
            haze_dust_snow_fog_etc: "LtZq3b-Bz112c-ZMvGec",
            partially_cloudy: "LtZq3b-Bz112c-qMuUrf",
            rain: "LtZq3b-Bz112c-LIKRTb",
            snow: "LtZq3b-Bz112c-hHh3ld",
            thunderstorm: "LtZq3b-Bz112c-gjyYBe",
            windy: "LtZq3b-Bz112c-NCU8D",
            very_cold: "LtZq3b-Bz112c-WUnPec"
        };
    dg.prototype.l = R("InfoCtrl");
    dg.prototype.h = function(a, b) {
        if (b && void 0 !== ae(b) && N(b, 10) && (be(b, 11, !1) || be(b, 12, !1))) {
            var c, d;
            a = ae(b);
            be(b, 11, !1) && (c = Math.round((a - 32) / 1.8).toString() + "\u00b0");
            be(b, 12, !1) && (d = Math.round(a).toString() + "\u00b0");
            be(b, 11, !1) && be(b, 12, !1) && (c += "C", d += "F");
            this.a.formattedCelsiusTemperature = c;
            this.a.formattedFahrenheitTemperature = d;
            b = N(b, 10);
            this.a.weatherConditionIconUrl = b;
            this.a.showWeather = !0;
            (b = b.match(/\/(\w+)\.png/)) && fg && (this.a.weatherConditionIconType = fg[b[1]])
        } else this.a.showWeather = !1
    };
    dg.prototype.o = function(a) {
        V(this.l, "event.data: " + a.data);
        a = JSON.parse(a.data);
        switch (a.type) {
            case "EUREKA_INFO":
                a = a.eureka_info;
                var b = new pa(a, this.w.host());
                this.a.e = b;
                for (var c = 0; c < eg.length; c++) eg[c](this.a, a);
                b.a ? this.c.B(!0) : gg(this.c.B.bind(this.c));
                this.a.$apply();
                this.a.$broadcast("eurekaInfoUpdated");
                a.jb && this.a.$broadcast("showNeedsExtenderBanner");
                break;
            case "PING":
                hg(this)
        }
    };
    var gg = function(a) {
            var b = new XMLHttpRequest;
            b.onreadystatechange = function() {
                if (4 == b.readyState) {
                    var c = JSON.parse(b.responseText);
                    a(oa(c))
                }
            };
            b.open("GET", "http://127.0.0.1:8008/setup/eureka_info?params=opt_in.stats");
            b.send()
        },
        hg = function(a) {
            a.b || (a.a.$broadcast("showPing"), a.b = a.g(function() {
                this.g.cancel(this.b);
                this.b = void 0;
                this.a.$broadcast("hidePing")
            }.bind(a), a.j))
        },
        ig = function(a) {
            return (a = a.search()["ping-idle-interval"]) ? 1E3 * a : 6E4
        };
    ig.$inject = ["$location"];
    Y.factory("idleInterval", ig).controller("InfoCtrl", dg);
    var jg = function(a, b, c, d, e, f, g) {
        this.l = R("imax.SecondScreenReceiver");
        this.g = null;
        this.h = 0;
        this.w = f.m;
        this.B = b;
        this.o = c;
        this.c = null;
        this.m = d;
        this.j = g;
        a = window.cast || {};
        this.a = a.receiver.CastReceiverManager.getInstance();
        this.b = this.a.getCastMessageBus("urn:x-cast:com.google.cast.sse", a.receiver.CastMessageBus.MessageType.JSON);
        this.b.onMessage = this.kb.bind(this);
        this.a.onSenderConnected = this.lb.bind(this);
        this.a.onSenderDisconnected = this.mb.bind(this);
        this.a.onVisibilityChanged = this.ob.bind(this);
        this.a.onStandbyChanged = this.nb.bind(this);
        try {
            this.a.start()
        } catch (h) {
            (a = this.l) && a.log(Q, "Cast receiver fail to start", h)
        }
    };
    jg.$inject = "$injector $rootScope $timeout loggingService umaLoggingService topicLoader historyService".split(" ");
    k = jg.prototype;
    k.lb = function() {
        V(this.l, "onSenderConnected. Total number of senders: " + this.a.getSenders().length)
    };
    k.mb = function() {
        V(this.l, "onSenderDisconnected. Total # of senders: " + this.a.getSenders().length)
    };
    k.kb = function(a) {
        var b = a.data;
        a = a.senderId;
        b.requestId && "GET_STATUS" === b.type && (cast.receiver.analytics.logInt("Cast.IMAX.SSERequest", N(this.g, 28) || 0), this.b.send(a, kg(this, b.requestId)));
        b.requestId && "SETTINGS_UPDATED" === b.type && (cast.receiver.analytics.logEvent("Cast.IMAX.SettingsChanged"), 0 == this.h ? (this.c && this.o.cancel(this.c), this.c = this.o(r(this.ta, this), 2E3)) : this.ta());
        V(this.l, "senderId: " + a);
        V(this.l, "message:" + b)
    };
    k.ta = function() {
        this.c = null;
        this.B.$emit("settingsUpdated")
    };
    k.ob = function(a) {
        var b = this.m;
        b.b = a.isVisible;
        Xf(b);
        U(this.l, "Visibility changed: " + JSON.stringify(a))
    };
    k.nb = function(a) {
        var b = this.m;
        b.c = a.isStandby;
        Xf(b);
        U(this.l, "Standby changed: " + JSON.stringify(a))
    };
    var kg = function(a, b) {
        var c = {};
        c.requestId = b;
        c.backendData = a.g.h();
        c.numLinkedUsers = a.h;
        c.appDeviceId = a.w;
        a.j && (c.topicHistory = a.j.a);
        return c
    };
    Y.service("secondScreenReceiver", jg);
    var lg = function(a, b, c, d, e, f, g, h, l, m, B, u, C, x) {
        this.D = a;
        this.w = b.absUrl();
        this.o = d;
        this.H = null != l ? l : null;
        this.X = f;
        this.I = g;
        this.F = O(h, Ye, 2);
        this.a = O(h, $e, 6);
        this.g = N(h, 7) || 0;
        this.m = N(h, 8) || "";
        this.h = !1;
        this.b = nb(de(h, ff, 1));
        "all" == Td(new K(this.w), "client-logging-level") && console.log("Fetched topic count was: " + this.b.length);
        this.B = 0;
        this.U = m;
        this.W = B;
        this.T = u;
        this.S = C;
        this.M = x;
        this.j = c;
        this.c = null;
        this.a && (this.c = this.j(this.C.bind(this), this.I));
        bg(h)
    };
    lg.$inject = "$http $location $interval $rootScope umaLoggingService numCachedTopics weatherUpdateIntervalMs initialState showDurationOverride isTextPromoEnabled isVoicePromoLanguage isOffersPromoEnabled isGFiberMessagingEnabled isAndroidTv".split(" ");
    var mg = 6E4 * (15 + 30 * Math.random());
    lg.prototype.l = R("TopicLoader");
    var ng = function(a, b) {
        var c = {
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            }
        };
        a.M && (c.headers.BackdropPostData = b);
        return c
    };
    lg.prototype.L = function(a, b, c, d) {
        200 == d ? (b = new df(c), this.F = O(b, Ye, 2), this.b = nb(de(b, ff, 1)), bg(b), "all" == Td(new K(this.w), "client-logging-level") && console.log("Refreshed topic count was: " + this.b.length), this.a = O(b, $e, 6), this.o.$emit("weatherInfoUpdated", this.a), this.c && (this.j.cancel(this.c), this.c = null), this.a && (this.c = this.j(this.C.bind(this), this.I)), this.g = N(b, 7) || 0, og(this, a)) : (cg(), b(d))
    };
    lg.prototype.C = function() {
        this.D.get("/cast/chromecast/home/weather").success(r(this.ba, this))
    };
    lg.prototype.ba = function(a, b) {
        200 == b ? (this.a = new $e(a), ag(), this.o.$emit("weatherInfoUpdated", this.a)) : 204 == b ? (this.a = null, this.o.$emit("weatherInfoUpdated", this.a)) : (cast.receiver.analytics.logEvent("Cast.IMAX.WeatherFailed"), console.log("Failed to update weather."))
    };
    lg.prototype.K = function(a, b, c) {
        cg();
        a(c)
    };
    var og = function(a, b) {
            var c = a.b.shift();
            a.B++;
            null != a.H && ce(c, 5, a.H);
            b(c)
        },
        pg = function(a) {
            return new df(a)
        };
    pg.$inject = ["initialStateJson"];
    var qg = function(a) {
        return (a = a.search()["slideshow-period"]) ? Number(a) : null
    };
    qg.$inject = ["$location"];
    var Pf = function(a) {
        var b = [];
        a.U && (0 < a.g ? (b.push({
            Z: "Open the Google Home app",
            ja: "Select devices from the drawer"
        }), a.W && b.push({
            Z: "Open the Google app",
            ja: 'Ask \u201cOk Google, What\u2019s on my Chromecast?"'
        })) : b.push({
            Z: "Customize this screen: chromecast.com/backdrop"
        }), a.T && b.push({
            Z: "View your offers at chromecast.com/offers"
        }), b.push({
            Z: "New apps at chromecast.com/apps"
        }));
        a.S && b.push({
            Z: "Press exit, guide, or channel up/down",
            ja: "on your remote to return to the TV"
        });
        return b
    };
    Y.service("topicLoader", lg).constant("numCachedTopics", 50).constant("weatherUpdateIntervalMs", mg).factory("initialState", pg).factory("showDurationOverride", qg);
    eg.push(function(a, b) {
        var c;
        if ("device_info" in b) {
            var d = b.device_info;
            "model_name" in d && (c = d.model_name)
        }
        var e;
        "net" in b && (b = b.net, "ethernet_oui" in b && (e = b.ethernet_oui));
        a.e.shouldShowLutherPowerWarning = "Chromecast Ultra" === c && !e
    });
    eg.push(function(a, b) {
        a.e.devicePin = b.opencast_pin_code
    });
    var Z = function(a, b, c, d, e, f, g, h, l, m, B, u, C, x, ga, I) {
        this.P = be(m, 5, 300);
        a = N(m, 4);
        if (0 < a.length)
            for (this.j = {}, m = 0; m < a.length; m++) this.j[a[m]] = !0;
        this.c = new wb(Math.max(this.P, 1), 900);
        e = e.absUrl();
        this.ib = new K(e);
        this.Y = c;
        this.D = d;
        this.S = f;
        this.L = g;
        this.g = h;
        this.o = l;
        this.w = !1;
        this.F = B;
        this.H = x;
        this.C = u;
        this.B = ga;
        this.ia = I;
        this.I = 0;
        this.h = !1;
        this.m = 0;
        this.O = this.a = null;
        this.b();
        this.Y.$on("doneUpdatingBackground", r(this.T, this));
        b.$on("settingsUpdated", r(this.U, this))
    };
    ma("imax.TopicController", Z);
    Z.$inject = "$injector $rootScope $scope $timeout $location $http imageFactory topicLoader eurekaHomeScreenApi initialState minTopicDisplayTimeSeconds loggingService umaLoggingService secondScreenReceiver historyService isImageBytesLoggingEnabled".split(" ");
    var rg = {
        url: "https://storage.googleapis.com/reliability-speedtest/random.txt"
    };
    Z.prototype.l = R("TopicCtrl");
    Z.prototype.X = function(a) {
        var b = this.c;
        b.a = Math.min(b.g, b.a * b.j);
        b.b = Math.min(b.g, b.a + (b.h ? Math.round(b.h * (Math.random() - .5) * 2 * b.a) : 0));
        var b = this.c.getValue(),
            c = this.l;
        c && c.log(Q, "Error (" + a + ") loading next slide, will retry in " + b + " seconds", void 0);
        sg(this, 1E3 * b)
    };
    var sg = function(a, b) {
        a.a && a.D.cancel(a.a);
        a.a = a.D(r(a.b, a), b)
    };
    Z.prototype.b = function() {
        this.a = null;
        var a = this.g,
            b = r(this.K, this),
            c = r(this.X, this);
        if (a.h || 0 == a.b.length) {
            a.h && (a.h = !1, a.B = 0);
            var d;
            d = new Ze;
            fe(d, 3, a.F);
            ce(d, 1, a.B);
            ce(d, 2, a.X);
            ce(d, 4, 1);
            d = d.h();
            var e = new Ld;
            Vd(e, "request", d);
            d = e.toString();
            a.D.post(a.w, d, ng(a, d)).success(r(a.L, a, b, c)).error(r(a.K, a, c))
        } else og(a, b)
    };
    Z.prototype.K = function(a) {
        var b = N(a, 5);
        this.c = new wb(Math.max(b, 1), 900);
        b = new K(N(a, 1));
        this.j && !this.j.hasOwnProperty(W(a)) ? (b = this.l, a = "Discarding unsupported topic type: " + W(a), b && b.log(Q, a, void 0), a = this.o, a.a.parent.postMessage(JSON.stringify({
            type: "REMOTE_WINDOW",
            status: "OFFLINE"
        }), a.b), this.b()) : this.O && N(this.O, 1) == b ? (V(this.l, "Discarding duplicate image: " + b), this.b()) : tg(this, a)
    };
    var tg = function(a, b) {
        var c = [];
        c[0] = N(b, 1);
        N(b, 16) && (c[1] = N(b, 16));
        O(b, af, 23) && N(O(b, af, 23), 7) && (c[2] = N(O(b, af, 23), 7));
        if (a.ia)
            for (var d = 0, e = c.length, f = 0; f < c.length; f++) a.S.head(c[f]).success(function(a, b, c) {
                c("Content-Length") && (--e, d += parseInt(c("Content-Length"), 10))
            });
        var g = r(function(a) {
            var b = a;
            return function(a) {
                this.ia && 0 == e && (cast.receiver.analytics.logInt("Cast.IMAX.ImageLoadBytes", d), V(this.l, "Network bytes loaded: " + d));
                0 == --b && (this.P = N(a, 5), this.O = a, cast.receiver.analytics.logInt("Cast.IMAX.ImageDisplay",
                    N(a, 28) || 0), this.Y.$broadcast("topicUpdated", a), "all" == Td(this.ib, "client-logging-level") && (console.log("Image url was loaded: " + N(a, 1)), console.log("Topic type was " + W(a))))
            }
        }(c.length), a, b);
        cast.receiver.analytics.logInt("Cast.IMAX.ImageLoadType", N(b, 26) || -(W(b) || 0));
        cast.receiver.analytics.logInt("Cast.IMAX.ImageLoad", N(b, 28) || 0);
        for (f = 0; f < c.length; f++) {
            V(a.l, "start loading image: " + c[f]);
            var h = a.L.create();
            h.src = c[f];
            h.onload = r(a.Y.$apply, a.Y, g);
            var l = r(a.M, a, b, c[f]);
            h.onerror = r(a.Y.$apply, a.Y,
                l)
        }
    };
    Z.prototype.M = function(a, b) {
        cast.receiver.analytics.logInt("Cast.IMAX.ImageFailed", N(a, 28) || 0);
        var c = this.l;
        a = eb('Failed to load image "%s" for [topic_type=%s url="%s", metadata_line_1="%s", metadata_line2="%s"], skipping to next topic in %d seconds.', b, W(a), N(a, 1) || "<none>", N(a, 13) || "<none>", N(a, 14) || "<none>", this.P);
        c && c.log(Q, a, void 0);
        sg(this, 1E3 * this.P)
    };
    Z.prototype.T = function() {
        this.m = t();
        if (this.B && this.O) {
            var a = this.B;
            a.a.push(this.O.h());
            5 < a.a.length && a.a.shift();
            V(this.l, "IMAX history updated.")
        }
        V(this.l, "Sending second screen update through receiver.");
        if (this.H && this.O) {
            var a = this.H,
                b = this.g.g;
            a.g = this.O;
            a.h = b;
            a.b && a.a.isSystemReady() && a.b.broadcast(kg(a, "0"))
        }
        60 <= this.P && !(34 < this.I) && this.ia && (this.I++, of ("http://127.0.0.1:8008/setup/test_internet_download_speed", r(this.W, this), "POST", JSON.stringify(rg), {
            "Content-type": "application/json"
        }));
        V(this.l, "Will load the next slide in " + this.P + " seconds");
        0 < this.P && (this.h ? (this.h = !1, sg(this, 1E3 * this.F)) : sg(this, 1E3 * this.P));
        this.w
    };
    Z.prototype.U = function() {
        U(this.l, "settingsUpdated");
        this.C && this.C.log(70);
        this.g.h = !0;
        var a = this.O && 13 == W(this.O);
        this.a || a ? (a = t() - this.m, sg(this, Math.max(1E3 * this.F - a, 0))) : this.h = !0
    };
    Z.prototype.W = function(a) {
        var b = a.target;
        yf(b) && (a = "", 0 < Af(b).length && (a = JSON.parse(Af(b))), a && "response_code" in a && 200 == a.response_code && "bytes_received" in a && "time_for_data_fetch" in a && a.bytes_received && a.time_for_data_fetch && 0 < a.bytes_received && 0 < a.time_for_data_fetch && (b = a.bytes_received / a.time_for_data_fetch * 1E3 / 1024, U(this.l, "Speedtest complted."), U(this.l, JSON.stringify(a)), cast.receiver.analytics.logInt("Cast.IMAX.SpeedtestThroughput", ~~b)))
    };
    Y.controller("TopicCtrl", Z).constant("minTopicDisplayTimeSeconds", 10);

} catch (e) {
    _DumpException(e)
}
/* _GlobalSuffix_ */
// Google Inc.