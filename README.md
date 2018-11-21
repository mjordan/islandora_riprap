# Islandora Riprap

## Introduction

Beginnings of a Drupal 8 module to provide reports using data from the [Riprap](https://github.com/mjordan/riprap) fixity microservice.

## Overview

Currently doesn't do anything useful other than add a "Riprap" menu item to each node of `islandora_object` content type, and display a very brief report indicating how many successful and failed events are in some sample data.

## Requirements

* [Islandora](https://github.com/Islandora-CLAW/islandora) a.k.a. CLAW

## Installation

1. Clone this repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_riprap`.

## Current maintainer:

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
