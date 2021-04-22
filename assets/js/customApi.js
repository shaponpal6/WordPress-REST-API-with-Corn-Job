(function () {
  var fetchApiData = function (section) {
    var pdxApiContainer = document.getElementById(section);
    var result = [];
    if (pdxApiContainer) {
      console.log("pdxApiContainer :>> ", pdxApiContainer);
      // Fetch all input data
      var rows = document.querySelectorAll(".apiRow");
      if (rows) {
        rows.forEach(function (ele) {
          var input = ele.querySelector("input");
          var key = input.dataset.key || null;
          var value = input.value || "";
          if (key) result[key] = value;
        });
      }

      // Fetch all object input data
      var apiObjBox = document.querySelectorAll(".apiObjBox");
      if (apiObjBox) {
        apiObjBox.forEach(function (ele) {
          var key = ele.dataset.key;
          var arr = [];
          var fields = ele.querySelectorAll(".apiRowObj");
          if (fields) {
            fields.forEach(function (ele) {
              var input = ele.querySelector("input");
              var key2 = input.dataset.key || null;
              var value = input.value || "";
              if (key2) arr[key2] = value;
            });
          }
          if (key) result[key] = Object.assign({}, arr);
        });
      }
    }
    return result;
  };
  var initAPIMarger2 = function (section, submit) {};
  var initAPIMarger = function (section, submit) {
    var target = document.getElementById(submit);
    console.log("target :>> ", target);
    if (target) {
      target.addEventListener(
        "click",
        function () {
          var data = fetchApiData(section);
          var idNode = document.getElementById("pdxApiRowId");
          console.log("data :>> ", data);
          console.log("data :>> ", data["Key"]);
          if (!data["Key"]) return jQuery("#pdxLog").html("Not valid");
          var action = {
            action: "pdx-sync-save-api-data",
            id: idNode ? idNode.value : 0,
            key: data["Key"] || "",
            data: JSON.stringify(Object.assign({}, data)),
          };
          jQuery.post(ajaxurl, action, function (response) {
            // localStorage.setItem("csv_data", data);
            // console.log("response :>> ", response);
            var result = JSON.parse(response) || {};
            // var result = parseInt(response) || 0;
            // console.log("response :>>2 ", response);
            // console.log("response :>>2 ", typeof response);
            console.log("response :>>2 ", result);
            // console.log("response :>>2 ", result["result"]);
            if (result["result"]) {
              jQuery("#pdxLog").html("Successfully updated data.");
              // window.location.href =
              //   window.location.pathname +
              //   window.location.search +
              //   window.location.hash;
            } else {
              jQuery("#pdxLog").html("Data not changed.");
            }
          });
        },
        false
      );
    }
  };

  document.addEventListener(
    "DOMContentLoaded",
    function () {
      initAPIMarger("pdxApiContainer", "pdxApiSubmit");
    },
    false
  );
})();
