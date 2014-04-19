function Controller() {
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "scanner";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    $.__views.scannerwin = Ti.UI.createWindow({
        backgroundColor: "white",
        id: "scannerwin"
    });
    $.__views.scannerwin && $.addTopLevelView($.__views.scannerwin);
    exports.destroy = function() {};
    _.extend($, $.__views);
    var args = arguments[0] || {};
    var scanditsdk = require("com.mirasense.scanditsdk");
    ("iphone" == Ti.Platform.osname || "ipad" == Ti.Platform.osname) && (Titanium.UI.iPhone.statusBarHidden = true);
    var picker;
    var window = Titanium.UI.createWindow({
        title: "Scandit SDK",
        navBarHidden: true
    });
    var openScanner = function() {
        picker = scanditsdk.createView({
            width: "100%",
            height: "100%"
        });
        picker.init("vRNYEm1VEeKY90ok35tWw0cf8zCRI36iR1siUpvgUy0", 0);
        picker.showSearchBar(true);
        picker.showToolBar(true);
        picker.setSuccessCallback(function(e) {
            Ti.API.info(args);
            if ("false" == args.upload) Ti.App.fireEvent(args.functionname, {
                sybology: e.symbology,
                barcode: e.barcode
            }); else {
                var sendit = Ti.Network.createHTTPClient({
                    onerror: function(e) {
                        Ti.API.debug(e.error);
                        alert("There was an error during the connection");
                    },
                    timeout: 1e3
                });
                sendit.onload = function() {
                    JSON.parse(this.responseText);
                    alert(this.responseText);
                };
                sendit.open("POST", "http://www.derekstearns.com/dinnernew/items/appadd");
                sendit.setRequestHeader("Content-Type", "application/json");
                sendit.send({
                    code: e.barcode
                });
            }
        });
        picker.setCancelCallback(function() {
            closeScanner();
        });
        window.add(picker);
        window.addEventListener("open", function() {
            "iphone" == Ti.Platform.osname || "ipad" == Ti.Platform.osname ? picker.setOrientation(Ti.UI.orientation) : picker.setOrientation(window.orientation);
            picker.setSize(Ti.Platform.displayCaps.platformWidth, Ti.Platform.displayCaps.platformHeight);
            picker.startScanning();
        });
        window.open();
    };
    var closeScanner = function() {
        if (null != picker) {
            picker.stopScanning();
            window.remove(picker);
        }
        window.close();
    };
    Ti.Gesture.addEventListener("orientationchange", function(e) {
        window.orientationModes = [ Titanium.UI.PORTRAIT, Titanium.UI.UPSIDE_PORTRAIT, Titanium.UI.LANDSCAPE_LEFT, Titanium.UI.LANDSCAPE_RIGHT ];
        if (null != picker) {
            picker.setOrientation(e.orientation);
            picker.setSize(Ti.Platform.displayCaps.platformWidth, Ti.Platform.displayCaps.platformHeight);
        }
    });
    var button = Titanium.UI.createButton({
        width: 200,
        height: 80,
        title: "start scanner"
    });
    button.addEventListener("click", function() {
        openScanner();
    });
    if ("false" == args.upload) {
        var clsbutton = Titanium.UI.createButton({
            width: 200,
            height: 80,
            top: 300,
            title: "Back"
        });
        clsbutton.addEventListener("click", function() {
            $.scannerwin.close();
        });
        $.scannerwin.add(clsbutton);
    }
    $.scannerwin.add(button);
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;