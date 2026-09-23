# OpenSeadragon Bookmark URL

Enables the OpenSeadragon Bookmark URL plugin that tracks OpenSeadragon zoom level and coordinates to allow sharing of a display via URL.

This module assumes you have added a string field to a media type that can hold the bookmark fragment, e.g.
`file` with `field_bookmark_url`.

## Requirements
- [OpenSeadragon](https://www.drupal.org/project/openseadragon)

## Installation

Install as you would normally install a contributed Drupal module. For further information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-modules).

## Configuration
Currently, no UI exists for configuration.

Create an openseadragon_bookmark_url.settings.yml file and set `bookmark_url_bundle` to the media type, e.g. `file`, and `bookmark_url_field` to the field. The value from the specific media and field are inject into the Drupal settings available to javascript.
* `bookmark_url_bundle`: `file`
* `bookmark_url_field`: `field_bookmark_url`
