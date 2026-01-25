<div class="container bg-light mt-3" id="brand">
    <style>
    /* Đảm bảo ảnh trong carousel được hiển thị đầy đủ */
    .carousel-inner img {
        object-fit: cover;
        /* Hiển thị ảnh vừa khung nhưng không bị bóp méo */
        height: 400px;
        /* Chiều cao tùy chỉnh phù hợp với thiết kế */
        width: 100%;
        /* Đảm bảo ảnh chiếm toàn bộ chiều rộng */
    }

    /* Đặt bo góc và bóng đổ cho ảnh */
    .carousel-inner img {
        border-radius: 10px;
        /* Bo góc ảnh */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Thêm bóng đổ nhẹ */
    }

    /* Đảm bảo layout của carousel không bị lỗi */
    .carousel-item {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    </style>
    <div class="p-3 bg-white rounded-4 shadow-sm">
        <div class="row">
            <div class="col-md-8 p-4">
                <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner rounded-4 shadow-sm">
                        <?php
                        $sql_select_banner_newsposts = "select * from tintucbaiviet";
                        $result_select_banner_newsposts = mysqli_query($conn, $sql_select_banner_newsposts);
                        $cnt = 0;
                        while ($row_select_banner_newsposts = mysqli_fetch_array($result_select_banner_newsposts)) {
                            if ($cnt == 0) {
                                echo '<div class="carousel-item active">
                                        <img src="../../../AdminManage/src/News_Posts/' . $row_select_banner_newsposts['AnhBaiViet'] . '" class="d-block img-fluid w-100" alt="...">
                                        <div class="carousel-caption d-none d-md-block">
                                            <a href="Detail_News_Posts.php?News_Posts_id=' . $row_select_banner_newsposts['ID_TinTucBaiViet'] . '" style="text-decoration: none;">
                                                <h5 class="bg-light p-2 rounded-4 shadow-sm text-dark">' . $row_select_banner_newsposts['TieuDeBaiViet'] . '</h5>
                                            </a>
                                        </div>
                                    </div>';
                            } else {
                                echo '<div class="carousel-item">
                                        <img src="../../../AdminManage/src/News_Posts/' . $row_select_banner_newsposts['AnhBaiViet'] . '" class="d-block img-fluid w-100" alt="...">
                                        <div class="carousel-caption d-none d-md-block">
                                            <a href="Detail_News_Posts.php?News_Posts_id=' . $row_select_banner_newsposts['ID_TinTucBaiViet'] . '" style="text-decoration: none;">
                                                <h5 class="bg-light p-2 rounded-4 shadow-sm text-dark">' . $row_select_banner_newsposts['TieuDeBaiViet'] . '</h5>
                                            </a>
                                        </div>
                                    </div>';
                            }
                            $cnt += 1;
                        }
                        ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-center text-center">
                <a style="text-decoration: none;" href="View_News_Posts.php">
                    <h1 class="card-title text-dark">Tin Tức Và Bài Viết</h1>
                </a>
            </div>
        </div>
    </div>
</div>