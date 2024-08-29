# ![Mascot](https://user-images.githubusercontent.com/5439169/65790675-0242b600-e115-11e9-817f-e31c41bf2ece.png) OpenSeadragon
[![Build Status](https://github.com/islandora/openseadragon/actions/workflows/build-2.x.yml/badge.svg)](https://github.com/Islandora/openseadragon/actions)
[![Contribution Guidelines](http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg)](./CONTRIBUTING.md)
[![LICENSE](https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square)](./LICENSE)

## Introduction

Drupal FieldFormatter to display an image or generic file using a IIIF Image server and OpenSeadragon.

## Requirements

* [drupal/token](https://www.drupal.org/project/token)

## Installation

This module can be installed via composer and enabled via Drush, like:
1. `composer require drupal/openseadragon:^2.0`
1. `drush pm-en openseadragon`

Downloading/deploying the openseadragon library itself is not neccessary, as it is referenced externally via a CDN.

If you are using the [islandora-playbook](https://github.com/Islandora-Devops/islandora-playbook), there is an [Ansible role](https://github.com/Islandora-Devops/islandora-playbook/tree/dev/roles/internal/Islandora-Devops.drupal-openseadragon) already built-in for installing OpenSeadragon.

## Configuration

The module, once enabled will create a configuration page under **Configuration** > **Media** > **Openseadragon Settings**.
There will be some default settings checked for you. The one setting which you will need to set as a required minimum is the IIIF Image Server Location, such as `http://127.0.0.1:8080/cantaloupe/iiif/2`
There are a myriad of other configuration settings available there. Additional OpenSeadragon documentation is available [here](https://openseadragon.github.io/#examples-and-features).
# ![Config](https://user-images.githubusercontent.com/5439169/65790661-fd7e0200-e114-11e9-8d71-86b5f949d870.png)


## Documentation

Further documentation for this module is available on the [Islandora 2 documentation site](https://islandora.github.io/documentation/).

## Troubleshooting/Issues

Having problems or solved a problem? Check out the Islandora google groups for a solution.

* [Islandora Group](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Islandora Dev Group](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

## Maintainers

Current maintainers:

* [Eli Zoller](https://github.com/elizoller)


## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/documentation/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

We recommend using the [islandora-playbook](https://github.com/Islandora-Devops/islandora-playbook) or [isle-dc](https://github.com/Islandora-Devops/isle-dc) to get started.

## License

[GPLv2](./LICENSE)
