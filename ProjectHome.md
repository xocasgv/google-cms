Despite its simplicity google-cms provides interesting features. Since Google Docs is used as a text editor we get:
  * Wysiwyg
  * Images import and resizing
  * Find and replace
  * Equations
  * Infinite undo (revisions)
  * Spell checker (37 languages)
  * Collaborative features: simultaneous edition, chat and comments
  * Highly secure login: SSL, Captcha and password recovery
  * Most navigators supported (included Android and iOS)
  * Trusted product which has already been tried and tested, and keeps being maintained by Google.

The unique function of this CMS consists in synchronizing Google Docs with a web server. Each GDocument corresponds to a web page. Few treatments are preformed during the transition from Google Docs to the web site.
  * A very basic template allows to assemble the page content with an existing design
  * Mail anti-spam protection using javascript ROT13 encryption

Pages are then stored on the web server using the Google Docs' naming hierarchy. For example the page "Notre université" in the folder "Contact" will be stored in "/contact/notre-universite.php", thus providing:
  * Friendly URLs

Files are also synchronized, so the whole web site can be stored on Google Docs and new files can be uploaded via a web browser:
  * Incremental backup of the entire site on the Google cloud
  * File upload with drag'n'drop, progress bar and multiple elements support

The Google Doc's file sharing can be used to share the google-cms.php code between all the powered sites:
  * Auto-update of the CMS