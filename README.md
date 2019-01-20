# TYPO3 Extension "sys_action"

This TYPO3 allows to run some TYPO3 Backend admin tasks like creation of new
Backend users as a non-admin user if properly set up. It hooks into
extension "taskcenter" for the GUI part.

The functionality was part of the TYPO3 Core until TYPO3 v10.0, and moved into its
own extension, receiving its own public repository.

## Installation
The latest version can be installed via TER (http://typo3.org) or via composer
by adding ''composer require friendsoftypo3/sys_action'' in a TYPO3 v10.0+ installation.

## Current state
The extension has been part of TYPO3 Core for a very long time but never
received much love. It may become more healthy if maintained as third party
extension and has been extracted for this reason.

## Contribution
Feel free to submit any pull request, or add documentation, tests, as you please.
We will publish a new version every once in a while, depending on the amount of changes
and pull requests submitted.

### License
The extension is published under GPL v2+, all included third-party libraries are
published under their respective licenses.

### Authors
Many contributors have been working on this area while this functionality was part of
the TYPO3 Core. This package is now maintained by a loose group of TYPO3 enthusiasts inside
the TYPO3 Community. Feel free to contact Benni Mack (benni.mack@typo3.org) for any questions
regarding "sys_action".