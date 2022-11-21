=== Client Portal - Private user pages and login ===
Contributors: cozmoslabs, madalin.ungureanu, sareiodata
Donate link: http://www.cozmoslabs.com/
Tags: client portal, private user page, private pages, private content, private client page, user restricted content
Requires at least: 3.1
Tested up to: 6.0.0
Stable tag: 1.1.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress Client Portal Plugin that creates private pages for all users that only an administrator can edit.

== Description ==

The [WordPress Client Portal plugin](https://www.cozmoslabs.com/add-ons/client-portal/) creates private pages for each user. The content for that page is accessible  on the frontend only by the owner of the page
after he has logged in.

The plugin doesn't offer a login or registration form and it gives you the possibility to use a plugin of your choice.

The **[client-portal]** shortcode can be added to any page and when the logged in user will access that page he will be redirected to its private page.

For login and registration of users we recommend the free [Profile Builder](https://wordpress.org/plugins/profile-builder/) plugin.

You can then use the [wppb-login] shortcode in the same page as the [client-portal] shortcode.

== Installation ==

1. Upload and install the zip file via the built in WordPress plugin installer.
2. Activate the WordPress Client Portal plugin from the "Plugins" admin panel using the "Activate" link.


== Screenshots ==
1. Access the Private Page in the Users Listing in the admin area: screenshot-1.jpg
2. A Private Page edited using the Classic Editor: screenshot-2.jpg
3. A Private Page edited with the Gutenberg Editor: screenshot-3.jpg
4. The Settings Page for the Plugin: screenshot-3.jpg

== Changelog ==
= 1.1.8 =
* Fix: delete private page content before reassigning data when a user is deleted from WordPress
* Misc: added a shortcode that can be used to retrieve all or different parts of the back-end content (Above Page, Default Content, Below Page): [cp-private-page-content content_above="show" content_default="show" content_below="show"]

= 1.1.7 =
* Added a Redirect users that are trying to access a Private Page option
* Fixed private page template not loading from child theme

= 1.1.6 =
* Fix: issue with Before/After Page Content textareas not saving the content correctly
* Misc: Added link to view a particular users private page

= 1.1.5 =
* Added a permanent dismiss button to in plugin notifications
* Readme and screenshot changes

= 1.1.4 =
* Fixed a bug where after editing with Gutenberg the page owner could not access the page

= 1.1.3 =
* Fixed an error when page.php was missing in the theme
* Usability improvements

= 1.1.2 =
* Fixed a potential php warning

= 1.1.1 =
* Added possibility to choose the Page Template if it exists in the theme to use for a Client Portal Page

= 1.1.0 =
* Fixed an incompatibility with Gutenberg on the admin side

= 1.0.9 =
* Comments on private pages are now restricted as well

= 1.0.8 =
* Fixed issue with not being able to edit the page on the backend that contained the client-portal shortcode

= 1.0.7 =
* We now flush permalinks when we first activate the plugin so we can access directly the private pages without a 404

= 1.0.6 =
* Added a View All Pages button on the settings page

= 1.0.5 =
* Ready for translation

= 1.0.4 =
* We now have a default content option for pages
* Now private pages are excluded from appearing in frontend search
* Fixed a bug where the private page would reload indefinitely if the user hadn't a page created
* Fixed a bug where you could create duplicate pages for the same user


= 1.0.3 =
* Minor fixes and security improvements

= 1.0.2 =
* Added support for bulk Create Private Pages to Users page bulk actions

= 1.0.1 =
* Added support for comments on private user pages
* Settings page is now stylized

= 1.0.0 =
* Initial Version of the WordPress Client Portal plugin.