var spinnerInhibited = true; //inhibit this initially so basic setup tasks that are done through AJAX, like loading the map, can be done without showing this. Can also inhibit as needed for minor things that aren't expected to take much time

//loading graphic
$(document).ajaxStart(function() {
	//check if loading spinner is inhibited first
	if (!spinnerInhibited) {
		$(".loadingspinnermain").css("visibility", "visible");
		$("body").css("cursor", "wait");
	}
}).ajaxStop(function() {
    $(".loadingspinnermain").css("visibility", "hidden");
    $("body").css("cursor", "default");
});

function genericError() {
	alert("We encountered a problem, try again later");
}

const SITE_DATA = "SiteData";

//page state information
var chartsDisplayMode = "in-line";
var tablePage = 1;
var numRecords = 0;
var numPages = 0;
var sortBy = "Date";
var sortDirection = "Desc";
var showBenchmarks = true;
var charts = [];
var measurementSettings; //will be filled in with the contents of the MeasurementSettings table, containing category/name/alias/benchmarks/detection limits for each
var groups;

//global variables used by the map
var mapData;
var map;
var view;

var selectPointAction = {
	title: "Select this point",
	id: "select-point",
}

var template;
var fields = [{ //fields object the map uses for the points layer
	name: "ObjectID",
	type: "oid"
}, {
	name: "siteNumber",
	type: "string"
}, {
	name: "siteName",
	type: "string"
}, {
	name: "siteLocation",
	type: "string"
}];
var clickedPoint;
var selectedPoints = [];

const defaultPointColor = [0,150,255];
const selectedPointColor = [226, 119, 40];
const clickedPointColor = [0,255,55];

function ucfirst(str) {
	//capitalize first character of a string
	return str[0].toUpperCase() + str.slice(1);
}

function selectColor(colorIndex, palleteSize) {
	//returns color at an index of an evenly-distributed color pallete of arbitrary size. To avoid ever having the color of the line matching the color of the benchmark lines, we offset the index and pallet size by 1
	if (palleteSize < 1) {
		palleteSize = 1; //defaults to one color, can't divide by zero or the universe implodes
	}
		
	return "hsl(" + ((colorIndex+1) * (360 / (palleteSize+1)) % 360) + ",70%,50%)";
}

function benchmarkLine(val, color) {
	//builds annotation line with a given value and color
	return {
		type: "line",
		mode: "horizontal",
		scaleID: "y-axis-0",
		value: val,
		borderColor: color,
		borderWidth: 3,
		drawTime: "afterDatasetsDraw",
	};
}

const driver = new Driver({
	animate: true, 
	doneBtnText: 'Finish Tour',
	closeBtnText: 'Exit Tour', 
	keyboardControl: true,
	allowClose: false,
	opacity: .75,

});

driver.defineSteps([
{
  element: '.h-100',
  stageBackground: '#00000000',
  popover: {
	className: 'first-popover-class',
    title: 'Welcome!',
    description: 'Welcome to the Water Quality Information System. This is a resource dedicated to analyzing and cataloging water quality samples taken within various watersheds located in Indiana, Michigan, and Ohio. More Can Be found in our About Page.',
	position: 'mid-center',
  }
},{
  element: '#navbar',
  stageBackground: '#BF000000',
  popover: {
	className: 'second-popover-class',
	
	title: 'Located Here!',
    description: 'The "about" page may help answer various questions you may have about the initiative or aspects of the project.',
	position: 'bottom-left',
	offset: 180
  }
},{
  element: '#mapCard',
  popover: {
	className: 'third-popover-class',
    title: 'The Map',
    description: 'This is the Map, Here you will see several blue points and colored outlines. The colorful outlines signify different watersheds, and the blue dots represent water collection sites.',
	position: 'left',
  }

},{
  element: '#map',
  popover: {
	className: 'fourth-popover-class',
    title: 'Try Selecting One!',
    description: 'Try Selecting one of the collection sites by clicking on any of the blue dots on the map. Doing so will provide all the data from the last collected water sample at that selected site.',
	position: 'left',
  }
},{
  element: '#layerBar',
  popover: {
	className: 'fifth-popover-class',
    title: 'Layers',
    description: 'These are the different layers. They can be toggled on or off by clicking the checkboxes. Selecting these layers will result in additional highlighted features on the map that were not present before.',
	position: 'left',
  }
},{
  element: '#selectBasemap',
  popover: {
	className: 'sixth-popover-class',
    title: 'Basemaps',
    description: 'This dropdown menu contains a list of different basemap views that are available. Selecting one of these will cause the background view of the map to change depending on which basemap is selected.',
	position: 'left',
  }, onNext: () => {
	  document.getElementById("driver-page-overlay").style.opacity = '0';
	  },
},{
  element: '.sidebarContainer',
  stageBackground: '#BF000000',
  popover: {
	className: 'seventh-popover-class',
    title: 'Sidebar Menu',
    description: 'This is the sidebar container. This is where we will be able to search the system for more specific data.',
	position: 'right',
  }
},{
 element: '#sidebarToggle', 
 stageBackground: '#BF000000',
  popover: {
	className: 'eighth-popover-class',
    title: 'Sidebar Toggle',
    description: 'This is the button that allows you to close and open the sidebar at will.',
	position: 'right',
  }
},{
  element: '#searchGroupsDropdown',
  stageBackground: '#BF000000',
  popover: {
	className: 'ninth-popover-class',
    title: 'Selecting Sites and Groups',
    description: 'Here is where you will select which sites or groups of sites you would like to search for data over.',
	position: 'right',
	offset: 35
  }
},{
  element: '#categorySelect',
 stageBackground: '#BF000000',
  popover: {
	className: 'tenth-popover-class',
    title: 'Selecting a Category',
    description: 'Here is where you will select a measurement category you would like to search by, all water quality data is classified under these four measurement categories.',
	position: 'right',
  },
   
},{
  element: '#checkboxList',
 stageBackground: '#BF000000',
  popover: {
	className: 'eleventh-popover-class',
    title: 'Selecting a Measurement',
    description: 'Here is where you will select a measurement you would like to search by, you may select as many checkboxes as there are available. These selections will determine the type of data you recieve.',
	position: 'right',
  }
},{
  element: '#startDate',
 stageBackground: '#BF000000',
  popover: {
	className: 'twelveth-popover-class',
    title: 'Selecting a Date Range',
    description: 'Here is where you will select a date range you would like to view data over. You will only recieve data that falls within this provided date range',
	position: 'right',
  }
},{
  element: '#measurementSelect',
 stageBackground: '#BF000000',
  popover: {
	className: 'thirteenth-popover-class',
    title: 'Additionally filtering your search results',
    description: 'This section is completely optional, but here is where you will be able to refine the data you will recieve. The measurement box will already be filled in and always match the same measurement criteria set above.',
	position: 'right',
  }
},{
  element: '#overUnderSelect',
 stageBackground: '#BF000000',
  popover: {
	className: 'fourteenth-popover-class',
    title: 'Searching Over, Under, or Equal to a specified amount',
    description: 'This is where we will specify if we would like to serach over, under, or equal to a certain amount of a measure. For example, if we search for Ecoli Over 2000, we would only recieve data where ecoli was over 2000,',
	position: 'right',
  }
},{
  element: '#amountEnter',
 stageBackground: '#BF000000',
  popover: {
	className: 'fifteenth-popover-class',
    title: 'Entering an amount',
    description: 'Here we can enter the amount we would like to search by. The number that appears in this textbox by default is the set benchmark for that given measure, this is to give the user a better sense of the range they should be searching by.',
	position: 'right',
  }
},{
  element: '#updateButton',
 stageBackground: '#BF000000',
  popover: {
	className: 'sixteenth-popover-class',
    title: 'Updating the graphs based on your search criteria',
    description: 'When you are completely finished filling out the form in the side panel, click the update function to get a visual and numerical representation of the data.',
	position: 'right',
  }
},{
  element: '#resetButton',
 stageBackground: '#BF000000',
  popover: {
	className: 'seventeenth-popover-class',
    title: 'Reseting the forum',
    description: 'If you would like to start the forum over, click the reset button and everything will be reset to its default state.',
	position: 'right',
  }, onNext: () => {
	  document.getElementById("driver-page-overlay").style.opacity = '.75';
	  },
},{
  element: '#timelineCard',
  popover: {
	className: 'eighteenth-popover-class',
    title: 'VIewing your Graphs',
    description: 'A visual represnetation of your searched data will appear here in the timeline section',
	position: 'left',
  }
},{
  element: '#tableCard',
  popover: {
	className: 'ninteenth-popover-class',
    title: 'VIewing your Data Numerically ',
    description: 'All enteries in the system that match your search criteria will appear here in Chronological Descending order. This can be changed by clicking on the table headers.',
	position: 'left',
  }
},

]);

//get all data needed for initial page loading
$.ajax({
	type: "POST",
	url: "chartsInitData",
	datatype: "JSON",
	async: false,
	success: function(response) {
		measurementSettings = response.settings;
		groups = response.groups;
		mapData = response.mapData;
	}
});

$(document).ready(function () {
	if (typeof admin == "undefined") {
		admin = false;
	}
	
	//build the table template we use to display all the data associated with a point on the map
	var templateContent = "<table>";
	for (var category in measurementSettings) {
		fields.push({
			name: category + "Date",
			type: "string",
			defaultValue: "No Records Found"
		});
	
		templateContent = templateContent + "<tr><th>" + ucfirst(category) + " Measurements</th><th>{" + category + "Date}</th></tr>";
		for (i=0; i<measurementSettings[category].length; i++) {
			fields.push({
				name: measurementSettings[category][i].measureKey,
				type: "string",
				defaultValue: "No Data"
			});
	
			templateContent = templateContent + "<tr><th>" + measurementSettings[category][i].measureName + "</th><td>{" + measurementSettings[category][i].measureKey + "}</td></tr>";
		}
	}
	templateContent = templateContent + "</table>";
	
	template = {
		title: "<b>{siteNumber} - {siteName} ({siteLocation})</b>",
		content: templateContent,
		actions: [selectPointAction]
	};
	
	//map code
	require([
		"esri/Map",
		"esri/views/MapView",
		"esri/layers/MapImageLayer",
		"esri/layers/FeatureLayer",
		"esri/layers/KMLLayer",
		"esri/widgets/Home",
		"esri/widgets/Fullscreen",
		"esri/Graphic",
		"esri/layers/support/LabelClass"
	], function(Map, MapView, MapImageLayer, FeatureLayer, KMLLayer, Home, Fullscreen, Graphic, LabelClass) {
		//build the site and group dropdowns in the sidebar
		$("#searchGroupsDropdown").append(new Option("select an option", "", false, false));
		for (var group of groups) {
			$("#searchGroupsDropdown").append(new Option(group.groupName, group.groupKey, false, false));
		}
		
		for (var site of mapData["SiteData"]) {
			$("#sites").append(new Option(site.Site_Number + " " + site.Site_Name, site.Site_Number, false, false));
		}
		
		var kmlurl = "http://emerald.pfw.edu/WQIS/img/wqisDev.kml";// + "?_=" + new Date().getTime(); //date/time at end is to force ESRI's server to not cache it. Remove this once dev is finished				
		var watershedsLayer = new KMLLayer({
			url: kmlurl,
			id: "watersheds"
		});
		
		var urls = [
			"https://maps.indiana.edu/arcgis/rest/services/Hydrology/Water_Bodies_Flowlines_Unclassified_LocalRes/MapServer",
			"https://maps.indiana.edu/ArcGIS/rest/services/Hydrology/Water_Bodies_Streams/MapServer",
			"https://maps.indiana.edu/arcgis/rest/services/Hydrology/Water_Quality_Impaired_Waters_303d_2016/MapServer",
			"https://maps.indiana.edu/ArcGIS/rest/services/Hydrology/Water_Bodies_Lakes/MapServer",
			"https://maps.indiana.edu/arcgis/rest/services/Hydrology/Floodplains_FIRM/MapServer",
			"https://maps.indiana.edu/ArcGIS/rest/services/Infrastructure/Dams_IDNR/MapServer",
			"https://maps.indiana.edu/arcgis/rest/services/Hydrology/Water_Wells_IDNR/MapServer",
			"https://maps.indiana.edu/arcgis/rest/services/Hydrology/Wetlands_NWI/MapServer",
		];
		
		//build the points layer
		var visibleSites = mapData["SiteData"];
		var graphics = [];
		const labels = new LabelClass({
			labelExpressionInfo: {
				expression: "$feature.siteName"
			},
			symbol: {
				type: "text",
				color: "white",
				font: {
					size: 10,
					font: "Playfair Display",
				},
				horizontalAlignment: "left",
				haloSize: 3,
				haloColor: "black"
			},
			labelPlacement: "above-center"
		});
		//add markers to the map at each sites longitude and latitude
		for (var i=0; i<visibleSites.length; i++) {
			var pointGraphic = new Graphic({
					ObjectID: i,
					geometry: {
					type: "point",
					longitude: visibleSites[i]["Longitude"],
					latitude: visibleSites[i]["Latitude"]
				},
				attributes: {}
			});
			
			pointGraphic.attributes.siteNumber = visibleSites[i]["Site_Number"].toString();
			pointGraphic.attributes.siteName = visibleSites[i]["Site_Name"];
			pointGraphic.attributes.siteLocation = visibleSites[i]["Site_Location"];
			
			pointGraphic.symbol = {
				type: "simple-marker",
				color: defaultPointColor,
				outline: {
					color: [255,255,255],
					width: 2
				}
			}
			
			for (var shortField in measurementSettings) {
				var field = shortField + "_samples";
				
				for (rowNum=0; rowNum<mapData[field].length; rowNum++) {
					var siteNumber = mapData[field][rowNum]["site_location_id"];
					if (pointGraphic.attributes.siteNumber == siteNumber) {
						pointGraphic.attributes[shortField + "Date"] = mapData[field][rowNum]["Date"].split("T")[0];
						for (z=0; z<measurementSettings[shortField].length; z++) {
							var key = measurementSettings[shortField][z].measureKey;
							if (mapData[field][rowNum][key] !== null) {
								pointGraphic.attributes[key] = mapData[field][rowNum][key].toString();
							}
						}
						break;
					}
				}
			}
			
			visibleSites[i].graphic = pointGraphic;
			
			graphics.push(pointGraphic);
		}
		
		var sampleSitesLayer = new FeatureLayer({
			fields: fields,
			objectIdField: "ObjectID",
			popupTemplate: template,
			source: graphics,
			id: "sampleSites",
			labelingInfo: [labels]
		});
		
		var mapLayers = [watershedsLayer];
		for (i=0; i<urls.length; i++) {
			mapLayers.push(new MapImageLayer({
				url: urls[i],
				visible: false
			}));
		}
		mapLayers.push(sampleSitesLayer);
		
		//create the map
		map = new Map({
			basemap: "satellite",
			layers: mapLayers
		});
		view = new MapView({
			container: "map",
			center: [-84.4, 41.2],
			zoom: 8,
			map: map
		});
		
		view.when(function() {
			view.graphics.addMany(graphics);

			//highlight points when they're clicked
			view.on("click", function(event) {
				view.hitTest(event.screenPoint).then(function(response) {
					var hitPoint = false; //have we hit a collection site?
					response.results.forEach(function(graphic) {
						if (graphic.graphic.ObjectID != null) { //if this is actually a site icon, not a watershed or something
							clearHighlight();
							
							highlightPoint(graphic.graphic);
							hitPoint = true;
						}
					});
										
					if (!hitPoint) {
						//we clicked something other than a collection site, clear the highlight
						clearHighlight();
					}
				});
			});
					
			//unselect when the popup is closed
			view.popup.watch("visible", function() {
				if (!view.popup.visible) {
					clearHighlight();
				}
			});
			
			//handle custom actions
			view.popup.on("trigger-action", function(event) {
				if (event.action.id === "select-point") {
					selectPoint();
				}
			});
		});
		
		//add home button to return to the default extent
		var homeButton = new Home({
			view: view
		});
		view.ui.add(homeButton, "top-left");
		
		//add fullscreen button
		var fullscreenButton = new Fullscreen({
			view: view
		});
		view.ui.add(fullscreenButton, "bottom-left");
		
		//dock the popup permanently to the bottom right, so its not hidden if the user pans away from that point on the map
		view.popup = {
			dockEnabled: true,
			dockOptions: {
				//disables the dock button from the popup
				buttonEnabled: false,
				breakpoint: false,
				position: "bottom-right"
			}
		};
		
		//handle the checkboxes that toggle layer visibility
		var watershedsLayerToggle = document.getElementById("watershedsLayer");
		watershedsLayerToggle.addEventListener("change", function() {
			mapLayers[0].visible = watershedsLayerToggle.checked;
			$("#watershedsLegend").toggle();
		});
		
		var drainsLayerToggle = document.getElementById("drainsLayer");
		drainsLayerToggle.addEventListener("change", function() {
			mapLayers[1].visible = drainsLayerToggle.checked;
		});
		
		var riverLayerToggle = document.getElementById("riverLayer");
		riverLayerToggle.addEventListener("change", function(){
			mapLayers[2].visible = riverLayerToggle.checked;
		});
		
		var impairedLayerToggle = document.getElementById("impairedLayer");
		impairedLayerToggle.addEventListener("change", function(){
			mapLayers[3].visible = impairedLayerToggle.checked;
		});
		
		var bodiesLayerToggle = document.getElementById("bodiesLayer");
		bodiesLayerToggle.addEventListener("change", function(){
			mapLayers[4].visible = bodiesLayerToggle.checked;
		});
		
		var floodLayerToggle = document.getElementById("floodLayer");
		floodLayerToggle.addEventListener("change", function(){
			$("#floodplainsLegend").toggle();
			mapLayers[5].visible = floodLayerToggle.checked;
		});
		
		var damLayerToggle = document.getElementById("damLayer");
		damLayerToggle.addEventListener("change", function(){
			mapLayers[6].visible = damLayerToggle.checked;
		});
		
		var wellLayerToggle = document.getElementById("wellLayer");
		wellLayerToggle.addEventListener("change", function(){
			mapLayers[7].visible = wellLayerToggle.checked;
		});
		
		var wetlandLayerToggle = document.getElementById("wetlandLayer");
		wetlandLayerToggle.addEventListener("change", function(){
			mapLayers[8].visible = wetlandLayerToggle.checked;
		});
		
		//handle the dropdown that allows basemap to be changed
		var basemapSelect = document.getElementById("selectBasemap");
		basemapSelect.addEventListener("change", function() {
			map.basemap = basemapSelect.value;
		});
		
		//if theres a preselected site defined in the GET data, set it
		if (preselectSite) {
			$("#sites").val(preselectSite).trigger("change");
		}
		else {
			//trigger change anyway, to make "Select sites" show since for some reason that doesn't work by default
			$("#sites").trigger("change");
		}
	});
	
	function setColor(point, color) {
		point.symbol.color = color;
		
		view.graphics.remove(point);
		view.graphics.add(point);
	}
	
	function highlightPoint(point) {
		clickedPoint = point;
		setColor(clickedPoint, clickedPointColor)
	}
	
	function clearHighlight() {
		if (clickedPoint != null) {
			setColor(clickedPoint, defaultPointColor);
		}
	}
	
	function selectPoint() {
		var siteNum = mapData["SiteData"][view.popup.selectedFeature.ObjectID].Site_Number;
		var currentlySelected = $("#sites").val();
		
		if (!currentlySelected.includes(siteNum.toString())) {
			//add it
			currentlySelected.push(siteNum.toString());
			$("#sites").val(currentlySelected).trigger("change");
		}
	}
	
	$("#searchGroupsDropdown").change(function() {
		var selected = $("#searchGroupsDropdown").val();
		
		if (selected == "") {
			//reset
			$("#sites").val(null).trigger("change");
		}
		else {
			//get all the sites that are in this group
			var inGroup = [];
			for (var site of mapData.SiteData) {
				if (site.groups.split(",").includes(selected)) {
					//select it in the sites dropdown
					inGroup.push(site.Site_Number);
				}
			}
			$("#sites").val(inGroup).trigger("change");
		}
	});
	
	$("#sites").change(function() {
		getRange();
		
		var points = $("#sites").val();
		
		//first clear out the existing ones
		for (var point of selectedPoints) {
			setColor(point, defaultPointColor);
		}
		
		selectedPoints = [];
		
		for (i=0; i<points.length; i++) {
			//get associated graphic for this point
			for (j=0; j<mapData["SiteData"].length; j++) {
				if (mapData["SiteData"][j].Site_Number.toString() === points[i]) {
					setColor(mapData["SiteData"][j].graphic, selectedPointColor);
					selectedPoints.push(mapData["SiteData"][j].graphic);
					break;
				}
			}
		}
    });
	
	$("#showBenchmarks").change(function() {
		showBenchmarks = !showBenchmarks;
		resetCharts();
		getGraphData();
	});
	
	$("#allCheckbox").change(function() {
		var checkboxList = document.getElementsByClassName("measurementCheckbox");
		for (i=0; i<checkboxList.length; i++) {
			checkboxList[i].checked = document.getElementById("allCheckbox").checked;
		}
	});
	
	function checkboxesChanged() {
		var checkboxList = document.getElementsByClassName("measurementCheckbox");
		
		for (i=0; i<checkboxList.length; i++) {
			if (checkboxList[i].checked === false) {
				document.getElementById("allCheckbox").checked = false; //deselect the All checkbox
				break;
			}
		}
	}
	
	$(".measurementCheckbox").change(function() {
		checkboxesChanged();
	});

    function getRange() {
		spinnerInhibited = true;
		
		var sites = $("#sites").val();
        var categoryData = $("#categorySelect").val();
        if (sites.length != 0) { //no point making a request if no sites are selected
            $.ajax({
                type: "POST",
                url: "daterange",
                data: {
                    "sites": sites,
                    "category": categoryData
                },
                datatype: "JSON",
				async: false,
                success: function (data) {
                    setDates(data);
                }
            });
        }
		else {
			setDates([null, null]);
		}
		
		spinnerInhibited = false;
    }
	
	function setDates(dates) {
		var startDate = dates[0];
		var endDate = dates[1];
		$("#startDate").val(startDate);
		$("#endDate").val(endDate);
		$("#startDate").datepicker("update", startDate);
		$("#endDate").datepicker("update", endDate);
	}
	
    document.getElementById("categorySelect").addEventListener("change", function() {
		changeMeasures();
	});
	
	document.getElementById("measurementSelect").addEventListener("change", function() {
		var category = $("#categorySelect").val();
		var measureIndex
			for (measureIndex=0; measureIndex<measurementSettings[category].length; measureIndex++) {
				if (measurementSettings[category][measureIndex].measureKey === document.getElementById("measurementSelect").value) {
					break;
				}
			}	
		if (measurementSettings[category][measureIndex].benchmarkMaximum == null) {
			document.getElementById("amountEnter").placeholder = "No Benchmark Available";
		}
		else {
			document.getElementById("amountEnter").placeholder = "Benchmark: " + measurementSettings[category][measureIndex].benchmarkMaximum;
		}
	});
	
    $(".date-picker").datepicker({
        trigger: "focus",
        format: "mm/dd/yyyy",
        todayHighlight: true,
        todayBtn: "linked"
    });

    $("#startDate").datepicker().on("changeDate", function (selected) {
        $("#endDate").datepicker("setStartDate", new Date(selected.date.valueOf()));
    });
	
    $("#endDate").datepicker().on("changeDate", function (selected) {
        $("#startDate").datepicker("setEndDate", new Date(selected.date.valueOf()));
    });

	$("#sites").select2({
		closeOnSelect: false,
		placeholder: "Select sites",
		width: "resolve"
	});
	
	var easter_egg = new Konami(function() {
		//dynamically download the needed code so we don't bog down the 99.9% of users who won't even see this
		import("/WQIS/js/EEGS.js")
			.then((module) => {
				module.start();
			});
	});
	
	document.addEventListener("keydown", function(e) {
		if (e.keyCode === 27) {
			//when user hits escape key, close the sidebar
			closeSearchSidebar();
		}
	}, false);
	
	$("#exportBtn").click(function () {
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		var sites = $("#sites").val();
		var category = document.getElementById("categorySelect").value;
		var amountEnter = document.getElementById("amountEnter").value;
		var overUnderSelect = document.getElementById("overUnderSelect").value;
		var measurementSearch = document.getElementById("measurementSelect").value;
		var selectedMeasures = getSelectedMeasures();
		selectedMeasures.push(ucfirst(category) + "Comments");

		$.ajax({
			type: "POST",
			url: "/WQIS/generic-samples/exportData",
			datatype: "JSON",
			data: {
				"sites": sites,
				"startDate": startDate,
				"endDate": endDate,
				"category": category,
				"amountEnter": amountEnter,
				"overUnderSelect": overUnderSelect,
				"measurementSearch": measurementSearch,
				"selectedMeasures": selectedMeasures,
				"aggregate": document.getElementById("aggregateGroup").checked
			},
			success: function(response) {
				downloadFile(response, category);
			},
			error: function(response) {
				genericError();
			}
		});
	});
	
	function changeMeasures() {
		//when the measurement category is changed, change both lists of available measurements to match
		var measureSelect = document.getElementById("measurementSelect");
		var checkboxList = document.getElementById("checkboxList");
		var measurementCheckboxes = document.getElementsByClassName("measurementCheckbox");
		var categoryData = measurementSettings[document.getElementById("categorySelect").value];
		
		//first clear all the measures currently listed
		while (measureSelect.options.length > 0) {
			measureSelect.remove(0);
		}
		
		for (i=measurementCheckboxes.length-1; i>=0; i--) {
			checkboxList.removeChild(measurementCheckboxes[i].parentNode);
		}
		
		document.getElementById("allCheckbox").checked = true;
	
		var option = document.createElement("option");
		option.value = "select";
		option.text = "Select a measure";
		measureSelect.appendChild(option);
		
		for (i=0; i<categoryData.length; i++) {
			//fill in the measurementSelect dropdown
			var option = document.createElement("option");
			option.value = categoryData[i].measureKey;
			option.text = categoryData[i]["measureName"];
			measureSelect.appendChild(option);
			
			//now create the checkboxes as well
			var listItem = document.createElement("li");
				
			var box = document.createElement("input");
			box.value = categoryData[i].measureKey;
			box.id = categoryData[i].measureKey + "Checkbox";
			box.type = "checkbox";
			box.setAttribute("class", "measurementCheckbox");
			box.checked = true;
			
			var boxLabel = document.createElement("label");
			boxLabel.innerText = categoryData[i]["measureName"];
			boxLabel.setAttribute("for", categoryData[i].measureKey + "Checkbox");
			
			listItem.appendChild(box);
			listItem.appendChild(boxLabel);
			
			checkboxList.appendChild(listItem);
		}
		
		document.getElementById("amountEnter").value = "";
		
		$(".measurementCheckbox").change(function() {
			checkboxesChanged();
		});
		
        getRange(); //recalculate date range
		
		//reset the sortBy field to Date, since none of the other measures will be valid anymore
		sortBy = "Date";
	}
	
	function downloadFile(fileData, type) {
		if (fileData.length < 1) {
			return;
		}
		
		var csvContent = "data:text/csv;charset=utf-8,";
		var fields = Object.keys(fileData[0]);
		for (var i = 0; i < fileData.length; i++) {
			fileData[i]["Date"] = fileData[i]["Date"].substring(0, 10);
		}
		
		//make null values not have text
		var replacer = function (key, value) {
			return value === null ? '' : value;
		};

		var csv = fileData.map(function (row) {
			return fields.map(function (fieldName) {
				return JSON.stringify(row[fieldName], replacer);
			}).join(',');
		});
		fields[fields.indexOf("site_location_id")] = "Site Number";
		
		//add header column
		csv.unshift(fields.join(','));

		csvContent += csv.join('\r\n');
		var link = document.createElement("a");
		link.setAttribute("href", encodeURI(csvContent));
		link.setAttribute("download", type + "_export.csv");
		document.body.appendChild(link);
		link.click();
	}
	
	$("#updateButton").click(function() {
		updateAll();
	});
	
	function updateAll() {
		//validation
		//check that, if there is something in amountEnter, a measure is also selected
		var amountEnter = document.getElementById("amountEnter").value;
		var measurementSelect = document.getElementById("measurementSelect").value;
		
		if (amountEnter != "" && measurementSelect === "select") {
			alert("You must specify a measure to search by");
		}
		else {
			resetAll();
			getNumRecords();
			getGraphData();
			getTableData(1);
			$("#chartsLayoutSelect").show();
			if (numPages > 0) {
				document.getElementById("exportBtn").disabled = false;
			}
			else {
				document.getElementById("exportBtn").disabled = true;
			}
		}
	}
	
	$("#resetButton").click(function() {
		//clear all parameters to default values, and clear the chart/table view
		resetAll();
		$("#sites").val(null).trigger("change");
		$("#categorySelect").val("bacteria");
		changeMeasures();
		$("#chartsLayoutSelect").hide();
		document.getElementById("exportBtn").disabled = true;
	});
	
	$("#chartsInlineButton").click(function() {
		chartsDisplayMode = "in-line";
		resetCharts();
		getGraphData();
	});
	
	$("#chartsGridButton").click(function() {
		chartsDisplayMode = "grid";
		resetCharts();
		getGraphData();
	});
	
	$("#sidebarToggle").click(function() {
		toggleSearchSidebar();
	});
	
	function getTableData(page) {
		if (numPages > 0) { //if there is any data to display
			document.getElementById("tableNoData").style = "display: none";
			document.getElementById("chartsNoData").style = "display: none";
			document.getElementById("tableSettings").style = "display: block";
			document.getElementById("tablePageSelector").style = "display: block";
	
			tablePage = page;
			document.getElementById("tableDiv").innerHTML = "";
			document.getElementById("pageNumBox").value = tablePage;

			$("#firstPageButton").attr("disabled", false);
			$("#previousPageButton").attr("disabled", false);
			$("#lastPageButton").attr("disabled", false);
			$("#nextPageButton").attr("disabled", false);

			if (tablePage == 1) {
				$("#previousPageButton").attr("disabled", true);
				$("#firstPageButton").attr("disabled", true);
			}
			if (tablePage == numPages) {
				$("#nextPageButton").attr("disabled", true);
				$("#lastPageButton").attr("disabled", true);
			}

			var category = document.getElementById("categorySelect").value;
			var amountEnter = document.getElementById("amountEnter").value;
			var overUnderSelect = document.getElementById("overUnderSelect").value;
			var measurementSearch = document.getElementById("measurementSelect").value;
			var numRows = document.getElementById("numRowsDropdown").value;
			var selectedMeasures = getSelectedMeasures();
			var aggregateMode = document.getElementById("aggregateGroup").checked;

			//set up the column names and IDs to actually display
			if (!aggregateMode) {
				var columns = ["Site ID", "Date", "Sample Number"];
			}
			else {
				var columns = ["Date"];
			}
			for (i=0; i<selectedMeasures.length; i++) {
				//get index of this measure so we can find its printable name
				for (j=0; j<measurementSettings[category].length; j++) {
					if (measurementSettings[category][j].measureKey === selectedMeasures[i]) {
						columns.push(measurementSettings[category][j].measureName);
						break;
					}
				}
			}
			if (!aggregateMode) {
				var columnIDs = ((["site_location_id", "Date", "Sample_Number"]).concat(selectedMeasures));
				if (admin) {
					columns.push("Comments");
					columnIDs.push(ucfirst(category) + "Comments");
				}
			}
			else {
				var columnIDs = ((["Date"]).concat(selectedMeasures));
			}

			$.ajax({
				type: "POST",
				url: "/WQIS/generic-samples/tabledata",
				datatype: "JSON",
				async: false,
				data: {
					"sites": $("#sites").val(),
					"startDate": $("#startDate").val(),
					"endDate": $("#endDate").val(),
					"category": category,
					"amountEnter": amountEnter,
					"overUnderSelect": overUnderSelect,
					"measurementSearch": measurementSearch,
					"selectedMeasures": selectedMeasures,
					"numRows": numRows,
					"pageNum": tablePage,
					"sortBy": sortBy,
					"sortDirection": sortDirection,
					"aggregate": aggregateMode
				},
				success: function(response) {
					//create the blank table
					var table = document.createElement("table");
					table.setAttribute("class", "table table-striped table-responsive");
					table.id = "tableView";
			
					//build the header row first
					var tableHeader = table.insertRow();
			
					for (i=0; i<columns.length; i++) {
						var newCell = document.createElement("th");
						newCell.innerHTML = columns[i];
						newCell.setAttribute("class", "sort-by headerSort" + ((columnIDs[i] === sortBy) ? (" " + sortDirection) : ""));
						newCell.id = columnIDs[i];
						newCell.onclick = function() {setSort(event);};
				
						tableHeader.appendChild(newCell);
					}
					if (admin && !aggregateMode) {
						var actionsCell = document.createElement("th");
						actionsCell.innerText = "Actions";
						tableHeader.appendChild(actionsCell);
					}
				
					//fill in each row
					for (var i=0; i<response[0].length; i++) {
						var newRow = table.insertRow();
				
						Object.keys(response[0][i]).forEach(function(key) {
							if (key != "ID") {
								var newCell = newRow.insertCell();
								var value = response[0][i][key];
						
								if (key === "Date") {
									//we get the date in a weird format, parse it to something more appropriate
									value = value.split("T")[0];
								}
					
								if (admin && !aggregateMode) {
									var textDiv = document.createElement("div");
									textDiv.setAttribute("class", "input text");
									newCell.appendChild(textDiv);
										
									if (!key.includes("Comment")) {
										var label = document.createElement("label");
										label.style = "display: table-cell; cursor: pointer; white-space:normal !important";
										label.setAttribute("class", "btn btn-thin inputHide");
										label.setAttribute("for", key + "-" + i);
										label.innerText = value;
										
										label.onclick = function () {
											var label = $(this);
											var input = $("#" + label.attr("for"));
											input.trigger("click");
											label.attr("style", "display: none");
											input.attr("style", "display: in-line");
										};
						
										textDiv.appendChild(label);
								
										var cellInput = document.createElement("input");
										cellInput.type = "text";
										cellInput.name = key + "-" + i;
										cellInput.setAttribute("maxlength", 20);
										cellInput.size = 20;
										cellInput.setAttribute("class", "inputfields tableInput");
										cellInput.style = "display: none";
										cellInput.id = key + "-" + i;
										cellInput.setAttribute("value", value);
							
										cellInput.onfocusout = (function () {
											var input = $(this);

											if (!input.attr("id")) {
												return;
											}
			
											var rowNumber = (input.attr("id")).split("-")[1];
											var sampleNumber = $("#Sample_Number-" + rowNumber).val();
		
											var parameter = (input.attr("name")).split("-")[0];
											var value = input.val();
	
											$.ajax({
												type: "POST",
												url: "/WQIS/generic-samples/updatefield",
												datatype: "JSON",
												data: {
													"sampleNumber": sampleNumber,
													"parameter": parameter,
													"value": value
												},
												success: function () {
													var label = $('label[for="' + input.attr('id') + '"');

													input.attr("style", "display: none");
													label.attr("style", "display: in-line; cursor: pointer");
	
													if (value === '') {
														label.text('  ');
													}
													else {
														label.text(value);
													}
												},
												error: function() {
													genericError();
												}
											});
										});
						
										textDiv.appendChild(cellInput);
									}
									else {
										//handle comments column separately because its a larger amount of text that needs to be displayed in multiple lines
										var label = document.createElement("label");
										label.style = "display: table-cell; cursor: pointer; white-space:normal !important; overflow-wrap: anywhere";
										label.setAttribute("class", "btn btn-thin inputHide");
										label.setAttribute("for", key + "-" + i);
										label.innerText = value;
										
										label.onclick = function () {
											var label = $(this);
											var input = $("#" + label.attr("for"));
											input.trigger("click");
											label.attr("style", "display: none");
											input.attr("style", "display: in-line");
										};
										
										textDiv.appendChild(label);
										
										var textArea = document.createElement("textarea");
										textArea.setAttribute("rows", "4");
										textArea.setAttribute("cols", "50");
										textArea.setAttribute("class", "tableInput");
										textArea.setAttribute("name", key + "-" + i);
										textArea.setAttribute("style", "display: none");
										textArea.setAttribute("id", key + "-" + i);
										textArea.innerText = value;
										
										textArea.onfocusout = (function () {
											var input = $(this);

											if (!input.attr("id")) {
												return;
											}
			
											var rowNumber = (input.attr("id")).split("-")[1];
											var sampleNumber = $("#Sample_Number-" + rowNumber).val();
		
											var parameter = (input.attr("name")).split("-")[0];
											var value = input.val();
	
											$.ajax({
												type: "POST",
												url: "/WQIS/generic-samples/updatefield",
												datatype: "JSON",
												data: {
													"sampleNumber": sampleNumber,
													"parameter": parameter,
													"value": value
												},
												success: function () {
													var label = $('label[for="' + input.attr('id') + '"');

													input.attr("style", "display: none");
													label.attr("style", "display: in-line; cursor: pointer");
	
													if (value === '') {
														label.text('  ');
													}
													else {
														label.text(value);
													}
												},
												error: function() {
													genericError();
												}
											});
										});
										
										textDiv.appendChild(textArea);
									}
								}
								else {
									if (!key.includes("Comment")) {
										var label = document.createElement("label");
										label.style = "display: table-cell; cursor: pointer; white-space:normal !important;";
										label.setAttribute("for", key + "-" + i);
										label.innerText = value;
							
										newCell.appendChild(label);
									}
								}
							}
						});
				
						if (admin && !aggregateMode) {
							//add the deletion button
							var newCell = newRow.insertCell();
							var delButton = document.createElement("span");
							delButton.setAttribute("class", "delete glyphicon glyphicon-trash");
							delButton.setAttribute("id", "Delete-" + i);
							delButton.setAttribute("name", "Delete-" + i);
							delButton.onclick = function() {
								var rowDiv = this;
		
								if (!$(rowDiv).attr("id")) {
									return;
								}
			
								$.confirm("Are you sure you want to delete this record?", function (bool) {
									if (bool) {
										//delete record with this sample number and category
										$.ajax({
											type: "POST",
											url: "/WQIS/generic-samples/deleteRecord",
											datatype: "JSON",
											data: {
												"sampleNumber": $("#Sample_Number-" + ($(rowDiv).attr("id")).split("-")[1]).val(),
												"type": category
											},
											success: function () {
												//remove the row from view
												rowDiv.parentNode.parentNode.style.display = "none";
						
												//future work: build a new table, to still maintain correct total number of rows and have correct black/white/black sequencing after deletions
											},
											error: function () {
												genericError();
											}
										});
									}
								});
							}
							newCell.append(delButton);
						}
					}

					document.getElementById("tableDiv").append(table);
				},
				error: function(response) {
					genericError();
				}
			});
		}
		else {
			document.getElementById("tableNoData").style = "display: block";
			document.getElementById("chartsNoData").style = "display: block";
			document.getElementById("tableSettings").style = "display: none";
			document.getElementById("tablePageSelector").style = "display: none";
		}
	}
	
	$("#numRowsDropdown").change(function() {
		getNumRecords();
		getTableData(1);
	});
	
	$("#firstPageButton").click(function() {
		getTableData(1);
	});
	
	$("#previousPageButton").click(function() {
		getTableData(tablePage-1);
	});
	
	$("#nextPageButton").click(function() {
		getTableData(tablePage+1);
	});
	
	$("#lastPageButton").click(function() {
		getTableData(numPages);
	});
	
	$("#chartType").change(function() {
		for (i=0; i<charts.length; i++) {
			var datasets = charts[i].data.datasets;
			var showLine = ($("#chartType").val() === "line");
			
			for (j=0; j<datasets.length; j++) {
				datasets[j].showLine = showLine;
			}
			
			charts[i].update(0);
		}
	});
	
	function getNumRecords() {
		//get the number of records
		$.ajax({
			type: "POST",
			url: "/WQIS/generic-samples/tablePages",
			datatype: "JSON",
			async: false,
			data: {
				"sites": $("#sites").val(),
				"startDate": $("#startDate").val(),
				"endDate": $("#endDate").val(),
				"category": document.getElementById("categorySelect").value,
				"amountEnter": document.getElementById("amountEnter").value,
				"overUnderSelect": document.getElementById("overUnderSelect").value,
				"measurementSearch": document.getElementById("measurementSelect").value,
				"selectedMeasures": getSelectedMeasures(),
				"aggregate": document.getElementById("aggregateGroup").checked
			},
			success: function(response) {
				numResults = response[0];
				var numRows = document.getElementById("numRowsDropdown").value;
				if (numRows > -1) {
					numPages = Math.ceil(numResults / numRows);
				}
				else {
					numPages = 1;
				}
				document.getElementById("totalPages").innerText = numPages;
			},
			error: function(response) {
				genericError();
			}
		});
	}
	
	function toggleSearchSidebar() {
		//expand the search sidebar and shift the rest of the page over, or the opposite
		if (document.getElementById("sidebarInner").style.width == "20vw") {
			closeSearchSidebar();
		}
		else {
			openSearchSidebar();
		}
	}
	
	function openSearchSidebar() {
		document.getElementById("sidebarInner").style.width = "20vw";
		document.getElementById("sidebarInner").style.paddingLeft = "10px";
		document.getElementById("sidebarInner").style.paddingRight = "10px";
		document.getElementById("main").style.marginLeft = "20.5vw";
		document.getElementById("sidebarToggleLabel").innerText = "CLOSE";
		document.getElementById("main").style.width = "77vw";
	}
	
	function closeSearchSidebar() {
		document.getElementById("sidebarInner").style.width = 0;
		document.getElementById("sidebarInner").style.paddingLeft = 0;
		document.getElementById("sidebarInner").style.paddingRight = 0;
		document.getElementById("main").style.marginLeft = "15px";
		document.getElementById("sidebarToggleLabel").innerText = "OPEN";
		document.getElementById("main").style.width = "100%";
	}
	
	//set the search sidebar open at start
	openSearchSidebar();

	function resetCharts() {
		//remove the old chart
		document.getElementById("chartDiv").innerHTML = "";
		
		document.getElementById("chartsNoData").style = "display: block";
	}
	
	function resetTable() {
		//remove the old table
		document.getElementById("tableDiv").innerHTML = "";
		
		var sampleTable = document.getElementById("tableView");
		if (sampleTable != null) {
			sampleTable.parentNode.removeChild(sampleTable);
		}
		
		document.getElementById("tableNoData").style = "display: block";
		document.getElementById("tableSettings").style = "display: none";
		document.getElementById("tablePageSelector").style = "display: none";
	}
	
	function resetAll() {
		resetCharts();
		resetTable();
	}
	
	function setSort(e) {
		var field = e.srcElement.id;
		
		if (field === "") {
			//we probably clicked on the sorting icon, get its parent node and try again
			field = e.srcElement.parentElement.id;
		}
		
		//check if this was already the sortBy field, if so then we swap the sort direction
		if (sortBy === field) {
			if (sortDirection === "Desc") {
				sortDirection = "Asc";
			}
			else {
				sortDirection = "Desc";
			}
		}
		
		resetTable();
		sortBy = field;
		
		getTableData(1);
	}
	
	function getSelectedMeasures() {
		var measures = [];
		var checkboxList = document.getElementsByClassName("measurementCheckbox");
		
		for (var i=0; i<checkboxList.length; i++) {
			if (checkboxList[i].checked) {
				measures.push(checkboxList[i].value);
			}
		}
		
		return measures;
	}

	function getGraphData() {
		charts = [];

		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		var sites = $("#sites").val();
		var measures = getSelectedMeasures();
		var category = $("#categorySelect").val();
		var amountEnter = document.getElementById("amountEnter").value;
		var overUnderSelect = document.getElementById("overUnderSelect").value;
		var measurementSearch = document.getElementById("measurementSelect").value;
		
		//build the necessary canvases
		var chartDiv = document.getElementById("chartDiv");
		var nMeasures = measures.length;
		
		if (chartsDisplayMode === "in-line") {
			for (var k=0; k<nMeasures; k++) {
				var newCanvasContainer = document.createElement("div");
				newCanvasContainer.style = "width: 80%; text-align: center; margin: auto";
			
				var newCanvas = document.createElement("canvas");
				newCanvas.id = "chart-" + k;
				newCanvasContainer.appendChild(newCanvas);
				chartDiv.appendChild(newCanvasContainer);
			}
		}
		else {
			//grid view
			var chartsGrid = document.createElement("div");
			chartsGrid.setAttribute("class", "container");
			
			//figure out the number of rows with 2 columns each
			var nx = 2;
			var ny = Math.ceil(nMeasures/nx);
			var chartNum = 0;
			for (y=0; y<ny; y++) {
				var row = document.createElement("div");
				row.setAttribute("class", "row");
				
				for (x=0; x<nx; x++) {
					var cell = document.createElement("div");
					cell.setAttribute("class", "col-sm");
				
					var newCanvasContainer = document.createElement("div");
					newCanvasContainer.style = "width: 100%; text-align: center; margin: auto;";
				
					var newCanvas = document.createElement("canvas");
					newCanvas.id = "chart-" + chartNum;
					newCanvasContainer.appendChild(newCanvas);
				
					cell.appendChild(newCanvasContainer);
					row.appendChild(cell);
					chartNum++;
				}
				chartsGrid.appendChild(row);
			}
			chartDiv.appendChild(chartsGrid);
		}
		
		var aggregateMode = document.getElementById("aggregateGroup").checked;
		
		$.ajax({
			type: "POST",
			url: "/WQIS/generic-samples/graphdata",
			datatype: "JSON",
			async: false,
			data: {
				"sites": sites,
				"startDate": startDate,
				"endDate": endDate,
				"selectedMeasures": measures,
				"category": category,
				"amount": amountEnter,
				"overUnderSelect": overUnderSelect,
				"measurementSearch": measurementSearch,
				"aggregate": aggregateMode
			},
			success: function(response) {
				if (!aggregateMode) {
					//individual mode
					for (k=0; k<measures.length; k++) {
						var datasets = [];

						for (i=0; i<sites.length; i++) {
							var newDataset = {
								label: sites[i],
								borderColor: selectColor(i, sites.length),
								data: [],
								lineTension: 0,
								fill: false,
								borderWidth: 1.5,
								showLine: (document.getElementById("chartType").value === "line"),
								spanGaps: true,
							};
							
							datasets.push(newDataset);
						}
						
						var labels = [];
						for (i=0; i<response.length; i++) {
							var newRow = [];
							var date = response[i].Date.split("T")[0];
							newRow.t = date;
							newRow.y = response[i][measures[k]];
							
							for (j=0; j<sites.length; j++) {
								if (response[i].site == sites[j]) {
									datasets[j].data.push(newRow);
									break;
								}
							}
						
							//make sure there isn't already a label created for this date, or things break in weird ways
							if (!labels.includes(date)) {
								labels.push(date);
							}
						}
						
						buildChart(k, category, measures, labels, datasets);
					}
				}
				else {
					//aggregate mode
					for (k=0; k<measures.length; k++) {
						var dataset = {
							label: "Average",
							borderColor: selectColor(0, 1),
							data: [],
							lineTension: 0,
							fill: false,
							borderWidth: 1.5,
							showLine: (document.getElementById("chartType").value === "line"),
							spanGaps: true,
						};
	
						var labels = [];
						for (i=0; i<response.length; i++) {
							var newRow = [];
							var date = response[i].Date.split("T")[0];
							newRow.t = date;
							newRow.y = response[i][measures[k]];
		
							dataset.data.push(newRow);
		
							//make sure there isn't already a label created for this date, or things break in weird ways
							if (!labels.includes(date)) {
								labels.push(date);
							}
						}
	
						buildChart(k, category, measures, labels, [dataset]);
					}	
				}
			}
		});
	}
	
	function buildChart(k, category, measures, labels, datasets) {
		var ctx = document.getElementById("chart-" + k).getContext("2d");
	
		var measureKey;
		//get index of this measure so we can find its printable name
		var measureIndex;
		for (measureIndex=0; measureIndex<measurementSettings[category].length; measureIndex++) {
			if (measurementSettings[category][measureIndex].measureKey === measures[k]) {
				measureKey = measurementSettings[category][measureIndex].measureName;
				break;
			}
		}
	
		var benchmarkAnnotation = {};
		if (showBenchmarks) {
			var benchmarkMax = measurementSettings[category][measureIndex].benchmarkMaximum;
			var benchmarkMin = measurementSettings[category][measureIndex].benchmarkMinimum;
			var benchmarkLines = [];

			if (benchmarkMax != null) {
				benchmarkLines.push(benchmarkLine(benchmarkMax, "red"));
			}
			if (benchmarkMin != null) {
				benchmarkLines.push(benchmarkLine(benchmarkMin, "blue"));
			}
			benchmarkAnnotation = {annotations: benchmarkLines};
		}
		
		charts.push(new Chart(ctx, {
			type: "line",
			data: {
				labels: labels,
				datasets: datasets
			},
			options: {
				annotation: benchmarkAnnotation,
				scales: {
					yAxes: [{
						scaleLabel: {
							display: true,
							labelString: measureKey
						}
					}]
				},
				pan: {
					enabled: true,
					mode: "x",
					speed: 100
				},
				zoom: {
					enabled: true,         
					mode: "x",
				},
				responsive: true
			}
		}));
	}
	
	if(!localStorage.getItem("visited")){
		driver.start();
		localStorage.setItem("visited",true);
	}
	

	spinnerInhibited = false;
});