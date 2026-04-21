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
    let currentSearch = '';

    // Load data functions (simple)
    function loadActive() {
      $.get('controller.php', {action: 'fetch_list', search: currentSearch}).done(data => {
        let html = '';
        if (data.status && data.data.length) {
          data.data.forEach(r => {
            html += `<tr>
              <td>${r.photo ? `<img src="${r.photo}" class="avatar" onerror="this.style.display='none'">` : '<i class="bi bi-person-circle avatar bg-light"></i>'}</td>
              <td><strong>${r.full_name}</strong></td>
              <td>${r.mobile}</td>
              <td>${r.email}</td>
              <td>${r.state_name || 'N/A'}</td>
              <td>
                <button class="btn btn-sm btn-warning editBtn" data-id="${r.id}">Edit</button>
                <button class="btn btn-sm btn-danger delBtn" data-id="${r.id}">Delete</button>
              </td>
            </tr>`;
          });
        } else {
          html = '<tr><td colspan="6" class="text-center py-4 text-muted">No records</td></tr>';
        }
        $('#activeTable').html(html);
      });
    }

    function loadTrash() {
      $.get('controller.php', {action: 'fetch_deleted'}).done(data => {
        let html = '';
        if (data.status && data.data.length) {
          data.data.forEach(r => {
            html += `<tr>
              <td>${r.full_name}</td>
              <td>${r.mobile}</td>
              <td>${r.deleted_at}</td>
              <td><button class="btn btn-sm btn-success restoreBtn" data-id="${r.id}">Restore</button></td>
            </tr>`;
          });
        } else {
          html = '<tr><td colspan="4" class="text-center py-4 text-muted">No deleted records</td></tr>';
        }
        $('#trashTable').html(html);
      });
    }

    function loadLogs() {
      $.get('controller.php', {action: 'fetch_logs'}).done(data => {
        let html = '';
        if (data.status && data.data.length) {
          data.data.forEach(l => {
            html += `<tr>
              <td>${new Date(l.logged_at).toLocaleString()}</td>
              <td><span class="badge bg-${l.action=='CREATE'?'success':l.action=='DELETE'?'danger':'warning'}">${l.action}</span></td>
              <td>${l.record_name}</td>
              <td>${l.ip_address}</td>
            </tr>`;
          });
        } else {
          html = '<tr><td colspan="4" class="text-center py-4 text-muted">No logs</td></tr>';
        }
        $('#logsTable').html(html);
      });
    }

    // State districts
    $('#stateSelect').change(function() {
      const stateId = $(this).val();
      $.get('controller.php', {action: 'get_districts', state_id: stateId}).done(data => {
        let opts = '<option value="">Select District</option>';
        if (data.data) data.data.forEach(d => opts += `<option value="${d.id}">${d.district_name}</option>`);
        $('#districtSelect').html(opts);
      });
    });

    // Form submit
    $('#regForm').submit(function(e) {
      e.preventDefault();
      const fd = new FormData(this);
      fd.append('action', $('#editId').val() ? 'update' : 'save');
      $.ajax({
        url: 'controller.php',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(res) {
          if (res.status) {
            $('#regForm')[0].reset();
            $('#editId').val('');
            loadActive();
            loadTrash();
            loadLogs();
            alert('Saved!');
          } else {
            alert('Error: ' + (res.message || 'Failed'));
          }
        }
      });
    });

    // Edit
    $(document).on('click', '.editBtn', function() {
      const id = $(this).data('id');
      $.get('controller.php', {action: 'fetch_single', id}).done(res => {
        if (res.id) {
          $('#editId').val(res.id);
          $('[name="full_name"]').val(res.full_name);
          $('[name="mobile"]').val(res.mobile);
          $('[name="email"]').val(res.email);
          $('#stateSelect').val(res.state_id).trigger('change');
          setTimeout(() => $('#districtSelect').val(res.district_id), 200);
          // Photo preview later
        }
      });
    });

    // Delete/Restore
    $(document).on('click', '.delBtn', function() {
      if (confirm('Move to trash?')) {
        const id = $(this).data('id');
        $.post('controller.php', {action: 'soft_delete', id}).done(() => {
          loadActive(); loadTrash();
        });
      }
    });
    $(document).on('click', '.restoreBtn', function() {
      if (confirm('Restore?')) {
        const id = $(this).data('id');
        $.post('controller.php', {action: 'restore', id}).done(() => {
          loadTrash(); loadActive();
        });
      }
    });

    // Search/Refresh
    $('#searchInput').on('keyup', function() {
      currentSearch = $(this).val();
      loadActive();
    });
    $('#refreshBtn').click(() => loadActive());

    // Cancel edit
    $('#cancelEdit').click(() => $('#regForm')[0].reset());

    // Init
    $(function() {
      loadActive();
      loadTrash();
      loadLogs();
    });
  </script>
</body>
</html>
