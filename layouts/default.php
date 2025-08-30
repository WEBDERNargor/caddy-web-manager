<?php
$config=getConfig();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
     <link rel="stylesheet" href="/api/readfile/css.php?file=style"></script>
    <script src="/api/readfile/js.php?file=cookie"></script>
    
    </style>
    <?php if (isset($_SESSION['user'])): ?>
    <?php endif; ?>
    
    <?= $layout->getHead() ?>
</head>

<body class="">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/api/readfile/js.php?file=script"></script>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <main class="pt-16">
        <?= $layout->getContent() ?>
    </main>

    

   
</body>

</html>