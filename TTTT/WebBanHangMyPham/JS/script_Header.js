
  // Kiểm tra kích thước màn hình và cập nhật lớp container
  function updateContainerClass() {
    var screenWidth = window.innerWidth;

    if (screenWidth <= 990) {
      document.getElementById('header-logo').classList.remove('col-md-3');
      document.getElementById('header-logo').classList.add('col');

      document.getElementById('header-search-inf').classList.remove('col-md-7');
      document.getElementById('header-search-inf').classList.add('d-none');

      document.getElementById('header-cart-like').classList.remove('col-md-2');
      document.getElementById('header-cart-like').classList.add('col');
    } else {
      document.getElementById('header-logo').classList.remove('col');
      document.getElementById('header-logo').classList.add('col-md-3');

      document.getElementById('header-search-inf').classList.remove('d-none');
      document.getElementById('header-search-inf').classList.add('col-md-7');

      document.getElementById('header-cart-like').classList.remove('col');
      document.getElementById('header-cart-like').classList.add('col-md-2');
    }
    
  }

  // Gọi hàm cập nhật khi trang được tải và khi kích thước màn hình thay đổi
  window.onload = updateContainerClass;
  window.addEventListener('resize', updateContainerClass);

