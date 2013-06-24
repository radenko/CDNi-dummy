<?php
    if (isset($_REQUEST['action']) && $_REQUEST['action']=='add') {
        
        
    }
    else {
        echo "<form method='post' enctype='multipart/form-data'>";
        echo "<input type='hidden' name='MAX_FILE_SIZE' value='3000000' />\n";
        echo "<table>";
        echo "<tr><td>Title</td><td><input type='text' name='title' /></td></tr>";
        echo "<tr><td>Descr</td><td><textarea name='description'></textarea></td></tr>";
        echo "<tr><td>File</td><td><input type='file' name='file' /></td></tr>";
        echo "<tr><td><input type='submit' name='action' value='add' /></td></tr>";
        echo "</table>";
        echo "</form>";
    }
?>
