<?php

session_start();
include './partials/connection.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$created_by = $_SESSION['user_id'];
$uploadDir = "uploads/marketplace_banner/";

if(!is_dir($uploadDir)){
    mkdir($uploadDir,0777,true);
}

$action = $_REQUEST['action'] ?? '';

switch($action){

/*==========================================================
CREATE
==========================================================*/
case "create":

    $images = [];
    $titles = [];

    for($i=1;$i<=3;$i++){

        $titles[] = $_POST["slide{$i}_title"];

        if(isset($_FILES["slide{$i}_image"]) && $_FILES["slide{$i}_image"]['error']==0){

            $ext = pathinfo($_FILES["slide{$i}_image"]['name'],PATHINFO_EXTENSION);

            $filename = uniqid().".".$ext;

            move_uploaded_file(
                $_FILES["slide{$i}_image"]['tmp_name'],
                $uploadDir.$filename
            );

            $images[] = $uploadDir.$filename;

        }else{

            $images[] = "";

        }

    }

    $images_json = json_encode($images);
    $titles_json = json_encode($titles);

    $stmt = $conn->prepare("
        INSERT INTO marketplace_banner
        (images,title,created_by,created_at)
        VALUES(?,?,?,NOW())
    ");

    $stmt->bind_param(
        "ssi",
        $images_json,
        $titles_json,
        $created_by
    );

    if($stmt->execute()){

        echo json_encode([
            "status"=>"success",
            "message"=>"Banner Added Successfully."
        ]);

    }else{

        echo json_encode([
            "status"=>"error",
            "message"=>"Insert Failed."
        ]);

    }

break;

/*==========================================================
GET
==========================================================*/
case "get":

    $id = intval($_GET['id']);

    $res = $conn->query("
        SELECT *
        FROM marketplace_banner
        WHERE id='$id'
    ");

    if($res->num_rows){

        $row = $res->fetch_assoc();

        $row['images'] = json_decode($row['images'],true);
        $row['title'] = json_decode($row['title'],true);

        echo json_encode([
            "status"=>"success",
            "data"=>$row
        ]);

    }else{

        echo json_encode([
            "status"=>"error"
        ]);

    }

break;

/*==========================================================
UPDATE
==========================================================*/
case "update":

    $id = intval($_POST['id']);

    $res = $conn->query("
        SELECT *
        FROM marketplace_banner
        WHERE id='$id'
    ");

    $old = $res->fetch_assoc();

    $oldImages = json_decode($old['images'],true);

    $images = [];
    $titles = [];

    for($i=1;$i<=3;$i++){

        $titles[] = $_POST["slide{$i}_title"];

        if(isset($_FILES["slide{$i}_image"]) && $_FILES["slide{$i}_image"]['error']==0){

            if(!empty($oldImages[$i-1]) && file_exists($oldImages[$i-1])){

                unlink($oldImages[$i-1]);

            }

            $ext = pathinfo($_FILES["slide{$i}_image"]['name'],PATHINFO_EXTENSION);

            $filename = uniqid().".".$ext;

            move_uploaded_file(
                $_FILES["slide{$i}_image"]['tmp_name'],
                $uploadDir.$filename
            );

            $images[] = $uploadDir.$filename;

        }else{

            $images[] = $oldImages[$i-1];

        }

    }

    $images_json = json_encode($images);
    $titles_json = json_encode($titles);

    $stmt = $conn->prepare("
        UPDATE marketplace_banner
        SET
        images=?,
        title=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssi",
        $images_json,
        $titles_json,
        $id
    );

    if($stmt->execute()){

        echo json_encode([
            "status"=>"success",
            "message"=>"Banner Updated Successfully."
        ]);

    }else{

        echo json_encode([
            "status"=>"error",
            "message"=>"Update Failed."
        ]);

    }

break;

/*==========================================================
DELETE
==========================================================*/
case "delete":

    $id = intval($_POST['id']);

    $res = $conn->query("
        SELECT images
        FROM marketplace_banner
        WHERE id='$id'
    ");

    if($res->num_rows){

        $row = $res->fetch_assoc();

        $imgs = json_decode($row['images'],true);

        foreach($imgs as $img){

            if(!empty($img) && file_exists($img)){

                unlink($img);

            }

        }

        $conn->query("
            DELETE FROM marketplace_banner
            WHERE id='$id'
        ");

        echo json_encode([
            "status"=>"success",
            "message"=>"Banner Deleted Successfully."
        ]);

    }else{

        echo json_encode([
            "status"=>"error",
            "message"=>"Banner Not Found."
        ]);

    }

break;

default:

    echo json_encode([
        "status"=>"error",
        "message"=>"Invalid Action."
    ]);

}
?>