This is a customized version of the open-source [SeedDMS 5.0.1](https://sourceforge.net/projects/seeddms/files/seeddms-5.0.1/) web application. I've forked this version of [SeedDMS 5.0.1](https://sourceforge.net/projects/seeddms/files/seeddms-5.0.1/) on
SoureForge to GitHub because there are several core customizations that are currently not able to be
contributed back to the original project. It is my hope to contribute back several feature implementations in the future.

# SeedDMS Features
* Each revision of a document type can contain these files:
 * Source file (.pdf, .doc, .docx, .odt, .rtf, .ppt, .pptx, .odp)
 * PDF copy of the source file (.pdf)
 * Attachment files (.txt, .csv, .xls, .xlt, .xlsm, .xlsx, .xlsb, .xltx, .xltm, .ods, .bmp, .gif, .jpeg, .jpg, .png, .tiff, .vsd)
* Documents are versioned.
 * ``**`` indicates the first version, and ``*A`` indicates the second version, and the numbering continues to *B, *Z, AA, AB.
* Documents are numbered using the logged in user's username and a number. For example jane.smith-1. 
* Any available memo number can be specified when creating a new document. By default, the next available sequetial number will be used. For example, if jane.smith only has jane.smith-1 submitted, she can then submit jane.smith-9. When Jane submits her third memo, the default number will be jane.smith-2.
* Documents can cross-reference another document that has already been submitted.
* Each document lists its cross-references and citations.
* Documents can have a distribution list, and email notifications are sent to the list when new versions are submitted.

# Updates to the original SeedDMS
* Attachments can now be uploaded for each version of a document.
* You no longer interface with a folder system to retrieve documents. Instead, to find a document you search for it by name, owner, or keywords.
* Currently there are two types of documents: memos and specifications (specs).
  * Memos contain information from one person. For example, an engineer's analysis of a problem, proposal for a new innovation, or to document the completion of a task. Only the author of a memo can revise it.
  * Specs are typically the product of a collaborative team. Only one person can own the document at any time, to regulate the submission process. Specs can be transferred to new owners. Examples of a spec are product requirements, business processes, application notes, or technical reference manuals. Specs require an approval workflow before being released into the document management system, where they can then be distributed to customers or employees. This version does not yet support workflows, consequently this version of SeedDMS does not yet allow submitting new specs. Imported specs are supported.
  
# SeedDMS Installation Settings

There are two ways to install SeedDMS. One is using an install tool that will be run when a file called `ENABLE_INSTALL_TOOL` is located in the /var/www/html/conf directory.

```
touch /var/www/html/conf/ENABLE_INSTALL_TOOL
```

This install tool takes input from a web form, when you navigate to the root address in the browser. This tool takes input from the browser and stores it to a file called `/var/www/html/conf/settings.xml`. This tool is limited in the settings that it allows you to configure, but it provides some checks to ensure that you have installed all of the packages that SeedDMS requires.

It's recommended to perform a manual install by setting the configuration values in the `/var/www/html/settings.xml` file to ensure you have configured all available settings like ldap and SMTP. To do this copy the /var/www/html/settings.xml.template file and review all available settings and save the file as `settings.xml`. Defaults are already set for the required parameters and work seamlessly with [seedBox](https://github.com/rachmari/seedBox). If you have a server that has a different directory structure than [seedBox](https://github.com/rachmari/seedBox) you will need to update your path settings.

## SeedDMS Modules

[seedBox](https://github.com/rachmari/seedBox) documents are requirements to install the SeedDMS Core in the provisioning script. SeedDMS is split into 3 main modules: SeedDMS Core, SeedDMS Lucene, and SeedDMS Preview. This version of SeedDMS only modifies the Core module. The [seedBox](https://github.com/rachmari/seedBox) provisioning script installs the Lucene and Preview modules using Pear. This version of SeedDMS does not modify the Lucene and Preview modules. The original [SeedDMS 5.0.1](https://sourceforge.net/projects/seeddms/files/seeddms-5.0.1/) are used.

## Data Storage

Memos and specs are stored in a directory outside of the http root directory called `data` by default. This name is customizable in the `settings.xml` file. A content directory is located within the data directory named `1048576` by default. This directory name can also be changed in the `settings.xml` file. When documents are uploaded using SeedDMS, the files are stored in the `data/1048576` directory. 

The directories within `1048576` are also numbered. The number corresponds to the document `id` in the mysql table `tblDocuments`. The document `id` directory is a flat directory that contains all of a document's files (including attachments) for every version of that documet. Files are named using numbers and letters. File names that only contain a number indicate the version of the document. A file prefixed with `p` is the pdf version of the source file. A file prefixed with `f` is an attachment file, and number corresponds to the file `id` in `tblDocumentFiles`. A file prefixed with `fp` is a pdf version of an attachment file, and the number also corresponds to the file `id` in `tblDocumentFiles`.
