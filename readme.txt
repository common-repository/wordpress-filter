=== WordPress Filter ===
Contributors: mattwalters
Donate link: http://mattwalters.net/projects/
Tags: post, filter, plugin, text, edit, tag, category, categories, template
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 1.4.1

WordPress Filter is a comprehensive post filtering & template system. It allows the user to define a set of simple "Catches" (criteria) to be met by a post, and then have a set of "Actions" applied to the post.

== Description ==

WordPress Filter is a comprehensive post filtering & template system. It allows the user to define a set of simple "Catches" (criteria) to be met by a post, and then have a set of "Actions" applied to the post.  This plugin takes action whenever a Post is published inside of WordPress (including Posts coming in via XMLRPC).  It can be used for many tasks, including creating your own mini plugins without ever having to leave your WordPress administration area.

*v1.4*

Added some catches back in (particularly Post Status which folks had been asking for).  Sorry it took me so long for a new release to be ready.

Added:

* Catch - Post Author: Equals
* Catch - Post Status: Equals

* Action - Post Status: Equals

Changed:

* Catches like "Post Title: Equals" no longer case sensitive

Bug Fixes:

* Fixed image link on add filter screen
* Fixed issue where if looking for content within the Post Content, Excerpt or Title and the content is the first string, it would not realize it was found (for instance if looking for "Hello" and the text being searched was "Hello world", the plugin catch would not engage)

Coming In Future Version:

* Ability to run one-off filters.  Basically you'll be able to define a filter and then run it on all of your current posts.
* Ability to force a filter to run on a post even if that specific filter has already been run on the post.

== Example Usage ==

Let's assume you want to participate in [Project 365](http://photojojo.com/content/tutorials/project-365-take-a-photo-a-day/) but want to make it as easy as possible.  You would like to take a picture with your camera phone and send it to your flickr2blog email address with the title "potd".  To do this, you could use WordPress Filter in this way:

Define the Catch:

* If Post Title: Equals "potd"

Apply the Actions:

* Title: Replace "Picture of the Day"
* Tag(s): Add "potd_%%year%%, photo"
* Content: Append "&lt;br/&gt;&lt;br/&gt;This photo is part of my &lt;a href="/tags/potd/"&gt;Picture of the Day Series for %%year%%&lt;/a&gt;."

When the post arrives at your blog it would:

* Change the post title to "Picture of the Day"
* Add the tags "potd_2008, photo"
* Add a short bit of text to the end of the content

%%year%% is a substitution that will be made by WordPress Filter at the time the post is saved.  It inserts the current four digit year.  There are other System Substitutions (see Other Notes tab for a listing) as well.

Another good use for this plugin would be to auto-tag or auto-categorize your posts.  For instance, you could set it up so that whenever you mentioned "Microsoft" in a post, you could have it automatically categorize the post in "Technology" and tag the post with "technology, microsoft".

== System Substitutions ==

* %%day-num%% -> Current day of the month in numeric format (1, 2, 3, etc)
* %%day-text%% -> Current day of the month in text format (Monday, Tuesday, etc)
* %%year%% -> Current four digit year (2008, 2009, etc)
* %%month-num%% -> Current month in numeric format (1, 2, 3, etc)
* %%month-text%% -> Current month of the year in text format (January, February, etc)

== Installation ==

1. Uncompress the ZIP file downloaded from this site.
1. Upload the files contained inside it to a folder named "wordpress-filter" inside of your wp-content/plugins directory.
1. Visit Plugins -> Installed within your WordPress administration area
1. Activate WordPress Filter
1. Visit Settings -> WordPress Filter within your WordPress administration area
1. Choose the second tab, Add Filter
1. Set up your Catches and Actions
1. Use the + Add Filter, and + Add Action links to produce more complex Catches and Actions

== Screenshots ==

7. Current Filters screen

8. Add Filter screen

9. Edit Filter screen

10. Import Filter Screen

== Frequently Asked Questions ==

= Q: For the actions "Tag(s): Add" and "Tag(s): Remove", what is the correct format for the Action data? =

A: Simply a comma-separated list just as you would do on the add/edit Post screen. (ex: "2008, photo")

= Q: Will filters be run on a post more then once? =

A: A post can have multiple filters run on it, but a specific filter cannot run on a specific post more then once. Depending on community feedback, the ability to let a filter run on a post more then once might be added to a future release.

= Q: How is the "Custom Field: Exists" Catch used? =

A: To use this, on any post that you want WordPress Filter to potentially engage on, just define the custom field with whatever name you setup as the Catch, then give it any value (the value "1" works perfectly well).

== Support ==

Please use the [Issues Page](http://code.google.com/p/wordpress-filter/issues/list) to report bugs and request feature enhancements.

== Changelog ==

*v1.3.6*

Changes:

* Added Catch -> Post Title: Contains
* Added Action -> Title: Replace Substring
* Added Action -> Content: Replace Substring
* Added Action -> Excerpt: Replace Substring

A special formatting of Action Data is needed to use Substring Replacements.  Let's say you wanted to replace all instances of "test" with "testing" inside the content area.  To do this:

* Select Content: Replace Substring as the action
* In Action Data enter: test=testing

The basic idea is you supply what you wish to have changed, then "=", followed by what you want to change it to.  I'd like to make it a little more user friendly eventually, but I needed this change for something I'm working on and thought I'd share it with you all as well in case it would be helpful.

*v1.3.3*

Enhancements:

* Fixed a couple of PHP notices that were occurring when the plugin was initially installed.

*v1.3 Release Notes*

Misc:

* Added ability to import/export filtersets.  Filters are validated during import process.
* Much backend work was done to validate adding filters and editing filters.  This should provide for a smoother experience in the UI.

*v1.2 Release Notes*

Misc:

* Improved UI (Tab interaction, text indicators, etc)
* Added confirmation for deleting filter from Current Filters view
* Added form validation
* Decreased database calls and improved sequence of actions being applied

Catches Added:

* Post Excerpt: Equals
* Post Excerpt: Contains
* Post Excerpt: Doesn't Contain

Actions Added:

* Excerpt: Prepend
* Excerpt: Replace
* Excerpt: Append
* Category(ies): Add -> Data supplied for this action should be a comma separated list of Categories by Category Name
* Category(ies): Remove -> Data supplied for this action should be a comma separated list of Categories by Category Name

Bug Fix:

* Fixed small bug with removing Catches or Actions from edit Filter form

*v1.1 Release Notes* -- Recommended upgrade due to small bug (see below)

Catches Added:

* Post Author: Equals -> The value supplied for this catch should be the Display Name from the users profile
* Comment Status: Equals -> Valid values: open, closed
* Ping Status: Equals -> Valid values: open closed

Actions Added:

* Comment Status: Equals -> Valid values: open, closed
* Ping Status: Equals -> Valid values: open, closed

Bug Fix:

* I ended up moving actions on Categories to v1.2 to go ahead and get this release out there.  There is a small bug in v1.0 that will allow a filter to be applied to a post more then once.  This bug has been fixed.
