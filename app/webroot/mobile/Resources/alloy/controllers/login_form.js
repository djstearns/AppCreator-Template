function Controller() {
    function actionLogin() {
        if ($.inputUsername.value && $.inputPassword.value) {
            $.activityIndicator.show();
            $.buttonLogin.enabled = false;
            var AppData = require("data");
            AppData.login($.inputUsername.value, $.inputPassword.value, function(response) {
                $.activityIndicator.hide();
                $.buttonLogin.enabled = true;
                if ("ok" === response.result) {
                    getworkerswidgets();
                    var index = Alloy.createController("indexhome").getView();
                    index.open();
                    var loginwin = Alloy.Globals.loginwin;
                    loginwin.close();
                    Alloy.Globals.loginwin = null;
                    var navgroup = Alloy.Globals.navgroup;
                    navgroup.close();
                    navgroup = null;
                    Alloy.Globals.navgroup = null;
                } else {
                    $.inputPassword.value = "";
                    alert(L("error", "Error") + ":\n" + response.msg);
                }
            });
        } else Ti.UI.createAlertDialog({
            message: L("formMissingFields", "Please complete all form fields"),
            ok: "OK",
            title: L("actionRequired", "Action Required")
        }).show();
    }
    function openRegister() {
        var registerController = Alloy.createController("register").getView();
        Alloy.Globals.navgroup.open(registerController);
    }
    function getworkerswidgets() {
        Alloy.Collections.WorkersWidgets = Alloy.createCollection("workers_widgets");
        Ti.API.info("Getting workers widgets");
        var newthing = [];
        var sendit = Ti.Network.createHTTPClient({
            onerror: function(e) {
                Ti.API.debug(e.error);
                alert("There was an error during the connection for workerswidgets");
            },
            timeout: 1e3
        });
        sendit.open("POST", Alloy.Globals.BASEURL + "workers_widgets/mobileindex.json");
        sendit.send({
            token: Ti.App.Properties.getString("token")
        });
        sendit.onload = function() {
            var json = JSON.parse(this.responseText);
            0 == json.length && ($.table.headerTitle = "The database row is empty");
            var recipes = json;
            for (var i = 0, iLen = recipes.length; iLen > i; i++) newthing.push(recipes[i].WorkersWidget);
            Alloy.Collections.WorkersWidgets.reset(newthing);
            Alloy.Collections.WorkersWidgets.each(function(_m) {
                _m.save();
            });
            var things = Alloy.Collections.WorkersWidgets;
            things.fetch();
            Ti.App.Properties.setString("workerswidgetsseeded", "yuppers");
        };
    }
    require("alloy/controllers/BaseController").apply(this, Array.prototype.slice.call(arguments));
    this.__controllerPath = "login_form";
    arguments[0] ? arguments[0]["__parentSymbol"] : null;
    arguments[0] ? arguments[0]["$model"] : null;
    arguments[0] ? arguments[0]["__itemTemplate"] : null;
    var $ = this;
    var exports = {};
    var __defers = {};
    $.__views.loginForm = Ti.UI.createWindow({
        backgroundColor: "white",
        id: "loginForm",
        fullscreen: "false"
    });
    $.__views.loginForm && $.addTopLevelView($.__views.loginForm);
    $.__views.loginView = Ti.UI.createView({
        id: "loginView",
        layout: "vertical"
    });
    $.__views.loginForm.add($.__views.loginView);
    $.__views.inputUsername = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "inputUsername"
    });
    $.__views.loginView.add($.__views.inputUsername);
    $.__views.inputPassword = Ti.UI.createTextField({
        width: 200,
        top: 10,
        borderStyle: Ti.UI.INPUT_BORDERSTYLE_ROUNDED,
        autocapitalization: Ti.UI.TEXT_AUTOCAPITALIZATION_NONE,
        id: "inputPassword",
        passwordMask: "true"
    });
    $.__views.loginView.add($.__views.inputPassword);
    $.__views.buttonLogin = Ti.UI.createButton({
        top: 10,
        id: "buttonLogin"
    });
    $.__views.loginView.add($.__views.buttonLogin);
    actionLogin ? $.__views.buttonLogin.addEventListener("click", actionLogin) : __defers["$.__views.buttonLogin!click!actionLogin"] = true;
    $.__views.activityIndicator = Ti.UI.createActivityIndicator({
        height: Ti.UI.SIZE,
        width: Ti.UI.SIZE,
        top: 20,
        style: Ti.UI.iPhone.ActivityIndicatorStyle.DARK,
        id: "activityIndicator"
    });
    $.__views.loginView.add($.__views.activityIndicator);
    exports.destroy = function() {};
    _.extend($, $.__views);
    $.loginForm.title = L("login", "Login");
    $.inputUsername.hintText = L("username", "Username");
    $.inputPassword.hintText = L("password", "Password");
    $.buttonLogin.title = L("login", "Login");
    var btnRightNav = Ti.UI.createButton({
        title: L("register", "Register")
    });
    btnRightNav.addEventListener("click", openRegister);
    $.loginForm.rightNavButton = btnRightNav;
    __defers["$.__views.buttonLogin!click!actionLogin"] && $.__views.buttonLogin.addEventListener("click", actionLogin);
    _.extend($, exports);
}

var Alloy = require("alloy"), Backbone = Alloy.Backbone, _ = Alloy._;

module.exports = Controller;