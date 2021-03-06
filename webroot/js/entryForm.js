$(document).ready(function () {
	$(function () {
		$(".date-picker").datepicker({
			trigger: "focus",
			format: "mm/dd/yyyy",
			todayHighlight: true,
			todayBtn: "linked"
		});
	});

	$("#addSite").click(function () {
		var $table = $("#tableBody");
		var rowCounter = $("#totalrows");

		var rowNumber = parseInt(rowCounter.val()) + 1;
		rowCounter.val(rowNumber);
		var $clone = $("#row-0").clone();

		$clone.find("td").each(function () {
			var el = $(this).find(":first-child");
			var id = el.attr("id") || null;

			if (id) {
				var prefix = id.substr(0, (id.length - 2));
				prefix = prefix.replace("-", "_");

				el.attr("id", prefix + "-" + rowNumber);
				el.attr("name", prefix + "-" + rowNumber);
			}
		});

		$clone.find(":input").val("");

		$clone.attr("id", "row-" + rowNumber);
		$clone.find("span").removeAttr("hidden");
		$table.append($clone);
	});

	$("#date").change(function () {
		//we must update all the site rows currently in play
		var rowCounter = parseInt($("#totalrows").val()) + 1;

		for (var i=0; i<rowCounter; i++) {
			helpSampleNumber(document.querySelector("#site_location_id-" + i).value, document.querySelector("#date").value, "#sample_number-" + i);
		}
	});
	
	//this allows us to determine which element was selected
	$(document).on("change", "select.siteselect", (function () {
		//this retrieves the site number from the selected site. Much easier than gleaning it from the label
		var row = $(this);
		var siteData = document.querySelector("#" + row.attr("id")).value;
		var dateData = document.querySelector("#date").value;

		//concatenate the row number to the samplenumber string so we can use that for later
		var sampleString = "#sample_number-" + row.attr("id").split("-")[1];

		helpSampleNumber(siteData, dateData, sampleString);
	}));

	$(document).on("click", "span.delete", (function () {
		var input = $(this);
		if (!input.attr("id")) {
			return;
		}
		var bool = window.confirm("Are you sure you want to delete this row?");
		if (bool) {
			var rowNumber = parseInt((input.attr("id")).split("-")[1]);
			deleteRow(rowNumber);
		}
	}));

	function deleteRow(rowNumber) {
		var deletedRow = $("#row-" + rowNumber);
		var rowCounter = $("#totalrows");
		var totalRows = parseInt(rowCounter.val());

		//this is all just a big shift operation.
		for (var current = deletedRow.next("tr"); rowNumber <= totalRows; rowNumber++, current = current.next("tr")) {
			current.find("td").each(function () {
				var el = $(this).find(":first-child");
				var id = el.attr("id") || null;
				if (id) {
					var i = id.substr(id.length - 1);
					var prefix = id.substr(0, (id.length - 2));
					prefix = prefix.replace("-", "_");
					el.attr("id", prefix + "-" + rowNumber);
					el.attr("name", prefix + "-" + rowNumber);
				}
			});
			current.attr("id", "row-" + rowNumber);
		}

		deletedRow.remove();
		rowCounter.val(totalRows - 1);
	}
});

function helpSampleNumber(site, date, sample) {
	//provided that date is not null, and that the siteData field isn't null, we can auto populate a sample number
	if (date !== null && date !== "" && site !== "" && site !== null) {
		//the date data should be formatted as dd/mm/yyyy. So pull out the '/' and reconcatenate
		var tokenizedDate = date.split("/");

		document.querySelector(sample).value = Number(site + tokenizedDate[0] + tokenizedDate[1] + tokenizedDate[2][2] + tokenizedDate[2][3]);
	}
	else {
		//if one of them is null, change the sample number to nothing
		document.querySelector(sample).value = "";
	}
};