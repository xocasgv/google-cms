**Google-CMS**
==============
A minimalist Content Management System based on Google Docs


Introduction
------------
**What's a CMS?**   
*A web content management system is a software system that provides website authoring, collaboration, and administration tools designed to allow users with little knowledge of web programming languages to create and manage website content with relative ease. [...] A WCMS typically requires a systems administrator and/or a web developer to set up and add features, but it is primarily a website maintenance tool for non-technical staff.* -- [Wikipedia](http://en.wikipedia.org/wiki/Web_content_management_system)

**Is GoogleCms for you?**   
GoogleCms is made be the interface between the website maker and the final user. If you don't know how to make a website GoogleCms is not going to help you at all.

**Why use this product rather than another one?**   
One word: simplicity. Most the CMS products pretend to be simple. Well, let's compare:

	Joomla			380000 lines
	Cms Made Simple	370000 lines
	Drupal			290000 lines
	WordPress		230000 lines
	CMSFS			94000 lines
	Symphony		76000 lines
	CMSimple		69000 lines
	Pixie			62000 lines
	Textpattern1	45000 lines
	Frog CMS		36000 lines
	GetSimple CMS	17000 lines
	GoogleCms		500 lines

Don't belive it? Keep on reading and you will see.   
(source: ` find . -type f -not -name "*.[jpgs][pniw][gf]" -exec wc -l {} +` the 07/09/11 on each projects source code.)


How it works
------------
GoogleCms is centred around Google Doc's text editor and file manager. The idea is to syncronise a Google Doc's folder with your webserver. Files are downloaded and Google Documents are turned into proper webpages. This webpages are stored as .php files with this layout:

<?php
    $contentHtml = '<h2 class="c1"><span>html content</span></h2>';
    $contentCss = '.c1{someCss;}';
    include('path/to/the/template.php');
?>

This allows to use the template.php to assemble the pages as needed. In addition to this manualy trigged global synchronization there is also a page by page update check that's performed at each page load.

Durring the synchronization there is also the generation of a PHP tree representing the hierarchy of the Google Doc file system. This tree is accesible from the $menuTree variable and is ment to be used in the template.php to generate a menu.


Features
--------
Despite its simplicity GoogleCms provides interesting features. Since Google Docs is used as a text editor we get:
  *  Wysiwyg
  *  Images import and resizing
  *  Find and replace
  *  Equations
  *  Infinite undo (revisions)
  *  Spell checker (37 languages)
  *  Collaborative features: simultaneous edition, chat and comments
  *  Highly secure login: SSL, Captcha and password recovery
  *  Most navigators supported (included Android and iOS)
  *  Trusted product which has already been tried and tested, and keeps being maintained by Google.

The unique function of this CMS consists in synchronizing Google Docs with a web server. Each GDocument corresponds to a web page. Few treatments are preformed during the transition from Google Docs to the web site.
  *  A very flexible template system allows to assemble the page content with any existing design
  *  Mail anti-spam protection using javascript ROT13 encryption

Pages are then stored on the web server using the Google Docs' naming hierarchy. For example the page "Notre université" in the folder "Contact" will be stored in "/contact/notre-universite.php", thus providing:
  *  Friendly URLs

Files are also synchronized, so the whole web site can be stored on Google Docs and new files can be uploaded via a web browser:
  *  Incremental backup of the entire site on the Google cloud
  *  File upload with drag'n'drop, progress bar and multiple elements support

The Google Doc's file sharing can be used to share the GoogleCms php code between all the powered sites:
  *  Auto-update of the CMS


Setting up the example
----------------------
This section contains instructions on how to set up the example site.
1) Upload all files from the src and the example folder to a googleCmsTester folder on your web server.
2) Go to http://yourWebSite.com/example/googlecms.php
3) The pages sould now be downloaded from the example Google Docs account to your web server, you can start browsing it from http://yourWebSite.com/example/.

Starting from scratch
---------------------
Google Docs
header.php/footer.php


Security considerations
-----------------------

FAQ
---