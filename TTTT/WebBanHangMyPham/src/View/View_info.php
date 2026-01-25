<?php

//----------------------------TRANG GIỚI THIỆU 



include("../DB_Connect.php");
include("../Layouts/Head.php");

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
        <div class="card text-start border-0">
            <div class="card-body">
                <h4 class="card-title">Chào Mừng Đến Với Mơ Shop - Cửa Hàng Mỹ Phẩm Uy Tín Tại Xã Ealy</h4>
                <p class="card-text fs-5">
                    Mơ Shop tự hào là địa chỉ mua sắm mỹ phẩm đáng tin cậy tại xã Ealy. Với nhiều năm kinh nghiệm trong
                    ngành làm đẹp, chúng tôi đã xây dựng danh tiếng vững chắc nhờ vào chất lượng sản phẩm và dịch vụ tận
                    tâm, đáp ứng đầy đủ nhu cầu chăm sóc sắc đẹp của mọi khách hàng trong khu vực.
                </p>
            </div>
            <div class="card-body">
                <img src="https://scontent.fsgn2-7.fna.fbcdn.net/v/t39.30808-6/321805351_471669848374977_8596214190487779546_n.jpg?stp=cp6_dst-jpg&_nc_cat=108&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeEwp7uqGfDh1dx6EoFH2SZznUXKakDmmbSdRcpqQOaZtNFS90scpIgt4sr7YG07dcbDcylxHr6JDBavc9Y27Y_7&_nc_ohc=TiFMPC1SIJUQ7kNvgHUdZDC&_nc_ht=scontent.fsgn2-7.fna&_nc_gid=ARPix5Yzmi1WEVrbgoEFPzx&oh=00_AYDxPW2OQ9jdow_5HkcPj5zutms3M29oTyEejJQVJRG8gw&oe=670E7A5A"
                    alt="" class="img-fluid w-75 rounded-4">
            </div>
            <div class="card-body">
                <h4 class="card-title">Dịch Vụ Chăm Sóc Khách Hàng Chuyên Nghiệp Và Tận Tâm</h4> <br>
                <p class="card-text fs-5">
                    Tại Mơ Shop, chúng tôi không chỉ cung cấp sản phẩm làm đẹp chất lượng mà còn có dịch vụ chăm sóc
                    khách hàng tuyệt vời: <br>
                    <li> Tư vấn làm đẹp chuyên sâu: Đội ngũ nhân viên tận tâm và giàu kinh nghiệm luôn sẵn sàng tư vấn,
                        giúp bạn chọn lựa sản phẩm phù hợp nhất với làn da và phong cách cá nhân <br></li>
                    <li> Chính sách bảo hành: Tất cả sản phẩm đều đi kèm với chính sách đổi trả minh bạch và dịch vụ hậu
                        mãi tận tình, mang lại sự an tâm tuyệt đối cho khách hàng. <br></li>
                    <li> Giao hàng nhanh chóng: Chúng tôi cam kết giao hàng đến tận nơi với thời gian nhanh nhất, phục
                        vụ mọi nhu cầu làm đẹp kịp thời của bạn. <br></li>
                </p>
            </div>
            <div class="card-body">
                <img src="https://scontent.fsgn2-5.fna.fbcdn.net/v/t39.30808-6/440582235_1630248994471697_4997647398678995947_n.jpg?stp=cp6_dst-jpg&_nc_cat=104&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeEWpOqwVGf6EbDD617tt-W3OD68uii1NDA4Pry6KLU0MKV_9fj9-TtOf3XaRcwUrYnKQ8LJ-RCV5R7fRdetxcLg&_nc_ohc=AxPwaVOaNEoQ7kNvgG4iHnH&_nc_ht=scontent.fsgn2-5.fna&_nc_gid=A496WQIlMoMQmgrF3ZKaZ2F&oh=00_AYCQuJvm4fCy86j_b5OF_a1BbWV7Bg7rHEhGPhof0CTUMw&oe=670E9EAE"
                    alt="" class="img-fluid w-75 rounded-4">
            </div>
            <div class="card-body">
                <h4 class="card-title">Chất Lượng Đảm Bảo - Mỹ Phẩm Chính Hãng Tại Mơ Shop</h4>
                <p class="card-text fs-5">
                    Chúng tôi cung cấp các sản phẩm mỹ phẩm chính hãng từ những thương hiệu nổi tiếng và uy tín trên thế
                    giới, đảm bảo tiêu chuẩn chất lượng cao nhất. Mỗi sản phẩm trước khi đến tay khách hàng đều được
                    kiểm tra kỹ lưỡng về độ an toàn và hiệu quả, giúp bạn luôn an tâm khi sử dụng.


                </p>
            </div>
            <div class="card-body">
                <img src="https://scontent.fsgn2-8.fna.fbcdn.net/v/t39.30808-6/428365628_1587616048734992_2205138990043236847_n.jpg?_nc_cat=102&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeHfAe6dRAXI-0LKPwcz6BFU9xYVUlCIrh73FhVSUIiuHqiusYpfYY7chkCyMlgXl8opEyR8JXmTdxYc_X03wL5l&_nc_ohc=HJR4AHw2mygQ7kNvgGD51Ix&_nc_ht=scontent.fsgn2-8.fna&_nc_gid=Aw6dSO6VS_SO8Uh8Va3Ta5A&oh=00_AYDssNfObw-vFxFPadHM3QCToHRbuIi9SWTLJ12LDsW7LA&oe=670E8F32"
                    alt="" class="img-fluid w-75 rounded-4">
            </div>
        </div>

    </div>

    <!-- footer -->
    <?php include("../Layouts/Footer.php"); ?>
    <!-- end footer -->

    <!-- js -->

</body>

</html>