// Kiểm tra kích thước màn hình và cập nhật lớp container
function updateNav() {
    var screenWidth = window.innerWidth;

    if (screenWidth < 768) {
      document.getElementById('nav-respon-btn-search').classList.add('w-100');
      document.getElementById('nav-respon-search').classList.remove('d-none');
    } else {
      document.getElementById('nav-respon-btn-search').classList.remove('w-100');
      document.getElementById('nav-respon-search').classList.add('d-none');
    }
  }

  // Gọi hàm cập nhật khi trang được tải và khi kích thước màn hình thay đổi
  window.onload = updateNav;
  window.addEventListener('resize', updateNav);