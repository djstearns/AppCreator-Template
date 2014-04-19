function Controller() {
    function __alloyId68() {
        __alloyId68.opts || {};
        var models = __alloyId67.models;
        var len = models.length;
        var rows = [];
        for (var i = 0; len > i; i++) {
            var __alloyId65 = models[i];
            __alloyId65.__transform = {};
            var __alloyId66 = Ti.UI.createTableViewRow({
                dataId: "undefined" != typeof __alloyId65.__transform["id"] ? __alloyId65.__transform["id"] : __alloyId65.get("id"),
                model: "undefined" != typeof __alloyId65.__transform["alloy_id"] ? __alloyId65.__transform["alloy_id"] : __alloyId65.get("alloy_id"),
                title: "undefined" != typeof __alloyId65.__transform["name"] ? __alloyId65.__transform["name"] : __alloyId65.get("name")
            });
            rows.push(__alloyId66);
        }
        $.__views.tblview.setData(rows);
    }
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "workerschooser";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    Alloy.Collections.instance("Worker");
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
    $.__views.__alloyId64 = Ti.UI.createSearchBar({
        id: "__alloyId64"
    });
    $.__views.tblview = Ti.UI.createTableView({
        height: Ti.UI.SIZE,
        top: 0,
        search: $.__views.__alloyId64,
        id: "tblview",
        editable: "true",
        filterAttribute: "title"
    });
    $.__views.tblviewWindow.add($.__views.tblview);
    var __alloyId67 = Alloy.Collections["Worker"] || Worker;
    __alloyId67.on("fetch destroy change add remove reset", __alloyId68);
    var __alloyId71 = [];
    $.__views.__alloyId72 = Ti.UI.createButton({
        systemButton: Ti.UI.iPhone.SystemButton.FLEXIBLE_SPACE
    });
    __alloyId71.push($.__views.__alloyId72);
    $.__views.cancel = Ti.UI.createButton({
        top: 10,
        id: "cancel",
        systemButton: Titanium.UI.iPhone.SystemButton.CANCEL
    });
    __alloyId71.push($.__views.cancel);
    $.__views.__alloyId73 = Ti.UI.createButton({
        systemButton: Ti.UI.iPhone.SystemButton.FLEXIBLE_SPACE
    });
    __alloyId71.push($.__views.__alloyId73);
    $.__views.__alloyId69 = Ti.UI.iOS.createToolbar({
        items: __alloyId71,
        bottom: "0",
        borderTop: "true",
        borderBottom: "false",
        id: "__alloyId69"
    });
    $.__views.tblviewWindow.add($.__views.__alloyId69);
    exports.destroy = function() {
        __alloyId67.off("fetch destroy change add remove reset", __alloyId68);
    };
    _.extend($, $.__views);
    var Modelname = "Worker";
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