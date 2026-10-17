<?php
$product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
if (!$product_id || $product_id < 1) {
    header('Location: Manage_Product.php');
    exit();
}

include("../Layouts/headHTML.php");
include("../DB_Connect.php");

$statement = $conn->prepare('SELECT TenSanPham, MoTaChiTiet FROM sanpham WHERE IDSanPham = ? LIMIT 1');
$statement->bind_param('i', $product_id);
$statement->execute();
$row = $statement->get_result()->fetch_assoc();
if (!$row) {
    http_response_code(404);
    echo 'Không tìm thấy sản phẩm.';
    exit();
}
?>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include("../Layouts/SlideBar.php"); ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include("../Layouts/TopBar.php"); ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Chi Tiết Sản Phẩm</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <h5><?php echo htmlspecialchars((string) $row['TenSanPham'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;"><?php
                                echo htmlspecialchars((string) $row['MoTaChiTiet'], ENT_QUOTES, 'UTF-8');
                            ?></div>
                        </div>
                        <div class="card-body">
                            <a href="Manage_Product.php" class="btn btn-primary">Quay lại</a>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
            <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>
        </div>
        <!-- End of Page Wrapper -->
        <?php include("../Layouts/Link.php"); ?>
    </div>

</body>

</html>
