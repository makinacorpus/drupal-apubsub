Async PubSub testing
====================

PHPUnit is necessary for testing.

Drupal
------

In order to be able to unit test any Drupal version, you need to tell to set
the following environment variable:

 * __DRUPAL_PATH__ : Path to an installed and working Drupal 7 *index.php* file.
   This Drupal site must be installed and running, and have the __apb__ module
   enabled.

Another important notice: testing on a Drupal site will mess up with its
database, ensure that you are not using a testing or development site for this.
