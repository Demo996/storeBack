<?php
header("Access-Control-Allow-Origin:*");
$servername = "localhost";
$username = "root";
$password = "31415926";
$dbname = "power";
$data = "";
$sendData = array();
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接出错");
}

if($data = file_get_contents("php://input")) {
    parse_str($data, $getData);
    $username = $getData["username"];
    $sql1 = "SELECT *FROM master WHERE name='$username'";
    $sql2 = "SELECT groupid FROM mastergroup WHERE name='$username'";
    $result1 = $conn->query($sql1);
    if($result1->num_rows > 0) {
        while($row = $result1->fetch_assoc()) {
            array_push($sendData, $row);
        }

        $result2 = $conn->query($sql2);
        if($result2->num_rows > 0) {
            $tmpArr = array();
            while($row = $result2->fetch_assoc()) {
                array_push($tmpArr, $row["groupid"]);
            }
            array_push($sendData, $tmpArr);
            echo json_encode($sendData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
            $conn->close();
        }
        else {
            $conn->close();
            return;
        }
    } else {
        $conn->close();
        return;
    }
} else {
    $conn->close();
    return;
}
?>