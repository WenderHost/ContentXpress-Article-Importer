<?php

class Redirects
{
    public static function contentXpressSessionEnd()
    {
        unset($_SESSION['importList']);
        session_destroy();
        wp_redirect('admin.php?page=contentXpress');
        exit;
    }
}

?>