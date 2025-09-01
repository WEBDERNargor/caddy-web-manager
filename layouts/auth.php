<?php
$config=getConfig();
$useAuth=composables("useAuth");
$auth=$useAuth['check_login']();
if($auth['status']=='success'){
   $_SESSION['user_data']=$auth['user'];
}else{
    unset($_SESSION['user_data']);
}

if(!isset($_SESSION['user_data'])){
    header('location:/login');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="//cdn.datatables.net/2.3.3/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="/api/readfile/css.php?file=style">
    <script src="/api/readfile/js.php?file=cookie"></script>
    
    
    <?= $layout->getHead() ?>
</head>

<body class="">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="//cdn.datatables.net/2.3.3/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <script src="/api/readfile/js.php?file=script&v=4"></script>
  
        <?= $layout->getContent() ?>


    

   
</body>

</html>