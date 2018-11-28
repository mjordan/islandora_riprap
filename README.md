# Islandora Riprap

## Introduction

A Drupal 8 module to provide node-level reports using data from the [Riprap](https://github.com/mjordan/riprap) fixity microservice.

## Overview

Currently under development. So far, adds a "Fixity auditing" field to the "Media" tab (which is actually the output of the "Manage Media" view provided by the Islandora module) showing Fedora URLs for Media associated with the node. Each URL is color coded to indicate whether or not any fixit events have failed, with a link to a full report of the events.

## Requirements

* [Islandora](https://github.com/Islandora-CLAW/islandora) a.k.a. CLAW
* A [Riprap](https://github.com/mjordan/riprap) fixity microservice.

## Installation

1. Clone this repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_riprap`.

## Configuration

1. Go to Drupal's "Configuration" menu.
   1. In the "Islandora" section, click on the "Fixity auditing" link.
   1. Adjust your config options.
1. Add the "Fixity Auditing" field to the "Manage Media" View (like you would add any other field to a view):
   1. In your list of Views ("Admin > Structure > Views"), click on the "Edit" button for the "Manage Media" View.
   1. In the "Page" display, click on the "Add" Fields button.
   1. From the list of fields, check "Fixity Auditing".
   1. Click on "Apply (this display)".
   1. Change the label if you want.
   1. Click on "Apply (this display)".
   1. Optionally, you can locate the new "Fixity Auditing" field to any position you want in the Media table.
   1. Click on the "Save" button to save the change to the View.

Now, when you click on the "Media" tab in an Islandora object node, you will see a new column in the table showing the Fedora URL for the media file:

![details](docs/islandora_riprap_details.png)

The cell is green to indicate that all fixity events for the media file were successful. The "Details" link leads to a full report of the events.

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
