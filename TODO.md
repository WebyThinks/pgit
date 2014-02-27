TODO
====

Below is a general list of the things I plan to implement in PGit. This list is not in any sort of order, it mostly depends on what I'm interested in at the time.

### Current TODO
 * Document all of the API for phpDocumentor.
 * Add support for reading tags.
 * Add samples to show how to use the API.
 * Implement ref deltas.
 * Add the ability to disable hash checking. Needs to be able to control each type of hash check (blob, pack, etc).
 * Change exceptions to a more specific type for each error. For example, create a exception class called InvalidHash and use that any time we encouter a bad hash.
 
### Future TODO
The following things may be implemented sometime in the future, if I get around to it.
 * Support for version 1 pack index file (if I can find a way to test it).
