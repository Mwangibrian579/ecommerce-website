<?php
class ImageUploader {
    private $upload_dir;
    private $allowed_types;
    private $max_size;
    
    public function __construct() {
        $this->upload_dir = __DIR__ . '/../assets/uploads/products/';
        $this->allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->max_size = 5 * 1024 * 1024; // 5MB
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    // Upload single image
    public function uploadImage($file, $product_id) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadError($file['error']);
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_types)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $this->allowed_types);
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate file size
        if ($file['size'] > $this->max_size) {
            $errors[] = 'File too large. Maximum size: 5MB';
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate image dimensions and type
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            $errors[] = 'Invalid image file';
            return ['success' => false, 'errors' => $errors];
        }
        
        // Generate unique filename
        $filename = 'product_' . $product_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $this->upload_dir . $filename;
        $relative_path = 'assets/uploads/products/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Create thumbnail
            $this->createThumbnail($file_path, $filename);
            
            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $file_path,
                'relative_path' => $relative_path,
                'file_size' => $file['size'],
                'image_width' => $image_info[0],
                'image_height' => $image_info[1]
            ];
        } else {
            $errors[] = 'Failed to upload file';
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    // Create thumbnail
    private function createThumbnail($source_path, $filename) {
        $thumb_dir = $this->upload_dir . 'thumbs/';
        if (!file_exists($thumb_dir)) {
            mkdir($thumb_dir, 0755, true);
        }
        
        $thumb_path = $thumb_dir . 'thumb_' . $filename;
        $max_width = 300;
        $max_height = 300;
        
        list($source_width, $source_height, $source_type) = getimagesize($source_path);
        
        switch ($source_type) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_GIF:
                $source_image = imagecreatefromgif($source_path);
                break;
            case IMAGETYPE_WEBP:
                $source_image = imagecreatefromwebp($source_path);
                break;
            default:
                return false;
        }
        
        if ($source_image === false) {
            return false;
        }
        
        // Calculate thumbnail size
        $aspect_ratio = $source_width / $source_height;
        
        if ($source_width <= $max_width && $source_height <= $max_height) {
            $thumb_width = $source_width;
            $thumb_height = $source_height;
        } elseif ($aspect_ratio > 1) {
            $thumb_width = $max_width;
            $thumb_height = $max_width / $aspect_ratio;
        } else {
            $thumb_width = $max_height * $aspect_ratio;
            $thumb_height = $max_height;
        }
        
        $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
        
        // Preserve transparency for PNG and GIF
        if ($source_type == IMAGETYPE_PNG || $source_type == IMAGETYPE_GIF) {
            imagecolortransparent($thumb_image, imagecolorallocatealpha($thumb_image, 0, 0, 0, 127));
            imagealphablending($thumb_image, false);
            imagesavealpha($thumb_image, true);
        }
        
        imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, $thumb_width, $thumb_height, $source_width, $source_height);
        
        // Save thumbnail
        switch ($source_type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb_image, $thumb_path, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb_image, $thumb_path, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb_image, $thumb_path);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumb_image, $thumb_path, 90);
                break;
        }
        
        imagedestroy($source_image);
        imagedestroy($thumb_image);
        
        return true;
    }
    
    // Delete image file
    public function deleteImage($filename) {
        $file_path = $this->upload_dir . $filename;
        $thumb_path = $this->upload_dir . 'thumbs/thumb_' . $filename;
        
        $deleted = false;
        
        if (file_exists($file_path)) {
            $deleted = unlink($file_path);
        }
        
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
        
        return $deleted;
    }
    
    // Get upload error message
    private function getUploadError($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
    
    // Get thumbnail path
    public function getThumbnailPath($filename) {
        $thumb_path = 'assets/uploads/products/thumbs/thumb_' . $filename;
        if (file_exists(__DIR__ . '/../' . $thumb_path)) {
            return $thumb_path;
        }
        return 'assets/uploads/products/' . $filename; // Fallback to original
    }
}
?>