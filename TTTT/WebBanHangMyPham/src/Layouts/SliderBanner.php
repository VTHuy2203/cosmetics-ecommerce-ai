<div class="carousel-container">
  <div id="carouselExampleRide" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-inner shadow">
      <div class="carousel-item active">
        <img src="/TTTT/imgs/slideban/slide1.jpg" alt="Slide 1">
      </div>
      <div class="carousel-item">
        <img src="/TTTT/imgs/slideban/slide2.jpg" alt="Slide 2">
      </div>
      <div class="carousel-item">
        <img src="/TTTT/imgs/slideban/slide3.jpg" alt="Slide 3">
      </div>
      <div class="carousel-item">
        <img src="/TTTT/imgs/slideban/slide4.jpg" alt="Slide 4">
      </div>
      <div class="carousel-item">
        <img src="/TTTT/imgs/slideban/slide5.jpg" alt="Slide 5">
      </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleRide" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleRide" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</div>
<style>
.carousel-container {
  width: 100vw;           /* Full chiều ngang màn hình */
  overflow: hidden;       /* Ẩn phần dư thừa (nếu có) */
  background-color: #fff; /* Nền trắng để ảnh sáng hơn */
  margin: 0 auto;
}

/* Ảnh trong carousel */
.carousel-item img {
  display: block;
  width: 100%;
  max-height: 600px;      /* Giới hạn chiều cao banner (tùy chỉnh) */
  height: auto;
  object-fit: contain;    /* Hiển thị toàn ảnh, không cắt */
  margin: 0 auto;
}

/* Tùy chỉnh nút điều hướng cho dễ nhìn */
.carousel-control-prev-icon,
.carousel-control-next-icon {
  filter: invert(1) brightness(1.5);
}
</style>
