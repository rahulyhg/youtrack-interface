[![Circle CI](https://circleci.com/gh/juno-media/youtrack-csv/tree/master.svg?style=svg)](https://circleci.com/gh/juno-media/youtrack-csv/tree/master)

#### installation
* composer install 
* make sure the 'timings', 'cache', 'uploads' and 'export' folders have apache/nginx read & write permissions
* fill in he details in customSettings.php, its very important change the cookie encryption key for security reasons.
* run composer install

#### notes
Custom fields may not have a ¬ in the name for custom fields form to work.

Time tracker keeps a copy of all form edits on the server, to stop browser cache clearence removing info. Each time a section of the form is updated a new copy of the information is saved. 

#### requirements
Composer
PHP5+