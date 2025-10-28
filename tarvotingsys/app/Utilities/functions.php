<?php
function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}
?>
