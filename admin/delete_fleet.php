<?php
// admin/delete_fleet.php
session_start();
require_once __DIR__ . '/../db/config.php';
$id = intval($_POST['id'] ?? 0);
if (!$id) { header('Location: fleet.php'); exit; }

// Optionally fetch images to unlink them
$q = $conn->prepare("SELECT image1,image2,image3 FROM fleet WHERE id = ? LIMIT 1");
if ($q) {
    $q->bind_param('i',$id);
    $q->execute();
    $res = $q->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $q->close();
    if ($row) {
        foreach (['image1','image2','image3'] as $col) {
            if (!empty($row[$col])) {
                $path = __DIR__ . '/../' . $row[$col];
                if (file_exists($path)) @unlink($path);
            }
        }
    }
}
$d = $conn->prepare("DELETE FROM fleet WHERE id = ?");
if ($d) {
    $d->bind_param('i',$id);
    if ($d->execute()) $_SESSION['success']='Vehicle deleted.';
    else $_SESSION['error']='Delete failed.';
    $d->close();
}
header('Location: fleet.php');
exit;