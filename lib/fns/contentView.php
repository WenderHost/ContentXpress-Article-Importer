<?php
function content_submenu_page_callback()
{
    global $BackgroundImageProcess;

    if (isset($_POST ['importButton'])) {
        //upload article and images to Wordpress
        write_log( '-----------------------------------' );
        write_log( 'IMPORTING ' . count( $_SESSION['importList'] ) . ' ARTICLE(S)' );
        write_log( '-----------------------------------' );

        foreach ($_SESSION['importList'] as $articleCid => $value) {
            write_log("\n\n");
            $article = CXPRequest::importArticle( $value );

            // If article has images, add a gallery to the top of the post
            if ( isset( $article->field_image ) )
                $article->body = "[gallery link=\"file\" type=\"slideshow\" autostart=\"false\"]\n" . $article->body;

            //create the initial post
            $post_id = WPActions::createPost( $article );

            // Add any article images to our Background Image Processing queue
            if ( isset( $article->field_image ) ) {
                write_log( 'Adding ' . count( $article->field_image ) . ' image(s) to Background Image Processing queue...' );
                for ( $j = 0; $j < sizeof( $article->field_image ); $j++) {
                    $image = [
                        'contentID' => $article->field_image[$j]['fid'],
                        'caption' => $article->field_image[$j]['caption'],
                        'post_id' => $post_id
                    ];
                    write_log( 'Queuing $image = [ contentID => ' . $image['contentID'] . ', post_id => ' . $image['post_id'] . ' ]' );
                    $BackgroundImageProcess->push_to_queue( $image );
                }
                $BackgroundImageProcess->save()->dispatch();
                $BackgroundImageProcess->empty_queue();
            }

            unset($_SESSION['importList'][$articleCid]);
        }

        write_log( '-----------------------------------' . "\n\n" );
    }

    $selectedPub = '';
    if (isset($_POST['publicationsSelect'])) {
        $selectedPub = $_POST['publicationsSelect'];
    }

    $selectedIssue = '';
    if (isset($_POST['issuesSelect'])) {
        $selectedIssue = $_POST['issuesSelect'];
    }

    $selectedSection = '';
    if (isset($_POST['sectionsSelect'])) {
        $selectedSection = $_POST['sectionsSelect'];
    }

    $keywords = '';
    if (isset($_POST['keywordsInput'])) {
        $keywords = $_POST['keywordsInput'];
    }

    //*** Set $newStart and $newPageLength ***//
    $newStart = 1;
    $newPageLength = 10;
    $newSort = "Relevancy";
    $newPlatform = "";
    $newPublished = "";

    if (isset($_POST['newStart'])) {
        $newStart = $_POST['newStart'];
    }

    if (isset($_POST['newPageLengthSelect'])) {
        $newPageLength = $_POST['newPageLengthSelect'];
    }

    if (isset($_POST['previous'])) {
        $newStart = $newStart - $newPageLength;
    }

    if (isset($_POST['next'])) {
        $newStart = $newStart + $newPageLength;
    }

    if (isset($_POST['sortBySelect'])) {
        $newSort = $_POST['sortBySelect'];
    }

    if (isset($_POST['platformSelect'])) {
        $newPlatform = $_POST['platformSelect'];
    }

    if (isset($_POST['publishedSelect'])) {
        $newPublished = $_POST['publishedSelect'];
    }

    if (isset($_POST['logoutButton'])) {
        Redirects::contentXpressSessionEnd();
    }
    echo '<div class="contentxpress_page_content wrap">';
        echo '<div class="float-left">';
            echo '<h1>ContentXpress</h1>';
        echo '</div>';

        initImportList();
        echo '<form id="pubsForm" name="pubsForm" method="post" autocomplete="off">';

            echo '<div class="clear"></div>';

            echo '<div class="container float-left">';
                echo '<div class="header"><span class="headerText">Filter Results</span></div>';
                echo '<div class="mainbody">';
                //*** Create Publication dropdown list ***//
                    echo '<div id="pubDropdown">';
                        echo '<span style="width: 130px; margin-top: 5px; float:left;"><strong>Select a Publication: </strong></span>';
                        $pubs = new SimpleXMLElement(CXPRequest::getPublicationFacets(''));
                        $optionsPub = array();
                        $optionsPub[] = "<option value=''>--?--</option>";
                        echo '<select name="publicationsSelect" onchange="this.form.submit()">';
                            echo '<option value=""></option>';
                            foreach ($pubs->xpath('//search:facet-value') as $pub) {
                                $selected = "";
                                if ($selectedPub == $pub["name"]) {
                                    $selected = 'selected="selected"';
                                }
                            echo $optionsPub[] = '<option value="' . $pub["name"] . '"' . $selected . '>' . $pub . '</option>';
                            }
                        echo '</select>';
                    echo '</div>';
                echo '<br />';

                //*** Create Issue dropdown list ***//
                echo '<div id="issueDropdown">';
                //if ($selectedPub) {
                    echo '<span style="width: 130px; margin-top: 5px; float:left;"><strong>Select an Issue: </strong></span>';
                    $issues = new SimpleXMLElement(CXPRequest::getIssueFacets('publication:"' . $selectedPub . '"'));
                    $optionsIssue = array();
                    echo '<select name="issuesSelect" onchange="this.form.submit()" class="select-default">';
                        echo '<option value=""></option>';
                        foreach ($issues->xpath('//search:facet[@name="issue"]/search:facet-value') as $issue) {
                            $selected = "";
                            if ($selectedIssue == $issue["name"]) {
                                $selected = 'selected="selected"';
                            }
                            echo $optionsIssue[] = '<option value="' . $issue["name"] . '"' . $selected . '>' . $issue . '</option>';
                        }
                    echo '</select>';
                //}
            echo '</div>';
            echo '<br />';

            //*** Create Section  dropdown list ***//
            echo '<div id="sectionDropdown">';
            //if ($selectedPub) {
                echo '<span style="width: 130px; margin-top: 5px; float:left;"><strong>Select a Section: </strong></span>';
                $sections = new SimpleXMLElement(CXPRequest::getSectionFacets('publication:"' . $selectedPub . '"'));
                $optionsSec = array();
                $optionsSec[] = "<option value=''>--?--</option>";
                echo '<select name="sectionsSelect" onchange="this.form.submit()" class="select-default">';
                    echo '<option value=""></option>';
                    foreach ($sections->xpath('//search:facet[@name="section"]/search:facet-value') as $section) {
                        $selected = "";
                        if ($selectedSection == $section["name"]) {
                            $selected = 'selected="selected"';
                        }
                        echo $optionsSec[] = '<option value="' . $section["name"] . '"' . $selected . '>' . $section . '</option>';
                    }
                echo '</select>';
            //}
            echo '</div>';
        echo '</div>';
        echo '<div class="footer"></div>';
    echo '</div>';

    echo '<div class="container float-left">';
    echo '<div class="header"><span class="headerText">Refine Results</span></div>';
    echo '<div class="mainbody">';
    //*** Search ***//
    echo '<strong>Search Keyword: </strong>';
    echo '<input id="keywordSearch" type="text" name="keywordsInput" value="' . $keywords . '" class="searchInput" style="margin-right: 5px;">';
    echo '<input type="submit" id="searchButton" class="button" name="keywordSearch" value="Search"/>';
    echo '<hr />';
    echo '<div class="float-left">';
    //*** Create Articles per Page dropdown ***//
    echo '<div style="margin-bottom: 5px;">';
    $pageLengthOptions = array("10", "25", "50");
    echo '<strong>Articles per Page: </strong>';
    echo '<select name="newPageLengthSelect" onchange="this.form.submit()">';
    foreach ($pageLengthOptions as $pageLengthOption) {
        $selected = "";
        if ($newPageLength == $pageLengthOption) {
            $selected = 'selected="selected"';
        }
        echo $pageLengthOptions[] = '<option value="' . $pageLengthOption . '"' . $selected . '>' . $pageLengthOption . '</option>';
    }
    echo '</select>';
    echo '</div>';

    //*** Create Platform dropdown ***//
    echo '<div style="margin-bottom: 5px;">';
    $platformOptions = array("All" => "", "Broadcast" => "broadcast", "Email" => "email", "Mobile" => "mobile", "Other" => "other", "Print" => "print", "Recordable Media" => "recordableMedia", "Web" => "web");
    echo '<strong>Platform: </strong>';
    echo '<select name="platformSelect" onchange="this.form.submit()">';
    foreach ($platformOptions as $platformOption => $platformValue) {
        $selected = "";
        if ($newPlatform == $platformValue) {
            $selected = 'selected="selected"';
        }
        echo $platformOptions[] = '<option value="' . $platformValue . '"' . $selected . '>' . $platformOption . '</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '</div>';
    echo '<div class="float-left">';
    //*** Create Sort By dropdown ***//
    echo '<div style="margin-bottom: 5px;">';
    $sortOptions = array("Relevancy" => "relevance", "Date Ascending" => "date-descending", "Date Descending" => "date-ascending");
    echo '<strong>Sort By: </strong>';
    echo '<select name="sortBySelect" onchange="this.form.submit()">';
    foreach ($sortOptions as $sortOption => $sortValue) {
        $selected = "";
        if ($newSort == $sortValue) {
            $selected = 'selected="selected"';
        }
        echo $sortOptions[] = '<option value="' . $sortValue . '"' . $selected . '>' . $sortOption . '</option>';
    }
    echo '</select>';
    echo '</div>';

    //*** Create Published dropdown ***//
    echo '<div style="margin-bottom: 5px;">';
    $publishedOptions = array("All" => "", "Published" => "true", "Unpublished" => "false");
    echo '<strong>Published Status: </strong>';
    echo '<select name="publishedSelect" onchange="this.form.submit()">';
    foreach ($publishedOptions as $publishedOption => $publishedValue) {
        $selected = "";
        if ($newPublished == $publishedValue) {
            $selected = 'selected="selected"';
        }
        echo $publishedOptions[] = '<option value="' . $publishedValue . '"' . $selected . '>' . $publishedOption . '</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '</div>';
    echo '<div class="clear"></div>';
    echo '</div>';
    echo '<div class="footer"></div>';
    echo '</div>';

    //*** Decipher Response from API ***//
    //echo CXPRequest::getArticleResults($selectedPub, $selectedIssue, $selectedSection, $keywords, $newStart, $newPageLength, $newSort, $newPlatform, $newPublished);
    $searchResponse = new SimpleXMLElement(CXPRequest::getArticleResults($selectedPub, $selectedIssue, $selectedSection, $keywords, $newStart, $newPageLength, $newSort, $newPlatform, $newPublished));

    $xsl = new DOMDocument;
    $xsl->load( CXP_PLUGIN_DIR_PATH . 'lib/xsl/highlight.xsl' );
    $xslt = new XSLTProcessor;
    $xslt->importStyleSheet($xsl);
    $searchResponse = new SimpleXMLElement($xslt->transformToXml($searchResponse));
    $searchResponse->registerXPathNamespace('s', 'http://marklogic.com/appservices/search');
    $searchFacet = $searchResponse->xpath('//s:result');
    $searchTotal = $searchResponse->xpath('//s:response/@total');
    $searchStart = $searchResponse->xpath('//s:response/@start');
    $searchPageLength = $searchResponse->xpath('//s:response/@page-length');
    $newEnd = $searchTotal[0] < 10 ? $searchTotal[0] : $newStart + 9;
    $contentList = array();
    $articleCid = '';
    echo '<input type="hidden" name="newStart" value="' . $searchStart[0] . '"/>';
    echo '<div class="container float-left">';
    echo '<div class="header"><span class="headerText">Import List</span>
                <input type="submit" id="importButton" class="button float-right" name="importButton" value="Import" style="margin-right: 10px;"/>
          </div>';
    //echo("{$_SESSION['defaultPostType']}");
    echo '<div class="mainbody" style="margin-top: 5px;">';
    echo '<div id="importListContainer" class="importListContainer">';
    echo '<table id="importTable" class="importTable"><colgroup><col style="width: 50%"/><col style="width: 10%" /><col style="width: 10%" /><col style="width: 30%" /></colgroup>';
    echo '<thead>';
    echo '<tr id="postTypesRow">';
    echo '<td colspan="3">Set Post Type for ALL in List:</td>';
    echo '<td colspan="1">';
    echo '<select name="postTypesAll" class="postTypesAll">';
    foreach ( get_post_types( '', 'names' ) as $wp_postType ) {
        if (isset($_SESSION['defaultPostType']) == $wp_postType) {
            echo '<option value="' . $wp_postType . '" class="postTypeAllOption" selected>' . $wp_postType . '</option>';
            //echo '<option value="' . $wp_postType . '" name="contentPost_' . $articleCid . '" class="postType" selected>' . $wp_postType . '</option>';
        }
        else {
            echo '<option value="' . $wp_postType . '" class="postTypeAllOption">' . $wp_postType . '</option>';
            //echo '<option value="' . $wp_postType . '" name="contentPost_' . $articleCid . '" class="postType">' . $wp_postType . '</option>';
        }
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="col">Title</th>';
    echo '<th scope="col" title="Automatically Publish in Wordpress" >Publish</th>';
    echo '<th scope="col" title="Remove Item From Import List">Remove</th>';
    echo '<th scope="col" title="Select Post Type">Post Type</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    displayArticlesList();
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="clear"></div>';

    echo '<div class="articlesWrapper">';
    echo '<div style="height: 50px;">';
    echo '<div class="selectAllWrapper float-left">';
    //*** Select All for Import Checkbox ***//
    echo '<input name="importSelectAllCheckbox" type="checkbox" value="" class="selectall" style="margin-left: 10px;"/><span>Select All for Import </span>';
    echo '</div>';
    echo '<div class="next-previousWrapper float-left">';
    //*** Next and Previous Buttons ***//
    if ($newStart == 1) {
        echo '<input type="submit" class="cxpButton inactive_cpxButton float-left" name="previous" value="Previous" disabled="disabled" />';
    } else {
        echo '<input type="submit" class="cxpButton float-left" name="previous" value="Previous" />';
    }

    echo '<p class="float-left" style="padding: 0 10px;">Displaying ' . $searchStart[0] . ' - ' . $newEnd . ' of ' . $searchTotal[0] . ' articles</p>';

    if ($newStart + $searchPageLength[0] >= $searchTotal[0]) {
        echo '<input type="submit" class="cxpButton inactive_cpxButton float-left" name="next" value="Next" disabled="disabled" />';
    } else {
        echo '<input type="submit" class="cxpButton float-left" name="next" value="Next" />';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="clear"></div>';

    //*** Create list of articles ***//
    foreach ($searchFacet as $searchFacetValue) {
        $searchFacetValue->registerXPathNamespace('s', 'http://marklogic.com/appservices/search');
        $articleCid = (string)$searchFacetValue->xpath('s:snippet')[0]->extra->contentId;
        $uri = (string)$searchFacetValue->xpath('@uri')[0];
        $articleTitle = (string)$searchFacetValue->xpath('s:snippet')[0]->extra->title;

        echo '<div class="snippetWrapper">';
        echo '<div class="snippet-left-side">';
        if ($searchFacetValue->xpath('s:snippet')[0]->extra->lockedBy == "") {
            if (array_key_exists($articleCid, $_SESSION['importList'])) {
                echo '<input id="importcheckbox_' . $articleCid . '" name="importcheckbox[]" type="checkbox" class="importCheckbox" value="' . $articleCid . '" checked/><span>Select for Import</span>';
            } else {
                echo '<input id="importcheckbox_' . $articleCid . '" name="importcheckbox[]" type="checkbox" class="importCheckbox" value="' . $articleCid . '"/><span>Select for Import</span>';
            }
            echo '<input type="hidden" class="articleTitle" id="articleTitle_' . $articleCid . '" value="' . urlencode($articleTitle) . '" />';
        } else {
            echo '<input id="importcheckbox_' . $articleCid . '" name="importcheckbox[' . $articleCid . ']" type="checkbox" value="" disabled style="cursor: not-allowed">
                        <span> Locked By: </span><span style="color:tomato">' . $searchFacetValue->xpath('s:snippet')[0]->extra->lockedBy . '</span>';
        }

        echo '<h2>' . $searchFacetValue->xpath('s:snippet')[0]->extra->title . '</h2>';

        if (!$searchFacetValue->xpath('s:snippet')[0]->extra->publication == "$selectedPub") {
            echo '<p>Publication:<strong> ' . $searchFacetValue->xpath('s:snippet')[0]->extra->publication . '</strong></p>';
        }

        if (!$searchFacetValue->xpath('s:snippet')[0]->extra->issue == "$selectedIssue") {
            echo '<p>Issue:<strong> ' . $searchFacetValue->xpath('s:snippet')[0]->extra->issue . '</strong></p>';
        }

        if (!$searchFacetValue->xpath('s:snippet')[0]->extra->section == "$selectedSection") {
            echo '<p>Section:<strong> ' . $searchFacetValue->xpath('s:snippet')[0]->extra->section . '</strong></p>';
        }

        if (!$searchFacetValue->xpath('s:snippet')[0]->extra->originPlatform == "$newPlatform") {
            echo '<p>Platform: <strong>' . $searchFacetValue->xpath('s:snippet')[0]->extra->originPlatform . '</strong></p>';
        }

        echo '<p>' . $searchFacetValue->xpath('s:snippet/s:match')[0]->asXML() . '</p>';

        $mediaDataResponse = new SimpleXMLElement(CXPRequest::getMediaData($articleCid));
        $mediaDataResponse->registerXPathNamespace('am', 'http://pubpress.com/asset/model');
        $mediaDataResponse->registerXPathNamespace('s', 'http://marklogic.com/appservices/search');
        $mediaResult = $mediaDataResponse->xpath('//s:result');
        $mediaRecord = $mediaDataResponse->xpath('//am:media-record');
        $mediaList = array();
        foreach ($mediaResult as $mediaResultValue) {
            $mediaResultValue->registerXPathNamespace('am', 'http://pubpress.com/asset/model');
            $mediaRecordCid = (string)$mediaResultValue->xpath('.//am:media-record/@identifier')[0];
            //echo $mediaRecordCid;
            array_push($mediaList, $mediaRecordCid);
        }
        $contentList[$articleCid] = $mediaList;
        echo '</div>';
        echo '<div class="snippet-right-side">';
        echo '<p><a href="javascript:lightbox(\'' . $uri . '\')" name="preview_' . $articleCid . '" class="linkButton">Preview</a></p>';
        echo '</div>';
        echo '<div id="clearDiv" class="clear"></div>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '<noscript><input type="submit" value="Submit"></noscript>';

    echo '</form>';
}