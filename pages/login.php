<?php
use App\Controllers\AuthController;
$config = getConfig();
$setHead(<<<HTML
<title> Login - {$config['web']['name']}</title>
HTML);
$AuthController = new AuthController();
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$username || !$password) {
            // Handle missing username or password
            echo "<script>document.addEventListener('DOMContentLoaded',function(){ Swal.fire({icon:'warning', title:'Missing fields', text:'Please enter both username and password.'}); });</script>";
        } else {
            $login = $AuthController->Login($username, $password);
            if ($login['status'] == 'success') {
                setcookies('login_token', $login['token'], 365);
                header('Location: /');
                exit();

            } else {
                $msg = isset($login['message']) ? addslashes($login['message']) : 'Login failed';
                echo "<script>document.addEventListener('DOMContentLoaded',function(){ Swal.fire({icon:'error', title:'Login failed', text:'{$msg}'}); });</script>";
            }
        }

    }
}
?>

<form method="POST"
    class="flex bg-theme absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[650px] h-[554px]  rounded-[20px] shadow-xl">
    <input type="hidden" name="action" value="login">
    <button class="absolute right-[50px] top-[35px] text-[30px] btn-theme text-theme " type="button"><i
            class="fas fa-sun"></i></button>
    <div class="px-20 py-20 w-full flex flex-col items-center">
        <div class="w-full flex justify-center">
            <i class="text-theme fas fa-user text-[35px] px-4"></i>
            <h1 class="text-theme text-[30px]">Login</h1>
        </div>

        <div class="w-full mt-10 relative">
            <label class="text-theme label-bg absolute top-[-13px] left-[40px] px-[4px]" id="username-label"
                for="username">Username</label>
            <input class="w-full input-theme h-[60px] rounded-[55px] text-center text-[20px] outline-none shadow-sm"
                placeholder="Username" name="username" id="username" type="text">
        </div>

        <div class="w-full mt-5 relative">
            <label class="text-theme label-bg absolute top-[-13px] left-[40px] px-[4px]" id="password-label"
                for="username">Password</label>
            <input class="w-full input-theme h-[60px] rounded-[55px] text-center text-[20px] outline-none shadow-sm"
                placeholder="Password" name="password" id="password" type="password">
        </div>


        <button class="w-[60%] py-4 bg-[#506BF4] rounded-[50px] mt-10 text-white text-[20px] "
            type="submit">Submit</button>

    </div>
</form>


<script>
    $(document).ready(function () {
        $("#username-label").hide();
        $("#username").focus(function () {
            $(this).attr("placeholder", "");
            $("#username-label").fadeIn(200);

        }).blur(function () {
            $(this).attr("placeholder", "Username");
            $("#username-label").fadeOut(200);
        });


        $("#password-label").hide();
        $("#password").focus(function () {
            $(this).attr("placeholder", "");
            $("#password-label").fadeIn(200);

        }).blur(function () {
            $(this).attr("placeholder", "Password");
            $("#password-label").fadeOut(200);
        });
    });
</script>