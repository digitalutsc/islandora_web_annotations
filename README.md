# Islandora Web Annotations 
[![Build Status](https://travis-ci.org/digitalutsc/islandora_web_annotations.svg?branch=7.x )](https://travis-ci.org/digitalutsc/islandora_web_annotations) [![DOI](https://zenodo.org/badge/72134170.svg)](https://zenodo.org/badge/latestdoi/72134170)

An Islandora module that enables annotation on Islandora objects, following the [W3C Web annotation model](https://github.com/w3c/web-annotation).   

# Status
This module is under active development. Please see the latest [release](https://github.com/digitalutsc/islandora_web_annotations/releases).   Currently, only Firefox and Chrome browsers are supported.

## Requirements

This module requires the following modules/libraries:

* [Context](https://www.drupal.org/project/context)
* [Context Add Assets](https://www.drupal.org/project/context_addassets)
* [Islandora Context](https://github.com/mjordan/islandora_context)

## Installation

Install as usual, see [this](https://drupal.org/documentation/install/modules-themes/modules-7) for further information.

## Configuration
After enabling the module, set the namespace for the annotation objects by going here: `/admin/islandora/tools/web_annotations`. 

This module requires specific configurations for different content models and solution packs.  Please see the [project wiki documentation](https://github.com/digitalutsc/islandora_web_annotations/wiki) for guides on how to configure for specific content models and solution packs as well as how to index annotation content.

## What is Different About This module?

This module can be considered an Islandora utility module; However, like solution packs, it installs content models into the Fedora repository to facilite the creation of annotation objects. The two content models that are installed are the Annotation content model and Annotation Container Content model.  These content models are not relevant for collection policies at this time.  By default, all content models become available in certain administration interfaces such as the collection management interface.  Selecting these content models through these interfaces has no discernable effect, though we recommend avoiding selecting these content models.

## Maintainers/Sponsors
Software leads:
* [Nat Kanthan](https://github.com/Natkeeran)
* [Marcus Barnes](https://github.com/MarcusBarnes)

Sponsors:
* The [Digital Scholarship Unit (DSU)](https://www.utsc.utoronto.ca/digitalscholarship/) at the University of Toronto Scarborough Library

## License

[GNU General Public License, version 3](http://www.gnu.org/licenses/gpl-3.0.txt) or later.
