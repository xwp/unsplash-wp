# Unsplash for WordPress

[![Build Status](https://travis-ci.com/xwp/unsplash-wp.svg?token=DzyA3Sey2BLS5sL6HDJq&branch=master)](https://travis-ci.com/xwp/unsplash-wp)
[![Coverage Status](https://coveralls.io/repos/github/xwp/unsplash-wp/badge.svg?branch=master&t=mLvdmf)](https://coveralls.io/github/xwp/unsplash-wp?branch=master)

## Requirements

- WordPress 5.0+ or the [Gutenberg Plugin](https://wordpress.org/plugins/gutenberg/).
- PHP 7.4 or greater for development and 5.6 or greater for production, [Composer](https://getcomposer.org) and [Node.js](https://nodejs.org) for dependency management.
- [Docker](https://docs.docker.com/install/) for a local development environment.

We suggest using a software package manager for installing the development dependencies such as [Homebrew](https://brew.sh) on MacOS:

	brew install php composer node docker docker-compose

or [Chocolatey](https://chocolatey.org) for Windows:

	choco install php composer node nodejs docker-compose

## Development

1. Clone the plugin repository.

		git clone git@github.com:xwp/unsplash-wp.git

2. Setup the development environment and tools using [Node.js](https://nodejs.org) and [Composer](https://getcomposer.org):

		npm install

	_Note that both Node.js and PHP 7.4 or greater are required on your computer for running the `npm` scripts. Use `npm run docker -- npm install --unsafe-perm` to run the installer inside a Docker container if you don't have the required version of PHP installed locally._

3. Update the `.env` file with the Unsplash API app ID and secret.

## Development Environment

This repository includes a WordPress development environment based on [Docker](https://docs.docker.com/install/) that can be run on your computer.

To use the Docker based environment with the Docker engine running on your host, run:

	docker-compose up

which will make it available at [localhost](http://localhost). Ensure that no other Docker containers or services are using port 80 or 3306 on your machine. 

Use the included wrapper command for running scripts inside the Docker container:

	npm run docker -- npm run test:php

where `npm run test:php` is any of the scripts you would like to run.

Visit [localhost:8025](http://localhost:8025) to check all emails sent by WordPress.

Add the following entry to your hosts file if you want to map `localhost` to a domain like [unsplash-wp.local](http://unsplash-wp.local).

	127.0.0.1 unsplash-wp.local

### Scripts

We use `npm` as the canonical task runner for the project. Some of the PHP related scripts are defined in `composer.json`.

All of these commands can be run inside the Docker container by prefixing the scripts with `npm run docker --`.

**Important**: The commands that generate coverage reports or merge them (i.e. contain `coverage` in the name) must run inside the Docker container.

- `npm run build` to build the plugin JS and CSS assets. Use `npm run dev` to watch and re-build as you work.

- `npm run lint:js` to lint JavaScript files with [eslint](https://eslint.org/).

- `npm run lint:php` to lint PHP files with [phpcs](https://github.com/squizlabs/PHP_CodeSniffer).

- `npm run test:php` to run PHPUnit tests without generating a coverage report.

- `npm run docker -- npm run test:php:coverage` to run the PHPUnit tests and generate multiple `coverage-php` `.cov` reports.

- `npm run docker -- npm run coverage:merge:php` to merge the `.cov` reports and generate a `clover.xml` and `html` report.
