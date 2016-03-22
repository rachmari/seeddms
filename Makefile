VERSION=5.0.1
SRC=CHANGELOG inc conf utils index.php languages views op out controllers doc drop-tables-innodb.sql styles js TODO LICENSE Makefile webdav install restapi
# webapp

EXTENSIONS := \
	dynamic_content.tar.gz\
	login_action.tar.gz\
	example.tar.gz

PHPDOC=~/Downloads/phpDocumentor-2.8.1/bin/phpdoc

dist:
	mkdir -p tmp/seeddms-$(VERSION)
	cp -a $(SRC) tmp/seeddms-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../seeddms-$(VERSION).tar.gz seeddms-$(VERSION))
	rm -rf tmp

pear:
	(cd SeedDMS_Core/; pear package)
	(cd SeedDMS_Lucene/; pear package)
	(cd SeedDMS_Preview/; pear package)
	(cd SeedDMS_SQLiteFTS/; pear package)

webdav:
	mkdir -p tmp/seeddms-webdav-$(VERSION)
	cp webdav/* tmp/seeddms-webdav-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../seeddms-webdav-$(VERSION).tar.gz seeddms-webdav-$(VERSION))
	rm -rf tmp

webapp:
	mkdir -p tmp/seeddms-webapp-$(VERSION)
	cp -a restapi webapp tmp/seeddms-webapp-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../seeddms-webapp-$(VERSION).tar.gz seeddms-webapp-$(VERSION))
	rm -rf tmp

dynamic_content.tar.gz: ext/dynamic_content
	tar czvf dynamic_content.tar.gz ext/dynamic_content

example.tar.gz: ext/example
	tar czvf example.tar.gz ext/example

login_action.tar.gz: ext/login_action
	tar czvf login_action.tar.gz ext/login_action

extensions: $(EXTENSIONS)

doc:
	$(PHPDOC) -d SeedDMS_Core --ignore 'getusers.php,getfoldertree.php,config.php,reverselookup.php' --force -t html

apidoc:
	apigen  generate -s SeedDMS_Core --exclude tests --skip-doc-prefix tests -d html

.PHONY: webdav webapp
