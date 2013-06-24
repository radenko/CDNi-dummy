<?
    $max_file_size = str_replace ("M","000000",ini_get('upload_max_filesize'));
    $max_post_size = str_replace ("M","000000",ini_get('post_max_size'));
?>

<form action="putContent_upload.php" method="post" enctype="multipart/form-data">
    <label for="file">Title:</label>
    <input type="text" name="title" />
    <br />

    <input type="hidden" name="MAX_FILE_SIZE" value="<?=min($max_file_size,$max_post_size);?>"> 
    <label for="file">Filename:</label>
    <input type="file" name="file"/>
    <br />
    <input type="submit" name="submit" value="Submit" />
</form>