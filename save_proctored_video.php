<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['videoBlob'])) {
    $uploadDir = 'uploads/proctored_videos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'admin_video_' . time() . '.webm';
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['videoBlob']['tmp_name'], $targetFile)) {
        echo "Video saved successfully as: $filename";
    } else {
        echo "Failed to save the video.";
    }
} else {
    echo "No video uploaded.";
}
?>
