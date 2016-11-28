//*** Start Lightbox for Article Preview ***//
function lightbox(uri) {
    if (jQuery(".lightbox").size() == 0) {
        var theLightbox = jQuery("<div class='lightbox'/>");
        var theShadow = jQuery("<div class='lightbox-shadow'/>");
        var theWrapper = jQuery("<div class='lightbox-wrapper resizable'/>");
        jQuery(theShadow).click(function (e) {
            closeLightbox();
        });
        var theButton = jQuery("<div class='lightbox-button'/>");
        jQuery(theButton).click(function (e) {
            closeLightbox();
        });
        jQuery("body").append(theShadow);
        jQuery("body").append(theWrapper);
        jQuery(".lightbox-wrapper").append(theButton);
        jQuery(".lightbox-wrapper").append(theLightbox);
    }
    jQuery("#lightbox").empty();
    jQuery(document.body).addClass('noScroll');
    jQuery(".lightbox").append("<p class='loading'>Loading...</p>");
    jQuery.ajax({
        type: "GET",
        headers: {"Basic": "admin/authenticate"},
        url: ajaxurl,
        data: {"uri": uri},
        success: function (data) {
            jQuery(".lightbox").empty();
            jQuery(".lightbox").append(data);
        },
        error: function () {
            alert("AJAX Failure!");
        }
    });
    jQuery(".lightbox-wrapper").css("top", jQuery(window).scrollTop() + 100 + "px");
    jQuery(".lightbox").css("top", jQuery(window).scrollTop() + 20 + "px");
    jQuery(".lightbox").show();
    jQuery(".lightbox-button").show();
    jQuery(".lightbox-wrapper").show();
    jQuery(".lightbox-shadow").show();
}
function closeLightbox() {
    jQuery(".lightbox").hide();
    jQuery(".lightbox-button").hide();
    jQuery(".lightbox-wrapper").hide();
    jQuery(".lightbox-shadow").hide();
    jQuery(document.body).removeClass('noScroll');
    jQuery(".lightbox").empty();
}


//*** End Lightbox for Preview ***//

//*** Start Select Article Checkboxes ***//

jQuery('.selectall').on('click', function () {
    var articleArray = [];
    if (this.checked) {
        jQuery('.importCheckbox').each(function () {
            this.checked = true;
            articleArray.push(createArticleData(jQuery(this).val(), true));
        });
    }
    else {
        jQuery('.importCheckbox').each(function () {
            this.checked = false;
            articleArray.push(createArticleData(jQuery(this).val(), false));
        });
    }
    storeArticles(articleArray);
});

jQuery('.importCheckbox').change(function () {
    if (jQuery('.importCheckbox:checked').length == jQuery('.importCheckbox').length) {
        jQuery('.selectall').prop('checked', true);
    } else {
        jQuery('.selectall').prop('checked', false);
    }
});

jQuery(function () {
    jQuery(':checkbox.importCheckbox').change(function () {
        storeArticles([createArticleData(jQuery(this).val(), jQuery(this).prop('checked'))]);
    });
});

jQuery(function () {
    jQuery('#importTable').on('change', ':checkbox.publishCheckbox', function () {
        publishStoredArticles(jQuery(this).val(), jQuery(this).prop('checked'));
    });
});

jQuery(function () {
    jQuery('#importTable').on('change', '.postTypes', function () {
        var optionSelected  = jQuery("option:selected", this);
        var articleCid = jQuery(optionSelected).attr('name').split('_')[1];
        var postType = this.value;
        postTypeStoredArticles(articleCid, postType);
    });
});

jQuery(function () {
    jQuery('#importTable').on('change', '.postTypesAll', function () {
        var postType = this.value;
        setDefaultPostType(postType);
        /*jQuery('.postTypes').each(function () {
            var optionSelected  = jQuery("option:selected", this);
            var articleCid = jQuery(optionSelected).attr('name').split('_')[1];
            jQuery('.postType').val(postType).attr('selected', 'selected');
            postTypeStoredArticles(articleCid, postType);
        });*/

    });
});

function removeItem(articleCid) {
    storeArticles([createArticleData(articleCid, false)]), jQuery('#importcheckbox_' + articleCid).prop('checked', false);
}

function createArticleData(articleCid, checked) {
    var articleTitle = jQuery('#articleTitle_' + articleCid).val();
    var postType = jQuery('.postTypesAll').val();
    var articleData = {"articleCid": articleCid, "importArticle": checked, "articleTitle": articleTitle, "postType" : postType};
    return articleData;
}

function storeArticles(articleArray) {
    jQuery.post(ajaxurl, {"articleArray": articleArray, action: "contentXpressImporter_contentStoreList"}, function (data) {
        jQuery("#importTable > tbody").html(data);
    })
}

function publishStoredArticles(articleCid, checked) {
    jQuery.post(ajaxurl, {"articleCid": articleCid, "publishArticle": checked, action: "contentXpressImporter_contentStoreList" },
        function (data) {
    });
}

function postTypeStoredArticles(articleCid, postType) {
    jQuery.post(ajaxurl, {"articleCid": articleCid, "postType": postType, action: "contentXpressImporter_contentStoreList" },
        function (data) {
        });
}

function setDefaultPostType(postType) {
    jQuery.post(ajaxurl, {"defaultPostType" : postType, action: "contentXpressImporter_defaultPostType" },
        function (data) {
            jQuery("#importTable > tbody").html(data);
        });
}

jQuery(function() {
    jQuery('#keywordSearch').keypress(function (e) {
        var key = e.which;
        if(key == 13)  // the enter key code
        {
            jQuery('input[name = keywordSearch]').click();
            return false;
        }
    });
});

/*jQuery (function() {
   var rowCount = jQuery("#importTable tbody tr").length;
    if(rowCount == 0) {
        jQuery('#postTypesRow').hide();
    }
    else {
        jQuery('#postTypesRow').show();
    }
});*/

window.onbeforeunload = processing;
function processing(){
    document.body.style.cursor = 'wait';
}