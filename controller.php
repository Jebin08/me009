<?php
header("Content-Type: application/json");
require_once "config/Database.php";

$db = (new Database())->connect();
$action = $_REQUEST['action'] ?? '';

if($action == 'fetch_list')    listData($db);
elseif($action == 'fetch_single') getOne($db);
elseif($action == 'save')      saveData($db);
elseif($action == 'update')    updateData($db);
elseif($action == 'soft_delete') deleteData($db);
elseif($action == 'restore')   restoreData($db);
elseif($action == 'fetch_deleted') trashData($db);
elseif($action == 'get_districts') districts($db);
elseif($action == 'fetch_logs') logs($db);
else echo json_encode(["status" => false, "message" => "Invalid action"]);


function listData($db) {
  $search = trim($_GET['search'] ?? '');
  $page   = (int)($_GET['page']  ?? 1);
  $limit  = (int)($_GET['limit'] ?? 10);
  $offset = ($page - 1) * $limit;

  if($search != '') {
    $like = "%$search%";
    $stmt = $db->prepare("SELECT r.*, s.state_name, d.district_name
      FROM registrations r
      LEFT JOIN states s ON s.id = r.state_id
      LEFT JOIN districts d ON d.id = r.district_id
      WHERE r.is_deleted = 0
      AND (r.full_name LIKE ? OR r.mobile LIKE ? OR r.email LIKE ?)
      ORDER BY r.id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $like);
    $stmt->bindValue(2, $like);
    $stmt->bindValue(3, $like);
    $stmt->bindValue(4, $limit, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $c = $db->prepare("SELECT COUNT(*) FROM registrations r WHERE r.is_deleted = 0
      AND (r.full_name LIKE ? OR r.mobile LIKE ? OR r.email LIKE ?)");
    $c->execute([$like, $like, $like]);
  } else {
    $stmt = $db->prepare("SELECT r.*, s.state_name, d.district_name
      FROM registrations r
      LEFT JOIN states s ON s.id = r.state_id
      LEFT JOIN districts d ON d.id = r.district_id
      WHERE r.is_deleted = 0
      ORDER BY r.id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $c = $db->prepare("SELECT COUNT(*) FROM registrations WHERE is_deleted = 0");
    $c->execute();
  }

  $total = $c->fetchColumn();
  echo json_encode([
    "status" => true,
    "data"   => $data,
    "total"  => $total,
    "current_page" => $page,
    "total_pages"  => ceil($total / $limit)
  ]);
}


function getOne($db) {
  $id   = (int)($_GET['id'] ?? 0);
  $stmt = $db->prepare("SELECT * FROM registrations WHERE id = ?");
  $stmt->execute([$id]);
  $row  = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode($row ? $row : []);
}


function saveData($db) {
  $name     = trim($_POST['full_name']    ?? '');
  $mobile   = trim($_POST['mobile']       ?? '');
  $email    = trim($_POST['email']        ?? '');
  $state    = (int)($_POST['state_id']    ?? 0);
  $district = (int)($_POST['district_id'] ?? 0);

  if(strlen($name) < 3) { echo json_encode(["status"=>false,"message"=>"Name too short"]); return; }
  if(!preg_match("/^[6-9][0-9]{9}$/", $mobile)) { echo json_encode(["status"=>false,"message"=>"Invalid mobile"]); return; }
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(["status"=>false,"message"=>"Invalid email"]); return; }

  $photo = null;
  if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if(in_array($ext, ['jpg','jpeg','png','gif'])) {
      if(!is_dir('uploads/photo/')) mkdir('uploads/photo/', 0755, true);
      $photo = 'uploads/photo/' . uniqid() . '.' . $ext;
      move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }
  }

  try {
    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO registrations (full_name, mobile, email, state_id, district_id, photo) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$name, $mobile, $email, $state, $district, $photo]);
    $id = $db->lastInsertId();
    addLog($db, 'CREATE', $id, $name);
    $db->commit();
    echo json_encode(["status" => true]);
  } catch(Exception $e) {
    $db->rollBack();
    echo json_encode(["status" => false, "message" => "Save failed"]);
  }
}


function updateData($db) {
  $id       = (int)($_POST['id']          ?? 0);
  $name     = trim($_POST['full_name']    ?? '');
  $mobile   = trim($_POST['mobile']       ?? '');
  $email    = trim($_POST['email']        ?? '');
  $state    = (int)($_POST['state_id']    ?? 0);
  $district = (int)($_POST['district_id'] ?? 0);

  if($id == 0) { echo json_encode(["status"=>false,"message"=>"Invalid ID"]); return; }
  if(strlen($name) < 3) { echo json_encode(["status"=>false,"message"=>"Name too short"]); return; }
  if(!preg_match("/^[6-9][0-9]{9}$/", $mobile)) { echo json_encode(["status"=>false,"message"=>"Invalid mobile"]); return; }
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(["status"=>false,"message"=>"Invalid email"]); return; }

  try {
    $db->beginTransaction();

    $photo = null;
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
      $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
      if(in_array($ext, ['jpg','jpeg','png','gif'])) {
        if(!is_dir('uploads/photo/')) mkdir('uploads/photo/', 0755, true);
        $photo = 'uploads/photo/' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);

        $old = $db->prepare("SELECT photo FROM registrations WHERE id = ?");
        $old->execute([$id]);
        $oldPhoto = $old->fetchColumn();
        if($oldPhoto && file_exists($oldPhoto)) unlink($oldPhoto);
      }
    }

    if($photo) {
      $stmt = $db->prepare("UPDATE registrations SET full_name=?, mobile=?, email=?, state_id=?, district_id=?, photo=? WHERE id=?");
      $stmt->execute([$name, $mobile, $email, $state, $district, $photo, $id]);
    } else {
      $stmt = $db->prepare("UPDATE registrations SET full_name=?, mobile=?, email=?, state_id=?, district_id=? WHERE id=?");
      $stmt->execute([$name, $mobile, $email, $state, $district, $id]);
    }

    addLog($db, 'UPDATE', $id, $name);
    $db->commit();
    echo json_encode(["status" => true]);
  } catch(Exception $e) {
    $db->rollBack();
    echo json_encode(["status" => false, "message" => "Update failed"]);
  }
}


function deleteData($db) {
  $id = (int)($_POST['id'] ?? 0);
  if($id == 0) { echo json_encode(["status"=>false,"message"=>"Invalid ID"]); return; }

  $s = $db->prepare("SELECT full_name FROM registrations WHERE id = ?");
  $s->execute([$id]);
  $name = $s->fetchColumn();

  $db->beginTransaction();
  $stmt = $db->prepare("UPDATE registrations SET is_deleted=1, deleted_at=NOW() WHERE id=?");
  $stmt->execute([$id]);
  addLog($db, 'DELETE', $id, $name);
  $db->commit();
  echo json_encode(["status" => true]);
}


function restoreData($db) {
  $id = (int)($_POST['id'] ?? 0);
  if($id == 0) { echo json_encode(["status"=>false,"message"=>"Invalid ID"]); return; }

  $s = $db->prepare("SELECT full_name FROM registrations WHERE id = ?");
  $s->execute([$id]);
  $name = $s->fetchColumn();

  $db->beginTransaction();
  $stmt = $db->prepare("UPDATE registrations SET is_deleted=0, deleted_at=NULL WHERE id=?");
  $stmt->execute([$id]);
  addLog($db, 'RESTORE', $id, $name);
  $db->commit();
  echo json_encode(["status" => true]);
}


function trashData($db) {
  $stmt = $db->query("SELECT * FROM registrations WHERE is_deleted = 1 ORDER BY id DESC");
  echo json_encode(["status" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}


function districts($db) {
  $sid  = (int)($_GET['state_id'] ?? 0);
  $stmt = $db->prepare("SELECT id, district_name FROM districts WHERE state_id = ? ORDER BY district_name");
  $stmt->execute([$sid]);
  echo json_encode(["status" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}


function logs($db) {
  $stmt = $db->query("SELECT * FROM audit_log ORDER BY id DESC LIMIT 100");
  echo json_encode(["status" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}


function addLog($db, $action, $id, $name) {
  $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $stmt = $db->prepare("INSERT INTO audit_log (action, record_id, record_name, ip_address) VALUES (?,?,?,?)");
  $stmt->execute([$action, $id, $name, $ip]);
}
?>