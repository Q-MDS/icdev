<?php



// TEST SERVER ONLY:
$_GET["url"] = str_replace("https://secure.intercape.co.za","http://192.168.10.239",$_GET["url"]);

$url = $_GET["url"];

echo "<a href='$url'>Click here to download as a PDF</a><br>";
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
