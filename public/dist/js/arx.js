/* ARX.JS v.4.1.1 */!function() {
    var a = !1, b = /xyz/.test(function() {}) ? /\b_super\b/ : /.*/;
    this.Class = function() {}, Class.extend = function(c) {
        function d() {
            !a && this.init && this.init.apply(this, arguments);
        }
        var e = this.prototype;
        a = !0;
        var f = new this();
        a = !1;
        for (var g in c) f[g] = "function" == typeof c[g] && "function" == typeof e[g] && b.test(c[g]) ? function(a, b) {
            return function() {
                var c = this._super;
                this._super = e[a];
                var d = b.apply(this, arguments);
                return this._super = c, d;
            };
        }(g, c[g]) : c[g];
        return d.prototype = f, d.prototype.constructor = d, d.extend = arguments.callee, 
        d;
    };
}(), function(a, b) {
    "use strict";
    var c = a(window), d = a(document), e = a("body"), f = null;
    f = b.module("arx", [ "ui.bootstrap", "ui.tree", "ui.utils", "ngAnimate", "smartTable.table" ]).config(function() {
        d.on("ready", function() {
            a("iframe.fullsize, .page-content iframe").length && Util.resize(function() {
                var b = a("iframe.fullsize, .page-content iframe");
                b.css({
                    height: e.outerHeight(),
                    width: b.parent().outerWidth()
                });
            }), a('[data-toggle="collapse"]').each(function() {
                var b = a(this), d = b.data("target") || b.attr("href"), e = a('[data-toggle="collapse"][href="' + d + '"], [data-toggle="collapse"][data-target="' + d + '"]');
                a(d).on("show.bs.collapse", function() {
                    e.closest(b.data("parent") || ".panel").addClass("open"), c.trigger("resize");
                }).on("hide.bs.collapse", function() {
                    e.closest(b.data("parent") || ".panel").removeClass("open"), c.trigger("resize");
                });
            }), a('[data-toggle="sidebar"]').on("click", function() {
                e.toggleClass("mini-sidebar"), c.trigger("resize");
            }), a(".collapse.in").each(function() {
                var b = "#" + a(this).attr("id");
                a('[data-toggle="collapse"][href="' + b + '"], [data-toggle="collapse"][data-target="' + b + '"]').closest(".panel").addClass("open");
            }), a(".tab-pane.active").each(function() {
                var b = a(this), c = a('[href="#' + b.attr("id") + '"]').parent();
                c.addClass("active");
            }), a("select.multiselect").each(function() {
                var b = a(this), c = b.data();
                b.multiselect({
                    buttonClass: c.buttonclass || "",
                    buttonWidth: "auto",
                    buttonContainer: '<div class="btn-group"></div>',
                    maxHeight: !1,
                    buttonText: function(b) {
                        if (0 == b.length) return 'None selected <span class="caret"></span>';
                        if (b.length > 3) return b.length + ' selected  <span class="caret"></span>';
                        var c = "";
                        return b.each(function() {
                            c += a(this).text() + ", ";
                        }), c.substr(0, c.length - 2) + ' <span class="caret"></span>';
                    }
                });
            }), a("select.select2").each(function() {
                var b = a(this), c = b.data();
                b.select2({
                    placeholder: c.placeholder || "",
                    minimumInputLength: c.minimum || 0,
                    maximumSelectionSize: c.maximum || 0
                });
            }), c.trigger("resize");
        });
    }).run(function() {
        console.log("-- angular arx initialized");
    });
}(window.jQuery, window.angular);