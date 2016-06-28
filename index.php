<?php require_once __DIR__.'/src/getCustomSettings.php'; ?>
<?php require_once __DIR__.'/src/authenticationAndSecurity.php'; ?>
<?php $authenticationAndSecurity = new authenticationAndSecurity; ?>
<?php $cookies = $authenticationAndSecurity->getBrowserCookies();?>
<!DOCTYPE html>
<html>
<body>
<h1 style="text-align:center">Youtrack Import</h1>
<?php if($authenticationType === 'password'): ?>
<header>
    <?php require_once(__DIR__.'/header.phtml'); ?> 
</header>
<?php else: ?>
    <?php if ($cookies===null ) : ?>
        <form action="src/youtrackLogin.php" method="post" enctype="multipart/form-data">
                <h1>Login</h1>
                <div>login user: <input type="text" name="user"></div><br>
                <div>login password: <input type="password" name="password"></div><br>
                <input type="submit" value="Submit">
        </form>
    <?php else: ?>
        <header>
            <?php require_once(__DIR__.'/header.phtml'); ?>            
            <form action="src/youtrackLogout.php" method="post" enctype="multipart/form-data">
                <button name="submit" value="Submit">logout</button>
            </form>
        </header>
    <?php endif; ?>
<?php endif; ?>
        
</body>
</html>