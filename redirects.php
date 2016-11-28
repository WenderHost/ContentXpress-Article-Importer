<?php

class Redirects
{

    public static function contentXpress_logIn()
    {
        global $pagenow;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : false);
        if ($pagenow == 'admin.php' && ($page == 'contentXpress')) {
            $cxpuser = CXPRequest::contentXpressSessionGet("cxpuser");
            if (!empty($cxpuser)) {
                wp_redirect('admin.php?page=content');
                exit;
            }
        }
    }

    public static function contentXpressSessionEnd()
    {
        unset($_SESSION['importList']);
        session_destroy();
        wp_redirect('admin.php?page=contentXpress');
        exit;
    }
}

?>