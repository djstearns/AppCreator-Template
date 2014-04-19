exports.definition = {
    config: {
        columns: {
            id: "INTEGER PRIMARY KEY",
            name: "TEXT",
            widget_id: "INTEGER",
            staus: "TEXT",
            user_id: "INTEGER",
            updated: "TEXT",
            created: "TEXT"
        },
        adapter: {
            type: "sql",
            collection_name: "places",
            idAttribute: "id"
        }
    },
    extendModel: function(Model) {
        _.extend(Model.prototype, {});
        return Model;
    },
    extendCollection: function(Collection) {
        _.extend(Collection.prototype, {});
        return Collection;
    }
};

var Alloy = require("alloy"), _ = require("alloy/underscore")._, model, collection;

model = Alloy.M("place", exports.definition, []);

collection = Alloy.C("place", exports.definition, model);

exports.Model = model;

exports.Collection = collection;