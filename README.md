PGit PHP Git Library
====================

PGit is a library for accessing Git repositories written in pure PHP. Instead of using the normal fork-exec to query git repositories, PGit reads git's object files directly. The PGit library is licensed under the MIT and is free for commercial and personal use.

[![Build Status](https://travis-ci.org/zordtk/pgit.png?branch=master)](https://travis-ci.org/zordtk/pgit)

### Requirements
 * Only PHP v5.3 or greater
 
### Features
 * Pure PHP 5 object-oriented implementation.
 * Can read Commit, Tree, and Blob objects.
 * Supports reading from pack files and applying OFS deltas.
 * Verifies all hashes for consistency checks.
 * Open-Source and free for commercial and personal use.
 * Test suite to help protect against regressions.
 
### Warning
The API is VERY far from being stable and will change a lot over time.

### Limitations
 * Since PGit is very early in it's development, many things are not yet implemented.
 * Doesn't support 64-Bit offsets in pack files. Most pack files aren't going to be this large, even the linux kernel is only ~750MB.
 * Missing support for ref deltas
 
### Contributing
I would like to encourage anyone to contribute any sort of code, suggestions, or issue reports. 
