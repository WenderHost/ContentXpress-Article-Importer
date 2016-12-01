# ContentXpress Article Importer

This is a [WordPress](https://wordpress.org) plugin for importing articles from [ContentXPress](http://www.pubpress.com/services/pubpress-solutions/contentxpress). It is a fork of the original *ContentXpress Article Importer* developed by the [Publishers Printing Company](http://www.pubpress.com/).

Implemented features:

- Uses `<dc:creator prism:role="author">` data as the post author
- Imports `<RTF:Terms>` as post tags
- Imports `<prism:coverDisplayDate>` as a custom taxnomy
- Assigns proper date to the post by parsing the date from the `<dc:identifier>` field

To be implemented features:

- Adds article images to the top of the content as a `[gallery]`
- Imports `<prism:section>` as a category
