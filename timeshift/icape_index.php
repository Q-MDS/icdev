<?  
    $changeopts = 0;
    if (!isset($firstlogin))
	$firstlogin = 0;
    if (!isset($skipdefault))
	$skipdefault = "";
    $shown = 0;
    if (!isset($defaultpage))
	$defaultpage = "";




    function checkmail()
    {
        global $cursor, $conn;
	
        $user = getenv("REMOTE_USER");
        $kcur = $cursor;
        ora_parse($cursor, "select staff_member, staff_no from user_details where username='$user' and is_current='Y'");
        ora_exec($cursor);
        ora_fetch($cursor);
        $is_staff = getdata($cursor,0);
	$my_staff_code = getdata($cursor,1);

        ora_parse($kcur, "select highest_read from announcement_status where username='$user'");
        ora_exec($kcur);
        if (ora_fetch($kcur)):
            $hread = getdata($kcur,0);
        else:
            $hread = 0;
            ora_parse($kcur, "insert into announcement_status values ('$user',0)");
            ora_exec($kcur);
        endif;

        if ($is_staff == "Y")
            ora_parse($kcur, "select max(serial) from announcements where expiry_date>=CURRENT_TIMESTAMP and for_staff='Y' and (branch_code is null or branch_code='$my_staff_code')");
        else
            ora_parse($kcur, "select max(serial) from announcements where expiry_date>=CURRENT_TIMESTAMP and for_agents='Y'");
        ora_exec($kcur);
        if (ora_fetch($kcur))
            $highest = getdata($kcur,0);

        //echo "highest $highest hread is $hread<br>";

        if ($hread < $highest):
            require_once("read_announce.phtml");
            exit;
        endif;
    }

    function go_on()
    {
        global $mycurr, $defaultpage;

        checkmail();
        echo "<form action='icape_index.php' method='post' name='goforit'><input name='defaultpage' type='hidden' value='$defaultpage' /><input name='firstlogin' type='hidden' value='0' /><a href='icape_index.php'>Click here to continue</a>";
        echo "<script type='text/javascript'>document.goforit.submit();</script></form>";
        exit;
    } //go_on

?>
<!DOCTYPE html>
<html>
<?

    $user = getenv("REMOTE_USER");


        require_once ("../php3/context.inc");
        require_once ("../php3/oracle.inc");
        require_once ("../php3/colors.inc");
        require_once ("../php3/logs.inc");
        require_once ("../php3/misc.inc");
        require_once ("../php3/sec.inc");
        require_once ("../php3/trans.inc");

        if (!isset($conn)) {
            if (!open_oracle()) {
                go_on();
                exit;
            }
        }

        $mycurr = "";

        ora_parse($cursor, "select staff_member,user_ip,user_serial,m_currency,default_page,pw_reset,email,cavmailpw,staff_no from user_details where username='$user' and is_current='Y'");
        ora_exec($cursor);

        if (!ora_fetch($cursor)) {

		echo "<font color=red>Sorry, you appear to be locked out.</font>";
		exit;

	} 
            if (getdata($cursor,5) == "Y" ) {
                echo "\n<script>\nalert('Your password has expired\\n\\nClick Ok, then log in again with your old password\\nThen, follow the prompts.'); window.location='/booking/change_pass_1.phtml?force=Y'\n</script>";
                echo "You must change your password!  <a href=/booking/change_pass_1.phtml?force=Y>Click here to continue</a>";
                exit;
            }
            $is_staff = getdata($cursor, 0);
            $userial = getdata($cursor, 2);
            $mycurr = getdata($cursor, 3);
            $defaultpage = getdata($cursor, 4);
	    $my_staff_no = getdata($cursor, 8);
            $emailu = getdata($cursor, 6);
            if (@strstr("@", $emailu)) {
                $split = explode("@", $emailu);
                $lune = strtolower($split[0]);
            }
            else {
                $lune = strtolower($user);
            }
            $lpw = getdata($cursor, 7);

?>

    <script type="text/javascript">

	var toolbarcontent = 0;
	var lastcaller = '';

        //helper functions
        function toggleclass(element, cssclass) {
            if (element.classList) { 
                element.classList.toggle(cssclass);
            } 
            else {
                // For IE9
                var classes = element.className.split(" ");
                var i = classes.indexOf(cssclass);
                if (i >= 0) 
                    classes.splice(i, 1);
                else 
                    classes.push(cssclass);
                element.className = classes.join(" ");                    
            }
        }
        function addclass(element, cssclass) {
            if (element.classList) { 
                element.classList.add(cssclass);
            } 
            else {
                // For IE9
                var classes = element.className.split(" ");
                if (classes.indexOf(cssclass) === -1) {
                    element.className += " " + cssclass;
                }              
            }
        }
        function removeclass(element, cssclass) {
            if (element.classList) { 
                element.classList.remove(cssclass);
            } 
            else {
                // For IE9
                element.className = element.className.replace(new RegExp('\\b' + cssclass + '\\b', 'g'), '');                
            }
        }        

        //content element
        var content = document.getElementById('content');
        var mainiframe = document.getElementsByName("Main")[0];
        //main page back button
        function onmainback() {
            addclass(content, 'main-loading');
            try {
                var backurl = mainiframe.contentDocument.body.dataset.back;
                if (backurl) {
                    mainiframe.contentDocument.location = backurl;
                }
                else {
                    Main.history.back();
                }
            }
            catch (err) {
                console.log(err);
                Main.history.back();
            }
        }


	function myonload() {
        //toggle toolbar

	console.log('onload is running');
        toolbarcontent = document.getElementById('toolbar-content');
        var toolbarbtn = document.getElementById('toolbar-toggle');        
        toolbarbtn.onclick = function() {
            toggleclass(document.getElementById('content'), 'toolbar-open');
        };
	console.log('got here');

        //load toolbar
        gettoolbar();
        setInterval(reloadtoolbar, 180000);        

	}

        //toolbar functions        
        function reloadtoolbar() {
            var toolbarnav = document.getElementById('toolbar-nav');
            gettoolbar(toolbarnav ? toolbarnav.dataset.msgcnt : null);
        }
        
        function gettoolbar(msgcnt) {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.responseText.indexOf('toolbar-body') > -1) {
                    toolbarcontent.innerHTML = xhr.responseText;
                    ontoolbarload();
                }
            };
            xhr.open('GET', 'toolbar.phtml?mycurr=<?=$mycurr?>' + (msgcnt ? '&msgcnt=' + msgcnt : ''));
            xhr.setRequestHeader('cache-control', 'no-cache, post-check=0, pre-check=0');
            xhr.setRequestHeader('cache-control', 'max-age=0');
            xhr.setRequestHeader('expires', '0');
            xhr.setRequestHeader('expires', 'Tue, 01 Jan 1980 1:00:00 GMT');
            xhr.setRequestHeader('pragma', 'no-cache');
            xhr.send();
        }
        
        function ontoolbarload() {
            var navlinks = toolbarcontent.querySelectorAll('.nav-link');
            for (var i = 0; i < navlinks.length; i++) {
                navlinks[i].addEventListener('click', function (event) {                    
                    if (event.currentTarget) {
                        initloading(event.currentTarget.target);
                    }
                    removeclass(content, 'toolbar-open');
                }, false);
            }
            getcalls();
            triggeralarms();
            triggerannouncement();            
        }
        function triggeralarms() {
            var toolbarnav = document.getElementById('toolbar-nav');
            if (toolbarnav) {
                var alarmsstring = toolbarnav.dataset.alarms;
                if (alarmsstring) {
                    var alarms = JSON.parse(alarmsstring);
                    if (alarms && alarms.length > 0) {
                        for (var i = 0; i < alarms.length; i++) {
                            floater = window.open('alarm_ring.phtml?asn=' + alarms[i].serial, 'Alarm' + alarms[i].serial, ',left=' + alarms[i].position + ',top=' + alarms[i].position + ',width=250,resizable=1,height=250,scrollbars=1');
                            floater.focus();
                        }
                    }
                }
            }
        }
        function triggerannouncement() {
            var toolbarnav = document.getElementById('toolbar-nav');
            if (toolbarnav) {
                var announcementstring = toolbarnav.dataset.announcement;
                if (announcementstring) {
                    var announcement = parseInt(announcementstring);
                    if (announcement && announcement === 1) {
                        floater = window.open('read_announce.phtml?closit=1', 'Ann', ',left=20,top=20,width=600,resizable=1,height=500,scrollbars=1');
                        floater.focus();
                    }
                }
            }
        }
        //call notifications
        var callstimeout;
        function getcalls() {
            clearTimeout(callstimeout);
<?
	
	 if ($my_staff_no != "0101" || $_SERVER["HTTP_HOST"]=="192.168.10.239")
		echo "return true; // not call centre ... dont run \n";
?>
	 if (!document.hasFocus()) {
		console.log('not in focus....');
		 setTimeout(function() {
                        getcalls();
                    }, 3000 ) ;
		return;
	  }

	    console.log('call request');
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var callfound = false;
                    if (xhr.status == 200) {
                        console.log(xhr.responseText);
			if (xhr.responseText != "")
                        try {
                            var callsresponse = JSON.parse(xhr.responseText);
                            var callcontainer = document.getElementById('call-notifications');
                            if (callcontainer) {
                                if (callsresponse.caller && callsresponse.callerhtml) {
                                    if (callcontainer.dataset.response !== callsresponse.caller) {
                                        callcontainer.dataset.response = callsresponse.caller;
                                        callcontainer.innerHTML = callsresponse.callerhtml;
                                        callfound = true;
					lastcaller = callsresponse.caller;
                                        //console.log('update caller');
                                    }
                                }
                                else if (callsresponse.error && callsresponse.errorhtml) {
                                    if (callcontainer.dataset.response !== callsresponse.error) {
                                        callcontainer.dataset.response = callsresponse.error;
                                        callcontainer.innerHTML = callsresponse.errorhtml;
                                        //console.log('update error');
                                    }
                                }
                                else {
                                    if (callcontainer.innerHTML != "") {
                                        callcontainer.dataset.response = '';
                                        callcontainer.innerHTML = "";
                                        //console.log('update empty');
                                    }
                                }
                                //console.log(callsresponse);
                            }
                        }
                        catch (err) {
                            console.log(err);
                        }
                    }
                    //wait a few seconds before refreshing if someone new is calling
                    callstimeout = setTimeout(function() {
                        getcalls();
                    }, callfound ? 6000 : 3000); 
                }
            };
            xhr.open('GET', 'mycall_json.phtml?contenttype=json&last='+escape(lastcaller));
            xhr.setRequestHeader('cache-control', 'no-cache, post-check=0, pre-check=0');
            xhr.setRequestHeader('cache-control', 'max-age=0');
            xhr.setRequestHeader('expires', '0');
            xhr.setRequestHeader('expires', 'Tue, 01 Jan 1980 1:00:00 GMT');
            xhr.setRequestHeader('pragma', 'no-cache');
            xhr.send();
        }
        

        /*function newcall(thenumber) {
            document.getElementById('calldetails').src='showcallerdetails2.phtml?number='+thenumber;
        }
        function calldetails(url) {
            try {
                parent.window.Main.location = url;
            } 
            catch (error) {
                reviewcalls = window.open(url,'reviewcalls','top=40,left=10,height=500,width=600,scrolling=auto'); 
                reviewcalls.focus();
            }
        }*/
        function switchoff(flag) { 
            document.getElementById('flag').src = 'images/' + flag; 
        }
        function doquit() {
            if (confirm('Quit Application?')) {
                window.location = 'logoff.phtml';
            }
        }
    </script>

<?

    if ($firstlogin == 1 ) {
            include_once ("/usr/local/www/pages/booking/daily_pass_check.phtml");

            ?>
            <script type="text/javascript">
                alert('Dont be the victim of fraud !\r*********************************\r\rRemember to log off & close all browser windows\ror use the new screen lock program\rwhen you are not at your PC\rand\rmake sure NOBODY knows your password.\r\r(Tip: Change your password regularly)');
            </script>
            <?



            $kcur = ora_open($conn);	
            ora_parse($kcur, "update user_details set bad_pw_counter=0, last_use=CURRENT_TIMESTAMP where username='$user' and is_current='Y'");
            ora_exec($kcur);
            ora_commit($conn);

     }

	        echo "<script> closethis=1; </script>";

	        if ($skipdefault != "Y") {
	            if ($defaultpage == "J" && $fromjump != "Y") {
                    echo "\n<script>\nwindow.location='/newjump/';\n</script>";
                    exit;
                }

		if ($defaultpage == "t") { // truck stop
			echo "\n<script>\nwindow.location='/ops/dbnfuel.phtml';\n</script>";
                            exit;

		}
	            if ($defaultpage == "o") {
			if (getenv("REMOTE_USER")=="xKeith")
			    echo "\n<script>\nwindow.location='/booking/keith/tablet_index.phtml';\n</script>";	
			else
		            echo "\n<script>\nwindow.location='tablet_index.phtml';\n</script>";
		        exit;
                }
                if (strstr($_SERVER["HTTP_USER_AGENT"], "Android")) {
			if (getenv("REMOTE_USER")=="xKeith")
                            echo "\n<script>\nwindow.location='/booking/keith/tablet_index.phtml';\n</script>";
                        else
                    	    echo "\n<script>\nwindow.location='tablet_index.phtml';\n</script>";
                    exit;
                }
                if ($defaultpage == "P") {
                    echo "\n<script>\nwindow.location='/parcel/index.phtml';\n</script>";
                    exit;
                }
                if ($defaultpage == "M") {
                    echo "\n<script>\nwindow.location='/move/index.phtml';\n</script>";
                    exit;
                }
                if ($defaultpage == "O") {
                    echo "\n<script>\nwindow.location='/ops/index.phtml';\n</script>";
                    exit;
                }
	            if ($defaultpage == "S") {
		       echo "<form method='post' id='myform' action='/mail/src/redirect.php'><input type='hidden' name='login_username' value='$lune' /><input type='hidden' name='secretkey' value='$lpw' /><input type='submit' value='Click here to continue' /></form><script>document.forms.myform.submit();</script>";
                       exit;
                }
            }
            

     if ($firstlogin == 1) {
            if ($is_staff != 'Y') {
                go_on(); //let non-staff through
                exit;
            }

            checkmail();
	    go_on();
        exit;
    }

    $pagetitle = "Intercape Booking System";
?>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?=$pagetitle?></title>
    <link rel="stylesheet" type="text/css" href="css/icape_index.css">
    <!--[if IE 9]>
    <style type="text/css">
        #content {
            min-height: 100vh;
        }
        #main {
            height: 100vh;
        }
    </style>
    <![endif]-->


</head>
<body onload="myonload()">
    <div id="content">
        <div id="header">
            <button type="button" id="page-back-button" onclick="onmainback();"><i class="icofont-simple-left"></i></button>
            <h1 id="page-title" data-default="<?=$pagetitle?>"><?=$pagetitle?></h1>
          <? //  <a href="trynew.phtml" id="switch-version-button">Switch back to the old look</a> ?>
            <a href="javascript:void(0)" id="toolbar-toggle"><i class="icofont-navigation-menu"></i></a>
            <div class="loading-bar">
            </div>
        </div>
        <div id="toolbar">
            <div id="toolbar-content"></div>
        </div>
        <div id="main">
            <iframe name="Main"  src="main.phtml?front" scrolling="auto" frameborder="0"></iframe>
        </div>
    </div>

</body>
</html>

