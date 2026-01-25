function changeQuantity(button, value, ID) {
    var input = button.parentNode.querySelector('input[type=number]');
    var currentValue = parseInt(input.value);
    
    if (!isNaN(currentValue)) {
        input.value = Math.max(currentValue + value, 1);

        var url = "Cart.php?CapNhatSoLuong="+input.value+"&IDCapNhatSoLuong=" + ID;
        window.location.href = url;
    }
}