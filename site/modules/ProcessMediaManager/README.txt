ProcessWire Media Manager
================================

Copyright 2015 by Francis Otieno (Kongondo)

PLEASE DO NOT DISTRIBUTE
========================

This is a commercial ProcessWire module that is authorized only for
use in accordance with the attached licence (see the file 'module-name-licence-type.txt')
where 'licence-type' equates to the licence you purchased, for instance 'single'.

Support is provided only to the purchaser.

If you did not purchase this copy of Media Manager, you should obtain a copy from
the (author's) ProcessWire Marketplace at: http://processwireshop.pw/


ABOUT MEDIA MANAGER
==========================

Media Manager (MM) is a digital asset manager for your Media Library.
It provides a centralasided Media Library for all you media.
Currently these include: audio, document, image and video media types.

Media Manager is a powerful, rich, ajax-driven module that enables editors to reuse media across their sites
as many times as they wish. Editors can search for media using any of their properties (name, title, image tag,
description, etc.), select, add, remove and delete media in an easy to use and friendly interface. It presents
an AJAX-driven grid of matched media presented as thumbnails that you can select to add to your page. All media
(save for documents) can be previewed. This minimises the chance of uploading or inserting into a page, the
'wrong' media. Media can be added to your Media Library either by scanning previously FTP-uploaded media into your
Media Library, or by directly uploading them within your Library. Image media can be manipulated as any other media,
i.e. cropping and resizing. Via a (free) third-party module, Media Manager allows for client-size-image resizing; no more
unwanted giant-sized images. In addition, Media Manager enables you to quickly and easily see usage statistics of your
media across your website pages. Shipping with a number of permissions, fine-grained access-control ensures Media Manager
fits into your workflow no matter the size of your web-editing staff.

Media Manager will significantly enhance the way you work with media in ProcessWire.


REQUIREMENTS
============

ProcessWire 3 or newer.
The free ProcessWire module JqueryFileUpload (also by Francis Otieno).


DOCUMENTATION
=============

Full documentation and demos available at http://mediamanager.kongondo.com


HOW TO INSTALL MEDIA MANAGER
============================

JQueryFileUpload
----------------

Before installing Media Manager, make sure you install the module JqueryFileUpload
(in a similar fashion to below). The module can be downloaded from:
http://modules.processwire.com/modules/jquery-file-upload

Media Manager
-------------

The ZIP file that the module comes in can be uploaded directly to
your admin in Modules > New > Upload. If your modules file system is
not writable, you can also install it this way:

1.	Copy the Media Manager files into the directory:
	/site/modules/MediaManager/. Please note that the module
	consists of 4 modules:
		i)   ProcessMediaManager
		ii)  MediaManagerImageEditor
		iii) FieldtypeMediaManager
		iv)  InputfieldMediaManage

2.	In your ProcessWire admin, go to Modules and click "Refresh".

3.	Click "Install" for the Media Manager (ProcessMediaManager) module.
	This will automatically install the other 3 modules.

Following installaton, you will need to set up your non-Superusers to be
able to use the module.

Access:
During install, Media Manager creates two permissions
i) media-manager: Allows a non-Superuser to view and use Media Manager
ii) media-manager-settings: Allows a non-Superuser to edit Media Manager upload settings

Create a role (for example 'editor') and assign it at least the permission 'media-manager'.
Give your user the role with the permission 'media-manager'.
If you want that user to be able to change upload settings, you need to give their role
the permission 'media-manager-settings'. Make sure you understand the implications of this
before assigning them such access.

Template-level access:
Next, you will need to set up template-level permissions to complete access setup.
Still in ProcessWire admin, head over to /setup/templates/ and expand the
group of templates tagged under 'mediamanager'. You will find 6 templates
	i)   media-manager
	ii)  media-manager-audio
	iii) media-manager-document
	iv)  media-manager-image
	v)   media-manager-video
	vi)  media-manager-setting

Open each of these templates for editing.

For the template 'media-manager':
	a) in the Access Tab, make sure 'Yes' is selected under managing view and edit access for
	   pages using that template.
	b) Under the roles that can access pages using the template, in the row with the role you created above, tick
	  'view pages' and 'edit pages'.
	c) In the section about pages being searchable when user has no access, select 'Yes'.
	d) Also select 'Yes' under the setting to allow edit-related access to inherit to children.

Repeat a, and b for the remaining 5 templates.

The user will now be able to user Media Manager.


HOW TO USE YOUR MEDIA LIBRARY (ProcessMediaManager)
===================================================

Using and finding your way round the Media Library is very easy. The interface allows you to perform various actions
including editing your media, previewing them, (un)publishing, (un)locking, trashing and deleting your media.
You can also easily upload media to your Media Library using the same interface. The Library incorporates a powerful
search engine to search for media within your Media Library using different criteria such as their tags, descriptions,
date modified, created, etc.


ENABLING TAGS FOR MEDIA
=======================

Currently (due to a possible bug in ProcessWire), you need to manually enable tags in each of the following Media Manager
Fields. These fields were created when the module was installed. However, enabling tags via the API leads to errors that will
prevent saving media tags.

	i)    media_manager_audio
	ii)   media_manager_document
	iii)  media_manager_image
	iv)   media_manager_video


HOW TO ADD MEDIA TO YOUR PAGES
==============================

For this you will need FieldtypeMediaManager which was installed when you installed the module
as outlined above. Create a field of type 'MediaManager' and add it to your desired template. You will
see a link to 'Add Media' to your page when editing a page that uses a template that has a Media Manager
field. Clicking on that link will open up a modal displaying your Media Library. Users will be able to
search for, select (by clicking on a media's thumb) and insert selected media (single or multiple) into
the page being edited. Using the same modal, users will also be able to upload (assuming no 'media-manager-upload'
permission restriction in place [see below]) a media to the Media Library which they could then insert in the page.
Once you click on the button 'Insert Media', the media will automatically be inserted in the page being edited. You
will not need to save or reload the page once you close the modal. These will be done automatically.

Media inserted in a page can be viewed in either a grid (less details) or list (with extra media details) format. They can
also be sorted by dragging and dropping and selected for deletion (saving the page is required here).

You can have as many MediaManager fields in your template as you wish. For instance, you may wish to have different MediaManager
fields for different types (or even categories) of media.


DISPLAYING MEDIA ATTACHED TO PAGES IN THE FRONTEND
==================================================

Accessing and outputting the contents of the MediaManager field(s) in your template is quite simple.
The fields are accessed like many other ProcessWire fields. The fields return an array of type MediaManagerArray
that need to be looped to output each media within. Assuming you created a field of type MediaManager named 'media',
you can loop through it for a given page as follows:

/*
	@note:
	Each MediaManager object has the following 5 basic properties:

	DATABASE (saved properties)
	1. id => pageID of the page where the media lives
	2. type => integer denoting media type (1=audio; 2=document; 3=image [for variations this will be 3x, where x is the number of the variation of an original image]; 4=video)

	RUNTIME
	3. typeLabel => user friendly string denoting media type (audio, document, image, video)
	4. media => a ProcessWire Image/File Object including all their properties (ext, filesizeStr, height, width, description, tags, filename, basename, etc.)
	5. title => title of media (@note: this is the title of the page with the media; not the name of the file itself)
*/

$media = $page->media;

foreach ($media as $m) {
	echo $m->title . '<br>';// e.g. 'My Awesome Trip'
	#echo $m->media->tags . '<br>';
	#if($m->typeLabel =='image') echo $m->media->ext;
	/*if($m->typeLabel =='image') {
		echo "<img src='{$m->media->size(100,75)->url}'><br>";
	}
	*/
}


UPGRADES
========

Upgrades are made available via email.

To install an upgrade you would typically just replace the old files
with the new. However, there may be more to it, depending on the version.
Always follow any instructions provided with the upgrade version.


MEDIA MANAGER VIP SUPPORT
================================

Your Media Manager Licence service includes 1-year of VIP support.

VIP support is available via email: kongondo@gmail.com


HAVE QUESTIONS OR NEED HELP?
============================

Send an email to kongondo@gmail.com.


Thanks for using Media Manager!

---

ProcessWire Media Manager
Copyright 2015 by Francis Otieno
