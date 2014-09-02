Events portal application
=========================

This is a PHP application which implements an events portal system, enabling individuals, or (in 'organisations' mode, i.e. a directory implementation context), group managers to list events.


Screenshot
----------

![Screenshot](screenshot.png)


Usage
-----

1. Clone the repository.
2. Download the library dependencies and ensure they are in your PHP include_path.
3. Download and install the famfamfam icon set in /images/icons/
4. Add the Apache directives in httpd.conf (and restart the webserver) as per the example given in .httpd.conf.extract; the example assumes mod_macro but this can be easily removed.
5. Create a copy of the index.html.example file as index.html in the URL directory where the application will run from, and fill in the parameters.
6. Optionally, create a providers implementation (see below).
7. Access the page in a browser at a URL which is served by the webserver.


Providers infrastructure
------------------------

The system, when run in 'organisations' mode, requires a directory implementation ('providers') which acts as a gateway to the organisations that can create events.

_Fuller documentation to follow._



Dependencies
------------

* [application.php application support library](http://download.geog.cam.ac.uk/projects/application/)
* [database.php database wrapper library](http://download.geog.cam.ac.uk/projects/database/)
* [frontControllerApplication.php front controller application implementation library](http://download.geog.cam.ac.uk/projects/frontcontrollerapplication/)
* [image.php image library](http://download.geog.cam.ac.uk/projects/image/)
* [pureContent.php general environment library](http://download.geog.cam.ac.uk/projects/purecontent/)
* [ultimateForm.php form library](http://download.geog.cam.ac.uk/projects/ultimateform/)
* [FamFamFam Silk Icons set](http://www.famfamfam.com/lab/icons/silk/)
* Optionally, a directory implementation ('providers') - see above



Author
------

Martin Lucas-Smith, CUSU, 2007-14.
Martin Lucas-Smith, Department of Geography, University of Cambridge, 2013-14.


License
-------

GPL2.
