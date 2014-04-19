exports.definition = {
    config: {
        columns: {
            id: "INTEGER PRIMARY KEY",
            widget_id: "INTEGER",
            worker_id: "INTEGER",
            numbermade: "INTEGER"
        },
        adapter: {
            type: "sql",
            collection_name: "workers_widgets",
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

model = Alloy.M("workers_widgets", exports.definition, []);

collection = Alloy.C("workers_widgets", exports.definition, model);

exports.Model = model;

exports.Collection = collection;