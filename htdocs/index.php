<?php require_once __DIR__ . '/../vendor/autoload.php'; ?>
<?php require_once __DIR__ . '/../src/getCustomSettings.php'; ?>
<?php use Youtrackinterfacer\authenticationAndSecurity as authenticationAndSecurity; ?>
<?php $authenticationAndSecurity = new authenticationAndSecurity; ?>
<?php $cookies = $authenticationAndSecurity->getBrowserCookies(); ?>
<!DOCTYPE html>
<head>
    <script src='js/jquery-1.10.2.min.js'></script>
    <script type="text/javascript" src="js/bootstrap/bootstrap-3.3.7-dist/bootstrap.min.js"></script>
    <link rel="stylesheet" href="css/bootstrap/bootstrap-3.3.7-dist/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap/bootstrap-3.3.7-dist/bootstrap-theme.min.css">
</head>
<html>
<body>
    <?php if ($authenticationType === 'password'): ?>
        <header>
            <?php require_once __DIR__.'/header.phtml'; ?>
        </header>
    <?php else: ?>
        <?php if ($cookies === null) : ?>
            <form action="../code/youtrackLogin.php" method="post" enctype="multipart/form-data">
                    <h1>Login</h1>
                    <div>login user: <input type="text" name="user"></div><br>
                    <div>login password: <input type="password" name="password"></div><br>
                    <input type="submit" value="Submit">
            </form>
        <?php else: ?>
            <header>
                <?php require_once __DIR__.'/header.phtml'; ?>
            </header>
        <?php endif; ?>
    <?php endif; ?>
        
</body>
</html>