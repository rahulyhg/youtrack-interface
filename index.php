<?php require_once __DIR__.'/src/getCustomSettings.php'; ?>
<?php require_once __DIR__.'/src/authenticationAndSecurity.php'; ?>
<?php $authenticationAndSecurity = new authenticationAndSecurity; ?>
<?php $cookies = $authenticationAndSecurity->getBrowserCookies();?>
<!DOCTYPE html>
<html>
<body>
<h1 style="text-align:center">Youtrack Import</h1>
<header>
    <a href="csvUploader.phtml">upload a csv</a><br>
    <a href="createByForm.phtml">create multiple youtrack tickets by using online form</a><br/>
    <a href="downloadProjectIssue.phtml">Download all issues in a project as xml</a><br>
    <a href="downloadFields.phtml">Download all custom field options and users csv</a><br>
    <a href="timeTracker.phtml">Time Tracker</a><br>
</header>
<?php if($authenticationType !== 'password'): ?>
    <?php if ($cookies===null ) : ?>
        <form action="src/youtrackLogin.php" method="post" enctype="multipart/form-data">
                <h1>Login</h1>
                <div>login user: <input type="text" name="user"></div><br>
                <div>login password: <input type="password" name="password"></div><br>
                <input type="submit" value="Submit">
        </form>
    <?php else: ?>
        <a href="csvUploader.phtml">upload a csv</a><br>
        <a href="createByForm.phtml">create multiple youtrack tickets by using online form</a><br/>
        <a href="downloadProjectIssue.phtml">Download all issues in a project as xml</a><br>
        <a href="downloadFields.phtml">Download all custom field options and users csv</a><br>
        <form action="src/youtrackLogout.php" method="post" enctype="multipart/form-data">
            <button name="submit" value="Submit">logout</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
        
</body>
</html>