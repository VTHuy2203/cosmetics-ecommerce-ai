<?php
include("../DB_Connect.php");
include("../Layouts/Head.php");
if (isset($_GET['News_Posts_id'])) {
    $News_Posts_id = $_GET['News_Posts_id'];
    $sql_select_newsposts_detail = "select * from tintucbaiviet where ID_TinTucBaiViet = '$News_Posts_id'";
    $result_select_newsposts_detail = mysqli_query($conn, $sql_select_newsposts_detail);
    $row_select_newsposts_detail = mysqli_fetch_array($result_select_newsposts_detail);
} else {
    header("Location: index.php");
    exit();
}

?>

<body class="bg-light">
    <!-- link scrip -->
    <?php include("../Layouts/Link.php"); ?>

    <!-- Banner header -->
    <?php include("../Layouts/Banner_Header.php"); ?>
    <!-- end Banner header -->

    <!-- header -->
    <?php include("../Layouts/Header_View.php"); ?>
    <!-- end header -->

    <!-- Navbar -->
    <?php include("../Layouts/Navbar.php"); ?>
    <!-- End Navbar -->

    <div class="container my-4 bg-white border-0 rounded-4 shadow">
        <div class="card text-start border-0 set-img-baiviet">
            <div class="card-body">
                <h3><?php echo $row_select_newsposts_detail['TieuDeBaiViet']; ?></h3>
                <p class="card-text"><?php echo $row_select_newsposts_detail['BaiViet']; ?></p>
            </div>
        </div>
    </div>

    <!-- footer -->
    <?php include("../Layouts/Footer.php"); ?>
    <!-- end footer -->

</body>

</html>