///**************
/*
 * 
 Three variable arrays:
 Data Transform:
 Static portion:	"id:_model.attributes.id"
 Variable:	 		"[fldname]: _model.attributes.[fldname]"
 
 Save Data:
 Static Portion:	id: $.name.datid,
 Variable:			[fldname]: $.[fldname].value,
 
 Local Save data:
 Static portion: NA
 Variable:		 itemModel.set("[fldname]", $.[fldname].value);
	    		
 */
////*************

var args = arguments[0] || {};
var parentTab = args.parentTab || '';
var dataId = (args.dataId === 0 || args.dataId > 0) ? args.dataId : '';

if (dataId !== '') {
	$.thingDetail.set(args.model.attributes);
	
	$.thingDetail = _.extend({}, $.thingDetail, {
	    transform : function() {
	        return dataTransformation(this);
	    }
	});

    function dataTransformation(_model) {
	    return {
	    	//ModelVars
	    	id : _model.attributes.id,
	        name : _model.attributes.name,
	        description : _model.attributes.description
	        //ModelVars
	    };
	}
}

function savetoremote(){
	var sendit = Ti.Network.createHTTPClient({
			onerror : function(e) {
				Ti.API.debug(e.error);
				savetoremote();
				alert('There was an error during the connection');
			},
			timeout : 1000,
		});
	sendit.open('GET', Alloy.Glogals.BASEURL+'workers/mobilesave');
	sendit.send({
		//Model Vars
		id: $.name.datid,
		name: $.name.value,
		description: $.description.value
		//Model Vars
	});
	// Function to be called upon a successful response
	sendit.onload = function() {
	    var json = JSON.parse(this.responseText);
		// var json = json.todo;
		// if the database is empty show an alert
		if (json.length == 0) {
			$.table.headerTitle = "The database row is empty";
			
		}
	};
}

///Buttons!

$.cancelbtn.addEventListener("click", function(){
    $.detail.close();
});

$.savebtn.addEventListener("click", function(){
	var itemModel = args.model;
    //Model VARS
    itemModel.set("description", $.description.value);
    itemModel.set("name", $.name.value);
    //End model vars
    
    itemModel.save();
    //Alloy.Collections.Thing.fetch();
    savetoremote();
    $.detail.close();
});

 // Android
if (OS_ANDROID) {
    $.detail.addEventListener('open', function() {
        if($.detail.activity) {
            var activity = $.detail.activity;

            // Action Bar
            if( Ti.Platform.Android.API_LEVEL >= 11 && activity.actionBar) {      
                activity.actionBar.title = L('detail', 'Detail');
                activity.actionBar.displayHomeAsUp = true; 
                activity.actionBar.onHomeIconItemSelected = function() {               
                    $.detail.close();
                    $.detail = null;
                };             
            }
        }
    });
    
    // Back Button - not really necessary here - this is the default behavior anyway?
    $.detail.addEventListener('android:back', function() {              
        $.detail.close();
        $.detail = null;
    });     
}
