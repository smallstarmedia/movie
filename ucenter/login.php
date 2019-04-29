<?php include('../system/inc.php');
$op=$_GET['op'];

if(isset($_POST['submit'])){
    null_back($_POST['u_name'],'请输入用户名');
    null_back($_POST['u_password'],'请输入密码');
    $u_name = $_POST['u_name'];
    $u_password = $_POST['u_password'];
    $sql = 'select * from mkcms_user where u_name = "'.$u_name.'" and u_password = "'.md5($u_password).'" and u_status=1';
    $result = mysqli_query($conn,$sql);
    if(!! $row = mysqli_fetch_array($result)){

        $_data['u_loginnum'] = $row['u_loginnum']+1;
        $_data['u_loginip'] =$_SERVER["REMOTE_ADDR"];
        $_data['u_logintime'] =date('y-m-d h:i:s',time());
        if(!empty($row['u_end'])) $u_end= $row['u_end'];
        if(time()>$u_end){
            $_data['u_flag'] =="0";
            $_data['u_start'] =="";
            $_data['u_end'] =="";
            $_data['u_group'] =1;
        }else{
            $_data['u_flag'] ==$row["u_flag"];
            $_data['u_start'] ==$row["u_start"];
            $_data['u_end'] ==$row["u_end"];
            $_data['u_group'] =$row["u_group"];
        }
        mysqli_query($conn,'update mkcms_user set '.arrtoupdate($_data).' where u_id ="'.$row['u_id'].'"');
        $_SESSION['user_name']=$row['u_name'];
        $_SESSION['user_group']=$row['u_group'];
        if($_POST['brand1']){
            setcookie('user_name',$row['u_name'],time()+3600 * 24 * 365);
            setcookie('user_password',$row['u_password'],time()+3600 * 24 * 365);
        }
        header('location:userinfo.php');
    }else{
        alert_href('用户名或密码错误或者尚未激活','login.php?op=login');
    }
}
if(isset($_POST['reg'])){
    $username = stripslashes(trim($_POST['name']));
// 检测用户名是否存在
    $query = mysqli_query($conn,"select u_id from mkcms_user where u_name='$username'");
    if(mysqli_fetch_array($query)){
        echo '<script>alert("用户名已存在，请换个其他的用户名");window.history.go(-1);</script>';
        exit;
    }
    $result = mysqli_query($conn,'select * from mkcms_user where u_email = "'.$_POST['email'].'"');
    if(mysqli_fetch_array($result)){
        echo '<script>alert("邮箱已存在，请换个其他的邮箱");window.history.go(-1);</script>';
        exit;
    }
    $password = md5(trim($_POST['password']));
    $email = trim($_POST['email']);
    $regtime = time();
    $token = md5($username.$password.$regtime); //创建用于激活识别码
    $token_exptime = time()+60*60*24;//过期时间为24小时后
    $data['u_name'] = $username;
    $data['u_password'] =$password;
    $data['u_email'] = $email;
    $data['u_regtime'] =$regtime;
    if($mkcms_mail==1){
        $data['u_status'] =0;
    }else{
        $data['u_status'] =1;
    }
    $data['u_group'] =1;
    $data['u_fav'] =0;
    $data['u_plays'] =0;
    $data['u_downs'] =0;
//推广注册
    if (isset($_GET['tg'])) {
        $data['u_qid'] =$_GET['tg'];
        $result = mysqli_query($conn,'select * from mkcms_user where u_id="'.$_GET['tg'].'"');
        if($row = mysqli_fetch_array($result)){

            $u_points=$row['u_points'];
        }
    }
    $_data['u_points'] =$u_points+$mkcms_tuiguang;
    $sql = 'update mkcms_user set '.arrtoupdate($_data).' where u_id="'.$_GET['tg'].'"';
    if (mysqli_query($conn,$sql)) {}
    $data['u_question'] =$token;
    $str = arrtoinsert($data);
    $sql = 'insert into mkcms_user ('.$str[0].') values ('.$str[1].')';
    if (mysqli_query($conn,$sql)) {
        if($mkcms_mail==1){
//写入数据库成功，发邮件
            include("emailconfig.php");
            //创建$smtp对象 这里面的一个true是表示使用身份验证,否则不使用身份验证.
            $smtp = new Smtp($MailServer, $MailPort, $smtpuser, $smtppass, true);
            $smtp->debug = false;
            $mailType = "HTML"; //信件类型，文本:text；网页：HTML
            $email = $email;  //收件人邮箱
            $emailTitle = "".$mkcms_name."用户帐号激活"; //邮件主题
            $emailBody = "亲爱的".$username."：<br/>感谢您在我站注册了新帐号。<br/>请点击链接激活您的帐号。<br/><a href='".$mkcms_domain."ucenter/active.php?verify=".$token."' target='_blank'>".$mkcms_domain."ucenter/active.php?verify=".$token."</a><br/>如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问，该链接24小时内有效。<br/>如果此次激活请求非你本人所发，请忽略本邮件。<br/><p style='text-align:right'>-------- ".$mkcms_name." 敬上</p>";

            // sendmail方法
            // 参数1是收件人邮箱
            // 参数2是发件人邮箱
            // 参数3是主题（标题）
            // 参数4是邮件主题（标题）
            // 参数4是邮件内容  参数是内容类型文本:text 网页:HTML
            $rs = $smtp->sendmail($email, $smtpMail, $emailTitle, $emailBody, $mailType);
            if($rs==true){
                echo '<script>alert("恭喜您，注册成功！请登录到您的邮箱及时激活您的帐号！");window.history.go(-1);</script>';
            }
        }
        if($mkcms_smsname!=""){
            if($_POST['txtsmscode']=="" || $_POST['txtsmscode']!=$_SESSION['mobilecode']){
                echo "<script type='text/javascript'>alert('短信验证码不能为空！');history.go(-1);</script>";
            }
            else{
                echo '<script>alert("恭喜您，注册成功！");window.history.go(-2);</script>';
            }
        }
        else
        {
            echo '<script>alert("恭喜您，注册成功！");window.history.go(-2);</script>';
        }
    }

}
?>
<!DOCTYPE html>
<!--[if IE 9]>         <html class="no-js lt-ie10" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title>会员登录</title>
    <meta name="keywords" content="yy4480高清影院,yy6080,免费电影,电影在线观看">
    <meta name="description" content="98影视网，是专门做剧集,电影等在线播放服务，本页面提供电影的相关内容。">
    <meta name="author" content="pixelcave">
    <meta name="robots" content="noindex, nofollow">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/icon57.png" sizes="57x57">
    <link rel="apple-touch-icon" href="img/icon72.png" sizes="72x72">
    <link rel="apple-touch-icon" href="img/icon76.png" sizes="76x76">
    <link rel="apple-touch-icon" href="img/icon114.png" sizes="114x114">
    <link rel="apple-touch-icon" href="img/icon120.png" sizes="120x120">
    <link rel="apple-touch-icon" href="img/icon144.png" sizes="144x144">
    <link rel="apple-touch-icon" href="img/icon152.png" sizes="152x152">
    <link rel="apple-touch-icon" href="img/icon180.png" sizes="180x180">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Bootstrap is included in its original form, unaltered -->
    <link rel="stylesheet" href="static/css/bootstrap.min.css">

    <!-- Related styles of various icon packs and plugins -->
    <link rel="stylesheet" href="static/css/plugins.css">

    <!-- The main stylesheet of this template. All Bootstrap overwrites are defined in here -->
    <link rel="stylesheet" href="static/css/main.css">

    <!-- Include a specific file here from css/themes/ folder to alter the default theme of the template -->

    <!-- The themes stylesheet of this template (for using specific theme color in individual elements - must included last) -->
    <link rel="stylesheet" href="static/css/themes.css">
    <!-- END Stylesheets -->

    <!-- Modernizr (browser feature detection library) -->
    <script src="static/js/modernizr-3.3.1.min.js"></script>
</head>
<body>
<!-- Full Background -->
<!-- For best results use an image with a resolution of 1280x1280 pixels (prefer a blurred image for smaller file size) -->
<img src="static/picture/lo.jpg" alt="Full Background" class="full-bg animation-pulseSlow">
<!-- END Full Background -->

<!-- Login Container -->
<div id="login-container">
    </script>
    </head>
    <body>


    <h1 class="h2 text-light text-center push-top-bottom animation-slideDown">

        在线电影</i> <strong> </strong>
    </h1>

    <!-- END Login Header -->

    <!-- Login Block -->
    <div class="block animation-fadeInQuick">
        <!-- Login Title -->
        <div class="block-title">
        <h2>用户登陆</h2>
        </div>
        <!-- END Login Title -->

        <!-- Login Form -->
        <form class="form-horizontal js-ajax-form" method="post">
        <div class="form-group">
        <label for="login-email" class="col-xs-12">账号</label>
        <div class="col-xs-12">
        <input type="text" id="login_username" name="u_name" class="form-control" placeholder="请输入账号..">
        </div>
        </div>
        <div class="form-group">
        <label for="login-password" class="col-xs-12">密码</label>
        <div class="col-xs-12">
        <input type="password" id="login_password" name="u_password" class="form-control" placeholder="请输入密码..">
        </div>
        </div>
        <div class="form-group form-actions">
        <div class="col-xs-8">
        <label class="csscheckbox csscheckbox-primary">
        <input type="checkbox" id="login-remember-me" name="remember" checked><span></span>
    <small>记住我?</small>
    </label>
    </div>
    <div class="col-xs-4 text-right">
        <button type="submit" class="btn btn-effect-ripple btn-sm btn-success"name="submit"></i>登录</button>
    </div>
    </div>
    </form>
    <!-- END Login Form -->

    <!-- Social Login -->
    <hr>
    <div class="push text-center">- other -</div>
        <div class="row push">
        <div class="col-xs-6">
        <a href="repass.php" class="btn btn-effect-ripple btn-sm btn-info btn-block"><i class="fa fa-facebook"></i> 找回密码</a>
    </div>
    <div class="col-xs-6">
        <a href="reg.php" class="btn btn-effect-ripple btn-sm btn-primary btn-block"><i class="fa fa-twitter"></i> 用户注册</a>
    </div>
    </div>
    <!-- END Social Login -->
    </div>
    <!-- END Login Block -->


    <!-- Footer -->
    <footer class="text-muted text-center animation-pullUp">
        <small><span id="year-copy"></span> &copy; <a href="../../index.php" target="_blank"><span style="color:#000;text-align:center;">本网站内容收集于互联网，热片网不承担任何由于内容的合法性及健康性所引起的争议和法律责任</span><br />
    <span style="color:#000;text-align:center;">Copyright &copy;&nbsp;</span></a></small>
    </footer>
    <!-- END Footer -->
    </div>
    <!-- END Login Container -->

    </div>
    </div>
    </div>
    </div>
    </div>
    <!-- jQuery, Bootstrap, jQuery plugins and Custom JS code -->
    <script src="static/js/jquery-2.2.4.min.js"></script>
    <script src="static/js/bootstrap.min.js"></script>
    <script src="static/js/plugins.js"></script>
    <script src="static/js/app.js"></script>

    <!-- Load and execute javascript code used only in this page -->
    <script src="static/js/readylogin.js"></script>
    <script>$(function(){ ReadyLogin.init(); });</script>
</body>
</html>