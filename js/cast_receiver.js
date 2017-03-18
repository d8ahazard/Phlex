// Copyright Google Inc. All Rights Reserved.
(function() {
    'use strict';
    var g, k = function(a, b) {
            function c() {}
            c.prototype = b.prototype;
            a.prototype = new c;
            a.prototype.constructor = a;
            for (var d in b)
                if (Object.defineProperties) {
                    var e = Object.getOwnPropertyDescriptor(b, d);
                    e && Object.defineProperty(a, d, e)
                } else a[d] = b[d]
        },
        aa = "function" == typeof Object.defineProperties ? Object.defineProperty : function(a, b, c) {
            if (c.get || c.set) throw new TypeError("ES3 does not support getters and setters.");
            a != Array.prototype && a != Object.prototype && (a[b] = c.value)
        },
        ba = "undefined" != typeof window &&
        window === this ? this : "undefined" != typeof global && null != global ? global : this,
        ca = function() {
            ca = function() {};
            ba.Symbol || (ba.Symbol = da)
        },
        ea = 0,
        da = function(a) {
            return "jscomp_symbol_" + (a || "") + ea++
        },
        ga = function() {
            ca();
            var a = ba.Symbol.iterator;
            a || (a = ba.Symbol.iterator = ba.Symbol("iterator"));
            "function" != typeof Array.prototype[a] && aa(Array.prototype, a, {
                configurable: !0,
                writable: !0,
                value: function() {
                    return fa(this)
                }
            });
            ga = function() {}
        },
        fa = function(a) {
            var b = 0;
            return ha(function() {
                return b < a.length ? {
                    done: !1,
                    value: a[b++]
                } : {
                    done: !0
                }
            })
        },
        ha = function(a) {
            ga();
            a = {
                next: a
            };
            a[ba.Symbol.iterator] = function() {
                return this
            };
            return a
        },
        ia = function(a) {
            ga();
            var b = a[Symbol.iterator];
            return b ? b.call(a) : fa(a)
        },
        l = this,
        m = function(a) {
            return void 0 !== a
        },
        n = function() {},
        ja = function(a) {
            a.rb = void 0;
            a.ca = function() {
                return a.rb ? a.rb : a.rb = new a
            }
        },
        ka = function(a) {
            var b = typeof a;
            if ("object" == b)
                if (a) {
                    if (a instanceof Array) return "array";
                    if (a instanceof Object) return b;
                    var c = Object.prototype.toString.call(a);
                    if ("[object Window]" == c) return "object";
                    if ("[object Array]" ==
                        c || "number" == typeof a.length && "undefined" != typeof a.splice && "undefined" != typeof a.propertyIsEnumerable && !a.propertyIsEnumerable("splice")) return "array";
                    if ("[object Function]" == c || "undefined" != typeof a.call && "undefined" != typeof a.propertyIsEnumerable && !a.propertyIsEnumerable("call")) return "function"
                } else return "null";
            else if ("function" == b && "undefined" == typeof a.call) return "object";
            return b
        },
        la = function(a) {
            var b = ka(a);
            return "array" == b || "object" == b && "number" == typeof a.length
        },
        p = function(a) {
            return "string" ==
                typeof a
        },
        q = function(a) {
            return "number" == typeof a
        },
        r = function(a) {
            return "function" == ka(a)
        },
        ma = function(a) {
            var b = typeof a;
            return "object" == b && null != a || "function" == b
        },
        na = function(a, b, c) {
            return a.call.apply(a.bind, arguments)
        },
        oa = function(a, b, c) {
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
        u = function(a,
            b, c) {
            u = Function.prototype.bind && -1 != Function.prototype.bind.toString().indexOf("native code") ? na : oa;
            return u.apply(null, arguments)
        },
        pa = function(a, b) {
            var c = Array.prototype.slice.call(arguments, 1);
            return function() {
                var b = c.slice();
                b.push.apply(b, arguments);
                return a.apply(this, b)
            }
        },
        qa = Date.now || function() {
            return +new Date
        },
        v = function(a, b) {
            a = a.split(".");
            var c = l;
            a[0] in c || !c.execScript || c.execScript("var " + a[0]);
            for (var d; a.length && (d = a.shift());) !a.length && m(b) ? c[d] = b : c = c[d] && Object.prototype.hasOwnProperty.call(c,
                d) ? c[d] : c[d] = {}
        },
        w = function(a, b) {
            function c() {}
            c.prototype = b.prototype;
            a.cb = b.prototype;
            a.prototype = new c;
            a.prototype.constructor = a;
            a.Fe = function(a, c, f) {
                for (var d = Array(arguments.length - 2), e = 2; e < arguments.length; e++) d[e - 2] = arguments[e];
                return b.prototype[c].apply(a, d)
            }
        };
    var cast = l.cast || {};
    v("cast.receiver.VERSION", "2.0.0");
    var ra = function(a, b) {
        a = a.toLowerCase();
        b = b.toLowerCase();
        return 0 == a.indexOf(b) || 0 == b.indexOf(a)
    };
    var sa = function(a) {
        if (Error.captureStackTrace) Error.captureStackTrace(this, sa);
        else {
            var b = Error().stack;
            b && (this.stack = b)
        }
        a && (this.message = String(a))
    };
    w(sa, Error);
    sa.prototype.name = "CustomError";
    var ta;
    var ua = function(a, b) {
            for (var c = a.split("%s"), d = "", e = Array.prototype.slice.call(arguments, 1); e.length && 1 < c.length;) d += c.shift() + e.shift();
            return d + c.join("%s")
        },
        va = String.prototype.trim ? function(a) {
            return a.trim()
        } : function(a) {
            return a.replace(/^[\s\xa0]+|[\s\xa0]+$/g, "")
        },
        wa = function(a, b) {
            a = String(a).toLowerCase();
            b = String(b).toLowerCase();
            return a < b ? -1 : a == b ? 0 : 1
        },
        xa = function(a, b) {
            return a < b ? -1 : a > b ? 1 : 0
        };
    var ya = function(a, b) {
        b.unshift(a);
        sa.call(this, ua.apply(null, b));
        b.shift()
    };
    w(ya, sa);
    ya.prototype.name = "AssertionError";
    var za = function(a, b, c, d) {
            var e = "Assertion failed";
            if (c) var e = e + (": " + c),
                f = d;
            else a && (e += ": " + a, f = b);
            throw new ya("" + e, f || []);
        },
        x = function(a, b, c) {
            a || za("", null, b, Array.prototype.slice.call(arguments, 2))
        },
        Aa = function(a, b) {
            throw new ya("Failure" + (a ? ": " + a : ""), Array.prototype.slice.call(arguments, 1));
        },
        Ba = function(a, b, c) {
            q(a) || za("Expected number but got %s: %s.", [ka(a), a], b, Array.prototype.slice.call(arguments, 2))
        },
        Ca = function(a, b, c) {
            r(a) || za("Expected function but got %s: %s.", [ka(a), a], b, Array.prototype.slice.call(arguments,
                2))
        };
    var Da = Array.prototype.indexOf ? function(a, b, c) {
            x(null != a.length);
            return Array.prototype.indexOf.call(a, b, c)
        } : function(a, b, c) {
            c = null == c ? 0 : 0 > c ? Math.max(0, a.length + c) : c;
            if (p(a)) return p(b) && 1 == b.length ? a.indexOf(b, c) : -1;
            for (; c < a.length; c++)
                if (c in a && a[c] === b) return c;
            return -1
        },
        Ea = Array.prototype.lastIndexOf ? function(a, b, c) {
            x(null != a.length);
            return Array.prototype.lastIndexOf.call(a, b, null == c ? a.length - 1 : c)
        } : function(a, b, c) {
            c = null == c ? a.length - 1 : c;
            0 > c && (c = Math.max(0, a.length + c));
            if (p(a)) return p(b) && 1 ==
                b.length ? a.lastIndexOf(b, c) : -1;
            for (; 0 <= c; c--)
                if (c in a && a[c] === b) return c;
            return -1
        },
        Fa = Array.prototype.forEach ? function(a, b, c) {
            x(null != a.length);
            Array.prototype.forEach.call(a, b, c)
        } : function(a, b, c) {
            for (var d = a.length, e = p(a) ? a.split("") : a, f = 0; f < d; f++) f in e && b.call(c, e[f], f, a)
        },
        Ga = Array.prototype.filter ? function(a, b, c) {
            x(null != a.length);
            return Array.prototype.filter.call(a, b, c)
        } : function(a, b, c) {
            for (var d = a.length, e = [], f = 0, h = p(a) ? a.split("") : a, t = 0; t < d; t++)
                if (t in h) {
                    var y = h[t];
                    b.call(c, y, t, a) && (e[f++] =
                        y)
                }
            return e
        },
        Ia = function(a, b) {
            b = Da(a, b);
            var c;
            (c = 0 <= b) && Ha(a, b);
            return c
        },
        Ha = function(a, b) {
            x(null != a.length);
            Array.prototype.splice.call(a, b, 1)
        },
        Ja = function(a) {
            var b = a.length;
            if (0 < b) {
                for (var c = Array(b), d = 0; d < b; d++) c[d] = a[d];
                return c
            }
            return []
        },
        La = function(a, b, c, d) {
            x(null != a.length);
            return Array.prototype.splice.apply(a, Ka(arguments, 1))
        },
        Ka = function(a, b, c) {
            x(null != a.length);
            return 2 >= arguments.length ? Array.prototype.slice.call(a, b) : Array.prototype.slice.call(a, b, c)
        };
    var Ma = function(a, b, c) {
            for (var d in a) b.call(c, a[d], d, a)
        },
        Na = function(a, b) {
            for (var c in a)
                if (b.call(void 0, a[c], c, a)) return !0;
            return !1
        },
        Oa = function(a) {
            var b = [],
                c = 0,
                d;
            for (d in a) b[c++] = d;
            return b
        },
        Pa = function(a, b) {
            return null !== a && b in a
        },
        Qa = function(a) {
            var b = {},
                c;
            for (c in a) b[c] = a[c];
            return b
        },
        Ra = "constructor hasOwnProperty isPrototypeOf propertyIsEnumerable toLocaleString toString valueOf".split(" "),
        Sa = function(a, b) {
            for (var c, d, e = 1; e < arguments.length; e++) {
                d = arguments[e];
                for (c in d) a[c] = d[c];
                for (var f =
                        0; f < Ra.length; f++) c = Ra[f], Object.prototype.hasOwnProperty.call(d, c) && (a[c] = d[c])
            }
        };
    var Ta;
    a: {
        var Ua = l.navigator;
        if (Ua) {
            var Va = Ua.userAgent;
            if (Va) {
                Ta = Va;
                break a
            }
        }
        Ta = ""
    }
    var z = function(a) {
        return -1 != Ta.indexOf(a)
    };
    var Wa = "StopIteration" in l ? l.StopIteration : {
            message: "StopIteration",
            stack: ""
        },
        Xa = function() {};
    Xa.prototype.next = function() {
        throw Wa;
    };
    Xa.prototype.Yc = function() {
        return this
    };
    var Ya = function(a, b) {
        this.K = {};
        this.l = [];
        this.gb = this.ia = 0;
        var c = arguments.length;
        if (1 < c) {
            if (c % 2) throw Error("Uneven number of arguments");
            for (var d = 0; d < c; d += 2) this.set(arguments[d], arguments[d + 1])
        } else a && this.addAll(a)
    };
    Ya.prototype.tc = function() {
        Za(this);
        for (var a = [], b = 0; b < this.l.length; b++) a.push(this.K[this.l[b]]);
        return a
    };
    var $a = function(a) {
        Za(a);
        return a.l.concat()
    };
    Ya.prototype.Ub = function() {
        return 0 == this.ia
    };
    Ya.prototype.clear = function() {
        this.K = {};
        this.gb = this.ia = this.l.length = 0
    };
    Ya.prototype.remove = function(a) {
        return ab(this.K, a) ? (delete this.K[a], this.ia--, this.gb++, this.l.length > 2 * this.ia && Za(this), !0) : !1
    };
    var Za = function(a) {
        if (a.ia != a.l.length) {
            for (var b = 0, c = 0; b < a.l.length;) {
                var d = a.l[b];
                ab(a.K, d) && (a.l[c++] = d);
                b++
            }
            a.l.length = c
        }
        if (a.ia != a.l.length) {
            for (var e = {}, c = b = 0; b < a.l.length;) d = a.l[b], ab(e, d) || (a.l[c++] = d, e[d] = 1), b++;
            a.l.length = c
        }
    };
    g = Ya.prototype;
    g.get = function(a, b) {
        return ab(this.K, a) ? this.K[a] : b
    };
    g.set = function(a, b) {
        ab(this.K, a) || (this.ia++, this.l.push(a), this.gb++);
        this.K[a] = b
    };
    g.addAll = function(a) {
        var b;
        if (a instanceof Ya) b = $a(a), a = a.tc();
        else {
            b = Oa(a);
            var c = [],
                d = 0,
                e;
            for (e in a) c[d++] = a[e];
            a = c
        }
        for (c = 0; c < b.length; c++) this.set(b[c], a[c])
    };
    g.forEach = function(a, b) {
        for (var c = $a(this), d = 0; d < c.length; d++) {
            var e = c[d],
                f = this.get(e);
            a.call(b, f, e, this)
        }
    };
    g.clone = function() {
        return new Ya(this)
    };
    g.Yc = function(a) {
        Za(this);
        var b = 0,
            c = this.gb,
            d = this,
            e = new Xa;
        e.next = function() {
            if (c != d.gb) throw Error("The map has changed since the iterator was created");
            if (b >= d.l.length) throw Wa;
            var e = d.l[b++];
            return a ? e : d.K[e]
        };
        return e
    };
    var ab = function(a, b) {
        return Object.prototype.hasOwnProperty.call(a, b)
    };
    var bb = function(a) {
        bb[" "](a);
        return a
    };
    bb[" "] = n;
    var db = function(a, b) {
        var c = cb;
        return Object.prototype.hasOwnProperty.call(c, a) ? c[a] : c[a] = b(a)
    };
    var eb = z("Opera"),
        A = z("Trident") || z("MSIE"),
        fb = z("Edge"),
        gb = z("Gecko") && !(-1 != Ta.toLowerCase().indexOf("webkit") && !z("Edge")) && !(z("Trident") || z("MSIE")) && !z("Edge"),
        hb = -1 != Ta.toLowerCase().indexOf("webkit") && !z("Edge"),
        ib = function() {
            var a = l.document;
            return a ? a.documentMode : void 0
        },
        jb;
    a: {
        var kb = "",
            lb = function() {
                var a = Ta;
                if (gb) return /rv\:([^\);]+)(\)|;)/.exec(a);
                if (fb) return /Edge\/([\d\.]+)/.exec(a);
                if (A) return /\b(?:MSIE|rv)[: ]([^\);]+)(\)|;)/.exec(a);
                if (hb) return /WebKit\/(\S+)/.exec(a);
                if (eb) return /(?:Version)[ \/]?(\S+)/.exec(a)
            }();
        lb && (kb = lb ? lb[1] : "");
        if (A) {
            var mb = ib();
            if (null != mb && mb > parseFloat(kb)) {
                jb = String(mb);
                break a
            }
        }
        jb = kb
    }
    var nb = jb,
        cb = {},
        ob = function(a) {
            return db(a, function() {
                for (var b = 0, c = va(String(nb)).split("."), d = va(String(a)).split("."), e = Math.max(c.length, d.length), f = 0; 0 == b && f < e; f++) {
                    var h = c[f] || "",
                        t = d[f] || "";
                    do {
                        h = /(\d*)(\D*)(.*)/.exec(h) || ["", "", "", ""];
                        t = /(\d*)(\D*)(.*)/.exec(t) || ["", "", "", ""];
                        if (0 == h[0].length && 0 == t[0].length) break;
                        b = xa(0 == h[1].length ? 0 : parseInt(h[1], 10), 0 == t[1].length ? 0 : parseInt(t[1], 10)) || xa(0 == h[2].length, 0 == t[2].length) || xa(h[2], t[2]);
                        h = h[3];
                        t = t[3]
                    } while (0 == b)
                }
                return 0 <= b
            })
        },
        pb;
    var qb = l.document;
    pb = qb && A ? ib() || ("CSS1Compat" == qb.compatMode ? parseInt(nb, 10) : 5) : void 0;
    var rb = function(a, b, c, d, e) {
        this.reset(a, b, c, d, e)
    };
    rb.prototype.Qb = null;
    var sb = 0;
    rb.prototype.reset = function(a, b, c, d, e) {
        "number" == typeof e || sb++;
        this.Wc = d || qa();
        this.ma = a;
        this.Bc = b;
        this.xc = c;
        delete this.Qb
    };
    rb.prototype.kc = function(a) {
        this.ma = a
    };
    var tb = function(a) {
            this.Cc = a;
            this.Sa = this.Lb = this.ma = this.H = null
        },
        B = function(a, b) {
            this.name = a;
            this.value = b
        };
    B.prototype.toString = function() {
        return this.name
    };
    var ub = new B("SHOUT", 1200),
        vb = new B("SEVERE", 1E3),
        wb = new B("WARNING", 900),
        xb = new B("INFO", 800),
        yb = new B("CONFIG", 700),
        zb = new B("FINE", 500),
        C = new B("FINER", 400),
        Ab = [new B("OFF", Infinity), ub, vb, wb, xb, yb, zb, C, new B("FINEST", 300), new B("ALL", 0)],
        Bb = null,
        Cb = function(a) {
            if (!Bb) {
                Bb = {};
                for (var b = 0, c; c = Ab[b]; b++) Bb[c.value] = c, Bb[c.name] = c
            }
            if (a in Bb) return Bb[a];
            for (b = 0; b < Ab.length; ++b)
                if (c = Ab[b], c.value <= a) return c;
            return null
        };
    tb.prototype.getName = function() {
        return this.Cc
    };
    tb.prototype.getParent = function() {
        return this.H
    };
    tb.prototype.qc = function() {
        this.Lb || (this.Lb = {});
        return this.Lb
    };
    tb.prototype.kc = function(a) {
        this.ma = a
    };
    var Db = function(a) {
        if (a.ma) return a.ma;
        if (a.H) return Db(a.H);
        Aa("Root logger has no level set.");
        return null
    };
    tb.prototype.log = function(a, b, c) {
        if (a.value >= Db(this).value)
            for (r(b) && (b = b()), a = new rb(a, String(b), this.Cc), c && (a.Qb = c), c = "log:" + a.Bc, l.console && (l.console.timeStamp ? l.console.timeStamp(c) : l.console.markTimeline && l.console.markTimeline(c)), l.msWriteProfilerMark && l.msWriteProfilerMark(c), c = this; c;) {
                var d = c,
                    e = a;
                if (d.Sa)
                    for (var f = 0; b = d.Sa[f]; f++) b(e);
                c = c.getParent()
            }
    };
    tb.prototype.info = function(a, b) {
        this.log(xb, a, b)
    };
    var Eb = {},
        Fb = null,
        Gb = function() {
            Fb || (Fb = new tb(""), Eb[""] = Fb, Fb.kc(yb))
        },
        D = function(a) {
            Gb();
            var b;
            if (!(b = Eb[a])) {
                b = new tb(a);
                var c = a.lastIndexOf("."),
                    d = a.substr(c + 1),
                    c = D(a.substr(0, c));
                c.qc()[d] = b;
                b.H = c;
                Eb[a] = b
            }
            return b
        };
    var E = function(a, b, c) {
            a && a.log(b, c, void 0)
        },
        G = function(a, b) {
            a && a.log(vb, b, void 0)
        },
        Hb = function(a, b) {
            a && a.log(wb, b, void 0)
        },
        H = function(a, b) {
            a && a.info(b, void 0)
        },
        Ib = function(a, b) {
            a && a.log(zb, b, void 0)
        };
    var I = function() {
        this.Ma = this.Ma;
        this.pa = this.pa
    };
    I.prototype.Ma = !1;
    I.prototype.I = function() {
        this.Ma || (this.Ma = !0, this.C())
    };
    var Jb = function(a, b) {
        a.Ma ? m(void 0) ? b.call(void 0) : b() : (a.pa || (a.pa = []), a.pa.push(m(void 0) ? u(b, void 0) : b))
    };
    I.prototype.C = function() {
        if (this.pa)
            for (; this.pa.length;) this.pa.shift()()
    };
    var Kb = function(a) {
        a && "function" == typeof a.I && a.I()
    };
    var J = function(a, b) {
        this.type = a;
        this.currentTarget = this.target = b;
        this.defaultPrevented = this.ta = !1;
        this.Mc = !0
    };
    J.prototype.stopPropagation = function() {
        this.ta = !0
    };
    J.prototype.preventDefault = function() {
        this.defaultPrevented = !0;
        this.Mc = !1
    };
    var Lb = !A || 9 <= Number(pb),
        Mb = A && !ob("9");
    !hb || ob("528");
    gb && ob("1.9b") || A && ob("8") || eb && ob("9.5") || hb && ob("528");
    gb && !ob("8") || A && ob("9");
    var Nb = function(a, b) {
        J.call(this, a ? a.type : "");
        this.relatedTarget = this.currentTarget = this.target = null;
        this.button = this.screenY = this.screenX = this.clientY = this.clientX = this.offsetY = this.offsetX = 0;
        this.key = "";
        this.charCode = this.keyCode = 0;
        this.metaKey = this.shiftKey = this.altKey = this.ctrlKey = !1;
        this.Da = this.state = null;
        if (a) {
            var c = this.type = a.type,
                d = a.changedTouches ? a.changedTouches[0] : null;
            this.target = a.target || a.srcElement;
            this.currentTarget = b;
            if (b = a.relatedTarget) {
                if (gb) {
                    var e;
                    a: {
                        try {
                            bb(b.nodeName);
                            e = !0;
                            break a
                        } catch (f) {}
                        e = !1
                    }
                    e || (b = null)
                }
            } else "mouseover" == c ? b = a.fromElement : "mouseout" == c && (b = a.toElement);
            this.relatedTarget = b;
            null === d ? (this.offsetX = hb || void 0 !== a.offsetX ? a.offsetX : a.layerX, this.offsetY = hb || void 0 !== a.offsetY ? a.offsetY : a.layerY, this.clientX = void 0 !== a.clientX ? a.clientX : a.pageX, this.clientY = void 0 !== a.clientY ? a.clientY : a.pageY, this.screenX = a.screenX || 0, this.screenY = a.screenY || 0) : (this.clientX = void 0 !== d.clientX ? d.clientX : d.pageX, this.clientY = void 0 !== d.clientY ? d.clientY : d.pageY, this.screenX =
                d.screenX || 0, this.screenY = d.screenY || 0);
            this.button = a.button;
            this.keyCode = a.keyCode || 0;
            this.key = a.key || "";
            this.charCode = a.charCode || ("keypress" == c ? a.keyCode : 0);
            this.ctrlKey = a.ctrlKey;
            this.altKey = a.altKey;
            this.shiftKey = a.shiftKey;
            this.metaKey = a.metaKey;
            this.state = a.state;
            this.Da = a;
            a.defaultPrevented && this.preventDefault()
        }
    };
    w(Nb, J);
    Nb.prototype.stopPropagation = function() {
        Nb.cb.stopPropagation.call(this);
        this.Da.stopPropagation ? this.Da.stopPropagation() : this.Da.cancelBubble = !0
    };
    Nb.prototype.preventDefault = function() {
        Nb.cb.preventDefault.call(this);
        var a = this.Da;
        if (a.preventDefault) a.preventDefault();
        else if (a.returnValue = !1, Mb) try {
            if (a.ctrlKey || 112 <= a.keyCode && 123 >= a.keyCode) a.keyCode = -1
        } catch (b) {}
    };
    var Ob = "closure_listenable_" + (1E6 * Math.random() | 0),
        Pb = function(a) {
            return !(!a || !a[Ob])
        },
        Qb = 0;
    var Rb = function(a, b, c, d, e) {
            this.listener = a;
            this.Ab = null;
            this.src = b;
            this.type = c;
            this.capture = !!d;
            this.pb = e;
            this.key = ++Qb;
            this.Ka = this.kb = !1
        },
        Sb = function(a) {
            a.Ka = !0;
            a.listener = null;
            a.Ab = null;
            a.src = null;
            a.pb = null
        };
    var Tb = function(a) {
        this.src = a;
        this.w = {};
        this.eb = 0
    };
    Tb.prototype.add = function(a, b, c, d, e) {
        var f = a.toString();
        a = this.w[f];
        a || (a = this.w[f] = [], this.eb++);
        var h = Ub(a, b, d, e); - 1 < h ? (b = a[h], c || (b.kb = !1)) : (b = new Rb(b, this.src, f, !!d, e), b.kb = c, a.push(b));
        return b
    };
    Tb.prototype.remove = function(a, b, c, d) {
        a = a.toString();
        if (!(a in this.w)) return !1;
        var e = this.w[a];
        b = Ub(e, b, c, d);
        return -1 < b ? (Sb(e[b]), Ha(e, b), 0 == e.length && (delete this.w[a], this.eb--), !0) : !1
    };
    var Vb = function(a, b) {
        var c = b.type;
        c in a.w && Ia(a.w[c], b) && (Sb(b), 0 == a.w[c].length && (delete a.w[c], a.eb--))
    };
    Tb.prototype.ic = function(a) {
        a = a && a.toString();
        var b = 0,
            c;
        for (c in this.w)
            if (!a || c == a) {
                for (var d = this.w[c], e = 0; e < d.length; e++) ++b, Sb(d[e]);
                delete this.w[c];
                this.eb--
            }
        return b
    };
    Tb.prototype.Qa = function(a, b, c, d) {
        a = this.w[a.toString()];
        var e = -1;
        a && (e = Ub(a, b, c, d));
        return -1 < e ? a[e] : null
    };
    Tb.prototype.hasListener = function(a, b) {
        var c = m(a),
            d = c ? a.toString() : "",
            e = m(b);
        return Na(this.w, function(a) {
            for (var f = 0; f < a.length; ++f)
                if (!(c && a[f].type != d || e && a[f].capture != b)) return !0;
            return !1
        })
    };
    var Ub = function(a, b, c, d) {
        for (var e = 0; e < a.length; ++e) {
            var f = a[e];
            if (!f.Ka && f.listener == b && f.capture == !!c && f.pb == d) return e
        }
        return -1
    };
    var Wb = "closure_lm_" + (1E6 * Math.random() | 0),
        Xb = {},
        Yb = 0,
        K = function(a, b, c, d, e) {
            if ("array" == ka(b)) {
                for (var f = 0; f < b.length; f++) K(a, b[f], c, d, e);
                return null
            }
            c = Zb(c);
            if (Pb(a)) a = a.Ha(b, c, d, e);
            else {
                if (!b) throw Error("Invalid event type");
                var f = !!d,
                    h = $b(a);
                h || (a[Wb] = h = new Tb(a));
                c = h.add(b, c, !1, d, e);
                if (!c.Ab) {
                    d = ac();
                    c.Ab = d;
                    d.src = a;
                    d.listener = c;
                    if (a.addEventListener) a.addEventListener(b.toString(), d, f);
                    else if (a.attachEvent) a.attachEvent(bc(b.toString()), d);
                    else throw Error("addEventListener and attachEvent are unavailable.");
                    Yb++
                }
                a = c
            }
            return a
        },
        ac = function() {
            var a = cc,
                b = Lb ? function(c) {
                    return a.call(b.src, b.listener, c)
                } : function(c) {
                    c = a.call(b.src, b.listener, c);
                    if (!c) return c
                };
            return b
        },
        dc = function(a, b, c, d, e) {
            if ("array" == ka(b))
                for (var f = 0; f < b.length; f++) dc(a, b[f], c, d, e);
            else c = Zb(c), Pb(a) ? a.fb(b, c, d, e) : a && (a = $b(a)) && (b = a.Qa(b, c, !!d, e)) && ec(b)
        },
        ec = function(a) {
            if (!q(a) && a && !a.Ka) {
                var b = a.src;
                if (Pb(b)) Vb(b.P, a);
                else {
                    var c = a.type,
                        d = a.Ab;
                    b.removeEventListener ? b.removeEventListener(c, d, a.capture) : b.detachEvent && b.detachEvent(bc(c),
                        d);
                    Yb--;
                    (c = $b(b)) ? (Vb(c, a), 0 == c.eb && (c.src = null, b[Wb] = null)) : Sb(a)
                }
            }
        },
        bc = function(a) {
            return a in Xb ? Xb[a] : Xb[a] = "on" + a
        },
        gc = function(a, b, c, d) {
            var e = !0;
            if (a = $b(a))
                if (b = a.w[b.toString()])
                    for (b = b.concat(), a = 0; a < b.length; a++) {
                        var f = b[a];
                        f && f.capture == c && !f.Ka && (f = fc(f, d), e = e && !1 !== f)
                    }
                return e
        },
        fc = function(a, b) {
            var c = a.listener,
                d = a.pb || a.src;
            a.kb && ec(a);
            return c.call(d, b)
        },
        hc = function(a, b) {
            x(Pb(a), "Can not use goog.events.dispatchEvent with non-goog.events.Listenable instance.");
            return a.dispatchEvent(b)
        },
        cc = function(a, b) {
            if (a.Ka) return !0;
            if (!Lb) {
                if (!b) a: {
                    b = ["window", "event"];
                    for (var c = l, d; d = b.shift();)
                        if (null != c[d]) c = c[d];
                        else {
                            b = null;
                            break a
                        }
                    b = c
                }
                d = b;
                b = new Nb(d, this);
                c = !0;
                if (!(0 > d.keyCode || void 0 != d.returnValue)) {
                    a: {
                        var e = !1;
                        if (0 == d.keyCode) try {
                            d.keyCode = -1;
                            break a
                        } catch (h) {
                            e = !0
                        }
                        if (e || void 0 == d.returnValue) d.returnValue = !0
                    }
                    d = [];
                    for (e = b.currentTarget; e; e = e.parentNode) d.push(e);a = a.type;
                    for (e = d.length - 1; !b.ta && 0 <= e; e--) {
                        b.currentTarget = d[e];
                        var f = gc(d[e], a, !0, b),
                            c = c && f
                    }
                    for (e = 0; !b.ta && e < d.length; e++) b.currentTarget =
                        d[e],
                    f = gc(d[e], a, !1, b),
                    c = c && f
                }
                return c
            }
            return fc(a, new Nb(b, this))
        },
        $b = function(a) {
            a = a[Wb];
            return a instanceof Tb ? a : null
        },
        ic = "__closure_events_fn_" + (1E9 * Math.random() >>> 0),
        Zb = function(a) {
            x(a, "Listener can not be null.");
            if (r(a)) return a;
            x(a.handleEvent, "An object listener must have handleEvent method.");
            a[ic] || (a[ic] = function(b) {
                return a.handleEvent(b)
            });
            return a[ic]
        };
    var L = function() {
        I.call(this);
        this.P = new Tb(this);
        this.Zc = this;
        this.hc = null
    };
    w(L, I);
    L.prototype[Ob] = !0;
    g = L.prototype;
    g.addEventListener = function(a, b, c, d) {
        K(this, a, b, c, d)
    };
    g.removeEventListener = function(a, b, c, d) {
        dc(this, a, b, c, d)
    };
    g.dispatchEvent = function(a) {
        jc(this);
        var b, c = this.hc;
        if (c) {
            b = [];
            for (var d = 1; c; c = c.hc) b.push(c), x(1E3 > ++d, "infinite loop")
        }
        c = this.Zc;
        d = a.type || a;
        if (p(a)) a = new J(a, c);
        else if (a instanceof J) a.target = a.target || c;
        else {
            var e = a;
            a = new J(d, c);
            Sa(a, e)
        }
        var e = !0,
            f;
        if (b)
            for (var h = b.length - 1; !a.ta && 0 <= h; h--) f = a.currentTarget = b[h], e = kc(f, d, !0, a) && e;
        a.ta || (f = a.currentTarget = c, e = kc(f, d, !0, a) && e, a.ta || (e = kc(f, d, !1, a) && e));
        if (b)
            for (h = 0; !a.ta && h < b.length; h++) f = a.currentTarget = b[h], e = kc(f, d, !1, a) && e;
        return e
    };
    g.C = function() {
        L.cb.C.call(this);
        this.P && this.P.ic(void 0);
        this.hc = null
    };
    g.Ha = function(a, b, c, d) {
        jc(this);
        return this.P.add(String(a), b, !1, c, d)
    };
    g.fb = function(a, b, c, d) {
        return this.P.remove(String(a), b, c, d)
    };
    var kc = function(a, b, c, d) {
        b = a.P.w[String(b)];
        if (!b) return !0;
        b = b.concat();
        for (var e = !0, f = 0; f < b.length; ++f) {
            var h = b[f];
            if (h && !h.Ka && h.capture == c) {
                var t = h.listener,
                    y = h.pb || h.src;
                h.kb && Vb(a.P, h);
                e = !1 !== t.call(y, d) && e
            }
        }
        return e && 0 != d.Mc
    };
    L.prototype.Qa = function(a, b, c, d) {
        return this.P.Qa(String(a), b, c, d)
    };
    L.prototype.hasListener = function(a, b) {
        return this.P.hasListener(m(a) ? String(a) : void 0, b)
    };
    var jc = function(a) {
        x(a.P, "Event target is not initialized. Did you call the superclass (goog.events.EventTarget) constructor?")
    };
    var M = function(a, b) {
        this.xa = b;
        this.Vb = !0;
        this.Ca = a;
        this.onClose = this.onMessage = null;
        this.h = new L;
        this.Ca.addEventListener(this.xa, this.$a.bind(this))
    };
    v("cast.receiver.CastChannel", M);
    M.prototype.ba = function() {
        return "CastChannel[" + this.xa + " " + this.Ca.ja() + "]"
    };
    M.prototype.ja = function() {
        return this.Ca.ja()
    };
    M.prototype.getNamespace = M.prototype.ja;
    M.prototype.Od = function() {
        return this.xa
    };
    M.prototype.getSenderId = M.prototype.Od;
    M.prototype.$a = function(a) {
        E(lc, C, "Dispatching CastChannel message [" + this.Ca.ja() + ", " + this.xa + "]: " + a.data);
        a = new mc("message", a.data);
        if (this.onMessage) this.onMessage(a);
        this.c(a)
    };
    M.prototype.close = function() {
        if (this.Vb) {
            this.Vb = !1;
            H(lc, "Closing CastChannel [" + this.Ca.ja() + ", " + this.xa + "]");
            var a = new mc("close", this.xa);
            if (this.onClose) this.onClose(a);
            this.c(a);
            this.h.I();
            E(lc, C, "Disposed " + this.ba())
        }
    };
    M.prototype.send = function(a) {
        if (!this.Vb) throw Error("Invalid state, socket not open");
        this.Ca.send(this.xa, a)
    };
    M.prototype.send = M.prototype.send;
    M.prototype.addEventListener = function(a, b) {
        K(this.h, a, b)
    };
    M.prototype.addEventListener = M.prototype.addEventListener;
    M.prototype.removeEventListener = function(a, b) {
        dc(this.h, a, b)
    };
    M.prototype.removeEventListener = M.prototype.removeEventListener;
    M.prototype.c = function(a) {
        a.target = this;
        return hc(this.h, a)
    };
    M.prototype.dispatchEvent = function(a) {
        return this.c(a)
    };
    M.prototype.dispatchEvent = M.prototype.dispatchEvent;
    M.EventType = {
        MESSAGE: "message",
        CLOSE: "close"
    };
    var mc = function(a, b) {
        J.call(this, a);
        this.message = b
    };
    k(mc, J);
    M.Event = mc;
    var lc = D("cast.receiver.CastChannel");
    var nc = function(a, b, c) {
        this.Vd = c;
        this.cd = a;
        this.ge = b;
        this.yb = 0;
        this.qb = null
    };
    nc.prototype.get = function() {
        var a;
        0 < this.yb ? (this.yb--, a = this.qb, this.qb = a.next, a.next = null) : a = this.cd();
        return a
    };
    nc.prototype.put = function(a) {
        this.ge(a);
        this.yb < this.Vd && (this.yb++, a.next = this.qb, this.qb = a)
    };
    var oc = function(a) {
            l.setTimeout(function() {
                throw a;
            }, 0)
        },
        pc, qc = function() {
            var a = l.MessageChannel;
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
                    a = u(function(a) {
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
                    if (m(c.next)) {
                        c = c.next;
                        var a = c.nc;
                        c.nc = null;
                        a()
                    }
                };
                return function(a) {
                    d.next = {
                        nc: a
                    };
                    d = d.next;
                    b.port2.postMessage(0)
                }
            }
            return "undefined" !== typeof document && "onreadystatechange" in document.createElement("SCRIPT") ?
                function(a) {
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
                    l.setTimeout(a, 0)
                }
        };
    var rc = function() {
            this.Hb = this.Aa = null
        },
        tc = new nc(function() {
            return new sc
        }, function(a) {
            a.reset()
        }, 100);
    rc.prototype.add = function(a, b) {
        var c = tc.get();
        c.set(a, b);
        this.Hb ? this.Hb.next = c : (x(!this.Aa), this.Aa = c);
        this.Hb = c
    };
    rc.prototype.remove = function() {
        var a = null;
        this.Aa && (a = this.Aa, this.Aa = this.Aa.next, this.Aa || (this.Hb = null), a.next = null);
        return a
    };
    var sc = function() {
        this.next = this.scope = this.Sb = null
    };
    sc.prototype.set = function(a, b) {
        this.Sb = a;
        this.scope = b;
        this.next = null
    };
    sc.prototype.reset = function() {
        this.next = this.scope = this.Sb = null
    };
    var yc = function(a, b) {
            uc || vc();
            wc || (uc(), wc = !0);
            xc.add(a, b)
        },
        uc, vc = function() {
            if (-1 != String(l.Promise).indexOf("[native code]")) {
                var a = l.Promise.resolve(void 0);
                uc = function() {
                    a.then(zc)
                }
            } else uc = function() {
                var a = zc;
                !r(l.setImmediate) || l.Window && l.Window.prototype && !z("Edge") && l.Window.prototype.setImmediate == l.setImmediate ? (pc || (pc = qc()), pc(a)) : l.setImmediate(a)
            }
        },
        wc = !1,
        xc = new rc,
        zc = function() {
            for (var a; a = xc.remove();) {
                try {
                    a.Sb.call(a.scope)
                } catch (b) {
                    oc(b)
                }
                tc.put(a)
            }
            wc = !1
        };
    var N = function(a, b) {
            this.D = 0;
            this.Kc = void 0;
            this.Ba = this.V = this.H = null;
            this.ob = this.Rb = !1;
            if (a != n) try {
                var c = this;
                a.call(b, function(a) {
                    Ac(c, 2, a)
                }, function(a) {
                    if (!(a instanceof Bc)) try {
                        if (a instanceof Error) throw a;
                        throw Error("Promise rejected.");
                    } catch (e) {}
                    Ac(c, 3, a)
                })
            } catch (d) {
                Ac(this, 3, d)
            }
        },
        Cc = function() {
            this.next = this.context = this.Ia = this.qa = this.ha = null;
            this.jb = !1
        };
    Cc.prototype.reset = function() {
        this.context = this.Ia = this.qa = this.ha = null;
        this.jb = !1
    };
    var Dc = new nc(function() {
            return new Cc
        }, function(a) {
            a.reset()
        }, 100),
        Ec = function(a, b, c) {
            var d = Dc.get();
            d.qa = a;
            d.Ia = b;
            d.context = c;
            return d
        },
        Fc = function(a) {
            if (a instanceof N) return a;
            var b = new N(n);
            Ac(b, 2, a);
            return b
        },
        Hc = function() {
            var a, b, c = new N(function(c, e) {
                a = c;
                b = e
            });
            return new Gc(c, a, b)
        };
    N.prototype.then = function(a, b, c) {
        null != a && Ca(a, "opt_onFulfilled should be a function.");
        null != b && Ca(b, "opt_onRejected should be a function. Did you pass opt_context as the second argument instead of the third?");
        return Ic(this, r(a) ? a : null, r(b) ? b : null, c)
    };
    N.prototype.then = N.prototype.then;
    N.prototype.$goog_Thenable = !0;
    N.prototype.cancel = function(a) {
        0 == this.D && yc(function() {
            var b = new Bc(a);
            Jc(this, b)
        }, this)
    };
    var Jc = function(a, b) {
            if (0 == a.D)
                if (a.H) {
                    var c = a.H;
                    if (c.V) {
                        for (var d = 0, e = null, f = null, h = c.V; h && (h.jb || (d++, h.ha == a && (e = h), !(e && 1 < d))); h = h.next) e || (f = h);
                        e && (0 == c.D && 1 == d ? Jc(c, b) : (f ? (d = f, x(c.V), x(null != d), d.next == c.Ba && (c.Ba = d), d.next = d.next.next) : Kc(c), Lc(c, e, 3, b)))
                    }
                    a.H = null
                } else Ac(a, 3, b)
        },
        Nc = function(a, b) {
            a.V || 2 != a.D && 3 != a.D || Mc(a);
            x(null != b.qa);
            a.Ba ? a.Ba.next = b : a.V = b;
            a.Ba = b
        },
        Ic = function(a, b, c, d) {
            var e = Ec(null, null, null);
            e.ha = new N(function(a, h) {
                e.qa = b ? function(c) {
                        try {
                            var e = b.call(d, c);
                            a(e)
                        } catch (F) {
                            h(F)
                        }
                    } :
                    a;
                e.Ia = c ? function(b) {
                    try {
                        var e = c.call(d, b);
                        !m(e) && b instanceof Bc ? h(b) : a(e)
                    } catch (F) {
                        h(F)
                    }
                } : h
            });
            e.ha.H = a;
            Nc(a, e);
            return e.ha
        };
    N.prototype.ve = function(a) {
        x(1 == this.D);
        this.D = 0;
        Ac(this, 2, a)
    };
    N.prototype.we = function(a) {
        x(1 == this.D);
        this.D = 0;
        Ac(this, 3, a)
    };
    var Ac = function(a, b, c) {
            if (0 == a.D) {
                a === c && (b = 3, c = new TypeError("Promise cannot resolve to itself"));
                a.D = 1;
                var d;
                a: {
                    var e = c,
                        f = a.ve,
                        h = a.we;
                    if (e instanceof N) null != f && Ca(f, "opt_onFulfilled should be a function."), null != h && Ca(h, "opt_onRejected should be a function. Did you pass opt_context as the second argument instead of the third?"), Nc(e, Ec(f || n, h || null, a)), d = !0;
                    else {
                        var t;
                        if (e) try {
                            t = !!e.$goog_Thenable
                        } catch (F) {
                            t = !1
                        } else t = !1;
                        if (t) e.then(f, h, a), d = !0;
                        else {
                            if (ma(e)) try {
                                var y = e.then;
                                if (r(y)) {
                                    Oc(e, y, f,
                                        h, a);
                                    d = !0;
                                    break a
                                }
                            } catch (F) {
                                h.call(a, F);
                                d = !0;
                                break a
                            }
                            d = !1
                        }
                    }
                }
                d || (a.Kc = c, a.D = b, a.H = null, Mc(a), 3 != b || c instanceof Bc || Pc(a, c))
            }
        },
        Oc = function(a, b, c, d, e) {
            var f = !1,
                h = function(a) {
                    f || (f = !0, c.call(e, a))
                },
                t = function(a) {
                    f || (f = !0, d.call(e, a))
                };
            try {
                b.call(a, h, t)
            } catch (y) {
                t(y)
            }
        },
        Mc = function(a) {
            a.Rb || (a.Rb = !0, yc(a.Cd, a))
        },
        Kc = function(a) {
            var b = null;
            a.V && (b = a.V, a.V = b.next, b.next = null);
            a.V || (a.Ba = null);
            null != b && x(null != b.qa);
            return b
        };
    N.prototype.Cd = function() {
        for (var a; a = Kc(this);) Lc(this, a, this.D, this.Kc);
        this.Rb = !1
    };
    var Lc = function(a, b, c, d) {
            if (3 == c && b.Ia && !b.jb)
                for (; a && a.ob; a = a.H) a.ob = !1;
            if (b.ha) b.ha.H = null, Qc(b, c, d);
            else try {
                b.jb ? b.qa.call(b.context) : Qc(b, c, d)
            } catch (e) {
                Rc.call(null, e)
            }
            Dc.put(b)
        },
        Qc = function(a, b, c) {
            2 == b ? a.qa.call(a.context, c) : a.Ia && a.Ia.call(a.context, c)
        },
        Pc = function(a, b) {
            a.ob = !0;
            yc(function() {
                a.ob && Rc.call(null, b)
            })
        },
        Rc = oc,
        Bc = function(a) {
            sa.call(this, a)
        };
    w(Bc, sa);
    Bc.prototype.name = "cancel";
    var Gc = function(a, b, c) {
        this.ce = a;
        this.resolve = b;
        this.reject = c
    };
    var Sc = function(a, b, c) {
        if (r(a)) c && (a = u(a, c));
        else if (a && "function" == typeof a.handleEvent) a = u(a.handleEvent, a);
        else throw Error("Invalid listener argument");
        return 2147483647 < Number(b) ? -1 : l.setTimeout(a, b || 0)
    };
    var Uc = function(a, b) {
        L.call(this);
        this.ad = m(a) ? a : !0;
        this.Tb = b || Tc;
        this.xb = this.Tb(this.ab)
    };
    w(Uc, L);
    g = Uc.prototype;
    g.F = null;
    g.Z = null;
    g.Ja = void 0;
    g.Mb = !1;
    g.ab = 0;
    g.ua = null;
    var Vc = Uc.prototype,
        Wc = D("goog.net.WebSocket");
    Vc.G = Wc;
    var Tc = function(a) {
        return Math.min(1E3 * Math.pow(2, a), 6E4)
    };
    g = Uc.prototype;
    g.send = function(a) {
        x(this.Ua(), "Cannot send without an open socket");
        this.F.send(a)
    };
    g.Ua = function() {
        return !!this.F && 1 == this.F.readyState
    };
    g.Yd = function() {
        H(this.G, "WebSocket opened on " + this.Z);
        this.dispatchEvent("d");
        this.ab = 0;
        this.xb = this.Tb(this.ab)
    };
    g.Xd = function(a) {
        H(this.G, "The WebSocket on " + this.Z + " closed.");
        this.dispatchEvent("a");
        this.F = null;
        this.Mb ? (H(this.G, "The WebSocket closed normally."), this.Z = null, this.Ja = void 0) : (G(this.G, "The WebSocket disconnected unexpectedly: " + a.data), this.ad && (H(this.G, "Seconds until next reconnect attempt: " + Math.floor(this.xb / 1E3)), this.ua = Sc(u(this.open, this, this.Z, this.Ja), this.xb, this), this.ab++, this.xb = this.Tb(this.ab)));
        this.Mb = !1
    };
    g.M = function(a) {
        this.dispatchEvent(new Xc(a.data))
    };
    g.ac = function(a) {
        a = a.data;
        G(this.G, "An error occurred: " + a);
        this.dispatchEvent(new Yc(a))
    };
    g.C = function() {
        Uc.cb.C.call(this);
        this.close()
    };
    var Xc = function(a) {
        J.call(this, "c");
        this.message = a
    };
    w(Xc, J);
    var Yc = function(a) {
        J.call(this, "b");
        this.data = a
    };
    w(Yc, J);
    v("cast.receiver.platform.PlatformValueKey", {
        Ce: "port-for-web-server",
        ze: "log-level-cast-receiver",
        Ae: "max-video-resolution-vpx",
        ye: "device-model",
        xe: "cast-receiver-version",
        De: "system-version",
        Ee: "volume-controllable"
    });
    var Zc = {
        "port-for-web-server": "8008"
    };
    v("cast.receiver.platform.canDisplayType", function(a) {
        return cast.__platform__.canDisplayType(a)
    });
    cast.__platform__ && cast.__platform__.canDisplayType || delete window.cast.receiver.platform.canDisplayType;
    v("cast.receiver.platform.VideoResolution", function() {});
    var $c = function(a) {
        if (cast.__platform__ && cast.__platform__.queryPlatformValue) return cast.__platform__.queryPlatformValue(a);
        if (a in Zc) return Zc[a]
    };
    v("cast.receiver.platform.getValue", $c);
    v("cast.receiver.platform.getHdcpVersion", function() {
        return cast.__platform__ && cast.__platform__.display && cast.__platform__.display.getHdcpVersion ? cast.__platform__.display.getHdcpVersion() : Promise.reject(Error("getHdcpVersion is not available"))
    });
    var ad = function() {
        this.h = new L;
        this.ra = !1
    };
    g = ad.prototype;
    g.Pa = function() {
        return "PlatformChannel"
    };
    g.open = function() {
        this.ra ? G(bd, this.Pa() + " Already open") : cast.__platform__.channel.open(u(this.ec, this), u(this.M, this))
    };
    g.close = function() {
        this.ra ? cast.__platform__.channel.close(u(this.Zb, this)) : G(bd, this.Pa() + " Cannot close unopened channel")
    };
    g.Ua = function() {
        return this.ra
    };
    g.send = function(a) {
        x(this.ra, "Cannot send until channel is openned");
        cast.__platform__.channel.send(a)
    };
    g.ec = function(a) {
        this.ra = a;
        this.c(a ? "d" : "b")
    };
    g.Zb = function() {
        this.ra && (this.ra = !1, this.c("a"))
    };
    g.M = function(a) {
        this.c(new Xc(a))
    };
    g.addEventListener = function(a, b) {
        this.h.Ha(a, b)
    };
    g.removeEventListener = function(a, b) {
        this.h.fb(a, b)
    };
    g.c = function(a) {
        a = p(a) ? new J(a) : a;
        a.target = this;
        this.h.dispatchEvent(a)
    };
    var bd = D("cast.receiver.platform.WebSocket");
    v("cast.receiver.system.NAMESPACE_PREFIX", "urn:x-cast:");
    v("cast.receiver.system.ApplicationData", function() {
        this.name = this.id = "";
        this.sessionId = 0;
        this.namespaces = [];
        this.launchingSenderId = ""
    });
    v("cast.receiver.system.SystemVolumeData", function() {
        this.level = 1;
        this.muted = !1
    });
    v("cast.receiver.system.Sender", function() {
        this.userAgent = this.id = ""
    });
    v("cast.receiver.system.VisibilityState", {
        VISIBLE: "visible",
        NOT_VISIBLE: "notvisible",
        UNKNOWN: "unknown"
    });
    v("cast.receiver.system.StandbyState", {
        STANDBY: "standby",
        NOT_STANDBY: "notstandby",
        UNKNOWN: "unknown"
    });
    v("cast.receiver.system.SystemState", {
        NOT_STARTED: "notstarted",
        STARTING_IN_BACKGROUND: "startinginbackground",
        STARTING: "starting",
        READY: "ready",
        STOPPING_IN_BACKGROUND: "stoppinginbackground",
        STOPPING: "stopping"
    });
    var ed = function() {
        this.O = null;
        cast.__platform__ && cast.__platform__.channel ? (H(cd, "Opening platform websocket"), dd(this, new ad)) : (H(cd, "Opening net websocket"), dd(this, new Uc(!0)));
        this.h = new L
    };
    v("cast.receiver.IpcChannel", ed);
    ed.prototype.Pa = function() {
        return "IpcChannel"
    };
    var dd = function(a, b) {
            a.O && a.O.I();
            a.O = b;
            a.O.addEventListener("d", a.ec.bind(a));
            a.O.addEventListener("a", a.Zb.bind(a));
            a.O.addEventListener("b", a.ac.bind(a));
            a.O.addEventListener("c", a.M.bind(a))
        },
        gd = function(a, b) {
            Ib(cd, a.Pa() + " " + b);
            a.c(new fd("urn:x-cast:com.google.cast.system", "SystemSender", JSON.stringify({
                type: b
            })))
        };
    g = ed.prototype;
    g.ec = function() {
        gd(this, "opened")
    };
    g.Zb = function() {
        gd(this, "closed")
    };
    g.ac = function() {
        gd(this, "error")
    };
    g.M = function(a) {
        Ib(cd, "Received message: " + a.message);
        var b = (a = JSON.parse(a.message)) && a.namespace;
        a && b && a.senderId && a.data ? this.c(new fd(b, a.senderId, a.data)) : G(cd, this.Pa() + " Message received is invalid")
    };
    g.open = function() {
        H(cd, "Opening message bus websocket");
        this.O.open("ws://localhost:" + $c("port-for-web-server") + "/v2/ipc")
    };
    g.close = function() {
        H(cd, "Closing message bus websocket");
        this.O.close()
    };
    g.Ua = function() {
        return this.O.Ua()
    };
    g.send = function(a, b, c) {
        a = JSON.stringify({
            namespace: a,
            senderId: b,
            data: c
        });
        Ib(cd, "IPC message sent: " + a);
        this.O.send(a)
    };
    g.addEventListener = function(a, b) {
        this.h.Ha(a, b)
    };
    g.removeEventListener = function(a, b) {
        this.h.fb(a, b)
    };
    g.c = function(a) {
        a.target = this;
        this.h.dispatchEvent(a)
    };
    var cd = D("cast.receiver.IpcChannel"),
        fd = function(a, b, c) {
            J.call(this, a);
            this.senderId = b;
            this.data = c
        };
    k(fd, J);
    var O = function(a, b, c, d) {
        I.call(this);
        this.L = a;
        this.W = b;
        this.Wb = !1;
        this.oa = d || "STRING";
        this.h = new L;
        this.fc = this.onMessage = null;
        this.serializeMessage = this.Bd;
        this.deserializeMessage = this.gd;
        this.A = {};
        a = ia(c);
        for (b = a.next(); !b.done; b = a.next()) this.A[b.value] = null;
        this.Ac = this.$a.bind(this);
        this.W.addEventListener(this.L, this.Ac)
    };
    k(O, I);
    v("cast.receiver.CastMessageBus", O);
    O.prototype.ba = function() {
        return "CastMessageBus[" + this.L + "]"
    };
    O.prototype.ja = function() {
        return this.L
    };
    O.prototype.getNamespace = O.prototype.ja;
    O.prototype.Ld = function() {
        return this.oa
    };
    O.prototype.getMessageType = O.prototype.Ld;
    O.prototype.$a = function(a) {
        E(hd, C, "Dispatching CastMessageBus message");
        var b = "STRING" == this.oa ? a.data : this.deserializeMessage(a.data);
        this.c(new id(a.senderId, a.senderId, b));
        a = new id("message", a.senderId, b);
        if (this.onMessage) this.onMessage(a);
        this.c(a)
    };
    O.prototype.send = function(a, b) {
        this.Wb || "urn:x-cast:com.google.cast.system" == this.L || Hb(hd, "Application should not send requests before the system is ready (they will be ignored)");
        if (!this.fc || !this.fc(a, this.L, b))
            if ("STRING" == this.oa) {
                if (!p(b)) throw Error("Wrong argument, CastMessageBus type is STRING");
                this.W.send(this.L, a, b)
            } else this.W.send(this.L, a, this.serializeMessage(b))
    };
    O.prototype.send = O.prototype.send;
    O.prototype.mc = function(a) {
        this.send("*:*", a)
    };
    O.prototype.broadcast = O.prototype.mc;
    O.prototype.Fd = function(a) {
        if (Pa(this.A, a)) return this.A[a] || (this.A[a] = new M(this, a)), this.A[a];
        throw Error("Requested a socket for a disconnected sender: " + a);
    };
    O.prototype.getCastChannel = O.prototype.Fd;
    O.prototype.Bd = function(a) {
        if ("JSON" != this.oa) throw Error("Unexpected message type for JSON serialization");
        return JSON.stringify(a)
    };
    O.prototype.gd = function(a) {
        if ("JSON" != this.oa) throw Error("Unexpected message type for JSON serialization");
        return JSON.parse(a)
    };
    O.prototype.C = function() {
        I.prototype.C.call(this);
        this.W.removeEventListener(this.L, this.Ac);
        this.h.I();
        for (var a in this.A) this.A[a] && this.A[a].close();
        this.A = {};
        E(hd, C, "Disposed " + this.ba())
    };
    O.prototype.addEventListener = function(a, b) {
        K(this.h, a, b)
    };
    O.prototype.addEventListener = O.prototype.addEventListener;
    O.prototype.removeEventListener = function(a, b) {
        dc(this.h, a, b)
    };
    O.prototype.removeEventListener = O.prototype.removeEventListener;
    O.prototype.c = function(a) {
        a.target = this;
        return hc(this.h, a)
    };
    O.prototype.dispatchEvent = function(a) {
        return this.c(a)
    };
    O.prototype.dispatchEvent = O.prototype.dispatchEvent;
    O.MessageType = {
        STRING: "STRING",
        JSON: "JSON",
        CUSTOM: "CUSTOM"
    };
    O.EventType = {
        MESSAGE: "message"
    };
    var hd = D("cast.receiver.CastMessageBus"),
        id = function(a, b, c) {
            J.call(this, a);
            this.senderId = b;
            this.data = c
        };
    k(id, J);
    O.Event = id;
    var jd = function() {
            this.Ic = qa()
        },
        kd = new jd;
    jd.prototype.set = function(a) {
        this.Ic = a
    };
    jd.prototype.reset = function() {
        this.set(qa())
    };
    jd.prototype.get = function() {
        return this.Ic
    };
    var ld = function(a) {
        this.be = a || "";
        this.te = kd
    };
    g = ld.prototype;
    g.lc = !0;
    g.Tc = !0;
    g.re = !0;
    g.qe = !0;
    g.Uc = !1;
    g.se = !1;
    var md = function(a) {
            return 10 > a ? "0" + a : String(a)
        },
        nd = function(a, b) {
            a = (a.Wc - b) / 1E3;
            b = a.toFixed(3);
            var c = 0;
            if (1 > a) c = 2;
            else
                for (; 100 > a;) c++, a *= 10;
            for (; 0 < c--;) b = " " + b;
            return b
        },
        od = function(a) {
            ld.call(this, a)
        };
    w(od, ld);
    var pd = function() {
        this.de = u(this.$c, this);
        this.nb = new od;
        this.nb.Tc = !1;
        this.nb.Uc = !1;
        this.wc = this.nb.lc = !1;
        this.Dd = {}
    };
    pd.prototype.$c = function(a) {
        if (!this.Dd[a.xc]) {
            var b;
            b = this.nb;
            var c = [];
            c.push(b.be, " ");
            if (b.Tc) {
                var d = new Date(a.Wc);
                c.push("[", md(d.getFullYear() - 2E3) + md(d.getMonth() + 1) + md(d.getDate()) + " " + md(d.getHours()) + ":" + md(d.getMinutes()) + ":" + md(d.getSeconds()) + "." + md(Math.floor(d.getMilliseconds() / 10)), "] ")
            }
            b.re && c.push("[", nd(a, b.te.get()), "s] ");
            b.qe && c.push("[", a.xc, "] ");
            b.se && c.push("[", a.ma.name, "] ");
            c.push(a.Bc);
            b.Uc && (d = a.Qb) && c.push("\n", d instanceof Error ? d.message : d.toString());
            b.lc && c.push("\n");
            b = c.join("");
            if (c = qd) switch (a.ma) {
                case ub:
                    rd(c, "info", b);
                    break;
                case vb:
                    rd(c, "error", b);
                    break;
                case wb:
                    rd(c, "warn", b);
                    break;
                default:
                    rd(c, "debug", b)
            }
        }
    };
    var sd = null,
        qd = l.console,
        rd = function(a, b, c) {
            //if (a[b]) a[b](c);
            
        };
    v("cast.receiver.media.MEDIA_NAMESPACE", "urn:x-cast:com.google.cast.media");
    v("cast.receiver.media.StreamType", {
        BUFFERED: "BUFFERED",
        LIVE: "LIVE",
        NONE: "NONE"
    });
    v("cast.receiver.media.HdrType", {
        SDR: "sdr",
        HDR: "hdr",
        DV: "dv"
    });
    v("cast.receiver.media.ErrorType", {
        INVALID_PLAYER_STATE: "INVALID_PLAYER_STATE",
        LOAD_FAILED: "LOAD_FAILED",
        LOAD_CANCELLED: "LOAD_CANCELLED",
        INVALID_REQUEST: "INVALID_REQUEST"
    });
    v("cast.receiver.media.ErrorReason", {
        INVALID_COMMAND: "INVALID_COMMAND",
        INVALID_PARAMS: "INVALID_PARAMS",
        INVALID_MEDIA_SESSION_ID: "INVALID_MEDIA_SESSION_ID",
        SKIP_LIMIT_REACHED: "SKIP_LIMIT_REACHED",
        NOT_SUPPORTED: "NOT_SUPPORTED",
        LANGUAGE_NOT_SUPPORTED: "LANGUAGE_NOT_SUPPORTED",
        END_OF_QUEUE: "END_OF_QUEUE",
        DUPLICATE_REQUEST_ID: "DUPLICATE_REQUEST_ID"
    });
    v("cast.receiver.media.IdleReason", {
        CANCELLED: "CANCELLED",
        INTERRUPTED: "INTERRUPTED",
        FINISHED: "FINISHED",
        ERROR: "ERROR"
    });
    v("cast.receiver.media.SeekResumeState", {
        PLAYBACK_START: "PLAYBACK_START",
        PLAYBACK_PAUSE: "PLAYBACK_PAUSE"
    });
    v("cast.receiver.media.PlayerState", {
        IDLE: "IDLE",
        PLAYING: "PLAYING",
        PAUSED: "PAUSED",
        BUFFERING: "BUFFERING"
    });
    v("cast.receiver.media.ExtendedPlayerState", {
        LOADING: "LOADING"
    });
    var td = function() {
        this.contentId = "";
        this.streamType = "NONE";
        this.contentType = "";
        this.breakClips = this.breaks = this.customData = this.textTrackStyle = this.tracks = this.duration = this.metadata = void 0
    };
    v("cast.receiver.media.MediaInformation", td);
    var ud = function() {
        this.muted = this.level = void 0
    };
    v("cast.receiver.media.Volume", ud);
    var vd = function(a, b, c) {
        this.width = a;
        this.height = b;
        this.hdrType = c
    };
    v("cast.receiver.media.VideoInformation", vd);
    v("cast.receiver.media.MediaStatus", function() {
        this.mediaSessionId = 0;
        this.videoInfo = this.media = void 0;
        this.playbackRate = 1;
        this.playerState = "IDLE";
        this.idleReason = void 0;
        this.supportedMediaCommands = this.currentTime = 0;
        this.volume = {
            level: 0,
            muted: !1
        };
        this.activeTrackIds = null;
        this.extendedStatus = this.breakStatus = this.repeatMode = this.items = this.customData = this.preloadedItemId = this.loadingItemId = this.currentItemId = void 0
    });
    var wd = function(a, b) {
        this.playerState = a;
        this.media = b
    };
    v("cast.receiver.media.ExtendedMediaStatus", wd);
    v("cast.receiver.media.Command", {
        PAUSE: 1,
        SEEK: 2,
        STREAM_VOLUME: 4,
        STREAM_MUTE: 8,
        ALL_BASIC_MEDIA: 15,
        QUEUE_NEXT: 64,
        QUEUE_PREV: 128,
        QUEUE_SHUFFLE: 256
    });
    v("cast.receiver.media.TrackType", {
        TEXT: "TEXT",
        AUDIO: "AUDIO",
        VIDEO: "VIDEO"
    });
    v("cast.receiver.media.TextTrackType", {
        SUBTITLES: "SUBTITLES",
        CAPTIONS: "CAPTIONS",
        DESCRIPTIONS: "DESCRIPTIONS",
        CHAPTERS: "CHAPTERS",
        METADATA: "METADATA"
    });
    v("cast.receiver.media.TextTrackEdgeType", {
        NONE: "NONE",
        OUTLINE: "OUTLINE",
        DROP_SHADOW: "DROP_SHADOW",
        RAISED: "RAISED",
        DEPRESSED: "DEPRESSED"
    });
    v("cast.receiver.media.TextTrackWindowType", {
        NONE: "NONE",
        NORMAL: "NORMAL",
        ROUNDED_CORNERS: "ROUNDED_CORNERS"
    });
    v("cast.receiver.media.TextTrackFontGenericFamily", {
        SANS_SERIF: "SANS_SERIF",
        MONOSPACED_SANS_SERIF: "MONOSPACED_SANS_SERIF",
        SERIF: "SERIF",
        MONOSPACED_SERIF: "MONOSPACED_SERIF",
        CASUAL: "CASUAL",
        CURSIVE: "CURSIVE",
        SMALL_CAPITALS: "SMALL_CAPITALS"
    });
    v("cast.receiver.media.TextTrackFontStyle", {
        NORMAL: "NORMAL",
        BOLD: "BOLD",
        BOLD_ITALIC: "BOLD_ITALIC",
        ITALIC: "ITALIC"
    });
    v("cast.receiver.media.Track", function(a, b) {
        this.trackId = a;
        this.trackContentType = this.trackContentId = void 0;
        this.type = b;
        this.customData = this.subtype = this.language = this.name = void 0
    });
    v("cast.receiver.media.TextTrackStyle", function() {
        this.customData = this.fontStyle = this.fontGenericFamily = this.fontFamily = this.windowRoundedCornerRadius = this.windowColor = this.windowType = this.edgeColor = this.edgeType = this.backgroundColor = this.foregroundColor = this.fontScale = void 0
    });
    v("cast.receiver.media.TracksInfo", function() {
        this.textTrackStyle = this.language = this.activeTrackIds = this.tracks = void 0
    });
    var xd = {
        REPEAT_OFF: "REPEAT_OFF",
        REPEAT_ALL: "REPEAT_ALL",
        REPEAT_SINGLE: "REPEAT_SINGLE",
        REPEAT_ALL_AND_SHUFFLE: "REPEAT_ALL_AND_SHUFFLE"
    };
    v("cast.receiver.media.RepeatMode", xd);
    v("cast.receiver.media.repeatMode", xd);
    var yd = function(a) {
        return "REPEAT_OFF" == a || "REPEAT_ALL" == a || "REPEAT_SINGLE" == a || "REPEAT_ALL_AND_SHUFFLE" == a
    };
    v("cast.receiver.media.GetStatusOptions", {
        NO_METADATA: 1,
        NO_QUEUE_ITEMS: 2
    });
    v("cast.receiver.media.Break", function(a, b, c) {
        this.id = a;
        this.breakClipIds = b;
        this.position = c;
        this.duration = void 0;
        this.isWatched = !1
    });
    v("cast.receiver.media.BreakClip", function(a) {
        this.id = a;
        this.customData = this.clickThroughUrl = this.mimeType = this.contentUrl = this.duration = this.title = void 0
    });
    v("cast.receiver.media.BreakStatus", function(a, b) {
        this.currentBreakTime = a;
        this.currentBreakClipTime = b;
        this.whenSkippable = this.breakClipId = this.breakId = void 0
    });
    var zd = function() {
        this.aa = P.ca();
        this.yc = 0;
        this.ib = null;
        this.bb = new Ya;
        this.he = this.ie.bind(this);
        this.S = this.aa.Oa("urn:x-cast:com.google.cast.inject", "JSON");
        this.S.onMessage = this.M.bind(this);
        for (var a = ia(["urn:x-cast:com.google.cast.cac", "urn:x-cast:com.google.cast.media"]), b = a.next(); !b.done; b = a.next())
            if (b = this.aa.u[b.value] || null) b.fc = this.he
    };
    zd.prototype.M = function(a) {
        var b = a.data,
            c = b.requestId;
        a = a.senderId;
        if ("WRAPPED" != b.type) this.N("Unsupported message type " + b.type, a, c);
        else {
            var b = JSON.parse(b.data),
                d = b.namespace,
                e = this.aa.u[d] || null;
            if (e) {
                var f = !1;
                if ("urn:x-cast:com.google.cast.cac" == d) b.data.requestId = c, f = !0;
                else if ("urn:x-cast:com.google.cast.media" == d) {
                    var h = b.data;
                    h.requestId = c;
                    h.mediaSessionId = this.yc
                } else {
                    this.N("Unsupported namespace " + d, a, c);
                    return
                }
                this.bb.set(c, a);
                try {
                    this.ib = null;
                    var t = JSON.stringify(b.data);
                    e.$a(new fd(d,
                        "__inject__", t))
                } catch (y) {
                    throw this.bb.remove(c), this.N("Injecting " + b.data + " failed with " + y, a, c), y;
                }
                f || (this.ib ? this.N("Error " + JSON.stringify(this.ib), a, c, "WRAPPED_ERROR", this.ib) : (this.bb.remove(c), Ad(this, a, c)))
            } else this.N("Unregistered namespace " + d, a, c, "WRAPPED_ERROR", {
                type: "ERROR",
                code: "UNREGISTERED_NAMESPACE"
            })
        }
    };
    zd.prototype.ie = function(a, b, c) {
        if ("urn:x-cast:com.google.cast.media" == b && "STRING" == (this.aa.u[b] || null).oa) try {
            c = JSON.parse(c)
        } catch (e) {
            return G(Bd, "Parse failed: " + c), !1
        }
        var d = c.type;
        "urn:x-cast:com.google.cast.media" == b && "MEDIA_STATUS" == d && c.status && c.status[0] && (this.yc = c.status[0].mediaSessionId);
        if ("__inject__" != a) return !1;
        a = c.requestId;
        if (!m(a)) return Hb(Bd, "No requestId in " + c), !0;
        if ("urn:x-cast:com.google.cast.media" == b) {
            switch (d) {
                case "INVALID_REQUEST":
                case "INVALID_PLAYER_STATE":
                    b = c.reason,
                        this.ib = {
                            type: "ERROR",
                            code: b ? b : d
                        }
            }
            return !0
        }
        d = this.bb.get(a);
        if (!d) return G(Bd, "Request not found " + a), !0;
        this.bb.remove(a);
        if ("urn:x-cast:com.google.cast.cac" != b) return G(Bd, "Unsupported namespace " + b), !0;
        b = c;
        switch (b.type) {
            case "SUCCESS":
                Ad(this, d, a, Object.getOwnPropertyNames(c).some(function(a) {
                    return "type" != a && "requestId" != a && m(c[a])
                }) ? JSON.stringify(c) : void 0);
                break;
            case "ERROR":
                this.N("Wrapped error", d, a, "WRAPPED_ERROR", b);
                break;
            default:
                this.N("Unknown message type " + c, d, a)
        }
        return !0
    };
    zd.prototype.N = function(a, b, c, d, e) {
        G(Bd, a);
        this.S.send(b, new Cd(c, d || "INJECT_ERROR", m(e) ? JSON.stringify(e) : void 0))
    };
    var Ad = function(a, b, c, d) {
        a.S.send(b, new Dd(c, d))
    };
    ja(zd);
    var Bd = D("cast.receiver.InjectManager"),
        Ed = function(a, b, c) {
            this.type = a;
            this.requestId = b;
            this.data = c
        },
        Dd = function(a, b) {
            Ed.call(this, "SUCCESS", a, b)
        };
    k(Dd, Ed);
    var Cd = function(a, b, c) {
        Ed.call(this, "ERROR", a, c);
        this.code = b
    };
    k(Cd, Ed);
    var P = function() {
        I.call(this);
        sd || (sd = new pd);
        if (sd) {
            var a = sd;
            if (1 != a.wc) {
                Gb();
                var b = Fb,
                    c = a.de;
                b.Sa || (b.Sa = []);
                b.Sa.push(c);
                a.wc = !0
            }
        }
        this.B = Qa(Fd);
        this.Za = !1;
        this.W = new ed;
        this.T = {};
        this.h = new L;
        this.ga = new O("urn:x-cast:com.google.cast.system", this.W, Oa(this.T), "JSON");
        Jb(this, pa(Kb, this.ga));
        this.pc = this.Ib = null;
        this.vc = !1;
        this.Wa = this.Xa = null;
        this.Bb = !0;
        this.Ud = "1.11";
        this.ya = "notstarted";
        this.Xc = null;
        this.u = {};
        this.bc = this.onMaxVideoResolutionChanged = this.onFeedbackStarted = this.onStandbyChanged =
            this.onVisibilityChanged = this.onSystemVolumeChanged = this.onSenderDisconnected = this.onSenderConnected = this.onShutdown = this.onReady = null;
        this.ga.addEventListener("SystemSender", this.Zd.bind(this));
        K(window, "unload", this.gc, !1, this);
        K(document, "visibilitychange", this.Ec, !1, this);
        E(Q, ub, "Version: 2.0.0.0049");
        Gd && Gd(this)
    };
    k(P, I);
    v("cast.receiver.CastReceiverManager", P);
    var Hd = function(a) {
        var b = a.toLocaleLowerCase();
        return ["com.vizio.vue", "com.vizio.smartcast"].some(function(a) {
            return b.includes(a)
        })
    };
    P.prototype.ba = function() {
        return "CastReceiverManager"
    };
    P.prototype.start = function(a) {
        if (a) {
            if (!a) throw Error("Cannot validate undefined config.");
            if (void 0 != a.maxInactivity && 5 > a.maxInactivity) throw Error("config.maxInactivity must be greater than or equal to 5 seconds.");
            Sa(this.B, a || {})
        }
        zd.ca();
        this.vc = !0;
        this.W.open()
    };
    P.prototype.start = P.prototype.start;
    P.prototype.stop = function() {
        this.I();
        window.close()
    };
    P.prototype.stop = P.prototype.stop;
    P.prototype.ub = function() {
        return "ready" == this.ya
    };
    P.prototype.isSystemReady = P.prototype.ub;
    P.prototype.getSenders = function() {
        return Oa(this.T)
    };
    P.prototype.getSenders = P.prototype.getSenders;
    P.prototype.Nd = function(a) {
        return this.T[a] ? Qa(this.T[a]) : null
    };
    P.prototype.getSender = P.prototype.Nd;
    P.prototype.Rd = function() {
        return null == this.Xa ? this.Wa ? "notvisible" : "unknown" : this.Xa ? "visible" : "notvisible"
    };
    P.prototype.getVisibilityState = P.prototype.Rd;
    P.prototype.Pd = function() {
        return null == this.Wa ? this.Xa ? "notstandby" : "unknown" : this.Wa ? "standby" : "notstandby"
    };
    P.prototype.getStandbyState = P.prototype.Pd;
    P.prototype.sc = function() {
        "notstarted" == this.ya && (this.ya = /[&?]google_cast_bg=true(&|$)/.test(window.location.search) ? "startinginbackground" : "starting");
        return this.ya
    };
    P.prototype.getSystemState = P.prototype.sc;
    P.prototype.Ed = function() {
        return this.Ib
    };
    P.prototype.getApplicationData = P.prototype.Ed;
    P.prototype.Hd = function() {
        return this.pc
    };
    P.prototype.getDeviceCapabilities = P.prototype.Hd;
    P.prototype.je = function(a) {
        this.ub() ? Id(this, a) : this.B.statusText != a && (this.B.statusText = a, this.Za = !0)
    };
    P.prototype.setApplicationState = P.prototype.je;
    P.prototype.le = function(a, b) {
        this.ub() ? Id(this, a, b) : (void 0 != a && a != this.B.statusText && (this.B.statusText = a, this.Za = !0), void 0 != b && b != this.B.dialData && (this.B.dialData = b, this.Za = !0))
    };
    P.prototype.setLegacyApplicationState = P.prototype.le;
    P.prototype.oe = function(a) {
        if (0 > a || 1 < a) throw Error("Invalid level value");
        this.ga.send("SystemSender", {
            type: "setvolume",
            level: a
        })
    };
    P.prototype.setSystemVolumeLevel = P.prototype.oe;
    P.prototype.pe = function(a) {
        this.ga.send("SystemSender", {
            type: "setvolume",
            muted: a
        })
    };
    P.prototype.setSystemVolumeMuted = P.prototype.pe;
    P.prototype.Qd = function() {
        return this.Xc
    };
    P.prototype.getSystemVolume = P.prototype.Qd;
    var Id = function(a, b, c) {
        var d = {
            type: "setappstate"
        };
        void 0 != b && (d.statusText = b);
        void 0 != c && (d.dialData = c);
        a.ga.send("SystemSender", d)
    };
    P.prototype.Qc = function(a) {
        this.ga.send("SystemSender", {
            type: "startheartbeat",
            maxInactivity: a
        })
    };
    P.prototype.setInactivityTimeout = P.prototype.Qc;
    P.prototype.Oa = function(a, b) {
        if ("urn:x-cast:com.google.cast.system" == a) throw Error("Protected namespace");
        if (0 != a.lastIndexOf("urn:x-cast:", 0)) throw Error("Invalid namespace prefix");
        if (!this.u[a]) {
            if (this.vc) throw Error("New namespaces can not be requested after start has been called");
            this.u[a] = new O(a, this.W, Oa(this.T), b);
            Jb(this, pa(Kb, this.u[a]))
        }
        if (b && this.u[a].oa != b) throw Error("Invalid messageType for the namespace");
        return this.u[a]
    };
    P.prototype.getCastMessageBus = P.prototype.Oa;
    P.prototype.Nc = function(a) {
        this.ga.send("SystemSender", {
            type: "sendfeedbackmessage",
            message: a
        })
    };
    P.prototype.sendFeedbackMessage = P.prototype.Nc;
    P.prototype.Zd = function(a) {
        a = a.data;
        switch (a.type) {
            case "opened":
                H(Q, "Underlying message bus is open");
                var b = Oa(this.u),
                    c = this.B.statusText;
                a = this.B.dialData;
                var d = {
                    type: "ready"
                };
                c && (d.statusText = c);
                a && (d.dialData = a);
                d.activeNamespaces = b;
                d.version = "2.0.0";
                d.messagesVersion = "1.0";
                this.ga.send("SystemSender", d);
                this.B.maxInactivity && this.Qc(this.B.maxInactivity);
                break;
            case "closed":
                this.gc();
                break;
            case "error":
                this.c(new R("error", null));
                break;
            case "ready":
                b = a.launchingSenderId;
                c = Oa(this.u);
                this.Fb =
                    a.version;
                this.Bb = !Jd(this, this.Ud);
                var e = a.deviceCapabilities;
                this.pc = e ? Qa(e) : {};
                this.Ib = {
                    id: a.applicationId,
                    name: a.applicationName,
                    sessionId: a.sessionId,
                    namespaces: c,
                    launchingSenderId: b
                };
                this.ya = "ready";
                for (d in this.u) this.u[d].Wb = !0;
                this.Za && (this.Za = !1, Id(this, this.B.statusText, this.B.dialData));
                H(Q, "Dispatching CastReceiverManager system ready event");
                b = new Kd(this.Ib);
                if (this.onReady) this.onReady(b);
                this.c(b);
                break;
            case "senderconnected":
                b = {
                    id: a.senderId,
                    userAgent: a.userAgent
                };
                if (Hd(b.id)) H(Q,
                    "Ignored connection from " + b.id);
                else {
                    H(Q, "Dispatching CastReceiverManager sender connected event [" + b.id + "]");
                    Pa(this.T, b.id) && G(Q, "Unexpected connected message for already connected sender: " + b.id);
                    this.T[b.id] = b;
                    a = new Ld(b.id, b.userAgent);
                    for (c in this.u) d = this.u[c], e = b.id, Pa(d.A, e) ? G(hd, "Unexpected sender already registered [" + d.L + ", " + e + "]") : (H(hd, "Registering sender [" + d.L + ", " + e + "]"), d.A[e] = null);
                    if (this.onSenderConnected) this.onSenderConnected(a);
                    this.c(a)
                }
                break;
            case "senderdisconnected":
                c =
                    a.senderId;
                a = a.reason;
                if (Hd(c)) H(Q, "Ignored disconnection from " + c);
                else {
                    switch (a) {
                        case "closed_by_peer":
                            a = "requested_by_sender";
                            break;
                        case "transport_invalid_message":
                            a = "error";
                            break;
                        default:
                            a = "unknown"
                    }
                    H(Q, "Dispatching sender disconnected event [" + c + "] Reason: " + a);
                    if (Pa(this.T, c)) {
                        d = this.T[c].userAgent;
                        delete this.T[c];
                        a = new Md(c, d, a);
                        for (b in this.u) d = this.u[b], e = c, Pa(d.A, e) && (H(hd, "Unregistering sender [" + d.L + ", " + e + "]"), d.A[e] && d.A[e].close(), delete d.A[e]);
                        if (this.onSenderDisconnected) this.onSenderDisconnected(a);
                        this.c(a)
                    } else G(Q, "Unknown sender disconnected: " + c)
                }
                break;
            case "volumechanged":
                this.Xc = b = {
                    level: a.level,
                    muted: a.muted
                };
                H(Q, "Dispatching system volume changed event [" + b.level + ", " + b.muted + "]");
                b = new Nd(b);
                if (this.onSystemVolumeChanged) this.onSystemVolumeChanged(b);
                this.c(b);
                break;
            case "visibilitychanged":
                this.Bb || (b = a.visible, Od(this, m(b) ? b : null));
                break;
            case "standbychanged":
                if (!this.Bb && (b = a.standby, b = m(b) ? b : null, b != this.Wa)) {
                    H(Q, "Dispatching standby changed event " + b);
                    this.Wa = b;
                    b = new Pd(1 == b);
                    if (this.onStandbyChanged) this.onStandbyChanged(b);
                    this.c(b)
                }
                break;
            case "maxvideoresolutionchanged":
                b = a.height;
                H(Q, "Dispatching maxvideoresolutionchanged " + b);
                b = new Qd(b);
                if (this.onMaxVideoResolutionChanged) this.onMaxVideoResolutionChanged(b);
                this.c(b);
                break;
            case "hdroutputtypechanged":
                this.bc && this.bc(a.hdrType);
                break;
            case "screenresolutionchanged":
                break;
            case "feedbackstarted":
                H(Q, "Dispatching feedback started event");
                b = new Rd;
                if (this.onFeedbackStarted) this.onFeedbackStarted(b);
                else this.Nc("");
                break;
            default:
                throw Error("Unexpected message type: " + a.type);
        }
    };
    var Od = function(a, b) {
        if (b == a.Xa) H(Q, "Ignoring visibility changed event, state is already " + b);
        else {
            H(Q, "Dispatching visibility changed event " + b);
            a.Xa = b;
            b = new Sd(0 != b);
            if (a.onVisibilityChanged) a.onVisibilityChanged(b);
            a.c(b)
        }
    };
    P.prototype.Ec = function() {
        this.Bb && Od(this, "visible" == document.visibilityState)
    };
    P.prototype.gc = function() {
        H(Q, "Dispatching shutdown event");
        this.sc();
        this.ya = "startinginbackground" == this.ya ? "stoppinginbackground" : "stopping";
        for (var a in this.u) this.u[a].Wb = !1;
        a = new Td;
        if (this.onShutdown) this.onShutdown(a);
        this.c(a)
    };
    var Jd = function(a, b) {
        if (!b) return G(Q, "Version not provided"), !1;
        if (!a.Fb) return G(Q, "No System Version"), !1;
        var c = b.split(".");
        if (!c.length) return G(Q, "Version provided is not valid: " + b), !1;
        var d = a.Fb.split(".");
        if (!d.length) return G(Q, "System Version format is not valid " + a.Fb), !1;
        for (var e = 0; e < c.length; e++) {
            var f = parseInt(c[e], 10);
            if (isNaN(f)) return G(Q, "Version is not numeric: " + b), !1;
            var h = d.length > e ? parseInt(d[e], 10) : 0;
            if (isNaN(h)) return G(Q, "System Version is not numeric: " + a.Fb), !1;
            if (f >
                h) return !1
        }
        return !0
    };
    P.prototype.C = function() {
        this.h.I();
        this.W.close();
        I.prototype.C.call(this);
        window && dc(window, "unload", this.gc, !1, this);
        document && dc(document, "visibilitychange", this.Ec, !1, this);
        delete P.rb;
        Ib(Q, "Disposed " + this.ba())
    };
    P.prototype.addEventListener = function(a, b) {
        K(this.h, a, b)
    };
    P.prototype.addEventListener = P.prototype.addEventListener;
    P.prototype.removeEventListener = function(a, b) {
        dc(this.h, a, b)
    };
    P.prototype.removeEventListener = P.prototype.removeEventListener;
    P.prototype.c = function(a) {
        a.target = this;
        return hc(this.h, a)
    };
    P.prototype.dispatchEvent = function(a) {
        return this.c(a)
    };
    P.prototype.dispatchEvent = P.prototype.dispatchEvent;
    ja(P);
    P.getInstance = P.ca;
    var Gd = null,
        Q = D("cast.receiver.CastReceiverManager");
    P.EventType = {
        READY: "ready",
        SHUTDOWN: "shutdown",
        SENDER_CONNECTED: "senderconnected",
        SENDER_DISCONNECTED: "senderdisconnected",
        ERROR: "error",
        SYSTEM_VOLUME_CHANGED: "systemvolumechanged",
        VISIBILITY_CHANGED: "visibilitychanged",
        STANDBY_CHANGED: "standbychanged",
        MAX_VIDEO_RESOLUTION_CHANGED: "maxvideoresolutionchanged",
        FEEDBACK_STARTED: "feedbackstarted"
    };
    var R = function(a, b) {
        J.call(this, a);
        this.data = b
    };
    k(R, J);
    P.Event = R;
    v("cast.receiver.system.DisconnectReason", {
        REQUESTED_BY_SENDER: "requested_by_sender",
        ERROR: "error",
        UNKNOWN: "unknown"
    });
    var Md = function(a, b, c) {
        R.call(this, "senderdisconnected", a);
        this.senderId = a;
        this.userAgent = b;
        this.reason = c
    };
    k(Md, R);
    P.SenderDisconnectedEvent = Md;
    var Ld = function(a, b) {
        R.call(this, "senderconnected", a);
        this.senderId = a;
        this.userAgent = b
    };
    k(Ld, R);
    P.SenderConnectedEvent = Ld;
    var Sd = function(a) {
        R.call(this, "visibilitychanged", a);
        this.isVisible = a
    };
    k(Sd, R);
    P.VisibilityChangedEvent = Sd;
    var Pd = function(a) {
        R.call(this, "standbychanged", null);
        this.isStandby = a
    };
    k(Pd, R);
    P.StandbyChangedEvent = Pd;
    var Nd = function(a) {
        R.call(this, "systemvolumechanged", a);
        this.data = a
    };
    k(Nd, R);
    P.SystemVolumeChangedEvent = Nd;
    var Kd = function(a) {
        R.call(this, "ready", a);
        this.data = a
    };
    k(Kd, R);
    P.ReadyEvent = Kd;
    var Td = function() {
        R.call(this, "shutdown", null)
    };
    k(Td, R);
    P.ShutdownEvent = Td;
    var Rd = function() {
        R.call(this, "feedbackstarted", null)
    };
    k(Rd, R);
    P.FeedbackStartedEvent = Rd;
    var Qd = function(a) {
        R.call(this, "maxvideoresolutionchanged", null);
        this.height = a
    };
    k(Qd, R);
    P.MaxVideoResolutionChangedEvent = Qd;
    P.Config = function() {
        this.dialData = this.maxInactivity = this.statusText = void 0
    };
    var Fd = {
        maxInactivity: 10
    };
    var Ud = function() {
        this.aa = P.ca();
        this.S = this.aa.Oa("urn:x-cast:com.google.cast.broadcast", "JSON");
        this.S.onMessage = this.M.bind(this)
    };
    Ud.prototype.M = function(a) {
        if (this.aa.ub()) Hb(Vd, "Ignoring broadcast request, system is ready.");
        else {
            a = a.data;
            var b = a.type;
            if ("APPLICATION_BROADCAST" != b) G(Vd, "Ignoring message type: " + b);
            else if (b = a.namespace) {
                var c = this.aa.u[b] || null;
                if (c) switch (b) {
                    case "urn:x-cast:com.google.cast.media":
                        var d = JSON.parse(a.message);
                        if ("PRECACHE" != d.type) {
                            G(Vd, "Unsupported type for media namespace: " + d.type);
                            break
                        }
                        c.$a(new fd(b, "__broadcast__", a.message));
                        break;
                    default:
                        G(Vd, "Unsupported namespace: " + a.namespace)
                } else G(Vd,
                    "Invalid message bus for namespace: " + b)
            } else G(Vd, "Missing namespace: " + b)
        }
    };
    ja(Ud);
    var Vd = D("cast.receiver.BroadcastManager");
    v("cast.receiver.BroadcastManager.NAMESPACE_PREFIX", "urn:x-cast:");
    v("cast.receiver.BroadcastManager.BroadcastRequest", function() {});
    var Wd = function() {
        return !(!cast.__platform__ || !cast.__platform__.metrics)
    };
    v("cast.receiver.analytics.logEvent", function(a) {
        Wd() && cast.__platform__.metrics.logEventToUma(a)
    });
    v("cast.receiver.analytics.logBool", function(a, b) {
        Wd() && cast.__platform__.metrics.logBoolToUma(a, b)
    });
    v("cast.receiver.analytics.logInt", function(a, b) {
        Wd() && cast.__platform__.metrics.logIntToUma(a, b)
    });
    v("cast.receiver.analytics.logHistogramValue", function(a, b, c, d, e) {
        Wd() && cast.__platform__.metrics.logHistogramValueToUma(a, b, c, d, e)
    });
    var S = function() {
        this.onCustomCommand = this.onDisplayStatus = this.onUserAction = this.onLoadBySearch = this.onLoadByEntity = this.onSetCredentials = null;
        this.S = P.ca().Oa("urn:x-cast:com.google.cast.cac", "JSON");
        this.S.onMessage = this.M.bind(this);
        this.Db = new Ya
    };
    v("cast.receiver.CommandAndControlManager", S);
    S.prototype.M = function(a) {
        var b = a.data,
            c = b.type;
        a = a.senderId;
        var d;
        switch (c) {
            case "SET_CREDENTIALS":
                d = this.onSetCredentials;
                break;
            case "LOAD_BY_ENTITY":
                d = this.onLoadByEntity;
                break;
            case "LOAD_BY_SEARCH":
                d = this.onLoadBySearch;
                break;
            case "USER_ACTION":
                d = this.onUserAction;
                break;
            case "DISPLAY_STATUS":
                d = this.onDisplayStatus;
                break;
            case "CUSTOM_COMMAND":
                d = this.onCustomCommand;
                break;
            case "SUCCESS":
            case "ERROR":
                c = b.requestId;
                (a = this.Db.get(c)) ? (this.Db.remove(c), a.resolve(b)) : Hb(Xd, "No matching request for response " +
                    JSON.stringify(b));
                return;
            default:
                this.N("Unsupported event " + c, a, b, "INVALID_REQUEST");
                return
        }
        d ? Yd(this, a, b, d) : this.N("Handler for " + c + " not implemented", a, b, "INVALID_COMMAND")
    };
    var Yd = function(a, b, c, d) {
            d = d(c);
            Fc(d).then(function(d) {
                a.S.send(b, Zd(c, d))
            }, function(d) {
                a.N("Got a rejected promise " + JSON.stringify(d), b, c, "APP_ERROR")
            })
        },
        ae = function(a, b, c) {
            G(Xd, a);
            a = new $d(c);
            a.requestId = b.requestId;
            return a
        },
        Zd = function(a, b) {
            if (!b) return ae("No response data", a, "APP_ERROR");
            switch (b.type) {
                case "SUCCESS":
                case "ERROR":
                    return b.requestId = a.requestId, b
            }
            return ae("Invalid response data " + JSON.stringify(b), a, "APP_ERROR")
        };
    S.prototype.N = function(a, b, c, d) {
        this.S.send(b, ae(a, c, d))
    };
    S.prototype.ne = function(a, b) {
        if (null !== b && !r(b)) throw G(Xd, "Given handler is not a function or null"), Error("Given handler is not a function or null");
        switch (a) {
            case "SET_CREDENTIALS":
                this.onSetCredentials = b;
                break;
            case "LOAD_BY_ENTITY":
                this.onLoadByEntity = b;
                break;
            case "LOAD_BY_SEARCH":
                this.onLoadBySearch = b;
                break;
            case "USER_ACTION":
                this.onUserAction = b;
                break;
            case "DISPLAY_STATUS":
                this.onDisplayStatus = b;
                break;
            case "CUSTOM_COMMAND":
                this.onCustomCommand = b;
                break;
            default:
                throw a = "Cannot set handler for " +
                    a, G(Xd, a), Error(a);
        }
    };
    S.prototype.setMessageHandler = S.prototype.ne;
    S.prototype.$d = function(a, b) {
        var c = qa();
        if (ab(this.Db.K, c)) return Promise.reject(Error("Duplicate request"));
        a = new be(a, b);
        a.requestId = c;
        this.S.send("system-0", a);
        c = Hc();
        this.Db.set(a.requestId, c);
        return Promise.resolve(c.ce)
    };
    S.prototype.playString = S.prototype.$d;
    ja(S);
    S.getInstance = S.ca;
    S.NAMESPACE = "urn:x-cast:com.google.cast.cac";
    var Xd = D("cast.receiver.CommandAndControlManager");
    S.MessageType = {
        SET_CREDENTIALS: "SET_CREDENTIALS",
        LOAD_BY_ENTITY: "LOAD_BY_ENTITY",
        LOAD_BY_SEARCH: "LOAD_BY_SEARCH",
        USER_ACTION: "USER_ACTION",
        DISPLAY_STATUS: "DISPLAY_STATUS",
        PLAY_STRING: "PLAY_STRING",
        CUSTOM_COMMAND: "CUSTOM_COMMAND",
        SUCCESS: "SUCCESS",
        ERROR: "ERROR"
    };
    S.PlayStringId = {
        FREE_TRIAL_ABOUT_TO_EXPIRE: "FREE_TRIAL_ABOUT_TO_EXPIRE",
        SUBSCRIPTION_ABOUT_TO_EXPIRE: "SUBSCRIPTION_ABOUT_TO_EXPIRE",
        STREAM_HIJACKED: "STREAM_HIJACKED"
    };
    var ce = function(a) {
        this.type = a
    };
    S.RequestData = ce;
    var de = function() {
        this.type = "SET_CREDENTIALS"
    };
    k(de, ce);
    S.SetCredentialsRequestData = de;
    var ee = function() {
        this.type = "LOAD_BY_ENTITY"
    };
    k(ee, ce);
    S.LoadByEntityRequestData = ee;
    var fe = function() {
        this.type = "CUSTOM_COMMAND"
    };
    k(fe, ce);
    S.CustomCommandRequestData = fe;
    var ge = function() {
        this.type = "LOAD_BY_SEARCH"
    };
    k(ge, ce);
    S.LoadBySearchRequestData = ge;
    var be = function(a, b) {
        this.type = "PLAY_STRING";
        this.stringId = a;
        this.arguments = b
    };
    k(be, ce);
    S.PlayStringRequestData = be;
    S.UserAction = {
        LIKE: "LIKE",
        DISLIKE: "DISLIKE",
        FOLLOW: "FOLLOW",
        UNFOLLOW: "UNFOLLOW",
        FLAG: "FLAG",
        SKIP_AD: "SKIP_AD"
    };
    S.UserActionContext = {
        UNKNOWN_CONTEXT: "UNKNOWN_CONTEXT",
        TRACK: "TRACK",
        ALBUM: "ALBUM",
        ARTIST: "ARTIST",
        PLAYLIST: "PLAYLIST",
        EPISODE: "EPISODE",
        SERIES: "SERIES",
        MOVIE: "MOVIE",
        CHANNEL: "CHANNEL",
        TEAM: "TEAM",
        PLAYER: "PLAYER",
        COACH: "COACH"
    };
    var he = function() {
        this.type = "USER_ACTION"
    };
    k(he, ce);
    S.UserActionRequestData = he;
    var ie = function() {
        this.type = "DISPLAY_STATUS"
    };
    k(ie, ce);
    S.DisplayStatusRequestData = ie;
    S.ErrorCode = {
        APP_ERROR: "APP_ERROR",
        NOT_SUPPORTED: "NOT_SUPPORTED",
        AUTHENTICATION_EXPIRED: "AUTHENTICATION_EXPIRED",
        PREMIUM_ACCOUNT_REQUIRED: "PREMIUM_ACCOUNT_REQUIRED",
        CONCURRENT_STREAM_LIMIT: "CONCURRENT_STREAM_LIMIT",
        PARENTAL_CONTROL_RESTRICTED: "PARENTAL_CONTROL_RESTRICTED",
        NOT_AVAILABLE_IN_REGION: "NOT_AVAILABLE_IN_REGION",
        CONTENT_ALREADY_PLAYING: "CONTENT_ALREADY_PLAYING",
        INVALID_COMMAND: "INVALID_COMMAND",
        INVALID_REQUEST: "INVALID_REQUEST"
    };
    var je = function(a) {
        this.type = a
    };
    k(je, ce);
    S.ResponseData = je;
    var ke = function(a) {
        this.type = "SUCCESS";
        this.status = a
    };
    k(ke, je);
    S.SuccessResponseData = ke;
    var $d = function(a, b) {
        this.type = "ERROR";
        this.code = a;
        this.reason = b
    };
    k($d, je);
    S.ErrorResponseData = $d;
    var le = !(!cast.__platform__ || !cast.__platform__.crypto);
    v("cast.receiver.cryptokeys.getKeyByName", cast.__platform__ && cast.__platform__.cryptokeys ? cast.__platform__.cryptokeys.getKeyByName : window.cryptokeys && window.cryptokeys.getKeyByName);
    v("cast.receiver.crypto.decrypt", le ? cast.__platform__.crypto.decrypt : window.crypto.subtle.decrypt);
    v("cast.receiver.crypto.encrypt", le ? cast.__platform__.crypto.encrypt : window.crypto.subtle.encrypt);
    v("cast.receiver.crypto.sign", le ? cast.__platform__.crypto.sign : window.crypto.subtle.sign);
    v("cast.receiver.crypto.unwrapKey", le ? cast.__platform__.crypto.unwrapKey : window.crypto.subtle.unwrapKey);
    v("cast.receiver.crypto.verify", le ? cast.__platform__.crypto.verify : window.crypto.subtle.verify);
    v("cast.receiver.crypto.wrapKey", le ? cast.__platform__.crypto.wrapKey : window.crypto.subtle.wrapKey);
    var me = /#(.)(.)(.)(.)/,
        oe = function(a) {
            if (!ne.test(a)) throw Error("'" + a + "' is not a valid alpha hex color");
            5 == a.length && (a = a.replace(me, "#$1$1$2$2$3$3$4$4"));
            a = a.toLowerCase();
            return [parseInt(a.substr(1, 2), 16), parseInt(a.substr(3, 2), 16), parseInt(a.substr(5, 2), 16), parseInt(a.substr(7, 2), 16) / 255]
        },
        ne = /^#(?:[0-9a-f]{4}){1,2}$/i,
        pe = function(a) {
            var b = a.slice(0);
            b[3] = Math.round(1E3 * a[3]) / 1E3;
            return "rgba(" + b.join(",") + ")"
        };
    var qe = !gb && !A || A && 9 <= Number(pb) || gb && ob("1.9.1");
    A && ob("9");
    var re = function(a, b, c) {
            function d(c) {
                c && b.appendChild(p(c) ? a.createTextNode(c) : c)
            }
            for (var e = 1; e < c.length; e++) {
                var f = c[e];
                if (!la(f) || ma(f) && 0 < f.nodeType) d(f);
                else {
                    var h;
                    a: {
                        if (f && "number" == typeof f.length) {
                            if (ma(f)) {
                                h = "function" == typeof f.item || "string" == typeof f.item;
                                break a
                            }
                            if (r(f)) {
                                h = "function" == typeof f.item;
                                break a
                            }
                        }
                        h = !1
                    }
                    Fa(h ? Ja(f) : f, d)
                }
            }
        },
        se = function(a) {
            this.mb = a || l.document || document
        };
    g = se.prototype;
    g.getElementsByTagName = function(a, b) {
        return (b || this.mb).getElementsByTagName(String(a))
    };
    g.createElement = function(a) {
        return this.mb.createElement(String(a))
    };
    g.createTextNode = function(a) {
        return this.mb.createTextNode(String(a))
    };
    g.appendChild = function(a, b) {
        a.appendChild(b)
    };
    g.append = function(a, b) {
        var c = a;
        x(c, "Node cannot be null or undefined.");
        re(9 == c.nodeType ? c : c.ownerDocument || c.document, a, arguments)
    };
    g.canHaveChildren = function(a) {
        if (1 != a.nodeType) return !1;
        switch (a.tagName) {
            case "APPLET":
            case "AREA":
            case "BASE":
            case "BR":
            case "COL":
            case "COMMAND":
            case "EMBED":
            case "FRAME":
            case "HR":
            case "IMG":
            case "INPUT":
            case "IFRAME":
            case "ISINDEX":
            case "KEYGEN":
            case "LINK":
            case "NOFRAMES":
            case "NOSCRIPT":
            case "META":
            case "OBJECT":
            case "PARAM":
            case "SCRIPT":
            case "SOURCE":
            case "STYLE":
            case "TRACK":
            case "WBR":
                return !1
        }
        return !0
    };
    g.removeNode = function(a) {
        return a && a.parentNode ? a.parentNode.removeChild(a) : null
    };
    g.qc = function(a) {
        return qe && void 0 != a.children ? a.children : Ga(a.childNodes, function(a) {
            return 1 == a.nodeType
        })
    };
    g.contains = function(a, b) {
        if (!a || !b) return !1;
        if (a.contains && 1 == b.nodeType) return a == b || a.contains(b);
        if ("undefined" != typeof a.compareDocumentPosition) return a == b || !!(a.compareDocumentPosition(b) & 16);
        for (; b && a != b;) b = b.parentNode;
        return b == a
    };
    var we = function(a, b, c, d) {
        I.call(this);
        this.b = a;
        this.lb = this.Eb = null;
        this.za = [];
        this.Nb = !1;
        this.Kb = "cast-captions-" + Math.floor(1E6 * Math.random()).toString();
        this.dd = "[" + this.Kb + '="true"]::cue ';
        this.ed = new RegExp(/^[\.'":%,;\s\-0-9a-z]+$/i);
        a = ia(b);
        for (b = a.next(); !b.done; b = a.next()) {
            b = b.value;
            var e = b.trackContentId;
            if ("TEXT" == b.type && e) {
                var f = b.trackContentType;
                if (0 == wa("vtt", e.substr(e.length - 3, 3)) || m(f) && 0 == wa(f, "text/vtt")) e = document.createElement("track"), e.src = b.trackContentId, e.id = b.trackId,
                    e.label = b.name, e.srclang = b.language, e.kind = (b.subtype || "CAPTIONS").toLowerCase(), this.za.push(e), this.b.appendChild(e)
            }
        }
        te(this);
        d && ue(this, d);
        ve(this, c)
    };
    k(we, I);
    we.prototype.ba = function() {
        return "TextTracksManager"
    };
    var xe = function(a, b) {
            a = ia(a.za);
            for (var c = a.next(); !c.done; c = a.next()) {
                var c = c.value,
                    d = c.track;
                b(c) ? d.mode = "showing" : (d.mode = "showing", d.mode = "disabled")
            }
        },
        ye = function(a) {
            return a.za.map(function(a) {
                return parseInt(a.id, 10)
            })
        },
        ve = function(a, b) {
            xe(a, function(a) {
                return 0 <= Da(b, parseInt(a.id, 10))
            })
        },
        ze = function(a, b) {
            xe(a, function(a) {
                return ra(b, a.srclang)
            })
        },
        Ae = function(a) {
            var b = [];
            a = ia(a.za);
            for (var c = a.next(); !c.done; c = a.next()) c = c.value, "showing" == c.track.mode && b.push(parseInt(c.id, 10));
            return b
        },
        Be = function(a) {
            a.lb && (a.b.removeAttribute(a.Kb), document.head.removeChild(a.lb), a.Eb = null)
        },
        Ce = function(a) {
            a.Nb && (a.b.removeAttribute("crossorigin"), a.Nb = !1)
        },
        T = function(a, b, c) {
            1 == c || a.ed.test(b) ? a.Eb.insertRule(a.dd + "{ " + b + " }", a.Eb.cssRules.length) : Hb(De, "Invalid css cue: " + b)
        },
        te = function(a) {
            Be(a);
            Ce(a);
            var b = ta || (ta = new se),
                c = b.mb,
                d = b.createElement("STYLE");
            d.type = "text/css";
            b.getElementsByTagName("HEAD")[0].appendChild(d);
            d.styleSheet ? d.styleSheet.cssText = "" : d.appendChild(c.createTextNode(""));
            a.lb = d;
            a.Eb = a.lb.sheet;
            T(a, "font-size: 4.1vh;");
            T(a, "font-family: monospace;");
            T(a, "font-style: normal;");
            T(a, "font-weight: normal;");
            T(a, "background-color: black;");
            T(a, "color: white;");
            a.b.setAttribute(a.Kb, !0);
            0 < a.za.length && !a.b.getAttribute("crossorigin") && (a.b.setAttribute("crossorigin", "anonymous"), a.Nb = !0)
        },
        Ee = function(a, b) {
            var c;
            try {
                c = pe(oe(a))
            } catch (d) {
                Hb(De, "Invalid color: " + a)
            }
            if (c) switch (a = "rgba(204, 204, 204, " + parseInt(a.substring(7, 9), 16) + ")", b) {
                case "OUTLINE":
                    return "text-shadow: 0 0 4px " +
                        c + ", 0 0 4px " + c + ", 0 0 4px " + c + ", 0 0 4px " + c + ";";
                case "DROP_SHADOW":
                    return "text-shadow: 0px 2px 3px " + c + ", 0px 2px 4px " + c + ", 0px 2px 5px " + c + ";";
                case "RAISED":
                    return "text-shadow: 1px 1px " + c + ", 2px 2px " + c + ", 3px 3px " + c + ";";
                case "DEPRESSED":
                    return "text-shadow: 1px 1px " + a + ", 0 1px " + a + ", -1px -1px " + c + ", 0 -1px " + c + ";"
            }
            return ""
        },
        Fe = function(a) {
            switch (a) {
                case "BOLD":
                    return "font-weight: bold;";
                case "BOLD_ITALIC":
                    return "font-style: italic; font-weight: bold;";
                case "ITALIC":
                    return "font-style: italic;"
            }
            return "font-style: normal;"
        },
        ue = function(a, b) {
            if (m(b.foregroundColor)) try {
                var c = pe(oe(b.foregroundColor));
                T(a, "color: " + c + ";", !0)
            } catch (t) {
                Hb(De, "Invalid color: " + b.foregroundColor)
            }
            if (m(b.backgroundColor)) try {
                var d = pe(oe(b.backgroundColor));
                T(a, "background-color: " + d + ";", !0)
            } catch (t) {
                Hb(De, "Invalid color: " + b.backgroundColor)
            }
            m(b.fontScale) && T(a, "font-size: " + 100 * b.fontScale + "%;");
            if (m(b.fontFamily) || m(b.fontGenericFamily)) {
                var c = b.fontFamily,
                    d = b.fontGenericFamily,
                    e = "font-family: ",
                    f = "";
                m(c) && (e += '"' + c + '"', f = ", ");
                if (m(d)) {
                    var h;
                    switch (d) {
                        case "SANS_SERIF":
                            h = '"Droid Sans", sans-serif';
                            break;
                        case "MONOSPACED_SANS_SERIF":
                            h = '"Droid Sans Mono", monospace';
                            break;
                        case "SERIF":
                            h = '"Droid Serif", serif';
                            break;
                        case "MONOSPACED_SERIF":
                            h = '"Cutive Mono"';
                            break;
                        case "CASUAL":
                            h = '"Short Stack"';
                            break;
                        case "CURSIVE":
                            h = "Quintessential";
                            break;
                        case "SMALL_CAPITALS":
                            h = '"Alegreya Sans SC"'
                    }
                    e += f + h
                }
                T(a, e + ";")
            }
            m(b.fontStyle) && T(a, Fe(b.fontStyle));
            m(b.edgeType) && (h = m(b.foregroundColor) ? b.foregroundColor : "#FFFFFFFF", b = m(b.edgeColor) ? Ee(b.edgeColor,
                b.edgeType) : Ee(h, b.edgeType), T(a, b, !0))
        };
    we.prototype.C = function() {
        I.prototype.C.call(this);
        for (var a = ia(this.za), b = a.next(); !b.done; b = a.next()) this.b.removeChild(b.value);
        this.za.length = 0;
        Be(this);
        Ce(this);
        E(De, C, "Disposed " + this.ba())
    };
    var De = D("cast.receiver.TextTracksManager");
    var Ge = function(a, b) {
        this.G = a;
        this.b = b;
        this.Xb = this.Ob = this.Pb = n;
        this.Fa = 0;
        this.X = this.Na = null;
        this.Hc = 0;
        this.o = this.v = null;
        this.Va = !1;
        this.sb = !0;
        K(this.b, "error", this.$b, !1, this);
        K(this.b, "ended", this.zb, !1, this);
        K(this.b, "loadedmetadata", this.dc, !1, this);
        H(this.G, "Using default Player")
    };
    g = Ge.prototype;
    g.Gc = function(a, b, c, d) {
        He(this);
        this.sb = a;
        this.Fa = b;
        this.Hc = d || 0;
        this.X = c || null
    };
    g.$b = function(a) {
        He(this);
        this.Pb(a)
    };
    g.zb = function() {
        He(this);
        this.Ob()
    };
    g.dc = function() {
        this.v && this.o && ve(this.v, this.o);
        this.Xb()
    };
    g.registerErrorCallback = function(a) {
        this.Pb = a
    };
    g.registerEndedCallback = function(a) {
        this.Ob = a
    };
    g.registerLoadCallback = function(a) {
        this.Xb = a
    };
    g.unregisterErrorCallback = function() {
        this.Pb = n
    };
    g.unregisterEndedCallback = function() {
        this.Ob = n
    };
    g.unregisterLoadCallback = function() {
        this.Xb = n
    };
    var Ie = function(a) {
            var b = a.b.duration;
            if (isNaN(b) || null == a.X) return b;
            if (null != a.Na) return a.Na;
            a.Na = 0 <= a.X ? Math.min(a.Hc + a.X, b) : Math.max(b + a.X, a.Fa);
            return a.Na
        },
        He = function(a) {
            null != a.X && (dc(a.b, "timeupdate", a.Fc, !1, a), a.Na = null, a.X = null)
        };
    Ge.prototype.Fc = function() {
        Je(this)
    };
    var Je = function(a) {
        if (null == a.X) return !1;
        var b = Ie(a);
        return isNaN(b) ? !1 : a.b.currentTime >= b ? (a.zb(), !0) : !1
    };
    g = Ge.prototype;
    g.load = function(a, b, c, d, e) {
        this.v && (this.v.I(), this.v = null);
        this.Va = !1;
        d && d.tracks && this.b && (this.v && this.v.I(), this.v = new we(this.b, d.tracks, d.activeTrackIds || [], d.textTrackStyle || null), d.language && ze(this.v, d.language));
        null != this.X && K(this.b, "timeupdate", this.Fc, !1, this);
        e || (this.Fa = c && 0 < c ? c : 0, H(this.G, "Load - contentId: " + a + " autoplay: " + b + " time: " + this.Fa), this.b.autoplay = !1, a && (this.b.src = a), this.b.autoplay = b, this.b.load())
    };
    g.reset = function() {
        this.Va = !1;
        this.v && (this.v.I(), this.v = null);
        this.o = null;
        this.b.removeAttribute("src");
        this.Fa = 0;
        this.b.load();
        He(this)
    };
    g.play = function() {
        this.Va = !1;
        this.b.play()
    };
    g.seek = function(a, b) {
        this.b.currentTime != a && (this.b.currentTime = a);
        Je(this) || ("PLAYBACK_START" == b && this.b.paused ? this.b.play() : "PLAYBACK_PAUSE" != b || this.b.paused || this.b.pause())
    };
    g.pause = function() {
        this.Va = !0;
        this.b.pause()
    };
    g.getState = function() {
        null == this.sb && (this.sb = this.b.autoplay);
        return this.b.paused || isNaN(this.b.duration) ? this.b.duration && (this.b.currentTime || 0 == this.b.currentTime) && this.b.currentTime < Ie(this) ? this.b.currentTime == this.Fa && this.sb && !this.Va ? "BUFFERING" : "PAUSED" : "IDLE" : "PLAYING"
    };
    g.getCurrentTimeSec = function() {
        var a = Ie(this);
        return isNaN(a) ? this.b.currentTime : this.b.currentTime < a ? this.b.currentTime : a
    };
    g.getDurationSec = function() {
        return Ie(this)
    };
    g.getVolume = function() {
        return {
            level: this.b.volume,
            muted: this.b.muted
        }
    };
    g.setVolume = function(a) {
        m(a.level) && (this.b.volume = a.level);
        m(a.muted) && (this.b.muted = a.muted)
    };
    g.editTracksInfo = function(a) {
        if (this.v) {
            if (a.textTrackStyle) {
                var b = this.v,
                    c = a.textTrackStyle;
                te(b);
                ue(b, c)
            }
            a.language ? ze(this.v, a.language) : a.activeTrackIds && ve(this.v, a.activeTrackIds)
        }
        Ke(this, a.activeTrackIds);
        return this.o
    };
    var Ke = function(a, b) {
        a.o = b ? b.slice(0) : a.o;
        a.o = a.o || [];
        if (a.v) {
            var c = ye(a.v);
            a.o = a.o.filter(function(a) {
                return !c.includes(a)
            }).concat(Ae(a.v))
        }
        0 == a.o.length && (a.o = null)
    };
    var Le = function(a) {
        I.call(this);
        this.Ra = a;
        this.l = {}
    };
    w(Le, I);
    var Me = [];
    g = Le.prototype;
    g.Ha = function(a, b, c, d) {
        "array" != ka(b) && (b && (Me[0] = b.toString()), b = Me);
        for (var e = 0; e < b.length; e++) {
            var f = K(a, b[e], c || this.handleEvent, d || !1, this.Ra || this);
            if (!f) break;
            this.l[f.key] = f
        }
        return this
    };
    g.fb = function(a, b, c, d, e) {
        if ("array" == ka(b))
            for (var f = 0; f < b.length; f++) this.fb(a, b[f], c, d, e);
        else c = c || this.handleEvent, e = e || this.Ra || this, c = Zb(c), d = !!d, b = Pb(a) ? a.Qa(b, c, d, e) : a ? (a = $b(a)) ? a.Qa(b, c, d, e) : null : null, b && (ec(b), delete this.l[b.key]);
        return this
    };
    g.ic = function() {
        Ma(this.l, function(a, b) {
            this.l.hasOwnProperty(b) && ec(a)
        }, this);
        this.l = {}
    };
    g.C = function() {
        Le.cb.C.call(this);
        this.ic()
    };
    g.handleEvent = function() {
        throw Error("EventHandler.handleEvent not implemented");
    };
    var Ne = {
            Be: "persistent-release-message"
        },
        Oe = new Uint8Array([43, 248, 102, 128, 198, 229, 78, 36, 190, 35, 15, 129, 90, 96, 110, 178]),
        U = function(a, b) {
            this.h = new L;
            this.Td = b;
            this.J = a.createSession("persistent-license");
            this.sessionId = "";
            this.expiration = this.J.expiration;
            this.closed = this.J.closed;
            this.keyStatuses = this.J.keyStatuses;
            this.Ra = new Le(this);
            Jb(this.h, pa(Kb, this.Ra))
        };
    v("cast.receiver.eme.KeySession", U);
    U.createSession = function(a, b) {
        a: {
            for (var c in Ne)
                if (Ne[c] == b) break a;
            throw Error("Unknown key session type: " + b);
        }
        a = new U(a, b);a.Ra.Ha(a.J, "message", a.Lc).Ha(a.J, "keystatuseschange", a.Lc);
        return a
    };
    U.prototype.generateRequest = function(a, b) {
        if ("persistent-release-message" == this.Td) {
            if ("cenc" != a) throw Error("Only cenc initDataType is supported for persistent-release-message session type.");
            var c = new Uint8Array([0, 0, 0, 0, 112, 115, 115, 104, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 2]);
            c.set(Oe, 12);
            (new DataView(c.buffer)).setUint32(0, c.length);
            var d = new Uint8Array(b.byteLength + c.byteLength);
            d.set(new Uint8Array(b), 0);
            d.set(c, b.byteLength);
            b = d
        }
        return this.J.generateRequest(a, b).then(u(function() {
            this.sessionId =
                this.J.sessionId
        }, this))
    };
    U.prototype.generateRequest = U.prototype.generateRequest;
    U.prototype.load = function(a) {
        return this.J.load(a)
    };
    U.prototype.load = U.prototype.load;
    U.prototype.update = function(a) {
        return this.J.update(a)
    };
    U.prototype.update = U.prototype.update;
    U.prototype.close = function() {
        this.h.I();
        return this.J.close()
    };
    U.prototype.close = U.prototype.close;
    U.prototype.remove = function() {
        return this.J.remove()
    };
    U.prototype.remove = U.prototype.remove;
    U.prototype.Lc = function(a) {
        var b = new J(a.type);
        Sa(b, a.Da);
        b.target = this;
        this.h.dispatchEvent(b)
    };
    U.prototype.addEventListener = function(a, b) {
        K(this.h, a, b)
    };
    U.prototype.addEventListener = U.prototype.addEventListener;
    U.prototype.removeEventListener = function(a, b) {
        dc(this.h, a, b)
    };
    U.prototype.removeEventListener = U.prototype.removeEventListener;
    U.prototype.dispatchEvent = function(a) {
        a.target = this;
        return this.h.dispatchEvent(a)
    };
    U.prototype.dispatchEvent = U.prototype.dispatchEvent;
    var Pe = D("cast");
    v("cast.receiver.logger", Pe);
    v("cast.receiver.LoggerLevel", {
        DEBUG: 0,
        VERBOSE: 500,
        INFO: 800,
        WARNING: 900,
        ERROR: 1E3,
        NONE: 1500
    });
    Pe.Rc = function(a) {
        Pe && Pe.kc(Cb(a))
    };
    Pe.setLevelValue = Pe.Rc;
    if (Pe) {
        var Qe = 1E3;
        if (document.currentScript) {
            var Re = document.currentScript.src;
            if (Re.includes("/df/cast/sdk/") || Re.includes("/staging/cast/sdk/")) Qe = 800
        }
        var Se = parseInt($c("log-level-cast-receiver"), 10);
        Pe.Rc(Se || Qe)
    };
    var Te = function() {
        this.R = [];
        this.U = []
    };
    Te.prototype.enqueue = function(a) {
        this.U.push(a)
    };
    var Ue = function(a) {
        0 == a.R.length && (a.R = a.U, a.R.reverse(), a.U = []);
        return a.R.pop()
    };
    g = Te.prototype;
    g.Ub = function() {
        return 0 == this.R.length && 0 == this.U.length
    };
    g.clear = function() {
        this.R = [];
        this.U = []
    };
    g.contains = function(a) {
        return 0 <= Da(this.R, a) || 0 <= Da(this.U, a)
    };
    g.remove = function(a) {
        var b;
        b = this.R;
        var c = Ea(b, a);
        0 <= c ? (Ha(b, c), b = !0) : b = !1;
        return b || Ia(this.U, a)
    };
    g.tc = function() {
        for (var a = [], b = this.R.length - 1; 0 <= b; --b) a.push(this.R[b]);
        for (var c = this.U.length, b = 0; b < c; ++b) a.push(this.U[b]);
        return a
    };
    var Ve = function(a) {
        this.itemId = a;
        this.customData = this.activeTrackIds = this.preloadTime = this.playbackDuration = this.startTime = this.autoplay = this.media = void 0
    };
    v("cast.receiver.media.QueueItem", Ve);
    Ve.prototype.clone = function(a) {
        var b = new Ve(this.itemId);
        b.autoplay = this.autoplay;
        b.startTime = this.startTime;
        b.playbackDuration = this.playbackDuration;
        b.preloadTime = this.preloadTime;
        b.activeTrackIds = this.activeTrackIds;
        b.customData = this.customData;
        if (!m(a) || a) b.media = this.media;
        return b
    };
    var V = function(a, b, c) {
        this.a = a;
        this.Y = b;
        this.j = c
    };
    v("cast.receiver.MediaQueue", V);
    V.prototype.rc = function(a) {
        if (!m(a) || a) return this.a;
        for (var b = [], c = 0; c < this.a.length; c++) b.push(this.a[c].clone(a));
        return b
    };
    V.prototype.getItems = V.prototype.rc;
    V.prototype.Id = function() {
        return this.a.length
    };
    V.prototype.getLength = V.prototype.Id;
    V.prototype.Md = function() {
        return this.Y
    };
    V.prototype.getRepeatMode = V.prototype.Md;
    V.prototype.Gd = function() {
        if (0 > this.j) return null;
        var a = this.a[this.j].itemId;
        Ba(a);
        return a
    };
    V.prototype.getCurrentItemId = V.prototype.Gd;
    V.prototype.Ea = function() {
        return 0 > this.j ? null : this.a[this.j]
    };
    V.prototype.getCurrentItem = V.prototype.Ea;
    var We = function(a, b) {
            for (var c = [], d = 0; d < b.length; d++)
                for (var e = 0; e < a.a.length; e++)
                    if (b[d] == a.a[e].itemId) {
                        c.push(b[d]);
                        break
                    }
            return c
        },
        Xe = function(a) {
            return "REPEAT_ALL_AND_SHUFFLE" == a.Y
        },
        Ye = function(a, b) {
            for (var c = 0; c < a.a.length; c++)
                if (b == a.a[c].itemId) return c;
            return -1
        },
        Ze = function(a) {
            return "REPEAT_ALL_AND_SHUFFLE" == a.Y || "REPEAT_ALL" == a.Y
        };
    V.prototype.reset = function() {
        this.j = -1
    };
    var $e = function(a, b) {
            b = Ye(a, b);
            if (-1 == b || a.j == b) return !1;
            a.j = b;
            return !0
        },
        bf = function(a, b, c) {
            if (0 > a.j) return "QUEUE_ENDED";
            b = a.j + b;
            var d = !1;
            b >= a.a.length ? (b = Ze(a) ? b % a.a.length : -1, d = !0) : 0 > b && (b = Ze(a) ? a.a.length + (b + 1) % a.a.length - 1 : 0, d = !0);
            c && (a.j = b);
            return -1 == b ? "QUEUE_ENDED" : d ? Xe(a) ? (af(a), "QUEUE_SHUFFLED") : "QUEUE_LOOP" : "QUEUE_ACTIVE"
        },
        af = function(a) {
            var b = a.a.length,
                c, d;
            if (!(3 > a.a.length))
                for (; 0 < b;) d = Math.floor(Math.random() * b), --b, c = a.a[d], a.a[d] = a.a[b], a.a[b] = c
        };
    var cf = function(a) {
        var b = this;
        this.Dc = a;
        this.$ = null;
        this.uc = "sdr";
        this.Jc = function() {
            b.Dc()
        }
    };
    var W = function(a, b, c) {
        var d = this,
            e = P.ca();
        Ud.ca();
        this.na = c || "local";
        this.wb = e.Oa("urn:x-cast:com.google.cast.media", "JSON");
        this.fa = 0;
        this.Ya = this.la = null;
        this.ae = 1;
        this.ue = b || 15;
        this.Vc = this.zc = this.Ga = this.ka = this.i = this.f = null;
        this.ea = !1;
        this.o = this.da = this.s = null;
        this.tb = !0;
        this.La = null;
        this.Jb = this.bd.bind(this);
        this.g = null;
        this.Gb = !1;
        this.sa = null;
        this.Yb = 1;
        this.Cb = -1;
        this.vb = new Te;
        this.Ta = !1;
        this.customizedStatusCallback = this.fd;
        this.onLoad = this.od;
        this.onPlay = this.sd;
        this.onPlayAgain = this.rd;
        this.onSeek = this.yd;
        this.onPause = this.qd;
        this.onStop = this.Ad;
        this.onSetVolume = this.zd;
        this.onEditTracksInfo = this.jd;
        this.onEditAudioTracks = this.hd;
        this.onQueueLoad = this.vd;
        this.onQueueInsert = this.ud;
        this.onQueueUpdate = this.oc;
        this.onQueueRemove = this.wd;
        this.onQueueReorder = this.xd;
        this.onMetadataLoaded = this.pd;
        this.onLoadMetadataError = this.nd;
        this.onEnded = this.kd;
        this.onQueueEnded = this.td;
        this.onAbort = n;
        this.onError = this.ld;
        this.onMediaStatus = this.onLocalRequestError = n;
        this.onCancelPreload = this.onPreload =
            this.onPrecache = null;
        this.onGetStatus = this.md;
        this.hb = new cf(this.m.bind(this, !1));
        e.bc = function(a) {
            var b = d.hb;
            b.uc = a;
            b.Dc()
        };
        this.h = new L;
        this.Sc(a);
        this.wb.onMessage = this.cc.bind(this);
        this.La = Sc(this.Jb, 1E3)
    };
    v("cast.receiver.MediaManager", W);
    W.prototype.ba = function() {
        return "MediaManager"
    };
    W.prototype.Jd = function() {
        return this.i ? this.i.media || null : null
    };
    W.prototype.getMediaInformation = W.prototype.Jd;
    W.prototype.Kd = function() {
        return this.g
    };
    W.prototype.getMediaQueue = W.prototype.Kd;
    W.prototype.Sd = function(a, b, c, d, e) {
        a = new df(a);
        a.insertBefore = null != b ? b : void 0;
        a.currentItemIndex = null != c ? c : void 0;
        a.currentItemId = null != d ? d : void 0;
        a.currentTime = null != e ? e : void 0;
        a.type = "QUEUE_INSERT";
        ef(this, this.na, a)
    };
    W.prototype.insertQueueItems = W.prototype.Sd;
    W.prototype.fe = function(a, b, c) {
        a = new ff(a);
        a.currentItemId = null != b ? b : void 0;
        a.currentTime = null != c ? c : void 0;
        a.type = "QUEUE_REMOVE";
        ef(this, this.na, a)
    };
    W.prototype.removeQueueItems = W.prototype.fe;
    W.prototype.me = function(a, b, c) {
        b = !m(b) || b;
        if (c && !b) throw Error("No broadcast call but status customData has been provided");
        this.i && (this.i.media = a);
        b && this.m(!0, null, c)
    };
    W.prototype.setMediaInformation = W.prototype.me;
    var gf = function(a) {
            for (var b = 0; b < a.length; b++)
                if (!m(a[b].trackId) || !m(a[b].type)) return !1;
            return !0
        },
        hf = function(a, b) {
            if (!b) return !0;
            if (!a) return !1;
            a = ia(a);
            for (var c = a.next(); !c.done; c = a.next())
                if (c = c.value, "TEXT" == c.type && m(c.language) && ra(c.language, b)) return !0;
            return !1
        },
        jf = function(a, b) {
            if (!b || 0 == b.length) return !0;
            if (!a || b.length > a.length) return G(X, "Too many track IDs"), !1;
            for (var c = 0, d = 0, e = 0; e < b.length; e++) {
                for (var f = !1, h = 0; h < a.length; h++)
                    if (b[e] == a[h].trackId) {
                        f = !0;
                        break
                    }
                if (!f) return G(X, "Track ID does not exist: " +
                    b[e]), !1;
                "AUDIO" == a[h].type ? d++ : "VIDEO" == a[h].type && c++;
                if (1 < d || 1 < c) return G(X, "Maximum one active video and one active audio track supported"), !1
            }
            return !0
        },
        ef = function(a, b, c) {
            c.mediaSessionId = a.fa;
            a.cc(new id("message", b, c))
        };
    W.prototype.cc = function(a) {
        var b = a.data,
            c = b.type;
        if (!this.Ta || this.vb.Ub() && "LOAD" == c) {
            a = a.senderId;
            var d;
            d = b.type;
            var e = b.requestId;
            if (q(e) && e == Math.floor(e)) {
                var f = !1;
                void 0 != b.mediaSessionId && b.mediaSessionId != this.fa ? (G(X, "Invalid media session ID: " + b.mediaSessionId + "  does not match the expected ID: " + this.fa), f = !0) : "LOAD" != d && "PLAY_AGAIN" != d && "GET_STATUS" != d && "QUEUE_LOAD" != d && "PRECACHE" != d && (m(b.mediaSessionId) ? "IDLE" == kf(this) && (G(X, "Unexpected command, player is in IDLE state so the media session ID is not valid yet"),
                    f = !0) : (G(X, "Invalid media session ID, it is undefined"), f = !0));
                f ? (this.wa(a, e, "INVALID_REQUEST", "INVALID_MEDIA_SESSION_ID"), d = !1) : (E(X, C, "MediaManager message received"), d = !0)
            } else G(X, "Ignoring request, requestId is not an integer: " + e), d = !1;
            if (d) {
                d = b.requestId;
                delete b.type;
                e = null;
                switch (c) {
                    case "LOAD":
                        this.Gb = !1;
                        e = lf(this, a, b, !0);
                        break;
                    case "GET_STATUS":
                        H(X, "Dispatching MediaManager getStatus event");
                        b = new Y("getstatus", b, a);
                        if (this.onGetStatus) this.onGetStatus(b);
                        this.c(b);
                        e = null;
                        break;
                    case "PLAY":
                        H(X,
                            "Dispatching MediaManager play event");
                        b = new Y("play", b, a);
                        if (this.onPlay) this.onPlay(b);
                        this.c(b);
                        e = null;
                        break;
                    case "PLAY_AGAIN":
                        H(X, "Dispatching MediaManager play again event");
                        b = new Y("playagain", b, a);
                        if (this.onPlayAgain) this.onPlayAgain(b);
                        this.c(b);
                        e = null;
                        break;
                    case "SEEK":
                        if (m(b.currentTime) || m(b.relativeTime)) {
                            H(X, "Dispatching MediaManager seek event");
                            b = new Y("seek", b, a);
                            if (this.onSeek) this.onSeek(b);
                            this.c(b);
                            e = null
                        } else G(X, "currentTime or relativeTime is required"), e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        break;
                    case "STOP":
                        H(X, "Dispatching MediaManager stop event");
                        b = new Y("stop", b, a);
                        if (this.onStop) this.onStop(b);
                        this.c(b);
                        e = null;
                        break;
                    case "PAUSE":
                        H(X, "Dispatching MediaManager pause event");
                        b = new Y("pause", b, a);
                        if (this.onPause) this.onPause(b);
                        this.c(b);
                        e = null;
                        break;
                    case "SET_VOLUME":
                        if (b.volume && (m(b.volume.level) || m(b.volume.muted)))
                            if (void 0 != b.volume.level && 0 > b.volume.level || 1 < b.volume.level) G(X, "volume level is invalid"), e = {
                                type: "INVALID_REQUEST",
                                reason: "INVALID_PARAMS"
                            };
                            else {
                                H(X, "Dispatching MediaManager setvolume event");
                                b = new Y("setvolume", b, a);
                                if (this.onSetVolume) this.onSetVolume(b);
                                this.c(b);
                                e = null
                            } else G(X, "volume is invalid"), e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        break;
                    case "EDIT_TRACKS_INFO":
                        H(X, "Dispatching MediaManager editTracksInfo event");
                        if (jf(this.i.media.tracks, b.activeTrackIds)) {
                            c = new Y("edittracksinfo", b, a);
                            b.textTrackStyle && (this.i.media.textTrackStyle = b.textTrackStyle);
                            b.activeTrackIds && (this.o = b.activeTrackIds);
                            if (this.onEditTracksInfo) this.onEditTracksInfo(c);
                            this.c(c);
                            e = null
                        } else G(X, "Invalid track info"), e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        break;
                    case "EDIT_AUDIO_TRACKS":
                        H(X, "Dispatching MediaManager editAudioTracks event");
                        b = new Y("editaudiotracks", b, a);
                        if (this.onEditAudioTracks) this.onEditAudioTracks(b);
                        this.c(b);
                        e = null;
                        break;
                    case "QUEUE_LOAD":
                        this.Gb = !0;
                        H(X, "Dispatching MediaManager queueLoad event");
                        c = !1;
                        if (m(b.startIndex) && (!q(b.startIndex) || 0 > b.startIndex)) G(X, "Invalid startIndex"), e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        else {
                            e = (b.startIndex || 0) + 1;
                            if (!b.items || b.items.length < e) G(X, "Invalid number of items"), c = !0;
                            else if (b.repeatMode && !yd(b.repeatMode)) G(X, "Invalid repeatMode"), c = !0;
                            else
                                for (e = 0; e < b.items.length; e++) b.items[e].media ? m(b.items[e].itemId) ? (G(X, "ItemId is not undefined, element at index: " + e), c = !0) : b.items[e].itemId = this.Yb++ : (G(X, "Media is mandatory, missing in element at index: " + e), c = !0);
                            if (c) e = {
                                type: "INVALID_REQUEST",
                                reason: "INVALID_PARAMS"
                            };
                            else {
                                b.items = mf(b.items);
                                b = new Y("queueload", b, a);
                                if (this.onQueueLoad) this.onQueueLoad(b);
                                this.c(b);
                                e = null
                            }
                        }
                        break;
                    case "QUEUE_INSERT":
                        this.Gb = !0;
                        H(X, "Dispatching MediaManager queueInsert event");
                        c = !1;
                        if (this.g)
                            if (b.items && 0 != b.items.length)
                                if (m(b.currentItemId) && m(b.currentItemIndex)) G(X, "Maximum one currentItem must be provided"), c = !0;
                                else if (m(b.currentItemIndex) && (!q(b.currentItemIndex) || 0 > b.currentItemIndex || b.currentItemIndex >= b.items.length)) G(X, "Invalid currentItemIndex"), c = !0;
                        else if (m(b.currentItemId) && (!q(b.currentItemId) || 0 > b.currentItemId)) G(X, "Invalid currentItemId"), c = !0;
                        else
                            for (e = 0; e < b.items.length; e++)
                                if (q(b.items[e].itemId)) {
                                    G(X, "Item contains an itemId at index: " + e);
                                    c = !0;
                                    break
                                } else b.items[e].itemId = this.Yb++;
                        else G(X, "No items to insert"), c = !0;
                        else G(X, "Queue does not exist"), c = !0;
                        if (c) e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        else {
                            b.items = mf(b.items);
                            b = new Y("queueinsert", b, a);
                            if (this.onQueueInsert) this.onQueueInsert(b);
                            this.c(b);
                            e = null
                        }
                        break;
                    case "QUEUE_UPDATE":
                        H(X, "Dispatching MediaManager queueUpdate event");
                        c = !1;
                        this.g ? b.items && !nf(b.items) ? c = !0 : b.repeatMode && !yd(b.repeatMode) && (G(X, "Invalid repeatMode"), c = !0) : (G(X, "Queue does not exist"), c = !0);
                        if (c) e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        else {
                            if (b.items && 0 < b.items.length) {
                                for (var c = this.g, e = b.items, f = [], h = 0; h < e.length; h++)
                                    for (var t = 0; t < c.a.length; t++)
                                        if (e[h].itemId == c.a[t].itemId) {
                                            f.push(e[h]);
                                            break
                                        }
                                b.items = mf(f)
                            }
                            b = new Y("queueupdate", b, a);
                            if (this.onQueueUpdate) this.onQueueUpdate(b);
                            this.c(b);
                            e = null
                        }
                        break;
                    case "QUEUE_REMOVE":
                        H(X, "Dispatching MediaManager queueRemove event");
                        c = !1;
                        this.g ? b.itemIds && 0 != b.itemIds.length ? of(b.itemIds) || (c = !0) : (G(X, "No itemIds to remove"), c = !0) : (G(X, "Queue does not exist"), c = !0);
                        if (c) e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        else {
                            b.itemIds && (b.itemIds = We(this.g, b.itemIds));
                            b = new Y("queueremove", b, a);
                            if (this.onQueueRemove) this.onQueueRemove(b);
                            this.c(b);
                            e = null
                        }
                        break;
                    case "QUEUE_REORDER":
                        H(X, "Dispatching MediaManager queueReorder event");
                        c = !1;
                        this.g ? b.itemIds && 0 != b.itemIds.length ? of(b.itemIds) ? m(b.insertBefore) && 0 <= Da(b.itemIds, b.insertBefore) &&
                            (G(X, "insertBefore can not be one of the reordered items"), c = !0) : c = !0 : (G(X, "No itemIds to reorder"), c = !0) : (G(X, "Queue does not exist"), c = !0);
                        if (c) e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_PARAMS"
                        };
                        else {
                            b.itemIds && (b.itemIds = We(this.g, b.itemIds));
                            b = new Y("queuereorder", b, a);
                            if (this.onQueueReorder) this.onQueueReorder(b);
                            this.c(b);
                            e = null
                        }
                        break;
                    case "PRECACHE":
                        b = new Y("precache", b, "__broadcast__");
                        if (this.onPrecache) this.onPrecache(b);
                        this.c(b);
                        break;
                    default:
                        G(X, "Unexpected message type: " + c), e = {
                            type: "INVALID_REQUEST",
                            reason: "INVALID_COMMAND"
                        }
                }
                e && (G(X, "Sending error: " + e.type + " " + e.reason), this.onLocalRequestError && a == this.na ? (e.requestId = d, this.onLocalRequestError(e)) : this.wa(a, d, e.type, e.reason))
            }
        } else this.vb.enqueue(a)
    };
    var kf = function(a) {
            if (!a.i) return "IDLE";
            var b = a.f.getState();
            return "PLAYING" == b && a.ea ? "BUFFERING" : b
        },
        pf = function(a, b, c, d) {
            var e = {
                    type: "MEDIA_STATUS"
                },
                f = a.s && a.s.message.media || null;
            if (!a.i && !a.ka && !f) return e.status = [], e;
            var h = {
                mediaSessionId: a.fa,
                playbackRate: a.ae,
                playerState: kf(a),
                currentTime: a.f.getCurrentTimeSec(),
                supportedMediaCommands: a.ue,
                volume: a.f.getVolume()
            };
            a.o && (h.activeTrackIds = a.o);
            a.sa && (h.preloadedItemId = a.sa);
            var t = a.hb,
                t = t.$ ? t.$.videoWidth : 0,
                y = a.hb,
                y = y.$ ? y.$.videoHeight : 0;
            0 < t &&
                0 < y && (h.videoInfo = new vd(t, y, a.hb.uc));
            if (a.i) b && (h.media = a.i.media || void 0), h.currentItemId = a.i.itemId;
            else if (a.ka && (b && (h.media = a.ka.media || void 0), h.currentItemId = a.ka.itemId, a.ka = null), a.g && (t = a.g.Ea())) h.loadingItemId = t.itemId;
            "IDLE" == h.playerState ? (a.da && (h.idleReason = a.da), f && (h.extendedStatus = new wd("LOADING", f))) : a.da = null;
            void 0 != c && (h.customData = c);
            a.g && (d && (h.items = a.g.rc(b)), h.repeatMode = a.g.Y);
            a.customizedStatusCallback ? (a = a.customizedStatusCallback(h), null == a ? e = null : e.status = [a]) :
                e.status = [h];
            return e
        },
        qf = function(a) {
            null != a.Ya && (l.clearTimeout(a.Ya), a.Ya = null)
        },
        rf = function(a) {
            var b = a.f.getCurrentTimeSec();
            a.Ga = b;
            a.zc = b;
            a.Vc = Date.now();
            null != a.La && l.clearTimeout(a.La);
            a.La = Sc(a.Jb, 1E3)
        };
    W.prototype.bd = function() {
        this.La = Sc(this.Jb, 1E3);
        var a = kf(this);
        if ("IDLE" != a && "PAUSED" != a) {
            a = this.Ga;
            this.Ga = this.f.getCurrentTimeSec();
            var b = this.ea;
            this.ea = 100 > 1E3 * (this.Ga - a);
            b != this.ea ? (E(X, C, "Buffering state changed, isPlayerBuffering: " + this.ea + " old time: " + a + " current time: " + this.Ga), this.m(!1)) : this.ea || (a = 1E3 * (this.Ga - this.zc) - (Date.now() - this.Vc), 1E3 < a || -1E3 > a ? (E(X, C, "Time drifted: " + a), this.m(!1)) : this.i && this.g && (a = this.g, (a = 0 > a.j ? null : "REPEAT_SINGLE" == a.Y ? a.a[a.j] : a.j + 1 >= a.a.length &&
                (Xe(a) || "REPEAT_OFF" == a.Y) ? null : a.a[(a.j + 1) % a.a.length]) && q(a.preloadTime) && this.i.media && !this.s && sf(this, a.preloadTime) && this.sa != a.itemId && (this.onPreload ? (Ba(a.itemId), b = new tf(a.itemId), b.requestId = 0, b.mediaSessionId = this.fa, b.autoplay = a.autoplay, b.currentTime = a.startTime, b.customData = a.customData || null, b.activeTrackIds = a.activeTrackIds, b.media = a.media, b = new Y("preload", b, ""), Ba(a.itemId), this.sa = a.itemId, H(X, "Sending preload event: " + JSON.stringify(b)), this.onPreload(b) && this.m(!1)) : H(X, "Not sending preload event"))))
        }
    };
    W.prototype.m = function(a, b, c, d) {
        if (this.f) {
            if (E(X, C, "Sending broadcast status message"), a = pf(this, a, c, d), null != a) {
                if (this.onMediaStatus && a.status) this.onMediaStatus(a.status[0] || null);
                a.requestId = b || 0;
                this.wb.mc(a);
                rf(this)
            }
        } else G(X, "Not sending broadcast status message, state is invalid")
    };
    W.prototype.broadcastStatus = W.prototype.m;
    W.prototype.ke = function(a) {
        E(X, C, "Setting IDLE reason: " + a);
        this.da = a
    };
    W.prototype.setIdleReason = W.prototype.ke;
    W.prototype.wa = function(a, b, c, d, e) {
        H(X, "Sending error message to " + a);
        var f = {};
        f.requestId = b;
        f.type = c;
        d && (f.reason = d);
        e && (f.customData = e);
        this.wb.send(a, f)
    };
    W.prototype.sendError = W.prototype.wa;
    W.prototype.Pc = function(a, b, c, d, e) {
        if (this.f) {
            if (E(X, C, "Sending status message to " + a), c = pf(this, c, d, e), null != c) {
                if (this.onMediaStatus && c.status) this.onMediaStatus(c.status[0] || null);
                c.requestId = b;
                this.wb.send(a, c);
                rf(this)
            }
        } else G(X, "State is invalid"), this.wa(a, b, "INVALID_PLAYER_STATE", null, d)
    };
    W.prototype.sendStatus = W.prototype.Pc;
    W.prototype.fd = function(a) {
        return a
    };
    var uf = function(a) {
        a.s = null;
        if (a.Ta)
            for (a.Ta = !1; !a.vb.Ub() && !a.Ta;) a.cc(Ue(a.vb))
    };
    W.prototype.load = function(a) {
        a.type = "LOAD";
        ef(this, this.na, a)
    };
    W.prototype.load = W.prototype.load;
    W.prototype.ee = function(a) {
        a.type = "QUEUE_LOAD";
        ef(this, this.na, a)
    };
    W.prototype.queueLoad = W.prototype.ee;
    var lf = function(a, b, c, d, e, f) {
        H(X, "Dispatching MediaManager load event");
        H(X, "Load message received:" + JSON.stringify(c));
        var h = !1;
        c.media ? c.media.tracks && !gf(c.media.tracks) ? (G(X, "Invalid tracks information"), h = !0) : c.activeTrackIds && !jf(c.media.tracks, c.activeTrackIds) && (h = !0) : (G(X, "Media is mandatory"), h = !0);
        if (h) return f && f(), {
            type: "INVALID_REQUEST",
            reason: "INVALID_PARAMS"
        };
        a.s ? a.jc("LOAD_CANCELLED") : a.i && (a.va("INTERRUPTED", !1), f = a.m.bind(a, !0));
        a.s = {
            senderId: b,
            message: c
        };
        f && f();
        a.o = c.activeTrackIds ||
            null;
        a.Ta = !0;
        d && (a.fa++, a.da = null, e ? a.g = e : (d = new Ve(a.Yb++), d.media = c.media, d.autoplay = c.autoplay, d.activeTrackIds = c.activeTrackIds, d.customData = c.customData, a.g = new V([d], "REPEAT_OFF", 0)));
        a.i = a.g && a.g.Ea();
        qf(a);
        a.la = c;
        a.tb && a.f.Gc && a.f.Gc(m(c.autoplay) ? c.autoplay : !0, 0 < c.currentTime ? c.currentTime : 0, a.i.playbackDuration, a.i.startTime);
        b = new Y("load", c, b);
        if (a.onLoad) a.onLoad(b);
        a.c(b);
        f || a.m(!0);
        return a.sa = null
    };
    W.prototype.od = function(a) {
        a = a.data;
        if (a.media && a.media.contentId) {
            var b = m(a.autoplay) ? a.autoplay : !0;
            a.media.tracks ? this.f.load(a.media.contentId, b, a.currentTime, {
                tracks: a.media.tracks,
                activeTrackIds: a.activeTrackIds,
                textTrackStyle: a.media.textTrackStyle
            }) : this.f.load(a.media.contentId, b, a.currentTime)
        }
    };
    W.prototype.Wd = function(a) {
        if (!this.s) return !1;
        a.tracks = a.tracks || void 0;
        if (a.tracks && !gf(a.tracks)) return G(X, "Invalid tracks information"), !1;
        if (a.activeTrackIds && !jf(a.tracks, a.activeTrackIds)) return G(X, "Invalid active tracks"), !1;
        this.o = a.activeTrackIds || null;
        if (this.g) {
            var b = this.g.Ea();
            b && b.media && (b.media.tracks = a.tracks, b.media.textTrackStyle = a.textTrackStyle || void 0)
        }
        this.f.load("", !1, void 0, a, !0);
        return !0
    };
    W.prototype.loadTracksInfo = W.prototype.Wd;
    W.prototype.Sc = function(a) {
        if (a != this.f) {
            this.f && (this.f.unregisterErrorCallback(), this.f.unregisterEndedCallback(), this.f.unregisterLoadCallback());
            this.f = (this.tb = a.getState ? !1 : !0) ? new Ge(X, a) : a;
            this.f.registerErrorCallback(this.$b.bind(this));
            this.f.registerEndedCallback(this.zb.bind(this));
            this.f.registerLoadCallback(this.dc.bind(this));
            var b = this.hb;
            b.$ && b.$.removeEventListener("resize", b.Jc);
            var c = null;
            a.tagName && "video" == a.tagName.toLowerCase() ? c = a : (a = document.getElementsByTagName("video"),
                1 == a.length && (c = a[0]));
            b.$ = c;
            b.$ && b.$.addEventListener("resize", b.Jc)
        }
    };
    W.prototype.setMediaElement = W.prototype.Sc;
    W.prototype.dc = function() {
        if (this.s)
            if (H(X, "Metadata loaded"), this.i && this.i.media && (this.i.media.duration = this.f.getDurationSec()), this.ea = !0, this.onMetadataLoaded) this.onMetadataLoaded(this.s);
            else uf(this)
    };
    W.prototype.pd = function(a) {
        this.tb && a.message && void 0 != a.message.currentTime && a.message.currentTime != this.f.getCurrentTimeSec() && this.f.seek(a.message.currentTime);
        this.Oc()
    };
    W.prototype.$b = function(a) {
        if (this.s)
            if (G(X, "Load metadata error: " + a), this.onLoadMetadataError) this.onLoadMetadataError(this.s);
            else uf(this);
        else if (this.onError) this.onError(a)
    };
    W.prototype.jc = function(a, b) {
        if (this.s) {
            a = a || "LOAD_FAILED";
            if (this.s.senderId == this.na) {
                if (this.onLocalRequestError) this.onLocalRequestError({
                    type: a
                })
            } else this.wa(this.s.senderId, this.s.message.requestId, a, null, b);
            uf(this)
        } else G(X, "Not sending LOAD error as there is no on going LOAD request")
    };
    W.prototype.sendLoadError = W.prototype.jc;
    W.prototype.Oc = function(a) {
        if (this.s) {
            var b = this.s.message.requestId;
            this.m(!0, b, a, 0 != b);
            uf(this)
        } else G(X, "Not sending status as there is no on going LOAD request")
    };
    W.prototype.sendLoadComplete = W.prototype.Oc;
    g = W.prototype;
    g.ld = function() {
        this.va("ERROR")
    };
    g.nd = function() {
        this.s && "" == this.s.senderId && this.s.message && 0 == this.s.message.requestId ? this.va("ERROR", !0) : (this.va("ERROR", !1), this.jc("LOAD_FAILED"))
    };
    g.zb = function() {
        if (this.onEnded) this.onEnded()
    };
    g.kd = function() {
        if (this.g) {
            var a = -1 != this.Cb ? this.Cb : void 0;
            this.Cb = -1;
            vf(this, "REPEAT_SINGLE" == this.g.Y ? 0 : 1, !1, a, void 0, void 0, void 0, "FINISHED")
        }
    };
    g.td = function(a, b) {
        this.va(a, !0, b)
    };
    var vf = function(a, b, c, d, e, f, h, t) {
        t = t || "INTERRUPTED";
        if (a.g && "QUEUE_ENDED" != bf(a.g, b, !1)) {
            var y = bf(a.g, b, !0);
            H(X, "After " + b + " jump, transition is: " + y);
            if (b = wf(a, a.g.Ea(), void 0, h)) {
                if (a.i && (a.o = null, a.da = t, a.ka = a.i, a.i = null, "QUEUE_SHUFFLED" == y && (f = !0), "INTERRUPTED" == a.da)) a.onAbort();
                lf(a, "", b, !1, void 0, a.m.bind(a, c, d, e, f))
            } else if (a.onQueueEnded) a.onQueueEnded(t, d)
        } else if (a.onQueueEnded) a.onQueueEnded(t, d)
    };
    g = W.prototype;
    g.md = function(a) {
        E(X, C, "onGetStatus");
        var b = a.data;
        E(X, C, "onGetStatus: " + JSON.stringify(b));
        var c = !0,
            d = !0;
        b.options && (b.options & 1 && (c = !1), b.options & 1 && (d = !1));
        this.Pc(a.senderId, a.data.requestId, c, null, d)
    };
    g.sd = function(a) {
        E(X, C, "onPlay");
        this.f.play();
        this.m(!1, a.data.requestId)
    };
    g.rd = function(a) {
        E(X, C, "onPlayAgain");
        this.i ? (this.f.seek(0), this.f.play(), this.m(!1, a.data.requestId)) : this.la && (this.la.type = "LOAD", this.la.autoplay = !0, ef(this, this.na, this.la))
    };
    g.yd = function(a) {
        a = a.data;
        E(X, C, "onSeek: " + JSON.stringify(a));
        var b = m(a.relativeTime) ? this.f.getCurrentTimeSec() + a.relativeTime : a.currentTime;
        Ba(b);
        this.f.seek(b, a.resumeState);
        "PAUSED" != this.f.getState() && (this.ea = !0);
        this.f.getCurrentTimeSec() < this.f.getDurationSec() ? this.m(!1, a.requestId) : this.Cb = a.requestId
    };
    g.Ad = function(a) {
        this.va("CANCELLED", !0, a.data.requestId)
    };
    g.va = function(a, b, c, d) {
        var e = this;
        b = !m(b) || b;
        if ((d || c) && !b) throw Error("customData and requestId should only be provided in broadcast mode");
        this.i ? (this.g = null, this.f.reset(), this.o = null, a && (this.da = a), this.ka = this.i, this.i = null, b && this.m(!1, c, d, void 0)) : H(X, "Nothing to reset, Media is already null");
        this.la && (qf(this), this.Ya = Sc(function() {
            e.la = null;
            e.Ya = null
        }, 9E5));
        if (a && "INTERRUPTED" == a) this.onAbort()
    };
    W.prototype.resetMediaElement = W.prototype.va;
    W.prototype.qd = function(a) {
        this.f.pause();
        this.m(!1, a.data.requestId)
    };
    W.prototype.zd = function(a) {
        a = a.data;
        this.f.setVolume(a.volume);
        this.m(!1, a.requestId)
    };
    W.prototype.jd = function(a) {
        var b = a.data;
        hf(this.i.media.tracks, b.language) ? (a = {
            activeTrackIds: b.activeTrackIds,
            language: b.language,
            textTrackStyle: b.textTrackStyle
        }, this.f.editTracksInfo && (this.o = this.f.editTracksInfo(a)), this.m(b.textTrackStyle ? !0 : !1, b.requestId)) : (G(X, "Invalid track language"), this.wa(a.senderId, b.requestId, "INVALID_REQUEST", "LANGUAGE_NOT_SUPPORTED"))
    };
    W.prototype.hd = function() {};
    var of = function(a) {
            if (2 > a.length) return !0;
            for (var b = 0; b < a.length; b++)
                for (var c = b + 1; c < a.length; c++)
                    if (a[b] == a[c]) return G(X, "Duplicate itemId: " + a[b] + "at positions:" + b + " " + c), !1;
            return !0
        },
        nf = function(a) {
            for (var b = 0; b < a.length; b++) {
                if (!q(a[b].itemId)) return G(X, "Invalid itemId at position: " + b), !1;
                for (var c = b + 1; c < a.length; c++) {
                    if (!q(a[c].itemId)) return G(X, "Invalid itemId at position: " + c), !1;
                    if (a[b].itemId == a[c].itemId) return G(X, "Duplicate itemId: " + a[b].itemId + "at positions:" + b + " " + c), !1
                }
            }
            return !0
        },
        mf = function(a) {
            for (var b = [], c = 0; c < a.length; c++) {
                var d = new Ve(a[c].itemId);
                d.media = a[c].media;
                d.autoplay = a[c].autoplay;
                d.startTime = a[c].startTime;
                d.playbackDuration = a[c].playbackDuration;
                d.preloadTime = a[c].preloadTime;
                d.activeTrackIds = a[c].activeTrackIds;
                d.customData = a[c].customData;
                b.push(d)
            }
            return b
        },
        wf = function(a, b, c, d) {
            if (!b) return null;
            var e = new xf;
            e.requestId = c || 0;
            e.mediaSessionId = a.fa;
            e.type = "LOAD";
            e.autoplay = b.autoplay;
            e.currentTime = m(d) ? d : b.startTime;
            e.activeTrackIds = b.activeTrackIds;
            e.customData =
                b.customData || null;
            e.media = b.media;
            return e
        },
        sf = function(a, b) {
            if (a.i.media.duration - a.f.getCurrentTimeSec() <= b) return !0;
            if (null == a.sa) return !1;
            a.sa = null;
            if (!a.onCancelPreload) return !1;
            b = new Z;
            b.requestId = 0;
            b.mediaSessionId = a.fa;
            b = new Y("cancelpreload", b, "");
            H(X, "Sending cancel preload event: " + JSON.stringify(b));
            a.onCancelPreload(b) && a.m(!1);
            return !1
        };
    g = W.prototype;
    g.vd = function(a) {
        var b = a.data,
            c = new V(b.items, b.repeatMode || "REPEAT_OFF", b.startIndex || 0);
        (b = wf(this, c.Ea(), b.requestId, b.currentTime)) ? lf(this, a.senderId, b, !0, c): G(X, "Queue Load request is invalid")
    };
    g.ud = function(a) {
        a = a.data;
        H(X, "Queue insert data: " + JSON.stringify(a));
        var b = !1;
        m(a.currentItemId) && (b = $e(this.g, a.currentItemId));
        m(a.currentItemIndex) && (b = !0);
        var c = this.g,
            d = a.items,
            e = a.insertBefore,
            f = a.currentItemIndex,
            e = q(e) ? Ye(c, e) : c.a.length,
            e = -1 == e ? c.a.length : e;
        pa(La, c.a, e, 0).apply(null, d);
        m(f) ? c.j = e + f : c.j >= e && (c.j += d.length);
        b ? vf(this, 0, !0, a.requestId, a.customData, !0, a.currentTime) : this.m(!0, a.requestId, a.customData, !0)
    };
    g.oc = function(a) {
        var b = a.data;
        if (this.Gb) {
            H(X, "Queue update data: " + JSON.stringify(b));
            var c = !1;
            a = !1;
            q(b.currentItemId) && (a = $e(this.g, b.currentItemId));
            q(b.jump) && (a = !0);
            b.repeatMode && (this.g.Y = b.repeatMode);
            if (b.items && 0 < b.items.length) {
                for (var c = this.g, d = b.items, e = 0; e < d.length; e++)
                    for (var f = 0; f < c.a.length; f++) d[e].itemId == c.a[f].itemId && (c.a[f] = d[e]);
                c = !0
            }
            b.shuffle && (af(this.g), a = !0);
            a ? vf(this, b.jump || 0, c, b.requestId, b.customData, c, b.currentTime) : this.m(c, b.requestId, b.customData, c)
        } else a = a.senderId,
            "__inject__" == a && this.onQueueUpdate == this.oc ? this.wa(a, b.requestId, "INVALID_REQUEST", "INVALID_COMMAND") : (H(X, "QUEUE_UPDATE request ignored"), this.m(!1, b.requestId))
    };
    g.wd = function(a) {
        a = a.data;
        H(X, "Queue remove data: " + JSON.stringify(a));
        var b = !1;
        q(a.currentItemId) && (b = $e(this.g, a.currentItemId));
        if (a.itemIds && 0 != a.itemIds.length) {
            if (!b) {
                for (var b = this.g, c = a.itemIds, d = !1, e = 0; e < c.length; e++)
                    for (var f = 0; f < b.a.length; f++)
                        if (c[e] == b.a[f].itemId) {
                            b.a.splice(f, 1);
                            b.j == f ? d = !0 : b.j > f && b.j--;
                            break
                        }
                b.j >= b.a.length && (b.j = Ze(b) ? 0 : -1, Xe(b) && 0 == b.j && af(b));
                b = d
            }
            b ? vf(this, 0, !1, a.requestId, a.customData, !0, a.currentTime) : this.m(!1, a.requestId, a.customData, !0)
        } else G(X, "No itemIds to remove")
    };
    g.xd = function(a) {
        a = a.data;
        H(X, "Queue reorder data: " + JSON.stringify(a));
        var b = !1,
            c = !1;
        q(a.currentItemId) && (c = $e(this.g, a.currentItemId));
        if (a.itemIds && 0 < a.itemIds.length) {
            var b = this.g,
                d = a.itemIds,
                e = a.insertBefore;
            if (d && 0 != d.length) {
                for (var f = b.a[b.j].itemId, h = m(e) ? e : -1, e = b.a.length - d.length, t = [], y = -1 == h ? !0 : !1, F = 0; F < b.a.length; F++) 0 <= Da(d, b.a[F].itemId) ? y || b.a[F].itemId != d[0] || (e = t.length) : (t.push(b.a[F]), h == b.a[F].itemId && (e = t.length - 1, y = !0));
                h = [];
                for (y = 0; y < d.length; y++) {
                    a: {
                        for (F = 0; F < b.a.length; F++)
                            if (d[y] ==
                                b.a[F].itemId) {
                                F = b.a[F];
                                break a
                            }
                        F = null
                    }
                    h.push(F)
                }
                pa(La, t, e, 0).apply(null, h);
                b.a = t;
                m(f) && $e(b, f)
            }
            b = !0
        }
        c ? vf(this, 0, !1, a.requestId, a.customData, b, a.currentTime) : this.m(!1, a.requestId, a.customData, b)
    };
    g.addEventListener = function(a, b) {
        K(this.h, a, b)
    };
    W.prototype.addEventListener = W.prototype.addEventListener;
    W.prototype.removeEventListener = function(a, b) {
        dc(this.h, a, b)
    };
    W.prototype.removeEventListener = W.prototype.removeEventListener;
    W.prototype.c = function(a) {
        a.target = this;
        return hc(this.h, a)
    };
    W.prototype.dispatchEvent = function(a) {
        return this.c(a)
    };
    W.prototype.dispatchEvent = W.prototype.dispatchEvent;
    var X = D("cast.receiver.MediaManager");
    W.EventType = {
        LOAD: "load",
        STOP: "stop",
        PAUSE: "pause",
        PLAY: "play",
        PLAY_AGAIN: "playagain",
        SEEK: "seek",
        SET_VOLUME: "setvolume",
        GET_STATUS: "getstatus",
        EDIT_TRACKS_INFO: "edittracksinfo",
        EDIT_AUDIO_TRACKS: "editaudiotracks",
        QUEUE_LOAD: "queueload",
        QUEUE_INSERT: "queueinsert",
        QUEUE_UPDATE: "queueupdate",
        QUEUE_REMOVE: "queueremove",
        QUEUE_REORDER: "queuereorder",
        PRECACHE: "precache",
        PRELOAD: "preload",
        CANCEL_PRELOAD: "cancelpreload"
    };
    var Y = function(a, b, c) {
        J.call(this, a);
        this.data = b;
        this.senderId = c
    };
    k(Y, J);
    W.Event = Y;
    var Z = function() {
        this.type = void 0;
        this.requestId = 0;
        this.mediaSessionId = void 0;
        this.customData = null
    };
    W.RequestData = Z;
    var xf = function() {
        Z.call(this);
        this.media = new td;
        this.autoplay = !1;
        this.currentTime = 0;
        this.activeTrackIds = void 0
    };
    k(xf, Z);
    W.LoadRequestData = xf;
    var tf = function(a) {
        xf.call(this);
        this.itemId = a
    };
    k(tf, xf);
    W.PreloadRequestData = tf;
    var yf = function(a) {
        Z.call(this);
        this.data = a
    };
    k(yf, Z);
    W.PrecacheRequestData = yf;
    var zf = function() {
        Z.call(this);
        this.volume = new ud
    };
    k(zf, Z);
    W.VolumeRequestData = zf;
    var Af = function() {
        Z.call(this);
        this.isSuggestedLanguage = this.textTrackStyle = this.language = this.activeTrackIds = void 0
    };
    k(Af, Z);
    W.EditTracksInfoData = Af;
    var Bf = function() {
        Z.call(this);
        this.isSuggestedLanguage = this.language = this.activeTrackIds = void 0
    };
    k(Bf, Z);
    W.EditAudioTracksData = Bf;
    var Cf = function() {
        Z.call(this);
        this.resumeState = void 0;
        this.currentTime = 0;
        this.relativeTime = void 0
    };
    k(Cf, Z);
    W.SeekRequestData = Cf;
    var Df = function() {
        Z.call(this);
        this.options = void 0
    };
    k(Df, Z);
    W.GetStatusRequestData = Df;
    var Ef = function(a) {
        Z.call(this);
        this.repeatMode = void 0;
        this.items = a;
        this.currentTime = this.startIndex = void 0
    };
    k(Ef, Z);
    W.QueueLoadRequestData = Ef;
    var df = function(a) {
        Z.call(this);
        this.currentTime = this.currentItemId = this.currentItemIndex = this.insertBefore = void 0;
        this.items = a
    };
    k(df, Z);
    W.QueueInsertRequestData = df;
    var Ff = function() {
        Z.call(this);
        this.shuffle = this.repeatMode = this.items = this.jump = this.currentTime = this.currentItemId = void 0
    };
    k(Ff, Z);
    W.QueueUpdateRequestData = Ff;
    var ff = function(a) {
        Z.call(this);
        this.currentTime = this.currentItemId = void 0;
        this.itemIds = a
    };
    k(ff, Z);
    W.QueueRemoveRequestData = ff;
    var Gf = function(a) {
        Z.call(this);
        this.insertBefore = this.currentTime = this.currentItemId = void 0;
        this.itemIds = a
    };
    k(Gf, Z);
    W.QueueReorderRequestData = Gf;
    W.LoadInfo = function(a, b) {
        this.message = a;
        this.senderId = b
    };
}).call(window);