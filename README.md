# Media: Webdam

[![Build Status](https://travis-ci.org/mobomo/media_webdam.svg?branch=8.x-1.x)](https://travis-ci.org/mobomo/media_webdam)
[![Coverage Status](https://coveralls.io/repos/github/mobomo/media_webdam/badge.svg?branch=8.x-1.x)](https://coveralls.io/github/mobomo/media_webdam?branch=8.x-1.x)

This module provides integration with Webdam.

## About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

## About Media entity webdam

This module provides webdam integration for Media entity (i.e. media type provider
plugin).

### Webdam API
This module uses Webdam's REST API to fetch assets and all the metadata.  The Webdam REST API client is provided by [php-webdam-client](https://github.com/cweagans/php-webdam-client)
At a minimum you will need to:

- Create a Media bundle with the type provider "Webdam".
- On that bundle create a field for the Webdam Asset ID (this should be an integer field).
- On that bundle create a field for the Webdam Asset file (this should be a file field).
- Return to the bundle configuration and set "Field with source information" to use the assetID field and set the field map to the file field.


### Storing field values
If you want to store the fields that are retrieved from Webdam you should create appropriate fields on the created media bundle (id) and map this to the fields provided by WebdamAsset.php.

### Asset status and expiration
If you want to use the Webdam asset status and asset expiration functionality you should map the "Status" field to "Publishing status"

This would be an example of that (the field_map section):

```
langcode: en
status: true
dependencies:
  module:
    - crop
    - media_webdam
third_party_settings:
  crop:
    image_field: field_file
id: webdam
label: Webdam
description: 'Webdam media assets to be used with content.'
type: webdam_asset
type_configuration:
  source_field: field_asset_id
field_map:
  description: field_description
  file: field_file
  type_id: field_type_id
  filename: field_filename
  filesize: field_filesize
  width: field_width
  height: field_height
  filetype: field_filetype
  colorspace: field_colorspace
  version: field_version
  datecreated: created
  datemodified: changed
  datecaptured: field_captured
  folderID: field_folder_id
  status: status
```

Project page: http://drupal.org/project/media_webdam

Maintainers:
 - Jason Schulte https://www.drupal.org/user/143978
 - Cameron Eagans https://www.drupal.org/u/cweagans

## Contributing

* Code must have test coverage - unit or functional as required by the specific feature. Use the Drupal testing module to help with writing and running tests.
* Follow Drupal coding standards. grumphp will enforce this for you after you run composer install.
