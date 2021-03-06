//loading graphic
$(document).ajaxStart(function () {
	document.getElementById("loadingSpinner").style.visibility = "visible";
	$("body").css("cursor", "wait");
}).ajaxStop(function () {
	document.getElementById("loadingSpinner").style.visibility = "hidden";
	$("body").css("cursor", "default");
});

$(document).ready(function () {
	$("#add-date").val("");
	$("#add-feedback").val("");
	$(function () {
		$('[data-toggle="tooltip"]').tooltip();
	});
	$(".info").tooltip();
	$("#delete-tooltip").tooltip();
	$("#message").on("click", function () {
		$(this).addClass("hidden");
	});

	$("body").on("click", ".delete", function () {
		var input = $(this);
		if (!input.attr("id")) {
			return;
		}
		
		var deleteRecord = window.confirm("Are you sure you want to delete this record?");
		if (deleteRecord) {
			var id = (input.attr("id")).split("-")[1];

			//now send ajax data to a delete script
			$.ajax({
				type: "POST",
				url: "deleteFeedback",
				datatype: "JSON",
				data: {
					"ID": id
				},
				success: function () {
					var num = $("#td-" + id + "-siteNum").text();

					$("#tr-" + id).remove();
					$(".message").html("Record: <strong>" + num + "</strong> has been deleted");
					$(".message").removeClass("error");
					$(".message").removeClass("hidden");
					$(".message").removeClass("success");
					$(".message").addClass("success");
				},
				error: function () {
					$(".message").html("Record: <strong>" + id + "</strong> was unable to be deleted");
					$(".message").removeClass("success");
					$(".message").removeClass("error");
					$(".message").addClass("error");
				}
			});
		}
	});
});