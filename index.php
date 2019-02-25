<?php
    date_default_timezone_set('Europe/Moscow');
    
    $db = new PDO('sqlite:messages.sqlite'); 
    
    $db->query('
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message TEXT,
            time INTEGER
        );
    ');
    
    if (!empty($_REQUEST["message"])) {
        $db->exec('INSERT INTO messages (message, time) VALUES ('.$db->quote($_REQUEST["sMessage"]).', '.time().')');
        //print_r($db->errorInfo());
        die();
    }
    if (!empty($_REQUEST["comet"])) {
        error_reporting(E_ALL);
        ini_set("display_errors", "on");
        
        set_time_limit(0);
        while (@ob_end_flush()) {}
        ob_implicit_flush(1);
        
        $iTime = 0;
        $iLimit = 1;
        
        while(connection_status() == CONNECTION_NORMAL && $iLimit) {
            $st = $db->query("SELECT * FROM messages WHERE time>=$iTime ORDER BY id DESC");
            $aResults = $st->fetchAll();

            if (!empty($aResults[0])) {
                $iTime = $aResults[0]['time'];
                foreach ($aResults as $aRow) {
                    $sMessage = addslashes(date('[d.m.Y H:i:s]', $aRow['time'])." ".$aRow['message']);
                    echo "<script>parent.fnPrintMessage('$sMessage')</script>";
                }
            }
            
            sleep(5);
            $iLimit--;
        }
        
        die();
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <script>
            function fnPrintMessage(sMessage)
            {
                console.log(sMessage);
                document.getElementById("messages-box").innerHTML += '<div>'+sMessage+'</div>';
            }
            
            function fnSendMessage(event)
            {
                if (event.keyCode==13) {
                    var formData = new FormData();
                    formData.append("sMessage", document.getElementById("text-box").value);
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '/?message=1', true);
                    //xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send(formData);
                    document.getElementById("text-box").value = "";
                }
            }
        </script>
    </head>
    <body>
        <div id="messages-box" style="width:600px;height:480px;border:1px solid gray;overflow-y: scroll">
            
        </div>
        <input id="text-box" type="text" style="width:600px" onkeydown="fnSendMessage(event)">
        <iframe src="/?comet=1" height="0" width="0" border="0" style="border:none;">
    </body>
</html>
