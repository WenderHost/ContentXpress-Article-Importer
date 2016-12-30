<?php

function displayArticlesList(){
    echo '<tr><td></td><td class="align-center"><input type="checkbox" name="wp_publish_all" id="wpPublishAll" value="1" /></td><td colspan="2"><label for="wpPublishAll">Publish All</label></td></tr>';
    foreach ($_SESSION['importList'] as $articleCid => $value) {
        echo '<tr>';
        if ($value instanceof ArticleToImport) {
            echo '<td class="align-left">' . $value->get_articleTitle() . '</td>';
        }

        if ($value->get_wp_publish()) {
            echo '<td class="align-center"><input type="checkbox" name="wp_publish[]" id="publishCheckbox_' . $articleCid . '" value="' . $articleCid . '"  class="publishCheckbox" title="Auto Publish" checked></td>';
        }
        else {
            echo '<td class="align-center"><input type="checkbox" name="wp_publish[]" id="publishCheckbox_' . $articleCid . '" value="' . $articleCid . '"  class="publishCheckbox" title="Auto Publish"></td>';
        }
        echo '<td class="align-center"><input type="button" name="trashImportButton_[]" id="trashImportButton_' . $articleCid . '" class="trashImportButton" value="" title="Remove Item" onclick="removeItem(' . $articleCid . ');"/></td>';

            echo '<td>';
                echo '<select name="postTypes" class="postTypes">';
                    foreach (get_post_types('', 'names') as $wp_postType) {
                        if ($value->get_wp_postType() == $wp_postType) {
                            echo '<option value="' . $wp_postType . '" name="contentPost_' . $articleCid . '" class="postType" selected>' . $wp_postType . '</option>';
                        }
                        else {
                            echo '<option value="' . $wp_postType . '" name="contentPost_' . $articleCid . '" class="postType">' . $wp_postType . '</option>';
                        }
                    }
                echo '</select>';
            echo '</td>';
        echo '</tr>';
    }
}

function initImportList() {
    if (!isset($_SESSION['importList'])) {
        $_SESSION['importList'] = array();
    }
}

function setDefaultPostType() {
    if (isset($_POST['defaultPostType'])) {
        $_SESSION['defaultPostType'] = $_POST['defaultPostType'];
        foreach ($_SESSION['importList'] as $articleCid => $value) {
            if ($value instanceof ArticleToImport) {
                $value->set_wp_postType($_POST['defaultPostType']);
                $_SESSION['importList'][$articleCid] = $value;
            }
        }
    }
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        displayArticlesList();
    }
    wp_die();
}

function updateImportList() {
    if  (isset($_POST['articleArray'])) {
        foreach ($_POST['articleArray'] as $articleData) {
            $articleCid = $articleData['articleCid'];
            if ($articleData ['importArticle'] == 'true') {
                $articleTitle = urldecode($articleData['articleTitle']);
                $articleToImport = new ArticleToImport($articleCid, $articleTitle);
                $articleToImport->set_wp_postType($articleData['postType']);
                $_SESSION['importList'][$articleCid] = $articleToImport;
            }

            else{
                unset($_SESSION['importList'][$articleCid]);
            }
        }
    }

    if (isset($_POST['articleCid'])) {
        $articleCid = $_POST['articleCid'];
        $articleToImport = $_SESSION['importList'][$articleCid];
        if ($articleToImport instanceof ArticleToImport) {
            if (isset($_POST['publishArticle'])) {
                $articleToImport->set_wp_publish($_POST['publishArticle'] == 'true');
            }
            if (isset($_POST['postType'])) {
                $articleToImport->set_wp_postType($_POST['postType']);
            }
        }
    }

    if(isset($_POST['action']) && !empty($_POST['action'])) {
        displayArticlesList();
    }
    wp_die();
}
