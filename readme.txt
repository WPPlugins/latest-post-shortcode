=== Latest Post Shortcode ===
Contributors: Iulia Cazan
Tags: posts shortcode, configurable shortcode with UI, content shortcode, customizable output, tiles from posts, grid from posts, latest post, latest posts, select posts, display posts, taxonomy, category, tag, parent, shortcode, latest, custom, selection, post by category, post by taxonomy, post by tag, post by id, post by parent, last post, tile template, short content, short excerpt, limit content, limit excerpt, pagination, posts pagination
Requires at least: not tested
Tested up to: 4.8
Stable tag: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ

== Description ==
The "Latest Post Shortcode" helps you display a list or grid of the posts or pages in a page/sidebar, without having to code or know PHP. You can embed as many shortcodes in a page as you need, each shortcode configured in a different way. The shortcode for displaying the latest posts is [latest-selected-content] and can be generated very easy, the plugin will add a shortcode button for above in the editor area.
The "Latest Post Shortcode" is configurable and allows you to create a dynamic content selection from your posts, pages and custom post types by combining, limiting and filtering what you need. The output parameters are extremely flexible, allowing you to choose the way your selected content will be displayed.

You can write your own "read more" replacement, choose wether to show/hide featured images, you can even sort the items by a number of options, paginate the output (also AJAX pagination).
This plugin should work with any modern theme.

Usage example: [latest-selected-content limit="4" type="post" display="title,content-small" chrlimit="50" image="full" elements="0" css="two-columns" taxonomy="category" term="samples" orderby="dateA"]

Starting with version 7.0, the plugin implements new hooks that allows for defining and managing your own custom output, through your theme or your plugins. The new hooks are:
* `lps_filter_tile_patterns` and `lps_filter_display_posts_list` - allows you to add your custom patterns
* `lps_filter_use_custom_tile_markup` - allows you to define your custom tile markup
* `lps_filter_use_custom_section_markup_start` and `lps_filter_use_custom_section_markup_end` - allows you to control the shortcode markup that is shown before and after the tiles block.
Check more hooks details and code sample at http://iuliacazan.ro/latest-post-shortcode-7/.

= Latest Post Shortcode Slider =
* https://wordpress.org/plugins/latest-post-shortcode-slider-extension/ 

== Installation ==
* Upload `Latest Post Shortcode` to the `/wp-content/plugins/` directory of your application
* Login as Admin
* Activate the plugin through the 'Plugins' menu in WordPress

== Hooks ==
admin_enqueue_scripts, init, plugins_loaded, media_buttons_context, admin_footer, admin_head, wp_head, lps_filter_tile_patterns, lps_filter_display_posts_list, lps_filter_use_custom_tile_markup, lps_filter_use_custom_section_markup_start, lps_filter_use_custom_section_markup_end

== Screenshots ==
1. Options to configure the shortcode content type and filters.
2. Options to configure the shortcode limit of items and pagination.
3. Options to configure the shortcode output.
4. Options to configure the shortcode output with custom code from your theme or your plugins.
5. Options to configure the shortcode extra options.
6. Options to configure the shortcode slider (if the extension is enabled).
7. The shortcode generator button in the editor area.

== Frequently Asked Questions ==
None

== Changelog ==
= 7.2 = 
* Added the option to exclude dynamic content already exposed by the shortcodes embedded above in the current page content.

= 7.1 = 
* Extra options to display author and taxonomies
* Allow to order items by random

= 7.0 = 
* New shortcode config UI
* Introduce hooks for allowing the definition of custom output

= 6.4 = 
* Tested up to 4.8
* Three columns style fix

= 6.3 = 
* Tested up to 4.5.2
* Fix parents list
* Replace thickbox style

= 6.2 = 
* Add support for post status filter
* Add support for exclude tags by slugs. The new argument sample: exclude_tags="slug1,slug2" 

= 6.1 = 
* Add suppress filters false
* Apply filters before displaying the post image

= 6.0 = 
* Add support for the Latest Post Shortcode Slider extension

= 5.4 =
* Add the plugin link
* Separate the content and excerpt filters
* Tested up to 4.3.1

= 5.3 =
* Add the 'open in a new window' option for the links

= 5.2 =
* Implement changes to render full posts content (including the extra shortcodes)

= 5.1 =
* Add the order option (by date, title, menu order)
* Add the ajax pagination option. As the shortcode pagination relies on the wp native pagination, when using more shortcodes with pagination on the same page, the navigation will affect all shortcodes, hence, by activating the ajax pagination, each shortcode pagination will act independent

= 5.0 =
* Add the extra display of post tags of the posts
* Add filter to allow the text widget to render the content of a shortcode

= 4.2 =
* Introduce the post date in the output. The general settings will apply to the date and time format.

= 4.1 =
* Add changes to the javascript to avoid the content check for resize when lightbox resources are not available (compatibility with other plugins)

= 4.0 =
* Add Pagination Position (default to top only) so that the pagination can be displayed below the results, or above and below the results
* Add Dynamic Tag option so that you can show the posts that have one of the current page tags (current page is the page where the shortcode is embedded), without the need to specify a particular tag. This is useful to display something like "similar posts" or "on the same topic", etc.

= 3.1 =
* Populate the "Use Image" dropdown dynamically from the list of image sizes registered in the application
* Add global tile a class to differentiate when the link is applied to the entire tile content or to just the "read more" text

= 3.0 =
* Add No Pagination / Paginate Results option that allows to paginate the posts selection
* Add Records Per Page option
* Add Offset
* Add Hide / Show Pagination Navigation that allows to hide or show the pagination
* Reload Tile Pattern selection when a shortcode is selected before clicking the plugin button (reload shotcode settings in the content selection lightbox) 

= 2.0 =
* Allow for different tile pattern (the html tags order in the tile: post title, image, text and read more message) 
* Add visual tile pattern selector
* Add short excerpt and short content options
* Add chars limit to the excerpt or content for the tile
* Add custom "read more" message option
* Allow for the post link to wrap the entire tile or just the "read more" message if this is set

== Upgrade Notice ==
Chars limit, custom "read more" option and different tile patterns in the new version! You should upgrade, it's free! 
Donation and reviews are welcomed and will help me to continue future development.

== License ==
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 

== Version history ==
7.2 - Exclude dynamic content already exposed in the current page.
7.1 - Extra options to display author and taxonomies, and order by random
7.0 - Introduce hooks for allowing the definition of custom output and the new UI
6.4 - Three columns style fix, tested up to 4.8
6.3 - Fix parents list, tested up to 4.4.2
6.2 - Add status support and exclude by tags support
6.1 - Apply more filters
6.0 - Add support for the Latest Post Shortcode Slider extension
5.4 - Separate the content and excerpt filters
5.3 - Open links in a new window
5.2 - Render full post content
5.1 - Posts order and ajax pagination
5.0 - Extra tags display and text widget filter
4.2 - Post date option
4.1 - Compatibility update
4.0 - Pagination position and dynamic tag
3.1 - Dynamic image dropdown option
3.0 - Pagination options
2.0 - Visual pattern selector and more features
1.0 - Development version.
