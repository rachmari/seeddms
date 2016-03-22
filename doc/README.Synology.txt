*********************************************
How to set up SeedDMS Preview on Synology NAS
*********************************************

Introduction
############
SeedDMS provides a function creating a preview of each document which is displayed on the document page.

Synology stations do not support the creation of the previews by default due to a missing Ghostscript implementation. Therefore
loading of a document page can use a lot of time because SeedDMS tries to create the missing preview images each time the document
page is being loaded.

Prerequisites
#############
In order to complete the steps outlined below, you must be able to carry out the following tasks:

* Use the command line and know essential commands
* Install a 3rd party package system and install packages using this system

To complete the installation, the following prerequisites on your Synology must be met:

* IPKG or OPKG (OPKG preferred) installed
* Pear Package SeedDMS_Preview already installed

Installation and configuration
##############################

In the following steps, you will first install the required packages, followed by doing the neccesary configurations. These steps
must be done on the terminal.

Install Ghostscript
***************************

The first step is to install Ghostscript to make ImageMagick capable of converting PDF files to images. Use IPKG or OPKG to complete this
step.

Make Ghostscript available to PHP
*****************************************

To check where Ghostscript is installed run *which gs* to get the installation path. Now check if this path is visible to PHP. To check this,
use phpinfo and find **_SERVER["PATH"]**. If you can't find /opt inside, PHP can't see applications installed there. You can now either try to
update the paths or just make a symlink.
To create the symlink, cd to /usr/bin and type *ln -s /opt/bin/gs gs*. Verify the created symlink.

Fix Ghostscript package bug
****************************************

Unfortunately the version delivered by OPKG has a bug, making Ghostscript failing to work properly. The bug requries fixing at the time
of the writing are the following:

* Resource path pointing to a wrong version (9.10 instead of 9.16)

First, fix the resource path. Go to /opt/bin and find **gs** in there. Open the file with VI. Change the GS_LIB path from */opt/share/ghostscript/9.10/Resource*
to */opt/share/ghostscript/9.16/Resource*. This will now allow Ghostscript to find it's files in the proper path.

Fix ImageMagick
********************

Not only Ghostscript is affected by bugs, the default configuration files are missing. Unfortunately some work is required here as well.

To check where ImageMagick looks for it's files, invoke the command *convert -debug configure logo: null:*. You will see some paths shown, these
are the paths where ImageMagic tries to locate it's configuration files. The first path shown will point to */usr/share/ImageMagick-6* followed by the
name of an XML file. At the very end of the output you will see which configuration file has been loaded, in the default setting there will be an error.

Point to */usr/share* and check if you can find the **ImageMagick-6** directory. If is not present, create it. Cd into the directory.

Next step is to fill the directory with files. Use the following list to download the files (credit goes to Thibault, http://blog.taillandier.name/2010/08/04/mediawiki-sur-son-nas-synology/).

* wget http://www.imagemagick.org/source/coder.xml
* wget http://www.imagemagick.org/source/colors.xml
* wget http://www.imagemagick.org/source/configure.xml
* wget http://www.imagemagick.org/source/delegates.xml
* wget http://www.imagemagick.org/source/english.xml
* wget http://www.imagemagick.org/source/francais.xml
* wget http://www.imagemagick.org/source/locale.xml
* wget http://www.imagemagick.org/source/log.xml
* wget http://www.imagemagick.org/source/magic.xml
* wget http://www.imagemagick.org/source/mime.xml
* wget http://www.imagemagick.org/source/policy.xml
* wget http://www.imagemagick.org/source/thresholds.xml
* wget http://www.imagemagick.org/source/type-ghostscript.xml
* wget http://www.imagemagick.org/source/type-windows.xml
* wget http://www.imagemagick.org/source/type.xml

Testing
*************

Now you should be ready to test. Put a PDF file in a directory, cd into this directory.

To test convert directly, invoke the following command (replace file.pdf with your filename, replace output.png with your desired name):

**convert file.pdf output.png**

If everything goes well you should now receive a png file which can be opened. There may be a warning message about iCCP which can be ignored.

If you want to test Ghostcript as well, invoke the follwing command:

**gs -sDEVICE=pngalpha -sOutputFile=output.png -r144 file.pdf**

This command should go through without any errors and as well output a png file.

If the tests above are successful, you are ready to use SeedDMS Preview. Go to your SeedDMS Installation and open a folder. For the first test you
may take a folder with less files in it. Be patient while the previews are generated. You may check the process using *top* on the terminal.

At the end your document page should show the previews like shown below:

.. figure:: preview.png
   :alt: Document previews
   :scale: 75%
