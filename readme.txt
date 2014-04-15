=== WordPress Development Kit Plugin ===
Plugin Name:  WordPress Development Kit Plugin
Contributors: charlestonsw
Donate link: http://www.charlestonsw.com/product/wordpress-development-kit-plugin/
Tags: WordPress, development, plugins
Requires at least: 3.4
Tested up to: 3.9
Stable tag: 0.4.0

A plugin that works with my WP Dev Kit, plugins.json in particular, to render product and plugin metadata on a WordPress page or post.

== Description ==

Part of the free WordPress Development Kit that I have been putting together to help automate the dozen-plus free and premium plugins that I have been creating over the years.
This is one more cog in the system that helps cut down on manual page updates that show things like change logs, latest news, and other details the customer base finds relevant or useful.
You can learn more via my [WordPress Development Kit articles](http://www.charlestonsw.com/tag/wordpress+development+kit/).
This plugin will do more as my WordPress Development Kit and related processes are refined.
Special thanks to [WordCamp Atlanta](http://central.wordcamp.org/) for planting the seeds that grew into this project.
If you've not been to a local WordCamp you should attend one soon.

= Usage =

Set the location of your production plugins.json file via the Settings/WP Dev Kit menu item in the admin panel of your site.
You can learn more about the plugins.json format from the [WP Dev Kit repository on Bitbucket](https://bitbucket.org/lance_cleveland/wp-dev-kit/wiki/Home).
Yup, it is also GPL and a public repo so go ahead and check it out.

Go to a page or post and use the wpdevkit shortcode.

**Actions**
[wpdevkit action="list"] to list out the production plugins metadata in a stylized format.
[wpdevkit action="filelist"] to create a download list of files

**Styles (default: formatted)**
[wpdevkit action='list' style='formatted'] list the details in an HTML formatted layout
[wpdevkit action="list" style="raw"] to list out the production plugins metadata in a print_r array format (ugly mode).

**Types (default: basic)**
[wpdevkit action='list' type='basic'] list basic details = version, updated, directory, wp versions
[wpdevkit action='list' type='detailed'] list all details = version, updated, directory, wp versions, description

**Slug (default: none = list ALL)**
[wpdevkit action="list" slug="wordpress-dev-kit-plugin"] to list out the production metadata in a stylized format for a single product.

**Target (default: production)**
[wpdevkit action="list" slug="wordpress-dev-kit-plugin" target="production"] to list out the production metadata for production builds.
[wpdevkit action="list" slug="wordpress-dev-kit-plugin" target="prerelease"] to list out the production metadata for prerelease builds.

= Example =

You can see the current iteration of [the plugin in action on my site](http://www.charlestonsw.com/support/documentation/store-locator-plus/release-notes/release-notes-4-0/).
The Current Releases section is updated automatically whenever I use my WP Dev Kit to publish a release to the public WordPress Plugin Directory or when premium add-ons are published to my site.
WP Dev Kit uses Grunt tasks and the plugins.json file to automate my production.
This plugin ties into that automated production system to create things like the WordPress shortcodes above to create formatted output on my site with no direct editing required.

= Related Links =

* [Other CSA Plugins](http://profiles.wordpress.org/charlestonsw/)

== Installation ==

= Requirements =

* Wordpress: 3.4+
* PHP: 5.1+

== Frequently Asked Questions ==

= What are the terms of the license? =

Like ALL of my plugins, including my premium add-ons, the license is GPL.
You get the code, feel free to modify it as you wish.
I prefer customers pay me because they like what I do and want to support my efforts to bring useful software to market.
Learn more on the [CSA License Terms](http://www.storelocatorplus.com/products/general-eula/) page.

== Changelog ==

Visit the [CSA Website for details](http://www.charlestonsw.com/).

= 0.4.0 =

* Enhancement: new shortcode attribute to set target, valid values are 'prerelease' and 'production' (default).
* Enhancement: new shortcode to present a download file list with name and version info, hooks to AJAX file reader/download interface.

= 0.3.0 =

* Enhancement: add the ability to change the header HTML tag in formatted listing output.
* Enhancement: Add extended data shortcode that reads description and changelog sections from a readme file.

= 0.2.0 =

* Enhancement: better readme/json file testing.
* Enhancement: link to product page if the donation link is set in the readme.txt file.
* Enhancement: add ability to list details for a single product.

= 0.1.0 =

* Enhancement: if readme.txt exists, spice-up the plugin info display

= 0.0.02 =

* Updated to match the 0.3.0 release of WP Dev Kit where production and prerelease values have their own metadata sections.

= 0.0.01 =

* Initial, if limited, release.