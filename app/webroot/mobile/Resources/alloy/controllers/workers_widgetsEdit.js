function Controller() {
    function dataTransformation(_model) {
        return {
            id: _model.attributes.id,
            widget_id: _model.attributes.widget_id,
            worker_id: _model.attributes.worker_id,
            itemqty: _model.attributes.numbermade
        };
    }
    function savetoremote() {
        var sendit = Ti.Network.createHTTPClient({
            onerror: function(e) {
                Ti.API.debug(e.error);
                savetoremote();
                alert("There was an error during the connection");
            },
            timeout: 1e3
        });
        sendit.open("GET", Alloy.Globals.BASEURL + Modelname + "/mobilesave");
        sendit.send({
            id: $.name.datid,
            name: $.name.value,
            description: $.description.value
        });
        sendit.onload = function() {
            var json = JSON.parse(this.responseText);
            0 == json.length && ($.table.headerTitle = "The database row is empty");
        };
    }
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "workers_widgetsEdit";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    $.thingDetail = Alloy.createModel("workers_widgets");
    $.__views.detail = Ti.UI.createWindow({
        backgroundColor: "white",
        id: "detail",
        model: "$.thingDetail",
        dataTransform: "dataTransformation",
        layout: "vertical"
    });
    $.__views.detail && $.addTopLevelView($.__views.detail);
    $.__views.widget_id = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "widget_id"
    });
    $.__views.detail.add($.__views.widget_id);
    $.__views.worker_id = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "worker_id"
    });
    $.__views.detail.add($.__views.worker_id);
    $.__views.numbermade = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "numbermade"
    });
    $.__views.detail.add($.__views.numbermade);
    $.__views.savebtn = Ti.UI.createButton({
        top: 10,
        title: "Save",
        id: "savebtn"
    });
    $.__views.detail.add($.__views.savebtn);
    $.__views.cancelbtn = Ti.UI.createButton({
        top: 10,
        title: "Cancel",
        id: "cancelbtn"
    });
    $.__views.detail.add($.__views.cancelbtn);
    var __alloyId61 = function() {
        $.widget_id.datid = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["id"] : $.thingDetail.get("id");
        $.widget_id.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["widget_id"] : $.thingDetail.get("widget_id");
        $.widget_id.datid = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["id"] : $.thingDetail.get("id");
        $.widget_id.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["widget_id"] : $.thingDetail.get("widget_id");
        $.worker_id.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["worker_id"] : $.thingDetail.get("worker_id");
        $.worker_id.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["worker_id"] : $.thingDetail.get("worker_id");
        $.numbermade.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["numbermade"] : $.thingDetail.get("numbermade");
        $.numbermade.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["numbermade"] : $.thingDetail.get("numbermade");
    };
    $.thingDetail.on("fetch change destroy", __alloyId61);
    exports.destroy = function() {
        $.thingDetail.off("fetch change destroy", __alloyId61);
    };
    _.extend($, $.__views);
    var Modelname = "workerswidgets";
    var args = arguments[0] || {};
    args.parentTab || "";
    var dataId = 0 === args.dataId || args.dataId > 0 ? args.dataId : "";
    Ti.API.info(dataId);
    if ("" != dataId) {
        $.thingDetail.set(args.model.attributes);
        $.thingDetail = _.extend({}, $.thingDetail, {
            transform: function() {
                return dataTransformation(this);
            }
        });
    }
    $.cancelbtn.addEventListener("click", function() {
        $.detail.close();
    });
    $.savebtn.addEventListener("click", function() {
        var itemModel = args.model;
        itemModel.set("name", $.name.value);
        itemModel.save();
        Alloy.Collections.Thing.fetch();
        savetoremote();
        $.detail.close();
    });
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;