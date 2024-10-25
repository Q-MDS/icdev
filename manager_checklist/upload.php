<?php
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    if (isset($_FILES['fault_picture']) && $_FILES['fault_picture']['error'] === UPLOAD_ERR_OK) 
	{
		$vehicle_serial = $_POST['vehicle_serial'];
        $fileTmpPath = $_FILES['fault_picture']['tmp_name'];
        $fileName = $_FILES['fault_picture']['name'];
        $fileSize = $_FILES['fault_picture']['size'];
        $fileType = $_FILES['fault_picture']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = $vehicle_serial . '.' . $fileExtension;

        // Check if the file has one of the allowed extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory in which the uploaded file will be moved
            // $uploadFileDir = './uploaded_files/';
            $uploadFileDir = '../uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $response['success'] = true;
                $response['message'] = 'File is successfully uploaded.';
            } else {
                $response['message'] = 'There was some error moving the file to upload directory. Please make sure the upload directory is writable by web server.';
            }
        } else {
            $response['message'] = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions);
        }
    } else {
        $response['message'] = 'There is some error in the file upload. Please check the following error.<br>';
        $response['message'] .= 'Error:' . $_FILES['fault_picture']['error'];
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>