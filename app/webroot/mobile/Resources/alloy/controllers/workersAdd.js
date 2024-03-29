function Controller() {
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "workersAdd";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
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
        id: "name_tf"
    });
    $.__views.addView.add($.__views.name_tf);
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
    exports.destroy = function() {};
    _.extend($, $.__views);
    var modelname = "workers";
    var Modelname = "Worker";
    arguments[0] || {};
    $.savebtn.addEventListener("click", function() {
        globalsave(Alloy.Globals.BASEURL + modelname + "/mobileadd/", {
            name: $.name_tf.value
        }, Modelname, {
            name: $.name_tf.value,
            updated: "12-31-2013",
            created: "12-31-2013"
        });
        $.AddWindow.close();
    });
    $.cancelbtn.addEventListener("click", function() {
        $.AddWindow.close();
    });
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;