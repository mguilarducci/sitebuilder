Getting Started
===============

- https://github.com/meumobi/sitebuilder/wiki/MeuMobi-Concepts
- https://github.com/meumobi/sitebuilder/wiki/Best-Practices

Using Vagrant
=============

```
vagrant box add --name sitebuilder http://arpoador.ipanemax.com/vagrant/sitebuilder.box
vagrant up
```

Set the ENVIRONMENT
===================

```
echo "development" > config/ENVIRONMENT
```

Set the database config
=======================

```
cp config/connections{.sample,}.php
```
