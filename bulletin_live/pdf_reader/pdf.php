<?php
ob_start();
require_once ("/usr/local/www/pages/php3/oracle.inc");
require_once ("/usr/local/www/pages/php3/misc.inc");
require_once ("/usr/local/www/pages/php3/sec.inc");

if (!open_oracle()) { Exit; };

// TEST SERVER ONLY:
$_GET["url"] = str_replace("https://secure.intercape.co.za","http://192.168.10.239",$_GET["url"]);

$url = $_GET["url"];

echo "<a href='$url'>Click here to download as a PDF</a><br>";

/**
 * Quintin: 2025-02-11
 * Decription: Add record to new table move_tech_bulletins_pdflog when pdf is opened
 * 
 * START
 */
$user_serial = $_GET["u"];
$bulletin_id = $_GET["i"];

add_pdf_log($user_serial, $bulletin_id);

function oci_conn()
{
	$host = 'localhost';
	$port = '1521';
	$sid = 'XE';
	$username = 'SYSTEM';
	$password = 'dontletmedown3';

	$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port)))(CONNECT_DATA=(SID=$sid)))");

	if (!$conn) 
	{
		$e = oci_error();
		// echo "Connection failed: " . $e['message'];
		exit;
	} 
	else 
	{
		// echo "Connection succeeded";
	}

	return $conn;
}

function add_pdf_log($user_serial, $bulletin_id)
{
	global $conn;

	$now = time();

	$sql = "INSERT INTO move_tech_bulletins_pdflog (USERSERIAL, ADD_DATE, BULLETIN_ID) VALUES (:user_serial, :add_date, :bulletin_id)";

	$stmt = oci_parse($conn, $sql);

	oci_bind_by_name($stmt, ':user_serial', $user_serial);
	oci_bind_by_name($stmt, ':bulletin_id', $bulletin_id);
	oci_bind_by_name($stmt, ':add_date', $now);

	oci_execute($stmt);

	oci_free_statement($stmt);
}
/**
 * END
 */
?>

<style>
    canvas{
        /* width: 100%; */
    }
</style>

<script src="/move/pdf.js" type="module"></script>

<script type="module">
  // If absolute URL from the remote server is provided, configure the CORS
  // header on that server.
	  var url = '<?php   echo $_GET["url"]?>';

 

  // Loaded via <script> tag, create shortcut to access PDF.js exports.
  var { pdfjsLib } = globalThis;

  // The workerSrc property shall be specified.
  pdfjsLib.GlobalWorkerOptions.workerSrc = '/move/pdf.worker.js';

  // Asynchronous download of PDF
  var loadingTask = pdfjsLib.getDocument(url);
  loadingTask.promise.then(function(pdf) {
    console.log('PDF loaded');

    console.log(pdf);

    console.log(pdf.numPages);
    

    for (let i = 1; i <= pdf.numPages; i++){
        
        // Fetch page
        var pageNumber = i;
        pdf.getPage(pageNumber).then(function(page) {
            console.log('Page loaded');

            var scale = 1.5;
            var viewport = page.getViewport({scale: scale});

            // Prepare canvas using PDF page dimensions
            // var canvas = document.getElementById('the-canvas');
            var canvas = document.createElement('canvas');
            document.body.appendChild(canvas);

            var context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            // Render PDF page into canvas context
            var renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            var renderTask = page.render(renderContext);
            renderTask.promise.then(function () {
                console.log('Page rendered');
            });
        });
    }
  }, function (reason) {
    // PDF loading error
    console.error(reason);
  });
</script>
