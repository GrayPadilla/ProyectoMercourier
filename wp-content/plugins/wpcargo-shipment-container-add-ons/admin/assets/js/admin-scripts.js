jQuery(document).ready(function ($) {
  var wpcargoDateFormat = shipmentContainerAjaxHandler.date_format;
  var wpcargoTimeFormat = shipmentContainerAjaxHandler.time_format;

  $("#container-shipment-list-wrapper").sortable({
    cursorAt: { left: 0 },
    update: function (event, ui) {
      shipment_sorter();
    },
  });
  function shipment_sorter() {
    var selectedShipment = [];
    $("#container-shipment-list-wrapper .selected-shipment").each(function (
      index
    ) {
      var shipmentID = $(this).data("shipment");
      selectedShipment.push(shipmentID);
    });
    $("#wpcc_sorted_shipments").val(selectedShipment.join(","));
  }
  $("#container-shipment-list-wrapper").disableSelection();
  //** Select2 script
  if ($.fn.select2) {
    $(".select-agent").select2();
  }
  //** Date and Time Picker Script

  if ($.fn.datetimepicker) {
    $(".wpcargo-timepicker").datetimepicker({
      datepicker: false,
      format: wpcargoTimeFormat,
      pickerPosition: "top-right",
    });
    $(".wpcargo-datepicker").datetimepicker({
      timepicker: false,
      format: wpcargoDateFormat,
    });
    $(".row-actions button.button-link.editinline").on("click", function () {
      $(".wpcargo-timepicker").datetimepicker({
        datepicker: false,
        format: wpcargoTimeFormat,
      });
      $(".wpcargo-datepicker").datetimepicker({
        timepicker: false,
        format: wpcargoDateFormat,
        pickerPosition: "top-right",
      });
    });
  }
  //** Repeater Script
  ("use strict");
  if ($.fn.repeater) {
    $("#container-history-wrapper").repeater({
      show: function () {
        $(this).slideDown();
        if ($.fn.select2) {
          $(".select-agent").select2();
        }
        //** Date and Time Picker Script
        if ($.fn.datetimepicker) {
          $(".wpcargo-timepicker").datetimepicker({
            datepicker: false,
            format: wpcargoTimeFormat,
          });
          $(".wpcargo-datepicker").datetimepicker({
            timepicker: false,
            format: wpcargoDateFormat,
          });
        }
        var sectionId = $("#container-history tbody").children().length;
        $("#container-history tbody")
          .children()
          .last()
          .attr("id", "history-" + sectionId);
      },
      hide: function (deleteElement) {
        if (confirm("Are you sure you want to delete this element?")) {
          $(this).slideUp(deleteElement);
        }
        setTimeout(function () {
          if ($.fn.datetimepicker) {
            $(".wpcargo-timepicker").datetimepicker({
              datepicker: false,
              format: wpcargoTimeFormat,
            });
            $(".wpcargo-datepicker").datetimepicker({
              timepicker: false,
              format: wpcargoDateFormat,
            });
          }
        }, 0);
      },
      ready: function (setIndexes) {},
    });
  }
  function reset_index() {
    $("#container-history .history_section").each(function (index) {
      var itemNo = index + 1;
      $(this).attr("id", "history-" + itemNo);
    });
  }
  $("#container-history").on("click", ".delete-history", function () {
    setTimeout(function () {
      reset_index();
    }, 500);
  });
  //** AJAX Script
  var dataColumn = [];
  $.each(shipmentContainerAjaxHandler.dataTableInfo, function (index, value) {
    dataColumn.push({ data: index });
  });
  dataColumn.push({ data: "actions", class: "text-center" });
  var shipmentOptions = {};
  var shipmentTable = $("#shipment-options-table").DataTable({
    data: shipmentOptions,
    columns: dataColumn,
  });
  shipmentTable.on("draw", function () {
    $(".dataTables_paginate > .pagination").addClass("pagination-circle");
    $(".dataTables_length select").addClass("mdb-select md-form");
  });
  $(".wpcargo-modal").on("click", ".close", function () {
    $(this).parent().parent().parent().removeClass("wpcargo-show-modal");
  });
  $("#assigned-shipment").on("click", "#add-shipment", function (e) {
    e.preventDefault();
    $(".wpcargo-modal").addClass("wpcargo-show-modal");
    var postID = $(this).attr("data-id");
    $.ajax({
      type: "POST",
      datatype: "json",
      data: {
        action: "get_shipments",
        postID: postID,
      },
      url: shipmentContainerAjaxHandler.ajaxurl,
      beforeSend: function () {
        $("body").append('<div class="wpc-loading">Loading...</div>');
        shipmentTable.clear().draw();
      },
      success: function (data) {
        $("body .wpc-loading").remove();
        shipmentOptions = JSON.parse(data);
        shipmentTable.rows.add(shipmentOptions).search("").draw();
        return false;
      },
    });
  });
  // Assign shipment to container
  $("#shipment-options-table").on(
    "click",
    ".shipment-assign-icon",
    function (e) {
      e.preventDefault();
      var shipmentID = $(this).data("id");
      var containerID = $(this).data("ctn");
      $.ajax({
        type: "POST",
        datatype: "json",
        data: {
          action: "assign_shipment_admin",
          shipmentID: shipmentID,
          containerID: containerID,
        },
        url: shipmentContainerAjaxHandler.ajaxurl,
        beforeSend: function () {
          $("body").append('<div class="wpc-loading">Loading...</div>');
          shipmentTable.clear().draw();
        },
        success: function (data) {
          $("body .wpc-loading").remove();
          var data = JSON.parse(data);
          shipmentOptions = data.data;
          shipmentTable.rows.add(shipmentOptions).search("").draw();
          if (data.status == "success") {
            $("#container-shipment-list-wrapper").append(data.message);
            $("#shipment-options-table #opt_" + shipmentID).remove();
          } else {
            alert(data.message);
          }
          return false;
        },
      });
    }
  );
  $("#container-shipment-list-wrapper").on(
    "click",
    ".dashicons-dismiss",
    function (e) {
      var postID = $(this).attr("data-id");
      var parentID = $(this).parent().attr("id");
      $.ajax({
        type: "POST",
        data: {
          action: "remove_shipment",
          postID: postID,
        },
        url: shipmentContainerAjaxHandler.ajaxurl,
        beforeSend: function () {
          //**
          $("#shipment-info-id").append(
            '<div class="wpc-loading">Loading&#8230;</div>'
          );
        },
        success: function (data) {
          $("#shipment-info-id .wpc-loading").remove();
          if (data == true) {
            $("#" + parentID).remove();
          } else {
            alert(shipmentContainerAjaxHandler.processError);
          }
          shipment_sorter();
          return;
        },
      });
    }
  );
});
