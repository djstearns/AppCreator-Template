exports.definition = {
    config: {
        columns: {
            id: "INTEGER",
            name: "TEXT",
            place_id: "INTEGER",
            updated: "TEXT",
            created: "TEXT"
        },
        adapter: {
            type: "sql",
            collection_name: "workers",
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

model = Alloy.M("worker", exports.definition, []);

collection = Alloy.C("worker", exports.definition, model);

exports.Model = model;

exports.Collection = collection;