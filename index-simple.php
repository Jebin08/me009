<?php 
include "config/Database.php"; 
$db = (new Database())->connect(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Simple Registration CRUD</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); font-family: 'Segoe UI', sans-serif; }
    .card { box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: none; border-radius: 12px; }
    .btn { border-radius: 8px; }
    .table { font-size: 0.95rem; }
    .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
    h2 { color: #495057; }
    .section { margin-bottom: 2rem; }
  </style>
</head>
<body class="py-4">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <h1 class="text-center mb-4 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Registration Manager</h1>
        
        <!-- Add/Edit Form Section -->
        <div class="card section">
          <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Registration</h3>
          </div>
          <div class="card-body">
            <form id="regForm" enctype="multipart/form-data">
              <input type="hidden" name="id" id="editId">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Full Name *</label>
                  <input type="text" name="full_name" class="form-control" required minlength="3">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Mobile *</label>
                  <input type="tel" name="mobile" class="form-control" required pattern="[6-9][0-9]{9}">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Email *</label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Photo</label>
                  <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">State *</label>
                  <select name="state_id" id="stateSelect" class="form-select" required>
                    <option value="">Select State</option>
                    <?php
                    $stmt = $db->query("SELECT * FROM states ORDER BY state_name");
                    while ($row = $stmt->fetch()) {
                      echo "<option value='{$row['id']}'>{$row['state_name']}</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">District</label>
                  <select name="district_id" id="districtSelect" class="form-select">
                    <option value="">Select District</option>
                  </select>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save me-2"></i>Save</button>
                <button type="button" id="cancelEdit" class="btn btn-secondary btn-lg ms-2">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Active Records -->
        <div class="card section">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>Active Records</h3>
            <div>
              <input type="text" id="searchInput" class="form-control form-control-sm d-inline-block w-auto me-2" placeholder="Search..." style="width: 200px;">
              <button id="refreshBtn" class="btn btn-light btn-sm"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="activeTable">
                  <!-- Loaded by JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Trash -->
        <div class="card section">
          <div class="card-header bg-warning text-dark">
            <h3 class="mb-0"><i class="bi bi-trash me-2"></i>Trash (Deleted)</h3>
          </div>
          <div class="card-body">
            <table class="table table-hover">
              <thead>
                <tr><th>Name</th><th>Mobile</th><th>Deleted</th><th>Action</th></tr>
              </thead>
              <tbody id="trashTable">
                <!-- Loaded by JS -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Logs -->
        <div class="card section">
          <div class="card-header bg-info text-white">
            <h3 class="mb-0"><i class="bi bi-journal-text me-2"></i>Activity Logs</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr><th>Time</th><th>Action</th><th>Record</th><th>IP</th></tr>
                </thead>
                <tbody id="logsTable">
                  <!-- Loaded by JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
 <script>

// LOAD ACTIVE
function loadData()
{
    var search = $("#search").val();

    $.get("controller.php",
    {
        action : "list",
        search : search
    },
    function(res)
    {
        var html = "";

        if(res.length > 0)
        {
            for(var i = 0; i < res.length; i++)
            {
                html += "<tr>";

                html += "<td>" + res[i].name + "</td>";
                html += "<td>" + res[i].mobile + "</td>";
                html += "<td>" + res[i].email + "</td>";

                html += "<td>";
                html += "<button class='edit btn btn-sm btn-warning' data-id='"+res[i].id+"'>Edit</button> ";
                html += "<button class='del btn btn-sm btn-danger' data-id='"+res[i].id+"'>Delete</button>";
                html += "</td>";

                html += "</tr>";
            }
        }
        else
        {
            html = "<tr><td colspan='4' class='text-center'>No data</td></tr>";
        }

        $("#data").html(html);

    }, "json");
}


// LOAD TRASH

// LOAD ACTIVE DATA
function loadData()
{
    var search = $("#searchInput").val();

    $.get("controller.php",
    {
        action : "fetch_list",
        search : search
    },
    function(res)
    {
        var html = "";

        if(res.status && res.data.length > 0)
        {
            for(var i = 0; i < res.data.length; i++)
            {
                var r = res.data[i];

                html += "<tr>";

                // photo
                html += "<td>";
                if(r.photo != "")
                {
                    html += "<img src='"+r.photo+"' class='avatar'>";
                }
                else
                {
                    html += "<i class='bi bi-person-circle'></i>";
                }
                html += "</td>";

                html += "<td>" + r.full_name + "</td>";
                html += "<td>" + r.mobile + "</td>";
                html += "<td>" + r.email + "</td>";
                html += "<td>" + (r.state_name ? r.state_name : "N/A") + "</td>";

                html += "<td>";
                html += "<button class='editBtn btn btn-sm btn-warning' data-id='"+r.id+"'>Edit</button> ";
                html += "<button class='delBtn btn btn-sm btn-danger' data-id='"+r.id+"'>Delete</button>";
                html += "</td>";

                html += "</tr>";
            }
        }
        else
        {
            html = "<tr><td colspan='6' class='text-center'>No records</td></tr>";
        }

        $("#activeTable").html(html);

    }, "json");
}


// LOAD TRASH
function loadTrash()
{
    $.get("controller.php",
    {
        action : "fetch_deleted"
    },
    function(res)
    {
        var html = "";

        if(res.status && res.data.length > 0)
        {
            for(var i = 0; i < res.data.length; i++)
            {
                var r = res.data[i];

                html += "<tr>";

                html += "<td>" + r.full_name + "</td>";
                html += "<td>" + r.mobile + "</td>";
                html += "<td>" + r.deleted_at + "</td>";

                html += "<td>";
                html += "<button class='restoreBtn btn btn-sm btn-success' data-id='"+r.id+"'>Restore</button>";
                html += "</td>";

                html += "</tr>";
            }
        }
        else
        {
            html = "<tr><td colspan='4' class='text-center'>No trash</td></tr>";
        }

        $("#trashTable").html(html);

    }, "json");
}


// LOAD LOGS
function loadLogs()
{
    $.get("controller.php",
    {
        action : "fetch_logs"
    },
    function(res)
    {
        var html = "";

        if(res.status && res.data.length > 0)
        {
            for(var i = 0; i < res.data.length; i++)
            {
                var l = res.data[i];

                html += "<tr>";

                html += "<td>" + l.logged_at + "</td>";
                html += "<td>" + l.action + "</td>";
                html += "<td>" + l.record_name + "</td>";
                html += "<td>" + l.ip_address + "</td>";

                html += "</tr>";
            }
        }
        else
        {
            html = "<tr><td colspan='4' class='text-center'>No logs</td></tr>";
        }

        $("#logsTable").html(html);

    }, "json");
}


// STATE -> DISTRICT
$("#stateSelect").change(function()
{
    var id = $(this).val();

    $.get("controller.php",
    {
        action : "get_districts",
        state_id : id
    },
    function(res)
    {
        var opt = "<option value=''>Select District</option>";

        if(res.data)
        {
            for(var i = 0; i < res.data.length; i++)
            {
                opt += "<option value='"+res.data[i].id+"'>"+res.data[i].district_name+"</option>";
            }
        }

        $("#districtSelect").html(opt);

    }, "json");
});


// SAVE / UPDATE
$("#regForm").submit(function(e)
{
    e.preventDefault();

    var fd = new FormData(this);

    if($("#editId").val() != "")
    {
        fd.append("action","update");
    }
    else
    {
        fd.append("action","save");
    }

    $.ajax({
        url : "controller.php",
        type : "POST",
        data : fd,
        processData : false,
        contentType : false,
        success : function(res)
        {
            if(res.status)
            {
                $("#regForm")[0].reset();
                $("#editId").val("");

                loadData();
                loadTrash();
                loadLogs();

                alert("Saved");
            }
            else
            {
                alert("Error");
            }
        }
    });
});


// EDIT
$(document).on("click",".editBtn",function()
{
    var id = $(this).data("id");

    $.get("controller.php",
    {
        action : "fetch_single",
        id : id
    },
    function(res)
    {
        if(res.status)
        {
            var r = res.data;

            $("#editId").val(r.id);

            $("input[name='full_name']").val(r.full_name);
            $("input[name='mobile']").val(r.mobile);
            $("input[name='email']").val(r.email);

            $("#stateSelect").val(r.state_id).change();

            setTimeout(function()
            {
                $("#districtSelect").val(r.district_id);
            },300);
        }
    },
    "json");
});

// DELETE
$(document).on("click",".delBtn",function()
{
    var id = $(this).data("id");

    if(confirm("Move to trash?"))
    {
        $.post("controller.php",
        {
            action : "soft_delete",
            id : id
        },
        function()
        {
            loadData();
            loadTrash();
        });
    }
});


// RESTORE
$(document).on("click",".restoreBtn",function()
{
    var id = $(this).data("id");

    if(confirm("Restore?"))
    {
        $.post("controller.php",
        {
            action : "restore",
            id : id
        },
        function()
        {
            loadData();
            loadTrash();
        });
    }
});


// SEARCH
$("#searchInput").keyup(function()
{
    loadData();
});


// REFRESH
$("#refreshBtn").click(function()
{
    loadData();
});


// CANCEL
$("#cancelEdit").click(function()
{
    $("#regForm")[0].reset();
    $("#editId").val("");
});


// INIT
$(document).ready(function()
{
    loadData();
    loadTrash();
    loadLogs();
});


</script>
</body>
</html>
