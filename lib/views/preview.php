<?php
require_once ( CXP_PLUGIN_DIR_PATH . 'lib/classes/ArticleToImport.php' );
require_once ( CXP_PLUGIN_DIR_PATH . 'lib/classes/cxpRequest.php');
require_once ( CXP_PLUGIN_DIR_PATH . 'lib/classes/httpUtils.php');

session_start();
if(isset($_GET['uri'])){
    $uri = $_GET['uri'];
    $previewResponse = new SimpleXMLElement (CXPRequest::getArticle($uri, 'pam-article-no-media'));
    $previewResponse->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
    $previewResponse->registerXPathNamespace('pam', 'http://prismstandard.org/namespaces/pam/2.0/');
    $previewResponse->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
    $previewResponse->registerXPathNamespace('prism', 'http://prismstandard.org/namespaces/basic/2.1/');
    $creators = $previewResponse->xpath('//dc:creator');
    $articleTitle = $previewResponse->xpath('//dc:title');

    if (count ($articleTitle) > 0){
        //echo '<h1 class="previewTitle">' . $previewResponse->xpath('//dc:title')[0]->asXML() . '</h1>';
        echo '<h1 class="previewTitle">' . $articleTitle[0]->asXML() . '</h1>';
    }

        foreach ($creators as $creatorValue) {
            $role = $creatorValue->xpath('@prism:role');
            if ((string)($creatorValue) !=NULL){
                echo '<h4> Creator: ' . $creatorValue . '</h4>';
            }
            if ($role && ((string)$creatorValue->xpath('@prism:role')[0]) !=NULL){
                $creatorRole = ((string)$creatorValue->xpath('@prism:role')[0]);
                echo '<h4>Role: ' . $creatorRole . '</h4>';
            }
        }
    $articleBody = $previewResponse->xpath('//xhtml:body');
    if (count ($articleBody) > 0) {
        echo $articleBody[0]->asXML();
    }
}
/**
 * !DO NOT CLOSE THIS SESSION!
 *
 * 10/19/2021 (07:18) - You are probably here because the WordPress site health screen is
 * complaining of a unclosed session. DO NOT CLOSE the session by uncommenting
 * `session_write_close()` below. If you do, the plugin will no longer function.
 *
 * You may be tempted to close the above `session_start()` by uncommenting
 * `session_write_close()` below. Don't do it unless you fix ContentXpress's implementation of
 * their code. Closing the session below results in the plugin not being able to import.
 *
 * You are able to select the articles for import on the intial screen. Then on the subsequent
 * screen you get the following message:
 *
 * [ContentXpress] IMPORTING 0 ARTICLE(S):
 *
 * [ContentXpress] 0 IMAGES were queued for background processing.
 * ------------- FINISHED! REDIRECTING... --------------
 *
 */
//session_write_close();
