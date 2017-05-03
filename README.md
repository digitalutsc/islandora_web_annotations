# Islandora Web Annotations [![Build Status](https://travis-ci.org/digitalutsc/islandora_web_annotations.svg?branch=7.x )](https://travis-ci.org/digitalutsc/islandora_web_annotations)

An experimental Islandora module with the aim of adding annotation support to Islandora using the [W3C Web annotation model](https://github.com/w3c/web-annotation).   

# Status
Currently under initial stages of development and not for use on production sites.

## Requirements

This module requires the following modules/libraries:

* [Context](https://www.drupal.org/project/context)
* [Context Add Assets](https://www.drupal.org/project/context_addassets)
* [Islandora Context](https://github.com/mjordan/islandora_context)

## Installation

Install as usual, see [this](https://drupal.org/documentation/install/modules-themes/modules-7) for further information.

## Configuration

This module requires specific configurations for different content models and solution packs.  Please see the [project wiki documentation](https://github.com/digitalutsc/islandora_web_annotations/wiki) for guides on how to configure for specific content models and solution packs.

## What is Different About This module?

This module can be considered an Islandora utility module, however, like solution packs, it installs content models into Fedora commons to facilite the creation of annotation objects. The two content models that are installed are the Annotation content model and Annotation Container Content model.  These content models do not apply to the collection level at this time.  By default, all content models become available in certain administration interfaces such as the collection management interface.  These content models should not be selected through these interfaces at this time.

## Maintainers/Sponsors
Current maintainers:
* [Nat Kanthan](https://github.com/Natkeeran)
* [Marcus Barnes](https://github.com/MarcusBarnes)

Sponsors:
* The [Digital Scholarship Unit (DSU)](https://www.utsc.utoronto.ca/digitalscholarship/) at the University of Toronto Scarborough Library

## License

[GNU General Public License, version 3](http://www.gnu.org/licenses/gpl-3.0.txt) or later.
