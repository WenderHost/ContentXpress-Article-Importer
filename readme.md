# ContentXpress Article Importer

This is a [WordPress](https://wordpress.org) plugin for importing articles from [ContentXPress](http://www.pubpress.com/services/pubpress-solutions/contentxpress). It is a fork of the original *ContentXpress Article Importer* developed by the [Publishers Printing Company](http://www.pubpress.com/).

### New features:

- Uses `<dc:creator prism:role="author">` data as the post author
- Imports `<RTF:Terms>` as post tags
- Imports `<prism:coverDisplayDate>` as a custom taxnomy
- Assigns proper date to the post by parsing the date from the `<dc:identifier>` field
- Imports `<prism:section>` as a category
- Adds article images to the top of the content as a `[gallery]`

### Changelog

#### 2.1.2

- Importing `<p prism:class="deck">...</p>` as a custom field called `sub_heading`

#### 2.1.1

- Adjusted `WPActions::uploadMedia` to generate unique filenames for each image attachment

#### 2.1.0

- Initial release with *New Features* listed above



