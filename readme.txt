=== Meks Easy Maps ===

Contributors: mekshq, ristojovanovic
Donate link: https://mekshq.com/
Tags: map, google map, location, pin, destination
Requires at least: 3.7
Tested up to: 6.6
Stable tag: 2.1.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Easily display map locations for your posts and categories with Google Maps.

== Description ==

Meks Easy Maps was originally created as a feature for our [Trawell WordPress theme](https://mekshq.com/theme/trawell/) but now it can be used on any WordPress website.

With Meks Easy Maps WordPress plugin you can easily assign locations to your posts and categories via Google Map user-friendly interface. The plugin is highly configurable and provides you with the various options for displaying the map.

Meks Easy Maps WordPress plugin is created by [Meks](https://mekshq.com)

== Features ==

* Assign Google Map or Open Street Map locations to your posts and categories
* Display post location pin above or below your post content
* Display all posts with locations on the current category template/archive
* Display category location pin on category template/archive
* Shortcode to manually display pins with posts or categories on any page
* Several display settings to fine-tune the map behavior like clustering (pin grouping) and polylines (lines that connects pins)
* Street view support

== Live example? ==
You can see Meks Easy Maps live example on our [Trawell theme demo website](https://mekshq.com/demo/trawell/)

== Quick shortcode guide ==

Besides the automatic display of your posts and categories on the map, you can embed your map anywhere on the website by using the predefined **`[mks_map]`** shortcode.

* Use **`[mks_map type="posts"]`** to display all your posts with locations on a single map.
* Use **`[mks_map type="posts" cat="2"]`** if you want to display posts from a specific category only (cat parameter represents the category ID).
* Use **`[mks_map type="categories"]`** to display all your categories with locations.

== Installation ==

1. Upload meks-easy-maps.zip to plugins via WordPress admin panel or upload unzipped folder to your wp-content/plugins/ folder
2. Activate the plugin through the "Plugins" menu in WordPress
3. In Admin panel, go to Settings -> Meks Easy Maps to manage the options

== Frequently Asked Questions ==

For any questions, error reports and suggestions please visit https://mekshq.com/contact/

== Screenshots ==

1. Settings
2. Adding a post location
3. Adding a category location
4. Map with posts on category archive
5. Map on single post

== Changelog ==

= 2.1.4 =
* WP 6.3 compatibility tested
* Patched a minor security issue

= 2.1.3 =
- Fixed: Open Street Maps pin popup not working on Safari (Mac)

= 2.1.2 =
- Updated: Google map zooming on mouse scroll is now restricted again (due to maps API changes)

= 2.1.1 =
- Fixed: OSM map position option above or below the content

= 2.1 =
- Fixed: issue with Google maps 

= 2.0 =
- Added: support for Open Street Map as an alternative to Google maps

= 1.1.5 =
* Fixed: Another conflict with Jetpack gallery module (map was not working when it was enabled in some cases)

= 1.1.4 =
* Fixed: Conflict with Jetpack gallery module (map was not working when it was enabled)

= 1.1.3 =
* Fixed: PHP notices thrown on specific server configurations

= 1.1.2 =
* Fixed: Rare bug with special characters in the image tags that prevents map to work properly in some cases

= 1.1.1 =
* Fixed: Post content disappearing when location is not set

= 1.1 =
* Added: Map shortocode "mks_map"
* Added: Option to enable/disable clustering (pin grouping)
* Added: Max number of pins to remove polylines (lines that connect the pins on the map)

= 1.0 =
* Initial release