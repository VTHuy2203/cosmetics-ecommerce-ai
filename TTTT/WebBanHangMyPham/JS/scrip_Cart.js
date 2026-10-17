function changeQuantity(button, value, ID) {
    var input = button.parentNode.querySelector('input[type=number]');
    var currentValue = parseInt(input.value);
    
    if (!isNaN(currentValue)) {
        input.value = Math.max(currentValue + value, 1);

        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (!csrf) {
            return;
        }
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'Cart.php';
        [['CapNhatSoLuong', input.value], ['IDCapNhatSoLuong', ID], ['csrf_token', csrf.content]].forEach(function (pair) {
            var field = document.createElement('input');
            field.type = 'hidden';
            field.name = pair[0];
            field.value = pair[1];
            form.appendChild(field);
        });
        document.body.appendChild(form);
        form.submit();
    }
}
