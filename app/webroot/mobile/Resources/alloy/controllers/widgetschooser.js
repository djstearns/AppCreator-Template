function Controller() {
    function __alloyId41() {
        __alloyId41.opts || {};
        var models = __alloyId40.models;
        var len = models.length;
        var rows = [];
        for (var i = 0; len > i; i++) {
            var __alloyId38 = models[i];
            __alloyId38.__transform = {};
            var __alloyId39 = Ti.UI.createTableViewRow({
                dataId: "undefined" != typeof __alloyId38.__transform["id"] ? __alloyId38.__transform["id"] : __alloyId38.get("id"),
                model: "undefined" != typeof __alloyId38.__transform["alloy_id"] ? __alloyId38.__transform["alloy_id"] : __alloyId38.get("alloy_id"),
                title: "undefined" != typeof __alloyId38.__transform["name"] ? __alloyId38.__transform["name"] : __alloyId38.get("name")
            });
            rows.push(__alloyId39);
        }
        $.__views.tblview.setData(rows);
    }
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "widgetschooser";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    Alloy.Collections.instance("Widget");
    $.__views.tblviewWindow = Ti.UI.createWindow({
        backgroundColor: "white",
        id: "tblviewWindow"
    });
    $.__views.tblviewWindow && $.addTopLevelView($.__views.tblviewWindow);
    $.__views.activityIndicator = Ti.UI.createActivityIndicator({
        height: Ti.UI.SIZE,
        width: Ti.UI.SIZE,
        top: 20,
        style: Ti.UI.iPhone.ActivityIndicatorStyle.DARK,
        id: "activityIndicator"
    });
    $.__views.tblviewWindow.add($.__views.activityIndicator);
    $.__views.labelNoRecords = Ti.UI.createLabel({
        width: Ti.UI.SIZE,
        height: Ti.UI.SIZE,
        color: "#000",
        visible: false,
        top: 20,
        id: "labelNoRecords"
    });
    $.__views.tblviewWindow.add($.__views.labelNoRecords);
    $.__views.__alloyId37 = Ti.UI.createSearchBar({
        id: "__alloyId37"
    });
    $.__views.tblview = Ti.UI.createTableView({
        height: Ti.UI.SIZE,
        top: 0,
        search: $.__views.__alloyId37,
        id: "tblview",
        editable: "true",
        filterAttribute: "title"
    });
    $.__views.tblviewWindow.add($.__views.tblview);
    var __alloyId40 = Alloy.Collections["Widget"] || Widget;
    __alloyId40.on("fetch destroy change add remove reset", __alloyId41);
    var __alloyId44 = [];
    $.__views.__alloyId45 = Ti.UI.createButton({
        systemButton: Ti.UI.iPhone.SystemButton.FLEXIBLE_SPACE
    });
    __alloyId44.push($.__views.__alloyId45);
    $.__views.cancel = Ti.UI.createButton({
        top: 10,
        id: "cancel",
        systemButton: Titanium.UI.iPhone.SystemButton.CANCEL
    });
    __alloyId44.push($.__views.cancel);
    $.__views.__alloyId46 = Ti.UI.createButton({
        systemButton: Ti.UI.iPhone.SystemButton.FLEXIBLE_SPACE
    });
    __alloyId44.push($.__views.__alloyId46);
    $.__views.__alloyId42 = Ti.UI.iOS.createToolbar({
        items: __alloyId44,
        bottom: "0",
        borderTop: "true",
        borderBottom: "false",
        id: "__alloyId42"
    });
    $.__views.tblviewWindow.add($.__views.__alloyId42);
    exports.destroy = function() {
        __alloyId40.off("fetch destroy change add remove reset", __alloyId41);
    };
    _.extend($, $.__views);
    var Modelname = "Widget";
    $.tblview.addEventListener("click", function(e) {
        Ti.App.fireEvent("changefield", {
            value: e.rowData.dataId,
            title: e.rowData.title
        });
        $.tblviewWindow.close();
    });
    $.cancel.addEventListener("click", function() {
        $.tblviewWindow.close();
    });
    var things = Alloy.Collections[Modelname];
    things.fetch();
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;