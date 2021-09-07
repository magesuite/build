# Build package for MageSuite projects

Contains **phing** automation 
for running builds of projects.

The docker container used for running the tests can be found
on [our GitHub](https://github.com/magesuite/docker-run-magento-tests)
and [Docker Hub](https://hub.docker.com/r/magesuite/run-tests).

# Documentation

## Prerequisites

Following software has to be installed on host machine in order for all functions to work properly.

```
phing
php
composer
patch
docker
automake
autoconf
npm
yarn
gcc
gcc-c++ 
make
```

## What does the build do

* composer update
* build selected frontend themes
* run all tests

## build.xml

Before build can be executed `build.xml` file must be created and placed in main directory.

It must contain basic configuration that consists of:
* project name
* docker container name for running unit and integration tests
* comma separated list of all themes that should be built

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project name="{{PROJECT_NAME}}" default="help" basedir="." description="{{PROJECT_NAME}} build definition">
    <import file="build/build.xml"/>

    <property name="themes" value="creativeshop,{{PROJECT_THEMES_NAMES}}"/>
    <property name="project" value="{{PROJECT_NAME}}"/>
    <property name="testing_docker_tag" value="php74-es7-mariadb104-stable" override="true"/>
    <property name="testing_docker_image" value="mageops/magento-run-tests" override="true"/>
</project>
```

Placeholders should be replaced with:

* `{{PROJECT_NAME}}` - name of a project, example: `toys-shop`
* `{{PROJECT_THEMES_NAMES}}` - comma separated list of names of all custom themes that should be built, contained in `vendor/creativestyle` directory. 
Name must be provided without `theme-` prefix, for `vendor/creativestyle/theme-toys-shop` placeholder value will become `toys-shop` 

## Running build

In order to run build execute following commands:
```bash
[[ -d "vendor" ]] || composer update
php -d memory_limit=-1 vendor/bin/phing ci-build
```
