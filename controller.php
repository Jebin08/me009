
<?php
header("Content-Type: application/json");

include "config/Database.php";

$db = (new Database())->connect();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";


/* ================= ROUTER ================= */

if($action == "fetch_list")
{
    listData($db);
}
else if($action == "fetch_single")
{
    getOne($db);
}
else if($action == "save")
{
    saveData($db);
}
else if($action == "update")
{
    updateData($db);
}
else if($action == "soft_delete")
{
    deleteData($db);
}
else if($action == "restore")
{
    restoreData($db);
}
else if($action == "fetch_deleted")
{
    trashData($db);
}
else if($action == "get_districts")
{
    districts($db);
}
else if($action == "fetch_logs")
{
    logs($db);
}
else
{
    echo json_encode(["status"=>false]);
}



/* ================= LIST ================= */

function listData($db)
{
    $search = "";
    if(isset($_GET['search']))
    {
        $search = trim($_GET['search']);
    }

    if($search != "")
    {
        $like = "%".$search."%";

        $sql = "SELECT r.*, s.state_name, d.district_name
                FROM registrations r
                LEFT JOIN states s ON s.id = r.state_id
                LEFT JOIN districts d ON d.id = r.district_id
                WHERE r.is_deleted = 0
                AND (r.full_name LIKE ? OR r.mobile LIKE ? OR r.email LIKE ?)
                ORDER BY r.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$like,$like,$like]);
    }
    else
    {
        $sql = "SELECT r.*, s.state_name, d.district_name
                FROM registrations r
                LEFT JOIN states s ON s.id = r.state_id
                LEFT JOIN districts d ON d.id = r.district_id
                WHERE r.is_deleted = 0
                ORDER BY r.id DESC";

        $stmt = $db->query($sql);
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data"   => $data
    ]);
}



/* ================= SINGLE ================= */

function getOne($db)
{
    $id = 0;

    if(isset($_GET['id']))
    {
        $id = $_GET['id'];
    }

    $stmt = $db->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->execute([$id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data"   => $row
    ]);
}



/* ================= SAVE ================= */

function saveData($db)
{
    $name     = $_POST['full_name'];
    $mobile   = $_POST['mobile'];
    $email    = $_POST['email'];
    $state    = $_POST['state_id'];
    $district = $_POST['district_id'];

    // basic validation
    if(strlen($name) < 3)
    {
        echo json_encode(["status"=>false]);
        return;
    }

    $photo = "";

    if(isset($_FILES['photo']) && $_FILES['photo']['name'] != "")
    {
        $photo = "uploads/".time().$_FILES['photo']['name'];

        move_uploaded_file($_FILES['photo']['tmp_name'],$photo);
    }

    $sql = "INSERT INTO registrations
            (full_name,mobile,email,state_id,district_id,photo)
            VALUES (?,?,?,?,?,?)";

    $stmt = $db->prepare($sql);
    $stmt->execute([$name,$mobile,$email,$state,$district,$photo]);

    $id = $db->lastInsertId();

    addLog($db,"CREATE",$id,$name);

    echo json_encode(["status"=>true]);
}



/* ================= UPDATE ================= */

function updateData($db)
{
    $id       = $_POST['id'];
    $name     = $_POST['full_name'];
    $mobile   = $_POST['mobile'];
    $email    = $_POST['email'];
    $state    = $_POST['state_id'];
    $district = $_POST['district_id'];

    $photo = "";

    if(isset($_FILES['photo']) && $_FILES['photo']['name'] != "")
    {
        $photo = "uploads/".time().$_FILES['photo']['name'];

        move_uploaded_file($_FILES['photo']['tmp_name'],$photo);

        // delete old photo
        $old = $db->prepare("SELECT photo FROM registrations WHERE id=?");
        $old->execute([$id]);

        $oldImg = $old->fetchColumn();

        if($oldImg && file_exists($oldImg))
        {
            unlink($oldImg);
        }
    }

    if($photo != "")
    {
        $sql = "UPDATE registrations
                SET full_name=?,mobile=?,email=?,state_id=?,district_id=?,photo=?
                WHERE id=?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$name,$mobile,$email,$state,$district,$photo,$id]);
    }
    else
    {
        $sql = "UPDATE registrations
                SET full_name=?,mobile=?,email=?,state_id=?,district_id=?
                WHERE id=?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$name,$mobile,$email,$state,$district,$id]);
    }

    addLog($db,"UPDATE",$id,$name);

    echo json_encode(["status"=>true]);
}



/* ================= DELETE ================= */

function deleteData($db)
{
    $id = $_POST['id'];

    $q = $db->prepare("SELECT full_name FROM registrations WHERE id=?");
    $q->execute([$id]);

    $name = $q->fetchColumn();

    $stmt = $db->prepare("UPDATE registrations SET is_deleted=1, deleted_at=NOW() WHERE id=?");
    $stmt->execute([$id]);

    addLog($db,"DELETE",$id,$name);

    echo json_encode(["status"=>true]);
}



/* ================= RESTORE ================= */

function restoreData($db)
{
    $id = $_POST['id'];

    $q = $db->prepare("SELECT full_name FROM registrations WHERE id=?");
    $q->execute([$id]);

    $name = $q->fetchColumn();

    $stmt = $db->prepare("UPDATE registrations SET is_deleted=0, deleted_at=NULL WHERE id=?");
    $stmt->execute([$id]);

    addLog($db,"RESTORE",$id,$name);

    echo json_encode(["status"=>true]);
}



/* ================= TRASH ================= */

function trashData($db)
{
    $stmt = $db->query("SELECT * FROM registrations WHERE is_deleted=1 ORDER BY id DESC");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"=>true,
        "data"=>$data
    ]);
}



/* ================= DISTRICTS ================= */

function districts($db)
{
    $state = $_GET['state_id'];

    $stmt = $db->prepare("SELECT id,district_name FROM districts WHERE state_id=?");
    $stmt->execute([$state]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"=>true,
        "data"=>$data
    ]);
}



/* ================= LOGS ================= */

function logs($db)
{
    $stmt = $db->query("SELECT * FROM audit_log ORDER BY id DESC LIMIT 50");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"=>true,
        "data"=>$data
    ]);
}



/* ================= ADD LOG ================= */

function addLog($db,$action,$id,$name)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $db->prepare("INSERT INTO audit_log
        (action,record_id,record_name,ip_address)
        VALUES (?,?,?,?)");

    $stmt->execute([$action,$id,$name,$ip]);
}
?>

