<?php
    date_default_timezone_set('Europe/Moscow');
    error_reporting(E_ALL);
    ini_set("display_errors", "on");
    session_start();
    
    //$db = new PDO('sqlite:messages.sqlite');
    $db = new PDO('mysql:host=localhost', 'root', ''); 
    
    $db->query('
        CREATE DATABASE IF NOT EXISTS messages;
    ');
    
    $db->query('
        USE messages;
    ');
    
    $db->query('
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER AUTO_INCREMENT PRIMARY KEY,
            message TEXT,
            time INTEGER
        );
    ');

    if (!empty($_REQUEST["message"])) {
        $db->exec('INSERT INTO messages (message, time) VALUES ('.$db->quote($_REQUEST["sMessage"]).', '.time().')');
        //print_r($db->errorInfo());
        die();
    }
    if (!empty($_REQUEST["polling"])) {
        set_time_limit(70);
        //while (@ob_end_flush()) {}
        //ob_implicit_flush(1);
        
        if (!isset($_SESSION['iTime']))
            $_SESSION['iTime'] = 0;
        $iLimit = 60;
        
        while(connection_status() == CONNECTION_NORMAL && $iLimit) {
            $st = $db->query("SELECT * FROM messages WHERE time>".$_SESSION['iTime']." ORDER BY id DESC");
            $aResults = $st->fetchAll();

            if (!empty($aResults[0])) {
                $_SESSION['iTime'] = $aResults[0]['time'];
                $aResult = [];
                
                foreach ($aResults as $aRow) {
                    $aResult[] = addslashes(date('[d.m.Y H:i:s]', $aRow['time'])." ".$aRow['message']);
                    //ob_end_flush();
                    //echo "<script>parent.fnPrintMessage('$sMessage')</script>";
                    //ob_start();
                }
                
                die(json_encode($aResult));
            }
            
            //sleep(1);
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
        <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
        <script>
            $.ajaxSetup({ cache: false });
            
            function longPolling()
            {
                setTimeout(
                    function()
                    {
                        $.ajax({                
                            type: "POST",
                            url: "/?polling=1",
                            dataType: 'json',
                            async: true,
                            success: function(aResponse) 
                            {
                                for (iKey in aResponse) {
                                    fnPrintMessage(aResponse[aResponse.length-1-iKey]);
                                }
                                longPolling();
                            },
                            error: function()
                            {
                                longPolling();
                            }
                        });
                    },
                    1000
                );
            }
            
            function fnPrintMessage(sMessage)
            {
                document.getElementById("messages-box").innerHTML += '<div>'+sMessage+'</div>';
            }
            
            function fnSendMessage(event)
            {
                if (event.keyCode==13) {
                    $.ajax({                
                        type: "POST",
                        url: "/?message=1",
                        dataType: 'json',
                        data: {
                            sMessage: document.getElementById("text-box").value
                        }
                    });
                    document.getElementById("text-box").value = "";
                }
            }
            
            longPolling();
        </script>
    </head>
    <body>
        <div id="messages-box" style="width:600px;height:280px;border:1px solid gray;overflow-y: scroll">
            
        </div>
        <input id="text-box" type="text" style="width:600px" onkeydown="fnSendMessage(event)">
        <!--iframe src="/?comet=1" height="0" width="0" border="0" style="border:none;"-->
    </body>
</html>
