<?php
function mylog($type, $message){
    $MYSQL_USER='user';
    $MYSQL_PW='pass';
    $MYSQL_HOST='localhost';
    $MYSQL_DB='deinCT';
    $dbconnection = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PW, $MYSQL_DB);
    if (mysqli_connect_errno()) {
        if(isset($_SESSION["twitter_uid"])){$user=$_SESSION["twitter_uid"];}else{$user="No user";}
        file_put_contents("/var/www/logs/withoutmysql.log", "[".date("d.m.Y H:i:s")." - $user - ".((isset($_SERVER["REMOTE_ADDR"]))?$_SERVER["REMOTE_ADDR"]:"local")."] ".$message.PHP_EOL, FILE_APPEND | LOCK_EX);
    }else{
        if(isset($_SESSION["twitter_uid"])){
            $stmt = $dbconnection->prepare("INSERT INTO log (type, user, message, time, ip) VALUES (?, ?, ?, ?, ?)");
            $user=$_SESSION['twitter_uid'];
            $time=time();
            $ip=((isset($_SERVER["REMOTE_ADDR"]))?$_SERVER["REMOTE_ADDR"]:"local");
            $stmt->bind_param('sssis', $type, $user, $message, $time, $ip);
        }else{
            $stmt = $dbconnection->prepare("INSERT INTO log (type, message, time, ip) VALUES (?, ?, ?, ?)");
            $time=time();
            $ip=((isset($_SERVER["REMOTE_ADDR"]))?$_SERVER["REMOTE_ADDR"]:"local");
            $stmt->bind_param('ssis', $type, $message, $time, $ip);
        }
        if(false===$stmt->execute()){
            //error while mysql log, save to file and save the mysql error too
            if(isset($_SESSION["twitter_uid"])){$user=$_SESSION["twitter_uid"];}else{$user="No user";}
            file_put_contents("/var/www/logs/withoutmysql.log", "[".date("d.m.Y H:i:s")." - $user - ".((isset($_SERVER["REMOTE_ADDR"]))?$_SERVER["REMOTE_ADDR"]:"local")."] ".$message.PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents("/var/www/logs/withoutmysql.log", "[".date("d.m.Y H:i:s")." - mysql error]".$stmt->error.PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        $stmt->close();
    }
    $dbconnection->close();
}
?>