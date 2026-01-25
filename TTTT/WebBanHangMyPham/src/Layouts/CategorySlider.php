<?php
    include("../DB_Connect.php");
    include("../Layouts/Head.php");
?>


<div class="container my-3">
    <div class="slider_category border-0 shadow-sm rounded-4">
        <div class="wrapper">
            <i id="left" class="fa-solid fa-angle-left"></i>
            <ul class="carousel">
                <?php
                $sql = "SELECT * FROM danhmuc";

                $result = mysqli_query($conn, $sql);
                $stt = 1;
                while ($row = mysqli_fetch_array($result)) {
                    echo '<li class="card border-0"><a href="../View/View_Product.php?category_id='.$row['IDDanhMuc'].'" class="text-center text-black" style="text-decoration: none;">';
                    echo '<div class="img"><img class="shadow-sm" src="/TTTT/AdminManage/src/Categorys/' . $row['AnhDanhMuc'] . '" alt="img" draggable="false"></div>';
                    echo '<h2>' . $row['TenDanhMuc'] . '</h2>';
                    echo '</a></li>';
                    $stt++;
                }
                ?>
            </ul>
            <i id="right" class="fa-solid fa-angle-right"></i>
        </div>
    </div>
</div>
<!-- ../View/View_Product.php?category_id=<?php echo $row['IDDanhMuc']; ?> -->