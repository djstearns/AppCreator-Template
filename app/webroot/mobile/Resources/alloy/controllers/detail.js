function Controller() {
    function dataTransformation(_model) {
        return {
            id: _model.attributes.id,
            name: _model.attributes.name,
            description: _model.attributes.description
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
        sendit.open("GET", Alloy.Glogals.BASEURL + "workers/mobilesave");
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
    this.__controllerPath = "detail";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    $.thingDetail = Alloy.createModel("Widget");
    $.__views.detail = Ti.UI.createWindow({
        backgroundColor: "white",
        id: "detail",
        model: "$.thingDetail",
        dataTransform: "dataTransformation",
        layout: "vertical"
    });
    $.__views.detail && $.addTopLevelView($.__views.detail);
    $.__views.name = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "name",
        hintText: "Name"
    });
    $.__views.detail.add($.__views.name);
    $.__views.description = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "description"
    });
    $.__views.detail.add($.__views.description);
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
    var __alloyId0 = function() {
        $.name.datid = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["id"] : $.thingDetail.get("id");
        $.name.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["name"] : $.thingDetail.get("name");
        $.name.datid = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["id"] : $.thingDetail.get("id");
        $.name.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["name"] : $.thingDetail.get("name");
        $.description.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["description"] : $.thingDetail.get("description");
        $.description.value = _.isFunction($.thingDetail.transform) ? $.thingDetail.transform()["description"] : $.thingDetail.get("description");
    };
    $.thingDetail.on("fetch change destroy", __alloyId0);
    exports.destroy = function() {
        $.thingDetail.off("fetch change destroy", __alloyId0);
    };
    _.extend($, $.__views);
    var args = arguments[0] || {};
    args.parentTab || "";
    var dataId = 0 === args.dataId || args.dataId > 0 ? args.dataId : "";
    if ("" !== dataId) {
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
        itemModel.set("description", $.description.value);
        itemModel.set("name", $.name.value);
        itemModel.save();
        savetoremote();
        $.detail.close();
    });
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;