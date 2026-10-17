<?php
include("../Layouts/Head.php");

include("../DB_Connect.php");
require_once dirname(__DIR__, 3) . '/config/security.php';


?>

<body>
    <?php
    include("../Layouts/Header_View.php");
    include("../Layouts/Link.php");
    ?>
    <section class="vh-100 bg-light">
        <div class="container pt-5">
            <div class="row d-flex justify-content-center pt-5">
                <div class="col col-xl-10">
                    <div class="card" style="border-radius: 1rem;">
                        <div class="row g-0">

                            <div class="col-md-6 p-3">
                                <div class="pt-3 px-3">
                                    <h5>Thông tin</h5>
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-name">Họ và tên</label>
                                    <input type="text" id="form-register-name" class="form-control form-control-lg" />
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-email">Email</label>
                                    <div class="input-group">
                                        <input type="email" id="form-register-email" class="form-control form-control-lg" />
                                        <button type="button" class="btn btn-outline-primary" onclick="sendVerificationCode()" id="btnSendCode">Gửi mã xác nhận</button>
                                    </div>
                                    <small class="text-muted">Vui lòng nhập email và nhấn "Gửi mã xác nhận" để nhận mã xác nhận.</small>
                                    <div id="cooldownMessage" style="display: none; margin-top: 8px;">
                                        <small class="text-muted" style="opacity: 0.7;">Vui lòng đợi <span id="cooldownTimer">30</span> giây trước khi gửi lại.</small>
                                    </div>
                                </div>
                                <div class="p-3" id="verificationCodeSection" style="display: none;">
                                    <label class="form-label" for="form-register-verification-code">Mã xác nhận</label>
                                    <input type="text" id="form-register-verification-code" class="form-control form-control-lg" placeholder="Nhập mã xác nhận từ email" maxlength="6" />
                                    <small class="text-muted">Vui lòng kiểm tra email và nhập mã xác nhận 6 chữ số.</small>
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-phone">Số điện thoại</label>
                                    <input type="text" id="form-register-phone" class="form-control form-control-lg" />
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-province">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                    <select id="form-register-province" class="form-control form-control-lg" required onchange="loadDistricts()">
                                        <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                    </select>
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-district">Quận/Huyện <span class="text-danger">*</span></label>
                                    <select id="form-register-district" class="form-control form-control-lg" required onchange="loadWards()">
                                        <option value="">-- Chọn Quận/Huyện --</option>
                                    </select>
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-ward">Phường/Xã <span class="text-danger">*</span></label>
                                    <select id="form-register-ward" class="form-control form-control-lg" required>
                                        <option value="">-- Chọn Phường/Xã --</option>
                                    </select>
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-address">Địa chỉ chi tiết (Số nhà, tên đường) <span class="text-danger">*</span></label>
                                    <input type="text" id="form-register-address" class="form-control form-control-lg" placeholder="Số nhà, tên đường..." required />
                                </div>
                            </div>
                            <div class="col-md-6 p-3">
                                <div class="pt-3 px-3">
                                    <h5>Tài khoản</h5>
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-nameUser">Tên đăng nhập</label>
                                    <input type="text" id="form-register-nameUser" class="form-control form-control-lg" />
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-pass">Mật khẩu</label>
                                    <input type="password" id="form-register-pass" class="form-control form-control-lg" />
                                </div>
                                <div class="p-3">
                                    <label class="form-label" for="form-register-passAgain">Nhập lại mật khẩu</label>
                                    <input type="password" id="form-register-passAgain" class="form-control form-control-lg" />
                                </div>
                            </div>
                        </div>
                        <div class="row g-0">
                            <div class="pb-3 px-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="registerRole" id="registerAsCustomer" value="customer" checked onchange="toggleRoleFields()">
                                    <label class="form-check-label" for="registerAsCustomer">
                                        Đăng ký làm khách hàng
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="registerRole" id="registerAsShipper" value="shipper" onchange="toggleRoleFields()">
                                    <label class="form-check-label" for="registerAsShipper">
                                        Đăng ký làm shipper (người giao hàng)
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="registerRole" id="registerAsEmployee" value="employee" onchange="toggleRoleFields()">
                                    <label class="form-check-label" for="registerAsEmployee">
                                        Đăng ký làm nhân viên
                                    </label>
                                </div>
                                <div id="shipperFields" style="display: none;">
                                    <p class="text-info mb-2"><small>Vui lòng điền đầy đủ thông tin bên dưới. Admin sẽ xem xét và phê duyệt tài khoản của bạn.</small></p>
                                    <div class="p-3">
                                        <label class="form-label" for="form-register-license">Số CMND/CCCD <span class="text-danger">*</span></label>
                                        <input type="text" id="form-register-license" class="form-control form-control-lg" placeholder="Nhập số CMND/CCCD" required />
                                    </div>
                                    <div class="p-3">
                                        <label class="form-label" for="form-register-hometown">Quê quán <span class="text-danger">*</span></label>
                                        <input type="text" id="form-register-hometown" class="form-control form-control-lg" placeholder="Nhập quê quán" required />
                                    </div>
                                    <div class="p-3">
                                        <label class="form-label" for="form-register-temporary-address">Địa chỉ tạm trú <span class="text-danger">*</span></label>
                                        <textarea id="form-register-temporary-address" class="form-control form-control-lg" rows="2" placeholder="Nhập địa chỉ tạm trú" required></textarea>
                                    </div>
                                    <div class="p-3" id="workLocationField" style="display: none;">
                                        <label class="form-label">Hình thức làm việc <span class="text-danger">*</span></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="work_location" id="work_location_store" value="in-store" required>
                                            <label class="form-check-label" for="work_location_store">
                                                Làm việc tại cửa hàng (In-store)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="work_location" id="work_location_online" value="remote" required>
                                            <label class="form-check-label" for="work_location_online">
                                                Làm việc online (Remote/Online)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <label class="form-label" for="form-register-id-card">Ảnh CMND/CCCD (Mặt trước và mặt sau) <span class="text-danger">*</span></label>
                                        <input type="file" id="form-register-id-card" class="form-control form-control-lg" accept="image/*" multiple required />
                                        <small class="text-muted">Vui lòng chọn 2 ảnh: mặt trước và mặt sau của CMND/CCCD</small>
                                        <div id="idCardPreview" class="mt-2" style="display: none;">
                                            <div id="previewImages" class="d-flex gap-2 flex-wrap"></div>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <label class="form-label" for="form-register-note">Ghi chú thêm</label>
                                        <textarea id="form-register-note" class="form-control form-control-lg" rows="3" placeholder="Kinh nghiệm giao hàng, khu vực hoạt động..."></textarea>
                                    </div>
                                    <div class="p-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="form-register-confirm" required>
                                            <label class="form-check-label" for="form-register-confirm">
                                                Tôi xác nhận thông tin trên là chính xác <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php echo turnstile_widget('register'); ?>
                                <button type="button" onclick="register()" class="btn btn-dark btn-lg btn-block px-3 mx-3">Đăng ký</button>
                            </div>
                        </div>
                        <div class="row g-0">
                            <div class="pb-3 px-3">
                                <p href="" class="mx-3">Bạn đã có tài khoản! <a href="Login.php" class="text-decoration-underline">Đăng nhập tại đây.</a></p>
                            </div>
                        </div>
                        
                        <script>
                        // Load provinces and districts on page load
                        let provincesData = {};
                        
                        $(document).ready(function() {
                            // Load provinces and districts
                            const provinceSelect = document.getElementById('form-register-province');
                            
                            if (!provinceSelect) {
                                console.error('Province select element not found!');
                                return;
                            }
                            
                            console.log('Loading provinces from: ../Processing/Load_Provinces_Districts.php');
                            
                            $.ajax({
                                url: '../Processing/Load_Provinces_Districts.php',
                                type: 'GET',
                                dataType: 'json',
                                timeout: 10000, // 10 second timeout
                                success: function(data) {
                                    console.log('Provinces data received:', data);
                                    
                                    if (data && data.success && data.provinces) {
                                        provincesData = data.provinces;
                                        console.log('Total provinces loaded:', Object.keys(provincesData).length);
                                        
                                        // Clear existing options (except the first one)
                                        provinceSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
                                        
                                        // Populate provinces
                                        const provinceNames = Object.keys(provincesData).sort();
                                        provinceNames.forEach(function(province) {
                                            const option = document.createElement('option');
                                            option.value = province;
                                            option.textContent = province;
                                            provinceSelect.appendChild(option);
                                        });
                                        
                                        console.log('Provinces populated successfully. Total: ' + provinceNames.length);
                                    } else {
                                        console.error('Invalid data structure:', data);
                                        alert('Không thể tải danh sách tỉnh/thành phố. Vui lòng tải lại trang.');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX Error loading provinces:');
                                    console.error('Status:', status);
                                    console.error('Error:', error);
                                    console.error('Response:', xhr.responseText);
                                    console.error('Status Code:', xhr.status);
                                    
                                    // Show user-friendly error
                                    alert('Lỗi khi tải danh sách tỉnh/thành phố. Vui lòng kiểm tra console để biết thêm chi tiết.');
                                    
                                    // Try to parse response if it's JSON
                                    try {
                                        const errorData = JSON.parse(xhr.responseText);
                                        console.error('Error details:', errorData);
                                    } catch (e) {
                                        console.error('Response is not JSON:', xhr.responseText);
                                    }
                                }
                            });
                        });
                        
                        function loadDistricts() {
                            const provinceSelect = document.getElementById('form-register-province');
                            const districtSelect = document.getElementById('form-register-district');
                            const wardSelect = document.getElementById('form-register-ward');
                            const selectedProvince = provinceSelect.value;
                            
                            // Clear districts and wards
                            districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
                            wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
                            
                            if (selectedProvince && provincesData[selectedProvince]) {
                                // New structure from JSON: { "Province": { "District": ["Ward1", "Ward2", ...] } }
                                if (typeof provincesData[selectedProvince] === 'object' && !Array.isArray(provincesData[selectedProvince])) {
                                    // Nested structure: Province -> District -> Wards
                                    Object.keys(provincesData[selectedProvince]).sort().forEach(function(district) {
                                        const option = document.createElement('option');
                                        option.value = district;
                                        option.textContent = district;
                                        districtSelect.appendChild(option);
                                    });
                                } else if (Array.isArray(provincesData[selectedProvince])) {
                                    // Array format (old CSV format): array of districts
                                    provincesData[selectedProvince].forEach(function(district) {
                                        const option = document.createElement('option');
                                        option.value = district;
                                        option.textContent = district;
                                        districtSelect.appendChild(option);
                                    });
                                }
                            }
                        }
                        
                        function loadWards() {
                            const provinceSelect = document.getElementById('form-register-province');
                            const districtSelect = document.getElementById('form-register-district');
                            const wardSelect = document.getElementById('form-register-ward');
                            const selectedProvince = provinceSelect.value;
                            const selectedDistrict = districtSelect.value;
                            
                            // Clear wards
                            wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
                            
                            if (selectedProvince && selectedDistrict && provincesData[selectedProvince]) {
                                // New structure from JSON: { "Province": { "District": ["Ward1", "Ward2", ...] } }
                                if (typeof provincesData[selectedProvince] === 'object' && !Array.isArray(provincesData[selectedProvince])) {
                                    // Nested structure: Province -> District -> Wards
                                    const districtData = provincesData[selectedProvince][selectedDistrict];
                                    if (districtData && Array.isArray(districtData)) {
                                        // Populate wards for selected district
                                        districtData.forEach(function(ward) {
                                            const option = document.createElement('option');
                                            option.value = ward;
                                            option.textContent = ward;
                                            wardSelect.appendChild(option);
                                        });
                                    }
                                }
                            }
                        }
                        
                        function toggleRoleFields() {
                            const shipperRadio = document.getElementById('registerAsShipper');
                            const employeeRadio = document.getElementById('registerAsEmployee');
                            const fields = document.getElementById('shipperFields');
                            const workLocationField = document.getElementById('workLocationField');
                            
                            if (shipperRadio.checked || employeeRadio.checked) {
                                fields.style.display = 'block';
                                // Show work location field only for employees
                                if (employeeRadio.checked) {
                                    workLocationField.style.display = 'block';
                                    // Make work location required for employees
                                    document.getElementById('work_location_store').required = true;
                                    document.getElementById('work_location_online').required = true;
                                } else {
                                    workLocationField.style.display = 'none';
                                    // Remove requirement for shippers
                                    document.getElementById('work_location_store').required = false;
                                    document.getElementById('work_location_online').required = false;
                                    // Uncheck work location radios
                                    document.getElementById('work_location_store').checked = false;
                                    document.getElementById('work_location_online').checked = false;
                                }
                            } else {
                                fields.style.display = 'none';
                                workLocationField.style.display = 'none';
                                // Remove requirement
                                document.getElementById('work_location_store').required = false;
                                document.getElementById('work_location_online').required = false;
                                // Uncheck work location radios
                                document.getElementById('work_location_store').checked = false;
                                document.getElementById('work_location_online').checked = false;
                            }
                        }
                        
                        function sendVerificationCode() {
                            var email = document.getElementById('form-register-email').value;
                            
                            if (!email) {
                                alert('Vui lòng nhập email trước!');
                                return;
                            }
                            
                            // Check email format
                            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(email)) {
                                alert("Địa chỉ email không hợp lệ!");
                                return;
                            }
                            
                            // Disable button to prevent multiple clicks
                            var btnSendCode = document.getElementById('btnSendCode');
                            var cooldownMessage = document.getElementById('cooldownMessage');
                            var cooldownTimer = document.getElementById('cooldownTimer');
                            btnSendCode.disabled = true;
                            btnSendCode.textContent = 'Đang gửi...';
                            cooldownMessage.style.display = 'none';
                            
                            $.ajax({
                                type: 'POST',
                                url: '../Processing/Send_Verification_Code.php',
                                data: {
                                    csrf_token: <?php echo json_encode(csrf_token()); ?>,
                                    email: email
                                },
                                xhrFields: {
                                    withCredentials: true
                                },
                                crossDomain: false,
                                success: function(response) {
                                    try {
                                        // Try to parse as JSON first
                                        var jsonResponse = JSON.parse(response);
                                        if (jsonResponse.success) {
                                            if (jsonResponse.code_link) {
                                                // Show code link for development
                                                alert('Mã xác nhận đã được tạo!\n\nVui lòng click vào link sau để xem mã:\n' + jsonResponse.code_link + '\n\nHoặc mã của bạn là: ' + (jsonResponse.code || ''));
                                                window.open(jsonResponse.code_link, '_blank');
                                            } else {
                                                alert('Mã xác nhận đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư!');
                                            }
                                            document.getElementById('verificationCodeSection').style.display = 'block';
                                            btnSendCode.textContent = 'Gửi lại mã';
                                            // Start 30-second cooldown timer
                                            startCooldownTimer(30);
                                        } else {
                                            alert('Lỗi: ' + (jsonResponse.error || 'Có lỗi xảy ra'));
                                            btnSendCode.disabled = false;
                                            btnSendCode.textContent = 'Gửi mã xác nhận';
                                        }
                                    } catch (e) {
                                        // Not JSON, treat as plain text
                                        response = response.trim();
                                        if (response === 'success') {
                                            alert('Mã xác nhận đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư!');
                                            document.getElementById('verificationCodeSection').style.display = 'block';
                                            btnSendCode.textContent = 'Gửi lại mã';
                                            // Start 30-second cooldown timer
                                            startCooldownTimer(30);
                                        } else if (response.startsWith('cooldown:')) {
                                            var seconds = parseInt(response.split(':')[1]);
                                            alert('Vui lòng đợi ' + seconds + ' giây trước khi gửi lại mã xác nhận!');
                                            btnSendCode.disabled = false;
                                            btnSendCode.textContent = 'Gửi lại mã';
                                            startCooldownTimer(seconds);
                                        } else if (response === 'error') {
                                            alert('Có lỗi xảy ra khi gửi email. Vui lòng thử lại sau.');
                                            btnSendCode.disabled = false;
                                            btnSendCode.textContent = 'Gửi mã xác nhận';
                                        } else {
                                            alert('Lỗi: ' + response);
                                            btnSendCode.disabled = false;
                                            btnSendCode.textContent = 'Gửi mã xác nhận';
                                        }
                                    }
                                },
                                error: function() {
                                    alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                                    btnSendCode.disabled = false;
                                    btnSendCode.textContent = 'Gửi mã xác nhận';
                                }
                            });
                        }
                        
                        // Cooldown timer function
                        var cooldownInterval = null;
                        function startCooldownTimer(seconds) {
                            var btnSendCode = document.getElementById('btnSendCode');
                            var cooldownMessage = document.getElementById('cooldownMessage');
                            var cooldownTimer = document.getElementById('cooldownTimer');
                            
                            // Clear any existing timer
                            if (cooldownInterval) {
                                clearInterval(cooldownInterval);
                            }
                            
                            btnSendCode.disabled = true;
                            btnSendCode.textContent = 'Gửi lại mã';
                            cooldownMessage.style.display = 'block';
                            
                            var remaining = seconds;
                            cooldownTimer.textContent = remaining;
                            
                            cooldownInterval = setInterval(function() {
                                remaining--;
                                cooldownTimer.textContent = remaining;
                                
                                if (remaining <= 0) {
                                    clearInterval(cooldownInterval);
                                    cooldownInterval = null;
                                    btnSendCode.disabled = false;
                                    cooldownMessage.style.display = 'none';
                                }
                            }, 1000);
                        }
                        
                        // Preview ID card images
                        document.getElementById('form-register-id-card').addEventListener('change', function(e) {
                            var files = e.target.files;
                            var previewDiv = document.getElementById('idCardPreview');
                            var previewImages = document.getElementById('previewImages');
                            
                            if (files.length > 0) {
                                previewDiv.style.display = 'block';
                                previewImages.innerHTML = '';
                                
                                for (var i = 0; i < files.length; i++) {
                                    var file = files[i];
                                    if (file.type.startsWith('image/')) {
                                        var reader = new FileReader();
                                        reader.onload = function(event) {
                                            var img = document.createElement('img');
                                            img.src = event.target.result;
                                            img.style.width = '150px';
                                            img.style.height = '100px';
                                            img.style.objectFit = 'cover';
                                            img.style.borderRadius = '8px';
                                            img.style.margin = '5px';
                                            img.style.border = '2px solid #2f9e91';
                                            previewImages.appendChild(img);
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                }
                            } else {
                                previewDiv.style.display = 'none';
                            }
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ajax -->
    <script>
        function register() {
            try {
                console.log('Register function called');
                var name = document.getElementById("form-register-name").value;
                var email = document.getElementById("form-register-email").value;
                var phone = document.getElementById("form-register-phone").value;
                var province = document.getElementById('form-register-province').value;
                var district = document.getElementById('form-register-district').value;
                var ward = document.getElementById('form-register-ward').value;
                var addressDetail = document.getElementById("form-register-address").value;
                // Build full address: [Detail], [Ward], [District], [Province]
                var addressParts = [];
                if (addressDetail) addressParts.push(addressDetail);
                if (ward) addressParts.push(ward);
                if (district) addressParts.push(district);
                if (province) addressParts.push(province);
                var address = addressParts.join(', ');
                var nameUser = document.getElementById("form-register-nameUser").value;
                var pass = document.getElementById("form-register-pass").value;
                var passAgain = document.getElementById("form-register-passAgain").value;

                // check nhập lại mật khẩu
                if (pass !== passAgain) {
                    alert('Mật khẩu nhập lại không đúng!');
                    return;
                }

                // check có nhập đủ thông tin
                if (name == "" ||
                    email == "" ||
                    phone == "" ||
                    address == "" ||
                    nameUser == "" ||
                    pass == "" ||
                    passAgain == "") {
                    alert('Vui lòng nhập đầy đủ thông tin!');
                    return;
                }

                //check email
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert("Địa chỉ email không hợp lệ!");
                    return;
                }

                //check sdt
                // Same rule as XuLy_DangKy.php: a Vietnamese mobile number.
                var phoneRegex = /^(?:0|\+84|84)(?:3|5|7|8|9)\d{8}$/;
                if (!phoneRegex.test(phone.replace(/[\s.\-()]/g, ''))) {
                    alert("Số điện thoại không hợp lệ!");
                    return;
                }
                
                // Check email verification code
                var verificationCode = document.getElementById('form-register-verification-code').value;
                if (!verificationCode || verificationCode.length !== 6) {
                    alert('Vui lòng nhập mã xác nhận 6 chữ số từ email!');
                    return;
                }
                
                // Check if registering as shipper/employee
                var registerRoleElement = document.querySelector('input[name="registerRole"]:checked');
                var registerRole = registerRoleElement ? registerRoleElement.value : 'customer';
                var isShipper = (registerRole === 'shipper');
                var isEmployee = (registerRole === 'employee');
                var isStaff = (isShipper || isEmployee);
                var license = '';
                var vehicle = '';
                var note = '';
                var hometown = '';
                var permanentAddress = '';
                var temporaryAddress = '';
                var workLocation = '';
                var idCardFiles = null;
                var confirmChecked = false;
                
                if (isStaff) {
                    license = document.getElementById('form-register-license').value;
                    vehicle = ''; // Not required anymore - simplified form
                    note = document.getElementById('form-register-note').value;
                    hometown = document.getElementById('form-register-hometown').value;
                    permanentAddress = address; // Use main address as permanent address
                    temporaryAddress = document.getElementById('form-register-temporary-address').value;
                    idCardFiles = document.getElementById('form-register-id-card').files;
                    confirmChecked = document.getElementById('form-register-confirm').checked;
                    
                    // Get work location for employees
                    if (isEmployee) {
                        var workLocationRadio = document.querySelector('input[name="work_location"]:checked');
                        if (workLocationRadio) {
                            workLocation = workLocationRadio.value;
                        } else {
                            alert('Vui lòng chọn hình thức làm việc!');
                            return;
                        }
                    }
                    
                    // Validate required fields (simplified form)
                    if (!license || !hometown || !temporaryAddress) {
                        alert('Vui lòng điền đầy đủ thông tin (tất cả các trường bắt buộc)!');
                        return;
                    }
                    
                    if (!idCardFiles || idCardFiles.length < 2) {
                        alert('Vui lòng tải lên ít nhất 2 ảnh CMND/CCCD (mặt trước và mặt sau)!');
                        return;
                    }
                    
                    if (!confirmChecked) {
                        alert('Vui lòng xác nhận thông tin trên là chính xác!');
                        return;
                    }
                }
                
                // Upload ID card images if staff
                var idCardData = null;
                if (isStaff && idCardFiles && idCardFiles.length > 0) {
                    idCardData = new FormData();
                    for (var i = 0; i < idCardFiles.length; i++) {
                        idCardData.append('id_card_images[]', idCardFiles[i]);
                    }
                }
                
                // Prepare registration data
                var regData = {
                        csrf_token: <?php echo json_encode(csrf_token()); ?>,
                        'cf-turnstile-response': (document.querySelector('[name="cf-turnstile-response"]') || {}).value || '',
                        HoVaTen: name,
                        Email: email,
                        Sdt: phone,
                        DiaChi: address,
                        TenDangNhap: nameUser,
                        MatKhau: pass,
                        is_shipper: isShipper ? '1' : '0',
                        is_employee: isEmployee ? '1' : '0',
                        register_role: registerRole,
                        license: license,
                        vehicle: vehicle,
                        note: note,
                        hometown: hometown,
                        permanent_address: permanentAddress,
                        temporary_address: temporaryAddress,
                        work_location: workLocation,
                        verification_code: verificationCode
                    };
                
                // If staff, append files to FormData
                if (isStaff && idCardData) {
                    for (var key in regData) {
                        idCardData.append(key, regData[key]);
                    }
                }
                
                // Debug: Log registration data (without password)
                console.log('Registration attempt:', {
                    HoVaTen: name,
                    Email: email,
                    Sdt: phone,
                    DiaChi: address,
                    TenDangNhap: nameUser,
                    is_shipper: isShipper,
                    is_employee: isEmployee,
                    register_role: registerRole,
                    verification_code: verificationCode ? '***' : 'missing',
                    hasFiles: isStaff && idCardData ? 'yes' : 'no'
                });
                
                //truyền thông tin qua xử lý đăng ký
                // Use FormData for all registrations to ensure proper data encoding
                var formDataToSend;
                if (isStaff && idCardData) {
                    // Staff with ID card files - already has all data appended
                    formDataToSend = idCardData;
                } else {
                    // Regular registration - convert to FormData for consistency
                    formDataToSend = new FormData();
                    for (var key in regData) {
                        formDataToSend.append(key, regData[key]);
                    }
                }

                console.log('Sending registration data...');

                $.ajax({
                    type: 'POST',
                    url: '../Processing/XuLy_DangKy.php',
                    data: formDataToSend,
                    processData: false,
                    contentType: false,
                    xhrFields: {
                        withCredentials: true
                    },
                    crossDomain: false,
                    success: function(response) {
                        console.log('Registration response:', response); // Debug log
                        response = response.trim();
                        if (response == "success") {
                            alert('Đăng ký thành công!');
                            window.location.href = "Login.php";
                        } else if (response == "shipper_pending") {
                            alert('Đăng ký shipper thành công! Tài khoản của bạn đang chờ Admin phê duyệt. Bạn sẽ nhận được thông báo khi được phê duyệt.');
                            window.location.href = "Login.php";
                        } else if (response == "employee_pending") {
                            alert('Đăng ký nhân viên thành công! Tài khoản của bạn đang chờ Admin phê duyệt. Bạn sẽ nhận được thông báo khi được phê duyệt.');
                            window.location.href = "Login.php";
                        } else if (response == "invalid_code") {
                            alert('Mã xác nhận không đúng! Vui lòng kiểm tra lại mã từ email.');
                        } else if (response == "code_expired") {
                            alert('Mã xác nhận đã hết hạn (quá 30 phút)! Vui lòng gửi lại mã mới.');
                        } else if (response == "code_used") {
                            alert('Mã xác nhận đã được sử dụng! Vui lòng gửi lại mã mới nếu cần.');
                        } else if (response == "missing_code") {
                            alert('Vui lòng nhập mã xác nhận từ email!');
                        } else if (response == "weak_password") {
                            alert('Mật khẩu phải có ít nhất 10 ký tự.');
                        } else if (response == "invalid_upload") {
                            alert('Ảnh CMND/CCCD phải gồm đúng 2 tệp JPEG/PNG, tối đa 5 MB mỗi tệp.');
                        } else if (response == "invalid_staff_data") {
                            alert('Thông tin hồ sơ ứng tuyển chưa đầy đủ.');
                        } else if (response == "error") {
                            alert('Đăng ký không thành công! Vui lòng thử lại');
                        } else if (response == "tontai") {
                            alert('Tên đăng nhập đã tồn tại! Vui lòng thử lại');
                        } else {
                            // Unknown response - show it for debugging
                            console.error('Unknown response:', response);
                            alert('Lỗi không xác định: ' + response + '\nVui lòng kiểm tra console để biết thêm chi tiết.');
                        }
                    },
                    error: function(xhr, status, error) {
                        var knownErrors = {
                            invalid_code: 'Mã xác nhận không đúng! Vui lòng kiểm tra lại mã từ email.',
                            code_expired: 'Mã xác nhận đã hết hạn. Vui lòng gửi mã mới.',
                            code_used: 'Mã xác nhận đã được sử dụng. Vui lòng gửi mã mới.',
                            missing_code: 'Vui lòng nhập mã xác nhận từ email.',
                            weak_password: 'Mật khẩu phải có ít nhất 10 ký tự.',
                            invalid_upload: 'Ảnh CMND/CCCD phải gồm đúng 2 tệp JPEG/PNG, tối đa 5 MB mỗi tệp.',
                            invalid_staff_data: 'Thông tin hồ sơ ứng tuyển chưa đầy đủ.',
                            invalid_phone: 'Số điện thoại không hợp lệ. Vui lòng nhập số di động Việt Nam, ví dụ 0912345678.',
                            invalid_address: 'Vui lòng chọn Tỉnh/Quận/Phường và nhập địa chỉ chi tiết.',
                            turnstile_invalid: 'Xác minh chống bot không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.',
                            tontai: 'Tên đăng nhập hoặc email đã tồn tại.'
                        };
                        var serverCode = (xhr.responseText || '').trim();
                        if (knownErrors[serverCode]) {
                            alert(knownErrors[serverCode]);
                            return;
                        }
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);
                        console.error('Status Code:', xhr.status);
                        alert('Đã xảy ra lỗi khi gửi dữ liệu. Vui lòng kiểm tra console để biết thêm chi tiết.\n\nLỗi: ' + error + '\nStatus: ' + status);
                    },
                    complete: function() {
                        if (window.turnstile) {
                            window.turnstile.reset();
                        }
                    }
                });
            } catch (e) {
                console.error('Error in register function:', e);
                alert('Đã xảy ra lỗi: ' + e.message + '\nVui lòng kiểm tra console để biết thêm chi tiết.');
            }
        }
    </script>
</body>

</html>
