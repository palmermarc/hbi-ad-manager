=== HBI Ad Manager ===
Contributors: palmermarc
Donate link: http://www.hubbardradio.com/
Tags: ads, dfp, ad manager
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

HBI Ad Manger is a proprietary ad manager written by Hubbard Radio

== Description ==

HBI Ad Manager is a plugin that was written to display DFP ads on a WordPress Website

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `hbi-ad-manager.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= Will this plugin automatically convert my ads from Ad Code Manager? =

No. Early on, this was the plan. However, the deeper that I got into building a conversion, the more modifications I found in each market. Because of that, all conversion has been scrapped.

= Does this allow me to target ads? =

Yes. setTargeting was added in 2.0

== Screenshots ==

None yet. We'll get there..

== Changelog ==

= 2.0.2 =
* Fixed an issue that was causing conditionals to not save properly

= 2.0.1 =
* Removed deprecated functions pertaining to the old auto-conversion feature that was missed in 2.0
* Removed the Tag ID metabox field. Currently, it appears to be completely useless.
* Fixed the Position In Feed label

= 2.0 =
* Completely removed all mentions of conversion
* Added in setTargeting for Twin Cities (Thanks Kayla)
* Finished the Conditionals ad matching
* Fixed many typos in various areas