exports.definition = {
    config: {
        columns: {
            id: "INTEGER PRIMARY KEY",
            name: "TEXT",
            description: "TEXT"
        },
        adapter: {
            type: "sql",
            collection_name: "widgets",
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

model = Alloy.M("widget", exports.definition, []);

collection = Alloy.C("widget", exports.definition, model);

exports.Model = model;

exports.Collection = collection;