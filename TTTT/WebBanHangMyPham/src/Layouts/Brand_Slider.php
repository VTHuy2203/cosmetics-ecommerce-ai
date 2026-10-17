<div class="container bg-light mt-3" id="brand">
    <div class="p-2 bg-white rounded-4 shadow-sm">
        <div class="text-left px-3 pt-3">
            <h3>Thương Hiệu</h3>
        </div>
        <div class="slider_brand">
            <?php
            $sql_brand = "SELECT * FROM thuonghieu";
            $result_brand = mysqli_query($conn, $sql_brand);
            while ($row_brand = mysqli_fetch_array($result_brand)) {
                echo '<div class="p-2 mb-3">
                        <div class="bg-white p-3 border-0 shadow-sm rounded-4">
                            <div class="card-body text-center">
                                <img src="../../../AdminManage/src/Brands/' . $row_brand['AnhThuongHieu'] . '" 
                                     alt="Thương hiệu ' . $row_brand['TenThuongHieu'] . '" 
                                     class="img-fluid rounded-4">
                            </div>
                            <div class="card-body mt-3 text-center">
                                <a style="text-decoration: none;" 
                                   href="../View/View_Product.php?brand_id=' . $row_brand['IDThuongHieu'] . '">
                                    <h5 class="card-title text-black">' . $row_brand['TenThuongHieu'] . '</h5>
                                </a>
                            </div>
                        </div>
                    </div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
    .slider_brand img {
        width: 100%;
        /* Chiếm toàn bộ chiều rộng của container */
        height: 150px;
        /* Đặt chiều cao cố định */
        object-fit: cover;
        /* Đảm bảo ảnh không bị méo, giữ tỷ lệ */
        border-radius: 8px;
        /* Bo góc nhẹ cho hình ảnh */
    }

    .slider_brand .card-body {
        padding: 0;
        /* Loại bỏ khoảng cách thừa */
    }

    .slider_brand .p-2 {
        padding: 5px;
        /* Tạo khoảng cách giữa các phần tử */
    }
</style>

<script>
    $(document).ready(function() {
        $('.slider_brand').slick({
            slidesToShow: 4, // Số lượng hiển thị trên màn hình trên 992px
            slidesToScroll: 1,
            autoplay: true, // Bật chế độ autoplay
            autoplaySpeed: 2000, // Thời gian chuyển tiếp giữa các card (2 giây)
            responsive: [{
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 700,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    });
</script>
