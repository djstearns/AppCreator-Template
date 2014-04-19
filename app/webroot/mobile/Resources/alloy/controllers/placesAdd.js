function Controller() {
    function dataTransformation(_model) {
        return {
            id: _model.attributes.id,
            name_tf: _model.attributes.name
        };
    }
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "placesAdd";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    $.thingDetail = Alloy.createModel("Place");
    $.__views.AddWindow = Ti.UI.createWindow({
        backgroundColor: "white",
        id: "AddWindow"
    });
    $.__views.AddWindow && $.addTopLevelView($.__views.AddWindow);
    $.__views.addView = Ti.UI.createScrollView({
        id: "addView",
        layout: "vertical"
    });
    $.__views.AddWindow.add($.__views.addView);
    $.__views.name_tf = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "name_tf",
        hintText: "Name"
    });
    $.__views.addView.add($.__views.name_tf);
    $.__views.status = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "status",
        hintText: "Status"
    });
    $.__views.addView.add($.__views.status);
    $.__views.widget_id = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "widget_id"
    });
    $.__views.addView.add($.__views.widget_id);
    $.__views.widgetname = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "widgetname"
    });
    $.__views.addView.add($.__views.widgetname);
    $.__views.pickingredient = Ti.UI.createButton({
        top: 10,
        title: "Ingredient",
        id: "pickingredient"
    });
    $.__views.addView.add($.__views.pickingredient);
    $.__views.savebtn = Ti.UI.createButton({
        top: 10,
        id: "savebtn",
        title: "Save"
    });
    $.__views.addView.add($.__views.savebtn);
    $.__views.cancelbtn = Ti.UI.createButton({
        top: 10,
        id: "cancelbtn",
        title: "Cancel"
    });
    $.__views.addView.add($.__views.cancelbtn);
    var __alloyId20 = function() {
        $.name_tf.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["name"] : $.thingDetail.get("name");
        $.name_tf.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["name"] : $.thingDetail.get("name");
    };
    $.thingDetail.on("fetch change destroy", __alloyId20);
    exports.destroy = function() {
        $.thingDetail.off("fetch change destroy", __alloyId20);
    };
    _.extend($, $.__views);
    var modelname = "places";
    var Modelname = "Place";
    var args = arguments[0] || {};
    args.parentTab || "";
    args.manytomanyaddscreen;
    var dataId = 0 === args.dataId || args.dataId > 0 ? args.dataId : "";
    if ("" != dataId) {
        $.thingDetail.set(args.model.attributes);
        $.thingDetail = _.extend({}, $.thingDetail, {
            transform: function() {
                return dataTransformation(this);
            }
        });
    }
    $.savebtn.addEventListener("click", function() {
        globalsave(Alloy.Globals.BASEURL + modelname + "/mobileadd/", {
            name: $.name_tf.value,
            widget_id: $.widget_id.value,
            status: $.status.value
        }, Modelname, {
            name: $.name_tf.value,
            widget_id: $.widget_id.value,
            status: $.status.value,
            updated: "12-31-2013",
            created: "12-31-2013"
        });
        $.AddWindow.close();
    });
    Ti.App.addEventListener("fillupc", function(e) {
        $.upc.value = e.barcode;
    });
    Ti.App.addEventListener("changefield", function(e) {
        $.widget_id.value = e.value;
        $.widgetname.value = e.title;
    });
    $.pickingredient.addEventListener("click", function() {
        var win = Alloy.createController("widgetschooser").getView();
        win.open();
    });
    $.cancelbtn.addEventListener("click", function() {
        $.AddWindow.close();
    });
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;