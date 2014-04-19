function Controller() {
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "indexhome";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    $.__views.tabGroup = Ti.UI.createTabGroup({
        id: "tabGroup"
    });
    $.__views.tabHome = Alloy.createController("home", {
        id: "tabHome"
    });
    $.__views.tabGroup.addTab($.__views.tabHome.getViewEx({
        recurse: true
    }));
    $.__views.recipestable = Alloy.createController("widgets", {
        id: "recipestable"
    });
    $.__views.recipestab = Ti.UI.createTab({
        window: $.__views.recipestable.getViewEx({
            recurse: true
        }),
        title: "Widgets",
        icon: "KS_nav_ui.png",
        id: "recipestab"
    });
    $.__views.tabGroup.addTab($.__views.recipestab);
    $.__views.items = Alloy.createController("places", {
        id: "items"
    });
    $.__views.recipestab = Ti.UI.createTab({
        window: $.__views.items.getViewEx({
            recurse: true
        }),
        title: "Places",
        icon: "KS_nav_ui.png",
        id: "recipestab"
    });
    $.__views.tabGroup.addTab($.__views.recipestab);
    $.__views.yingredients = Alloy.createController("workers", {
        id: "yingredients"
    });
    $.__views.recipestab1 = Ti.UI.createTab({
        window: $.__views.yingredients.getViewEx({
            recurse: true
        }),
        title: "Workers",
        icon: "KS_nav_ui.png",
        id: "recipestab1"
    });
    $.__views.tabGroup.addTab($.__views.recipestab1);
    $.__views.scanner = Alloy.createController("scanner", {
        id: "scanner"
    });
    $.__views.__alloyId5 = Ti.UI.createTab({
        window: $.__views.scanner.getViewEx({
            recurse: true
        }),
        id: "__alloyId5"
    });
    $.__views.tabGroup.addTab($.__views.__alloyId5);
    $.__views.tabGroup && $.addTopLevelView($.__views.tabGroup);
    exports.destroy = function() {};
    _.extend($, $.__views);
    Alloy.Globals.tabGroup = $.tabGroup;
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;