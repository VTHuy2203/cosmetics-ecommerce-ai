<?php
require_once "../DB_Connect.php";

if (isset($_POST['IDComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $IDComment = $_POST['IDComment'];

    $sql_del_comment = "delete from binhluan where ID_BinhLuan = '$IDComment'";

    $result = mysqli_query($conn, $sql_del_comment);

    if($result)
    {
        echo 'xoathanhcong';
    }
    else
    {
        echo 'error';
    }
}
else
{
    echo 'error';
}

?>
